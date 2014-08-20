<?php
if (!defined('DC')) die("!defined('DC')");

//Нельзя завершить голосование юзеров раньше чем через сутки, даже если набрано нужное кол-во голосов.
//В голосовании нодов ждать сутки не требуется, т.к. там нельзя поставить поддельных нодов

define('TASK_TIME', 3600*24); // чтобы не выдавать одно и тоже голосование
	
$rand_array = array();

// в запросе к votes_miners было votes_start_time` > ".(time()-86400).". Не могу вспомнить, зачем я это делал.

// Модерация новых майнеров
// берем тех, кто прошел проверку нодов (type='node_voting')
$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM `".DB_PREFIX."votes_miners`
		WHERE  `votes_end` = 0 AND
					 `type` = 'user_voting'
		", 'fetch_one');
if ( $num>0 )
	$rand_array[] = 1;

// Модерация promised_amount
// вначале получим ID валют, которые мы можем проверять.
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`
		FROM `".DB_PREFIX."promised_amount`
		WHERE `status` IN ('mining', 'repaid') AND
					 `user_id` = {$user_id}
		");
$currency_ids='';
while ($row = $db->fetchArray($res))
	$currency_ids.=$row['currency_id'].',';
$currency_ids = substr($currency_ids, 0, -1);

if ($currency_ids || $user_id == 1) {

	if ($user_id==1)
		$add_sql = '';
	else
		$add_sql = "AND `currency_id` IN ({$currency_ids})";
	$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`)
			FROM `".DB_PREFIX."promised_amount`
			WHERE `status` =  'pending' AND
						 `del_block_id` = 0
			{$add_sql}
			ORDER BY rand()
			LIMIT 1
			", 'fetch_one');
	if ( $num>0 )
		$rand_array[] = 2;
}

$task_type = false;
if ($rand_array) {
	$rand_key = array_rand($rand_array);
	$task_type = $rand_array[$rand_key];
}

