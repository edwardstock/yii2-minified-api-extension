<?php
namespace EdwardStock\Minified;

define('DS', DIRECTORY_SEPARATOR) or defined('DS');

use EdwardStock\Minified\Core\MinifiedService;
use EdwardStock\Minified\Exceptions\MinifiedException;
use EdwardStock\Minified\Helpers;
use EdwardStock\Minified\Helpers\FileHelper;
use yii\base\Exception;
use yii\web\View;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 * Class: MinifiedClient
 * RESTfull API based extension for service MINIFIED.pw
 */
class MinifiedClient extends Minified {
	// compilation levels
	const COMPILATION_LEVEL_WHITESPACE_ONLY = 'WHITESPACE_ONLY';
	const COMPILATION_LEVEL_SIMPLE_OPTIMIZATION = 'SIMPLE_OPTIMIZATIONS';
	const COMPILATION_LEVEL_ADVANCED_OPTIMIZATION = 'ADVANCED_OPTIMIZATIONS';

	//javascript specifications
	const SPEC_DEFAULT_ECMASCRIPT3 = 'ECMASCRIPT3';
	const SPEC_ECMASCRIPT5 = 'ECMASCRIPT5';
	const SPEC_ECMASCRIPT5_STRICT = 'ECMASCRIPT5_STRICT';
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
	private $contents = [];
	/**
	 * Here contains data like in array above,
	 * but this array will be sent for getting compressed data,
	 * because files in this array are was changed
	 * @var array
	 */
	private $contentsModified = [];
	/**
	 * This array will be send
	 * @var array
	 */
	private $toSend = [];
	/**
	 * @var string By default is in extension's "resources/storage" directory
	 */
	private $storagePath;
	/**
	 * @var string By default is a "resources/hashes"
	 */
	private $hashFilesPath;
	/**
	 * @var int Count of hash files
	 */
	private $hashFilesCount = 0;
	/**
	 * @var int Count of compressed files
	 */
	private $storageFilesCount = 0;
	/**
	 * @var array List of hash files
	 */
	private $hashFilesList = [];
	/**
	 * @var MinifiedService
	 */
	private $service;
	/**
	 * @var Bootstrap
	 */
	private $bootstrap;

	public function __construct(Bootstrap $bootstrap) {
		$this->storagePath = __DIR__ . '/resources/storage';
		$this->hashFilesPath = __DIR__ . '/resources/hashes';
		$this->hashFilesCount = FileHelper::countFilesInPath($this->hashFilesPath);
		$this->storageFilesCount = FileHelper::countFilesInPath($this->storagePath);

		$this->bootstrap = $bootstrap;
		$this->service = new MinifiedService($bootstrap);
	}

	/**
	 * Preparing data to send or not
	 * @param View $context
	 * @return MinifiedClient
	 */
	public function prepare(View $context) {

		$this->prepareFiles();

		if (!$this->obtainFilesContent()) {
			return $this;
		}

		if ($this->hashPathIsEmpty() || $this->hashFilesCount !== count($this->contents)) {
			foreach ($this->contents AS $file) {
				$this->writeHashFile($file);
			}
		}

		if ($this->storagePathIsEmpty()) {
			$this->registerRequestData($this->contents);
		}
		elseif (!$this->hashesEqualsOriginal()) {
			$this->registerRequestData($this->contentsModified);
		}

		try {
			$this->service->authenticate();
		} catch (Exception $e) {
			echo $e->getMessage();

			return $this;
		}

		return $this;
	}

	/**
	 * Scanning existed files and put into array @see contents
	 */
	private function prepareFiles() {
		\Yii::trace("Preparing source JavaScript files", __METHOD__);
		foreach ($this->bootstrap->sourceJsPaths AS $path) {
			$this->obtainSourceInfo($path, $this->bootstrap->recursiveJsScan, ['js']);
		}

		\Yii::trace("Preparing source CSS styles", __METHOD__);
		foreach ($this->bootstrap->sourceCssPaths AS $path) {
			$this->obtainSourceInfo($path, $this->bootstrap->recursiveCssScan, ['css']);
		}
	}

