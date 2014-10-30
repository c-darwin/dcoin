<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_key_active';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['admin_user_id'] = get_admin_user_id($db);

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `change_key`,
					 `change_key_time`,
					 `change_key_close`
		FROM `".DB_PREFIX."users`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_array' );
// разрешил ли уже юзер менять свой ключ админу
$tpl['change_key_status'] = $data['change_key'];
if ($data['change_key_time'] && !$data['change_key_close'])
	$tpl['requests'] = date('d/m/Y H:i:s', $data['change_key_time']);

require_once( ABSPATH . 'templates/restoring_access.tpl' );

?>