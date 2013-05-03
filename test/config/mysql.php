<?php
$config = array(
	'server' => array(
		'db1' => array(
			'name' => 'Knock',
			'host' => '127.0.0.1',
			'port' => '3306',
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
	),
);