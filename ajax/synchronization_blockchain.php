<?php
session_start();

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
if (file_exists(ABSPATH . 'db_config.php')) {

	require_once( ABSPATH . 'db_config.php' );
	require_once( ABSPATH . 'includes/autoload.php' );

	$lang = get_lang();

	require_once( ABSPATH . 'lang/'.$lang.'.php' );

	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	$block_id = 0;
	$block_time = 0;
	$data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT `block_id`,
						 `time`
			FROM `" . DB_PREFIX . "info_block`
			", 'fetch_array');
	$block_id = $data['block_id'];
	$block_time = $data['time'];
	// если время более 12 часов от текущего, то выдаем не подвержденные, а просто те, что есть в блокчейне
	if ( (time() - $block_time) < 3600*12 ) {
		$confirmed_block_id = get_confirmed_block_id($db);
		if ($confirmed_block_id) {
			// получим время из последнего подвержденного блока
			$last_block_bin = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `" . DB_PREFIX . "block_chain`
				WHERE `id` = {$confirmed_block_id}
				LIMIT 1
				", 'fetch_one');
			ParseData::string_shift($last_block_bin, 1); // уберем тип
			$block_id = ParseData::binary_dec_string_shift($last_block_bin, 4);
			$block_time = ParseData::binary_dec_string_shift($last_block_bin, 4);
			if ( time() - $block_time < 3600 ) {
				$block_id = -1;
				$block_time = -1;
			}
		}
	}

	print json_encode(
			array(
				'block_id'=>$block_id,
				'block_time'=>$block_time
			)
	);
}

?>