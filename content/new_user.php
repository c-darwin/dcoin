<?php
if (!defined('DC')) die("!defined('DC')");

$param = array();
$param['nopass']['x'] = 176;
$param['nopass']['y'] = 100;
$param['nopass']['width'] = 100;
$param['nopass']['bg_path'] = ABSPATH.'img/k_bg.png';
$param = $param['nopass'];

$my_refs_keys = array();
if (empty($_SESSION['restricted'])) {
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `".DB_PREFIX.MY_PREFIX."my_new_users`.*,
						 `log_id`
			FROM `".DB_PREFIX.MY_PREFIX."my_new_users`
			LEFT JOIN `".DB_PREFIX."users` ON `".DB_PREFIX."users`.`user_id` = `".DB_PREFIX.MY_PREFIX."my_new_users`.`user_id`
			WHERE `status` = 'approved'
			");
	while ( $row = $db->fetchArray( $res ) ) {

		// проверим, не сменил ли уже юзер свой ключ
		if ($row['log_id']) {
			$my_refs_keys[$row['user_id']] = array('user_id'=>$row['user_id']);
		}
		else {
			$my_refs_keys[$row['user_id']] = $row;
			$k_path = ABSPATH . 'public/' . substr(md5($row['private_key']), 0, 16);
			$k_path_png = $k_path . '.png';
			$k_path_txt = $k_path . '.txt';
			if (!file_exists($k_path_png)) {
				$private_key = str_replace(array('-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----'), '', $row['private_key']);
				$gd = key_to_img($private_key, $param, $row['user_id']);
				imagepng($gd, $k_path_png);
				file_put_contents($k_path_txt, trim($private_key));
			}
		}
	}
}

// инфа по рефам юзера
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `referral`, sum(`amount`) as `amount`,
				     `currency_id`
		FROM `referral_stats`
		WHERE `user_id` = {$user_id}
		GROUP BY `currency_id`,
						  `referral`
		");
$refs = array();
while ( $row = $db->fetchArray( $res ) ) {
	$refs[$row['referral']][$row['currency_id']] = $row['amount'];
}
$my_refs_amounts = array();
foreach ($refs as $ref_user_id=>$ref_data) {
	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$ref_user_id}
			LIMIT 1
			", 'fetch_array' );
	// получим ID майнеров, у которых лежат фото нужного нам юзера
	if (!$data)
		continue;
	$miners_ids = ParseData::get_miners_keepers($data['photo_block_id'], $data['photo_max_miner_id'],  $data['miners_keepers'], true);
	$hosts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX."miners_data`
			WHERE `miner_id` IN (".implode(',', $miners_ids).")
			", 'array' );
	$my_refs_amounts[$ref_user_id]['amounts'] = $ref_data;
	$my_refs_amounts[$ref_user_id]['hosts'] = $hosts;
}

$tpl['my_refs'] = array();
foreach ($my_refs_amounts as $ref_user_id=>$ref_data) {
	$tpl['my_refs'][$ref_user_id] = $ref_data;
}
foreach ($my_refs_keys as $ref_user_id=>$ref_data) {
	$tpl['my_refs'][$ref_user_id]['key'] = $ref_data['private_key'];
}

/*
 * Общая стата по рефам
 */
// берем лидеров по USD
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `user_id`,
					  sum(`amount`) as `amount`
		FROM `referral_stats`
		WHERE `currency_id` = 72
		GROUP BY `user_id`
		ORDER BY `amount` DESC
		");
while ( $row = $db->fetchArray( $res ) ) {
	// вся прибыль с рефов у данного юзера
	$ref_amounts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT ROUND(sum(`amount`)) as `amount`,
					   `currency_id`
			FROM `referral_stats`
			WHERE `user_id` = {$row['user_id']}
			GROUP BY `currency_id`
			", 'all_data');

	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$row['user_id']}
			LIMIT 1
			", 'fetch_array' );
	// получим ID майнеров, у которых лежат фото нужного нам юзера
	$miners_ids = ParseData::get_miners_keepers($data['photo_block_id'], $data['photo_max_miner_id'],  $data['miners_keepers'], true);
	$hosts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX."miners_data`
			WHERE `miner_id` IN (".implode(',', $miners_ids).")
			", 'array' );
	$tpl['global_refs'][$row['user_id']]['amounts'] = $ref_amounts;
	$tpl['global_refs'][$row['user_id']]['hosts'] = $hosts;
}


$tpl['data']['type'] = 'new_user';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['last_tx'] = get_last_tx($user_id, $tpl['data']['type_id']);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_tx($tpl['last_tx']);

$config = get_node_config();
$tpl['pool_url'] = $config['pool_url'];

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/new_user.tpl' );

?>