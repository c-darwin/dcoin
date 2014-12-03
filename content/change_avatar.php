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
if (empty($tpl['avatar']))
	$tpl['avatar'] = '0';

$tpl['last_tx'] = get_last_tx($user_id, $tpl['data']['type_id']);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_tx($tpl['last_tx']);

require_once( ABSPATH . 'templates/change_avatar.tpl' );

?>