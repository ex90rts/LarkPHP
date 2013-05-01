<?php
$config = array(
	'server' => array(
		'db1' => array(
			'name' => 'knock',
			'host' => '127.0.0.1',
			'port' => '3306',
			'user' => 'admin',
			'pass' => '123456',
		),
		'db2' => array(
			'name' => 'knock',
			'host' => '127.0.0.1',
			'port' => '3307',
			'user' => 'admin',
			'pass' => '123456',
		),
		'db3' => array(
			'name' => 'knock',
			'host' => '127.0.0.1',
			'port' => '3308',
			'user' => 'admin',
			'pass' => '123456',
		),
		'db4' => array(
			'name' => 'knock',
			'host' => '127.0.0.1',
			'port' => '3309',
			'user' => 'admin',
			'pass' => '123456',
		),
	),
	'tables' => array(
		'Users' => array(
			'server'   => array('db1'),
		),
		'JokeIndex' => array(
			'server'   => array('db1'),
		),
		'JokeContent' => array(
			'server'=> array('db1', 'db2'),
			'dbnum' => 10,
			'hash'  => array(
				'type' => 'mod',
				'seed' => 20,
			),
		),
	),
);