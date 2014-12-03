<?php
if (!defined('DC')) die("!defined('DC')");
/*
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."reduction`
		ORDER BY `time` DESC
		");
while ($row = $db->fetchArray($res)) {
	$tpl['reduction'][] = array('time'=>date('d/m/Y H:i:s', $row['time']), 'currency_id'=>$row['currency_id'], 'pct'=>$row['pct'], 'block_id'=>$row['block_id']);
}

$tpl['currency_list'] = get_currency_list($db);

require_once(ABSPATH . 'templates/reduction.tpl');*/
?>