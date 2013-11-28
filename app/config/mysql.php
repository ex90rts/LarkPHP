<?php
$config = array(
	'server' => array(
		'db1' => array(
			'name' => 'blog',
			'host' => '127.0.0.1',
			'port' => '3306',
			'user' => 'root',
			'pass' => '123456',
		),
	),
	'tables' => array(
		'Users' => array(
			'server'   => array('db1'),
		),
		'Uniqid' => array(
			'server'   => array('db1'),
		),
		'Posts' => array(
			'server'   => array('db1'),
		),
		'Tags' => array(
			'server'   => array('db1'),
		),
		'Tagconnects' => array(
			'server'   => array('db1'),
		),
	),
);