<?php
if (!defined('DC')) die("!defined('DC')");

$variables = ParseData::get_all_variables($db);

if (empty($_SESSION['restricted'])) {
	$tpl['public_key'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `public_key`
			FROM `".DB_PREFIX.MY_PREFIX."my_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX.MY_PREFIX."my_keys` )
			", 'fetch_one');
	$tpl['public_key'] = bin2hex($tpl['public_key']);
}

$tpl['my_notice'] = get_my_notice_data();
$tpl['script_version'] = str_ireplace('[ver]', get_current_version($db), $lng['script_version']);
$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name == 'my_lock')
	$tpl['demons_status'] = 'OFF';
else
	$tpl['demons_status'] = 'ON';

if ( isset($db) && get_community_users($db) ) {
	$pool_admin_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `pool_admin_user_id`
				FROM `".DB_PREFIX."config`
				", 'fetch_one' );
	if ( (int)$_SESSION['user_id'] === (int)$pool_admin_user_id ) {
		define('POOL_ADMIN', true);
	}
}

// несколько краудфандинговых проектов
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `".DB_PREFIX."cf_projects`.*
		FROM `".DB_PREFIX."cf_projects`
		LEFT JOIN `".DB_PREFIX."cf_projects_data` ON  `".DB_PREFIX."cf_projects_data`.`project_id` = `".DB_PREFIX."cf_projects`.`id`
		WHERE `del_block_id` = 0 AND
					  `end_time` > ".time()." AND
					 `lang_id` = {$lang}
		ORDER BY `funders` DESC
		LIMIT 3
		");
while ( $row =  $db->fetchArray( $res ) ) {
	$row = array_merge (project_data($row), $row);
	$tpl['projects'][$row['id']] = $row;
}

// история операций по кошелькам
if (empty($_SESSION['restricted'])) {
	// получаем последние транзакции по кошелькам
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX.MY_PREFIX."my_dc_transactions`
				WHERE `status` = 'approved'
				ORDER BY `id` DESC
				LIMIT 0, 10
				");
	while ( $row = $db->fetchArray($res) ) {
		$tpl['my_dc_transactions'][] = $row;
	}
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."credits`
		WHERE (`from_user_id` = {$user_id} OR `to_user_id` = {$user_id}) AND
					 `del_block_id` = 0 AND
					 `amount` > 0
		");
while ($row = $db->fetchArray($res)) {
	if ($user_id == $row['from_user_id'])
		$tpl['I_debtor'][] = $row;
	else
		$tpl['I_creditor'][] = $row;
}


// балансы
$wallets = get_balances($user_id);
foreach ($wallets as $id => $data){
	$tpl['wallets'][$data['currency_id']] = $data;
}

$tpl['block_id'] = get_block_id($db);
$tpl['confirmed_block_id'] = get_confirmed_block_id($db);

$tpl['currency_list'] = get_currency_list($db, 'full');


// входящие запросы
$tpl['cash_requests'] = 0;
if (empty($_SESSION['restricted'])) {
	$my_user_id = get_my_user_id($db);
	$tpl['cash_requests'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$my_user_id} AND
							 `status` = 'pending' AND
							 `for_repaid_del_block_id` = 0 AND
							 `del_block_id` = 0
				", 'fetch_one' );
	$tpl['cash_requests'] = $tpl['cash_requests']?1:0;
}

/*
 *  Задания
*/
$tpl['tasks_count'] = 0;
$tpl['tasks_count']+= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM `".DB_PREFIX."votes_miners`
		WHERE  `votes_end` = 0 AND
					 `type` = 'user_voting'
		", 'fetch_one');
$tpl['tasks_count']+= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM `".DB_PREFIX."votes_miners`
		WHERE  `votes_end` = 0 AND
					 `type` = 'user_voting'
		", 'fetch_one');
// вначале получим ID валют, которые мы можем проверять.
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`
		FROM `".DB_PREFIX."promised_amount`
		WHERE `status` IN ('mining', 'repaid') AND
					 `user_id` = {$user_id}
		");
$currency_ids='';
while ($row = $db->fetchArray($res))
	$currency_ids.=$row['currency_id'].',';
$currency_ids = substr($currency_ids, 0, -1);
if ($currency_ids || $user_id == 1) {
	if ($user_id==1)
		$add_sql = '';
	else
		$add_sql = "AND `currency_id` IN ({$currency_ids})";
	$tpl['tasks_count']+= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`)
			FROM `".DB_PREFIX."promised_amount`
			WHERE `status` =  'pending' AND
						 `del_block_id` = 0
			{$add_sql}
			", 'fetch_one');
}

