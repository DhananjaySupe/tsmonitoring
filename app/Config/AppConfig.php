<?php namespace Config;

	use CodeIgniter\Config\BaseConfig;

	class AppConfig extends BaseConfig
	{
		public $appName = 'Tentage and Sanitation';
		public $appDesc = "Tentage and Sanitation";
		public $appEmails = array();
		public $cssVersion = '1.0.0';
		public $jsVersion = '1.0.0';
		/** */
		public $apiKey = 'fx4ni3n75wtxywa9wlu70fycp2e0ajxkh7o6adjshiifmvaukq57jyrs15e3d55u';
		public $AppCurrentVersion = '1.0.0';
		public $appForceUpdate = '1';
		public $appDownloadUrl = 'https://play.google.com/store/apps/details?id=com.kashit.tentagesanitation';
		public $appDownloadQr = 'assets/images/appqrcode/appurl.webp';
		/* JWT */
		public $jwt_secret = 'VNyLbLP7aGg9YKZXlshZqkRFahRLgf1L';
		/* JWT Expiry in seconds */
		public $jwt_expiry = 36000;
		/* Single Login */
		public $single_login = true;
		/* User Code Prefix */
		public $userCodePrefix = 'KSH';
		/* Maximum OTP Attempts */
		public $maxOtpAttempts = 5;
		/* OTP Expiry in minutes*/
		public $otpExpiry = 10;
		/* Check User Type Permissions */
		public $checkUserTypePermissions = true;
		/* AWS S3 */
		public $S3 = array(
			'enabled' => false,
			'key' => '',
			'secret' => '',
			'bucket' => '',
			'region' => '',
		);

		public $imageSizes = array(
        'large'  => array(1024, 768),
		'thumb'  => array(240, 240),
		);

		public $twoFactorAuth = array(
			'enabled' => false,
			'send' => array(
				'email' => true,
				'sms' => true,
				'whatsapp' => true,
			),
		);

		// Maintenance mode for APIs (handled by MaintenanceMode filter)
		public $maintenanceMode = false;
		public $maintenanceMessage = 'Service is under maintenance. Please try again later.';

		public function __construct()
		{
			$this->appEmails = array(
			'enabled' => true,
            'admin' => 'admin@example.com'
			);
		}
	}
