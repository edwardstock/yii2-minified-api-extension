<?php
/**
 * yii2-minified-api-extension. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: ServiceHandlerTrait
 *
 * @package edwardstock\minified\events
 */

namespace edwardstock\minified\events;


use edwardstock\curl\Curl;
use edwardstock\minified\exceptions\MinifiedServiceException;
use edwardstock\minified\helpers\JsonHelper;
use edwardstock\minified\MinifiedService;

trait ServiceHandlerTrait {

	private $authenticated = false;
	private $response = [];

	public function getIsAuthenticated() {
		return $this->authenticated;
	}

	public function onAuthSuccess(Curl $curl) {
		$response = JsonHelper::handleJsonResponse($curl);

		if($response->error !== MinifiedService::API_NO_ERRORS) {
			\Yii::error("Authorization failed. Service response - error code: {$response->error}", __METHOD__);
			throw new MinifiedServiceException('Unable to authorize on service', $response->error);
		}
		\Yii::trace('Authorization successful ', __METHOD__);
		$this->authenticated = true;
	}

	public function onAuthError(Curl $curl, array $data, MinifiedService $context) {
		\Yii::error("Authorization failed. Error code: {$curl->errorCode}. Message: {$curl->errorMessage}", __METHOD__);
		throw new \HttpException($curl->errorCode, $context->getCurlConfig()['auth'].' '.$curl->errorMessage);
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