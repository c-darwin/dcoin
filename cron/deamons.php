<?php
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
define( 'CRON_DIR', ABSPATH . 'cron/' );

require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'cron/deamons_inc.php' );

//define('WAIT_SCRIPT', 300);
define('WAIT_SCRIPT', 300);

// ****************************************************************************
//  Берем скрипты, которые более 300 сек не отстукивались в таблицу
// Т.к. данный скрипт запускается каждые 60 сек в nix и работает в цикле в windows, то у всех демнов есть ровно 60 сек,
// чтобы сообщить, что они запущены
// ****************************************************************************
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$my_user_id = get_my_user_id($db);
if ($my_user_id !=1) {

    $n = array_search('_tx/_tmp_new_user.php', $daemons);
	if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_new_miner.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_voting_for_miner.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_send_dc.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_unban_miner.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_ban_miner.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_write_abuse.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_new_promised_amount.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_votes_promised_amount.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_votes_complex.php', $daemons);
    if ($n) unset($daemons[$n]);
    $n = array_search('_tx/_tmp_mining.php', $daemons);
    if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_cash_request_out.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_cash_request_in.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_new_holidays.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_change_host.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_change_promised_amount.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_del_promised_amount.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_change_geolocation.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_change_commission.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_new_miner_update.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_variables.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_spots.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_message.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_new_version.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_new_version_alert.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_message_to_admin.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_admin_blog.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_new_forex_order.php', $daemons);
	if ($n) unset($daemons[$n]);
	$n = array_search('_tx/_tmp_del_forex_order.php', $daemons);
	if ($n) unset($daemons[$n]);

}

$php_path = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `php_path`
		FROM `".DB_PREFIX."my_table`
		", 'fetch_one');
do{

	$lock_script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `script_name`
			FROM `".DB_PREFIX."main_lock`
			", 'fetch_one');
	if ($lock_script_name=='my_lock')
		exit;

	foreach ($daemons as $script_name) {
		// проверим, давно ли отстукивался данный демон
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time`, `script`
				FROM `".DB_PREFIX."deamons`
				WHERE `script` = '{$script_name}'
				", 'fetch_array');
		if ( ($data['time'] > time() - WAIT_SCRIPT) )
			continue;

		if ($data['script'] == 'generate_new_node_key.php' &&  ($data['time'] > time() - NODE_KEY_UPD_TIME) )
			continue;

		if (!$data) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO `".DB_PREFIX."deamons` (
					`script`
				) VALUES (
					'{$script_name}'
				)");
		}

		$cmd = $php_path.' '.CRON_DIR.''.$script_name;
		if (OS == 'WIN')
			pclose(popen("start /B ". $cmd, "r"));
		else
			exec( $cmd.' > /dev/null &' );
		debug_print($cmd , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	// не в винде у нас скрипт запускается по крону раз в минуту, а  винде юзер запускает его сам 1 раз
	if (OS!='WIN')
		break;

	sleep(60);

} while (true);

/*
// если в info_block пусто, значит это первый запуск
$block_id = intval($db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
	SELECT `block_id` FROM `".DB_PREFIX."info_block` LIMIT 1", 'fetch_one' ));
if ($block_id<2) {

	$count = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX."deamons`", 'fetch_one');
	if ($count>0)
		exit;

	if ($count==0) {
		debug_print($daemons , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		foreach ($daemons as $script_name) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT IGNORE INTO `".DB_PREFIX."deamons` (
						`script`,
						`time`
					) VALUES (
						'{$script_name}',
						{$time}
					)");

			//debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}
	}
	$deamons_start = $daemons;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script`, `time`
		FROM `".DB_PREFIX."deamons`
		WHERE `time` < " . ( $time - WAIT_SCRIPT ) );
//debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
$send_message = '';
while ( $row = $db->fetchArray( $res ) ) {
	debug_print($row , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$deamons_start[] = $row['script'];

	if ( $row['time'] < time() - WAIT_SCRIPT )
		$send_message .= "Скрипт \"{$row['script']} \" не запускался более 1 минуты\n";

}

//  Сообщаем на мыло о скриптах, которые не отстукивались
if ($send_message) {
	//mail ();
	print $send_message;
}

//  Запускаем скрипты

debug_print($deamons_start , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

foreach ($deamons_start as $script_name) {
	$cmd = PHP_PATH.' '.CRON_DIR.''.$script_name;
	if (substr(php_uname(), 0, 7) == "Windows")
		pclose(popen("start /B ". $cmd, "r"));
	else
		exec( $cmd.' > /dev/null &' );
	debug_print($cmd , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
}
*/

?>
