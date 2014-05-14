<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['show_sign_data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `show_sign_data`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_one');

require_once( ABSPATH . 'templates/interface.tpl' );

?>