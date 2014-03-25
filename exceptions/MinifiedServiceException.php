<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedServiceException
 */

namespace edwardstock\minified\Exceptions;


class MinifiedServiceException extends MinifiedException {

	public function __construct($message, $code = 0, \Exception $_previous = null) {
		parent::__construct($message, $code, $_previous);
	}
} 