<?php
session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (@$_REQUEST['parameters']=='lang=42' || $_REQUEST['parameters']['lang_id']=='42') {
	$lang = 42;
	setlang($lang);
}
else if (@$_REQUEST['parameters']=='lang=1' || $_REQUEST['parameters']['lang_id']=='1') {
	$lang = 1;
	setlang($lang);
}

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$tpl['cf_url'] = get_cf_url();
if (!$tpl['cf_url'])
	die ('access denied');

if ( isset($_REQUEST['tpl_name']) && check_input_data($_REQUEST['tpl_name'], 'tpl_name') )
	require_once( ABSPATH . 'content/'.$_REQUEST['tpl_name'].'.php' );
else
	require_once( ABSPATH . 'content/cf_catalog.php' );

?>