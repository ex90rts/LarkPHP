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
    
    private static $_instance;
    
    private $_connPool;
    
    private $_currConn;
    
    private $_server = array();
    
    private $_tables = array();
    
    private $_isTrans = false;
    
    private $_lastConnKey = null;
    
    private $_lastConn = null;
    
    private $_holdQueries = 0;
    
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
    
    public static function getInstance(){
        if (!self::$_instance){
            $class = __CLASS__;
            self::$_instance = new $class();
        }
        return self::$_instance;
    }
    
    public function __clone(){
        throw new CloneNotAllowedException(sprintf('class name %s', __CLASS__));
    }
    
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
            echo "auto commit is false\r\n";
            $mysqli->autocommit(false);
        }
        if ($this->_isTrans && !empty($this->_lastConnKey) && $connKey!=$this->_lastConnKey){
            throw new \Exception('not support transactions cross different db servers');
        }
        
        echo "transaction status:";var_dump($this->_isTrans);
        
        $this->_lastConnKey = $connKey;
        $this->_lastConn = $mysqli;
        
        $sql = $query->makeSql();
        $mapTables = $query->tables;
        $realTables = $connInfo['tables'];
        foreach ($mapTables as $mapTable){
            $sql = str_replace("@{$mapTable}", $realTables[$mapTable], $sql);
        }
        echo $sql."\r\n";
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
    
    public function transaction(){
        $this->_isTrans = true;
    }
    
    public function commit(){
        if (empty($this->_lastConn)){
            throw new MysqlNoConnectionException('connection not valid when commit a transaction');
        }
        if (!$this->_isTrans){
            throw new MysqlNoEnabledTransException();
        }
        
        echo "Commit\r\n";
        
        $this->_isTrans = false;
        $this->_holdQueries = 0;
        
        return $this->_lastConn->commit();
    }
    
    public function rollback(){
        if (empty($this->_lastConn)){
            throw new MysqlNoConnectionException('connection not valid when rollback a transaction');
        }
        if (!$this->_isTrans){
            throw new MysqlNoEnabledTransException();
        }
        
        echo "Rollback\r\n";
        
        $this->_isTrans = false;
        $this->_holdQueries = 0;
        
        return $this->_lastConn->rollback();
    }
    
    public function errno(){
        return mysqli_connect_errno();
    }
    
    public function error(){
        return mysqli_connect_error();
    }
    
    private function close(\mysqli $conn){
        $conn->close();
    }
    
    public function __destruct(){
        if (!empty($this->_connPool)){
            foreach ($this->_connPool as $conn){
                $this->close($conn);
            }
        }
    }
}