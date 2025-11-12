<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
