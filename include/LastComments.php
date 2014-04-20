<?php

class LastComments {
	
	private $db;
	private $host;
	private $password;
	private $username;
	
	public function SetMySQL($db, $host, $password, $username) {
		$this->db = $db;
		$this->host = $host;
		$this->password = $password;
		$this->username = $username;
	}

	public function Get10Comments($domain) {
		$link = mysql_connect($this->host, $this->username, $this->password) or die("Не могу соединиться с MySQL.");
		mysql_select_db($this->db) or die("Не могу подключиться к базе.");
		mysql_query("set names utf8");
		$result = mysql_query("SELECT * FROM tx_wecdiscussion_post WHERE useruid > 0 AND deleted = 0 AND hidden = 0 ORDER BY uid DESC LIMIT 10");
		mysql_close($link);
		$last10comments = "";
		while($row = mysql_fetch_array($result))	{
			$datetime = date("Y-m-d", $row['tstamp']);
			#$text = substr($row['message'],0,90);
			$text=trim(mb_substr($row['message'],0,mb_strrpos(mb_substr($row['message'],0,60,'utf-8'),' '),'utf-8'), '\,');
			$text = $text.'...';
			$finger = 5;
			/*'<div class="comments_last"><div id="userrate"><img src="fileadmin/commenta/'.$finger.'ball.png">
			</div><div class="comment_user">'.$row['name'].'</div><a href="http://skidki-yola.ru/index.php?id='.$row['pid'].'">
			<div class="comment_text">'.$text.'</div></a><div class="comment_time">'.$datetime.'</div></div>';*/
			$last10comments .= "<div class='oneComment'><h4>".$row['name']."</h4>";
			$last10comments .= "<a href=http://".$domain."/index.php?id=".$row['pid'].">".$text."</a>";
			$last10comments .= "<h5>".$datetime."</h5></div>";
		}
		return $last10comments;
	}


	
}
?> 