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

	protected $info;

	public function __construct($message, $code = 0, \Exception $_previous = null, $info = null) {
		$this->info = $info;
		parent::__construct($message, $code, $_previous);
	}

	public function getInfo() {
		return $this->info;
	}
} 