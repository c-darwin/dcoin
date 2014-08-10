<?php

if (!defined('DC'))
	die('!DC');

date_default_timezone_set('America/New_York');

/**
 * Обработчик ошибок
 * @param int $errno уровень ошибки
 * @param string $errstr сообщение об ошибке
 * @param string $errfile имя файла, в котором произошла ошибка
 * @param int $errline номер строки, в которой произошла ошибка
 * @return boolean
 */
function error_handler($errno, $errstr, $errfile, $errline)
{
	global $my_error;
    // если ошибка попадает в отчет (при использовании оператора "@" error_reporting() вернет 0)
    if (error_reporting() & $errno)
    {
        @$errors = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        );
		
		// шлем на мыло
		//mail();

	    /*$out1 = ob_get_contents();
	    $out1.="{$errors[$errno]} [$errno] $errstr ($errfile on line $errline)\n";
	    file_put_contents(ABSPATH . 'log/' . get_script_name().'.log', $out1,  FILE_APPEND);
	    file_put_contents(ABSPATH . 'log/error_'.get_script_name().'.log', $out1,  FILE_APPEND);
	    @ob_end_clean();*/
	    $err_text =  "{$errors[$errno]} [$errno] $errstr in $errfile (".get_script_name().") on line $errline ".date('H:i:s')."\n";
	    ob_save($err_text, 1);
	    $ini_array = parse_ini_file(ABSPATH . "config.ini", true);
	    if ($ini_array['main']['error_log']==1)
		    file_put_contents( ABSPATH . 'log/error.log', $err_text,  FILE_APPEND );

        // выводим свое сообщение об ошибке
        //die("<b>{$errors[$errno]}</b>[$errno] $errstr ($errfile on line $errline)\n".date('H:i:s')."<br /><br />\n");
        
    }

    // не запускаем внутренний обработчик ошибок PHP
    return TRUE;
}

/**
 * Функция перехвата фатальных ошибок
 */
function fatal_error_handler()
{
    // если была ошибка и она фатальна
    if ($error = error_get_last() AND $error['type'] & ( E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR))
    {
        // очищаем буффер (не выводим стандартное сообщение об ошибке)
        ob_end_clean();
        // запускаем обработчик ошибок
        error_handler($error['type'], $error['message'], $error['file'], $error['line']);
    }
    else
    {
        // отправка (вывод) буфера и его отключение
       ob_end_flush();
    }
}

// определеяем уровень протоколирования ошибок
//error_reporting( E_ALL ^ E_USER_NOTICE ^ E_NOTICE ^ E_USER_ERROR ^ E_USER_WARNING  );
error_reporting( E_ALL );
// определяем режим вывода ошибок
ini_set('display_errors', 'On');
// включаем буфферизацию вывода (вывод скрипта сохраняется во внутреннем буфере)
ob_start();
// устанавливаем пользовательский обработчик ошибок
set_error_handler("error_handler");
// регистрируем функцию, которая выполняется после завершения работы скрипта (например, после фатальной ошибки)
register_shutdown_function('fatal_error_handler');

?>
