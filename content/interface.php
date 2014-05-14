<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `in_connections_ip_limit`,
					 `in_connections`,
					 `out_connections`
		FROM `'.DB_PREFIX.'config`
		', 'fetch_array');

$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name == 'my_lock')
	$tpl['my_status'] = 'OFF';
else
	$tpl['my_status'] = 'ON';

$tpl['config_ini'] = file_get_contents( ABSPATH . 'config.ini' );

require_once( ABSPATH . 'templates/node_config.tpl' );

?>