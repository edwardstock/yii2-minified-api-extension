<?php
namespace EdwardStock\Minified\Core;

define('DS', DIRECTORY_SEPARATOR) or defined('DS');

use EdwardStock\Minified\Bootstrap;
use EdwardStock\Minified\Exceptions\MinifiedException;
use EdwardStock\Minified\Helpers;
use EdwardStock\Minified\Helpers\FileHelper;
use EdwardStock\Minified\StorageAssets\MinifiedAsset;
use yii\base\Exception;
use yii\web\View;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 * Class: MinifiedClient
 * RESTfull API based extension for service MINIFIED.pw
 */
class MinifiedClient extends Minified {

	const LOG_CATEGORY = 'EdwardStock\Minified\Core\MinifiedClient';

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

	/**
	 * @var bool If files does not modified, we just set flag to TRUE and will not request data from service
	 */
	private $justPublish = false;

	/**
	 * @var array Final array to store compressed files from "storage" directory
	 */
	private $compressed = [];

	private function prepareStorageData() {
		$files = FileHelper::scanDirectory($this->storagePath, true);

		foreach ($files AS $file) {
			$publishPath = str_replace($this->storagePath . '/', '', $file->getPathname());

			if ($file->getExtension() === 'css') {
				$this->compressed['css'][] = $publishPath;
			} elseif ($file->getExtension() === 'js') {
				$this->compressed['js'][] = $publishPath;
			}
		}
	}

	public function getCompressedStyles() {
		return isset($this->compressed['css']) ? $this->compressed['css'] : [];
	}

	public function getCompressedScripts() {
		return isset($this->compressed['js']) ? $this->compressed['js'] : [];
	}

	public function __construct(Bootstrap $bootstrap) {
		$this->storagePath = realpath(__DIR__ . '/../resources/storage');
		$this->hashFilesPath = realpath(__DIR__ . '/../resources/hashes');
		$this->hashFilesCount = FileHelper::countFilesInPath($this->hashFilesPath);
		$this->storageFilesCount = FileHelper::countFilesInPath($this->storagePath, true);

		$this->bootstrap = $bootstrap;
		$this->service = new MinifiedService($bootstrap);
	}

	/**
	 * Preparing data to send or not
	 * @return MinifiedClient
	 */
	public function prepare() {
		$this->prepareFiles();

		if (!$this->obtainFilesContent()) {
			return $this;
		}


		if ($this->hashPathIsEmpty() || $this->hashFilesCount !== count($this->contents)) {
			foreach ($this->contents AS $file) {
				$this->writeHashFile($file);
			}
		} else {
			$this->obtainHashFilesList();
		}

		if ($this->storagePathIsEmpty()) {
			$this->registerRequestData($this->contents);
		} elseif ($this->hashesEqualsOriginal() === false) {
			$this->registerRequestData($this->contentsModified);
		} else {
			//meaning sources does not modified and we not need to connect
			$this->justPublish = true;

			return $this;
		}


		try {
			$this->service->authenticate();

		} catch(Exception $e) {

			\Yii::trace($e->getMessage() . "\n" . $e->getCode(), __METHOD__);

			return $this;
		}

		return $this;
	}

	/**
	 * @param View $context
	 */
	public function rock(View $context) {
		if (!$this->justPublish) {
			$this->service->add($this->toSend);

			$this->service->putData();
			$result = json_decode($this->service->getResponse());

			/** @var \stdClass[] $result */
			foreach ($result AS $item) {
				$hash = md5($item->filepath);
				$path = $this->storagePath . DS . $hash;
				@mkdir($path, 0775, true);

				file_put_contents($path . DS . $item->filename, $item->target);
			}
		}

		// !IMPORTANT! If method prepareStorageData will be moved somewhere else, assets will not be published
		$this->prepareStorageData();
		MinifiedAsset::register($context);
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
	 * Gets information about sources
	 * @see Minified::$_contents
	 * @param       $path
	 * @param bool  $recursive
	 * @param array $acceptableExtensions
	 * @throws \EdwardStock\Minified\Exceptions\MinifiedException
	 */
	private function obtainSourceInfo($path, $recursive = true, array $acceptableExtensions = ['js', 'css']) {
		\Yii::trace("Getting sources information", __METHOD__);

		$objects = FileHelper::scanDirectory($path, $recursive);

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

	private function removeHashFile($hash) {
		$file = $this->hashFilesPath . DS . $hash;
		if (!file_exists($file))
			return false;

		return unlink($file);
	}

	/**
	 * Writes empty file with hashed name by: filename+timestamp wrapped with md5()
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
		return $this->storageFilesCount === 0;
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

		$originHashes = [];
		$contentWithHash = [];
		$currentHashes = $this->hashFilesList;

		foreach ($this->contents AS $file) {
			$contentWithHash[md5($file['filename'] . $file['timestamp'])] = $file;
			$originHashes[] = md5($file['filename'] . $file['timestamp']);
		}

		$originHashCount = count($originHashes);
		$currentHashCount = count($currentHashes);

		if ($originHashCount !== $currentHashCount) {
			$this->contentsModified = $this->contents;

			return false;
		}

		sort($originHashes);
		sort($currentHashes);

		for ($i = 0; $i < $originHashCount; $i++) {
			if ($originHashes[$i] !== $currentHashes[$i]) {
				$this->removeHashFile($currentHashes[$i]);
				$this->contentsModified[] = $contentWithHash[$originHashes[$i]];
			}
		}

		return count($this->contentsModified) === 0;
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
			if ($object->getFilename() === '.' || $object->getFilename() === '..')
				continue;

			$this->hashFilesList[] = $object->getFilename();
		}

	}
}

