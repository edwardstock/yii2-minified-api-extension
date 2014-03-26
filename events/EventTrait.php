<?php
/**
 * yii2-minified-api-extension. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: EventTrait
 *
 */

namespace edwardstock\minified\events;


use edwardstock\curl\Curl;
use edwardstock\minified\exceptions\MinifiedServiceException;
use edwardstock\minified\helpers\JsonHelper;
use edwardstock\minified\MinifiedService;

/**
 * Class EventTrait
 * @package edwardstock\minified\events
 * @property array $_curlConfig
 * @property bool $_authenticated
 */
trait EventTrait {

	private $_authenticated = false;

	public function getIsAuthenticated() {
		return $this->_authenticated;
	}

	public function onAuthSuccess(Curl $curl) {
		$response = JsonHelper::handleJsonResponse($curl);

		if($response->error !== MinifiedService::API_NO_ERRORS) {
			\Yii::error("Authorization failed. Service response - error code: {$response->error}", __METHOD__);
			throw new MinifiedServiceException('Unable to authorize on service', $response->error);
		}
		\Yii::trace('Authorization successful ', __METHOD__);
		$this->_authenticated = true;
	}

	public function onAuthError(Curl $curl) {
		\Yii::error("Authorization failed. Error code: {$curl->errorCode}. Message: {$curl->errorMessage}", __METHOD__);
		throw new \HttpException($curl->errorCode, $this->_curlConfig['auth'].' '.$curl->errorMessage);
	}

	public function onPutDataError(Curl $curl) {

	}

	public function onPutDataSuccess(Curl $curl) {

	}

	public function onGetDataError(Curl $curl) {

	}

	public function onGetDataSuccess(Curl $curl) {

	}

	public function onDeleteDataError(Curl $curl) {

	}

	public function onDeleteDataSuccess(Curl $curl) {

	}
}