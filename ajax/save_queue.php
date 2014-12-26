<?php

session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

define('MY_PREFIX', get_my_prefix($db));

$_REQUEST['time'] = intval($_REQUEST['time']);
$_REQUEST['user_id'] = intval($_REQUEST['user_id']);
$type = ParseData::findType($_REQUEST['type']);
$time = intval($_REQUEST['time']);
$user_id = intval($_REQUEST['user_id']);
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

		if ( !check_input_data ($_REQUEST['public_key'], 'public_key' ) )
			die('error public_key');
		if ( !check_input_data ($_REQUEST['private_key'], 'private_key' ) )
			die('error public_key');

		$public_key = hextobin($_REQUEST['public_key']);
		$public_key_hex = $db->escape($_REQUEST['public_key']);
		$private_key = $db->escape($_REQUEST['private_key']);
		if (empty($_SESSION['restricted'])) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_new_users` (
						`public_key`,
						`private_key`
					)
					VALUES (
						0x{$public_key_hex},
						'{$private_key}'
					)");
		}


		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					ParseData::encode_length_plus_data($user_id) .
					ParseData::encode_length_plus_data($public_key) .
					$bin_signatures;

		break;

		case 'del_cf_project' :

			$project_id = $_REQUEST['project_id'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($project_id) .
				$bin_signatures;

		break;

		case 'cf_comment' :

			$project_id = $_REQUEST['project_id'];
			$lang_id = $_REQUEST['lang_id'];
			$comment = $_REQUEST['comment'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($project_id) .
				ParseData::encode_length_plus_data($lang_id) .
				ParseData::encode_length_plus_data($comment) .
				$bin_signatures;

		break;


		case 'new_credit' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['to_user_id']) .
				ParseData::encode_length_plus_data($_REQUEST['amount']) .
				ParseData::encode_length_plus_data($_REQUEST['currency_id']) .
				ParseData::encode_length_plus_data($_REQUEST['pct']) .
				$bin_signatures;

			break;


		case 'del_credit' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['credit_id']) .
				$bin_signatures;

			break;

		case 'repayment_credit' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['credit_id']) .
				ParseData::encode_length_plus_data($_REQUEST['amount']) .
				$bin_signatures;

			break;

		case 'change_creditor' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['to_user_id']) .
				ParseData::encode_length_plus_data($_REQUEST['credit_id']) .
				$bin_signatures;

			break;

		case 'change_credit_part' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['pct']) .
				$bin_signatures;

			break;

	case 'user_avatar' :

		$name = $_REQUEST['name'];
		$avatar = $_REQUEST['avatar'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($name) .
				ParseData::encode_length_plus_data($avatar) .
				$bin_signatures;

		break;

		case 'del_cf_funding' :

			$funding_id = $_REQUEST['funding_id'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($funding_id) .
				$bin_signatures;

			break;

	case 'cf_project_change_category' :

		$project_id = $_REQUEST['project_id'];
		$category_id = $_REQUEST['category_id'];

		$data = dec_binary ($type, 1) .
			dec_binary ($time, 4) .
			ParseData::encode_length_plus_data($user_id) .
			ParseData::encode_length_plus_data($project_id) .
			ParseData::encode_length_plus_data($category_id) .
			$bin_signatures;

		break;

	case 'new_cf_project' :

		$currency_id = $_REQUEST['currency_id'];
		$amount = $_REQUEST['amount'];
		$end_time = $_REQUEST['end_time'];
		$latitude = $_REQUEST['latitude'];
		$longitude = $_REQUEST['longitude'];
		$category_id = $_REQUEST['category_id'];
		$currency_name = $_REQUEST['currency_name'];

		$data = dec_binary ($type, 1) .
			dec_binary ($time, 4) .
			ParseData::encode_length_plus_data($user_id) .
			ParseData::encode_length_plus_data($currency_id) .
			ParseData::encode_length_plus_data($amount) .
			ParseData::encode_length_plus_data($end_time) .
			ParseData::encode_length_plus_data($latitude) .
			ParseData::encode_length_plus_data($longitude) .
			ParseData::encode_length_plus_data($category_id) .
			ParseData::encode_length_plus_data($currency_name) .
			$bin_signatures;

		break;

	case 'cf_project_data' :

		$project_id = $_REQUEST['project_id'];
		$lang_id = $_REQUEST['lang_id'];
		$blurb_img = $_REQUEST['blurb_img'];
		$head_img = $_REQUEST['head_img'];
		$description_img = $_REQUEST['description_img'];
		$picture = $_REQUEST['picture'];
		$video_type = $_REQUEST['video_type'];
		$video_url_id = $_REQUEST['video_url_id'];
		$news_img = $_REQUEST['news_img'];
		$links = $_REQUEST['links'];
		$hide = $_REQUEST['hide'];

		$data = dec_binary ($type, 1) .
			dec_binary ($time, 4) .
			ParseData::encode_length_plus_data($user_id) .
			ParseData::encode_length_plus_data($project_id) .
			ParseData::encode_length_plus_data($lang_id) .
			ParseData::encode_length_plus_data($blurb_img) .
			ParseData::encode_length_plus_data($head_img) .
			ParseData::encode_length_plus_data($description_img) .
			ParseData::encode_length_plus_data($picture) .
			ParseData::encode_length_plus_data($video_type) .
			ParseData::encode_length_plus_data($video_url_id) .
			ParseData::encode_length_plus_data($news_img) .
			ParseData::encode_length_plus_data($links) .
			ParseData::encode_length_plus_data($hide) .
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

		if (!$race || empty($country) || !$latitude || !$longitude || !$host || !$face_hash || !$profile_hash || !$face_coords || !$profile_coords || !$video_type || !$video_url_id || !$node_public_key) {
			die('error');
		}
		if ($video_type=='null' || $video_url_id=='null') {
			if ( !file_exists(ABSPATH."public/{$_SESSION['user_id']}_user_video.mp4") ) {
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

		if (empty($_SESSION['restricted'])) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
					SET `node_voting_send_request` = {$time}
					");
		}

		break;

	case 'votes_miner' : // голос за юзера, который хочет стать майнером

		$vote_id = intval($_REQUEST['vote_id']);
		$result = $_REQUEST['result'];
		$comment = $_REQUEST['comment'];

		if (empty($_SESSION['restricted'])) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_tasks` (
							`type`,
							`id`,
							`time`
						)
						VALUES (
							'miner',
							{$vote_id},
							{$time}
						)");
		}

		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($vote_id)) . $vote_id .
					encode_length(strlen($result)) . $result .
					encode_length(strlen($comment)) . $comment .
					$bin_signatures;

		break;

	case 'new_promised_amount' :

		$currency_id = $db->escape($_REQUEST['currency_id']);
		$amount = $db->escape($_REQUEST['amount']);
		$video_type = $_REQUEST['video_type'];
		$video_url_id = $_REQUEST['video_url_id'];
		$payment_systems_ids = $_REQUEST['payment_systems_ids'];

		if ( !check_input_data ($currency_id, 'int' ) )
			die('error currency_id');
		if ( !check_input_data ($amount, 'amount' ) )
			die('error amount');
		if ( !check_input_data ($payment_systems_ids, 'payment_systems_ids' ) )
			die('error payment_systems_ids');

		$data = dec_binary ($type, 1) .
					dec_binary ($time, 4) .
					encode_length(strlen($user_id)) . $user_id .
					encode_length(strlen($currency_id)) . $currency_id .
					encode_length(strlen($amount)) . $amount .
					encode_length(strlen($video_type)) . $video_type .
					encode_length(strlen($video_url_id)) . $video_url_id .
					encode_length(strlen($payment_systems_ids)) . $payment_systems_ids .
					$bin_signatures;

		if (empty($_SESSION['restricted'])) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_promised_amount` (
						currency_id,
						amount
					)
					VALUES (
						{$currency_id},
						{$amount}
					)");
		}

		break;

		case 'change_promised_amount' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			$amount = $_REQUEST['amount'];
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
			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				encode_length(strlen($amount)) . $amount .
				$bin_signatures;

			break;

		case 'votes_promised_amount':

			$promised_amount_id = intval($_REQUEST['promised_amount_id']);
			$result = $_REQUEST['result'];
			$comment = $_REQUEST['comment'];

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_tasks` (
							`type`,
							`id`,
							`time`
						)
						VALUES (
							'promised_amount',
							{$promised_amount_id},
							{$time}
						)");
			}

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($promised_amount_id)) . $promised_amount_id .
						encode_length(strlen($result)) . $result .
						encode_length(strlen($comment)) . $comment .
						$bin_signatures;

			break;

		case 'change_geolocation' :

			$latitude = $db->escape($_REQUEST['latitude']);
			$longitude = $db->escape($_REQUEST['longitude']);
			$country = intval($_REQUEST['country']);

			if ( !check_input_data ($latitude, 'coordinate' ) )
				die('error latitude');
			if ( !check_input_data ($longitude, 'coordinate' ) )
				die('error longitude');
			if ( !check_input_data ($country, 'int' ) )
				die('error country');

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
						SET `geolocation` = '{$latitude}, {$longitude}',
								`location_country` =  {$country}
						");
			}

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($latitude)) . $latitude .
						encode_length(strlen($longitude)) . $longitude .
						encode_length(strlen($country)) . $country .
						$bin_signatures;

			break;

		case 'del_promised_amount' :

			$promised_amount_id = $_REQUEST['promised_amount_id'];
			//print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($promised_amount_id)) . $promised_amount_id .
				$bin_signatures;

			break;

		case 'del_forex_order' :

			$order_id= $_REQUEST['order_id'];

			//print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($order_id)) . $order_id .
				$bin_signatures;

			break;

		case 'send_dc' :

			$to_user_id = intval($_REQUEST['to_id']);
			$currency_id = intval($_REQUEST['currency_id']);
			$amount = $db->escape($_REQUEST['amount']);
			$commission = $db->escape($_REQUEST['commission']);

			$arbitrators_commissions=0;
			for ($i=0; $i<5; $i++) {
				if (!empty($_REQUEST['arbitrators'][$i])) {
					$arbitrator[$i] = intval($_REQUEST['arbitrators'][$i]);
					$arbitrator_commission[$i] = $db->escape($_REQUEST['arbitrators_commissions'][$i]);
					if ( !check_input_data ($arbitrator[$i], 'int' ) )
						die('error $arbitrator_id');
					if ( !check_input_data ($arbitrator_commission[$i], 'amount' ) )
						die('error $arbitrator_commission');
				}
				else {
					$arbitrator[$i] = 0;
					$arbitrator_commission[$i] = 0;
				}
				$arbitrators_commissions+=$arbitrator_commission[$i];
			}

			$comment = $_REQUEST['comment'];
			$comment_text = $_REQUEST['comment_text'];

			if ( !check_input_data ($to_user_id, 'int' ) )
				die('error to_user_id');
			if ( !check_input_data ($currency_id, 'int' ) )
				die('error currency_id');
			if ( !check_input_data ($amount, 'amount' ) )
				die('error amount');
			if ( !check_input_data ($commission, 'amount' ) )
				die('error commission');

			$comment_text = clear_comment($comment_text, $db);

			$total_commission = $commission+$arbitrators_commissions;
			if (empty($_SESSION['restricted'])) {
				// пишем транзакцкцию к сбе в таблу
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO
							`".DB_PREFIX.MY_PREFIX."my_dc_transactions` (
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
								{$total_commission},
								{$currency_id},
								'{$comment_text}',
								'decrypted'
							)");
				//print $db->printsql()."\n";
			}

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
				encode_length(strlen($arbitrator[0])) . $arbitrator[0] .
				encode_length(strlen($arbitrator[1])) . $arbitrator[1] .
				encode_length(strlen($arbitrator[2])) . $arbitrator[2] .
				encode_length(strlen($arbitrator[3])) . $arbitrator[3] .
				encode_length(strlen($arbitrator[4])) . $arbitrator[4] .
				encode_length(strlen($arbitrator_commission[0])) . $arbitrator_commission[0] .
				encode_length(strlen($arbitrator_commission[1])) . $arbitrator_commission[1] .
				encode_length(strlen($arbitrator_commission[2])) . $arbitrator_commission[2] .
				encode_length(strlen($arbitrator_commission[3])) . $arbitrator_commission[3] .
				encode_length(strlen($arbitrator_commission[4])) . $arbitrator_commission[4] .
				encode_length(strlen($comment)) . $comment .
				$bin_signatures;

			break;

		case 'cf_send_dc' :

			$project_id = intval($_REQUEST['to_id']);
			$amount = $db->escape($_REQUEST['amount']);
			$commission = $db->escape($_REQUEST['commission']);
			$comment = $_REQUEST['comment'];
			$comment_text = $_REQUEST['comment_text'];

			if ( !check_input_data ($project_id, 'int' ) )
				die('error to_user_id');
			if ( !check_input_data ($amount, 'amount' ) )
				die('error amount');
			if ( !check_input_data ($commission, 'amount' ) )
				die('error commission');

			$comment_text = clear_comment($comment_text, $db);

			$currency_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `currency_id`
					FROM `".DB_PREFIX."cf_projects`
					WHERE `id` = {$project_id}
					LIMIT 1
					", 'fetch_one' );

			if (empty($_SESSION['restricted'])) {
				// пишем транзакцкцию к сбе в таблу
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO
							`".DB_PREFIX.MY_PREFIX."my_dc_transactions` (
								`status`,
								`type`,
								`type_id`,
								`amount`,
								`commission`,
								`currency_id`,
								`comment`,
								`comment_status`
							)
							VALUES (
								'pending',
								'cf_project',
								{$project_id},
								{$amount},
								{$commission},
								{$currency_id},
								'{$comment_text}',
								'decrypted'
							)");
			}

			if (!$comment)
				$comment = 'null';
			else
				$comment = hextobin($comment);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($project_id) .
				ParseData::encode_length_plus_data($amount) .
				ParseData::encode_length_plus_data($commission) .
				ParseData::encode_length_plus_data($comment) .
				$bin_signatures;

			break;

		case 'cash_request_out' :

			$to_user_id = intval($_REQUEST['to_user_id']);
			$currency_id = intval($_REQUEST['currency_id']);
			$amount = $db->escape($_REQUEST['amount']);
			$comment = hextobin($_REQUEST['comment']);
			$comment_text = $_REQUEST['comment_text'];
			$hash_code = $_REQUEST['hash_code'];
			$code = $db->escape($_REQUEST['code']);

			if ( !check_input_data ($code, 'cash_code' ) )
				die('error code');
			if ( !check_input_data ($to_user_id, 'int' ) )
				die('error to_user_id');
			if ( !check_input_data ($currency_id, 'int' ) )
				die('error currency_id');
			if ( !check_input_data ($amount, 'amount' ) )
				die('error amount');
			if ( !check_input_data ($commission, 'amount' ) )
				die('error commission');

			$comment_text = clear_comment($comment_text, $db);

			if (empty($_SESSION['restricted'])) {

				// пишем в личную таблу
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_cash_requests` (
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
							`".DB_PREFIX.MY_PREFIX."my_dc_transactions` (
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
			}

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

			//print_r($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($cash_request_id)) . $cash_request_id .
						encode_length(strlen($code)) . $code .
						$bin_signatures;

			break;

		case 'abuses' :

			$abuses = $_REQUEST['abuses'];

			// проверим, не делал слал ли юзер абузы за последние сутки.
			// если слал - то выходим.
			$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `time`
					FROM `".DB_PREFIX."log_time_abuses`
					WHERE `user_id` = {$user_id}
					LIMIT 1
					", 'num_rows' );
			if ( $num > 0 )
				exit;
			//print_R($_REQUEST);

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

			//print_r($_REQUEST);

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

		case 'admin_message' : // админ отправил alert message

			$message = $_REQUEST['message'];
			$currency_list = $_REQUEST['currency_list'];
			
			//print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($message)) . $message .
						encode_length(strlen($currency_list)) . $currency_list .
						$bin_signatures;

			break;

		case 'change_primary_key' :

			$public_key_1 = $db->escape($_REQUEST['public_key_1']);
			$public_key_2 = $_REQUEST['public_key_2'];
			$public_key_3 = $_REQUEST['public_key_3'];
			$private_key = $db->escape($_REQUEST['private_key']);
			$password_hash = $db->escape($_REQUEST['password_hash']);
			$save_private_key = $_REQUEST['save_private_key'];

			if ( !check_input_data ($public_key_1, 'public_key' ) )
				die('error public_key');
			if ( $private_key && !check_input_data ($private_key, 'private_key' ) )
				die('error private_key');
			if ( !check_input_data ($password_hash, 'sha256' ) )
				die('error password_hash');

			if (empty($_SESSION['restricted'])) {
				if ($save_private_key==1 && !get_community_users($db))
					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_keys` (
									`public_key`,
									`private_key`,
									`password_hash`
								)
								VALUES (
									0x{$public_key_1},
									'{$private_key}',
									'{$password_hash}'
								)");
				else
					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_keys` (
									`public_key`
								)
								VALUES (
									0x{$public_key_1}
								)");
			}

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

			$public_key = $db->escape($_REQUEST['public_key']);
			$private_key = $db->escape($_REQUEST['private_key']);

			if ( !check_input_data ($public_key, 'public_key' ) )
				die('error public_key');
			if ( !check_input_data ($private_key, 'private_key' ) )
				die('error private_key');

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_node_keys` (
								`public_key`,
								`private_key`
							)
							VALUES (
								0x{$public_key},
								'{$private_key}'
							)");
				//print $db->printsql();
			}

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

		case 'new_holidays' :

			$start_time = intval($_REQUEST['start_time']);
			$end_time = intval($_REQUEST['end_time']);

			if ( !check_input_data ($start_time, 'int' ) )
				die('error start_time');
			if ( !check_input_data ($end_time, 'int' ) )
				die('error end_time');

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO
							`".DB_PREFIX.MY_PREFIX."my_holidays` (
								`start_time`,
								`end_time`
							)
							VALUES (
								{$start_time},
								{$end_time}
							)");
			}

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($start_time)) . $start_time .
						encode_length(strlen($end_time)) . $end_time .
						$bin_signatures;

			break;

		/*
		case 'holidays_del' :

			$holidays_id = $_REQUEST['holidays_id'];

			//print_R($_REQUEST);

			$data = dec_binary ($type, 1) .
						dec_binary ($time, 4) .
						encode_length(strlen($user_id)) . $user_id .
						encode_length(strlen($holidays_id)) . $holidays_id .
						$bin_signatures;

			break;*/


		case 'new_miner_update' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				$bin_signatures;

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
					SET `node_voting_send_request` = {$time}
					");
			}

			break;

		case 'admin_add_currency' :

			$currency_name = $_REQUEST['currency_name'];
			$currency_full_name = $_REQUEST['currency_full_name'];
			$max_promised_amount = $_REQUEST['max_promised_amount'];
			$max_other_currencies = $_REQUEST['max_other_currencies'];

			//print_R($_REQUEST);
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

			//print_R($_REQUEST);

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

			//print_r($_REQUEST);

			$message_id = intval($_REQUEST['message_id']);
			$parent_id = $_REQUEST['parent_id'];
			$subject = $_REQUEST['subject'];
			$message = $_REQUEST['message'];
			$message_type = $_REQUEST['message_type'];
			$message_subtype = $_REQUEST['message_subtype'];
			$encrypted_message = $db->escape($_REQUEST['encrypted_message']);

			if ( !check_input_data ($message_id, 'int' ) )
				die('error message_id');

			if ( !check_input_data ($encrypted_message, 'hex_message' ) )
				die('error encrypted_message');

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX.MY_PREFIX."my_admin_messages`
						SET  `status` = 'my_pending',
								`encrypted` = 0x{$encrypted_message}
						WHERE `id` = {$message_id}
						");
				//print $db->printsql()."\n";
			}

			$encrypted_message = hextobin($encrypted_message);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($encrypted_message)) . $encrypted_message .
				$bin_signatures;

			break;

		case 'admin_answer' :

			//print_r($_REQUEST);

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

			$host = $db->escape($_REQUEST['host']);

			if ( !check_input_data ($host, 'host' ) )
				die('error host');

			if (empty($_SESSION['restricted'])) {
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
					SET  `host` = '{$host}',
							`host_status` = 'my_pending'
					");
			}

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

			//print_R($_REQUEST);
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

		case 'actualization_promised_amounts' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				$bin_signatures;

			break;

		case 'change_commission' :

			$commission = $_REQUEST['commission'];
			$commission_decode = json_decode($commission, true);

			$pool_commission = false;
			if (get_community_users($db)) {
				$pool_commission = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
						SELECT `commission`
						FROM `".DB_PREFIX."config`
						", 'fetch_one');
				$pool_commission = json_decode($pool_commission, true);
			}
			foreach  ($commission_decode as $currency_id => $data) {

				if ( !check_input_data ($currency_id, 'bigint') )
					die('bad $currency_id');
				// % от 0 до 10
				if ( !check_input_data ($data[0], 'currency_commission') || $data[0]>10)
					die('bad pct');
				// минимальная комиссия от 0. При 0% будет = 0
				if ( !check_input_data ($data[1], 'currency_commission') )
					die('bad currency_min_commission');
				// макс. комиссия. 0 - значит, считается по %
				if ( !check_input_data ($data[2], 'currency_commission') )
					die('bad currency_max_commission');
				if ($data[1]>$data[2] && $data[2])
					die('bad currency_max_commission');

				// и если в пуле, то
				if ($pool_commission) {
					// нельзя допустить, чтобы блок подписал майнер, у которого комиссия больше той, что разрешана в пуле,
					// т.к. это приведет к попаднию в блок некорректной тр-ии, что приведет к сбою пула
					if ( $data[0] > @$pool_commission[$currency_id][0] )
						die( $data[0].' > '.@$pool_commission[$currency_id][0]);
					if ( $data[1] > @$pool_commission[$currency_id][1] )
						die($data[1].' > '.@$pool_commission[$currency_id][1]);
				}
			}

			if (empty($_SESSION['restricted'])) {
				foreach ($commission_decode as $currency_id=>$data) {
					$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX.MY_PREFIX."my_commission` (
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
			}

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				encode_length(strlen($user_id)) . $user_id .
				encode_length(strlen($commission)) . $commission .
				$bin_signatures;

			break;

		case 'change_key_active' :

			$secret = hextobin($_REQUEST['secret']);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($secret) .
				$bin_signatures;

			break;

		case 'change_key_close' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				$bin_signatures;

			break;

		case 'change_key_request' :

			$to_user_id = $_REQUEST['to_user_id'];

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($to_user_id) .
				$bin_signatures;

			break;

		case 'admin_change_primary_key' :

			$for_user_id = $_REQUEST['for_user_id'];
			$new_public_key = hextobin($_REQUEST['new_public_key']);

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($for_user_id) .
				ParseData::encode_length_plus_data($new_public_key) .
				$bin_signatures;

			break;

		case 'change_arbitrator_list' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['arbitration_trust_list']) .
				$bin_signatures;

			break;

		case 'money_back_request' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['order_id']) .
				ParseData::encode_length_plus_data($_REQUEST['arbitrator0_enc_text']) .
				ParseData::encode_length_plus_data($_REQUEST['arbitrator1_enc_text']) .
				ParseData::encode_length_plus_data($_REQUEST['arbitrator2_enc_text']) .
				ParseData::encode_length_plus_data($_REQUEST['arbitrator3_enc_text']) .
				ParseData::encode_length_plus_data($_REQUEST['arbitrator4_enc_text']) .
				ParseData::encode_length_plus_data($_REQUEST['seller_enc_text']) .
				$bin_signatures;

			break;

		case 'change_seller_hold_back' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['arbitration_days_refund']) .
				ParseData::encode_length_plus_data($_REQUEST['hold_back_pct']) .
				$bin_signatures;

			break;

		case 'money_back' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['order_id']) .
				ParseData::encode_length_plus_data($_REQUEST['amount']) .
				$bin_signatures;

			break;

		case 'change_arbitrator_conditions' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['conditions']) .
				ParseData::encode_length_plus_data($_REQUEST['url']) .
				$bin_signatures;

			break;

		case 'change_money_back_time' :

			$data = dec_binary ($type, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($user_id) .
				ParseData::encode_length_plus_data($_REQUEST['order_id']) .
				ParseData::encode_length_plus_data($_REQUEST['amount']) .
				$bin_signatures;

			break;
	}


$hash = md5($data);

if (!in_array($_REQUEST['type'], array('new_pct', 'new_max_promised_amounts', 'new_reduction', 'votes_node_new_miner', 'new_max_other_currencies'))) {
	$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			INSERT INTO `" . DB_PREFIX . "transactions_status` (
				`hash`,
				`time`,
				`type`,
				`user_id`
			)
			VALUES (
				0x{$hash},
				" . time() . ",
				{$type},
				{$user_id}
			)");
}

$data = bin2hex($data);
$file = save_tmp_644 ('FSQ', "{$hash}\t{$data}");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
		FIELDS TERMINATED BY '\t'
		(@hash, @data)
		SET `data` = UNHEX(@data),
			   `hash` = UNHEX(@hash)
		");
unlink($file);


?>