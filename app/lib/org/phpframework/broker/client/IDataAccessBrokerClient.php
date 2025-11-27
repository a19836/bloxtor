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

interface IDataAccessBrokerClient {
	
	public function getBrokersDBDriversName();
	
	public function getFunction($function_name, $parameters = false, $options = false);
	
	public function getData($sql, $options = false);
	public function setData($sql, $options = false);
	
	public function getSQL($sql, $options = false);
	public function setSQL($sql, $options = false);
	
	public function getInsertedId($options = false);
	
	public function insertObject($table_name, $attributes, $options = false);
	public function updateObject($table_name, $attributes, $conditions = false, $options = false);
	public function deleteObject($table_name, $conditions = false, $options = false);
	public function findObjects($table_name, $attributes = false, $conditions = false, $options = false);
	public function countObjects($table_name, $conditions = false, $options = false);
	public function findRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function countRelationshipObjects($table_name, $rel_elm, $parent_conditions = false, $options = false);
	public function findObjectsColumnMax($table_name, $attribute_name, $options = false);
}
?>
