<?php
//this is a git hook script just pull master branch, enjoy ;)
echo nl2br(shell_exec('sudo git pull origin master 2>&1'));