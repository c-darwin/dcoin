<?php
if (!defined('DC')) die("!defined('DC')");


// есть ли загруженное видео.
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `video_url_id`,
					 `video_type`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
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
require_once( ABSPATH . 'templates/upgrade_4.tpl' );

?>