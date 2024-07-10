<?php
interface IDataAccessBrokerServer {
	
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
