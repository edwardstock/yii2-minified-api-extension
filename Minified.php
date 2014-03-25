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

define('DS', DIRECTORY_SEPARATOR) or defined('DS');

use edwardstock\minified\exceptions\MinifiedException;
use edwardstock\minified\helpers\FileHelper;
use yii\base\Component;
use yii\base\Exception;
use yii\web\View;

class Minified extends Component
{

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
	 * ~~~
	 * [
	 *      'filename'   filename without extension
	 *      'pathname',  full file path
	 *      'timestamp', timestamp of edit time
	 *      'size'       size in bytes
	 *      'type'       file extension
	 * ]
	 * ~~~
	 */
	private $_contents = [];
	/**
	 * Here contains data like in array above,
	 * but this array will be sent for getting compressed data,
	 * because files in this array are was changed
	 * @var array
	 */
	private $_contentsModified = [];
	/**
	 * This array will be send
	 * @var array
	 */
	private $_toSend = [];
	/**
	 * @var string By default is in extension's "resources/storage" directory
	 */
	private $_storagePath;
	/**
	 * @var string By default is a "resources/hashes"
	 */
	private $_hashFilesPath;
	private $_hashFilesCount = 0;
	private $_storageFilesCount = 0;
	private $_hashFilesList = [];
	/**
	 * @var MinifiedService
	 */
	private $_service;

	public function init() {
		$this->_storagePath = __DIR__ . '/resources/storage';
		$this->_hashFilesPath = __DIR__ . '/resources/hashes';
		$this->_hashFilesCount = FileHelper::countFilesInPath($this->_hashFilesPath);
		$this->_storageFilesCount = FileHelper::countFilesInPath($this->_storagePath);

		$this->_service = new MinifiedService($this);

		parent::init();
	}

	/**
	 * Preparing data to send or not
	 * @param View $context
	 * @return Minified
	 */
	public function prepare(View $context) {

		$this->prepareFiles();

		if ( !$this->obtainFilesContent() ) {
			return $this;
		}

		if ( $this->hashPathIsEmpty() || $this->_hashFilesCount !== count($this->_contents) ) {
			foreach ( $this->_contents AS $file ) {
				$this->writeHashFile($file);
			}
		}

		if ( $this->storagePathIsEmpty() ) {
			$this->registerRequestData($this->_contents);
		} elseif ( !$this->hashesEqualsOriginal() ) {
			$this->registerRequestData($this->_contentsModified);
		}

		try {
			$this->_service->authenticate();
		} catch (Exception $e) {
			echo $e->getMessage();

			return $this;
		}

		return $this;
	}

	/**
	 * Do something
	 */
	public function rock() {
		$this->_service->add($this->_toSend);
	}

	/**
	 * @return array
	 */
	public function getDepends() {
		return $this->assetsDepends;
	}

	/**
	 * @return bool
	 */
	private function storagePathIsEmpty() {
		return $this->_storageFilesCount === 0 ? true : false;
	}

	/**
	 * @return bool
	 */
	private function hashPathIsEmpty() {
		return $this->_hashFilesCount === 0 ? true : false;
	}

	/**
	 * @param array $files
	 */
	private function registerRequestData($files) {
		$this->_toSend = $files;
	}

	/**
	 * Scanning existed files and put into array @see $_contents
	 */
	private function prepareFiles() {
		\Yii::trace("Preparing source JavaScript files", __METHOD__);
		foreach ( $this->sourceJsPaths AS $path ) {
			$this->obtainSourceInfo($path, $this->recursiveJsScan, ['js']);
		}

		\Yii::trace("Preparing source CSS styles", __METHOD__);
		foreach ( $this->sourceCssPaths AS $path ) {
			$this->obtainSourceInfo($path, $this->recursiveCssScan, ['css']);
		}
	}

	/**
	 * Gets information about sources
	 * @see Minified::$_contents
	 * @param $path
	 * @param bool $recursive
	 * @param array $acceptableExtensions
	 * @throws exceptions\MinifiedException
	 */
	private function obtainSourceInfo($path, $recursive = true, $acceptableExtensions = ['js', 'css']) {
		\Yii::trace("Getting sources information", __METHOD__);

		$objects = helpers\FileHelper::scanDirectory($path, $recursive);

		foreach ( $objects as $object ) {
			if ( $object->isDir() || !in_array(strtolower($object->getExtension()), $acceptableExtensions) ) {
				continue;
			}

			if ( !$object->isReadable() ) {
				throw new MinifiedException("File by path {$object->getPathname()} is not readable. Please check rights.");
			}

			$this->_contents[] = [
				'filename' => $object->getFilename(),
				'pathname' => $object->getPathname(),
				'timestamp' => $object->getMTime(),
				'size' => $object->getSize(),
				'type' => $object->getExtension()
			];
		}
	}

	/**
	 * @return bool
	 */
	private function obtainFilesContent() {
		\Yii::trace("Getting source files content intro array", __METHOD__);
		if ( empty($this->_contents) ) {
			return false;
		}

		foreach ( $this->_contents AS &$item ) {
			$item['content'] = file_get_contents($item['pathname']);
		}

		return true;
	}

	/**
	 * Getting hash files list
	 * @return void
	 */
	private function obtainHashFilesList() {
		if ( $this->_hashFilesCount === count($this->_contents) AND !empty($this->_hashFilesList) ) {
			return;
		}

		foreach ( FileHelper::scanDirectory($this->_hashFilesPath, false) AS $object ) {
			$this->_hashFilesList[] = $object->getFilename();
		}
	}

	/**
	 * Writes empty file with hashed name by: filename+timestamp wrapped by md5()
	 * @param array $file
	 */
	private function writeHashFile(array $file) {
		\Yii::trace("Writing empty hash file " . $file['pathname'], __METHOD__);
		$targetPath = $this->_hashFilesPath . DS . md5($file['filename'] . $file['timestamp']);

		if ( file_exists($targetPath) && is_file($targetPath) ) {
			return;
		}

		//protector from double directory scanning
		$this->_hashFilesList[] = md5($file['filename'] . $file['timestamp']);
		$handle = fopen($targetPath, 'w');
		fwrite($handle, "\x0");
		fclose($handle);

		unset($targetPath);
	}

	/**
	 * If hashes are not equal with filename and "edit stamp" of original files,
	 * we know about fact what files are changed.
	 * Then we will send request to the service and get compressed data
	 */
	private function hashesEqualsOriginal() {
		$count = 0;
		foreach ( $this->_contents AS $file ) {
			if ( md5($file['filename'] . $file['timestamp']) === $this->_hashFilesList[$count] ) {
				continue;
			}

			$this->_contentsModified[] = $file;

			$count++;
		}

		$equals = $count === 0 ? true : false;
		$info = $equals ? "true" : "false";
		\Yii::trace("Are hashes equals originals? $info", __METHOD__);

		return $equals;
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	private function assetsCountIsEquals() {
		$equals = (count($this->_contents) === $this->_hashFilesCount) === $this->_storageFilesCount;
		\Yii::info("Hash count: {$this->_hashFilesCount}; Storage count: {$this->_storageFilesCount}; Contents count: " . count($this->_contents),
			__METHOD__);
		$info = $equals ? "true" : "false";
		\Yii::info("Are they equals? $info", __METHOD__);

		return $equals;
	}


}