<?php
	namespace App\Controllers;
	/**
		* Class BaseController
		*
		* BaseController provides a convenient place for loading components
		* and performing functions that are needed by all your controllers.
		* Extend this class in any new controllers:
		*     class Home extends BaseController
		*
		* For security be sure to declare any new methods as protected or private.
		*
		* @package CodeIgniter
	*/
	use CodeIgniter\Controller;
	use CodeIgniter\API\ResponseTrait;
	use App\Models\SessionsModel;
	use App\Models\UsersModel;
	use App\Models\UserTypePermissionsModel;

	use App\Libraries\JwtLib;

	class BaseController extends Controller
	{
		use ResponseTrait;
		/**
			* An array of helpers to be loaded automatically upon
			* class instantiation. These helpers will be available
			* to all other controllers that extend BaseController.
			*
			* @var array
		*/
		protected $helpers = ['app','text','imagick'];
		protected $_member = array();
		protected $_session = array();
		protected $_output = array('success' => false);
		protected $_status = 200;
		protected $params = array();
		protected $paramSources = array('_GET', '_POST');
		protected $invalidApiKey = "401 Invalid API key";
		protected $methodNotAllowed = "405 Method not allowed";
		protected $noContent = "204 No Content";
		protected $invalidToken = "Invalid access token";
		protected $invalidUser = "Invalid User Type";
		protected $successMessage = "Request Successfully Processed";
		protected $errorMessage = 'Error !!!';
		protected $session;
		protected $AppConfig;
		protected $jsonBody = null;
		/**
			* Constructor.
		*/
		public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
		{
			// Do Not Edit This Line
			parent::initController($request, $response, $logger);
			//--------------------------------------------------------------------
			// Preload any models, libraries, etc, here.
			//--------------------------------------------------------------------
			// E.g.:
			// $this->session = \Config\Services::session();
			$this->AppConfig = new \Config\AppConfig();
		}

		public function AuthenticateApikey()
		{
			if(isset($_SERVER['HTTP_X_API_KEY'])&&!empty($_SERVER['HTTP_X_API_KEY'])) {
				if($this->AppConfig->apiKey===$_SERVER['HTTP_X_API_KEY']){
					return true;
				}
			}
			return false;
		}
		public function AuthenticateToken()
		{
			if(isset($_SERVER['HTTP_X_ACCESS_TOKEN'])&&!empty($_SERVER['HTTP_X_ACCESS_TOKEN'])) {
				$SessionsModel = new SessionsModel();
				$session = $SessionsModel->where('session_token', $_SERVER['HTTP_X_ACCESS_TOKEN'])->first();
				if($session){
					$this->_session = $session;
					$usersModel = new UsersModel();
					$member = $usersModel->find($session['user_id']);
					if ($member) {
						if($member['is_active'] == 1){
							$jwt = new JwtLib();
							$validated = $jwt->validateToken($_SERVER['HTTP_X_ACCESS_TOKEN']);
							if($validated){
								$this->_member = $this->sessionData($member);
								return true;
							} else {
								return false;
							}
						} else {
							return false;
						}
					}
				}
			}

			return false;
		}

		public function HttpApiVersion()
		{
			if(isset($_SERVER['HTTP_X_API_VERSION'])&&!empty($_SERVER['HTTP_X_API_VERSION'])) {
				return $_SERVER['HTTP_X_API_VERSION'];
			}
			return 0;
		}

		public function GetPlatform()
		{
			if(isset($_SERVER['HTTP_X_PLATFORM'])&&!empty($_SERVER['HTTP_X_PLATFORM'])) {
				return $_SERVER['HTTP_X_PLATFORM'];
			}
			return null;
		}


		public function CheckUserTypePermissions($permission)
		{
			if ($this->AppConfig->checkUserTypePermissions) {

				$userTypePermissionsModel = new UserTypePermissionsModel();

				$permission = explode(':', $permission);
				$resource = $permission[0];
				$action = $permission[1];

				// Map logical action names to column suffixes
				$fieldAction = $action === 'read' ? 'view' : $action;

				$userTypePermission = $userTypePermissionsModel
					->select('permission_id')
					->where('user_type_id', $this->_member['user_type_id'])
					->where('permission', $resource)
					->where('can_' . $fieldAction, 1)
					->first();

				if (! $userTypePermission) {
					$this->setError('Access denied. You are not authorized to access this resource.', 403);
					return false;
				}
			}
			return true;
		}

		public function sessionData($member)
		{
			if (! is_array($member) && (int) $member > 0) {
				$usersModel = new UsersModel();
				$member = $usersModel->find($member);
			}
			if (! is_array($member)) {
				return [];
			}
			$data = [
				'id' => $member['user_id'],
				'code' => $member['code'] ?? null,
				'email' => $member['email'] ?? null,
				'phone' => $member['phone'] ?? null,
				'full_name' => $member['full_name'],
				'user_type_id' => $member['user_type_id'],
				'is_active' => $member['is_active'] ?? 1,
			];
			return $data;
		}
		public function setSuccess($message = "")
		{
			$this->_status = 200;
			$this->_output['success'] = true;
			$this->_output['message'] = $message;
		}
		public function setError($message = "", $status = 200)
		{
			$this->_status = $status;
			if ($this->_status != 200) {
				$this->_output['message'] = $message;
				} else {
				$this->_output['success'] = false;
				$this->_output['message'] = $message;
				$this->_output['data'] = [];
			}
			if (isset($this->_output['message']) && empty($this->_output['message'])) {
				unset($this->_output['message']);
			}
		}
		public function setOutput($value = "", $key = "")
		{
			if (!empty($key)) {
				$this->_output[$key] = $value;
				} else {
				if($value){
					$this->_output['data'] = $value;
					} else {
					$this->_output['data'] = json_decode("{}");
				}
			}
		}
		public function response($value = null)
		{
			$this->_output['version'] = array(
				'version' => $this->AppConfig->AppCurrentVersion,
				'force_update' => $this->AppConfig->appForceUpdate,
				'app_download_url' => $this->AppConfig->appDownloadUrl,
				'app_download_qr' => $this->AppConfig->appDownloadQr
			);

			return $this->respond((!is_null($value) ? $value : $this->_output), $this->_status);
		}
		public function isPost()
		{
			return ($_SERVER['REQUEST_METHOD'] == 'POST') ? true : false;
		}
		public function isGet()
		{
			return ($_SERVER['REQUEST_METHOD'] == 'GET') ? true : false;
		}
		public function isDelete()
		{
			return ($_SERVER['REQUEST_METHOD'] == 'DELETE') ? true : false;
		}
		public function isOptions()
		{
			return ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') ? true : false;
		}
		public function getPost($key = null, $default = null)
		{
			// Support both form-encoded and JSON bodies
			if ($this->jsonBody === null) {
				$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
				if (stripos($contentType, 'application/json') !== false) {
					$raw        = file_get_contents('php://input');
					$decoded    = json_decode($raw, true);
					$this->jsonBody = is_array($decoded) ? $decoded : [];
				} else {
					$this->jsonBody = [];
				}
			}

			if ($key === null) {
				return array_merge($_POST, $this->jsonBody);
			}

			if (isset($_POST[$key])) {
				return $_POST[$key];
			}

			if (isset($this->jsonBody[$key])) {
				return $this->jsonBody[$key];
			}

			return $default;
		}
		public function getCookie($key = null, $default = null)
		{
			if (null === $key) {
				return $_COOKIE;
			}
			return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
		}
		public function getParam($key, $default = null)
		{
			$paramSources = $this->getParamSources();
			if (isset($this->params[$key])) {
				return $this->params[$key];
				} elseif (in_array('_GET', $paramSources) && (isset($_GET[$key]))) {
				return $_GET[$key];
				} elseif (in_array('_POST', $paramSources) && (isset($_POST[$key]))) {
				return $_POST[$key];
			}
			return $default;
		}
		public function getParams()
		{
			$return = $this->params;
			$paramSources = $this->getParamSources();
			if (in_array('_GET', $paramSources) && isset($_GET) && is_array($_GET)) {
				$return += $_GET;
			}
			if (in_array('_POST', $paramSources) && isset($_POST) && is_array($_POST)) {
				$return += $_POST;
			}
			return $return;
		}
		private function getParamSources()
		{
			return $this->paramSources;
		}
	}