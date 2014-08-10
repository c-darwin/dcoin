<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'user_avatar';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `name`,
					 `avatar`
		FROM `".DB_PREFIX."users`
		WHERE `user_id`= {$user_id}
		", 'fetch_array');
$tpl = array_merge($tpl, $data);

require_once( ABSPATH . 'templates/change_avatar.tpl' );

?>