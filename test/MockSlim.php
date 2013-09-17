<?php
namespace Slim;

// Mock class for Slim framework
class Slim
{

	protected function __construct() {}

	protected $data = array(
		'dsn' => 'sqlite::memory:',
	);

	public static function getInstance()
	{
		static $instance = null;
		if ($instance === null) {
			$instance = new Slim();
		}
		return $instance;
	}

	public function config($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : false;
	}

}
