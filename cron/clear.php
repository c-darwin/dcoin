<?php
define( 'DC', true );
// ****************************************************************************
// Чистим таблы
// ****************************************************************************

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

main_lock();

$variables = ParseData::get_all_variables($db);
$current_block_id = get_block_id($db);

if (!$current_block_id) {
	main_unlock();
	exit;
}

// чистим log_transactions каждые 15 минут. Удаляем данные, которые старше 36 часов.
// Можно удалять и те, что старше rollback_blocks_2 + погрешность для времени транзакции (5-15 мин),
// но пусть будет 36 ч. - с хорошим запасом.
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_transactions`
		WHERE `time` < ".(time() - 86400*3)."
		");

// через rollback_blocks_2 с запасом 1440 блоков чистим таблу log_votes где есть del_block_id
// при этом, если проверяющих будет мало, то табла может захламиться незаконченными голосованиями
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_votes`
		WHERE `del_block_id` < ".($current_block_id - $variables['rollback_blocks_2']-1440)." AND
					 `del_block_id` > 0
		");

// через 1440 блоков чистим таблу wallets_buffer где есть del_block_id
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."wallets_buffer`
		WHERE `del_block_id` < ".($current_block_id - $variables['rollback_blocks_2']-1440)." AND
					 `del_block_id` > 0
		");


// чистим все _log_time_
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_votes_complex`
		WHERE `time` < ".(time() - $variables['limit_votes_complex_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_commission`
		WHERE `time` < ".(time() - $variables['limit_commission_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_change_host`
		WHERE `time` < ".(time() - $variables['limit_change_host_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_votes_miners`
		WHERE `time` < ".(time() - $variables['limit_votes_miners_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_primary_key`
		WHERE `time` < ".(time() - $variables['limit_primary_key_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_node_key`
		WHERE `time` < ".(time() - $variables['limit_node_key_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_mining`
		WHERE `time` < ".(time() - $variables['limit_mining_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_message_to_admin`
		WHERE `time` < ".(time() - $variables['limit_message_to_admin_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_holidays`
		WHERE `time` < ".(time() - $variables['limit_holidays_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_change_geolocation`
		WHERE `time` < ".(time() - $variables['limit_change_geolocation_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_cash_requests`
		WHERE `time` < ".(time() - $variables['limit_cash_requests_out_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_promised_amount`
		WHERE `time` < ".(time() - $variables['limit_promised_amount_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_abuses`
		WHERE `time` < ".(time() - $variables['limit_abuses_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_new_miner`
		WHERE `time` < ".(time() - $variables['limit_new_miner_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_votes`
		WHERE `time` < ".(time() - 86400 - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_votes_nodes`
		WHERE `time` < ".(time() - $variables['node_voting_period'] - 86400)."
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_wallets`
		WHERE `block_id` < ".($current_block_id - $variables['rollback_blocks_2']-1440)." AND
					 `block_id` > 0
		");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		DELETE FROM `".DB_PREFIX."log_time_money_orders`
		WHERE `del_block_id` < ".($current_block_id - $variables['rollback_blocks_2']-1440)." AND
					 `del_block_id` > 0
		");

$arr = array(
	'log_commission',
	'log_faces',
	'log_forex_orders',
	'log_forex_orders_main',
	'log_miners',
	'log_miners_data',
	'log_points',
	'log_promised_amount',
	'log_recycle_bin',
	'log_spots_compatibility',
	'log_users',
	'log_votes_max_other_currencies',
	'log_votes_max_promised_amount',
	'log_votes_miner_pct',
	'log_votes_reduction',
	'log_votes_user_pct',
	'log_wallets');
foreach ($arr as $table) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."{$table}`
			WHERE `block_id` < ".($current_block_id - $variables['rollback_blocks_2']-1440)." AND
						 `block_id` > 0
			");
}
main_unlock();

?>