<?php

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

define( 'limit_new_credit', 10 );
define( 'new_credit_period', 3600*24 );
define( 'limit_change_creditor', 10 );
define( 'change_creditor_period', 3600*24 );
define( 'limit_repayment_credit', 5 );
define( 'repayment_credit_period', 3600*24 );
define( 'limit_change_credit_part', 10 );
define( 'limit_change_credit_part_period', 3600*24 );
define( 'limit_change_key_active', 3 );
define( 'limit_change_key_active_period', 3600*24*7 );
define( 'limit_change_key_request', 1 );
define( 'limit_change_key_request_period', 3600*24*7 );

// через какое время админ имеет право изменить ключ юзера, если тот дал на это свое согласие. Это время дается юзеру на то, чтобы отменить запрос.
//define( 'CHANGE_KEY_PERIOD', 3600*24*30 );
define( 'CHANGE_KEY_PERIOD', 3600 );


?>