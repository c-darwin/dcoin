<?php

/*
 * Вызывается из cron/connector.php с другой ноды
 * */

define( 'DC', TRUE);

define( 'ABSPATH', dirname(__FILE__) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (isset($_GET['user_id']))
	$user_id = intval($_GET['user_id']);
else
	$user_id = 0;
// если работаем в режиме пула, то нужно проверить, верный ли у юзера нодовский ключ.
if (get_community_users($db)) {
	$table_exists = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES LIKE '".DB_PREFIX."{$user_id}_my_node_keys'
			", 'num_rows');
	if (!$table_exists)
		die('error');

	$my_block_id = get_my_block_id($db);
	$my_node_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `public_key`
			FROM `".DB_PREFIX."{$user_id}_my_node_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."{$user_id}_my_node_keys` ) AND
						 `block_id` < {$my_block_id}
			LIMIT 1
			", 'fetch_one' );
	if (!$my_node_key)
		die('error');

	$node_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `node_public_key`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$user_id}
			LIMIT 1
			", 'fetch_one' );
	if ($my_node_key != $node_key )
		die('$my_node_key != $node_key');
}

print 'ok';

?>