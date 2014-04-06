<?php
namespace EdwardStock\Minified\Helpers;

use EdwardStock\Minified\Exceptions\MinifiedException;
use yii\base\ErrorException;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: FileHelper
 */
class FileHelper {
	/**
	 * Checks for empty storage dir
	 * @param      $path
	 * @param bool $recursive
	 * @return bool
	 */
	public static function isEmptyPath($path, $recursive = false) {
		\Yii::trace("Checking path $path for empty", __METHOD__);
		$filesCount = 0;
		foreach (FileHelper::scanDirectory($path, $recursive) AS $object) {

			if ($object->isDir() || $object->getExtension() === 'gitignore') {
				continue;
			}

			if ($object->isFile()) {
				$filesCount++;
			}
		}

		return $filesCount > 0 ? false : true;
	}

	/**
	 * @param string $path
	 * @param bool   $recursive
	 * @throws \EdwardStock\Minified\Exceptions\MinifiedException
	 * @return \SplFileInfo[]
	 */
	public static function scanDirectory($path, $recursive = true) {
		\Yii::trace("Scanning directory. Recursive: {$recursive}", __METHOD__);

		if (!is_dir($path)) {
			try {
				mkdir($path, 0775, true);
			} catch(ErrorException $e) {
				throw new MinifiedException("Directory $path is not exists and cannot be created. Check right for your web directory");
			}

		}

		$flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS;

		/** @var \SplFileInfo[] $objects */

		if ($recursive) {
			$objects = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($path, $flags), \RecursiveIteratorIterator::SELF_FIRST
			);
		}
		else {
			$objects = new \DirectoryIterator($path);
		}

		return $objects;
	}

	/**
	 * Counts files in pat NOT recursively and ignoring .gitignore
	 * @param string $path
	 * @return int
	 */
	public static function countFilesInPath($path) {
		$count = 0;
		foreach (FileHelper::scanDirectory($path, false) AS $object) {
			if ($object->isDir() || $object->getExtension() === 'gitignore') {
				continue;
			}

			if ($object->isFile()) {
				$count++;
			}
		}

		return $count;
	}
} 