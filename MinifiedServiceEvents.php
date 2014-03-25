<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: Events
 */

namespace edwardstock\minified;


use edwardstock\curl\Curl;

class MinifiedServiceEvents {

	public function onAuthSuccess(Curl $curl) {
		$response = helpers\JsonHelper::handleJsonResponse($curl);

		if($response->error !== MinifiedService::API_NO_ERRORS) {
			\Yii::error("Authorization failed. Service response - error code: {$response->error}", __METHOD__);
			throw new exceptions\MinifiedServiceException('Unable to authorize on service', $response->error);
		}
		\Yii::trace('Authorization successful ', __METHOD__);
		$this->_authorized = true;
	}

	public function onAuthError(Curl $curl) {
		\Yii::error("Authorization failed. Error code: {$curl->errorCode}. Message: {$curl->errorMessage}", __METHOD__);
		throw new HttpException($curl->errorCode, $this->_curlConfig['auth'].' '.$curl->errorMessage);
	}
} 