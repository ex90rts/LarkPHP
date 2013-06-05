<?php
namespace Flexper;

use Flexper\Mysql\Query;
use Flexper\Exception\MissingConfigException;
use Flexper\Exception\CloneNotAllowedException;
use Flexper\Exception\MysqlConnectFailedException;
use Flexper\Exception\MysqlNoEnabledTransException;
use Flexper\Exception\MysqlNoConnectionException;
use Flexper\Exception\MysqlConfigException;

class Mysql{

	/**
	 * Singleton instance of Mysql
	 * @var \Flexper\Mysql
	 */
    private static $_instance;

	/**
	 * Pool to cache different mysql connections
	 * @var \Array
	 */
    private $_connPool;

    /**
     * Current mysql connection instance
     * @var \Mysqli\Connection
     */
    private $_currConn;

	/**
	 * Current Query Servers
	 * @var \Array
	 */
    private $_server = array();

    /**
     * Current Query Tables
     * @var \Array
     */
    private $_tables = array();

    /**
     * Is current query transactional
     * @var Boolean
     */
    private $_isTrans = false;

    /**
     * Former mysql connection uniq key
     * @var String
     */
    private $_lastConnKey = null;

    /**
     * Former mysql connection instance
     * @var \Mysqli\Connection
     */
    private $_lastConn = null;

    /**
     * Query counts in one transaction peroid
     * @var int
     */
    private $_holdQueries = 0;

    /**
     * Private construct function for singleton
     * Read mysql options from config mysql
     *
     * @throws MissingConfigException
     * @throws MysqlConfigException
     */
    private function __construct(){
        $this->_connPool = array();
        $this->_currConn = null;

        $config = Env::getInstance('Flexper\Config');
        if (!isset($config->mysql)){
            throw new MissingConfigException('missing mysql config');
        }

        if (!isset($config->mysql['server']) || !isset($config->mysql['tables'])){
            throw new MysqlConfigException('empty server or tables');
        }

        $this->_server = $config->mysql['server'];
        $this->_tables = $config->mysql['tables'];
    }

