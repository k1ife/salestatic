<?php

require_once('RedisCache.php');
require_once('mail/class.phpmailer.php');

class GearMan {
	
	private $SMTPlogin;
	private $SMTPpass;
	
	public function SetGearMan($SMTPlogin, $SMTPpass) {
		$this->SMTPlogin = $SMTPlogin;
		$this->SMTPpass = $SMTPpass;
	}
	
	public function GearmanSendServer($emailto, $letter) {
		$mail = new PHPMailer();
		$mail -> Host = 'smtp.yandex.ru';
		$mail -> Port = 587;
		$mail -> From = $this->SMTPlogin;
		$mail -> FromName = "Портал скидок";
		$mail -> Username = $this->SMTPlogin;
		$mail -> Password = $this->SMTPpass;
		$mail -> IsSMTP();
		$mail -> AddAddress($emailto);
		$mail -> MsgHTML($letter);
		if(!$mail->Send()) {
			$cache = new RedisCache();
			$hKey = $city.":EMailLog";
			$cache -> hSet($hKey, date('Y-m-d H-i-s', strtotime('now')), 'Ошибка - '.$mail->ErrorInfo);
		} else {
		}
	}
	
	public function GearmanCronSendClient($sitename, $emaillogin, $emailpass, $receiver, $contento, $superTheme) {
		$client= new GearmanClient();
		$client->addServer();
		$data = array('sitename' => $sitename, 'emaillogin' => $emaillogin, 'emailpass' => $emailpass, 'receiver' => $receiver, 'contento' => $contento, 'superTheme' => $superTheme);
		$client->doBackground("reverse", serialize($data));
	}
	
	public function GearmanCronStartServer() {
		$worker= new GearmanWorker();
		$worker->addServer();
		$worker->addFunction("reverse", "reverse_fn");
		while ($worker->work());
	}
	
