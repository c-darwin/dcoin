<?php
session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

// входящие данные не проверяем, т.к. их шлет владелец данного нода
if ( !$_SESSION['DC_ADMIN'] ) {
	exit;
}

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$type = $_POST['type'];

if ( $type == 'user_video' || substr_count($type, 'promised_amount')>0 ) {

	switch ( $_FILES['file']['type'] ) {

		case 'video/mp4':
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


// Если тип face/profile/face_video, то пишем в таблу my_table
if ( $type == 'user_face_tmp' ) {

	copy($_FILES['image']['tmp_name'], ABSPATH . 'public/user_face_tmp.jpg');
	//print $_FILES['image']['tmp_name'].' / '.ABSPATH . 'public/user_face_tmp.jpg';
	$return_url = 'public/user_face_tmp.jpg';
	
}
else if ( $type == 'user_profile_tmp' ) {
	
	copy($_FILES['image']['tmp_name'], ABSPATH . 'public/user_profile_tmp.jpg');
	$return_url = 'public/user_profile_tmp.jpg';
}
else if ( $type == 'user_video' ) {

	copy($_FILES['file']['tmp_name'], ABSPATH . "public/user_video.{$end}");
	$return_url = "public/user_video.{$end}";
}
else if ( substr_count($type, 'promised_amount')>0 ) {

	$data = explode('-', $type);
	$currency_id = $data[1];
	copy($_FILES['file']['tmp_name'], ABSPATH . "public/promised_amount_{$currency_id}.{$end}");
	$return_url = "public/promised_amount_{$currency_id}.{$end}";
}

echo json_encode(array('url'=>$return_url));

?>