<?php
require_once '../library/Lark/Env.php';

use Lark\Env as LarkEnv;

$options = array(
	'namespace' => 'Foo',
);
LarkEnv::init($options);
LarkEnv::execute();
