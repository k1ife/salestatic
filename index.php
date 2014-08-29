<?php
//error_reporting(0);
$url = $_SERVER['REQUEST_URI'];
$url = explode("/", $url);
unset($url[0]);

switch ($url[1]) {
case "yola":			  require_once('../-yola/typo3conf/localconf.php');     break;
case "kirov":		  	require_once('../-kirov/typo3conf/localconf.php');    break;
case "astrachan":		require_once('../-astrachan/typo3conf/localconf.php');break;
case "kemerovo":		require_once('../-kemerovo/typo3conf/localconf.php'); break;
case "chita":   		require_once('../-chita/typo3conf/localconf.php');    break;
case "irkutsk":   	require_once('../-irkutsk/typo3conf/localconf.php');  break;
case "saratov":   	require_once('../-saratov/typo3conf/localconf.php');  break;
case "sakhalin":   	require_once('../-sakhalin/typo3conf/localconf.php'); break;
case "yakutsk":   	require_once('../-yakutsk/typo3conf/localconf.php');  break;
default:					header('Location: /404.html');exit();break;
}
date_default_timezone_set($TYPO3_CONF_VARS['SYS']['phpTimeZone']);


switch ($url[2]) {
case "DayItem": /* вроде работает нормально */

	require_once("include/DayItem.php");
	require_once('include/RedisCache.php');
	
	$hKey = $url[1].':'.$url[2];
	$timeout = 900;
	$returned = RedisCache::hExists($hKey, "0");
	if ($returned) {
		$AllNum = RedisCache::hLen($hKey);
		$AllNum = $AllNum -1;
		$Rnum = rand(0,$AllNum);
		$result = RedisCache::hmGet($hKey, array($Rnum));
		$data['dayitem'] = $result[$Rnum];
		//$_GET['callback'];
		echo $_GET['callback']."(".json_fix_cyr(json_encode($data)).");";
	} else {
		$data = new DayItem();
		$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username);
		$result = $data -> GetDayItem($domain, $hKey);
		$AllNum = RedisCache::hLen($hKey);
		$AllNum = $AllNum -1;
		$Rnum = rand(0,$AllNum);
		$result = RedisCache::hmGet($hKey, array($Rnum));
		$data = array( "dayitem" => $result[$Rnum] );
		echo $_GET['callback']."(".json_fix_cyr(json_encode($data)).");";
	}
	
	break;
	
case "LastComments": /* вроде работает нормально */

	require_once("include/LastComments.php");
	require_once('include/RedisCache.php');
	
	$hKey = $url[1].':'.$url[2];
	$timeout = 900;
	$returned = RedisCache::hExists($hKey, "comments");
	if ($returned) {
		$result = RedisCache::hGetAll($hKey);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	} else {
		$data = new LastComments();
		$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username);
		$result = $data -> Get10Comments($domain);
		RedisCache::hSetTimeout($hKey, 'comments', $result, 900);
		$result = RedisCache::hGetAll($hKey);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	}
	
	break;
	
