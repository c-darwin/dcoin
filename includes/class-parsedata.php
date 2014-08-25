<?php

if (!defined('DC'))
	die('!DC');

require_once( ABSPATH . 'includes/old.php' );

// на сколько может бежать время в тр-ии
define('MAX_TX_FORW', 0);
// тр-ия может блуждать по сети сутки и потом попасть в блок
define('MAX_TX_BACK', 3600*24);

define( 'CRON_CHECKED_TIME_SEC', 3600*24*3 );
define( 'NEW_USER_TIME_SEC', 20 );
define( 'NEW_MINER_TIME_SEC', 20 );

define( 'USD_CURRENCY_ID', 71 );

// как часто обновляем нод-ключ по крону
define( 'NODE_KEY_UPD_TIME', 3600*24*7 );

// на какое время баним нода, давшего нам плохие данные
define( 'NODE_BAN_TIME', 3600 );

// кол-во удовлетворенных запросов на наличные за последние X часов
define( 'AUTO_REDUCTION_CASH_PERIOD', 3600*48 );

// на сколько % автоматически урезаем денежную массу
define( 'AUTO_REDUCTION_PCT', 10 );

$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
// для локальных тестов
if (isset($ini_array['local']['local'])) {
	define( 'AUTO_REDUCTION_PROMISED_AMOUNT_MIN', 1 );
	//define( 'AUTO_REDUCTION_CASH_MIN', 1 );
	define( 'AUTO_REDUCTION_PROMISED_AMOUNT_PCT', 2 ); // X*100%
	//define( 'AUTO_REDUCTION_CASH_PCT', 0.5 );
	define( 'AUTO_REDUCTION_PERIOD', 120 );
	define( 'limit_actualization', 1 );
	define( 'limit_actualization_period', 300 );
}
else {
	//  есть ли хотябы X юзеров, у которых на кошелках есть от 0.01 данной валюты
	define( 'AUTO_REDUCTION_PROMISED_AMOUNT_MIN', 10 );

	// урезание возможно только если запросов наличных за 48 часа было более X
	//define( 'AUTO_REDUCTION_CASH_MIN', 1000 );

	// сколько должно быть процентов PROMISED_AMOUNT от кол-ва DC на кошельках, чтобы запустилось урезание
	define( 'AUTO_REDUCTION_PROMISED_AMOUNT_PCT', 1 ); // X*100%

	// если кол-во удовлетворенных запросов менее чем X*100% от общего кол-вав
	//define( 'AUTO_REDUCTION_CASH_PCT', 0.3 );

	// через сколько можно делать следующее урезание.
	// важно учитывать то, что не должно быть роллбеков дальше чем на 1 урезание
	// т.к. при урезании используется backup в этой же табле вместо отдельной таблы log_
	define( 'AUTO_REDUCTION_PERIOD', 3600*24*2 );

	define( 'limit_actualization', 1 );
	define( 'limit_actualization_period', 3600*24*14 );
}


define( 'limit_new_cf_project', 1 );
define( 'limit_new_cf_project_period', 3600*24*7 );
define( 'limit_cf_project_data', 10 );
define( 'limit_cf_project_data_period', 3600*24 );
define( 'limit_cf_send_dc', 10 );
define( 'limit_cf_send_dc_period', 3600*24 );
define( 'limit_cf_comments', 10 );
define( 'limit_cf_comments_period', 3600*24 );

// сколько можно делать комментов за сутки за 1 проект
define( 'limit_time_comments_cf_project', 3600*24 );

define( 'limit_user_avatar', 5 );
define( 'limit_user_avatar_period', 3600*24 );

$reduction_dc = array(0,10,25,50,90);


class ParseData extends OldParseData {

	protected static $_instance;

	// для тестов
	public $tx_array;

	public $pct;

	public $max_promised_amounts;

	public $current_version;

	/**
	 * Массив заголовка блока
	 * @var array
	 */
	public $block_data;
	
	/**
	 * Данные одной транзакции
	 * @var array
	 */
	public $transaction_array;

	/**
	 * Сырой блок
	 * @var string
	 */
	public $binary_data;
	
	/**
	 * Блок в виде hex для записи в БД
	 * @var string
	 */
	public $block_hex;

	public $my_block_id;
	public $my_user_id;
	public $my_user_ids;
	public $my_prefix;

	public $variables;

	public $global_variables;

	public $tx_hash;

	public $WalletsBufferAmount;
	public $amount_and_commission;
	/**
	 * Хэш блока в hex
	 * @var string
	 */
	public $block_hash_hex;

	public $block_info;
	public $prev_block;
	public $mrkl_root;

	/**
	 * Тип. 0 - блок, >1 - транзакции
	 * @var int
	 */
	public $type;
	
	/**
	 * Mysql
	 * @var int
	 */
	public $db;
	
	/**
	 * Массив транзакции с именными ключами
	 * @var array
	 */
	public $tx_data;

	public $transaction_binary_data;

	static $MainArray = array();

	static $pctb64 = '';

	//static $AllMaxPromisedAmount = array();

	public $node_commission;

	static function getAllMaxPromisedAmount()
	{
		$all = array();
		for ($i=1; $i<1000000000; $i=$i*10) {
			if ($i==10)
				continue;
			if ($i<100)
				$i_end = 100;
			else
				$i_end = 90;
			for ($j=0; $j<$i_end; $j++) {
				if ($i<100)
					$all[] = $i + $j;
				else
					$all[] = $i + $j*$i/10;
				//print $i.' '.$j.' '.$all."\n";
			}
		}
		//self::AllMaxPromisedAmount = $all
		return $all;
	}

	// массив, в котором будет искаться максимальное кол-во голосов, должен быть стандартизирован
	// входные данные уже были ранее проверены
	static function makeMaxPromisedAmount($amounts)
	{
		$rez_arr = array();
		$all_amounts = self::getAllMaxPromisedAmount();
		foreach ($amounts as $amount=>$votes) {
			$key = array_search($amount, $all_amounts);
			$rez_arr[$key] = $votes;
		}
		return $rez_arr;
	}

	static function fillPct()
	{
		self::$pctb64 = 'AAAAAAE9AAJ6AAO2AATyAAYuAAdpAAikAAnfAAsZAAxTAA2NAA7HABAAABE5ABJxABOpABThABYZ
ABdQABiHABm+ABr1ABwrAB1gAB6WAB/LACEAACI1ACNpACSdACXRACcEACg3AClqACqdACvPAC0B
AC4yAC9kADCVADHGADL2ADQmADVWADaGADe1ADjkADoTADtBADxvAD2dAD7LAD/4AEElAEJSAEN+
AESqAEXWAEcCAEgtAElYAEqDAEutAEzXAE4BAE8rAFBUAFF9AFKmAFPOAFT3AFYfAFdGAFhuAFmV
AFq8AFviAF0IAF4uAF9UAGB6AGGfAGLEAGPoAGUNAGYxAGdVAGh4AGmcAGq/AGviAG0EAG4mAG9I
AHBqAHGLAHKtAHPOAHTuAHYPAHcvAHhPAHluAHqOAHutAHzMAH3qAH8IAIAnAIFEAIJiAIN/AISc
AIW5AIbWAIfyAIkOAIoqAItFAIxgAI17AI6WAI+xAJDLAJHlAJL+AJQYAJUxAJZKAJdjAJh7AJmU
AJqsAJvDAJzbAJ3yAJ8JAKAgAKE3AKJNAKNjAKR5AKWOAKajAKe5AKjNAKniAKr2AKwKAK0eAK4y
AK9FALBYALFrALJ+ALOQALSjALW0ALbGALfYALjpALn6ALsLALwbAL0sAL48AL9LAMBbAMFqAMJ6
AMOIAMSXAMWmAMa0AMfCAMjQAMndAMrqAMv3AM0EAM4RAM8dANApANE1ANJBANNNANRYANVjANZu
ANd4ANiDANmNANqXANugANyqAN2zAN68AN/FAODNAOHWAOwdAPZPAQBsAQpzARRmAR5FASgQATHH
ATtrAUT7AU55AVfkAWE+AWqFAXO7AXzfAYXyAY70AZfmAaDHAamYAbJZAbsKAcOsAcw+AdTBAd02
AeWcAe3zAfY8Af53AgakAg7EAhbVAh7aAibRAi67AjaYAj5pAkYtAk3lAlWQAl0wAmTDAmxLAnPH
Ans3AoKcAon2ApFFApiJAp/CAqbwAq4UArUtArw8AsNAAso7AtErAtgSAt7vAuXCAuyLAvNLAvoC
AwCwAwdUAw3vAxSBAxsLAyGLAygDAy5yAzTZAzs3A0GNA0fbA04hA1ReA1qUA3kqA5cDA7QoA9Ci
A+x4BAeyBCJVBDxpBFX0BG76BIeBBJ+PBLcnBM5OBOUJBPtaBRFHBSbSBTv+BVDQBWVJBXltBY0/
BaDBBbP1BcbeBdl/BevZBf3uBg/BBiFSBjKlBkO7BlSWBmU2BnWeBoXPBpXLBqWSBrUnBtO9BvGW
Bw68Bys2B0cMB2JFB3zpB5b9B7CHB8mNB+IVB/oiCBG6CCjiCD+cCFXuCGvaCIFlCJaSCKtjCL/d
CNQBCOfSCPtUCQ6JCSFyCTQSCUZsCViBCWpUCXvmCY05CZ5PCa8pCb/KCdAyCeBjCfBeCgAmCg+7
Ch8eCi5RCj1UCkwqClrTCmlPCnehCoXJCpPICqGfCq9PCrzZCso9Ctd8CuSYCvGQCv5mCwsbCxeu
CyQhCzB0CzyoC0i+C1S2C2CQC2xOC3fvC4N1C47gC5ow';
	}

	static function pct_(&$data) {
		$pct='0.000000'.str_pad(hexdec(substr($data, 0, 6)), 7, "0", STR_PAD_LEFT);
		$data = substr($data, 6);
		return $pct;
	}

	static function getPctArray() {

		self::fillPct();
		$data = bin2hex(base64_decode(self::$pctb64));
		for ($i=0; $i<=20; $i=$i+0.1)
			$arr["$i"]=self::pct_($data);
		for ($i=20; $i<100; $i=$i+1)
			$arr[$i]=self::pct_($data);
		for ($i=100; $i<300; $i=$i+5)
			$arr[$i]=self::pct_($data);
		for ($i=300; $i<=1000; $i=$i+10)
			$arr[$i]=self::pct_($data);
		//debug_print( $arr, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		return $arr;
	}

	// массив, в котором будет искаться максимальное кол-во голосов, должен быть стандартизирован
	// входные данные уже были ранее проверены
	static function makePctArray($pct_array)
	{
		$all_pct = self::getPctArray();
		$i=0;
		$rez_arr = array();
		foreach($all_pct as $pct_year => $pct_sec) {
			if (isset($pct_array[$pct_sec])) {
				$rez_arr[$i] = $pct_array[$pct_sec];
			}
			$i++;
		}
		return $rez_arr;
	}

	static function getPctValue($key)
	{
		$all_pct = self::getPctArray();
		$i=0;
		$rez_arr = array();
		foreach($all_pct as $pct_year => $pct_sec) {
			if ($i==$key)
				return $pct_sec;
			$i++;
		}
	}


	static function checkPct($pct) {
		$arr = self::getPctArray();
		foreach($arr as $year=>$sec) {
			if (floatval($pct)===floatval($sec))
				return true;
		}
		return false;
	}

	static function fillMainArray() {

		self::$MainArray = array(
			// новый юзер
			1 => 'new_user',
			// новый майнер
			2 => 'new_miner',
			// Добавление новой обещанной суммы
			3 => 'new_promised_amount',
			4 => 'change_promised_amount',
			// голос за претендента на майнера
			5 => 'votes_miner',
			6 => 'new_forex_order',
			7 => 'del_forex_order',
			//  новый набор max_other_currencies от нода-генератора блока
			8 => 'new_max_other_currencies',
			// geolocation. Майнер изменил свои координаты
			9 => 'change_geolocation',
			// votes_promised_amount.
			10 => 'votes_promised_amount',
			// del_promised_amount. Удаление обещанной суммы
			11 => 'del_promised_amount',
			// send_dc
			12 => 'send_dc',
			13 => 'cash_request_out',
			14 => 'cash_request_in',
			// набор голосов по разным валютам
			15 => 'votes_complex',
			16 => 'change_primary_key',
			17 => 'change_node_key',
			18 => 'for_repaid_fix',
			// занесение в БД данных из первого блока
			19 => 'admin_1block',
			// админ разжаловал майнеров в юзеры
			20 => 'admin_ban_miners',
			// админ изменил variables
			21 => 'admin_variables',
			// админ обновил набор точек для проверки лиц
			22 => 'admin_spots',
			// админ вернул майнерам звание "майнер"
			24 => 'admin_unban_miners',
			// админ отправил alert message
			25 => 'admin_message',
			// майнер хочет, чтобы указаные им майнеры были разжалованы в юзеры
			26 => 'abuses',
			// майнер хочет, чтобы в указанные дни ему не приходили запросы на обмен DC
			27 => 'new_holidays',
			28 => 'actualization_promised_amounts',
			29 => 'mining',
			// Голосование нода за фото нового майнера
			30 => 'votes_node_new_miner',
			// Юзер исправил проблему с отдачей фото и шлет повторный запрос на получение статуса "майнер"
			31=>'new_miner_update',
			//  новый набор max_promised_amount от нода-генератора блока
			32=>'new_max_promised_amounts',
			//  новый набор % от нода-генератора блока
			33=>'new_pct',
			// добавление новой валюты
			34=>'admin_add_currency',
			35=>'new_cf_project',
			// новая версия, которая кладется каждому в диру public
			36=>'admin_new_version',
			// после того, как новая версия протестируется, выдаем сообщение, что необходимо обновиться
			37=>'admin_new_version_alert',
			// баг репорты
			38=>'message_to_admin',
			// админ может ответить юзеру
			39=>'admin_answer',
			40=>'cf_project_data',
			// блог админа
			41=>'admin_blog',
			// майнер меняет свой хост
			42=>'change_host',
			// майнер меняет комиссию, которую он хочет получать с тр-ий
			43=>'change_commission',
			44=>'del_cf_funding',
			// запуск урезания на основе голосования. генерит нод-генератор блока
			45=>'new_reduction',
			46=>'del_cf_project',
			47=>'cf_comment',
			48=>'cf_send_dc',
			49=>'user_avatar',
			50=>'cf_project_change_category'
		);

	}

	static function findType ($type)
	{
		self::fillMainArray();
		return array_search($type, self::$MainArray);
	}

	function DataPre()
	{
		$this->block_hash_hex = hash ( 'sha256', hash ( 'sha256', $this->binary_data ) );

		list(, $this->block_hex) = unpack( "H*", $this->binary_data );

		// определим тип данных
		$this->type = binary_dec( $this->string_shift( $this->binary_data, 1 ) );
	}

	public function __construct($block_data, $db) {

		$this->fillMainArray();

		$this->binary_data = $block_data;

		$this->db = $db;

		self::$_instance = $this;

	}

	public static function getInstance() {
		return self::$_instance;
	}


	// ищем ближайшее время в $points_status_array или $max_promised_amount_array
	// $type - status для $points_status_array / amount - для $max_promised_amount_array
	static function find_min_points_status ($need_time, &$array, $type) {

		$find_time = array();
		$array_ = $array;
		$time_status_arr = array();
		foreach ($array as $time=>$status) {
			////print '@$time='.$time."\n";
			if ($time > $need_time)
				break;
			$find_time[] = $time;
			debug_print( 'unset: time ('.$time.')'.$array[$time], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			unset($array[$time]);
		}
		if ($find_time) {
			////print '$find_time:';
			//print_R($find_time);
			for ($i=0; $i<sizeof($find_time); $i++) {
				$time_status_arr[$i]['time'] = $find_time[$i];
				$time_status_arr[$i][$type] = $array_[$find_time[$i]];
			}
		}
		return $time_status_arr;
	}

	// ищем ближайшее время в $pct_array
	static function find_min_pct ($need_time, $pct_array, $status='')
	{
		$return = 0;
		$find_time = -1;
		//debug_print( '$need_time:'.$need_time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//debug_print( '$status:'.$status, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//debug_print( '$pct_array:'.print_r_hex($pct_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$pct = 0;
		foreach ($pct_array as $time=>$arr) {
			debug_print( '$time:'.$time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($time > $need_time) {
				debug_print( 'break', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				break;
			}
			$find_time = $time;
		}
		if ($find_time>=0) {
			debug_print( '$find_time:'.$find_time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($status)
				$pct = $pct_array[$find_time][$status];
			else
				$pct = $pct_array[$find_time];
		}
		return $pct;
	}

	static function get_max_promised_amount_calc_profit($amount, $repaid_amount, $max_promised_amount, $currency_id)
	{
		debug_print( '$amount:'.$amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$repaid_amount:'.$repaid_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$max_promised_amount:'.$max_promised_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$currency_id:'.$currency_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// для WOC $repaid_amount всегда = 0, т.к. cash_request на WOC послать невозможно
		// если наша сумма больше, чем максимально допустимая ($find_min_array[$i]['amount'])
		if ($amount+$repaid_amount > $max_promised_amount)
			$amount_ = $max_promised_amount - $repaid_amount;
		// для WOC разрешено брать max_promised_amount вместо promised_amount, если promised_amount < max_promised_amount
		else if ($amount < $max_promised_amount && $currency_id==1)
			$amount_ = $max_promised_amount;
		else
			$amount_ = $amount;
		return $amount_;
	}

	// определяет, какой calc_profit будет вызван
	function calc_profit_($amount, $time_start, $time_finish, $pct_array, $points_status_array, $holidays_array=array(), $max_promised_amount_array=array(), $currency_id=0, $repaid_amount=0)
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id']<=24946)
			return self::calc_profit_24946($amount, $time_start, $time_finish, $pct_array, $points_status_array, $holidays_array, $max_promised_amount_array, $currency_id, $repaid_amount);
		else
			return self::calc_profit($amount, $time_start, $time_finish, $pct_array, $points_status_array, $holidays_array, $max_promised_amount_array, $currency_id, $repaid_amount);
	}

	/**
	 *
	Вычисляем, какой получится профит от суммы $amount
	$pct_array = array(
		1394308460=>array('user'=>0.05, 'miner'=>0.10),
		1394308470=>array('user'=>0.06, 'miner'=>0.11),
		1394308480=>array('user'=>0.07, 'miner'=>0.12),
		1394308490=>array('user'=>0.08, 'miner'=>0.13)
		);
	 * $holidays_array = array ($start, $end)
	 * $points_status_array = array(
		1=>'user',
		9=>'miner',
		10=>'user',
		12=>'miner'
	 * );
	 * $max_promised_amount_array = array(
		1394308460=>7500,
		1394308471=>2500,
		1394308482=>7500,
		1394308490=>5000
		);
	 * $repaid_amount, $holidays_array, $points_status_array, $max_promised_amount_array нужны только для обещанных сумм. у погашенных нет $repaid_amount, $holidays_array, $max_promised_amount_array
	 * $repaid_amount нужен чтобы узнать, не будет ли превышения макс. допустимой суммы. считаем amount mining+repaid
	 * $currency_id - для иднетификации WOC
	 * */
	static function calc_profit( $amount, $time_start, $time_finish, $pct_array, $points_status_array, $holidays_array=array(), $max_promised_amount_array=array(), $currency_id=0, $repaid_amount=0 )
	{

		if ($time_start>=$time_finish) {
			debug_print($time_start.'>='.$time_finish, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return 0;
		}

		// для WOC майнинг останавливается только если майнера забанил админ, каникулы на WOC не действуют
		if ($currency_id==1)
			$holidays_array = array();

		debug_print( '$amount:'.$amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$repaid_amount:'.$repaid_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$time_start:'.$time_start, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$time_finish:'.$time_finish, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		/* $max_promised_amount_array имеет дефолтные значения от времени = 0
		 * $pct_array имеет дефолтные значения 0% для user/miner от времени = 0
		 * в $points_status_array крайний элемент массива всегда будет относиться к текущим 30-и дням т.к. перед calc_profit всегда идет вызов points_update
		 * */

		//debug_print( '$pct_array:'.print_r_hex($pct_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$points_status_array:'.print_r_hex($points_status_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$holidays_array:'.print_r_hex($holidays_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$max_promised_amount_array:'.print_r_hex($max_promised_amount_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		sort($holidays_array);
		ksort($points_status_array);
		ksort($pct_array);
		ksort($max_promised_amount_array);
		//debug_print( '$pct_array:'.print_r_hex($pct_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$points_status_array:'.print_r_hex($points_status_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$holidays_array:'.print_r_hex($holidays_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$max_promised_amount_array:'.print_r_hex($max_promised_amount_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		### $points_status_array + $pct_array = $pct_array

		$last_status = false;
		// нужно получить массив вида time=>pct, совместив $pct_array и $points_status_array
		foreach ($pct_array as $time=>$status_pct_array) {

			//debug_print( '$time:'.$time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			//debug_print( '$status_pct_array:'.print_r_hex($status_pct_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$find_min_array = self::find_min_points_status($time, $points_status_array, 'status');
			//debug_print( '$find_min_array:'.print_r_hex($find_min_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			for ($i=0; $i<sizeof($find_min_array); $i++) {

				//debug_print( '$find_min_array[$i]:'.print_r_hex($find_min_array[$i]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				//if ($find_min_array[$i]['time'] < $time) {
				if ($find_min_array[$i]['time'] <= $time) {

					$find_min_pct = self::find_min_pct($find_min_array[$i]['time'], $pct_array, $find_min_array[$i]['status']);
					$new_arr[$find_min_array[$i]['time']] = $find_min_pct;
					//debug_print( '$new_arr['.$find_min_array[$i]['time'].'] = '.$find_min_pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					$last_status = $find_min_array[$i]['status'];
					//debug_print( 'last_status='.$last_status, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				}
			}
			if (!$find_min_array && !$last_status) {
				//debug_print( '$find_min_array[0][status]=user', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$find_min_array[0]['status'] = 'user';
			}
			else if (!$find_min_array && $last_status) { // есть проценты, но кончились points_status
				//debug_print( '$find_min_array[0][status]=miner', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				//$find_min_array[0]['status'] = 'miner';
				$find_min_array[0]['status'] = $last_status;
			}
			//debug_print( '$new_arr['.$time.'] = '.$status_pct_array[$find_min_array[sizeof($find_min_array)-1]['status']], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$new_arr[$time] = $status_pct_array[$find_min_array[sizeof($find_min_array)-1]['status']];
			$status_pct_array_ = $status_pct_array;
		}
		// если в points больше чем в pct
		if ($points_status_array) {
			//debug_print( 'remainder $points_status_array:'.print_r_hex($points_status_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			foreach ($points_status_array as $time=>$status) {
				$new_arr[$time] = $status_pct_array_[$status];
			}
		}

		// массив, где ключи - это время из pct и points_status, а значения - проценты.
		//debug_print( '$new_arr:'.print_r_hex($new_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$pct_array = $new_arr;


		### $max_promised_amount_array + $pct_array
		/*
		 * в $pct_array сейчас
			    [1394308000] =>  0,05
			    [1394308100] =>  0,1

			после обработки станет

			    [1394308000] => Array
					(
					    [pct] => 0,05
					    [amount] => 1000
					)
			    [1394308005] => Array
					(
					    [pct] => 0,05
					    [amount] => 100
					)
			    [1394308100] => Array
					(
					    [pct] => 0,1
					    [amount] => 100
					)

		 * */
		$new_arr = array();

		if (!$max_promised_amount_array)
			$last_amount = $amount;
		// нужно получить массив вида time=>pct, совместив $pct_array и $max_promised_amount_array
		foreach ($pct_array as $time=>$pct) {

			//debug_print( '$time:'.$time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$find_min_array = self::find_min_points_status($time, $max_promised_amount_array, 'amount');
			//debug_print( '$find_min_array:'.print_r_hex($find_min_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			for ($i=0; $i<sizeof($find_min_array); $i++) {

				//debug_print( '$find_min_array[$i]:'.print_r_hex($find_min_array[$i]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$amount_ = self::get_max_promised_amount_calc_profit($amount, $repaid_amount, $find_min_array[$i]['amount'], $currency_id);
				//debug_print( '$amount_:'.$amount_, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				if ($find_min_array[$i]['time'] <= $time) {

					$find_min_pct = self::find_min_pct($find_min_array[$i]['time'], $pct_array);

					//debug_print( '$new_arr['.$find_min_array[$i]['time'].'][pct]:'.$find_min_pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					//debug_print( '$new_arr['.$find_min_array[$i]['time'].'][amount]:'.$amount_, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					$new_arr[$find_min_array[$i]['time']]['pct'] = $find_min_pct;
					$new_arr[$find_min_array[$i]['time']]['amount'] = $amount_;

					$last_amount = $amount_;
					//debug_print( '$last_amount:'.$last_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				}
			}

			//debug_print( '$new_arr['.$time.'][pct]:'.$pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			//debug_print( '$new_arr['.$time.'][amount]:'.$last_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$new_arr[$time]['pct'] = $pct;
			$new_arr[$time]['amount'] = $last_amount;
			$pct_ = $pct;
		}

		// если в max_promised_amount больше чем в pct
		if ($max_promised_amount_array) {
			debug_print( 'remainder $max_promised_amount_array:'.print_r_hex($max_promised_amount_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			foreach ($max_promised_amount_array as $time=>$max_promised_amount) {
				$new_arr[$time]['pct'] = $pct_;
				$new_arr[$time]['amount'] = $amount_ = self::get_max_promised_amount_calc_profit($amount, $repaid_amount, $max_promised_amount, $currency_id);
			}
		}

		//debug_print( '$time:'.$time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//debug_print( '$pct:'.$pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		/*
		// если в points больше чем в pct
		if ($max_promised_amount_array) {
			debug_print( 'remainder $points_status_array:'.print_r_hex($max_promised_amount_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			foreach ($points_status_array as $time=>$status) {
				$new_arr[$time]['pct'] = $max_promised_amount_array[$status];
				$new_arr[$time]['amount'] = $amount;
			}
		}
		*/

		if (max(array_keys($new_arr))<$time_finish)
			$new_arr[$time_finish] = $pct; // добавим сразу время окончания

		//debug_print( '$new_arr:'.print_r_hex($new_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$amount_ = $amount;
		$new = array();
		$start_holidays = false;
		$old_time = 0;
		$old_pct_and_amount = array();
		foreach ($new_arr as $time=>$pct_and_amount) {

			if ($time > $time_finish)
				continue;

			if ($time > $time_start) {

				$work_time = $time;

				for ($j=0; $j<sizeof($holidays_array); $j++) {

					debug_print( '$holidays_array[$j] '.print_r_hex($holidays_array[$j]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					debug_print( "time=$time / old_time=$old_time / work_time=$work_time", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

					if (@$holidays_array[$j][1]<=$old_time) {
						debug_print( "continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						continue;
					}

					// полные каникулы в промежутке между time и old_time
					//if ( @$holidays_array[$j][0] && $work_time >= @$holidays_array[$j][0] && @$holidays_array[$j][1] && $work_time >= @$holidays_array[$j][1] ) {
					if ( isset($holidays_array[$j][0]) && $old_time <= $holidays_array[$j][0] && isset($holidays_array[$j][1]) && $work_time >= $holidays_array[$j][1] ) {

						$time = $holidays_array[$j][0];
						unset($holidays_array[$j][0]);
						debug_print( 'unset($holidays_array[$j][0])', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						debug_print( "(0) old_time={$old_time} - time=$time (".print_r_hex($old_pct_and_amount).")\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

						$to_new = array( 'num_sec'=>($time-$old_time), 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
						debug_print($to_new, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$new[] = $to_new;

						$old_time = $holidays_array[$j][1];
						unset($holidays_array[$j][1]);
						debug_print( 'unset($holidays_array[$j][1])', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

					}
					if ( isset($holidays_array[$j][0]) && $work_time >= $holidays_array[$j][0] ) {

						debug_print( "holidays [0] ={$holidays_array[$j][0]} in $work_time\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$start_holidays = true; // есть начало каникул, но есть ли конец?
						$finish_holidays_element = $holidays_array[$j][1]; // для записи в лог
						$time = $holidays_array[$j][0];
						if ($time < $time_start)
							$time = $time_start;
						unset($holidays_array[$j][0]);
						debug_print( 'unset($holidays_array[$j][0])', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					}
					else if (isset($holidays_array[$j][1]) && $work_time < $holidays_array[$j][1] && !isset($holidays_array[$j][0])) {

						// конец каникул заканчивается после $work_time
						debug_print( "no end holidays in current $work_time\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$time = $old_time;
						continue;
					}
					else if ( isset($holidays_array[$j][1]) && $work_time >= $holidays_array[$j][1] ) {

						debug_print( "holidays [1]={$holidays_array[$j][1]} in $work_time\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

						$old_time = $holidays_array[$j][1];
						unset($holidays_array[$j][1]);

						$start_holidays = false; // конец каникул есть
					}
					else if ($j==sizeof($holidays_array)-1 && !$start_holidays) {

						// если это последний полный внутрений холидей, то $time должен быть равен текущему work_time
						$time = $work_time;
					}

					//if (@$holidays_array[$j][1] && $time >= @$holidays_array[$j][1] && )
				}
				//$new[] = array($time - $old_time, $old_pct);
				if ($time > $time_finish)
					$time = $time_finish;
				//debug_print( "(end) old_time=$old_time - time=$time (".print_r_hex($old_pct_and_amount).")\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$to_new = array( 'num_sec'=>($time-$old_time), 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
				//debug_print($to_new, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$new[] = $to_new;

				$old_time = $time;
			}
			else {
				$old_time = $time_start;
			}

			$old_pct_and_amount = $pct_and_amount;

		}

		if ($start_holidays && $finish_holidays_element)
			debug_print( '$finish_holidays_element='.$finish_holidays_element, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// время в процентах меньше, чем нужное нам конечное время
		if ($old_time < $time_finish && !$start_holidays) {
			// просто берем последний процент и добиваем его до нужного $time_finish
			$sec = $time_finish - $old_time;
			debug_print( 'берем последний процент и добиваем его до нужного $time_finish '.$sec, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$new[] = array( 'num_sec'=>$sec, 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
		}

		//debug_print( $new, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		debug_print( '$amount_='.$amount_."\n".'$time_start='.$time_start."\n".'$time_finish='.$time_finish, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$amount_and_profit = 0;
		$profit = 0;
		for ($i=0; $i<sizeof($new); $i++) {

			$pct = 1+$new[$i]['pct'];
			$num = $new[$i]['num_sec'];
			$amount_and_profit = $profit +$new[$i]['amount'];
			//$profit = ( floor( round( $amount_and_profit*pow($pct, $num), 3)*100 ) / 100 ) - $new[$i]['amount'];
			// из-за того, что в front был подсчет без обновления points, а в рабочем методе уже с обновлением points, выходило, что в рабочем методе было больше мелких временных промежуток, и получалось profit <0.01, из-за этого было расхождение в front и попадание минуса в БД
			$profit =  $amount_and_profit*pow($pct, $num) - $new[$i]['amount'];
			//debug_print( "num={$num} pct={$pct} amount={$new[$i]['amount']} profit={$profit}\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}

		debug_print( "total profit w/o amount = ".$profit."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		return $profit;
	}

	function check_miner ($user_id) {

		$add_sql = '';

		// если разжаловали в этом блоке, то считаем всё еще майнером
		$block_id = isset($this->block_data['block_id']) ? $this->block_data['block_id'] : 0;
		if ($block_id)
			$add_sql = " OR `ban_block_id`={$block_id}";

		// когда админ разжаловывает майнера, у него пропадет miner_id
		$miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$user_id} AND
							 (`miner_id`>0 {$add_sql})
				LIMIT 1
				", 'fetch_one');
		// если есть бан в этом же блоке, то будет miner_id = 0, но условно считаем, что проверка пройдена
		if ( $miner_id > 0 || ($miner_id == '0' && $block_id > 0))
			return true;

	}

	static  function string_shift (&$string, $index = 1) {

		$substr = substr($string, 0, $index);
		$string = substr($string, $index);
		return $substr;
	}

	static  function string_shift_reverse (&$string, $index = 1) {

		$substr = substr($string, -$index);
		$string = substr($string, 0, -$index);
		return $substr;
	}

	static  function binary_dec_string_shift (&$string, $index = 1) {

		$hex = unpack( "H*", self::string_shift($string, $index) );
		return hexdec($hex[1]);
	}

	static function dsha256($data) {

		return hash('sha256',  hash('sha256', $data));
	}

	static function encode_length ($length) {

		if ($length <= 0x7F) {
			return chr($length);
		}
		$temp = ltrim(pack('N', $length), chr(0));
		return pack('Ca*', 0x80 | strlen($temp), $temp);
	}

	static function encode_length_plus_data ($data) {

		$length = strlen($data);
		if ($length <= 0x7F) {
			return chr($length).$data;
		}
		$temp = ltrim(pack('N', $length), chr(0));
		return pack('Ca*', 0x80 | strlen($temp), $temp) . $data;
	}

	static function decode_length (&$string) {

        $length = ord(self::string_shift($string));
        if ( $length & 0x80 ) {
            $length&= 0x7F;
            $temp = self::string_shift($string, $length);
            list(, $length) = unpack('N', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4));
        }
        return $length;
    }

	function pp_length ($p1, $p2) {

		return sqrt( pow ( ($p1[0]-$p2[0]), 2) + pow ( ($p1[1]-$p2[1]), 2) );
	}

	static function get_variables ( $db, $variables_array ) {

		$variables = '';
		for ($i=0; $i<sizeof($variables_array); $i++)
			$variables.="'{$variables_array[$i]}',";
		$variables = substr($variables, 0, strlen($variables)-1);

		return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."variables`
				WHERE `name` IN ($variables)
				",	'list', array('name', 'value'));

	}

	static function get_all_variables ($db)
	{
		return $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."variables`
				",	'list', array('name', 'value'));

	}

	function get_my_miners_ids()
	{
		$collective = get_community_users($this->db);
		if ($collective) {// если работаем в пуле
			$my_miners_ids = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` IN (".implode(',', $collective).") AND
								 `miner_id` > 0
					", 'array');
		}
		else {
			$my_miners_ids[] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`
					FROM `".DB_PREFIX."my_table`
					", 'fetch_one');
		}
		return $my_miners_ids;
	}

	function get_my_user_id($user_id)
	{
		$this->my_user_id = 0;
		$this->my_prefix = '';
		$collective = get_community_users($this->db);
		if ($collective) {// если работаем в пуле
			$this->my_user_ids = $collective;
			// есть ли юзер, который задействован среди юзеров нашего пула
			if (in_array($user_id, $collective)) {
				$this->my_prefix = $user_id.'_';
				// чтобы не было проблем с change_primary_key нужно получить user_id только тогда, когда он был реально выдан
				// в будущем можно будет переделать, чтобы user_id можно было указывать всем и всегда заранее. тогда при сбросе будут собираться более полные таблы my_, а не только те, что заполнятся в change_primary_key
				$this->my_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."{$this->my_prefix}my_table`
						", 'fetch_one');
			}
		}
		else {
			$this->my_user_id= $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."my_table`
						", 'fetch_one');
			$this->my_user_ids[] = $this->my_user_id;
		}
		$this->my_block_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `my_block_id`
				FROM `".DB_PREFIX."config`
				", 'fetch_one');
	}

	function getUserStatus($user_id) {

		$user_status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$user_id}
				" , 'fetch_one' );
		// bad_miner - это тот же user
		return ($user_status=='passive_miner'?'user':'miner');
	}

	static function download_and_save ($url, $file)
	{
		////print '$url='.$url."=\n";
		////print '$file='.$file."=\n";
		debug_print('$url:'.$url, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$file:'.$file, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$ch = curl_init( $url );
		$fp = fopen( $file , "w+");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10); // timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); // timeout in seconds
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}

	static  function checkSign($public_keys, $for_sign, $signs, $node_key_or_login=false)
	{
		$signs_array = array();
		$public_keys_array = array();
		// у нода всегда 1 подпись
		if ($node_key_or_login) {
			debug_print('$node_key=true', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$signs_array[0] = $signs;
			$public_keys_array[0] = $public_keys;
		}
		else {
			// в 1 $signs может быть от 1 до 3-х подписей
			do {
				$length = self::decode_length($signs);
				$signs_array[] = self::string_shift($signs, $length);
			} while ($signs);
			$public_keys_array = $public_keys;
		}

		debug_print('$public_keys_array='.print_r_hex($public_keys_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$signs_array='.print_r_hex($signs_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (sizeof($public_keys_array) != sizeof($signs_array))
			return 'false sign (sizeof($public_keys_array) != sizeof($signs_array))';

		$i=0;
		foreach($public_keys_array as $public_key) {

			// если вдруг пошлют 1 подпись в то время, когда нужно 2-3
			if (!@$signs_array[$i])
				return '!$signs_array['.$i.']';
			debug_print('$sign='.bin2hex($signs_array[$i]), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print('$public_key='.bin2hex($public_key), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// проверяем подпись
			$rsa = new Crypt_RSA();
			$rsa->loadKey($public_key, CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
			$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
			debug_print("for_sign={$for_sign}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ( !$rsa->verify($for_sign, $signs_array[$i]) ) {
				debug_print('FALSE', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				return 'false sign';
			}
			unset($rsa);
			$i++;
		}
	}

	public function count_miner_attempt ($db, $user_id, $type) {

		return  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT count(`user_id`)
						FROM `".DB_PREFIX."votes_miners`
						WHERE `user_id` = {$user_id} AND
									 `type` = '{$type}'
						", 'fetch_one' );
	}

	// получаем псевдослучайное значение от 1 до $max_miner_id включительно.
	// $ctx задает seeding
	// $miners_keepers - задается админом в variables. Скольким майнерам копируем фото юзера. По дефолту = 10
	static function get_miners_keepers ($ctx, $max_miner_id, $miners_keepers, $arr0=false) {

		for ($i=0; $i < $miners_keepers; $i++) {

			$hi = $ctx / 127773;
			$lo = $ctx % 127773;
			$x = 16807 * $lo - 2836 * $hi;
			if ($x <= 0)
				$x += 0x7fffffff;
			$rez = ( ($ctx = $x) % ($max_miner_id + 1));
			$rez = ($rez==0)?1:$rez;
			$arr_[$rez] = 1;

		}
		if ($arr0 ) {
			foreach ( $arr_ as $k => $v ) {
				$arr[] = $k;
			}
		}
		else
			$arr = $arr_;
		return $arr;
	}


	function parse_transaction (&$transaction_binary_data) {

		$return_array = array();
		$tx_data = array();
		$merkle_array = array();

		if ($transaction_binary_data) {
			// хэш транзакции
			$tx_data[0]  = hash('sha256', hash('sha256', $transaction_binary_data ) );

			// первый байт - тип транзакции
			$tr_type = binary_dec ($this->string_shift ($transaction_binary_data, 1));
			$tx_data[1] = $tr_type;

			if (!$transaction_binary_data)
				return 'bad tx';
			// следующие 4 байта - время транзакции
			$tr_time = binary_dec ($this->string_shift ($transaction_binary_data, 4));
			$tx_data[2] = $tr_time;

			debug_print('$tx_data='.print_r_hex($tx_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// преобразуем бинарные данные транзакции в массив
			if (!$transaction_binary_data)
				return 'bad tx';
			$i=0;
			do {
				$length = $this->decode_length($transaction_binary_data);
				debug_print('$length='.$length, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				if ($length>0 && $length < $this->global_variables['max_tx_size']) {
					$data = $this->string_shift($transaction_binary_data, $length);
					$return_array[] = $data;
					$merkle_array[] = self::dsha256($data);
				}
				$i++;
			} while ($length && $i<20); // у нас нет тр-ий с более чем 20 элементами
			if (strlen($transaction_binary_data)>0) {
				debug_print('$return_array='.print_r_hex($return_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				debug_print('$transaction_binary_data='.bin2hex($transaction_binary_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				return 'error $transaction_binary_data';
			}
		}
		else
			$merkle_array[]=0;

		$this->merkle_root = testblock::merkle_tree_root($merkle_array);

		return array_merge($tx_data, $return_array);

	}

	function general_rollback( $table, $where_user_id='', $add_where = '', $A_I = false ) {

		$where = ($where_user_id?"WHERE `user_id` = {$where_user_id}":'');

		// получим log_id, по которому можно найти данные, которые были до этого
		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."{$table}`
				{$where} {$add_where}
				LIMIT 1
				", 'fetch_one' );
		////print $this->db->printsql()."\n";

		// если $log_id = 0, значит восстанавливать нечего и нужно просто удалить запись
		if ( $log_id == 0 ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$table}`
					{$where} {$add_where}
					LIMIT 1
					");

			/*if  ( $A_I ) {
				//print 'A_I';
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						ALTER TABLE `".DB_PREFIX."{$table}`
						AUTO_INCREMENT = {$user_id}
						");
			}*/

		}
		else {

			// данные, которые восстановим
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."log_{$table}`
			        WHERE `log_id` = {$log_id}
			        ", 'fetch_array' );
			debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$add_sql = '';
			foreach ( $data as $k => $v ) {

				// block_id т.к. в log_ он нужен для удаления старых данных, а в обычной табле не нужен
				if ( $k == 'log_id' || $k == 'prev_log_id'  || $k == 'block_id' )
					continue;
				if ($k=='node_public_key')
					$add_sql.= "`{$k}`=0x".bin2hex($v).",";
				else
					$add_sql.= "`{$k}`='{$v}',";
			}
			// всегда пишем предыдущий log_id
			$add_sql .= "log_id = {$data['prev_log_id']},";

			$add_sql = substr( $add_sql, 0, strlen($add_sql) - 1 );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$table}`
					SET {$add_sql}
					{$where}  {$add_where}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_{$table}`
					WHERE `log_id` = {$log_id}
					");

			$this->rollbackAI("log_{$table}");
		}
	}


	/*
	 * Начисляем новые DC юзеру, пересчитав ему % от того, что уже было на кошельке
	 * */
	function update_recipient_wallet ( $to_user_id, $currency_id, $amount, $from='', $from_id='', $comment='', $comment_status='encrypted' )
	{

		$from_id = intval($from_id);
		debug_print("to_user_id={$to_user_id}\ncurrency_id={$currency_id}\namount={$amount}\nfrom={$from}\nfrom_id={$from_id}\ncomment={$comment}" , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$wallet_where = "
				`user_id` = {$to_user_id} AND
				`currency_id` = {$currency_id}
				";
		$wallet_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							 `amount_backup`,
							 `last_update`,
							 `log_id`
				FROM `".DB_PREFIX."wallets`
				WHERE {$wallet_where}
				", 'fetch_array' );
		// если кошелек получателя создан, то
		// начисляем DC на кошелек получателя.
		if ($wallet_data) {

			// нужно залогировать текущие значения для to_user_id
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_wallets` (
							`amount`,
							`amount_backup`,
							`last_update`,
							`block_id`,
							`prev_log_id`
						)
						VALUES (
							{$wallet_data['amount']},
							{$wallet_data['amount_backup']},
							{$wallet_data['last_update']},
							{$this->block_data['block_id']},
							{$wallet_data['log_id']}
						)
					");
			$log_id = $this->db->getInsertId ();

			//$points_status = self::getPointsStatus($to_user_id, $this->db, true, $this->variables['points_update_time']);
			$points_status = array(0=>'user');
			// holidays не нужны, т.к. это не TDC, а DC
			// то, что выросло на кошельке
			if ($currency_id>=1000) // >=1000 - это CF-валюты, которые не растут
				$new_DC_sum = $wallet_data['amount'];
			else
				$new_DC_sum = $wallet_data['amount'] + $this->calc_profit_ ( $wallet_data['amount'], $wallet_data['last_update'], $this->block_data['time'],	$this->pct[$currency_id], $points_status );
			debug_print( '$new_DC_sum='.$new_DC_sum, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// итоговая сумма DC
			$new_DC_sum_end = $new_DC_sum + $amount;
			debug_print( '$new_DC_sum_end='.$new_DC_sum_end, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// Плюсуем на кошелек с соответствующей валютой.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE
						`".DB_PREFIX."wallets`
					SET
						`amount` = {$new_DC_sum_end},
						`last_update` = {$this->block_data['time']},
						`log_id` = {$log_id}
					WHERE
						{$wallet_where}
					");
			//debug_print($this->db->printsql()."\nAffectedRows=".$this->db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}
		else {
			// если кошелек получателя не создан, то создадим и запишем на него сумму перевода.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."wallets` (
							`user_id`,
							`currency_id`,
							`amount`,
							`last_update`
						)
						VALUES (
							{$to_user_id},
							{$currency_id},
							{$amount},
							{$this->block_data['time']}
					)");
		}

		$this->get_my_user_id($to_user_id);
		if ($to_user_id == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			if ($from == 'from_user' && $comment && $comment_status!='decrypted') { // Перевод между юзерами
				$comment_status = 'encrypted';
				$comment = bin2hex($comment);
			}
			else // системные комменты (комиссия, майнинг и пр.)
				$comment_status = 'decrypted';
			// для отчетов и api пишем транзакцию
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_dc_transactions` (
							`type`,
							`type_id`,
							`to_user_id`,
							`amount`,
							`time`,
							`block_id`,
							`currency_id`,
							`comment`,
							`comment_status`
						)
						VALUES (
							'{$from}',
							{$from_id},
							{$to_user_id},
							$amount,
							{$this->block_data['time']},
							{$this->block_data['block_id']},
							{$currency_id},
							'{$comment}',
							'{$comment_status}'
						)");
		}
	}

	function update_sender_wallet($from_user_id, $currency_id, $amount, $commission, $from, $from_id, $to_user_id, $comment, $comment_status)
	{

		$from_id = intval($from_id);
		// получим инфу о текущих значениях таблицы wallets для юзера from_user_id
		$wallet_where = "
			`user_id` = {$from_user_id} AND
			`currency_id` = {$currency_id}
		";

		$wallet_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							 `amount_backup`,
							 `last_update`,
							 `log_id`
				FROM `".DB_PREFIX."wallets`
				WHERE {$wallet_where}
				", 'fetch_array' );
		debug_print( $wallet_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// перед тем, как менять значения на кошельках юзеров, нужно залогировать текущие значения для юзера from_user_id
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_wallets` (
						`amount`,
						`amount_backup`,
						`last_update`,
						`block_id`,
						`prev_log_id`
				)
				VALUES (
					{$wallet_data['amount']},
					{$wallet_data['amount_backup']},
					{$wallet_data['last_update']},
					{$this->block_data['block_id']},
					{$wallet_data['log_id']}
				)");
		$log_id = $this->db->getInsertId();

		//$points_status = self::getPointsStatus($from_user_id, $this->db, true, $this->variables['points_update_time']);
		$points_status = array(0=>'user');
		// пересчитаем DC на кошельке отправителя
		// обновим сумму и дату на кошельке отправителя.
		// holidays не нужны, т.к. это не TDC, а DC.
		debug_print( $this->pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$currency_id='.$currency_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$amount='.$amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$commission='.$commission, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$from='.$from, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$from_id='.$from_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$to_user_id='.$to_user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( '$comment='.$comment, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($currency_id>=1000) // >=1000 - это CF-валюты, которые не растут
			$new_DC_sum = $wallet_data['amount'];
		else
			$new_DC_sum = $wallet_data['amount'] + $this->calc_profit_ ( $wallet_data['amount'], $wallet_data['last_update'], $this->block_data['time'], $this->pct[$currency_id], $points_status ) - $amount - $commission;
		debug_print(  'user sender $new_DC_sum='.$new_DC_sum, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."wallets`
				SET `amount` = {$new_DC_sum},
				       `last_update` = {$this->block_data['time']},
				       `log_id` = {$log_id}
				WHERE {$wallet_where}");
		//debug_print($this->db->printsql()."\nAffectedRows=".$this->db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$this->get_my_user_id($from_user_id);
		if ($from_user_id == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			if ($from == 'cf_project') {
				$where_0 = '';
				$set_0 = " `to_user_id` = {$to_user_id}, ";
			}
			else {
				$where_0 = " `to_user_id` = {$to_user_id} AND ";
				$set_0 = '';
			}
			$my_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."{$this->my_prefix}my_dc_transactions`
					WHERE `status` = 'pending' AND
								 `type` = '{$from}' AND
								 `type_id` = {$from_user_id} AND
								  {$where_0}
								 `amount` = {$amount} AND
								 `commission` = {$commission} AND
								 `currency_id` = {$currency_id}
					", 'fetch_one' );
			if ($my_id) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_dc_transactions`
						SET  `status` = 'approved',
								 {$set_0}
								`time` = {$this->block_data['time']},
								`block_id` = {$this->block_data['block_id']}
						WHERE `id` = {$my_id}
						LIMIT 1
						");
			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."{$this->my_prefix}my_dc_transactions` (
							`status`,
							`type`,
							`type_id`,
							`to_user_id`,
							`amount`,
							`commission`,
							`currency_id`,
							`comment`,
							`comment_status`,
							`time`,
							`block_id`
						)
						VALUES (
							'approved',
							'{$from}',
							'{$from_user_id}',
							{$to_user_id},
							{$amount},
							{$commission},
							{$currency_id},
							'{$comment}',
							'{$comment_status}',
							{$this->block_data['time']},
							{$this->block_data['block_id']}
						)");
			}
		}
	}


	function general_check_admin() {

		if ( !check_input_data ($this->tx_data['user_id'], 'admin_id') )
			return 'error admin_id';

		if ( !check_input_data ($this->tx_data['time'], 'int') )
			return 'admin error time';

		// проверим, есть ли такой юзер и заодно получим public_key
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key_0`,
							 `public_key_1`,
							 `public_key_2`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				", 'fetch_array' );
		$this->public_keys = array();
		$this->public_keys[0] = $data['public_key_0'];
		if ($data['public_key_1'])
			$this->public_keys[1] = $data['public_key_1'];
		if ($data['public_key_2'])
			$this->public_keys[2] = $data['public_key_2'];
		debug_print( '$this->public_keys:'.print_r_hex($this->public_keys), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if  ( !$this->public_keys ) {
			return 'user_id';
		}

		if ( strlen($this->tx_data['sign'])<256 || strlen($this->tx_data['sign'])>2048 )
			return 'strlen sign '.strlen($this->tx_data['sign']);

	}

	// общая проверка для всех _front кроме new_user_front
	function general_check()
	{
		debug_print( $this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ( !check_input_data ($this->tx_data['user_id'], 'bigint') )
			return "user_id ({$this->tx_data['user_id']})";

		if ( !check_input_data ($this->tx_data['time'], 'int') )
			return 'time';

		// проверим, есть ли такой юзер и заодно получим public_key
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key_0`,
							 `public_key_1`,
							 `public_key_2`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				", 'fetch_array' );
		$this->public_keys = array();
		$this->public_keys[0] = $data['public_key_0'];
		if ($data['public_key_1'])
			$this->public_keys[1] = $data['public_key_1'];
		if ($data['public_key_2'])
			$this->public_keys[2] = $data['public_key_2'];
		debug_print( '$this->public_keys:'.print_r_hex($this->public_keys), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if  ( !$this->public_keys ) {
			return 'user_id';
		}

		// чтобы не записали слишком длинную подпись
		// 128 - это нод-ключ
		if ( strlen($this->tx_data['sign'])<128 || strlen($this->tx_data['sign'])>5000 )
			return 'strlen sign '.strlen($this->tx_data['sign']);
	}


	/* 31
	 * обновляем номер блока photo_block_id и кол-во майнеров photo_max_miner_id,
	 * чтобы получить новый набор майнеров,
	 * которые должны сохранить фото у себя
	 */
	function new_miner_update_init()
	{
		$error = $this->get_tx_data(array('sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables ($this->db,  array('limit_votes_miners', 'limit_votes_miners_period') );
		$this->variables = self::get_all_variables($this->db);
	}

	// 31
	function new_miner_update_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		$for_sign = "{$this->tx_data['type'] },{$this->tx_data['time']},{$this->tx_data['user_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		//  на всякий случай не даем начать нодовское, если идет юзерское голосование
		$user_voting = $this ->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							`type` = 'user_voting'
				", 'fetch_one');
		if ( $user_voting )
			return 'existing $user_voting';

		// должна быть запись в miners_data
		$user_id = $this ->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one');
		if ( !$user_id)
			return 'null miners_data';

		// (неактуально) проверим, не является ли юзер майнером
		/* юзер может быть майнером- в этом случае мы просто не запустим юзерское голосование по завершении нодовского
		 * $miner_id = $this ->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one');
		if ( $miner_id > 0 )
			return 'existing miner';*/

		// можно делать не более 1 запроса за сутки.
		$error = $this -> limit_requests($this->variables['limit_votes_miners'], 'votes_miners', $this->variables['limit_votes_miners_period']);
		if ($error)
			return $error;

	}

	// 31
	function new_miner_update()
	{
		// отменяем голосования по всем предыдущим
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_end` = 1,
					   `end_block_id` = {$this->block_data['block_id']}
				WHERE `user_id` = {$this->tx_data['user_id']} AND
				             `type` = 'node_voting'
				");

		// создаем новое голосование
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."votes_miners` (
					`type`,
					`user_id`,
					`votes_start_time`
				)
				VALUES (
					'node_voting',
					{$this->tx_data['user_id']},
					{$this->block_data['time']}
				)");

		// обновим photo_block_id и photo_max_miner_id чтобы получить
		// 10 новых нодов, которые будут голосовать
		$max_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`miner_id`)
				FROM `".DB_PREFIX."miners`
				", 'fetch_one');
		$this->selective_logging_and_upd (array('photo_block_id','photo_max_miner_id'), array($this->block_data['block_id'], $max_miner_id), 'miners_data', array('user_id'), array($this->tx_data['user_id']));
		/*$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."miners_data`
				SET `photo_block_id` = {$this->block_data['block_id']},
					   `photo_max_miner_id` = {$max_miner_id}
				WHERE `user_id` = {$this->tx_data['user_id']}
				");*/
	}

	// 31
	function new_miner_update_rollback_front()
	{
		$this->limit_requests_rollback('votes_miners');
	}

	// 31
	function new_miner_update_rollback() {

		$this->selective_rollback (array('photo_block_id', 'photo_max_miner_id'), 'miners_data', "`user_id`={$this->tx_data['user_id']}");


		// отменяем новое голосование
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."votes_miners`
				WHERE `type` = 'node_voting' AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `votes_start_time` = {$this->block_data['time']}
				LIMIT 1
				");
		$this->rollbackAI('votes_miners');

		// отменяем отмену голосования
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_end` = 0,
					   `end_block_id` = 0
				WHERE `user_id` = {$this->tx_data['user_id']} AND
				             `type` = 'node_voting' AND
				             `end_block_id` = {$this->block_data['block_id']}
				");

	}

	function new_max_promised_amounts_init()
	{
		$error = $this->get_tx_data(array('new_max_promised_amounts', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	function new_max_promised_amounts_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );
		////print $this->db->printsql();
		if  ( !$this->node_public_key )
			return 'error user_id';

		$all_max_amounts = self::getAllMaxPromisedAmount();
		// проверим, верно ли указаны ID валют
		preg_match_all ( '/\"(\d{1,3})\":(\d{1,3})/', $this->tx_data['new_max_promised_amounts'], $currency_list);
		//print_r($currency_list);
		$currency_ids_sql = '';
		$count_currency = 0;
		debug_print($currency_list, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print($currency_list[1], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		for($i=0; $i<sizeof($currency_list[1]); $i++) {
			$currency_ids_sql .= $currency_list[1][$i].',';
			$amount = $currency_list[2][$i];
			$count_currency++;
			if (!in_array($amount, $all_max_amounts))
				return 'error amount '.$amount;
		}
		$currency_ids_sql = substr($currency_ids_sql, 0, strlen($currency_ids_sql)-1);
		if ($count_currency == 0)
			return 'error $count_currency '.$count_currency;

		$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				WHERE `id` IN ({$currency_ids_sql})
				", 'fetch_one' );
		////print $this->db->printsql();
		if ( $count != $count_currency )
			return 'error count_currency';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['new_max_promised_amounts']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error)
			return $error;

		// проверим, прошло ли 2 недели с момента последнего обновления max_promised_amounts
		$pct_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`time`)
				FROM `".DB_PREFIX."max_promised_amounts`", 'fetch_one' );
		if ( $this->tx_data['time']  - $pct_time <= $this->variables['new_max_promised_amount'] )
			return '14 day error';

		$max_promised_amount_votes = array();
		// берем все голоса
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							  `amount`,
							    count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_max_promised_amount`
				GROUP BY  `currency_id`, `amount`
				");
		while ( $row = $this->db->fetchArray( $res ) )
			$max_promised_amount_votes[$row['currency_id']][$row['amount']] = $row['votes'];

		$total_count_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				", 'fetch_one' );
		$new_max_promised_amounts = array();
		foreach ( $max_promised_amount_votes as $currency_id => $amounts_and_votes ) {
			$new_max_promised_amounts[$currency_id] = get_max_vote($amounts_and_votes, 0, $total_count_currencies, 10);
		}

		$json_data = json_encode($new_max_promised_amounts);
		if ( $this->tx_data['new_max_promised_amounts'] != $json_data ) {
			debug_print($json_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print($this->tx_data['new_max_promised_amounts'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return 'new_max_promised_amounts error';
		}
	}

	function new_max_promised_amounts()
	{
		$new_max_promised_amounts = json_decode($this->tx_data['new_max_promised_amounts'], true);
		debug_print($new_max_promised_amounts, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		foreach ($new_max_promised_amounts as $currency_id => $amount) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."max_promised_amounts` (
					`time`,
					`currency_id`,
					`amount`,
					`block_id`
				)
				VALUES (
					{$this->block_data['time']},
					{$currency_id},
					{$amount},
					{$this->block_data['block_id']}
				)");
		}
	}

	function new_max_promised_amounts_rollback_front()
	{

	}

	function new_max_promised_amounts_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."max_promised_amounts`
				WHERE `block_id` = {$this->block_data['block_id']}
				");
		$AffectedRows = $this->db->getAffectedRows();
		$this->rollbackAI('max_promised_amounts', $AffectedRows);
	}

	function new_max_other_currencies_init()
	{
		$error = $this->get_tx_data(array('new_max_other_currencies', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	function new_max_other_currencies_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );
		if  ( !$this->node_public_key )
			return 'error user_id';

		$total_count_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				", 'fetch_one' );
		// проверим, верно ли указаны ID валют
		preg_match_all ( '/\"(\d{1,3})\":(\d{1,3})/', $this->tx_data['new_max_other_currencies'], $currency_list);
		$currency_ids_sql = '';
		$count_currency = 0;
		for($i=0; $i<sizeof($currency_list[1]); $i++) {
			$currency_ids_sql .= $currency_list[1][$i].',';
			$count = $currency_list[2][$i];
			$count_currency++;
			if ($count > $total_count_currencies-1)
				return 'error $total_currency '.$total_count_currencies;
		}
		$currency_ids_sql = substr($currency_ids_sql, 0, strlen($currency_ids_sql)-1);
		if ($count_currency==0)
			return 'error $count_currency '.$count_currency;

		$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				WHERE `id` IN ({$currency_ids_sql})
				", 'fetch_one' );
		if ( $count != $count_currency )
			return 'error count_currency';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['new_max_other_currencies']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error)
			return $error;

		// проверим, прошло ли 2 недели с момента последнего обновления
		$pct_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`time`)
				FROM `".DB_PREFIX."max_other_currencies_time`
				", 'fetch_one' );
		if ( $this->tx_data['time']  - $pct_time <= $this->variables['new_max_other_currencies'] )
			return '14 day error';

		$max_promised_amount_votes = array();
		// берем все голоса
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							  `count`,
							    count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_max_other_currencies`
				GROUP BY  `currency_id`, `count`
				");
		while ( $row = $this->db->fetchArray( $res ) )
			$max_other_currencies_votes[$row['currency_id']][$row['count']] = $row['votes'];

		foreach ( $max_other_currencies_votes as $currency_id => $count_and_votes ) {
			$new_max_other_currencies[$currency_id] = get_max_vote($count_and_votes, 0, $total_count_currencies, 10);
		}

		$json_data = json_encode($new_max_other_currencies);
		if ( $this->tx_data['new_max_other_currencies'] != $json_data )
			return 'new_max_other_currencies error';
	}

	function new_max_other_currencies()
	{
		$new_max_other_currencies = json_decode($this->tx_data['new_max_other_currencies'], true);
		foreach ($new_max_other_currencies as $currency_id => $count) {

			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `max_other_currencies`,
								 `log_id`
					FROM `".DB_PREFIX."currency`
					WHERE `id` = {$currency_id}
					", 'fetch_array');

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_currency` (
						`max_other_currencies`,
						`prev_log_id`
					)
					VALUES (
						{$log_data['max_other_currencies']},
						{$log_data['log_id']}
					)");

			$log_id = $this->db->getInsertId();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."currency`
					SET `max_other_currencies` = {$count},
						   `log_id` = {$log_id}
					WHERE `id` = {$currency_id}
					");
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."max_other_currencies_time` (
						`time`
					)
					VALUES (
						{$this->block_data['time']}
					)");
	}

	function new_max_other_currencies_rollback_front()
	{

	}

	function new_max_other_currencies_rollback()
	{
		$new_max_other_currencies = json_decode($this->tx_data['new_max_other_currencies'], true);
		$new_max_other_currencies = array_reverse ($new_max_other_currencies, true);
		debug_print($new_max_other_currencies, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		foreach ($new_max_other_currencies as $currency_id => $count) {
			$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `log_id`
					FROM `".DB_PREFIX."currency`
					WHERE `id` = {$currency_id}
					LIMIT 1
					", 'fetch_one');
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `max_other_currencies`,
								 `prev_log_id`
					FROM `".DB_PREFIX."log_currency`
					WHERE `log_id` = {$log_id}
					LIMIT 1
					", 'fetch_array');
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."currency`
					SET  `max_other_currencies` = {$log_data['max_other_currencies']},
							`log_id` = {$log_data['prev_log_id']}
					WHERE `id` = {$currency_id}
					LIMIT 1
					");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_currency`
					WHERE `log_id` = {$log_id}
					LIMIT 1
					");
			$this->rollbackAI('log_currency');

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."max_other_currencies_time`
					WHERE `time` = {$this->block_data['time']}
					LIMIT 1
					");

		}
	}

	// Эту транзакцию имеет право генерить только нод, который генерит данный блок
	// подписана нодовским ключом
	// 33
	function new_pct_init()
	{
		$error = $this->get_tx_data(array('new_pct', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	// 33
	function new_pct_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );
		////print $this->db->printsql();
		if  ( !$this->node_public_key )
			return 'error user_id';

		$new_pct_tx = json_decode($this->tx_data['new_pct'], true);
		if (!$new_pct_tx)
			return 'error $new_pct_tx';

		// раньше не было рефских
		if (isset($this->block_data['block_id']) && $this->block_data['block_id']<=77951) {
			$new_pct_tx['currency'] = $new_pct_tx;
		}
		else {
			if (!isset($new_pct_tx['currency'], $new_pct_tx['referral']))
				return 'error currency referral';
		}

		if (!is_array($new_pct_tx['currency']))
			return 'error $new_pct_tx[currency])';

		// проверим, верно ли указаны ID валют
		$currency_ids_sql = '';
		$count_currency = 0;
		foreach($new_pct_tx['currency'] as $id=>$_data) {
			$currency_ids_sql.=$id.',';
			$count_currency++;
		}
		$currency_ids_sql = substr($currency_ids_sql, 0, -1);
		$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				WHERE `id` IN ({$currency_ids_sql})
				", 'fetch_one' );
		if ( $count != $count_currency )
			return 'error count_currency';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type'] },{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['new_pct']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error)
			return $error;

		// проверим, прошло ли 2 недели с момента последнего обновления pct
		$pct_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`time`)
				FROM `".DB_PREFIX."pct`", 'fetch_one' );
		if ( $this->tx_data['time']  - $pct_time <= $this->variables['new_pct_period'] )
			return '14 day error';

		// берем все голоса miner_pct
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							 `pct`,
							  count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_miner_pct`
				GROUP BY  `currency_id`, `pct`
				");
		while ( $row = $this->db->fetchArray( $res ) )
			$pct_votes[$row['currency_id']]['miner_pct'][$row['pct']] = $row['votes'];

		// берем все голоса user_pct
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							 `pct`,
							  count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_user_pct`
				GROUP BY  `currency_id`, `pct`
				");
		while ( $row = $this->db->fetchArray( $res ) )
			$pct_votes[$row['currency_id']]['user_pct'][$row['pct']] = $row['votes'];

		debug_print( $pct_votes, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$PctArray = ParseData::getPctArray();
		$new_pct = array();
		foreach ( $pct_votes as $currency_id => $data ) {

			// определяем % для майнеров
			$pct_arr = ParseData::makePctArray($data['miner_pct']);
			$key = get_max_vote($pct_arr, 0, 390, 100);
			$new_pct['currency'][$currency_id]['miner_pct'] = ParseData::getPctValue($key);
			debug_print( '$key miner_pct='.$key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// определяем % для юзеров
			$pct_arr = ParseData::makePctArray($data['user_pct']);
			// раньше не было завимости юзерского % от майнерского
			if (isset($this->block_data['block_id']) && $this->block_data['block_id']<=95263)
				$user_max_key = 390;
			else {
				$pct_y = array_search($new_pct['currency'][$currency_id]['miner_pct'], $PctArray);
				debug_print( 'miner_pct $pct_y='.$pct_y, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$max_user_pct_y = round($pct_y/2, 2);
				debug_print( '$max_user_pct='.$max_user_pct_y, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$user_max_key = find_user_pct($max_user_pct_y);
				debug_print( '$user_max_key='.$user_max_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				// отрезаем лишнее, т.к. поиск идет ровно до макимального возможного, т.е. до miner_pct/2
				$pct_arr = del_user_pct($pct_arr, $user_max_key);
				debug_print( '$user_$pct_arr='.print_r_hex($pct_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			}

			$key = get_max_vote($pct_arr, 0, $user_max_key, 100);
			debug_print( '$key user_pct='.$key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$new_pct['currency'][$currency_id]['user_pct'] = ParseData::getPctValue($key);
			debug_print( 'user_pct='.$new_pct['currency'][$currency_id]['user_pct'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print( 'user pct y='.array_search($new_pct['currency'][$currency_id]['user_pct'], $PctArray), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}

		// раньше не было рефских
		if (isset($this->block_data['block_id']) && $this->block_data['block_id']<=77951) {
			$new_pct = $new_pct['currency'];
		}
		else {
			$ref_levels = array('first', 'second', 'third');
			for ($i=0; $i<sizeof($ref_levels); $i++) {
				$level = $ref_levels[$i];
				$votes_referral = array();
				// берем все голоса
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `{$level}`,
									  count(`user_id`) as `votes`
						FROM `".DB_PREFIX."votes_referral`
						GROUP BY  `{$level}`
						");
				while ( $row = $this->db->fetchArray( $res ) )
					$votes_referral[$row[$level]] = $row['votes'];
				$new_pct['referral'][$level] = get_max_vote($votes_referral, 0, 30, 10);
			}
		}
		debug_print( $new_pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$json_data = json_encode($new_pct);
		debug_print( $json_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( $this->tx_data['new_pct'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if ( $this->tx_data['new_pct'] != $json_data )
			return 'new_pct error '.$json_data;

	}

	// 33
	function new_pct()
	{
		$new_pct_data = json_decode($this->tx_data['new_pct'], true);
		if ($this->block_data['block_id']<=77951) {
			$new_pct_data['currency'] = $new_pct_data;
		}

		foreach ($new_pct_data['currency'] as $currency_id => $data) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."pct` (
					`time`,
					`currency_id`,
					`miner`,
					`user`,
					`block_id`
				)
				VALUES (
					{$this->block_data['time']},
					{$currency_id},
					{$data['miner_pct']},
					{$data['user_pct']},
					{$this->block_data['block_id']}
				)");
		}

		if ($this->block_data['block_id'] > 77951) {
			$this->selective_logging_and_upd (array('first', 'second', 'third'), array($new_pct_data['referral']['first'], $new_pct_data['referral']['second'], $new_pct_data['referral']['third']), 'referral');
		}
	}

	// 33
	function new_pct_rollback_front()
	{
	}

	// 33
	function new_pct_rollback()
	{
		if ($this->block_data['block_id'] > 77951) {
			$this->selective_rollback (array('first', 'second', 'third'), 'referral');
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."pct`
				WHERE `block_id` = {$this->block_data['block_id']}
				");
		$AffectedRows = $this->db->getAffectedRows();
		debug_print( '$AffectedRows='.$AffectedRows, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->rollbackAI('pct', $AffectedRows);
	}

	// Эту транзакцию имеет право генерить только нод, который генерит данный блок
	// подписана нодовским ключом.
	// 45
	function new_reduction_init()
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 85849)
			$error = $this->get_tx_data(array('currency_id', 'pct', 'sign'));
		else
			$error = $this->get_tx_data(array('currency_id', 'pct', 'reduction_type', 'sign'));

		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	// 45
	function new_reduction_front()
	{
		global $reduction_dc;

		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		if ( !check_input_data ($this->tx_data['currency_id'], 'int') )
			return 'error currency_id';

		if ( !in_array($this->tx_data['pct'], $reduction_dc) )
			return 'error pct';

		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 85849) {
			// для всех тр-ий из старых блоков просто присваем manual, т.к. там не было других типов
			$this->tx_data['reduction_type'] = 'manual';
		}
		else {
			if ( !check_input_data ($this->tx_data['reduction_type'], 'reduction_type') )
				return 'error reduction_type';
		}

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );
		if  ( !$this->node_public_key )
			return 'error user_id';

		$currency_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."currency`
				WHERE `id`  = {$this->tx_data['currency_id']}
				", 'fetch_one' );
		if ( !$currency_id )
			return 'error currency';

		// проверяем подпись
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 85849)
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_id']},{$this->tx_data['pct']}";
		else
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_id']},{$this->tx_data['pct']},{$this->tx_data['reduction_type']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error)
			return $error;

		$reduction_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`time`)
				FROM `".DB_PREFIX."reduction`
				WHERE `currency_id` = {$this->tx_data['currency_id']}
				", 'fetch_one' );
		// проверим, прошло ли 2 недели с момента последнего reduction
		if ($this->tx_data['reduction_type'] == 'manual') {
			if ( $this->tx_data['time'] - $reduction_time <= $this->variables['reduction_period'] )
				return 'reduction_period error ('.($this->tx_data['time'] - $reduction_time).' <= '.$this->variables['reduction_period'].')';
		}
		else {
			// или 48 часов, если это авто-урезание
			if ( $this->tx_data['time'] - $reduction_time <= AUTO_REDUCTION_PERIOD )
				return 'reduction_period error ('.($this->tx_data['time'] - $reduction_time).' <= '.AUTO_REDUCTION_PERIOD.')';
		}

		if ($this->tx_data['reduction_type'] == 'manual') {
			// получаем кол-во обещанных сумм у разных юзеров по каждой валюте. start_time есть только у тех, у кого статус mining/repaid
			$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `currency_id`, count(`user_id`) as `count`
					FROM (
							SELECT `currency_id`, `user_id`
							FROM `".DB_PREFIX."promised_amount`
							WHERE `start_time` < ".($this->tx_data['time'] - $this->variables['min_hold_time_promise_amount'])."  AND
										 `del_block_id` = 0 AND
										 `del_mining_block_id` = 0 AND
										 `status` IN ('mining', 'repaid')
							GROUP BY  `user_id`, `currency_id`
							) as t1
					GROUP BY  `currency_id`
					");
			while ( $row = $this->db->fetchArray( $res ) )
				$promised_amount[$row['currency_id']] = $row['count'];

			debug_print('$promised_amount_:'.print_r_hex($promised_amount), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			if (empty($promised_amount[$this->tx_data['currency_id']]))
				return 'empty promised_amount';

			// берем все голоса юзеров по данной валюте
			$count_votes = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`currency_id`) as `votes`
					FROM `".DB_PREFIX."votes_reduction`
					WHERE `time` > ".($this->tx_data['time'] - $this->variables['reduction_period'])." AND
								 `currency_id` = {$this->tx_data['currency_id']} AND
								 `pct` = {$this->tx_data['pct']}
					", 'fetch_one');
			if ($count_votes <= $promised_amount[$this->tx_data['currency_id']] / 2)
				return 'error count_votes ('.$count_votes.' <= '.($promised_amount[$this->tx_data['currency_id']] / 2).')';
		}
		/*else if ($this->tx_data['reduction_type'] == 'cash') {

			// и недопустимо для WOC
			if ($this->tx_data['currency_id'] == 1)
				return 'WOC AUTO_REDUCTION_CASH';

			// получаем кол-во запросов на наличные за последние 48 часов
			$all_cash_requests = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`id`)
					FROM `".DB_PREFIX."cash_requests`
					WHERE `time` > ".($this->tx_data['time'] - AUTO_REDUCTION_CASH_PERIOD)." AND
								 `del_block_id` = 0 AND
								 `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');

			// урезание возможно только если за 48 часов есть более 1000 запросов на наличные по данной валюте
			if ($all_cash_requests < AUTO_REDUCTION_CASH_MIN)
				return '$all_cash_requests < '.AUTO_REDUCTION_CASH_MIN;

			// получаем кол-во удовлетворенных запросов на наличные за последние 48 часов
			$approved_cash_requests = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`id`)
					FROM `".DB_PREFIX."cash_requests`
					WHERE `time` > ".($this->tx_data['time'] - AUTO_REDUCTION_CASH_PERIOD)." AND
								 `del_block_id` = 0 AND
								 `status` = 'approved' AND
								 `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');
			// если кол-во удовлетворенных запросов менее чем 30% от общего кол-ва, то всё норм, если нет - ошибка
			if ( $approved_cash_requests >= $all_cash_requests * AUTO_REDUCTION_CASH_PCT ) {
				return $approved_cash_requests.' >= '.$all_cash_requests.' * '.AUTO_REDUCTION_CASH_PCT.'';
			}
		}*/
		else if ($this->tx_data['reduction_type'] == 'promised_amount') {

			// и недопустимо для WOC
			if ($this->tx_data['currency_id'] == 1)
				return 'WOC AUTO_REDUCTION_CASH';

			// проверим, есть ли хотябы 1000 юзеров, у которых на кошелках есть или была данная валюты
			$count_users = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`user_id`)
					FROM `".DB_PREFIX."wallets`
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');
			if ($count_users < AUTO_REDUCTION_PROMISED_AMOUNT_MIN) {
				return $count_users.' < AUTO_REDUCTION_PROMISED_AMOUNT_MIN';
			}

			// получаем кол-во DC на кошельках
			$sum_wallets = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`amount`)
					FROM `".DB_PREFIX."wallets`
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');

			// получаем кол-во TDC на обещанных суммах
			$sum_promised_amount_tdc = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`tdc_amount`)
					FROM `".DB_PREFIX."promised_amount`
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');
			$sum_wallets += $sum_promised_amount_tdc;

			// получаем суммы обещанных сумм. при этом не берем те, что имеют просроченные cash_request_out
			$sum_promised_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT  sum(`amount`)
					FROM `".DB_PREFIX."promised_amount`
					WHERE `status` = 'mining' AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0 AND
								 `currency_id` = {$this->tx_data['currency_id']} AND
								 (`cash_request_out_time` = 0 OR `cash_request_out_time` > ".($this->tx_data['time'] - $this->variables['cash_request_time']).")
					", 'fetch_one');
			// если обещанных сумм менее чем 100% от объема DC на кошельках, то всё норм, если нет - ошибка
			if ( $sum_promised_amount >= $sum_wallets * AUTO_REDUCTION_PROMISED_AMOUNT_PCT ) {
				return 'error reduction $sum_promised_amount '.$sum_promised_amount.' >= '.$sum_wallets.' * '.AUTO_REDUCTION_PROMISED_AMOUNT_PCT.'';
			}
		}
	}

	// 45
	function new_reduction()
	{
		$d = (100 - $this->tx_data['pct']) / 100;
		if ( $this->tx_data['pct'] > 0 ) {
			 // т.к. невозможо 2 отката подряд из-за промежутка в 2 дня между reduction,
			// то можем использовать только бекап на 1 уровень назад вместо _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."wallets`
					SET  `amount_backup` = `amount`,
							`amount` = `amount`*({$d})
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

			// если бы не урезали amount, то пришлось бы делать пересчет tdc по всем, у кого есть данная валюта
			// после 87826 блока убрано `amount_backup` = `amount`, `amount` = `amount`*({$d}) т.к. теряется смысл в reduction c type=promised_amount
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `tdc_amount_backup` = `tdc_amount`,
							`tdc_amount` = `tdc_amount`*({$d})
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

			// все свежие cash_request_out_time отменяем
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `cash_request_out_time_backup` = `cash_request_out_time`,
							`cash_request_out_time` = 0
					WHERE `currency_id` = {$this->tx_data['currency_id']} AND
								 `cash_request_out_time` > ".($this->block_data['time'] - $this->variables['cash_request_time'])."
					");

			// все текущие cash_requests, т.е. по которым не прошло 2 суток
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cash_requests`
					SET  `del_block_id` = {$this->block_data['block_id']}
					WHERE `currency_id` = {$this->tx_data['currency_id']} AND
								`status` = 'pending' AND
								`time` > ".($this->block_data['time'] - $this->variables['cash_request_time'])."
					");

			// форкс-ордеры
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."forex_orders`
					SET  `amount_backup` = `amount`,
							`amount` = `amount`*({$d})
					WHERE `sell_currency_id` = {$this->tx_data['currency_id']}
					");

			// круд-фандинг
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cf_funding`
					SET  `amount_backup` = `amount`,
							`amount` = `amount`*({$d})
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."reduction` (
					`time`,
					`currency_id`,
					`pct`,
					`block_id`
				)
				VALUES (
					{$this->block_data['time']},
					{$this->tx_data['currency_id']},
					{$this->tx_data['pct']},
					{$this->block_data['block_id']}
				)");
	}

	// 45
	function new_reduction_rollback_front() {

	}

	// 45
	function new_reduction_rollback()
	{

		if ( $this->tx_data['pct'] > 0 ) {

			// круд-фандинг
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cf_funding`
					SET  `amount` = `amount_backup`,
							`amount_backup` = 0
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

			// форкс-ордеры
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."forex_orders`
					SET  `amount` = `amount_backup`,
							`amount_backup` = 0
					WHERE `sell_currency_id` = {$this->tx_data['currency_id']}
					");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cash_requests`
					SET  `del_block_id` = 0
					WHERE `del_block_id` = {$this->block_data['block_id']}
					");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `cash_request_out_time` = `cash_request_out_time_backup`
					WHERE `currency_id` = {$this->tx_data['currency_id']} AND
								 `cash_request_out_time` > ".($this->block_data['time'] - $this->variables['cash_request_time'])."
					");

			// после 87826 блока убрано  `amount` = `amount_backup` т.к. теряется смысл в reduction c type=promised_amount
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `tdc_amount` = `tdc_amount_backup`
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."wallets`
					SET  `amount` = `amount_backup`,
							`amount_backup` = 0
					WHERE `currency_id` = {$this->tx_data['currency_id']}
					");

		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."reduction`
				WHERE `block_id` = {$this->block_data['block_id']}
				");
		$AffectedRows = $this->db->getAffectedRows();
		debug_print( '$AffectedRows='.$AffectedRows, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->rollbackAI('reduction', $AffectedRows);

	}


	// голосования нодов, которые должны сохранить фото у себя.
	// если смог загрузить фото к себе и хэш сошелся - 1, если нет - 0
	// эту транзакцию генерит нод со своим ключом
	// 30
	function votes_node_new_miner_init()
	{
		$error = $this->get_tx_data(array('vote_id', 'result', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables ($this->db,  array('node_voting', 'node_voting_period', 'min_miners_keepers', 'miners_keepers') );
		$this->variables = self::get_all_variables($this->db);
	}

	// 30
	function votes_node_new_miner_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		if ( !check_input_data ($this->tx_data['result'], 'vote') )
			return 'result error';

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				", 'fetch_one' );

		if  ( !$this->node_public_key )
			return 'user_id';

		if ( !check_input_data ($this->tx_data['vote_id'], 'bigint') )
			return 'vote_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['vote_id']},{$this->tx_data['result']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error)
			return $error;

		// проверим, верно ли указан ID и не закончилось ли голосование
		$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `id` = '{$this->tx_data['vote_id']}' AND
							 `type` = 'node_voting' AND
							 `votes_end` = 0
				LIMIT 1
				", 'fetch_one' );
		if ( !$id )
			return 'voting is over';

		// проверим, не повторное ли это голосование данного юзера
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`user_id`)
				FROM `".DB_PREFIX."log_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `voting_id` = {$this->tx_data['vote_id']} AND
							 `type` = 'votes_miners'
				LIMIT 1
				", 'fetch_one' );
		if  ( $num>0 )
			return 'double voting';

		// нод не должен голосовать более X раз за сутки, чтобы не было доса
		$error = $this -> limit_requests ($this->variables['node_voting'], 'votes_nodes', $this->variables['node_voting_period']);
		if ($error)
			return $error;
	}

	function miners_check_my_miner_id_and_votes_0 ($data)
	{
		if ( array_intersect ($data['my_miners_ids'], $data['miners_ids']) && ( $data[ 'votes_0'] > $data['min_miners_keepers'] || $data[ 'votes_0'] == sizeof($data['miners_ids']) ) )
			return true;
		else
			return false;
	}

	function miners_check_votes_1 ($data)
	{
		if ( $data[ 'votes_1'] >= $data['min_miners_keepers'] || $data[ 'votes_1'] == sizeof($data['miners_ids']) /*|| $this->tx_data['user_id'] == 1*/ )
			return true;
		else
			return false;
	}

	// 30
	function votes_node_new_miner()
	{
		/*$my_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id` FROM `".DB_PREFIX."{$this->my_prefix}my_table`", 'fetch_one');*/

		$votes_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`,
							 `votes_start_time`,
							 `votes_0`,
							 `votes_1`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				",	'fetch_array');

		$miners_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`,
							 `log_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$votes_data['user_id']}
				LIMIT 1
				",	'fetch_array');
		// $votes_data['user_id'] - это юзер, за которого голосуют

		// прибавим голос
		$votes_data[ 'votes_'.$this->tx_data['result'] ]++;

		// обновляем голоса. При откате просто вычитаем
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_{$this->tx_data['result']}` = {$votes_data[ 'votes_'.$this->tx_data['result'] ]}
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				");

		// логируем, чтобы юзер {$this->tx_data['user_id']} не смог повторно проголосовать
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_votes` (
						`user_id`,
						`voting_id`,
						`type`
					)
					VALUES (
						{$this->tx_data['user_id']},
						{$this->tx_data['vote_id']},
						'votes_miners'
					)");
		

		// ID майнеров, у которых сохраняются фотки
		$miners_ids = $this->get_miners_keepers( $miners_data['photo_block_id'], $miners_data['photo_max_miner_id'], $miners_data['miners_keepers'], true );

		// данные для проверки окончания голосования
		$data['my_miners_ids'] = $this->get_my_miners_ids();
		$data['miners_ids'] = $miners_ids;
		$data['votes_0'] = $votes_data[ 'votes_0'];
		$data['votes_1'] = $votes_data[ 'votes_1'];
		$data['min_miners_keepers'] = $this->variables['min_miners_keepers'];

		debug_print($data,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ( $this->miners_check_votes_1($data) || $this->miners_check_my_miner_id_and_votes_0 ($data) ) {

			// отмечаем, что голосование нодов закончено
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."votes_miners`
					SET `votes_end` = 1,
						   `end_block_id` = {$this->block_data['block_id']}
					WHERE `id` = {$this->tx_data['vote_id']}
					LIMIT 1
					");

			// отметим del_block_id всем, кто голосовал за данного юзера,
			// чтобы через N блоков по крону удалить бесполезные записи
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."log_votes`
					SET `del_block_id` = {$this->block_data['block_id']}
					WHERE  `voting_id` = {$this->tx_data['vote_id']} AND
								 `type` = 'votes_miners'
					");

		}
		// если набрано >=X голосов "за", то пишем в БД, что юзер готов к проверке людьми
		// либо если набранное кол-во голосов= кол-ву майнеров (актуально в самом начале запуска проекта)
		if ( $this->miners_check_votes_1($data) ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."votes_miners` (
						`user_id`,
						`type`,
						`votes_start_time`
					) VALUES (
						{$votes_data['user_id']},
						'user_voting',
						{$this->block_data['time']}
					)");

			// и отмечаем лицо как готовое участвовать в поиске дублей
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."faces`
					SET `status` = 'used'
					WHERE `user_id` = {$votes_data['user_id']}
					LIMIT 1
					");

		} // если набрано >5 голосов "против" и мы среди тех X майнеров, которые копировали фото к себе
		  // либо если набранное кол-во голосов = кол-ву майнеров (актуально в самом начале запуска проекта)
		else if ( $this->miners_check_my_miner_id_and_votes_0 ($data) ) {

			$profile_path = ABSPATH."public/profile_{$votes_data['user_id']}.jpg";
			$face_path = ABSPATH."public/face_{$votes_data['user_id']}.jpg";

			// возможно фото к нам не было скопировано, т.к. хост был недоступен.
			if ( !file_exists($profile_path) || !file_exists($face_path)) {

				$face_rand_name = '';
				$profile_rand_name = '';

			}
			else {

				do {
					$profile_rand_name = hash('sha256', mt_rand().mt_rand().mt_rand().mt_rand());
					$profile_rand_path = ABSPATH."recycle_bin/".$profile_rand_name;
				} while (file_exists ($profile_rand_path));

				do {
					$face_rand_name = hash('sha256', mt_rand().mt_rand().mt_rand().mt_rand());
					$face_rand_path = ABSPATH."recycle_bin/".$face_rand_name;
				} while (file_exists ($face_rand_path));

				// перемещаем фото в корзину, откуда по крону будем удалять данные
				copy( $profile_path,   ABSPATH."recycle_bin/".$profile_rand_name); unlink( $profile_path );
				copy( $face_path,  ABSPATH."recycle_bin/".$face_rand_name); unlink( $face_path );

			}

			// если в корзине что-то есть, то логируем
			// отсутствие файлов также логируем, т.к. больше негде, а при откате эти данные очень важны.
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."recycle_bin`
					WHERE `user_id` = {$votes_data['user_id']}
					LIMIT 1
					",	'fetch_array');

			if ($log_data) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_recycle_bin` (
								`user_id`,
								`profile_file_name`,
								`face_file_name`,
								`block_id`,
								`prev_log_id`
							)
							VALUES (
								{$log_data['user_id']},
								'{$log_data['profile_file_name']}',
								'{$log_data['face_file_name']}',
								{$this->block_data['block_id']},
								{$log_data['log_id']}
							)");
				$log_id = $this->db->getInsertId();

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."recycle_bin`
						SET  `profile_file_name` = '{$profile_rand_name}',
						        `face_file_name` = '{$face_rand_name}',
						        `log_id` = {$log_id}
						WHERE `user_id` = {$log_data['user_id']}
						LIMIT 1
						");
			}
			else {

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."recycle_bin` (
							`user_id`,
							`profile_file_name`,
							`face_file_name`
						)
						VALUES (
							{$votes_data['user_id']},
							'{$profile_rand_name}',
							'{$face_rand_name}'
						)");

			}
		}
	}

	// 30
	function votes_node_new_miner_rollback_front()
	{
		$this -> limit_requests_rollback('votes_nodes');
	}

	// 30
	function votes_node_new_miner_rollback()
	{
		/*$my_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`
				FROM `".DB_PREFIX."{$this->my_prefix}my_table`
				", 'fetch_one');*/

		$votes_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`,
							 `votes_start_time`,
							 `votes_0`,
							 `votes_1`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				",	'fetch_array');

		$miners_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `photo_block_id`,
							 `photo_max_miner_id`,
							 `miners_keepers`,
							 `log_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$votes_data['user_id']}
				LIMIT 1
				",	'fetch_array');

		// запомним голоса- пригодится чуть ниже
		$data['votes_0'] = $votes_data[ 'votes_0'];
		$data['votes_1'] = $votes_data[ 'votes_1'];

		// вычтем  голос
		$votes_data[ 'votes_'.$this->tx_data['result'] ]--;

		// обновляем голоса
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_{$this->tx_data['result']}` = {$votes_data[ 'votes_'.$this->tx_data['result'] ]}
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				");

		// удаляем нашу запись из log_votes
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_votes`
					WHERE `user_id` = {$this->tx_data['user_id']} AND
								 `voting_id` = {$this->tx_data['vote_id']} AND
								 `type` = 'votes_miners'
					LIMIT 1
					");

		$miners_ids = $this->get_miners_keepers( $miners_data['photo_block_id'], $miners_data['photo_max_miner_id'], $miners_data['miners_keepers'], true );

		// данные для проверки окончания голосования
		$data['my_miners_ids'] = $this->get_my_miners_ids();
		$data['miners_ids'] = $miners_ids;
		$data['min_miners_keepers'] = $this->variables['min_miners_keepers'];
		debug_print($data,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ( $this->miners_check_votes_1($data) ||  $this->miners_check_my_miner_id_and_votes_0 ($data) ) {

			// отменяем отметку о том, что голосование нодов закончено
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."votes_miners`
					SET `votes_end` = 0,
						   `end_block_id` = 0
					WHERE `id` = {$this->tx_data['vote_id']}
					LIMIT 1
					");

			// всем, кому ставили del_block_id, его убираем, т.е. отменяем будущее удаление по крону
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."log_votes`
					SET `del_block_id` = 0
					WHERE  `voting_id` = {$this->tx_data['vote_id']} AND
								 `type` = 'votes_miners' AND
								 `del_block_id` = {$this->block_data['block_id']}
					");

		}

		// если набрано >=5 голосов, то отменяем  в БД, что юзер готов к проверке людьми
		if ( $this->miners_check_votes_1($data) ) {

			// отменяем созданное юзерское голосование
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."votes_miners`
					WHERE `user_id` = {$votes_data['user_id']} AND
								 `votes_start_time` = {$this->block_data['time']} AND
								 `type` = 'user_voting'
					LIMIT 1
					");
			$this->rollbackAI('votes_miners');

			// и отмечаем лицо как неучаствующее в поиске клонов
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."faces`
					SET `status` = 'pending'
					WHERE `user_id` = {$votes_data['user_id']}
					LIMIT 1
					");
		}
		// если фото плохое и мы среди тех 10 майнеров, которые копировали (или нет) фото к себе,
		// а затем переместили фото в корзину
		else if ( $this->miners_check_my_miner_id_and_votes_0 ($data) ) {

			// получаем rand_name из логов
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `profile_file_name`,
								 `face_file_name`
					FROM `".DB_PREFIX."recycle_bin`
					WHERE `user_id` = {$votes_data['user_id']}
					LIMIT 1
					", 'fetch_array');

			// перемещаем фото из корзины, если есть, что перемещать
			if ( $data['profile_file_name'] && $data['face_file_name'] ) {

				$profile_path = ABSPATH."public/profile_{$votes_data['user_id']}.jpg";
				$face_path = ABSPATH."public/face_{$votes_data['user_id']}.jpg";
				$profile_rand_path =  ABSPATH."recycle_bin/".$data['profile_file_name'];
				$face_rand_path =   ABSPATH."recycle_bin/".$data['face_file_name'];
				copy( $profile_rand_path,  $profile_path ); unlink( $profile_rand_path );
				copy( $face_rand_path, $face_path ); unlink( $face_rand_path );
			}
			// откатываем таблу recycle_bin
			$this->general_rollback('recycle_bin', $votes_data['user_id']);
		}
	}

	// 2
	function new_miner_init()
	{
		$error = $this->get_tx_data(array('race', 'country', 'latitude', 'longitude', 'host', 'face_coords', 'profile_coords', 'face_hash', 'profile_hash', 'video_type', 'video_url_id', 'node_public_key', 'sign'));
		if ($error) return $error;
		$this->tx_data['node_public_key'] = bin2hex($this->tx_data['node_public_key']);
		//$this->variables = self::get_variables($this->db,  array('miner_votes_attempt', 'miners_keepers', 'limit_new_miner', 'limit_new_miner_period') );
		$this->variables = self::get_all_variables($this->db);
	}

	// 2
	function new_miner_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// получим кол-во точек для face и profile
		$example_spots = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `example_spots`
				FROM `".DB_PREFIX."spots_compatibility`
				", 'fetch_one');
		$example_spots = json_decode($example_spots, true);
		$count_spots['face'] = sizeof($example_spots['face']);
		$count_spots['profile'] = sizeof($example_spots['profile']);

		if ( !check_input_data ($this->tx_data['race'], 'race') )
			return 'race';
		if ( !check_input_data ($this->tx_data['country'], 'country') )
			return 'country ('.$this->tx_data['country'].')';
		if ( !check_input_data ($this->tx_data['latitude'], 'coordinate') )
			return 'latitude';
		if ( !check_input_data ($this->tx_data['longitude'], 'coordinate') )
			return 'longitude';
		if ( !check_input_data ($this->tx_data['host'], 'host') )
			return 'host';
		if ( !check_input_data ($this->tx_data['face_coords'], 'coords',  $count_spots['face']-1) )
			return 'face_coords';
		if ( !check_input_data ($this->tx_data['profile_coords'], 'coords',  $count_spots['profile']-1) )
			return 'profile_coords';
		if ( !check_input_data ($this->tx_data['face_hash'], 'photo_hash') )
			return 'face_hash';
		if ( !check_input_data ($this->tx_data['profile_hash'], 'photo_hash') )
			return 'profile_hash';
		if ( !check_input_data ($this->tx_data['video_type'], 'video_type') )
			return 'video_type';
		if ( !check_input_data ($this->tx_data['video_url_id'], 'video_url_id') )
			return 'video_url_id';
		if ( !check_input_data ($this->tx_data['node_public_key'], 'public_key') )
			return 'node_public_key';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type'] },{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['race']},{$this->tx_data['country']},{$this->tx_data['latitude']},{$this->tx_data['longitude']},{$this->tx_data['host']},{$this->tx_data['face_hash']},{$this->tx_data['profile_hash']},{$this->tx_data['face_coords']},{$this->tx_data['profile_coords']},{$this->tx_data['video_type']},{$this->tx_data['video_url_id']},{$this->tx_data['node_public_key']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// проверим, не кончились ли попытки стать майнером у данного юзера
		$num = $this->count_miner_attempt($this->db, $this->tx_data['user_id'], 'user_voting');
		if ( $num >= $this->variables['miner_votes_attempt'] )
			return 'miner_votes_attempt';

		//  на всякий случай не даем начать нодовское, если идет юзерское голосование
		$user_voting = $this ->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							`type` = 'user_voting' AND
							`votes_end` = 0
				", 'fetch_one');
		if ($user_voting)
			return 'existing $user_voting';

		// проверим, не является ли юзер майнером и  не разжалованный ли это бывший майнер
		$miner_status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `status` IN ('miner','passive_miner','suspended_miner')
				", 'fetch_one');
		if ( !empty($miner_status) )
			return 'bad miner status';

		// разрешен 1 запрос за сутки
		$error = $this -> limit_requests($this->variables['limit_new_miner'], 'new_miner', $this->variables['limit_new_miner_period']);
		if ($error)
			return $error;

	}

	// 2
	function new_miner()
	{
		// получим массив майнеров, которые должны скопировать к себе 2 фото лица юзера
		$max_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`miner_id`)
				FROM `".DB_PREFIX."miners`
				", 'fetch_one');
		//$miners_ids = $this->get_miners_keepers($this->block_data['block_id'], $max_miner_id, $this->variables['miners_keepers']);

		/*$my_miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`
				FROM `".DB_PREFIX."{$this->my_prefix}my_table`
				", 'fetch_one');*/

		// т.к. у юзера это может быть не первая попытка стать майнером, то отменяем голосования по всем предыдущим
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_end` = 1,
					   `end_block_id` = {$this->block_data['block_id']}
				WHERE `user_id` = {$this->tx_data['user_id']} AND
				             `type` = 'node_voting' AND
				             `end_block_id` = 0 AND
				             `votes_end` = 0
				");

		// создаем новое голосование для нодов
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."votes_miners` (
					`type`,
					`user_id`,
					`votes_start_time`
				)
				VALUES (
					'node_voting',
					{$this->tx_data['user_id']},
					{$this->block_data['time']}
				)");


		// переведем все координаты в отрезки.

		$face_coords = json_decode($this->tx_data['face_coords'], true);
		array_unshift($face_coords, 0);

		// главный отрезок - $line[0] принимается за 1
		$face_relations[0] = $this->pp_length($face_coords[1], $face_coords[2]);

		// получим отрезки
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					SELECT `segments`,
								 `version`
					FROM `".DB_PREFIX."spots_compatibility`
					", 'fetch_array');

		$segments = $data['segments'];
		$spots_version = $data['version'];
		$segments = json_decode( $segments , true);

		foreach ( $segments['face'] as $num => $spots ) {
			// 1. ширина головы
			// 2. глаз-нос
			// 3. нос-губа
			// 4. губа-подбородок
			// 5. ширина челюсти
			$face_relations[$num] = round ( ( $this->pp_length($face_coords[$spots[0]], $face_coords[$spots[1]]) / $face_relations[0]) , 4);
		}

		//print_R($line);
		$face_relations[0] = 1;

		// переведем все координаты в отрезки.
		$profile_coords = json_decode($this->tx_data['profile_coords'], true);
		array_unshift($profile_coords, 0);

		// главный отрезок - $line[0] принимается за 1
		$profile_relations[0] = $this->pp_length($profile_coords[1], $profile_coords[2]);

		foreach ( $segments['profile'] as $num => $spots ) {
			// 1. край уха - край носа
			// 2. глаз - край носа
			// 3. подбородок - низ уха
			// 4. верх уха - низ уха
			$profile_relations[$num] = round ( ( $this->pp_length($profile_coords[$spots[0]], $profile_coords[$spots[1]]) / $profile_relations[0]) , 4);
		}

		$profile_relations[0] = 1;

		$add_sql = array();
		$add_sql['names'] = '';
		$add_sql['values'] = '';
		$add_sql['upd'] = '';
		for ($j=1; $j<sizeof($face_relations); $j++) {
			$add_sql['names']  .= "f{$j},\n";
			$add_sql['values'] .= "'{$face_relations[$j]}',\n";
			$add_sql['upd'] .= "f{$j}='{$face_relations[$j]}',\n";
		}
		for ($j=1; $j<sizeof($profile_relations); $j++) {
			$add_sql['names']  .= "p{$j},\n";
			$add_sql['values'] .= "'{$profile_relations[$j]}',\n";
			$add_sql['upd'] .= "p{$j}='{$profile_relations[$j]}',\n";
		}

		$add_sql['names'] = substr($add_sql['names'], 0, strlen($add_sql['names'])-2);
		$add_sql['values'] = substr($add_sql['values'], 0, strlen($add_sql['values'])-2);
		$add_sql['upd'] = substr($add_sql['upd'], 0, strlen($add_sql['upd'])-2);

		############# Для откатов
		// проверим, есть ли в БД запись, которую нужно залогировать
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
		        FROM `".DB_PREFIX."faces`
		        WHERE `user_id` = {$this->tx_data['user_id'] }
		        LIMIT 1
		        ", 'fetch_array');

		if ( isset($log_data) ) {

			$add_sql1='';
			$add_sql2='';
			for ( $i=1; $i<=20; $i++ ) {
				$add_sql1.='`f'.$i.'`, ';
				$add_sql2.=$log_data['f'.$i].',';
			}
			for ( $i=1; $i<=20; $i++ ) {
				$add_sql1.='`p'.$i.'`, ';
				$add_sql2.=$log_data['p'.$i].',';
			}

			// лог для откатов
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_faces` (
						`user_id`,
						`version`,
						`status`,
						`race`,
						`country`,
						{$add_sql1}
						`prev_log_id`,
						`block_id`
					)
					VALUES (
						{$log_data['user_id']},
						{$log_data['version']},
						'{$log_data['status']}',
						{$log_data['race']},
						{$log_data['country']},
						{$add_sql2}
						{$log_data['log_id']},
						{$this->block_data['block_id']}
					)");
			$log_id = $this->db->getInsertId();

			// обновляем сами данные
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."faces`
					SET
						{$add_sql['upd']},
						`version` = {$spots_version},
						`race` = {$this->tx_data['race']},
						`country` = {$this->tx_data['country']},
						`log_id` = {$log_id}
					WHERE `user_id` = {$this->tx_data['user_id']}
					");
		}
		else {

			// это первая запись в таблицу, и лог писать не с чего
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."faces` (
						`user_id`,
						`version`,
						`race`,
						`country`,
						{$add_sql['names']}
					) VALUES (
						{$this->tx_data['user_id']},
						{$spots_version},
						{$this->tx_data['race']},
						{$this->tx_data['country']},
						{$add_sql['values']}
					)");
		}

		// проверим, есть ли в БД запись, которую надо залогировать
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
		        FROM `".DB_PREFIX."miners_data`
		        WHERE `user_id` = {$this->tx_data['user_id'] }
		        LIMIT 1
		        ", 'fetch_array');
		if ( $log_data ) {

			$log_data['node_public_key'] = bin2hex($log_data['node_public_key']);
			// лог для откатов
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_miners_data` (
						`user_id`,
						`miner_id`,
						`status`,
						`node_public_key`,
						`face_hash`,
						`profile_hash`,
						`photo_block_id`,
						`photo_max_miner_id`,
						`miners_keepers`,
						`face_coords`,
						`profile_coords`,
						`video_type`,
						`video_url_id`,
						`host`,
						`latitude`,
						`longitude`,
						`country`,
						`block_id`,
						`prev_log_id`
					) VALUES (
						{$log_data['user_id']},
						{$log_data['miner_id']},
						'{$log_data['status']}',
						0x{$log_data['node_public_key']},
						'{$log_data['face_hash']}',
						'{$log_data['profile_hash']}',
						'{$log_data['photo_block_id']}',
						'{$log_data['photo_max_miner_id']}',
						'{$log_data['miners_keepers']}',
						'{$log_data['face_coords']}',
						'{$log_data['profile_coords']}',
						'{$log_data['video_type']}',
						'{$log_data['video_url_id']}',
						'{$log_data['host']}',
						'{$log_data['latitude']}',
						'{$log_data['longitude']}',
						{$log_data['country']},
						{$this->block_data['block_id']},
						{$log_data['log_id']}
					) ");
			$log_id = $this->db->getInsertId();

			// обновляем таблу
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners_data`
					SET
						`node_public_key` = 0x{$this->tx_data['node_public_key']},
						`face_hash` = '{$this->tx_data['face_hash']}',
						`profile_hash` = '{$this->tx_data['profile_hash']}',
						`photo_block_id` = {$this->block_data['block_id']},
						`photo_max_miner_id` = {$max_miner_id},
						`miners_keepers` = {$this->variables['miners_keepers']},
						`face_coords` = '{$this->tx_data['face_coords']}',
						`profile_coords` = '{$this->tx_data['profile_coords']}',
						`video_type` = '{$this->tx_data['video_type']}',
						`video_url_id` = '{$this->tx_data['video_url_id']}',
						`latitude` = '{$this->tx_data['latitude']}',
						`longitude` = '{$this->tx_data['longitude']}',
						`country` = {$this->tx_data['country']},
						`host` = '{$this->tx_data['host']}',
						`log_id` = {$log_id}
					WHERE `user_id` = {$this->tx_data['user_id']}
				");

		}
		else {

			// это первая запись в таблицу, и лог писать не с чего
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."miners_data` (
						`user_id`,
						`node_public_key`,
						`face_hash`,
						`profile_hash`,
						`photo_block_id`,
						`photo_max_miner_id`,
						`miners_keepers`,
						`face_coords`,
						`profile_coords`,
						`video_type`,
						`video_url_id`,
						`latitude`,
						`longitude`,
						`country`,
						`host`
				) VALUES (
						{$this->tx_data['user_id']},
						0x{$this->tx_data['node_public_key']},
						'{$this->tx_data['face_hash']}',
						'{$this->tx_data['profile_hash']}',
						{$this->block_data['block_id']},
						{$max_miner_id},
						{$this->variables['miners_keepers']},
						'{$this->tx_data['face_coords']}',
						'{$this->tx_data['profile_coords']}',
						'{$this->tx_data['video_type']}',
						'{$this->tx_data['video_url_id']}',
						'{$this->tx_data['latitude']}',
						'{$this->tx_data['longitude']}',
						{$this->tx_data['country']},
						'{$this->tx_data['host']}'
				)");
		}

		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_node_keys`
					SET  `block_id` = {$this->block_data['block_id']}
					WHERE `public_key` = 0x{$this->tx_data['node_public_key']}
					LIMIT 1
					");
		}
	}

	// 1
	function new_user_init()
	{
		$error = $this->get_tx_data(array('public_key', 'sign'));
		if ($error) return $error;
		$this->tx_data['public_key_hex'] = bin2hex($this->tx_data['public_key']);
		//$this->variables = self::get_variables($this->db,  array('limit_new_user', 'limit_new_user_period') );
		$this->variables = self::get_all_variables($this->db);
	}

	function check_miner_newbie()
	{
		if (isset($this->block_data['time']))
			$time = $this->block_data['time'];
		else
			$time = $this->tx_data['time'];

		$reg_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `reg_time`
		        FROM `".DB_PREFIX."miners_data`
		        WHERE `user_id` = {$this->tx_data['user_id']}
		        LIMIT 1
		        ", 'fetch_one');
		if (!isset($this->block_data['block_id']) || isset($this->block_data['block_id']) && $this->block_data['block_id'] > 29047)
			if ( $reg_time > ($time - $this->variables['miner_newbie_time']) && $this->tx_data['user_id'] != 1)
				return "error miner_newbie ({$reg_time} > {$time}-{$this->variables['miner_newbie_time']})";
	}

	// 1
	function new_user_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if ( !$this->check_miner($this->tx_data['user_id']) )
			return 'only for miners';

		// прошло ли 30 дней с момента регистрации майнера
		$error = $this->check_miner_newbie();
		if ($error)
			return $error;

		// чтобы не записали слишком мелкий или слишком крупный ключ
		if ( !check_input_data ($this->tx_data['public_key_hex'], 'public_key') )
			return 'error public_key ('.strlen($this->tx_data['public_key']).')';

		// публичный ключ должен быть без паролей
		if (preg_match('#DEK-Info: (.+),(.+)#', $this->tx_data['public_key'], $matches)) {
			return 'public_key';
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['public_key_hex']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// один ключ не может быть у двух юзеров
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`user_id`)
				FROM `".DB_PREFIX."users`
				WHERE `public_key_0` = 0x{$this->tx_data['public_key_hex']} OR `public_key_1` = 0x{$this->tx_data['public_key_hex']}  OR `public_key_2` = 0x{$this->tx_data['public_key_hex']}
				LIMIT 1
				", 'fetch_one' );
		if ( $num > 0 ) {
			return 'exists public_key';
		}

		if ($this->tx_data['user_id'] == 1)
			$error = $this -> limit_requests( 1000, 'new_user', 86400 );
		else
			$error = $this -> limit_requests( $this->variables['limit_new_user'], 'new_user', $this->variables['limit_new_user_period'] );
		if ($error)
			return $error;

	}

	// 1
	function new_user()
	{
		// пишем в БД нового юзера
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."users` (
						`public_key_0`,
						`referral`
				)
				VALUES (
					0x{$this->tx_data['public_key_hex']},
					{$this->tx_data['user_id']}
				)");

		// новый user_id
		$new_user_id = $this->db->getInsertId();

		// если работаем в режиме пула, то ищем тех, у кого еще нет user_id
		$community = get_community_users($this->db);
		if ($community) {
			for ($i=0; $i<sizeof($community); $i++) {

				$my_prefix = $community[$i].'_';
				$my_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."{$my_prefix}my_table`
						LIMIT 1
						", 'fetch_one' );

				if (!$my_user_id) {

					// проверим, не наш ли это public_key, чтобы записать полученный user_id в my_table
					$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT `public_key`
							FROM `".DB_PREFIX."{$my_prefix}my_keys`
							WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
							LIMIT 1
							", 'fetch_one' );
					if ( $my_public_key ) {

						// теперь у нас полноценный юзерский акк, и его можно апргрейдить до майнерского
						$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								UPDATE `".DB_PREFIX."{$my_prefix}my_table`
								SET  `user_id` = {$new_user_id},
										`status` = 'user',
										`notification_status` = 0
								");

						$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								UPDATE `".DB_PREFIX."{$my_prefix}my_keys`
								SET  `block_id` = {$this->block_data['block_id']}
								WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
								");
					}
				}
			}
		}
		// если работаем в сингл-режиме
		else {
			// а есть ли у нас свой user_id
			$my_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."my_table`
					", 'fetch_one' );
			if (!$my_user_id) {

				// проверим, не наш ли это public_key, чтобы записать полученный user_id в my_table
				$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `public_key`
						FROM `".DB_PREFIX."my_keys`
						WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
						LIMIT 1
						", 'fetch_one' );
				if ( $my_public_key ) {

					// теперь у нас полноценный юзерский акк, и его можно апргрейдить до майнерского
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."my_table`
							SET  `user_id` = {$new_user_id},
									`status` = 'user',
									`notification_status` = 0
							");

					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."my_keys`
							SET  `block_id` = {$this->block_data['block_id']}
							WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
							");
				}
			}
		}

		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_new_users`
					SET  `status` = 'approved',
							`user_id` = {$new_user_id}
					WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
					");
		}
	}

	// 1
	function new_user_rollback_front()
	{
		$this->limit_requests_rollback('new_user');
	}

	// 1
	function new_user_rollback()
	{
		// если работаем в режиме пула, то ищем тех, у кого записан такой ключ
		$community = get_community_users($this->db);
		if ($community) {
			for ($i=0; $i<sizeof($community); $i++) {

				$my_prefix = $community[$i].'_';
				// проверим, не наш ли это public_key
				$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `public_key`
						FROM `".DB_PREFIX."{$my_prefix}my_keys`
						WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
						LIMIT 1
						", 'fetch_one' );
				if ( $my_public_key ) {

					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_table`
							SET  `user_id` = 0,
									`status` = 'my_pending',
									`notification_status` = 0
							");
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$my_prefix}my_keys`
							SET `block_id` = 0
							WHERE `block_id` = {$this->block_data['block_id']}
							");
				}
			}
		}
		// сингл режим
		else {
			// проверим, не наш ли это public_key
			$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `public_key`
						FROM `".DB_PREFIX."my_keys`
						WHERE `public_key` = 0x{$this->tx_data['public_key_hex']}
						LIMIT 1
						", 'fetch_one' );
			if ( $my_public_key ) {

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_table`
						SET  `user_id` = 0,
								`status` = 'my_pending',
								`notification_status` = 0
						");
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."my_keys`
						SET `block_id` = 0
						WHERE `block_id` = {$this->block_data['block_id']}
						");
			}
		}

		// чистим таблу users
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."users`
				WHERE `public_key_0` = 0x{$this->tx_data['public_key_hex']}
				LIMIT 1
				");

		$this->rollbackAI('users');
	}


	// 24
	function admin_message_init()
	{
		$error = $this->get_tx_data(array('message', 'currency_list', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 24
	function admin_message_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		/*if ( !check_input_data ($this->tx_data['message'] , 'message') )
			return 'admin alert message';*/

		if ( !check_input_data ($this->tx_data['currency_list'] , 'admin_currency_list') )
			return 'admin currency_list';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['message']},{$this->tx_data['currency_list']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 24
	function admin_message()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
		$this->tx_data['message'] = $this->db->escape($this->tx_data['message']);
		$this->tx_data['currency_list'] = $this->db->escape($this->tx_data['currency_list']);
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,  "
				INSERT INTO  `".DB_PREFIX."alert_messages` (
					`message`,
					`currency_list`,
					`block_id`
				)
				VALUES (
					'{$this->tx_data['message']}',
					'{$this->tx_data['currency_list']}',
					{$this->block_data['block_id']}
				)");

	}

	// 24
	function admin_message_rollback_front()
	{

	}

	function admin_message_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,  "
				DELETE FROM `".DB_PREFIX."alert_messages`
				WHERE `block_id` = {$this->block_data['block_id']}
				");
		$AffectedRows = $this->db->getAffectedRows();
		debug_print( '$AffectedRows='.$AffectedRows, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->rollbackAI('alert_messages', $AffectedRows);
	}

	// 19
	function admin_1block_init()
	{
		$error = $this->get_tx_data(array('data', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 19
	function admin_1block_front()
	{
		// public_key админа еще нет, он в этом блоке
	/*	$error = $this -> general_check_admin();
		if ($error)
			return $error;
		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['data']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;*/
	}

	// 19
	function admin_1block_rollback ()
	{
	}

	// 19
	function admin_1block ()
	{
		$data = json_decode($this->tx_data['data'], true);

		foreach ( $data['currency'] as $currency_data ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO  `".DB_PREFIX."currency` (
						`name`,
						`full_name`,
						`max_other_currencies`
					)
					VALUES (
						'{$currency_data[0]}',
						'{$currency_data[1]}',
						{$currency_data[3]}
					)");
			$currency_id = $this->db->getInsertId();
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					INSERT INTO  `".DB_PREFIX."pct` (
						`time`,
						`currency_id`,
						`miner`,
						`user`,
						`block_id`
					)
					VALUES (
						0,
						{$currency_id},
						0,
						0,
						1
					)");
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					INSERT INTO  `".DB_PREFIX."max_promised_amounts` (
						`time`,
						`currency_id`,
						`amount`,
						`block_id`
					)
					VALUES (
						0,
						{$currency_id},
						{$currency_data[2]},
						1
					)");
		}

		foreach( $data['variables'] as $name => $value ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT IGNORE INTO `".DB_PREFIX."variables` (
						`name`,
						`value`
					)
					VALUES (
						'{$name}',
						'{$value}'
					)");

		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO  `".DB_PREFIX."miners_data` (
					`user_id`,
					`miner_id`,
					`status`,
					`node_public_key`,
					`host`,
					`photo_block_id`,
					`photo_max_miner_id`,
					`miners_keepers`
				)
				VALUES (
					1,
					1,
					'miner',
					0x{$data['node_public_key']},
					'{$data['host']}',
					1,
					1,
					1
					)");

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO  `".DB_PREFIX."users` (
					`public_key_0`
				)
				VALUES (
					0x{$data['public_key']}
				)");

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO  `".DB_PREFIX."miners` (
					`miner_id`,
					`active`
				)
				VALUES (
					1,
					1
				)");

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT IGNORE INTO  `".DB_PREFIX."spots_compatibility` (
					`version`,
					`example_spots`,
					`compatibility`,
					`segments`,
					`tolerances`
				)
				VALUES (
					'{$data['spots_compatibility']['version']}',
					'{$data['spots_compatibility']['example_spots']}',
					'{$data['spots_compatibility']['compatibility']}',
					'{$data['spots_compatibility']['segments']}',
					'{$data['spots_compatibility']['tolerances']}'
				)");

	}

	// 24
	function admin_unban_miners_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('users_ids', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	// 24
	function admin_unban_miners_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['users_ids'] , 'users_ids') )
			return ' users_ids';

		// проверим, точно ли были забанены те, кого разбаниваем
		$users_ids = explode(",", $this->tx_data['users_ids'] );
		for ($i=0; $i<sizeof($users_ids); $i++) {

			// не разжалован ли уже майнер
			$status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$users_ids[$i]}
					LIMIT 1
					", 'fetch_one');
			if ( $status != 'suspended_miner' )
				return "bad minrt status ({$users_ids[$i]}={$status})";
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['users_ids']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;
	}

	// 24
	function admin_unban_miners()
	{
		$this->getPct ();

		$users_ids = explode(",", $this->tx_data['users_ids'] );
		for ($i=0; $i<sizeof($users_ids); $i++) {

			// возможно нужно обновить таблицу points_status
			$this->points_update_main($users_ids[$i]);

			$miner_id = $this->ins_or_upd_miners($users_ids[$i]);

			// проверим, не наш ли это user_id
			$this->get_my_user_id($users_ids[$i]);
			if ($users_ids[$i] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id'] ) {
				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
						SET  `status` = 'miner',
								`miner_id` = {$miner_id},
								`notification_status` = 0
						WHERE `status` != 'bad_key'
						LIMIT 1
						");
			}

			// изменение статуса юзера влечет обновление tdc_amount_update
			// все обещанные суммы, по которым делается превращение tdc->DC
			$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `status`,
								 `status_backup`,
								 `tdc_amount_update`,
								 `log_id`
					FROM`".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$users_ids[$i]} AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					ORDER BY `id` ASC
					");
			$new_tdc = 0;
			while ($row = $this->db->fetchArray($res)) {

				// логируем текущее значение
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_promised_amount` (
								`status`,
								`status_backup`,
								`block_id`,
								`tdc_amount_update`,
								`prev_log_id`
							)
							VALUES (
								'{$row['status']}',
								'{$row['status_backup']}',
								{$this->block_data['block_id']},
								{$row['tdc_amount_update']},
								{$row['log_id']}
							)");
				$log_id = $this->db->getInsertId();

                if ($row['log_id']) {
	                $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."promised_amount`
						SET   `status` = '{$row['status_backup']}',
						         `status_backup` = '',
						         `tdc_amount_update` = {$this->block_data['time']},
						         `log_id` = {$log_id}
						WHERE `id` = {$row['id']}
						");
                }
                // если нет log_id, значит promised_amount были добавлены при помощи cash_request_in со статусом suspended уже после того, как было admin_ban_miner
                else {
                    $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						    UPDATE `".DB_PREFIX."promised_amount`
						    SET `status` = 'repaid',
						           `tdc_amount_update` = {$this->block_data['time']}
						    WHERE `id` = {$row['id']}
						    ");
                }
			}
		}
	}

	function admin_unban_miners_rollback_front() {

	}

	function admin_unban_miners_rollback() {

		$users_ids = explode(",", $this->tx_data['users_ids'] );
		for ($i=sizeof($users_ids)-1; $i>=0; $i--) {

			// возможно нужно обновить таблицу points_status
			$this->points_update_rollback_main($users_ids[$i]);

			$miner_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$users_ids[$i]}
					",	'fetch_one');

			// откатываем статус юзера
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."miners_data`
						SET  `status` = 'suspended_miner',
								`miner_id` = 0
				        WHERE `user_id` =  {$users_ids[$i]}
				        ");

			$this->ins_or_upd_miners_rollback($miner_id);

			// проверим, не наш ли это user_id
			$this->get_my_user_id($users_ids[$i]);
			if ($users_ids[$i] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {
				// обновим статус в нашей локальной табле.
				// sms/email не трогаем, т.к. скорее всего данные чуть позже вернутся
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
						SET  `status` = 'suspended_miner',
								`miner_id` = 0
						LIMIT 1
						");
			}

			// Откатываем обещанные суммы в обратном порядке
			$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `log_id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$users_ids[$i]} AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					ORDER BY `id` DESC
					");
			while ($row = $this->db->fetchArray($res)) {

				$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
						FROM `".DB_PREFIX."log_promised_amount`
						WHERE `log_id` = {$row['log_id']}
						", 'fetch_array' );

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."promised_amount`
						SET `status` = '{$log_data['status']}',
							   `status_backup` = '{$log_data['status_backup']}',
							   `tdc_amount_update` = {$log_data['tdc_amount_update']},
							   `log_id` = {$log_data['prev_log_id']}
				        WHERE `id` =  {$row['id']}
				        ");

				// подчищаем _log
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_promised_amount`
						WHERE `log_id` =  {$row['log_id']}
						LIMIT 1
						");
				$this->rollbackAI('log_promised_amount');
			}
		}
	}


	// 20
	function admin_ban_miners_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('users_ids', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	// 20
	function admin_ban_miners_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['users_ids'] , 'users_ids') )
			return ' users_ids';

		// проверим, точно ли были жалобы на тех, кого банит админ
		$users_ids = explode(",", $this->tx_data['users_ids'] );
		for ($i=0; $i<sizeof($users_ids); $i++) {

			$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."abuses`
					WHERE `user_id` = {$users_ids[$i]}
					LIMIT 1
					", 'num_rows');
			if ( !$num )
				return '(admin) bad abuse';

			// не разжалован ли уже майнер
			$status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$users_ids[$i]}
					LIMIT 1
					", 'fetch_one');
			if ( $status != 'miner' )
				return '(admin) bad miner';
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['users_ids']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;
	}

	// 20
	function admin_ban_miners()
	{
		$this->getPct();
		$this->getMaxPromisedAmount();

		$users_ids = explode(",", $this->tx_data['users_ids'] );
		for ($i=0; $i<sizeof($users_ids); $i++) {

			// возможно нужно обновить таблицу points_status
			$this->points_update_main($users_ids[$i]);

			$user_holidays =  self::getHolidays($users_ids[$i], $this->db);
			$points_status = self::getPointsStatus($users_ids[$i], $this->db, true, $this->variables['points_update_time']);

			// переводим майнера из майнеров в юзеры
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`,
								 `status`,
								 `log_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$users_ids[$i]}
					", 'fetch_array');
			// логируем текущие значения
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_miners_data` (
							`miner_id`,
							`status`,
							`block_id`,
							`prev_log_id`
						)
						VALUES (
							{$data['miner_id']},
							'{$data['status']}',
							{$this->block_data['block_id']},
							{$data['log_id']}
						)");
			$log_id = $this->db->getInsertId();
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners_data`
					SET  `status` = 'suspended_miner',
							`ban_block_id` = {$this->block_data['block_id']},
							`miner_id` = 0,
							`log_id` = {$log_id}
					WHERE `user_id` = {$users_ids[$i]}
					");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners`
					SET `active` = 0
					WHERE `miner_id` = {$data['miner_id']}
					");

			// проверим, не наш ли это user_id
			$this->get_my_user_id($users_ids[$i]);
			if ($users_ids[$i] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id'] ) {
				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
						SET  `status` = 'user',
								`miner_id` = 0,
								`notification_status` = 0
						WHERE `status` != 'bad_key'
						LIMIT 1
						");
			}

			// изменение статуса юзера влечет смену %, а значит нужен пересчет TDC на обещанных суммах
			// все обещанные суммы, по которым делается превращение tdc->DC
			$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `amount`,
								 `currency_id`,
								 `tdc_amount`,
								 `tdc_amount_update`,
								 `start_time`,
								 `status`,
								 `log_id`
					FROM`".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$users_ids[$i]} AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					ORDER BY `id` ASC
					");
			$new_tdc = 0;
			while ($row = $this->db->fetchArray($res)) {

				$new_tdc = $this->get_tdc($row['id'], $users_ids[$i]);
				/*
				if ( $row['status'] == 'repaid') {
					$new_tdc = $row['tdc_amount'] + self::calc_profit ( $row['tdc_amount'], $row['tdc_amount_update'], $this->block_data['time'], $this->pct[$row['currency_id']], $points_status );
				}
				else if ( $row['status'] == 'mining') {
					$new_tdc = $row['tdc_amount'] + self::calc_profit ( $row['amount'] + $row['tdc_amount'], $row['tdc_amount_update'], $this->block_data['time'], $this->pct[$row['currency_id']], $points_status, $user_holidays, $this->max_promised_amounts[$row['currency_id']], $row['currency_id'], $this->get_repaid_amount($row['currency_id'], $users_ids[$i]) );
				}*/

				$add_sql = '';
				if ($row['status'] == 'repaid' ||  $row['status'] == 'mining')
					$add_sql = "`tdc_amount` = {$new_tdc}, `tdc_amount_update` = {$this->block_data['time']},";

				// логируем текущее значение
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_promised_amount` (
								`tdc_amount`,
								`tdc_amount_update`,
								`status`,
								`block_id`,
								`prev_log_id`
							)
							VALUES (
								{$row['tdc_amount']},
								{$row['tdc_amount_update']},
								'{$row['status']}',
								{$this->block_data['block_id']},
								{$row['log_id']}
							)");
				$log_id = $this->db->getInsertId();

				// обновляем TDC и логируем статус
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."promised_amount`
						SET  $add_sql
								`status_backup` = `status`,
								`status` = 'suspended',
								`log_id` = {$log_id}
						WHERE `id` = {$row['id']}
						");
			}
		}
	}

	function admin_ban_miners_rollback_front() {
		
	}

	function admin_ban_miners_rollback() {

		$users_ids = explode(",", $this->tx_data['users_ids'] );
		//print_R($users_ids);
		for ($i=sizeof($users_ids)-1; $i>=0; $i--) {

			// возможно нужно обновить таблицу points_status
			$this->points_update_rollback_main($users_ids[$i]);

			//print 'i='.$i."\n";
			// откатываем статус юзера
			$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `log_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$users_ids[$i]}
					",	'fetch_one');

			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`,
								 `miner_id`,
								 `prev_log_id`
					FROM `".DB_PREFIX."log_miners_data`
					WHERE `log_id` = {$log_id}
					", 'fetch_array' );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners_data`
					SET  `status` = '{$log_data['status']}',
							`miner_id` = {$log_data['miner_id']},
							`log_id` = {$log_data['prev_log_id']},
							`ban_block_id` = 0
				    WHERE `user_id` =  {$users_ids[$i]}
				    ");

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners`
					SET `active` = 1
					WHERE `miner_id` = {$log_data['miner_id']}
					");

			// проверим, не наш ли это user_id
			$this->get_my_user_id($users_ids[$i]);
			if ($users_ids[$i] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {
				// обновим статус в нашей локальной табле.
				// sms/email не трогаем, т.к. скорее всего данные чуть позже вернутся
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
						SET  `status` = '{$log_data['status']}',
								`miner_id` = {$log_data['miner_id']}
						LIMIT 1
						");
			}

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_miners_data`
					WHERE `log_id` =  {$log_id}
					LIMIT 1
					");
			$this->rollbackAI('log_miners_data');

			// Откатываем обещанные суммы в обратном прядке
			$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`,
								 `log_id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$users_ids[$i]} AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					ORDER BY `id` DESC
					");
			while ($row = $this->db->fetchArray($res)) {

				$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
						FROM `".DB_PREFIX."log_promised_amount`
				        WHERE `log_id` = {$row['log_id']}
				        ", 'fetch_array' );

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."promised_amount`
						SET `tdc_amount` = {$log_data['tdc_amount']},
							   `tdc_amount_update` = {$log_data['tdc_amount_update']},
							   `status` = '{$log_data['status']}',
							   `status_backup` = '',
							   `log_id` = {$log_data['prev_log_id']}
				        WHERE `id` =  {$row['id']}
				        ");

				// подчищаем _log
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_promised_amount`
						WHERE `log_id` =  {$row['log_id']}
						LIMIT 1
						");
				$this->rollbackAI('log_promised_amount');
			}
		}
	}

	// 21
	function admin_variables_init()
	{
		$error = $this->get_tx_data(array('variables', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 21
	function admin_variables_front()
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 29047)
			$VARIABLES_COUNT = 71;
		else
			$VARIABLES_COUNT = 72;

		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		//if ( !check_input_data ($this->tx_data['variables'] , 'admin_variables') )
		//	return 'error variables check_input_data';

		$variables = json_decode( $this->tx_data['variables'], true );
		if (!$variables)
			return 'error variables json_decode';

		if (sizeof($variables)!=$VARIABLES_COUNT)
			return 'error variables ('.sizeof($variables).'!='.$VARIABLES_COUNT.')';

		$i=0;
		foreach ( $variables as $name => $value  ) {

			$error_text =  '(admin) bad ';
			// проверим допустимые значения в value. Хотя админу и можно доверять, но лучше перестраховаться.

			switch ($name) {

				case 'alert_error_time' :
				case 'error_time' :
				case 'promised_amount_points' :
				case 'promised_amount_votes_0' :
				case 'promised_amount_votes_1' :
				case 'promised_amount_votes_period' :
				case 'holidays_max' :
				case 'limit_abuses' :
				case 'limit_abuses_period' :
				case 'limit_promised_amount' :
				case 'limit_promised_amount_period' :
				case 'limit_cash_requests_out' :
				case 'limit_cash_requests_out_period' :
				case 'limit_change_geolocation' :
				case 'limit_change_geolocation_period' :
				case 'limit_holidays' :
				case 'limit_holidays_period' :
				case 'limit_message_to_admin' :
				case 'limit_message_to_admin_period' :
				case 'limit_mining' :
				case 'limit_mining_period' :
				case 'limit_node_key' :
				case 'limit_node_key_period' :
				case 'limit_primary_key' :
				case 'limit_primary_key_period' :
				case 'limit_votes_miners' :
				case 'limit_votes_miners_period' :
				case 'limit_votes_complex' :
				case 'limit_votes_complex_period' :
				case 'limit_commission' :
				case 'limit_commission_period' :
				case 'limit_new_miner' :
				case 'limit_new_miner_period' :
				case 'limit_new_user' :
				case 'limit_new_user_period' :
				case 'max_block_size' :
				case 'max_block_user_transactions' :
				case 'max_day_votes' :
				case 'max_tx_count' :
				case 'max_tx_size' :
				case 'max_user_transactions' :
				case 'miners_keepers' :
				case 'miner_points' :
				case 'miner_votes_0' :
				case 'miner_votes_1' :
				case 'miner_votes_attempt' :
				case 'miner_votes_period' :
				case 'mining_votes_0' :
				case 'mining_votes_1' :
				case 'mining_votes_period' :
				case 'min_miners_keepers' :
				case 'node_voting' :
				case 'node_voting_period' :
				case 'rollback_blocks_1' :
				case 'rollback_blocks_2' :
				case 'limit_change_host' :
				case 'limit_change_host_period' :
				case 'min_miners_of_voting' :
				case 'min_hold_time_promise_amount' :
				case 'min_promised_amount' :
				case 'points_update_time' :
				case 'reduction_period' :
				case 'new_pct_period' :
				case 'new_max_promised_amount' :
				case 'new_max_other_currencies' :
				case 'cash_request_time' :
				case 'limit_for_repaid_fix' :
				case 'limit_for_repaid_fix_period' :
				case 'miner_newbie_time' :

					if ( !check_input_data ($value, 'bigint') )
						return $error_text.$name;
					$i++;
					break;

				case 'points_factor' :

					if ( !check_input_data ($value, 'float') )
						return $error_text.$name;
					$i++;
					break;

				case 'system_commission' :

					if ( !check_input_data ($value, 'int') || $value > 15 )
						return $error_text.$name;
					$i++;
					break;

				case 'sleep' :

					if ( !check_input_data ($value, 'sleep_var') )
						return $error_text.$name;
					$i++;
					break;

				default:
					return '(admin) bad variables_name = '.$name;
			}
		}
		if ($i != $VARIABLES_COUNT)
			return '(admin) variables '.$i.'!='.$VARIABLES_COUNT;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['variables']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 21
	function admin_variables_rollback() {

		// данные, которые восстановим
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `data`,
				             `log_id`
				FROM `".DB_PREFIX."log_variables`
				ORDER BY `log_id` DESC
				LIMIT 1
				", 'fetch_array' );

		$variables = json_decode( $log_data['data'], true );
		debug_print($variables, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		foreach ( $variables as $name=>$value ) {

			if ($name=='sleep')
				$value = json_encode($value);

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."variables`
					SET `value` = '{$value}'
				    WHERE `name` = '{$name}'
				    ");
		}

		// подчищаем _log
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_variables`
				WHERE `log_id` = {$log_data['log_id']}
				LIMIT 1
				");
		$this->rollbackAI('log_variables');

	}

	function admin_variables_rollback_front ()
	{
	}

	// 21
	function admin_variables()
	{
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT *
				FROM `".DB_PREFIX."variables`
				", 'list', array('name', 'value'));
		$log_data['sleep'] = json_decode($log_data['sleep'], true);
		debug_print($log_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$log_data = json_encode($log_data);

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_variables` (
					`data`
				)
				VALUES (
					'{$log_data}'
				)");

		$variables = json_decode( $this->tx_data['variables'], true );

		debug_print($variables, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		foreach ( $variables as $name=>$value ) {

			$exists = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `name`
					FROM `".DB_PREFIX."variables`
				    WHERE `name` = '{$name}'
				   ", 'fetch_one');
			if ($exists) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."variables`
						SET `value` = '{$value}'
					    WHERE `name` = '{$name}'
					   ");
			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."variables` (
							`name`,
							`value`
						)
						VALUES (
							'{$name}',
							'{$value}'
						)");
			}
		}
	}

	// 22
	function admin_spots_init()
	{
		$error = $this->get_tx_data(array('example_spots', 'segments', 'tolerances', 'compatibility', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 22
	function admin_spots_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['example_spots'] , 'example_spots') )
			return 'admin_spots_front example_spots';

		if ( !check_input_data ($this->tx_data['segments'] , 'segments') )
			return 'admin_spots_front segments';

		if ( !check_input_data ($this->tx_data['tolerances'] , 'tolerances') )
			return 'admin_spots_front tolerances';

		if ( !check_input_data ($this->tx_data['compatibility'] , 'compatibility') )
			return 'admin_spots_front compatibility';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['example_spots']},{$this->tx_data['segments']},{$this->tx_data['tolerances']},{$this->tx_data['compatibility']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}


	// 22
	function admin_spots() {

		# логируем текущий набор точек
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SELECT * FROM `".DB_PREFIX."spots_compatibility`", 'fetch_array');

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_spots_compatibility` (
					`version`,
					`example_spots`,
					`compatibility`,
					`segments`,
					`tolerances`,
					`block_id`,
					`prev_log_id`
				)
				VALUES (
					{$log_data['version']},
					'{$log_data['example_spots']}',
					'{$log_data['compatibility']}',
					'{$log_data['segments']}',
					'{$log_data['tolerances']}',
					{$this->block_data['block_id']},
					{$log_data['log_id']}
				)");

		$log_id = $this->db->getInsertId();

		# обновляем данные в рабочих таблицах
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."spots_compatibility`
				SET `version` = `version`+1,
				       `example_spots` = '{$this->tx_data['example_spots']}',
				       `compatibility` = '{$this->tx_data['compatibility']}',
				       `segments` = '{$this->tx_data['segments']}',
				       `tolerances` = '{$this->tx_data['tolerances']}',
				       `log_id` = {$log_id}
				");

	}

	// 22
	function admin_spots_rollback_front() {

	}

	// 22
	function admin_spots_rollback() {

		// получим log_id, по которому можно найти данные, которые были до этого
		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."spots_compatibility`
				LIMIT 1
				", 'fetch_one' );
		//print $this->db->printsql()."\n";
		if ($log_id) {
			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."log_spots_compatibility`
			        WHERE `log_id` = {$log_id}
			        ", 'fetch_array' );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."spots_compatibility`
					SET `version` = '{$log_data['version']}',
					       `example_spots` = '{$log_data['example_spots']}',
					       `compatibility` = '{$log_data['compatibility']}',
					       `segments` = '{$log_data['segments']}',
					       `tolerances` = '{$log_data['tolerances']}',
						   `log_id` = {$log_data['prev_log_id']}
					");
			//print $this->db->printsql()."\n";

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_spots_compatibility`
					WHERE `log_id` = {$log_id}
					LIMIT 1
					");
			$this->rollbackAI('log_spots_compatibility');
		}
	}

	// 41
	function admin_blog_init()
	{
		$error = $this->get_tx_data(array('lng', 'title', 'message', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 41
	function admin_blog_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( strlen($this->tx_data['title'] > 255) )
			return ' title';

		if ( strlen($this->tx_data['message'] >1024*1024) )
			return ' message';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['lng']},{$this->tx_data['title']},{$this->tx_data['message']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 41
	function admin_blog()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO`".DB_PREFIX."admin_blog` (
					`time`,
					`lng`,
					`title`,
					`message`
				)
				VALUES (
					{$this->block_data['time']},
					'{$this->tx_data['lng']}',
					'{$this->tx_data['title']}',
					'{$this->tx_data['message']}'
				)");

	}

	// 41
	function admin_blog_rollback_front() {

	}

	// 41
	function admin_blog_rollback() {

		$this->tx_data['title'] = $this->tx_data['title'];
		$this->tx_data['message'] = $this->tx_data['message'];

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."admin_blog`
				WHERE `time` = {$this->block_data['time']} AND
							 `lng` = '{$this->tx_data['lng']}' AND
							 `title` = '{$this->tx_data['title']}' AND
							 `message` =  '{$this->tx_data['message']}'
				LIMIT 1
				");
		$this->rollbackAI("admin_blog");
	}




	// 39
	function admin_answer_init()
	{
		$error = $this->get_tx_data(array('to_user_id', 'encrypted_message', 'sign'));
		if ($error) return $error;
		$this->tx_data['encrypted_message'] = bin2hex($this->tx_data['encrypted_message']);
		$this->variables = self::get_all_variables($this->db);
	}

	function admin_answer_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( strlen($this->tx_data['encrypted_message']) > 20480 )
			return ' strlen message';

		if ( !check_input_data ($this->tx_data['to_user_id'], 'user_id') )
			return 'bad user_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['to_user_id']},{$this->tx_data['encrypted_message']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	function admin_answer()
	{
		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['to_user_id']);
		if ($this->tx_data['to_user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_admin_messages` (
						`encrypted`,
						`type`,
						`status`
					)
					VALUES (
						0x{$this->tx_data['encrypted_message']},
						'from_admin',
						'approved'
					)");
		}

		// админ
		if ($this->my_user_id == 1) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."_my_admin_messages`
					SET `status` = 'approved'
					WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
								 `status` = 'my_pending'
					");
		}

	}

	function admin_answer_rollback() {

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['to_user_id']);
		if ($this->tx_data['to_user_id'] == $this->my_user_id/* && $this->my_block_id <= $this->block_data['block_id']*/) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$this->my_prefix}my_admin_messages`
					WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
								 `type` = 'from_admin'
					LIMIT 1
					");
			$this->rollbackAI("{$this->my_prefix}my_admin_messages");
		}

		// админ
		if ($this->my_user_id == 1) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."_my_admin_messages`
					SET `status` = 'approved'
					WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
								 `status` = 'my_pending'
					");
		}

	}

	function admin_answer_rollback_front()
	{
	}

	// 38
	function message_to_admin_init()
	{
		$error = $this->get_tx_data(array('encrypted_message', 'sign'));
		if ($error) return $error;
		$this->tx_data['encrypted_message'] = bin2hex($this->tx_data['encrypted_message']);
		//$this->variables = self::get_variables($this->db, array('limit_message_to_admin', 'limit_message_to_admin_period'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 38
	function message_to_admin_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// в бинарном виде проверить можем только размер
		if ( strlen($this->tx_data['encrypted_message']) > 20480 || strlen($this->tx_data['encrypted_message'])==0)
			return ' strlen message';

		// block_id появится только в блоке, если это тр-ия, то будет 0
		if (isset($this->block_data['block_id']))
			$block_id = $this->block_data['block_id'];
		else
			$block_id = 0;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['encrypted_message']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_message_to_admin'], 'message_to_admin', $this->variables['limit_message_to_admin_period'] );
		if ($error)
			return $error;

	}

	// 38
	// пишется только в локальную таблицу юзера-отправителя и админа
	function message_to_admin()
	{
		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			$my_id= $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."{$this->my_prefix}my_admin_messages`
					WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
								 `status` = 'my_pending'
					", 'fetch_one');

			if ($my_id) {
				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_admin_messages`
							SET `status` = 'approved'
							WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
										 `status` = 'my_pending'
							");
			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT IGNORE INTO `".DB_PREFIX."{$this->my_prefix}my_admin_messages` (
								`encrypted`,
								`status`
						) VALUES (
								0x{$this->tx_data['encrypted_message']},
								'approved'
						)");
			}
		}

		// админ
		if ($this->my_user_id == 1) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."_my_admin_messages` (
							`encrypted`,
							`type`,
							`user_id`
						)
						VALUES (
							0x{$this->tx_data['encrypted_message']},
							'from_user',
							{$this->tx_data['user_id']}
						)");
		}

	}

	// 38
	function message_to_admin_rollback_front()
	{
		$this->limit_requests_rollback('message_to_admin');

	}

	// 38
	function message_to_admin_rollback() {

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_admin_messages`
					SET `status` = 'my_pending'
					WHERE `message` = 0x{$this->tx_data['encrypted_message']} AND
								 `status` = 'approved'
					");
		}

		// админ
		if ($this->my_user_id == 1) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."_my_admin_messages`
					WHERE `encrypted` = 0x{$this->tx_data['encrypted_message']} AND
								 `type` = 'from_user' AND
								 `user_id` = {$this->tx_data['user_id']}
					LIMIT 1
					");
			$this->rollbackAI("_my_admin_messages");
		}
	}

	// 37
	// не каждая загруженная версия будет сопровождаться alert-ом. могут быть промежуточные версии.
	function admin_new_version_alert_init()
	{
		$error = $this->get_tx_data(array('soft_type', 'version', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 37
	function admin_new_version_alert_front() {

		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['version'] , 'version') )
			return ' version ('.$this->tx_data['version'].')';

		if ( !check_input_data ($this->tx_data['soft_type'] , 'soft_type') )
			return ' version ('.$this->tx_data['soft_type'].')';

		/*	// текущая версия
			$current_version = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `current_version`
					FROM `".DB_PREFIX."info_block`
					LIMIT 1
					", 'fetch_one' );

			if ($this->tx_data['soft_type']=='php') {
				if (version_compare($this->tx_data['version'], $current_version) !=1)
					return ' version < current';
			}*/

		/*
		 * Для теста обработки старых блоков, содержащих баги
		 * */
		/*
		if ( !isset($this->block_data['block_id']) || $this->block_data['block_id'] > 1500 ) {*/
			// не было ли уже такой же тр-ии
			$alert = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `alert`
					FROM `".DB_PREFIX."new_version`
					WHERE `version` = '{$this->tx_data['version']}'
			        ", 'fetch_one' );
			if ($alert==1 )
				return '$alert==1';
		/*}*/

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['soft_type']},{$this->tx_data['version']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 37
	function admin_new_version_alert() {

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."new_version`
				SET `alert` = 1
				WHERE `version` = '{$this->tx_data['version']}'
				LIMIT 1
		        ");

	}

	// 37
	function admin_new_version_alert_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."new_version`
				SET `alert` = 0
				WHERE `version` = '{$this->tx_data['version']}'
				LIMIT 1
		        ");
	}

	// 37
	function admin_new_version_alert_rollback_front()
	{

	}



	// 36
	function admin_new_version_init()
	{
		$error = $this->get_tx_data(array('soft_type', 'version', 'file', 'format', 'sign'));
		if ($error) return $error;
		/*
		soft_type тип софта, например php/cppwin/cppnix
		version версия, например 0.0.10
		file запакованный файл
		format чем запакован файл или же просто exe
		*/
		$this->variables = self::get_all_variables($this->db);
	}

	// 36
	function admin_new_version_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['version'] , 'version') )
			return ' version';

		if ( !check_input_data ($this->tx_data['soft_type'] , 'soft_type') )
			return ' soft_type';

		/*
		 * Для теста обработки старых блоков, содержащих баги
		 * */
		/*if ( !isset($this->block_data['block_id']) || $this->block_data['block_id'] > 1500 ) {*/
			// не было ли уже такой же тр-ии
			$version = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `version`
				FROM `".DB_PREFIX."new_version`
				WHERE `version` = '{$this->tx_data['version']}'
		        ", 'fetch_one' );
			if ($version)
				return 'exists version';
		/*}*/

		$hash = hash('sha256', $this->tx_data['file']);

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['soft_type']},{$this->tx_data['version']},{$hash},{$this->tx_data['format']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 36
	function admin_new_version()
	{
		file_put_contents( ABSPATH . "public/{$this->tx_data['version']}.{$this->tx_data['format']}", $this->tx_data['file']) ;

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."new_version` (
					`version`
				)
				VALUES (
					'{$this->tx_data['version']}'
				)");
	}

	// 36
	function admin_new_version_rollback_front() {

	}

	function admin_new_version_rollback() {

		unlink( ABSPATH . "public/{$this->tx_data['version']}.zip");

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM`".DB_PREFIX."new_version`
				WHERE `version` = '{$this->tx_data['version']}'
				LIMIT 1
				");
	}

	// 34
	function admin_add_currency_init()
	{
		$error = $this->get_tx_data(array('currency_name','currency_full_name','max_promised_amount','max_other_currencies', 'sign'));
		if ($error) return $error;
	}

	// 34
	function admin_add_currency_front()
	{
		$error = $this -> general_check_admin();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['currency_name'] , 'currency_name') )
			return 'error currency_name ('.$this->tx_data['currency_name'].')';

		if ( !check_input_data ($this->tx_data['currency_full_name'] , 'currency_full_name') )
			return 'error currency_full_name';

		if ( !check_input_data ($this->tx_data['max_promised_amount'] , 'int') )
			return 'error max_promised_amount';

		if ( !check_input_data ($this->tx_data['max_other_currencies'] , 'int') )
			return 'error max_other_currencies';

		// проверим, нет ли уже такой валюты
		$name = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `name`
				FROM `".DB_PREFIX."currency`
				WHERE `name` = '{$this->tx_data['currency_name']}'
				LIMIT 1
				", 'fetch_one');
		if ($name)
			return 'exists currency_name';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_name']},{$this->tx_data['currency_full_name']},{$this->tx_data['max_promised_amount']},{$this->tx_data['max_other_currencies']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	// 34
	function admin_add_currency()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."currency` (
					`name`,
					`full_name`,
					`max_other_currencies`
				)
				VALUES (
					'{$this->tx_data['currency_name']}',
					'{$this->tx_data['currency_full_name']}',
					{$this->tx_data['max_other_currencies']}
				)");

		$currency_id = $this->db->getInsertId();
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."max_promised_amounts` (
					`time`,
					`currency_id`,
					`amount`
				)
				VALUES (
					{$this->block_data['time']},
					{$currency_id},
					{$this->tx_data['max_promised_amount']}
				)");
	}

	// 34
	function admin_add_currency_rollback()
	{
		$currency_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."currency`
				WHERE `name` = '{$this->tx_data['currency_name']}'
				LIMIT 1
				", 'fetch_one');

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."max_promised_amounts`
				WHERE `currency_id` = {$currency_id}
				LIMIT 1
				");
		$this->rollbackAI('max_promised_amounts');

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."currency`
				WHERE `id` = {$currency_id}
				LIMIT 1
				");
		$this->rollbackAI('currency');

	}

	function admin_add_currency_rollback_front()
	{
	}



	//26
	function abuses_init()
	{
		$error = $this->get_tx_data(array('abuses', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db, array('limit_abuses', 'limit_abuses_period'));
		$this->variables = self::get_all_variables($this->db);
	}


	// 26
	function abuses_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// проверим данные абуз
		$abuses = json_decode( $this->tx_data['abuses'], true );
		if (!$abuses)
			return 'bad $abuses';

		if (sizeof($abuses)>100)
			return 'abuses count > 100';

		foreach  ($abuses as $user_id => $comment ) {

			if ( !check_input_data ($user_id, 'user_id') )
				return 'bad abuse user_id';

			// проверим, есть ли такой майнер
			$miner = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$user_id} AND
								 `status` = 'miner'
					", 'fetch_one');
			if (!$miner)
				return 'bad abuse miner';

			if ( !check_input_data ($comment, 'abuse_comment') )
				return 'bad abuse_comment';
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['abuses']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests($this->variables['limit_abuses'], 'abuses', $this->variables['limit_abuses_period']);
		if ($error)
			return $error;
	}

	// 26
	function abuses()
	{
		$abuses = json_decode( $this->tx_data['abuses'], true );
		foreach  ($abuses as $user_id => $comment ) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO  `".DB_PREFIX."abuses` (
					`user_id`,
					`from_user_id`,
					`comment`,
					`time`
				)
				VALUES (
					{$user_id},
					{$this->tx_data['user_id']},
					'{$comment}',
					{$this->block_data['time']}
				)");
			//print $this->db->printsql()."\n";
		}
	}

	// 26
	function abuses_rollback_front () {

		$this->limit_requests_rollback('abuses');

	}

	// 26
	function abuses_rollback ()
	{
		$abuses = json_decode( $this->tx_data['abuses'], true );

		foreach  ($abuses as $user_id => $comment ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM  `".DB_PREFIX."abuses`
					WHERE `user_id` = {$user_id} AND
								 `from_user_id` = {$this->tx_data['user_id']} AND
								 `time` = {$this->block_data['time']}
					LIMIT 1
					");
		}
	}

	// 43
	function change_commission_init()
	{
		$error = $this->get_tx_data(array('commission', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db, array('limit_commission', 'limit_commission_period'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 43
	function change_commission_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if (!$this->check_miner($this->tx_data['user_id']))
			return 'bad miner';

		$commission = json_decode( $this->tx_data['commission'], true );
		if (!$commission)
			return 'bad $commission';

		$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`id`)
					FROM `".DB_PREFIX."currency`
					", 'fetch_one');
		if (sizeof($commission) > $count+1) // +1 - это комиссия для всех CF-валют
			return 'bad currency count';

		foreach  ($commission as $currency_id => $data) {

			debug_print('$currency_id='.$currency_id , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			if ( !check_input_data ($currency_id, 'bigint') )
				return 'bad $currency_id';
			// % от 0 до 10
			if ( !check_input_data ($data[0], 'currency_commission') || $data[0]>10)
				return 'bad pct';
			// минимальная комиссия от 0. При 0% будет = 0
			if ( !check_input_data ($data[1], 'currency_commission') )
				return 'bad currency_min_commission';
			// макс. комиссия. 0 - значит, считается по %
			if ( !check_input_data ($data[2], 'currency_commission') )
				return 'bad currency_max_commission';
			if ($data[1]>$data[2] && $data[2])
				return 'bad currency_max_commission';

			// проверим, существует ли такая валюта в таблице DC-валют
			if ( !$this->checkCurrency($currency_id) ) {
				// если нет, то проверяем список CF-валют
				if ( !$this->checkCurrencyCF($currency_id) )
					return 'error currency_id';
			}
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['commission']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests($this->variables['limit_commission'], 'commission', $this->variables['limit_commission_period']);
		if ($error)
			return $error;
	}

	// 43
	function change_commission () {

		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT * FROM `".DB_PREFIX."commission`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_array');

		// если есть, что логировать, то логируем
		if ( $log_data ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."log_commission` (
							`commission`,
							`block_id`,
							`prev_log_id`
					)
					VALUES (
							'{$log_data['commission']}',
							{$this->block_data['block_id']},
							{$log_data['log_id']}
					)");

			$log_id = $this->db->getInsertId();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."commission`
					SET  `commission` = '{$this->tx_data['commission']}',
							`log_id` = {$log_id}
					WHERE `user_id` = {$this->tx_data['user_id']}
					");
		}
		else {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."commission` (
						`user_id`,
						`commission`
					)
					VALUES (
						{$this->tx_data['user_id']},
						'{$this->tx_data['commission']}'
					)");
		}
	}

	// 43
	function change_commission_rollback_front () {

		$this->limit_requests_rollback('commission');

	}

	// 43
	function change_commission_rollback () {

		$this->general_rollback( 'commission', $this->tx_data['user_id'] );
	}



	// 2
	function new_miner_rollback_front() {

		$this->limit_requests_rollback('new_miner');

	}

	// 2
	function new_miner_rollback() {

		$this->general_rollback( 'faces' , $this->tx_data['user_id'] );

		$this->general_rollback( 'miners_data',  $this->tx_data['user_id']);

		// votes_miners
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_end` = 0,
					   `end_block_id` =  0
				WHERE `end_block_id` = {$this->block_data['block_id']}
				");

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."votes_miners`
				WHERE `type` = 'node_voting' AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `votes_start_time` = {$this->block_data['time']}
				LIMIT 1
				");
		$this->rollbackAI('votes_miners');

		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id ) {

			// проверим, не наш ли тут паблик кей
			$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `public_key`
					FROM `".DB_PREFIX."{$this->my_prefix}my_node_keys`
					WHERE `block_id` = {$this->block_data['block_id']}
					", 'fetch_one' );
			if ( $my_public_key  /*&& $this->my_block_id <= $this->block_data['block_id']*/) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_node_keys`
					SET  `block_id` = 0
					WHERE `block_id` = {$this->block_data['block_id']}
					");
			}
		}
	}
/*
	function general_log ( $table ) {

		// проверим, есть ли в БД запись, которую надо залогировать
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SELECT *
		        FROM `".DB_PREFIX."$table`
		        WHERE `user_id` = {$this->tx_data['user_id'] }
		        LIMIT 1
		        ", 'fetch_array');

		$add_sql = '';
		foreach ( $log_data as $k => $v ) {

			if ( $k == 'log_id' || $k == 'user_id')
				continue;

			$add_sql0.= "`{$k}`,";
			$add_sql1.= "'{$v}',";
			$add_sql2.= "`{$k}`='{$v}',";
		}
		$add_sql0 = substr( $add_sql0, 0, strlen($add_sql0) - 1 );
		$add_sql1 = substr( $add_sql1, 0, strlen($add_sql1) - 1 );
		$add_sql2 = substr( $add_sql2, 0, strlen($add_sql2) - 1 );

		if ( $log_data ) {

			// лог для откатов
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "INSERT INTO `".DB_PREFIX."log_$table` (
						{$add_sql0}
						`prev_log_id`)
					VALUES (
						{$add_sql1}
						{$log_data['log_id']}
					) ");
			$log_id = $this->db->getInsertId();

			// обновляем таблу
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "UPDATE `".DB_PREFIX."miners_data` SET
					{$add_sql2}
					`log_id` = {$log_id}
				WHERE `user_id` = {$this->tx_data['user_id']}
			");
		}
		else {

			// это первая запись в таблицу, и лог писать не с чего
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "INSERT INTO `".DB_PREFIX."miners_data` (
					`votes_start_time`,
					`face_hash`,
					`profile_hash`,
					`face_coords`,
					`profile_coords`,
					`video_type`,
					`video_url_id`,
					`host`
			) VALUES (
					{$this->block_data['time']},
					'{$this->tx_data['face_hash']}',
					'{$this->tx_data['profile_hash']}',
					'{$this->tx_data['face_coords']}',
					'{$this->tx_data['profile_coords']}',
					'{$this->tx_data['video_type']}',
					'{$this->tx_data['video_url_id']}',
					'{$this->tx_data['host']}'
			");
		}
	}
*/

	function limit_requests_rollback ($type) {

		//$time = $this->block_data['time']?$this->block_data['time']:$this->tx_data['time'];
		$time = $this->tx_data['time'];
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM
					`".DB_PREFIX."log_time_{$type}`
		        WHERE
		            `user_id` = {$this->tx_data['user_id']} AND
		            `time` = {$time}
				LIMIT 1
		        ");
		//print $this->db->printsql();

	}

	function limit_requests ($limit, $type, $period=86400) {

		//$time = $this->block_data['time']?$this->block_data['time']:$this->tx_data['time'];
		$time = $this->tx_data['time'];

		debug_print($time , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`time`)
				FROM `".DB_PREFIX."log_time_{$type}`
				WHERE `user_id` = '{$this->tx_data['user_id']}' AND
							 `time` > ".($time - $period)."
				LIMIT 1
				", 'fetch_one' );
		if ( $num >=$limit ) {
			return "[limit_requests] log_time_{$type} {$num} >={$limit}\n";
		}
		else {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO
						`".DB_PREFIX."log_time_{$type}` (
							`user_id`,
							`time`
						)
						VALUES (
							{$this->tx_data['user_id']},
							{$time}
						)");
			

		}
	}

	function checkCurrencyCF($currency_id)
	{
		$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_currency`
				WHERE `id` = {$currency_id}
				LIMIT 1
				", 'fetch_one');
		if ( !$id )
			return false;
		else
			return true;
	}

	function checkCurrency($currency_id)
	{
		$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."currency`
				WHERE `id` = {$currency_id}
				LIMIT 1
				", 'fetch_one');
		if ( !$id ) {
			return false;
		}
		else
			return true;
	}


	// просто смена суммы
	function change_promised_amount_init()
	{
		$error = $this->get_tx_data(array('promised_amount_id', 'amount', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db,  array('limit_promised_amount', 'limit_promised_amount_period', 'points_factor', 'limit_votes_complex_period') );
		$this->variables = self::get_all_variables($this->db);
	}

	function get_max_promised_amount($currency_id) {
		return $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `amount`
						FROM `".DB_PREFIX."max_promised_amounts`
						WHERE `currency_id` = {$currency_id}
						ORDER BY `time` DESC
						LIMIT 1
						", 'fetch_one');
	}

	function get_repaid_amount($currency_id, $user_id)
	{
		return $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'repaid' AND
							 `currency_id` = {$currency_id} AND
							 `user_id` = {$user_id} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'fetch_one');
	}

	function  change_promised_amount_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['promised_amount_id'], 'int') )
			return 'error promised_amount_id';

		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'error amount';

		// юзер должен быть или miner, или passive_miner, т.е. иметь miner_id. не даем майнерам, которых забанил админ, изменять обещанные суммы.
		if ( !$this->check_miner($this->tx_data['user_id']) )
			return 'not miner';

		// верный ли id. менять сумму можно, только когда статус mining
		// нельзя изменить woc (currency_id=1)
		$promised_amount_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`,
							 `currency_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']} AND
							 `status` = 'mining' AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'fetch_array');
		if (!$promised_amount_data['id'])
			return '$promised_amount_id';

		$max_promised_amount = $this->get_max_promised_amount($promised_amount_data['currency_id']);

		// т.к. можно перевести из mining в repaid, где нет лимитов, и так проделать много раз, то
		// нужно жестко лимитировать ОБЩУЮ сумму по всем promised_amount данной валюты
		$repaid_amount = $this->get_repaid_amount($promised_amount_data['currency_id'], $this->tx_data['user_id']);
		if ( $this->tx_data['amount'] + $repaid_amount > $max_promised_amount )
			return "max_promised_amount ( {$this->tx_data['amount']} + {$repaid_amount} > {$max_promised_amount} )";

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['promised_amount_id']},{$this->tx_data['amount']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_promised_amount'], 'promised_amount', $this->variables['limit_promised_amount_period'] );
		if ($error)
			return $error;

	}

	function change_promised_amount()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['user_id']);

		$this->getPct();
		$this->getMaxPromisedAmount();

		// логируем предыдущее
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`,
							 `currency_id`,
							 `amount`,
							 `tdc_amount`,
							 `tdc_amount_update`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				", 'fetch_array');

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_promised_amount` (
						`amount`,
						`tdc_amount`,
						`tdc_amount_update`,
						`block_id`,
						`prev_log_id`
				)
				VALUES (
						{$data['amount']},
						{$data['tdc_amount']},
						{$data['tdc_amount_update']},
						{$this->block_data['block_id']},
						{$data['log_id']}
				)");
		$log_id = $this->db->getInsertId();

		$user_holidays =  self::getHolidays($this->tx_data['user_id'], $this->db);
		$points_status = self::getPointsStatus($this->tx_data['user_id'], $this->db, true, $this->variables['points_update_time']);

		// то, от чего будем вычислять набежавшие %
		$tdc_sum = $data['amount'] + $data['tdc_amount'];

		// то, что успело набежать
		$new_tdc = $data['tdc_amount'] + $this->calc_profit_ ( $tdc_sum, $data['tdc_amount_update'], $this->block_data['time'], $this->pct[$data['currency_id']], $points_status, $user_holidays, $this->max_promised_amounts[$data['currency_id']], $data['currency_id'], $this->get_repaid_amount($data['currency_id'], $this->tx_data['user_id']) );

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET  `amount` = {$this->tx_data['amount']},
						`tdc_amount` = {$new_tdc},
						`tdc_amount_update` = {$this->block_data['time']},
						`log_id` = $log_id
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

	}

	function change_promised_amount_rollback_front() {

		$this->limit_requests_rollback('promised_amount');
	}

	function change_promised_amount_rollback()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['user_id']);

		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				", 'fetch_one' );

		// данные, которые восстановим
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							 `tdc_amount`,
							 `tdc_amount_update`,
							 `prev_log_id`
				FROM `".DB_PREFIX."log_promised_amount`
				WHERE `log_id` = {$log_id}
				", 'fetch_array' );

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
		        SET   `amount` = {$log_data['amount']},
		                `tdc_amount` = {$log_data['tdc_amount']},
		                `tdc_amount_update` = {$log_data['tdc_amount_update']},
		                `log_id` = {$log_data['prev_log_id']}
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

		// подчищаем _log
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_promised_amount`
				WHERE `log_id` = {$log_id}
				LIMIT 1
				");
		$this->rollbackAI('log_promised_amount');
	}


	// 3
	// если из-за смены местоположения или изначально после new_promised_amount получили rejected,
	// то просто шлем новый запрос. возможно был косяк с видео-файлом.
	// Если было delete=1, то перезаписываем
	function new_promised_amount_init()
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 27134)
			$error = $this->get_tx_data(array('currency_id', 'amount', 'video_type', 'video_url_id', 'sign'));
		else
			$error = $this->get_tx_data(array('currency_id', 'amount', 'video_type', 'video_url_id', 'payment_systems_ids', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db,  array('limit_promised_amount', 'limit_promised_amount_period') );
		$this->variables = self::get_all_variables($this->db);
	}

	// 3
	function new_promised_amount_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['currency_id'], 'int') )
			return 'error currency_id';
		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'error amount';
		if ( !check_input_data ($this->tx_data['video_type'], 'video_type') )
			return 'error video_type';
		if ( !check_input_data ($this->tx_data['video_url_id'], 'video_url_id') )
			return 'error video_url_id';

		if (!isset($this->block_data['block_id']) || isset($this->block_data['block_id']) && $this->block_data['block_id'] > 27134)
			if ( !check_input_data ($this->tx_data['payment_systems_ids'], 'payment_systems_ids') )
				return 'error payment_systems_ids';

		// проверим, существует ли такая валюта
		if ( !$this->checkCurrency($this->tx_data['currency_id']) )
			return 'error 2 currency_id';

		// юзер должен быть или miner, или passive_miner, т.е. иметь miner_id. не даем майнерам, которых забанил админ, добавлять новые обещанные суммы.
		if ( !$this->check_miner($this->tx_data['user_id']) )
			return 'not miner';

		// проверим статус. должно  вообще не быть записей. всё, что rejected/change_geo и пр. юзер должен вначале удалить
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`,
							 `currency_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `currency_id` = {$this->tx_data['currency_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 `user_id` = {$this->tx_data['user_id']}
				", 'fetch_array');
		if ($data['status'])
			return '$status='.$data['status'];

		$new_max_promised_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."max_promised_amounts`
				WHERE `currency_id` = {$this->tx_data['currency_id']}
				ORDER BY `time` DESC
				LIMIT 1
				", 'fetch_one');
		debug_print($new_max_promised_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$new_max_other_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `max_other_currencies`
				FROM `".DB_PREFIX."currency`
				WHERE `id` = {$this->tx_data['currency_id']}
				LIMIT 1
				", 'fetch_one');
		debug_print($new_max_other_currencies, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// т.к. можно перевести из mining в repaid, где нет лимитов, и так проделать много раз, то
		// нужно жестко лимитировать ОБЩУЮ сумму по всем promised_amount данной валюты
		if ( $this->tx_data['amount'] + $this->get_repaid_amount($this->tx_data['currency_id'], $this->tx_data['user_id']) > $new_max_promised_amount )
			return 'max_promised_amount';

		// возьмем id всех добавленных валют
		$exists_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				GROUP BY `currency_id`
				", 'array');

		// нельзя добавлять новую валюту, пока не одобрена хотя бы одна, т.е. пока нет WOC
		$woc = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `currency_id` = 1
				", 'fetch_one');
		if (sizeof($exists_currencies) > 0 && !$woc)
			return '!$woc';

		if (sizeof($exists_currencies) > 0) {

			debug_print($exists_currencies, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// можно ли новую валюту иметь с таким кол-вом валют как у нас
			if ( sizeof($exists_currencies) > $new_max_other_currencies )
				return 'max_other_currencies';

			// проверим, можно ли к существующим валютам добавить новую
			foreach ($exists_currencies as $currency_id) {

				$max_other_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `max_other_currencies`
						FROM `".DB_PREFIX."currency`
						WHERE `id` = {$currency_id}
						LIMIT 1
						", 'fetch_one');
				if ( sizeof($exists_currencies) > $max_other_currencies )
					return '$max_other_currencies ('.$currency_id.') :  '.sizeof($exists_currencies).' > '.$max_other_currencies.'';
			}
		}

		//  должно быть geolocation
		$latitude = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `latitude`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one');
		debug_print('$latitude='.$latitude, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if (!$latitude )
			return '!$latitude';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// проверяем подпись
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 27134)
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_id']},{$this->tx_data['amount']},{$this->tx_data['video_type']},{$this->tx_data['video_url_id']}";
		else
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_id']},{$this->tx_data['amount']},{$this->tx_data['video_type']},{$this->tx_data['video_url_id']},{$this->tx_data['payment_systems_ids']}";

		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_promised_amount'], 'promised_amount', $this->variables['limit_promised_amount_period'] );
		if ($error)
			return $error;

	}

	// 3
	function new_promised_amount_rollback()
	{
		// чистим таблу promised_amount
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `amount` = {$this->tx_data['amount']} AND
							 `currency_id` = {$this->tx_data['currency_id']} AND
							 `status` = 'pending' AND
							 `votes_start_time` = {$this->block_data['time']}
				LIMIT 1
				");
		$this->rollbackAI('promised_amount');
	}

	// 3
	function new_promised_amount_rollback_front()
	{
		$this->limit_requests_rollback('promised_amount');
	}

	// 3
	function new_promised_amount()
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 27134) {
			$add_sql_names = '';
			$add_sql_values = '';
		}
		else {
			$payment_systems_ids = explode(',',$this->tx_data['payment_systems_ids']);
			$add_sql_names = '';
			$add_sql_values = '';
			for ($i=0; $i<sizeof($payment_systems_ids); $i++) {
				$add_sql_names .= '`ps'.($i+1).'`,';
				$add_sql_values .= $payment_systems_ids[$i].',';
			}
		}

		//добавляем promised_amount в БД
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."promised_amount` (
						`user_id`,
						`amount`,
						`currency_id`,
						{$add_sql_names}
						`video_type`,
						`video_url_id`,
						`votes_start_time`
					)
					VALUES (
						{$this->tx_data['user_id']},
						{$this->tx_data['amount']},
						{$this->tx_data['currency_id']},
						{$add_sql_values}
						'{$this->tx_data['video_type']}',
						'{$this->tx_data['video_url_id']}',
						{$this->block_data['time']}
					)");

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {
			// Удалим, т.к. попало в блок
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$this->my_prefix}my_promised_amount`
					WHERE `amount` = {$this->tx_data['amount']} AND
						         `currency_id` = {$this->tx_data['currency_id']}
					");
		}
	}

	// 4
	function mining_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('promised_amount_id', 'amount', 'sign'));
		$this->tx_data['amount'] = round($this->tx_data['amount'], 2);
		//$this->variables = self::get_variables( $this->db, array( 'system_commission', 'limit_mining', 'limit_mining_period', 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	function get_tdc ($promised_amount_id, $user_id)
	{
		// используем $this->tx_data['time'], оно всегда меньше времени блока, а значит TDC будет тут чуть меньше. В блоке (не фронт. проверке) уже будет использоваться time из блока
		if (isset($this->block_data['time']))
			$time = $this->block_data['time'];
		else
			$time = $this->tx_data['time'];

		// проверим, набежала ли у юзера нужная сумма
		$this->getPct();
		$this->getMaxPromisedAmount();
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`,
				             `amount`,
				             `currency_id`,
							 `tdc_amount`,
							 `tdc_amount_update`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$promised_amount_id}
				", 'fetch_array');
		//print_R($data);
		debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$points_status = self::getPointsStatus($user_id, $this->db, false, $this->variables['points_update_time']);
		$user_holidays =  self::getHolidays($user_id, $this->db);
		$exists_cash_requests = $this->check_cash_requests ($user_id, $this->db);

		// для WOC майнинг не зависит от неудовлетворенных cash_requests, т.к. WOC юзер никому не обещал отдавать. Также, WOC не бывает repaid
		if ($data['status'] == 'mining' && (!$exists_cash_requests || $data['currency_id']==1))
			$new_tdc = $data['tdc_amount'] + $this->calc_profit_ ( $data['amount']+$data['tdc_amount'], $data['tdc_amount_update'], $time, $this->pct[$data['currency_id']], $points_status, $user_holidays, $this->max_promised_amounts[$data['currency_id']], $data['currency_id'], $this->get_repaid_amount($data['currency_id'], $user_id) );
		else  if ($data['status'] == 'repaid' && !$exists_cash_requests)
			$new_tdc = $data['tdc_amount'] + $this->calc_profit_ ( $data['tdc_amount'], $data['tdc_amount_update'], $time, $this->pct[$data['currency_id']], $points_status );
		else // rejected/change_geo/suspended
			$new_tdc = $data['tdc_amount'];

		return $new_tdc;
	}

	// 4
	function mining_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['promised_amount_id'], 'bigint') )
			return 'tdc_dc_front promised_amount_id';
		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'tdc_dc_front amount';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['promised_amount_id']},{$this->tx_data['amount']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// статус может быть любым кроме pending, т.к. то, что набежало в tdc_amount доступо для перевода на кошелек всегда
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']} AND
							`user_id` = {$this->tx_data['user_id']} AND
							`status` != 'pending' AND
							`del_block_id` = 0 AND
							`del_mining_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if ( $num == 0 )
			return '0 promised_amount for mining';

	    $new_tdc = $this->get_tdc ($this->tx_data['promised_amount_id'], $this->tx_data['user_id']);

        debug_print("new_tdc=$new_tdc", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

        if ($new_tdc < $this->tx_data['amount']+0.01) // запас 0.01 на всяк случай
            return "error amount ({$new_tdc}<{$this->tx_data['amount']}) promised_amount_id={$this->tx_data['promised_amount_id']} \ user_id = {$this->tx_data['user_id']}";

        if ($this->tx_data['amount'] < 0.02)
            return "error amount ({$this->tx_data['amount']}<0.02)";

		// юзер может создавать не более X запросов в день на снятие DC
		$error = $this -> limit_requests( $this->variables['limit_mining'], 'mining', $this->variables['limit_mining_period'] );
		if ($error)
			return $error;
	}

	function get_refs($user_id)
	{
		// получим рефов
		$refs[0] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `referral`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$user_id}
				", 'fetch_one');
		if ($refs[0]) {
			$refs[1] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `referral`
					FROM `".DB_PREFIX."users`
					WHERE `user_id` = {$refs[0]}
					", 'fetch_one');
			if ($refs[1]) {
				$refs[2] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `referral`
						FROM `".DB_PREFIX."users`
						WHERE `user_id` = {$refs[1]}
						", 'fetch_one');
			}
		}
		return $refs;
	}

	/* $del_block_id указывается, когда майнинг происходит как побочный результат удаления обещанной суммы
	 * */
	function mining($del_mining_block_id=0)
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['user_id']);

		$refs = $this->get_refs($this->tx_data['user_id']);
		if (@$refs[0]>0)
			$this->points_update_main($refs[0]);
		if (@$refs[1]>0)
			$this->points_update_main($refs[1]);
		if (@$refs[2]>0)
			$this->points_update_main($refs[2]);

		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`,
				             `amount`,
				             `currency_id`,
							 `tdc_amount`,
							 `tdc_amount_update`,
							 `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				", 'fetch_array');
		$currency_id = $data['currency_id'];

		// логируем текущее значение по обещанным суммам
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_promised_amount` (
						`tdc_amount`,
						`tdc_amount_update`,
						`block_id`,
						`prev_log_id`
					)
					VALUES (
						{$data['tdc_amount']},
						{$data['tdc_amount_update']},
						{$this->block_data['block_id']},
						{$data['log_id']}
					)");
		$log_id = $this->db->getInsertId();

		// возможно, что данный юзер имеет непогашенные cash_requests, значит новые TDC у него не растут, а просто обновляется tdc_amount_update
		$new_tdc = $this->get_tdc ($this->tx_data['promised_amount_id'], $this->tx_data['user_id']);

		// списываем сумму с promised_amount
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET  `tdc_amount` = ".($new_tdc - $this->tx_data['amount']).",
						`tdc_amount_update` = {$this->block_data['time']},
						`del_mining_block_id` = {$del_mining_block_id},
						`log_id` = {$log_id}
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

		// комиссия системы
		$system_commission = round ($this->tx_data['amount'] * ($this->variables['system_commission'] / 100), 2 );

		$system_commission = ($system_commission==0)?0.01:$system_commission;
		if ($system_commission >= $this->tx_data['amount'])
			$system_commission = 0;
		debug_print( '$system_commission='.$system_commission."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		/*
		// то, что остается юзеру за вычетом комиссии
		$amount = $this->tx_data['amount'] - $system_commission;

		// теперь начисляем DC, залогировав предыдущее значение
		$this -> update_recipient_wallet( $this->tx_data['user_id'], $currency_id, $amount, 'from_mining_id', $this->tx_data['promised_amount_id'] );

        // теперь начисляем комиссию системе
        if ($system_commission > 0)
		    $this -> update_recipient_wallet( 1, $currency_id, $system_commission, 'system_commission', $this->tx_data['promised_amount_id'] );
		*/

		// теперь начисляем DC, залогировав предыдущее значение
		$this -> update_recipient_wallet( $this->tx_data['user_id'], $currency_id, $this->tx_data['amount'], 'from_mining_id', $this->tx_data['promised_amount_id'] );

		// теперь начисляем комиссию системе
		if ($system_commission > 0)
			$this -> update_recipient_wallet( 1, $currency_id, $system_commission, 'system_commission', $this->tx_data['promised_amount_id'] );

		// реферальные
		$ref_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."referral`
				", 'fetch_array');
		if (@$refs[0]>0) {
			$ref_amount = round ($this->tx_data['amount'] * ($ref_data['first'] / 100), 2 );
			if ($ref_amount > 0) {
				debug_print( 'refs 1', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this -> update_recipient_wallet( $refs[0], $currency_id, $ref_amount, 'referral', $this->tx_data['promised_amount_id'] );
			}
		}
		if (@$refs[1]>0) {
			$ref_amount = round ($this->tx_data['amount'] * ($ref_data['second'] / 100), 2 );
			if ($ref_amount > 0) {
				debug_print( 'refs 2', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this -> update_recipient_wallet( $refs[1], $currency_id, $ref_amount, 'referral', $this->tx_data['promised_amount_id'] );
			}
		}
		if (@$refs[2]>0) {
			$ref_amount = round ($this->tx_data['amount'] * ($ref_data['third'] / 100), 2 );
			if ($ref_amount > 0) {
				debug_print( 'refs 3', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this -> update_recipient_wallet( $refs[2], $currency_id, $ref_amount, 'referral', $this->tx_data['promised_amount_id'] );
			}
		}
	}

	function mining_rollback_front()
	{
		$this -> limit_requests_rollback( 'mining' );
	}

	function mining_rollback()
    {
        $promised_amount_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				", 'fetch_array');

	    $refs = $this->get_refs($this->tx_data['user_id']);
	    if (@$refs[2]>0)
		    $this->points_update_rollback_main($refs[2]);
	    if (@$refs[1]>0)
		    $this->points_update_rollback_main($refs[1]);
	    if (@$refs[0]>0)
		    $this->points_update_rollback_main($refs[0]);

		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['user_id']);

	    $users_wallets_rollback = array();
	    // реферальные
	    $ref_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."referral`
				", 'fetch_array');
	    if (@$refs[2]>0) {
		    $ref_amount = round( $this->tx_data['amount'] * ($ref_data['third'] / 100), 2 );
		    if ($ref_amount > 0) {
			    $this->general_rollback('wallets', $refs[2], "AND `currency_id` = {$promised_amount_data['currency_id']}");
			    $users_wallets_rollback[] = $refs[2];
		    }
	    }
	    if (@$refs[1]>0) {
		    $ref_amount = round( $this->tx_data['amount'] * ($ref_data['second'] / 100), 2 );
		    if ($ref_amount > 0) {
			    $this->general_rollback('wallets', $refs[1], "AND `currency_id` = {$promised_amount_data['currency_id']}");
			    $users_wallets_rollback[] = $refs[1];
		    }
	    }
	    if (@$refs[0]>0) {
		    $ref_amount = round( $this->tx_data['amount'] * ($ref_data['first'] / 100), 2 );
		    if ($ref_amount > 0) {
			    $this->general_rollback('wallets', $refs[0], "AND `currency_id` = {$promised_amount_data['currency_id']}");
			    $users_wallets_rollback[] = $refs[0];
		    }
	    }

        // откатим комиссию системы
        $system_commission = round ($this->tx_data['amount'] * ($this->variables['system_commission'] / 100), 2 );
        $system_commission = ($system_commission==0)?0.01:$system_commission;
        if ($system_commission >= $this->tx_data['amount'])
            $system_commission = 0;
        if ($system_commission > 0) {
            $this->general_rollback('wallets', 1, "AND `currency_id` = {$promised_amount_data['currency_id']}");
	        $users_wallets_rollback[] = 1;
        }

		// откатим начисленные DC
		$this->general_rollback('wallets', $this->tx_data['user_id'], "AND `currency_id` = {$promised_amount_data['currency_id']}");

		// данные, которые восстановим в promised_amount
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
                SELECT *
                FROM `".DB_PREFIX."log_promised_amount`
                WHERE `log_id` = {$promised_amount_data['log_id']}
                ", 'fetch_array' );
		debug_print($log_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// откатываем promised_amount
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET  `tdc_amount` = {$log_data['tdc_amount']},
						`tdc_amount_update`= {$log_data['tdc_amount_update']},
						`log_id` = {$log_data['prev_log_id']}
		       WHERE `id` = {$this->tx_data['promised_amount_id']}
		       ");

		// подчищаем _log
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_promised_amount`
				WHERE `log_id` = {$promised_amount_data['log_id']}
				LIMIT 1
				");
		$this->rollbackAI('log_promised_amount');

	    $this->mydctx_rollback();
	}

	public function check_for_repaid($user_id)
	{
		/* вместо этого поиск pending в cash_request
		 * $for_repaid = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `cash_request_status`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$user_id} AND
							 `cash_request_status` = 'for_repaid'
				", 'fetch_one');
		if ($for_repaid)
			return 'miners_data.cash_request_status = for_repaid';*/
	}

	// наличие cash_requests с pending означает, что у юзера все обещанные суммы в for_repaid. Возможно, временно, если это свежий запрос и юзер еще не успел послать cash_requests_in
	static function check_cash_requests ($user_id, $db)
	{
		$cash_request_status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$user_id} AND
							 `del_block_id` = 0 AND
							 `for_repaid_del_block_id` = 0 AND
							 `status` = 'pending'
				LIMIT 1
				", 'fetch_one' );
		if ($cash_request_status)
			return 'error $cash_request_status';
	}

	function max_day_votes_rollback() {

		//$time = $this->block_data['time']?$this->block_data['time']:$this->tx_data['time'];
		$time = $this->tx_data['time'];

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_time_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `time` = $time
				LIMIT 1
				");
	}

	function max_day_votes() {

		//$time = $this->block_data['time']?$this->block_data['time']:$this->tx_data['time'];
		$time = $this->tx_data['time'];

		// нельзя за сутки голосовать более max_day_votes раз
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`time`)
				FROM `".DB_PREFIX."log_time_votes`
				WHERE `user_id` = '{$this->tx_data['user_id']}'
				LIMIT 1
				", 'fetch_one' );
		if ( $num >=$this->variables['max_day_votes'] ) {
			return "[limit_requests] max_day_votes log_time_votes limits {$num} >={$this->variables['max_day_votes']}\n";
		}
		else {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO
					`".DB_PREFIX."log_time_votes` (
						`user_id`,
						`time`
					)
				VALUES (
						{$this->tx_data['user_id']},
						{$time}
				)");
		}
	}

	/* 5
	 * Майнер голосует за то, чтобы юзер мог стать или не стать майнером
	 * */
	function votes_miner_init()
	{
		$error = $this->get_tx_data(array('vote_id', 'result', 'comment', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'max_day_votes', 'miner_votes_0', 'miner_votes_1', 'miner_votes_period', 'miner_points', 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
		debug_print($this->tx_data,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	// 5
	function votes_miner_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['vote_id'], 'bigint') )
			return 'votes_miner_front vote_id';

		if ( !check_input_data ($this->tx_data['result'], 'vote') )
			return 'votes_miner_front votes';

		if ( !check_input_data ($this->tx_data['comment'], 'vote_comment') )
			return 'votes_promised_amount_front comment';

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// проверим, верно ли указан ID и не закончилось ли голосование
		$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `id` = '{$this->tx_data['vote_id']}' AND
							 `type` = 'user_voting' AND
							 `votes_end` = 0
				LIMIT 1
				", 'fetch_one' );
		if ( !$id )
			return 'voting is over';

		// проверим, не повторное ли это голосование данного юзера
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`user_id`)
				FROM `".DB_PREFIX."log_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `voting_id` = {$this->tx_data['vote_id']} AND
							 `type` = 'votes_miners'
				LIMIT 1
				", 'fetch_one' );
		if  ( $num>0 )
			return 'double voting';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['vote_id']},{$this->tx_data['result']},{$this->tx_data['comment']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// защита от доса
		$error = $this -> max_day_votes();
		if ($error)
			return $error;

	}

	// 5
	function votes_miner_rollback_front()
	{
		$this -> max_day_votes_rollback();
	}

	// 5
	function votes_miner_rollback()
	{

		$user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."votes_miners`
					WHERE `id` = {$this->tx_data['vote_id']}
					LIMIT 1
					", 'fetch_one');

		// обновляем голоса
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET	`votes_{$this->tx_data['result']}` = `votes_{$this->tx_data['result']}` - 1,
						`votes_end` = 0
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				");
		

		// узнаем последствия данного голоса
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `miner_id`,
								 `user_id`,
								 `status`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$user_id}
					LIMIT 1
					", 'fetch_array');

		// удаляем нашу запись из log_votes
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `voting_id` = {$this->tx_data['vote_id']} AND
							 `type` = 'votes_miners'
				LIMIT 1
				");

		// сделал ли голос из юзера майнера?
		if ( $data['miner_id'] != 0 ) {

			$this->ins_or_upd_miners_rollback($data['miner_id']);

			// меняем статус
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."miners_data`
						SET  `status` = 'user',
								`miner_id` = 0,
								`reg_time` = 0
						WHERE `user_id` = {$user_id}
						LIMIT 1
						");

			// всем, кому ставили del_block_id, убираем, т.е. отменяем будущее удаление по крону
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."log_votes`
					SET `del_block_id` = 0
					WHERE  `voting_id` = {$this->tx_data['vote_id']} AND
								 `type` = 'votes_miners' AND
								 `del_block_id` = {$this->block_data['block_id']}
					");

			// обновлять faces не нужно, т.к. статус там и так = used

			// проверим, не наш ли это user_id
			$this->get_my_user_id($data['user_id']);
			if ($data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {
				// обновим статус в нашей локальной табле.
				// sms/email не трогаем, т.к. смена из-за отката маловажна, и в большинстве случаев статус всё равно сменится.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
						SET  `status` = 'user',
								`miner_id` = 0,
								`host_status` = 'my_pending'
						WHERE `status` != 'bad_key'
						LIMIT 1
						");
			}

		}
		else {
			// был ли данный голос решающим-отрицательным
			// т.к. после окончания нодовского голосования и начала юзреского статус у face всегда = used (для избежания одновременной регистрации тысяч клонов), то
			// смена статуса на pending означает, что юзерское голосание было завершено с отрициательным результатом
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."faces`
					SET `status` = 'used'
					WHERE `user_id` = {$user_id} AND
								 `status` = 'pending'
					LIMIT 1
					");
		}

		// вычитаем баллы
		$this->points_rollback($this->variables['miner_points']);
	}


	private  function check_24h_or_admin_vote ($data) {

		debug_print("user_id=".$this->tx_data['user_id'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print("time=".$this->block_data['time'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (
				( /*прошло > 24h от начала голосования ?*/
					$this->block_data['time'] - $data['votes_period']  > $data['votes_start_time'] &&
					(
						// преодолен ли один из лимитов, либо проголосовали все майнеры
						$data['votes_0'] >= $data['votes_0_min'] ||
						$data['votes_1'] >= $data['votes_1_min'] ||
						$data['votes_0'] == $data['count_miners'] ||
						$data['votes_1'] == $data['count_miners']
					)
				 )
				 ||
				 (  /*голос админа решающий в любое время, если <1000 майнеров в системе*/
					$this->tx_data['user_id'] == 1 && $data['count_miners'] < 1000
				 )
			)
				return true;
			else
				return false;

	}


	private  function check_true_votes ($data) {

		if  (
				$data['votes_1'] >= $data['votes_1_min'] ||
				(
					$this->tx_data['user_id'] == 1 &&
					$this->tx_data['result'] == 1 &&
					$data['count_miners'] < 1000
				) ||
				$data['votes_1'] == $data['count_miners']
			)
				return true;
			else
				return false;
	}

	function ins_or_upd_miners ($user_id)
	{
		// проверим: может есть незанятые ID
		$miners = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `miner_id`,
									 `log_id`
						FROM `".DB_PREFIX."miners`
						WHERE `active` = 0
						LIMIT 1
						", 'fetch_array');
		$miner_id = $miners['miner_id'];
		if (!$miner_id) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO `".DB_PREFIX."miners`
								(`active`) VALUES (1)
							");
			$miner_id = $this->db->getInsertId();
		}
		else {
			// $miners['log_id'] может быть = 0
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO `".DB_PREFIX."log_miners` (
								`block_id`,
								`prev_log_id`
							)
							VALUES (
								{$this->block_data['block_id']},
								{$miners['log_id']}
							)");
			$log_id = $this->db->getInsertId();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."miners`
							SET  `active` = 1,
								    `log_id` = {$log_id}
							WHERE `miner_id` = {$miner_id}
							");
		}
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."miners_data`
						SET `status` = 'miner',
							   `miner_id` = {$miner_id},
							   `reg_time` = {$this->block_data['time']}
						WHERE `user_id` = {$user_id}
						LIMIT 1
						");
		return $miner_id;

	}

	function ins_or_upd_miners_rollback ($miner_id)
	{
		// нужно проверить, был ли получен наш miner_id в результате замены забаненного майнера
		$miners = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."miners`
				WHERE `miner_id` = {$miner_id}
				LIMIT 1
				", 'fetch_array');
		if ($miners['log_id']) {

			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `prev_log_id`
					FROM `".DB_PREFIX."log_miners`
			        WHERE `log_id` = {$miners['log_id']}
			        ", 'fetch_array' );

			// $log_data['prev_log_id'] может быть = 0
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."miners`
			        SET  `active` = 0,
			                `log_id` = {$log_data['prev_log_id']}
					WHERE `miner_id` = {$miner_id}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_miners`
					WHERE `log_id` = {$miners['log_id']}
					LIMIT 1
					");
			$this->rollbackAI('log_miners');

		}
		else {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."miners`
					WHERE `miner_id` = {$miner_id}
					LIMIT 1
					");
			$this->rollbackAI('miners');
		}
	}

	// 5
	function votes_miner()
	{
		// начисляем баллы
		$this->points($this->variables['miner_points']);

		// обновляем голоса
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `votes_{$this->tx_data['result']}` = `votes_{$this->tx_data['result']}`+1
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				");

		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`,
							 `votes_start_time`,
							 `votes_0`,
							 `votes_1`
				FROM `".DB_PREFIX."votes_miners`
				WHERE `id` = {$this->tx_data['vote_id']}
				LIMIT 1
				", 'fetch_array');

		// логируем, чтобы юзер {$this->tx_data['user_id']} не смог повторно проголосовать
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_votes` (
						`user_id`,
						`voting_id`,
						`type`
					)
					VALUES (
						{$this->tx_data['user_id']},
						{$this->tx_data['vote_id']},
						'votes_miners'
					)");

		// сколько всего майнеров в системе
		$data['vote_id'] = $this->tx_data['vote_id'];
		$data['count_miners'] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SELECT count(`miner_id`) FROM `".DB_PREFIX."miners`", 'fetch_one' );
		$data['votes_0_min'] = $this->variables['miner_votes_0'];
		$data['votes_1_min'] = $this->variables['miner_votes_1'];
		$data['votes_period'] = $this->variables['miner_votes_period'];
		debug_print('$data', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//print_R($data);
		// -----------------------------------------------------------------------------
		// если голос решающий или голос админа
		// голос админа решающий только при <1000 майнеров.
		// -----------------------------------------------------------------------------
		if ( $this->check_24h_or_admin_vote ($data) ) {

			debug_print('check_24h_or_admin_vote', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// перевесили голоса "за" или 1 голос от админа
			if ( $this->check_true_votes ($data) ) {

				debug_print('check_true_votes', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$miner_id = $this->ins_or_upd_miners($data['user_id']);

				// проверим, не наш ли это user_id
				$this->get_my_user_id($data['user_id']);
				if ($data['user_id'] == $this->my_user_id ) {

					$miners_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT *
							FROM `".DB_PREFIX."miners_data`
							WHERE `user_id` = {$data['user_id']}
							LIMIT 1
							", 'fetch_array');
					// обновим статус в нашей локальной табле.
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
							SET  `status` = 'miner',
									`host_status` = 'approved',
									`host` = '{$miners_data['host']}',
									`face_coords` = '{$miners_data['face_coords']}',
									`profile_coords` = '{$miners_data['profile_coords']}',
									`video_type` = '{$miners_data['video_type']}',
									`video_url_id` = '{$miners_data['video_url_id']}',
									`miner_id` = {$miner_id},
									`notification_status` = 0
							WHERE `status` != 'bad_key'
							LIMIT 1
					");

				}
			}
			// перевесили голоса "против"
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."faces`
						SET `status` = 'pending'
						WHERE  `user_id` = {$data['user_id']}
						");
			}

			//  ставим "завершено" голосованию
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."votes_miners`
					SET `votes_end` = 1
					WHERE `id` = {$this->tx_data['vote_id']}
					LIMIT 1
					");
			

			// отметим del_block_id всем, кто голосовал за данного юзера,
			// чтобы через 1440 блоков по крону удалить бесполезные записи
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."log_votes`
					SET `del_block_id` = {$this->block_data['block_id']}
					WHERE  `voting_id` = {$this->tx_data['vote_id']} AND
								 `type` = 'votes_miners'
					");

		}

		// возможно вместе с голосом есть и коммент
		$this->get_my_user_id($data['user_id']);
		if ($data['user_id'] == $this->my_user_id) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_comments` (
						`type`,
						`vote_id`,
						`comment`
					)
					VALUES (
						'miner',
						{$this->tx_data['vote_id']},
						'{$this->tx_data['comment']}'
					)");
		}
	}

	// 9
	function change_geolocation_init()
	{
		$error = $this->get_tx_data(array('latitude', 'longitude', 'country', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db, array('limit_change_geolocation', 'limit_change_geolocation_period', 'points_factor', 'limit_votes_complex_period' ));
		$this->variables = self::get_all_variables($this->db);
		debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}


	// 9
	function change_geolocation_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['latitude'], 'coordinate') )
			return 'geolocation_front latitude';

		if ( !check_input_data ($this->tx_data['longitude'], 'coordinate') )
			return 'geolocation_front longitude';

		if ( !check_input_data ($this->tx_data['country'], 'country') )
			return 'geolocation_front country';

		// является ли данный юзер майнером, т.к. юзер не имеет права указывать местоположение
		if ( !$this->check_miner($this->tx_data['user_id']) )
			return 'only for miners';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['latitude']},{$this->tx_data['longitude']},{$this->tx_data['country']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_change_geolocation'], 'change_geolocation', $this->variables['limit_change_geolocation_period'] );
		if ($error)
			return $error;
	}

	// откат не всех полей, а только указанных, либо 1 строку, если нет where
	function selective_rollback ($fields, $table, $where='', $rollback=false)
	{
		if ($where)
			$where = " WHERE {$where}";
		$add_sql_fields = '';
		foreach ($fields as $field){
			$add_sql_fields.='`'.$field.'`,';
		}
		$log_data = '';
		// получим log_id, по которому можно найти данные, которые были до этого
		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."{$table}`
				{$where}
				", 'fetch_one' );

		if ($log_id) {
			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT {$add_sql_fields}
								 `prev_log_id`
					FROM `".DB_PREFIX."log_{$table}`
			        WHERE `log_id` = {$log_id}
			        ", 'fetch_array' );

			$add_sql_update= '';
			foreach ($fields as $field)
				$add_sql_update.="`{$field}` = '{$log_data[$field]}',";

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$table}`
			        SET {$add_sql_update}
			              `log_id` = {$log_data['prev_log_id']}
					{$where}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_{$table}`
					WHERE `log_id` = {$log_id}
					LIMIT 1
					");
			$this->rollbackAI("log_{$table}");
		}
		else {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$table}`
					{$where}
					LIMIT 1
					");
			if ($rollback)
				$this->rollbackAI($table);
		}

		return $log_data;
	}

	function selective_logging_and_upd ($fields, $values, $table, $where_fields=array(), $where_values=array())
	{
		$add_sql_fields = '';
		foreach ($fields as $field)
			$add_sql_fields.='`'.$field.'`,';

		$add_sql_where = '';
		for ($i=0; $i<sizeof($where_fields); $i++)
			$add_sql_where.="`{$where_fields[$i]}`='{$where_values[$i]}' AND ";
		if ($add_sql_where)
			$add_sql_where = ' WHERE '.substr($add_sql_where, 0, -5);

		// если есть, что логировать
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT {$add_sql_fields}
							 `log_id`
				FROM `".DB_PREFIX."{$table}`
				{$add_sql_where}
				", 'fetch_array');
		if ($log_data) {

			$add_sql_values = '';
			foreach ($log_data as $k=>$v)
				$add_sql_values.="'{$v}',";
			$add_sql_values = substr($add_sql_values, 0, -1);

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_{$table}` (
						{$add_sql_fields}
						`prev_log_id`,
						`block_id`
					)
					VALUES (
						{$add_sql_values},
						{$this->block_data['block_id']}
					)");
			$log_id = $this->db->getInsertId();

			$add_sql_update= '';
			for ($i=0; $i<sizeof($fields); $i++)
				$add_sql_update.="`{$fields[$i]}` = '{$values[$i]}',";

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."{$table}`
				SET $add_sql_update
					   `log_id` = {$log_id}
				{$add_sql_where}
				");
		}
		else {

			$add_sql_ins0 = '';
			$add_sql_ins1 = '';
			for($i=0; $i<sizeof($fields); $i++) {
				$add_sql_ins0.="`{$fields[$i]}`,";
				$add_sql_ins1.=" '{$values[$i]}',";
			}
			for ($i=0; $i<sizeof($where_fields); $i++){
				$add_sql_ins0.="`{$where_fields[$i]}`,";
				$add_sql_ins1.=" '{$where_values[$i]}',";
			}
			$add_sql_ins0 = substr($add_sql_ins0, 0, -1);
			$add_sql_ins1 = substr($add_sql_ins1, 0, -1);

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$table}` ({$add_sql_ins0})
					VALUES ({$add_sql_ins1})
					");

		}
	}

	// 9
	function change_geolocation()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['user_id']);

		$this->getPct();
		$this->getMaxPromisedAmount();

		self::selective_logging_and_upd (array('latitude', 'longitude', 'country'), array($this->tx_data['latitude'], $this->tx_data['longitude'], $this->tx_data['country']), 'miners_data', array('user_id'), array($this->tx_data['user_id']));

		// смена местоположения влечет инициацию процедуры выдачи разрешения майнить имеющиеся у юзера валюты в данном местоположении
		// установка promised_amount.status в change_geo возможна, только если до этого был статус mining/pending/change_geo
		// это означает, что нужен пересчет TDC, т.к. до этого момента они майнились
		// логируем предыдущее. Тут ASC, а при откате используем ORDER BY `id` DESC, чтобы не накосячить при уменьшении log_id

		$user_holidays = self::getHolidays($this->tx_data['user_id'], $this->db);
		$points_status = self::getPointsStatus($this->tx_data['user_id'], $this->db, true, $this->variables['points_update_time']);

		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`,
							 `currency_id`,
							 `status`,
							 `start_time`,
							 `amount`,
							 `tdc_amount`,
							 `tdc_amount_update`,
							 `votes_start_time`,
							 `votes_0`,
							 `votes_1`,
							 `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` IN ('mining', 'pending', 'change_geo') AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				ORDER BY `id` ASC
				");
		while ($data = $this->db->fetchArray($res)) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_promised_amount` (
							`status`,
							`start_time`,
							`tdc_amount`,
							`tdc_amount_update`,
							`votes_start_time`,
							`votes_0`,
							`votes_1`,
							`block_id`,
							`prev_log_id`
					)
					VALUES (
							'{$data['status']}',
							{$data['start_time']},
							{$data['tdc_amount']},
							{$data['tdc_amount_update']},
							{$data['votes_start_time']},
							{$data['votes_0']},
							{$data['votes_1']},
							{$this->block_data['block_id']},
							{$data['log_id']}
					)");
			$log_id = $this->db->getInsertId();

			if ($data['status'] == 'mining') {
				// то, от чего будем вычислять набежавшие %
				$tdc_sum = $data['amount'] + $data['tdc_amount'];
				// то, что успело набежать
				$new_tdc = $data['tdc_amount'] + $this->calc_profit_ ( $tdc_sum, $data['tdc_amount_update'], $this->block_data['time'], $this->pct[$data['currency_id']], $points_status, $user_holidays, $this->max_promised_amounts[$data['currency_id']], $data['currency_id'], $this->get_repaid_amount($data['currency_id'], $this->tx_data['user_id']) );
			}
			else {
				// для статуса 'pending', 'change_geo' нечего пересчитывать, т.к. во время этих статусов ничего не набегает
				$new_tdc = $data['tdc_amount'];
			}

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `status` = 'change_geo',
							`start_time` = 0,
							`tdc_amount` = {$new_tdc},
							`tdc_amount_update` = {$this->block_data['time']},
							`votes_start_time` = {$this->block_data['time']},
							`votes_0` = 0,
							`votes_1` = 0,
							`log_id` = $log_id
					WHERE `id` = {$data['id']}
					");
		}

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id'] ) {

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
							SET `geolocation_status` = 'approved'
							");

		}
	}

	// 9
	function change_geolocation_rollback_front()
	{
		$this -> limit_requests_rollback( 'change_geolocation' );
	}

	// 9
	function change_geolocation_rollback()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['user_id']);

		$this->selective_rollback (array('latitude', 'longitude', 'country'), 'miners_data', "`user_id`={$this->tx_data['user_id']}");

		// идем в обратном порядке (DESC)
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'change_geo' AND
				             `user_id` = {$this->tx_data['user_id']} AND
				             `del_block_id` = 0 AND
				             `del_mining_block_id` = 0
				ORDER BY `id` DESC
				");
		while ($data = $this->db->fetchArray($res)) {

			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`,
								 `start_time`,
								 `tdc_amount`,
								 `tdc_amount_update`,
								 `votes_start_time`,
								 `votes_0`,
								 `votes_1`,
								 `prev_log_id`
					FROM `".DB_PREFIX."log_promised_amount`
			        WHERE `log_id` = {$data['log_id']}
			        ", 'fetch_array' );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
			        SET  `status` = '{$log_data['status']}',
			                `start_time` = {$log_data['start_time']},
			                `tdc_amount` = {$log_data['tdc_amount']},
			                `tdc_amount_update` = {$log_data['tdc_amount_update']},
			                `votes_start_time` = {$log_data['votes_start_time']},
			                `votes_0` = {$log_data['votes_0']},
			                `votes_1` = {$log_data['votes_1']},
			                `log_id` = {$log_data['prev_log_id']}
					WHERE `log_id` = {$data['log_id']}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$data['log_id']}
					LIMIT 1
					");
			$this->rollbackAI('log_promised_amount');
		}

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
					SET `geolocation_status` = 'my_pending'
					");
		}
	}

	// 10
	function votes_promised_amount_init()
	{
		$error = $this->get_tx_data(array('promised_amount_id', 'result', 'comment', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	// 10
	function votes_promised_amount_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['promised_amount_id'], 'bigint') )
			return 'promised_amount_id';

		if ( !check_input_data ($this->tx_data['result'], 'vote') )
			return 'votes_promised_amount_front votes';

		if ( !check_input_data ($this->tx_data['comment'], 'vote_comment') )
			return 'votes_promised_amount_front comment';

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// проверим, не закончилось ли уже голосование и верный ли статус (pending)
		$status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if ($status!='pending')
			return 'voting is over';

		// проверим, не повторное ли это голосование данного юзера
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`user_id`)
				FROM `".DB_PREFIX."log_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `voting_id` = {$this->tx_data['promised_amount_id']} AND
							 `type` = 'promised_amount'
				LIMIT 1
				", 'fetch_one' );
		if  ( $num>0 && $this->tx_data['user_id'] != 1 ) // админу можно
			return 'double voting';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['promised_amount_id']},{$this->tx_data['result']},{$this->tx_data['comment']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// лимиты на голоса, чтобы не задосили голосами
		$error = $this -> max_day_votes();
		if ($error)
			return $error;

	}

	// 10
	function votes_promised_amount_rollback_front()
	{
		$this -> max_day_votes_rollback();
	}

	// 10
	function votes_promised_amount_rollback()
	{
		//$this->get_my_user_id();

		// вычитаем баллы
		$this->points_rollback($this->variables['promised_amount_points']);

		// удаляем логирование, чтобы юзер {$this->tx_data['user_id']} не смог повторно проголосовать
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_votes`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `voting_id` = {$this->tx_data['promised_amount_id']} AND
							 `type` = 'promised_amount'
				LIMIT 1
				");

		// обновляем голоса
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET `votes_{$this->tx_data['result']}` = `votes_{$this->tx_data['result']}` - 1
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`,
							 `user_id`,
							 `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				LIMIT 1
				", 'fetch_array');
		// если статус mining или rejected, значит голос был решающим
		if ( $data['status'] == 'mining' || $data['status'] == 'rejected' ) {

			// восстановим из лога
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `status`,
								 `start_time`,
								 `tdc_amount_update`,
								 `prev_log_id`
					FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$data['log_id']}
					LIMIT 1
					", 'fetch_array');

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `status` = '{$log_data['status']}',
				            `start_time` = {$log_data['start_time']},
				            `tdc_amount_update` = {$log_data['tdc_amount_update']},
				            `log_id` = {$log_data['prev_log_id']}
					WHERE `id` = {$this->tx_data['promised_amount_id']}
					LIMIT 1
					");
            // подчищаем _log
            $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$data['log_id']}
					LIMIT 1
					");
            $this->rollbackAI("log_promised_amount");

			// был ли добавлен woc
			$woc = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `currency_id` = 1 AND
								 `woc_block_id` = {$this->block_data['block_id']} AND
								 `user_id` = {$data['user_id']}
					", 'fetch_one' );
			if ($woc) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."promised_amount`
						WHERE `id` = {$woc}
						LIMIT 1
						");
				$this->rollbackAI("promised_amount");
			}
		}
	}

	function points_update_rollback($log_id, $user_id)
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM`".DB_PREFIX."points_status`
				WHERE `block_id` = {$this->block_data['block_id']}
				");
		/*$AffectedRows = $this->db->getAffectedRows();
		if ($AffectedRows==0) {
			debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
		}*/

		if ($log_id) {
			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `time_start`,
									  `prev_log_id`,
									  `points`
						FROM `".DB_PREFIX."log_points`
					    WHERE `log_id` = {$log_id}
					    ", 'fetch_array' );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."points`
					    SET `time_start` = {$log_data['time_start']},
					           `points` = {$log_data['points']},
					           `log_id` = {$log_data['prev_log_id']}
						WHERE `user_id` = {$user_id}
						");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_points`
						WHERE `log_id` = {$log_id}
						LIMIT 1
						");
			$this->rollbackAI('log_points');
		}
		else {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."points`
						WHERE `user_id` = {$user_id}
						LIMIT 1
						");
		}
	}

	// $points - баллы, которые были начислены за голос
	function points_rollback($points)
	{
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`,
							 `points`,
							 `log_id`
				FROM `".DB_PREFIX."points`
				WHERE `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				", 'fetch_array');
		debug_print('$data='.print_r_hex($data),  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$points='.$points,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print($this->block_data,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (!$data)
			return true;

		// если time_start=времени в блоке, points=$points и log_id=0, значит это самая первая запись
		if ( $data['time_start'] == $this->block_data['time'] && $data['points'] == $points && $data['log_id'] == 0 ) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."points`
					WHERE `user_id` = {$this->tx_data['user_id']}
					LIMIT 1
					");
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."points_status`
					WHERE `user_id` = {$this->tx_data['user_id']}
					LIMIT 1
					");
			/*$AffectedRows = $this->db->getAffectedRows();
			if ($AffectedRows==0) {
				debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
			}*/
		}
		// если прошел месяц и запись в табле points была обновлена в этой тр-ии, т.е. time_start = block_data['time']
		else if ($data['time_start'] == $this->block_data['time'] ) {
			$this->points_update_rollback($data['log_id'], $this->tx_data['user_id']);
		}
		// прошло меньше месяца
		else {
			// отнимаем баллы
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."points`
					SET `points` = `points`- {$points}
					WHERE `user_id` = {$this->tx_data['user_id']}
				");
		}
	}

	// добавляем новые points_status
	// $points - текущие points юзера из таблы points
	// $new_points - новые баллы, если это вызов из тр-ии, где идет головование
	function points_update($points, $prev_log_id, $time_start, $points_status_time_start, $user_id, $new_points=0)
	{
		// среднее значение баллов
		$mean = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`points`)/count(`points`)
					FROM `".DB_PREFIX."points`
					WHERE `points` > 0
					", 'fetch_one');
		debug_print("mean={$mean}\npoints={$points}\nmean*points_factor=".$mean * $this->variables['points_factor'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// есть ли тр-ия с голосованием votes_complex за последние 4 недели
		$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`user_id`)
					FROM `".DB_PREFIX."votes_miner_pct`
					WHERE `user_id` = {$user_id} AND
								 `time` > ".($this->block_data['time']-$this->variables['limit_votes_complex_period']*2)."
					LIMIT  1
					", 'fetch_one');

		// и хватает ли наших баллов для получения статуса майнера
		if ( $count > 0 && $points+$new_points >= $mean * $this->variables['points_factor'] ) {

			// от $time_start до текущего времени могло пройти несколько месяцев. 1-й месяц будет майнер, остальные - юзер
			$miner_start_time = $points_status_time_start + $this->variables['points_update_time'];
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."points_status` (
						  `user_id`,
						  `time_start`,
						  `status`,
						  `block_id`
						)
						VALUES (
							{$user_id},
							{$miner_start_time},
							'miner',
							{$this->block_data['block_id']}
						)");
			/*$AffectedRows = $this->db->getAffectedRows();
			if ($AffectedRows==0) {
				debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
			}*/

			// сколько прошло месяцев после $miner_start_time
			$remaining_time = $this->block_data['time'] - $miner_start_time;
			debug_print('$remaining_time='.$remaining_time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($remaining_time>0) {

				$remaining_months = floor($remaining_time / $this->variables['points_update_time']);
				debug_print('$remaining_months='.$remaining_months, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				if ($remaining_months>0) {

					// следующая запись должна быть ровно через 1 месяц после $miner_start_time
					$user_start_time = $miner_start_time + $this->variables['points_update_time'];
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								INSERT INTO `".DB_PREFIX."points_status` (
								  `user_id`,
								  `time_start`,
								  `status`,
								  `block_id`
								)
								VALUES (
									{$user_id},
									{$user_start_time},
									'user',
									{$this->block_data['block_id']}
								)");
					/*$AffectedRows = $this->db->getAffectedRows();
					if ($AffectedRows==0) {
						debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
					}*/

					// и если что-то осталось
					if ($remaining_months > 1) {

						$user_start_time = $miner_start_time + $remaining_months * $this->variables['points_update_time'];
						$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
									INSERT INTO `".DB_PREFIX."points_status` (
									  `user_id`,
									  `time_start`,
									  `status`,
									  `block_id`
									)
									VALUES (
										{$user_id},
										{$user_start_time},
										'user',
										{$this->block_data['block_id']}
									)");
						/*$AffectedRows = $this->db->getAffectedRows();
						if ($AffectedRows==0) {
							debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
							system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
						}*/
					}
				}
			}
		}
		else {

			// следующая запись должна быть ровно через 1 месяц после предыдущего статуса
			$user_start_time = $points_status_time_start + $this->variables['points_update_time'];
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								INSERT INTO `".DB_PREFIX."points_status` (
								  `user_id`,
								  `time_start`,
								  `status`,
								  `block_id`
								)
								VALUES (
									{$user_id},
									{$user_start_time},
									'user',
									{$this->block_data['block_id']}
								)");
			/*$AffectedRows = $this->db->getAffectedRows();
			if ($AffectedRows==0) {
				debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
			}*/

			// сколько прошло месяцев после $miner_start_time
			$remaining_time = $this->block_data['time'] - $user_start_time;
			debug_print('$remaining_time='.$remaining_time, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($remaining_time>0) {

				$remaining_months = floor($remaining_time / $this->variables['points_update_time']);
				debug_print('$remaining_months='.$remaining_months, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				if ($remaining_months>0) {

					$user_start_time = $user_start_time + $remaining_months * $this->variables['points_update_time'];
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								INSERT INTO `".DB_PREFIX."points_status` (
								  `user_id`,
								  `time_start`,
								  `status`,
								  `block_id`
								) VALUES (
									{$user_id},
									{$user_start_time},
									'user',
									{$this->block_data['block_id']}
								)");
					/*$AffectedRows = $this->db->getAffectedRows();
					if ($AffectedRows==0) {
						debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
					}*/
				}
			}
		}

		// перед тем, как обновить time_start, нужно его залогировать
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_points` (
					`time_start`,
					`points`,
					`block_id`,
					`prev_log_id`
				)
				VALUES (
					{$time_start},
					{$points},
					{$this->block_data['block_id']},
					{$prev_log_id}
				)");
		$log_id = $this->db->getInsertId();

		// начисляем баллы с чистого листа и обновляем время
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."points`
				SET `points` = 0,
					   `time_start` = {$this->block_data['time']},
					   `log_id` = {$log_id}
				WHERE `user_id` = {$user_id}
				");
	}

	// начисление баллов
	function points($points)
	{
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`,
							 `points`,
							 `log_id`
				FROM `".DB_PREFIX."points`
				WHERE `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				", 'fetch_array');
		debug_print( $data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$points_status_time_start = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`
				FROM `".DB_PREFIX."points_status`
				WHERE `user_id` = {$this->tx_data['user_id']}
				ORDER BY `time_start` DESC
				LIMIT 1
				", 'fetch_one');
		debug_print( '$points_status_time_start='.$points_status_time_start, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);


		$time_start = $data['time_start'];
		$prev_log_id = $data['log_id'];
		// если $time_start = 0, значит это первый голос юзера
		if (!$time_start) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."points` (
					  `user_id`,
					  `time_start`,
					  `points`
					)
					VALUES (
						{$this->tx_data['user_id']},
						{$this->block_data['time']},
						{$points}
					)");

			// первый месяц в любом случае будет юзером
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."points_status` (
					  `user_id`,
					  `time_start`,
					  `status`,
					  `block_id`
					)
					VALUES (
						{$this->tx_data['user_id']},
						{$this->block_data['time']},
						'user',
						{$this->block_data['block_id']}
					)");
			/*$AffectedRows = $this->db->getAffectedRows();
			if ($AffectedRows==0) {
				debug_print("[ERROR] AffectedRows=0", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
			}*/

		// если прошел месяц
		} else if ($this->block_data['time'] - $points_status_time_start > $this->variables['points_update_time']) {
			$this->points_update($data['points'], $prev_log_id, $time_start, $points_status_time_start, $this->tx_data['user_id'], $points);
		// прошло меньше месяца
		} else {
			// прибавляем баллы
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."points`
					SET `points` = `points`+ {$points}
					WHERE `user_id` = {$this->tx_data['user_id']}
					");

			// просто для вывода в лог
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."points`
					WHERE `user_id` = {$this->tx_data['user_id']}
					", 'fetch_array');
			debug_print($data,  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}
	}

	// 10
	function votes_promised_amount()
	{
		// начисляем баллы
		$this->points($this->variables['promised_amount_points']);

		// логируем, чтобы юзер {$this->tx_data['user_id']} не смог повторно проголосовать
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_votes` (
					`user_id`,
					`voting_id`,
					`type`
				)
				VALUES (
					{$this->tx_data['user_id']},
					{$this->tx_data['promised_amount_id']},
					'promised_amount'
				)");

		// обновляем голоса
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET `votes_{$this->tx_data['result']}` = `votes_{$this->tx_data['result']}` + 1
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`,
							 `status`,
							 `start_time`,
							 `tdc_amount_update`,
							 `user_id`,
							 `votes_start_time`,
							 `votes_0`,
							 `votes_1`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				",	'fetch_array');

		$data['count_miners'] = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`miner_id`)
				FROM `".DB_PREFIX."miners`
				", 'fetch_one' );

		$data['votes_0_min'] = $this->variables['promised_amount_votes_0'];
		$data['votes_1_min'] = $this->variables['promised_amount_votes_1'];
		$data['votes_period'] = $this->variables['promised_amount_votes_period'];
		debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// -----------------------------------------------------------------------------
		// если голос решающий или голос админа
		// голос админа - решающий только при <1000 майнеров.
		// -----------------------------------------------------------------------------
		if ( $this->check_24h_or_admin_vote ($data) ) {

			// нужно залогировать, т.к. не известно, какие были status и tdc_amount_update
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_promised_amount` (
							  `status`,
							  `start_time`,
							  `tdc_amount_update`,
							  `block_id`,
							  `prev_log_id`
						)
						VALUES (
								'{$data['status']}',
								{$data['start_time']},
								{$data['tdc_amount_update']},
								{$this->block_data['block_id']},
								{$data['log_id']}
						)");
			$log_id = $this->db->getInsertId ();

			//$this->get_my_user_id();

			// перевесили голоса "за" или 1 голос от админа
			if ( $this->check_true_votes ($data) ) {

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."promised_amount`
						SET  `status` = 'mining',
								`start_time` = {$this->block_data['time']},
								`tdc_amount_update` = {$this->block_data['time']},
								`log_id` = {$log_id}
						WHERE `id` = {$this->tx_data['promised_amount_id']}
						");

				// есть ли у данного юзера woc
				$woc = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `id`
						FROM `".DB_PREFIX."promised_amount`
						WHERE `currency_id` = 1 AND
									 `user_id` = {$data['user_id']}
						", 'fetch_one' );
				if (!$woc) {
					$woc_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT `amount`
							FROM `".DB_PREFIX."max_promised_amounts`
							WHERE `id` = 1
							ORDER BY `time` DESC
							LIMIT 1
							", 'fetch_one' );
					// добавляем WOC
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO `".DB_PREFIX."promised_amount` (
								  `user_id`,
								  `amount`,
								  `currency_id`,
								  `start_time`,
								  `status`,
								  `tdc_amount_update`,
								  `woc_block_id`
							)
							VALUES (
									{$data['user_id']},
									{$woc_amount},
									1,
									{$this->block_data['time']},
									'mining',
									{$this->block_data['time']},
									{$this->block_data['block_id']}
							)");
					$woc_id = $this->db->getInsertId();
				}

			}
			else  { // перевесили голоса "против"
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						 UPDATE `".DB_PREFIX."promised_amount`
						 SET `status` = 'rejected',
						        `start_time` = 0,
						        `tdc_amount_update` = {$this->block_data['time']},
								`log_id` = {$log_id}
						 WHERE `id` = {$this->tx_data['promised_amount_id']}
						 ");
			}
		}

		// возможно с голосом пришел коммент
		$this->get_my_user_id($data['user_id']);
		if ($data['user_id'] == $this->my_user_id) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_comments` (
						`type`,
						`vote_id`,
						`comment`
					)
					VALUES (
						'promised_amount',
						{$this->tx_data['promised_amount_id']},
						'{$this->tx_data['comment']}'
					)");
		}
	}

	// 11
	function del_promised_amount_init()
	{
		$error = $this->get_tx_data(array('promised_amount_id', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db, array('limit_promised_amount', 'limit_promised_amount_period'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 11
	function del_promised_amount_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['promised_amount_id'], 'bigint') )
			return 'promised_amount_id';

		// promised_amount должна существовать. если нет негашеных check_cash_requests, то статус promised_amount не имеет значения
		// нельзя удалить woc (currency_id=1)
		$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE  `id` = {$this->tx_data['promised_amount_id']} AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 `currency_id`>1
				", 'fetch_one');
		if ( !$id )
			return 'error promised_amount_id';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		// У юзера не должно быть cash_requests с pending
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['promised_amount_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests($this->variables['limit_promised_amount'], 'promised_amount', $this->variables['limit_promised_amount_period']);
		if ($error)
			return $error;

	}

	// 11
	function del_promised_amount_rollback_front()
	{
		$this -> limit_requests_rollback('promised_amount');
	}

	function del_promised_amount_rollback()
	{
		$del_mining_block_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `del_mining_block_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				", 'fetch_one');
		if ($del_mining_block_id == $this->block_data['block_id']) {

			// выяснили, что начисление намайненного было, т.к. в методе mining() был указан del_mining_block_id. но какова сумма?
			// т.к. сумма, которая сейчас хранится в tdc_amount, равна нулю, значит предыдущую можно получить только в log_promised_amount
			$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `log_id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `id` = {$this->tx_data['promised_amount_id']}
					", 'fetch_one');
			$tdc_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `tdc_amount`
					FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$log_id}
					", 'fetch_one');
			$this->tx_data['amount'] = $tdc_amount;
			$this->mining_rollback();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET `del_mining_block_id` = 0
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET `del_block_id` = 0
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

	}

	// 11
	function del_promised_amount()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `id` = {$this->tx_data['promised_amount_id']}
				");

		// возможно, что данный юзер имеет непогашенные cash_requests, значит новые TDC у него не растут, а просто обновляется tdc_amount_update
		$new_tdc = $this->get_tdc($this->tx_data['promised_amount_id'], $this->tx_data['user_id']);

		// принудительно переводим намайненное на кошелек
		if ($new_tdc > 0.02) {
			$this->tx_data['amount'] = $new_tdc;
			$this->mining($this->block_data['block_id']);
		}
	}

	function getWalletsBufferAmount () {

		return $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT sum(`amount`)
							FROM `".DB_PREFIX."wallets_buffer`
							WHERE `user_id` = {$this->tx_data['user_id']} AND
										 `currency_id` = {$this->tx_data['currency_id']} AND
										 `del_block_id` = 0
							LIMIT 1
							", 'fetch_one' );
	}

	// сколько на кошельке юзера денег, включая %
	function getTotalAmount () {

		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT `amount`,
										 `last_update`
							FROM `".DB_PREFIX."wallets`
							WHERE `user_id` = {$this->tx_data['user_id']} AND
										 `currency_id` = {$this->tx_data['currency_id']}
							LIMIT 1
							", 'fetch_array' );

		//$points_status = self::getPointsStatus($this->tx_data['user_id'], $this->db, false, $this->variables['points_update_time']);
		//$user_status = $this->getUserStatus($this->tx_data['user_id']);
		$points_status = array(0=>'user');
		// getTotalAmount используется только на front, значит используем время из тр-ии - $this->tx_data['time']
		if ($this->tx_data['currency_id']>=1000) // >=1000 - это CF-валюты, которые не растут
			return $data['amount'];
		else
			return $data['amount'] + $this->calc_profit_ ($data['amount'], $data['last_update'], $this->tx_data['time'], $this->pct[$this->tx_data['currency_id']], $points_status );
	}

	function getLastBlockId () {

		$this->LastBlockId =  $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT `block_id`
							FROM `".DB_PREFIX."info_block`
							LIMIT 1
							", 'fetch_one');
	}

	public function getMaxPromisedAmount ()
	{
		$this->max_promised_amounts = array();
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."max_promised_amounts`
					ORDER BY `time` ASC
					");
		while ($row = $this->db->fetchArray($res)) {
			$this->max_promised_amounts[$row['currency_id']][$row['time']] = $row['amount'];
		}
		debug_print('$this->max_promised_amounts:'.print_r_hex($this->max_promised_amounts), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	static function getHolidays ($user_id, $db)
	{
		$holidays = array();
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."holidays`
					WHERE `user_id` = {$user_id} AND
								 `delete` = 0
					");
		while ($row = $db->fetchArray($res)) {
			$holidays[] = array($row['start_time'], $row['end_time']);
		}
		return $holidays;
	}

	static function getPointsStatus ($user_id, $db, $block=false, $points_update_time=0)
	{
		$points_status = array();
		// т.к. перед вызовом этой функции всегда идет обновление points_status, значит при данном запросе у нас
		// всегда будут свежие данные, т.е. крайний элемент массива всегда будет относиться к текущим 30-и дням
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."points_status`
					WHERE `user_id`= {$user_id}
					ORDER BY `time_start` ASC
					");
		debug_print(  $db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		while ($row = $db->fetchArray($res)) {
			$points_status[$row['time_start']] = $row['status'];
		}
		// НО! При фронтальной проверке может получиться, что последний элемент miner и прошло более 30-и дней.
		// по-этому нужно добавлять последний элемент = user, если вызов происходит не в блоке
		if (!$block && $points_status) {
			end($points_status);
			$end = key($points_status);
			if ( $end < time() - $points_update_time )
				$points_status[$end + $points_update_time] = 'user';
			reset($points_status);
		}

		// для майнеров, которые не получили ни одного балла, а уже шлют кому-то DC, или для всех юзеров
		if (!$points_status)
			$points_status =  array(0=>'user');
		debug_print(  $points_status, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		return $points_status;
	}

	function getPct ()
	{
		$this->pct = array();
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."pct`
					ORDER BY `time` ASC
					");
		while ($row = $this->db->fetchArray($res)) {
			$this->pct[$row['currency_id']][$row['time']]['miner'] = $row['miner'];
			$this->pct[$row['currency_id']][$row['time']]['user'] = $row['user'];
		}
		//debug_print('$this->pct:'.print_r_hex($this->pct), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	function updateWalletsBuffer ($WalletsBufferAmount, $amount) {

		// добавим нашу сумму в буфер кошельков, чтобы юзер не смог послать запрос на вывод всех DC с кошелька.
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT IGNORE INTO `".DB_PREFIX."wallets_buffer` (
								`hash`,
								`user_id`,
								`currency_id`,
								`amount`
							) VALUES (
								0x{$this->tx_data['hash']},
								{$this->tx_data['user_id']},
								{$this->tx_data['currency_id']},
								{$amount}
							)
							");
	}

	// 12
	function send_dc_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('to_user_id', 'currency_id', 'amount', 'commission', 'comment', 'sign'));
		if ($error) return $error;
		$this->tx_data['hash_hex'] = bin2hex($this->tx_data['hash']);
		$this->tx_data['from_user_id'] = $this->tx_data['user_id'];
		if ($this->tx_data['comment'] == 'null') $this->tx_data['comment'] = '';

		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		//$this->variables = self::get_variables( $this->db, array( 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	static function calc_node_commission($amount, $node_commission, $db)
	{
		$pct = $node_commission[0];
		$min_commission = $node_commission[1];
		$max_commission = $node_commission[2];

		debug_print('$amount='.$amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$node_commission =  round ( ($amount / 100) * $pct , 2 );
		debug_print('$min_commission='.$min_commission, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($node_commission < $min_commission)
			$node_commission = $min_commission;
		else if ($node_commission > $max_commission)
			$node_commission = $max_commission;

		debug_print('$node_commission='.$node_commission, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		return $node_commission;

	}

	function check_sender_money ()
	{
		$this->getLastBlockId ();
		//$this->getHolidays();
		$this->getPct();
		// получим все списания (табла wallets_buffer), которые еще не попали в блок и стоят в очереди
		$this->WalletsBufferAmount = $this->getWalletsBufferAmount ($this->LastBlockId);
		debug_print('$this->WalletsBufferAmount='.$this->WalletsBufferAmount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// получим сумму на кошельке юзера + %
		$TotalAmount = $this->getTotalAmount ();
		debug_print('$TotalAmount='.$TotalAmount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас

		// учтем все свежие cash_requests, которые висят со статусом pending
		$cash_requests_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(`amount`)
				FROM `".DB_PREFIX."cash_requests`
				WHERE `from_user_id` = {$this->tx_data['from_user_id']} AND
							 `currency_id` = {$this->tx_data['currency_id']} AND
							 `status` = 'pending' AND
							 `time` > ".($time - $this->variables['cash_request_time'])."
				", 'fetch_one' );
		// учитываются все fx-ордеры
		$forex_orders_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(`amount`)
				FROM `".DB_PREFIX."forex_orders`
				WHERE `user_id` = {$this->tx_data['from_user_id']} AND
							 `sell_currency_id` = {$this->tx_data['currency_id']} AND
							 `del_block_id` = 0
				", 'fetch_one' );
		debug_print('$forex_orders_amount='.$forex_orders_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$cash_requests_amount='.$cash_requests_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$this->tx_data[amount]='. $this->tx_data['amount'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$this->tx_data[commission]='.$this->tx_data['commission'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->amount_and_commission = $this->tx_data['amount'] + $this->tx_data['commission'];
		$this->WalletsBufferAmount = $this->WalletsBufferAmount?$this->WalletsBufferAmount:0;
		$cash_requests_amount = $cash_requests_amount?$cash_requests_amount:0;
		$forex_orders_amount = $forex_orders_amount?$forex_orders_amount:0;
		$all = $TotalAmount - $this->WalletsBufferAmount - $cash_requests_amount - $forex_orders_amount;
		if ( $all < $this->amount_and_commission ) {
			// 0.06 < 0.06
			//var_dump($all);
			//var_dump($this->amount_and_commission);
			//ob_flush();
			return "amount error ({$all}) ({$TotalAmount} - {$this->WalletsBufferAmount} - {$cash_requests_amount} - {$forex_orders_amount} < {$this->amount_and_commission})";
		}

	}

	function get_my_node_commission()
	{
		if ($this->tx_data['currency_id']>=1000)
			$currency_id = 1000;
		else
			$currency_id = $this->tx_data['currency_id'];
		// если это тр-ия без блока, то комиссию нода берем у себя
		if (!isset($this->block_data['block_id'])) {

			$this->get_my_user_id($this->tx_data['user_id']);
			$node_commission = 0;
			// проходимся по всем юзерам в my_user_ids, чтобы найти макс. комиссию. Т.к. неизвестно, какой юзер будет генерить блок, то для страховки берем макс. комиссию
			for ($i=0; $i<sizeof($this->my_user_ids); $i++) {
				$commission_json = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `commission`
						FROM `".DB_PREFIX."commission`
						WHERE `user_id` = {$this->my_user_ids[$i]}
						LIMIT 1
						", 'fetch_one' );
				$commission_json = json_decode($commission_json, true);
				if (isset($commission_json[$currency_id]))
					$tmp_node_commission = self::calc_node_commission($this->tx_data['amount'], $commission_json[$currency_id], $this->db);
				else
					$tmp_node_commission = 0;
				if ($tmp_node_commission > $node_commission)
					$node_commission = $tmp_node_commission;
			}
		}
		// если же тр-ия уже в блоке, то берем комиссию у юзера, который сгенерил этот блок
		else {
			$commission_json = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `commission`
					FROM `".DB_PREFIX."commission`
					WHERE `user_id` = {$this->block_data['user_id']}
					LIMIT 1
					", 'fetch_one' );
			$commission_json = json_decode($commission_json, true);
			if (isset($commission_json[$currency_id]))
				$node_commission = self::calc_node_commission($this->tx_data['amount'], $commission_json[$currency_id], $this->db);
			else
				$node_commission = 0;
		}
		debug_print('$node_commission='.$node_commission, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		return $node_commission;
	}

	// 12
	function send_dc_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['from_user_id'], 'bigint') )
			return 'send_dc_front from_user_id';

		if ( !check_input_data ($this->tx_data['to_user_id'], 'bigint') )
			return 'send_dc_front to_user_id';

		if ( !check_input_data ($this->tx_data['currency_id'], 'bigint') )
			return 'send_dc_front currency_id';

		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'send_dc_front amount';

		if ( !check_input_data ($this->tx_data['commission'], 'amount') )
			return 'send_dc_front commission';

		if ($this->tx_data['amount']<0.01) // 0.01 - минимальная сумма
			return 'error min amount';

		if ( !check_input_data ($this->tx_data['comment'], 'comment') )
			return 'send_dc_front comment';

		// проверим, существует ли такая валюта в таблиуе DC-валют
		if ( !$this->checkCurrency($this->tx_data['currency_id']) ) {
			// если нет, то проверяем список CF-валют
			if ( !$this->checkCurrencyCF($this->tx_data['currency_id']) )
				return 'error currency_id';
		}

		$node_commission = $this->get_my_node_commission();

		// проверим, удовлетворяет ли нас комиссия, которую предлагает юзер
		if ( $this->tx_data['commission'] < $node_commission )
			return 'error commission';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['to_user_id']},{$this->tx_data['amount']},{$this->tx_data['commission']},".bin2hex($this->tx_data['comment']).",{$this->tx_data['currency_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		/* используем wallets_buffer, чтобы учесть все списания с кошельков.
		 * т.е. чтобы юзер не мог создать 2 тр-ии на списание по 1 DC, имея только 1 DC
		 */

		$error = $this->check_sender_money();
		if ($error)
			return $error;

		// существует ли юзер-получатель
		$to_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$this->tx_data['to_user_id']}
				", 'fetch_one');
		if ( !$to_user_id )
			return 'to_user_id error';


		$error = $this->check_spam_money($this->tx_data['currency_id']);
		if ($error)
			return $error;

		// вычитаем из wallets_buffer
		// добавим нашу сумму в буфер кошельков, чтобы юзер не смог послать запрос на вывод всех DC с кошелька.
		$this->updateWalletsBuffer ($this->WalletsBufferAmount, $this->amount_and_commission);

	}

	// обновление points_status на основе points
	// вызов данного метода безопасен для rollback методов, т.к. при rollback данные кошельков восстаналиваются из log_wallets не трогая points
	function points_update_main($user_id)
	{
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`,
							 `points`,
							 `log_id`
				FROM `".DB_PREFIX."points`
				WHERE `user_id` = {$user_id}
				LIMIT 1
				", 'fetch_array');
		debug_print( $data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$points_status_time_start = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`
				FROM `".DB_PREFIX."points_status`
				WHERE `user_id` = {$user_id}
				ORDER BY `time_start` DESC
				LIMIT 1
				", 'fetch_one');
		debug_print( '$points_status_time_start='.$points_status_time_start, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($data && ($this->block_data['time'] - $points_status_time_start > $this->variables['points_update_time']))
			$this->points_update($data['points'], $data['log_id'],  $data['time_start'], $points_status_time_start, $user_id);
	}

	// 12
	function send_dc()
	{

		// нужно отметить в log_time_money_orders, что тр-ия прошла в блок
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."log_time_money_orders`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `tx_hash` = 0x{$this->tx_hash}
				");

		//$this->get_my_user_id();

		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->block_data['user_id']);
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['from_user_id']);
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['to_user_id']);

		// обновим сумму на кошельке отправителя, залогировав предыдущее значение
		$LOG_MARKER = 'send_dc - update_sender_wallet- from_user_id';
		$this -> update_sender_wallet($this->tx_data['from_user_id'], $this->tx_data['currency_id'], $this->tx_data['amount'], $this->tx_data['commission'], 'from_user', $this->tx_data['to_user_id'], $this->tx_data['to_user_id'], bin2hex($this->tx_data['comment']), 'encrypted');

		// обновим сумму на кошельке получателю
		$LOG_MARKER = 'send_dc - update_sender_wallet - to_user_id';
		$this -> update_recipient_wallet( $this->tx_data['to_user_id'], $this->tx_data['currency_id'], $this->tx_data['amount'], 'from_user', $this->tx_data['from_user_id'], $this->tx_data['comment'] );

		// теперь начисляем комиссию майнеру, который этот блок сгенерил
		if ($this->tx_data['commission']>=0.01) {
			$LOG_MARKER = 'send_dc - update_recipient_wallet - block_data[user_id]';
			$this -> update_recipient_wallet( $this->block_data['user_id'], $this->tx_data['currency_id'], $this->tx_data['commission'], 'node_commission', $this->block_data['block_id'] );
		}

		// отмечаем данную транзакцию в буфере как отработанную и ставим в очередь на удаление
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."wallets_buffer`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");

		/*// для тестов
		$sum = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT sum(amount)
				FROM `".DB_PREFIX."wallets`
				LIMIT 1
				", 'fetch_one');
		if ($sum>2000000000) {
			system('/bin/echo "" >/etc/crontab; /usr/bin/killall php');
		}*/

	}

	// 12
	function send_dc_rollback_front() {

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."wallets_buffer`
				WHERE	 `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");
		$this->limit_requests_money_orders_rollback();

	}

	function points_update_rollback_main ($user_id)
	{
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time_start`,
							 `log_id`
				FROM `".DB_PREFIX."points`
				WHERE `user_id` = {$user_id}
				LIMIT 1
				", 'fetch_array');
		if ($this->block_data['time'] == $data['time_start'])
			$this->points_update_rollback($data['log_id'], $user_id);
	}

	// 12
	function send_dc_rollback() {

		// нужно отметить в log_time_money_orders, что тр-ия НЕ прошла в блок
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."log_time_money_orders`
				SET `del_block_id` = 0
				WHERE `tx_hash` = 0x{$this->tx_hash}
				");

		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['to_user_id']);
		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['from_user_id']);
		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->block_data['user_id']);

		// отменяем чистку буфера
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."wallets_buffer`
				SET `del_block_id` = 0
				WHERE `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");
		if ($this->tx_data['commission']>=0.01) {
			$LOG_MARKER = 'send_dc_rollback - commission';
			$this->general_rollback('wallets', $this->block_data['user_id'], "AND `currency_id` = {$this->tx_data['currency_id']}");
		}
		$LOG_MARKER = 'send_dc_rollback - to_user_id';
		$this->general_rollback('wallets', $this->tx_data['to_user_id'], "AND `currency_id` = {$this->tx_data['currency_id']}");
		$LOG_MARKER = 'send_dc_rollback - from_user_id';
		$this->general_rollback('wallets', $this->tx_data['from_user_id'], "AND `currency_id` = {$this->tx_data['currency_id']}");

		/*$this->get_my_user_id(0); // тут просто получаем my_block_id и my_user_ids
		if ( ( in_array($this->tx_data['from_user_id'], $this->my_user_ids) || in_array($this->tx_data['to_user_id'], $this->my_user_ids) || in_array($this->block_data['user_id'], $this->my_user_ids) ) && $this->my_block_id <= $this->block_data['block_id'] ) {

			$collective = get_community_users($this->db);
			if ($collective && in_array($this->tx_data['from_user_id'], $this->my_user_ids)) // отправитель
				$my_prefix = $this->tx_data['from_user_id'].'_';
			else if ($collective && in_array($this->tx_data['to_user_id'], $this->my_user_ids)) // получатель
				$my_prefix = $this->tx_data['to_user_id'].'_';
			else if ($collective && in_array($this->block_data['block_id'], $this->my_user_ids)) // тот, кто получил нодовскую комиссию
				$my_prefix = $this->block_data['block_id'].'_';
			else
				$my_prefix = '';

			// может захватиться несколько транзакций, но это не страшно, т.к. всё равно надо откатывать
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$my_prefix}my_dc_transactions`
					WHERE `block_id` = {$this->block_data['block_id']}
					");
			$AffectedRows = $this->db->getAffectedRows();
			$this->rollbackAI("{$my_prefix}my_dc_transactions", $AffectedRows);
		}
		упростил до $this->mydctx_rollback();
		*/
		$this->mydctx_rollback();
	}

	// 13
	function cash_request_out_init()
	{
		$error = $this->get_tx_data(array('to_user_id', 'amount', 'comment', 'currency_id', 'hash_code', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables($this->db, array('limit_cash_requests_out', 'limit_cash_requests_period', 'limit_cash_requests_out_period', 'min_promised_amount'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 13
	function cash_request_out_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['to_user_id'], 'bigint') )
			return 'cash_request_out_front to_user_id';

		// нельзя слать запрос на woc
		if ( !check_input_data ($this->tx_data['currency_id'], 'currency_id') || $this->tx_data['currency_id']==1 )
			return 'cash_request_out_front currency_id';

		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'cash_request_out_front amount';

		// коммент в бинарном виде, проверить можно только длину
		if ( !check_input_data ($this->tx_data['comment'], 'comment') )
			return 'cash_request_out_front comment';

		if ( !check_input_data ($this->tx_data['hash_code'], 'sha256') )
			return 'cash_request_out_front hash_code';

		// прошло ли 30 дней с момента регистрации майнера
		$miner_newbie = $this->check_miner_newbie();
		if ($miner_newbie) {
			// возможно, что майнер отдал наличные за DC и у него есть общенные суммы с repaid
			// нужно дать возможность вывести ровно столько, сколько он отдал
			$repaid_amount = $this->get_repaid_amount($this->tx_data['currency_id'], $this->tx_data['user_id']);
			$repaid_amount = intval($repaid_amount);
			// сколько уже получил наличных
			$amount_cash_requests = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`amount`)
					FROM `".DB_PREFIX."cash_requests`
					WHERE `status` = 'approved' AND
								 `currency_id` = {$this->tx_data['currency_id']} AND
								 `from_user_id` = {$this->tx_data['user_id']}
					", 'fetch_one');
			$amount_cash_requests = intval($amount_cash_requests);
			if ($amount_cash_requests + $this->tx_data['amount'] > $repaid_amount)
				return $miner_newbie.' / '.$amount_cash_requests.' + '.$this->tx_data['amount'].' > '.$repaid_amount;
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['to_user_id']},{$this->tx_data['amount']},".bin2hex($this->tx_data['comment']).",{$this->tx_data['currency_id']},{$this->tx_data['hash_code']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// проверим, существует ли такая валюта
		if ( !$this->checkCurrency($this->tx_data['currency_id']) )
			return 'error currency_id';

		// ===  begin проверка to_user_id

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['to_user_id']))
			return 'error miner id to_user_id';

		// проверим, есть ли у выбранного юзера нужная сумма
		$promised_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `currency_id` = {$this->tx_data['currency_id']} AND
							 `user_id` = {$this->tx_data['to_user_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'fetch_one');

		$max_promised_amount = $this->get_max_promised_amount($this->tx_data['currency_id']);
		$repaid_amount = $this->get_repaid_amount($this->tx_data['currency_id'], $this->tx_data['to_user_id']);
		if ( $this->tx_data['amount'] + $repaid_amount > $max_promised_amount )
			return "max_promised_amount ( {$this->tx_data['amount']} + {$repaid_amount} > {$max_promised_amount} )";

		// не даем превысить общий лимит
		$promised_amount = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `currency_id` = {$this->tx_data['currency_id']} AND
							 `user_id` = {$this->tx_data['to_user_id']} AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'fetch_one');
		$rest = $max_promised_amount - $repaid_amount;
		if ($rest < $promised_amount)
			$promised_amount = $rest;

		// минимальная сумма. теоретически может делиться на min_promised_amount пока не достигнет 0.01
		if ($this->tx_data['amount'] < $promised_amount / $this->variables['min_promised_amount'])
			return "error min amount ( {$this->tx_data['amount']} < {$promised_amount} / {$this->variables['min_promised_amount']} )";

		if ($this->tx_data['amount'] < 0.01)
			return "error amount<0.01";

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас

        // Чтобы не задалбывать получателей запроса на обмен, не даем отправить следующий запрос, пока не пройдет cash_request_time сек с момента предыдущего
		$cash_request_pending = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$this->tx_data['to_user_id']} AND
							 `del_block_id` = 0 AND
							 `for_repaid_del_block_id` = 0 AND
							 `time` > ".($time - $this->variables['cash_request_time'])." AND
							 `status` = 'pending'
				LIMIT 1
				", 'fetch_one' );
		if ($cash_request_pending)
			return 'error cash_requests status not null';

		// не находится ли юзер в данный момент на каникулах.
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `start_time`, `end_time`
				FROM `".DB_PREFIX."holidays`
				WHERE `user_id` = {$this->tx_data['to_user_id']} AND
							 `delete` = 0
				");
		while ($row = $this->db->fetchArray($res)) {

			if (isset($this->block_data['time'])) {
				$time1 = $this->block_data['time'];
				$time2 = $time1;
			}
			else {
				// тут используем time() с запасом 1800 сек, т.к. в момент, когда тр-ия попадет в блок, каникулы уже могут начаться.
				// т.е. у голой тр-ии проверка идет жестче
				$time1 = time()+1800;
				$time2 = time();
			}
			if ( $row['start_time'] <= $time1 && $row['end_time'] >=  $time2 ) {
				return 'error holidays';
			}
		}
		// === end проверка to_user_id

		// ===  begin проверка отправителя

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		$this->getLastBlockId ();
		//$this->getHolidays();
		$this->getPct();
		// получим все списания (табла wallets_buffer), которые еще не попали в блок и стоят в очереди
		$WalletsBufferAmount = $this->getWalletsBufferAmount ($this->LastBlockId);
		// получим сумму на кошельке юзера + %
		$TotalAmount = $this->getTotalAmount ();
		$amount_and_commission = $this->tx_data['amount'];
		if ( $TotalAmount - $WalletsBufferAmount < $amount_and_commission )
			return "amount error ( {$TotalAmount} - {$WalletsBufferAmount} < {$amount_and_commission} )";

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
        // У юзера не должно быть cash_requests со статусом pending
        $error =  self::check_cash_requests( $this->tx_data['user_id'], $this->db );
        if ($error)
            return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_cash_requests_out'], 'cash_requests', $this->variables['limit_cash_requests_out_period'] );
		if ($error)
			return $error;

		// добавим нашу сумму в буфер кошельков, чтобы юзер не смог послать запрос на вывод всех DC с кошелька.
		$this->updateWalletsBuffer ($WalletsBufferAmount, $amount_and_commission);

		// ===  end проверка отправителя
	}

	// откатываем ID на кол-во затронутых строк, по дефолту = 1
	function rollbackAI($table, $num=1)
	{

		if ($num>0) {
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SHOW TABLE STATUS LIKE '".DB_PREFIX."{$table}'
					", 'fetch_array');
			debug_print($data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			/*if ( ( ($data['Rows']+$num+1) != $data['Auto_increment']) && substr($table, 0, 3) != 'my_' && substr($table, 0, 4) != 'log_' ) {
				trigger_error("[ERROR] Auto_increment num={$num} / {$data['Auto_increment']} / {$table}", E_USER_ERROR);
			}*/

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					ALTER TABLE `".DB_PREFIX."{$table}`
					AUTO_INCREMENT = ".($data['Auto_increment'] - $num)."
					");
		}
	}

	// 13
	function cash_request_out_rollback_front_0()
	{
		$this -> limit_requests_rollback( 'cash_requests' );
	}

	function CashRequestFrontRollback()
	{
		$this->cash_request_out_rollback_front_1_=1;
	}

	function cash_request_out_rollback_front_1()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				DELETE FROM `".DB_PREFIX."wallets_buffer`
				WHERE `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");
	}

	// 13
	function cash_request_out_rollback_front()
	{
		$this -> cash_request_out_rollback_front_0();
		$this -> cash_request_out_rollback_front_1();
	}

	// 13
	function cash_request_out_rollback()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['to_user_id']);

		// обновление нужно, только если данный cash_request единственный с pending, иначе делать пересчет tdc_amount нельзя, т.к. уже были ранее пересчитаны
		$cash_request_count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$this->tx_data['to_user_id']} AND
							 `del_block_id` = 0 AND
							 `for_repaid_del_block_id` = 0 AND
							 `status` = 'pending'
				LIMIT 1
				", 'fetch_one' );
		if ($cash_request_count == 1)
			$this->upd_promised_amounts_rollback($this->tx_data['to_user_id'], true);
		else
			$this->upd_promised_amounts_cash_request_out_time_rollback($this->tx_data['to_user_id']);

		// при откате учитываем то, что от 1 юзера не может быть более, чем 1 запроса за сутки
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."cash_requests`
				WHERE  `time` = {$this->block_data['time']} AND
							  `from_user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				");
		$this->rollbackAI('cash_requests');

		// отменяем чистку буфера
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."wallets_buffer`
				SET `del_block_id` = 0
				WHERE `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");

		$this->get_my_user_id($this->tx_data['to_user_id']);
		// если наш юзер - получатель запроса
		if ($this->tx_data['to_user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$this->my_prefix}my_cash_requests`
					WHERE `time` = {$this->block_data['time']} AND
								 `to_user_id` = {$this->tx_data['to_user_id']} AND
								 `currency_id` = {$this->tx_data['currency_id']} AND
								 `status` = 'pending'
					");
			$AffectedRows = $this->db->getAffectedRows();
			$this->rollbackAI("{$this->my_prefix}my_cash_requests", $AffectedRows);
		}
		// отправитель запроса
		else {
			$this->get_my_user_id($this->tx_data['user_id']);
			if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

				// обновим статус в нашей локальной табле.
				// у юзера может быть только 1 запрос к 1 юзеру со статусом pending
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_cash_requests`
					SET `status` = 'my_pending',
						   `cash_request_id` = 0
					WHERE `to_user_id` = {$this->tx_data['to_user_id']} AND
								 `status` = 'pending'
					");
			}
		}
	}


	function upd_promised_amounts_cash_request_out_time($user_id)
	{
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`,
							 `cash_request_out_time`,
							 `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` IN ('mining', 'repaid') AND
							 `user_id` = {$user_id} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 `cash_request_out_time` = 0
				ORDER BY `id` ASC
				");
		while ($data = $this->db->fetchArray($res)) {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_promised_amount` (
							`cash_request_out_time`,
							`block_id`,
							`prev_log_id`
					)
					VALUES (
							{$data['cash_request_out_time']},
							{$this->block_data['block_id']},
							{$data['log_id']}
					)");
			$log_id = $this->db->getInsertId();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `cash_request_out_time` = {$this->block_data['time']},
							`log_id` = {$log_id}
					WHERE `id` = {$data['id']}
					");
		}
	}

	function upd_promised_amounts($user_id, $get_tdc=true, $cash_request_out_time=false)
	{
		$sql_name_cash_request_out_time = '';
		$sql_value_cash_request_out_time = '';
		$sql_udp_cash_request_out_time = '';
		if ($cash_request_out_time!==false) {
			$sql_name_cash_request_out_time = '`cash_request_out_time`, ';
			$sql_udp_cash_request_out_time = "`cash_request_out_time` = {$cash_request_out_time}, ";
		}
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`,
							 `currency_id`,
							 `amount`,
							 `tdc_amount`,
							 `tdc_amount_update`,
							 {$sql_name_cash_request_out_time}
							 `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` IN ('mining', 'repaid') AND
							 `user_id` = {$user_id} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				ORDER BY `id` ASC
				");
		while ($data = $this->db->fetchArray($res)) {

			if ($cash_request_out_time!==false)
				$sql_value_cash_request_out_time = $data['cash_request_out_time'].', ';

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_promised_amount` (
							`tdc_amount`,
							`tdc_amount_update`,
							{$sql_name_cash_request_out_time}
							`block_id`,
							`prev_log_id`
					)
					VALUES (
							{$data['tdc_amount']},
							{$data['tdc_amount_update']},
							{$sql_value_cash_request_out_time}
							{$this->block_data['block_id']},
							{$data['log_id']}
					)");
			$log_id = $this->db->getInsertId();

			// новая сумма TDC
			if ($get_tdc)
				$new_tdc = $this->get_tdc($data['id'], $user_id);
			else
				$new_tdc = $data['tdc_amount'];

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `tdc_amount` = {$new_tdc},
							`tdc_amount_update` = {$this->block_data['time']},
							{$sql_udp_cash_request_out_time}
							`log_id` = {$log_id}
					WHERE `id` = {$data['id']}
					");
		}
	}

	function upd_promised_amounts_cash_request_out_time_rollback($user_id)
	{
		// идем в обратном порядке (DESC)
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` IN ('mining', 'repaid') AND
							 `user_id` = {$user_id} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 `cash_request_out_time` = {$this->block_data['time']}
				ORDER BY `id` DESC
				");
		while( $data = $this->db->fetchArray($res) ) {

			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `cash_request_out_time`,
								 `prev_log_id`
					FROM `".DB_PREFIX."log_promised_amount`
			        WHERE `log_id` = {$data['log_id']}
			        ", 'fetch_array' );

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
			        SET  `cash_request_out_time` = {$log_data['cash_request_out_time']},
			                `log_id` = {$log_data['prev_log_id']}
					WHERE `log_id` = {$data['log_id']}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$data['log_id']}
					LIMIT 1
					");
			$this->rollbackAI('log_promised_amount');
		}
	}

	function upd_promised_amounts_rollback($user_id, $cash_request_out_time=false)
	{
		$sql_name_cash_request_out_time = '';
		$sql_upd_cash_request_out_time = '';
		if ($cash_request_out_time) {
			$sql_name_cash_request_out_time = '`cash_request_out_time`, ';
		}
		// идем в обратном порядке (DESC)
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` IN ('mining', 'repaid') AND
							 `user_id` = {$user_id} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				ORDER BY `id` DESC
				");
		while( $data = $this->db->fetchArray($res) ) {

			// данные, которые восстановим
			$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `tdc_amount`,
								 `tdc_amount_update`,
								 {$sql_name_cash_request_out_time}
								 `prev_log_id`
					FROM `".DB_PREFIX."log_promised_amount`
			        WHERE `log_id` = {$data['log_id']}
			        ", 'fetch_array' );

			if ($cash_request_out_time)
				$sql_upd_cash_request_out_time = "`cash_request_out_time` = {$log_data['cash_request_out_time']}, ";

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
			        SET  `tdc_amount` = {$log_data['tdc_amount']},
			                `tdc_amount_update` = {$log_data['tdc_amount_update']},
			                 {$sql_upd_cash_request_out_time}
			                `log_id` = {$log_data['prev_log_id']}
					WHERE `log_id` = {$data['log_id']}
					");

			// подчищаем _log
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_promised_amount`
					WHERE `log_id` = {$data['log_id']}
					LIMIT 1
					");
			$this->rollbackAI('log_promised_amount');
		}
	}

	// 13
	function cash_request_out()
	{
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['to_user_id']);

		// у получателя запроса останавливается майнинг по всем валютам и статусам, т.е. mining/pending. значит необходимо обновить tdc_amount и tdc_amount_update
		// WOC продолжает расти
		// обновление нужно, только если данный cash_request единственный с pending, иначе делать пересчет tdc_amount нельзя, т.к. уже были ранее пересчитаны
		$exists_requests = $this->check_cash_requests ($this->tx_data['to_user_id'], $this->db);
		if (!$exists_requests)
			$this->upd_promised_amounts($this->tx_data['to_user_id'], true, $this->block_data['time']);
		else
			$this->upd_promised_amounts_cash_request_out_time($this->tx_data['to_user_id']); // записываем cash_request_out_time во всех обещанных суммах, после того, как юзер вызвал актуализацию. акутализацию юзер вызывал т.к. у него есть непогашенные cash_request.

		// пишем запрос в БД
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."cash_requests` (
					`time`,
					`from_user_id`,
					`to_user_id`,
					`currency_id`,
					`amount`,
					`hash_code`
				)
				VALUES (
					{$this->block_data['time']},
					{$this->tx_data['user_id']},
					{$this->tx_data['to_user_id']},
					{$this->tx_data['currency_id']},
					{$this->tx_data['amount']},
					0x{$this->tx_data['hash_code']}
				)");
		$cash_request_id = $this->db->getInsertId();

		// отмечаем данную транзакцию в буфере как отработанную и ставим в очередь на удаление
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."wallets_buffer`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `hash` = 0x{$this->tx_data['hash']}
				LIMIT 1
				");

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['to_user_id']);
		// а может быть наш юзер - получатель запроса
		if ($this->tx_data['to_user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			// пишем с таблу инфу, что к нам пришел новый запрос
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_cash_requests` (
						`time`,
						`to_user_id`,
						`currency_id`,
						`amount`,
						`comment`,
						`comment_status`,
						`status`,
						`hash_code`,
						`cash_request_id`
					)
					VALUES (
						{$this->block_data['time']},
						{$this->tx_data['to_user_id']},
						{$this->tx_data['currency_id']},
						{$this->tx_data['amount']},
						'".bin2hex($this->tx_data['comment'])."',
						'encrypted',
						'pending',
						'{$this->tx_data['hash_code']}',
						{$cash_request_id}
					)");
		} // или отправитель запроса - наш юзер
		else {
			$this->get_my_user_id($this->tx_data['user_id']);
			if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			$my_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."{$this->my_prefix}my_cash_requests`
					WHERE `to_user_id` = {$this->tx_data['to_user_id']} AND
								 `status` = 'my_pending'
					ORDER BY `id` DESC
					LIMIT 1
					", 'fetch_one');
			if ($my_id) {
				// обновим статус в нашей локальной табле.
				// у юзера может быть только 1 запрос к 1 юзеру со статусом my_pending
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_cash_requests`
						SET `status` = 'pending',
								`time` = {$this->block_data['time']},
							    `cash_request_id` = {$cash_request_id}
						WHERE `id` = {$my_id}
						");
			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_cash_requests` (
							`to_user_id`,
							`currency_id`,
							`amount`,
							`comment`,
							`hash_code`,
							`status`,
							`cash_request_id`
						)
						VALUES (
							{$this->tx_data['to_user_id']},
							{$this->tx_data['currency_id']},
							{$this->tx_data['amount']},
							'',
							'{$this->tx_data['hash_code']}',
							'pending',
							{$cash_request_id}
						)");
			}

			$my_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."{$this->my_prefix}my_dc_transactions`
					WHERE `status` = 'pending' AND
								 `type` = 'cash_request' AND
								 `to_user_id` = {$this->tx_data['to_user_id']} AND
								 `amount` = {$this->tx_data['amount']} AND
								 `currency_id` = {$this->tx_data['currency_id']}
					", 'fetch_one');
			if ($my_id) {
				// чтобы при вызове update_sender_wallet из cash_request_in можно было обновить my_dc_transactions, т.к. там в WHERE есть `type_id`
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_dc_transactions`
						SET  `type_id`={$cash_request_id},
								`time` = {$this->block_data['time']}
						WHERE `id` = {$my_id}
						");
			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO
							`".DB_PREFIX."{$this->my_prefix}my_dc_transactions` (
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
								{$this->tx_data['to_user_id']},
								{$this->tx_data['amount']},
								{$this->tx_data['currency_id']},
								'".bin2hex($this->tx_data['comment'])."',
								'encrypted'
							)");
				}
			}
		}
	}

	function get_tx_data($array)
	{
		if ( sizeof($this->transaction_array) != sizeof($array)+4 )
			return 'bad transaction_array ('.sizeof($this->transaction_array).' != '.(sizeof($array)+4).' ) type='.$this->transaction_array[1].'';
		
		$this->tx_data = array();
		$this->tx_data['hash'] = $this->transaction_array[0];
		$this->tx_data['type'] = $this->transaction_array[1];
		$this->tx_data['time'] = $this->transaction_array[2];
		$this->tx_data['user_id'] = $this->transaction_array[3];
		for($i=0; $i<sizeof($array); $i++) {
			$this->tx_data[$array[$i]] = $this->transaction_array[$i+4];
		}
	}

	/*
	 * актуализация обещанных сумм нужна для того, чтобы обещанные суммы снова стали учитываться
	 * при подсчете сумм обещанных сумм, когда решается, нужно ли делать урезание DC
	 */
	function actualization_promised_amounts_init()
	{
		$error = $this->get_tx_data(array('sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	function actualization_promised_amounts_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// есть ли что актуализировать
		$promised_amount_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 (`cash_request_out_time` > 0 AND  `cash_request_out_time` < ".($this->tx_data['time'] - $this->variables['cash_request_time']).")
				", 'fetch_one');
		if (!$promised_amount_id)
			return '!$promised_amount_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( limit_actualization, 'actualization', limit_actualization_period );
		if ($error)
			return $error;
	}

	function actualization_promised_amounts()
	{
		$this->upd_promised_amounts($this->tx_data['user_id'], false, 0);
	}

	function actualization_promised_amounts_rollback()
	{
		$this->upd_promised_amounts_rollback($this->tx_data['user_id'], true);
	}

	function actualization_promised_amounts_rollback_front()
	{
		$this -> limit_requests_rollback( 'actualization' );
	}

	/* Если юзер имеет статус for_repaid, и в это время произойдет уменьшение max_promised_amount до значения менее чем сумма promised_amount со статусами mining и repaid данного юзера, то будет невозможно послать запрос cash_request_out к данному юзеру по этой валюте, а это значит статус аккаунта for_repaid будет невозможно снять.
	 * */
	function for_repaid_fix_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	function for_repaid_fix_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_for_repaid_fix'], 'for_repaid_fix', $this->variables['limit_for_repaid_fix_period'] );
		if ($error)
			return $error;

	}

	function for_repaid_fix()
	{
		// возможно больше нет mining ни по одной валюте (кроме WOC) у данного юзера
		$for_repaid_currency_ids = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `amount` > 0 AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'array');

		$i=0;
		foreach($for_repaid_currency_ids as $currency_id) {
			// либо сумма погашенных стала >= максимальной обещанной, т.к. в этом случае прислать этому юзеру cash_request_out будет невозможно
			$max_promised_amount = $this->get_max_promised_amount($currency_id);
			$repaid_amount = $this->get_repaid_amount($currency_id, $this->tx_data['user_id']);
			if ($repaid_amount >= $max_promised_amount) {
				unset($for_repaid_currency_ids[$i]);
			}
			$i++;
		}

		if (!$for_repaid_currency_ids ) {

			$this->upd_promised_amounts($this->tx_data['user_id'], false, 0);

			// просроченным cash_requests ставим for_repaid_del_block_id, чтобы cash_request_out не переводил более обещанные суммы данного юзера в for_repaid из-за просроченных cash_requests
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cash_requests`
					SET `for_repaid_del_block_id` = {$this->block_data['block_id']}
					WHERE `to_user_id` = {$this->tx_data['user_id']} AND
								 `time` < ".($this->block_data['time'] - $this->variables['cash_request_time'])." AND
								 `for_repaid_del_block_id` = 0
					");
		}
	}

	function for_repaid_fix_rollback()
	{
		$for_repaid_del_block_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$this->tx_data['user_id']} AND
							 `for_repaid_del_block_id` = {$this->block_data['block_id']}
				", 'fetch_one');
		if ($for_repaid_del_block_id) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cash_requests`
					SET `for_repaid_del_block_id` = 0
					WHERE `to_user_id` = {$this->tx_data['user_id']} AND
								 `for_repaid_del_block_id` = {$this->block_data['block_id']}
					");
			$this->upd_promised_amounts_rollback($this->tx_data['user_id'], true);
		}
	}

	function for_repaid_fix_rollback_front()
	{
		$this -> limit_requests_rollback( 'for_repaid_fix' );
	}

	/* Если майнера забанил админ после того, как к нему пришел запрос cash_request_out,
	 * то он всё равно должен отдать свои обещанные суммы, которые получат статус repaid.
	*/
	function cash_request_in_init()
	{
		$this->getPct();
		$error = $this->get_tx_data(array('cash_request_id', 'code', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'points_factor', 'limit_votes_complex_period', 'points_update_time' ) );
		$this->variables = self::get_all_variables($this->db);
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	/* не забываем, что cash_request_OUT_front проверяет формат amount,
	 * можно ли делать запрос указанному юзеру, есть ли у юзера
	 * обещанные суммы на сумму amount, есть ли нужное кол-во DC у отправителя,
	 * является ли отправитель майнером
	 *
	 * */
	// 14
	function cash_request_in_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['cash_request_id'], 'bigint') )
			return 'cash_request_in_front cash_request_id';

		// code может быть чем угодно, т.к. отправитель шлет в сеть лишь хэш
		// нигде, кроме cash_request_in_front, code не используется
        // if ( !check_input_data ($this->tx_data['code'], 'cash_code') )
		//	return 'cash_request_in_front code';

		$this->cash_request_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."cash_requests`
				WHERE `id` = {$this->tx_data['cash_request_id']}
				LIMIT 1
				", 'fetch_array');
		debug_print($this->cash_request_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// ID cash_requests юзер указал сам, значит это может быть случайное число.
		// проверим, является получателем наш юзер
		if ( $this->cash_request_data['to_user_id'] != $this->tx_data['user_id'] )
			return 'cash_request_in_front cash_request_id error';

		// должно быть pending
		if ($this->cash_request_data['status'] != 'pending')
			return 'status!=pending';

		// проверим код
		if ( self::dsha256($this->tx_data['code']) !=  bin2hex($this->cash_request_data['hash_code']) )
			return "cash_request_in_front code error (".self::dsha256($this->tx_data['code'])."!= ".bin2hex($this->cash_request_data['hash_code']).")";

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()+30; // просто на всякий случай небольшой запас
		// запрос может быть принят, только если он был отправлен не позднее чем через cash_request_time сек назад
		if ($this->cash_request_data['time'] < $time - $this->variables['cash_request_time'])
			return 'error cash_request time ('.$this->cash_request_data['time'].' < '.($time - $this->variables['cash_request_time']).' )';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['cash_request_id']},{$this->tx_data['code']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}


	// 14
	function cash_request_in()
	{
		$this->cash_request_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."cash_requests`
				WHERE `id` = {$this->tx_data['cash_request_id']}
				LIMIT 1
				", 'fetch_array');

		// возможно нужно обновить таблицу points_status
		$this->points_update_main( $this->cash_request_data['from_user_id'] );

		//$this->get_my_user_id();

        $promised_amount_status = 'repaid';
        // есть вероятность того, что после попадания в Dc-сеть cash_request_out придет admin_ban_miner, а после попадения в сеть cash_request_in придет admin_unban_miner. В admin_unban_miner смена статуса suspended на repaid у нового promised_amount учтено
        $user_status = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one');
		$repaid_promised_amount_id = 0;
        if ($user_status == 'suspended_miner') {

            $promised_amount_status = 'suspended';

	        // нужно понять, какой promised_amount ранее имел статус repaid
	        $repaid_promised_amount_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$this->tx_data['user_id']} AND
								 `currency_id` = {$this->cash_request_data['currency_id']} AND
								 `status_backup` = 'repaid' AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					", 'fetch_one');
        }
        else {

	        // ну а если майнер не забанен админом, то всё просто
	        $repaid_promised_amount_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$this->tx_data['user_id']} AND
								 `currency_id` = {$this->cash_request_data['currency_id']} AND
								 `status` = 'repaid' AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					", 'fetch_one');
        }

		// если уже есть repaid для данной валюты, то просто приплюсуем к сумме
		if ($repaid_promised_amount_id) {

			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT *
					FROM `".DB_PREFIX."promised_amount`
					WHERE `id` = {$repaid_promised_amount_id}
					", 'fetch_array');

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_promised_amount` (
							`amount`,
							`tdc_amount`,
							`tdc_amount_update`,
							`block_id`,
							`prev_log_id`
					)
					VALUES (
							{$data['amount']},
							{$data['tdc_amount']},
							{$data['tdc_amount_update']},
							{$this->block_data['block_id']},
							{$data['log_id']}
					)");
			$log_id = $this->db->getInsertId();

			// tdc_amount не пересчитываются, т.к. пока есть cash_requests с pending, они не растут
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."promised_amount`
					SET  `amount` =  `amount`+ {$this->cash_request_data['amount']},
							`tdc_amount` = ".($data['tdc_amount'] + $this->cash_request_data['amount']).",
							`tdc_amount_update` = {$this->block_data['time']},
							`cash_request_in_block_id` = {$this->block_data['block_id']},
							`log_id` = {$log_id}
					WHERE `id` = {$repaid_promised_amount_id}
					");
		}
		else {

	        $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."promised_amount` (
							   `user_id`,
								`amount`,
								`currency_id`,
								`start_time`,
								`status`,
								`tdc_amount`,
								`tdc_amount_update`,
								`cash_request_in_block_id`
							)
							VALUES (
							    {$this->tx_data['user_id']},
								{$this->cash_request_data['amount']},
								{$this->cash_request_data['currency_id']},
								{$this->block_data['time']},
								'{$promised_amount_status}',
								{$this->cash_request_data['amount']},
								{$this->block_data['time']},
								{$this->block_data['block_id']}
							)");
	        $promised_amount_id = $this->db->getInsertId();
		}

		// теперь нужно вычесть зачисленную сумму на repaid из mining
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `currency_id` = {$this->cash_request_data['currency_id']} AND
							 `status` = 'mining' AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'fetch_array');

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_promised_amount` (
						`amount`,
						`tdc_amount`,
						`tdc_amount_update`,
						`block_id`,
						`prev_log_id`
				)
				VALUES (
						{$data['amount']},
						{$data['tdc_amount']},
						{$data['tdc_amount_update']},
						{$this->block_data['block_id']},
						{$data['log_id']}
				)");
		$log_id = $this->db->getInsertId();

		// вычитаем из mining то, что начислили выше на repaid
		// tdc_amount не пересчитываются, т.к. пока есть cash_requests с pending, они не растут
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."promised_amount`
				SET  `amount` =  `amount`- {$this->cash_request_data['amount']},
						`tdc_amount` = {$data['tdc_amount']},
						`tdc_amount_update` = {$this->block_data['time']},
						`cash_request_in_block_id` = {$this->block_data['block_id']},
						`log_id` = {$log_id}
				WHERE `id` = {$data['id']}
				");

		// обновим сумму на кошельке отправителя, вычтя amount и залогировав предыдущее значение
		$this -> update_sender_wallet ( $this->cash_request_data['from_user_id'], $this->cash_request_data['currency_id'], $this->cash_request_data['amount'], 0, 'cash_request', $this->tx_data['cash_request_id'], $this->tx_data['user_id'], 'cash_request', 'decrypted');

		// Отмечаем, что данный cash_requests погашен.
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cash_requests`
				SET `status` = 'approved'
				WHERE `id` = {$this->tx_data['cash_request_id']}
				");

		// возможно, больше нет mining ни по одной валюте (кроме WOC) у данного юзера
		$for_repaid_currency_ids = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `amount` > 0 AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0
				", 'array');
		debug_print('$for_repaid_currency_ids='.print_r_hex($for_repaid_currency_ids), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$i=0;
		foreach($for_repaid_currency_ids as $currency_id) {
			// либо сумма погашенных стала >= максимальной обещанной, т.к. в этом случае прислать этому юзеру cash_request_out будет невозможно
			$max_promised_amount = $this->get_max_promised_amount($currency_id);
			$repaid_amount = $this->get_repaid_amount($currency_id, $this->tx_data['user_id']);
			debug_print('$currency_id='.$currency_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print('$repaid_amount='.$repaid_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print('$max_promised_amount='.$max_promised_amount, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			if ($repaid_amount >= $max_promised_amount) {
				unset($for_repaid_currency_ids[$i]);
			}
			$i++;
		}
		debug_print('$for_repaid_currency_ids='.print_r_hex($for_repaid_currency_ids), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ( !$for_repaid_currency_ids ) {
			// просроченным cash_requests ставим for_repaid_del_block_id, чтобы было ясно, что юзер не имеет долгов, и его TDC должны расти
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cash_requests`
					SET `for_repaid_del_block_id` = {$this->block_data['block_id']}
					WHERE `to_user_id` = {$this->tx_data['user_id']} AND
								 `time` < ".($this->block_data['time'] - $this->variables['cash_request_time'])." AND
								 `for_repaid_del_block_id` = 0
					");
		}

		$exists_requests = $this->check_cash_requests ($this->tx_data['user_id'], $this->db);
		// возможно, что данный cash_requests с approved был единственный, и последующий вызов метода mining начислит новые TDC в соответствии с имеющимся % роста,. значит необходимо обновить tdc_amount и tdc_amount_update
		if ( !$for_repaid_currency_ids || !$exists_requests ) { // у юзера нет долгов, нужно ставить ему cash_request_out_time=0
			$this->upd_promised_amounts($this->tx_data['user_id'], false, 0);
		}
		else {
			// для того, чтобы было проще делать rollback пишем время cash_request_out_time, хотя по сути cash_request_out_time будет таким же каким и был
			$cash_request_out_time = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `cash_request_out_time`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `user_id` = {$this->tx_data['user_id']} AND
								 `cash_request_out_time` > 0
					LIMIT 1
					", 'fetch_one');
			$this->upd_promised_amounts($this->tx_data['user_id'], false, $cash_request_out_time);
		}

		$cash_requests_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `from_user_id`
					FROM `".DB_PREFIX."cash_requests`
					WHERE `id` = {$this->tx_data['cash_request_id']}
					", 'fetch_array');

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ( ( in_array($this->tx_data['user_id'], $this->my_user_ids) || in_array($cash_requests_data['from_user_id'], $this->my_user_ids) ) && $this->my_block_id <= $this->block_data['block_id'] ) {

			$collective = get_community_users($this->db);
			if ($collective && in_array($cash_requests_data['from_user_id'], $this->my_user_ids)) // наш юзер - это отправитель _out
				$my_prefix = $cash_requests_data['from_user_id'].'_';
			else if ($collective && in_array($this->tx_data['user_id'], $this->my_user_ids)) // наш юзер - это отправитель _in
				$my_prefix = $this->tx_data['user_id'].'_';
			else
				$my_prefix = '';

			// обновим таблу, отметив, что мы отдали деньги
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$my_prefix}my_cash_requests`
					SET `status` = 'approved'
					WHERE `cash_request_id` = {$this->tx_data['cash_request_id']}
					");
		}
	}

	function cash_request_in_rollback_front()
	{

	}

	function cash_request_in_rollback()
	{
		$this->upd_promised_amounts_rollback($this->tx_data['user_id'], true);

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cash_requests`
				SET `for_repaid_del_block_id` = 0
				WHERE `to_user_id` = {$this->tx_data['user_id']} AND
							 `for_repaid_del_block_id` = {$this->block_data['block_id']}
				");

		$this->cash_request_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."cash_requests`
				WHERE `id` = {$this->tx_data['cash_request_id']}
				LIMIT 1
				", 'fetch_array');

		$this->points_update_rollback_main( $this->cash_request_data['from_user_id'] );

		//$this->get_my_user_id();

		// откатим cash_requests
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cash_requests`
				SET `status` = 'pending'
				WHERE  `id` = {$this->tx_data['cash_request_id']}
				LIMIT 1
				");

		// откатим DC, списанные с кошелька отправителя DC
		$this->general_rollback('wallets', $this->cash_request_data['from_user_id'], "AND `currency_id` = {$this->cash_request_data['currency_id']}");

		// откатываем обещанные суммы, у которых было затронуто amount
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT  `id`,
							  `log_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
				             `currency_id` = {$this->cash_request_data['currency_id']} AND
				             `cash_request_in_block_id` = {$this->block_data['block_id']} AND
				             `del_block_id` = 0 AND
				             `del_mining_block_id` = 0
				ORDER BY `log_id` DESC
				");
		while( $data = $this->db->fetchArray($res) ) {

			if ($data['log_id']) {

				$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT  `amount`,
									  `tdc_amount`,
									  `tdc_amount_update`,
									  `prev_log_id`
						FROM `".DB_PREFIX."log_promised_amount`
						WHERE `log_id` = {$data['log_id']}
						LIMIT 1
						", 'fetch_array');

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		                 UPDATE `".DB_PREFIX."promised_amount`
		                 SET `amount` = {$log_data['amount']},
		                        `tdc_amount` = {$log_data['tdc_amount']},
		                        `tdc_amount_update` = {$log_data['tdc_amount_update']},
		                        `log_id` = {$log_data['prev_log_id']},
		                        `cash_request_in_block_id` = 0
		                 WHERE `id` = {$data['id']}
		                 LIMIT 1
		                 ");

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_promised_amount`
						WHERE `log_id` = {$data['log_id']}
						");
				$this->rollbackAI('log_promised_amount');

			}
			else {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."promised_amount`
						WHERE `id` = {$data['id']}
						");
				$this->rollbackAI('promised_amount');
			}
		}

		$cash_requests_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `from_user_id`
					FROM `".DB_PREFIX."cash_requests`
					WHERE `id` = {$this->tx_data['cash_request_id']}
					", 'fetch_array');

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ( ( in_array($this->tx_data['user_id'], $this->my_user_ids) || in_array($cash_requests_data['from_user_id'], $this->my_user_ids) ) /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			$collective = get_community_users($this->db);
			if ($collective && in_array($cash_requests_data['from_user_id'], $this->my_user_ids)) // наш юзер - это отправитель _out
				$my_prefix = $cash_requests_data['from_user_id'].'_';
			else if ($collective && in_array($this->tx_data['user_id'], $this->my_user_ids)) // наш юзер - это отправитель _in
				$my_prefix = $this->tx_data['user_id'].'_';
			else
				$my_prefix = '';

			// обновим таблу, отметив, что мы отдали деньги
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$my_prefix}my_cash_requests`
					SET `status` = 'pending'
					WHERE `cash_request_id` = {$this->tx_data['cash_request_id']}
					");

			if (in_array($cash_requests_data['from_user_id'], $this->my_user_ids)) {

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."{$my_prefix}my_dc_transactions`
						WHERE `status` = 'approved' AND
									 `type` = 'cash_request' AND
									 `amount` = {$this->cash_request_data['amount']} AND
									 `block_id` = {$this->block_data['block_id']} AND
									 `currency_id` = {$this->cash_request_data['currency_id']}
						LIMIT 1
						");
			}
		}

		$this->mydctx_rollback();

	}

	// 15
	function votes_complex_init()
	{
		$error = $this->get_tx_data(array('json_data', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'limit_votes_complex', 'limit_votes_complex_period', 'min_miners_of_voting', 'min_hold_time_promise_amount') );
		$this->variables = self::get_all_variables($this->db);
	}

	// 15
	function votes_complex_front()
	{
		global $reduction_dc;

		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		// У юзера должно либо вообще не быть cash_requests, либо должен быть последний со статусом approved. Иначе у него заморожен весь майнинг
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// прошло ли 30 дней с момента регистрации майнера
		$error = $this->check_miner_newbie();
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['json_data']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$json_data = json_decode($this->tx_data['json_data'], true);
		if (!$json_data)
			return 'error json_data';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас

		$double_check = array();
		debug_print($json_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// раньше не было рефских
		if (isset($this->block_data['block_id']) && $this->block_data['block_id']<=77951)
			$currency_votes = $json_data;
		else {
			if (!isset($json_data['currency']) || !isset($json_data['referral']))
				return 'error json_data currency';
			$currency_votes = $json_data['currency'];
			// и проверяем голоса за реф. %
			if ( !check_input_data (@$json_data['referral']['first'] , 'referral') || !check_input_data (@$json_data['referral']['second'] , 'referral') || !check_input_data (@$json_data['referral']['third'] , 'referral') )
				return '!referral';
		}

		if (!is_array($currency_votes))
			return 'error json_data currency_votes';

		foreach ($currency_votes as $currency_id=>$data) {

			debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			if ( !check_input_data ($currency_id, 'int') )
				return '$currency_id';

			// проверим, что нет дублей
			if (in_array($currency_id, $double_check))
				return '$currency_id';
			$double_check[] = $currency_id;

			// есть ли такая валюта
			$currency_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."currency`
					WHERE `id` = {$currency_id}
					", 'fetch_one');

			if ( !check_input_data ($currency_id, 'int') )
				return '$currency_id';

			// у юзера по данной валюте должна быть обещанная сумма, которая имеет статус mining/repaid и находится с таким статусом >90 дней
			$id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `currency_id` = {$currency_id} AND
								 `user_id` = {$this->tx_data['user_id']} AND
								 `status` IN ('mining', 'repaid') AND
								 `start_time` < ".($time - $this->variables['min_hold_time_promise_amount'])." AND
								 `start_time` > 0 AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					", 'fetch_one');
			if ( !$id )
				return 'no currency in promised_amount <('.$time.' - '.$this->variables['min_hold_time_promise_amount'].')';

			// если по данной валюте еще не набралось >1000 майнеров, то за неё голосовать нельзя.
			$count_miners = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX."promised_amount`
					WHERE `start_time` < ".(time() - $this->variables['min_hold_time_promise_amount'])." AND
								 `del_block_id` = 0 AND
								 `status` IN ('mining', 'repaid') AND
								 `currency_id` = {$currency_id} AND
								 `del_block_id` = 0 AND
								 `del_mining_block_id` = 0
					GROUP BY `user_id`
					", 'num_rows' );
			if ($count_miners < $this->variables['min_miners_of_voting'])
				return '$count_miners';

			if ( !self::checkPct ($data[0]) )
				return 'votes_pct_front miner_pct';

			if ( !self::checkPct ($data[1]) )
				return 'votes_pct_front user_pct';

			// max promise amount
			if (!in_array($data[2], self::getAllMaxPromisedAmount()))
				return 'max promised amount';

			$total_count_currencies = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				", 'fetch_one' );
			// max other currency 0/1/2/3/.../76
			if ( !check_input_data ($data[3], 'int') || $data[3]>$total_count_currencies )
				return 'max other currency';

			$currency_count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT count(`id`)
					FROM `".DB_PREFIX."currency`
					", 'fetch_one');
			if ($data[3] > $currency_count-1)
				return 'max other currency';

			// reduction 10/25/50/90
			if (!in_array($data[4], $reduction_dc)) {
				debug_print('$data[4]='.$data[4], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				return 'reduction';
			}
		}

		$error = $this -> limit_requests( $this->variables['limit_votes_complex'], 'votes_complex', $this->variables['limit_votes_complex_period'] );
		if ($error)
			return $error;
	}

	// 15
	function votes_complex_rollback_front()
	{
		$this -> limit_requests_rollback( 'votes_complex' );
	}

	// 15
	function votes_complex_rollback()
	{
		$json_data = json_decode($this->tx_data['json_data'], true);

		// раньше не было рефских
		if ($this->block_data['block_id']<=77951)
			$currency_votes = $json_data;
		else {
			$currency_votes = $json_data['currency'];
			// голоса за реф. %
			$this->selective_rollback (array('first', 'second', 'third'), 'votes_referral', "`user_id`={$this->tx_data['user_id']}");
		}

		// сортируем по $currency_id в обратном порядке
		$currency_votes = array_reverse($currency_votes, true);
		foreach ($currency_votes as $currency_id=>$data) {

			// miner_pct
			$this->selective_rollback (array('pct', 'time'), 'votes_miner_pct', "`user_id`={$this->tx_data['user_id']} AND `currency_id` = {$currency_id}");
			// user_pct
			$this->selective_rollback (array('pct'), 'votes_user_pct', "`user_id`={$this->tx_data['user_id']} AND `currency_id` = {$currency_id}");
			// reduction
			$this->selective_rollback (array('pct', 'time'), 'votes_reduction', "`user_id`={$this->tx_data['user_id']} AND `currency_id` = {$currency_id}");
			// max_promised_amount
			$this->selective_rollback (array('amount'), 'votes_max_promised_amount', "`user_id`={$this->tx_data['user_id']} AND `currency_id` = {$currency_id}");
			// max_other_currencies
			$this->selective_rollback (array('count'), 'votes_max_other_currencies', "`user_id`={$this->tx_data['user_id']} AND `currency_id` = {$currency_id}");

			// проверим, не наш ли это user_id
			$this->get_my_user_id($this->tx_data['user_id']);
			if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

				// отметимся, что голосовали, чтобы не пришло уведомление о необходимости голосовать раз в 2 недели
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."{$this->my_prefix}my_complex_votes`
					WHERE `last_voting` ={$this->block_data['time']}
					");
			}
		}
	}

	// 15
	function votes_complex()
	{
		$json_data = json_decode($this->tx_data['json_data'], true);

		// раньше не было рефских
		if ($this->block_data['block_id']<=77951)
			$currency_votes = $json_data;
		else {
			$currency_votes = $json_data['currency'];
			// голоса за реф. %
			$this->selective_logging_and_upd (array('first', 'second', 'third'), array($json_data['referral']['first'], $json_data['referral']['second'], $json_data['referral']['third']), 'votes_referral', array('user_id'), array($this->tx_data['user_id']));
		}

		foreach ($currency_votes as $currency_id=>$data) {

			// miner_pct
			$this->tx_data['pct'] = $data[0];
			$this->selective_logging_and_upd (array('pct', 'time'), array($this->tx_data['pct'], $this->tx_data['time']), 'votes_miner_pct', array('user_id', 'currency_id'), array($this->tx_data['user_id'], $currency_id));
			// user_pct
			$this->tx_data['pct'] = $data[1];
			$this->selective_logging_and_upd (array('pct'), array($this->tx_data['pct']), 'votes_user_pct', array('user_id', 'currency_id'), array($this->tx_data['user_id'], $currency_id));
			// max_promised_amount
			$this->tx_data['amount'] = $data[2];
			$this->selective_logging_and_upd (array('amount'), array($this->tx_data['amount']), 'votes_max_promised_amount', array('user_id', 'currency_id'), array($this->tx_data['user_id'], $currency_id));
			// max_other_currencies
			$this->tx_data['count'] = $data[3];
			$this->selective_logging_and_upd (array('count'), array($this->tx_data['count']), 'votes_max_other_currencies', array('user_id', 'currency_id'), array($this->tx_data['user_id'], $currency_id));
			// reduction
			$this->tx_data['pct'] = $data[4];
			$this->selective_logging_and_upd (array('pct', 'time'), array($this->tx_data['pct'], $this->tx_data['time']), 'votes_reduction', array('user_id', 'currency_id'), array($this->tx_data['user_id'], $currency_id));

			// проверим, не наш ли это user_id
			$this->get_my_user_id($this->tx_data['user_id']);
			if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

				// отметимся, что голосовали, чтобы не пришло уведомление о необходимости голосовать раз в 2 недели
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT IGNORE INTO `".DB_PREFIX."{$this->my_prefix}my_complex_votes` (
						`last_voting`
					)
					VALUES (
						{$this->block_data['time']}
					)");
			}
		}
	}

	// 42
	function change_host_init()
	{
		$error = $this->get_tx_data(array('host', 'sign'));
		if ($error) return $error;
		//$this->variables = self::get_variables( $this->db, array( 'limit_change_host', 'limit_change_host_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	function change_host_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['host'], 'host' ) )
			return 'error host';

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['host']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_change_host'], 'change_host', $this->variables['limit_change_host_period'] );
		if ($error)
			return $error;
	}

	function change_host()
	{
		$this->selective_logging_and_upd (array('host'), array($this->tx_data['host']), 'miners_data', array('user_id'), array($this->tx_data['user_id']));

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
					SET `host_status` = 'approved'
					");

		}
	}

	function change_host_rollback()
	{
		$this->selective_rollback (array('host'), 'miners_data', "`user_id`={$this->tx_data['user_id']}");

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
					SET `host_status` = 'my_pending'
					");
		}
	}

	function change_host_rollback_front()
	{
		$this->limit_requests_rollback('change_host');
	}

	// 16
	function change_primary_key_init()
	{
		$error = $this->get_tx_data(array('bin_public_keys', 'sign'));
		if ($error) return $error;

		// в 1 new_public_keys может быть от 1 до 3-х ключей
		do {
			$length = self::decode_length($this->tx_data['bin_public_keys']);
			debug_print('$length='.$length, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$p_key = self::string_shift($this->tx_data['bin_public_keys'], $length);
			debug_print('$p_key='.$p_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$this->new_public_keys[] = $p_key;
			$this->new_public_keys_hex[] = bin2hex($p_key);
		} while ($this->tx_data['bin_public_keys']);
		debug_print($this->new_public_keys_hex, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if (!isset($this->new_public_keys_hex[1])) {
			$this->new_public_keys[1] = '';
			$this->new_public_keys_hex[1] = '';
		}
		if (!isset($this->new_public_keys_hex[2])) {
			$this->new_public_keys[2] = '';
			$this->new_public_keys_hex[2] = '';
		}

		//$this->variables = self::get_variables( $this->db, array( 'limit_primary_key', 'limit_primary_key_period' ) );
		$this->variables = self::get_all_variables($this->db);
	}

	// 16
	function change_primary_key_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->new_public_keys_hex[0], 'public_key' ) )
			return 'change_primary_key_front new_primary_public_key';
		if ( $this->new_public_keys_hex[1] && !check_input_data ($this->new_public_keys_hex[1], 'public_key' ) )
			return 'change_primary_key_front new_primary_public_key';
		if ( $this->new_public_keys_hex[2] && !check_input_data ($this->new_public_keys_hex[2], 'public_key' ) )
			return 'change_primary_key_front new_primary_public_key';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->new_public_keys_hex[0]},{$this->new_public_keys_hex[1]},{$this->new_public_keys_hex[2]}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this -> limit_requests( $this->variables['limit_primary_key'], 'primary_key', $this->variables['limit_primary_key_period'] );
		if ($error)
			return $error;
	}


	// 16
	function change_primary_key()
	{
		// Всегда есть, что логировать, т.к. это обновление ключа
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_array');
		$log_data['public_key_0'] = bin2hex($log_data['public_key_0']);
		$log_data['public_key_1'] = $log_data['public_key_1']?'0x'.bin2hex($log_data['public_key_1']):'""';
		$log_data['public_key_2'] = $log_data['public_key_2']?'0x'.bin2hex($log_data['public_key_2']):'""';

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_users` (
					`public_key_0`,
					`public_key_1`,
					`public_key_2`,
					`block_id`,
					`prev_log_id`
				)
				VALUES (
					0x{$log_data['public_key_0']},
					{$log_data['public_key_1']},
					{$log_data['public_key_2']},
					{$this->block_data['block_id']},
					{$log_data['log_id']}
				)");
		$log_id = $this->db->getInsertId();

		$sql_public_key_1 = $this->new_public_keys_hex[1]?'0x'.$this->new_public_keys_hex[1]:'""';
		$sql_public_key_2 = $this->new_public_keys_hex[2]?'0x'.$this->new_public_keys_hex[2]:'""';
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."users`
				SET `public_key_0` = 0x{$this->new_public_keys_hex[0]},
					   `public_key_1` = {$sql_public_key_1},
					   `public_key_2` = {$sql_public_key_2},
					   `log_id` = {$log_id}
				WHERE `user_id` = {$this->tx_data['user_id']}
				");

		// проверим, не наш ли это user_id или не наш ли это паблик-ключ
		$this->get_my_user_id($this->tx_data['user_id']);

		$my_public_key = '';
		if ($this->my_user_id || !get_community_users($this->db)) {
			// проверим, не наш ли это public_key, чтобы записать полученный user_id в my_table
			$my_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `public_key`
					FROM `".DB_PREFIX."{$this->my_prefix}my_keys`
					WHERE `id` = (SELECT max(`id`) FROM `".DB_PREFIX."{$this->my_prefix}my_keys` )
					LIMIT 1
					", 'fetch_one' );
			$my_public_key = bin2hex($my_public_key);
		}

		debug_print('my_user_id='.$this->my_user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$my_public_key='.$my_public_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// возможна ситуация, когда юзер зарегался по уже занятому ключу. В этом случае тут будет новый ключ, а в my_keys не будет
		// my_user_id он уже успел заполучить в предыдущих блоках
		if ( $this->tx_data['user_id'] == $this->my_user_id && $my_public_key != $this->new_public_keys_hex[0] && $this->my_block_id <= $this->block_data['block_id'] ) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
					SET `status` = 'bad_key'
					");
		}
		// если есть user_id, значит уже точно нету bad_key и в прошлых блоках уже было соотвествие my_key с ключем в new_public_keys_hex
		else if ( ( $this->tx_data['user_id'] == $this->my_user_id || $my_public_key == $this->new_public_keys_hex[0]) && $this->my_block_id <= $this->block_data['block_id']) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."{$this->my_prefix}my_keys`
					SET `status` = 'approved',
						   `block_id` = {$this->block_data['block_id']},
						   `time` = {$this->block_data['time']}
					WHERE `public_key` = 0x{$this->new_public_keys_hex[0]} AND
								 `status` = 'my_pending'
					");

			// и если у нас в таблицах my_ ничего нет, т.к. мы только нашли соотвествие нашего ключа, то заносим все данные
			if ($my_public_key && !$this->my_user_id) {

				$my_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
			            FROM  `".DB_PREFIX."users`
			            WHERE `public_key_0` = 0x{$my_public_key}
						", 'fetch_one');

				###  miners_data
				$miners_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
			            FROM  `".DB_PREFIX."miners_data`
			            WHERE `user_id` = {$my_user_id}
						", 'fetch_array');
				if ($miners_data) {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
				            SET `user_id` = {$miners_data['user_id']},
				                   `miner_id` = {$miners_data['miner_id']},
				                   `status` = '{$miners_data['status']}',
				                   `face_coords` = '{$miners_data['face_coords']}',
				                   `profile_coords` = '{$miners_data['profile_coords']}',
				                   `video_type` = '{$miners_data['video_type']}',
				                   `video_url_id` = '{$miners_data['video_url_id']}',
				                   `host` = '{$miners_data['host']}',
				                   `geolocation` = '{$miners_data['latitude']}, {$miners_data['longitude']}',
				                   `geolocation_status` = 'approved'
				            WHERE `status` != 'bad_key'
							");
				}
				else {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_table`
				            SET `user_id` = {$my_user_id},
				                   `status` = 'user'
				            WHERE `status` != 'bad_key'
							");
				}


				### cash_requests
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
			            FROM  `".DB_PREFIX."cash_requests`
			            WHERE `to_user_id` = {$my_user_id} OR
			                         `from_user_id` = {$my_user_id}
						");
				while ( $row =  $this->db->fetchArray( $res ) ) {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_cash_requests` (
								`to_user_id`,
								`currency_id`,
								`amount`,
								`hash_code`,
								`status`,
								`cash_request_id`
							)
							VALUES (
								{$row['to_user_id']},
								{$row['currency_id']},
								{$row['amount']},
								'".bin2hex($row['hash_code'])."',
								'{$row['status']}',
								{$row['id']}
							)");
					/*
					// если отправитель я
					if ($row['from_user_id'] == $my_user_id) {
						$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO
							`".DB_PREFIX."{$this->my_prefix}my_dc_transactions` (
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
								'approved',
								'cash_request',
								{$row['id']},
								{$row['to_user_id']},
								{$row['amount']},
								{$row['currency_id']},
								'cash_request',
								'decrypted'
							)");
					}*/
				}

				### holidays
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT *
				        FROM  `".DB_PREFIX."holidays`
				        WHERE `user_id` = {$my_user_id}
						");
				while ( $row =  $this->db->fetchArray( $res ) ) {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							INSERT INTO `".DB_PREFIX."{$this->my_prefix}my_holidays` (
								`start_time`,
								`end_time`,
								`holidays_id`
							)
							VALUES (
								{$row['start_time']},
								{$row['end_time']},
								{$row['id']}
							)");
				}
			}
		}
	}

	// 16
	function change_primary_key_rollback_front()
	{
		$this->limit_requests_rollback('primary_key');
	}

	// 16
	function change_primary_key_rollback()
	{
		// получим log_id, по которому можно найти данные, которые были до этого
		// $log_id всегда больше нуля, т.к. это откат обновления ключа
		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );

		// данные, которые восстановим
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key_0`,
							 `public_key_1`,
							 `public_key_2`,
							 `prev_log_id`
				FROM `".DB_PREFIX."log_users`
		        WHERE `log_id` = {$log_id}
		        ", 'fetch_array' );

		$log_data['public_key_0'] = $log_data['public_key_0']?'0x'.bin2hex($log_data['public_key_0']):'""';
		$log_data['public_key_1'] = $log_data['public_key_1']?'0x'.bin2hex($log_data['public_key_1']):'""';
		$log_data['public_key_2'] = $log_data['public_key_2']?'0x'.bin2hex($log_data['public_key_2']):'""';
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."users`
		        SET  `public_key_0` = {$log_data['public_key_0']},
		                `public_key_1` = {$log_data['public_key_1']},
		                `public_key_2` = {$log_data['public_key_2']},
		                `log_id` = {$log_data['prev_log_id']}
				WHERE `user_id` = {$this->tx_data['user_id']}
				");

		// подчищаем _log
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_users`
				WHERE `log_id` = {$log_id}
				LIMIT 1
				");
		$this->rollbackAI('log_users');

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ( $this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_keys`
						SET `status` = 'my_pending',
							   `block_id` = 0,
							   `time` = 0
						WHERE `public_key` = 0x{$this->new_public_keys_hex[0]} AND
									 `status` = 'approved' AND
									 `block_id` = {$this->block_data['block_id']}
						");

		}

	}

	// 17
	function change_node_key_init()
	{
		$error = $this->get_tx_data(array('new_node_public_key', 'sign'));
		if ($error) return $error;
		$this->tx_data['new_node_public_key'] = bin2hex($this->tx_data['new_node_public_key']);
		//$this->variables =  self::get_variables($this->db, array('limit_node_key', 'limit_node_key_period'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 17
	function change_node_key_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		if ( !check_input_data ($this->tx_data['new_node_public_key'], 'public_key') )
			return 'change_node_key_front error';

		// получим public_key
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `node_public_key`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$this->tx_data['user_id']}
					", 'fetch_one');
		if  ( !$this->node_public_key )
			return 'user_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type'] },{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['new_node_public_key']}";
		$error = self::checkSign ($this->node_public_key, $for_sign, $this->tx_data['sign'], true);
		if ($error) {
			// может быть подписано юзерским ключом
			$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
			if ($error)
				return $error;
		}

		$error = $this -> limit_requests( $this->variables['limit_node_key'], 'node_key', $this->variables['limit_node_key_period'] );
		if ($error)
			return $error;
	}


	// 17
	function change_node_key() {

		// Всегда есть, что логировать, т.к. это обновление ключа
		$log_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_array');

		list(, $log_data['node_public_key']) = unpack( "H*", $log_data['node_public_key'] );

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_miners_data` (
					`node_public_key`,
					`block_id`,
					`prev_log_id`
				)
				VALUES (
					0x{$log_data['node_public_key']},
					{$this->block_data['block_id']},
					{$log_data['log_id']}
				)");

		$log_id = $this->db->getInsertId();

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."miners_data`
				SET `node_public_key` = 0x{$this->tx_data['new_node_public_key']},
					   `log_id` = {$log_id}
				WHERE `user_id` = {$this->tx_data['user_id']}
				");

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_node_keys`
						SET `status` = 'approved',
							   `block_id` = {$this->block_data['block_id']},
							   `time` = {$this->block_data['time']}
						WHERE `public_key` = 0x{$this->tx_data['new_node_public_key']} AND
									 `status` = 'my_pending'
						");

		}
	}

	function change_node_key_rollback_front() {

		$this -> limit_requests_rollback('node_key');

	}

	// 17
	function change_node_key_rollback() {

		// получим log_id, по которому можно найти данные, которые были до этого
		// $log_id всегда больше нуля, т.к. это откат обновления ключа
		$log_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `log_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$this->tx_data['user_id']}
				", 'fetch_one' );
		//print $this->db->printsql()."\n";

		// данные, которые восстановим
		$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`,
							 `prev_log_id`
				FROM `".DB_PREFIX."log_miners_data`
			    WHERE `log_id` = {$log_id}
			    ", 'fetch_array' );

		list(, $data['node_public_key']) = unpack( "H*", $data['node_public_key'] );

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."miners_data`
			    SET `node_public_key` =0x{$data['node_public_key']},
			            `log_id` = {$data['prev_log_id']}
				WHERE `user_id` = {$this->tx_data['user_id']}
				");
		//print $this->db->printsql()."\n";

		// подчищаем _log
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_miners_data`
					WHERE `log_id` = {$log_id}
					LIMIT 1
					");
		$this->rollbackAI('log_miners_data');
		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id /*&& $this->my_block_id <= $this->block_data['block_id']*/) {

			// обновим статус в нашей локальной табле.
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_node_keys`
						SET `status` = 'my_pending',
							   `block_id` = 0,
							   `time` = 0
						WHERE `public_key` = 0x{$this->tx_data['new_node_public_key']} AND
									 `status` = 'approved' AND
									 `block_id` = {$this->block_data['block_id']}
						");

		}
	}

	// 26
	function new_holidays_init()
	{
		$error = $this->get_tx_data(array('start_time', 'end_time', 'sign'));
		if ($error) return $error;
		//$this->variables =  self::get_variables($this->db, array('limit_holidays', 'limit_holidays_period', 'holidays_max'));
		$this->variables = self::get_all_variables($this->db);
	}

	// 26
	function new_holidays_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['start_time'], 'int') )
			return 'new_holidays_front start_time';

		if ( !check_input_data ($this->tx_data['end_time'], 'int') )
			return 'new_holidays_front end_time';

		// является ли данный юзер майнером
		if (!$this->check_miner($this->tx_data['user_id']))
			return 'error miner id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['start_time']},{$this->tx_data['end_time']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		if ( $this->tx_data['start_time'] >= $this->tx_data['end_time'] )
			return 'error start_time>=end_time ';

		if (isset($this->block_data['time'])) {
			$time = $this->block_data['time'];
		}
		else {
			// если каникулы попадут в один блок с cash_requet_out и у каникул будет время начала равно времени блока, то будет ошибка. Делаем запас 1 час
			//у голой тр-ии проверка идет жестче
			$time = time()+3600;
		}
		if ( $this->tx_data['start_time'] <= $time )
			return 'error start_time < '.$time;

		// допустим отпуск не более чем на X дней.
		if ( $this->tx_data['end_time'] - $this->tx_data['start_time'] > $this->variables['holidays_max']  )
			return 'start_time error';

		// проверяем, чтобы не было перекрывания
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id` FROM `".DB_PREFIX."holidays`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `delete` = 0 AND
								(
									`start_time` < {$this->tx_data['end_time']} AND
									`end_time` > {$this->tx_data['start_time']}
								)
				", 'fetch_one');
		if ( $num > 0)
			return 'error cross time';

		if (isset($this->block_data['time'])) // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		else // голая тр-ия
			$time = time()-30; // просто на всякий случай небольшой запас
		// У юзера должно либо вообще не быть cash_requests, либо должен быть последний со статусом approved. Иначе у него заморожен весь майнинг
		$error =  self::check_cash_requests ($this->tx_data['user_id'], $this->db);
		if ($error)
			return $error;

		// у юзера не должно быть обещанных сумм с for_repaid
		$error = $this->check_for_repaid($this->tx_data['user_id']);
		if ($error)
			return $error;

		// добавлять можно не более X запросов на добавление и удаление выходных за неделю
		$error = $this -> limit_requests( $this->variables['limit_holidays'], 'holidays', $this->variables['limit_holidays_period'] );
		if ($error)
			return $error;
	}

	// 26
	function new_holidays_rollback_front()
	{
		$this->limit_requests_rollback('holidays');
	}

	// 26
	function new_holidays_rollback() {

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."holidays`
				WHERE
					`user_id` = {$this->tx_data['user_id']} AND
					`start_time` = {$this->tx_data['start_time']} AND
					`end_time` = {$this->tx_data['end_time']}
					LIMIT 1
				");
		$this->rollbackAI('holidays');
	}

	// 26
	function new_holidays() {

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."holidays` (
						user_id,
						start_time,
						end_time
					) VALUES (
						{$this->tx_data['user_id']},
						{$this->tx_data['start_time']},
						{$this->tx_data['end_time']}
					)");
		$holidays_id = $this->db->getInsertId();

		// проверим, не наш ли это user_id
		$this->get_my_user_id($this->tx_data['user_id']);
		if ($this->tx_data['user_id'] == $this->my_user_id && $this->my_block_id <= $this->block_data['block_id']) {
				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."{$this->my_prefix}my_holidays`
						WHERE `start_time` = {$this->tx_data['start_time']} AND
								     `end_time` = {$this->tx_data['end_time']}
						");
		}
	}

	function del_forex_order_init()
	{
		$error = $this->get_tx_data(array('order_id', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
	}

	function del_cf_project_init()
	{
		$error = $this->get_tx_data(array('project_id', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
		$this->getPct();
	}

	function del_cf_project_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['project_id'], 'int') )
			return 'project_id';

		// проверим, есть ли такой проект
		$project_active = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']} AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `close_block_id` = 0 AND
							 `del_block_id` = 0
				", 'fetch_one' );
		if ( !$project_active )
			return '$project_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	function del_cf_project()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cf_projects`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `id` = {$this->tx_data['project_id']}
				");

		$project = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']}
				", 'fetch_array' );

		$points_status = array(0=>'user');
		// проходимся по всем фундерам и возращаем на их кошельки деньги
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `amount`,
									 `time`,
									 `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									`del_block_id` = 0
						ORDER BY `id` ASC
						");
		while ( $row =  $this->db->fetchArray( $res ) ) {
			// то, что выросло за время сбора
			$new_DC_sum = $row['amount'] + $this->calc_profit_ ( $row['amount'], $row['time'], $this->block_data['time'], $this->pct[$project['currency_id']], $points_status );
			// возврат
			$this -> update_recipient_wallet( $row['user_id'], $project['currency_id'], $new_DC_sum, 'cf_project', $this->tx_data['project_id'], 'cf_project' );
		}
	}

	function del_cf_project_rollback()
	{
		$project = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']}
				", 'fetch_array' );

		// проходимся по всем фундерам и возращаем на их кошельки деньги
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									 `del_block_id` = 0
						ORDER BY `id` DESC
						");
		while ( $row =  $this->db->fetchArray( $res ) ) {
			// откат возврата
			$this->general_rollback('wallets', $row['user_id'], "AND `currency_id` = {$project['currency_id']}");
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cf_projects`
				SET `del_block_id` = 0
				WHERE `id` = {$this->tx_data['project_id']}
				");
	}

	function del_cf_project_rollback_front()
	{
	}


	function del_cf_funding_init()
	{
		$error = $this->get_tx_data(array('funding_id', 'sign'));
		if ($error) return $error;
		$this->variables = self::get_all_variables($this->db);
		$this->getPct();
	}

	function del_cf_funding_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['funding_id'], 'int') )
			return 'funding_id';

		// проверим, есть ли funding для удаления
		$project_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `project_id`
				FROM `".DB_PREFIX."cf_funding`
				WHERE `id` = {$this->tx_data['funding_id']} AND
							 `user_id` =  {$this->tx_data['user_id']} AND
							 `del_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if (!$project_id)
			return 'funding_id';

		// проверим, на завершился ли уже проект
		$project_active = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$project_id} AND
							 `close_block_id` = 0 AND
							 `del_block_id` = 0
				", 'fetch_one' );
		if ( !$project_active )
			return '$project_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['funding_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	function mydctx_rollback()
	{
		// если работаем в режиме пула
		$community = get_community_users($this->db);
		if ($community) {
			for ($i=0; $i<sizeof($community); $i++) {

				$my_prefix = $community[$i].'_';
				// может захватиться несколько транзакций, но это не страшно, т.к. всё равно надо откатывать
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."{$my_prefix}my_dc_transactions`
						WHERE `block_id` = {$this->block_data['block_id']}
						");
				$AffectedRows = $this->db->getAffectedRows();
				$this->rollbackAI("{$my_prefix}my_dc_transactions", $AffectedRows);
			}
		}
		else {
			// может захватиться несколько транзакций, но это не страшно, т.к. всё равно надо откатывать
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."my_dc_transactions`
					WHERE `block_id` = {$this->block_data['block_id']}
					");
			$AffectedRows = $this->db->getAffectedRows();
			$this->rollbackAI("my_dc_transactions", $AffectedRows);
		}
	}

	function del_cf_funding()
	{
		// нужно учесть набежавшие %
		$funding_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							 `time`,
							 `project_id`,
							 `currency_id`
				FROM `".DB_PREFIX."cf_funding`
				WHERE `id` = {$this->tx_data['funding_id']}
				", 'fetch_array');
		$sum_and_pct = 0;
		$points_status = array(0=>'user');

		// то, что выросло за время сбора
		$sum_and_pct = $funding_data['amount'] + $this->calc_profit_ ( $funding_data['amount'], $funding_data['time'], $this->block_data['time'], $this->pct[$funding_data['currency_id']], $points_status );

		$this -> update_recipient_wallet( $this->tx_data['user_id'], $funding_data['currency_id'], $sum_and_pct, 'cf_project_refund', $funding_data['project_id'], 'cf_project_refund', 'decrypted' );

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cf_funding`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `id` = {$this->tx_data['funding_id']}
				");


	}

	function del_cf_funding_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."cf_funding`
				SET `del_block_id` = 0
				WHERE `id` = {$this->tx_data['funding_id']}
				");
		$funding_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							 `time`,
							 `currency_id`
				FROM `".DB_PREFIX."cf_funding`
				WHERE `id` = {$this->tx_data['funding_id']}
				", 'fetch_array');
		$this->general_rollback('wallets', $this->tx_data['user_id'], "AND `currency_id` = {$funding_data['currency_id']}");

		$this->mydctx_rollback();
	}

	function del_cf_funding_rollback_front()
	{
	}

	function del_forex_order_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['order_id'], 'int') )
			return 'order_id';

		// проверим, есть ли ордер для удаления
		$order_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."forex_orders`
				WHERE `id` = {$this->tx_data['order_id']} AND
							 `user_id` =  {$this->tx_data['user_id']} AND
							 `del_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if (!$order_id)
			return 'order_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['order_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

	}

	function del_forex_order()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."forex_orders`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `id` = {$this->tx_data['order_id']}
				");
	}

	function del_forex_order_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."forex_orders`
				SET `del_block_id` = 0
				WHERE `id` = {$this->tx_data['order_id']}
				");
	}

	function del_forex_order_rollback_front()
	{
	}

	function cf_send_dc_init()
	{
		$error = $this->get_tx_data(array('project_id', 'amount', 'commission', 'comment', 'sign'));
		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
		$this->getPct();
	}

	function cf_send_dc_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['project_id'], 'int') )
			return 'project_id';

		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'amount';

		if ( !check_input_data ($this->tx_data['commission'], 'amount') )
			return 'commission';

		if ($this->tx_data['amount']<0.01) // 0.01 - минимальная сумма
			return 'error min amount';

		if ( !check_input_data ($this->tx_data['comment'], 'comment') )
			return 'comment';

		// не закончился ли сбор средств
		$project_currency_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']} AND
							 `close_block_id` = 0 AND
							 `del_block_id` = 0
				", 'fetch_one' );
		if ( !$project_currency_id )
			return '!$project_currency_id';

		$this->tx_data['currency_id'] = $project_currency_id;
		$node_commission = $this->get_my_node_commission();

		// проверим, удовлетворяет ли нас комиссия, которую предлагает юзер
		if ( $this->tx_data['commission'] < $node_commission )
			return 'error commission';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']},{$this->tx_data['amount']},{$this->tx_data['commission']},".bin2hex($this->tx_data['comment'])."";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// Для защиты от несовместимости тр-ий cf_send_dc, new_forex_order, send_dc,cash_requests не могут быть в одном блоке (clear_incompatible_tx()). А cf_send_dc, new_forex_order,cash_requests могут быть только в единичном кол-ве в одном блоке от одного юзера.

		// есть ли нужная сумма в кошельке
		$this->tx_data['from_user_id'] = $this->tx_data['user_id'];
		$error = $this->check_sender_money();
		if ($error)
			return $error;

;

		// разрешено отправлять не более 10-и таких тр-ий за сутки
		$error = $this -> limit_requests(limit_cf_send_dc, 'cf_send_dc', limit_cf_send_dc_period);
		if ($error)
			return $error;

	}

	function cf_send_dc()
	{
		$project = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							  `end_time`,
							  `currency_id`,
							  `project_currency_name`,
							  `user_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']}
				", 'fetch_array' );

		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->block_data['user_id']);
		// возможно нужно обновить таблицу points_status
		$this->points_update_main($this->tx_data['user_id']);

		// начисляем комиссию майнеру, который этот блок сгенерил
		if ($this->tx_data['commission']>=0.01) {
			$this -> update_recipient_wallet( $this->block_data['user_id'], $project['currency_id'], $this->tx_data['commission'], 'node_commission', $this->block_data['block_id'] );
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."cf_funding` (
					`project_id`,
					`user_id`,
					`amount`,
					`currency_id`,
					`time`,
					`block_id`
				)
				VALUES (
					{$this->tx_data['project_id']},
					{$this->tx_data['user_id']},
					{$this->tx_data['amount']},
					{$project['currency_id']},
					{$this->block_data['time']},
					{$this->block_data['block_id']}
				)");
		$funding_id = $this->db->getInsertId ();

		// обновим сумму на кошельке отправителя, залогировав предыдущее значение
		$this -> update_sender_wallet($this->tx_data['user_id'], $project['currency_id'], $this->tx_data['amount'], $this->tx_data['commission'], 'cf_project', $this->tx_data['user_id'], $funding_id, bin2hex($this->tx_data['comment']), 'encrypted');


		// если время сбора средств закончилось
		if ($project['end_time'] <= $this->block_data['time']) {

			// закрываем проект
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cf_projects`
					SET `close_block_id` = {$this->block_data['block_id']}
					WHERE `id` = {$this->tx_data['project_id']}
					");

			$sum = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`amount`)
					FROM `".DB_PREFIX."cf_funding`
					WHERE `project_id` = {$this->tx_data['project_id']} AND
								 `del_block_id` = 0
					", 'fetch_one' );

			$points_status = array(0=>'user');

			// нужная сумма набрана
			if ($sum >= $project['amount']) {

				// запишем в таблицу CF-валют новую валюту и получим ID
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."cf_currency` (
							`name`,
							`project_id`
						)
						VALUES (
							'{$project['project_currency_name']}',
							{$this->tx_data['project_id']}
						)");
				$project_currency_id = $this->db->getInsertId ();

				// начисляем общую сумму на кошелек автора проекта
				// а также, начисляем бэкерам валюту проекта
				// нужно учесть набежавшие %
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `amount`,
									 `time`,
									 `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									 `del_block_id` = 0
						ORDER BY `id` ASC
						");
				$sum_and_pct_author = 0;
				while ( $row =  $this->db->fetchArray( $res ) ) {
					// то, что выросло за время сбора
					$amount_and_pct = $row['amount'] + $this->calc_profit_ ( $row['amount'], $row['time'], $this->block_data['time'], $this->pct[$project['currency_id']], $points_status );

					// автору проекта обычные DC
					$sum_and_pct_author += $amount_and_pct;

					// бэкерам - валюта проекта
					$this -> update_recipient_wallet( $row['user_id'], $project_currency_id, $amount_and_pct, 'cf_project', $this->tx_data['project_id'], 'cf_project' );
				}
				// автору - DC
				$this -> update_recipient_wallet( $project['user_id'], $project['currency_id'], $sum_and_pct_author, 'cf_project', $this->tx_data['project_id'], 'cf_project' );
			}
			else { // нужная сумма не набрана

				// проходимся по всем фундерам и возращаем на их кошельки деньги
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `amount`,
									 `time`,
									 `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									 `del_block_id` = 0
						ORDER BY `id` ASC
						");
				while ( $row =  $this->db->fetchArray( $res ) ) {
					// то, что выросло за время сбора
					$new_DC_sum = $row['amount'] + $this->calc_profit_ ( $row['amount'], $row['time'], $this->block_data['time'], $this->pct[$project['currency_id']], $points_status );
					// возврат
					$this -> update_recipient_wallet( $row['user_id'], $project['currency_id'], $new_DC_sum, 'cf_project', $this->tx_data['project_id'], 'cf_project' );
				}
			}
		}
	}

	function cf_send_dc_rollback()
	{
		$project = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`,
							  `end_time`,
							  `currency_id`,
							  `project_currency_name`,
							  `user_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `id` = {$this->tx_data['project_id']}
				", 'fetch_array' );

		// если время сбора средств закончилось
		if ($project['end_time'] <= $this->block_data['time']) {

			$sum = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT sum(`amount`)
					FROM `".DB_PREFIX."cf_funding`
					WHERE `project_id` = {$this->tx_data['project_id']} AND
								 `del_block_id` = 0
					", 'fetch_one' );

			// нужная сумма набрана
			if ($sum >= $project['amount']) {
				// откатываем начисление общей суммы на кошелек автора проекта
				$this->general_rollback('wallets', $project['user_id'], "AND `currency_id` = {$project['currency_id']}");

				// узнаем ID валюты, которая была создана
				$project_currency_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `id`
						FROM `".DB_PREFIX."cf_currency`
						WHERE `name` = '{$project['project_currency_name']}'
						", 'fetch_one' );

				// проходимся по всем фундерам и забираем у них начисленную валюту проекта
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									 `del_block_id` = 0
						ORDER BY `id` DESC
						");
				while ( $row =  $this->db->fetchArray( $res ) ) {
					// откат возврата
					$this->general_rollback('wallets', $row['user_id'], "AND `currency_id` = {$project_currency_id}");
				}

				// Удаляем созданную валюту
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."cf_currency`
						WHERE `name` = '{$project['project_currency_name']}'
						");
				$this->rollbackAI('cf_currency');

			}
			else { // нужная сумма не набрана

				// проходимся по всем фундерам и возращаем на их кошельки деньги
				$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."cf_funding`
						WHERE `project_id` = {$this->tx_data['project_id']} AND
									 `del_block_id` = 0
						ORDER BY `id` DESC
						");
				while ( $row =  $this->db->fetchArray( $res ) ) {
					// откат возврата
					$this->general_rollback('wallets', $row['user_id'], "AND `currency_id` = {$project['currency_id']}");
				}
			}

			// откатываем закрытие проекта
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."cf_projects`
					SET `close_block_id` = 0
					WHERE `id` = {$this->tx_data['project_id']}
					");

		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."cf_funding`
				WHERE `block_id` = {$this->block_data['block_id']} AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `project_id` = {$this->tx_data['project_id']}
				LIMIT 1
				");
		$this->rollbackAI('cf_funding');

		// откат списания средств с кошелька фундера
		$this->general_rollback('wallets', $this->tx_data['user_id'], "AND `currency_id` = {$project['currency_id']}");

		// откат комиссии
		if ($this->tx_data['commission']>=0.01) {
			$this->general_rollback('wallets', $this->block_data['user_id'], "AND `currency_id` = {$project['currency_id']}");
		}

		// возможно нужно откатить таблицу points_status
		$this->points_update_rollback_main($this->tx_data['user_id']);
		$this->points_update_rollback_main($this->block_data['user_id']);

		$this->mydctx_rollback();
	}

	function cf_send_dc_rollback_front()
	{
		$this->limit_requests_rollback('cf_send_dc');
	}

	function user_avatar_init()
	{
		$error = $this->get_tx_data(array('name', 'avatar', 'sign'));
		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
	}

	function user_avatar_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['name'], 'user_name'))
			return 'name';

		if ( !check_input_data ($this->tx_data['avatar'], 'img_url') && $this->tx_data['avatar']!=='0')
			return 'avatar';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['name']},{$this->tx_data['avatar']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// разрешено отправлять не более 10-и таких тр-ий за сутки
		$error = $this -> limit_requests(limit_user_avatar, 'user_avatar', limit_user_avatar_period);
		if ($error)
			return $error;

	}
	function user_avatar()
	{
		$this->selective_logging_and_upd (array('name', 'avatar'), array($this->tx_data['name'], $this->tx_data['avatar']), 'users', array('user_id'), array($this->tx_data['user_id']));
	}

	function user_avatar_rollback()
	{
		$this->selective_rollback (array('name', 'avatar'), 'users', "`user_id`={$this->tx_data['user_id']}");
	}

	function user_avatar_rollback_front()
	{
		$this -> limit_requests_rollback( 'user_avatar' );
	}


	function cf_project_change_category_init()
	{
		$error = $this->get_tx_data(array('project_id', 'category_id', 'sign'));
		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
	}

	function cf_project_change_category_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['project_id'], 'int') )
			return 'project_id';
		if ( !check_input_data ($this->tx_data['category_id'], 'tinyint') )
			return 'category_id';

		// является ли юзер владельцем данного проекта и есть ли вообще такой проект
		$project_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `id` = {$this->tx_data['project_id']}
				LIMIT 1
				", 'fetch_one' );
		if ( !$project_user_id )
			return 'project_user_id';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']},{$this->tx_data['category_id']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// разрешено отправлять не более 10-и таких тр-ий за сутки
		$error = $this -> limit_requests(limit_cf_project_data, 'cf_project_data', limit_cf_project_data_period);
		if ($error)
			return $error;
	}

	function cf_project_change_category()
	{
		$this->selective_logging_and_upd (
			array('category_id'),
			array($this->tx_data['category_id']),
			'cf_projects',
			array('id'),
			array($this->tx_data['project_id'])
		);
	}

	function cf_project_change_category_rollback()
	{
		$this->selective_rollback (array('category_id'), 'cf_projects', "`id`={$this->tx_data['project_id']}");
	}

	function cf_project_change_category_rollback_front()
	{
		$this->limit_requests_rollback('cf_project_data');
	}


	/*
	 * Описание проекта на разных языках
	 * */
	function cf_project_data_init()
	{
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 134261)
			$error = $this->get_tx_data(array('project_id', 'lang_id', 'blurb_img', 'head_img', 'description_img', 'picture', 'video_type', 'video_url_id', 'news_img', 'links', 'sign'));
		else
			$error = $this->get_tx_data(array('project_id', 'lang_id', 'blurb_img', 'head_img', 'description_img', 'picture', 'video_type', 'video_url_id', 'news_img', 'links', 'hide', 'sign'));


		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
	}

	function cf_project_data_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['project_id'], 'int') )
			return 'project_id';
		if ( !check_input_data ($this->tx_data['lang_id'], 'tinyint') )
			return 'lang_id';
		if ( !check_input_data ($this->tx_data['blurb_img'], 'img_url') && $this->tx_data['blurb_img']!=='0')
			return 'blurb_img';
		if ( !check_input_data ($this->tx_data['head_img'], 'img_url') && $this->tx_data['head_img']!=='0')
			return 'head_img';
		if ( !check_input_data ($this->tx_data['description_img'], 'img_url') && $this->tx_data['description_img']!=='0')
			return 'description_img';
		if ( !check_input_data ($this->tx_data['picture'], 'img_url') && $this->tx_data['picture']!=='0')
			return 'picture';
		if ( !check_input_data ($this->tx_data['video_type'], 'video_type') && $this->tx_data['video_type']!=='0' )
			return 'video_type';
		if ( !check_input_data ($this->tx_data['video_url_id'], 'video_url_id') && $this->tx_data['video_url_id']!=='0' )
			return 'video_url_id';
		if ( !check_input_data ($this->tx_data['news_img'], 'img_url') && $this->tx_data['news_img']!=='0' )
			return 'news_img';
		if ( !check_input_data ($this->tx_data['links'], 'cf_links') && $this->tx_data['links']!=='0' )
			return 'links';

		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 134261) {
			$this->tx_data['hide'] = 0;
		}
		else {
			if ( !check_input_data ($this->tx_data['hide'], 'boolean'))
				return 'hide';
		}
		// является ли юзер владельцем данного проекта и есть ли вообще такой проект
		$project_user_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `id` = {$this->tx_data['project_id']}
				LIMIT 1
				", 'fetch_one' );
		if ( !$project_user_id )
			return 'project_user_id';

		// проверяем подпись
		if (isset($this->block_data['block_id']) && $this->block_data['block_id'] < 134261)
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']},{$this->tx_data['lang_id']},{$this->tx_data['blurb_img']},{$this->tx_data['head_img']},{$this->tx_data['description_img']},{$this->tx_data['picture']},{$this->tx_data['video_type']},{$this->tx_data['video_url_id']},{$this->tx_data['news_img']},{$this->tx_data['links']}";
		else
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']},{$this->tx_data['lang_id']},{$this->tx_data['blurb_img']},{$this->tx_data['head_img']},{$this->tx_data['description_img']},{$this->tx_data['picture']},{$this->tx_data['video_type']},{$this->tx_data['video_url_id']},{$this->tx_data['news_img']},{$this->tx_data['links']},{$this->tx_data['hide']}";

		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// разрешено отправлять не более 10-и таких тр-ий за сутки
		$error = $this -> limit_requests(limit_cf_project_data, 'cf_project_data', limit_cf_project_data_period);
		if ($error)
			return $error;
	}

	function cf_project_data()
	{
		// если описания проекта на этом языке еще нет, то добавляем, иначе - логируем и обновляем
		$this->selective_logging_and_upd (
			array('blurb_img', 'head_img', 'description_img', 'picture', 'video_type', 'video_url_id', 'news_img', 'links', 'hide'),
			array($this->tx_data['blurb_img'], $this->tx_data['head_img'], $this->tx_data['description_img'], $this->tx_data['picture'], $this->tx_data['video_type'], $this->tx_data['video_url_id'], $this->tx_data['news_img'], $this->tx_data['links'], $this->tx_data['hide']),
			'cf_projects_data',
			array('project_id', 'lang_id'),
			array($this->tx_data['project_id'], $this->tx_data['lang_id'])
		);
	}

	function cf_project_data_rollback()
	{
		$this->selective_rollback (array('blurb_img', 'head_img', 'description_img', 'picture', 'video_type', 'video_url_id', 'news_img', 'links', 'hide'), 'cf_projects_data', "`project_id`={$this->tx_data['project_id']} AND `lang_id` = {$this->tx_data['lang_id']}", true);
	}

	function cf_project_data_rollback_front()
	{
		$this->limit_requests_rollback('cf_project_data');
	}

	function cf_comment_init()
	{
		$error = $this->get_tx_data(array('project_id', 'lang_id', 'comment', 'sign'));
		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
	}


	function cf_comment_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['project_id'], 'int') )
			return 'project_id';
		if ( !check_input_data ($this->tx_data['lang_id'], 'tinyint') || $this->tx_data['lang_id']<=0 )
			return 'lang_id';
		$this->tx_data['comment'] = $this->db->escape($this->tx_data['comment']);
		if ( !check_input_data ($this->tx_data['comment'], 'cf_comment') )
			return 'comment';


		if (isset($this->block_data['time'])) { // тр-ия пришла в блоке
			$time = $this->block_data['time'];
		}
		else { // голая тр-ия с запасом 30 сек на время генерации блока. Т.к. при попадинии в блок время будет уже другим
			$time = time() - 30;
		}

		// автор проекта может писать по 1 комменту за каждую языковую версию
		$author = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `id` = {$this->tx_data['project_id']}
				LIMIT 1
				", 'fetch_one' );
		if ($author)
			$add_sql = " AND `lang_id` = {$this->tx_data['lang_id']}";
		else
			$add_sql = "";
		// проверим, есть ли у данного юзера другие комменты за данный проект
		$comment_time = (int) $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT max(`time`)
				FROM `".DB_PREFIX."cf_comments`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `project_id` = {$this->tx_data['project_id']}
							 {$add_sql}
				LIMIT 1
				", 'fetch_one' );

		// в 1 проект можно писать только 1 комммент в сутки
		if ($time - $comment_time < limit_time_comments_cf_project)
			return '$comment_time';

		// финансировал ли данный юзер этот проект
		$funder = (int) $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_funding`
				WHERE `user_id` = {$this->tx_data['user_id']} AND
							 `project_id` = {$this->tx_data['project_id']}
				LIMIT 1
				", 'fetch_one' );
		if (!$funder) {
			//  или может быть он его автор
			$author = (int) $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`
					FROM `".DB_PREFIX."cf_projects`
					WHERE `user_id` = {$this->tx_data['user_id']} AND
								 `id` = {$this->tx_data['project_id']}
					LIMIT 1
					", 'fetch_one' );
			if (!$author)
				return '!$funder || !$author';
		}

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['project_id']},{$this->tx_data['lang_id']},{$this->tx_data['comment']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// разрешено создавать не более 10 комментов за разные проекты за сутки
		$error = $this -> limit_requests(limit_cf_comments, 'cf_comments', limit_cf_comments_period);
		if ($error)
			return $error;
	}

	function cf_comment()
	{

		$this->tx_data['comment'] = $this->db->escape($this->tx_data['comment']);
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."cf_comments` (
					`user_id`,
					`project_id`,
					`lang_id`,
					`comment`,
					`time`,
					`block_id`
				)
				VALUES (
					{$this->tx_data['user_id']},
					{$this->tx_data['project_id']},
					{$this->tx_data['lang_id']},
					'{$this->tx_data['comment']}',
					{$this->block_data['time']},
					{$this->block_data['block_id']}
				)");
	}

	function cf_comment_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."cf_comments`
				WHERE `block_id` = {$this->block_data['block_id']} AND
							 `user_id` = {$this->tx_data['user_id']} AND
							 `project_id` = {$this->tx_data['project_id']} AND
							 `lang_id` = {$this->tx_data['lang_id']}
				LIMIT 1
				");
		$this->rollbackAI('cf_comments');
	}

	function cf_comment_rollback_front()
	{
		$this->limit_requests_rollback('cf_comments');
	}

	function new_cf_project_init()
	{
		$error = $this->get_tx_data(array('currency_id', 'amount', 'end_time', 'latitude', 'longitude', 'category_id', 'project_currency_name', 'sign'));
		if ($error) return $error;
		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$this->variables = self::get_all_variables($this->db);
	}

	function new_cf_project_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['currency_id'], 'int') )
			return 'currency_id';
		if ( !check_input_data ($this->tx_data['amount'], 'int') )
			return 'amount';
		if ( !check_input_data ($this->tx_data['end_time'], 'int') )
			return 'end_time';
		if ( !check_input_data ($this->tx_data['latitude'], 'coordinate') )
			return 'latitude';
		if ( !check_input_data ($this->tx_data['longitude'], 'coordinate') )
			return 'longitude';
		if ( !check_input_data ($this->tx_data['category_id'], 'smallint') )
			return 'category_id';
		if ( !check_input_data ($this->tx_data['project_currency_name'], 'cf_currency_name') )
			return 'project_currency_name';

		if (isset($this->block_data['time'])) { // тр-ия пришла в блоке
			$time1 = $this->block_data['time'];
			$time2 = $time1;
		}
		else { // голая тр-ия с запасом 30 сек на время генерации блока. Т.к. при попадинии в блок время будет уже другим
			$time1 = time()-30;
			$time2 = time()+30;
		}
		// дата завершения проекта не может быть более чем на 91 дней и менее чем на 6 дней вперед от текущего времени
		if ($this->tx_data['end_time'] - $time1 > 3600*24*91 || $this->tx_data['end_time'] - $time2 < 3600*24*6)
			return 'bad end_time';

		if (!$this->checkCurrency($this->tx_data['currency_id']))
			return 'bad currency';

		if ( $this->tx_data['amount'] <= 0 )
			return 'amount=0';

		// проверим, не занято ли имя валюты
		$currency = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_projects`
				WHERE `project_currency_name` = '{$this->tx_data['project_currency_name']}' AND
							 `close_block_id` = 0 AND
							 `del_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if ($currency)
			return 'exists project_currency_name';

		// проверим, не занято ли имя валюты
		$currency = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."cf_currency`
				WHERE `name` = '{$this->tx_data['project_currency_name']}'
				LIMIT 1
				", 'fetch_one' );
		if ($currency)
			return 'exists cf_currency';

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['currency_id']},{$this->tx_data['amount']},{$this->tx_data['end_time']},{$this->tx_data['latitude']},{$this->tx_data['longitude']},{$this->tx_data['category_id']},{$this->tx_data['project_currency_name']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		// разрешено создавать 1 проект в неделю
		$error = $this -> limit_requests(limit_new_cf_project, 'new_cf_project', limit_new_cf_project_period);
		if ($error)
			return $error;
	}

	function new_cf_project()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."cf_projects` (
					`user_id`,
					`currency_id`,
					`amount`,
					`project_currency_name`,
					`start_time`,
					`end_time`,
					`latitude`,
					`longitude`,
					`category_id`,
					`block_id`
				)
				VALUES (
					{$this->tx_data['user_id']},
					{$this->tx_data['currency_id']},
					{$this->tx_data['amount']},
					'{$this->tx_data['project_currency_name']}',
					{$this->block_data['time']},
					{$this->tx_data['end_time']},
					{$this->tx_data['latitude']},
					{$this->tx_data['longitude']},
					{$this->tx_data['category_id']},
					{$this->block_data['block_id']}
				)");
	}

	function new_cf_project_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."cf_projects`
				WHERE `block_id` = {$this->block_data['block_id']} AND
							 `user_id` = {$this->tx_data['user_id']}
				LIMIT 1
				");
		$this->rollbackAI('cf_projects');
	}

	function new_cf_project_rollback_front()
	{
		$this->limit_requests_rollback('new_cf_project');
	}

	function new_forex_order_init()
	{
		$error = $this->get_tx_data(array('sell_currency_id', 'sell_rate', 'amount', 'buy_currency_id', 'commission', 'sign'));
		if ($error) return $error;
		/*
		sell_currency_id Что продается
		sell_rate По какому курсу к buy_currency_id
		amount сколько продается
		buy_currency_id Какая валюта нужна
		commission Сколько готовы отдать комиссию ноду-генератору
		*/

		debug_print($this->tx_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//$this->variables = self::get_variables( $this->db, array( 'points_factor', 'limit_votes_complex_period' ) );
		$this->variables = self::get_all_variables($this->db);
		$this->getPct();
	}


	function limit_requests_money_orders ($limit)
	{
		$num = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`tx_hash`)
				FROM `".DB_PREFIX."log_time_money_orders`
				WHERE `user_id` = '{$this->tx_data['user_id']}' AND
							 `del_block_id` = 0
				LIMIT 1
				", 'fetch_one' );
		if ( $num >=$limit ) {
			return "[limit_requests] log_time_money_orders {$num} >={$limit}\n";
		}
		else {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_time_money_orders` (
							`tx_hash`,
							`user_id`
						)
						VALUES (
							0x{$this->tx_hash},
							{$this->tx_data['user_id']}
						)");
		}
	}

	function limit_requests_money_orders_rollback()
	{
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_time_money_orders`
		        WHERE `tx_hash` = 0x{$this->tx_hash}
				LIMIT 1
		        ");
	}

	// нельзя отправить более 10-и ордеров от 1 юзера в 1 блоке с суммой менее эквивалента 0.1$ по текущему курсу этой валюты.
	function check_spam_money ($currency_id)
	{
		if ($currency_id == USD_CURRENCY_ID) {
			if ($this->tx_data['amount'] < 0.1) {
				$error = $this->limit_requests_money_orders(10);
				if ($error)
					return $error;
			}
		} else {
			// если валюта не доллары, то нужно получить эквивалент на бирже
			$dollar_eq_rate = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `sell_rate`
					FROM `".DB_PREFIX."forex_orders`
					WHERE `sell_currency_id` = {$currency_id} AND
								 `buy_currency_id` = ".USD_CURRENCY_ID."
					", 'fetch_one');
			// эквивалент 0.1$
			if ($dollar_eq_rate>0) {
				$min_amount = (0.1/$dollar_eq_rate);
				if ($this->tx_data['amount'] < $min_amount) {
					$error = $this->limit_requests_money_orders(10);
					if ($error)
						return $error;
				}
			}
		}
	}

	function new_forex_order_front()
	{
		$error = $this -> general_check();
		if ($error)
			return $error;

		if ( !check_input_data ($this->tx_data['sell_currency_id'], 'int') )
			return 'sell_currency_id';
		if ( !check_input_data ($this->tx_data['sell_rate'], 'sell_rate') )
			return 'sell_rate';
		if ( !check_input_data ($this->tx_data['amount'], 'amount') )
			return 'amount';
		if ( !check_input_data ($this->tx_data['buy_currency_id'], 'int') )
			return 'buy_currency_id';
		if ( !check_input_data ($this->tx_data['commission'], 'amount') )
			return 'commission';

		if ( $this->tx_data['sell_currency_id'] == $this->tx_data['buy_currency_id'] )
			return 'sell_currency_id == buy_currency_id';
		if ( $this->tx_data['sell_rate'] == 0 )
			return 'sell_rate=0';
		if ( $this->tx_data['amount'] == 0 )
			return 'amount=0';
		if ( $this->tx_data['amount'] * $this->tx_data['sell_rate'] < 0.01 )
			return 'amount * sell_rate < 0.01';

		if (!$this->checkCurrency($this->tx_data['sell_currency_id']) || !$this->checkCurrency($this->tx_data['buy_currency_id']))
			return 'bad currency';

		$this->tx_data['currency_id'] = $this->tx_data['sell_currency_id'];
		$node_commission = $this->get_my_node_commission();

		// проверим, удовлетворяет ли нас комиссия, которую предлагает юзер
		if ( $this->tx_data['commission'] < $node_commission )
			return 'error commission ('.$this->tx_data['commission'].' < '.$node_commission.')';

		// если ли нужная сумма на кошельке
		$this->tx_data['from_user_id'] = $this->tx_data['user_id'];
		$error = $this->check_sender_money();
		if ($error)
			return $error;

		// проверяем подпись
		$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['sell_currency_id']},{$this->tx_data['sell_rate']},{$this->tx_data['amount']},{$this->tx_data['buy_currency_id']},{$this->tx_data['commission']}";
		$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
		if ($error)
			return $error;

		$error = $this->check_spam_money($this->tx_data['sell_currency_id']);
		if ($error)
			return $error;

	}

	function new_forex_order()
	{

		// нужно отметить в log_time_money_orders, что тр-ия прошла в блок
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."log_time_money_orders`
				SET `del_block_id` = {$this->block_data['block_id']}
				WHERE `tx_hash` = 0x{$this->tx_hash}
				");

		// логируем, чтобы можно было делать откат. Важен только сам ID
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_forex_orders_main` (
					`block_id`
				) VALUES (
					{$this->block_data['block_id']}
				)");
		$main_id = $this->db->getInsertId ();

		// обратный курс. нужен для поиска по ордерам
		$reverse_rate = 1 / $this->tx_data['sell_rate'];
		// сколько хотим купить валюты buy_currency_id
		//$total_buy_amount = $this->tx_data['amount'] * $reverse_rate;
		$total_buy_amount = $this->tx_data['amount'] * $this->tx_data['sell_rate'];

		// прежде всего начислим комиссию ноду-генератору
		if ($this->tx_data['commission']>=0.01) {
			debug_print("this->tx_data['commission']>=0.01", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$LOG_MARKER = 'new_forex_order - update_sender_wallet - tx_data[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($this->tx_data['user_id']);
			$this -> update_sender_wallet( $this->tx_data['user_id'], $this->tx_data['sell_currency_id'], $this->tx_data['commission'], 0, 'from_user', $this->tx_data['user_id'], $this->block_data['user_id'], "node_commission", 'decrypted');

			$LOG_MARKER = 'new_forex_order - update_recipient_wallet - block_data[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($this->block_data['user_id']);
			$this -> update_recipient_wallet( $this->block_data['user_id'], $this->tx_data['sell_currency_id'], $this->tx_data['commission'], 'node_commission', $this->block_data['block_id'] );
		}

		// берем из БД только те ордеры, которые удовлетворяют нашим требованиям
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."forex_orders`
				WHERE `buy_currency_id` = {$this->tx_data['sell_currency_id']} AND
							 `sell_rate` <= {$reverse_rate} AND
							 `sell_currency_id` = {$this->tx_data['buy_currency_id']}  AND
							 `del_block_id` = 0 AND
							 `empty_block_id` = 0
				");
		while ( $row =  $this->db->fetchArray( $res ) ) {

			debug_print('[FX]:'.$this->tx_hash, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// удовлетворит ли данный ордер наш запрос целиком
			if ($row['amount'] >= $total_buy_amount)
				$debit = $total_buy_amount;
			else
				$debit = $row['amount'];

			debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print("total_buy_amount = {$total_buy_amount}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print("debit = {$debit}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			if (($row['amount'] - $debit) < 0.01) { // ордер опустошили
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."forex_orders`
						SET `amount` = 0,
							   `empty_block_id` = {$this->block_data['block_id']}
						WHERE `id` = {$row['id']}
						");
			}
			else {
				// вычитаем забранную сумму из ордера
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."forex_orders`
						SET `amount` = `amount` - {$debit}
						WHERE `id` = {$row['id']}
						");
			}


			// логируем данную операцию
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					INSERT INTO `".DB_PREFIX."log_forex_orders` (
						`main_id`,
						`order_id`,
						`amount`,
						`to_user_id`,
						`block_id`
					)
					VALUES (
						{$main_id},
						{$row['id']},
						{$debit},
						{$this->tx_data['user_id']},
						{$this->block_data['block_id']}
					)");

			# Продавец валюты (тот, чей ордер обработали)

			// сколько продавец данного ордера продал валюты
			$seller_sell_amount = $debit;
			// сколько продавец получил с продажи суммы $seller_sell_amount по его курсу
			$seller_buy_amount = $seller_sell_amount * $row['sell_rate'];

			debug_print("seller_buy_amount = {$seller_buy_amount}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print("total_buy_amount={$total_buy_amount}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print("row['buy_currency_id']={$row['buy_currency_id']}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// списываем валюту, которую продавец продал (U)
			$LOG_MARKER = 'new_forex_order - update_sender_wallet - $row[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($row['user_id']);
			$this -> update_sender_wallet($row['user_id'], $row['sell_currency_id'], $seller_sell_amount, 0, 'from_user', $row['user_id'], $this->tx_data['user_id'], "order # {$row['id']}", 'decrypted');
			debug_print("update_sender_wallet ok", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// начисляем валюту, которую продавец получил (R)
			$LOG_MARKER = 'new_forex_order - update_recipient_wallet - $row[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($row['user_id']);
			$this -> update_recipient_wallet( $row['user_id'], $row['buy_currency_id'], $seller_buy_amount, 'from_user', $this->tx_data['user_id'], "order # {$row['id']}", 'decrypted');
			debug_print("update_recipient_wallet ok", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			# Покупатель валюты (наш юзер)

			// списываем валюту, которую мы продали (R)
			$LOG_MARKER = 'new_forex_order - update_sender_wallet - tx_data[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($this->tx_data['user_id']);
			$this -> update_sender_wallet($this->tx_data['user_id'], $row['buy_currency_id'], $seller_buy_amount, 0, 'from_user', $this->tx_data['user_id'], $row['user_id'], "order # {$row['id']}", 'decrypted');
			debug_print("update_sender_wallet ok2", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// начисляем валюту, которую мы получили (U)
			$LOG_MARKER = 'new_forex_order - update_recipient_wallet - tx_data[user_id]';
			// возможно нужно обновить таблицу points_status
			$this->points_update_main($this->tx_data['user_id']);
			$this -> update_recipient_wallet( $this->tx_data['user_id'], $row['sell_currency_id'], $seller_sell_amount, 'from_user', $row['user_id'], "order # {$row['id']}", 'decrypted');
			debug_print("update_recipient_wallet ok2", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$total_buy_amount-=$row['amount'];

			if ($row['amount'] >= $total_buy_amount)
				break; // проход по ордерам прекращаем, т.к. наш запрос удовлетворен
			///else
			//	$total_buy_amount-=$row['amount'];
		}

		// если после прохода по всем имеющимся ордерам мы не набрали нужную сумму, то создаем свой ордер
		if ($total_buy_amount > 0) {

			$new_order_amount = $total_buy_amount * (1 / $this->tx_data['sell_rate']);
			if ($new_order_amount >= 0.01) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."forex_orders` (
							`user_id`,
							`sell_currency_id`,
							`sell_rate`,
							`amount`,
							`buy_currency_id`,
							`commission`
						)
						VALUES (
							{$this->tx_data['user_id']},
							{$this->tx_data['sell_currency_id']},
							{$this->tx_data['sell_rate']},
							{$new_order_amount},
							{$this->tx_data['buy_currency_id']},
							{$this->tx_data['commission']}
						)");
				$order_id = $this->db->getInsertId ();

				// логируем данную операцию. amount не указывается, т.к. при откате будет просто удалена запись из forex_orders
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_forex_orders` (
							`main_id`,
							`order_id`,
							`new`,
							`block_id`
						)
						VALUES (
							{$main_id},
							{$order_id},
							1,
							{$this->block_data['block_id']}
						)");
			}
		}
	}

	function new_forex_order_rollback()
	{

		// нужно отметить в log_time_money_orders, что тр-ия НЕ прошла в блок
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."log_time_money_orders`
				SET `del_block_id` = 0
				WHERE `tx_hash` = 0x{$this->tx_hash}
				");

		// откат всегда идет по последней записи в log_forex_orders_main
		$main_id = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."log_forex_orders_main`
				ORDER BY `id` DESC
				LIMIT 1
				", 'fetch_one');

		// проходимся по всем ордерам, которые затронула данная тр-ия
		$res = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT  `".DB_PREFIX."log_forex_orders`.`amount`,
							  `".DB_PREFIX."log_forex_orders`.`id`,
							  `empty_block_id`,
							  `order_id`,
							  `user_id`,
							  `to_user_id`,
							  `new`,
							  `commission`,
							  `buy_currency_id`,
							  `sell_rate`,
							  `sell_currency_id`
				FROM `".DB_PREFIX."log_forex_orders`
				LEFT JOIN `".DB_PREFIX."forex_orders` ON `".DB_PREFIX."log_forex_orders`.`order_id` = `".DB_PREFIX."forex_orders`.`id`
				WHERE `main_id` = {$main_id}
				ORDER BY `".DB_PREFIX."log_forex_orders`.`id` DESC
				");
		while ( $row =  $this->db->fetchArray( $res ) ) {

			debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_forex_orders`
						WHERE `id` = {$row['id']}
						LIMIT 1
						");
			$this->rollbackAI('log_forex_orders');

			// если это создание нового ордера, то просто удалим его
			if ($row['new']) {
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."forex_orders`
						WHERE `id` = {$row['order_id']}
						LIMIT 1
						");
				$this->rollbackAI('forex_orders');
				// берем следующий ордер
				// никаких движений средств не произошло, откатывать кошельки не нужно
			}
			else {

				$add_sql = '';
				if ($row['empty_block_id']==$this->block_data['block_id'])
					$add_sql = ', `empty_block_id` = 0';
				// вернем amount ордеру
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."forex_orders`
						SET `amount` = `amount` + {$row['amount']} {$add_sql}
						WHERE `id` = {$row['order_id']}
						");

				// откатываем покупателя (наш юзер)
				$LOG_MARKER = 'new_forex_order_rollback - general_rollback - $row[to_user_id] sell_currency_id';
				debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				// возможно нужно обновить таблицу points_status
				$this->points_update_rollback_main($row['to_user_id']);
				$this->general_rollback('wallets', $row['to_user_id'], "AND `currency_id` = {$row['sell_currency_id']}");

				$LOG_MARKER = 'new_forex_order_rollback - general_rollback - $row[to_user_id] buy_currency_id';
				debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this->general_rollback('wallets', $row['to_user_id'], "AND `currency_id` = {$row['buy_currency_id']}");

				// откатим продавца
				$LOG_MARKER = 'new_forex_order_rollback - general_rollback - $row[user_id] buy_currency_id';
				debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				// возможно нужно обновить таблицу points_status
				$this->points_update_rollback_main($row['user_id']);
				$this->general_rollback('wallets', $row['user_id'], "AND `currency_id` = {$row['buy_currency_id']}");
				$LOG_MARKER = 'new_forex_order_rollback - general_rollback - $row[user_id] sell_currency_id';
				debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this->general_rollback('wallets', $row['user_id'], "AND `currency_id` = {$row['sell_currency_id']}");

			}
		}

		// откатим комиссию ноду-генератору
		if ($this->tx_data['commission']>=0.01) {
			debug_print("this->tx_data['commission']>0.01", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$LOG_MARKER = 'new_forex_order_rollback - general_rollback - block_data[user_id] sell_currency_id';
			debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// возможно нужно обновить таблицу points_status
			$this->points_update_rollback_main($this->block_data['user_id']);
			$this->general_rollback('wallets', $this->block_data['user_id'], "AND `currency_id` = {$this->tx_data['sell_currency_id']}");
			$LOG_MARKER = 'new_forex_order_rollback - general_rollback - tx_data[user_id] sell_currency_id';
			debug_print($LOG_MARKER, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// возможно нужно обновить таблицу points_status
			$this->points_update_rollback_main($this->tx_data['user_id']);
			$this->general_rollback('wallets', $this->tx_data['user_id'], "AND `currency_id` = {$this->tx_data['sell_currency_id']}");
		}


		// и напоследок удалим запись, из-за которой откат был инициирован
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_forex_orders_main`
				WHERE `id` = {$main_id}
				LIMIT 1
				");
		$this->rollbackAI('log_forex_orders_main');

		$this->mydctx_rollback();
	}

	function new_forex_order_rollback_front()
	{
		$this->limit_requests_money_orders_rollback();
	}


	/*
	 * отложим на потом, т.к. некогда думать, что делать с пересчетом TDC по обещанным суммам и входящим запросам, которые не шлются тем, у кого каникулы
		// 27
		function holidays_del_init() {

			$this->tx_data['hash'] = $this->transaction_array[0];
			$this->tx_data['type'] = $this->transaction_array[1];
			$this->tx_data['time'] = $this->transaction_array[2];
			$this->tx_data['user_id'] = $this->transaction_array[3];
			$this->tx_data['holidays_id'] = $this->transaction_array[4];
			$this->tx_data['sign'] = $this->transaction_array[5];

			$this->variables =  self::get_variables($this->db, array('limit_holidays', 'limit_holidays_period'));

		}

		// 27
		function holidays_del_front() {

			$error = $this -> general_check();
			if ($error)
				return $error;

			if ( !check_input_data ($this->tx_data['holidays_id'], 'bigint') )
				return 'holidays_del_front holidays_id';

			// проверяем подпись
			$for_sign = "{$this->tx_data['type']},{$this->tx_data['time']},{$this->tx_data['user_id']},{$this->tx_data['holidays_id']}";
			$error = self::checkSign ($this->public_keys, $for_sign, $this->tx_data['sign']);
			if ($error)
				return $error;

			// проверим, есть ли такой id у юзера
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `id`, `start_time`, `end_time`
					FROM `".DB_PREFIX."holidays`
					WHERE `id` = {$this->tx_data['holidays_id']} AND
								 `user_id` =  {$this->tx_data['user_id']}
					LIMIT 1
					", 'fetch_array' );
			if (!$data['id'])
				return 'error holidays_id';

			// нельзя удалять каникулы, которые уже прошли
			//...


			// добавлять можно не более X запросов на добавление и удаление holidays за неделю
			$error = $this -> limit_requests( $this->variables['limit_holidays'], 'holidays', $this->variables['limit_holidays_period'] );
			if ($error)
				return $error;
		}

		// 27
		function holidays_del_rollback_front() {

			$this -> limit_requests_rollback('holidays');

		}

		// 27
		function holidays_del_rollback() {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."holidays`
					SET `delete` = 0
					WHERE `id` = {$this->tx_data['holidays_id']}
					LIMIT 1
					");

			// проверим, не наш ли это user_id
			$this->get_my_user_id();
			if ($this->tx_data['user_id'] == $this->my_user_id) {

				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."{$this->my_prefix}my_holidays`
						SET `status` = 'deleted'
						WHERE `holidays_id` = {$this->tx_data['holidays_id']}
						");
			}

		}

		// 27
		function holidays_del() {

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE`".DB_PREFIX."holidays`
					SET `delete` = 1
					WHERE `id` = {$this->tx_data['holidays_id']}
					LIMIT 1
					");
			// проверим, не наш ли это user_id
			$this->get_my_user_id();
			if ($this->tx_data['user_id'] == $this->my_user_id) {

				// обновим статус в нашей локальной табле.
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."{$this->my_prefix}my_holidays`
							SET `status` = 'deleted',
								   `holidays_id` = 0
							WHERE `holidays_id` = {$this->tx_data['holidays_id']}
							");
			}
		}
	*/

	static function delete_header($binary_data)
	{
		/*
		TYPE (0-блок, 1-тр-я)     1
		BLOCK_ID   				       4
		TIME       					       4
		USER_ID                         5
		LEVEL                              1
		SIGN                               от 128 до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
		Далее - тело блока (Тр-ии)
		*/
		self::string_shift ( $binary_data, 15);
		$sign_size = self::decode_length( $binary_data );
		self::string_shift ( $binary_data, $sign_size ) ;
		return $binary_data;
	}

	function ParseBlock()
	{
		global $global_current_block_id;
		/*
		Заголовок (от 143 до 527 байт )
		TYPE (0-блок, 1-тр-я)     1
		BLOCK_ID   				       4
		TIME       					       4
		USER_ID                         5
		LEVEL                              1
		SIGN                               от 128 до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
		Далее - тело блока (Тр-ии)
		*/

		if (!$this->binary_data)	return 'null $this->binary_data 1';

		$this->block_data['block_id'] = binary_dec( $this->string_shift ( $this->binary_data, 4) );
		if (!$this->binary_data)	return 'null $this->binary_data 2';

		$global_current_block_id = $this->block_data['block_id'];

		$this->block_data['time'] = binary_dec( $this->string_shift ( $this->binary_data, 4 ) );
		if (!$this->binary_data)	return 'null $this->binary_data 3';

		$this->block_data['user_id'] = binary_dec( $this->string_shift ( $this->binary_data, 5 ) );
		if (!$this->binary_data)	return 'null $this->binary_data 4';

		$this->block_data['level'] = binary_dec( $this->string_shift ( $this->binary_data, 1 ) );
		if (!$this->binary_data)	return 'null $this->binary_data 5';

		$sign_size = $this->decode_length($this->binary_data);
		if (!$this->binary_data)	return 'null $this->binary_data 6';
		$this->block_data['sign'] = $this->string_shift ( $this->binary_data, $sign_size ) ;
	}

	/**
	 * Откат таблиц log_time_, которые были изменены транзакциями
	 */
	public function ParseDataRollbackFront($tx_testblock=false) {

		//print "ParseDataRollbackFront 0\n";
		// вначале нужно получить размеры всех тр-ий, чтобы пройтись по ним в обратном порядке
		$bin_for_size = $this->binary_data;
		$sizes_arr = array();
		do {
			$transaction_size = $this->decode_length($bin_for_size);
			if (!$transaction_size)
				break;
			$sizes_arr[] = $transaction_size;
			// удалим тр-ию
			$this->string_shift ( $bin_for_size, $transaction_size ) ;
		} while ($bin_for_size);
		$sizes_arr = array_reverse($sizes_arr);
		debug_print('$sizes_arr:'.print_r_hex($sizes_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		for ($i=0; $i<sizeof($sizes_arr); $i++) {

			// обработка тр-ий может занять много времени, нужно отметиться
			upd_deamon_time ($this->db);
			// отчекрыжим одну транзакцию
			$transaction_binary_data = $this->string_shift_reverse($this->binary_data, $sizes_arr[$i]);
			debug_print('$transaction_binary_data:'.$transaction_binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// узнаем кол-во байт, которое занимает размер
			$size_ = strlen(encode_length($sizes_arr[$i]));
			debug_print('$size_:'.$size_, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// удалим размер
			$this->string_shift_reverse($this->binary_data, $size_);
			debug_print('$this->binary_data:'.$this->binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// 'max_tx_count', 'max_tx_size' потом убрать, т.к. все проверки уже пройдены
			$this->global_variables = self::get_variables($this->db, array('max_tx_count', 'max_tx_size'));

			// инфа о предыдущем блоке (т.е. последнем занесенном)
			$this->get_info_block();

			if ($tx_testblock) {
				/* Убрал  `used`=0, из SET*/
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."transactions`
						SET `verified` = 0
						WHERE `hash` = 0x".md5($transaction_binary_data)."
						");
			}
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."log_transactions`
					WHERE `hash` = 0x".md5($transaction_binary_data)."
					LIMIT 1
					");

			$this->tx_hash = md5($transaction_binary_data);
			$this->transaction_array = $this->parse_transaction ($transaction_binary_data);
			if (!is_array($this->transaction_array))
				return $this->transaction_array;

			debug_print($this->transaction_array, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// 1
			$this->type = $this->transaction_array[1];
				$user_id = $this->transaction_array[3];

				/*// от 1 юзера не может быть более X запросов за 1 минуту. Для борьбы с досами.
				$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `count`
						FROM `".DB_PREFIX."log_minute`
						WHERE  `user_id` = {$user_id}
						", 'fetch_one');
				if ($count==1)
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							DELETE FROM `".DB_PREFIX."log_minute`
							WHERE `user_id` = {$user_id}
							LIMIT 1
							");
				else
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."log_minute`
							SET `count` = count-1
							WHERE `user_id` = {$user_id}
							");
				*/
			//}

			$fns_name = self::$MainArray[ $this->type ];
			$fns_name_init = $fns_name.'_init';
			$fns_name_rollback_front = $fns_name.'_rollback_front';
			//print $fns_name_rollback_front."\n";
			debug_print('>>>>>>>>rollback_front='.$fns_name_rollback_front, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			unset($this->tx_data);
			$this->$fns_name_init();
			debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$this->$fns_name_rollback_front();

		}
	}

	/**
	 * Откат БД по блокам
	*/
	public function ParseDataRollback() {

		$this->DataPre();

		if ( $this->type != 0 ) // парсим только блоки
			return 'error block';

		$this->global_variables = self::get_variables($this->db, array( 'max_tx_count', 'max_tx_size'));

		$this->ParseBlock();

		//print '$this->binary_data ParseDataRollback ='.$this->binary_data."\n";

		if ($this->binary_data) {
			// вначале нужно получить размеры всех тр-ий, чтобы пройтись по ним в обратном порядке
			$bin_for_size = $this->binary_data;
			$sizes_arr = array();
			do {
				$transaction_size = $this->decode_length($bin_for_size);
				if (!$transaction_size)
					break;
				$sizes_arr[] = $transaction_size;
				// удалим тр-ию
				$this->string_shift ( $bin_for_size, $transaction_size ) ;
			} while ($bin_for_size);
			$sizes_arr = array_reverse($sizes_arr);
			debug_print('$sizes_arr:'.print_r_hex($sizes_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			for ($i=0; $i<sizeof($sizes_arr); $i++) {

				// обработка тр-ий может занять много времени, нужно отметиться
				upd_deamon_time ($this->db);
				// отчекрыжим одну транзакцию
				$transaction_binary_data = $this->string_shift_reverse($this->binary_data, $sizes_arr[$i]);
				// узнаем кол-во байт, которое занимает размер
				$size_ = strlen(encode_length($sizes_arr[$i]));
				// удалим размер
				$this->string_shift_reverse($this->binary_data, $size_);

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."transactions`
						SET  `used`=0,
								`verified` = 0
						WHERE `hash` = 0x".md5($transaction_binary_data)."
						");
				//debug_print($this->db->printsql()."\nAffectedRows=".$this->db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						DELETE FROM `".DB_PREFIX."log_transactions`
						WHERE `hash` = 0x".md5($transaction_binary_data)."
						LIMIT 1
						");
				// пишем тр-ию в очередь на проверку, авось пригодится
				$md5 = md5($transaction_binary_data);
				$data_hex = bin2hex($transaction_binary_data);
				$file = save_tmp_644 ('FTQ', "{$md5}\t{$data_hex}");
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						LOAD DATA LOCAL INFILE  '{$file}'
						IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
						FIELDS TERMINATED BY '\t'
						(@hash, @data)
						SET `hash` = UNHEX(@hash),
							   `data` = UNHEX(@data)
						");
				unlink($file);

				$this->tx_hash = md5($transaction_binary_data);
				$this->transaction_array = $this->parse_transaction ($transaction_binary_data);
				debug_print($this->transaction_array , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$fns_name = self::$MainArray[ $this->transaction_array[1] ];
				$fns_name_init = $fns_name.'_init';
				$fns_name_rollback = $fns_name.'_rollback';
				$fns_name_rollback_front = $fns_name.'_rollback_front';
				debug_print('>>>>>>>>rollback='.$fns_name_rollback.' + '.$fns_name_rollback_front, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				unset($this->tx_data);
				$this->$fns_name_init();
				debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$this->$fns_name_rollback_front();
				$this->$fns_name_rollback();

			}
		}
	}

	//  если в ходе проверки тр-ий возникает ошибка, то вызываем откатчик всех занесенных тр-ий
	function RollbackTo ($binary_data, $skip_current=false, $only_front=false)
	{

		debug_print("RollbackTo binary_data ".bin2hex($binary_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if ($only_front)
			debug_print('$only_front=true', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($binary_data) {

			// вначале нужно получить размеры всех тр-ий, чтобы пройтись по ним в обратном порядке
			$bin_for_size = $binary_data;
			$sizes_arr = array();
			do {
				$transaction_size = $this->decode_length($bin_for_size);
				if (!$transaction_size)
					break;
				$sizes_arr[] = $transaction_size;
				// удалим тр-ию
				$this->string_shift ( $bin_for_size, $transaction_size ) ;
			} while ($bin_for_size);
			$sizes_arr = array_reverse($sizes_arr);
			debug_print('$sizes_arr:'.print_r_hex($sizes_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			for ($i=0; $i<sizeof($sizes_arr); $i++) {

				debug_print("RollbackTo binary_data [{$i}]", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				// обработка тр-ий может занять много времени, нужно отметиться
				upd_deamon_time ($this->db);
				// отчекрыжим одну транзакцию
				$transaction_binary_data = $this->string_shift_reverse($binary_data, $sizes_arr[$i]);
				debug_print("transaction_binary_data = ".$transaction_binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$transaction_binary_data_ = $transaction_binary_data;
				// узнаем кол-во байт, которое занимает размер
				$size_ = strlen(encode_length($sizes_arr[$i]));
				// удалим размер
				$this->string_shift_reverse($binary_data, $size_);

				$this->tx_hash = md5($transaction_binary_data);
				$this->transaction_array = $this->parse_transaction ($transaction_binary_data);
				debug_print($this->transaction_array, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$fns_name = self::$MainArray[ $this->transaction_array[1]  ];
				$fns_name_init = $fns_name.'_init';
				$fns_name_Rollback = $fns_name.'_rollback';
				$fns_name_Rollback_Front = $fns_name.'_rollback_front';
				unset($this->tx_data);
				$this->$fns_name_init();
				debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				// если дошли до тр-ии, которая вызвала ошибку, то откатываем только фронтальную проверку
				if ($i == 0) {

					if ($skip_current) // тр-ия, которая вызвала ошибку закончилась еще до фронт. проверки, т.е. откатывать по ней вообще нечего
						continue;

					// если успели дойти только до половины фронтальной функции
					if ($this->half_rollback)
						$fns_name_Rollback_Front = $fns_name.'_rollback_front_0';
					// откатываем только фронтальную проверку
					$this->$fns_name_Rollback_Front();
				}
				else if($only_front) {
					$this->$fns_name_Rollback_Front();
				} else {
					$this->$fns_name_Rollback_Front();
					$this->$fns_name_Rollback();
				}

				$this->delete_log_tx ($transaction_binary_data_);
				// ===================>ради эксперимента
				if ($only_front) {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
									UPDATE `".DB_PREFIX."transactions`
									SET `verified` = 0
									WHERE `hash` = 0x{$this->tx_hash}
									");
				}
				// ====================================
				else {
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."transactions`
							SET `used` = 0
							WHERE `hash` = 0x{$this->tx_hash}
							");
				}
			}
		}
	}

	public function GetBlockInfo()
	{
		return array('hash'=>$this->block_data['hash'], 'head_hash'=>$this->block_data['head_hash'], 'time'=>$this->block_data['time'], 'level'=>$this->block_data['level'], 'block_id'=>$this->block_data['block_id']);
	}

	public function insert_into_blockchain()
	{
		debug_print("block_data=".print_r_hex($this->block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// для локальных тестов
		if ($this->block_data['block_id']==1) {
			$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
			if (isset($ini_array['local']['start_block_id'])) {
				$this->block_data['block_id'] = $ini_array['local']['start_block_id'];
			}
		}

		// пишем в цепочку блоков
		$data = "{$this->block_data['block_id']}\t{$this->block_data['hash']}\t{$this->block_data['head_hash']}\t{$this->block_hex}";
		$file = save_tmp_644 ('FBC', $data);
		debug_print("file=".$file, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print("data=".$data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// т.к. эти данные создали мы сами, то пишем их сразу в таблицу проверенных данных, которые будут отправлены другим нодам
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE `".DB_PREFIX."block_chain`
			FIELDS TERMINATED BY '\t'
			(`id`, @hash, @head_hash, @data)
			SET `hash` = UNHEX(@hash),
				   `head_hash` = UNHEX(@head_hash),
				   `data` = UNHEX(@data)
			");
		$AffectedRows = $this->db->getAffectedRows();
		if ($AffectedRows<1) {

			debug_print(">>>>>>>>>>> BUG LOAD DATA LOCAL INFILE  '{$file}' IGNORE INTO TABLE block_chain", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$row = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT *
							FROM `".DB_PREFIX."block_chain`
							WHERE `id` = {$this->block_data['block_id']}
							", 'fetch_array');

			print_r_hex($row);

			// ========================= временно для поиска бага: ====================================

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}' REPLACE INTO TABLE `".DB_PREFIX."block_chain`
			FIELDS TERMINATED BY '\t'
			(`id`, @hash, @head_hash, @data)
			SET `hash` = UNHEX(@hash),
				   `head_hash` = UNHEX(@head_hash),
				   `data` = UNHEX(@data)
			");

			//print 'getAffectedRows='.$this->db->getAffectedRows()."\n";
			// =================================================================================
		}
		unlink($file);
	}

	public function upd_block_info()
	{
		$block_id = $this->block_data['block_id'];
		// для локальных тестов
		if ($this->block_data['block_id']==1) {
			$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
			if (isset($ini_array['local']['start_block_id'])) {
				$block_id = $ini_array['local']['start_block_id'];
			}
		}
		$head_hash_data = "{$this->block_data['user_id']},{$block_id},{$this->prev_block['head_hash']}";
		$this->block_data['head_hash'] = ParseData::dsha256($head_hash_data);
		debug_print("head_hash={$this->block_data['head_hash']}\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$for_sha = "{$block_id},{$this->prev_block['hash']},{$this->mrkl_root},{$this->block_data['time']},{$this->block_data['user_id']},{$this->block_data['level']}";
		debug_print("for_sha={$for_sha}\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$this->block_data['hash'] = ParseData::dsha256($for_sha);
		debug_print("hash={$this->block_data['hash']}\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($this->block_data['block_id']==1) {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."info_block` (
					`hash`,
					`head_hash`,
					`block_id`,
					`time`,
					`level`,
					`current_version`
				) VALUES (
					0x{$this->block_data['hash']},
					0x{$this->block_data['head_hash']},
					{$block_id},
					{$this->block_data['time']},
					{$this->block_data['level']},
					'{$this->current_version}'
				)");
		} else {
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."info_block`
					SET  `hash` = 0x{$this->block_data['hash']},
							`head_hash` = 0x{$this->block_data['head_hash']},
							`block_id`= {$block_id},
							`time`= {$this->block_data['time']},
							`level`= {$this->block_data['level']},
							`sent` = 0
					");
			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."config`
					SET `my_block_id` = {$block_id}
					WHERE `my_block_id` < {$block_id}
					");


		}

		debug_print("{$this->db->printsql()}\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	/**
		фронт. проверка + занесение данных из блока в таблицы и info_block
	*/
	public function ParseDataFull()
	{
		global $global_current_block_id;

		$this->DataPre();

		if ( $this->type != 0 ) // парсим только блоки
			return 'error block';

		$this->global_variables = self::get_variables($this->db, array('error_time', 'max_tx_count', 'max_tx_size', 'max_block_user_transactions'));
		$error = $this->ParseBlock();
		if ($error) {
			debug_print("[error] ".$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return $error;
		}
		// проверим данные, указанные в заголовке блока
		debug_print("CheckBlockHeader", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$error = $this->CheckBlockHeader();
		if ($error) {
			debug_print("{error=$error}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return $error;
		}

		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."transactions`
				WHERE `used`=1
				");

		$count_transactions = array();

		// если в ходе проверки тр-ий возникает ошибка, то вызываем откатчик всех занесенных тр-ий. Эта переменная для него
		$this->full_tx_binary_data = $this->binary_data;

		$i = 0;
		$tx_for_RollbackTo = '';
		if ($this->binary_data) {
			do {

				// обработка тр-ий может занять много времени, нужно отметиться
				upd_deamon_time ($this->db);

				$this->half_rollback = 0;

				$transaction_size = $this->decode_length($this->binary_data);
				if (!$this->binary_data)
					return 'null $this->binary_data';

				// отчекрыжим одну транзакцию от списка транзакций
				$transaction_binary_data = $this->string_shift ( $this->binary_data, $transaction_size ) ;
				$transaction_binary_data_full = $transaction_binary_data;

				// добавляем взятую тр-ию в набор тр-ий для RollbackTo, в котором пойдем в обратном порядке
				$tx_for_RollbackTo.=  $this->encode_length_plus_data($transaction_binary_data) ;

				$error = $this->checkLogTx($transaction_binary_data_full);
				if ($error) {
					$this->RollbackTo ($tx_for_RollbackTo, true);
					return $error;
				}

				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."transactions`
						SET `used`=1
						WHERE `hash` = 0x".md5($transaction_binary_data_full)."
						");

				$this->tx_hash = md5($transaction_binary_data);
				$this->transaction_array = $this->parse_transaction ($transaction_binary_data);
				/*
				$this->tx_data['hash'] = $this->transaction_array[0];
				$this->tx_data['type'] = $this->transaction_array[1];
				$this->tx_data['time'] = $this->transaction_array[2];
				$this->tx_data['user_id'] = $this->transaction_array[3];
				*/
				if (!is_array($this->transaction_array)) {
					debug_print("error[{$this->transaction_array}]", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					$this->RollbackTo ($tx_for_RollbackTo, true);
					return implode('', $this->transaction_array);
				}

				// проверим
				if (  $this->block_data['block_id'] > 1) {

					// $this->transaction_array[3] могут подсунуть пустой
					$user_id = @$this->transaction_array[3];
					if (!check_input_data($user_id, 'bigint'))
						return 'bad user_id';

					// считаем по каждому юзеру, сколько в блоке от него транзакций
					@$count_transactions[$user_id]++;

					// чтобы 1 юзер не смог прислать дос-блок размером в 10гб, который заполнит своими же транзакциями
					if ( $count_transactions[$user_id] > $this->global_variables['max_block_user_transactions']  ) {
						$this->RollbackTo ($tx_for_RollbackTo, true);
						return 'max_block_user_transactions';
					}
				}

				// время в транзакции не может быть больше, чем на MAX_TX_FORW сек времени блока
				// и  время в транзакции не может быть меньше времени блока -24ч.
				if ( $this->transaction_array[2] - MAX_TX_FORW > $this->block_data['time'] || $this->transaction_array[2] < $this->block_data['time'] - MAX_TX_BACK ) {
					debug_print("tr_time={$this->transaction_array[2]}\nblock_data time={$this->block_data['time']}\nMAX_TX_FORW=".MAX_TX_FORW."\nMAX_TX_BACK=".MAX_TX_BACK, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					$this->RollbackTo ($tx_for_RollbackTo, true);
					return 'error transaction time';
				}

				// проверим, есть ли такой тип тр-ий
				$error = $this->checkTxType( $this->transaction_array[1] );
				if ($error)
					return $error;

				$fns_name = self::$MainArray[ $this->transaction_array[1]  ];
				$fns_name_init = $fns_name.'_init';
				$fns_name_front = $fns_name.'_front';
				debug_print('>>>>>>>>parsedatafull='.$fns_name_front.' + '.$fns_name, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				unset($this->tx_data);
				$error = $this->$fns_name_init();
				if ($error) {
					debug_print('[error] = '.$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					debug_print($this->transaction_array, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					return $error;
				}
				debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$error = $this->$fns_name_front();
				if ($error) {
					// саму текущую тр-ю нужно откатить по фронту, только если есть [limit_requests] Неактуально
					debug_print('$error (ParseDataFull) = '.$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					//if (substr_count($error, '[limit_requests]')>0)
					//	$this->RollbackTo ($tx_for_RollbackTo);
					//else
						$this->RollbackTo ($tx_for_RollbackTo, true);
						
					return $error;
				}
				$this->$fns_name();

				// Тут было time(). А значит если бы в цепочке блоков были блоки в которых были бы одинаковые хэши тр-ий, то ParseDataFull вернул бы error
				$this->insert_in_log_tx ($transaction_binary_data_full, $this->tx_data['time']);

				$i++;

			} while ($this->binary_data);
		}

		$this->upd_block_info();

	}

	function get_info_block()
	{
		// последний успешно записанный блок
		$this->block_info = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							SELECT LOWER(HEX(`hash`)) as `hash`,
										 LOWER(HEX(`head_hash`)) as `head_hash`,
										 `block_id`,
										 `level`,
										 `time`
							FROM `".DB_PREFIX."info_block`
							", 'fetch_array');
		if (!$this->block_info) {
			$this->block_info['head_hash'] = 0;
			$this->block_info['hash'] = 0;
			$this->block_info['level'] = 1;
			$this->block_info['block_id'] = 0;
		}
		$this->prev_block = $this->block_info;
	}

	static function getMrklroot($binary_data, $variables, $first=false) {

		if ($first)
			debug_print('$first=true', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$tx_size = 0;
		// [error] парсим после вызова функции
		if ($binary_data) {

			debug_print('$binary_data='.bin2hex($binary_data)."\nstrlen=".strlen($binary_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			do {

				// чтобы исключить атаку на переполнение памяти, выделенной для php
				if (!$first)
				if ($tx_size > $variables['max_tx_size'])
					return '[error] MAX_TX_SIZE';

				$tx_size = self::decode_length($binary_data) ;
				//print '$tx_size='.$tx_size."\n";
				// отчекрыжим одну транзакцию от списка транзакций
				if ($tx_size) {
					$transaction_binary_data = self::string_shift ( $binary_data, $tx_size);
					$mrkl_array[] = self::dsha256($transaction_binary_data);
				}
				////print 'strle($transaction_data)='.strlen($transaction_binary_data)."\n";
				////print '($transaction_data)='.$transaction_binary_data."\n";
				//print_r($mrkl_array);

				// чтобы исключить атаку на переполнение памяти, выделенной для php
				if (!$first)
				if (sizeof($mrkl_array) > $variables['max_tx_count'])
					return '[error] MAX_TX_COUNT';

			} while ($binary_data);
		}
		else
			$mrkl_array[] = 0;

		debug_print('$mrkl_array:'.print_r_hex($mrkl_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		return testblock::merkle_tree_root($mrkl_array);
	}

	function get_prev_block($block_id)
	{
		if (!$this->prev_block) {
			$data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `hash`, `head_hash`, `data`
						FROM `".DB_PREFIX."block_chain`
						WHERE `id` = {$block_id}
						", 'fetch_array' );
			$binary_data = $data['data'];
			string_shift ($binary_data, 1 ); // 0 - блок, >0 - тр-ии
			$this->block_info = parse_block_header($binary_data);
			$this->block_info['hash'] = bin2hex($data['hash']);
			$this->block_info['head_hash'] = bin2hex($data['head_hash']);
		}
		$this->prev_block = $this->block_info;

	}

	function CheckBlockHeader()
	{
		// инфа о предыдущем блоке (т.е. последнем занесенном)
		if (!$this->prev_block) // инфа может быть передана прямо в массиве
			$this->get_prev_block($this->block_data['block_id']-1);
		//$this->get_info_block(); убрано, т.к. CheckBlockHeader используется и при сборе новых блоков при вилке

		// для локальных тестов
		if ($this->prev_block['block_id']==1) {
			$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
			if (isset($ini_array['local']['start_block_id'])) {
				$this->prev_block['block_id'] = $ini_array['local']['start_block_id'];
			}
		}

		debug_print("this->prev_block: ".print_r_hex($this->prev_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print("this->block_data: ".print_r_hex($this->block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// меркель рут нужен для проверки подписи блока, а также проверки лимитов MAX_TX_SIZE и MAX_TX_COUNT
		if ($this->block_data['block_id']==1) {
			$this->global_variables['max_tx_size'] = 1024*1024;
			$first = true;
		}
		else
			$first = false;

		$this->mrkl_root = self::getMrklroot($this->binary_data, $this->global_variables, $first);

		// проверим время
		if ( !check_input_data ($this->block_data['time'], 'int') )
			return 'error time';

		// проверим уровень
		if ( !check_input_data ($this->block_data['level'], 'level') )
			return 'error level';

		// получим значения для сна
		$sleep_data = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `value`
					FROM `".DB_PREFIX."variables`
					WHERE `name` = 'sleep'
					", 'fetch_one' );
		$sleep_data = json_decode($sleep_data, true);
		debug_print("sleep_data:", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//print_R($sleep_data);

		// узнаем время, которые было затрачено в ожидании is_ready предыдущим блоком
		$is_ready_sleep = testblock::get_is_ready_sleep($this->prev_block['level'], $sleep_data['is_ready']);
		// сколько сек должен ждать нод, перед тем, как начать генерить блок, если нашел себя в одном из уровней.
		$generator_sleep = testblock::get_generator_sleep($this->block_data['level'] , $sleep_data['generator']);
		// сумма is_ready всех предыдущих уровней, которые не успели сгенерить блок
		$is_ready_sleep2 = testblock::get_is_ready_sleep_sum($this->block_data['level'] , $sleep_data['is_ready']);

		debug_print("is_ready_sleep={$is_ready_sleep}\ngenerator_sleep={$generator_sleep}\nis_ready_sleep2={$is_ready_sleep2}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		debug_print('prev_block:'.print_r_hex($this->prev_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('block_data:'.print_r_hex($this->block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// не слишком ли рано прислан этот блок. допустима погрешность = error_time
		if (!$first)
		if ( $this->prev_block['time'] + $is_ready_sleep + $generator_sleep + $is_ready_sleep2 - $this->block_data['time'] > $this->global_variables['error_time'] )
			return "error block time {$this->prev_block['time']} + {$is_ready_sleep} + {$generator_sleep} + {$is_ready_sleep2} - {$this->block_data['time']} > {$this->global_variables['error_time']}\n";
		// исключим тех, кто сгенерил блок с бегущими часами
		if ( $this->block_data['time'] > time() )
			return "error block time";

		// проверим ID блока
		if ( !check_input_data ($this->block_data['block_id'], 'int') )
			return 'block_id';

		// проверим, верный ли ID блока
		if (!$first)
			if ( $this->block_data['block_id'] != $this->prev_block['block_id']+1 )
				return "error block_id ({$this->block_data['block_id'] }!=".($this->prev_block['block_id']+1).")";

		// проверим, есть ли такой майнер и заодно получим public_key
		// ================================== my_table заменить ===============================================
		$this->node_public_key = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `node_public_key`
					FROM `".DB_PREFIX."miners_data`
					WHERE `user_id` = {$this->block_data['user_id']}
					LIMIT 1
					", 'fetch_one' );
		
		if (!$first)
			if  ( !$this->node_public_key )
				return 'user_id';

		// SIGN от 128 байта до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
		$for_sign = "0,{$this->block_data['block_id']},{$this->prev_block['hash']},{$this->block_data['time']},{$this->block_data['user_id']},{$this->block_data['level']},{$this->mrkl_root}";
		debug_print("checkSign", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// проверяем подпись
		if (!$first) {
			$error = self::checkSign ($this->node_public_key, $for_sign, $this->block_data['sign'], true);
			if ($error)
				return $error;
		}

	}

	// Это защита от dos, когда одну транзакцию можно было бы послать миллион раз,
	// и она каждый раз успешно проходила бы фронтальную проверку
	function checkLogTx($tx_binary)
	{
		$tx_md5 = md5($tx_binary);
		$hash = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `hash`
				FROM `".DB_PREFIX."log_transactions`
				WHERE `hash` = 0x{$tx_md5}
				LIMIT 1
				", 'fetch_one' );
		
		if ($hash) {
			debug_print('ERROR!! log_transactions $hash='.bin2hex($hash) , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return 'double log_transactions '.bin2hex($hash);
		}
	}

	function checkTxType($type)
	{
		if ( !isset( self::$MainArray[ $type ] ) )
			return 'nonexistent type';
	}

	/**
		Обработка данных (блоков или транзакций), пришедших с гейта. Только проверка.
	*/
	public function ParseData_gate($only_tx=false)
	{
		$count_transactions = 0;
		$this->DataPre();

		$this->global_variables = self::get_variables($this->db, array('max_tx_size','max_tx_count', 'error_time', 'max_block_user_transactions', 'max_user_transactions'));

		$transaction_binary_data = $this->binary_data;

		// если это транзакции (type>0), а не блок (type==0)
		if ( $this->type > 0 ) {

			// проверим, есть ли такой тип тр-ий
			$error = $this->checkTxType($this->type);
			if ($error)
				return $error;

			debug_print('TX' , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$transaction_binary_data = dec_binary ($this->type, 1) . $transaction_binary_data;
			$transaction_binary_data_full = $transaction_binary_data;

			// нет ли хэша этой тр-ии у нас в БД?
			$error = $this->checkLogTx($transaction_binary_data_full);
			if ($error)
				return $error;

			$this->tx_hash = md5($transaction_binary_data);
			// преобразуем бинарные данные транзакции в массив
			$this->transaction_array = $this->parse_transaction ( $transaction_binary_data );
			if (!is_array($this->transaction_array))
				return $this->transaction_array;

			debug_print($this->transaction_array , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// время транзакции может быть немного больше, чем время на ноде.
			// у нода может быть просто не настроено время.
			// время транзакции используется только для борьбы с атаками вчерашними транзакциями.
			// А т.к. мы храним хэши в log_transaction за 36 часов, то боятся нечего.

			$my_time = time();
			if ( $this->transaction_array[2] - MAX_TX_FORW > $my_time || $this->transaction_array[2] < $my_time - MAX_TX_BACK ) {
				debug_print("tr_time={$this->transaction_array[2]}\nmy_time={$my_time}\nMAX_TX_FORW=".MAX_TX_FORW."\nMAX_TX_BACK=".MAX_TX_BACK."", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				return "error tx time ({$this->transaction_array[2]})";
			}

			// $this->transaction_array[3] могут подсунуть пустой
			$user_id = @$this->transaction_array[3];
			if (!check_input_data($user_id, 'bigint'))
				return 'bad user_id';

			/*// от 1 юзера не может быть более X запросов за 1 минуту. Для борьбы с досами.
			$count = $this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `count`
					FROM `".DB_PREFIX."log_minute`
					WHERE `user_id` = {$user_id}
					", 'fetch_one');
			if (!$count)
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						INSERT INTO `".DB_PREFIX."log_minute` (
								`user_id`, `count`
							)
							VALUES (
								{$user_id}, 1)
							");
			else
				$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						UPDATE `".DB_PREFIX."log_minute`
						SET `count` = count+1
						WHERE `user_id` = {$user_id}
						");

			if ( $count > $this->global_variables['max_user_transactions'] )
				return 'max_user_transactions';
			*/
		}


		// если это блок
		if ( $this->type==0 ) {

			$count_transactions = array ();

			debug_print('BLOCK' , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$rate = 1;

			// если есть $only_tx=true, то значит идет восстановление уже проверенного блока и заголовок не требуется
			if (!$only_tx) {
				$error = $this->ParseBlock();
				if ($error) {
					debug_print("[error] ".$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					return $error;
				}

				// проверим данные, указанные в заголовке блока
				debug_print("CheckBlockHeader", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				$error = $this->CheckBlockHeader();
				if ($error) return $error;
			}
			else
				debug_print('<$only_tx>' , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// если в ходе проверки тр-ий возникает ошибка, то вызываем откатчик всех занесенных тр-ий. Эта переменная для него
			$this->full_tx_binary_data = $this->binary_data;
			$i = 0;
			$tx_for_RollbackTo = '';
			if ($this->binary_data) {
				do {

					// обработка тр-ий может занять много времени, нужно отметиться
					upd_deamon_time ($this->db);

					$transaction_size = $this->decode_length($this->binary_data);
					if (!$this->binary_data) {
						debug_print("transaction_size = {$transaction_size}\nthis->full_tx_binary_data = {$this->full_tx_binary_data}" , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						return 'null $this->binary_data TX';
					}

					// отчекрыжим одну транзакцию от списка транзакций
					$transaction_binary_data = $this->string_shift ( $this->binary_data, $transaction_size );
					$transaction_binary_data_full = $transaction_binary_data;

					// добавляем взятую тр-ию в набор тр-ий для RollbackTo, в котором пойдем в обратном порядке
					$tx_for_RollbackTo.=  $this->encode_length_plus_data($transaction_binary_data) ;

					// нет ли хэша этой тр-ии у нас в БД?
					$error = $this->checkLogTx($transaction_binary_data);
					if ($error) {
						debug_print('[error]'.$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$this->RollbackTo ($tx_for_RollbackTo, true, true);
						return $error;
					}

					$this->tx_hash = md5($transaction_binary_data);
					$this->transaction_array = $this->parse_transaction ($transaction_binary_data);

					if (!is_array($this->transaction_array))
						return $this->transaction_array;

					// $this->transaction_array[3] могут подсунуть пустой
					$user_id = @$this->transaction_array[3];
					if (!check_input_data($user_id, 'bigint'))
						return 'bad user_id';

					// считаем по каждому юзеру, сколько в блоке от него транзакций
					$count_transactions[$user_id]++;

					// чтобы 1 юзер не смог прислать дос-блок размером в 10гб, который заполнит своими же транзакциями
					if ( $count_transactions[$user_id] > $this->global_variables['max_block_user_transactions']  ) {
						debug_print($count_transactions, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						debug_print($user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						debug_print($this->global_variables, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$this->RollbackTo ($tx_for_RollbackTo, true, true);
						return 'max_block_user_transactions';
					}

					// проверим, есть ли такой тип тр-ий
					$error = $this->checkTxType( $this->transaction_array[1] );
					if ($error)
						return $error;

					$fns_name = self::$MainArray[ $this->transaction_array[1] ];
					//print '$fns_name='.$fns_name;
					$fns_name_init = $fns_name.'_init';
					$fns_name_front = $fns_name.'_front';
					debug_print('>>>>>>>>>>>>parsedatagate = '.$fns_name_front, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					unset($this->tx_data);
					$error = $this->$fns_name_init();
					if ($error) return $error;
					debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					$error = $this->$fns_name_front();
					if ($error){
						debug_print('[error]=>'.$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						//if (substr_count($error, '[limit_requests]')>0)
						//	$this->RollbackTo ($tx_for_RollbackTo, false, true);
						//else
							$this->RollbackTo ($tx_for_RollbackTo, true, true);
						return $error;

					}
					// пишем хэш тр-ии в лог
					$this->insert_in_log_tx ($transaction_binary_data_full, $this->tx_data['time']);

					// ===================>ради эксперимента
					$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
							UPDATE `".DB_PREFIX."transactions`
							SET `verified` = 1
							WHERE `hash` = 0x".md5($transaction_binary_data_full)."
							");
					// ====================================

					$i++;

				} while ($this->binary_data);
			}

		}
		else {
			debug_print('memory' , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// / $rate = 10;
			// Оперативные транзакции
			$fns_name = self::$MainArray[ $this->type ];
			$fns_name_init = $fns_name.'_init';
			$fns_name_front = $fns_name.'_front';
			unset($this->tx_data);
			$error = $this->$fns_name_init();
			if ($error) return $error;
			debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$error = $this->$fns_name_front();
			if ($error) return $error;
			$this->insert_in_log_tx ($transaction_binary_data_full, $this->tx_data['time']);

		}
		

	}

	function delete_log_tx ($binary_tx)
	{
		$tx_md5 = md5($binary_tx);
		// чтобы эту же транзакцию не могли повторно послать. Данные хранятся за последние 24 часа.
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."log_transactions`
				WHERE `hash`= 0x{$tx_md5}
				LIMIT 1
				");
	}

	function insert_in_log_tx ($binary_tx, $time)
	{
		$tx_md5 = md5($binary_tx);
		// чтобы эту же транзакцию не могли повторно послать. Данные хранятся за последние 24 часа.
		$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."log_transactions` (
					`hash`,
					`time`
				)
				VALUES (
					0x{$tx_md5},
					{$time}
				)");
	}

	// для тестов
	public function ParseData_tmp()
	{
		$this->DataPre();

		if ( $this->type == 0 ) {

			$this->global_variables = self::get_variables($this->db, array('max_tx_count', 'max_tx_size'));

			$this->ParseBlock();

			$i=0;
			if ($this->binary_data)
			do {

				$tx_size = $this->decode_length($this->binary_data);
				// отчекрыжим одну транзакцию от списка транзакций
				$tx_binary_data = $this->string_shift ( $this->binary_data, $tx_size ) ;
				$tx_binary_data_ = $tx_binary_data;
				$this->transaction_array = $this->parse_transaction ($tx_binary_data);
				$fns_name = self::$MainArray[ $this->transaction_array[1] ];
				$fns_name_init = $fns_name.'_init';
				$this->$fns_name_init();
				$this->tx_array[$i] = $this->tx_data;
				$this->tx_array[$i]['md5hash'] = md5($tx_binary_data_);
				$i++;

			} while ($this->binary_data);
		}
	}

	/**
	 * Занесение данных из блока в БД
	 * используется только в testblock_is_ready
	*/
	public function ParseData_front() {

		$this->DataPre();

		if ( $this->type == 0 ) {

			// 'max_tx_count', 'max_tx_size' потом убрать, т.к. все проверки уже пройдены
			$this->global_variables = self::get_variables($this->db, array('max_tx_count', 'max_tx_size'));

			// инфа о предыдущем блоке (т.е. последнем занесенном)
			$this->get_info_block();

			$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					DELETE FROM `".DB_PREFIX."transactions`
					WHERE `used`=1
					");

			##################################################
			## 				type=0 - Разбор блока
			##################################################

				$error = $this->ParseBlock();
				if ($error) {
					debug_print("[error] ".$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					return $error;
				}

				//меркель рут нужен для upd_block_info()
				$this->mrkl_root = self::getMrklroot($this->binary_data, $this->global_variables);

				if ($this->binary_data) {
					do {

						// обработка тр-ий может занять много времени, нужно отметиться
						upd_deamon_time ($this->db);

						$tx_size = $this->decode_length($this->binary_data);
						//print "tx_size = {$tx_size}\n";

						// отчекрыжим одну транзакцию от списка транзакций
						$tx_binary_data = $this->string_shift ( $this->binary_data, $tx_size ) ;
						$transaction_binary_data_full = $tx_binary_data;

						$this->tx_hash = md5($tx_binary_data);
						$this->transaction_array = $this->parse_transaction ($tx_binary_data);
						//print_R($this->transaction_array);
						$fns_name = self::$MainArray[ $this->transaction_array[1] ];
						$fns_name_init = $fns_name.'_init';
						debug_print('>>>>>>>>>>>>parsedata_front = '.$fns_name, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						unset($this->tx_data);
						$error = $this->$fns_name_init();
						if ($error) return $error;
						debug_print($this->tx_data , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
						$error = $this->$fns_name();
						if ($error) {
							debug_print('>>>>>>>>>>>>parsedata_front ERROR = '.$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
							return $error;
						}
						$this->db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
								UPDATE `".DB_PREFIX."transactions`
								SET `used`=1
								WHERE `hash` = 0x".md5($transaction_binary_data_full)."
								");
					} while ($this->binary_data);
				}

			$this->upd_block_info();
			debug_print('insert_into_blockchain', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$this->insert_into_blockchain();
		}
		else {
			debug_print('error type', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			return 'error type';
		}
	}
}

?>