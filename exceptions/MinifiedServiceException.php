<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedServiceException
 */

namespace edwardstock\minified\exceptions;


use edwardstock\minified\Minified;

class MinifiedServiceException extends MinifiedException {

	public function __construct($message, $code = 0, \Exception $_previous = null) {
		parent::__construct($message.' ERROR::'.$this->getMessageByCode($code), $code, $_previous);
	}

	protected function getMessageByCode($code) {
		foreach((new \ReflectionClass('edwardstock\minified\Minified'))->getConstants() AS $name=>$value) {
			if(preg_match('/(API_)(.*)/s', $name, $matches) && $value === $code) {
				return $matches[2];
			}
		}

		return 'NO_ERROR';
	}
} 