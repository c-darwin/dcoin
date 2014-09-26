<?php
session_start();
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$dc_currency_id = intval($_REQUEST['dc_currency_id']);
$currency_id = intval($_REQUEST['currency_id']);
$amount = $db->escape($_REQUEST['amount']);
if ( !preg_match('/^[0-9]{0,6}(\.[0-9]{0,8})?$/D', $amount) || $amount == 0)
	die(json_encode(array('error'=>'amount_error')));

$currency_list = get_currency_list($db);
$config = get_node_config();
if ($config['cf_available_coins_url']) {
	$url =  "{$config['cf_available_coins_url']}?dc_currency_id={$dc_currency_id}&currency_id={$currency_id}&amount={$amount}";
	$answer = file_get_contents($url);
	$answer_array = json_decode($answer, true);
	if (!isset($answer_array['success']))
		echo json_encode(array('error'=>str_ireplace(array('[url]', '[amount]', '[currency_name]'), array('<a href="'.$config['cf_exchange_url'].'">'.$config['cf_exchange_url'].'</a>', $answer_array['error'], 'D'.$currency_list[$dc_currency_id]), $lng['no_DC'])));
	else
		echo $answer;
}

?>