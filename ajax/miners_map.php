<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

$my_user_id = $_SESSION['user_id'];

$min_amount = $_REQUEST['min_amount'];
$currency_id = $_REQUEST['currency_id'];

$max_promised_amounts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `amount`
		FROM `".DB_PREFIX."max_promised_amounts`
		WHERE `currency_id` = {$currency_id}
		ORDER BY `time` DESC
		LIMIT 1
		", 'fetch_one');

$print = '';
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `amount`,
					 `latitude`,
					 `longitude`,
					 `".DB_PREFIX."promised_amount`.`user_id`
		FROM `".DB_PREFIX."promised_amount`
		LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."promised_amount`.`user_id`
		WHERE `".DB_PREFIX."promised_amount`.`status` = 'mining' AND
					 `currency_id` = {$currency_id} AND
					  `".DB_PREFIX."promised_amount`.`user_id`!={$my_user_id} AND
					  `del_block_id` = 0
		");
while ( $row = $db->fetchArray($res) ) {

	//print_R($row);
	$repaid = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'repaid' AND
							 `currency_id` = {$currency_id} AND
							 `user_id` = {$row['user_id']} AND
							 `del_block_id` = 0
				", 'fetch_one');
	//print $max_promised_amounts."\n";
	//print $repaid."\n";
	if ($repaid + $row['amount'] < $max_promised_amounts)
         $return_amount =  $row['amount'];
	else
		$return_amount = $max_promised_amounts-$repaid;

	if ($return_amount<=0)
		continue;

	$print.="{\"user_id\": {$row['user_id']},\"amount\": {$return_amount},\"longitude\": {$row['longitude']}, \"latitude\": {$row['latitude']}},";
}

print '{ "info": ['.substr($print, 0, -1).']}';

?>