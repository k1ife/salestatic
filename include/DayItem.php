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

	public function GetDayItem($domain, $hKey) {
		$link = mysql_connect($this->host, $this->username, $this->password);
		mysql_select_db($this->db);
		mysql_query("set names utf8");
		$result = mysql_query("SELECT title,image,short,tx_ttnewsoldprice_oldprice,tx_ttnewsnewprice_newprice,ext_url FROM tt_news,tt_news_cat_mm WHERE tt_news.uid = tt_news_cat_mm.uid_local AND uid_foreign = 180 AND deleted = 0 AND hidden = 0 ORDER BY RAND() LIMIT 20");
		mysql_close($link);
		$all_goods = '';
		$i = 0;
		require_once('RedisCache.php');
		while($row = mysql_fetch_array($result)) {
			$all_goods = '<div id="DaiItem">';
			$all_goods .= '<div id="DaiItemHead"><a href="'.$row['ext_url'].'" style="text-align:center;">'.$row['title'].'</a></div>';
			$all_goods .= '<div id="DaiItemImage"><a href="'.$row['ext_url'].'"><img style="max-height:90px;width:auto;max-width:200px;margin:auto;display:block;" src="http://'.$domain.'/fileadmin/tovar_img/'.$row['image'].'" "></a></div>';
			$all_goods .= '<div id="DayItemPrices"><del>'.$row['tx_ttnewsoldprice_oldprice'].'</del></div><div style="font-weight:900;color:red;text-align:center;">'.$row['tx_ttnewsnewprice_newprice'].'</div></div>';
			$all_goods .= '<div id="DayItemShort">'.$row['short'].'</div>';
			#$all_goods .= '<div id="DayItemLabel">Товар дня</div>';
			$all_goods .= '<div id="DayItemBuyButton"><a href="'.$row['ext_url'].'"><img src="http://'.$domain.'/fileadmin/templates/main/img/zakaz.png"></a></div>';
			$all_goods .= '</div>';
			RedisCache::hMsetTimeout($hKey, array( $i=>$all_goods ), 900);
			$i++;
		}
		return $all_goods;
	}
	
}
	
?> 