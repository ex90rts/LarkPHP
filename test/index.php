<?php
require_once '../src/Alcedoo/Env.php';

use Alcedoo\Env as AlcedooEnv;

$options = array(
	'namespace' => 'Knock',
);
AlcedooEnv::init($options);
AlcedooEnv::execute();
