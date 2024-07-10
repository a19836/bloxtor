<?php
interface IIbatisDataAccessBrokerClient {
	
	public function callQuerySQL($module, $type, $service, $parameters = false);
	public function callQuery($module, $type, $service, $parameters = false, $options = false);
	
	public function callSelectSQL($module, $service, $parameters = false);
	public function callSelect($module, $service, $parameters = false, $options = false);
	
	public function callInsertSQL($module, $service, $parameters = false);
	public function callInsert($module, $service, $parameters = false, $options = false);
	
	public function callUpdateSQL($module, $service, $parameters = false);
	public function callUpdate($module, $service, $parameters = false, $options = false);
	
	public function callDeleteSQL($module, $service, $parameters = false);
	public function callDelete($module, $service, $parameters = false, $options = false);
	
	public function callProcedureSQL($module, $service, $parameters = false);
	public function callProcedure($module, $service, $parameters = false, $options = false);
}
?>
