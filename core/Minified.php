<?php
namespace EdwardStock\Minified\Core;

use yii\base\Object;

/**
 * yii2-minified-api-extension. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: Minified
 */
abstract class Minified extends Object {

	const ERROR_CODE_BAD_REQUEST = 400;
	const ERROR_CODE_PERMISSION_DENIED = 403;
	const ERROR_CODE_AUTH_REQUIRED = 401;
	const ERROR_CODE_NOT_FOUND = 404;
	const ERROR_CODE_INTERNAL_ERROR = 500;
	const ERROR_CODE_SERVICE_UNAVAILABLE = 503;

}
