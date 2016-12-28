<?php

namespace Sairoko;

use PDO;
use Exception;
use PDOException;

class Connection
{
	protected $db;

	public function __construct(array $params)
	{
		try {
			$this->db = new PDO($params['driver'] . ":host=" . $params["db_host"] . ";port=" . $params["db_port"] . ";dbname=" . $params["database"], $params["db_user"], $params["db_pass"]);
		} catch (PDOException  $e) {
			echo "Message: " . $e->getMessage();
			exit(0);
		}
	}

	public function setDb(PDO $connection)
	{
		$this->db = $connection;
	}

	public function getAdapter()
	{
		if (empty($this->db)) {
            if (!$this->db instanceof PDO) {
                throw new Exception("No set PDO connection :(", 500);
            }
        }

		return $this->db;
	}
}