<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

define('MY_PREFIX', get_my_prefix($db));

$_REQUEST['geolocation'] = $db->escape($_REQUEST['geolocation']);
$geolocation = explode(', ', $_REQUEST['geolocation']);
$geolocation[0] = round($geolocation[0], 5);
$geolocation[1] = round($geolocation[1], 5);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
		SET `geolocation` = '{$geolocation[0]}, {$geolocation[1]}'
		");
print '{"error":0}';
?>