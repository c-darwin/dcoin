<?php

if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

// валюты
$tpl['currency_list'] = get_currency_list($db, 'full');

$tpl['user_id'] = $_SESSION['user_id'];

if ($tpl['user_id']!='wait') {

	$tpl['wallets'] = get_balances($user_id);

	if (empty($_SESSION['restricted'])) {
		// получаем последние транзакции по кошелькам
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX.MY_PREFIX."my_dc_transactions`
				ORDER BY `id` DESC
				LIMIT 0, 100
				");
		while ( $row = $db->fetchArray($res) ) {
			$tpl['my_dc_transactions'][] = $row;
		}
	}

}

//$tpl['variables'] = ParseData::get_variables ($db,  array('node_commission') );

$tpl['data']['user_type'] = 'send_dc';
$tpl['data']['project_type'] = 'cf_send_dc';
$tpl['data']['user_type_id'] = ParseData::findType($tpl['data']['user_type']);
$tpl['data']['project_type_id'] = ParseData::findType($tpl['data']['project_type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;
$tpl['data']['current_block_id'] = get_block_id($db);
$tpl['data']['confirmed_block_id'] = get_confirmed_block_id($db);

$names = array('cash_request'=>$lng['cash'],'from_mining_id'=>$lng['from_mining'],'from_repaid'=>$lng['from_repaid_mining'],'from_user'=>$lng['from_user'],'node_commission'=>$lng['node_commission'], 'system_commission'=>'system_commission', 'referral'=>'referral', 'cf_project'=>'Crowd funding', 'cf_project_refund'=>'Crowd funding refund');

$tpl['miner_id'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `miner_id`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_one' );

// если юзер кликнул по кнопку "профинансировать" со страницы проекта
if (!empty($_REQUEST['parameters']['project_id'])){
	$tpl['cf_project_id'] = intval($_REQUEST['parameters']['project_id']);
}

// нужна мин. комиссия на пуле для перевода монет
$tpl['config'] = get_node_config();
$tpl['config']['commission'] = json_decode($tpl['config']['commission'], true);
//print_R($tpl['config']['commission']);

$tpl['last_tx'] = get_last_tx($user_id, $tpl['data']['user_type_id']);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_tx($tpl['last_tx']);

$tpl['arbitration_trust_list'] = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `arbitrator_user_id`,
		              `conditions`
		FROM `" . DB_PREFIX . "arbitration_trust_list`
		LEFT JOIN `" . DB_PREFIX . "arbitrator_conditions` ON `" . DB_PREFIX . "arbitrator_conditions`.`user_id` = `" . DB_PREFIX . "arbitration_trust_list`.`arbitrator_user_id`
		WHERE `" . DB_PREFIX . "arbitration_trust_list`.`user_id` = {$user_id}
		", 'list', array('arbitrator_user_id', 'conditions'));
$tpl['arbitration_trust_list'] = $tpl['arbitration_trust_list']?$tpl['arbitration_trust_list']:'0';

require_once( ABSPATH . 'templates/wallets_list.tpl' );

?>