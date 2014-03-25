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

define('DS',DIRECTORY_SEPARATOR) or defined('DS');

use common\helpers\ES;
use edwardstock\curl\Curl;
use edwardstock\minified\exceptions\MinifiedException;
use edwardstock\minified\exceptions\MinifiedServiceException;
use yii\base\Component;
use yii\base\Exception;
use yii\web\HttpException;
use yii\web\View;

class Minified extends Component {

	const API_NO_ERRORS                         = 0x0;
	const API_ERROR_USER_NOT_FOUND              = 0x1;
	const API_ERROR_USER_TOKEN_DOES_NOT_MATCH   = 0x2;
	const API_WARNING_USER_DATA_NOT_FOUND       = 0x3;
	const API_ERROR_SOURCE_NOT_FOUND            = 0x4;
	const API_ERROR_SOURCE_UNKNOWN_ERROR        = 0x5;

	const ERROR_CODE_BAD_REQUEST = 400;
	const ERROR_CODE_PERMISSION_DENIED = 403;
	const ERROR_CODE_AUTH_REQUIRED = 401;
	const ERROR_CODE_NOT_FOUND = 404;
	const ERROR_CODE_INTERNAL_ERROR = 500;
	const ERROR_CODE_SERVICE_UNAVAILABLE = 503;

	// compilation levels
	const COMPILATION_LEVEL_WHITESPACE_ONLY = 'WHITESPACE_ONLY';
	const COMPILATION_LEVEL_SIMPLE_OPTIMIZATION = 'SIMPLE_OPTIMIZATIONS';
	const COMPILATION_LEVEL_ADVANCED_OPTIMIZATION = 'ADVANCED_OPTIMIZATIONS';

	//javascript specifications
	const SPEC_DEFAULT_ECMASCRIPT3 = 'ECMASCRIPT3';
	const SPEC_ECMASCRIPT5 = 'ECMASCRIPT5';
	const SPEC_ECMASCRIPT5_STRICT = 'ECMASCRIPT5_STRICT';

	/**
	 * @var string
	 */
	public $username;

	/**
	 * Personal user token given on site
	 * @var string
	 */
	public $token;

	/**
	 * JS paths
	 * @var array
	 */
	public $sourceJsPaths = [];

	/**
	 * CSS paths
	 * @var array
	 */
	public $sourceCssPaths = [];

	/**
	 * AssetBundle depends. Meaning how files will be ordered
	 * @see yii\web\AssetBundle::$depends
	 * @var array
	 */
	public $assetsDepends = [];

	/**
	 * @var bool If enabled, files will not compressed and published originals
	 */
	public $yiiDebug = false;

	/**
	 * @var bool
	 */
	public $recursiveJsScan = true;

	/**
	 * @var bool
	 */
	public $recursiveCssScan = true;

	/**
	 * @var bool Combining css files into one file
	 */
	public $combineCss = false;

	/**
	 * @var bool Combining js files into one file
	 */
	public $combineJs = false;

	/**
	 * @var string Compilation level
	 * @see https://developers.google.com/closure/compiler/docs/overview?hl=ru
	 */
	public $jsCompilationLevel = self::COMPILATION_LEVEL_SIMPLE_OPTIMIZATION;

	/**
	 * JavaScript specification version.
	 * By default recommended ECMASCRIPT3
	 * @var string
	 */
	public $jsSpecification = self::SPEC_DEFAULT_ECMASCRIPT3;

	/**
	 * @var array
	 * [
	 *      'pathname',  full file path
	 *      'timestamp', timestamp of edit time
	 *      'size'       size in bytes
	 *      'type'       file extension
	 * ]
	 */
	private $_contents = array();

	/**
	 * @var string By default is in extension's "assets" directory
	 */
	private $_assetsPath;

