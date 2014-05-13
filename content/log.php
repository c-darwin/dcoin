<?php
if (!defined('DC')) die("!defined('DC')");


$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT * FROM `'.DB_PREFIX.MY_PREFIX.'my_log` ' );
while ($row = $db->fetchArray($res))
	$tpl['log'][] = $row;

	
require_once( ABSPATH . 'templates/log.tpl' );

?>