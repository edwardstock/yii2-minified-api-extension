<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedService
 */

namespace edwardstock\minified;


use edwardstock\curl\Curl;
use edwardstock\curl\helpers\ArrayHelper;
use edwardstock\minified\core\ServiceInterface;
use edwardstock\minified\events\EventTrait;
use edwardstock\minified\helpers\JsonHelper;
use yii\base\Object;

final class MinifiedService extends Object implements ServiceInterface {

	use EventTrait{
		onAuthError         AS protected authErrorEvent;
		onAuthSuccess       AS protected authSuccessEvent;
		onDeleteDataError   AS protected deleteDataErrorEvent;
		onDeleteDataSuccess AS protected deleteDataSuccessEvent;
		onGetDataError      AS protected getDataErrorEvent;
		onGetDataSuccess    AS protected getDataSuccessEvent;
		onPutDataError      AS protected putDataErrorEvent;
		onPutDataSuccess    AS protected putDataSuccessEvent;
	}

	const API_NO_ERRORS                         = 0x0;
	const API_ERROR_USER_NOT_FOUND              = 0x1;
	const API_ERROR_USER_TOKEN_DOES_NOT_MATCH   = 0x2;
	const API_WARNING_USER_DATA_NOT_FOUND       = 0x3;
	const API_ERROR_SOURCE_NOT_FOUND            = 0x4;
	const API_ERROR_SOURCE_UNKNOWN_ERROR        = 0x5;

	private $_username;
	private $_token;
	private $_queue = [];
	private $_curl;
	private $_response = [];
	/**
	 * @var array API config
	 */
	private $_curlConfig = [
		'apiUrl' => 'http://api.minified.pw',
		'auth' => 'http://api.minified.pw/user/auth',
		'putItems' => 'http://api.minified.pw/source/put', //verb POST
		'deleteItem' => 'http://api.minified.pw/source/delete', //verb DELETE
		'getItem' => 'http://api.minified.pw/source/get-static', //verb GET
		'userAgent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36',
	];

	public function __construct(MinifiedClient $minified) {
		$this->_username = $minified->username;
		$this->_token = $minified->token;
		$this->_curl = new Curl();
	}

	public function add(array $data) {
		$this->_queue[] = $data;
	}

	public function getData() {
		// TODO: Implement getData() method.
	}

	public function putData() {
		$this->_curl->onError([$this, 'putDataErrorEvent']);
		$this->_curl->onSuccess([$this, 'putDataSuccessEvent']);
		foreach($this->_queue AS $items) {
			$this->_curl->post($this->_curlConfig['putItems'], array_merge($items, [
				'username'=>$this->_username,
				'token'=>$this->_token
			]));
		}
	}

	public function deleteData() {
		// TODO: Implement deleteData() method.
	}

	/**
	 * Authenticates user
	 */
	public function authenticate() {
		$this->_curl->setUserAgent($this->_curlConfig['userAgent']);
		$this->_curl->onError(array($this,'authErrorEvent'));
		$this->_curl->onSuccess(array($this,'authSuccessEvent'));

		$this->_curl->post($this->_curlConfig['auth'], [
			'username'=>$this->_username,
			'token'=>$this->_token
		]);
	}

	public function setResponse(EventTrait $event) {

	}

	public function __destruct() {
		$this->_curl->close();
	}
}