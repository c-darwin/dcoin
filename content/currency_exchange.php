<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'new_forex_order';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `full_name`
		FROM `'.DB_PREFIX.'currency`
		ORDER BY `full_name`
		');
while ($row = $db->fetchArray($res)) {
	$tpl['currency_list'][$row['id']] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`
		FROM `'.DB_PREFIX.'currency`
		ORDER BY `name`
		');
while ($row = $db->fetchArray($res)) {
	$tpl['currency_list_name'][$row['id']] = $row['name'];
}

if (isset($_REQUEST['parameters']['buy_currency_id'])){
	$tpl['buy_currency_id'] = intval($_REQUEST['parameters']['buy_currency_id']);
	$_SESSION['buy_currency_id'] = $tpl['buy_currency_id'];
}
if (isset($_REQUEST['parameters']['sell_currency_id'])){
	$tpl['sell_currency_id'] = intval($_REQUEST['parameters']['sell_currency_id']);
	$_SESSION['sell_currency_id'] = $tpl['sell_currency_id'];
}

if (!$tpl['buy_currency_id'])
	$tpl['buy_currency_id'] = $_SESSION['buy_currency_id'];
if (!$tpl['sell_currency_id'])
	$tpl['sell_currency_id'] = $_SESSION['sell_currency_id'];

if (!$tpl['buy_currency_id'])
	$tpl['buy_currency_id'] = 23;
if (!$tpl['sell_currency_id'])
	$tpl['sell_currency_id'] = 72;

$tpl['buy_currency_name'] = $tpl['currency_list_name'][$tpl['buy_currency_id']];
$tpl['sell_currency_name'] = $tpl['currency_list_name'][$tpl['sell_currency_id']];

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."forex_orders`
		WHERE `buy_currency_id` = {$tpl['buy_currency_id']} AND
					 `sell_currency_id` = {$tpl['sell_currency_id']} AND
					 `empty_block_id` = 0 AND
					 `del_block_id` = 0
		");
while ($row = $db->fetchArray($res)) {
	$tpl['buy_orders'][] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."forex_orders`
		WHERE `buy_currency_id` =  {$tpl['sell_currency_id']} AND
					 `sell_currency_id` = {$tpl['buy_currency_id']} AND
					 `empty_block_id` = 0 AND
					 `del_block_id` = 0
		");
while ($row = $db->fetchArray($res)) {
	$tpl['sell_orders'][] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."forex_orders`
		WHERE `user_id` =  {$user_id} AND
					 `empty_block_id` = 0 AND
					 `del_block_id` = 0
		");
while ($row = $db->fetchArray($res)) {
	$tpl['my_orders'][] = $row;
}

// получаем список кошельков, на которых есть FC
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."wallets`
			WHERE `user_id` = {$user_id} AND
						 `currency_id` IN ({$tpl['sell_currency_id']}, {$tpl['buy_currency_id']})
			");
while ( $row = $db->fetchArray($res) ) {
	$row['amount']+=calc_profit_($row['currency_id'], $row['amount'], $user_id, $db, $row['last_update'], time(), 'wallet');
	$row['amount'] = floor( round( $row['amount'], 3)*100 ) / 100;
	$forex_orders_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(`amount`)
				FROM `".DB_PREFIX."forex_orders`
				WHERE `user_id` = {$user_id} AND
							 `sell_currency_id` = {$row['currency_id']} AND
							 `del_block_id` = 0
				", 'fetch_one' );
	$row['amount'] -= $forex_orders_amount;
	$tpl['wallets_amounts'][$row['currency_id']] = $row['amount'];
}

require_once( ABSPATH . 'templates/currency_exchange.tpl' );

?>