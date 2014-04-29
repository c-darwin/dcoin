<?php

session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$type = ParseData::findType($_REQUEST['type']);
$time = $_REQUEST['time'];
$user_id = $_REQUEST['user_id'];
$signature1 = hextobin($_POST['signature1']);
$signature2 = hextobin(@$_POST['signature2']);
$signature3 = hextobin(@$_POST['signature3']);
$sign = ParseData::encode_length_plus_data($signature1);
if ($signature2)
	$sign .= ParseData::encode_length_plus_data($signature2);
if ($signature3)
	$sign .= ParseData::encode_length_plus_data($signature3);
$bin_signatures = ParseData::encode_length_plus_data($sign);

	switch ($_REQUEST['type']) {

	case 'new_user' :

		$public_key = hextobin($_REQUEST['public_key']);
		$private_key = $_POST['private_key'];

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO  `".DB_PREFIX."my_new_users` (
					`public_key`,
					`private_key`
				)
				VALUES (
					0x{$_REQUEST['public_key']},
					'{$private_key}'
				)");


		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					ParseData::encode_length_plus_data($user_id) .
					ParseData::encode_length_plus_data($public_key) .
					$bin_signatures;

		break;

	case 'new_miner' :

		$race = $_REQUEST['race'];
		$country = $_REQUEST['country'];
		$latitude = $_REQUEST['latitude'];
		$longitude = $_REQUEST['longitude'];
		$host = $_REQUEST['host'];
		$face_hash = $_REQUEST['face_hash'];
		$profile_hash = $_REQUEST['profile_hash'];
		$face_coords = $_REQUEST['face_coords'];
		$profile_coords = $_REQUEST['profile_coords'];
		$video_type = $_REQUEST['video_type'];
		$video_url_id = $_REQUEST['video_url_id'];
		$node_public_key = $_REQUEST['node_public_key'];

		if (!$race || !$country || !$latitude || !$longitude || !$host || !$face_hash || !$profile_hash || !$face_coords || !$profile_coords || !$video_type || !$video_url_id || !$node_public_key) {
			die('error');
		}
		if ($video_type=='null' || $video_url_id=='null') {
			if ( !file_exists(ABSPATH.'public/user_video.mp4') || ( !file_exists(ABSPATH.'public/user_video.ogv') && !file_exists(ABSPATH.'public/user_video.webm') ) ) {
				die('empty video');
			}
		}

		$node_public_key = hextobin($node_public_key);
		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($race)) . $race .
					encode_length(strlen($country)) . $country .
					encode_length(strlen($latitude)) . $latitude .
					encode_length(strlen($longitude)) . $longitude .
					encode_length(strlen($host)) . $host .
					encode_length(strlen($face_coords)) . $face_coords .
					encode_length(strlen($profile_coords)) . $profile_coords .
					encode_length(strlen($face_hash)) . $face_hash .
					encode_length(strlen($profile_hash)) . $profile_hash .
					encode_length(strlen($video_type)) . $video_type .
					encode_length(strlen($video_url_id)) . $video_url_id .
					encode_length(strlen($node_public_key)) . $node_public_key .
					$bin_signatures;

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."my_table`
				SET `node_voting_send_request` = {$time}
				");

		break;

	case 'votes_miner' : // голос за юзера, который хочет стать майнером

		$vote_id = $_REQUEST['vote_id'];
		$result = $_REQUEST['result'];
		$comment = $_REQUEST['comment'];

		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($vote_id)) . $vote_id .
					encode_length(strlen($result)) . $result .
					encode_length(strlen($comment)) . $comment .
					$bin_signatures;

		break;
			
	case 'votes_tdc_dc' : // голос за майнинг tdc->DC

		$mining_id = $_REQUEST['mining_id'];
		$result = $_REQUEST['result'];
		

		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($mining_id)) . $mining_id .
					encode_length(strlen($result)) . $result .
					$bin_signatures;

		break;

	case 'new_promised_amount' :

		$currency_id = $_REQUEST['currency_id'];
		$amount = $_REQUEST['amount'];
		$video_type = $_REQUEST['video_type'];
		$video_url_id = $_REQUEST['video_url_id'];
		

		print_R($_REQUEST);

		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($currency_id)) . $currency_id .
					encode_length(strlen($amount)) . $amount .
					encode_length(strlen($video_type)) . $video_type .
					encode_length(strlen($video_url_id)) . $video_url_id .
					$bin_signatures;

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO  `".DB_PREFIX."my_promised_amount` (
					currency_id,
					amount
				)
				VALUES (
					{$currency_id},
					{$amount}
				)");

		break;

		case 'change_promised_amount' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$amount = $_REQUEST['amount'];
			$signature = hextobin($_POST['signature']);
			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				encode_length(strlen($amount)) . $amount .
				$bin_signatures;

			break;

		case 'mining' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$amount = $_REQUEST['amount'];
			$signature = hextobin($_POST['signature']);
			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				encode_length(strlen($amount)) . $amount .
				$bin_signatures;

			break;

	case 'promised_amount_add_photo' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$hash = $_REQUEST['hash'];
			$signature = hextobin($_POST['signature']);
			
			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($promised_amount_id)) . $promised_amount_id .
						encode_length(strlen($hash)) . $hash .
						$bin_signatures;

			
			
			break;
			
		case 'votes_promised_amount':

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$result = $_REQUEST['result'];
			$comment = $_REQUEST['comment'];

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($promised_amount_id)) . $promised_amount_id .
						encode_length(strlen($result)) . $result .
						encode_length(strlen($comment)) . $comment .
						$bin_signatures;

			break;

		case 'change_geolocation' :

			$latitude = $_REQUEST['latitude'];
			$longitude = $_REQUEST['longitude'];
			$country = $_REQUEST['country'];
			

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_table`
					SET `geolocation` = '{$latitude}, {$longitude}',
							`location_country` =  {$country}
					");

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($latitude)) . $latitude .
						encode_length(strlen($longitude)) . $longitude .
						encode_length(strlen($country)) . $country .
						$bin_signatures;

			break;

		case 'votes_geolocation' :

			$geolocation_id = $_REQUEST['geolocation_id'];
			$result = $_REQUEST['result'];			
			
			
			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($geolocation_id)) . $geolocation_id .
						encode_length(strlen($result)) . $result .
						$bin_signatures;

			break;

		case 'del_promised_amount' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];


			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				$bin_signatures;

			break;

		case 'del_forex_order' :

			$order_id= $_REQUEST['order_id'];

			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($order_id)) . $order_id .
				$bin_signatures;

			break;
			
		case 'send_dc' :

			$to_user_id = $_REQUEST['to_user_id'];
			$currency_id = $_REQUEST['currency_id'];
			$amount = $_REQUEST['amount'];
			$commission = $_REQUEST['commission'];
			$comment = $_REQUEST['comment'];
			$comment_text = $_REQUEST['comment_text'];
			

			// пишем транзакцкцию к сбе в таблу
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."my_dc_transactions` (
							`status`,
							`type`,
							`type_id`,
							`to_user_id`,
							`amount`,
							`commission`,
							`currency_id`,
							`comment`,
							`comment_status`
						)
						VALUES (
							'pending',
							'from_user',
							{$user_id},
							{$to_user_id},
							{$amount},
							{$commission},
							{$currency_id},
							'{$comment_text}',
							'decrypted'
						)");
			print $db->printsql()."\n";

			if (!$comment)
				$comment = 'null';
			else
				$comment = hextobin($comment);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($to_user_id)) . $to_user_id .
						encode_length(strlen($currency_id)) . $currency_id .
						encode_length(strlen($amount)) . $amount .
						encode_length(strlen($commission)) . $commission .
						encode_length(strlen($comment)) . $comment .
						$bin_signatures;

			break;

		case 'cash_request_out' :

			$to_user_id = $_REQUEST['to_user_id'];
			$currency_id = $_REQUEST['currency_id'];
			$amount = $_REQUEST['amount'];
			$comment = hextobin($_REQUEST['comment']);
			$comment_text = $_REQUEST['comment_text'];
			$hash_code = $_REQUEST['hash_code'];
			$code = $_REQUEST['code'];
			

			print_r($_REQUEST);

			// пишем в личную таблу
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX."my_cash_requests` (
							`to_user_id`,
							`currency_id`,
							`amount`,
							`comment`,
							`code`
						)
						VALUES (
							{$to_user_id},
							{$currency_id},
							'{$amount}',
							'{$comment_text}',
							'{$code}'
						)");

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."my_dc_transactions` (
							`status`,
							`type`,
							`type_id`,
							`to_user_id`,
							`amount`,
							`currency_id`,
							`comment`,
							`comment_status`
						)
						VALUES (
							'pending',
							'cash_request',
							{$user_id},
							{$to_user_id},
							{$amount},
							{$currency_id},
							'{$comment_text}',
							'decrypted'
						)");
			//print $db->printsql()."\n";

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($to_user_id)) . $to_user_id .
						encode_length(strlen($amount)) . $amount .
						encode_length(strlen($comment)) . $comment .
						encode_length(strlen($currency_id)) . $currency_id .
						encode_length(strlen($hash_code)) . $hash_code .
						$bin_signatures;

			break;

		case 'cash_request_in' :

			$cash_request_id = $_REQUEST['cash_request_id'];
			$code = $_REQUEST['code'];
			

			print_r($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($cash_request_id)) . $cash_request_id .
						encode_length(strlen($code)) . $code .
						$bin_signatures;