	/**
	 * @var array API config
	 */
	private $_curlConfig = array(
		'apiUrl'=>'http://api.minified.pw',
		'auth'=>'http://api.minified.pw/user/auth',
		'createItem'=>'http://api.minified.pw/source/add',     //verb POST
		'updateItem'=>'http://api.minified.pw/source/update',  //verb PUT
		'deleteItem'=>'http://api.minified.pw/source/delete',  //verb DELETE
		'getItem'=>'http://api.minified.pw/source/get-static', //verb GET
		'userAgent'=>'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.102 Safari/537.36',
	);

	private $_errors = [];

	/**
	 * @var bool Success or unsuccess authorization
	 */
	private $_authorized = false;

	public function init() {
		$this->_assetsPath = __DIR__ . '/assets';
		parent::init();
	}

	/**
	 * Starts this mega-machine
	 */
	public function getRock(View $context) {

//		if(YII_DEBUG and $this->yiiDebug)
//			return;

//		$this->prepareFiles();
//		if(!$this->getFilesContent())
//			return;

		try{
			$this->auth();
		}catch (Exception $e) {
			echo $e->getMessage();exit;
		}




	}

	/**
	 * Scanning existed files and put into array @see $_contents
	 */
	private function prepareFiles() {

		foreach($this->sourceJsPaths AS $path) {
			$this->scanDirectory($path, $this->recursiveJsScan);
		}

		foreach($this->sourceCssPaths AS $path) {
			$this->scanDirectory($path, $this->recursiveCssScan);
		}
	}

	/**
	 * @param string $path
	 * @param bool $recursive
	 * @param array $acceptableExtensions JS | CSS
	 * @throws Exceptions\MinifiedException
	 * @return void
	 */
	private function scanDirectory($path, $recursive = true, $acceptableExtensions = ['js','css']) {
		$flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;

		/** @var \SplFileInfo[] $objects */

		if($recursive) {
			$objects = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, $flags), \RecursiveIteratorIterator::SELF_FIRST
			);
		} else {
			$objects = new \DirectoryIterator($path, $flags);
		}

		foreach($objects as $object) {
			if($object->isDir() || !in_array(strtolower($object->getExtension()), $acceptableExtensions)) {
				continue;
			}

			if(!$object->isReadable())
				throw new MinifiedException("File by path {$object->getPathname()} is not readable. Please check rights.");

			$this->_contents[] = [
				'pathname'=>$object->getPathname(),
				'timestamp'=>$object->getMTime(),
				'size'=>$object->getSize(),
				'type'=>$object->getExtension()
			];
		}
	}

	private function getFilesContent() {
		if(empty($this->_contents))
			return false;

		foreach($this->_contents AS &$item) {
			$item['content'] = file_get_contents($item['pathname']);
		}

		return true;
	}


	private function prepareUserToken() {
		return md5($this->username).$this->token;
	}

	/**
	 * @return array
	 */
	public function getDepends() {
		return $this->assetsDepends;
	}

	private function auth() {
		$curl = new Curl();
		$curl->setUserAgent($this->_curlConfig['userAgent']);
		$curl->success(array($this,'onAuthSuccess'));
		$curl->error(array($this,'onAuthError'));

		$curl->post($this->_curlConfig['auth'], [
			'username'=>$this->username,
			'token'=>$this->token
		]);

		$curl->close();

	}

	public function onAuthSuccess(Curl $curl) {
		$response = $this->handleJsonResponse($curl);
		$this->_errors['auth'] = $response->error;

		if($response->error !== self::API_NO_ERRORS) {
			throw new MinifiedServiceException('Unable to authorize on service', $response->error);
		}
	}

	public function onAuthError(Curl $curl) {
		throw new HttpException($curl->errorCode, $this->_curlConfig['auth'].' '.$curl->errorMessage);
	}

	/**
	 * @param Curl $curl
	 * @return \stdClass|array
	 */
	private function handleJsonResponse(Curl $curl) {
		return json_decode($curl->response);
	}


}