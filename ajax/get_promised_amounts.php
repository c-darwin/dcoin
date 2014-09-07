<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

header("Access-Control-Allow-Origin: *");

$currency_list = get_currency_list($db);
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT  sum(`amount`) as `sum`,
					 `currency_id`
		FROM `".DB_PREFIX."promised_amount`
		LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."promised_amount`.`user_id`
		WHERE `".DB_PREFIX."promised_amount`.`status` = 'mining' AND
					  `del_block_id` = 0 AND
					  `currency_id` !=1
		GROUP BY `currency_id`
		");
while ( $row = $db->fetchArray( $res ) ) {
	echo "<tr><td>{$currency_list[$row['currency_id']]}</td><td>{$row['sum']}</td></tr>";
}

?>