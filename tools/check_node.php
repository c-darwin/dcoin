<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($_REQUEST['table'] && $_REQUEST['row'] && $_REQUEST['col']){
	$table = $_REQUEST['table'];
	$row = $_REQUEST['row'];
	$col = $_REQUEST['col'];
	$all_tables = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');
	if ( !in_array($table, $all_tables) || substr($table, 0, 3)=='my_' )
		die ('error table');
	if ( !check_input_data ($row, 'bigint') )
		die ('error row');
	if ( !check_input_data ($col, 'bigint') )
		die ('error col');

	if (!$row) {
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SHOW TABLE STATUS LIKE '{$table}'
				", 'fetch_array');
		$row = $data['Rows'] -1 ;
	}

	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."{$table}`
			LIMIT $row, 1
			");
	$data =  $db->fetchArrayNum($res);
	print $data[$col];
}
else {

	$all_counts = array();
	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `current_version`,
						 `block_id`
			FROM `".DB_PREFIX."info_block`
			", 'fetch_array');
	$all_counts['time'] = time();
	$all_counts['block_id'] = $data['block_id'];
	$all_counts['db_version'] = $data['current_version'];
	$all_counts['file_version'] = file_get_contents( ABSPATH . 'version' );
	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT sum(`amount`) as amount,
						 sum(`tdc_amount`) as tdc_amount
			FROM `".DB_PREFIX."promised_amount`
			WHERE `del_block_id`=0
			", 'fetch_array');
	$all_counts['sum_promised_amount'] = $data['amount'];
	$all_counts['sum_promised_tdc_amount'] = $data['tdc_amount'];
	$all_counts['sum_wallets_amount'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT sum(`amount`)
			FROM `".DB_PREFIX."wallets`
			", 'fetch_one');

	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');
	foreach($tables_array as $table) {
		if (substr($table, 0, 3) != 'my_' && substr($table, 0, 3) != '_my') {
			$count = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(*)
					FROM `".DB_PREFIX."{$table}`
					", 'fetch_one');
			$all_counts[$table] = $count;
		}
	}
	print json_encode($all_counts);
}
?>