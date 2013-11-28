<?php
namespace Lark;

/*Base Envirament Constants*/
define("START_DATETIME",  '2013-01-01 00:00:00');
define("START_TIMESTAMP", 1356969600);

/*Redirect Constants*/
define("REDIRECT_PERMISSION", 'redirect_permission');
define("REDIRECT_NEXTACTION", 'redirect_nextaction');

/*Data Types*/
define("TYPE_INT",       'int');
define("TYPE_NUMBER",    'number');
define("TYPE_STRING",    'string');
define("TYPE_BOOL",      'bool');
define("TYPE_DATETIME",  'datetime');
define("TYPE_SERIALIZED",'serialized');
define("TYPE_JSON",      'json');

/*MySQL Primary Key Name*/
define("PRIMARY_KEY", 'id');

/*Access Result*/
define("AC_DENY",  'deny');
define("AC_GRANT", 'grant');

/* Aceess User Roles */
define("ROLE_GUEST", 'GUEST');
define("ROLE_USER",  'USER');
define("ROLE_ADMIN", 'ADMIN');

/*Auth Types*/
define("AUTH_CREATE", 'CREATE');
define("AUTH_READ",   'READ');
define("AUTH_UPDATE", 'UPDATE');
define("AUTH_DELETE", 'DELETE');
