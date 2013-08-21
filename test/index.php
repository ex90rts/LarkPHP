<?php
require_once '../src/Alcedoo/Env.php';

use Alcedoo\Env as SolomoEnv;

$options = array(
	'namespace' => 'Knock',
);
SolomoEnv::init($options);
SolomoEnv::execute();
