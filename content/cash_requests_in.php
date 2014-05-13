<?php
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

$cash_requests_status =
	array(
		'my_pending'=> $lng['local_pending'],
		'pending' => $lng['pending'],
		'approved'=> $lng['approved'],
		'rejected'=> $lng['rejected']
	);

// валюты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`
		FROM `'.DB_PREFIX.'currency`
		ORDER BY `name`
		');
while ($row = $db->fetchArray($res)) 
	$tpl['currency_list'][$row['id']] = $row['name'];

// Узнаем свой user_id
$tpl['user_id'] = get_my_user_id($db);

$variables = ParseData::get_all_variables($db);
// актуальный запрос к нам на получение налички. Может быть только 1.
$tpl['data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX.MY_PREFIX."my_cash_requests`
		WHERE `to_user_id` = {$tpl['user_id']} AND
					 `status` = 'pending' AND
					 `time` > ".(time()-$variables['cash_request_time'])."
		ORDER BY `cash_request_id` DESC
		LIMIT 1
		", 'fetch_array' );

// список ранее отправленных ответов на запросы.
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX.MY_PREFIX."my_cash_requests`
		WHERE `to_user_id` = {$tpl['user_id']}
		");
while ($row = $db->fetchArray($res))
	$tpl['my_cash_requests'][] = $row;


$tpl['data']['type'] = 'cash_request_in';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/cash_requests_in.tpl' );

?>