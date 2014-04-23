<?php
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

$tpl['data']['type'] = 'geolocation_current';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;
$tpl['data']['geolocation_id'] = $_REQUEST['parameters']['geolocation_id'];

require_once( ABSPATH . 'templates/geolocation_current.tpl' );

?>