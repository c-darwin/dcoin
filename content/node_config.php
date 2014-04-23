<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `host`,
					 `in_connections_ip_limit`,
					 `in_connections`,
					 `out_connections`
		FROM `'.DB_PREFIX.'my_table`
		', 'fetch_array');

$tpl['config_ini'] = file_get_contents( ABSPATH . 'config.ini' );

require_once( ABSPATH . 'templates/node_config.tpl' );

?>