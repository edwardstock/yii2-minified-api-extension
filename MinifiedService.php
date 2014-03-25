<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedService
 */

namespace edwardstock\minified;


use edwardstock\curl\Curl;
use edwardstock\minified\helpers\JsonHelper;
use yii\web\HttpException;

final class MinifiedService {

	const API_NO_ERRORS                         = 0x0;
	const API_ERROR_USER_NOT_FOUND              = 0x1;
	const API_ERROR_USER_TOKEN_DOES_NOT_MATCH   = 0x2;
	const API_WARNING_USER_DATA_NOT_FOUND       = 0x3;
	const API_ERROR_SOURCE_NOT_FOUND            = 0x4;
	const API_ERROR_SOURCE_UNKNOWN_ERROR        = 0x5;

	private $_username;
	private $_token;
	private $_authorized = false;
	private $_queue = [];
	private $_curl;
	/**
	 * @var array API config
	 */
	private $_curlConfig = [
		'apiUrl' => 'http://api.minified.pw',
		'auth' => 'http://api.minified.pw/user/auth',
		'createItem' => 'http://api.minified.pw/source/add', //verb POST
		'updateItem' => 'http://api.minified.pw/source/update', //verb PUT
		'deleteItem' => 'http://api.minified.pw/source/delete', //verb DELETE
		'getItem' => 'http://api.minified.pw/source/get-static', //verb GET
		'userAgent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36',
	];

	public function __construct(Minified $minified) {
		$this->_username = $minified->username;
		$this->_token = $minified->token;
		$this->_curl = new Curl();
	}

	public function add(array $data) {
		$this->_queue[] = $data;
	}

	/**
	 * Authenticates user
	 */
	public function authenticate() {
		$this->_curl->setUserAgent($this->_curlConfig['userAgent']);
		$this->_curl->success(array($this,'onAuthSuccess'));
		$this->_curl->error(array($this,'onAuthError'));

		$this->_curl->post($this->_curlConfig['auth'], [
			'username'=>$this->_username,
			'token'=>$this->_token
		]);
	}

	/**
	 * @return bool
	 */
	public function getIsAuthenticated() {
		return $this->_authorized;
	}

	public function __destruct() {
		$this->_curl->close();
	}
} 