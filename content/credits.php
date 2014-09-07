<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['credit_part_type'] = 'change_credit_part';
$tpl['data']['credit_part_type_id'] = ParseData::findType($tpl['data']['credit_part_type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."credits`
		WHERE (`from_user_id` = {$user_id} OR `to_user_id` = {$user_id}) AND
					 `del_block_id` = 0
		");
while ($row = $db->fetchArray($res)) {
	$row['time'] = date('d/m/Y H:i:s', $row['time']);
	if ($user_id == $row['from_user_id'])
		$tpl['I_debtor'][] = $row;
	else
		$tpl['I_creditor'][] = $row;
}

$tpl['credit_part'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `credit_part`
		FROM `".DB_PREFIX."users`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/credits.tpl' );
?>