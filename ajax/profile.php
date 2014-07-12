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
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$user_id = $_REQUEST['user_id'];

if ( !check_input_data ($user_id , 'int') )
	die('error user_id');

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."abuses`
		WHERE `user_id` = {$user_id}
		");
$abuses = '';
while ($row = $db->fetchArray($res)) {
	$abuses .= 'from_user_id: '.$row['from_user_id'].'; time: '.date('d-m-Y H:i:s',$row['time']).'; comment: '.$row['comment']."<br>";
}
if (!$abuses)
	$abuses = 'No';

$reg_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `reg_time`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
$reg_time = date('d-m-Y H:i:s', $reg_time);
echo json_encode(
	array( 'abuses'=>$abuses,
			   'reg_time'=>$reg_time
			)
);

?>