<?php
if (!defined('DC')) die("!defined('DC')");

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT * FROM `'.DB_PREFIX.'alert_messages` ORDER BY `id` DESC' );
while ($row = $db->fetchArray($res)) {

	$show = false;

	if ( $row['currency_list'] != 'ALL') {

		// проверим, есть ли у нас обещнные суммы с такой валютой
		$amounts =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `currency_id` IN ({$row['currency_list']})
				LIMIT 1
				", 'fetch_one');
		if ($amounts)
			$show = true;
	}
	else
		$show = true;

	if ($show)
		$tpl['alert_messages'][] = $row;
}

require_once( ABSPATH . 'templates/information.tpl' );

?>