<?php
if (!defined('DC')) die("!defined('DC')");

// нужно узнать время последнего блока
$confirmed_block_id = get_confirmed_block_id($db);
$tpl['wait'] = '';
if (!$confirmed_block_id) {
	$first_load_blockchain =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT `first_load_blockchain`
			FROM `'.DB_PREFIX.'config`
			', 'fetch_one');
	if ($first_load_blockchain=='file') {
		$tpl['wait'] = $lng['loading_blockchain_please_wait'];
	}
	else {
		$tpl['wait'] = $lng['is_synchronized_with_the_dc_network'];
	}
}
else {
// получим время из последнего подвержденного блока
	$last_block_bin = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT `data`
			FROM `" . DB_PREFIX . "block_chain`
			WHERE `id` = {$confirmed_block_id}
			LIMIT 1
			", 'fetch_one');
	ParseData::string_shift($last_block_bin, 5); // уберем тип и id блока
	$tpl['block_time'] = ParseData::binary_dec_string_shift($last_block_bin, 4);
	$tpl['block_id'] = $confirmed_block_id;
}

// для сингл-мода, кнопка включения и выключения демонов
if ( !defined('COMMUNITY') ) {
	$script_name = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `" . DB_PREFIX . "main_lock`
		", 'fetch_one');
	if ($script_name == 'my_lock')
		$tpl['start_daemons'] = '<a href="#" id="start_daemons" style="color:#C90600">Start daemons</a>';
	else
		$tpl['start_daemons'] = '';
}

require_once( ABSPATH . 'templates/updating_blockchain.tpl' );
?>