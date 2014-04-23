<?php
if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

$tpl['data']['type'] = 'votes_complex';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					  `name`,
					  `full_name`
		FROM `".DB_PREFIX."promised_amount`
        LEFT JOIN `currency` ON `currency`.`id` = `promised_amount`.`currency_id`
		WHERE `user_id` = {$user_id} AND
					 `status` IN ('mining', 'repaid') AND
					 `del_block_id` = 0
		GROUP BY `currency_id`
		");
while ($row = $db->fetchArray($res))
	$tpl['promised_amount_currency_list'][$row['currency_id']] =  array('name'=>$row['name']);

$tpl['max_currency_id'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT max(`id`)
		FROM `".DB_PREFIX."currency`
		", 'fetch_one');

$tpl['AllMaxPromisedAmount'] = ParseData::getAllMaxPromisedAmount();
$tpl['AllPct'] = ParseData::getPctArray();
$pct_array = ParseData::getPctArray();
$tpl['js_pct'] = '{';
foreach($pct_array as $year_pct=>$sec_pct) {
	$tpl['js_pct'] .= "{$year_pct}: '{$sec_pct}',";
}
$tpl['js_pct'] = substr($tpl['js_pct'], 0, -1);
$tpl['js_pct'] .= '}';

require_once( ABSPATH . 'templates/voting.tpl' );

?>