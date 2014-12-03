<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$user_id = intval($_REQUEST['user_id']);

if ( !check_input_data ($user_id , 'int') )
	die('error user_id');

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$sec = 3600*24*365;
$prognosis = array();
$counters_ids = array();

$miners_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_array' );

// получим ID майнеров, у которых лежат фото нужного нам юзера
$miners_ids = ParseData::get_miners_keepers($miners_data['photo_block_id'], $miners_data['photo_max_miner_id'],  $miners_data['miners_keepers'], true);
$hosts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `host`
		FROM `".DB_PREFIX."miners_data`
		WHERE `miner_id` IN (".implode(',', $miners_ids).")
		", 'array' );

$currency_list = get_currency_list($db);

/*
 * Обещанные
 * */

get_promised_amounts($user_id);
if (!empty($tpl['promised_amount_list_gen'][72]))
	$data = $tpl['promised_amount_list_gen'][72];
else if (!empty($tpl['promised_amount_list_gen'][72]))
	$data = $tpl['promised_amount_list_gen'][72];
else if (!empty($tpl['promised_amount_list_gen'][23]))
	$data = $tpl['promised_amount_list_gen'][23];
else
	$data = '';

$promised_amounts = '';
if ($data && $data['amount']>1) {
	$promised_amounts = round($data['amount']).' '.$currency_list[$data['currency_id']].'<br>';
	$prognosis[$data['currency_id']]+=(pow(1+$data['pct_sec'], $sec)-1)*$data['amount'];
}

if ($promised_amounts)
	$promised_amounts='<strong>'.substr($promised_amounts, 0, -4).'</strong><br>'.$lng['promised'].'<hr>';


/*
 * На кошельках
 * */

$balances = get_balances($user_id);
$balances_currency = array();
foreach ($balances as $data) {
	$balances_currency[$data['currency_id']] = $data;
}

if (!empty($balances_currency[72]))
	$data = $balances_currency[72];
else if (!empty($balances_currency[58]))
	$data = $balances_currency[58];
else if (!empty($balances_currency[23]))
	$data = $balances_currency[23];
else
	$data = '';

$wallets = '';
if ($data) {
	$counter_id = "map-{$user_id}-{$data['currency_id']}";
	$counters_ids[] = $counter_id;
	$wallets = "<span class='dc_amount' id='{$counter_id}'>{$data['amount']}</span> d{$currency_list[$data['currency_id']]}<br>";
	// прогноз
	$prognosis[$data['currency_id']] += (pow(1 + $data['pct_sec'], $sec) - 1) * $data['amount'];
	$pct_sec = $data['pct_sec'];
}

if ($wallets)
	$wallets=''.substr($wallets, 0, -4).'<br>'.$lng['on_the_account'].'<hr>';

/*
 * Годовой прогноз
 * */
$prognosis_html = '';
foreach ($prognosis as $currency_id=>$amount) {
	if ($amount < 0.01)
		continue;
	else if ($amount < 1)
		$amount = round($amount, 2);
	else
		$amount = number_format($amount, 0, '.', ' ');
	$prognosis_html.= "<span class='amount_1year'>+{$amount} d{$currency_list[$currency_id]}</span><br>";
}
if ($prognosis_html)
	$prognosis_html=substr($prognosis_html, 0, -4)."<br> {$lng['profit_forecast']} {$lng['after_1_year']}";


$result = array('hosts'=>$hosts, 'lnglat'=>array('lng'=>floatval($miners_data['longitude']), 'lat'=>floatval($miners_data['latitude'])), 'html'=>$promised_amounts.$wallets.'<div style="clear:both"></div>'.$prognosis_html.'</p>', 'counters'=>$counters_ids, 'pct_sec'=>$pct_sec);
header("Access-Control-Allow-Origin: *");
echo json_encode($result);

?>