/*
 * убрано, т.к. тот, кто отдает наличку получает не DC, а TDC и погашенные банкноты
			// нужно получить сумму, которая будет начислена нам на кошелек
			$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `amount`,
								 `currency_id`
					FROM `".DB_PREFIX."cash_requests`
					WHERE `id` = {$cash_request_id}
					", 'fetch_array');

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."my_dc_transactions` (
							`status`,
							`type`,
							`type_id`,
							`to_user_id`,
							`amount`,
							`currency_id`,
							`comment`,
							`comment_status`
						)
						VALUES (
							'pending',
							'cash_request',
							{$cash_request_id},
							{$user_id},
							{$data['amount']},
							{$data['currency_id']},
							'',
							'decrypted'
						)");
			print $db->printsql()."\n";
*/
			break;

		case 'abuses' :

			$abuses = $_REQUEST['abuses'];
			

			// проверим, не делал слал ли юзер абузы за последние сутки.
			// если слал - то выходим.
			$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `time`
					FROM `".DB_PREFIX."log_time_abuses`
					WHERE `user_id` = '{$user_id}'
					LIMIT 1
					", 'num_rows' );
			if ( $num > 0 )
				exit;
			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($abuses)) . $abuses .
						$bin_signatures;

			break;

		case 'admin_ban_miners' :

			$users_ids = $_REQUEST['users_ids'];


			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($users_ids)) . $users_ids .
				$bin_signatures;

			break;

		case 'admin_unban_miners' :

			$users_ids = $_REQUEST['users_ids'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($users_ids)) . $users_ids .
				$bin_signatures;

			break;

		case 'admin_variables' :  // админ изменил variables

			$variables = $_REQUEST['variables'];
			

			print_r($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($variables)) . $variables .
						$bin_signatures;

			break;

		case 'admin_spots' : // админ обновил набор точек для проверки лиц

			$example_spots = $_REQUEST['example_spots'];
			$segments = $_REQUEST['segments'];
			$tolerances = $_REQUEST['tolerances'];
			$compatibility = $_REQUEST['compatibility'];
			

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($example_spots)) . $example_spots .
						encode_length(strlen($segments)) . $segments .
						encode_length(strlen($tolerances)) . $tolerances .
						encode_length(strlen($compatibility)) . $compatibility .
						$bin_signatures;

			break;

		case 'admin_entropy' : // админ обновил энтропию

			$entropy = $_REQUEST['entropy'];
			

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($entropy)) . $entropy .
						$bin_signatures;

			

			break;

		case 'admin_message' : // админ отправил alert message

			$message = $_REQUEST['message'];
			$currency_list = $_REQUEST['currency_list'];
			
			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($message)) . $message .
						encode_length(strlen($currency_list)) . $currency_list .
						$bin_signatures;

			break;

		case 'change_primary_key' :

			$public_key_1 = $_REQUEST['public_key_1'];
			$public_key_2 = $_REQUEST['public_key_2'];
			$public_key_3 = $_REQUEST['public_key_3'];
			$private_key = $_REQUEST['private_key'];
			$password_hash = $_REQUEST['password_hash'];
			

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX."my_keys` (
							`public_key`,
							`private_key`,
							`password_hash`
						)
						VALUES (
							0x{$public_key_1},
							'{$private_key}',
							'{$password_hash}'
						)");

			$bin_public_key_1 = hextobin($public_key_1);
			$bin_public_key_2 = hextobin($public_key_2);
			$bin_public_key_3 = hextobin($public_key_3);
			$bin_public_key_pack =  ParseData::encode_length_plus_data($bin_public_key_1) .
				ParseData::encode_length_plus_data($bin_public_key_2) .
				ParseData::encode_length_plus_data($bin_public_key_3);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data( $user_id ) .
				ParseData::encode_length_plus_data( $bin_public_key_pack ) .
				$bin_signatures;

			break;

		case 'change_node_key' :

			$public_key = $_REQUEST['public_key'];
			$private_key = $_REQUEST['private_key'];
			

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX."my_node_keys` (
							`public_key`,
							`private_key`
						)
						VALUES (
							0x{$public_key},
							'{$private_key}'
						)");
			print $db->printsql();

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data(hextobin($public_key)) .
				$bin_signatures;

			break;

		case 'votes_complex' :

			$json_data = $_REQUEST['json_data'];
			

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($json_data) .
				$bin_signatures;

			break;

		case 'votes_reduction' :

			$currency_id = $_REQUEST['currency_id'];
			$pct = $_REQUEST['pct'];
			

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($currency_id)) . $currency_id .
				encode_length(strlen($pct)) . $pct .
				$bin_signatures;

			break;

		case 'new_holidays' :

			$start_time = $_REQUEST['start_time'];
			$end_time = $_REQUEST['end_time'];
			

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."my_holidays` (
							`start_time`,
							`end_time`
						)
						VALUES (
							{$start_time},
							{$end_time}
						)");

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($start_time)) . $start_time .
						encode_length(strlen($end_time)) . $end_time .
						$bin_signatures;

			break;

		case 'holidays_del' :

			$holidays_id = $_REQUEST['holidays_id'];
			

			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($holidays_id)) . $holidays_id .
						$bin_signatures;

			

			break;

		case 'tdc_dc_repaid' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$amount = $_REQUEST['amount'];
			

			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				encode_length(strlen($amount)) . $amount .
				$bin_signatures;

			break;

		case 'new_miner_update' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				$bin_signatures;

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."my_table`
				SET `node_voting_send_request` = {$time}
				");

			break;

		case 'admin_add_currency' :

			$currency_name = $_REQUEST['currency_name'];
			$currency_full_name = $_REQUEST['currency_full_name'];
			$max_promised_amount = $_REQUEST['max_promised_amount'];
			$max_other_currencies = $_REQUEST['max_other_currencies'];

			print_R($_REQUEST);
			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($currency_name) .
				ParseData::encode_length_plus_data($currency_full_name) .
				ParseData::encode_length_plus_data($max_promised_amount) .
				ParseData::encode_length_plus_data($max_other_currencies) .
				$bin_signatures;

			break;

		case 'admin_new_version' :

			$soft_type = $_REQUEST['soft_type'];
			$version = $_REQUEST['version'];
			$format = $_REQUEST['format'];
			
			$new_file = file_get_contents(ABSPATH . "public/new.zip");

			print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($soft_type) .
				ParseData::encode_length_plus_data($version) .
				ParseData::encode_length_plus_data($new_file) .
				ParseData::encode_length_plus_data($format) .
				$bin_signatures;

			break;

		case 'admin_new_version_alert' :

			$soft_type = $_REQUEST['soft_type'];
			$version = $_REQUEST['version'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($soft_type)) . $soft_type .
				encode_length(strlen($version)) . $version .
				$bin_signatures;

			break;

		case 'admin_blog' :

			$title = $_REQUEST['title'];
			$message = $_REQUEST['message'];
			

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($title)) . $title .
				encode_length(strlen($message)) . $message .
				$bin_signatures;

			break;

		case 'message_to_admin' :

			print_r($_REQUEST);

			$message_id = $_REQUEST['message_id'];
			$parent_id = $_REQUEST['parent_id'];
			$subject = $_REQUEST['subject'];
			$message = $_REQUEST['message'];
			$message_type = $_REQUEST['message_type'];
			$message_subtype = $_REQUEST['message_subtype'];
			$encrypted_message = $_REQUEST['encrypted_message'];
			

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_admin_messages`
					SET  `status` = 'my_pending',
							`encrypted` = 0x{$encrypted_message}
					WHERE `id` = {$message_id}
					");
			print $db->printsql()."\n";

			$encrypted_message = hextobin($encrypted_message);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($encrypted_message)) . $encrypted_message .
				$bin_signatures;

			break;

		case 'admin_answer' :

			print_r($_REQUEST);

			//$message_id = $_REQUEST['message_id'];
			$parent_id = $_REQUEST['parent_id'];
			//$subject = $_REQUEST['subject'];
			$message = $_REQUEST['message'];
			//$message_type = $_REQUEST['message_type'];
			//$message_subtype = $_REQUEST['message_subtype'];
			$encrypted_message = $_REQUEST['encrypted_message'];
			$to_user_id = $_REQUEST['to_user_id'];
			
/*
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."_my_admin_messages`
					SET  `status` = 'my_pending',
							`encrypted` = 0x{$encrypted_message},
							`user_id` = {$to_user_id}
					WHERE `id` = {$message_id}
					");
			print $db->printsql()."\n";
