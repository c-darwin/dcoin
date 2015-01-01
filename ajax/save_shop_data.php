<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

define('MY_PREFIX', get_my_prefix($db));

$shop_secret_key = $db->escape($_POST['shop_secret_key']);
$shop_callback_url = $db->escape($_POST['shop_callback_url']);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
		SET  `shop_secret_key` = '{$shop_secret_key}',
				`shop_callback_url` =  '{$shop_callback_url}'
		");


?>