<?php

if (!defined('DC'))
	die('!DC');

if (preg_match('/install/', $_REQUEST['tpl_name']))
	return true;

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
	print "
			<script>
			$('#close_alert').bind('click', function () {
				$.post( 'ajax/close_alert.php', {
					'id' : '{$data['id']}'
				} );
			});
			</script>
			 <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
			 <h4>Warning!</h4>
			    {$data['message']}
			  </div>
			  ";
}

// сообщение о новой версии движка
$my_ver = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `current_version`
			FROM `".DB_PREFIX."info_block`
			", 'fetch_one');

// возможны 2 сценария:
// 1. информация о новой версии есть в блоках
$new_ver =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `version`
			FROM `".DB_PREFIX."new_version`
			WHERE `alert` = 1
			", 'array');
$new_max_ver = '0';
for ($i=0; $i<sizeof($new_ver); $i++) {
	if (version_compare($new_ver[$i], $my_ver) == 1 && !$new_max_ver)
		$new_max_ver = $new_ver[$i];
	if (version_compare($new_ver[$i], $new_max_ver) == 1 && $new_max_ver)
		$new_max_ver = $new_ver[$i];
}
// 2. информации о новой версии нет в блоках, есть только файл version с указанием новой версии
if ($new_max_ver) {
	$lng['new_version'] = str_ireplace('[ver]', $new_max_ver, $lng['new_version']);
}
else {
	$new_ver = @file_get_contents( ABSPATH . 'version' );
	if (version_compare($new_ver, $my_ver) == 1) {
		$new_max_ver = $new_ver;
		$lng['new_version'] = str_ireplace('[ver]', $new_max_ver, $lng['new_version_wo_block']);
	}
}

// для пулов и ограниченных юзеров выводим сообщение без кнопок
$community = get_community_users($db);
if (($community || !empty($_SESSION['restricted'])) && $new_max_ver) {
	$lng['new_version'] = str_ireplace('[ver]', $new_max_ver, $lng['new_version_pulls']);
}

if ($new_max_ver && $my_ver) {

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

			<div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
			    <h4>Warning!</h4>
			   <div id='new_version_text'>{$lng['new_version']}</div>
			 </div>
			 ";
}

if (empty($_SESSION['restricted']) && !defined('COMMUNITY')) {
	$variables_ = ParseData::get_variables($db, array('alert_error_time'));
	$my_miner_id = get_my_miner_id($db);
	// если юзер уже майнер, то у него должно быть настроено точное время
	if ($my_miner_id) {
		$diff = intval(ntp_time());
		if ($diff > $variables_['alert_error_time']) {
			$lng['alert_time'] = str_ireplace('[sec]', $diff, $lng['alert_time']);
			echo "
					 <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
				     <h4>Warning!</h4>
				     <div>{$lng['alert_time']}</div>
				     </div>
				     ";
		}
	}
}

if (empty($_SESSION['restricted'])) {
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
				FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`
				WHERE `block_id` > 0
				LIMIT 1
				", 'fetch_one');
		$my_miner_id = get_my_miner_id($db);
		if (!$my_node_key && $my_miner_id>0) {
			echo "
					 <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
				     <h4>Warning!</h4>
				     <div>{$lng['alert_change_node_key']}</div>
				     </div>
				     ";
		}
	//}
}
// просто информируем, что в данном разделе у юзера нет прав
$skip_community = array('node_config', 'nulling', 'start_stop');
$skip_restricted_users = array('node_config', 'change_node_key', 'nulling', 'start_stop', 'cash_requests_in', 'cash_requests_out', 'upgrade', 'notifications');
if ( (!node_admin_access($db) && in_array($tpl_name, $skip_community)) ||  (!empty($_SESSION['restricted']) && in_array($tpl_name, $skip_restricted_users)) ) {
	echo "
			  <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
			  <h4>Warning!</h4>
			  <div>{$lng['permission_denied']}</div>
			  </div>
			  ";

}

// информируем, что у юзера нет прав и нужно стать майнером
$miners_only = array('cash_requests_in', 'cash_requests_out', 'change_node_key', 'voting', 'geolocation', 'promised_amount_list', 'promised_amount_add', 'holidays_list', 'new_holidays', 'points', 'tasks', 'change_host', 'new_user', 'change_commission');
if (in_array($tpl_name, $miners_only)) {
	$miner_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `miner_id`
			FROM `".DB_PREFIX."users`
			LEFT JOIN `".DB_PREFIX."miners_data` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX."miners_data`.`user_id`
			WHERE `".DB_PREFIX."users`.`user_id` = {$user_id}
			LIMIT 1
			", 'fetch_one');
	//print $db->printsql();
	if (!$miner_id) {
		echo "
				 <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
				 <h4>Warning!</h4>
				 <div>{$lng['only_for_miners']}</div>
				 </div>
				 ";
	}
}

// информируем, что необходимо вначале сменить праймари-ключ
$primary_key_alert = array('wallets_list', 'upgrade', 'upgrade_0', 'upgrade_1', 'upgrade_2', 'upgrade_3', 'upgrade_4', 'upgrade_5', 'notifications');
if (in_array($tpl_name, $primary_key_alert)) {
	$log_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `log_id`
			FROM `".DB_PREFIX."users`
			WHERE `user_id` = {$user_id}
			LIMIT 1
			", 'fetch_one');
	if (!$log_id) {
		echo "
				  <div class=\"alert alert-danger alert-dismissable\" style='margin-top: 30px'><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">×</button>
				  <h4>Warning!</h4>
				  <div>{$lng['alert_change_primary_key']}</div>
				  </div>
				  ";
	}
}

?>