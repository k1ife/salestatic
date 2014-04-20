<?php

class RedisCache {

	function hSet($hKey, $key, $data) { /* добавляем в кэш > ключ -> значение < и заменяем, если ключ повторяется */
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->hSet($hKey, $key, $data);
	}
	
	function hMset($hKey, $data) { /* добавляем в кэш массивом > array('name' => 'Joe', 'salary' => 2000) < */
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->hMset($hKey, $data);
	}
	
	function hSetTimeout($hKey, $key, $data, $timeout) {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->hSet($hKey, $key, $data);
		$redis->setTimeout($hKey, $timeout);
	}
	
	function hMsetTimeout($hKey, $data, $timeout) {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->hMset($hKey, $data);
		$redis->setTimeout($hKey, $timeout);
	}	
	
	function expireAt($hKey, $when) {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->expireAt($hKey, $when);
	
	}
	
	function hGetAll($hKey) {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->hGetAll($hKey);
		return $result;
		
	}
	
	function hmGet($hKey, $key) {
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->hmGet($hKey, $key);
		return $result;
		
	}
	
	function hIncrBy($hKey, $key, $val) { /* увеличиваем значение на заданное число */
		
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->hIncrBy($hKey, $key, $val);
		
	}
	
	function hExists($hKey, $key) { /* проверяем существование в хэше по ключу */
	
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->hExists($hKey, $key);
		return $result;
	}

	function hLen($hKey) { /* считаем количество ключей в хэше */
	
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->hLen($hKey);
		return $result;
	}

	function hVals($hKey) { /* возвращаем только значения ключей в хэше */
	
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->hVals($hKey);
		return $result;
	}
	

	function rename($old, $new) { /* переименовываем ключ */
	
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$result = $redis->rename($old, $new);
	}
	
	function delete($key) {
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		$redis->delete($key);
	}
	
}

function json_fix_cyr($json_str) {
	$cyr_chars = array (
	'\u0430' => 'а', '\u0410' => 'А',
	'\u0431' => 'б', '\u0411' => 'Б',
	'\u0432' => 'в', '\u0412' => 'В',
	'\u0433' => 'г', '\u0413' => 'Г',
	'\u0434' => 'д', '\u0414' => 'Д',
	'\u0435' => 'е', '\u0415' => 'Е',
	'\u0451' => 'ё', '\u0401' => 'Ё',
	'\u0436' => 'ж', '\u0416' => 'Ж',
	'\u0437' => 'з', '\u0417' => 'З',
	'\u0438' => 'и', '\u0418' => 'И',
	'\u0439' => 'й', '\u0419' => 'Й',
	'\u043a' => 'к', '\u041a' => 'К',
	'\u043b' => 'л', '\u041b' => 'Л',
	'\u043c' => 'м', '\u041c' => 'М',
	'\u043d' => 'н', '\u041d' => 'Н',
	'\u043e' => 'о', '\u041e' => 'О',
	'\u043f' => 'п', '\u041f' => 'П',
	'\u0440' => 'р', '\u0420' => 'Р',
	'\u0441' => 'с', '\u0421' => 'С',
	'\u0442' => 'т', '\u0422' => 'Т',
	'\u0443' => 'у', '\u0423' => 'У',
	'\u0444' => 'ф', '\u0424' => 'Ф',
	'\u0445' => 'х', '\u0425' => 'Х',
	'\u0446' => 'ц', '\u0426' => 'Ц',
	'\u0447' => 'ч', '\u0427' => 'Ч',
	'\u0448' => 'ш', '\u0428' => 'Ш',
	'\u0449' => 'щ', '\u0429' => 'Щ',
	'\u044a' => 'ъ', '\u042a' => 'Ъ',
	'\u044b' => 'ы', '\u042b' => 'Ы',
	'\u044c' => 'ь', '\u042c' => 'Ь',
	'\u044d' => 'э', '\u042d' => 'Э',
	'\u044e' => 'ю', '\u042e' => 'Ю',
	'\u044f' => 'я', '\u042f' => 'Я',
	
	'\r' => '',
	'\n' => '<br />',
	'\t' => ''
);

foreach ($cyr_chars as $cyr_char_key => $cyr_char) {
	$json_str = str_replace($cyr_char_key, $cyr_char, $json_str);
	}
	return $json_str;
} 	

function CleanWord($data) {
	$data = str_replace(" ","",$data);
	$data = str_replace("&", "", $data);
	$data = str_replace("?", "", $data);
	$data = str_replace(">", "", $data);
	$data = str_replace("<", "", $data);
	$data = str_replace("/", "", $data);
	$data = str_replace("|", "", $data);
	$data = str_replace("!", "", $data);
	$data = str_replace("#", "", $data);
	$data = str_replace("$", "", $data);
	$data = str_replace("%", "", $data);
	$data = str_replace("^", "", $data);
	$data = str_replace("*", "", $data);
	$data = str_replace("(", "", $data);
	$data = str_replace(")", "", $data);
	$data = str_replace("№", "", $data);
	$data = str_replace(";", "", $data);
	$data = str_replace(":", "", $data);
	$data = str_replace('"', "", $data);
	return $data;
}

?>