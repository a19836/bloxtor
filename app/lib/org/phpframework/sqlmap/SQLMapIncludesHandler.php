<?php
class SQLMapIncludesHandler {
	
	public static function getLibsOfResultClassAndMap($result_class, $result_map) {
		$includes = array();
		
		if ($result_class)
			$includes[] = self::getConfiguredInclude($result_class);
		
		if (!empty($result_map["attrib"]["class"]))
			$includes[] = self::getConfiguredInclude($result_map["attrib"]["class"]);
		
		$t = !empty($result_map["result"]) ? count($result_map["result"]) : 0;
		for ($i = 0; $i < $t; $i++) {
			$result = $result_map["result"][$i];
			
			if(is_array($result)) 
				foreach($result as $key => $value) 
					if($value && ($key == "output_type" || $key == "input_type")) 
						$includes[] = self::getConfiguredInclude($value);
		}
		return $includes;
	}
	
	private static function getConfiguredInclude($include) {
		$pos = strpos($include, "(");
		$include = $pos !== false ? substr($include, 0, $pos) : $include;
		return $include;
	}
	
	public static function getRelationshipsLibsOfResultClassAndMap($relations) {
		$includes = array();
		if (is_array($relations)) {
			foreach($relations as $rel_name => $rel_elm) {
				$result_class = isset($rel_elm["result_class"]) ? $rel_elm["result_class"] : null;
				$result_map = isset($rel_elm["result_map"]) ? $rel_elm["result_map"] : null;
				
				$rel_includes = self::getLibsOfResultClassAndMap($result_class, $result_map);
				$includes = array_merge($includes, $rel_includes);
			}
		}
		return $includes;
	}
	
	public static function includeLibsOfResultClassAndMap($includes) {
		if (is_array($includes)) {
			$includes = array_flip($includes);
			$includes = array_flip($includes);
		
			reset($includes);
			foreach($includes as $include) {
				$include = get_lib($include);
			
				if(file_exists($include)) {
					include_once $include;
				}
			}
		}
	}
}
?>
