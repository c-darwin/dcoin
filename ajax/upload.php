<?php
session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

if ( empty($_SESSION['user_id']) ) {
	die('Permission denied');
}

if (!empty($_SESSION['restricted']))
	die('Permission denied');

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

if ( $type == 'user_face_tmp' ) {

	$name = 'public/'.$_SESSION['user_id'].'_user_face_tmp.jpg';
	copy($_FILES['image']['tmp_name'], ABSPATH . $name);
	//print $_FILES['image']['tmp_name'].' / '.ABSPATH . 'public/user_face_tmp.jpg';
	$return_url = $name;
}
else if ( $type == 'user_profile_tmp' ) {

	$name = 'public/'.$_SESSION['user_id'].'_user_profile_tmp.jpg';
	copy($_FILES['image']['tmp_name'], ABSPATH . $name);
	$return_url = $name;
}
// в пул-моде пока не даем заливать видео
else if ( $type == 'user_video' && !get_community_users($db)) {

	$name = "public/{$_SESSION['user_id']}_user_video.{$end}";
	copy($_FILES['file']['tmp_name'], ABSPATH . $name);
	$return_url = $name;
}
// в пул-моде пока не даем заливать видео
else if ( substr_count($type, 'promised_amount')>0 && !get_community_users($db)) {

	$data = explode('-', $type);
	$currency_id = intval($data[1]);
	$name = "public/{$_SESSION['user_id']}_promised_amount_{$currency_id}.{$end}";
	copy($_FILES['file']['tmp_name'], ABSPATH . $name);
	$return_url = $name;
}

echo json_encode(array('url'=>$return_url));

?>