    /**
     * Static method to get singleton instance of this class
     * @return \Flexper\Mysql
     */
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }

    /**
     * Forbid to clone new instance
     * @throws CloneNotAllowedException
     */
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }

    /**
     * Get current connection info from \Flexper\Query instance
     * @param \Flexper\Query $query
     * @throws MysqlConfigException
     * @return Ambigous <mixed, multitype:string unknown Array >
     */
    private function getConnInfo(Query $query){
        $tables = $query->tables;
        $action = $query->action;
        $hashKey = $query->hashKey;

        $connInfo = array();
        $exception = null;
        while (!empty($tables)){
            $table = array_shift($tables);
            if (!isset($this->_tables[$table])){
                $exception = sprintf('table %s not configed', $table);
                break;
            }
            $tbconf = $this->_tables[$table];
            if (!isset($tbconf['server'])){
                $exception = sprintf('table %s has no server configed', $table);
                break;
            }

            $server = $tbconf['server'];
            if (!is_array($tbconf['server'])){
                $server = array($tbconf['server']);
            }
            $serverNum = count($server);

            $dbnum = $tbconf['dbnum'];
            if (!isset($tbconf['dbnum']) || !is_numeric($tbconf['dbnum'])){
                $dbnum = 1;
            }

            if ($serverNum>$dbnum){
                $exception = sprintf('table %s has disted to more than one servers but dbnum is less than server amount', $table);
                break;
            }
            if ($serverNum>1 && empty($hashKey)){
                $exception = sprintf('table %s has more than one databases, but no hash key given', $table);
                break;
            }

            //No table hash, just return single table connction info
            if ($serverNum==1 && $dbnum==1 && !isset($tbconf['hash'])){
                $targetServer = current($server);
                if (!isset($this->_server[$targetServer])){
                    $exception = sprintf('target server %s for table %s not defined', $targetServer, $table);
                    break;
                }

                $serverInfo = $this->_server[$targetServer];
                if ($action==Query::ACT_SELECT && isset($serverInfo['slave'])){
                    if (!isset($this->_server[$serverInfo['slave']])){
                        $exception = sprintf('slave db server not found for table %s, server %s', $table, $targetServer);
                        break;
                    }
                    $serverInfo = $this->_server[$serverInfo['slave']];
                }

                $connInfo['server'] = $serverInfo;
                $connInfo['database'] = $serverInfo['name'];
                $connInfo['tables'][$table] = $table;

                $connKey = "{$serverInfo['host']}:{$serverInfo['port']}:{$serverInfo['name']}";
                if (!empty($this->_lastConnKey) && $connKey!=$this->_lastConnKey){
                    $exception = sprintf('not support cross server and database action for servers: %s, %s', $this->_lastConnKey, $connKey);
                    break;
                }

                $connInfo['connKey'] = $connKey;

                $this->_lastConnKey = $connKey;

                continue;
            }

            //While need to hash the data
            if (!isset($tbconf['hash'])){
                $exception = sprintf('table %s needs to hash but not hash configuration given', $table);
                break;
            }

            if ($tbconf['hash']['type']=='mod' && is_numeric($tbconf['hash']['seed'])){
                $seed = intval($tbconf['hash']['seed']);
                if ($seed<$dbnum){
                    $exception = sprintf('table %s seed is less than dbnum', $table);
                    break;
                }
                $tableId = $hashKey % $seed;
                $dbId = floor($tableId/($seed/$dbnum));
                $serverId = floor($dbId/($dbnum/$serverNum));
                $targetServer = $server[$serverId];
                if (!isset($this->_server[$targetServer])){
                    $exception = sprintf('target server %s for table %s not defined', $targetServer, $table);
                    break;
                }

                $serverInfo = $this->_server[$targetServer];
                if ($action==Query::ACT_SELECT && isset($serverInfo['slave'])){
                    if (!isset($this->_server[$serverInfo['slave']])){
                        $exception = sprintf('slave db server not found for table %s, server %s', $table, $targetServer);
                        break;
                    }
                    $serverInfo = $this->_server[$serverInfo['slave']];
                }

                $connInfo['server'] = $serverInfo;
                $connInfo['database'] = "{$serverInfo['name']}{$dbId}";
                $connInfo['tables'][$table] = "{$table}{$tableId}";

                $connKey = "{$serverInfo['host']}:{$serverInfo['port']}:{$connInfo['database']}";
                if (!empty($this->_lastConnKey) && $connKey!=$this->_lastConnKey){
                    $exception = sprintf('not support cross server and database action for servers: %s, %s', $this->_lastConnKey, $connKey);
                    break;
                }

                $connInfo['connKey'] = $connKey;

                $this->_lastConnKey = $connKey;
            }else{
                $exception = sprintf('table %s hash method not support', $table);
                break;
            }
        }

        if (!empty($exception)){
            throw new MysqlConfigException($exception);
        }

        return $connInfo;
    }

    /**
     * Execute query
     * @param Query $query
     * @throws MysqlConnectFailedException
     * @throws \Exception
     * @return \mysqli_result
     */
    public function exec(Query $query){
        $connInfo = $this->getConnInfo($query);
        $connKey = $connInfo['connKey'];
        if (!isset($this->_connPool[$connKey])){
            $this->_connPool[$connKey] = new \mysqli($connInfo['server']['host'], $connInfo['server']['user'], $connInfo['server']['pass'], $connInfo['database'], $connInfo['server']['port']);
        }

        if ($this->errno()){
            throw new MysqlConnectFailedException(sprintf('falied try to connect %s, error %d:%s', $connKey, $this->errno(), $this->error()));
        }

        $mysqli = $this->_connPool[$connKey];

        if ($this->_isTrans && $this->_holdQueries==0){
            $mysqli->autocommit(false);
        }
        if ($this->_isTrans && !empty($this->_lastConnKey) && $connKey!=$this->_lastConnKey){
            throw new \Exception('not support transactions cross different db servers');
        }

        $this->_lastConnKey = $connKey;
        $this->_lastConn = $mysqli;

        $sql = $query->makeSql();
        $mapTables = $query->tables;
        $realTables = $connInfo['tables'];
        foreach ($mapTables as $mapTable){
            $sql = str_replace("@{$mapTable}", $realTables[$mapTable], $sql);
        }
        
        $result = $mysqli->query($sql);
        if ($query->action==Query::ACT_SELECT){
            if ($result instanceof \mysqli_result){
                $result = $result->fetch_all();
            }
        }elseif ($query->action==Query::ACT_INSERT){
            if ($query->insertId){
                $result = $mysqli->insert_id;
            }
        }else{
            if ($query->affectedRows){
                $result = $mysqli->affected_rows;
            }
        }

        if ($this->_isTrans){
            $this->_holdQueries++;
        }

        return $result;
    }

    /**
     * Start a transaction
     */
    public function transaction(){
        $this->_isTrans = true;
    }

    /**
     * Commit a transaction
     * @throws MysqlNoConnectionException
     * @throws MysqlNoEnabledTransException
     */
    public function commit(){
        if (empty($this->_lastConn)){
            throw new MysqlNoConnectionException('connection not valid when commit a transaction');
        }
        if (!$this->_isTrans){
            throw new MysqlNoEnabledTransException();
        }

        $this->_isTrans = false;
        $this->_holdQueries = 0;

        return $this->_lastConn->commit();
    }

    /**
     * Rollback a transaction
     * @throws MysqlNoConnectionException
     * @throws MysqlNoEnabledTransException
     */
    public function rollback(){
        if (empty($this->_lastConn)){
            throw new MysqlNoConnectionException('connection not valid when rollback a transaction');
        }
        if (!$this->_isTrans){
            throw new MysqlNoEnabledTransException();
        }

        $this->_isTrans = false;
        $this->_holdQueries = 0;

        return $this->_lastConn->rollback();
    }

    /**
     * Return last error number
     */
    public function errno(){
        return mysqli_connect_errno();
    }

    /**
     * Return last error message
     */
    public function error(){
        return mysqli_connect_error();
    }

    /**
     * Close mysql connection
     * @param \mysqli $conn
     */
    private function close(\mysqli $conn){
        $conn->close();
    }

    /**
     * Destruct function, close mysql connection if exist
     */
    public function __destruct(){
        if (!empty($this->_connPool)){
            foreach ($this->_connPool as $conn){
                $this->close($conn);
            }
        }
    }
}