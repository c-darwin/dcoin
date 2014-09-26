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

$names = array('cash_request'=>'Наличные','from_mining_id'=>'С майнинга','from_repaid'=>'С майнинга погашенных','from_user'=>'От пользователя','node_commission'=>'Комиссия нода', 'system_commission'=>'system_commission', 'referral'=>'referral', 'cf_project'=>'Crowd funding', 'cf_project_refund'=>'Crowd funding refund');

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

require_once( ABSPATH . 'templates/wallets_list.tpl' );

?>