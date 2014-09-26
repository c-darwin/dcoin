<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die ('Permission denied');

if (!get_community_users($db)) { // сингл-мод

	define('MY_PREFIX', '');
	// переключаемся в пул-мод
	$my_user_id = get_my_user_id($db);
	for ($i=0; $i<sizeof($my_tables); $i++) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			RENAME TABLE `".DB_PREFIX."{$my_tables[$i]}` TO `".DB_PREFIX."{$my_user_id}_{$my_tables[$i]}`
			");
	}

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO`".DB_PREFIX."community` (`user_id`) VALUES ({$my_user_id})
			");

	$commission = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `commission`
			FROM `".DB_PREFIX."commission`
			WHERE `user_id` = {$my_user_id}
			", 'fetch_one' );

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."config`
			SET `pool_admin_user_id` = {$my_user_id},
				   `pool_max_users` = 100,
				   `commission` = '{$commission}'
			");
}
else {
	$community = get_community_users($db);
	$community = json_encode($community);
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			REPLACE INTO `".DB_PREFIX."backup_community` (`data`) VALUES ('{$community}')
			");

	$my_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `pool_admin_user_id`
			FROM `".DB_PREFIX."config`
			", 'fetch_one' );

	for ($i=0; $i<sizeof($my_tables); $i++) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				RENAME TABLE `".DB_PREFIX."{$my_user_id}_{$my_tables[$i]}` TO `".DB_PREFIX."{$my_tables[$i]}`
				");
	}

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			TRUNCATE TABLE`".DB_PREFIX."community`
			");

}



?>