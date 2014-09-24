<?php

$daemons = array();
$daemons[] = 'notifications.php'; // main_lock не используем, т.к. ничего критичного не обновляется
$daemons[] = 'connector.php'; // main_lock не используем, т.к. ничего критичного не обновляется
$daemons[] = 'disseminator.php'; // main_lock не используем, т.к. обнолвляется только `sent` в transactions
$daemons[] = 'queue_parser_testblock.php'; // main_lock на всё
$daemons[] = 'queue_parser_tx.php'; // main_lock на всё
$daemons[] = 'queue_parser_blocks.php'; // main_lock на всё
$daemons[] = 'blocks_collection.php'; // main_lock на всё
$daemons[] = 'node_voting.php'; // main_lock на всё. Рестарт каждые 3-5 минут
$daemons[] = 'testblock_generator.php'; // main_lock в 3-х местах
$daemons[] = 'testblock_is_ready.php'; // main_lock в 2-х местах
$daemons[] = 'testblock_disseminator.php'; // main_lock не используем, т.к. ничего критичного не обновляется
$daemons[] = 'pct_generator.php'; // main_lock на всё. Рестарт каждые 5-10 минут
$daemons[] = 'reduction_generator.php'; // main_lock на всё. Рестарт каждые 5-10 минут
$daemons[] = 'max_promised_amount_generator.php'; // main_lock на всё. Рестарт каждые 5-10 минут
$daemons[] = 'max_other_currencies_generator.php'; // main_lock на всё. Рестарт каждые 5-10 минут
$daemons[] = 'clear.php'; // просто чистит таблы от старых данных
//$daemons[] = 'generate_new_node_key.php'; временно отключим
$daemons[] = 'cleaning_db.php'; // main_lock используем, лочим, если там висит чужой лок более 10-и минут
//$daemons[] = '_tmp_fill_data.php'; // просто генерит тр-ии без локов. временное.
$daemons[] = 'cf_projects.php'; // geo + фундеры в cf_projects
$daemons[] = 'elections_admin.php'; //

if (file_exists(ABSPATH.'config_stend.ini')) {

	// только у админа
	$daemons[] = '_tx/_tmp_new_user.php';
	$daemons[] = '_tx/_tmp_new_miner.php';
	$daemons[] = '_tx/_tmp_voting_for_miner.php';
	$daemons[] = '_tx/_tmp_send_dc.php';
	$daemons[] = '_tx/_tmp_unban_miner.php';
	$daemons[] = '_tx/_tmp_ban_miner.php';
	$daemons[] = '_tx/_tmp_write_abuse.php';
	$daemons[] = '_tx/_tmp_new_promised_amount.php';
	$daemons[] = '_tx/_tmp_votes_promised_amount.php';
	$daemons[] = '_tx/_tmp_votes_complex.php';
	$daemons[] = '_tx/_tmp_mining.php';
	$daemons[] = '_tx/_tmp_cash_request_out.php';
	$daemons[] = '_tx/_tmp_cash_request_in.php';
	$daemons[] = '_tx/_tmp_new_holidays.php';
	$daemons[] = '_tx/_tmp_change_host.php';
	$daemons[] = '_tx/_tmp_change_promised_amount.php';
	$daemons[] = '_tx/_tmp_del_promised_amount.php';
	$daemons[] = '_tx/_tmp_change_geolocation.php';
	$daemons[] = '_tx/_tmp_change_commission.php';
	$daemons[] = '_tx/_tmp_new_miner_update.php';
	$daemons[] = '_tx/_tmp_admin_variables.php';
	$daemons[] = '_tx/_tmp_admin_spots.php';
	$daemons[] = '_tx/_tmp_admin_message.php';
	$daemons[] = '_tx/_tmp_admin_new_version.php';
	$daemons[] = '_tx/_tmp_admin_new_version_alert.php';
	$daemons[] = '_tx/_tmp_message_to_admin.php';
	$daemons[] = '_tx/_tmp_admin_blog.php';
	$daemons[] = '_tx/_tmp_new_forex_order.php';
	$daemons[] = '_tx/_tmp_del_forex_order.php';
}

?>
