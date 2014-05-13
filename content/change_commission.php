<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_commission';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_commission', 'limit_commission_period') );

$min_commission_array = array(
	'WOC'=>'0.01', 'AED'=>'0.04', 'AOA'=>'0.96', 'ARS'=>'0.06', 'AUD'=>'0.01', 'AZN'=>'0.01', 'BDT'=>'0.78', 'BGN'=>'0.01', 'BOB'=>'0.07', 'BRL'=>'0.02', 'BYR'=>'89.25', 'CAD'=>'0.01', 'CHF'=>'0.01', 'CLP'=>'5.13', 'CNY'=>'0.06', 'COP'=>'19.11', 'CRC'=>'4.98', 'CZK'=>'0.19', 'DKK'=>'0.06', 'DOP'=>'0.42', 'DZD'=>'0.81', 'EGP'=>'0.07', 'EUR'=>'0.01', 'GBP'=>'0.01', 'GEL'=>'0.02', 'GHS'=>'0.02', 'GTQ'=>'0.08', 'HKD'=>'0.08', 'HRK'=>'0.06', 'HUF'=>'2.25', 'IDR'=>'103.85', 'ILS'=>'0.04', 'INR'=>'0.62', 'IQD'=>'11.64', 'IRR'=>'999.99', 'JOD'=>'0.01', 'JPY'=>'0.98', 'KES'=>'0.87', 'KRW'=>'11.14', 'KWD'=>'0.01', 'KZT'=>'1.53', 'LBP'=>'15.11', 'LKR'=>'1.32', 'MAD'=>'0.08', 'MXN'=>'0.13', 'MYR'=>'0.03', 'NGN'=>'1.61', 'NOK'=>'0.06', 'NPR'=>'0.98', 'NZD'=>'0.01', 'PEN'=>'0.03', 'PHP'=>'0.44', 'PKR'=>'1.03', 'PLN'=>'0.03', 'QAR'=>'0.04', 'RON'=>'0.03', 'RSD'=>'0.85', 'RUB'=>'0.33', 'SAR'=>'0.04', 'SDG'=>'0.04', 'SEK'=>'0.07', 'SGD'=>'0.01', 'SVC'=>'0.09', 'SYP'=>'1.08', 'THB'=>'0.31', 'TND'=>'0.02', 'TRY'=>'0.02', 'TWD'=>'0.30', 'TZS'=>'16.19', 'UAH'=>'0.08', 'UGX'=>'25.79', 'USD'=>'0.01', 'UZS'=>'21.15', 'VEF'=>'0.06', 'VND'=>'210.95', 'YER'=>'2.15', 'ZAR'=>'0.10');


$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
	SELECT `id`,
				 `name`
	FROM `'.DB_PREFIX.'currency`
	ORDER BY `name`
	');
while ($row = $db->fetchArray($res)) {
	$tpl['currency_list'][$row['id']] = $row['name'];
	$tpl['currency_min'][$row['id']] = $min_commission_array[$row['name']];
}

if (empty($_SESSION['restricted'])) {
	$res= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_commission`
			');
	while ($row = $db->fetchArray($res)) {
		$my_commission[$row['currency_id']] = array($row['pct'], $row['min'], $row['max']);
	}
}

$res= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'currency`
		');
while ($row = $db->fetchArray($res)) {
	if (isset($my_commission[$row['id']]))
		$tpl['commission'][$row['id']] = $my_commission[$row['id']];
	else
		$tpl['commission'][$row['id']] = array(0.1, $tpl['currency_min'][$row['id']], 0);

}

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_commission'], $tpl['periods'][$tpl['variables']['limit_commission_period']]), $lng['change_commission_limits_text']);

require_once( ABSPATH . 'templates/change_commission.tpl' );

?>