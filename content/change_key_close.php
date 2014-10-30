<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_key_active';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['admin_user_id'] = get_admin_user_id($db);

// нужно узнать, разрешил ли уже юзер менять свой ключ админу
$tpl['change_key_status'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `change_key`
		FROM `".DB_PREFIX."users`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_one' );

require_once( ABSPATH . 'templates/restoring_access.tpl' );

?>