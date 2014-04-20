<?php

class DayItem {
	
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
	
	public function CountItems() { 
		$link = mysql_connect($this->host, $this->username, $this->password) or die("Не могу соединиться с MySQL.");
		mysql_select_db($this->db) or die("Не могу подключиться к базе.");
		mysql_query("set names utf8");
		$result = mysql_query("SELECT COUNT(uid) FROM tt_news WHERE hidden = 0 AND deleted = 0");
		while($row = mysql_fetch_array($result))
		{
			$data = $row['COUNT(uid)'];
		}
		$returnData['countOffers'] = $data;
		$result = mysql_query("SELECT COUNT(uid) FROM tt_address WHERE hidden = 0 AND deleted = 0");
		mysql_close($link);
		while($row = mysql_fetch_array($result))
		{
			$data = $row['COUNT(uid)'];
		}
		$returnData['countFirms'] = $data;
		return $returnData;
	}
}

?> 