<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

$user_id = intval($_SESSION['user_id']);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
/*
function save_img($src, $save_name, $coords, $type="resized") {

	$size = getimagesize($src);
	$w = $size[0];
	$h = $size[1];

	$koef = $w/350;
	if ($koef > 2) {
		$new_w = round(350*2);
		$new_h = round(500*2);
	}
	else {
		$new_w = round($coords[4]*$koef);
		$new_h = round($coords[5]*$koef);
	}

	if ($type=="resized") {
		$coords[0] = round($coords[0]*$koef);
		$coords[1] = round($coords[1]*$koef);
		$coords[2] = round($coords[2]*$koef);
		$coords[3] = round($coords[3]*$koef);
		$coords[4] = round($coords[4]*$koef);
		$coords[5] = round($coords[5]*$koef);
	}
	//file_put_contents('coord.txt', "w $w h $h 0 $coords[0]\n1 $coords[1]\n2 $coords[2]\n3 $coords[3]\n4 $coords[4]\n5 $coords[5]\nnew_w $new_w\nnew_h $new_h\n\n");

	switch ($size['mime']) {
		case 'image/jpeg':
		case 'image/jpg': $img = imagecreatefromjpeg($src); break;
		case 'image/bmp': $img = imagecreatefromwbmp($src); break;
		case 'image/gif': $img = imagecreatefromgif($src); break;
		
		case 'image/png': $img = imagecreatefrompng($src); break;
		default : return "Unsupported picture type!";
	}

	$new = imagecreatetruecolor($new_w, $new_h);
	imagecopyresampled($new, $img, 0, 0, $coords[0], $coords[1], $new_w, $new_h, $coords[4], $coords[5]);
	imagejpeg($new, $save_name, 85);

	return true;
}
*/
if ( !empty($_SESSION['user_id']) && empty($_SESSION['restricted']) ) {
	/*
	$coords = explode(';', $_REQUEST['coords']);
	$r = "\d{1,5}(\.\d{1,30})?";
	if (!preg_match("/^({$r};){5}{$r}$/iD", $_REQUEST['coords']))
		die('bad coords');

	// пришла временная фотка, которую обрежем и сохраним, как постоянную
	if ($_REQUEST['type'] == 'user_face_tmp') {

		$name = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
		save_img( ABSPATH . '/public/'.$_SESSION['user_id'].'_user_face_tmp.jpg' , ABSPATH . $name , $coords);
	}
	else if ($_REQUEST['type'] == 'user_profile_tmp') {

		$name = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
		save_img( ABSPATH . '/public/'.$_SESSION['user_id'].'_user_profile_tmp.jpg' , ABSPATH . $name , $coords);
	}
	else if ($_REQUEST['type'] == 'user_face_webcam') {

		$name = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
		save_img('data:image/jpeg;base64,'.$_POST['image'], ABSPATH . $name, $coords, "original");
	}
	else if ($_REQUEST['type'] == 'user_profile_webcam') {

		$name = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
		save_img('data:image/jpeg;base64,'.$_POST['image'], ABSPATH . $name, $coords, "original");
	}
*/

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