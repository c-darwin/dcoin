<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$currency_list = get_currency_list($db);
$print = '';
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `user_id`,
					 `latitude`,
					 `longitude`
		FROM `".DB_PREFIX."miners_data`
		WHERE `status` = 'miner' AND
				 	`user_id`>7 AND
				 	`user_id`!=106
		");
while ( $row = $db->fetchArray($res) ) {
	$print.="{\"user_id\": {$row['user_id']},\"longitude\": {$row['longitude']}, \"latitude\": {$row['latitude']}},";
}
header("Access-Control-Allow-Origin: *");
print '{ "info": ['.substr($print, 0, -1).']}';

?>