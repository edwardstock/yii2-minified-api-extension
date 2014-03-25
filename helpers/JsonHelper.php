<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: JsonHelper
 */

namespace edwardstock\minified\helpers;


use edwardstock\curl\Curl;

class JsonHelper {

	/**
	 * @param Curl $curl
	 * @return \stdClass|array
	 */
	public static function handleJsonResponse(Curl $curl) {
		\Yii::trace("Handling JSON response", __METHOD__);
		return json_decode($curl->response);
	}
} 