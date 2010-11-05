<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
class DbConnectionFactory {
	static private $instance = null;

	private $config = array();

	static public function getInstance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function setDatabase($name, $config) {
		$this->config[$name] = $config;
	}

	public function getConnection($name) {
		$config = $this->config[$name];

		$connection = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
		
		return $connection;
	}
}