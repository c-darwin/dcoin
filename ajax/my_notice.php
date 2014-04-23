<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
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

	if (@$_SESSION['lang'])
		$lang = $_SESSION['lang'];
	else if (@$_COOKIE['lang'])
		$lang = $_COOKIE['lang'];
	if (!isset($lang))
		$lang = 'en';

	if (!preg_match('/^[a-z]{2}$/iD', $lang))
		die('lang error');

	require_once( ABSPATH . 'lang/'.$lang.'.php' );

	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	$my_notice = get_my_notice_data();

	$my_user_id = get_my_user_id($db);
	$cash_request = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`)
			FROM `".DB_PREFIX."cash_requests`
			WHERE `to_user_id` = {$my_user_id} AND
						 `status` = 'pending' AND
						 `for_repaid_del_block_id` = 0 AND
						 `del_block_id` = 0
			", 'fetch_one' );
	print json_encode(
			array(
				'main_status'=>$my_notice['main_status'],
				'account_status'=>$my_notice['account_status'],
				'cur_block_id'=>$my_notice['cur_block_id'],
				'connections'=>$my_notice['connections'],
				'time_last_block'=>$my_notice['time_last_block'],
				'alert'=>$cash_request
			)
	);
}

?>