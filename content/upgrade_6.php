<?php

$tpl['geolocation'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `geolocation`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_one');
if (!$tpl['geolocation']) {
	$tpl['geolocation_lat'] = false;
	$tpl['geolocation_lon'] = false;
}
else {
	$x = explode(', ', $tpl['geolocation']);
	$tpl['geolocation_lat'] = $x[0];
	$tpl['geolocation_lon'] = $x[1];
}

require_once( ABSPATH . 'templates/upgrade_6.tpl' );

?>