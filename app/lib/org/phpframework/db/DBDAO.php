<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

trait DBDAO { 
	
	public function insertObject($table_name, $attributes, $options = false) {
		$sql = $this->buildTableInsertSQL($table_name, $attributes, $options);
		//echo "<pre>$sql\n$table_name\n";print_r($attributes);print_r($options);die();
		
		return $sql ? $this->setSQL($sql, $options) : false;
	}
	
	public function updateObject($table_name, $attributes, $conditions = false, $options = false) {
		$sql = $this->buildTableUpdateSQL($table_name, $attributes, $conditions, $options);
		//echo "<pre>$sql\n$table_name\n";print_r($attributes);print_r($conditions);print_r($options);die();
		
		return $sql ? $this->setSQL($sql, $options) : false;
	}
	
	public function deleteObject($table_name, $conditions = false, $options = false) {
		$sql = $this->buildTableDeleteSQL($table_name, $conditions, $options);
		
		return $sql ? $this->setSQL($sql, $options) : false;
	}
	
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false) {
		$sql = $this->buildTableFindSQL($table_name, $attributes, $conditions, $options);
		//echo "<pre>$sql\n$table_name\n";print_r($attributes);print_r($conditions);print_r($options);die();
		
		if ($sql)
			return $this->getSQL($sql, $options);
		return false;
	}
	
	public function countObjects($table_name, $conditions = false, $options = false) {
		$sql = $this->buildTableCountSQL($table_name, $conditions, $options);
		
		if ($sql) {
			$result = $this->getSQL($sql, $options);
			return isset($result[0]["total"]) ? $result[0]["total"] : null;
		}
		return false;
	}
	
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = $this->buildTableFindRelationshipSQL($table_name, $rel_elm, $parent_conditions, $options);
		
		if ($sql)
			return $this->getSQL($sql, $options);
		return false;
	}
	
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false) {
		$sql = $this->buildTableCountRelationshipSQL($table_name, $rel_elm, $parent_conditions, $options);
		
		if ($sql) {
			$result = $this->getSQL($sql, $options);
			return isset($result[0]["total"]) ? $result[0]["total"] : null;
		}
		return false;
	}
	
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false) {
		$sql = $this->buildTableFindColumnMaxSQL($table_name, $attribute_name, $options);
		
		if ($sql) {
			$result = $this->getSQL($sql, $options);
			return isset($result[0]["max"]) ? $result[0]["max"] : null;
		}
		return false;
	}
} 
?>
