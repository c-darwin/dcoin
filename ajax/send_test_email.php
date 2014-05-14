<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

require_once( ABSPATH . 'includes/class.phpmailer.php');
require_once( ABSPATH . 'includes/class.smtp.php');

define('MY_PREFIX', get_my_prefix($db));

// делаем выборку, т.к. данные для smtp могли быть записаны юзером ранее
$mail_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table`
		', 'fetch_array' );

$mail                = new PHPMailer();
//$mail->Mailer = 'sendmail';
if ($mail_data['use_smtp'] && $mail_data['smtp_server'])
{
	$mail->IsSMTP();
	$mail->SMTPAuth      = ($mail_data['smtp_auth']?true:false);
	$mail->SMTPSecure    = ($mail_data['smtp_ssl']?'ssl':'');
	$mail->Host          = $mail_data['smtp_server'];
	$mail->Port          = $mail_data['smtp_port'];
	$mail->Username      = $mail_data['smtp_username'];
	$mail->Password      = $mail_data['smtp_password'];
	$mail->SetFrom($mail_data['email'], 'Server');
}
//$mail->SetFrom('root@democratic-coin.com', 'democratic-coin.com');

$mail->Subject       = "test";
$mail->Body    = "test";
$mail->AddAddress($mail_data['email']);

if(!$mail->Send()) {
	echo json_encode(
				array('error'=>'Mailer Error (' . str_replace("@", "&#64;", $mail_data["email"]) . ') ' . $mail->ErrorInfo . '<br />')
			);
} else {
	echo json_encode(
		array('error'=>'null')
	);
}


?>