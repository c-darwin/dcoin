<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_arbitrator_list';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['my_trust_list'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT arbitrator_user_id, url, count(arbitration_trust_list.user_id) as count
		FROM `".DB_PREFIX."arbitration_trust_list`
		LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."arbitration_trust_list`.`user_id`
		LEFT JOIN `".DB_PREFIX."users` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX."arbitration_trust_list`.`arbitrator_user_id`
		WHERE `".DB_PREFIX."miners_data`.`status`='miner' AND
					 `".DB_PREFIX."arbitration_trust_list`.`user_id`= {$user_id}
		GROUP BY arbitrator_user_id
		ORDER BY count(`".DB_PREFIX."arbitration_trust_list`.`user_id`)  DESC
		", 'all_data');

// top 10 арбитров
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT arbitrator_user_id, url, count(arbitration_trust_list.user_id) as count
		FROM `".DB_PREFIX."arbitration_trust_list`
		LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."arbitration_trust_list`.`user_id`
		LEFT JOIN `".DB_PREFIX."users` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX."arbitration_trust_list`.`arbitrator_user_id`
		WHERE `".DB_PREFIX."miners_data`.`status`='miner'
		GROUP BY `arbitrator_user_id`
		ORDER BY count(`".DB_PREFIX."arbitration_trust_list`.`user_id`)  DESC
		LIMIT 10
		");
while ( $row = $db->fetchArray( $res ) ) {

	// кол-во манибеков и сумма за полседний месяц
	$row['refund_data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`) as `count`, sum(`refund`) as `sum`
			FROM `".DB_PREFIX."orders`
			LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."orders`.`buyer`
			WHERE (`arbitrator0` = {$row['arbitrator_user_id']} OR `arbitrator1` = {$row['arbitrator_user_id']} OR `arbitrator2` = {$row['arbitrator_user_id']} OR `arbitrator3` = {$row['arbitrator_user_id']} OR `arbitrator4` = {$row['arbitrator_user_id']}) AND
						 `".DB_PREFIX."orders`.`status` = 'refund' AND
						 `arbitrator_refund_time` > ".(time()-3600*24*30)." AND
						 `arbitrator_refund_time` < ".(time())." AND
						 `".DB_PREFIX."miners_data`.`status` = 'miner'
			GROUP BY `user_id`
			", 'fetch_array');

	// кол-во неудовлетвореных манибеков за последний месяц
	$row['count_rejected_refunds'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`)
			FROM `".DB_PREFIX."orders`
			LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."orders`.`buyer`
			WHERE  (`arbitrator0` = {$row['arbitrator_user_id']} OR `arbitrator1` = {$row['arbitrator_user_id']} OR `arbitrator2` = {$row['arbitrator_user_id']} OR `arbitrator3` = {$row['arbitrator_user_id']} OR `arbitrator4` = {$row['arbitrator_user_id']}) AND
						 `".DB_PREFIX."orders`.`status` = 'refund' AND
						 `end_time` > ".(time()-3600*24*30)." AND
						 `end_time` < ".(time())." AND
						 `".DB_PREFIX."miners_data`.`status` = 'miner'
			GROUP BY `user_id`
			", 'fetch_one');

	$tpl['arbitrators'][] = $row;

}

$tpl['currency_list'] = get_currency_list($db);

$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('change_arbitrator_conditions', 'change_seller_hold_back', 'change_arbitrator_list', 'money_back_request', 'money_back', 'change_money_back_time')), 3);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_txs($tpl['last_tx']);

$tpl['pending_tx'] = $pending_tx[ParseData::findType('change_arbitrator_list')];

require_once( ABSPATH . 'templates/arbitration.tpl' );
?>