case "Rating":

	require_once("include/Rating.php");
	require_once('include/RedisCache.php');
	
	if ( $url[3] == "-fullrating" ) { /*=========================полный рейтинг get all user rating*/ 
	
		$gaur = new RedisCache();
		$hKey = $url[1].":thisweek";
		$data = $gaur -> hGetAll($hKey);
		arsort($data);
		unset($data['undefined']);
		unset($data['']);
		unset($data['null']);
		$data = array_slice($data, 0, 5, true);
		if ($data == null) {$data = array(''=>"Данных ещё нет");}
		$resultThisWeek = $data;

		$hKey = $url[1].":lastweek";
		$data = $gaur -> hGetAll($hKey);
		arsort($data);
		unset($data['undefined']);
		unset($data['']);
		unset($data['null']);
		$data = array_slice($data, 0, 5, true);
		if ($data == null) {$data = array(''=>"Данных ещё нет");}
		$resultLastWeek = $data;

		$hKey = $url[1].":thisyear";
		$data = $gaur -> hGetAll($hKey);
		arsort($data);
		unset($data['undefined']);
		unset($data['']);
		unset($data['null']);
		$data = array_slice($data, 0, 5, true);
		if ($data == null) {$data = array(''=>"Данных ещё нет");}
		$resultThisYear = $data;
		$result = array('thisweek' => $resultThisWeek, 'lastweek' => $resultLastWeek, 'thisyear' => $resultThisYear);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	
	}	else if ( $url[3] == "-thisweekrating" ) { /*=========================рейтинг этой недели*/
		
		$gaur = new RedisCache();		
		$hKey = $url[1].":thisweek";
		$data = $gaur -> hGetAll($hKey);		
		arsort($data);
		unset($data['undefined']);
		unset($data['']);
		unset($data['null']);
		$data = array_slice($data, 0, 5, true);
		if ($data == null) {$data = array('' => "Данных ещё нет");}
		$result = array('thisweekrating' => $data);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	
	}	else { /*=========================рейтинг пользователя но могут быть недоработки*/
		
		
		
		$username = CleanWord(urldecode($url[3]));
		$gur = new RedisCache();
		
		if ( !preg_match("!-thisweekrating!si", $username) and !preg_match("!-fullrating!si", $username) ) {
		
			$existKey = $url[1].":LastUpdate";
			$existResult = $gur -> hExists($existKey, 'LastTime');
			if ( $existResult == false ) {
				$gur -> hSet($existKey, 'LastTime', strtotime("now"));
			} else {
				$lastUpdate = $gur -> hGetAll($existKey);
				$now = strtotime('now');
				$timing = $now - $lastUpdate['LastTime'];
				if ( $timing > 300 ) {
					$data = new Rating();
					$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username);
					$data -> GetUserRating($url[1], $timing);/*==========раз в 5 минут достаем всё новое и считаем!!==========*/
				}
			}
			
			/*
			
			проверка существования логина пользователя
			
			*/
			
			if ( !preg_match("!-thisweekrating!si", $username) and !preg_match("!-fullrating!si", $username) and !preg_match("!null!si", $username ) and !preg_match("!undefined!si", $username) ) {
				$UserUpKey = $url[1].":UpdatedIn:".urldecode($url[3]);
				$UserUpData = $gur -> hExists($UserUpKey, 'UpdatedIn');
				if ( $UserUpData == '' ) {
					$today = strtotime("00:00:00");
					$now = strtotime("now");
					$expireAt = 86400 - ($now - $today);
					$gur -> hSetTimeout($UserUpKey, 'UpdatedIn', $now, $expireAt);
					$userKey = $url[1].":thisweek";
					$gur -> hIncrBy($userKey, $username, 1);
				} 
			}

			
			$hKey1 = $url[1].":thisweek";
			$hKey2 = $url[1].":lastweek";
			$hKey3 = $url[1].":thisyear";
			$result1 = $gur -> hmGet($hKey1, array($username));
			$a = $result1[$username];
				if ($a == false) {$a = "Данных ещё нет";}
			$result2 = $gur ->hmGet($hKey2, array($username));
			$b = $result2[$username];
				if ($b == false) {$b = "Данных ещё нет";}
			$result3 = $gur ->hmGet($hKey3, array($username));
			$c = $result3[$username];
				if ($c == false) {$c = "Данных ещё нет";}
			$data = array("thisweek" => $a,  "lastweek" => $b, "thisyear" => $c );
			echo $_GET['callback']."(".json_fix_cyr(json_encode($data)).");";
		}
	}
	
	break;

case "CompStat": /* вроде работает нормально */

	require_once("include/CompStat.php");
	require_once('include/RedisCache.php');
	
	$hKey = $url[1].':'.$url[2];
	$timeout = 3600;
	$returned = RedisCache::hExists($hKey, 'countOffers');
	
	if ($returned) {
		$result = RedisCache::hGetAll($hKey);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	} else {
		$data = new DayItem();
		$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username);
		$result = $data -> CountItems();
		RedisCache::hMsetTimeout($hKey, array( 'countFirms'=>$result['countFirms'], 'countOffers'=>$result['countOffers'] ), 900);
		$_GET['callback'];
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	}	
	
	break;

