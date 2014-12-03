<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('');
$user_id = intval($_SESSION['user_id']);
	
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

define('MY_PREFIX', get_my_prefix($db));

$tpl['progress_bar_pct'] = array();
$tpl['progress_bar_pct']['begin'] = 10;
$tpl['progress_bar_pct']['change_key'] = 10;
$tpl['progress_bar_pct']['my_table'] = 5;
$tpl['progress_bar_pct']['upgrade_country'] = 3;
$tpl['progress_bar_pct']['upgrade_face_hash'] = 3;
$tpl['progress_bar_pct']['upgrade_profile_hash'] = 3;
$tpl['progress_bar_pct']['upgrade_face_coords'] = 3;
$tpl['progress_bar_pct']['upgrade_profile_coords'] = 3;
$tpl['progress_bar_pct']['upgrade_video'] = 3;
$tpl['progress_bar_pct']['upgrade_host'] = 3;
$tpl['progress_bar_pct']['upgrade_geolocation'] = 3;
$tpl['progress_bar_pct']['promised_amount'] = 5;
$tpl['progress_bar_pct']['commission'] = 3;
$tpl['progress_bar_pct']['tasks'] = 8;
$tpl['progress_bar_pct']['vote'] = 5;
$tpl['progress_bar_pct']['referral'] = 1;

$tpl['progress_bar'] = array();

// сменил ли юзер ключ
$change_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `log_id`
		FROM `".DB_PREFIX."users`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('change_primary_key')));
if (!empty($tpl['last_tx'][0]['queue_tx']) || !empty($tpl['last_tx'][0]['tx']) || $change_key) {
	$tpl['progress_bar']['change_key'] = 1;
}

// есть в БД личная юзерсая таблица
if (get_community_users($db)) {
	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');
	if (in_array("{$user_id}_my_table", $tables_array)) {
		$tpl['progress_bar']['my_table'] = 1;
	}
}
else {
	$tpl['progress_bar']['my_table'] = 1;
}

// апгрейд аккаунта
$my_miner_id = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `miner_id`
		FROM `" . DB_PREFIX . "miners_data`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
if ($my_miner_id) {
	$tpl['progress_bar']['upgrade_country'] = 1;
	$tpl['progress_bar']['upgrade_face_hash'] = 1;
	$tpl['progress_bar']['upgrade_profile_hash'] = 1;
	$tpl['progress_bar']['upgrade_face_coords'] = 1;
	$tpl['progress_bar']['upgrade_profile_coords'] = 1;
	$tpl['progress_bar']['upgrade_video'] = 1;
	$tpl['progress_bar']['upgrade_host'] = 1;
	$tpl['progress_bar']['upgrade_geolocation'] = 1;
}
else if(empty($_SESSION['restricted'])) {
	$upgrade_data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, '
			SELECT `user_id`,
						 `race`,
						 `country`,
						 `geolocation`,
						 `host`,
						 `face_coords`,
						 `profile_coords`,
						 `video_url_id`,
						 `video_type`
			FROM `' . DB_PREFIX . MY_PREFIX . 'my_table`
			', 'fetch_array');
	if ($upgrade_data['race'] && $upgrade_data['country'])
		$tpl['progress_bar']['upgrade_country'] = 1;
	if ($upgrade_data['face_hash'])
		$tpl['progress_bar']['upgrade_face_hash'] = 1;
	if ($upgrade_data['profile_hash'])
		$tpl['progress_bar']['upgrade_profile_hash'] = 1;
	if ($upgrade_data['face_coords'])
		$tpl['progress_bar']['upgrade_face_coords'] = 1;
	if ($upgrade_data['profile_coords'])
		$tpl['progress_bar']['upgrade_profile_coords'] = 1;
	if ($tpl['data']['video_url_id'] || file_exists(ABSPATH . "public/{$user_id}_user_video.mp4"))
		$tpl['progress_bar']['upgrade_video'] = 1;
	if ($upgrade_data['host'])
		$tpl['progress_bar']['upgrade_host'] = 1;
	if ($upgrade_data['latitude'] && $upgrade_data['longitude'])
		$tpl['progress_bar']['upgrade_geolocation'] = 1;
}

// добавлена ли обещанная сумма
$promised_amount = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `id`
		FROM `" . DB_PREFIX . "promised_amount`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
// возможно юзер уже отправил запрос на добавление обещенной суммы
$last_tx = get_last_tx($user_id, types_to_ids(array('new_promised_amount')));
if (!empty($last_tx[0]['queue_tx']) || !empty($last_tx[0]['tx']) || $promised_amount) {
	$tpl['progress_bar']['promised_amount'] = 1;
}

// установлена ли комиссия
$commission = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `commission`
		FROM `" . DB_PREFIX . "commission`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
// возможно юзер уже отправил запрос на добавление комиссии
$last_tx = get_last_tx($user_id, types_to_ids(array('change_commission')));
if (!empty($last_tx[0]['queue_tx']) || !empty($last_tx[0]['tx']) || $commission) {
	$tpl['progress_bar']['commission'] = 1;
}


// голосование за параметры валют. для простоты смотрим в голоса за реф %
$vote = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `user_id`
		FROM `" . DB_PREFIX . "votes_referral`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
$last_tx = get_last_tx($user_id, types_to_ids(array('votes_complex')));
if (!empty($last_tx[0]['queue_tx']) || !empty($last_tx[0]['tx']) || $vote) {
	$tpl['progress_bar']['vote'] = 1;
}

if (empty($_SESSION['restricted'])) {
	// выполнялись ли задания
	$my_tasks = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `id`
		FROM `" . DB_PREFIX . MY_PREFIX . "my_tasks`
		", 'fetch_one');
	if ($my_tasks) {
		$tpl['progress_bar']['tasks'] = 1;
	}
}

// сколько майнеров зарегались по ключам данного юзера
$tpl['progress_bar']['referral'] = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`miner_id`)
		FROM `" . DB_PREFIX . "users`
		LEFT JOIN `" . DB_PREFIX . "miners_data` on `" . DB_PREFIX . "miners_data`.`user_id` = `" . DB_PREFIX . "users`.`user_id`
		WHERE `referral` = {$user_id} AND
					 `miner_id` > 0
		", 'fetch_one');

// итог
$tpl['progress_pct'] = $tpl['progress_bar_pct']['begin'];
foreach ($tpl['progress_bar'] as $name=>$result) {
	if ($name == 'referral') {
		$tpl['progress_pct']+=$tpl['progress_bar_pct'][$name]*$result;
	}
	else {
		$tpl['progress_pct']+=$tpl['progress_bar_pct'][$name];
	}
}
$tpl['progress_bar']['begin'] = 1;

if ($include_type!='progress.php') { // может инклудиться из content/progress.php
	$progress_bar = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT `show_progress_bar`
			FROM `" . DB_PREFIX . MY_PREFIX . "my_table`
			", 'fetch_one');
	if ($progress_bar==1)
		require_once(ABSPATH . 'templates/progress_bar.tpl');
}

?>