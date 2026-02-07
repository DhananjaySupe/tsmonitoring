<?php namespace Config;

	use CodeIgniter\Config\BaseConfig;

	class AppConfig extends BaseConfig
	{
		public $appName = 'Tentage and Sanitation';
		public $appDesc = "Tentage and Sanitation";
		public $appEmails = array();
		public $cssVersion = '1.0.0';
		public $jsVersion = '1.0.0';
		/* API Keys */
		public $apiKeyWebApp = 'fx4ni3n75wtxywa9wlu70fycp2e0ajxkh7o6adjshiifmvaukq57jyrs15e3d55u';
		public $apiKeyMobile = 'k9x4m2w7a8e0qf3n6t5y1ujsrhlvcpdobi5z7xawq9m3e2n8t4r6fyk0suvp1423';
		/* App Version */
		public $appCurrentVersion = '1.0.0';
		/* App Force Update */
		public $appForceUpdate = '1';
		/* App Download URL */
		public $appDownloadUrl = 'https://play.google.com/store/apps/details?id=com.kashit.tentagesanitation';
		/* App Download QR */
		public $appDownloadQr = 'assets/images/appqrcode/appurl.webp';
		/* JWT */
		public $jwtSecret = 'VNyLbLP7aGg9YKZXlshZqkRFahRLgf1L';
		/* JWT Expiry in seconds */
		public $jwtExpiryWebApp = 36000; // in seconds
		public $jwtExpiryMobile = 3600; // in seconds
		/* Single Login */
		public $singleLogin = true;
		/* User Code Prefix */
		public $userCodePrefix = 'KSH';
		/* Maximum OTP Attempts */
		public $maxOtpAttempts = 5;
		/* OTP Expiry in minutes*/
		public $otpExpiry = 10;
		/* Check User Type Permissions */
		public $checkUserTypePermissions = true;
		/* Maintenance mode for APIs (handled by MaintenanceMode filter) */
		public $maintenanceMode = false;
		public $maintenanceMessage = 'Service is under maintenance. Please try again later.';
		/* cache */
		public $cache = array(
			'enabled' => true,
			'prefix' => 'monitoring_',
			'expiration' => 60 * 60 * 24, //in seconds
		);
		/* AWS S3 */
		public $S3 = array(
			'enabled' => false,
			'key' => '',
			'secret' => '',
			'bucket' => '',
			'region' => '',
		);
		/* Image Sizes */
		public $imageSizes = array(
        'large'  => array(1024, 768),
		'thumb'  => array(240, 240),
		);
		/* Two Factor Authentication */
		public $twoFactorAuth = array(
			'enabled' => false,
			'send' => array(
				'email' => true,
				'sms' => true,
				'whatsapp' => true,
			),
		);
		/* SMS */
		public $sms = array(
			'enabled' => false,
			'url' => 'https://www.fast2sms.com/',
			'authorization' => 'Gsyrl3nBx1LYA6ZECRowJqaOH0Id2NK57D8iQT9guhFSMepmUWZnDqKpt3cTB24FgCl9ydRJO18GrjUi',
			'header' => 'SHALIT',
			'username' => '',
			'password' => '',
			'sender' => ''
		);

		/* Whatsapp */
		public $whatsapp = array(
			'enabled' => false,
			'url' => 'https://api.whatsapp.com/send?phone=',
			'token' => '',
			'sender' => '',
			'message' => '',
		);

		/* Email */
		public $email = array(
			'enabled' => false,
			'fromEmail' => '',
			'fromName' => '',
			'SMTPHost' => '',
			'SMTPUser' => '',
			'SMTPPass' => '',
			'SMTPPort' => 25
		);

		public function __construct()
		{
			$this->appEmails = array(
            'admin' => 'admin@example.com'
			);
		}
	}
