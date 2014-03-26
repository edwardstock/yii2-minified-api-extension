<?php
namespace EdwardStock\Minified\Exceptions;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Exception class: MinifiedServiceException
 */
class MinifiedServiceException extends MinifiedException {

	public function __construct($message, $code = 0, \Exception $_previous = null) {

		parent::__construct($message, $code, $_previous, $this->getInfoByCode($code));
	}

	protected function getInfoByCode($code) {
		foreach((new \ReflectionClass('edwardstock\minified\Minified'))->getConstants() AS $name=>$value) {
			if(preg_match('/(API_)(.*)/s', $name, $matches) && $value === $code) {
				return $matches[2];
			}
		}

		return 'UNCAUGHT_ERROR';
	}
} 