<?php
require_once '../src/Flexper/Env.php';

use Flexper\Env as SolomoEnv;

$options = array(
	'namespace' => 'Knock',
);
SolomoEnv::init($options);
SolomoEnv::execute();
