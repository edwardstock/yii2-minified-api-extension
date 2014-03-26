<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedAsset
 */

namespace EdwardStock\Minified\StorageAssets;


use EdwardStock\Minified\MinifiedClient;
use yii\web\AssetBundle;

class MinifiedAsset extends AssetBundle {

	public $basePath = '@webroot';
	public $baseUrl = '@web';
	public $css = [];
	public $js = [];
	public $depends = [];

	public function init() {
		$this->getDepends();
		parent::init();
	}

	private function getDepends() {
		if(isset(\Yii::$app->minified) && \Yii::$app->minified instanceof MinifiedClient){
			$this->depends = \Yii::$app->minified->getDepends();
		}
	}

} 