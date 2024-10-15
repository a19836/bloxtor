<?php
class BreadCrumbsUIHandler {
	
	public static function getFilePathBreadCrumbsHtml($file_path, $layer_obj = null, $remove_extension = false, $url = false, $main_class = false, $item_class = false) {
		$html = '<div class="breadcrumbs' . ($main_class ? " $main_class" : "") . '">' . self::getFilePathBreadCrumbsItemsHtml($file_path, $layer_obj, $remove_extension, $url, $item_class) . '</div>';
		
		return $html;
	}
		
	public static function getFilePathBreadCrumbsItemsHtml($file_path, $layer_obj = null, $remove_extension = false, $url = false, $item_class = false) {
		$html = '';
		
		$file_path = preg_replace("/[\/]+/", "/", $file_path); //remove duplicated /
		$path = $file_path;
		
		if ($layer_obj && is_a($layer_obj, "Layer")) {
			$layer_path = $layer_obj->getLayerPathSetting();
			$layer_folder_path = WorkFlowBeansFileHandler::getLayerObjFolderName($layer_obj);
		}
		else
			$layer_path = CMS_PATH;
		
		if ($layer_path && strpos($file_path, $layer_path) === 0)
			$path = substr($file_path, strlen($layer_path));
		
		//filter /src/.../ if presentation layer
		if ($layer_obj && is_a($layer_obj, "PresentationLayer")) {
			$selected_project_id = $layer_obj->getSelectedPresentationId();
			$str = "$selected_project_id/src/";
			//echo $str."<br>".$path;die();
			
			if (strpos($path, $str) === 0) {
				$pos = strpos($path, "/", strlen($str));
				$pos = $pos !== false ? $pos : strlen($path);
				
				$element_type = substr($path, strlen($str), $pos - strlen($str));
				
				if ($element_type) {
					//echo $element_type;die();
					
					if ($element_type == "entity") 
						$element_type = "pages";
					else
						$element_type .= "s"; //make it plural
					
					$path = substr($str, 0, -4) . $element_type . substr($path, $pos); //remove "src/" with: substr($str, 0, -4)
				}
			}
		}
		
		if ($remove_extension && substr($path, -4, 1) == ".")
			$path = substr($path, 0, -4);
		
		//echo $layer_path."<br>".$file_path."<br>".$path;die();
		
		if (!empty($layer_folder_path))
			$html .= '<span class="breadcrumb-item' . ($item_class ? " $item_class" : "") . '">' . $layer_folder_path . '</span>';
		
		$path = preg_replace("/^[\/]+/", "", $path); //remove start /
		$path = preg_replace("/[\/]+$/", "", $path); //remove end /
		
		$parts = explode("/", $path);
		$t = count($parts);
		$path_str = "";
		
		for ($i = 0; $i < $t; $i++) {
			$part = $parts[$i];
			
			if ($url) {
				$path_str .= ($path_str ? "/" : "") . $part;
				$part = '<a href="' . str_replace("#path#", $path_str, $url) . '" title="' . $path_str . '">' . $part . '</a>';
			}
			
			$html .= '<span class="breadcrumb-item' . ($item_class ? " $item_class" : "") . '">' . $part . '</span>';
		}
		
		return $html;
	}
}
?>
