<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['credit_part_type'] = 'money_back_request';
$tpl['data']['credit_part_type_id'] = ParseData::findType($tpl['data']['credit_part_type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."orders`
		WHERE (`arbitrator0` = {$user_id} OR `arbitrator1` = {$user_id} OR `arbitrator2` = {$user_id} OR `arbitrator3` = {$user_id} OR `arbitrator4` = {$user_id}) AND
					 `status` = 'refund'
		ORDER BY `time` DESC
		LIMIT 20
		");
while ($row = $db->fetchArray($res)) {
	if (empty($_SESSION['restricted'])) {
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `comment`,
							 `comment_status`
				FROM `".DB_PREFIX.MY_PREFIX."my_comments`
				WHERE `id` = {$row['id']} AND
							 `type` = 'arbitrator'
				LIMIT 1
				", 'fetch_array');
		$row['comment'] = $data['comment'];
		$row['comment_status'] = $data['comment_status'];
	}
	$tpl['my_orders'][] = $row;
}

$tpl['currency_list'] = get_currency_list($db);

$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('change_arbitrator_conditions', 'money_back')), 3);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_txs($tpl['last_tx']);

require_once( ABSPATH . 'templates/arbitration_arbitrator.tpl' );
?>