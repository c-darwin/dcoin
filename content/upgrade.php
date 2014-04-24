<?php
if (!defined('DC')) die("!defined('DC')");

// получим инфу об имеющейся заявку на апргейд аккаунта.

// проверим, послали ли мы запрос в FC-сеть
$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `node_voting_send_request`,
					 `host`
		FROM `".DB_PREFIX."my_table`
		LIMIT 1
		", 'fetch_array');
$node_voting_send_request = $data['node_voting_send_request'];
$host = $data['host'];

if ( $node_voting_send_request > 0 ) {

	// голосование нодов
	$node_votes_end = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `votes_end`
			FROM `".DB_PREFIX."votes_miners`
			WHERE `user_id` = {$user_id} AND
			             `type` = 'node_voting'
			 ORDER BY `id` DESC
			 LIMIT 1
			", 'fetch_one');

	if ( $node_votes_end == '1' ) { // голосование нодов завершено

		$user_votes_end = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `votes_end`
			FROM `".DB_PREFIX."votes_miners`
			WHERE `user_id` = {$user_id} AND
			             `type` = 'user_voting'
			", 'fetch_one');

		if ( $user_votes_end == '1' ) { // юзерское голосование закончено

			$miner_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
			if ($miner_id > 0)
				$tpl['result'] = 'ok';
			else
				$tpl['result'] = 'bad';

		}
		else if ( $user_votes_end == '0' ) { // идет юзерское голосование

			$tpl['result'] = 'users_pending';
		}
		else { // ноды приняли решение, что фото плохое

			$tpl['result'] = 'bad_photos_hash';
			$tpl['host'] = $host;
		}

	}
	else if ( $node_votes_end == '0' && time() - $node_voting_send_request < 86400 ) { // голосование нодов началось, ждем.

		$tpl['result'] = 'nodes_pending';
	}
	else if ( $node_votes_end == '0' && time() - $node_voting_send_request > 86400 ) { // голосование нодов удет более суток и еще не завершилось

		$tpl['result'] = 'resend';
	}
	else { // запрос в FC-сеть еще не дошел и голосования не начались

		// если прошло менее 1 часа
		if ( time() - $node_voting_send_request < 3600 ) {

			$tpl['result'] = 'pending';
		}
		else { // где-то проблема и запрос не ушел.

			$tpl['result'] = 'resend';
		}

	}
}
else { // запрос на получение статуса "майнер" мы еще не слали

	$tpl['result'] = 'null';
}

require_once( ABSPATH . 'includes/class-parsedata.php' );

// сколько у нас осталось попыток стать майнером.
$count_attempt = ParseData::count_miner_attempt($db, $user_id, 'user_voting');
$variables = ParseData::get_variables($db,  array('miner_votes_attempt') );

$tpl['miner_votes_attempt'] = $variables['miner_votes_attempt'] - $count_attempt;

require_once( ABSPATH . 'templates/upgrade.tpl' );

?>