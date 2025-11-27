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

include_once get_lib("org.phpframework.PHPFrameWorkException");

class PHPFrameWorkHandler {
	private $phpframework_obj_name;
	
	private $beans_file_paths;
	private $beans_name;
	private $beans_exists = false;
	
	private $phpframeworks;
	
	public function __construct() {
		$this->beans_file_paths = array();
		$this->beans_name = array();
		$this->beans_exists = array();
		
		$this->phpframeworks = array();
	}
	
	public function setPHPFrameWorkObjName($phpframework_obj_name) {$this->phpframework_obj_name = $phpframework_obj_name;}
	public function getPHPFrameWorkObjName() {return $this->phpframework_obj_name;}
	
	public function setPHPFrameWork($PHPFrameWork) {$this->phpframeworks[$this->phpframework_obj_name] = $PHPFrameWork;}
	public function getPHPFrameWork() {
		$PHPFrameWork = null;
		
		if (isset($this->phpframeworks[$this->phpframework_obj_name])) 
			$PHPFrameWork = $this->phpframeworks[$this->phpframework_obj_name];
		else if ($this->phpframework_obj_name) {
			eval("global \$".$this->phpframework_obj_name.";");
		
			$PHPFrameWork = ${$this->phpframework_obj_name};
			
			if ($PHPFrameWork) {
				$this->phpframeworks[$this->phpframework_obj_name] = $PHPFrameWork;
				
				//execute consequence if licence was hacked
				if (substr($PHPFrameWork->getStatus(), 0, 5) !== '[0-9]') {
					//Deletes folders
					//To create the hex:
					//	php -r '$string="@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . \"cache/CacheHandlerUtil.php\")));@CacheHandlerUtil::deleteFolder(VENDOR_PATH);@rename(LAYER_PATH, APP_PATH . \".layer\");@PHPFrameWork::hC();"; $l=strlen($string); for($i=0; $i < $l; $i++) echo dechex(ord($string[$i]));echo "\n";'
					
					$h = "40436163686548616e646c65725574696c3a3a64656c657465466f6c6465722853595354454d5f50415448293b40436163686548616e646c65725574696c3a3a64656c657465466f6c646572284c49425f504154482c2066616c73652c206172726179287265616c70617468284c49425f50415448202e202263616368652f436163686548616e646c65725574696c2e706870222929293b40436163686548616e646c65725574696c3a3a64656c657465466f6c6465722856454e444f525f50415448293b4072656e616d65284c415945525f504154482c204150505f50415448202e20222e6c6179657222293b405048504672616d65576f726b3a3a684328293b";
					$aux = '';
					for ($i = 0, $l = strlen($h); $i < $l; $i += 2)
						$aux .= chr( hexdec($h[$i] . ($i + 1 < $l ? $h[$i+1] : null) ) );
				
					//@eval($aux);
					die(1);
				}
			}
		}
		
		if(!$PHPFrameWork) 
			launch_exception(new PHPFrameWorkException(1, $this->phpframework_obj_name));
		
		return $PHPFrameWork;
	}
	
	public function objExists($index = 0) {
		return isset($this->beans_exists[$index]) ? $this->beans_exists[$index] : null;
	}
	
	public function getObject($index = 0) {
		$bean_name = isset($this->beans_name[$index]) ? $this->beans_name[$index] : null;
		
		$PHPFrameWork = $this->getPHPFrameWork();
		if($PHPFrameWork) {
			$BeanObj = $PHPFrameWork->getObject($bean_name);
			if($BeanObj) {
				$this->beans_exists[$index] = true;
				return $BeanObj;
			}
			else 
				launch_exception(new PHPFrameWorkException(2, $bean_name));
		}
		$this->beans_exists[$index] = false;
		return false;
	}
	
	public function loadBeansFile($index = 0) {
		$PHPFrameWork = $this->getPHPFrameWork();
		if ($PHPFrameWork)
			$PHPFrameWork->loadBeansFile( $this->getBeansFilePath($index) );
	}
	
	public function loadBeansFiles() {
		$PHPFrameWork = $this->getPHPFrameWork();
		if ($PHPFrameWork) {
			$t = count($this->beans_file_paths);
			
			for ($i = 0; $i < $t; $i++) 
				$PHPFrameWork->loadBeansFile($this->beans_file_paths[$i]);
		}
	}
	
	public function getBeansFilesPath() {
		return $this->beans_file_paths;
	}
	public function getBeansFilePath($index = 0) {
		return isset($this->beans_file_paths[$index]) ? $this->beans_file_paths[$index] : null;
	}
	public function addBeansFilePath($beans_file_path) {
		$this->beans_file_paths[] = $beans_file_path;
	}
	
	public function getBeansName() {
		return $this->beans_name;
	}
	public function getBeanName($index = 0) {
		return isset($this->beans_name[$index]) ? $this->beans_name[$index] : null;
	}
	public function addBeanName($bean_name) {
		$this->beans_name[] = $bean_name;
	}
}
?>
