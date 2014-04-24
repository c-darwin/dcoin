<?php

if (!defined('DC'))
	die('!DC');
$show = false;
// проверим, есть ли сообщения от админа
$data =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."alert_messages`
			WHERE `close` = 0
			ORDER BY `id` DESC
			", 'fetch_array');
if ($data['message']) {
	$data['message'] = json_decode($data['message'], true);
	if (isset($data['message'][$lang]))
		$data['message'] = $data['message'][$lang];
	else
		$data['message'] = $data['message']['gen'];

	if ( $data['currency_list'] != 'ALL') {
		// проверим, есть ли у нас обещанные суммы с такой валютой
		$promised_amount =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `currency_id` IN ({$data['currency_list']})
				LIMIT 1
				", 'fetch_one');
		if ($promised_amount)
			$show = true;
	}
	else
		$show = true;
}

if ($show) {
	print "<div class=\"container\"><script>
			$('#close_alert').bind('click', function () {
				$.post( 'ajax/close_alert.php', {
					'id' : '{$data['id']}'
				} );
			});
			</script>
			<div class='alert alert-error' >
			    <button id='close_alert' type='button' class='close' data-dismiss='alert'>&times;</button>
			    <h4>Warning!</h4>
			    {$data['message']}
			    </div>
			    </div>";
}

// сообщение о новой версии движка
$my_ver = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `current_version`
			FROM `".DB_PREFIX."info_block`
			", 'fetch_one');
$new_ver =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `version`
			FROM `".DB_PREFIX."new_version`
			WHERE `alert` = 1
			", 'array');
$new_max_ver = 0;
for ($i=0; $i<sizeof($new_ver); $i++) {
	if (version_compare($new_ver[$i], $my_ver) == 1 && !$new_max_ver)
		$new_max_ver = $new_ver[$i];
	if (version_compare($new_ver[$i], $new_max_ver) == 1 && $new_max_ver)
		$new_max_ver = $new_ver[$i];
}
if ($new_max_ver) {
	$lng['new_version'] = str_ireplace('[ver]', $new_max_ver, $lng['new_version']);
		echo "<script>
				$('#btn_install').bind('click', function () {
					$('#new_version_text').text('Please wait');
					$.post( 'ajax/install_new_version.php', {}, function(data) {
						$('#new_version_text').text(data);
					});
				});
				$('#btn_upgrade').bind('click', function () {
					$('#new_version_text').text('Please wait');
					$.post( 'ajax/upgrade_to_new_version.php', {}, function(data) {
						$('#new_version_text').text(data);
					});
				});
			</script>
			<div class=\"container\"><div class='alert alert-error' >
			    <button id='close_alert' type='button' class='close' data-dismiss='alert'>&times;</button>
			    <h4>Warning!</h4>
			   <div id='new_version_text'>{$lng['new_version']}</div>
			    </div>
			    </div>";
}

$variables_ = ParseData::get_variables($db, array('alert_error_time'));
$my_miner_id = get_my_miner_id($db);
// если юзер уже майнер, то у него должно быть настроено точное время
if ($my_miner_id) {
	$diff = intval(ntp_time());
	if ($diff > $variables_['alert_error_time']) {
		$lng['alert_time'] = str_ireplace('[sec]', $diff, $lng['alert_time']);
		echo "<div class=\"container\"><div class='alert alert-error' >
			    <button id='close_alert' type='button' class='close' data-dismiss='alert'>&times;</button>
			    <h4>Warning!</h4>
			   <div>{$lng['alert_time']}</div>
			    </div>
			    </div>";
	}
}

// после обнуления таблиц my_node_key будет пуст
// получим время из последнего блока
$last_block_bin = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `data`
		FROM `".DB_PREFIX."block_chain`
		ORDER BY `id` DESC
		LIMIT 1
		", 'fetch_one');
ParseData::string_shift( $last_block_bin, 5 );
$block_time = ParseData::binary_dec_string_shift( $last_block_bin, 4 );
// дождемся загрузки свежих блоков
// if (time() - $block_time < 600) { закомменчено, т.к. при ручном откате до какого-то блока время будет старое
	$my_node_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `private_key`
			FROM `".DB_PREFIX."my_node_keys`
			WHERE `block_id` > 0
			LIMIT 1
			", 'fetch_one');
	if (!$my_node_key && $my_miner_id>0) {
		echo "<div class=\"container\"><div class='alert alert-error' >
			    <button id='close_alert' type='button' class='close' data-dismiss='alert'>&times;</button>
			    <h4>Warning!</h4>
			   <div>{$lng['alert_change_node_key']}</div>
			    </div>
			    </div>";
	}
//}

?>