	/**
	 * @return bool
	 */
	private function obtainFilesContent() {
		\Yii::trace("Getting source files content intro array", __METHOD__);
		if (empty($this->contents)) {
			return false;
		}

		foreach ($this->contents AS &$item) {
			$item['content'] = file_get_contents($item['pathname']);
		}

		return true;
	}

	/**
	 * @return bool
	 */
	private function hashPathIsEmpty() {
		return $this->hashFilesCount === 0 ? true : false;
	}

	/**
	 * Writes empty file with hashed name by: filename+timestamp wrapped by md5()
	 * @param array $file
	 */
	private function writeHashFile(array $file) {
		\Yii::trace("Writing empty hash file " . $file['pathname'], __METHOD__);
		$targetPath = $this->hashFilesPath . DS . md5($file['filename'] . $file['timestamp']);

		if (file_exists($targetPath) && is_file($targetPath)) {
			return;
		}

		//protector from double directory scanning
		$this->hashFilesList[] = md5($file['filename'] . $file['timestamp']);
		$handle = fopen($targetPath, 'w');
		fwrite($handle, "\x0");
		fclose($handle);

		unset($targetPath);
	}

	/**
	 * @return bool
	 */
	private function storagePathIsEmpty() {
		return $this->storageFilesCount === 0 ? true : false;
	}

	/**
	 * @param array $files
	 */
	private function registerRequestData($files) {
		$this->toSend = $files;
	}

	/**
	 * If hashes are not equal with filename and "edit stamp" of original files,
	 * we know about fact what files are changed.
	 * Then we will send request to the service and get compressed data
	 */
	private function hashesEqualsOriginal() {
		$count = 0;
		foreach ($this->contents AS $file) {
			if (md5($file['filename'] . $file['timestamp']) === $this->hashFilesList[$count]) {
				continue;
			}

			$this->contentsModified[] = $file;

			$count++;
		}

		$equals = $count === 0 ? true : false;
		$info = $equals ? "true" : "false";
		\Yii::trace("Are hashes equals originals? $info", __METHOD__);

		return $equals;
	}

	/**
	 * Gets information about sources
	 * @see Minified::$_contents
	 * @param       $path
	 * @param bool  $recursive
	 * @param array $acceptableExtensions
	 * @throws exceptions\MinifiedException
	 */
	private function obtainSourceInfo($path, $recursive = true, $acceptableExtensions = ['js', 'css']) {
		\Yii::trace("Getting sources information", __METHOD__);

		$objects = helpers\FileHelper::scanDirectory($path, $recursive);

		foreach ($objects as $object) {
			if ($object->isDir() || !in_array(strtolower($object->getExtension()), $acceptableExtensions)) {
				continue;
			}

			if (!$object->isReadable()) {
				throw new MinifiedException("File by path {$object->getPathname()} is not readable. Please check rights.");
			}

			$this->contents[] = [
				'filename'  => $object->getFilename(),
				'pathname'  => $object->getPathname(),
				'timestamp' => $object->getMTime(),
				'size'      => $object->getSize(),
				'type'      => $object->getExtension()
			];
		}
	}

	/**
	 * Do something
	 */
	public function rock() {
		$this->service->add($this->toSend);
	}

	/**
	 * @return array
	 */
	public function getDepends() {
		return $this->bootstrap->assetsDepends;
	}

	/**
	 * Getting hash files list
	 * @return void
	 */
	private function obtainHashFilesList() {
		if ($this->hashFilesCount === count($this->contents) AND !empty($this->hashFilesList)) {
			return;
		}

		foreach (FileHelper::scanDirectory($this->hashFilesPath, false) AS $object) {
			$this->hashFilesList[] = $object->getFilename();
		}
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	private function assetsCountIsEquals() {
		$equals = (count($this->contents) === $this->hashFilesCount) === $this->storageFilesCount;
		\Yii::info("Hash count: {$this->hashFilesCount}; Storage count: {$this->storageFilesCount}; Contents count: " . count($this->contents),
			__METHOD__);
		$info = $equals ? "true" : "false";
		\Yii::info("Are they equals? $info", __METHOD__);

		return $equals;
	}


}