<?php
if (!$argv) die('browser');

/*
 * Важно! отключать в кроне при обнулении данных в БД
*/

define( 'DC', TRUE);
set_time_limit(0);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (get_my_block_id($db) > get_block_id($db))
	die('get_my_block_id > get_block_id');

$currency_list = get_currency_list($db);

// нужно знать текущий блок, который есть у большинства нодов
$block_id = get_confirmed_block_id($db);

// сколько должно быть подтверждений, т.е. кол-во блоков сверху
$confirmations = 5;

// берем всех юзеров по порядку
$community = get_community_users($db);
for ($k=0; $k<sizeof($community); $k++) {

	define('MY_PREFIX', $community[$k] . '_');
	// наш приватный ключ нода, которым будем расшифровывать комменты
	$private_key = get_node_private_key($db, MY_PREFIX);
	// возможно, что комменты будут зашифрованы юзерским ключем
	if (!$private_key)
		$private_key = get_miner_private_key($db);
	// если это еще не майнер и админ ноды не указал его приватный ключ в табле my_keys, то $private_key будет пуст
	if (!$private_key)
		continue;

	$my_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT `shop_secret_key`,
						 `shop_callback_url`
			FROM `".DB_PREFIX.MY_PREFIX."my_table`
			", 'fetch_array' );

	// Получаем инфу о входящих переводах и начисляем их на счета юзеров
	$res = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
			SELECT *
			FROM `" . DB_PREFIX . MY_PREFIX . "my_dc_transactions`
			WHERE `type` = 'from_user' AND
						 `block_id` < " . ($block_id - $confirmations) . " AND
						 `merchant_checked` = 0 AND
						 `status` = 'approved'
			ORDER BY `id` DESC
			");
	//print $db->printsql();
	while ($row = $db->fetchArray($res)) {
		//print_R($row);
		// вначале нужно проверить, точно ли есть такой перевод в блоке
		$binary_data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `" . DB_PREFIX . "block_chain`
				WHERE `id` = {$row['block_id']}
				", 'fetch_one');
		$parsedata = new ParseData($binary_data, $db);
		$parsedata->ParseData_tmp();
		$tx_array = $parsedata->tx_array;
		//print_R($tx_array);
		for ($i = 0; $i < sizeof($tx_array); $i++) {

			//print "{$tx_array[$i]['type']} === ".ParseData::findType('send_dc')."\n";
			// пропускаем все ненужные тр-ии
			if ($tx_array[$i]['type'] != ParseData::findType('send_dc'))
				continue;

			$tx_array[$i]['comment'] = bin2hex($tx_array[$i]['comment']);

			// сравнение данных из таблы my_dc_transactions с тем, что в блоке
			if ($tx_array[$i]['user_id'] === $row['type_id'] &&
				$tx_array[$i]['currency_id'] === $row['currency_id'] &&
				(float)$tx_array[$i]['amount'] === (float)$row['amount'] &&
				$tx_array[$i]['to_user_id'] === $row['to_user_id']
			) {

				//print 'OK===============';
				// расшифруем коммент
				if ($row['comment_status'] == 'encrypted') {
					$rsa = new Crypt_RSA();
					$rsa->loadKey($private_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
					$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
					$decrypted_comment = $rsa->decrypt(hextobin($row['comment']));
					unset($rsa);

					// запишем расшифрованный коммент, чтобы потом можно было найти перевод в ручном режиме
					$decrypted_comment = filter_var($decrypted_comment, FILTER_SANITIZE_STRING);
					$decrypted_comment = str_replace(array('\'', '"'), '', $decrypted_comment);
					$decrypted_comment = $db->escape($decrypted_comment);
					$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						UPDATE `" . DB_PREFIX . MY_PREFIX . "my_dc_transactions`
						SET  `comment` = '{$decrypted_comment}',
								`comment_status` = 'decrypted'
						WHERE `id` = {$row['id']}
						");
				} else {
					$decrypted_comment = $row['comment'];
				}
				// возможно, что чуть раньше было reduction, а это значит, что все тр-ии,
				// которые мы ещё не обработали и которые были До блока с reduction нужно принимать с учетом reduction
				// т.к. средства на нашем счете уже урезались, а  вот те, что после reduction - остались в том виде, в котором пришли
				$last_reduction = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						SELECT *
						FROM `" . DB_PREFIX . "reduction`
						WHERE `currency_id` = {$row['currency_id']}
						ORDER BY `block_id`
						LIMIT 1
						", 'fetch_array');
				if ($row['block_id'] <= $last_reduction['block_id']) {
					// сумму с учетом reduction
					$k0 = (100 - $last_reduction['pct']) / 100;
					$row['amount'] = $row['amount'] * $k0;
				}

				// делаем запрос к callback скрипту
				preg_match("/\s*#\s*([0-9]+)\s*/i", $decrypted_comment, $order);
				$order_id = $order[1];
				if ($order_id) {
					$sign = hash('sha256', "{$row['amount']}:{$currency_list[$row['currency_id']]}:{$order_id}:{$row['block_id']}:{$tx_id}:{$my_data['shop_secret_key']}");
					$tx_id = $row['id'];
					$url = $my_data['shop_callback_url'];
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
					curl_setopt($ch, CURLOPT_TIMEOUT, 20);
					curl_setopt($ch, CURLOPT_HEADER, true);
					curl_setopt($ch, CURLOPT_NOBODY, true);
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('amount' => $row['amount'], 'currency' => $currency_list[$row['currency_id']], 'order_id' => $order_id, 'block_id' => $row['block_id'], 'tx_id' => $tx_id, 'sign' => $sign)));
					$answer = curl_exec($ch);
					//print $answer;
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					print $http_code;

					if ($http_code == 200) {
						// отметим merchant_checked=1, чтобы больше не брать эту тр-ию
						$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
								UPDATE `" . DB_PREFIX . MY_PREFIX . "my_dc_transactions`
								SET `merchant_checked` = 1
								WHERE `id` = {$row['id']}
								");
					}
				}
			}
		}
	}
}

?>