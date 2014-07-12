<?php

if (!defined('DC'))
	die('!DC');

/*
 * В get_blocks из-за того, что текущая цепочка блоков откатывается, а взамен неё пишется новая, то
 * тр-ии, которые были в предыдущей и не попали в новую теряются. Это может коснуться тех тр-ий,
 * которые не успели распространиться по сети
 * */
# временно тут
date_default_timezone_set('America/New_York');
##########################################################################
$my_lock = false;
$lock_time = 0;
$default_lang = 'ru';

if(substr(PHP_OS, 0, 3) == "WIN")
	define('OS', 'WIN');
else
	define('OS', 'NIX');

function generate_token($num, $rand_sleep=0) {

  $arr = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
  $pass = "";  
  for($i = 0; $i < $num; $i++)   {
    $index = rand(0, count($arr) - 1);  
    $pass .= $arr[$index];
	if ($rand_sleep)
		usleep(rand(0, $rand_sleep));
  }
  return $pass;  
} 


function calc_pct($pct_sec, $start, $end, $sum) {
	return number_format((pow(($pct_sec/100+1), ($end-$start))*$sum-$sum), 10, '.', '');
}

function hextobin ($hexstr)
{
	return pack("H*",$hexstr);
}

function clear_comment($comment_text, $db)
{
	$comment_text = filter_var($comment_text, FILTER_SANITIZE_STRING);
	$comment_text = str_ireplace(array('\'', '"'),  array('', ''), $comment_text);
	$comment_text = $db->escape($comment_text);
	return $comment_text;
}

function clear_quotes($text)
{
	return str_ireplace(array('\'', '"'),  array('', ''), $text);
}
    
// функция проверки входящих данных
function check_input_data ($data, $type, $info='')
{
	switch ($type) {

		case 'reduction_type' :
			if ( preg_match ("/^(manual|promised_amount)$/D", $data))
				return true;
			break;

		case 'referral' :
			if ( preg_match ("/^[0-9]{1,2}$/Di", $data) && $data <= 30 )
				return true;
			break;

		case 'sleep_var' :
			//{"is_ready":[0,5,10,15,20,40,80,128,256,512,1024,2048,4096,8192],"generator":[30,30,30,30,30,30,30,30,30,30,30,30,30,30]}
			if ( preg_match ("/^\{\"is_ready\"\:\[([0-9]{1,5},){1,100}[0-9]{1,5}\],\"generator\"\:\[([0-9]{1,5},){1,100}[0-9]{1,5}\]\}$/D", $data) )
				return true;
			break;

		case 'payment_systems_ids' :
			if ( preg_match ("/^([0-9]{1,4},){0,4}[0-9]{1,4}$/D", $data) )
				return true;
			break;

		case 'db_prefix' :
			if ( preg_match ("/^[0-9a-z\-\_]{0,20}$/Di", $data) )
				return true;
			break;

		case 'hex':
		case 'hex_sign':
			if ( preg_match ("/^[0-9a-z]{64,2048}$/D", $data) )
				return true;
			break;

		case 'hex_message':
			if ( preg_match ("/^[0-9a-z]{1,10240}$/D", $data) )
				return true;
			break;

		case 'level':

			if ( $data >= 0 && $data <= 34 )
				return true;
			break;

		case 'soft_type':

			if ( preg_match ("/^[a-z]{3,10}$/iD", $data) )
				return true;
			break;

		case 'currency_commission':

			if ( preg_match ("/^[0-9]{1,7}(\.[0-9]{1,2})?$/D", $data) )
				return true;
			break;

		case 'currency_name' :

			if ( preg_match ("/^[A-Z]{3}$/D", $data) )
				return true;
			break;

		case 'currency_full_name' :

			if ( preg_match ("/^[a-z\s]{3,50}$/iD", $data) )
				return true;
			break;

		case 'users_ids':

			if ( preg_match ("/^([0-9]{1,12},){0,1000}[0-9]{1,12}$/D", $data) )
				return true;
			break;

		case 'country':

			if ( preg_match ("/^[0-9]{1,3}$/D", $data) && $data<245 )
				return true;
			break;

		case 'race':

			if (preg_match ("/^[1-3]$/D", $data))
				return true;
			break;

		case 'example_spots' :

			/*$r1 = '"\d{1,2}":\["\d{1,3}","\d{1,3}",(\[("[a-z_]{1,20}",){0,20}"[a-z_]{1,20}"\]|""),"\d{1,2}","\d{1,2}"\]';
			$face = "\"face\":\{({$r1}\,){1,30}{$r1}\}";
			$profile = "\"profile\":\{({$r1}\,){1,30}{$r1}\}";*/
			$r1 = '"\d{1,2}":\["\d{1,3}","\d{1,3}",(\[("[a-z_]{1,30}",?){0,20}\]|""),"\d{1,2}","\d{1,2}"\]';
			$reg = "/^\{(\"(face|profile)\":\{({$r1},?){1,20}\},?){2}}$/D";
			if (preg_match ($reg, $data))
				return true;

			break;

		case 'segments' :

			$r1 = '"\d{1,2}":\["\d{1,2}","\d{1,2}"\]';
			$face = "\"face\":\{({$r1}\,){1,20}{$r1}\}";
			$profile = "\"profile\":\{({$r1}\,){1,20}{$r1}\}";

			$reg = "/^\{{$face},{$profile}\}$/D";
			if (preg_match ($reg, $data))
				return true;

			break;

		case 'tolerances' :

			$r1 = '"\d{1,2}":"0\.\d{1,2}"';
			$face = "\"face\":\{({$r1}\,){1,50}{$r1}\}";
			$profile = "\"profile\":\{({$r1}\,){1,50}{$r1}\}";

			$reg = "/^\{{$face},{$profile}\}$/D";
			if (preg_match ($reg, $data))
				return true;

			break;

		case 'compatibility' :

			if (preg_match ("/^\[(\d{1,5},)*\d{1,5}\]$/D", $data))
				return true;

			break;

		case 'admin_variables' :

			$xy = '\"\w{1,100}\"\:\"\d{1,10}\"';
			$r = "/^\{({$xy}\,){0,43}{$xy}\}$/D";
			if (preg_match ($r, $data))
				return true;

			break;

		case 'lang_full_name':

			if (preg_match('/^[\p{L}\p{Nd}\p{Zs}]{1,100}$/u', $data))
				return true;

			break;

		case 'admin_currency_list':

			if (preg_match('/^((\d{1,3}\,){0,9}\d{1,3}|ALL)$/D', $data))
				return true;
			break;

		case 'new_max_promised_amounts':

			$xy = '\"\d{1,3}\"\:\d{1,9}';
			$r = "/^\{({$xy}\,){0,165}{$xy}\}$/D";
			if (preg_match ($r, $data))
				return true;
			break;

		case 'new_pct':

			$xy = '\"\d{1,3}\"\:\{\"miner_pct\"\:(0\.[0-9]{13}),"user_pct\"\.:(0\.[0-9]{13})\}';
			$r = "/^\{({$xy}\,){0,165}{$xy}\}$/D";
			if (preg_match ($r, $data))
				return true;
			break;

		case 'private_key':

			if (preg_match('/^[0-9a-z\+\-\s\=\/]{256,3072}$/iD', $data))
				return true;
			break;

		case 'public_key':

			if (preg_match('/^[0-9a-z]{256,2048}$/iD', $data))
				return true;
			break;

		case 'cash_code':

			if (preg_match('/^[0-9a-z]{32,64}$/iD', $data))
				return true;
			break;

		case 'promised_amounts_ids':
			$xy = '\d{1,19}';
			if (preg_match("/^({$xy}\,){0,9}{$xy}$/i", $data))
				return true;
			break;

		case 'md5_hash':

			if (preg_match('/^[0-9a-z]{32}$/iD', $data))
				return true;
			break;

			
		case 'hash_code':

			if (preg_match('/^[0-9a-z]{64,128}$/iD', $data))
				return true;
			break;

		case 'amount':

			if (preg_match('/^[0-9]{0,10}(\.[0-9]{0,2})?$/D', $data))
				return true;
			break;

		case 'sell_rate':

			if (preg_match('/^[0-9]{0,10}(\.[0-9]{0,10})?$/D', $data))
				return true;
			break;

		case 'coordinate':

			if (preg_match('/^\-?[0-9]{1,3}(\.[0-9]{1,5})?$/D', $data))
				return true;
			break;

		case 'admin_id':

			if (preg_match('/^1$/D', $data))
				return true;
			break;

		case 'boolean':
		case 'vote':

			if (preg_match('/^0|1$/D', $data))
				return true;
			break;

		case 'currency_value':

			if (preg_match('/^[0-9]{1,10}$/D', $data))
				return true;
			break;

		case 'currency_id':

			if (preg_match('/^[0-9]{1,3}$/D', $data) && $data<256)
				return true;
			break;

		// пропускаем данные в сыром виде
		case 'promised_amount_serial':

			// http://www.php.net/manual/en/regexp.reference.unicode.php
			if (preg_match('/^[\p{L}\p{Nd}\p{Zs}-]+$/u', $data))
				return true;
			break;

		case 'video_type':

			if (preg_match('/^(youtube|vimeo|youku|null)$/iD', $data))
				return true;
			break;

		case 'relation':

			if (preg_match('/^[0-9]{1,4}(\.[0-9]{,4})?$/iD', $data))
				return true;
			break;

		case 'coords':

			$xy = '\[\d{1,3}\,\d{1,3}\]';
			$r = "/^\[({$xy}\,){{$info}}{$xy}\]$/i";
			// print "$r \n $data";
			if (preg_match ($r, $data))
				return true;
			break;

		case 'video_url_id':

			if (preg_match('/^([0-9a-z_-]{5,32}|null)$/iD', $data))
				return true;
			break;

		case 'invite_hash':
			if (preg_match('/^[0-9a-z]{32}$/iD', $data))
				return true;
			break;

		case 'invite': // доделать
			if (preg_match('/^[0-9a-z]{32}$/iD', $data))
				return true;
			break;

		case 'photo_hash':
		case 'sha256':
			if (preg_match('/^[0-9a-z]{64}$/iD', $data))
				return true;
			break;

		case 'md5':
			if (preg_match('/^[0-9a-z]{32}$/iD', $data))
				return true;
			break;

		case 'host':

			if ( substr_count ($data, '/') > 5 )
				return false;

			if (preg_match('/^https?:\/\/[0-9a-z\_\.\-\/:]{1,100}[\/]$/iD', $data))
				return true;

			break;

		case 'message':

			//// print 'message='.$data;
			if (preg_match('/^[a-z0-9\)\(\?\&\=\.\,\-\_\+\@\:\"\;\/\s\n\r]{1,1000}$/iD', $data))
				return true;
			break;

		case 'entropy':

			if (preg_match('/^[0-9]{1000,10000}$/D', $data))
				return true;
			break;

		// коммент в бинарном виде, проверить можно только длину
		case 'comment':

			if (strlen($data)>=1 && strlen($data)<=512)
				return true;
			break;

		case 'vote_comment':
		case 'abuse_comment': // комментарий к абузе на майнера

			if (preg_match('/^[0-9a-z\,\s\.\-]{1,255}$/D', $data))
				return true;
			break;

		case 'lang': // выбор языка
			
			if (preg_match('/^[a-z]{2}$/D', $data))
				return true;
			break;

		case 'promised_amount_id':
		case 'mining_id':
		case 'user_id':
		case 'bigint':

			if (preg_match('/^[0-9]{1,20}$/D', $data) && $data < 18446744073709551615)
				return true;

			break;

		case 'float':

			if (preg_match('/^[0-9]{1,5}(\.[0-9]{1,5})?$/D', $data))
				return true;

			break;

		case 'version':

			if ( preg_match('/^[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}([a-z]{1,2}[0-9]{1,2})?$/D', $data) )
				return true;

			break;

		case 'int':

			if (preg_match('/^[0-9]{1,10}$/D', $data) && $data < 4294967295)
				return true;

			break;

		case 'smallint':

			if (!preg_match('/^[0-9]{1,5}$/D', $data))
				return false;
			if ($data > 65535)
				return false;

			break;
			
	    case 'tpl_name': // название шаблона для инклуда
			
			if (preg_match('/^[\w]{1,30}$/D', $data))
				return true;
			
			break;
			
		case '':
			if (preg_match('/^[[:alpha:]]{2}$/D', $data))
				return true;
		
			break;
			
		default:
			return false;
	}
}

/*
 * $remote_node_user_id - это когда идет пересылка уже зашифрованной тр-ии внутри сети. Чтобы protected_gate_tx.php мог понять,
 * какому ноду слать эту тр-ию, пишем в первые 5 байт user_id
 * */
