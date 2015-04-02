<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');
$user_id = intval($_SESSION['user_id']);

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$type = $_POST['type'];

$end = 'mp4';
if ( $type == 'user_video' || substr_count($type, 'promised_amount')>0 ) {

	switch ( $_FILES['file']['type'] ) {

		case 'video/mp4':
		case 'video/quicktime':
			$end = 'mp4';
			break;

		case 'video/ogg':
			$end = 'ogv';
			break;

		case 'video/webm':
			$end = 'webm';
			break;

		default :
			die ( json_encode(array('error'=>'error format')) );
			break;

	}
}
else {
	die ( json_encode(array('error'=>'error format')) );
}

if ($_FILES['image']['error']>0) {

}
else if ( $type == 'user_video') {

	$name = "public/{$user_id}_user_video.{$end}";
	copy($_FILES['file']['tmp_name'], ABSPATH . $name);
	$return_url = $name;
}
else if ( substr_count($type, 'promised_amount')>0) {

	$data = explode('-', $type);
	$currency_id = intval($data[1]);
	$name = "public/{$user_id}_promised_amount_{$currency_id}.{$end}";
	copy($_FILES['file']['tmp_name'], ABSPATH . $name);
	$return_url = $name;
}

if ($_FILES['image']['error']>0)
	echo json_encode(array('url'=>'', 'error'=>'error. code '.$_FILES['image']['error']));
else
	echo json_encode(array('url'=>$return_url));

?>