case "FlushUseasdasdasdfsdwq23565e56324rawyer78t7ujdefaw4564rfzdgnftey4affrs": /* вроде работает нормально */

	require_once('include/RedisCache.php');
	
	$thisweek = $url[1].":thisweek";
	$lastweek = $url[1].":lastweek";
	$thisyear = $url[1].":thisyear";
	
	$fus = new RedisCache();
	$data = $fus -> hGetAll($lastweek);
	
	/*$logthisweek = $fus -> hGetAll($thisweek);
	$loglastweek = $fus -> hGetAll($lastweek);
	$logthisyear = $fus -> hGetAll($thisyear);
	
	$thisweekHkey = $url[1].":LOG:thisweek-".$date("Y-m-d H-i-s", strtotime("now"));
	$lastweekHkey = $url[1].":LOG:lastweek-".$date("Y-m-d H-i-s", strtotime("now"));
	$thisyearHkey = $url[1].":LOG:thisyear-".$date("Y-m-d H-i-s", strtotime("now"));
	
	foreach ( $logthisweek as $key => $value ) {
		$fus -> hSet($thisweekHkey, $key, $value);
	}
	foreach ( $loglastweek as $key => $value ) {
		$fus -> hSet($lastweekHkey, $key, $value);
	}
	foreach ( $logthisyear as $key => $value ) {
		$fus -> hSet($thisyearHkey, $key, $value);
	}*/
	
	foreach ( $data as $key => $value ) {
		$fus -> hIncrBy($thisyear, $key, $value);
	}
	//$toname = $url[1].":lasweek-log";
	$fus -> rename($lastweek, $toname);
	$fus -> rename($thisweek, $lastweek);
	
	
	$existKey = $url[1].":LastUpdate";
	$fus -> hSet($existKey, 'LastTime', strtotime("now"));
	
	break;
	
case "Subscribe": /* вроде работает нормально */

	require_once('include/RedisCache.php');
	
	$cache = new RedisCache();
	
	$hKey = $url[1].":UsersNotAllowSpam";
	$exStatus = $cache -> hGetAll($hKey);
	
	if ( $exStatus[$url[3]] == "Не получать" ) {
		$cache -> hSet($hKey, $url[3], 'Я не против рассылки');
		$result = array('result' => 'Вы подписались на рассылку');
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	} else {
		$cache -> hSet($hKey, $url[3], 'Не получать');
		$result = array('result' => 'Вы отписались от рассылки');
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	}
		
	break;
	
case "SpamStatus": /* вроде работает нормально */

	require_once('include/RedisCache.php');
	
	$cache = new RedisCache();
	$hKey = 	$url[1].":UsersNotAllowSpam";
	$data = $cache -> hGetAll($hKey);
	if ( isset($url[3]) && isset($data[$url[3]]) ) {
		if ( $data[$url[3]] != 'Не получать' || $data[$url[3]] == null || $data[$url[3]] == 'null' ) { /* непонятная ошибка. в логе имя пользователя */
			$result = array('result' => 'Вы подписаны на рассылку');
		} else {
			$result = array('result' => 'Вы НЕ подписаны на рассылку');
		}
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	}
		
	break;
	
case "ThursdayUsersEmail345]2[4j87": /* вроде работает нормально но не тестировалось */

	require_once('include/RedisCache.php');
	
	$cache = new RedisCache();
	$usersAllowSpam = $url[1].":UsersAllowSpam";
	$usersNotAllowSpam = $url[1].":UsersNotAllowSpam";
	$allowed = $cache->hGetAll($usersAllowSpam);
	$notAllowed = $cache->hGetAll($usersNotAllowSpam);
	foreach ($notAllowed as $key => $value) {
		if ( $value != 'Не получать' ) {  unset($notAllowed[$key]); }
	}
	array_diff_key($allowed, $notAllowed);// теперь в массиве $allowed только те, кто будет получать за минусом отписавшихся
		
	break;
	
