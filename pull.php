<?php
//this is a git hook script just pull master branch, have fun ;)
echo nl2br(shell_exec('sudo git pull origin master 2>&1'));