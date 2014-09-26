<?php
if (!defined('DC')) die("!defined('DC')");

$cash_requests_status =
	array(
		'my_pending'=> $lng['local_pending'],
		'pending' => $lng['pending'],
		'approved'=> $lng['approved'],
		'rejected'=> $lng['rejected']
		);

// валюты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT id, name FROM `'.DB_PREFIX.'currency` ORDER BY name' );
while ($row = $db->fetchArray($res)) 
	$tpl['currency_list'][$row['id']] = $row['name'];

$tpl['payment_systems'] =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`
		FROM `'.DB_PREFIX.'payment_systems`
		ORDER BY `name`
		', 'list', array('id', 'name'));

// список отравленных нами запросов
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX.MY_PREFIX."my_cash_requests`
		WHERE `to_user_id` != {$user_id}
		");
while ($row = $db->fetchArray($res) )
	$tpl['my_cash_requests'][] = $row;

$tpl['json_currency_wallets'] = '';
// получаем список кошельков, на которых есть FC
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SELECT * FROM `".DB_PREFIX."wallets` WHERE `user_id` = {$user_id}" );
while ( $row = $db->fetchArray($res) ) {
	if ($row['currency_id']==1)
		continue;
	$row['amount']+=calc_profit_($row['currency_id'], $row['amount'], $user_id, $db, $row['last_update'], time(), 'wallet');
	$tpl['json_currency_wallets'].= "\"{$row['currency_id']}\":[\"{$tpl['currency_list'][$row['currency_id']]}\",{$row['amount']}],";
	$tpl['available_currency'][] = $row['currency_id'];
}

$tpl['json_currency_wallets'] = substr($tpl['json_currency_wallets'], 0, strlen($tpl['json_currency_wallets'])-1);

$tpl['code'] = md5( mt_rand() );
$tpl['hash_code'] = hash( 'sha256', hash( 'sha256', $tpl['code'] ) );

$tpl['variables'] = ParseData::get_variables ($db,  array('node_commission', 'limit_cash_requests_out', 'limit_cash_requests_out_period', 'min_promised_amount') );

$tpl['data']['type'] = 'cash_request_out';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_cash_requests_out'], $tpl['periods'][$tpl['variables']['limit_cash_requests_out_period']]), $lng['cash_request_out_limits_text']);

$tpl['min_promised_amount'] = $tpl['variables']['min_promised_amount'];

$tpl['maxlength'] = 200;

require_once( ABSPATH . 'templates/cash_requests_out.tpl' );

?>