case "ResetPass": /* вроде работает нормально но не по назначению */
	
	require_once('include/RedisCache.php');
	require_once('include/MailSender.php');
	
	$userEmail = $url[3];
	if ( preg_match("~^([a-z0-9_\-\.])+@([a-z0-9_\-\.])+\.([a-z0-9])+$~i", $userEmail)  ) {
	
		$cache = new RedisCache();
		$hKey = $url[1].":UsersAllowSpam";
		$users = $cache->hGetAll($hKey);
		
		if ( in_array($userEmail,$users) ) {
			$username = array_search($userEmail,$users);
			$data = new MailSender();
			$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username, $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'], $TYPO3_CONF_VARS['MAIL']['transport_smtp_password']);
			$data -> SendResetPassAndChange($url[1], $username, $userEmail, $domain, $TYPO3_CONF_VARS['SYS']['sitename']);
			$result = array('result' => 'Новый пароль выслан на указанную почту');
			echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
			
		} else {
			
			mysql_connect($typo_db_host, $typo_db_username, $typo_db_password);
			mysql_select_db($typo_db);
			mysql_query("set names utf8");
			$result = mysql_query("SELECT * FROM fe_users WHERE email != ''");
			$hKey = $url[1].":UsersAllowSpam";
			while($row = mysql_fetch_array($result)) {
				$cache -> hSet( $hKey, $row['username'], $row['email']);
			}
			
			if ( in_array($userEmail,$users)  ) {
			$username = array_search($userEmail,$users);
			$data = new MailSender();
			$data -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username, $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'], $TYPO3_CONF_VARS['MAIL']['transport_smtp_password']);
			$data -> SendResetPassAndChange($url[1], $username, $userEmail);
			$result = array('result' => 'Новый пароль выслан на указанную почту');
			echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
			} else {
				$result = array('result' => 'Тапого email у нас нет');
				echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
			}
		}
				
	}
		
	break;
	
case "addNews":
	
	//require_once('include/RedisCache.php');
	echo "asd";
		
	break;
	
case "addFirm":
	
	//require_once('include/RedisCache.php');
	echo "asd";
		
	break;
	
case "CountFirms":

	require_once('include/RedisCache.php');
	require_once('include/CountFirms.php');
	
	$cache = new RedisCache();
	$hKey = $url[1].":CountedFirms";
	if ( !$cache -> hExists($hKey, 'Авто') ) {	
		$countAll = new DayItem();
		$countAll -> SetMySQL($typo_db, $typo_db_host, $typo_db_password, $typo_db_username);
		$result = $countAll -> CountAllFirms($url[1]);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	} else {
		$result = $cache->hGetAll($hKey);
		echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	}
	
	break;

case "StartServer236dhhv7ehhc7u7dhhsivhwjdygvZKydgfaksjdfkgkyb":

	require_once('include/Gearman.php');
	$creator = new GearMan();
	$creator -> GearmanCronStartServer();
	break;

case "StartCronSpamSendYGH73HF7HWND77WFJFU8":

	require_once('include/Gearman.php');
	$contento = file_get_contents('etemplates/cronspam.html');
	$contento = str_replace('{site_name}',$TYPO3_CONF_VARS['SYS']['sitename'],$contento);
	$contento = str_replace('{site_link}',$domain,$contento);
	$date_start = strtotime('this Thursday');
	$month_start = date('m', $date_start);
	$date_end = $date_start + 608400;
	$month_end = date('m', $date_end);
	$superTheme = 'Выгодные предложения за последнюю неделю';
	$month = array('01'=>'Января','02'=>'Февраля','03'=>'Марта','04'=>'Апреля','05'=>'Мая','06'=>'Июня','07'=>'Июля','08'=>'Августа','09'=>'Сентября','10'=>'Октября','11'=>'Ноября','12'=>'Декабря');
	$contento = str_replace('{date_start}',date("d", $date_start)." ".$month[date("m", $date_start)],$contento);
	$contento = str_replace('{date_end}',date("d", $date_end)." ".$month[date("m", $date_end)],$contento);
	
	$cache = new RedisCache();
	if ( $url[1] == 'poipoi' ) {
		$mails = $Users_mails_test;
	} else {
		$mails = $cache -> hGetAll($url[1].":UsersAllowSpam");
	}
	
	$letter = new GearMan();
	$contento = $letter -> CreateLetter($typo_db_host, $typo_db_username, $typo_db_password, $typo_db, $TYPO3_CONF_VARS['SYS']['sitename'], $domain, $superTheme);
	foreach ( $mails as $key => $value ) {
		$creator = new GearMan();
		$creator -> GearmanCronSendClient(
			$TYPO3_CONF_VARS['SYS']['sitename'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_username'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_password'],
			$value,
			$contento,
			$superTheme
		);
	}
	break;
	
