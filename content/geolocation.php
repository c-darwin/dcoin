<?php
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

$tpl['data']['type'] = 'change_geolocation';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

if (empty($_SESSION['restricted'])) {
	$tpl['geolocation'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT `geolocation`
			FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
			 ', 'fetch_one');
}
if (!$tpl['geolocation'])
	$tpl['geolocation'] = '39.94887, -75.15005';

$x = explode(', ', $tpl['geolocation']);
$tpl['geolocation_lat'] = $x[0];
$tpl['geolocation_lon'] = $x[1];

$tpl['country'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `country`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
$tpl['countries'] = $countries;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_change_geolocation', 'limit_change_geolocation_period') );

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_change_geolocation'], $tpl['periods'][$tpl['variables']['limit_change_geolocation_period']]), $lng['geolocation_limits_text']);

require_once( ABSPATH . 'templates/geolocation.tpl' );

?>