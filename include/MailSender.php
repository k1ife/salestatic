<?php

require_once('RedisCache.php');
require_once('mail/class.phpmailer.php');

class MailSender {

	private $db;
	private $host;
	private $password;
	private $username;
	private $smtp_username;
	private $smtp_password;
	
	public function SetMySQL($db, $host, $password, $username, $smtp_username, $smtp_password) {
		$this->db = $db;
		$this->host = $host;
		$this->password = $password;
		$this->username = $username;
		$this->smtp_username = $smtp_username;
		$this->smtp_password = $smtp_password;
	}

	public function SendResetPassAndChange($city, $username, $userEmail, $domain, $sitename) {
		$link = mysql_connect($this->host, $this->username, $this->password);
		mysql_select_db($this->db);
		mysql_query("set names utf8");
		
		$str = 'qwertyuiopasdfghjklzxcvbnm1234567890';
		$newPass = '';
		while(strlen($newPass) < 10) $newPass .= substr($str, rand(0, 35), 1);
		
		mysql_query("UPDATE fe_users SET password='$newPass' WHERE email = '$userEmail'");
		mysql_close($link);
		
		$mail = new PHPMailer();
		$mail -> Host = 'smtp.yandex.ru';
		$mail -> Port = 587;
		$mail -> From = $this->smtp_username;
		$mail -> FromName = "Портал скидок";
		$mail -> Username = $this->smtp_username;
		$mail -> Password = $this->smtp_password;
		$body = file_get_contents('./etemplates/forgot.html');
		
		/*$arr = array('NEWPASS' => $newPass);
		foreach ($arr as $parameter=>$value) {
			$body = str_replace('{'.$parameter.'}',$value,$body);
		};*/
		$body = str_replace('{newpass}',$newPass,$body);
		$body = str_replace('{site_name}',$sitename,$body);
		$body = str_replace('{site_link}',$domain,$body);
		
		$mail -> IsSMTP();
		$mail -> SMTPAuth   = true;
		$mail -> AddAddress($userEmail);
		$mail -> Subject  = "Восстановление пароля";
		$mail -> MsgHTML($body);
	
		if(!$mail->Send()) {
			$cache = new RedisCache();
			$hKey = $city.":EMailLog";
			$cache -> hSet($hKey, date('Y-m-d H-i-s', strtotime('now')), 'Ошибка - '.$mail->ErrorInfo);
		} else {
		}
	}
	
	public function SendAlertToSomebody ($toName, $sendEmailSubject, $emailHTMLAndText, $listEmailFrom) {
		
	}
	
}

?>