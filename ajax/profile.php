<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$user_id = $_REQUEST['user_id'];

if ( !check_input_data ($user_id , 'int') )
	die('error user_id');

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$user_id}
				LIMIT 1
				", 'fetch_array' );
// получим ID майнеров, у которых лежат фото нужного нам юзера
$miners_ids = ParseData::get_miners_keepers($data['photo_block_id'], $data['photo_max_miner_id'],  $data['miners_keepers'], true);

// берем 1 случайный из 10-и ID майнеров
$r = array_rand($miners_ids, 1);
$miner_id = $miners_ids[$r];

$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `miner_id` = {$miner_id}
				LIMIT 1
				", 'fetch_one' );

echo json_encode(
	array( 'face'=>"{$host}public/face_{$user_id}.jpg",
			  'profile'=>"{$host}public/profile_{$user_id}.jpg"
			)
);

?>