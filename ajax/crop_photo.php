v y<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

$user_id = intval($_SESSION['user_id']);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

if ( !empty($_SESSION['user_id']) && empty($_SESSION['restricted']) ) {

	$photo = explode(',', $_POST['photo']);
	if($photo[1] !== base64_encode(base64_decode($photo[1])))
		die (json_encode( array('error'=>'Incorrect photo')));
	$photo_data= base64_decode($photo[1]);
	print $photo_data;
	$image = imagecreatefromstring($photo_data);
	if ($_POST['type']=='face')
		imagejpeg($image, ABSPATH . 'public/'.$user_id.'_user_face.jpg', 85);
	else
		imagejpeg($image, ABSPATH . 'public/'.$user_id.'_user_profile.jpg', 85);

	echo json_encode( array('success'=>'ok' ));
	
}

?>