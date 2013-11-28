<?php
require_once '../src/Lark/Env.php';

use Lark\Env as LarkEnv;

$options = array(
	'namespace' => 'Knock',
);
LarkEnv::init($options);
LarkEnv::execute();
