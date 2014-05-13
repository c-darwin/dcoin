<?php
if (!defined('DC')) die("!defined('DC')");

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `race`,
					 `country`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_array');
$tpl['race'] = $data['race'];
$tpl['country'] = $data['country'];
$tpl['countries'] = $countries;

require_once( ABSPATH . 'templates/change_country_race.tpl' );

?>