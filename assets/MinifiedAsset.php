<?php
/**
 * minified. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Class: MinifiedAsset
 */

namespace edwardstock\minified\assets;


use edwardstock\minified\MinifiedClient;
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