	public function CreateLetter($host, $username, $pass, $database, $sitename, $domain) {
		$contento = file_get_contents('./etemplates/cronspam.html');	
		$contento = str_replace('{site_name}',$sitename,$contento);
		$contento = str_replace('{site_link}',$domain,$contento);
		
		$link = mysql_connect($host, $username, $pass) or die("Не могу соединиться с MySQL.");
		mysql_select_db($database) or die("Не могу подключиться к базе.");
		mysql_query("set names utf8");
		
		$date_start = strtotime('this Thursday');
		$month_start = date('m', $date_start);
		$date_end = $date_start - 608400;
		$month_end = date('m', $date_end);
		$month = array('01'=>'Января','02'=>'Февраля','03'=>'Марта','04'=>'Апреля','05'=>'Мая','06'=>'Июня','07'=>'Июля','08'=>'Августа','09'=>'Сентября','10'=>'Октября','11'=>'Ноября','12'=>'Декабря');
		$icons = array(
		'avto' => 'avto.png','atele-rukodelie' => 'atele.png',	'banki' => 'banki.png',	'besplatno' => 'besplatno.png','blagotvoritelnost' => 'blagotvoritelnost.png',
		'detjam' => 'detam.png','zhivotnym' => 'zoomagazin.png','zdorove-krasota' => 'kosmitika.png','intim-tovary' => 'intim.png','knigi-kanctovary' => 'knigi.png',
		'kompjutery' => 'komputer.png','nedvizhimost' => 'nedvijemost.png','obrazovanie' => 'obrazovanie.png','odezhda' => 'odejda.png','otdykh-puteshestvija' => 'otduh-razvlechenie.png',
		'prazdniki-svadby' => 'prazdniki.png','produkty' => 'produktu.png','rabota' => 'rabota.png','razvlechenija' => 'razvlechenie.png','reklama-poligrafija' => 'reklama.png',
		'restorany-kafe' => 'restoran.png','stroitelstvo' => 'stroitelstvo.png','tovary-dlja-doma' => 'dlya-doma.png','telefony' => 'telefonu.png','uslugi' => 'uslugi.png',
		'cvety-podarki' => 'cvety-podarky.png','juvelirnye-izdelija' => 'juvelir-magazin.png');
		
		$borderColors = array('avto' => '#444','atele-rukodelie' => '#bf279a',	'banki' => '#f9e300',	'besplatno' => '#1c72c8','blagotvoritelnost' => '#c8ac70',
		'detjam' => '#1c72c8','zhivotnym' => '#aadb08','zdorove-krasota' => '#1c72c8','intim-tovary' => '#f88f00','knigi-kanctovary' => '#1c72c8',
		'kompjutery' => '#be6770','nedvizhimost' => '#cbb073','obrazovanie' => '#1c72c8','odezhda' => '#bf279a','otdykh-puteshestvija' => '#f88f00',
		'prazdniki-svadby' => '#f88f00','produkty' => '#1c72c8','rabota' => '#be6770','razvlechenija' => '#f88f00','reklama-poligrafija' => '#aadb08',
		'restorany-kafe' => '#bf279a','stroitelstvo' => '#444','tovary-dlja-doma' => '#aadb08','telefony' => '#be6770','uslugi' => '#cbb073',
		'cvety-podarki' => '#bf279a','juvelirnye-izdelija' => '#444');
		$contento = str_replace('{date_start}',date("d", $date_start)." ".$month[date("m", $date_start)],$contento);
		$contento = str_replace('{date_end}',date("d", $date_end)." ".$month[date("m", $date_end)],$contento);
		
		$result = mysql_query("SELECT * FROM tt_news LEFT JOIN tt_news_cat_mm ON tt_news.uid=tt_news_cat_mm.uid_local WHERE tt_news_cat_mm.uid_foreign != 10 AND tt_news.hidden = 0 AND tt_news.deleted = 0 AND tt_news.crdate > '$date_end' AND tt_news.crdate < '$date_start'");
		while($row = mysql_fetch_array($result))
		{
			$results[$row['title']] = array('text' => $row['short'], 'link' => $row['ext_url'], 'findate' => date('m.d', $row['endtime']), 'saletype' => $row['tx_ttnewsfield_linktitle'], 'saletime' => $row['tx_ttnewssaletime_saletime'], 'newprice' => $row['tx_ttnewsnewprice_newprice'], 'percent' => $row['tx_ttnewspercent_percent']);
		}
		mysql_close($link);
		
		$i = 1;
		foreach( $results as $Mkey => $Mvalue ) {
			if ( $i%2 === 0 ) {
				$bgcolor = '#f8f8f8';
			} else {
				$bgcolor = '#fcfcfc';
			}
				
			foreach ( $icons as $key => $value ) {
				if ( preg_match("/".$key."/", $Mvalue['link']) ) {
					$iconImg = $value;
				} else {
          $iconImg = $icons['razvlechenija'];
        }
			}
			foreach ( $borderColors as $key => $value ) {
				if ( preg_match("/".$key."/", $Mvalue['link']) ) {
					$btop = $value;
				}
			}
			
			/*if ( $Mkey['saletime'] != '' || $Mkey['saletime'] != NULL ) {
				$SuperPuperData = 'Акция до: '.$Mkey['saletime'].'!';
			} else {
				if ( $Mkey['newprice'] != '' || $Mkey['newprice'] != NULL ) {
					$SuperPuperData = 'Новая цена : '.$Mkey['newprice'].'!';
				} else {
					if ( $Mkey['percent'] != '' || $Mkey['percent'] != NULL ) {
						$SuperPuperData = 'Скидка: '.$Mkey['percent'].'!';
					}
				}
			}*/
			
			if (isset($Mkey['saletype']) && $Mkey['saletype'] != NULL && $Mkey['saletype'] != '') {
				if ( isset($Mkey['saletime'])) {
					$SuperPuperData = '<p>'.$Mvalue['saletype'].' :'.$Mvalue['saletime'].'!</p>';
					
					if ( isset($Mkey['percent']) ) {
						$SuperPuperData = '<p>'.$Mvalue['saletype'].' '.$Mvalue['percent'].' '.$Mvalue['saletime'].'!</p>';
					}
					
				} 
				if ( $SuperPuperData == '<p>  !</p>' ) {
					$SuperPuperData = '<p>Время действия ограничено!</p>';
				}
			}
			
			$resultedBlocks[$i] = '<td style="background: '.$bgcolor.';text-align:center;width:190px;border-top:2px solid '.$btop.';padding-top:5px;overhidden:none;" valign="top">
                            <img src="http://salestatic.ru/catIcons/'.$iconImg.'" alt=""><div style="padding: 15px;text-align:left;">
                                <h2 style="font-weight: 100; color: #3990b7; margin-top: 0px; margin-bottom: 10px;">'.$Mkey.'</h2>
								'.$SuperPuperData.'
                                <p>'.$Mvalue['text'].'<a href="'.$Mvalue['link'].'" style="color: #3990b7"> Подробнее »</a></p></div></td>';
			$i++;
		}
		$i = 1;
		$totalResult = '';
		foreach ( $resultedBlocks as $key => $value) {
			if ( $i == 1 ) {
				$totalResult .= '<tr>';
				$totalResult .= $resultedBlocks[$key];
			}
			if ( $i == 2 ) {
				$totalResult .= $resultedBlocks[$key];
			}
			if ( $i == 3 ) {
				$totalResult .= $resultedBlocks[$key];
				$totalResult .= '</tr>';
				$i = 0;
			}
			$i++;
		}
	
		$contento = str_replace('{blocks}',$totalResult,$contento);
		
		return $contento;
	}
	
}

function reverse_fn($job) {
	
	$workload= $job->workload();
	$data = unserialize($workload);
	
	$redis = new Redis();
	$redis->pconnect('127.0.0.1', 6379);
	$redis->hSet('testKey:Gearman', $data['receiver'], $data['sitename']);
	sleep(5);
	$mail = new PHPMailer();
	$mail -> Host = 'smtp.yandex.ru';
	$mail -> Port = 587;
	$mail -> From = $data['emaillogin'];
	$mail -> FromName = $data['sitename'];
	$mail -> Username = $data['emaillogin'];
	$mail -> Password = $data['emailpass'];
	$mail -> IsSMTP();
	$mail -> AddAddress($data['receiver']);
	$mail -> Subject  = $data['superTheme'];
	$mail -> MsgHTML($data['contento']);
	if(!$mail->Send()) {
		return strrev($workload);
	} else {
	}
	
}

?>