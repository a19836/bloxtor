<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.mongodb.exception.MongoDBException");
include_once get_lib("org.phpframework.mongodb.IMongoDBHandler");

/*
 * This class only works for PHP 5.6 or lower and if the php5-mongo is installed
 */
class MongoDBHandlerDeprecated implements IMongoDBHandler {
	private $conn;
	private $db_link;
	private $ok;
	private $auth_active;
	
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
			
			$this->conn = new Mongo($host_port, $options);
			$this->db_link = false;
			
			if ($this->conn) {
				$this->db_link = $this->conn->selectDB($db_name);
				
				if ($this->db_link) {
					$this->ok = true;
					
					return $this->conn;
				}
				else
					launch_exception(new MongoDBException(2, null, $db_name));
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
			if ($this->auth_active) {
				$this->conn->command( array("logout" => 1) );//Not sure if we should do this when we have multiple connections open with the same user. Maybe when we logout from 1 conn, we are logging out from all the conn of that user. if this is true, please disable this line.
			
				$this->ok = false;
			}
			
			$this->conn->close();
		}
	} 
	
	public function ok() { 
		return $this->ok; 
	}
	
	public function getConn() {
		return $this->ok ? $this->conn : null;
	}
	
	public function getDBLink() {
		return $this->ok ? $this->db_link : null;
	}
	
	public function get($collection_name, $key) {
		if ($this->ok && !empty($collection_name) && !empty($key)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (!empty($collection)) {
				$doc = $collection->findOne(
					array(
						'_id' => new MongoId($key)
					)
				);
				
				if (is_array($doc) && isset($doc["content"])) {
					return $doc["content"];
				}
			}
		}
		return false;
	}
	
	public function getByRegex($collection_name, $regex) {
		$docs = array();
		
		if ($this->ok && !empty($collection_name) && !empty($regex)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (!empty($collection)) {
				$MongoRegex = new MongoRegex($regex);
				$where = array("raw_id" => $MongoRegex);
				
				$cursor = $collection->find($where);
				while($cursor->hasNext()) {
					$docs[] = $cursor->getNext();
				}
			}
		}
		return $docs;
	}
	
	public function set($collection_name, $key, $cont) {
		if ($this->ok && !empty($collection_name) && !empty($key)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (empty($collection)) {
				$collection = $this->db_link->createCollection($collection_name);
				
				if (!empty($collection)) {
					$collection->ensureIndex( array( "raw_id" => 1 ) );//create ascending index on the "raw_id" column
				}
			}
			
			if (!empty($collection)) {
				$status = $collection->save( 
					array(
						'_id' => new MongoId($key),
						'raw_id' => $key,
						"content" => $cont,
					), 
					array(
						"safe" => true,
					)
				);
				
				return isset($status["ok"]) ? $status["ok"] : null;
			}
		}
		return false;
	}
	
	public function delete($collection_name, $key) {
		if ($this->ok && !empty($collection_name) && !empty($key)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (!empty($collection)) {
				return $collection->remove(
					array(
						'_id' => new MongoId($key)
					)
				);
			}
		}
		return false;
	}
	
	public function deleteByRegex($collection_name, $regex) {
		if ($this->ok && !empty($collection_name) && !empty($regex)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (!empty($collection)) {
				//If remove by regex does NOT exist in the Mongo engine, please use the following code:
				/*$docs = $this->getByRegex($collection_name, $regex);
				
				$status = true;
				foreach ($docs as $doc) {
					if (!empty($doc["_id"])) {
						if (!$collection->remove(
								array(
									'_id' => $doc["_id"]
								)
							)
						) {
							$status = false;
						} 
					}
				}
				
				return $status;*/
				
				$MongoRegex = new MongoRegex($regex);
				$where = array("raw_id" => $MongoRegex);
				
				return $collection->remove($where);
			}
		}
		return false;
	}
	
	public function deleteCollection($collection_name) {
		if ($this->ok && !empty($collection_name)) {
			$collection = $this->db_link->selectCollection($collection_name);
			
			if (!empty($collection)) {
				return $collection->drop();
			}
		}
		return false;
	}
}
?>
