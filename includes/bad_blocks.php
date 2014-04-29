<?php

if (!defined('DC'))
	die('!DC');

/*
 * Используется в install_step_2_1
 * */
/*
$bad_blocks = json_encode(array(601=>'1003ee2ee3ab7cb0ec84d87962cc0481abf0adf623efd669d3cc617ab9e3aa4010fd1dc77ca405c5ae3e8346132eadac5122ae7adc25a6347f39210950f58d542b3ffb905c7de0c4d4cb124aaf50f7e375dbed7b97839816b090309c22ba6f428e803285bac022ac3898de40a820c983e2a02c80f6766c4ced5c74b95b39b3af'));
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".$tpl['mysql_prefix']."my_table`
			SET `bad_blocks` = '{$bad_blocks}'
			");
*/
?>
