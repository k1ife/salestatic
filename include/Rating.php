<?php

require_once('RedisCache.php');

class  Rating {

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
	
	public function GetUserRating($city, $startData) {
		$cache = new RedisCache();
		$link = mysql_connect($this->host, $this->username, $this->password);
		mysql_select_db($this->db);
		mysql_query("set names utf8");
		
		$hKey = $city.":thisweek";
		
		$startData = strtotime('now') - $startData;
		$log = $city.":UserRatingLog";
		
		$result = mysql_query("SELECT * FROM tx_wecdiscussion_post,fe_users WHERE tx_wecdiscussion_post.useruid = fe_users.uid AND  tx_wecdiscussion_post.post_datetime>'$startData' AND tx_wecdiscussion_post.deleted = 0 AND tx_wecdiscussion_post.hidden = 0");
		while($row = mysql_fetch_array($result)) {
			$username = $row['username'];
			$cache -> hIncrBy( $hKey, $username, 2);
			$key = $username." - за комментарий +2  |".rand(1,999999999);
			$cache -> hSet($log , $key, date("Y-m-d H-i-s", strtotime("now")));
		}
		
		# тут считаем новости
		$result = mysql_query("SELECT author FROM tt_news WHERE  deleted = 0 AND tx_ttnewsfield_linktitle = '' AND crdate > '$startData' AND author != '' ");
		while($row = mysql_fetch_array($result)) {
			$username = $row['author'];
			$cache -> hIncrBy( $hKey, $username, 4);
			$key = $username." - за новость +4  |".rand(1,999999999);
			$cache -> hSet($log,  $key, date("Y-m-d H-i-s", strtotime("now")));
		}

		# тут считаем акции и прочую шалупонь
		$result = mysql_query("SELECT author FROM tt_news WHERE deleted = 0 AND tx_ttnewsfield_linktitle != '' AND crdate > '$startData'  AND author != '' ");
		while($row = mysql_fetch_array($result)) {
			$username = $row['author'];
			$cache -> hIncrBy( $hKey, $row['author'], 5);
			$key = $username." - за акцию + 5  |".rand(1,999999999);
			$cache -> hSet($log, $key, date("Y-m-d H-i-s", strtotime("now")));
		}
		$existKey = $city.":LastUpdate";
		$cache -> hSet($existKey, 'LastTime', strtotime("now"));
		
		/* 
			а теперь обновим пользователей готовых получать спам рассылку:
			- смотрим что не обновляли дольше 3х дней
			- достаем все имена пользователей + адреса почты и складываем в мешочек
			- ставим дату обновления
		*/
		
		$nowTime = strtotime("now");
		$hKey = $city.":LastSpamUpdate";
		$lastSpamUpdate = $cache->hGetAll($hKey);
		$spamTiming = $nowTime - $lastSpamUpdate['LastUpdate'];
		if ( $lastSpamUpdate['LastUpdate'] == '' ) {
			mysql_close($link);
			$cache -> hSet($hKey, 'LastUpdate', strtotime("now"));
		} else if ( $spamTiming  < 345600 ) {
			
		} else {
			$result = mysql_query("SELECT * FROM fe_users WHERE email != ''");
			mysql_close($link);
			$hKey = $city.":UsersAllowSpam";
			while($row = mysql_fetch_array($result)) {
				$cache -> hSet( $hKey, $row['username'], $row['email']);
			}
			$hKey = $city.":LastSpamUpdate";
			$cache -> hSet($hKey, 'LastUpdate', strtotime("now"));
		}
		
	}

}

//echo $_GET['callback']."(".json_encode($data).");";

?> 
