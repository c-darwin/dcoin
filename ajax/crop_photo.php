<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

function save_img($src, $save_name, $coords) {

	$size = getimagesize($src);
	$w = $size[0];
	$h = $size[1];

	$koef = $w/350;

	$coords[0] = round($coords[0]*$koef);
	$coords[1] = round($coords[1]*$koef);
	$coords[2] = round($coords[2]*$koef);
	$coords[3] = round($coords[3]*$koef);
	$coords[4] = round($coords[4]*$koef);
	$coords[5] = round($coords[5]*$koef);


	switch ($size['mime']) {
		case 'image/jpeg':
		case 'image/jpg': $img = imagecreatefromjpeg($src); break;
		case 'image/bmp': $img = imagecreatefromwbmp($src); break;
		case 'image/gif': $img = imagecreatefromgif($src); break;
		
		case 'image/png': $img = imagecreatefrompng($src); break;
		default : return "Unsupported picture type!";
	}

	$new = imagecreatetruecolor($coords[4], $coords[5]);	
	imagecopyresampled($new, $img, 0, 0, $coords[0], $coords[1], $coords[4], $coords[5], $coords[4], $coords[5]);
	imagejpeg($new, $save_name, 95);

	return true;
}

if ( $_SESSION['DC_ADMIN'] ) {
	
	$coords = explode(';', $_REQUEST['coords']);

	// пришла временная фотка, которую обрежим и сохраним как постоянную
	if ($_REQUEST['type'] == 'user_face_tmp') {
		
		save_img( ABSPATH . '/public/user_face_tmp.jpg' , ABSPATH . '/public/user_face.jpg' , $coords);
		
		echo json_encode( array('url'=>'public/user_face.jpg') );
	}
	else if ($_REQUEST['type'] == 'user_profile_tmp') {
		
		save_img( ABSPATH . '/public/user_profile_tmp.jpg' , ABSPATH . '/public/user_profile.jpg' , $coords);
		
		echo json_encode( array('url'=>'public/user_profile.jpg') );
	}
	else if ($_REQUEST['type'] == 'banknote') {
	
		$promised_amount_id = filter_var($_REQUEST['promised_amount_id'], FILTER_SANITIZE_NUMBER_INT);
		
		save_img( ABSPATH . '/public/banknote_tmp.jpg' , ABSPATH . '/public/banknote_'.$promised_amount_id.'.jpg' , $coords);
		
		echo json_encode( array('url'=>'public/banknote_'.$promised_amount_id.'.jpg', 'hash'=>hash('sha256', (hash_file('sha256', ABSPATH . '/public/banknote_'.$promised_amount_id.'.jpg')))));
	}
	
}

?>