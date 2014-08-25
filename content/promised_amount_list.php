<?php
if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

$tpl['currency_list'] = get_currency_list($db);

$tpl['currency_id'] = intval(@$_REQUEST['parameters']['currency_id']);
if (!$tpl['currency_id'])
	$tpl['currency_id'] = 150;

if (empty($_SESSION['restricted'])) {
	// то, что еще не попало в блоки.
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_promised_amount`
			');
	while ($row = $db->fetchArray($res)) {
		$tpl['promised_amount_list']['my_pending'][] = $row;
	}
}

$tpl['variables'] = ParseData::get_all_variables($db);

get_promised_amounts($user_id);

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_promised_amount'], $tpl['periods'][$tpl['variables']['limit_promised_amount_period']]), $lng['limits_text']);

require_once( ABSPATH . 'templates/promised_amount_list.tpl' );

?>