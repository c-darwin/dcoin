<?php

if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

// валюты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT `id`, `name` FROM `'.DB_PREFIX.'currency` ORDER BY `name`' );
while ($row = $db->fetchArray($res)) 
	$tpl['currency_list'][$row['id']] = $row['name'];

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
		$tpl['wallets'][] = array( 'currency_id' => $row['currency_id'], 'amount' => $row['amount']);

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

$tpl['data']['type'] = 'send_dc';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$names = array('cash_request'=>'Наличные','from_mining_id'=>'С майнинга','from_repaid'=>'С майнинга погашенных','from_user'=>'От пользователя','node_commission'=>'Комиссия нода', 'system_commission'=>'system_commission');

require_once( ABSPATH . 'templates/wallets_list.tpl' );

?>