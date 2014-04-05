<?php
namespace EdwardStock\Minified;

use EdwardStock\Minified\Exceptions\MinifiedException;
use yii\base\Component;

/**
 * yii2-minified-api-extension. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: Bootstrap
 */
class Bootstrap extends Component {

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
	public $jsCompilationLevel = MinifiedClient::COMPILATION_LEVEL_SIMPLE_OPTIMIZATION;
	/**
	 * JavaScript specification version.
	 * By default recommended ECMASCRIPT3
	 * @var string
	 */
	public $jsSpecification = MinifiedClient::SPEC_DEFAULT_ECMASCRIPT3;

	/**
	 * @var MinifiedClient
	 */
	private $client;


	public function init() {
		$this->client = new MinifiedClient($this);
	}

	public function __call($name, Array $params) {
		if (!method_exists($this->client, $name)) {
			throw new MinifiedException("Method $name not found in MinifiedClient class object");
		}

		return call_user_func_array(array($this->client, $name), $params);
	}

	public static function __callStatic($name, Array $params) {
		if (!method_exists(MinifiedClient::className(), $name)) {
			throw new MinifiedException("Static method $name not found in MinifiedClient class");
		}

		return call_user_func_array(MinifiedClient::className(), $params);
	}


} 