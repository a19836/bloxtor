<?php
include_once get_lib("org.phpframework.phpscript.phpparser.phpparser_autoload");
include_once get_lib("org.phpframework.phpscript.PHPCodeBeautifier");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class CMSFileHandler {
	
	public static function getContentsMethodParams($contents, $method_names, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, $method_names, $class_obj, $search_limit, $depth);
			
			return $methods;
		}
	}
	
	public static function getFileTemplates($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getTemplates(self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getTemplates($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$templates = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			/*$class_obj_name = !$class_obj_name ? "[a-z0-1\_]+" : $class_obj_name;
			
			preg_match_all('/\$(' . $class_obj_name . ')(\s*)->(\s*)setTemplate(\s*)\((\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$codes_1 = $matches[0] ? $matches[0] : array();
			$template_names_1 = $matches[6] ? $matches[6] : array();
			
			$codes = $codes_1;//array_merge($codes_1, $codes_2, $codes_3);
			$template_names = $template_names_1;//array_merge($template_names_1, $template_names_2, $template_names_3);
			
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$template = trim($template_names[$i]);
				$template_type = self::getArgumentType($template);
				$template = self::prepareArgument($template, $template_type);
				
				$templates[] = array(
					"match" => $codes[$i],
					"template" => $template,
					"template_type" => $template_type,
				);
			}*/
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, "setTemplate", $class_obj, $search_limit, $depth);
			//echo "<pre>";print_r($matches);
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					self::prepareMethodParamVariables($method, 0, $template, $template_type);
					self::prepareMethodParamVariables($method, 1, $template_params, $template_params_type);
					
					$templates[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"template" => $template,
						"template_type" => $template_type,
						"template_params" => $template_params,
						"template_params_type" => $template_params_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		return $templates;
	}
	
	public static function getFileRegions($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getRegions( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	//Parse $XXX->renderRegion(YYY)
	public static function getRegions($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$regions = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			/*$class_obj_name = !$class_obj_name ? '[^\$]+' : $class_obj_name;
			
			preg_match_all('/\$(' . $class_obj_name . ')(\s*)->(\s*)renderRegion(\s*)\((\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$codes = $matches[0] ? $matches[0] : array();
			$class_objs = $matches[1] ? $matches[1] : array();
			$region_names = $matches[6] ? $matches[6] : array();
			
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$region = trim($region_names[$i]);
				$region_type = self::getArgumentType($region);
				$region = self::prepareArgument($region, $region_type);
				
				$regions[] = array(
					"match" => $codes[$i],
					"class_obj" => '$' . $class_objs[$i],
					"region" => $region,
					"region_type" => $region_type,
				);
			}*/
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, "renderRegion", $class_obj, $search_limit, $depth);
			//echo "<pre>";print_r($matches);
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					self::prepareMethodParamVariables($method, 0, $region, $region_type);
					
					$regions[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"region" => $region,
						"region_type" => $region_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		return $regions;
	}
	
	public static function getFileRegionsBlocks($file_path, $class_obj_name = false) {
		return self::getRegionsBlocks( self::getFileContents($file_path), $class_obj_name );
	}
	
	//Parse $XXX->addRegionBlock(YYY, WWW)
	public static function getRegionsBlocks($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$regions_blocks = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getBlockPath(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$block_paths = !empty($matches[8]) ? $matches[8] : array();
			$block_projects = !empty($matches[11]) ? $matches[11] : array();
			
			$blocks_projects = array();
			$t = count($block_paths);
			for ($i = 0; $i < $t; $i++) {
				$block_path = trim($block_paths[$i]);
				$project = trim($block_projects[$i]);
				
				$blocks_projects[$block_path][] = $project;
			}
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getViewPath(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$view_paths = !empty($matches[8]) ? $matches[8] : array();
			$view_projects = !empty($matches[11]) ? $matches[11] : array();
			
			$views_projects = array();
			$t = count($view_paths);
			for ($i = 0; $i < $t; $i++) {
				$view_path = trim($view_paths[$i]);
				$project = trim($view_projects[$i]);
				
				$views_projects[$view_path][] = $project;
			}
			
			/*$con = !$class_obj_name ? '[^\$]+' : $class_obj_name;
			
			preg_match_all('/\$(' . $con . ')(\s*)->(\s*)addRegionBlock(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$codes = $matches[0] ? $matches[0] : array();
			$class_objs = $matches[1] ? $matches[1] : array();
			$region_names = $matches[6] ? $matches[6] : array();
			$block_names = $matches[9] ? $matches[9] : array();
			$block_project_names = $matches[12] ? $matches[12] : array();
			
			$blocks_projects_idx = array();
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$region = trim($region_names[$i]);
				$region_type = self::getArgumentType($region);
				$region = self::prepareArgument($region, $region_type);
				
				$block = trim($block_names[$i]);
				
				$idx = $blocks_projects_idx[$block] ? $blocks_projects_idx[$block] : 0;
				$block_project = $blocks_projects[$block][$idx];
				$blocks_projects_idx[$block] = count($blocks_projects[$block]) > $idx + 1 ? $idx + 1 : $idx;
				
				$block_project = $block_project ? $block_project : trim($block_project_names[$i]);
				
				$block_type = self::getArgumentType($block);
				$block = self::prepareArgument($block, $block_type);
				
				$block_project_type = self::getArgumentType($block_project);
				$block_project = self::prepareArgument($block_project, $block_project_type);
				
				$regions_blocks[] = array(
					"match" => $codes[$i],
					"class_obj" => '$' . $class_objs[$i],
					"region" => $region,
					"region_type" => $region_type,
					"block" => $block,
					"block_type" => $block_type,
					"block_project" => $block_project,
					"block_project_type" => $block_project_type,
				);
			}*/
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, array("addRegionHtml", "addRegionBlock", "includeRegionBlockPathOutput", "addRegionView", "includeRegionViewPathOutput"), $class_obj, $search_limit, $depth);
			//echo '<textarea>'.$contents.'</textarea>';die();
			//echo "<pre>";print_r($methods);die();
			
			$blocks_projects_idx = array();
			$views_projects_idx = array();
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					$method_name = isset($method["method"]) ? $method["method"] : null;
					$block = $block_type = $block_project = $block_project_type = null;
					$type = null;
					
					self::prepareMethodParamVariables($method, 0, $region, $region_type);
					
					if ($method_name == "addRegionHtml") {
						$type = 1;
						self::prepareMethodParamVariables($method, 1, $block, $block_type);
					}
					else if ($method_name == "addRegionBlock") {
						$type = 2;
						self::prepareMethodParamVariables($method, 1, $block, $block_type);
						
						$b = trim( self::getVariableValueCode($block, $block_type) );
						$idx = !empty($blocks_projects_idx[$b]) ? $blocks_projects_idx[$b] : 0;
						$block_project = isset($blocks_projects[$b][$idx]) ? $blocks_projects[$b][$idx] : null;
						$blocks_projects_idx[$b] = !empty($blocks_projects[$b]) && count($blocks_projects[$b]) > $idx + 1 ? $idx + 1 : $idx;
						
						if ($block_project) {
							$block_project_type = self::getArgumentType($block_project);
							$block_project = self::prepareArgument($block_project, $block_project_type);
						}
						else 
							self::prepareMethodParamVariables($method, 2, $block_project, $block_project_type);
					}
					else if ($method_name == "includeRegionBlockPathOutput") {
						$type = 3;
						self::prepareMethodParamVariables($method, 1, $block, $block_type);
						self::prepareMethodParamVariables($method, 2, $block_project, $block_project_type);
					}
					else if ($method_name == "addRegionView") {
						$type = 4;
						self::prepareMethodParamVariables($method, 1, $block, $block_type);
						
						$b = trim( self::getVariableValueCode($block, $block_type) );
						$idx = !empty($views_projects_idx[$b]) ? $views_projects_idx[$b] : 0;
						$block_project = isset($views_projects[$b][$idx]) ? $views_projects[$b][$idx]: null;
						$views_projects_idx[$b] = !empty($views_projects[$b]) && count($views_projects[$b]) > $idx + 1 ? $idx + 1 : $idx;
						
						if ($block_project) {
							$block_project_type = self::getArgumentType($block_project);
							$block_project = self::prepareArgument($block_project, $block_project_type);
						}
						else 
							self::prepareMethodParamVariables($method, 2, $block_project, $block_project_type);
					}
					else if ($method_name == "includeRegionViewPathOutput") {
						$type = 5;
						self::prepareMethodParamVariables($method, 1, $block, $block_type);
						self::prepareMethodParamVariables($method, 2, $block_project, $block_project_type);
					}
					
					$regions_blocks[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"method" => $method_name,
						"type" => $type,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"region" => $region,
						"region_type" => $region_type,
						"block" => $block,
						"block_type" => $block_type,
						"block_project" => $block_project,
						"block_project_type" => $block_project_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		//echo "<pre>";print_r($regions_blocks);die();
		return $regions_blocks;
	}
	
	//Parse $XXX->getBlock(WWW)
	public static function getHardCodedRegionsBlocks($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$blocks = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getBlockPath(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$block_paths = !empty($matches[8]) ? $matches[8] : array();
			$block_projects = !empty($matches[11]) ? $matches[11] : array();
			
			$blocks_projects = array();
			$t = count($block_paths);
			for ($i = 0; $i < $t; $i++) {
				$block_path = trim($block_paths[$i]);
				$project = trim($block_projects[$i]);
				
				$blocks_projects[$block_path][] = $project;
			}
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getViewPath(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$view_paths = !empty($matches[8]) ? $matches[8] : array();
			$view_projects = !empty($matches[11]) ? $matches[11] : array();
			
			$views_projects = array();
			$t = count($view_paths);
			for ($i = 0; $i < $t; $i++) {
				$view_path = trim($view_paths[$i]);
				$project = trim($view_projects[$i]);
				
				$views_projects[$view_path][] = $project;
			}
			
			/*$con = !$class_obj_name ? '[^\$]+' : $class_obj_name;
			
			preg_match_all('/\$(' . $con . ')(\s*)->(\s*)getBlock(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$codes = $matches[0] ? $matches[0] : array();
			$class_objs = $matches[1] ? $matches[1] : array();
			$block_names = $matches[6] ? $matches[6] : array();
			$block_project_names = $matches[9] ? $matches[9] : array();
			
			$blocks_projects_idx = array();
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$block = trim($block_names[$i]);
				
				$idx = $blocks_projects_idx[$block] ? $blocks_projects_idx[$block] : 0;
				$block_project = $blocks_projects[$block][$idx];
				$blocks_projects_idx[$block] = count($blocks_projects[$block]) > $idx + 1 ? $idx + 1 : $idx;
				
				$block_project = $block_project ? $block_project : trim($block_project_names[$i]);
				
				$block_type = self::getArgumentType($block);
				$block = self::prepareArgument($block, $block_type);
				
				$block_project_type = self::getArgumentType($block_project);
				$block_project = self::prepareArgument($block_project, $block_project_type);
				
				$blocks[] = array(
					"match" => $codes[$i],
					"class_obj" => '$' . $class_objs[$i],
					"block" => $block,
					"block_type" => $block_type,
					"block_project" => $block_project,
					"block_project_type" => $block_project_type,
				);
			}*/
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$'  ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, array("getBlock", "getView"), $class_obj, $search_limit, $depth);
			//echo '<textarea>'.$contents.'</textarea>';
			//echo "<pre>";print_r($methods);
			
			$blocks_projects_idx = array();
			$views_projects_idx = array();
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					$method_name = isset($method["method"]) ? $method["method"] : null;
					
					self::prepareMethodParamVariables($method, 0, $block, $block_type);
					self::prepareMethodParamVariables($method, 1, $block_index, $block_index_type);
					
					$b = trim( self::getVariableValueCode($block, $block_type) );
					
					if ($method_name == "getBlock") {
						$idx = !empty($blocks_projects_idx[$b]) ? $blocks_projects_idx[$b] : 0;
						$block_project = isset($blocks_projects[$b][$idx]) ? $blocks_projects[$b][$idx] : null;
						$blocks_projects_idx[$b] = !empty($blocks_projects[$b]) && count($blocks_projects[$b]) > $idx + 1 ? $idx + 1 : $idx;
					}
					else {
						$idx = !empty($views_projects_idx[$b]) ? $views_projects_idx[$b] : 0;
						$block_project = isset($views_projects[$b][$idx]) ? $views_projects[$b][$idx] : null;
						$blocks_projects_idx[$b] = !empty($views_projects[$b]) && count($views_projects[$b]) > $idx + 1 ? $idx + 1 : $idx;
					}
					
					if ($block_project) {
						$block_project_type = self::getArgumentType($block_project);
						$block_project = self::prepareArgument($block_project, $block_project_type);
					}
					
					$blocks[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"method" => $method_name,
						"type" => $method_name == "getBlock" ? 2 : 4,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"block" => $block,
						"block_type" => $block_type,
						"block_project" => $block_project,
						"block_project_type" => $block_project_type,
						"block_index" => $block_index,
						"block_index_type" => $block_index_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		//echo "<pre>";print_r($blocks);die();
		return $blocks;
	}
	
	public static function getFileIncludes($file_path, $with_blocks_include = true) {
		return self::getIncludes(self::getFileContents($file_path), $with_blocks_include);
	}
	
	public static function getIncludes($contents, $with_blocks_include = true) {
		$includes = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/(include( |\$)|include_once( |\$)|require( |\$)|require_once( |\$))(\s*)([^;]+);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$codes = !empty($matches[0]) ? $matches[0] : array();
			$types = !empty($matches[1]) ? $matches[1] : array();
			$paths = !empty($matches[7]) ? $matches[7] : array();
			
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) 
				if (isset($paths[$i])) {
					$path = trim($paths[$i]);
					$path_type = self::getArgumentType($path);
					$path = self::prepareArgument($path, $path_type);
					
					if (!$with_blocks_include) {
						preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getBlockPath(\s*)\((\s*)([^\)]*)(\s*)\)([^;]*);/iu', $codes[$i], $m, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
						
						if (isset($m[8][0]))
							continue 1;
						else {
							preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+\(?\)?\->)+(\s*)(includeRegionBlockPathOutput|includeRegionViewPathOutput)(\s*)\((\s*)([^,]*)(\s*),(\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $codes[$i], $m, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
							
							if (isset($m[8][0]))
								continue 1;
						}
					}
					
					$includes[] = array(
						"match" => $codes[$i],
						"once" => isset($types[$i]) && strpos($types[$i], "_once") > 0,
						"path" => $path,
						"path_type" => $path_type,
					);
				}
		}
		
		return $includes;
	}
	
	public static function getFileIncludesBlock($file_path) {
		return self::getIncludesBlock( self::getFileContents($file_path) );
	}
	
	public static function getIncludesBlock($contents) {
		$includes = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getBlockPath(\s*)\((\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$codes_1 = !empty($matches[0]) ? $matches[0] : array();
			$types_1 = !empty($matches[1]) ? $matches[1] : array();
			$paths_1 = !empty($matches[8]) ? $matches[8] : array();
			$projects_1 = !empty($matches[11]) ? $matches[11] : array();
			$regions_1 = array();
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)getBlocksPath\(\)(\s*)\.(\s*)([^;]*)(\s*)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$codes_2 = !empty($matches[0]) ? $matches[0] : array();
			$types_2 = !empty($matches[1]) ? $matches[1] : array();
			$paths_2 = !empty($matches[8]) ? $matches[8] : array();
			$projects_2 = array();
			$regions_2 = array();
			
			preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+)(\s*)->(\s*)includeRegionBlockPathOutput(\s*)\((\s*)([^,]*)(\s*),(\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/iu', $contents, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			$codes_3 = !empty($matches[0]) ? $matches[0] : array();
			$types_3 = !empty($matches[1]) ? $matches[1] : array();
			$paths_3 = !empty($matches[11]) ? $matches[11] : array();
			$projects_3 = !empty($matches[14]) ? $matches[14] : array();
			$regions_3 = !empty($matches[8]) ? $matches[8] : array();
			
			$codes = array_merge($codes_1, $codes_2, $codes_3);
			$types = array_merge($types_1, $types_2, $types_3);
			$paths = array_merge($paths_1, $paths_2, $paths_3);
			$projects = array_merge($projects_1, $projects_2, $projects_3);
			$regions = array_merge($regions_1, $regions_2, $regions_3);
			
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) 
				if (isset($paths[$i])) {
					$path = trim($paths[$i]);
					$path_type = self::getArgumentType($path);
					$path = self::prepareArgument($path, $path_type);
					
					$project = isset($projects[$i]) ? trim($projects[$i]) : "";
					$project_type = self::getArgumentType($project);
					$project = self::prepareArgument($project, $project_type);
					
					$includes[] = array(
						"match" => $codes[$i],
						"once" => isset($types[$i]) && strpos($types[$i], "_once") > 0,
						"path" => $path,
						"path_type" => $path_type,
						"project" => $project,
						"project_type" => $project_type,
					);
				}
		}
		
		return $includes;
	}
	
	public static function getFileParams($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getParams( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	//Parse $XXX->getParam("YYY") or $XXX->getParam('YYY')
	public static function getParams($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$params = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, "getParam", $class_obj, $search_limit, $depth);
			//echo "<pre>";print_r($methods);
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					self::prepareMethodParamVariables($method, 0, $param, $param_type);
					
					$params[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"param" => $param,
						"param_type" => $param_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		//echo "<pre>";print_r($params);die();
		return $params;
	}
	
	public static function getFileParamsValues($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getParamsValues( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	//Parse $XXX->setParam("YYY", "aS") or $XXX->setParam('YYY', $as)
	public static function getParamsValues($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$params = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
			$methods = self::getMethodParamsFromContent($contents, "setParam", $class_obj, $search_limit, $depth);
			//echo "<pre>";print_r($methods);die();
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					self::prepareMethodParamVariables($method, 0, $param, $param_type);
					self::prepareMethodParamVariables($method, 1, $value, $value_type);
					
					$params[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"param" => $param,
						"param_type" => $param_type,
						"value" => $value,
						"value_type" => $value_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
		
		//echo "<pre>";print_r($params);die();
		return $params;
	}
	
	public static function getFileBlockParams($file_path) {
		return self::getBlockParams( self::getFileContents($file_path) );
	}
	
	public static function getBlockParams($contents) {
		$params = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/\$block_local_variables(\s*)\[([^\]]*)\]/iu', $contents, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.

			$codes = !empty($matches[0]) ? $matches[0] : array();
			$param_names = !empty($matches[2]) ? $matches[2] : array();
		
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$param = isset($param_names[$i]) ? trim($param_names[$i]) : "";
				$param_type = self::getArgumentType($param);
				$param = self::prepareArgument($param, $param_type);
				
				$params[] = array(
					"param" => $param,
					"param_type" => $param_type,
				);
			}
		}
		
		return $params;
	}
	
	public static function getFileRegionBlockParamsValues($file_path) {
		return self::getRegionBlockParamsValues( self::getFileContents($file_path) );
	}
	
	public static function getRegionBlockParamsValues($contents) {
		$params = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/\$region_block_local_variables(\s*)\[([^\]]*)\](\s*)\[([^\]]*)\](\s*)\[([^\]]*)\](\s*)=(\s*)([^;]+);/iu', $contents, $matches, PREG_OFFSET_CAPTURE); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			$codes = !empty($matches[0]) ? $matches[0] : array();
			
			if ($codes) {
				$region_names = !empty($matches[2]) ? $matches[2] : array();
				$block_names = !empty($matches[4]) ? $matches[4] : array();
				$param_names = !empty($matches[6]) ? $matches[6] : array();
				$param_values = !empty($matches[9]) ? $matches[9] : array();
				
				$regions_blocks = self::getRegionsBlocks($contents);
				
				$t = count($codes);
				for ($i = 0; $i < $t; $i++) {
					$region = isset($region_names[$i][0]) ? trim($region_names[$i][0]) : "";
					$region_type = self::getArgumentType($region);
					$region = self::prepareArgument($region, $region_type);
					
					$block = isset($block_names[$i][0]) ? trim($block_names[$i][0]) : "";
					$block_type = self::getArgumentType($block);
					$block = self::prepareArgument($block, $block_type);
					
					$param = isset($param_names[$i][0]) ? trim($param_names[$i][0]) : "";
					$param_type = self::getArgumentType($param);
					$param = self::prepareArgument($param, $param_type);
					
					$value = isset($param_values[$i][0]) ? trim($param_values[$i][0]) : "";
					$value_type = self::getArgumentType($value);
					$value = self::prepareArgument($value, $value_type);
					
					$line = self::getContentsLineFromOffset($contents, $codes[$i][1]);
					$index = self::getRegionBlockDependencyIndex($regions_blocks, $region, $region_type, $block, $block_type, $line);
					
					$params[] = array(
						"match" => $codes[$i][0],
						"region" => $region,
						"region_type" => $region_type,
						"block" => $block,
						"block_type" => $block_type,
						"param" => $param,
						"param_type" => $param_type,
						"value" => $value,
						"value_type" => $value_type,
						"region_block_index" => $index,
						"line" => $line,
					);
				}
			}
		}
			
		return $params;
	}
	
	public static function setFileBlockSettingsPropertyValue($file_path, $property_path, $property_value) {
		$contents = self::getFileContents($file_path);
		$status = self::setBlockSettingsPropertyValue($contents, $property_path, $property_value);
		//echo $contents;die();
		//echo $file_path;die();
		
		//save code to file
		if ($status && file_put_contents($file_path, $contents) === false)
			$status = false;
		
		//echo file_get_contents($file_path);die();
		return $status;
	}
	
	public static function setBlockSettingsPropertyValue(&$contents, $property_path, $property_value) {
		return self::setMethodParamsFromContent($contents, $property_path, $property_value, "createBlock", false, 0, 2, -1);
	}
	
	public static function getFileCreateBlockParams($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getCreateBlockParams( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getCreateBlockParams($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$params = array();
		//echo "<pre>".$contents;die();
		
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "createBlock", $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $module, $module_type);
				self::prepareMethodParamVariables($method, 1, $block, $block_type);
				self::prepareMethodParamVariables($method, 2, $block_settings, $block_settings_type);
				
				$params[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"module" => $module,
					"module_type" => $module_type,
					"block" => $block,
					"block_type" => $block_type,
					"block_settings" => $block_settings,
					"block_settings_type" => $block_settings_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($params){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($params);die();}
		return $params;
	}
	
	public static function getFileIncludeJoinPoints($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getIncludeJoinPoints( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getIncludeJoinPoints($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$jps = array();
		
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, array("includeJoinPoint", "includeStatusJoinPoint"), $class_obj, $search_limit, $depth);
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $join_point_name, $join_point_name_type);
				self::prepareMethodParamVariables($method, 1, $join_point_settings, $join_point_settings_type);
				self::prepareMethodParamVariables($method, 2, $join_point_description, $join_point_description_type);
				
				$jps[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"method" => isset($method["method"]) ? $method["method"] : null,
					"method_type" => "string",
					"join_point_name" => $join_point_name,
					"join_point_name_type" => $join_point_name_type,
					"join_point_settings" => $join_point_settings,
					"join_point_settings_type" => $join_point_settings_type,
					"join_point_description" => $join_point_description,
					"join_point_description_type" => $join_point_description_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($jps){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($jps);die();}
		return $jps;
	}
	
	public static function getFileAddBlockJoinPoints($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getAddBlockJoinPoints( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getAddBlockJoinPoints($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$jps = array();
		
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "addBlockJoinPoint", $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $block, $block_type);
				self::prepareMethodParamVariables($method, 1, $join_point_name, $join_point_name_type);
				self::prepareMethodParamVariables($method, 2, $join_point_settings, $join_point_settings_type);
				
				$jps[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"method" => isset($method["method"]) ? $method["method"] : null,
					"method_type" => "string",
					"block" => $block,
					"block_type" => $block_type,
					"join_point_name" => $join_point_name,
					"join_point_name_type" => $join_point_name_type,
					"join_point_settings" => $join_point_settings,
					"join_point_settings_type" => $join_point_settings_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($jps){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($jps);die();}
		return $jps;
	}
	
	public static function getFileAddRegionBlockJoinPoints($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getAddRegionBlockJoinPoints( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getAddRegionBlockJoinPoints($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$jps = array();
		
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "addRegionBlockJoinPoint", $class_obj, $search_limit, $depth);
		
		if ($methods) {
			$regions_blocks = self::getRegionsBlocks($contents);
			
			foreach ($methods as $method) {
				if (empty($method["static"])) {
					self::prepareMethodParamVariables($method, 0, $region, $region_type);
					self::prepareMethodParamVariables($method, 1, $block, $block_type);
					self::prepareMethodParamVariables($method, 2, $join_point_name, $join_point_name_type);
					self::prepareMethodParamVariables($method, 3, $join_point_settings, $join_point_settings_type);
					
					$index = self::getRegionBlockDependencyIndex($regions_blocks, $region, $region_type, $block, $block_type, isset($method["line"]) ? $method["line"] : null);
					
					$jps[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"method" => isset($method["method"]) ? $method["method"] : null,
						"method_type" => "string",
						"region" => $region,
						"region_type" => $region_type,
						"block" => $block,
						"block_type" => $block_type,
						"join_point_name" => $join_point_name,
						"join_point_name_type" => $join_point_name_type,
						"join_point_settings" => $join_point_settings,
						"join_point_settings_type" => $join_point_settings_type,
						"region_block_index" => $index,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
				}
			}
		}
			
		//if ($jps){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($jps);die();}
		return $jps;
	}
	
	public static function getFileBlockLocalJoinPoints($file_path) {
		return self::getBlockLocalJoinPoints( self::getFileContents($file_path) );
	}
	
	public static function getBlockLocalJoinPoints($contents) {
		$jps = array();
		
		if ($contents) {
			self::prepareContentsForParsing($contents);
			
			preg_match_all('/\$block_local_join_points(\s*)\[([^\]]*)\](\s*)\[([^\]]*)\]/iu', $contents, $matches, PREG_OFFSET_CAPTURE);//must be capture to return the offset //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			//echo "<pre>";print_r($matches);die();
			
			$codes = !empty($matches[0]) ? $matches[0] : array();
			$blocks = !empty($matches[2]) ? $matches[2] : array();
			$join_point_names = !empty($matches[4]) ? $matches[4] : array();
			
			$t = count($codes);
			for ($i = 0; $i < $t; $i++) {
				$block = isset($blocks[$i][0]) ? trim($blocks[$i][0]) : "";
				$block_type = self::getArgumentType($block);
				$block = self::prepareArgument($block, $block_type);
				$pos = isset($blocks[$i][1]) ? $blocks[$i][1] : null;
				
				self::getVariableFromRegex($contents, $block, $block_type, $pos);
				
				$join_point_name = !empty($join_point_names[$i][0]) ? trim($join_point_names[$i][0]) : "";
				$join_point_name_type = self::getArgumentType($join_point_name);
				$join_point_name = self::prepareArgument($join_point_name, $join_point_name_type);
				
				$jps[] = array(
					"block" => $block,
					"block_type" => $block_type,
					"join_point_name" => $join_point_name,
					"join_point_name_type" => $join_point_name_type,
				);
			}
		}
		
		return $jps;
	}
	
	public static function getFileAddSequentialLogicalActivities($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getAddSequentialLogicalActivities( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getAddSequentialLogicalActivities($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$params = array();
		//echo $contents;die();
		
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "addSequentialLogicalActivities", $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $sla_settings, $sla_settings_type);
				
				$params[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"sla_settings" => $sla_settings,
					"sla_settings_type" => $sla_settings_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($params){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($params);die();}
		return $params;
	}
	
	public static function getFilePageProperties($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getPageProperties( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getPageProperties($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$props = array();
		//echo $contents;die();
		
		//prepare $methods variable
		/*
			$methods_to_search = array(
				"setParseFullHtml" => "parse_full_html", 
				"setParseRegionsHtml" => "parse_regions_html", 
				"setExecuteSLA" => "execute_sla", 
				"setParseHashTags" => "parse_hash_tags", 
				"setParsePTL" => "parse_ptl", 
				"setAddMyJSLib" => "add_my_js_lib", 
				"setAddWidgetResourceLib" => "add_widget_resource_lib", 
				"setFilterByPermission" => "filter_by_permission", 
				"setIncludeBlocksWhenCallingResources" => "include_blocks_when_calling_resources",
				"setInitUserData" => "init_user_data", 
				"setMaximumUsageMemory" => "maximum_usage_memory", 
				"setMaximumBufferChunkSize" => "maximum_buffer_chunk_size"
			);
		*/
		$class = new ReflectionClass("CMSPagePropertyLayer");
		$class_methods = $class->getMethods();
		$methods_to_search = array();
		
		foreach ($class_methods as $ReflectionMethod) {
			$method_name = $ReflectionMethod->getName();
			
			if (substr($method_name, 0, 3) == "set") {
				$params = $ReflectionMethod->getParameters();
				$ReflectionParameter = isset($params[0]) ? $params[0] : null;
				$param_name = $ReflectionParameter ? $ReflectionParameter->getName() : "";
				$default_value = $ReflectionParameter && $ReflectionParameter->isOptional() ? $ReflectionParameter->getDefaultValue() : null;
				$default_value_type = is_string($default_value) ? "string" : "";
				
				$methods_to_search[$method_name] = array($param_name, $default_value, $default_value_type);
			}
		}
		//echo "<pre>";print_r($methods_to_search);die();
		
		//find methods in php code
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, array_keys($methods_to_search), $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $prop_value, $prop_value_type);
				
				$prop_name = "";
				$prop_default_value = "";
				$prop_default_value_type = "";
				
				if (!empty($method["method"]))
					foreach ($methods_to_search as $method_name => $method_prop)
						if ($method_name == $method["method"]) {
							$prop_name = $method_prop[0];
							$prop_default_value = $method_prop[1];
							$prop_default_value_type = $method_prop[2];
							break;
						}
				
				if ($prop_name)
					$props[] = array(
						"match" => isset($method["match"]) ? $method["match"] : null,
						"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
						"method" => isset($method["method"]) ? $method["method"] : null,
						"method_type" => "string",
						"prop_name" => $prop_name,
						"prop_value" => $prop_value,
						"prop_value_type" => $prop_value_type,
						"prop_default_value" => $prop_default_value,
						"prop_default_value_type" => $prop_default_value_type,
						"line" => isset($method["line"]) ? $method["line"] : null,
					);
			}
		}
		
		//if ($props){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($props);die();}
		return $props;
	}
	
	public static function getFileAddEntities($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getAddEntities( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getAddEntities($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$entities = array();
		//echo $contents;die();
		
		//find methods in php code
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "addEntity", $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $entity_code, $entity_code_type);
				self::prepareMethodParamVariables($method, 1, $entity_params, $entity_params_type);
				
				$entities[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"method" => isset($method["method"]) ? $method["method"] : null,
					"method_type" => "string",
					"entity_code" => $entity_code,
					"entity_code_type" => $entity_code_type,
					"entity_params" => $entity_params,
					"entity_params_type" => $entity_params_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($entities){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($entities);die();}
		return $entities;
	}
	
	public static function getFileAddTemplates($file_path, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		return self::getAddTemplates( self::getFileContents($file_path), $class_obj_name, $search_limit, $depth);
	}
	
	public static function getAddTemplates($contents, $class_obj_name = false, $search_limit = 0, $depth = -1) {
		$templates = array();
		//echo $contents;die();
		
		//find methods in php code
		self::prepareContentsForParsing($contents);
		
		$class_obj = $class_obj_name && substr($class_obj_name, 0, 1) != '$' && substr($class_obj_name, 0, 2) != '@$' ? '$' . $class_obj_name : $class_obj_name;
		$methods = self::getMethodParamsFromContent($contents, "addTemplate", $class_obj, $search_limit, $depth);
		//echo "<pre>";print_r($methods);die();
		
		foreach ($methods as $method) {
			if (empty($method["static"])) {
				self::prepareMethodParamVariables($method, 0, $template_code, $template_code_type);
				self::prepareMethodParamVariables($method, 1, $template_params, $template_params_type);
				
				$templates[] = array(
					"match" => isset($method["match"]) ? $method["match"] : null,
					"class_obj" => isset($method["class_obj"]) ? $method["class_obj"] : null,
					"method" => isset($method["method"]) ? $method["method"] : null,
					"method_type" => "string",
					"template_code" => $template_code,
					"template_code_type" => $template_code_type,
					"template_params" => $template_params,
					"template_params_type" => $template_params_type,
					"line" => isset($method["line"]) ? $method["line"] : null,
				);
			}
		}
		
		//if ($templates){echo "<textarea>$contents</textarea>";echo "<pre>";print_r($templates);die();}
		return $templates;
	}
	
	/* UTILS */
	
	private static function prepareContentsForParsing(&$contents) {
		//remove php comments from $contents, bc if the code was generated from the workflow, it will create comments that will mess up with this method.
		$contents = PHPCodePrintingHandler::getCodeWithoutComments($contents);
	}
	
	private static function getRegionBlockDependencyIndex($regions_blocks, $region, $region_type, $block, $block_type, $line) {
		$index = 0;
		
		if ($regions_blocks)
			foreach ($regions_blocks as $region_block) {
				$rb_region = isset($region_block["region"]) ? $region_block["region"] : null;
				$rb_region_type = isset($region_block["region_type"]) ? $region_block["region_type"] : null;
				$rb_block = isset($region_block["block"]) ? $region_block["block"] : null;
				$rb_block_type = isset($region_block["block_type"]) ? $region_block["block_type"] : null;
				
				if ($rb_region == $region && $rb_region_type == $region_type && $rb_block == $block && $rb_block_type == $block_type) {
					if (isset($region_block["line"]) && $region_block["line"] > $line)
						return $index;
					
					$index++;
				}
			}
		
		return $index;
	}
	
	private static function getContentsLineFromOffset($contents, $offset) {
		$line = 1;
		$pos = -1;
		do {
			$pos = strpos($contents, "\n", $pos + 1);
			
			if ($pos > $offset)
				break;
			
			$line++;
		}
		while ($pos !== false);
		
		return $line;
	}
	
	private static function prepareMethodParamVariables($method, $param_idx, &$var_value, &$var_type) {
		$var_value = $var_type = null;
		
		if ($method && !empty($method["params"])) {
			$param = isset($method["params"][$param_idx]) ? $method["params"][$param_idx] : null;
			
			if (isset($param["type"]) && $param["type"] == "variable") {
				$var_value = isset($param["referenced_value"]) ? $param["referenced_value"] : null;
				$var_type = isset($param["referenced_type"]) ? $param["referenced_type"] : null;
			}
			else {
				$var_value = isset($param["value"]) ? $param["value"] : null;
				$var_type = isset($param["type"]) ? $param["type"] : null;
			}
		}
	}
	
	public static function setMethodParamsFromContent(&$contents, $property_path, $property_value, $method_names, $class_obj = false, $search_method_index = 0, $search_argument_index = 0, $depth = -1) {
		$status = false;
		
		//prepare property_path removing extra slashes
		$property_path = preg_replace("/\/+/", "/", $property_path);
		$property_path = preg_replace("/^[\s\/]*/", "", $property_path);
		$property_path = preg_replace("/[\s\/]*$/", "", $property_path);
		
		if ($property_path) {
			//preparing property_value
			$property_code = '<? $aux = ' . var_export($property_value, true) . '; ?>';
			$property_stmts = self::getContentStmts($property_code);
			//echo "<pre>";print_r($property_stmts);die();
			$new_item_value = $property_stmts && !empty($property_stmts[0]) && strtolower($property_stmts[0]->getType()) == "expr_assign" && !empty($property_stmts[0]->var) && isset($property_stmts[0]->expr) ? $property_stmts[0]->expr : null;
			//echo "<pre>";print_r($new_item_value);die();
			
			//checking if new_item_value is valid
			if (is_object($new_item_value)) {
				//converting property_path into array
				$item_path_parts = explode("/", $property_path);
				
				//parseing file php code
				//echo "<pre>$contents";die();
				$stmts = self::getContentStmts($contents);
				//echo "<pre>";print_r($stmts);die();
				
				$PHPParserPrettyPrinter = new PHPParserPrettyPrinter();
				
				//setting new value in correspondent argument
				$status = self::setMethodParamsFromStmts($PHPParserPrettyPrinter, $stmts, $item_path_parts, $new_item_value, "createBlock", false, 0, 2, -1);
				
				//print new code
				if ($status) {
					$PHPCodePrettyPrinter = new PHPParserPrettyPrinterStandard();
					$new_contents = $PHPCodePrettyPrinter->nativeStmtsPrettyPrint($stmts);
					
					//prettyPrint removes the php open and close tags when is a pure php file, so we must add them
					if (substr(trim($contents), 0, 2) == "<?" && substr(trim($new_contents), 0, 2) != "<?")
						$new_contents = "<?php\n" . $new_contents;
					
					if (substr(trim($contents), -2) == "?>" && substr(trim($new_contents), -2) != "?>")
						$new_contents = trim($new_contents) . "\n?>";
					
					//beautify php code
					$PHPCodeBeautifier = new PHPCodeBeautifier();
					$new_contents = $PHPCodeBeautifier->beautifyCode($new_contents);
					//echo $PHPCodeBeautifier->getError();
					//print_r($PHPCodeBeautifier->getIssues());
					//echo "<pre>new contents:$new_contents";die();
					
					//replace \n inside of double quotes variables, bc prettyPrint escapes the end lines.
					$new_contents = TextSanitizer::replaceEscapedEndLinesInsideOfPHPDoubleQuotesInHtmlCode($new_contents);
					//echo "<pre>new contents:$new_contents";die();
					
					//update contents
					$contents = $new_contents;
				}
			}
		}
		
		return $status;
	}
	
	private static function getMethodParamsFromContent($contents, $method_names, $class_obj = false, $search_limit = 0, $depth = -1) {
		$stmts = self::getContentStmts($contents);
		//echo "<pre>";print_r($stmts);die();
		
		$PHPParserPrettyPrinter = new PHPParserPrettyPrinter();
		$PHPParserPrettyPrinter->disableNoIndentToken(); //very important, otherwise it will add a weird code like an _NO_INDENT_ string.
		
		$params = self::getMethodParamsFromStmts($PHPParserPrettyPrinter, $stmts, $method_names, $class_obj, $search_limit, $depth);
		//error_log(print_r($params, 1), 3, '/tmp/method_params_test.log');
		//echo "<pre>";print_r($params);die();
		
		return $params;
	}
	
	private static function getContentStmts($contents) {
		/*$contents = '<?php
		switch($X) {
			case 1: $x=1; break;
			case 2: $x=2; break;
			default:$x=0; 
		}
		
		if ($as = 123) {
			$asd=123;
		}
		elseif (1 ==1) {
			$b=1;
		}
		elseif (2 ==2) {
			$d=1;
		}
		else {
			$a=1;
		}
		?>';*/
		
		$PHPMultipleParser = new PHPMultipleParser();
		
		/*

		 * The $PHPMultipleParser->parse($contents) parses the '\n' as end-lines but we dont' want this, bc we can have the code: 
		 	$x = '<script>modal.find(".modal-body").html(msg.replace(/\n/g, "<br>"));</script>';
		 Without this the $PHPMultipleParser->parse will convert the stmts to:
		 	$x = '<script>modal.find(".modal-body").html(msg.replace(/
/g, "<br>"));</script>';
		 */
		//$contents = str_replace('\n', '\\\n', $contents); //2019-10-17: DO NOT USE str_replace because we only to escape the end-lines which are not escaped.
		$contents = TextSanitizer::replaceIfNotEscaped('\n', '\\\n', $contents);
		
		//echo "<textarea>$contents</textarea>";die();
		//TODO: replace '\n' that are not escaped with something
		$stmts = $PHPMultipleParser->parse($contents);
		//TODO loop stmts and inner stmts and replace back to '\n'
		//echo "<pre>";print_r($stmts);die();
		
		//convert stmts expression to simple stmts
		$stmts = self::convertStmtsExpressionToSimpleStmts($stmts);
		//echo "<pre>";print_r($stmts);die();
		
		return $stmts;
	}
	
	private static function getMethodParamsFromStmts($PHPParserPrettyPrinter, $stmts, $method_names, $class_obj = false, $search_limit = 0, $depth = -1, $variables_stmts = null) {
		$methods = array();
		
		$method_names = is_array($method_names) ? $method_names : array($method_names);
		foreach ($method_names as $idx => $method_name)
			$method_names[$idx] = strtolower($method_name);
		
		$stmts_count = $stmts ? count($stmts) : 0;
		$variables_stmts = $variables_stmts ? $variables_stmts : array();
		
		//echo "stmts_count:$stmts_count\n";
		//if ($stmts_count && in_array("includejoinpoint", $method_names)){echo "<pre>HERE:";print_r($stmts);die();}
		
		for ($i = 0; $i < $stmts_count; $i++) {
			$stmt = $stmts[$i];
			$stmt = self::convertStmtExpressionToSimpleStmt($stmt);
			$stmt_type = strtolower($stmt->getType());
			
			//prepare variables_stmts - Do not set the $variables_stmts outside this loop because we only want the variables inited before this current $stmts[$i], otherwise when we call the self::getVariableFromStmts method, we can get variables that were inited after the $class_obj->$method_names gets called, And that is not what we want. This is very important bc in case we have multiple variables set, one for each call "$class_obj->$method_names", then we only get the latest variable set. And that is not what we want, this is: if we have the code: '$x=0;foo($x);$x=1;foo($x);' we want the $variables_stmts to reference first the $x=0 and then the $x=1; If we move this outside of this loop, we will always get $x=1; for both foo methods, which is incorrect.
			if ($stmt_type == "expr_assign") {
				$stmt_var = isset($stmt->var) ? $stmt->var : null;
				$var_code = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt_var, false);
				$var_code_type = self::getArgumentType($var_code);
				
				if ($var_code_type == "variable")
					$variables_stmts[] = $stmt;
			}
			
			//prepare stmt
			if ($stmt_type == "expr_assign" || $stmt_type == "expr_assignop_concat" || $stmt_type == "expr_assignop_plus" || $stmt_type == "expr_assignop_minus") {
				$stmt = $stmt->expr;
				$stmt_type = strtolower($stmt->getType());
			}
			else if ($stmt_type == "stmt_echo") {
				$stmt = isset($stmt->exprs[0]) ? $stmt->exprs[0] : null;
				$stmt_type = $stmt ? strtolower($stmt->getType()) : "";
			}
			else if ($stmt_type == "expr_include") {
				$stmt = $stmt->expr;
				$stmt_type = strtolower($stmt->getType());
			}
			
			//parse var if a method/function call
			$stmt_var = isset($stmt->var) ? $stmt->var : null;
			
			if ($stmt_var) {
				$stmt_var_type = strtolower($stmt_var->getType());
				
				if ($stmt_var_type == "expr_methodcall" || $stmt_var_type == "expr_staticcall" || $stmt_var_type == "expr_funccall") {
					//echo "getMethodParamsFromStmts:".$PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt_var, false)."\n";
					
					$items = self::getMethodParamsFromStmts($PHPParserPrettyPrinter, array($stmt_var), $method_names, $class_obj, $search_limit, $depth, $variables_stmts);
					$methods = array_merge($methods, $items);
					
					if ($search_limit > 0 && $search_limit <= count($methods))
						break;
				}
			}
			
			//parse expr if a method/function call
			if ($stmt_type == "expr_methodcall" || $stmt_type == "expr_staticcall" || $stmt_type == "expr_funccall") {
				$func_name = self::printCodeNodeName($PHPParserPrettyPrinter, $stmt);
				//echo "func_name:$func_name\n<br/>";
				
				if ($func_name && is_string($func_name) && in_array(strtolower($func_name), $method_names)) {
					//Getting obj_name
					$obj = null;
					if (!empty($stmt->var))
						$obj = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt->var, false);
					else
						$obj = self::printCodeNodeName($PHPParserPrettyPrinter, isset($stmt->class) ? $stmt->class : null);
					
					//Checking obj_name
					if (!$class_obj || $class_obj == $obj) {
						$stmt_args = isset($stmt->args) ? $stmt->args : null;
						$params = array();
						
						//preparing params
						if (is_array($stmt_args)) {
							foreach ($stmt_args as $idx => $stmt_arg) {
								$stmt_arg_type = strtolower($stmt_arg->value->getType());
								$var_name = $referenced_value = $referenced_type = null;
								
								if ($stmt_arg_type == "expr_assign" || $stmt_arg_type == "expr_assignop_concat" || $stmt_arg_type == "expr_assignop_plus" || $stmt_arg_type == "expr_assignop_minus") {
									$var_name = isset($stmt_arg->value->var->name) ? $stmt_arg->value->var->name : null;
									$stmt_arg->value = isset($stmt_arg->value->expr) ? $stmt_arg->value->expr : null;
								}
							
								if (!empty($stmt_arg->value->items)) {
									$value = self::getMethodParamsArrayItems($PHPParserPrettyPrinter, $stmt_arg->value->items);
									$value_type = "array";
								}
								else {
									//$arg_type = strtolower($stmt_arg->value->getType());
									
								//echo "!<pre>";print_r($stmt_arg);echo "</pre>!";
									$value = $PHPParserPrettyPrinter->printArg($stmt_arg);
									$value_type = self::getArgumentType($value);
								//echo "!<pre>value $value_type:";print_r($value);echo "</pre>!";
									$value = self::prepareArgument($value, $value_type);
									
									//if ($arg_type == "expr_variable") { //Do not use arg_type bc a variable can be more than: expr_variable
									if ($value_type == "variable") {
								//echo "!<pre>stmt_arg:";print_r($stmt_arg->value->getType());echo "</pre>!";die();
										$referenced_value = $value;
										$referenced_type = $value_type;
								//echo "!<pre>referenced $referenced_type:";print_r($referenced_value);echo "</pre>!";
								//echo "<pre>";print_r($variables_stmts);echo "</pre>";
										self::getVariableFromStmts($PHPParserPrettyPrinter, $variables_stmts, $referenced_value, $referenced_type);
								//echo "!<pre>referenced $referenced_type:";print_r($referenced_value);echo "</pre>!";
									}
								}
								
								$param = array(
									"value" => $value,
									"type" => $value_type,
								);
								
								if (isset($referenced_value)) {
									$param["referenced_value"] = $referenced_value;
									$param["referenced_type"] = $referenced_type;
								}
							
								if ($var_name)
									$param["var"] = $var_name;
							
								$params[] = $param;
							}
						}
					}
					
					$method = array(
						"match" => $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt, false),
						"method" => $func_name,
						"params" => isset($params) ? $params : null,
						"line" => $stmt->getAttribute("startLine"),
					);
					
					if ($obj) {
						$method["class_obj"] = $obj;
						$method["static"] = $stmt_type == "expr_staticcall";
					}
					
					$methods[] = $method;
				}
			}
			
			if ($search_limit > 0 && $search_limit <= count($methods))
				break;
			else if ($depth <= -1 || $depth > 0) {
				//prepare inner tasks
				$sub_stmts_groups = !empty($stmt->stmts) ? array($stmt) : array();
				
				if ($stmt_type == "stmt_if") {
					//prepare conditions bc there might be some $method_names calls in the conditions
					$stmt_cond = isset($stmt->cond) ? $stmt->cond : null;
					$stmt_cond = self::convertStmtConditionInStmt($stmt_cond);
					
					if ($stmt_cond)
						$sub_stmts_groups[] = $stmt_cond;
					
					if (!empty($stmt->elseifs)) {
						//prepare conditions bc there might be some $method_names calls in the conditions
						for ($j = 0, $tj = count($stmt->elseifs); $j < $tj; $j++) {
							$stmt_cond = isset($stmt->elseifs[$j]->cond) ? $stmt->elseifs[$j]->cond : null;
							$stmt_cond = self::convertStmtConditionInStmt($stmt_cond);
							
							if ($stmt_cond)
								$sub_stmts_groups[] = $stmt_cond;
						}
						
						//prepare elseifs inner tasks
						$sub_stmts_groups = array_merge($sub_stmts_groups, $stmt->elseifs);
					}
					
					if (!empty($stmt->else->stmts))
						$sub_stmts_groups[] = $stmt->else;
				}
				else if ($stmt_type == "stmt_switch" && !empty($stmt->cases)) {
					//prepare conditions bc there might be some $method_names calls in the conditions
					$stmt_cond = isset($stmt->cond) ? $stmt->cond : null;
					$stmt_cond = self::convertStmtConditionInStmt($stmt_cond);
					
					if ($stmt_cond)
						$sub_stmts_groups[] = $stmt_cond;
					
					//prepare cases
					if (!empty($stmt->cases))
						for ($j = 0, $tj = count($stmt->cases); $j < $tj; $j++) {
							$stmt_cond = isset($stmt->cases[$j]->cond) ? $stmt->cases[$j]->cond : null;
							$stmt_cond = self::convertStmtConditionInStmt($stmt_cond);
							
							if ($stmt_cond)
								$sub_stmts_groups[] = $stmt_cond;
						}
					
					$sub_stmts_groups = array_merge($sub_stmts_groups, $stmt->cases);
					//echo "<pre>";print_r($sub_stmts_groups);
				}
				else if ($stmt_type == "stmt_trycatch" && !empty($stmt->catches)) {
					//echo "<pre>";print_r($stmt);
					$sub_stmts_groups = array_merge($sub_stmts_groups, $stmt->catches);
				}
				else if ($stmt_type == "expr_binaryop_concat" && (!empty($stmt->left) || !empty($stmt->right))) {//in case of echo "asd" . $EVC->foo()
					$stmt->stmts = array();
					
					if (!empty($stmt->left))
						$stmt->stmts[] = $stmt->left;
					
					if (!empty($stmt->right))
						$stmt->stmts[] = $stmt->right;
					
					$sub_stmts_groups[] = $stmt;
				}
				else if ($stmt_type == "expr_methodcall" || $stmt_type == "expr_staticcall" || $stmt_type == "expr_funccall") {
					$func_name = self::printCodeNodeName($PHPParserPrettyPrinter, $stmt);
					
					//a method can be called inside of another method
				 	if ($func_name && is_string($func_name) && !in_array(strtolower($func_name), $method_names) && !empty($stmt->args)) {
						$stmt->stmts = array();
						
						foreach ($stmt->args as $arg)
							if (!empty($arg->value))
								$stmt->stmts[] = $arg->value;
						
						$sub_stmts_groups[] = $stmt;
					}
				}
			
				if ($sub_stmts_groups) {	
					$new_search_limit = $search_limit > 0 ? $search_limit - count($methods) : 0;//it will be always bigger than 0
					$new_depth = $depth > 0 ? $depth - 1 : $depth;
					
					$t = count($sub_stmts_groups);
					for ($j = 0; $j < $t; $j++)
						if (!empty($sub_stmts_groups[$j]->stmts)) {
							$items = self::getMethodParamsFromStmts($PHPParserPrettyPrinter, $sub_stmts_groups[$j]->stmts, $method_names, $class_obj, $new_search_limit, $new_depth, $variables_stmts);
							$methods = array_merge($methods, $items);
							
							$new_search_limit = $search_limit > 0 ? $new_search_limit - count($items) : 0;
							
							if ($search_limit > 0 && $new_search_limit <= 0) 
								break;
						}
				}
			}
		}
		
		return $methods;
	}
	
	private static function convertStmtConditionInStmt($cond) {
		$tmp_stmt = null;
		
		if ($cond) {
			$cond = self::convertStmtExpressionToSimpleStmt($cond);
			
			if (empty($cond->stmts)) {
				$tmp_stmt = new stdClass();
				
				if (!empty($cond->expr)) //for expr_booleannot
					$tmp_stmt->stmts = array($cond->expr);
				else
					$tmp_stmt->stmts = array($cond);
			}
			else
				$tmp_stmt = $cond;
		}
		
		return $tmp_stmt;
	}
	
	private static function convertStmtExpressionToSimpleStmt($stmt) {
		if ($stmt && is_object($stmt) && method_exists($stmt, "getType") && strtolower($stmt->getType()) == "stmt_expression" && isset($stmt->expr)) {
			if ($stmt->hasAttribute("comments")) {
				$comments = $stmt->expr->getAttribute("comments");
				
				if (!$comments)
					$comments = array();
				
				if ($stmt->getAttribute("comments"))
					$comments = array_merge($stmt->getAttribute("comments"), $comments);
				
				$stmt->expr->setAttribute("comments", $comments);
			}
			
			$stmt = $stmt->expr;
		}
		
		return $stmt;
	}
	
	private static function convertStmtsExpressionToSimpleStmts($stmts) {
		if ($stmts)
			foreach ($stmts as $idx => $stmt)
				$stmts[$idx] = self::convertStmtExpressionToSimpleStmt($stmt);
		
		return $stmts;
	}
	
	private static function setMethodParamsFromStmts($PHPParserPrettyPrinter, $stmts, $item_path_parts, $new_item_value, $method_names, $class_obj = false, $search_method_index = 0, $search_argument_index = 0, $depth = -1, $variables_stmts = null, $found_method = false) {
		$status = false;
		
		$stmts = self::convertStmtsExpressionToSimpleStmts($stmts);
		
		$method_names = is_array($method_names) ? $method_names : array($method_names);
		foreach ($method_names as $idx => $method_name)
			$method_names[$idx] = strtolower($method_name);
		
		$stmts_count = $stmts ? count($stmts) : 0;
		$variables_stmts = $variables_stmts ? $variables_stmts : array();
		
		for ($i = 0; $i < $stmts_count; $i++) {
			$stmt = $stmts[$i];
			$stmt = self::convertStmtExpressionToSimpleStmt($stmt);
			$stmt_type = strtolower($stmt->getType());
			
			if ($stmt_type == "expr_assign") {
				$stmt_var = isset($stmt->var) ? $stmt->var : null;
				$var_code = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt_var, false);
				$var_code_type = self::getArgumentType($var_code);
				
				if ($var_code_type == "variable")
					$variables_stmts[] = $stmt;
			}
		}
		
		for ($i = 0; $i < $stmts_count; $i++) {
			$stmt = &$stmts[$i];
			$stmt_type = strtolower($stmt->getType());
			
			if ($stmt_type == "stmt_expression") {
				$stmt = &$stmt->expr;
				$stmt_type = strtolower($stmt->getType());
			}
			else if ($stmt_type == "expr_assign" || $stmt_type == "expr_assignop_concat" || $stmt_type == "expr_assignop_plus" || $stmt_type == "expr_assignop_minus") {
				$stmt = &$stmt->expr;
				$stmt_type = strtolower($stmt->getType());
			}
			else if ($stmt_type == "stmt_echo") {
				$stmt = &$stmt->exprs[0];
				$stmt_type = $stmt ? strtolower($stmt->getType()) : "";
			}
			
			if ($stmt_type == "expr_methodcall" || $stmt_type == "expr_staticcall" || $stmt_type == "expr_funccall") {
				$func_name = self::printCodeNodeName($PHPParserPrettyPrinter, $stmt);
				
				if ($func_name && is_string($func_name) && in_array(strtolower($func_name), $method_names)) {
					//Getting obj_name
					$obj = null;
					if (!empty($stmt->var))
						$obj = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt->var, false);
					else
						$obj = self::printCodeNodeName($PHPParserPrettyPrinter, isset($stmt->class) ? $stmt->class : null);
					
					//Checking obj_name
					if (!$class_obj || $class_obj == $obj) {
						if ($search_method_index <= 0) {
							$found_method = true;
							$stmt_args = isset($stmt->args) ? $stmt->args : null;
							
							//preparing params
							if (is_array($stmt_args)) {
								$stmt_arg = isset($stmt_args[$search_argument_index]) ? $stmt_args[$search_argument_index] : null;
								
								if ($stmt_arg) {
									$stmt_arg_type = strtolower($stmt_arg->value->getType());
									$var_name = $referenced_value = $referenced_type = null;
									
									if ($stmt_arg_type == "expr_assign" || $stmt_arg_type == "expr_assignop_concat" || $stmt_arg_type == "expr_assignop_plus" || $stmt_arg_type == "expr_assignop_minus") {
										$status = self::setMethodParamStmtExprValue($PHPParserPrettyPrinter, $variables_stmts, $item_path_parts, $new_item_value, $stmt_arg->value->expr);
									}
									else {
										$status = self::setMethodParamStmtExprValue($PHPParserPrettyPrinter, $variables_stmts, $item_path_parts, $new_item_value, $stmt_arg->value);
									}
									
									if ($status)
										$stmt->args[$search_argument_index] = $stmt_arg;
								}
							}
							
							break;
						}
						
						$search_method_index--;
					}
				}
			}
			
			if ($found_method)
				break;
			else if ($depth <= -1 || $depth > 0) {
				$sub_stmts_groups = !empty($stmt->stmts) ? array($stmt) : array();
				
				if ($stmt_type == "stmt_if") {
					if (!empty($stmt->elseifs)) {
						foreach ($stmt->elseifs as $idx => $child)
							$sub_stmts_groups[] = &$stmt->elseifs[$idx];
					}
					
					if (!empty($stmt->else->stmts))
						$sub_stmts_groups[] = &$stmt->else;
				}
				else if ($stmt_type == "stmt_switch" && !empty($stmt->cases)) {
					foreach ($stmt->cases as $idx => $child)
						$sub_stmts_groups[] = &$stmt->cases[$idx];
				}
				else if ($stmt_type == "stmt_trycatch" && !empty($stmt->catches)) {
					foreach ($stmt->catches as $idx => $child)
						$sub_stmts_groups[] = &$stmt->catches[$idx];
				}
				else if ($stmt_type == "expr_binaryop_concat" && (!empty($stmt->left) || !empty($stmt->right))) {//in case of echo "asd" . $EVC->foo()
					$stmt_aux = new stdClass();
					$stmt_aux->stmts = array();
					
					if (!empty($stmt_aux->left))
						$stmt_aux->stmts[] = &$stmt->left;
					
					if (!empty($stmt_aux->right))
						$stmt_aux->stmts[] = &$stmt->right;
					
					$sub_stmts_groups[] = $stmt_aux;
				}
				else if ($stmt_type == "expr_methodcall" || $stmt_type == "expr_staticcall" || $stmt_type == "expr_funccall") {
					$func_name = self::printCodeNodeName($PHPParserPrettyPrinter, $stmt);
					
					//a method can be called inside of another method
				 	if ($func_name && is_string($func_name) && !in_array(strtolower($func_name), $method_names) && !empty($stmt->args)) {
						$stmt_aux = new stdClass();
						$stmt_aux->stmts = array();
						
						foreach ($stmt->args as $arg)
							if (!empty($arg->value))
								$stmt_aux->stmts[] = &$arg->value;
						
						$sub_stmts_groups[] = $stmt_aux;
					}
				}
			
				if ($sub_stmts_groups) {	
					$new_depth = $depth > 0 ? $depth - 1 : $depth;
					
					$t = count($sub_stmts_groups);
					for ($j = 0; $j < $t; $j++)
						if (!empty($sub_stmts_groups[$j]->stmts)) {
							$status = self::setMethodParamsFromStmts($PHPParserPrettyPrinter, $sub_stmts_groups[$j]->stmts, $item_path_parts, $new_item_value, $method_names, $class_obj, $search_method_index, $search_argument_index, $new_depth, $variables_stmts, $found_method);
							
							if ($found_method) 
								break;
						}
				}
			}
		}
		
		return $status;
	}
	
	private static function setMethodParamStmtExprValue($PHPParserPrettyPrinter, &$variables_stmts, $item_path_parts, $new_item_value, &$stmt_expr) {
		$status = false;
		
		$variables_stmts = self::convertStmtsExpressionToSimpleStmts($variables_stmts);
		$stmt_expr = self::convertStmtExpressionToSimpleStmt($stmt_expr);
		
		//$expr_type = strtolower($stmt_expr->getType());
		$value = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt_expr, false);
		$value_type = self::getArgumentType($value);
		//echo "!<pre>value $value_type:";print_r($value);echo "</pre>!";
		$value = self::prepareArgument($value, $value_type);
		
		if (!empty($stmt_expr->items)) {
			$status = self::setMethodParamsArrayItems($PHPParserPrettyPrinter, $stmt_expr->items, $item_path_parts, $new_item_value);
		}
		//else if ($expr_type == "expr_variable") { //Do not use expr_type bc a variable can be more than: expr_variable
		else if ($value_type == "variable") {	
			$status = self::setVariableFromStmts($PHPParserPrettyPrinter, $variables_stmts, $item_path_parts, $new_item_value, $value, $value_type);
		}
		else if (count($item_path_parts) == 1 && empty(strlen($item_path_parts[0]))) { //only change it if the item_path_parts is empty
			$value_attributes = $stmt_expr->getAttributes();
			$stmt_expr = $new_item_value;
			
			if ($value_attributes)
				foreach ($value_attributes as $k => $v)
					$stmt_expr->setAttribute($k, $v);
			
			//echo "<pre>";print_r($stmt_expr);die();
			$status = true;
		}
		
		return $status;
	}
	
	private static function printCodeNodeName($PHPParserPrettyPrinter, $node) {
		if (is_object($node)) {
			if (isset($node->name) && is_object($node->name)) {
				$node_type = strtolower($node->name->getType());
				
				if ($node_type == "name")
					return $PHPParserPrettyPrinter->printName($node->name);
				else if ($node_type == "identifier") //JP: Identifier type added in 20-09-2024
					return $PHPParserPrettyPrinter->printIdentifier($node->name);
			}
			
			if (isset($node->parts) && is_array($node->parts))
				return $PHPParserPrettyPrinter->printName($node);
			
			return isset($node->name) ? $node->name : null;
		}
		return $node;
	}
	
	private static function getVariableFromStmts($PHPParserPrettyPrinter, $stmts, &$var, &$var_type) {
		if ($var_type == "variable") {
			$stmts = self::convertStmtsExpressionToSimpleStmts($stmts);
			
			//get last stmt variable initialized
			for ($i = count($stmts) - 1; $i >= 0; $i--) {
				$stmt = $stmts[$i];
				
				if (strtolower($stmt->getType()) == "expr_assign" && !empty($stmt->var)) {
					$v = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt->var, false);
					
					if ($v == '$' . $var || $v == '@$' . $var) {
						if (!empty($stmt->expr->items)) {
							$var = self::getMethodParamsArrayItems($PHPParserPrettyPrinter, $stmt->expr->items);
							$var_type = "array";
						}
						else {
							$expr = isset($stmt->expr) ? $stmt->expr : null;
							$var = $PHPParserPrettyPrinter->nodeExprPrettyPrint($expr, false);
							$var_type = self::getArgumentType($var);
							$var = self::prepareArgument($var, $var_type);
						}
						
						break;
					}
				}
			}
		}
	}
	
	private static function setVariableFromStmts($PHPParserPrettyPrinter, &$stmts, $item_path_parts, $new_item_value, $var, $var_type) {
		$status = false;
		
		if ($var_type == "variable") {
			$stmts = self::convertStmtsExpressionToSimpleStmts($stmts);
			
			$t = count($stmts);
			for ($i = 0; $i < $t; $i++) {
				$stmt = $stmts[$i];
				
				if (strtolower($stmt->getType()) == "expr_assign" && !empty($stmt->var)) {
					$v = $PHPParserPrettyPrinter->nodeExprPrettyPrint($stmt->var, false);
				
					if ($v == '$' . $var || $v == '@$' . $var) {
						$value = isset($stmt->expr) ? $stmt->expr : null;
						
						if (count($item_path_parts) == 1 && empty(strlen($item_path_parts[0]))) { //only change it if the item_path_parts is empty
							//echo "<pre>";print_r($value);die();
							$value_attributes = $value->getAttributes();
							$value = $new_item_value;
							
							if ($value_attributes)
								foreach ($value_attributes as $k => $v)
									$value->setAttribute($k, $v);
							
							//echo "<pre>";print_r($value);die();
							$status = true;
						}
						else if (!empty($value->items) && self::setMethodParamsArrayItems($PHPParserPrettyPrinter, $value->items, $item_path_parts, $new_item_value))
							$status = true;
						
						if ($status)
							$stmts[$i]->expr = $value;
						
						break;
					}
				}
			}
		}
		
		return $status;
	}
	
	private static function getVariableFromRegex($contents, &$var, &$var_type, $idx = null) {
		if ($var_type == "variable") {
			if ($idx)
				$contents = substr($contents, 0, $idx);
			
			preg_match_all('/\$' . preg_quote($var, "/") . '(\s*)=/u', $contents, $matches, PREG_OFFSET_CAPTURE); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			if ($matches) {
				$matches = $matches[0];
				$matches = $matches[ count($matches) - 1 ];
				$str = $matches[0];
				$pos = $matches[1] + strlen($str);
				
				$t = strlen($contents);
				
				if ($str && $pos < $t) {
					$open_single_quotes = $open_double_quotes = false;
					$value = "";
					
					for ($i = $pos; $i < $t; $i++) {
						$char = $contents[$i];
					
						if ($char == "'" && !$open_double_quotes && !TextSanitizer::isCharEscaped($contents, $i))
							$open_single_quotes = !$open_single_quotes;
						else if ($char == '"' && !$open_single_quotes && !TextSanitizer::isCharEscaped($contents, $i))
							$open_double_quotes = !$open_double_quotes;
					
						if (!$open_single_quotes && !$open_double_quotes && $char == ";")
							break;
					
						$value .= $char;
					}
				
					$var = trim($value);
					$var_type = self::getArgumentType($var);
					$var = self::prepareArgument($var, $var_type);
				}
			}
		}
	}
	
	private static function getMethodParamsArrayItems($PHPParserPrettyPrinter, $items) {
		$props = array();
		
		$items = self::convertStmtExpressionToSimpleStmt($items);
		
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			$key = isset($item->key) ? $item->key : null;
			$value = isset($item->value) ? $item->value : null;
			
			$key_type = is_object($key) ? strtolower($key->getType()) : null;
			$value_type = is_object($value) ? strtolower($value->getType()) : null;
			
			if ($key_type) {
				$key = $PHPParserPrettyPrinter->nodeExprPrettyPrint($key, false);
				$key = self::getStmtValueAccordingWithType($key, $key_type);
				
				$key_type = strtolower($item->key->getType());
				$key_type = $key_type == "scalar_string" || $key_type == "scalar_encapsed" || $key_type == "scalar_interpolatedstring" ? "string" : (
					$key_type == "expr_variable" ? "variable" : (
					$key_type == "expr_funccall" ? "function" : (
					$key_type == "expr_methodcall" || $key_type == "expr_staticcall" ? "method" : (
					$key_type == "expr_array" ? "array" : ""
				))));
			}
			else
				$key = null;
			
			if ($value_type) {
				if ($value_type == "expr_array")
					$value = self::getMethodParamsArrayItems($PHPParserPrettyPrinter, isset($value->items) ? $value->items : null);
				else {
					$value = $PHPParserPrettyPrinter->nodeExprPrettyPrint($value, false);
					//echo "<pre>value($value_type):$value<pre><br>";
					$value = self::getStmtValueAccordingWithType($value, $value_type);
					//echo "<pre>value($value_type):$value<pre><br>";
				}
				//echo "$key($value_type): $value<br>";
				
				$value_type = strtolower($value_type);
				$value_type = $value_type == "scalar_string" || $value_type == "scalar_encapsed" || $value_type == "scalar_interpolatedstring" ? "string" : (
					$value_type == "expr_variable" ? "variable" : (
					$value_type == "expr_funccall" ? "function" : (
					$value_type == "expr_methodcall" || $value_type == "expr_staticcall" ? "method" : (
					$value_type == "expr_array" ? "array" : ""
				))));
			}
			else 
				$value = null;
			
			$prop = array(
				"key" => $key,
				"key_type" => isset($key) ? $key_type : "null"
			);
			
			if (is_array($value)) 
				$prop["items"] = $value;
			else {
				$prop["value"] = $value;
				$prop["value_type"] = isset($value) ? $value_type : "null";
			}
			
			$props[] = $prop;
		}
		
		return $props;
	}
	
	private static function setMethodParamsArrayItems($PHPParserPrettyPrinter, &$items, $item_path_parts, $new_item_value) {
		$status = false;
		
		$items = self::convertStmtExpressionToSimpleStmt($items);
		
		$item_path_parts_0 = isset($item_path_parts[0]) ? $item_path_parts[0] : null;
		
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			$key = isset($item->key) ? $item->key : null;
			$value = isset($item->value) ? $item->value : null;
			
			$key_type = is_object($key) ? strtolower($key->getType()) : null;
			$value_type = is_object($value) ? strtolower($value->getType()) : null;
			
			if ($key_type) {
				$key = $PHPParserPrettyPrinter->nodeExprPrettyPrint($key, false);
				$key = self::getStmtValueAccordingWithType($key, $key_type);
				
				$key_type = strtolower($item->key->getType());
			}
			else
				$key = null;
			
			//check if key is the same inside of array
			$is_same_key = false;
			
			if ($key && ($key_type == "scalar_string" || $key_type == "scalar_encapsed" || $key_type == "scalar_interpolatedstring") && $key == $item_path_parts_0)
				$is_same_key = true;
			else if (empty($key) && $i == $item_path_parts_0) //Note that the key may be empty, bc is an array with automatic indexes, but the $item_path_parts_0 could be "0"
				$is_same_key = true;
			
			//$key_type doesn't exists if is an array with automatic indexes
			if ($is_same_key && $value_type) {
				//echo "$key:";print_r($item_path_parts);
				
				if (count($item_path_parts) == 1) { //only change it if the item_path_parts count is 1. Note that here canot be empty. If empty, it means that it was already replaced by the setVariableFromStmts and setMethodParamStmtExprValue methods.
					//echo "<pre>";print_r($value);
					$value_attributes = $value->getAttributes();
					$value = $new_item_value;
					
					if ($value_attributes)
						foreach ($value_attributes as $k => $v)
							$value->setAttribute($k, $v);
					//echo "<pre>";print_r($value);die();
					$status = true;
				}
				else if ($value_type == "expr_array") {
					array_shift($item_path_parts); //remove first path from item_path_parts
					
					//continue inthe array sub items
					if (self::setMethodParamsArrayItems($PHPParserPrettyPrinter, $value->items, $item_path_parts, $new_item_value))
						$status = true;
				}
				
				if ($status)
					$items[$i]->value = $value;
				
				break;
			}
		}
		
		return $status;
	}
	
	//used in the WorkFlowTaskCodeParser->getStmtValueAccordingWithType too
	/* Note that if you wish to test this be sure that the $value variable has one of the following codes:
		$value = "\\\\\\ \\p JP new Regex(\"\\p\\[\") \" \\n
			{$foo}
			<script>
			var m = /^[\\w]+$/g.exec(\"test\");
			console.log(m);
			console.log($x); //note that this will be replaced by {$x}
			console.log(\$x);
			console.log({$x});
			console.log({\$x});
			</script>";
		
		$value = '\\\\\\ \\p JP new Regex("\\p\\[") " \\n
			{$foo}
			<script>
			var m = /^[\\w]+$/g.exec("test");
			console.log(m);
			console.log($x);
			console.log(\$x);
			console.log({$x});
			console.log({\$x});
			</script>';
		
		$value = "\\\\\\ \\p JP new Regex(\"\\p\\[\") \" \\n
			{$foo}
			<script>" . '\\n á $bar joão $& {$x} {\\$x}' . "
			var m = /^[\\w]+$/g.exec(\"test\");
			console.log(m);
			console.log($x); //note that this will be replaced by {$x}
			console.log(\$x);
			console.log({$x});
			console.log({\$x});
			</script>";
	*/
	public static function getStmtValueAccordingWithType($value, $value_type) {
		//error_log("$value, $value_type\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		if ($value_type == "scalar_string" || $value_type == "scalar_encapsed" || $value_type == "scalar_interpolatedstring") {
			$first_char = substr($value, 0, 1);
			
			$value = substr($value, 1, -1); //remove double quotes
			
			//2019-10-17: DO NOT USE THE stripslashes. Use instead the stripcslashes, otherwise if we have a end-line escaped ("\\\n" or '\n'), it will convert tis to a real end-line
			//2020-09-13: Do not add the stripcslashes either bc it removes the back-slashes from the escaped backslashes and we want to show th raw code. By default the $value already contains the right slashes, with the exception of quotes, end-lines and tabs which should be unescaped if not escaped! The $ symbol it is escaped too by default, so we unescaped by default!
			//$value = stripcslashes($value); //remove slashes from quotes that are not escaped and other slashes
		
			//un-escaped quotes
			$value = TextSanitizer::stripCSlashes($value, $first_char);
			
			//un-escaped end-lines and tabs
			$value = TextSanitizer::replaceIfNotEscaped('\n', "\n", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\t', "\t", $value);
			
			/* Note that this function always returns the $value but for double quotes, so if we have  a single quote, we must convert it to double quotes.
			 If quote is a single quote, it means that the $value_type is a single quote type, where the variales should be escaped by default, this is, if we have the code:
				$x = 'foo $y bar';
				where $value == 'foo $y bar'
				
				than the $value should become 'foo \$y bar' with the following code:
					$x = "foo \$y bar";
				
				If we have the code:
					$x = 'foo \\$y bar';
				it will be replaced by:
					$x = "foo \\\$y bar";
				
				If we have the code:
					$x = 'foo \$y bar';
				the phpparser class will convert it automatically to:
					$x = 'foo \\$y bar';
				and then it will be replaced by:
					$x = "foo \\\$y bar";
			*/
			if ($first_char == "'") //transforms the single code behaviour in the the double quote behaviour, adding backslash to all $ symbols even if they are already escaped
				$value = str_replace('$', '\\$', $value);//2020-09-14: Do not use TextSanitizer::replaceIfNotEscaped('$', '\\$', $value) or addcslashes or addslashes, bc we only want to add backspaces to all $ symbols (even the escaped ones) and leave the \n and \t alone! This is the default behaviour of single quotes!
			
			/* 2020-09-13: Do not uncomment this code is deprecated
			//$value = preg_replace('/(\${?[\w]+)/u', '\\\\$1', $value);
			//or $value = str_replace('$', '\$', $value);
			
			//if ($first_char == '"')
				//$value = preg_replace('/{\\(\$[\w]+)/u', '{$1', $value);
				//or $value = str_replace('{\$', '{$', $value);
			
			To replace the cases where it was added a backslash to the $ without being a php variable, use the preg_replace bellow.
			This will be used for the cases like the javascript code: 
				\$.find
				that wil be converted to: $.find
				
				\$function() {}
				that wil be converted to: $function() {}
				
				/^[\\p\\w]+\$/g.exec("test");
				that wil be converted to: /^[\\p\\w]+$/g.exec("test");
			
			Note that the phpparser adds automatically a backslash to all '$' symbols even if is not a variable. So we need to remove the cases where it is not a variable. This only happens when code is inside of double quotes, but bc the code above "TextSanitizer::replaceIfNotEscaped('$', '\\$', $value);", should happens when the cod is inside of single quotes too.
			*/
			$value = preg_replace('/(|[^\\\\])\\\\(\${?[^\w])/u', '$1$2', $value); //'/(|[^\\])\\(\$[^\w])/' won't work bc the preg_replace removes 1 backslash, so we need to have: '/(|[^\\\\])\\\\(\$[^\w])/'. '\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			//if(strpos($value, "JP") !== false)error_log("$value\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
			//error_log("$value\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
			//echo "value:$value\n";
			//if (strpos($value, "html(msg.replace(") !== false) die();
		}
		else if ($value_type == "expr_binaryop_concat") { //2019-11-20: if we have a code a string concat where there is inside some text surronded by double quotes, we must convert the '\n' inside of the double quotes to end-lines with the stripclashes function
			//This is, if we have something like: "xx\n\t\t\t\tx" . $as . 'as\n asdas', we want to convert the \n and \t of "xx\n\t\t\t\tx"
			//echo "<pre>value($value_type):$value<pre><br>";
			
			$value_chars = TextSanitizer::mbStrSplit($value);
			$t = count($value_chars);
			$new_value = "";
			
			for ($i = 0; $i < $t; $i++) {
				$char = $value_chars[$i];
				
				if (($char == '"' || $char == "'") && !TextSanitizer::isMBCharEscaped($value, $i, $value_chars)) {
					for ($j = $i + 1; $j < $t; $j++) 
						if ($value_chars[$j] == $char && !TextSanitizer::isMBCharEscaped($value, $j, $value_chars)) 
							break;
					
					$sub_value = implode("", array_slice($value_chars, $i, ($j + 1) - $i));
					
					//2019-11-20: DO NOT USE THE stripslashes or stripcslashes, otherwise if we have any escaped \" both functions will remove this slashes, and we want to keep them bc the $value is php code. We only want to convert to end-lines and tabs inside of double quotes! So use the TextSanitizer::replaceIfNotEscaped instead.
					$sub_value = TextSanitizer::replaceIfNotEscaped('\n', "\n", $sub_value);
					$sub_value = TextSanitizer::replaceIfNotEscaped('\t', "\t", $sub_value);
					
					//Note that the phpparser adds automatically a backslash to all '$' symbols even if is not a variable. So we need to remove the cases where it is not a variable. This only happens when code is inside of double quotes.
					if ($char == '"')
						$sub_value = preg_replace('/(|[^\\\\])\\\\(\${?[^\w])/u', '$1$2', $sub_value); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
					
					$new_value .= $sub_value;
					$i = $j;
				}
				else
					$new_value .= $char;
			}
			
			$value = $new_value;
		}
		
		return $value;
	}
	
	//2019-10-17: Deprecated method
	private static function getStmtValueAccordingWithTypeOld($value, $value_type) {
		if ($value_type == "scalar_string" || $value_type == "scalar_encapsed" || $value_type == "scalar_interpolatedstring") {
			$first_char = substr($value, 0, 1);
			
			$value = substr($value, 1, -1); //remove double quotes
			
			//Do not do this, bc we only want to add end-lines and tabs to the \n and \t chars that are not escaped, this is, if I havea  code \\\n or \\n I don't want to add any end-line.
			//This measn that we cannot use anymore this code: $value = str_replace('\t', "\t", str_replace('\n', "\n", $value));
			$value = TextSanitizer::replaceIfNotEscaped('\n', "\n", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\t', "\t", $value);
			
			//check escape sequences in http://php.net/manual/en/regexp.reference.escape.php
			/*
			$value = TextSanitizer::replaceIfNotEscaped('\cx', "\cx", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\e', "\e", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\e', "\e", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\p', "\p", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\P', "\P", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\r', "\r", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\xhh', "\xhh", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\ddd', "\ddd", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\040', "\040", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\40', "\40", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\7', "\7", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\11', "\11", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\011', "\011", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\0113', "\0113", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\377', "\377", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\81', "\81", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\d', "\d", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\D', "\D", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\h', "\h", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\H', "\H", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\s', "\s", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\S', "\S", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\v', "\v", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\V', "\V", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\w', "\w", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\W', "\W", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\b', "\b", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\B', "\B", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\a', "\a", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\A', "\A", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\z', "\z", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\Z', "\Z", $value);
			$value = TextSanitizer::replaceIfNotEscaped('\G', "\G", $value);
			*/
			
			$value = stripslashes($value);
			
			$value = str_replace('$', '\$', $value);
			
			if ($first_char == '"')
				$value = str_replace('{\$', '{$', $value);
			
			//echo "value:$value\n";
		}
		return $value;
	}
	
	public static function getArgumentType($arg) {
		if (empty($arg) && $arg !== 0)
			return "";
		
		$arg = trim($arg);
		
		if (strtolower($arg) == "null" || is_numeric($arg))
			return "";
		
		preg_match_all('/^(\$[\w\[\]\$]+)$/u', $arg, $matches, PREG_PATTERN_ORDER); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
		
		if (isset($matches[0][0]) && $matches[0][0] == $arg)
			return "variable";
		
		$first_char = substr($arg, 0, 1);
		if (($first_char == '"' || $first_char == "'") && substr($arg, -1) == $first_char) {
			$arg = substr($arg, 1, -1);
			$start = 0;
			
			do {
				$pos = strpos($arg, $first_char, $start);
				$start = $pos + 1;
				
				if ($pos !== false && !TextSanitizer::isCharEscaped($arg, $pos))
					return "";
			}
			while ($pos !== false);
			
			return "string";
		}
		
		return "";
	}
	
	public static function prepareArgument($arg, $arg_type) {
		if ($arg_type == "string")
			return self::getStmtValueAccordingWithType($arg, "scalar_string");
		
		return $arg_type == "variable" ? substr($arg, 1) : (!$arg_type && strtolower($arg) == "null" ? null : $arg);
	}
	
	public static function getFileContents($file_path) {
		return $file_path && file_exists($file_path) ? file_get_contents($file_path) : "";
	}
	
	public static function getVariableValueCode($variable, $type = null) {
		if (!isset($variable))
			return $type == "string" || $type == "date" ? "''" : (!$type ? "null" : "");
		
		return $type == "variable" && $variable ? ((substr(trim($variable), 0, 1) != '$' && substr(trim($variable), 0, 2) != '@$' ? '$' : '') . trim($variable)) : ($type == "string" || $type == "date" ? "\"" . addcslashes($variable, '"') . "\"" : (!$type && strlen(trim($variable)) == 0 ? "null" : trim($variable)) );//Please do not add the addcslashes($variable, '\\"') otherwise it will create an extra \\. The correct is without the \\, because yo are editing php code directly.
	}
}
?>
