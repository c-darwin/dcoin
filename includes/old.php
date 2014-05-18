<?php

if (!defined('DC'))
	die('!DC');

/*
 * То, что актально только до определенного блока.
 * calc_profit_24946 не успел создать критичные ошибки и потому был оставлен для уже сгенерированных блоков
 * */
class OldParseData {

	static function find_min_points_status_24946 ($need_time, &$array, $type) 
	{
		$find_time = array();
		$array_ = $array;
		$time_status_arr = array();
		foreach ($array as $time=>$status) {
			if ($time > $need_time)
				break;
			$find_time[] = $time;
			unset($array[$time]);
		}
		if ($find_time) {
			for ($i=0; $i<sizeof($find_time); $i++) {
				$time_status_arr[$i]['time'] = $find_time[$i];
				$time_status_arr[$i][$type] = $array_[$find_time[$i]];
			}
		}
		return $time_status_arr;
	}

	static function find_min_pct_24946 ($need_time, $pct_array, $status='')
	{
		$return = 0;
		$find_time = 0;
		$pct = 0;
		foreach ($pct_array as $time=>$arr) {
			if ($time > $need_time) {
				break;
			}
			$find_time = $time;
		}
		if ($find_time) {
			if ($status)
				$pct = $pct_array[$find_time][$status];
			else
				$pct = $pct_array[$find_time];
		}
		return $pct;
	}
	
	static function calc_profit_24946( $amount, $time_start, $time_finish, $pct_array, $points_status_array, $holidays_array=array(), $max_promised_amount_array=array(), $currency_id=0, $repaid_amount=0 )
	{
		sort($holidays_array);
		ksort($points_status_array);
		ksort($pct_array);
		ksort($max_promised_amount_array);
		$last_status = false;
		foreach ($pct_array as $time=>$status_pct_array) {
			$find_min_array = self::find_min_points_status_24946($time, $points_status_array, 'status');
			for ($i=0; $i<sizeof($find_min_array); $i++) {
				if ($find_min_array[$i]['time'] < $time) {
					$new_arr[$find_min_array[$i]['time']] = self::find_min_pct_24946($find_min_array[$i]['time'], $pct_array, $find_min_array[$i]['status']);
					$last_status = $find_min_array[$i]['status'];
				}
			}
			if (!$find_min_array && !$last_status)
				$find_min_array[0]['status']='user';
			else if (!$find_min_array && $last_status) // есть проценты, но кончились points_status
				$find_min_array[0]['status']='miner';
			$new_arr[$time] = $status_pct_array[$find_min_array[sizeof($find_min_array)-1]['status']];
			$status_pct_array_ = $status_pct_array;
		}
		if ($points_status_array) {
			foreach ($points_status_array as $time=>$status) {
				$new_arr[$time] = $status_pct_array_[$status];
			}
		}
		$pct_array = $new_arr;
		$new_arr = array();
		if (!$max_promised_amount_array)
			$last_amount = $amount;
		foreach ($pct_array as $time=>$pct) {
			$find_min_array = self::find_min_points_status_24946($time, $max_promised_amount_array, 'amount');
			for ($i=0; $i<sizeof($find_min_array); $i++) {
				if ($amount+$repaid_amount > $find_min_array[$i]['amount'])
					$amount_ = $find_min_array[$i]['amount'] - $repaid_amount;
				else if ($amount < $find_min_array[$i]['amount'] && $currency_id==1)
					$amount_ = $find_min_array[$i]['amount'];
				else
					$amount_ = $amount;
				if ($find_min_array[$i]['time'] <= $time) {
					$new_arr[$find_min_array[$i]['time']]['pct'] = self::find_min_pct_24946($find_min_array[$i]['time'], $pct_array);
					$new_arr[$find_min_array[$i]['time']]['amount'] = $amount_;
					$last_amount = $amount_;
				}
			}
			$new_arr[$time]['pct'] = $pct;
			$new_arr[$time]['amount'] = $last_amount;
		}
		$pct_array = $new_arr;
		$pct_array[$time_finish] = $pct;
		$amount_ = $amount;
		$new = array();
		$start_holidays = false;
		$old_time = 0;
		$old_pct_and_amount = array();
		foreach ($pct_array as $time=>$pct_and_amount) {
			if ($time > $time_start) {
				$work_time = $time;
				for ($j=0; $j<sizeof($holidays_array); $j++) {
					if (@$holidays_array[$j][1]<=$old_time) {
						continue;
					}
					// полные каникулы в промежутке между time и old_time
					if ( @$holidays_array[$j][0] && $work_time >= @$holidays_array[$j][0] && @$holidays_array[$j][1] && $work_time >= @$holidays_array[$j][1] ) {
						$time = $holidays_array[$j][0];
						unset($holidays_array[$j][0]);
						$to_new = array( 'num_sec'=>($time-$old_time), 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
						$new[] = $to_new;
						$old_time = $holidays_array[$j][1];
						unset($holidays_array[$j][1]);
					}
					if ( @$holidays_array[$j][0] && $work_time >= @$holidays_array[$j][0] ) {
						$start_holidays = true; // есть начало каникул, но есть ли конец?
						$finish_holidays_element = $holidays_array[$j][1]; // для записи в лог
						$time = $holidays_array[$j][0];
						if ($time < $time_start)
							$time = $time_start;
						unset($holidays_array[$j][0]);
					}
					else if ($work_time < $holidays_array[$j][1] && !@$holidays_array[$j][0]) {
						$time = $old_time;
						continue;
					}
					else if ( @$holidays_array[$j][1] && $work_time >= @$holidays_array[$j][1] ) {
						$old_time = $holidays_array[$j][1];
						unset($holidays_array[$j][1]);

						$start_holidays = false; // конец каникул есть
					}
					else if ($j==sizeof($holidays_array)-1 && !$start_holidays) {
							$time = $work_time;
					}
				}
				if ($time > $time_finish)
					$time = $time_finish;
				$to_new = array( 'num_sec'=>($time-$old_time), 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
				$new[] = $to_new;
				$old_time = $time;
			}
			else {
				$old_time = $time_start;
			}
			$old_pct_and_amount = $pct_and_amount;
		}
		if ($old_time < $time_finish && !$start_holidays) {
			$sec = $time_finish - $old_time;
			$new[] = array( 'num_sec'=>$sec, 'pct'=>$old_pct_and_amount['pct'], 'amount'=>$old_pct_and_amount['amount'] );
		}
		$amount_and_profit = 0;
		$profit = 0;
		for ($i=0; $i<sizeof($new); $i++) {
			$pct = 1+$new[$i]['pct'];
			$num = $new[$i]['num_sec'];
			$amount_and_profit = $profit +$new[$i]['amount'];
			$profit =  $amount_and_profit*pow($pct, $num) - $new[$i]['amount'];
		}
		return $profit;
	}
}

?>