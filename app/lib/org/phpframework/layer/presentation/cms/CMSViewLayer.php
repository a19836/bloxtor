<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");

class CMSViewLayer {
	private $CMSLayer;
	
	private $views;
	private $current_view_id;
	private $regions_by_view;
	
	private $stop_all_views;
	private $stop_by_view;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
	
		$this->views = array();
		$this->current_view_id = null;
		$this->regions_by_view = array();
		
		$this->stop_all_views = false;
		$this->stop_by_view = array();
	}
	
	public function createViewHtml($view_id, $html) {
		if ($this->isViewExecutionValid($view_id)) {
			$this->current_view_id = $view_id;//To be used by the stop views action
			
			$this->views[$view_id] = $html;
		}
	}
	
	public function getCurrentViewId() { return $this->current_view_id; }
	
	public function getViews() { return $this->views; }
	public function getView($view_id) { 
		return isset($this->views[$view_id]) ? $this->views[$view_id] : null;
	}
	public function existsView($view_id) {
		return $this->views && array_key_exists($view_id, $this->views);
	}
	public function getCurrentView() { 
		$view_id = $this->getCurrentViewId();
		return $view_id && isset($this->views[$view_id]) ? $this->views[$view_id] : null; 
	}
	
	public function getViewIdFromFilePath($file_path, $project_id = false) {
		$file_path = normalize_windows_path_to_linux($file_path); //$file_path is usually __FILE__, so we must convert the "\\" to "/" on windows.
		
		if ($project_id)
			$view_id = str_replace($this->CMSLayer->getEVC()->getViewsPath($project_id), "", $file_path);
		else {
			$P = $this->CMSLayer->getEVC()->getPresentationLayer();
			$current_project_id = $P->getSelectedPresentationId();
			
			$project_id = str_replace($P->getLayerPathSetting(), "", $file_path);
			$pos = strpos($project_id, "/src/");
			$project_id = substr($project_id, 0, $pos);
			
			if ($project_id != $current_project_id)
				$view_id = $project_id . "/" . str_replace($this->CMSLayer->getEVC()->getViewsPath($project_id), "", $file_path);
			else 
				$view_id = str_replace($this->CMSLayer->getEVC()->getViewsPath($current_project_id), "", $file_path);
		}
		
		$extension = pathinfo($view_id, PATHINFO_EXTENSION);
		
		if ($extension)
			$view_id = str_replace("." . $extension, "", $view_id);
		
		return $view_id;
	}
	
	/* STOP EXECUTION FUNCTIONS */
	
	public function stopAllViews() { 
		$this->stop_all_views = true; 
	}
	public function startAllViews() { 
		$this->stop_all_views = false; 
	}
	
	public function stopView($view_id) { 
		if ($view_id)
			$this->stop_by_view[$view_id] = true; 
	}
	public function startView($view_id) { 
		if ($view_id)
			$this->stop_by_view[$view_id] = false;
	}
	
	/*
	 * In order to this function works correctly, the $CMSTemplateLayer->addRegionHtml($region_id, $view_id) must be called before the 'include $EVC->getViewPath("view_id");', otherwise the stopViewRegions($view_id) won't work because it doesn't know the regions for the correspondent view.
	 */
	public function stopViewRegions($view_id) { 
		$this->stopView($view_id);
		
		if ($view_id && !empty($this->regions_by_view[$view_id])) {
			$T = $this->CMSLayer->getCMSTemplateLayer();
			
			foreach ($this->regions_by_view[$view_id] as $region_id => $aux)
				$T->stopRegion($region_id);
		}
	}
	public function startViewRegions($view_id) { 
		$this->startView($view_id);
		
		if ($view_id && !empty($this->regions_by_view[$view_id])) {
			$T = $this->CMSLayer->getCMSTemplateLayer();
			
			foreach ($this->regions_by_view[$view_id] as $region_id => $aux)
				$T->startRegion($region_id);
		}
	}
	
	public function stopCurrentView() { 
		$view_id = $this->getCurrentViewId();
		$this->stopView($this->view_id); 
	}
	public function startCurrentView() { 
		$view_id = $this->getCurrentViewId();
		$this->startView($this->view_id); 
	}
	
	public function stopCurrentViewRegions() { 
		$view_id = $this->getCurrentViewId();
		$this->stopViewRegions($this->view_id); 
	}
	public function startCurrentViewRegions() { 
		$view_id = $this->getCurrentViewId();
		$this->startViewRegions($this->view_id); 
	}
	
	public function isAllViewsExecutionValid() { 
		return !$this->stop_all_views; 
	}
	public function isViewRegionsExecutionValid($view_id) { 
		if ($view_id) {
			if (!empty($this->regions_by_view[$view_id])) {
				$CMSTemplateLayer = $this->CMSLayer->getCMSTemplateLayer();
				
				//Return true if exists at least one valid region. Only return false if all the regions are invalid. If 1 region is valid, return true, because te view should be executed!
				//By default each view will only have 1 region, so that region is invalid, this code will return false.
				foreach ($this->regions_by_view[$view_id] as $region_id => $aux)
					if ($CMSTemplateLayer->isRegionExecutionValid($region_id))
						return true;
				return false;
			}
			return true;
		}
		return false;
	}
	public function isViewExecutionValid($view_id) { 
		return $this->isAllViewsExecutionValid() && $view_id && empty($this->stop_by_view[$view_id]) && $this->isViewRegionsExecutionValid($view_id);
	}
	public function isCurrentViewExecutionValid() { 
		$view_id = $this->getCurrentViewId();
		return $this->isViewExecutionValid($this->view_id); 
	}
	
	public function addViewRegion($view_id, $region_id) {
		if ($view_id && $region_id)
			$this->regions_by_view[$view_id][$region_id] = true;
	}
}
?>
