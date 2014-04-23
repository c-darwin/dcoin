<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'message_to_admin';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

// если юзер тыкнул по какой-то ветке сообщений, то тут будет parent_id, т.е. id этой ветки
$tpl['parent_id'] = intval(@$_REQUEST['parameters']['parent_id']);

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM  `".DB_PREFIX."my_admin_messages`
		WHERE `message_type` = 0 AND
					 (`parent_id` = {$tpl['parent_id']} OR `id` = {$tpl['parent_id']})
		ORDER BY `id` DESC
		");
while ($row = $db->fetchArray($res)) {

	$tpl['data']['messages'][] = $row;

}

require_once( ABSPATH . 'templates/bug_reporting.tpl' );

?>