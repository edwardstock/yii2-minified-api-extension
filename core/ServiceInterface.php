<?php
namespace EdwardStock\Minified\Core;

/**
 * yii2-minified-api-extension. 2014
 * @author Eduard Maksimovich <edward.vstock@gmail.com>
 *
 * Interface: ServiceInterface
 */
interface ServiceInterface {

	/**
	 * Get JSON data from api
	 * @return mixed
	 */
	public function getData();

	/**
	 * Send JSON data to service api though POST method
	 * @return mixed
	 */
	public function putData();

	/**
	 * Delete data on service api though DELETE method
	 * @return mixed
	 */
	public function deleteData();
} 