switch ($task_type) {

	case 1:

		// ***********************************
		// задания по модерации новых майнеров
		// ***********************************

		$tpl['data']['type'] = 'votes_miner';
		$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
		$tpl['data']['time'] = time();
		$tpl['data']['user_id'] = $user_id;

		$tpl['user_info'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `".DB_PREFIX."miners_data`.`user_id`,
							 `id` as `vote_id`,
							 `face_coords`,
							 `profile_coords`,
							 `video_type`,
							 `video_url_id`,
							 `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`,
							 `host`
				FROM `".DB_PREFIX."votes_miners`
				LEFT JOIN `".DB_PREFIX."miners_data`
						 ON `".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."votes_miners`.`user_id`
				WHERE `votes_end` = 0 AND
							 `type` = 'user_voting'
				ORDER BY rand()
				LIMIT 1
				" , 'fetch_array' );

		// проверим, не голосовали ли мы за это в последние 30 минут
		$repeated = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX.MY_PREFIX."my_tasks`
				WHERE `type` = 'miner' AND
							 `id` = {$tpl['user_info']['vote_id']} AND
							 `time` > ".(time()-TASK_TIME)."
				", 'fetch_one' );
		if ($repeated) {
			require_once( ABSPATH . 'templates/tasks.tpl');
			break;
		}

		$tpl['user_info']['example_points'] = get_points($db);
		//print_R($tpl['user_info']['example_points']);
		//print_R($tpl['user_info']);
		// получим ID майнеров, у которых лежат фото нужного нам юзера
		$miners_ids = ParseData::get_miners_keepers($tpl['user_info']['photo_block_id'], $tpl['user_info']['photo_max_miner_id'],  $tpl['user_info']['miners_keepers'], true);
		//print_R($miners_ids);

		// берем 1 случайный из 10-и ID майнеров
		$r = array_rand($miners_ids, 1);
		$miner_id = $miners_ids[$r];

		// получаем хост
		$tpl['user_info']['miner_host'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `miner_id` = {$miner_id}
				", 'fetch_one' );

		// отрезки майнера, которого проверяем
		$tpl['relations'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."faces`
				WHERE `user_id` = {$tpl['user_info']['user_id']}
				LIMIT 1
				", 'fetch_array' );

		// получим допустимые расхождения между точками и совместимость версий
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `tolerances`,
							 `compatibility`
				FROM `".DB_PREFIX."spots_compatibility`
				", 'fetch_array' );
		$tolerances = json_decode($data['tolerances'], true);
		$compatibility = json_decode($data['compatibility'], true); // array(1,2,3)

		// формируем кусок SQL-запроса для соотношений отрезков
		$add_sql_tolerances = '';
		$types_arr = array('face', 'profile');
		for ($i=0; $i<sizeof($types_arr); $i++) {
			for ($j=1; $j<=sizeof($tolerances[$types_arr[$i]]); $j++) {
				$current_relations = $tpl['relations'][$types_arr[$i][0].$j];
				$diff = $tolerances[$types_arr[$i]][$j] * $current_relations;
				if ( !$diff )
					continue;
				$min = $current_relations - $diff;
				$max = $current_relations + $diff;
				$add_sql_tolerances.="`{$types_arr[$i][0]}{$j}` > {$min} AND `{$types_arr[$i][0]}{$j}` < {$max} AND \n";
			}
		}
		$add_sql_tolerances = substr($add_sql_tolerances, 0, strlen($add_sql_tolerances)-6);

		// формируем кусок SQL-запроса для совместимости версий
		$add_sql_compatibility=' ';
		for ($i=0; $i<sizeof($compatibility); $i++) {
			$add_sql_compatibility.="{$compatibility[$i]},";
		}
		$add_sql_compatibility = substr($add_sql_compatibility, 0, strlen($add_sql_compatibility)-1);

		// получаем из БД похожие фото
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `".DB_PREFIX."miners_data`.`user_id`,
							 `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`
				FROM `".DB_PREFIX."faces`
				LEFT JOIN `".DB_PREFIX."miners_data` ON
						`".DB_PREFIX."miners_data`.`user_id` = `".DB_PREFIX."faces`.`user_id`
				WHERE {$add_sql_tolerances} AND
							`version` IN ({$add_sql_compatibility}) AND
				             `".DB_PREFIX."faces`.`status` = 'used' AND
				             `".DB_PREFIX."miners_data`.`user_id` != {$tpl['user_info']['user_id']}
				LIMIT 0, 100" );
		//print  '<pre>'.$db->printsql().'</pre>';
		while ( $row = $db->fetchArray($res) ) {
			// майнеры, у которых можно получить фото нужного нам юзера
			$miners_ids = ParseData::get_miners_keepers($row['photo_block_id'], $row['photo_max_miner_id'], $row['miners_keepers'], true);
			// берем 1 случайный из 10-и ID майнеров
			$r = array_rand($miners_ids, 1);
			$miner_id = $miners_ids[$r];
			// получаем хост, где сможем получить фото юзера
			$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `host`
					FROM `".DB_PREFIX."miners_data`
					WHERE `miner_id` = {$miner_id}
					LIMIT 1
					", 'fetch_one' );
			$tpl['search'][] = array('user_id'=>$row['user_id'], 'host'=>$host);
		}

		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
				SELECT `race`,
							 `country`
				FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
				', 'fetch_array' );
		$tpl['my_race'] = $races[$data['race']];
		$tpl['my_country'] = $countries[$data['country']];

		require_once( ABSPATH . 'templates/tasks_new_miner.tpl' );

	break;

	case 2:

		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,'
				SELECT `id`,
							 `name`
				FROM `'.DB_PREFIX.'currency` ORDER BY `full_name`
				');
		while ($row = $db->fetchArray($res))
			$tpl['currency_list'][$row['id']] = $row['name'];

		$tpl['data'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`,
							 `currency_id`,
							 `amount`,
							 `user_id`,
							 `video_type`,
							 `video_url_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` =  'pending' AND
							 `del_block_id` = 0
				{$add_sql}
				ORDER BY rand()
				LIMIT 1
				", 'fetch_array' );
		debug_print($tpl['data'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$tpl['data']['currency_name'] = $tpl['currency_list'][$tpl['data']['currency_id']];

		// проверим, не голосовали ли мы за это в последние 30 минут
		$repeated = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX.MY_PREFIX."my_tasks`
				WHERE `type` = 'promised_amount' AND
							 `id` = {$tpl['data']['id']} AND
							 `time` > ".(time()-TASK_TIME)."
				", 'fetch_one' );
		if ($repeated) {
			require_once( ABSPATH . 'templates/tasks.tpl');
			break;
		}

		// если нету видео на ютубе, то получаем host юзера, где брать видео
		if ( $tpl['data']['video_url_id']=='null' ) {
			$tpl['data']['host'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$tpl['data']['user_id']}
				LIMIT 1
				", 'fetch_one' );
		}

		// каждый раз обязательно проверяем, где находится юзер
		$tpl['data']['user_info'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `latitude`,
							 `user_id`,
							 `longitude`,
							 `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`,
							 `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$tpl['data']['user_id']}
				LIMIT 1
				", 'fetch_array');
		debug_print($tpl['data']['user_info'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// получим ID майнеров, у которых лежат фото нужного нам юзера
		$miners_ids = ParseData::get_miners_keepers($tpl['data']['user_info']['photo_block_id'], $tpl['data']['user_info']['photo_max_miner_id'],  $tpl['data']['user_info']['miners_keepers'], true);
		debug_print($miners_ids, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// берем 1 случайный из 10-и ID майнеров
		$r = array_rand($miners_ids, 1);
		$miner_id = $miners_ids[$r];
		// получаем хост, где будем брать фото лица проверяемого нами майнера
		$tpl['data']['miner_host'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `miner_id` = {$miner_id}
				", 'fetch_one' );

		$tpl['data']['type'] = 'votes_promised_amount';
		$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
		$tpl['data']['time'] = time();
		$tpl['data']['user_id'] = $user_id;

		$lng['new_promise_amount'] = str_ireplace(array('[amount]', '[currency]'), array($tpl['data']['amount'], $tpl['data']['currency_name']), $lng['new_promise_amount']);
		$lng['main_question'] = str_ireplace(array('[amount]', '[currency]'), array($tpl['data']['amount'], $tpl['data']['currency_name']), $lng['main_question']);

		require_once( ABSPATH . 'templates/tasks_promised_amount.tpl');

		break;

	default:
		require_once( ABSPATH . 'templates/tasks.tpl');
	
	
	
}


?>