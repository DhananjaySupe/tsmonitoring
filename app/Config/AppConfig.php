<?php namespace Config;

	use CodeIgniter\Config\BaseConfig;

	class AppConfig extends BaseConfig
	{
		public $appName = 'Tentage and Sanitation';
		public $appDesc = "Tentage and Sanitation";
		public $appEmails = array();
		public $defaultCSS = array();
		public $defaultJS = array();
		public $defaultMETA = array();
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
		public $jwt_expiry = 36000; // in sec - 10 hours

		/* Session */
		public $single_login = true; // true or false
		public $userCodePrefix = 'KSH';
		public $maxOtpAttempts = 5; // maximum OTP attempts
		public $otpExpiry = 10; // OTP expiry in minutes

		/* User Type Permissions */
		public $checkUserTypePermissions = true; // true or false

		public $imageSizes = array(
        'large' => array(800, 600),
        'thumb' => array(340, 255),
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

			$this->defaultCSS = array(
			);

			$this->defaultJS = array(
			);

			$this->defaultMETA = array(
			);
		}
	}
