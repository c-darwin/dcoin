<?php
if (!defined('DC')) die("!defined('DC')");


$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `email`,
					 `sms_http_get_request`,
					 `use_smtp`,
					 `smtp_server`,
					 `smtp_port`,
					 `smtp_ssl`,
					 `smtp_auth`,
					 `smtp_username`,
					 `smtp_password`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_array');
$tpl['data'] = $data;

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.MY_PREFIX.'my_notifications`
		ORDER BY `sort` ASC
		');
while ($row = $db->fetchArray($res))
	$tpl['my_notifications'][$row['name']] = array('email'=>$row['email'], 'sms'=>$row['sms'], 'important'=>$row['important']);

require_once( ABSPATH . 'templates/notifications.tpl' );

?>