if (empty($_SESSION['restricted'])) {
	$repeated_tasks = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM `" . DB_PREFIX . MY_PREFIX . "my_tasks`
		WHERE  `time` > " . (time() - TASK_TIME) . "
		", 'fetch_one');
	$tpl['tasks_count'] -= $repeated_tasks;
	$tpl['tasks_count'] = $tpl['tasks_count'] > 0 ? $tpl['tasks_count'] : 0;
}
// баллы
$tpl['points'] = (int) $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `points`
		FROM `".DB_PREFIX."points`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');

// проценты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."currency`
		");
while ($row = $db->fetchArray($res)) {
	$pct = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."pct`
			WHERE `currency_id` = {$row['id']}
			ORDER BY `block_id` DESC
			LIMIT 1
			", 'fetch_array');
	$tpl['currency_pct'][$row['id']]['name'] = $row['name'];
	$tpl['currency_pct'][$row['id']]['miner'] = round((pow(1+$pct['miner'], 3600*24*365)-1)*100, 2);
	$tpl['currency_pct'][$row['id']]['user'] = round((pow(1+$pct['user'], 3600*24*365)-1)*100, 2);
	$tpl['currency_pct'][$row['id']]['miner_block'] = round((pow(1+$pct['miner'], 120)-1)*100, 4);
	$tpl['currency_pct'][$row['id']]['user_block'] = round((pow(1+$pct['user'], 120)-1)*100, 4);
	$tpl['currency_pct'][$row['id']]['miner_sec'] = $pct['miner'];
	$tpl['currency_pct'][$row['id']]['user_sec'] = $pct['user'];
}

// случайне майнеры для нанесения на карту
$tpl['rand_miners'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `user_id`
		FROM `".DB_PREFIX."miners_data`
		WHERE `status` = 'miner' AND
				 	`user_id` > 7 AND
				 	`user_id` != 106 AND
				 	`longitude` > 0
		ORDER BY RAND()
		LIMIT 3
		", 'array');


// получаем кол-во DC на кошельках
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`amount`) as sum_amount
			FROM `".DB_PREFIX."wallets`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_wallets[$row['currency_id']] = $row['sum_amount'];
}

// получаем кол-во TDC на обещанных суммах, плюсуем к тому, что на кошельках
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`tdc_amount`) as sum_amount
			FROM `".DB_PREFIX."promised_amount`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	if (!isset($sum_wallets[$row['currency_id']]))
		$sum_wallets[$row['currency_id']] = $row['sum_amount'];
	else
		$sum_wallets[$row['currency_id']] += $row['sum_amount'];
}

// получаем суммы обещанных сумм
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`amount`) as sum_amount
			FROM `".DB_PREFIX."promised_amount`
			WHERE `status` = 'mining' AND
						 `del_block_id` = 0 AND
						  (`cash_request_out_time` = 0 OR `cash_request_out_time` > ".(time() - $variables['cash_request_time']).")
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_promised_amount[$row['currency_id']] = round($row['sum_amount']);
}

// мои обещанные суммы
get_promised_amounts($user_id);

// показываем ли карту
if (empty($_SESSION['restricted'])) {
	$tpl['show_map'] = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `show_map`
				FROM `" . DB_PREFIX . MY_PREFIX . "my_table`
				", 'fetch_one');
}
else {
	$tpl['show_map'] = 1;
}

require_once( ABSPATH . 'templates/home.tpl' );

?>