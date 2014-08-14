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

$gzip = false;

$my_tables = array('my_admin_messages','my_cash_requests','my_comments','my_commission','my_complex_votes','my_dc_transactions','my_holidays','my_keys','my_new_users','my_node_keys','my_notifications','my_promised_amount','my_table');

if (!node_admin_access($db))
	die ('Permission denied');

$tables_cmd = '';
$dump_user_id = intval($_REQUEST['dump_user_id']);
if ($dump_user_id) {
	foreach ($my_tables as $table)
		$tables_cmd .= "{$dump_user_id}_{$table} ";
}
else {
	$community_users = get_community_users($db);
	for ($i=0; $i<sizeof($community_users); $i++) {
		foreach ($my_tables as $table)
			$tables_cmd .= "{$community_users[$i]}_{$table} ";
	}
}

if ($gzip) {
	$filename = "backup-" . date("d-m-Y-H-i-s") . ".sql.gz";
	header( "Content-Type: application/x-gzip" );
	$add_cmd = ' | gzip --best"';
}
else{
	$filename = "backup-" . date("d-m-Y-H-i-s") . ".sql";
	header( "Content-Type: text/plain" );
	$add_cmd='';
}
header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

$cmd = "mysqldump  -u ".DB_USER." --password=".DB_PASSWORD."  -h".DB_HOST." --default-character-set=binary  --add-drop-database --databases ".DB_NAME." --tables {$tables_cmd} --lock-tables=false --skip-add-locks {$add_cmd}";
passthru( $cmd );

exit(0);

?>