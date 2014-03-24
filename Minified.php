<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: Minified
 *
 * RESTfull API based extension for service MINIFIED.pw
 */

namespace edwardstock\minified;

use yii\base\Component;

class Minified extends Component {

	// compilation levels
	const COMPILATION_LEVEL_WHITESPACE_ONLY = 'WHITESPACE_ONLY';
	const COMPILATION_LEVEL_SIMPLE_OPTIMIZATION = 'SIMPLE_OPTIMIZATIONS';
	const COMPILATION_LEVEL_ADVANCED_OPTIMIZATION = 'ADVANCED_OPTIMIZATIONS';

	//javascript specifications
	const SPEC_DEFAULT_ECMASCRIPT3 = 'ECMASCRIPT3';
	const SPEC_ECMASCRIPT5 = 'ECMASCRIPT5';
	const SPEC_ECMASCRIPT5_STRICT = 'ECMASCRIPT5_STRICT';

	public $username;
	public $token;
	public $password;
	public $sourceJsPaths = array();
	public $sourceCssPaths = array();
	public $yiiDebug = false;
	public $params = array(
		'jsCompilationLevel'=>self::COMPILATION_LEVEL_SIMPLE_OPTIMIZATION,
		'jsSpecification'=>self::SPEC_DEFAULT_ECMASCRIPT3,
		'combineCss'=>false,
	);

	private $_css;
	private $_js;
	private $_assetsPath;
	private $_toSend = array();
	private $_curlConfig = array(
		'apiUrl'=>'http://api.minified.pw',
		'createItem'=>'http://api.minified.pw/source/add',     //verb POST
		'updateItem'=>'http://api.minified.pw/source/update',  //verb POST
		'deleteItem'=>'http://api.minified.pw/source/delete',  //verb DELETE
		'getItem'=>'http://api.minified.pw/source/get-static', //verb GET
		'userAgent'=>'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36',
	);

	public function init() {
		$this->_assetsPath = __DIR__ . '/assets';

		parent::init();
	}

	/**
	 * Starts this mega-machine
	 */
	public function getRock() {
		$this->prepareFiles();
	}

	private function prepareFiles() {
//		foreach($this->sourceJsPaths AS $path) {
//			ES::dump(scandir($path));
//		}
//
//		foreach($this->sourceCssPaths AS $path) {
//			ES::dump(scandir($path));
//		}
	}

	private function prepareUserToken() {
		return md5($this->username).$this->token;
	}





} 