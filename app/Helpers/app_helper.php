<?php
	if (!function_exists('root_url')) {
		function root_url($uri = "")
		{
			$url_parts = parse_url(base_url());
			return $url_parts['scheme'] . '://' . $url_parts['host'] . (isset($url_parts['port']) ? ':' . $url_parts['port'] : '')  . '/' . ltrim($uri, '/');
		}
	}
	if (!function_exists('site_title')) {
		function site_title($title = '')
		{
			$AppConfig = new \Config\AppConfig();
			if ($AppConfig->appName) {
				$title = empty($title) ? $AppConfig->appName : $title . ' | ' . $AppConfig->appName;
			}
			return $title;
		}
	}
	if (!function_exists('fullname')) {
		function fullname($firstname = "", $lastname = "")
		{
			return trim($firstname . ' ' . $lastname);
		}
	}

	if(!function_exists('generateUserCode')){
		function generateUserCode(){
			$AppConfig = new \Config\AppConfig();
			$usersModel = new \App\Models\UsersModel();
			$max = $usersModel->selectMax('user_id')->get()->getRowArray();
			if($max){
				$max = $max['user_id'] + 1;
			} else {
				$max = 1;
			}
			return $AppConfig->userCodePrefix . date('YmdHis') . rand(100, 999).str_pad($max, 5, '0', STR_PAD_LEFT);
		}
	}

	if (!function_exists('paging')) {
		function paging($page = 1, $records = 0, $length = 25)
		{
			$totalpages = ceil($records / $length);
			if ($totalpages < 1) {
				$totalpages = 1;
			}
			if ($page > $totalpages) {
				$page = $totalpages;
			}
			$offset = (($page - 1) * $length);
			$from = $records > 0 ? ($offset + 1) : 0;
			$to = (int) ($totalpages == $page ? $records : ($from + $length) - 1);
			$paging = array('from' => $from, 'to' => $to, 'totalrecords' => (int) $records, 'totalpages' => $totalpages, 'currentpage' => $page, 'offset' => $offset, 'length' => $length);
			return $paging;
		}
	}

	if (!function_exists('moneyFormat')) {
		function moneyFormat($amount, $decimal = 0)
		{
			return 'rs ' . number_format(($amount * 1), $decimal);
		}
	}

	if (!function_exists('deleteFile')) {
		function deleteFile($file = '')
		{
			if (!empty($file)) {
				if (file_exists($file)) {
					chmod($file, 0777);
					unlink($file);
				}
			}
		}
	}
	if (!function_exists('phoneCleanup')) {
		function phoneCleanup($phone)
		{
			return preg_replace('/\D+/', '', $phone);
		}
	}
	if (!function_exists('phonePattern')) {
		function phonePattern($phone)
		{
			$phone =  preg_replace('/\D+/', '', $phone);
			if(  preg_match( '/^(\d{4})(\d{3})(\d{3})$/', $phone,  $matches ) )
			{
				$result = $matches[1] . ' ' .$matches[2] . ' ' . $matches[3];
				return $result;
				}else{
				return $phone;
			}
		}
	}

	if (!function_exists('nl2sms')) {
		function nl2sms($text)
		{
			return str_replace(array('<br>', '<br/>', '<br />', '/n', '/r/n'), '%0a', $text);
		}
	}
	if (!function_exists('previousUrl')) {
		function previousUrl($url='')
		{
			if(empty($url)){
				if(isset($_SERVER['HTTP_REFERER'])){
					$referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
					if (!empty($referer)) {
						$url = $referer;
						} else {
						$url ="javascript:history.go(-1)";
					}
					} else {
					$url ="javascript:history.go(-1)";
				}
			}
			return $url;
		}
	}
	if(!function_exists('replaceWord')) {
		function replaceWord($search, $replace, $subject) {
			return str_replace($search, $replace, $subject);
		}
	}
	if(!function_exists('replaceWordList')) {
		function replaceWordList($wordlist, $subject) {
			foreach($wordlist as $key => $val) {
				$subject = str_replace($key, $val, $subject);
			}
			return $subject;
		}
	}
	if(!function_exists('phpDate')){
		function phpDate($date)
		{
			$date = str_replace(array('/','.',' '),'-',$date);
			return date('Y-m-d',strtotime($date));
		}
	}
	if(!function_exists('phpDateTime')){
		function phpDateTime($datetime)
		{
			$date = str_replace(array('/',',','.',' '),'-',substr($datetime, 0, 12));
			$date = str_replace(array('--'),'-',$date);
			$time = substr($datetime, 13);
			return date('Y-m-d H:i:s',strtotime($date.' '.$time));
		}
	}
	if(!function_exists('text2Array')){
		function text2Array($values)
		{
			$values = str_replace(array("\n", "\r"), ',', $values);
			$values = explode(",", $values);
			foreach ($values as $k => $val) {
				$v = trim($val);
				if(strlen($v)==0){
					unset($values[$k]);
					} else {
					$values[$k] = $v;
				}
			}
			return $values;
		}
	}
	if(!function_exists('urlfileExist')){
		function urlfileExist($url)
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			return $code == 200 ? true : false;
		}
	}
	if(!function_exists('br2nl')){
		function br2nl($string='')
		{
			if(!empty($string)){
				return preg_replace('/<br(\s+)?\/?>/i', "\n", $string);
			}
			return $string;
		}
	}
	if(!function_exists('initials')){
		function initials($name='')
		{
			$name  = strtoupper($name);
			$words = explode(" ",$name);
			$firtsname = reset($words);
			$lastname  = end($words);
			return substr($firtsname,0,1).substr($lastname ,0,1);
		}
	}
	if (!function_exists('milliseconds')) {
		function milliseconds() {
			$mt = explode(' ', microtime());
			return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
		}
	}

	if (!function_exists('sendOtp')) {
		function sendOtp($user_id) {
			$model = null;
			$model = new \App\Models\UsersModel();
			$AppConfig = new \Config\AppConfig();
			if($model){
				$user = $model->find($user_id);
				$otp = rand(100000, 999999);
				$user['otp'] = $otp;
				$user['otp_expiry'] = date('Y-m-d H:i:s', strtotime('+' . $AppConfig->otpExpiry . ' minutes'));
				$user['otp_attempts'] = 0;
				if ($AppConfig->twoFactorAuth['enabled']) {
					if($AppConfig->twoFactorAuth['send']['email']){
						//send otp to email
						$email = $user['email'];
					}
					if($AppConfig->twoFactorAuth['send']['sms']){
						//send otp to sms
						$phone = $user['phone'];
					}
					if($AppConfig->twoFactorAuth['send']['whatsapp']){
						//send otp to whatsapp
						$whatsapp = $user['phone'];
					}
					$model->update($user_id, $user);
					return true;
				}
			} else {
				return false;
			}
		}
	}

