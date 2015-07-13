<?php

class MySql {
	private $connection;
	private $log;

	function __construct($host, $user, $pass, $db, $log) {
		try {			
			$this->log = $log;
			$this->connection = new PDO (
				"mysql:dbname=$db;host=$host",
				$user,
				$pass,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING)
			);
		}
		catch (PDOException $e) {
			$this->log->error("Could not connect to mysql");
			$this->log->warning($e);
		}
	}

	function execute($sql, $params = null) {
		// handle with/without params
		$this->handleArrayParams($sql, $params);
		if ($params == null || (is_array($params) && count($params) == 0)) {
			$statement = null;
			$success = $this->connection->query($sql);
		} else {
			$statement = $this->connection->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$success = $statement->execute($params);
		}

		// error handling
		if ($success === false) {
			$this->log->fatal($this->connection->errorInfo());
			$result = array();
		} else if ($statement !== null) {
			$result = $statement->fetchAll();
		} else {
			$result = array ();
		}

		// buffer the output
		return $result;
	}

	function close() {
		$this->connection = null;
	}

	private function handleArrayParams(&$query, &$params) {
		$toRemove = array();
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$values = implode(",", array_map(function($value) { return $this->escape($value); }, $value));
				$query = str_replace(
					":$key", 
					$values, 
					$query
				);
				$toRemove[] = $key;
			}
		}

		foreach ($toRemove as $key) {
			unset($params[$key]);
		}
	}

	private function escape($data) {
		if (is_numeric($data)) {
			return $data;
		} else {
			return $this->connection->quote($data);
		}
	}
}

?>