case "StartSendSpamType1":

	require_once('include/Gearman.php');
	require_once('include/RedisCache.php');
	$cache = new RedisCache();
	$mails = $cache -> hGetAll($url[1].":UsersAllowSpam");
	//$mails = array('vbhhbv' => 'aleks911f@yandex.ru');
	foreach ($mails as $key => $value) {
		$contento = file_get_contents('etemplates/empty.html');
		$contento = str_replace('{site_name}',$TYPO3_CONF_VARS['SYS']['sitename'],$contento);
		$contento = str_replace('{site_link}',$domain,$contento);
		$blocks = '<tr><td><div style="padding: 0px 15px;">
					<p>Приведи своих друзей на страничку конкурса на '.$domain.'.</p>
					<p>Выиграй бонусную  карту  ГОРОДА СКИДОК.</p>
					<p>На ней уже начислены 3400 бонусов. 1 бонус=1 рубль.</p>
					<p>Только первым 50 участникам, кто больше всех приведет посетителей на страничку.</p>
					<p>Приди и потрать их. <a href="http://'.$domain.'/prize/index.php?id='.$key.'&id_project=3" style="color: #3990b7;">Ваша персональная ссылка</a>.</p>
					</div></td></tr>';
		$contento = str_replace('{blocks}',$blocks,$contento);
		$creator = new GearMan();
		$creator -> GearmanCronSendClient(
			$TYPO3_CONF_VARS['SYS']['sitename'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_username'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_password'],
			$value,
			$contento
		);
	}
	
	break;
	
