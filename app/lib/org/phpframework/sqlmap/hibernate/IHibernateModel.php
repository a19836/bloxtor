<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface IHibernateModel {
	
	public function insert($data, &$ids = false, $options = false);
	public function insertAll($data, &$statuses = false, &$ids = false, $options = false);
	public function update($data, $options = false);
	public function updateAll($data, &$statuses = false, $options = false);
	public function insertOrUpdate($data, &$ids = false, $options = false);
	public function insertOrUpdateAll($data, &$statuses = false, &$ids = false, $options = false);
	public function updateByConditions($data, $options = false);
	public function updatePrimaryKeys($data, $options = false);
	public function delete($data, $options = false);
	public function deleteAll($data, &$statuses = false, $options = false);
	public function findById($ids, $data = array(), $options = false);
	public function find($data = array(), $options = false);
	public function findRelationships($parent_ids, $options = false);
	public function findRelationship($rel_name, $parent_ids, $options = false);
	
	/*********** IBATIS ************/
	public function callQuerySQL($query_type, $query_id, $parameters = false);
	public function callQuery($query_type, $query_id, $parameters = false, $options = false);
	
	public function callInsertSQL($query_id, $parameters = false);
	public function callInsert($query_id, $parameters = false, $options = false);
	
	public function callUpdateSQL($query_id, $parameters = false);
	public function callUpdate($query_id, $parameters = false, $options = false);
	
	public function callDeleteSQL($query_id, $parameters = false);
	public function callDelete($query_id, $parameters = false, $options = false);
	
	public function callSelectSQL($query_id, $parameters = false);
	public function callSelect($query_id, $parameters = false, $options = false);
	
	public function callProcedureSQL($query_id, $parameters = false);
	public function callProcedure($query_id, $parameters = false, $options = false);
	
	/*********** BROKER ************/
	
	public function getData($sql, $options = false);
	public function setData($sql, $options = false);
    	public function getInsertedId($options = false);
}
?>