*/
			$encrypted_message = hextobin($encrypted_message);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($to_user_id)) . $to_user_id .
				encode_length(strlen($encrypted_message)) . $encrypted_message .
				$bin_signatures;

			break;

		case 'change_host' :

			$host = $_REQUEST['host'];


			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($host)) . $host .
				$bin_signatures;

			break;

		case 'new_forex_order' :

			$sell_currency_id = $_REQUEST['sell_currency_id'];
			$sell_rate = $_REQUEST['sell_rate'];
			$amount = $_REQUEST['amount'];
			$buy_currency_id = $_REQUEST['buy_currency_id'];
			$commission = $_REQUEST['commission'];

			print_R($_REQUEST);
//			$error = $this->get_tx_data(array('sell_currency_id', 'sell_rate', 'amount', 'buy_currency_id', 'commission', 'sign'));
			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				ParseData::encode_length_plus_data($sell_currency_id) .
				ParseData::encode_length_plus_data($sell_rate) .
				ParseData::encode_length_plus_data($amount) .
				ParseData::encode_length_plus_data($buy_currency_id) .
				ParseData::encode_length_plus_data($commission) .
				$bin_signatures;

			break;

		case 'for_repaid_fix' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				$bin_signatures;

			break;

		/*
				case 'admin_lang' :

					$type = ParseData::findType('admin_lang');
					$time = $_REQUEST['time'];
					$user_id = $_REQUEST['user_id'];
					$lang = $_REQUEST['lang'];
					$full_name = $_REQUEST['full_name'];
					$version = $_REQUEST['version'];
					$data = $_REQUEST['data'];
					

					$data = dec_binary ($type, 1) .
								dec_binary ($time, 4) .
								encode_length(strlen($user_id)) . $user_id .
								encode_length(strlen($lang)) . $lang .
								encode_length(strlen($full_name)) . $full_name .
								encode_length(strlen($version)) . $version .
								encode_length(strlen($data)) . $data .
								$bin_signatures;

					

					break;
		*/


		case 'change_commission' :

			//print_r($_REQUEST);

			$commission = $_REQUEST['commission'];
			

			$commission_decode = json_decode($commission, true);
			//print_r($commission_decode);
			foreach ($commission_decode as $currency_id=>$data) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."my_commission` (
							`currency_id`,
							`pct`,
							`min`,
							`max`
						)
						VALUES (
							{$currency_id},
							{$data[0]},
							{$data[1]},
							{$data[2]}
						)
                    ON DUPLICATE KEY UPDATE `pct`={$data[0]}, `min`={$data[0]}, `max`={$data[0]}
                    ");
				//print $db->printsql();
			}

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($commission)) . $commission .
				$bin_signatures;

			break;

	}


	$hash = md5($data);
	$data = bin2hex($data);
	$file = save_tmp_644 ('FSQ', "{$hash}\t{$data}");
	// т.к. эти данные создали мы сами, то пишем их сразу в таблицу проверенных данных, которые будут отправлены другим нодам
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
			FIELDS TERMINATED BY '\t'
			(@hash, @data)
			SET `data` = UNHEX(@data),
				   `hash` = UNHEX(@hash)
			");
	unlink($file);


?>