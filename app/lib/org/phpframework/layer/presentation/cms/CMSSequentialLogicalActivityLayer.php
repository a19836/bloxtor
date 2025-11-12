<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");
include_once get_lib("org.phpframework.layer.presentation.cms.SequentialLogicalActivity");
include_once get_lib("org.phpframework.util.HashTagParameter");
include_once get_lib("org.phpframework.ptl.PHPTemplateLanguage");

class CMSSequentialLogicalActivityLayer {
	private $CMSLayer;
	
	private $sla_settings;
	private $sla_results;
	private $is_sla_executed;
	private $non_executed_slas;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
		
		$this->resetSequentialLogicalActivities();
	}
	
	public function resetSequentialLogicalActivities() {
		$this->sla_settings = array();
		$this->sla_results = null;
		$this->is_sla_executed = false;
		$this->non_executed_slas = array();
	}
	
	/* 
	 * Note that we can call this function from the entities and from the templates which means that if we call it from the templates, we need to append the $sla_settings var to the previous sla_settings that were set in the entities. 
	 * Do not use '$this->sla_settings = $sla_settings;' otherwise when we call this method from the templates, it will overwrite the sla_settings from the entities.
	 */
	public function addSequentialLogicalActivities($sla_settings) {
		if (is_array($sla_settings)) {
			//merge sla_settings with generic sla_settings
			$this->sla_settings = array_merge($this->sla_settings, $sla_settings);
			
			$id = $this->getSLASettingsId($sla_settings);
			$this->non_executed_slas[$id] = $sla_settings;
			//echo "id:$id<br/>";
		}
	}
	
	public function existSequentialLogicalActivitiesToBeAlwaysExecuted($sla_settings = null) {
		$sla_settings = $sla_settings ? $sla_settings : $this->sla_settings;
		$EVC = $this->CMSLayer->getEVC();
		
		$SequentialLogicalActivity = new SequentialLogicalActivity();
		$SequentialLogicalActivity->setEVC($EVC);
		$exists = $SequentialLogicalActivity->existActionsValidCondition($sla_settings, $this->sla_results);
		//echo "exists:$exists<br/>";
		
		return $exists;
	}
	
	public function prepareHTMLWithSequentialLogicalActivities(&$html) {
		$this->prepareHTMLHashTagsWithSequentialLogicalActivities($html);
		$this->prepareHTMLPTLWithSequentialLogicalActivities($html);
	}
	
	public function prepareHTMLHashTagsWithSequentialLogicalActivities(&$html) {
		//execute the hash_tag replacer even if no sla_results bc the hash_tags can be to the php GLOBAL vars like #_GET[aa]# or something else.
		if (stripos($html, '#') !== false) {
			//echo "<pre>";print_r(array_keys($this->sla_results));die();
			
			//replace hashtags in html, but only if are present in $this->sla_results
			$exists_sla_results = is_array($this->sla_results) && count($this->sla_results);
			
			$external_vars = $exists_sla_results ? $this->sla_results : array();
			
			//prepare filter by resource names
			if (!$external_vars) {
			//be sure that none of the resources get parsed, so create a regex where variables starts with '-1', which is impossible, variables name in php cannot start with a dash or number. 
			//Note that this is super important bc the MyWidgetResourceLib.js and vendor/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js libraries add the codes '#widget_search_attr_name#' and '#widget_search_secs_to_wait#', when adding 'data-widget-search' html elements in the Page Editor, and we MUST NOT replace these codes. 
			//If in the future, by any change, we decide to allow replacements to hash codes that don't exists yet, then we need to update the MyWidgetResourceLib.js and LayoutUIEditorWidgetResource.js files accordingly.
				$filter_regex = "/#-1#/";
			}
			else {
				$filter_regex = array();
				
				foreach ($external_vars as $k => $aux) 
					if ($k) {
						$filter_regex[] = "/#\[(\"|')?(Resource|SLA|Resources|SLAs)(\"|')?\]\[?$k(\]|#)/i";
						$filter_regex[] = "/#(\"|')?(Resource|SLA|Resources|SLAs)(\"|')?\[$k(\]|#)/i";
					}
			}
			//error_log("prepareHTMLHashTagsWithSequentialLogicalActivities filter_regex:".print_r($filter_regex, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			//error_log("prepareHTMLHashTagsWithSequentialLogicalActivities external_vars:".print_r($external_vars, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			//always execute the str_replace so it can replace the global vars
			$items = HashTagParameter::getHTMLHashTagParametersValues($html, $filter_regex, true, "external_vars");
			//error_log("items:".print_r($items, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			foreach ($items as $hash_tag => $replacement) 
				if ($replacement) {
					$replacement = preg_replace("/^(\\\$external_vars)\[(\"|')(Resource|SLA|Resources|SLAs)(\"|')\]/i", '$1', $replacement);
					//error_log("$hash_tag => $replacement\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
					
					eval('$replacement = ' . $replacement . ';');
					$html = str_replace($hash_tag, $replacement, $html); //always execute the str_replace so it can replace the global vars
				}
		}
	}
	
	public function prepareHTMLPTLWithSequentialLogicalActivities(&$html) {
		//prepare PTL (Note that the ptl may not use the sla_results, this is, it can simply use some static data)
		//Note that if there is a ptl tag which is not closed, the code below will break. So is very important that this method don't get called in the render template regions, bc we may have some ptl open tag in 1 region and the correspondent ptl close tag in another region.
		if (stripos($html, '<ptl:') !== false) {
			$EVC = $this->CMSLayer->getEVC();
			$UserCacheHandler = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
			$PHPTemplateLanguage = new PHPTemplateLanguage();
			
			if ($UserCacheHandler)
				$PHPTemplateLanguage->setCacheHandler($UserCacheHandler);
			
			$exists_sla_results = is_array($this->sla_results) && count($this->sla_results);
			
			$external_vars = $exists_sla_results ? $this->sla_results : array();
			$external_vars["EVC"] = $EVC;
			
			$html = $PHPTemplateLanguage->parseTemplate($html, $external_vars);
		}
	}
	
	public function executeSequentialLogicalActivities() {
		$output = "";
		
		if (!$this->is_sla_executed && is_array($this->sla_settings) && count($this->sla_settings)) {
			$this->is_sla_executed = true;
			$this->non_executed_slas = array();
			//echo "1<br/>";
			
			//execute sla settings and save results to sla_results
			$EVC = $this->CMSLayer->getEVC();
			
			$SequentialLogicalActivity = new SequentialLogicalActivity();
			$SequentialLogicalActivity->setEVC($EVC);
			
			$output = $SequentialLogicalActivity->execute($this->sla_settings, $this->sla_results); //this already updates the $this->sla_results with new results and $this->sla_settings.
		}
		else if ($this->non_executed_slas) {
			//echo "2<br/>";
			$EVC = $this->CMSLayer->getEVC();
			
			$SequentialLogicalActivity = new SequentialLogicalActivity();
			$SequentialLogicalActivity->setEVC($EVC);
			
			foreach ($this->non_executed_slas as $id => $sla_settings) {
				//execute sla settings and save results to sla_results
				$output .= $SequentialLogicalActivity->execute($sla_settings, $this->sla_results); //this already updates the $this->sla_results with new results and $this->sla_settings.
				
				unset($this->non_executed_slas[$id]);
			}
		}
		
		if ($output)
			echo $output;
	}
	
	private function getSLASettingsId($sla_settings) {
		return md5(json_encode($sla_settings));
	}
	
	public function getSLAResults() { return $this->sla_results; }
	public function getSLASettings() { return $this->sla_settings; }
	public function isSLAExecuted() { return $this->is_sla_executed && empty($this->non_executed_slas); }
}
?>
