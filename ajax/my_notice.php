<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die(json_encode(array('block_id'=>0, 'alert'=>'')));

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

if (file_exists(ABSPATH . 'db_config.php')) {

	require_once( ABSPATH . 'db_config.php' );
	require_once( ABSPATH . 'includes/class-parsedata.php' );

	$lang = get_lang();

	require_once( ABSPATH . 'lang/'.$lang.'.php' );

	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	define( 'MY_PREFIX', get_my_prefix($db) );
	$my_notice = get_my_notice_data();

	$cash_requests = 0;
	if (empty($_SESSION['restricted'])) {
		$my_user_id = get_my_user_id($db);
		$cash_requests = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$my_user_id} AND
							 `status` = 'pending' AND
							 `for_repaid_del_block_id` = 0 AND
							 `del_block_id` = 0
				", 'fetch_one' );
		$cash_requests = $cash_requests?1:0;
	}
	print json_encode(
			array(
				'main_status'=>$my_notice['main_status'],
				'main_status_complete'=>$my_notice['main_status_complete'],
				'account_status'=>$my_notice['account_status'],
				'cur_block_id'=>$my_notice['cur_block_id'],
				'connections'=>$my_notice['connections'],
				'time_last_block'=>$my_notice['time_last_block'],
				'inbox'=>$cash_requests
			)
	);
}

?>