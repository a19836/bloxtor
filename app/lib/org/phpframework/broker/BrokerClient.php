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

include_once get_lib("org.phpframework.broker.Broker");
include_once get_lib("org.phpframework.PHPFrameWorkHandler");

abstract class BrokerClient extends Broker {
	protected $PHPFrameWorkHandler;
	
	public function __construct() {
		parent::__construct();
		
		$this->PHPFrameWorkHandler = new PHPFrameWorkHandler();
	}
	
	public function setPHPFrameWorkObjName($phpframework_obj_name) {$this->PHPFrameWorkHandler->setPHPFrameWorkObjName($phpframework_obj_name);}
	
	public function addBeansFilePath($beans_file_path) {$this->PHPFrameWorkHandler->addBeansFilePath($beans_file_path);}
	public function getBeansFilesPath() {return $this->PHPFrameWorkHandler->getBeansFilesPath();}
	
	public function setBeanName($bean_name) {$this->PHPFrameWorkHandler->addBeanName($bean_name);}
	public function getBeanName() {return $this->PHPFrameWorkHandler->getBeanName();}
}
?>
