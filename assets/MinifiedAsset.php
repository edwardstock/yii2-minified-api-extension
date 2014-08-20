<?php
namespace EdwardStock\Minified\StorageAssets;

use EdwardStock\Minified\Bootstrap;
use EdwardStock\Minified\MinifiedClient;
use yii\web\AssetBundle;

/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedAsset
 */
class MinifiedAsset extends AssetBundle
{

	public $sourcePath = '@vendor/edwardstock/minified/resources/storage';

	public function init() {
		$this->setDepends();
		$this->setAssets();

		parent::init();
	}

	private function setDepends() {
		if (isset(\Yii::$app->minified) && \Yii::$app->minified instanceof Bootstrap) {
			$this->depends = \Yii::$app->minified->getDepends();
		}
	}

	private function setAssets() {
		if (isset(\Yii::$app->minified) && \Yii::$app->minified instanceof Bootstrap) {
			$this->css = \Yii::$app->minified->getCompressedStyles();
			$this->js = \Yii::$app->minified->getCompressedScripts();
		}
	}

} 