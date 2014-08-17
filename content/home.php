<?php
if (!defined('DC')) die("!defined('DC')");

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

// балансы
$tpl['wallets'] = get_balances($user_id);

$tpl['block_id'] = get_block_id($db);
$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/home.tpl' );

?>