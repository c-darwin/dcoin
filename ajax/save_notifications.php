<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );


if (!empty($_SESSION['restricted']))
	die('Permission denied');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

define('MY_PREFIX', get_my_prefix($db));

$data = json_decode($_REQUEST['data']);

for ($i=0; $i<sizeof($data); $i++ ) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX.MY_PREFIX."my_notifications`
			SET `email` = ".intval($data[$i]->email).",
				   `sms` = ".intval($data[$i]->sms)."
		    WHERE `name` = '".$db->escape($data[$i]->name)."'
			");
}
print '{"error":0}';

?>