<?php
include_once get_lib("org.phpframework.mongodb.exception.MongoDBException");
include_once get_lib("org.phpframework.mongodb.IMongoDBHandler");

class MongoDBHandler implements IMongoDBHandler {
	private $conn;
	private $ok;
	private $auth_active;
	private db_name;
	
	public function connect($host = "",  $db_name = "", $username = "", $password = "", $port = "", $options = null) {
		try {
			$host_port = is_numeric($port) ? $host . ":" . $port : $host;
		
			$this->ok = false;
			$this->auth_active = !empty($username);
			
			if (!empty($username)) {
				if (empty($options))
					$options = array();
				
				$options["username"] = $username;
				$options["password"] = $password;
			}
			
			$this->conn = new MongoDB\Driver\Manager("mongodb://$host_port", $options);
			$this->db_name = $db_name;
			
			if ($this->conn) {
				$this->ok = true;
				
				return $this->conn;
			}
			else
				launch_exception(new MongoDBException(1, null, array($host, $db_name, $username, "***", $port)));
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(1, $e, array($host, $db_name, $username, "***", $port)));
		}
	}
	
	public function close() {
		if ($this->ok) {
			$cmd = new MongoDB\Driver\Command( array("logout" => 1) );
			$this->conn->executeCommand($this->db_name, $cmd);//Not sure if we should do this when we have multiple connections open with the same user. Maybe when we logout from 1 conn, we are logging out from all the conn of that user. if this is true, please disable this line.
			
			$this->ok = false;
		}
	} 
	
	public function ok() { 
		return $this->ok; 
	}
	
	public function getConn() {
		return $this->ok ? $this->conn : null;
	}
	
	public function get($collection_name, $key) {
		if ($this->ok && $collection_name && $key) {
			$exists = $this->existsCollection($collection_name);
			
			if ($exists) {
				$id = new MongoDB\BSON\ObjectId($key);
				$doc = $this->findCollectionDocuments($collection_name, array('_id' => $id), array("limit" => 1));
				$doc = isset($doc[0]) ? $doc[0] : null;
				
				if (is_array($doc) && isset($doc["content"]))
					return $doc["content"];
			}
		}
		return false;
	}
	
	public function getByRegex($collection_name, $regex) {
		$docs = array();
		
		if ($this->ok && $collection_name && $regex) {
			$exists = $this->existsCollection($collection_name);
			
			if ($exists) {
				$MongoRegex = new MongoDB\BSON\Regex($regex);
				return $this->findCollectionDocuments($collection_name, array('raw_id' => $MongoRegex));
			}
		}
		return $docs;
	}
	
	public function set($collection_name, $key, $cont) {
		if ($this->ok && $collection_name && $key) {
			$exists = $this->existsCollection($collection_name);
			
			if (!$exists) {
				$exists = $this->createCollection($collection_name);
				
				if ($exists)
					$collection->ensureIndex( array( "raw_id" => 1 ) );//create ascending index on the "raw_id" column
			}
			
			if ($exists) {
				$id = new MongoDB\BSON\ObjectId($key);
				
				$data = array(
					'_id' => $id,
					'raw_id' => $key,
					"content" => $cont,
				);
				
				return $this->insertCollectionDocument($collection_name, $data);
			}
		}
		return false;
	}
	
	public function delete($collection_name, $key) {
		if ($this->ok && $collection_name && $key) {
			$exists = $this->existsCollection($collection_name);
			
			if ($exists) {
				$id = new MongoDB\BSON\ObjectId($key);
				return $this->deleteCollectionDocuments($collection_name, array('_id' => $id));
			}
		}
		return false;
	}
	
	public function deleteByRegex($collection_name, $regex) {
		if ($this->ok && $collection_name && $regex) {
			$exists = $this->existsCollection($collection_name);
			
			if ($exists) {
				//If remove by regex does NOT exist in the Mongo engine, please use the following code:
				/*$docs = $this->getByRegex($collection_name, $regex);
				
				$status = true;
				foreach ($docs as $doc)
					if (!empty($doc["_id"]))
						if (!$this->deleteCollectionDocuments($collection_name, array('_id' => $doc["_id"])))
							$status = false;
				
				return $status;*/
				
				$MongoRegex = new MongoDB\BSON\Regex($regex);
				return $this->deleteCollectionDocuments($collection_name, array('raw_id' => $MongoRegex));
			}
		}
		return false;
	}
	
	public function executeCommand($cmd_settings) {
		try {
			if ($this->ok && $cmd_settings) {
				$cmd = new MongoDB\Driver\Command($cmd_settings);
				$cursor = $this->conn->executeCommand($this->db_name, $cmd);
				$response = null;
				
				if ($cursor) {
					$arr = $cursor->toArray();
					$response = isset($arr[0]) ? $arr[0] : null;
				}
				
				return $response && !empty($response["ok"]);
			}
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(3, $e, $cmd_settings));
		}
		return false;
	}
	
	public function executeQuery($collection_name, $filter = array(), $options = array()) {
		try {
			if ($this->ok && $collection_name) {
				$query = new MongoDB\Driver\Query($filter, $options);
				$rows = $this->conn->executeQuery($this->db_name . "." . $collection_name, $query);

				$data = array();
				foreach ($rows as $row)
					$data[] = $row;
				
				return $data;
			}
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(7, $e, array("collection_name" => $collection_name, "filter" => $filter, "options" => $options)));
		}
		return false;
	}
	
	public function deleteCollection($collection_name) {
		return $this->executeCommand( array("drop" => $collection_name) );
	}
	
	public function createCollection($collection_name) {
		return $this->executeCommand( array("create" => $collection_name) );
	}
	
	public function existsCollection($collection_name) {
		return $this->executeCommand( array("collstats" => $collection_name) );
	}
	
	public function insertCollectionDocument($collection_name, $data) {
		try {
			if ($this->ok && $collection_name) {
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->insert($data);
				$write_result = $this->conn->executeBulkWrite($this->db_name, $bulk); //$write_result is from type: MongoDB\Driver\WriteResult 
				
				return $write_result && !empty($write_result["ok"]);
			}
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(4, $e, array("collection_name" => $collection_name, "data" => $data)));
		}
		return false;
	}
	
	public function updateCollectionDocument($collection_name, $filter, $data) {
		try {
			if ($this->ok && $collection_name && $filter) {
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, array('$set' => $data), array('multi' => false));
				$write_result = $this->conn->executeBulkWrite($this->db_name, $bulk); //$write_result is from type: MongoDB\Driver\WriteResult 
				
				return $write_result && !empty($write_result["ok"]);
			}
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(5, $e, array("collection_name" => $collection_name, "filter" => $filter, "data" => $data)));
		}
		return false;
	}
	
	public function deleteCollectionDocuments($collection_name, $filter) {
		try {
			if ($this->ok && $collection_name && $filter) {
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->delete($filter);
				$write_result = $this->conn->executeBulkWrite($this->db_name, $bulk); //$write_result is from type: MongoDB\Driver\WriteResult 
				
				return $write_result && !empty($write_result["ok"]);
			}
		}
		catch(Exception $e) {
			launch_exception(new MongoDBException(6, $e, array("collection_name" => $collection_name, "filter" => $filter)));
		}
		return false;
	}
	
	public function findCollectionDocuments($collection_name, $filter = array(), $options = array()) {
		return $this->executeQuery($collection_name, $filter, $options);
	}
}
?>
