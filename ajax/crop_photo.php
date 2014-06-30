<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

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
	imagejpeg($new, $save_name, 85);

	return true;
}

if ( !empty($_SESSION['user_id']) && empty($_SESSION['restricted']) ) {
	
	$coords = explode(';', $_REQUEST['coords']);
	$r = "\d{1,5}(\.\d{1,30})?";
	if (!preg_match("/^({$r};){5}{$r}$/iD", $_REQUEST['coords']))
		die('bad coords');

	// пришла временная фотка, которую обрежем и сохраним, как постоянную
	if ($_REQUEST['type'] == 'user_face_tmp') {

		$name = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
		save_img( ABSPATH . '/public/'.$_SESSION['user_id'].'_user_face_tmp.jpg' , ABSPATH . $name , $coords);
		echo json_encode( array('url'=>$name) );
	}
	else if ($_REQUEST['type'] == 'user_profile_tmp') {

		$name = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
		save_img( ABSPATH . '/public/'.$_SESSION['user_id'].'_user_profile_tmp.jpg' , ABSPATH . $name , $coords);
		echo json_encode( array('url'=>$name ));
	}
	
}

?>