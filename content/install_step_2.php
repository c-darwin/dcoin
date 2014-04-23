<?php
if (!defined('DC')) die("!defined('DC')");

if (file_exists(ABSPATH . 'db_config.php')){
	require_once(ABSPATH . 'db_config.php');
	$tpl['mysql_host'] = DB_HOST;
	$tpl['mysql_port'] = DB_PORT;
	$tpl['mysql_db_name'] = DB_NAME;
	$tpl['mysql_username'] = DB_USER;
	$tpl['mysql_password'] = DB_PASSWORD;
	$tpl['mysql_prefix'] = DB_PREFIX;
}
else {
	$tpl['mysql_host'] = '';
	$tpl['mysql_port'] = '';
	$tpl['mysql_db_name'] = '';
	$tpl['mysql_username'] = '';
	$tpl['mysql_password'] = '';
	$tpl['mysql_prefix'] = '';
}

require_once( ABSPATH . 'templates/install_step_2.tpl' );

?>