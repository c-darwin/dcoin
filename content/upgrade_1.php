<?php
if (!defined('DC')) die("!defined('DC')");

// есть ли загруженное видео.
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `video_url_id`,
					 `video_type`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table
		');
$row = $db->fetchArray($res);
switch ($row['video_type']) {

	case 'youtube' :
		$tpl['video_url'] = 'http://www.youtube.com/embed/'.$row['video_url_id'];
		break;
		
	case 'vimeo' : 
		$tpl['video_url'] = 'http://www.vimeo.com/embed/'.$row['video_url_id'];
		break;
		
	case 'youku' : 
		$tpl['video_url'] = 'http://www.youku.com/embed/'.$row['video_url_id'];
		break;

}

$path = 'public/'.$_SESSION['user_id'].'_user_profile.jpg';
if ( file_exists( ABSPATH . $path ) )
	$tpl['user_profile'] = $path;

$path = 'public/'.$_SESSION['user_id'].'_user_face.jpg';
if ( file_exists( ABSPATH . $path ) )
	$tpl['user_face'] = $path;

if ( file_exists( ABSPATH . '/public/user_video.mp4' ) )
	$tpl['user_video_mp4'] = 'public/user_video.mp4';

if ( file_exists( ABSPATH . '/public/user_video.ogv' ) )
	$tpl['user_video_ogg'] = 'public/user_video.ogv';

if ( file_exists( ABSPATH . '/public/user_video.webm' ) )
	$tpl['user_video_webm'] = 'public/user_video.webm';

require_once( ABSPATH . 'templates/upgrade_1.tpl' );

?>