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
		SELECT `amount`,
					 `currency_id`,
					 `latitude`,
					 `longitude`
		FROM `".DB_PREFIX."promised_amount`
		LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."promised_amount`.`user_id`
		WHERE `".DB_PREFIX."promised_amount`.`status` = 'mining' AND
					  `del_block_id` = 0 AND
					  `currency_id` !=1
		");
while ( $row = $db->fetchArray($res) ) {
	$print.="{\"amount\": {$row['amount']},\"currency\": \"{$currency_list[$row['currency_id']]}\",\"longitude\": {$row['longitude']}, \"latitude\": {$row['latitude']}},";
}
header("Access-Control-Allow-Origin: *");
print '{ "info": ['.substr($print, 0, -1).']}';

?>