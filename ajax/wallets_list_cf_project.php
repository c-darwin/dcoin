<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die(json_encode(array('error'=>'Permission denied')));

require_once( ABSPATH . 'lang/'.get_lang().'.php' );

$project_currency_name = $_REQUEST['project_currency_name'];
if ( !check_input_data ($project_currency_name, 'cf_currency_name') )
	die(json_encode(array('error'=>$lng['incorrect_currency_name'])));

// проверим, не занято ли имя валюты
$currency = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `project_currency_name` = '{$project_currency_name}' AND
							 `close_block_id` = 0 AND
							 `del_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
if ($currency)
	die(json_encode(array('error'=>$lng['currency_name_busy'])));

// проверим, не занято ли имя валюты
$currency = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_currency`
				WHERE `name` = '{$project_currency_name}'
				LIMIT 1
				", 'fetch_one' );
if ($currency)
	die(json_encode(array('error'=>$lng['currency_name_busy'])));

print json_encode(array('success'=>$lng['name_is_not_occupied']));

?>