<?php
session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(__FILE__) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );



require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$max_block_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT `block_id` FROM `'.DB_PREFIX.'info_block`', 'fetch_one' );
echo $max_block_id;

?>