function m_curl ($urls, $_data, $db, $type='data', $timeout=10, $answer=false, $post=true, $remote_node_host='')
{
	//создаем набор дескрипторов cURL
	$mh = curl_multi_init();

	// при $remote_node_host будет всего 1 url - ip из локальной сети
	for ($i=0; $i<sizeof($urls); $i++) {

		debug_print('$urls['.$i.']: '.print_r_hex($urls[$i]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($db) {
			// т.к. на приеме может быть пул, то нужно дописать user_id, чьим нодовским ключем шифруем
			$data = encrypt_data ($_data, $urls[$i]['node_public_key'], $db);
			$data = dec_binary($urls[$i]['user_id'], 5).$data;
		}
		else
			$data = $_data;
		if ($remote_node_host)
			$data = ParseData::encode_length_plus_data($remote_node_host) . $data;

		// создаем ресурс cURL
		$ch[$i] = curl_init();
		//// print $urls[$i];
		curl_setopt($ch[$i], CURLOPT_URL, $urls[$i]['url']);
		//curl_setopt($ch[$i], CURLOPT_FAILONERROR, 1);
		curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT , 10); // timeout in seconds
		curl_setopt($ch[$i], CURLOPT_TIMEOUT, $timeout);
		if ($post) {
			curl_setopt($ch[$i], CURLOPT_POST, 1);
			curl_setopt($ch[$i], CURLOPT_POSTFIELDS, $type.'='.urlencode($data));
		}
		if ($answer)
			curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
		//$params[] = 'data='.urlencode($data);
		//curl_setopt($ch[$i], CURLOPT_POSTFIELDS, $params);	

		
		//добавляем X дескрипторов
		curl_multi_add_handle($mh, $ch[$i]);
	}
	
	$active = null;
	
	//запускаем дескрипторы
	do {
		$mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	
	
	while ($active && $mrc == CURLM_OK) {
		if (curl_multi_select($mh) != -1) {
			do {
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		}
	}

	$return = array();
	if ($answer) {
		for ($i=0; $i<sizeof($urls); $i++) {
			$return[$urls[$i]['url']] = curl_multi_getcontent ( $ch[$i] );
		}
	}

	for ($i=0; $i<sizeof($urls); $i++) {
		// закрываем все дескрипторы
		curl_multi_remove_handle($mh, $ch[$i]);
	}
	curl_multi_close($mh);
	return $return;
}


function dec_binary($dec, $size_bytes) {

	return pack( "H*", str_pad( dechex( $dec ), $size_bytes*2, "0", STR_PAD_LEFT ) );
}

function binary_dec ($binary) {

	$hex = unpack( "H*", $binary );
	return hexdec( $hex[1] );
}

function encode_length ($length) {

	if ($length <= 0x7F) {
		return chr($length);
	}
	$temp = ltrim(pack('N', $length), chr(0));
	return pack('Ca*', 0x80 | strlen($temp), $temp);
}

    function string_shift (&$string, $index = 1) {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }
    
    function decode_length (&$string) {
        $length = ord(string_shift($string));
        if ( $length & 0x80 ) {
            $length&= 0x7F;
            $temp = string_shift($string, $length);
            list(, $length) = unpack('N', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4));
        }
        return $length;
    }

	function parse_transaction ($data, $num = 20) {

		do {
			$length = decode_length($data);
			// print '$length='.$length."\n";
			if ($length)
				$return_array[] = string_shift($data, $length);
		} while ($length);

		return $return_array;
	}

function get_points ( $db ) {

	global $lng;

	$example_spots = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `example_spots`
		FROM `'.DB_PREFIX.'spots_compatibility`
		', 'fetch_one');
	$example_spots = json_decode($example_spots, true);

	$print = array();
	$print['face'] = '';
	$print['profile'] = '';
	foreach ($example_spots as $type=>$data) {
		//$print[$type] = array();

		foreach ($data as $id=>$array) {

			$print[$type].= "[{$array[0]}, {$array[1]},  '{$id}. {$lng['points-'.$type.'-'.$id]}'";

			if ( $array[2] ) {
				$print[$type].= ", [".($array[3]-1).", ".($array[4]-1).",";
				for ($j=0; $j<sizeof($array[2]); $j++) {

					$print[$type].= "'{$array[2][$j]}'";
					if ( $j != sizeof($array[2])-1 )
						$print[$type].= ",";
				}
				$print[$type].= "] ]";
			}
			else
				$print[$type].= "]";

			$print[$type].= ",\n";
		}
		$print[$type] = substr($print[$type], 0, strlen($print[$type])-2);
	}

	/*
	for ($i=1; $i <= sizeof( $array ); $i++) {

		$print.= "[{$array[$i]['example_x']}, {$array[$i]['example_y']},  '{$i}. {$array[$i]['comment']}'";

		if ( $array[$i]['instructions'] ) {

			$print.= ", [".($array[$i]['p1']-1).", ".($array[$i]['p2']-1).",";
			for ($j=0; $j<sizeof($array[$i]['instructions'] ); $j++) {

				$print.= "'{$array[$i]['instructions'][$j]}'";
				if ( $j != sizeof( $array[$i]['instructions'] )-1  )
					$print.= ",";
			}
			$print.= "] ]";
		}
		else
			$print.= "]";
		if ( $i != sizeof($tpl['example_points']['face']) )
			$print.= ",\n";
		else
			$print.= "\n";
	}
	*/
	return $print;
}

/*
function get_example_spots($db) {

	// текущий набор точек для шаблонов
	$example_spots = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'example_spots`
		', 'fetch_one');

	while ($row = $db->fetchArray($res)) {
		$example_points[ $row['type'] ][ $row['id'] ] = $row;

		if ($example_points[ $row['type'] ][ $row['id'] ]['instructions']) {

			$instructions = explode( ',', $example_points[ $row['type'] ][ $row['id'] ]['instructions'] );
			$example_points[ $row['type'] ][ $row['id'] ]['instructions'] = $instructions;
		}

		//$example_points[ $row['type'] ][ $row['id'] ]['comment'] = $lang_data['points-'.$row['type']][$row['id']];
	}
	return $example_points;
}
*/

function clear_public_key($key)
{
	preg_match('/[\-]+BEGIN PUBLIC KEY[\-]+([\w\+\/\n\r]+)[\-]+END PUBLIC KEY[\-]+/ms', $key, $matches);
	$bin = base64_decode( trim($matches[1] ) );
	list(, $hex) = unpack( "H*", $bin);
	return  $hex;
}


function send_mail($mail_data)
{
	//debug_print($mail_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$mail                = new PHPMailer();
	if ($mail_data['use_smtp'] && $mail_data['smtp_server'])
	{
		$mail->IsSMTP();
		$mail->SMTPAuth      = ($mail_data['smtp_auth']?true:false);
		$mail->SMTPSecure    = ($mail_data['smtp_ssl']?'ssl':'');
		$mail->Host          = $mail_data['smtp_server'];
		$mail->Port          = $mail_data['smtp_port'];
		$mail->Username      = $mail_data['smtp_username'];
		$mail->Password      = $mail_data['smtp_password'];
	}
	$mail->SetFrom($mail_data['email'], 'Server');

	$mail->Subject       = $mail_data['subj'];
	$mail->Body    = $mail_data['text'];
	$mail->AddAddress($mail_data['email'], 'Server');

	if(!$mail->Send()) {
		echo json_encode(
			array('error'=>'Mailer Error (' . str_replace("@", "&#64;", $mail_data["email"]) . ') ' . $mail->ErrorInfo . '<br />')
		);
	} else {
		echo json_encode(
			array('error'=>'null')
		);
	}
}


function send_sms($sms_http_get_request, $text) {

	$ch = curl_init($sms_http_get_request.$text);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10); // timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_exec($ch);
	curl_close($ch);

}

function get_currency_list($db)
{
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					  `name`
		FROM `'.DB_PREFIX.'currency`
		 ORDER BY `name`
		 ');
	while ($row = $db->fetchArray($res))
		$currency_list[$row['id']] = $row['name'];
	return $currency_list;
}

function get_block_id($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `block_id`
				FROM `".DB_PREFIX."info_block`
				", 'fetch_one');
}

function get_user_public_key($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `public_key`
			FROM `".DB_PREFIX."my_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_keys`)
			LIMIT 1
			", 'fetch_one' );
}

function get_miner_private_key($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `private_key`
			FROM `".DB_PREFIX.MY_PREFIX."my_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX.MY_PREFIX."my_keys`)
			LIMIT 1
			", 'fetch_one' );
}

function get_node_private_key($db, $my_prefix)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `private_key`
			FROM `".DB_PREFIX."{$my_prefix}my_node_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."{$my_prefix}my_node_keys`)
			LIMIT 1
			", 'fetch_one' );
}

function get_my_miner_id($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `miner_id`
			FROM `".DB_PREFIX.MY_PREFIX."my_table`
			", 'fetch_one' );
}

function get_my_miners_ids($db, $collective)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT `miner_id`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` IN (".implode(',', $collective).") AND
						 `miner_id` > 0
			", 'array' );
}

// для пулов
function get_community_users($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX."community`
			", 'array' );
}

// доступ к управлению нодой есть только у админа ноды
function node_admin_access($db)
{
	$community = get_community_users($db);
	if ($community) {
		$pool_admin_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `pool_admin_user_id`
				FROM `".DB_PREFIX."config`
				", 'fetch_one' );
		if ( (int)$_SESSION['user_id'] === (int)$pool_admin_user_id )
			return true;
		else
			return false;
	}
	else
		return true;
}


