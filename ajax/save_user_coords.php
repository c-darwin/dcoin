<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!preg_match("/face|profile/i", $_REQUEST['type']))
		die ('type error');

for ($i=0; $i<sizeof($_REQUEST['coords']); $i++) {

	$_REQUEST['coords'][$i][0] = intval( $_REQUEST['coords'][$i][0] );
	$_REQUEST['coords'][$i][1] = intval( $_REQUEST['coords'][$i][1] );
}

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."my_table`
		SET `{$_REQUEST['type']}_coords` = '".json_encode($_REQUEST['coords'])."'
		");



?>