<?php

$tpl['geolocation'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `geolocation`
		FROM `'.DB_PREFIX.'my_table
		', 'fetch_one');
if (!$tpl['geolocation'])
	$tpl['geolocation'] = '39.94887, -75.15005';

$x = explode(', ', $tpl['geolocation']);
$tpl['geolocation_lat'] = $x[0];
$tpl['geolocation_lon'] = $x[1];

	require_once( ABSPATH . 'templates/upgrade_4.tpl' );

?>