case "SenderMan":

	require_once('include/Gearman.php');
	require_once('include/RedisCache.php');
	$cache = new RedisCache();
	
	$city = $url[1];
	$superTheme = $_POST['superTheme'];
	$theme = $_POST['theme'];
	$morkovka = $_POST['morkovka'];
	$message = $_POST['message'];
	$otherslaves = $_POST['emails'];
	if ( $otherslaves != null) {
		$otherslaves = $_POST['emails'];
		$otherslaves = str_replace(" ","",$otherslaves);
		$otherslaves = explode(",", $otherslaves);
		foreach ( $otherslaves as $key => $value) {
			if (!eregi("^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\._-]+\.[a-zA-Z]{2,4}$",$value)) {
				unset($otherslaves[$key]);
			}
		}
	} else {
		$city = $url[1];
		$cache = new RedisCache();
		$otherslaves = $cache -> hGetAll($url[1].":UsersAllowSpam");
	}
	
	if ( $cache->hExists('AllSites:Morkovki', $morkovka)) {
	foreach ($otherslaves as $key => $value) {
		$contento = file_get_contents('etemplates/empty.html');
		$contento = str_replace('{site_name}',$TYPO3_CONF_VARS['SYS']['sitename'],$contento);
		$contento = str_replace('{site_link}',$domain,$contento);
		$texto = "<h1>".$theme."</h1><p>".$message."<p>";
		$contento = str_replace('{blocks}',$texto,$contento);
		$contento = str_replace('{UserName}',$key,$contento);
		
		$creator = new GearMan();
		$creator -> GearmanCronSendClient(
			$TYPO3_CONF_VARS['SYS']['sitename'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_username'],
			$TYPO3_CONF_VARS['MAIL']['transport_smtp_password'],
			$value,
			$contento,
			$superTheme
		);
		$cache->hSet('AllSites:Morkovki', $morkovka, date('c', strtotime('now')));
	}
	}
	
	break;

case "timer":
	
	echo date('y.m.d H.i.s', strtotime('now'));
	break;
	
case "getLatLong":

	require_once('include/RedisCache.php');
	
	
	if(array_key_exists('callback', $_GET)){
		$firm_uid = $_GET['firm'];
		mysql_connect($typo_db_host, $typo_db_username, $typo_db_password);
		mysql_select_db($typo_db);
		mysql_query("set names utf8");
		$result = mysql_query("SELECT tx_odsosm_lat,tx_odsosm_lon FROM tt_address WHERE uid = '$firm_uid'");
		
		while($row = mysql_fetch_array($result)) {
			$my_array = array('lat'=>$row['tx_odsosm_lat'], 'lon'=>$row['tx_odsosm_lon']);
		};
		echo $_GET['callback']."(".json_fix_cyr(json_encode($my_array)).");";
	} else {
		$my_array = array('data' => 'none');
		echo $_GET['callback']."(".json_fix_cyr(json_encode($my_array)).");";
	}
	
	//$my_array = array('lat'=>30.32, 'long'=>24.32);
	//echo $_GET['callback']."(".json_fix_cyr(json_encode($my_array)).");";
	break;
    
case "GetHeadMenu":
        require_once('include/RedisCache.php');
            
        $aM = array(
                'tc'        =>'<li><a href="http://'.$domain.'/katalog/torgovye-centry/">ТЦ</a></li>',
                'map'       =>'<li><a href="http://'.$domain.'/karta/">Карта</a></li>',
                'news'      =>'<li><a href="http://'.$domain.'/novosti/">Новости</a></li>',
                'allSales'  =>'<li><a href="http://'.$domain.'/sait/rasshirennyi-poisk/vse-skidki/">Все скидки</a></li>',
                'allSales_yola' =>'<li><a href="http://'.$domain.'/sait/rasshirennyi-poisk/vse-razdely/">Все скидки</a></li>', 
                'club'  =>'<li><a href="http://club.'.$domain.'">Дисконтный клуб</a></li>',
                
                'gifts'     =>'<li><a href="http://podarki.'.$domain.'/">Подарки</a></li>',
                'gifts_red' =>'<li><a href="http://podarki.'.$domain.'/" style="color:red;">Подарки</a></li>',
                
                'doska'     =>'<li><a href="http://doska.'.$domain.'/">Объявления</a></li>',
                'afisha'    =>'<li><a href="http://'.$domain.'/afisha">Афиша</a></li>',
                'zakaz'     =>'<li><a href="http://zakaz.'.$domain.'/">Онлайн заказ</a></li>',
                
                'konkurs'   =>'<li><a href="http://konkurs.'.$domain.'/">Конкурсы</a></li>',
                'konkurs_red'   =>'<li><a href="http://konkurs.'.$domain.'/" style="color:red;">Конкурсы</a></li>',
                
                'user'      =>'<li style="float:right;margin-right:10px;"><a href="/polzovateli/kabinet/"><img src="fileadmin/templates/main/img/key.png" />Вход</a></li>'
            );
        
    switch ($url[1]) {
        case "yola":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales_yola'] . $aM['doska'] . $aM['gifts_red'] . $aM['afisha'] . $aM['konkurs'];break;
        
        case "kirov":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['doska'] . $aM['gifts_red'] . $aM['konkurs'] . $aM['afisha'];break;
        
        case "astrachan":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts_red'] . $aM['doska'] . $aM['afisha'] . $aM['konkurs'];break;
        
        case "kemerovo":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts'] . $aM['doska'] . $aM['afisha'] . $aM['konkurs'];break;
        
        case "chita":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts'] . $aM['doska'] . $aM['afisha'] . $aM['konkurs'];break;
        
        case "irkutsk":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['doska'] . $aM['gifts_red'] . $aM['afisha'] . $aM['konkurs'];break;
        
        case "saratov":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts_red'] . $aM['doska'] . $aM['konkurs']. $aM['afisha'];break;
        
        case "sakhalin":
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts_red'] . $aM['doska'] . $aM['konkurs']. $aM['afisha'];break;
        
        case "yakutsk": 
        $result = $aM['tc'] . $aM['map'] . $aM['news'] . $aM['allSales'] . $aM['gifts_red'] . $aM['doska'] . $aM['konkurs']. $aM['afisha'];break;
        }
        
        echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
        //echo $result;
    break;
    
case "GetFooterMenu":
        require_once('include/RedisCache.php');
        
        $aM = array(
                'about_us'  =>'<li><a href="http://'.$domain.'/sait/">О нас</a></li>',
                'contacts'  =>'<li><a href="http://'.$domain.'/sait/kontakty/">Контакты</a></li>',
                'advert'    =>'<li><a href="http://'.$domain.'/sait/reklamodateljam/">Рекламодателям</a></li>',
                
                'users'     =>'<li><a href="http://'.$domain.'/sait/polzovateljam/">Пользователям</a></li>',
                'users_km'  =>'<li><a href="http://'.$domain.'/o-nas/polzovateljam/">Пользователям</a></li>',
                
                'main'      =>'<li><a href="http://'.$domain.'/">Главная</a></li>',
                'sms'       =>'<li><a href="http://sms.skidki-yola.ru/">СМС</a></li>',
                'otzyvi'    =>'<li><a href="http://'.$domain.'/sait/otzyvy/">Отзывы о нас</a></li>',
                'sitemap'   =>'<li><a href="http://'.$domain.'/sait/karta-saita/">Карта сайта</a></li>',
                
                'map'       =>'<li><a href="http://'.$domain.'/karta/">Карта города</a></li>',
                'map_yola'  =>'<li><a href="http://'.$domain.'/karta/">Карта Йошкар-Олы</a></li>',
                
                'rss'       =>'<li><a href="http://'.$domain.'/index.php?id=15&type=100/">RSS</a></li>',
                
                'links'     =>'<li><a href="http://'.$domain.'/poleznye-stati/">Полезные статьи</a></li>',
                
                'vk'        =>'<li><a href="http://vk.com" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_yola'   =>'<li><a href="http://vk.com/club20346376" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_ast'   =>'<li><a href="http://vk.com/club50848119" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_chita' =>'<li><a href="http://vk.com/club52815280" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_sarat' =>'<li><a href="http://vk.com/regionsaratov" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_sakh'  =>'<li><a href="http://vk.com/club50032272" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
                'vk_ykt'   =>'<li><a href="http://vk.com/club51106428" style="padding-bottom:0;"><img src="fileadmin/templates/main/img/vk.png" alt="" /></a></li>',
            );
        
        switch ($url[1]) {
            case "yola":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['otzyvi'] . $aM['rss'] . $aM['vk_yola'];break;
            
            case "kirov":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['links'] . $aM['rss'];break;
            
            case "astrachan":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk_ast'] . $aM['rss'];break;
            
            case "kemerovo":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users_km'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk'] . $aM['rss'];break;
            
            case "chita":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk'] . $aM['links'] . $aM['rss'];break;
            
            case "irkutsk":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk'] . $aM['rss'];break;
            
            case "saratov":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk_sarat'] . $aM['rss'];break;
            
            case "sakhalin":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk_sakh'] . $aM['rss'];break;
            
            case "yakutsk":
            $result = $aM['about_us'] . $aM['contacts'] . $aM['advert'] . $aM['users'] . $aM['main'] . $aM['sms'] . $aM['sitemap'] . $aM['vk_ykt'] . $aM['rss'];break;
            }
            
        echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
        //echo $result;
    break;
	
