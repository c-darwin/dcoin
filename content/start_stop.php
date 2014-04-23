<?php
if (!defined('DC')) die("!defined('DC')");

$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name == 'my_lock')
	$tpl['my_status'] = 'OFF';
else
	$tpl['my_status'] = 'ON';

require_once( ABSPATH . 'templates/start_stop.tpl' );

?>