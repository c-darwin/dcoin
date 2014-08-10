<?php

if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

// валюты
$tpl['currency_list'] = get_currency_list($db, 'full');

$tpl['user_id'] = $_SESSION['user_id'];

if ($tpl['user_id']!='wait') {

	// получаем список кошельков, на которых есть FC
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."wallets`
			WHERE `user_id` = {$tpl['user_id']}
			");
	while ( $row = $db->fetchArray($res) ) {

		$row['amount']+=calc_profit_($row['currency_id'], $row['amount'], $tpl['user_id'], $db, $row['last_update'], time(), 'wallet');
		$row['amount'] = floor( round( $row['amount'], 3)*100 ) / 100;
		$forex_orders_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(`amount`)
				FROM `".DB_PREFIX."forex_orders`
				WHERE `user_id` = {$tpl['user_id']} AND
							 `sell_currency_id` = {$row['currency_id']} AND
							 `del_block_id` = 0
				", 'fetch_one' );
		$row['amount'] -= $forex_orders_amount;
		$pct = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user`
				FROM `".DB_PREFIX."pct`
				WHERE `currency_id` = {$row['currency_id']}
				ORDER BY `block_id` DESC
				LIMIT 1
				", 'fetch_one');
		$pct = round((pow(1+$pct, 3600*24*365)-1)*100, 2);
		$tpl['wallets'][] = array( 'currency_id' => $row['currency_id'], 'amount' => $row['amount'], 'pct' => $pct);

	}

	if (empty($_SESSION['restricted'])) {
		// получаем последние 20 транзакций по кошелькам
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
if ($_REQUEST['parameters']['project_id']){
	$tpl['cf_project_id'] = intval($_REQUEST['parameters']['project_id']);
}

require_once( ABSPATH . 'templates/wallets_list.tpl' );

?>