case "company_register":

	require_once('include/RedisCache.php');
  
  # получаем логин и пароль
  # проверяем есть ли такой логин-пароль в редиске
  # если логина с паролем нет - записываем нового пользователя и возвращаем ему уникальный ключ, проверяем входные данные и сохраняем в мускул
  # если логин-пароль есть в редиске - генерим ключ, записываем в редиску и выдаем пользователю
  
  $login = $url[3];
  $pass = $url[4];
  $key = md5(uniqid(rand(1,999), true));
  $result =  Array("login" => $login, "pass" => $pass, "key" => $key);
  echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	break;
  
case "company_login":

	require_once('include/RedisCache.php');
  
  # получаем логин и пароль
  # проверяем есть ли такой логин-пароль в редиске
  # если логина с паролем нет - импортируем всех пользователей из мускула и снова смотрим, если всё равно нет - шлем лесом
  # если логин-пароль есть в редиске - генерим ключ - высылаем форму добавления скидок
  
	
  $login = $url[3];
  $pass = $url[4];
  $key = md5(uniqid(rand(1,999), true));
  $result =  Array("login" => $login, "pass" => $pass, "key" => $key);
  echo $_GET['callback']."(".json_fix_cyr(json_encode($result)).");";
	break;
  
case "company_data":

	require_once('include/RedisCache.php');
  
  # проверяем есть ли такой логин-пароль в редиске
  # если ключа нет - шлем лесом
  # если ключ есть - проверяем входные данные и отправляем менеджеру
  
	echo date('y.m.d H.i.s', strtotime('now'));
	break;
  
default:				header('Location: /404.html');exit();break;
}

?>
