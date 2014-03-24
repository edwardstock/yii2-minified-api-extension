<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedException
 */

namespace edwardstock\minified\Exceptions;

use yii\base\Exception;

class MinifiedException extends Exception {

	public function __construct($message, $code = 0, \Exception $_previous = null) {
		parent::__construct($message, $code, $_previous);
	}
} 