function pool_add_users ($pool_data, $my_queries, $mysqli_link, $prefix, $install=false)
{
	$pool_data = explode("\n", $pool_data);
	debug_print('$pool_data='.print_r_hex($pool_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	for ($i=0; $i<sizeof($pool_data); $i++) {

		$data = explode(';', $pool_data[$i]);
		$user_id = intval(trim($data[0]));
		$my_prefix = $user_id.'_';
		$my_public_key = trim($data[1]);

		debug_print('$my_prefix='.$my_prefix, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$my_public_key='.$my_public_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ( !check_input_data ($my_public_key, 'public_key') )
			return 'bad public_key - '.$my_public_key;

		for ($j=0; $j<sizeof($my_queries); $j++) {

			debug_print(' $my_queries[$j]='. $my_queries[$j], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$my_query = str_ireplace('[my_prefix]', $my_prefix, $my_queries[$j]);
			mysqli_multi_query($mysqli_link, $my_query);
			while (@mysqli_next_result($mysqli_link)) {;}

			if ( mysqli_error($mysqli_link) ) {
				return 'Error performing query (' . $my_query . ') - Error message : '. mysqli_error($mysqli_link);
			}
		}

		mysqli_query($mysqli_link, "
				INSERT INTO `{$prefix}community` (
					`user_id`
				)
				VALUES (
					{$user_id}
				)");

		// чтобы не было проблем с change_primary_key нужно иметь 0 в my_table и уже через change_primary_key получить user_id, если была смена ключа
		if ($install)
			$my_user_id = 0;
		else
			$my_user_id = $user_id;

		mysqli_query($mysqli_link, "
				INSERT INTO `{$prefix}{$my_prefix}my_table` (
					`user_id`
				)
				VALUES (
					{$my_user_id}
				)");

		mysqli_query($mysqli_link, "
				INSERT INTO `{$prefix}{$my_prefix}my_keys` (
					`public_key`,
					`status`
				)
				VALUES (
					0x{$my_public_key},
					'approved'
				)");
	}
}

function get_my_prefix($db)
{
	if (!get_community_users($db))
		return '';
	else
		return $_SESSION['user_id'].'_';
}

function get_my_host($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX.MY_PREFIX."my_table`
			", 'fetch_one' );
}

function get_my_local_gate_ip($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `local_gate_ip`
			FROM `".DB_PREFIX."config`
			", 'fetch_one' );
}

function get_my_users_ids($db)
{
	$users_ids = get_community_users($db);
	if (!$users_ids) // сингл-мод
		$users_ids[0] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `user_id`
				FROM `".DB_PREFIX."my_table`
				", 'fetch_one' );
	return $users_ids;
}

function get_my_user_id($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX.MY_PREFIX."my_table`
			", 'fetch_one' );
}

function get_my_block_id($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `my_block_id`
			FROM `".DB_PREFIX."config`
			", 'fetch_one' );
}

function get_current_version($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `current_version`
			FROM `".DB_PREFIX."info_block`
			", 'fetch_one' );
}

function get_testblock_id($db)
{
	return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `block_id`
				FROM `".DB_PREFIX."testblock`
				", 'fetch_one');
}

// время выполнения - 0,01сек на 1000. т.е. на 100 валют майнеру и юзеру уйдет 2 сек.
function get_max_vote($array, $min, $max, $step) {

	debug_print( '$array='.print_r_hex($array)."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print( '$min='.$min."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print( '$max='.$max."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print( '$step='.$step."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// если <=10 то тупо берем максимальное кол-во голосов
	if (sizeof($array)<=10) {
		if (sizeof($array)>0) {
			$max_votes = max($array);
			$max_pct = array_search($max_votes, $array);
		}
		else {
			$max_pct = 0;
		}
		return $max_pct;
	}

	// делим набор данных от $min до $max на секции кратные $step
	// таких секций будет 100 при $max=1000
	// цель такого деления - найти ту секцию, где больше всего голосов
	for ($i=$min; $i<$max; $i=$i+$step/10) {
		$min_0 = $i;
		$max_0 = $i+$step;
		// берем из массива те данные, которые попадают в данную секцию
		foreach($array as $number=>$votes) {
			if ($number>=$min_0 && $number<$max_0){
				$data_bank[$i][$number] = $votes;
				//debug_print( '$min_0='.$min_0."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				//debug_print( '$max_0='.$max_0."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			}
		}
		if (isset($data_bank[$i]))
			$sums[$i] = array_sum($data_bank[$i]);
	}
	debug_print( '$data_bank='.print_r_hex($data_bank)."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print( '$sums='.print_r_hex($sums)."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// ищем id секции, где больше всего голосов
	$max_sum = max($sums);
	$max_i = array_search($max_sum, $sums);
	debug_print( '$max_sum:'.$max_sum, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print( '$max_i:'.$max_i, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	debug_print( '$data_bank[$max_i]:'.print_r_hex($data_bank[$max_i]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	// если в этой секции <= 10-и элементов, то просто выбираем максимальное кол-во голосов
	if (sizeof($data_bank[$max_i])<=10) {
		debug_print( 'sizeof($data_bank[$max_i])<=10', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$max_votes = max($data_bank[$max_i]);
		$max_pct = array_search($max_votes, $data_bank[$max_i]);
		return $max_pct;

	} else // без рекурсии, просто один раз вызываем свою функцию
		return get_max_vote($data_bank[$max_i], $max_i, $max_i+$step, $step/10);

}



/*function get_block_generator_miner_id_range ($cur_miner_id, $max_miner_id) {

	$end = 0;
	$begin = 0;
	$minus_1_ok = 0;
	$return = array();
	for ($i=1; $i<6; $i++) {

		$need_users = pow(10, $i);

		if ($begin == 0)
			$begin = $cur_miner_id + 1 + (($i>1)?pow(10, $i-1):0);
		else
			$begin = $end + 1 ;

		if (($begin == $cur_miner_id) || ($end == $cur_miner_id) )
			break;

		$end = $begin + $need_users - 1;

		if ( $end > $cur_miner_id && $minus_1_ok )
			$end = $cur_miner_id-1;

		$end_p = $end;
		if ($end_p > $max_miner_id)
			$end_p = $max_miner_id;

		$return[$i][0] = array($begin, $end_p);

		$minus=0;
		if ($end > $max_miner_id) {
			$minus = $max_miner_id  - $end;

			if (abs($minus)>=$cur_miner_id)
				$minus = - ($cur_miner_id - 1);

			$end = abs($minus);
			$minus_1_ok = 1;

		}

		if ($minus)
			$return[$i][1] = array(1, abs($minus));

	}
	return $return;
}
*/



function ntp_time() {

	$bit_max = 4294967296;
	$epoch_convert = 2208988800;
	$vn = 3;

	$servers = array('europe.pool.ntp.org',
								'asia.pool.ntp.org',
								'oceania.pool.ntp.org',
								'north-america.pool.ntp.org',
								'south-america.pool.ntp.org',
								'africa.pool.ntp.org');
	$server_count = count($servers);

	//see rfc5905, page 20
	//first byte
	//LI (leap indicator), a 2-bit integer. 00 for 'no warning'
	$header = '00';
	//VN (version number), a 3-bit integer.  011 for version 3
	$header .= sprintf('%03d',decbin($vn));
	//Mode (association mode), a 3-bit integer. 011 for 'client'
	$header .= '011';

	//echo bindec($header);

	//construct the packet header, byte 1
	$request_packet = chr(bindec($header));

	//we'll use a for loop to try additional servers should one fail to respond
	$i = 0;
	for($i; $i < $server_count; $i++) {

		$socket = @fsockopen('udp://'.$servers[$i], 123, $err_no, $err_str,1);
		if ($socket) {

			//add nulls to position 11 (the transmit timestamp, later to be returned as originate)
			//10 lots of 32 bits
			for ($j=1; $j<40; $j++) {
				$request_packet .= chr(0x0);
			}

			//the time our packet is sent from our server (returns a string in the form 'msec sec')
			$local_sent_explode = explode(' ',microtime());
			$local_sent = $local_sent_explode[1] + $local_sent_explode[0];

			//add 70 years to convert unix to ntp epoch
			$originate_seconds = $local_sent_explode[1] + $epoch_convert;

			//convert the float given by microtime to a fraction of 32 bits
			$originate_fractional = round($local_sent_explode[0] * $bit_max);

			//pad fractional seconds to 32-bit length
			$originate_fractional = sprintf('%010d',$originate_fractional);

			//pack to big endian binary string
			$packed_seconds = pack('N', $originate_seconds);
			$packed_fractional = pack("N", $originate_fractional);

			//add the packed transmit timestamp
			$request_packet .= $packed_seconds;
			$request_packet .= $packed_fractional;

			if (fwrite($socket, $request_packet)) {

				$data = NULL;
				stream_set_timeout($socket, 1);

				$response = fread($socket, 48);

				//the time the response was received
				$local_received = microtime(true);

				//echo 'response was: '.strlen($response).$response;
			}
			fclose($socket);


			if (strlen($response) == 48) {
				//the response was of the right length, assume it's valid and break out of the loop
				break;
			}
			else {
				if ($i == $server_count-1) {
					//this was the last server on the list, so give up
					return 'unable to establish a connection';
				}
			}
		}
		else {
			if ($i == $server_count-1) {
				//this was the last server on the list, so give up
				return 'unable to establish a connection';
			}
		}
	}

	//unpack the response to unsiged lonng for calculations
	$unpack0 = unpack("N12", $response);
	//print_r($unpack0);


	//present as a decimal number
	$remote_originate_seconds = sprintf('%u', $unpack0[7])-$epoch_convert;
	$remote_received_seconds = sprintf('%u', $unpack0[9])-$epoch_convert;
	$remote_transmitted_seconds = sprintf('%u', $unpack0[11])-$epoch_convert;

	$remote_originate_fraction = sprintf('%u', $unpack0[8]) / $bit_max;
	$remote_received_fraction = sprintf('%u', $unpack0[10]) / $bit_max;
	$remote_transmitted_fraction = sprintf('%u', $unpack0[12]) / $bit_max;

	$remote_originate = $remote_originate_seconds + $remote_originate_fraction;
	$remote_received = $remote_received_seconds + $remote_received_fraction;
	$remote_transmitted = $remote_transmitted_seconds + $remote_transmitted_fraction;

	//unpack to ascii characters for the header response
	$unpack1 = unpack("C12", $response);
	//print_r($unpack1);

	//echo 'byte 1: ' . $unpack1[1] . ' | ';

	//the header response in binary (base 2)
	$header_response =  base_convert($unpack1[1], 10, 2);

	//pad with zeros to 1 byte (8 bits)
	$header_response = sprintf('%08d',$header_response);

	//Mode (the last 3 bits of the first byte), converting to decimal for humans;
	$mode_response = bindec(substr($header_response, -3));

	//VN
	$vn_response = bindec(substr($header_response, -6, 3));

	//the header stratum response in binary (base 2)
	$stratum_response =  base_convert($unpack1[2], 10, 2);
	$stratum_response = bindec($stratum_response);

	//calculations assume a symmetrical delay, fixed point would give more accuracy
	$delay = (($local_received - $local_sent) / 2)  - ($remote_transmitted - $remote_received);

	//$delay_ms = round($delay * 1000) . ' ms';
	//$server = $servers[$i];
	$ntp_time =  $remote_transmitted - $delay;
	$ntp_time_explode = explode('.',$ntp_time);

	//$ntp_time_formatted = date('Y-m-d H:i:s', $ntp_time_explode[0]).'.'.$ntp_time_explode[1];
	return abs(time()-$ntp_time_explode[0]);
}



class testblock {

	protected static $_instance;

	public function __construct($db, $wo_lock=false) {

		if ($wo_lock)
			$this->wo_lock = true;
		else
			$this->wo_lock = false;

		$this->db = $db;
		/*// получаем наш miner_id и приватный ключ нода
		$this->my_table = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `user_id`,
							 `miner_id` ,
							 `private_key`
				FROM `".DB_PREFIX.MY_PREFIX."my_table`
				LEFT JOIN `my_node_keys` ON 1=1
				WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`)
				", 'fetch_array' );*/
		// print 'testblock my_table';
		//print_r($this->my_table);

		if (!$this->wo_lock) main_lock();

		// последний успешно записанный блок
		$this->prev_block = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT LOWER(HEX(`hash`)) as `hash`,
							 LOWER(HEX(`head_hash`)) as `head_hash`,
							 `block_id`,
							 `time`,
							 `level`
				FROM `".DB_PREFIX."info_block`
				", 'fetch_array');
		// print 'testblock prev_block';
		//print_r($this->prev_block);

		// общее кол-во майнеров.
		$max_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT max(`miner_id`)
				FROM `".DB_PREFIX."miners`
				", 'fetch_one' );

		$i=0;
		do {
			// если майнера заморозили то у него исчезает miner_id, чтобы не попасть на такой пустой miner_id
			// нужно пербирать энтропию, пока не дойдем до существующего miner_id
			if ($i==0)
				$entropy = self::get_entropy($this->prev_block['head_hash']);
			else {
				$block_id = $this->prev_block['block_id'] - $i;
				if ($block_id < 1)
					break;

				// bug fixed
				//if ($this->prev_block['block_id']>10) {
					$new_head_hash = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
							SELECT LOWER(HEX(`head_hash`)) as `head_hash`
							FROM `".DB_PREFIX."block_chain`
							WHERE `id` = {$block_id}
							", 'fetch_one' );
					$entropy = self::get_entropy($new_head_hash);
				/*}
				else {
					$entropy = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
						SELECT `head_hash`
						FROM `".DB_PREFIX."block_chain`
						WHERE `id` = {$block_id}
						", 'fetch_one' );
				}*/
			}
			debug_print('$entropy='.$entropy, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print('$max_miner_id='.$max_miner_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$cur_miner_id = self::get_block_generator_miner_id ($max_miner_id, $entropy);
			// получим ID юзера по его miner_id
			$this->cur_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT `user_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `miner_id` = {$cur_miner_id}
					", 'fetch_one' );
			$i++;
		} while (!$this->cur_user_id);

		//debug_print('(this->my_table[miner_id]='.$this->my_table['miner_id'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$collective = get_my_users_ids($db);

		// в сингл-моде будет только $my_miners_ids[0]
		$my_miners_ids = get_my_miners_ids( $this->db, $collective);

		// есть ли кто-то из нашего пула (или сингл-мода), кто находится на 0-м уровне
		if (in_array($cur_miner_id, $my_miners_ids)) {
			$this->level = 0;
			$this->levels_range[0][1] = $this->levels_range[0][0] = 1;
			$this->miner_id = $cur_miner_id;
		}
		// все остальные уровни
		else {
			$this->levels_range = self::get_block_generator_miner_id_range ($cur_miner_id, $max_miner_id);
			debug_print($this->levels_range , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			if ($my_miners_ids)
				list($this->miner_id, $this->level) = $this->find_miner_id_level($my_miners_ids, $this->levels_range);
			else {
				$this->level = 'NULL'; // у нас нет уровня, т.к. пуст $my_miners_ids, т.е. на сервере нет майнеров
				$this->miner_id = 0;
			}
		}

		$this->user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `user_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `miner_id` = {$this->miner_id}
				", 'fetch_one' );

		debug_print('$this->level ='.$this->level, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$this->miner_id ='.$this->miner_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$this->user_id ='.$this->user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (!$this->wo_lock) main_unlock();

		self::$_instance = $this;

	}

	public static function getInstance() {
		return self::$_instance;
	}

	static function get_entropy($hash) {
		return number_format(hexdec(substr($hash, 0, 6)), 0, '.', '');
	}


	function getSleepData() {
		// получим значения для сна
		$sleep_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT `value`
					FROM `".DB_PREFIX."variables`
					WHERE `name` = 'sleep'
					", 'fetch_one' );
		$this->sleep_data = json_decode($sleep_data, true);
	}

	function getIsReadySleep() {
		$this->getSleepData();
		return $this->get_is_ready_sleep($this->prev_block['level'], $this->sleep_data['is_ready']);
	}

	function getOurLevelNodes() {
		$arr = array();
		//// print '$this->level='.$this->level;
		if ($this->level!='NULL' && $this->level!='') {
			for ($i=$this->levels_range[$this->level][0][0]; $i<=$this->levels_range[$this->level][0][1]; $i++)
				$arr[]=$i;
			if (isset($this->levels_range[$this->level][1])) {
				for ($i=$this->levels_range[$this->level][1][0]; $i<=$this->levels_range[$this->level][1][1]; $i++)
					$arr[]=$i;
			}
		}
		return $arr;
	}

	function getGenSleep()
	{
		$this->getSleepData();

		debug_print($this->prev_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// узнаем время, которые было затрачено в ожидании is_ready предыдущим блоком
		$is_ready_sleep = self::get_is_ready_sleep($this->prev_block['level'], $this->sleep_data['is_ready']);
		// print '$is_ready_sleep='.$is_ready_sleep."\n";
		// сколько сек должен ждать нод, перед тем, как начать генерить блок, если нашел себя в одном из уровней.
		$generator_sleep = self::get_generator_sleep($this->level , $this->sleep_data['generator']);
		// print '$generator_sleep='.$generator_sleep."\n";
		// сумма is_ready всех предыдущих уровней, которые не успели сгенерить блок
		$is_ready_sleep2 = self::get_is_ready_sleep_sum($this->level , $this->sleep_data['is_ready']);
		// print '$is_ready_sleep2='.$is_ready_sleep2."\n";

		// узнаем, сколько нам нужно спать
		$sleep = $is_ready_sleep + $generator_sleep + $is_ready_sleep2;
		// print '$sleep='.$sleep."\n";
		//ob_flush();

		return $sleep;

	}

	/**
	 * Определяем, какой юзер должен генерить блок
	 *
	 * @param int $max_miner_id Общее число майнеров
	 * @param string $ctx Энтропия
	 * @return int ID майнера
	 */
	static function get_block_generator_miner_id ($max_miner_id, $ctx)
	{
		$hi = $ctx / 127773;
		$lo = $ctx % 127773;
		$x = 16807 * $lo - 2836 * $hi;
		if ($x <= 0)
			$x += 0x7fffffff;
		$rez = ( ($ctx = $x) % ($max_miner_id + 1));
		$rez = ($rez==0)?1:$rez;

		return $rez;
	}
	/**
	 * Получаем уровни и диапазоны, начиная от начального $cur_miner_id.
	 * Сгенирировать блок должен $cur_miner_id, но если он не смог,
	 * то дальше идет генерация по уровням.
	 *
	 * @param int $cur_miner_id Главный нод, который должен генерить блок
	 * @param array $max_miner_id Общее число нодов
	 * @return array
	 */
	/*
	function get_block_generator_miner_id_range ($cur_miner_id, $max_miner_id) {

		$i = 0;
		do {

			$count = pow(2, $i);
			$end = $cur_miner_id + $count-1;
			if ($end > $max_miner_id)
				$end = $max_miner_id;

			$arr[$i][0] = $cur_miner_id;
			$arr[$i][1] = $end;
			$cur_miner_id = $end+1;

			$i++;

		} while ($end < $max_miner_id);

		return $arr;
	}*/
	static function get_block_generator_miner_id_range ($cur_miner_id, $max_miner_id)
	{
		debug_print("cur_miner_id={$cur_miner_id}\nmax_miner_id={$max_miner_id}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$end = 0;
		$begin = 0;
		$minus_1_ok = 0;
		$minus_stop = false;
		// на верхнем уровне тот, кто генерит блок первым
		$return = array(0=>array(0=>array($cur_miner_id, $cur_miner_id)));
		//print '$cur_miner_id='.$cur_miner_id."\n";
		//print '$max_miner_id='.$max_miner_id."\n";
		//print_R($return);
		$i = 1;
		do {

			$need_users = pow(2, $i);
			//print '$need_users='.$need_users."\n";

			if ($begin == 0)
				$begin = $cur_miner_id + 1 + (($i>1)?pow(2, $i-1):0);
			else
				$begin = $end + 1 ;

			//print '$begin='.$begin."\n";

			if ($begin == $max_miner_id+1 && !$minus_stop && $cur_miner_id > 1 && $begin != 2) {

				$begin=1;
				//print 'BEGIN = 1';
				$minus_stop = true;
			}
			else {
				if (($begin == $cur_miner_id) || ($end == $cur_miner_id) || $begin>$max_miner_id )
					break;
			}

			$end = $begin + $need_users - 1;

			if ( $end > $cur_miner_id && $minus_1_ok ) {
				//print '$end > $cur_miner_id && $minus_1_ok'."\n";
				$end = $cur_miner_id-1;
			}

			$end_p = $end;
			if ( $end_p > $max_miner_id ) {
				//print '$end_p > $max_miner_id'."\n";
				$end_p = $max_miner_id;
			}

			if ( $end_p == $max_miner_id && $end_p == $cur_miner_id ) {
				//print '$end_p == $max_miner_id && $end_p == $cur_miner_id'."\n";
				$end_p = $cur_miner_id-1;
			}


			$return[$i][0] = array($begin, $end_p);

			$minus=0;
			if ($end > $max_miner_id && !$minus_stop) {
				//print '$end > $max_miner_id && !$minus_stop'."\n";

				$minus = $max_miner_id  - $end;

				if (abs($minus)>=$cur_miner_id)
					$minus = - ($cur_miner_id - 1);

				$end = abs($minus);
				$minus_1_ok = 1;

			}

			if ($minus)
				$return[$i][1] = array(1, abs($minus));

			//print_r($return[$i]);

			$i++;

		} while (true);
		return $return;
	}

		/**
	 * Определяем, к какому уровню принадлежит указанный miner_id
	 *
	 * @param int $miners_ids Юзер или набор юзеров (при работе в пуле), которым определяем уровень
	 * @param array $levels_range Массив уровней с диапазонами
	 * @return array miner_id, level
	 */

	function find_miner_id_level ($miners_ids, $levels_range)
	{
		for ($i=0; $i<sizeof($miners_ids); $i++) {
			foreach ($levels_range as $level => $ranges) {
				if ($miners_ids[$i] >= $ranges[0][0] && $miners_ids[$i] <= $ranges[0][1])
					return array($miners_ids[$i], $level);
				if (isset($ranges[1]))
					if ($miners_ids[$i] >= $ranges[1][0] && $miners_ids[$i] <= $ranges[1][1])
						return array($miners_ids[$i], $level);
			}
		}
	}

	// на 0-м уровне всегда большее значение, чтобы успели набраться тр-ии
	// на остальных уровнях - это время, за которое нужно успеть получить новый блок и занести его в БД
	static function get_generator_sleep($level, $data)
	{
		$sleep = 0;
		// суммируюем время со всех уровней, которые не успели сгенерить блок до нас
		for ($i=0; $i<=$level; $i++)
			$sleep+=$data[$i];
		return $sleep;

	}

	// время на поиск меньшего хэша на уровне
	static function get_is_ready_sleep($level, $data) {
		return $data[$level];
	}

	// сумма is_ready всех предыдущих уровней, которые не успели сгенерить блок
	static function get_is_ready_sleep_sum($level, $data) {
		$sum=0;
		for ($i=0; $i<$level; $i++)
			$sum+=$data[$i];
		return $sum;
	}

	static  function merkle_tree_root($data_array) {

		for ($i=0; $i<sizeof($data_array); $i++) {
			$arr[0][] = hash('sha256', hash('sha256', $data_array[$i]));
		}

		$j=0;
		do {
			for ($i=0; $i<sizeof($arr[$j]); $i=$i+2) {
				if (!isset($arr[$j][$i+1]))
					$arr[$j+1][]=$arr[$j][$i];
				else
					$arr[$j+1][]=hash('sha256', hash('sha256', $arr[$j][$i].$arr[$j][$i+1]));
			}
			$j++;
		} while (sizeof($arr[$j])>1);
		$arr = end($arr);
		return $arr[0];

	}
}

function main_lock()
{
	global $db, $lock_time;
	lock_('main');
}

function main_unlock()
{
	global $db;
	global $my_lock;
	global $lock_time;

	debug_print("\n==========================E N D==========================\nmain_unlock ".get_script_name()."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"DELETE FROM `".DB_PREFIX."main_lock` WHERE `script_name`='".get_script_name()."'" );
	$my_lock = false;
	// для поиска бага задержки времени
	//if (time() - $lock_time > 1)
	//	file_put_contents( ABSPATH . 'log/slow_lock.log', date("H:i:s").'::'.get_script_name().'-'.date("H:i:s", time()).'-'.date("H:i:s", $lock_time)."\n",  FILE_APPEND);
	//ob_save();
	// print "main_unlock\n";
}


function lock_ ($table) {

	global $db;
	global $my_lock;
	global $lock_time;

	do {

		debug_print("try {$table}_lock\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//ob_save();
		// запрещаем любые добавления данных из блоков/тр-ий
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT IGNORE INTO `".DB_PREFIX."{$table}_lock` (
					`lock_time`,
					`script_name`
				)
				VALUES (
					".time().",
					'".get_script_name()."'
				)");
		//debug_print($db->printsql()."\ngetAffectedRows=".$db->getAffectedRows(),  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$affected_rows = $db->getAffectedRows();
		if ($affected_rows==0){
			upd_deamon_time($db);
			// для логов
			$name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT `script_name`
					FROM `".DB_PREFIX."{$table}_lock`
					", 'fetch_one');
			debug_print($name, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if (get_script_name()=='testblock_generator.php' || get_script_name()=='testblock_is_ready.php')
				usleep(200000);
			else
				sleep(1);
		}
	} while ($affected_rows==0);
	$my_lock = true;
	$lock_time = time();
	debug_print("\n==========================B E G I N==========================\n{$table} ".get_script_name()."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
}

function testblock_lock()
{
	lock_('main');
}

function testblock_unlock()
{
	global $db;
	global $my_lock;

	debug_print("\n==========================E N D==========================\ntestblock_unlock ".get_script_name()."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	//$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"DELETE FROM `".DB_PREFIX."testblock_lock` WHERE `script_name`='".get_script_name()."'" );
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"DELETE FROM `".DB_PREFIX."main_lock` WHERE `script_name`='".get_script_name()."'" );
	$my_lock = false;
	//ob_save();
	//// print "testblock_unlock\n";
}


function delete_queue_block() {

	global $db, $new_block_data;

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			DELETE FROM `".DB_PREFIX."queue_blocks`
			WHERE `head_hash` = 0x{$new_block_data['head_hash_hex']} AND
			             `hash` = 0x{$new_block_data['hash_hex']}
			");
	//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
}

function delete_queue_tx($new_tx_data) {

	global $db;

	list(, $hash) = unpack( "H*", $new_tx_data['hash'] );

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			DELETE FROM `".DB_PREFIX."queue_tx`
			WHERE `hash` = 0x{$hash}
			");

	// т.к. мы обрабатываем в queue_parser_tx тр-ии с verified=0, то после их обработки их нужно удалять.
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			DELETE FROM `".DB_PREFIX."transactions`
			WHERE `hash` = 0x{$hash} AND `verified`=0 AND `used` = 0
			");


	//sleep(1);
}

function clear_tmp($blocks) {
	foreach ( $blocks as $block_id => $tmp_file_name ) {
		unlink($tmp_file_name);
		// print '^^^^^^^^^^^^^^unlink'.$tmp_file_name."\n";
	}
}

function save_tmp_644 ($pref, $content)
{
	$tmp = tempnam(sys_get_temp_dir(), $pref);
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		$tmp = str_replace('\\', '/', $tmp);
	file_put_contents ($tmp, $content);
	chmod ( $tmp, 0644 );
	return $tmp;
}

function get_tx_type_and_user_id ($binary_block)
{
	$user_id = 0;
	//$to_user_id = 0;
	//$voting_id = 0;
	$third_var = 0;
	$type = ParseData::binary_dec_string_shift( $binary_block, 1);
	ParseData::binary_dec_string_shift( $binary_block, 4); // уберем время
	$user_id = ParseData::string_shift($binary_block, ParseData::decode_length($binary_block));
	//if ($type==ParseData::findType('cash_request_out'))
	//	$to_user_id = ParseData::string_shift($binary_block, ParseData::decode_length($binary_block));
	//else if (in_array($type, array(ParseData::findType('votes_geolocation'), ParseData::findType('votes_miner'), ParseData::findType('votes_node_new_miner'), ParseData::findType('votes_pct'), ParseData::findType('votes_promised_amount')) ))
	//	$voting_id = ParseData::string_shift($binary_block, ParseData::decode_length($binary_block));
	//$third_var = $to_user_id?$to_user_id:$voting_id;
	if (in_array($type, array(ParseData::findType('cash_request_out'), ParseData::findType('votes_geolocation'), ParseData::findType('votes_miner'), ParseData::findType('votes_node_new_miner'), ParseData::findType('votes_pct'), ParseData::findType('votes_promised_amount'), ParseData::findType('del_promised_amount')) ))
		$third_var = ParseData::string_shift($binary_block, ParseData::decode_length($binary_block));

	debug_print("get_tx_type_and_user_id={$type},{$user_id},{$third_var}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	return array($type, $user_id, $third_var);
}

function parse_block_header (&$binary_block)
{
	// распарсим заголовок блока
	/*
	Заголовок (от 143 до 527 байт )
	TYPE (0-блок, 1-тр-я)        1
	BLOCK_ID   				       4
	TIME       					       4
	USER_ID                         5
	LEVEL                              1
	SIGN                               от 128 до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
	Далее - тело блока (Тр-ии)
	*/
	$block_data['block_id'] = ParseData::binary_dec_string_shift( $binary_block, 4);
	$block_data['time'] = ParseData::binary_dec_string_shift( $binary_block, 4 );
	$block_data['user_id'] = ParseData::binary_dec_string_shift( $binary_block, 5 );
	$block_data['level'] = ParseData::binary_dec_string_shift ( $binary_block, 1 );
	$sign_size = ParseData::decode_length($binary_block);
	$block_data['sign'] = ParseData::string_shift ( $binary_block, $sign_size ) ;
	return $block_data;

}

function rollback_to_block_id($block_id, $db)
{
	rollback_transactions($db);
	rollback_transactions_testblock($db, true);
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			TRUNCATE TABLE `".DB_PREFIX."testblock`
			");

	// откатываем наши блоки
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT *
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` > {$block_id}
			ORDER BY `id` DESC
			");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$LOG_MARKER =  "Откатываем наши блоки до блока {$block_id}";
		$parsedata = new ParseData($row['data'], $db);
		$parsedata->ParseDataRollback();
		unset($parsedata);

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				DELETE
				FROM `".DB_PREFIX."block_chain`
				WHERE `id` = {$row['id']}
				");
	}

	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT *
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` = {$block_id}
			LIMIT 1
			", 'fetch_array');

	ParseData::string_shift($data['data'], 1);
	$block_data['block_id'] = ParseData::binary_dec_string_shift($data['data'], 4);
	$block_data['time'] = ParseData::binary_dec_string_shift($data['data'], 4);
	$block_data['user_id'] = ParseData::binary_dec_string_shift($data['data'], 5);
	$block_data['level'] = ParseData::binary_dec_string_shift($data['data'], 1);

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			UPDATE `".DB_PREFIX."info_block`
			SET  `hash` = 0x".bin2hex($data['hash']).",
					`head_hash` = 0x".bin2hex($data['head_hash']).",
					`block_id` = {$block_data['block_id']},
					`time` = {$block_data['time']},
					`level` = {$block_data['level']}
			");
}

/*
 * $get_block_script_name, $add_node_host используется только при работе в защищенном режиме и только из blocks_collection.php
 * */
function get_blocks($block_id, $host, $user_id, $rollback_blocks, $get_block_script_name='', $add_node_host='')
{
	global $db,  $variables;

	debug_print('$block_id='.$block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$blocks = array();
	$count = 0;
	do {
		// отметимся в БД, что мы живы.
		upd_deamon_time($db);
		// отметимся, чтобы не спровоцировать очистку таблиц
		upd_main_lock($db);
		// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
		if (check_deamon_restart($db)){
			main_unlock();
			exit;
		}

		if ($block_id < 2)
			return '[error] $block_id < 2';

		// если превысили лимит кол-ва полученных от нода блоков
		if ($count > $variables[$rollback_blocks]) {
			//delete_queue_block();
			clear_tmp($blocks);
			return 'error $count > $variables[$rollback_blocks] ('.$count.'>'.$variables[$rollback_blocks].') ['.$rollback_blocks.']';
		}

		if (!$host) {
			$hosts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$user_id}
				", 'fetch_array' );
			$rk = array_rand($hosts, 1);
			$host = $hosts[$rk];
		}


		if (!$get_block_script_name)
			$url = "{$host}/get_block.php?id={$block_id}";
		else
			$url = "{$host}/{$get_block_script_name}?id={$block_id}{$add_node_host}";

		debug_print($url, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$binary_block = curl_exec($ch);
		curl_close($ch);
		debug_print('$binary_block='.$binary_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$binary_block_full = $binary_block;

		if (!$binary_block) {
			debug_print('continue 2 !$binary_block', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			clear_tmp($blocks);
			return 'error !$binary_block';
		}

		ParseData::string_shift($binary_block, 1); // уберем 1-й байт - тип (блок/тр-я)
		// распарсим заголовок блока
		$block_data = parse_block_header ($binary_block);
		debug_print("block_data=".print_r_hex($block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// если существуют глючная цепочка, тот тут мы её проигнорируем
		$bad_blocks = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `bad_blocks`
				FROM `".DB_PREFIX."config`
				" , 'fetch_one');
		$bad_blocks = json_decode($bad_blocks, true);
		debug_print('$bad_blocks='.print_r_hex($bad_blocks), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if (@$bad_blocks[$block_data['block_id']] == bin2hex($block_data['sign'])) {
			clear_tmp($blocks);
			return "bad_block = {$block_data['block_id']}=>{$bad_blocks[$block_data['block_id']]}";
		}

		if ($block_data['block_id'] != $block_id) {
			debug_print("bad block_data['block_id']", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			clear_tmp($blocks);
			return "bad block_data['block_id']";
		}

		// размер блока не может быть более чем max_block_size
		if ( strlen($binary_block) > $variables['max_block_size'] ) {
			//delete_queue_block();
			clear_tmp($blocks);
			return 'error  strlen($binary_block) > $variables[max_block_size] ';
		}

		// нам нужен хэш предыдущего блока, чтобы найти, где началась вилка
		$prev_block_hash = bin2hex($db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `hash`
				FROM `".DB_PREFIX."block_chain`
				WHERE `id` = ".($block_id-1)."
				", 'fetch_one'));
		//debug_print($db->printsql()."\nprev_block_hash={$prev_block_hash}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// нам нужен меркель-рут текущего блока
		$mrkl_root = ParseData::getMrklroot($binary_block, $variables);
		if (substr($mrkl_root, 0, 7) == '[error]') {
			//delete_queue_block();
			clear_tmp($blocks);
			return '$mrkl_root error';
		}

		// публичный ключ того, кто этот блок сгенерил
		$node_public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$block_data['user_id']}
				", 'fetch_one');

		// SIGN от 128 байта до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
		$for_sign = "0,{$block_data['block_id']},{$prev_block_hash},{$block_data['time']},{$block_data['user_id']},{$block_data['level']},{$mrkl_root}";
		debug_print("for_sign={$for_sign}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// проверяем подпись
		$error = ParseData::checkSign ($node_public_key, $for_sign, $block_data['sign'], true);
		if ($error) debug_print("error={$error}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// сам блок сохраняем в файл, чтобы не нагружать память
		$tmp_file_name = tempnam(sys_get_temp_dir(), 'FCB');
		$blocks[$block_id] = $tmp_file_name;
		file_put_contents($tmp_file_name, $binary_block_full);

		$block_id--;
		$count++;

		// качаем предыдущие блоки до тех пор, пока отличается хэш предудущего.
		// другими словами, пока подпись с $prev_block_hash будет неверной, т.е. пока что-то есть в $error
		if ( !$error ) {
			debug_print("===========Вилка найдена\nСошлись на блоке {$block_data['block_id']}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			break;
		}

	} while (true);

	// чтобы брать блоки по порядку
	ksort($blocks);

	debug_print("blocks:".print_r_hex($blocks), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// запрещаем любые добавления данных из блоков/тр-ий
	//main_lock();

	// получим наши транзакции в 1 бинарнике, просто для удобства
	$transactions = '';
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT `data`
			FROM `".DB_PREFIX."transactions`
			WHERE `verified` = 1 AND
						 `used` = 0
			");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$transactions .= ParseData::encode_length_plus_data( $row['data'] );
	}

	if ($transactions) {
		// отмечаем, что эти тр-ии теперь нужно проверять по новой
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				UPDATE `".DB_PREFIX."transactions`
				SET  `verified` = 0
				WHERE `verified` = 1 AND
							 `used` = 0
				");

		// откатываем по фронту все свежие тр-ии
		$parsedata = new ParseData($transactions, $db);
		$parsedata->ParseDataRollbackFront();
		unset($parsedata);
	}

	// теперь откатим и transactions_testblock
	debug_print('rollback_transactions_testblock start' , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	rollback_transactions_testblock($db, true);

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			TRUNCATE TABLE `".DB_PREFIX."testblock`
			");

	// откатываем наши блоки до начала вилки
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT *
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` > {$block_id}
			ORDER BY `id` DESC
			");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$LOG_MARKER =  "откатываем наши блоки до начала вилки\nParseDataRollback start\nblock_id>{$block_id}";
		$parsedata = new ParseData($row['data'], $db);
		$parsedata->ParseDataRollback();
		unset($parsedata);
	}

	debug_print($blocks, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$prev_block = array();
	// проходимся по новым блокам
	foreach ( $blocks as $int_block_id => $tmp_file_name ) {

		debug_print("# # # проходимся по новым блокам\nblock_id={$int_block_id}\ntmp_file_name={$tmp_file_name}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// проверяем и заносим данные
		$binary_block = file_get_contents($tmp_file_name);

		debug_print('$binary_block = '.$binary_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$binary_block hex = '.bin2hex($binary_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$parsedata = new ParseData($binary_block, $db);
		// передаем инфу о предыдущем блоке, т.к. это новые блоки, то инфа о предыдущих блоках в block_chain будет всё еще старая, т.к. обновление block_chain идет ниже
		if (isset($prev_block[$int_block_id-1])) {
			$parsedata->prev_block['hash'] = $prev_block[$int_block_id-1]['hash'];
			$parsedata->prev_block['head_hash'] = $prev_block[$int_block_id-1]['head_hash'];
			$parsedata->prev_block['time'] = $prev_block[$int_block_id-1]['time'];
			$parsedata->prev_block['level'] = $prev_block[$int_block_id-1]['level'];
			$parsedata->prev_block['block_id'] = $prev_block[$int_block_id-1]['block_id'];
		}
		// если вернулась ошибка, значит переданный блок уже откатился
		// info_block и config.my_block_id обновляются только если ошибки не было
		$error = $parsedata->ParseDataFull();
		// для последующей обработки получим хэши и time
		if (!$error) $prev_block[$int_block_id] = $parsedata->GetBlockInfo();
		unset($parsedata);

		// если есть ошибка, то откатываем все предыдущие блоки из новой цепочки
		if ($error) {

			debug_print("[error]={$error}\nесть ошибка,  откатываем все предыдущие блоки из новой цепочки\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// баним на 1 час хост, который дал нам ложную цепочку;
			nodes_ban ($db, $user_id, $error."\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);

			// обязательно проходимся по блокам в обратном порядке
			krsort($blocks);
			foreach ( $blocks as $int2_block_id => $tmp_file_name ) {
				@$LOG_MARKER.="int2_block_id={$int2_block_id}";
				debug_print("int2_block_id={$int2_block_id}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				if ($int2_block_id>=$int_block_id)
					continue;
				$binary_block = file_get_contents($tmp_file_name);
				$parsedata = new ParseData($binary_block, $db);
				$parsedata->ParseDataRollback();
				unset($parsedata);
				debug_print("[{$int2_block_id}] ParseDataRollback ok\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			}

			// заносим наши данные из block_chain, которые были ранее
			debug_print("заносим наши данные из block_chain, которые были ранее", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT *
					FROM `".DB_PREFIX."block_chain`
					WHERE `id` > {$block_id}
					ORDER BY `id` ASC
			");
			while ( $row = $db->fetchArray( $res ) ) {

				debug_print('$block_id='.$block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				debug_print("[{$int_block_id}] ParseDataFull start\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$parsedata = new ParseData($row['data'], $db);
				$parsedata->ParseDataFull();
				unset($parsedata);
			}
			// т.к. в предыдущем запросе к block_chain могло не быть данных, т.к. $block_id больше чем наш самый большой id в block_chain
			// то значит info_block мог не обновится и остаться от занесения новых блоков, что приведет к пропуску блока в block_chain
			$last_my_block = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT *
					FROM `".DB_PREFIX."block_chain`
					ORDER BY `id` DESC
					LIMIT 1
					", 'fetch_array');
			ParseData::string_shift($last_my_block['data'], 1); // уберем 1-й байт - тип (блок/тр-я)
			$last_my_block_data = parse_block_header($last_my_block['data']);
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."info_block`
					SET  `hash` = 0x".bin2hex($last_my_block['hash']).",
							`head_hash` = 0x".bin2hex($last_my_block['head_hash']).",
							`block_id`= {$last_my_block_data['block_id']},
							`time`= {$last_my_block_data['time']},
							`level`= {$last_my_block_data['level']},
							`sent` = 0
					");
			debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."config`
					SET `my_block_id` = {$last_my_block_data['block_id']}
					");

			clear_tmp($blocks);
			return 'get_block error '.$error; // переходим к следующему блоку в queue_blocks
		}
	}

	debug_print("# # удаляем блоки из block_chain и заносим новые", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// если всё занеслось без ошибок, то удаляем блоки из block_chain и заносим новые
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			DELETE
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` > {$block_id}
			");
	debug_print($db->getAffectedRows(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print("blocks:".print_r_hex($blocks), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print($prev_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// для поиска бага
	$max_block_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`
			FROM `".DB_PREFIX."block_chain`
			ORDER BY `id` DESC
			LIMIT 1
			", 'fetch_one');
	debug_print('$max_block_id='.$max_block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// проходимся по новым блокам
	foreach ( $blocks as $block_id => $tmp_file_name ) {

		$block_hex = bin2hex(file_get_contents($tmp_file_name));

		// пишем в цепочку блоков
		$file = save_tmp_644 ('FBC', "{$block_id}\t{$prev_block[$block_id]['hash']}\t{$prev_block[$block_id]['head_hash']}\t{$block_hex}");
		debug_print("{$block_id} ==> LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."block_chain`", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."info_block`
				SET  `hash` = 0x{$prev_block[$block_id]['hash']},
						`head_hash` = 0x{$prev_block[$block_id]['head_hash']},
						`block_id`= {$prev_block[$block_id]['block_id']},
						`time`= {$prev_block[$block_id]['time']},
						`level`= {$prev_block[$block_id]['level']},
						`sent` = 0
				");
		debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// т.к. эти данные создали мы сами, то пишем их сразу в таблицу проверенных данных, которые будут отправлены другим нодам
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."block_chain`
				FIELDS TERMINATED BY '\t'
				(`id`, @hash, @head_hash, @data)
				SET `hash` = UNHEX(@hash),
					   `head_hash` = UNHEX(@head_hash),
					   `data` = UNHEX(@data)
				");
		debug_print($db->getAffectedRows(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		unlink($file);
		unlink($tmp_file_name);

		// для поиска бага
		$max_block_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `id`
			FROM `".DB_PREFIX."block_chain`
			ORDER BY `id` DESC
			LIMIT 1
			", 'fetch_one');
		debug_print('$max_block_id='.$max_block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	}

	debug_print("-------------------------HAPPY END ---------------------------", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

}

/*
 * $get_block_script_name, $add_node_host используется только при работе в защищенном режиме и только из blocks_collection.php
 * */
function get_old_blocks($user_id, $block_id, $host=false, $host_user_id, $get_block_script_name='', $add_node_host='')
{
	global $db,  $variables;

	debug_print("user_id={$user_id}\nblock_id={$block_id}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$result = get_blocks($block_id, $host, $host_user_id, 'rollback_blocks_2', $get_block_script_name, $add_node_host);
	if ($result) {
		debug_print($result, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		return $result;
	}
}

function ddos_protection ($ip)
{
	global $db;
	/*
	  * Защита от случайного ддоса
	 * */
	$my_table = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		SELECT `in_connections_ip_limit`,
					 `in_connections`
		FROM `".DB_PREFIX.MY_PREFIX."my_table`
		", 'fetch_array' );

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		INSERT IGNORE INTO `".DB_PREFIX.MY_PREFIX."my_ddos_protection` (
			`ip`,
			`req`
		) VALUES (
			INET_ATON('{$ip}'),
			1
		)
		ON DUPLICATE KEY UPDATE `req` = `req`+1
		");

	$ip_count = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		SELECT sum(`req`)
		FROM `".DB_PREFIX.MY_PREFIX."my_ddos_protection`
		WHERE  `ip` = INET_ATON('{$ip}')
		", 'fetch_one' );

	$total_count = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		SELECT count(`ip`)
		FROM `".DB_PREFIX.MY_PREFIX."my_ddos_protection`
		", 'fetch_one' );

	if ($ip_count > $my_table['in_connections_ip_limit'])
		die ('in_connections_ip_limit');

	if ($total_count > $my_table['in_connections'])
		die ('in_connections_ip_limit');

	/***/
}

function insert_tx($bin_data, $db)
{
	$hash = md5($bin_data);
	$data = bin2hex($bin_data);
	$file = save_tmp_644 ('FSQ', "{$hash}\t{$data}");
	debug_print("hash={$hash}\ndata={$data}" , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
			FIELDS TERMINATED BY '\t'
			(@hash, @data)
			SET  `hash` = UNHEX(@hash),
					`data` = UNHEX(@data)
			");
	unlink($file);

}


function bintohex($bin){
	/*list(, $hex) = unpack( "H*", $bin );
	return $hex;*/
	return bin2hex($bin);
}

function get_script_name()
{
	$n = explode('/', $_SERVER['SCRIPT_FILENAME']);
	return $n[sizeof($n)-1];
}

function upd_main_lock ($db)
{
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			UPDATE `".DB_PREFIX."main_lock`
			SET `lock_time` = '".time()."'
			LIMIT 1
			");
}

function upd_deamon_time ($db)
{
	$lock_script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT `script_name`
			FROM `".DB_PREFIX."main_lock`
			", 'fetch_one');
	if ($lock_script_name == 'my_lock')
		die ('my_lock');

	if (preg_match('/_tmp/', get_script_name())>0)
		$script_name = '_tx/'.get_script_name();
	else
		$script_name = get_script_name();

	debug_print('daemons start '.microtime(true), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			UPDATE `".DB_PREFIX."daemons`
			SET `time` = '".time()."',
					`memory` = ".memory_get_usage()."
			WHERE `script` = '{$script_name}'
			");
	debug_print('daemons end  '.microtime(true), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if ($db->getAffectedRows()==0) {

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			INSERT IGNORE INTO `".DB_PREFIX."daemons` (
					`time`,
					`memory`,
					`script`
					)
					VALUES (
						'".time()."',
						".memory_get_usage().",
						'{$script_name}'
			)");
	}
	//debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
}

function print_r_hex ($arr)
{
	if (is_array($arr)) {
		foreach($arr as $k=>$v) {
			if (is_array($v)) {
				$n1 = print_r_hex ($v);
				$arr[$k] = $n1;
			}
			else {
				if ($k=='sign' || $k=='header_hash' || $k=='signature' || $k=='mrkl_root' || $k=='data' || $k=='hash' || $k=='head_hash') {
					if (!is_array($v) &&  $k!==0 && !preg_match("/([a-z0-9]{8,})/i", $v)) {
						$v = bin2hex($v);
					}
					$arr[$k] = $v;
				}
			}
		}
		return print_r($arr, true);
	}
	else
		return "no array\n";
}



function debug_print($text, $file, $line, $function, $class, $method, $all_log=false)
{
	global $LOG_MARKER;
	global $db;
	global $my_lock;
	global $global_current_block_id;

	$new_text =  "\n### ".date('H:i:s').':'.microtime(true)." | ".$file.":".$line." | ".getmypid()." |  ".wordwrap(memory_get_usage(), 3, " ", true)." | ".get_script_name()." | {$LOG_MARKER} | ";
	//$new_text =  "\n### ".date('H:i:s')." | ".$file.":".$line." | ".getmypid()." | ";
	
	if ($function)
		$new_text.=$function."()\n";
	else if ($class)
		$new_text.=$class."->".$method."()\n";

	if (is_array($text))
		$new_text.=print_r_hex($text);
	else
		$new_text.=$text;

	$new_text.=  "\n";

	$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
	$log_fns = explode('|', $ini_array['main']['log_fns']);
	if ($ini_array['main']['log'] == 1 || (in_array($function, $log_fns) && $function) || ($global_current_block_id >= $ini_array['main']['log_block_id_begin'] && $global_current_block_id <= $ini_array['main']['log_block_id_end'] && $ini_array['main']['log_block_id_begin'] && $ini_array['main']['log_block_id_end']) || $all_log)
	{
		if ($my_lock)
			$file = ABSPATH . 'log/gen_main.log';
		else
			$file = ABSPATH . 'log/'. get_script_name().'.log';
		@file_put_contents($file, $new_text,  FILE_APPEND);
	}
}


function ob_save($text, $error=false)
{
	global $db;
	global $my_lock;
	global $global_current_block_id;

	$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
	if ($ini_array['main']['log'] == 1 || ($global_current_block_id >= $ini_array['main']['log_block_id_begin'] && $global_current_block_id <= $ini_array['main']['log_block_id_end'] && $ini_array['main']['log_block_id_begin'] && $ini_array['main']['log_block_id_end']))
	{
		if ($my_lock)
			$file = ABSPATH . 'log/gen_main.log';
		//else if ($testblock_lock_script_name==get_script_name())
		//	$file = ABSPATH . 'log/gen_testblock.log';
		else
			$file = ABSPATH . 'log/'. get_script_name().'.log';
		@file_put_contents($file, $text,  FILE_APPEND);
		if ($error) {
			$text.=$error;
			@file_put_contents(ABSPATH . 'log/error_'.get_script_name().'.log', $text,  FILE_APPEND);
		}
	}
}

function calc_profit_($currency_id, $amount, $user_id, $db, $last_update, $end_time, $type)
{
	$user_holidays = array();
	$pct_array = array();
	$max_promised_amounts = array();
	$repaid_amount = 0;
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT *
					FROM `".DB_PREFIX."pct`
					WHERE `currency_id` = {$currency_id}
					ORDER BY `time` ASC
					");
	while ($row0 = $db->fetchArray($res)) {
		$pct_array[$row0['time']]['miner'] = $row0['miner'];
		$pct_array[$row0['time']]['user'] = $row0['user'];
	}

	$cash_request_status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `status`
			FROM `".DB_PREFIX."cash_requests`
			WHERE `to_user_id` = {$user_id} AND
						 `del_block_id` = 0 AND
						 `for_repaid_del_block_id` = 0 AND
						 `status` = 'pending'
			LIMIT 1
			", 'fetch_one' );

	if ($type == 'wallet') {
		$points_status_array = array(0=>'user');
	}
	else if ($type == 'mining') { // обычная обещанная сумма
		$points_status_array = ParseData::getPointsStatus($user_id, $db);
		$user_holidays =  ParseData::getHolidays($user_id, $db);
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."max_promised_amounts`
					WHERE `currency_id` = {$currency_id}
					ORDER BY `time` ASC
					");
		while ($row = $db->fetchArray($res)) {
			$max_promised_amounts[$row['time']] = $row['amount'];
		}

		$repaid_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'repaid' AND
							 `currency_id` = {$currency_id} AND
							 `user_id` = {$user_id}
				", 'fetch_one');
	}
	else if ($type == 'repaid') { // погашенная обещанная сумма
		$points_status_array = ParseData::getPointsStatus($user_id, $db);
	}

	if ( ( ($type == 'mining' || $type == 'repaid') && !$cash_request_status) || $type == 'wallet' )
		return ParseData::calc_profit( $amount, $last_update, $end_time, $pct_array, $points_status_array, $user_holidays, $max_promised_amounts, $currency_id, $repaid_amount );
	else
		return 0;

}

function check_deamon_restart ($db)
{
	$restart = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			SELECT `restart`
			FROM `".DB_PREFIX."daemons`
			WHERE `script` = '".get_script_name()."'
			", 'fetch_one');

	if ($restart) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				UPDATE `".DB_PREFIX."daemons`
				SET `restart`=0
				WHERE `script` = '".get_script_name()."'
				");
	}
	return $restart;
}


function setlang($lang)
{
	$_SESSION['lang'] = $lang;
	setcookie("lang", $lang, time()+31536000);
}

// для админа. потом убрать.
function generate_password()
{
	$arr = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
	$pass = "";
	for($i = 0; $i < 32; $i++)
	{
		$index = rand(0, count($arr) - 1);
		$pass .= $arr[$index];
		usleep(rand(0, 10));
	}
	return $pass;
}

function encrypt_data ($data, $public_key, $db, &$key='')
{
	// генерим ключ
	$rand_testblock_hash = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT `head_hash`
					FROM `".DB_PREFIX."queue_testblock`
					ORDER BY RAND() LIMIT 1
					", 'fetch_array');
	$key = ParseData::dsha256(microtime().rand().$rand_testblock_hash.generate_token(128));
	debug_print('$key='.$key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('$public_key='.bin2hex($public_key), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// шифруем ключ публичным ключем получателя
	$rsa = new Crypt_RSA();
	$rsa->loadKey($public_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
	$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
	$encrypted_key = $rsa->encrypt($key);
	unset($rsa);

	debug_print('$binary_data='.($data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('$binary_data(hex)='.bin2hex($data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// шифруем сам блок/тр-ию
	$aes = new Crypt_AES();
	$aes->setKey($key);
	$encrypted_data = $aes->encrypt($data);
	unset($aes);

	return ParseData::encode_length_plus_data($encrypted_key).$encrypted_data;
}

function decrypt_data (&$binary_tx, $db, &$decrypted_key='')
{
	if (!$binary_tx)
		return '[error]!$binary_tx';
	// вначале пишется user_id, чтобы в режиме пула можно было понять
	$my_user_id = ParseData::binary_dec_string_shift( $binary_tx, 5 ) ;
	debug_print('$my_user_id='.$my_user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	// изымем зашифрванный ключ, а всё, что останется в $binary_tx - сами зашифрованные хэши тр-ий/блоков
	$encrypted_key = ParseData::string_shift ( $binary_tx, ParseData::decode_length($binary_tx) ) ;
	debug_print('$encrypted_key='.$encrypted_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if (!$encrypted_key)
		return '[error]!$encrypted_key';

	$collective = get_community_users($db);
	if ($collective)
		$my_prefix = $my_user_id.'_';
	else
		$my_prefix = '';

	debug_print('$my_prefix='.$my_prefix, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$private_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `private_key`
				FROM `".DB_PREFIX."{$my_prefix}my_node_keys`
				WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."{$my_prefix}my_node_keys`)
				", 'fetch_one' );
	//debug_print('$private_key='.$private_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if (!$private_key)
		return '[error]!$my_private_key';

	$rsa = new Crypt_RSA();
	$rsa->loadKey($private_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
	$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
	$decrypted_key =  $rsa->decrypt($encrypted_key);
	debug_print('$decrypted_key='.($decrypted_key), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if (!$decrypted_key)
		return '[error]!$decrypted_key';

	$aes = new Crypt_AES();
	$aes->setKey($decrypted_key);
	// теперь в $binary_tx будет обычная тр-ия
	$binary_tx = $aes->decrypt($binary_tx);
	debug_print('$binary_data='.($binary_tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('$binary_data(hex)='.bin2hex($binary_tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	return $binary_tx;
}

function rollback_transactions($db)
{
	$my_testblock['block_body'] = '';
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `data`, `hash`
			FROM `".DB_PREFIX."transactions`
			WHERE `verified` = 1 AND
						 `used` = 0
			");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$my_testblock['block_body'] .= ParseData::encode_length_plus_data( $row['data'] );
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."transactions`
						SET `verified` = 0
						WHERE `hash` = 0x".bin2hex($row['hash'])."
						");
	}

	// нужно откатить наши транзакции
	if ($my_testblock['block_body']) {
		$parsedata = new ParseData($my_testblock['block_body'], $db);
		$parsedata->ParseDataRollbackFront();
		unset($parsedata);
	}
}

function rollback_transactions_testblock($db, $truncate=false)
{
	// прежде чем удалять, нужно откатить
	// получим наши транзакции в 1 бинарнике, просто для удобства
	$my_testblock['block_body'] = '';
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."transactions_testblock`
			ORDER BY `id` ASC
			");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$my_testblock['block_body'] .= ParseData::encode_length_plus_data( $row['data'] );
		if ($truncate) {
			// чтобы тр-ия не потерлас, её нужно заново записать
			$data_hex = bin2hex($row['data']);
			$hash_hex = bin2hex($row['hash']);
			$file = save_tmp_644 ('FTT', "{$hash_hex}\t{$data_hex}");
			debug_print("hash={$hash_hex}\ndata={$data_hex}" , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						LOAD DATA LOCAL INFILE  '{$file}'
						REPLACE INTO TABLE `".DB_PREFIX."queue_tx`
						FIELDS TERMINATED BY '\t'
						(@hash, @data)
						SET `hash` = UNHEX(@hash),
							   `data` = UNHEX(@data)
						");
			unlink($file);
		}
	}

	// нужно откатить наши транзакции
	if ($my_testblock['block_body']) {
		$parsedata = new ParseData($my_testblock['block_body'], $db);
		$parsedata->ParseDataRollbackFront(true);
		unset($parsedata);
	}

	if ($truncate) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				TRUNCATE TABLE `".DB_PREFIX."transactions_testblock`
				");
	}

}

function get_from_log($key) {
	$config = file(ABSPATH . 'config.ini');
	for ($i=0; $i<sizeof($config); $i++) {
		$line = explode("=", trim($config[$i]));
		if ($line[0] == $key)
			return $line[1];
	}
}

function nodes_ban ($db, $user_id, $info)
{
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT IGNORE INTO `".DB_PREFIX."nodes_ban` (
						`user_id`,
						`ban_start`,
						`info`
					)
					VALUES (
						{$user_id},
						".time().",
						'{$info}'
				)
				ON DUPLICATE KEY UPDATE `ban_start` = ".time().", `info` = '{$info}'
				");
	/*$config_ini = parse_ini_file("config.ini", true);
	$exit = $config_ini['main']['nodes_ban_exit'];
	if ($exit)
		system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');*/
}

function clear_incompatible_tx_sql ($db, $where_type,  $user_id, &$wait_error) {

	if (!is_int($where_type))
		$where_type = ParseData::findType($where_type);

	if ($user_id>0)
		$add_sql = "AND `user_id` = {$user_id}";
	else
		$add_sql = '';

	$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
			            FROM (
				            SELECT `hash`
				            FROM `".DB_PREFIX."transactions`
				            WHERE `type` = {$where_type}
				                          $add_sql AND
				                         `verified`=1 AND
				                         `used` = 0
							UNION
							SELECT `hash`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE `type` = {$where_type}
										  $add_sql
						)  AS `x`
						", 'fetch_one');
	debug_print('$num='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if ($num) {
		$wait_error = 'wait_error';
		debug_print('[error]='.$wait_error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}
}


function clear_incompatible_tx_sql_set ($db, $types_arr,  $user_id, &$wait_error, $third_var='') {

	$where_type = '';
	foreach ($types_arr as $type)
		$where_type.= ParseData::findType($type).',';
	$where_type = substr($where_type, 0, -1);

	if ($user_id)
		$add_sql = "AND `user_id` = {$user_id}";
	else
		$add_sql = '';

	if ($third_var)
		$add_sql1 = "AND `third_var` = {$third_var}";
	else
		$add_sql1 = '';

	$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
			            FROM (
				            SELECT `hash`
				            FROM `".DB_PREFIX."transactions`
				            WHERE `type` IN ({$where_type})
				                          {$add_sql} {$add_sql1} AND
				                         `verified`=1 AND
				                         `used` = 0
							UNION
							SELECT `hash`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE `type` IN ({$where_type})
										   {$add_sql} {$add_sql1} AND
										 `user_id` = {$user_id}
						)  AS `x`
						", 'fetch_one');
	debug_print('$num='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if ($num) {
		$wait_error = 'wait_error';
		debug_print('[error]='.$wait_error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}
}

function rollback_incompatible_tx ($types) {

	global $db;

	$where_type = '';
	foreach ($types as $type)
		$where_type.= ParseData::findType($type).',';
	$where_type = substr($where_type, 0, -1);

	/*$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
				            FROM (
					            SELECT `data`
					            FROM `".DB_PREFIX."transactions`
					            WHERE `type` IN (".ParseData::findType('cash_request_out').", ".ParseData::findType('send_dc').", ".ParseData::findType('new_promised_amount').") AND
					                         `verified`=1 AND
					                         `used` = 0
								UNION
								SELECT `data`
								FROM `".DB_PREFIX."transactions_testblock`
								WHERE `type` IN (".ParseData::findType('cash_request_out').", ".ParseData::findType('send_dc').", ".ParseData::findType('new_promised_amount').")
							)  AS `x`
						");*/
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					            SELECT `data`
					            FROM `".DB_PREFIX."transactions`
					            WHERE `type` IN ({$where_type}) AND
					                         `verified`=1 AND
					                         `used` = 0
						");
	while ($row = $db->fetchArray($res)) {

		$tx_data = $row['data'];
		$md5 = md5($tx_data);

		// откатим фронтальные записи
		$parsedata = new ParseData(ParseData::encode_length_plus_data($tx_data), $db);
		$parsedata->ParseDataRollbackFront();
		unset($parsedata);

		// Удаляем уже записанные тр-ии.
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions`
			                WHERE `hash` = 0x{$md5}
							");

		/*
				 * создает проблемы для tesblock_is_ready
				 *
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions_testblock`
			                WHERE `hash` = 0x{$md5}
							");
		*/

		// создаем тр-ию, которую потом заново проверим
		$file = save_tmp_644 ('FTX', "{$md5}\t{$tx_data}");
		debug_print("{$md5}\t{$tx_data}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// используем REPLACE т.к. тр-ия уже может быть в transactions с verified=0
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						LOAD DATA LOCAL INFILE  '{$file}'
						REPLACE INTO TABLE `".DB_PREFIX."queue_tx`
						FIELDS TERMINATED BY '\t'
						(@hash, @data)
						SET `hash` = UNHEX(@hash),
							   `data` = UNHEX(@data)
						");

	}
}

/*
 +admin_currency_commission
  +cash_request_out
  +send_dc
  +new_promised_amount

+admin_ban_miners
    cash_request_in - не требуется, т.к. есть проверка у cash_request_out
    +cash_request_out
    +change_host
    +geolocation
    +geolocation_current
    +holidays_add
    +new_promised_amount
    +change_node_key
    +new_pct
    +tdc_dc
    +votes_geolocation
    +votes_miner
    +votes_node_new_miner
    +votes_pct
    +votes_promised_amount
	+abuses

+votes_geolocation - 1 за 1 geolocation
+votes_miner - 1 за 1 miner
+votes_node_new_miner - 1 за 1 new_miner
+votes_pct - 1 за 1 pct
+votes_promised_amount - 1 за 1 tdc_dc


+tdc_dc
  +del_promised_amount

+change_node_key
  +new_miner_update
  +new_pct
    new_reduction


new_pct
	change_node_key
	...

new_reduction
	change_node_key
	...

cash_request_rejected
	change_node_key
	...


+message_to_admin


+holidays_add ($this->tx_data['user_id'])
  +cash_request_in ($this->tx_data['user_id'])
  +cash_request_out ($this->tx_data['to_user_id'])
  +mining ($this->tx_data['user_id'])


+cash_request_out
  +holidays_add ($this->tx_data['to_user_id']) уже есть в holidays_add_front
  +send_dc ($this->tx_data['user_id'])

+cash_request_in
  +promised_amounts_del

*/
function clear_incompatible_tx($binary_tx, $db, $my_tx)
{

	$LOG_MARKER = md5($binary_tx);

	$fatal_error = '';
	$wait_error = '';
	$to_user_id = '';

	// получим тип тр-ии и юзера
	list($type, $user_id, $third_var) = get_tx_type_and_user_id($binary_tx);

	if ( !check_input_data ($type, 'int' ) )
		$fatal_error= 'error type';
	if ( !check_input_data ($user_id, 'int' ) )
		$fatal_error= 'error user_id';
	if ( !check_input_data ($third_var, 'int' ) )
		$fatal_error= 'error $third_var';

	if ($type == ParseData::findType('cash_request_out'))
		$to_user_id = $third_var;

	if ( $type == ParseData::findType('new_pct') || $type == ParseData::findType('new_reduction') || $type == ParseData::findType('new_max_promised_amounts')  || $type == ParseData::findType('new_max_other_currencies') ) {
		//  чтобы никому не слать эту тр-ю
		$for_self_use = 1;
		// $my_tx == true - это значит функция вызвана из pct_generator.php/reduction_generator.php
		// если же false, то она была спаршена query_tx или tesblock_generator и имела verified=0
		// а т.к. new_pct/new_reduction актуальны только 1 блок, то нужно её удалять
		if (!$my_tx) {
			$fatal_error = 'old new_pct/new_reduction/new_max_promised_amounts/new_max_other_currencies';
			$return = array($fatal_error, $wait_error, $for_self_use, $type, $user_id, $to_user_id);
			debug_print($return, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return $return;
		}
	}
	else
		$for_self_use = 0;


	// две тр-ии одного типа от одного юзера не должны попасть в один блок
	// исключение - перевод DC между юзерами
	if (!$fatal_error) {

		clear_incompatible_tx_sql ($db, $type, $user_id, $wait_error);

		// если новая тр-ия - это запрос на удаление банкноты, то нужно проверить
		// нет ли запросов на получение банкнот к данному юзеру
		// а также, нужно проверить, нет от данного юзера тр-ии cash_request_in
		if ($type == ParseData::findType('del_promised_amount')) {
			debug_print('если новая тр-ия - это запрос на удаление банкноты, то нужно проверить, нет ли запросов на получение банкнот к данному юзеру, а также, нужно проверить, нет от данного юзера тр-ии cash_request_in', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
			            FROM (
				            SELECT `user_id`
				            FROM `".DB_PREFIX."transactions`
				            WHERE (
				                             `third_var` = {$user_id} AND
					                         `verified`=1 AND
					                         `used` = 0
					                      )
				                          OR (
					                          `type` = ".ParseData::findType('cash_request_in')." AND
					                          `user_id` = {$user_id}
				                         )
							UNION
							SELECT `user_id`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE (
											 `third_var` = {$user_id}
										) OR (
					                         `type` = ".ParseData::findType('cash_request_in')." AND
					                         `user_id` = {$user_id}
										)
						)  AS `x`
						", 'fetch_one');
			debug_print('$num ='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($num)
				$fatal_error= "`third_var` = {$user_id}";
		}

		// если новая тр-ия - это запрос на получение банкнот, то нужно проверить
		// нет ли у получающего юзера запросов на удаление банкнот
		if ($type == ParseData::findType('cash_request_out')) {
			debug_print('если новая тр-ия - это запрос на получение банкнот, то нужно проверить, нет ли у получающего юзера запросов на удаление банкнот', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$tx_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
			            FROM (
				            SELECT `data`
				            FROM `".DB_PREFIX."transactions`
				            WHERE `type` = ".ParseData::findType('del_promised_amount')." AND
				                         `user_id` = {$to_user_id} AND
				                         `verified`=1 AND
				                         `used` = 0
							UNION
							SELECT `data`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE `type` = ".ParseData::findType('del_promised_amount')." AND
										 `user_id` = {$to_user_id}
						)  AS `x`
						", 'fetch_one');
			debug_print('$tx_data ='.$tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($tx_data) {

				// откатим фронтальные записи
				$parsedata = new ParseData(ParseData::encode_length_plus_data($tx_data), $db);
				$parsedata->ParseDataRollbackFront();
				unset($parsedata);

				// Удаляем именно уже записанную тр-ию. При этом новая (cash_request_out) тр-ия успешно обработается
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions`
			                WHERE `hash` = 0x".md5($tx_data)."
							");
				/*
				 * создает проблемы для tesblock_is_ready
				 *
				 * $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions_testblock`
			                WHERE `hash` = 0x".md5($tx_data)."
							");*/
			}
		}
		// если новая тр-ия - это запрос на получение банкнот, то нужно проверить
		// нет ли у отправителя запроса на отправку DC, т.к. после списания может не остаться средств
		if ($type == ParseData::findType('cash_request_out'))
			clear_incompatible_tx_sql ($db, 'send_dc', $user_id, $wait_error);
		// и наоборот
		if ($type == ParseData::findType('send_dc'))
			clear_incompatible_tx_sql ($db, 'cash_request_out', $user_id, $wait_error);

		// на всякий случай не даем попасть в один блок holidays и тр-им, где holidays используются
		if ($type == ParseData::findType('new_holidays'))
			clear_incompatible_tx_sql ($db, 'mining', $user_id, $wait_error);
		if ($type == ParseData::findType('mining'))
			clear_incompatible_tx_sql ($db, 'new_holidays', $user_id, $wait_error);

		if ($type == ParseData::findType('new_holidays'))
			clear_incompatible_tx_sql ($db, 'cash_request_in', $user_id, $wait_error);
		if ($type == ParseData::findType('cash_request_in'))
			clear_incompatible_tx_sql ($db, 'new_holidays', $user_id, $wait_error);

		if ($type == ParseData::findType('cash_request_out'))
			clear_incompatible_tx_sql ($db, 'new_holidays', $to_user_id, $wait_error);

		// не должно попадать в один блок смена нодовского ключа и тр-ии которые этим ключем подписываются
		if ($type == ParseData::findType('change_node_key'))
			clear_incompatible_tx_sql ($db, 'new_miner_update', $user_id, $wait_error);
		if ($type == ParseData::findType('change_node_key'))
			clear_incompatible_tx_sql ($db, 'new_pct', $user_id, $wait_error);
		if ($type == ParseData::findType('new_miner_update'))
			clear_incompatible_tx_sql ($db, 'change_node_key', $user_id, $wait_error);
		if ($type == ParseData::findType('new_pct'))
			clear_incompatible_tx_sql ($db, 'change_node_key', $user_id, $wait_error);
		if ($type == ParseData::findType('change_node_key'))
			clear_incompatible_tx_sql ($db, 'new_reduction', $user_id, $wait_error);

		// нельзя удалить банкноту и затем создать запрос на её майнинг
		if ($type == ParseData::findType('mining'))
			clear_incompatible_tx_sql ($db, 'del_promised_amount', $user_id, $wait_error);
        if ($type == ParseData::findType('del_promised_amount'))
            clear_incompatible_tx_sql ($db, 'mining', $user_id, $wait_error);
        // в 1 блоке только 1 майнинг от юзера
        if ($type == ParseData::findType('mining'))
            clear_incompatible_tx_sql ($db, 'mining', $user_id, $wait_error);
        if ($type == ParseData::findType('mining'))
            clear_incompatible_tx_sql ($db, 'admin_ban_miners', 0, $wait_error);


        if ($type == ParseData::findType('cash_request_out'))
            clear_incompatible_tx_sql ($db, 'admin_ban_miners', 0, $wait_error);
		if ($type == ParseData::findType('new_promised_amount'))
			clear_incompatible_tx_sql ($db, 'admin_ban_miners', 0, $wait_error);

		if ($type == ParseData::findType('admin_ban_miners'))
			rollback_incompatible_tx( array('cash_request_out', 'change_host', 'new_promised_amount', 'change_node_key', 'new_pct', 'mining', 'votes_miner', 'votes_node_new_miner',  'votes_promised_amount', 'abuses', 'new_promised_amount', 'votes_complex'), 0, $wait_error);

		if ($type == ParseData::findType('votes_miner'))
			clear_incompatible_tx_sql_set($db, array('admin_ban_miners'), 0, $wait_error);

		if ($type == ParseData::findType('votes_complex'))
			clear_incompatible_tx_sql_set($db, array('admin_ban_miners'), 0, $wait_error);

		if ($type == ParseData::findType('abuses')) // admin_ban_miners преоритетнее, abuses надо вытеснять
			clear_incompatible_tx_sql_set($db, array('admin_ban_miners'), 0, $wait_error); // дополнить

		if ($type == ParseData::findType('votes_node_new_miner'))
			clear_incompatible_tx_sql_set( $db, array('admin_ban_miners'), 0, $wait_error);

		if ($type == ParseData::findType('votes_promised_amount'))
			clear_incompatible_tx_sql_set( $db, array('admin_ban_miners'), 0, $wait_error);

		// нельзя голосовать за обещанную сумму юзера $promised_amount_user_id, если он меняет свое местоположение, т.к. сменится статус
		if ($type == ParseData::findType('votes_promised_amount')) {
			$promised_amount_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE  `id` = {$third_var}
					LIMIT 1
					", 'fetch_one');
			if ($promised_amount_user_id)
				clear_incompatible_tx_sql_set( $db, array('change_geolocation'), $promised_amount_user_id, $wait_error);
		}

		// нельзя менять местоположение, если кто-то отдал голос за мою обещанную сумму
		if ($type == ParseData::findType('change_geolocation')) {
			$promised_amount_ids = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE  `user_id` = {$user_id}
					", 'array');
			$promised_amount_ids = implode(',', $promised_amount_ids);
			if ($promised_amount_ids) {
				$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
						    FROM (
						        SELECT `user_id`
						        FROM `".DB_PREFIX."transactions`
						        WHERE  (
						                        `type` = ".ParseData::findType('votes_promised_amount')." AND `third_var` IN ($promised_amount_ids)
						                      ) AND
						                     `verified`=1 AND
						                     `used` = 0
								UNION
								SELECT `user_id`
								FROM `".DB_PREFIX."transactions_testblock`
								WHERE  (
						                        `type` = ".ParseData::findType('votes_promised_amount')." AND `third_var` IN ($promised_amount_ids)
						                      )
							)  AS `x`
						", 'fetch_one');
				if ($num)
					$wait_error = 'votes_promised_amount change_geolocation';
			}
		}


		// нельзя удалять promised_amount и голосовать за него
		if ($type == ParseData::findType('del_promised_amount'))
			clear_incompatible_tx_sql_set( $db, array('votes_promised_amount'), 0, $wait_error, $third_var);
		if ($type == ParseData::findType('votes_promised_amount'))
			clear_incompatible_tx_sql_set( $db, array('del_promised_amount'), 0, $wait_error, $third_var);

		if ($type == ParseData::findType('new_max_promised_amounts'))
			clear_incompatible_tx_sql_set( $db, array('new_max_promised_amounts'), 0, $wait_error, $third_var);
		if ($type == ParseData::findType('new_max_other_currencies'))
			clear_incompatible_tx_sql_set( $db, array('new_max_other_currencies'), 0, $wait_error, $third_var);
		if ($type == ParseData::findType('new_pct'))
			clear_incompatible_tx_sql_set( $db, array('new_pct'), 0, $wait_error, $third_var);
		if ($type == ParseData::findType('new_reductions'))
			clear_incompatible_tx_sql_set( $db, array('new_reductions'), 0, $wait_error, $third_var);


		// в один блок должен попасть только один голос за один объект голосования. $third_var - объект голосования
		if (in_array($type, array(ParseData::findType('votes_promised_amount'), ParseData::findType('votes_miner'), ParseData::findType('votes_node_new_miner'), ParseData::findType('votes_complex')) )) {
			$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
				            FROM (
					            SELECT `user_id`
					            FROM `".DB_PREFIX."transactions`
					            WHERE  `type` IN (".ParseData::findType('votes_promised_amount').", ".ParseData::findType('votes_miner').", ".ParseData::findType('votes_node_new_miner').", ".ParseData::findType('votes_complex').") AND
					                          `third_var` = {$third_var} AND
					                          `verified`=1 AND
					                          `used` = 0
								UNION
								SELECT `user_id`
								FROM `".DB_PREFIX."transactions_testblock`
								WHERE `type` IN (".ParseData::findType('votes_promised_amount').", ".ParseData::findType('votes_miner').", ".ParseData::findType('votes_node_new_miner').", ".ParseData::findType('votes_complex').") AND
					                          `third_var` = {$third_var}
							)  AS `x`
						", 'fetch_one');
			debug_print('$num ='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($num)
				$wait_error = 'only 1 vote';
		}

		// если новая тр-ия - это запрос, в котором юзер отдает банкноты (cash_request_in)
		// то нужно проверить, не хочет ли юзер удалить одну из передаваемых банкнот
		if ($type == ParseData::findType('cash_request_in')) {
			debug_print('если новая тр-ия - это запрос, в котором юзер отдает банкноты (cash_request_in), то нужно проверить, не хочет ли юзер удалить одну из передаваемых банкнот', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$tx_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
			            FROM (
				            SELECT `data`
				            FROM `".DB_PREFIX."transactions`
				            WHERE `type` = ".ParseData::findType('del_promised_amount')." AND
				                         `user_id` = {$user_id} AND
				                         `verified`=1 AND
				                         `used` = 0
							UNION
							SELECT `data`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE `type` = ".ParseData::findType('del_promised_amount')." AND
										 `user_id` = {$user_id}
						)  AS `x`
						", 'fetch_one');
			debug_print('$tx_data ='.$tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($tx_data) {

				// откатим фронтальные записи
				$parsedata = new ParseData(ParseData::encode_length_plus_data($tx_data), $db);
				$parsedata->ParseDataRollbackFront();
				unset($parsedata);

				// Удаляем именно уже записанную тр-ию. При этом новая (cash_request_in) тр-ия успешно обработается
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions`
			                WHERE `hash` = 0x".md5($tx_data)."
							");

				/*
				 * создает проблемы для tesblock_is_ready
				 *
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE
			                FROM `".DB_PREFIX."transactions_testblock`
			                WHERE `hash` = 0x".md5($tx_data)."
							");
				*/
			}
		}

		if ($type == ParseData::findType('change_primary_key')) {
			debug_print('если новая тр-я - это смена праймари ключа, то не должно быть никаких других тр-ий от этого юзера', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
				            FROM (
					            SELECT `user_id`
					            FROM `".DB_PREFIX."transactions`
					            WHERE  `user_id` = {$user_id} AND
					                         `verified`=1 AND
					                         `used` = 0
								UNION
								SELECT `user_id`
								FROM `".DB_PREFIX."transactions_testblock`
								WHERE `user_id` = {$user_id}
							)  AS `x`
						", 'fetch_one');
			debug_print('$num ='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($num)
				$wait_error = 'there are other tr-s';
		}

		// любая тр-я от юзера не должна проходить, если уже есть тр-я со сменой праймари ключа или new_pct или new_reduction
		$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(*)
				            FROM (
					            SELECT `user_id`
					            FROM `".DB_PREFIX."transactions`
					            WHERE  (
						                            (`type` = ".ParseData::findType('change_primary_key')." AND `user_id` = {$user_id})
						                            OR
						                            (type IN (".ParseData::findType('new_pct').", ".ParseData::findType('new_reduction').") )
					                          ) AND
					                         `verified`=1 AND
					                         `used` = 0
								UNION
								SELECT `user_id`
								FROM `".DB_PREFIX."transactions_testblock`
								WHERE  (
						                            (`type` = ".ParseData::findType('change_primary_key')." AND `user_id` = {$user_id})
						                            OR
						                            (type IN (".ParseData::findType('new_pct').", ".ParseData::findType('new_reduction').") )
					                          )
							)  AS `x`
						", 'fetch_one');
		debug_print('$num ='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if ($num)
			$wait_error = 'have change_primary_key tx';

		// если пришло new_pct, то нужно откатить следующие тр-ии
		if ($type == ParseData::findType('new_pct'))
			rollback_incompatible_tx( array('new_reduction', 'change_node_key', 'votes_promised_amount', 'send_dc', 'cash_request_in', 'mining') );

		// если пришло new_reduction, то нужно откатить следующие тр-ии
		if ($type == ParseData::findType('new_reduction'))
			rollback_incompatible_tx( array('new_pct', 'change_node_key', 'votes_promised_amount', 'send_dc', 'cash_request_in', 'mining') );

		// временно запрещаем 2-е тр-ии любого типа от одного юзера, а то затрахался уже.
		$num = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(*)
				    FROM (
							SELECT `user_id`
							FROM `".DB_PREFIX."transactions`
							WHERE  `user_id` = {$user_id} AND
				                      `verified`=1 AND
				                      `used` = 0
							UNION
							SELECT `user_id`
							FROM `".DB_PREFIX."transactions_testblock`
							WHERE `user_id` = {$user_id}
					)  AS `x`
				", 'fetch_one');
		debug_print('$num ='.$num, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if ($num)
			$wait_error = 'only 1 tx';
	}

	if ($fatal_error) debug_print('fatal[error] =='.$fatal_error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	if ($wait_error) debug_print('wait[error] =='.$wait_error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$return = array($fatal_error, $wait_error, $for_self_use, $type, $user_id, $third_var);
	debug_print($return, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	return $return;

}




function encrypt_and_sign($pass, $encrypt_private_key, $data_for_sign) {

	debug_print("pass={$pass}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print("encrypt_private_key={$encrypt_private_key}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print("data_for_sign={$data_for_sign}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	if ($pass!=='') {

		debug_print("pass exists", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$aes = new Crypt_AES( CRYPT_AES_MODE_ECB );
		$aes = new Crypt_AES( CRYPT_AES_MODE_ECB );
		$aes->setKey(md5($pass));
		$user_private_key = $aes->decrypt($encrypt_private_key);
		unset($aes);
	}
	else
		$user_private_key = $encrypt_private_key;

	debug_print("user_private_key=".$user_private_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$rsa = new Crypt_RSA();
	$rsa->loadKey($user_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	unset($rsa);
	debug_print("signature=".$signature, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	return $signature;
}

function encrypt_comment ($user_id, $db, $comment_text)
{
	// шифруем текст ключем порлучателя
	$public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key_0`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$user_id}
				LIMIT 1
				", 'fetch_one' );
	$rsa = new Crypt_RSA();
	$rsa->loadKey($public_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
	$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
	$enc =  $rsa->encrypt($comment_text);
	return bin2hex($enc);
}


function tx_parser ($new_tx_data, $my_tx=false) {

	global $db;

	$error = '';

	$binary_tx = $new_tx_data['data'];

	debug_print('$new_tx_data='.print_r_hex($new_tx_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// проверим, нет ли несовместимых тр-ий
	// 	$wait_error  -	 значит просто откладываем обработку тр-ии на после того, как сформируются блок
	// $fatal_error - удаляем тр-ию, т.к. она некорректная
	list($fatal_error, $wait_error, $for_self_use, $type, $user_id, $third_var) = clear_incompatible_tx($binary_tx, $db, $my_tx);

	if (!$fatal_error && !$wait_error) {
		$parsedata = new ParseData($binary_tx, $db);
		$error = $parsedata->ParseData_gate();
		unset($parsedata);
	}

	if ($error || $fatal_error)
		delete_queue_tx($new_tx_data);// удалим тр-ию из очереди

	if (!$error) $error = $fatal_error;
	if (!$error) $error = $wait_error;

	if ($error) {

		/* не актуально
		 * if (substr_count($error, '[limit_requests]')>0) {
			debug_print('----------------[error]'.$error.'-------------------', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$parsedata = new ParseData(ParseData::encode_length_plus_data($binary_tx), $db);
			$parsedata->ParseDataRollbackFront();
			unset($parsedata);
		}
		else {*/
			debug_print('error wo rollback----------------[error]'.$error.'-------------------', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// пишем в отдельный лог невалидных тр-ий
			$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
			if ($ini_array['main']['bad_tx_log']==1) {
				$file = ABSPATH . 'log/bad_tx.log';
				$text = "time: ".date('d-m-Y H:i:s')."\n";
				$text.= "script: ".get_script_name()."\n";
				$text.= "error: {$error}\n";
				$text.= "md5_hash: ".md5($binary_tx)."\n";
				$text.= "data: {$binary_tx}\n";
				@file_put_contents($file, $text,  FILE_APPEND);
			}
		/*}*/
		//delete_queue_tx();
		//main_unlock();
		//ob_save();
		//sleep(1);
		//return 'continue';
	}
	else {

		list(, $data_hex ) = unpack( "H*", $binary_tx);
		list(, $hash_hex ) = unpack( "H*", $new_tx_data['hash']);

		// счтечик, чтобы не было зацикливания
		$counter = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `counter`
				FROM `".DB_PREFIX."transactions`
				WHERE `hash` = 0x{$hash_hex}
				", 'fetch_one');
		$counter = intval($counter);
		$counter++;

		$data = "{$hash_hex}\t{$data_hex}\t{$for_self_use}\t{$type}\t{$user_id}\t{$third_var}\t{$counter}";
		$file = save_tmp_644 ('FTX', $data);

		debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// используем REPLACE т.к. тр-ия уже может быть в transactions с verified=0
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				LOAD DATA LOCAL INFILE  '{$file}'
				REPLACE INTO TABLE `".DB_PREFIX."transactions`
				FIELDS TERMINATED BY '\t'
				(@hash, @data, `for_self_use`, `type`, `user_id`, `third_var`, `counter`)
				SET `hash` = UNHEX(@hash),
					   `data` = UNHEX(@data)
				");
		unlink($file);

		// удалим тр-ию из очереди
		delete_queue_tx($new_tx_data);

	}
}

function all_tx_parser () {

	global $db;
	// берем тр-ии
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
            FROM (
	            SELECT `data`,
	                         `hash`
	            FROM `".DB_PREFIX."queue_tx`
				UNION
				SELECT `data`,
							 `hash`
				FROM `".DB_PREFIX."transactions`
				WHERE `verified` = 0 AND
							 `used` = 0
			)  AS `x`
			");
	while ( $new_tx_data = $db->fetchArray( $res ) ) {
		tx_parser ($new_tx_data);
	}
}

function write_php_ini($array, $file)
{
	$res = array();
	foreach($array as $key => $val)	{
		if(is_array($val)) {
			$res[] = "[$key]";
			foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
		}
		else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
	}
	$f =  implode("\r\n", $res);
	//print $f;
	file_put_contents($file, $f);
}

function get_my_notice_data()
{
	global $db, $lng;

	if (empty($_SESSION['restricted'])) {

		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
				SELECT `user_id`,
							 `miner_id`,
							 `status`
				FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
				', 'fetch_array' );

		if (!$data['user_id']) {
			$tpl['account_status'] = 'searching';
		} else if ($data['status'] == 'bad_key') {
			$tpl['account_status'] = 'bad_key';
		} else if ($data['miner_id']) {
			$tpl['account_status'] = 'miner';
		} else if ($data['user_id']) {
			$tpl['account_status'] = 'user';
		}
	}
	else {
		// user_id уже есть, т.к. мы смогли зайти в урезанном режиме по паблик-кею
		// проверим, может есть что-то в miners_data
		$status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$_SESSION['user_id']}
				LIMIT 1
				", 'fetch_one');
		if ($status)
			$tpl['account_status'] = $status;
		else
			$tpl['account_status'] = 'user';

	}
	$tpl['account_status'] = $lng['status_'.$tpl['account_status']];

	// получим время из последнего блока
	$last_block_bin = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `data`
			FROM `".DB_PREFIX."block_chain`
			ORDER BY `id` DESC
			LIMIT 1
			", 'fetch_one');
	ParseData::string_shift( $last_block_bin, 1 ); // уберем тип
	$block_id = ParseData::binary_dec_string_shift( $last_block_bin, 4 );
	$block_time = ParseData::binary_dec_string_shift( $last_block_bin, 4 );
	$tpl['time_last_block'] = date('d-m-Y H:i:s', $block_time);
	$tpl['cur_block_id'] = $block_id;

	$tpl['connections'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(*)
			FROM `".DB_PREFIX."nodes_connection`
			", 'fetch_one');

	if (time() - $block_time > 600)
		$tpl['main_status'] = "<p style='color:#ff0000'>{$lng['downloading_blocks']}</p>";
	else
		$tpl['main_status'] = "<p style='color:#008800'>{$lng['downloading_complete']}</p>";

	return $tpl;
}

function hash_table_data($db, $table, $where='', $order_by='')
{
	$columns = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT GROUP_CONCAT( column_name SEPARATOR ',' )
			FROM information_schema.columns
			WHERE table_schema = '".DB_NAME."'
			AND table_name = '".DB_PREFIX."{$table}'
			", 'fetch_one');
	$columns = str_replace(',notification', '', $columns);
	$columns = str_replace('notification,', '', $columns);
	$columns = str_replace(',cron_checked_time', '', $columns);
	$columns = str_replace('cron_checked_time,', '', $columns);
	if ($columns) {
		if ($order_by)
			$order_by = " ORDER BY {$order_by}";
		$columns = '`'.str_replace(',', '`,`', $columns).'`';
		return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT MD5(GROUP_CONCAT( CONCAT_WS( '#', {$columns})  {$order_by} )) FROM `".DB_PREFIX."{$table}` {$where}
			", 'fetch_one');
	}
	else
		return '';
}


function find_user_pct($max_user_pct_y)
{
	$PctArray = ParseData::getPctArray();
	//debug_print( '$PctArray='.print_r_hex($PctArray), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$i=0;
	foreach ($PctArray as $pct_y =>$pct_ssc) {
		if ($pct_y>=$max_user_pct_y) {
			debug_print( '$pct_y<$max_user_pct_y = '.$pct_y.' < '.$max_user_pct_y, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($i>0)
				return ($i-1);
			else
				return 0;
		}
		$i++;
	}
}

function del_user_pct($pct_arr, $user_max_key)
{
	$new = array();
	foreach ($pct_arr as $key =>$votes) {
		if ($key>$user_max_key) {
			break;
		}
		else {
			$new[$key] = $votes;
		}
	}
	return $new;
}


?>