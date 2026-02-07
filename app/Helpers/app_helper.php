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

    if (!function_exists('generateShortUrl')) {
        function generateShortUrl($qrCode)
		{
			return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8).'-'.$qrCode;
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

	if (!function_exists('inspectQuestionsAndNotify')) {
		function inspectQuestionsAndNotify(array $questionsData, int $inspectionId, int $assetId, int $swachhagrahiId, string $swachhagrahiName): void
		{
			if (empty($questionsData)) {
				return;
			}

			// Load config (also reused later for notification channels)
			$AppConfig = new \Config\AppConfig();

			$questionIds = [];
			foreach ($questionsData as $item) {
				if (isset($item['que']) && is_numeric($item['que'])) {
					$questionIds[] = (int) $item['que'];
				}
			}

			if (empty($questionIds)) {
				return;
			}

			$questionIds = array_values(array_unique($questionIds));

			/** @var array<int, array<string,mixed>> $questions */
			$questions = [];

			// Use cache if enabled in AppConfig->cache
			$cacheEnabled = !empty($AppConfig->cache['enabled']);
			$cache        = null;
			$cacheKey     = null;

			if ($cacheEnabled) {
				$cache    = \Config\Services::cache();
				$cacheKey = $AppConfig->cache['prefix'] . 'questions_' . md5(json_encode($questionIds));
				$cached   = $cache->get($cacheKey);
				if (is_array($cached) && !empty($cached)) {
					$questions = $cached;
				}
			}

			if (empty($questions)) {
				$questionsModel = new \App\Models\QuestionsModel();
				foreach ($questionsModel->select('question_id, question_text, severity, sla, is_mandatory, condition_type, condition_value')->whereIn('question_id', $questionIds)->where('is_active', 1)->findAll() as $row) {
					$questions[(int) $row['question_id']] = $row;
				}

				if ($cacheEnabled && $cache && $cacheKey) {
					$cache->save($cacheKey, $questions,	(int) $AppConfig->cache['expiration']);
				}
			}

			if (empty($questions)) {
				return;
			}

			$failed = [];
			foreach ($questionsData as $item) {
				$questionId = isset($item['que']) && is_numeric($item['que']) ? (int) $item['que'] : null;
				if (!$questionId || !isset($questions[$questionId])) {
					continue;
				}

				$qRow   = $questions[$questionId];
				$answer = isset($item['ans']) ? trim((string) $item['ans']) : '';

				// Treat missing answer for mandatory questions as a failure
				if ((int) ($qRow['is_mandatory'] ?? 1) === 1 && $answer === '') {
					$failed[] = [
						'question_id'   => $questionId,
						'question_text' => $qRow['question_text'] ?? '',
						'severity'      => $qRow['severity'] ?? 'MEDIUM',
						'sla'           => $qRow['sla'] ?? 60,
						'given_answer'  => $answer,
						'reason'        => 'Mandatory question not answered.',
					];
					continue;
				}

				$conditionType  = $qRow['condition_type'] ?? null;
				$conditionValue = $qRow['condition_value'] ?? null;

				if (empty($conditionType) || $conditionValue === null || $conditionValue === '') {
					continue;
				}

				$conditionFailed = false;
				$reason          = '';

				switch ($conditionType) {
					case 'EQUALS':
						// Failure when the given answer equals the configured condition value
						if (strcasecmp($answer, (string) $conditionValue) === 0) {
							$conditionFailed = true;
							$reason = 'Answer equals disallowed value: ' . $conditionValue;
						}
						break;

					case 'NOT_EQUALS':
						if (strcasecmp($answer, (string) $conditionValue) !== 0) {
							$conditionFailed = true;
							$reason = 'Answer must equal ' . $conditionValue;
						}
						break;

					case 'GREATER_THAN':
						if (is_numeric($answer) && is_numeric($conditionValue) && (float) $answer > (float) $conditionValue) {
							$conditionFailed = true;
							$reason = 'Answer is greater than ' . $conditionValue;
						}
						break;

					case 'LESS_THAN':
						if (is_numeric($answer) && is_numeric($conditionValue) && (float) $answer < (float) $conditionValue) {
							$conditionFailed = true;
							$reason = 'Answer is less than ' . $conditionValue;
						}
						break;

					case 'CONTAINS':
						if (stripos((string) $answer, (string) $conditionValue) !== false) {
							$conditionFailed = true;
							$reason = 'Answer contains disallowed value: ' . $conditionValue;
						}
						break;
				}

				if ($conditionFailed) {
					$failed[] = [
						'question_id'   => $questionId,
						'question_text' => $qRow['question_text'] ?? '',
						'severity'      => $qRow['severity'] ?? 'MEDIUM',
						'sla'           => $qRow['sla'] ?? 60,
						'given_answer'  => $answer,
						'reason'        => $qRow['question_text'] . ' - ' . $reason
					];
				}
			}

			if (empty($failed)) {
				return;
			}

			// Derive related asset/vendor details
			$vendorName         = '';
			$vendorEmail        = '';
			$vendorPhone        = '';
			$vendorId           = 1;
			$qrCode          = '';
			try {
				$assetsModel    = new \App\Models\SanitationAssetsModel();
				$usersModel     = new \App\Models\UsersModel();
				$incidentsModel = new \App\Models\SanitationIncidentsModel();

				$assetRow = $assetsModel->select('vendor_id, qr_code')->where('sanitation_asset_id', $assetId)->first();
				if (is_array($assetRow)) {
					$qrCode = $assetRow['qr_code'] ?? '';
					if (!empty($assetRow['vendor_id'])) {
						$vendorId = (int) $assetRow['vendor_id'];
					}
				}

				// If we still do not have a reasonable recipient for phone/email, try user #1 as admin
				$userRow = $usersModel->select('email, phone')->where('user_id', $vendorId)->first();
				if (is_array($userRow)) {
					$vendorEmail = $userRow['email'] ?? '';
					$vendorPhone = $userRow['phone'] ?? '';
				}

				// Create one incident per failed question
				foreach ($failed as $f) {
					$incidentCode = 'INC' . date('YmdHis') . '_' . $inspectionId . '_' . $f['question_id'];

					$incidentsModel->insert([
						'incident_code'  => $incidentCode,
						'inspection_id'  => $inspectionId,
						'response_id'    => 0,
						'asset_id'       => $assetId,
						'question_id'    => $f['question_id'],
						'reported_by'    => $swachhagrahiId,
						'resolved_by'    => null,
						'due_date'       => date('Y-m-d H:i:s', strtotime('+' . ($f['sla'] ?? 60) . ' minutes')),
						'vendor_id'      => $vendorId,
						'severity'       => $f['severity'] ?? 'MEDIUM',
						'description'    => $f['reason'] ?? '',
						'incident_status'=> 'OPEN',
					]);
				}
			} catch (\Throwable $e) {
				// In helper context we silently ignore lookup / incident errors and still attempt notification
			}

			// Build grouped message
			$lines = [];
			$lines[] = 'Inspection #' . $inspectionId . ' has validation failures.';
			$lines[] = 'Asset QR Code: ' . $qrCode;
			if ($vendorName !== '') {
				$lines[] = 'Vendor: ' . $vendorName;
			}
			$lines[] = 'Performed by Swachhagrahi: ' . $swachhagrahiName;
			$lines[] = '';
			$lines[] = 'Failed questions:';

			$highestSeverity = 'MEDIUM';
			$severityOrder   = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];

			foreach ($failed as $idx => $f) {
				$line = ($idx + 1) . '. Q#' . $f['question_id'] . ' [' . ($f['severity'] ?? 'MEDIUM') . ']';
				if (!empty($f['question_text'])) {
					$line .= ' - ' . $f['question_text'];
				}
				if (!empty($f['given_answer'])) {
					$line .= ' | Answer: ' . $f['given_answer'];
				}
				if (!empty($f['reason'])) {
					$line .= ' | Reason: ' . $f['reason'];
				}
				$lines[] = $line;

				$sev  = strtoupper((string) ($f['severity'] ?? 'MEDIUM'));
				$curr = $severityOrder[$sev] ?? $severityOrder['MEDIUM'];
				$max  = $severityOrder[$highestSeverity] ?? $severityOrder['MEDIUM'];
				if ($curr > $max) {
					$highestSeverity = $sev;
				}
			}

			$message = implode("\n", $lines);
			$title   = 'Inspection validation failures';

			// Persist in-app notification
			try {
				$notificationsModel = new \App\Models\NotificationsModel();

				$notificationId = $notificationsModel->insert([
					'user_id'            => $vendorId,
					'notification_type'  => 'INCIDENT_ASSIGNED',
					'title'              => $title,
					'message'            => $message,
					'related_entity_type'=> 'INSPECTION',
					'related_entity_id'  => $inspectionId,
					'is_read'            => 0,
					'priority'           => $highestSeverity === 'CRITICAL' ? 'HIGH' : 'MEDIUM',
				], true);

				if ($notificationId) {
					// SMS notification
					if (!empty($AppConfig->sms['enabled']) && $AppConfig->sms['enabled'] && !empty($vendorPhone)) {
						sendSmsNotification($vendorPhone, $title, $message);
					}

					// WhatsApp notification
					if (!empty($AppConfig->whatsapp['enabled']) && $AppConfig->whatsapp['enabled'] && !empty($vendorPhone)) {
						sendWhatsappNotification($vendorPhone, $title, $message);
					}

					// Email notification
					if (!empty($AppConfig->email['enabled']) && $AppConfig->email['enabled'] && !empty($vendorEmail)) {
						sendEmailNotification($vendorEmail, $title, $message, $AppConfig);
					}
				}
			} catch (\Throwable $e) {
				// Fail silently: the main inspection creation flow must not break
			}
		}
	}

	if (!function_exists('sendSmsNotification')) {
		/**
		 * Basic SMS notification sender.
		 * This is a lightweight wrapper intended to be adapted to the actual SMS gateway.
		 */
		function sendSmsNotification(string $phone, string $title, string $message): bool
		{
			$AppConfig = new \Config\AppConfig();
			if (empty($AppConfig->sms['enabled']) || !$AppConfig->sms['enabled']) {
				return false;
			}

			$phone = phoneCleanup($phone);
			if ($phone === '') {
				return false;
			}

			// Prepare a concise SMS-friendly message
			$text = $title . ': ' . $message;
			$text = substr($text, 0, 480); // basic safety limit
			$text = nl2sms($text);

			// Placeholder: integrate with actual SMS provider here.
			// We keep this as a no-op that returns true to not block the flow.
			return true;
		}
	}

	if (!function_exists('sendWhatsappNotification')) {
		/**
		 * Basic WhatsApp notification sender.
		 * Intended as a placeholder for the real WhatsApp gateway integration.
		 */
		function sendWhatsappNotification(string $phone, string $title, string $message): bool
		{
			$AppConfig = new \Config\AppConfig();
			if (empty($AppConfig->whatsapp['enabled']) || !$AppConfig->whatsapp['enabled']) {
				return false;
			}

			$phone = phoneCleanup($phone);
			if ($phone === '') {
				return false;
			}

			// Placeholder: build WhatsApp API request using $AppConfig->whatsapp
			// and send it via cURL. Currently treated as a no-op.
			return true;
		}
	}

	if (!function_exists('sendEmailNotification')) {
		/**
		 * Basic email notification sender using CodeIgniter's email service.
		 */
		function sendEmailNotification(string $to, string $subject, string $body, \Config\AppConfig $AppConfig): bool
		{
			if (empty($AppConfig->email['enabled']) || !$AppConfig->email['enabled']) {
				return false;
			}

			try {
				$email = \Config\Services::email();

				if (!empty($AppConfig->email['SMTPHost'])) {
					$email->setSMTPHost($AppConfig->email['SMTPHost']);
				}
				if (!empty($AppConfig->email['SMTPUser'])) {
					$email->setSMTPUser($AppConfig->email['SMTPUser']);
				}
				if (!empty($AppConfig->email['SMTPPass'])) {
					$email->setSMTPPass($AppConfig->email['SMTPPass']);
				}
				if (!empty($AppConfig->email['SMTPPort'])) {
					$email->setSMTPPort($AppConfig->email['SMTPPort']);
				}

				$fromEmail = $AppConfig->email['fromEmail'] ?? null;
				$fromName  = $AppConfig->email['fromName'] ?? null;
				if ($fromEmail) {
					$email->setFrom($fromEmail, $fromName ?: $fromEmail);
				}

				$email->setTo($to);
				$email->setSubject($subject);
				$email->setMessage($body);

				return $email->send();
			} catch (\Throwable $e) {
				return false;
			}
		}
	}

