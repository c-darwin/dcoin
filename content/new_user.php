<?php
if (!defined('DC')) die("!defined('DC')");

if (empty($_SESSION['restricted'])) {
	$tpl['new_users'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`,
						 `private_key`,
						 `status`
			FROM `".DB_PREFIX.MY_PREFIX."my_new_users`
			", 'all_data' );
}

$tpl['data']['type'] = 'new_user';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/new_user.tpl' );

?>