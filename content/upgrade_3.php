<?php
if (!defined('DC')) die("!defined('DC')");

$photos['user_profile'] = ABSPATH . '/public/'.$_SESSION['user_id'].'_user_profile.jpg';
$photos['user_face'] = ABSPATH . '/public/'.$_SESSION['user_id'].'_user_face.jpg';

if ( file_exists( $photos['user_profile'] ) ) {

	$tpl['user_profile'] = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
	$tpl['user_profile_size'] = getimagesize( $photos['user_profile'] );
}

if ( file_exists( $photos['user_face'] ) ) {

	$tpl['user_face'] = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
	$tpl['user_face_size'] = getimagesize( $photos['user_face'] );
}

// текущий набор точек для шаблонов
$tpl['example_points'] =  get_points($db);

// точки, которые юзер уже отмечал
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `face_coords`,
					 `profile_coords`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		');
$row = $db->fetchArray($res);
if ($row) {
	$tpl['face_coords'] = $row['face_coords'];
	$tpl['profile_coords'] = $row['profile_coords'];
}

require_once( ABSPATH . 'templates/upgrade_3.tpl' );

?>