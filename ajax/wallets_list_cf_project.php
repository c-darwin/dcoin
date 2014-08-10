<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');
$user_id = intval($_SESSION['user_id']);

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die(json_encode(array('error'=>'Permission denied')));

$project_id = intval($_REQUEST['project_id']);

$cf_project = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."cf_projects`
		WHERE `del_block_id`=0 AND
					 `id` = {$project_id}
		", 'fetch_array');
if (!$cf_project)
	die(json_encode(array('error'=>'No project')));

$cf_project = array_merge (project_data($cf_project), $cf_project);

// сколько у нас есть DC данной валюты
$wallet = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."wallets`
			WHERE `user_id` = {$user_id} AND
						 `currency_id` = {$cf_project['currency_id']}
			", 'fetch_array');

$wallet['amount']+=calc_profit_($wallet['currency_id'], $wallet['amount'], $user_id, $db, $wallet['last_update'], time(), 'wallet');
$wallet['amount'] = floor( round( $wallet['amount'], 3)*100 ) / 100;
$forex_orders_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(`amount`)
				FROM `".DB_PREFIX."forex_orders`
				WHERE `user_id` = {$user_id} AND
							 `sell_currency_id` = {$wallet['currency_id']} AND
							 `del_block_id` = 0
				", 'fetch_one' );
$wallet['amount'] -= $forex_orders_amount;
$cf_project['wallet_amount'] = $wallet['amount'];

$currency_list = get_currency_list($db);
$cf_project['currency'] = $currency_list[$cf_project['currency_id']];

print json_encode($cf_project);

?>