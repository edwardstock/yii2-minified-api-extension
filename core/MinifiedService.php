<?php
namespace EdwardStock\Minified\Core;


use EdwardStock\Curl\Curl;
use EdwardStock\Minified\Bootstrap;
use EdwardStock\Minified\Core\ServiceHandlerTrait;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 * Class: MinifiedService
 */
final class MinifiedService extends Minified implements ServiceInterface {

	use ServiceHandlerTrait {
		onAuthError AS protected authErrorEvent;
		onAuthSuccess AS protected authSuccessEvent;
		onDeleteDataError AS protected deleteDataErrorEvent;
		onDeleteDataSuccess AS protected deleteDataSuccessEvent;
		onGetDataError AS protected getDataErrorEvent;
		onGetDataSuccess AS protected getDataSuccessEvent;
		onPutDataError AS protected putDataErrorEvent;
		onPutDataSuccess AS protected putDataSuccessEvent;
	}

	const API_NO_ERRORS = 0x0;
	const API_ERROR_USER_NOT_FOUND = 0x1;
	const API_ERROR_USER_TOKEN_DOES_NOT_MATCH = 0x2;
	const API_WARNING_USER_DATA_NOT_FOUND = 0x3;
	const API_ERROR_SOURCE_NOT_FOUND = 0x4;
	const API_ERROR_SOURCE_UNKNOWN_ERROR = 0x5;
	private $username;
	private $token;
	private $queue = [];
	private $curl;
	/**
	 * @var array API config
	 */
	private $_curlConfig = [
		'apiUrl'     => 'http://api.minified.pw',
		'auth'       => 'http://api.minified.pw/user/auth',
		'putItems'   => 'http://api.minified.pw/source/put',
		//verb POST
		'deleteItem' => 'http://api.minified.pw/source/delete', //verb DELETE
		'getItem'    => 'http://api.minified.pw/source/get-static',
		//verb GET
		'userAgent'  => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36',
	];

	public function __construct(Bootstrap $bootstrap) {
		$this->username = $bootstrap->username;
		$this->token = $bootstrap->token;
		$this->curl = new Curl();
	}

	public function add(array $data) {
		$this->queue[] = $data;
	}

	public function deleteData() {
		// TODO: Implement deleteData() method.
	}

	public function getData() {
		// TODO: Implement getData() method.
	}

	public function putData() {
		$this->curl->onError([$this, 'putDataErrorEvent']);
		$this->curl->onSuccess([$this, 'putDataSuccessEvent']);
		foreach ($this->queue AS $items) {
			$this->curl->post($this->_curlConfig['putItems'], array_merge($items, [
				'username' => $this->username,
				'token' => $this->token,
				'data'  => json_encode($this->queue)
			]));
		}
	}

	/**
	 * Authenticates user
	 */
	public function authenticate() {
		$this->curl->setUserAgent($this->_curlConfig['userAgent']);
		$this->curl->onError(array($this, 'authErrorEvent'));
		$this->curl->onSuccess(array($this, 'authSuccessEvent'));

		$this->curl->post($this->_curlConfig['auth'], [
			'username' => $this->username,
			'token'    => $this->token
		]);
	}

	public function setResponse(ServiceHandlerTrait $event) {
		$this->response = $event->getResponse();
	}

	public function getCurlConfig() {
		return $this->_curlConfig;
	}

	public function __destruct() {
		$this->curl->close();
	}
}