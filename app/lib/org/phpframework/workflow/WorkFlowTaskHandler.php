<?php
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.workflow.exception.WorkFlowTaskException");
include_once get_lib("org.phpframework.workflow.WorkFlowTaskCache");
include_once get_lib("org.phpframework.phpscript.PHPScriptHandler");

class WorkFlowTaskHandler {
	/*private*/ const TASKS_WEBROOT_FOLDER_PREFIX = "workflow/tasks/";
	
	private $tasks_webroot_folder_path;
	private $workflow_webroot_url;
	private $tasks_folder_paths;
	private $allowed_task_types;
	private $allowed_task_folders;
	private $allowed_task_tags;
	private $tasks_containers;
	
	private $loaded_tasks;
	private $parsed_tasks_containers;
	private $tasks_folder_paths_id;
	
	private $WorkFlowTaskCache;
	
	public function __construct($workflow_webroot_folder_path, $workflow_webroot_url = "/") {
		$workflow_webroot_folder_path = trim($workflow_webroot_folder_path);
		$workflow_webroot_url = trim($workflow_webroot_url);
		
		if (empty($workflow_webroot_folder_path)) {
			launch_exception(new WorkFlowTaskException(7));
		}
	
		if (empty($workflow_webroot_url)) {
			launch_exception(new WorkFlowTaskException(13));
		}
	
		$this->tasks_webroot_folder_path = $workflow_webroot_folder_path . self::TASKS_WEBROOT_FOLDER_PREFIX;
		$this->workflow_webroot_url = $workflow_webroot_url . self::TASKS_WEBROOT_FOLDER_PREFIX;
		$this->tasks_folder_paths = array( self::getDefaultTasksFolderPath() );
		$this->allowed_task_types = array();
		$this->allowed_task_folders = array();
		$this->allowed_task_tags = array();
		$this->tasks_containers = array();
	
		$this->loaded_tasks = array();
		
		$this->WorkFlowTaskCache = new WorkFlowTaskCache();
	}
	
	public function initWorkFlowTasks() {
		$this->initWorkFlowLoadedTasks();
		$this->initWorkFlowTaskContainers();
	}
	
	private function initWorkFlowLoadedTasks() {
		$this->tasks_folder_paths_id = $this->WorkFlowTaskCache->getCachedId( array($this->tasks_folder_paths, $this->allowed_task_types, $this->allowed_task_folders, $this->workflow_webroot_url) );
		
		if ($this->WorkFlowTaskCache->isActive() && $this->WorkFlowTaskCache->cachedLoadedTasksIncludesExists($this->tasks_folder_paths_id) && $this->WorkFlowTaskCache->cachedLoadedTasksExists($this->tasks_folder_paths_id)) {
			$includes = $this->WorkFlowTaskCache->getCachedLoadedTasksIncludes($this->tasks_folder_paths_id);
			
			$total = $includes ? count($includes) : 0;
			for ($i = 0; $i < $total; $i++)
				include_once $includes[$i];
			
			$new_tasks = $this->WorkFlowTaskCache->getCachedLoadedTasks($this->tasks_folder_paths_id);
		}
		
		if (empty($new_tasks)) {
			$includes = array();
			$new_tasks = array();
		
			$this->prepareWorkFlowTasks($includes, $new_tasks);
			
			if ($this->WorkFlowTaskCache->isActive()) {
				$this->WorkFlowTaskCache->setCachedLoadedTasksIncludes($this->tasks_folder_paths_id, array_keys($includes) );
				$this->WorkFlowTaskCache->setCachedLoadedTasks($this->tasks_folder_paths_id, $new_tasks);
			}
		}
		
		$this->loaded_tasks = $new_tasks;
		
		//print_r($this->loaded_tasks);die();
		return $new_tasks;
	}
	
	private function initWorkFlowTaskContainers() {
		$tasks_containers_id = $this->WorkFlowTaskCache->getCachedId( array($this->tasks_folder_paths_id, $this->tasks_containers) );
		
		if ($this->WorkFlowTaskCache->isActive() && $this->WorkFlowTaskCache->cachedTasksContainersExists($tasks_containers_id)) {
			$new_containers = $this->WorkFlowTaskCache->getCachedTasksContainers($tasks_containers_id);
		}
		
		if (empty($new_containers)) {
			$new_containers = array();
			
			if (is_array($this->tasks_containers)) {
				foreach ($this->tasks_containers as $container_id => $task_prefixes) {
					$new_containers[$container_id] = array();
			
					$total = $task_prefixes ? count($task_prefixes) : 0;
					for ($i = 0; $i < $total; $i++) {
						$task_prefix = $task_prefixes[$i];
				
						$tasks = $this->getTasksByPrefix($task_prefix);
						$t = count($tasks);
						for ($j = 0; $j < $t; $j++) {
							$new_containers[$container_id][] = isset($tasks[$j]["type"]) ? $tasks[$j]["type"] : null;
						}
					}
				}
			}
			
			if ($this->WorkFlowTaskCache->isActive()) {
				$this->WorkFlowTaskCache->setCachedTasksContainers($tasks_containers_id, $new_containers);
			}
		}
		
		$this->parsed_tasks_containers = $new_containers;
		
		//print_r($this->parsed_tasks_containers);die();
		return $new_containers;
	}
	
	private function prepareWorkFlowTasks(&$includes, &$new_tasks) {
		$loaded_tasks = self::loadTasksFolderPaths($this->tasks_folder_paths);
		//echo "<pre>";print_r($loaded_tasks);die();
		
		foreach ($loaded_tasks as $folder_id => $folder_tasks) {
			$new_tasks[$folder_id] = array();
			
			foreach ($folder_tasks as $task_prefix => $task) {
				if (!empty($task["path"])) {
					include_once $task["path"];
			
					$class = (isset($task["namespace"]) ? $task["namespace"] : null) . "\\" . (isset($task["class"]) ? $task["class"] : null);
					
					if (class_exists($class)) {
						$task["prefix"] = $folder_id . "/" . $task_prefix;
						
						if (!is_subclass_of($class, "WorkFlowTask")) 
							launch_exception(new WorkFlowTaskException(3, $class));
						else if ($this->isTaskAllowed($task["prefix"])) {
							eval('$WorkFlowTask = new ' . $class . '();');
						
							$task["class"] = get_class($WorkFlowTask);
							$task["type"] = hash("crc32b", $task["prefix"]);
							$task["webroot_url"] = $this->workflow_webroot_url . $task["prefix"] . "/";
							
							if (is_object($WorkFlowTask)) {
								unset($task["obj"]);
								$WorkFlowTask->setTaskClassInfo($task);
								
								$task["obj"] = $WorkFlowTask;
							}
							else {
								launch_exception(new WorkFlowTaskException(4, $class));
							}
							
							$includes[ $task["path"] ] = 0;
							$new_tasks[$folder_id][ $task["type"] ] = $task;
						}
					}
					else {
						launch_exception(new WorkFlowTaskException(12, $class));
					}
				}
				else {
					launch_exception(new WorkFlowTaskException(15, $task_prefix));
				}
			}
		}
		//echo "<pre>";print_r($new_tasks);die();
		//echo "<pre>";print_r($includes);die();
	}
	
	private function loadTasksFolderPaths($tasks_folder_paths) {
		$tasks = array();
		
		$this->tasks_webroot_folder_path = str_replace("//", "/", $this->tasks_webroot_folder_path);
		
		if (!is_dir($this->tasks_webroot_folder_path)) 
			if (!mkdir($this->tasks_webroot_folder_path, 0775, true)) 
				launch_exception(new WorkFlowTaskException(8, $this->tasks_webroot_folder_path));
		
		$default_path = self::prepareFolderPath(self::getDefaultTasksFolderPath());
		
		$total = $tasks_folder_paths ? count($tasks_folder_paths) : 0;
		for ($i = 0; $i < $total; $i++) {
			$folder_path = $tasks_folder_paths[$i];
			
			if ($folder_path) {
				self::prepareFolderPath($folder_path);
				
				if (strpos($folder_path, $default_path) === 0) {
					$fp = str_replace($default_path, "", $folder_path);
					$folder_path = $default_path;
					
					//Add allowed task folder, but only if there is not other folder path which is it's own parent, otherwise we are limiting the workflow to only this sub-path.
					if ($fp) {
						$exists_parent = false;
						for ($j = 0; $j < $total; $j++) {
							if ($j != $i) {
								$pfp = self::prepareFolderPath($tasks_folder_paths[$j]);
								if (strpos($folder_path, $pfp) === 0) {
									$exists_parent = true;
									break;
								}
							}
						}
						
						if (!$exists_parent) 
							$this->addAllowedTaskFolder($fp);
					}
				}
			
				$folder_id = self::getFolderId($folder_path);
				
				$tasks_webroot_folder_path = $this->tasks_webroot_folder_path . $folder_id . "/";
				
				if (!is_dir($tasks_webroot_folder_path)) 
					if (!mkdir($tasks_webroot_folder_path, 0775, true))
						launch_exception(new WorkFlowTaskException(8, $tasks_webroot_folder_path));
				
				$folder_tasks = self::loadTasksFolderPath($folder_path, $tasks_webroot_folder_path);
				//echo "folder_path:$folder_path:";print_r($folder_tasks);echo "\n<br>";
				$tasks[ $folder_id ] = $folder_tasks;
			}
		}
		
		return $tasks;
	}
	
	private static function loadTasksFolderPath($src, $dst, $main_src = false) { 
		$tasks = array();
		
		if (is_dir($src) && ($dir = opendir($src)) ) {
			$main_src = empty($main_src) ? $src : $main_src;
			$main_src_id = self::getFolderId($main_src);
			
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					if(is_dir($src . $file)) {
						if ($file == "webroot") 
							if (!self::copyFolder($src . $file, $dst)) 
								launch_exception(new WorkFlowTaskException(9, array($src . $file, $dst)));
						
						$folder_tasks = self::loadTasksFolderPath($src . $file . "/", $dst . $file . "/", $main_src);
						$tasks = array_merge($tasks, $folder_tasks);
					}
					else if ($file == "WorkFlowTaskImpl.php") {
						$class_name = basename($file, ".php");
						$task_prefix = substr($src, strlen($main_src), ( strlen($src) - strlen($main_src) ) - 1);
						
						$content = file_get_contents($src . $file);
						preg_match_all("/([\n\t ;]+)namespace ([^;]+);/u", $content, $matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too.
						$namespace = isset($matches[2][0]) ? trim($matches[2][0]) : null;
						
						if ($namespace) {
							if (is_dir($src . "webroot")) 
								if (!self::copyFolder($src . "webroot", $dst)) 
									launch_exception(new WorkFlowTaskException(9, array($src . "webroot", $dst)));
							
							$tasks[$task_prefix] = array(
								"path" => $src . $file,
								"webroot_path" => $dst,
								"class" => "WorkFlowTaskImpl",
								"namespace" => $namespace,
							);
						}
						else 
							launch_exception(new WorkFlowTaskException(10, $src . $file));
					}
				}
			}
			closedir($dir);
		}
		
		return $tasks;
	}
	
	private static function copyFolder($src, $dst) {
		if ($src && $dst && is_dir($src)) {
			if (!is_dir($dst)) 
				@mkdir($dst, 0755, true);
			
			if (is_dir($dst)) {
				$status = true;
				$files = scandir($src);
				
				if ($files)
					foreach ($files as $file) 
						if ($file != '.' && $file != '..') { 
							if (is_dir("$src/$file")) { 
								if (!self::copyFolder("$src/$file", "$dst/$file"))
									$status = false;
							} 
							else if (!copy("$src/$file", "$dst/$file"))
								$status = false;
						}
					
				return $status; 
			}
		}
	}
	
	public function parseFile($file_path, $loops = null, $options = null) {
		$return_obj = $options && isset($options["return_obj"]) ? $options["return_obj"] : false;
		$arr = XMLFileParser::parseXMLFileToArray($file_path, false, false, false);
		
		$tasks = array();
		$start_tasks = array();
		
		if (isset($arr["tasks"][0]["childs"]["task"]) && is_array($arr["tasks"][0]["childs"]["task"])) {
			if (!is_array($loops))
				$loops = $this->getLoopsTasksFromFileTasksData($arr);
			
			$this->removeInfinitLoopFromFileTasksData($arr, $loops);
			
			$start_tasks_aux = array();
			
			foreach ($arr["tasks"][0]["childs"]["task"] as $task_data) {
				$task = $this->parseTask($task_data);
				
				if ($task) {
					$task_id = isset($task->data["id"]) ? $task->data["id"] : null;
					$tasks[$task_id] = $task;
					
					if (isset($task->data["start"]) && $task->data["start"]) {
						if (is_numeric($task->data["start"]))
							$start_tasks[ $task->data["start"] ] = $task_id;
						else 
							$start_tasks_aux[] = $task_id;
					}
				}
				else 
					launch_exception(new WorkFlowTaskException(1, isset($task_data["childs"]["id"][0]["value"]) ? $task_data["childs"]["id"][0]["value"] : null));
			}
			
			//if not start tasks defined, tries to get the start task automatically
			if (empty($start_tasks))
				$start_tasks = $this->suggestStartTasksId($arr);
			
			ksort($start_tasks);
			$start_tasks = array_merge($start_tasks, $start_tasks_aux);
		}
		
		$res = $return_obj ? array() : "";
		
		$total = count($start_tasks);
		for ($i = 0; $i < $total; $i++) {
			$task_id = $start_tasks[$i];
			$result = WorkFlowTask::printTask($tasks, $task_id, null, "", $options);
			
			if ($return_obj)
				$res[] = isset($result[0]) ? $result[0] : null;
			else
				$res .= $result;
		}
		
		return $res;
	}
	
	private function suggestStartTasksId($arr) {
		$start_tasks_id = array();
		$tasks_ids = array();
		$tasks_ids_with_parent_connections = array();
		
		if (isset($arr["tasks"][0]["childs"]["task"]) && is_array($arr["tasks"][0]["childs"]["task"])) {
			foreach ($arr["tasks"][0]["childs"]["task"] as $task_idx => $task_data) {
				$tasks_ids[] = WorkFlowTask::getTaskId($task_data);
				$exits = WorkFlowTask::getTaskExists($task_data);
				
				if ($exits)
					foreach ($exits as $exit_key => $exit_items) {
						$t = $exit_items ? count($exit_items) : 0;
						
						for ($i = 0; $i < $t; $i++)
							$tasks_ids_with_parent_connections[] = $exit_items[$i];
					}
			}
			
			$start_tasks_id = array_diff($tasks_ids, $tasks_ids_with_parent_connections);
		}
		
		return $start_tasks_id;
	}
	
	private function removeInfinitLoopFromFileTasksData(&$arr, $loops) {
		if (isset($arr["tasks"][0]["childs"]["task"]) && is_array($arr["tasks"][0]["childs"]["task"]) && !empty($loops)) {
			$t = count($loops);
			for ($i = 0; $i < $t; $i++) {
				$loop = $loops[$i];
			
				$source_task_id = isset($loop[0]) ? $loop[0] : null;
				$target_task_id = isset($loop[1]) ? $loop[1] : null;
				$is_loop_task = isset($loop[2]) ? $loop[2] : null;
			
				if (!$is_loop_task) {
					foreach ($arr["tasks"][0]["childs"]["task"] as $task_idx => $task_data) {
						$task_id = WorkFlowTask::getTaskId($task_data);
						
						if ($task_id == $source_task_id) {
							if (isset($task_data["childs"]["exits"][0]["childs"]) && is_array($task_data["childs"]["exits"][0]["childs"])) {
								$new_exits = array();
							
								foreach ($task_data["childs"]["exits"][0]["childs"] as $exit_key => $exit_data) {
									$total = $exit_data ? count($exit_data) : 0;
									for ($j = 0; $j < $total; $j++) {
										$exit_task_id = isset($exit_data[$j]["childs"]["task_id"][0]["value"]) ? $exit_data[$j]["childs"]["task_id"][0]["value"] : null;
										
										if ($exit_task_id != $target_task_id) {
											$new_exits[$exit_key][] = $exit_data[$j];
										}
									}
								}
								
								$task_data["childs"]["exits"][0]["childs"] = $new_exits;
								$arr["tasks"][0]["childs"]["task"][$task_idx] = $task_data;
							}
						}
					}
				}
			}
		}
			
		return $arr;
	}
	
	public function getLoopsTasksFromFile($file_path) {
		$arr = XMLFileParser::parseXMLFileToArray($file_path, false, false, false);
		//print_r($arr);die();
		
		return $this->getLoopsTasksFromFileTasksData($arr);
	}
	
	//Note that this must be done through recursive functions, otherwise we get memory dump.
	private function getLoopsTasksFromFileTasksData($arr) {
		$visited = array(); // To track nodes that have been completely processed
		$rec_stack = array(); // To track nodes in the current recursion stack
		$loop_tasks = array(); // To track nodes unique ids
		$tasks = isset($arr["tasks"][0]["childs"]["task"]) ? $arr["tasks"][0]["childs"]["task"] : null;
		
		if (is_array($tasks)) {
			//prepare tasks_by_ids
			$tasks_by_ids = array();
			foreach ($tasks as $task_idx => $task_data) {
				$task_id = WorkFlowTask::getTaskId($task_data);
				$tasks_by_ids[$task_id] = $task_data;
			}
			
			//prepare loop_tasks
			foreach ($tasks as $task_idx => $task_data) {
				$task_id = WorkFlowTask::getTaskId($task_data);
				
				if (!isset($visited[$task_id]))
					$this->dfs($task_id, $tasks_by_ids, $visited, $rec_stack, $loop_tasks);
			}
		}
		
		//return loop_tasks values only
		return array_values($loop_tasks);
	}
	/* DEPRECATED bc it consumes a lot of memory and gives a memory dump if we have a more than 16 lines of code similar with '$x = $y ? $y : null;', which gives almost 100.000 items in the paths array, blocking apache server and consuming 100% CPU.
	private function getLoopsTasksFromFileTasksData($arr) {
		$loops_tasks = array();
			
		if (isset($arr["tasks"][0]["childs"]["task"]) && is_array($arr["tasks"][0]["childs"]["task"])) {
			$paths = array();
			
			foreach ($arr["tasks"][0]["childs"]["task"] as $task_idx => $task_data) {
				$task_id = WorkFlowTask::getTaskId($task_data);
				$exits = WorkFlowTask::getTaskExists($task_data);
				
				$indexes = array();
				$t = count($paths);
				for ($i = 0; $i < $t; $i++) {
					$path = $paths[$i];
					
					if (in_array($task_id, $path))
						$indexes[] = $i;
				}
				
				if (empty($indexes)) {
					$indexes = array(count($paths));
					$paths[] = array($task_id);
				}
				
				$new_paths = array();
				$t = count($paths);
				for ($i = 0; $i < $t; $i++) {
					$path = $paths[$i];
					
					if (in_array($i, $indexes)) {
						$is_new_array = false;
						
						foreach ($exits as $exit_key => $exit_items) {
							$t2 = $exit_items ? count($exit_items) : 0;
							
							for ($j = 0; $j < $t2; $j++) {
								$exit_task_id = $exit_items[$j];
								
								if (in_array($exit_task_id, $path)) {
									$type = WorkFlowTask::getTaskType($task_data);
									$task = $this->getTaskByType($type);
									
									$is_loop_task = false;
									if ($task && isset($task["obj"]) && is_object($task["obj"]))
										$is_loop_task = $task["obj"]->isLoopTask();
									
									$loops_tasks[ hash("crc32b", "$task_id/$exit_task_id") ] = array($task_id, $exit_task_id, $is_loop_task);
								}
								else { //add exit_task_id to path
									$path_aux = $path;
									$path_aux[] = $exit_task_id;
									$new_paths[] = $path_aux;
									
									$is_new_array = true;
								}
							}
						}
						
						if (!$is_new_array)
							$new_paths[] = $path;
					}
					else
						$new_paths[] = $path;
				}
				
				$paths = $new_paths;
			}
		}
		
		return array_values($loops_tasks);
	}*/
	
	// Helper function for DFS - Depth-First Search
	private function dfs($task_id, &$tasks_by_ids, &$visited, &$rec_stack, &$loop_tasks) {
		// If the node is in the current recursion stack, a cycle is detected
		if (isset($rec_stack[$task_id]) && $rec_stack[$task_id])
			return true;
		
		// If the node is already visited and not in the recursion stack, skip it
		if (isset($visited[$task_id]) && $visited[$task_id])
			return false;

		// Mark the node as visited and add it to the recursion stack
		$visited[$task_id] = true;
		$rec_stack[$task_id] = true;

		// Traverse all neighbors
		$task_data = isset($tasks_by_ids[$task_id]) ? $tasks_by_ids[$task_id] : null;
		
		if (isset($task_data)) {
			$exits = WorkFlowTask::getTaskExists($task_data);
			
			foreach ($exits as $exit_key => $exit_items) {
				$t = $exit_items ? count($exit_items) : 0;
				
				for ($i = 0; $i < $t; $i++) {
					$exit_task_id = $exit_items[$i];
					$is_cycle = $this->dfs($exit_task_id, $tasks_by_ids, $visited, $rec_stack, $loop_tasks);
					
					if ($is_cycle) { // Cycle detected
						$type = WorkFlowTask::getTaskType($task_data);
						$task = $this->getTaskByType($type);
						
						$is_loop_task = false;
						if ($task && isset($task["obj"]) && is_object($task["obj"]))
							$is_loop_task = $task["obj"]->isLoopTask();
						
						$loop_tasks[ hash("crc32b", "$task_id/$exit_task_id") ] = array($task_id, $exit_task_id, $is_loop_task);
					}
				}
			}
		}
		
		// Remove the node from the recursion stack before returning
		$rec_stack[$task_id] = false;
		
		return false;
	}
	
	private function parseTask($task_data) {
		$type = WorkFlowTask::getTaskType($task_data);
		
		$task = $this->getTaskByType($type);
		
		if ($task && isset($task["obj"]) && is_object($task["obj"]) && get_class($task["obj"]) == $task["class"]) {
			$WorkFlowTask = $task["obj"];
			
			if (isset($WorkFlowTask)) {
				$WorkFlowTaskClone = $WorkFlowTask->cloneTask();
				
				if (!empty($WorkFlowTaskClone)) {
					$WorkFlowTaskClone->parse($task_data);
					
					return $WorkFlowTaskClone;
				}
				else {
					launch_exception(new WorkFlowTaskException(6, array($task["class"], $WorkFlowTaskClone) ));
				}
			}
			else {
				launch_exception(new WorkFlowTaskException(5, array($task["class"], $task["obj"]) ));
			}
		}
		else {
			$task_class = isset($task["class"]) ? $task["class"] : null;
			$task_obj = isset($task["obj"]) ? $task["obj"] : null;
			launch_exception(new WorkFlowTaskException(5, array($task_class, $task_obj) ));
		}
		
		return false;
	}
	
	public function getLoadedTasksSettingsCacheId($params = false) {
		return $this->WorkFlowTaskCache->getCachedId( array($this->tasks_folder_paths_id, $this->allowed_task_tags, $params) );
	}
	
	public function getCachedLoadedTasksSettings($cache_id) {
		return $this->WorkFlowTaskCache->getCachedLoadedTasksSettings($cache_id);
	}
	
	public function getLoadedTasksSettings($params = false) {
		$tasks_settings = array();
		
		$tasks_settings_id = $this->getLoadedTasksSettingsCacheId($params);
		
		if ($this->WorkFlowTaskCache->isActive() && $this->WorkFlowTaskCache->cachedLoadedTasksSettingsExists($tasks_settings_id))
			$tasks_settings = $this->WorkFlowTaskCache->getCachedLoadedTasksSettings($tasks_settings_id);
		
		if (empty($tasks_settings) && is_array($this->loaded_tasks)) {
			foreach ($this->loaded_tasks as $folder_id => $folder_tasks) {
				$tasks_settings[$folder_id] = array();
				
				foreach ($folder_tasks as $task_type => $task) {
					$task_path = isset($task["path"]) ? $task["path"] : null;
					$task_webroot_path = isset($task["webroot_path"]) ? $task["webroot_path"] : null;
					
					$task_folder_path = dirname($task_path) . "/";
					$task_webroot_folder_path = $task_webroot_path . "/";
					
					$settings_path = $task_folder_path . "settings.xml";
					
					if (!file_exists($settings_path)) {
						$content = $this->getHtmlFileContent($task_folder_path . "WorkFlowTaskHtml.php", $task, $task_folder_path);
						$tasks_settings[$folder_id][$task_type]["task_properties_html"] = $content ? $content : null;
						
						$content = $this->getHtmlFileContent($task_folder_path . "WorkFlowConnectionHtml.php", $task, $task_folder_path);
						$tasks_settings[$folder_id][$task_type]["connection_properties_html"] = $content ? $content : null;
					}
					else {
						$arr = XMLFileParser::parseXMLFileToArray($settings_path, $params);
						$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
						$arr = $arr["task"];
						$arr_tag = isset($arr["tag"]) ? $arr["tag"] : null;
						
						if ($this->isTaskAllowedByTag($arr_tag)) {
							$task_prefix = isset($task["prefix"]) ? $task["prefix"] : null;
							
							if (empty($arr["label"])) 
								$arr["label"] = substr($task_prefix, strpos($task_prefix, "/") + 1);
							
							if (isset($arr["files"])) {
								//PREPARE TASK_PROPERTIES_HTML
								$files_task_properties_html = isset($arr["files"]["task_properties_html"]) ? $arr["files"]["task_properties_html"] : null;
								$content = $this->getHtmlFileContent($files_task_properties_html, $task, $task_folder_path);
								
								if (isset($content)) 
									$arr["task_properties_html"] = $content;
								
								unset($arr["files"]["task_properties_html"]);
								
								//PREPARE CONNECTION_PROPERTIES_HTML
								$files_connection_properties_html = isset($arr["files"]["connection_properties_html"]) ? $arr["files"]["connection_properties_html"] : null;
								$content = $this->getHtmlFileContent($files_connection_properties_html, $task, $task_folder_path);
								if (isset($content))
									$arr["connection_properties_html"] = $content;
								
								unset($arr["files"]["connection_properties_html"]);
								
								//PREPARE CSS
								if (!empty($arr["files"]["css"])) {
									if (!is_array($arr["files"]["css"]))
										$arr["files"]["css"] = array($arr["files"]["css"]);
						
									$new_files = array();
									$t = count($arr["files"]["css"]);
									for ($i = 0; $i < $t; $i++) {
										$new_file_path = $task_webroot_folder_path . $arr["files"]["css"][$i];
										
										if (is_file($new_file_path)) 
											$new_files[$new_file_path] = $task["webroot_url"] . $arr["files"]["css"][$i];
									}
									
									$arr["files"]["css"] = $new_files;
								}
					
								//PREPARE JS
								if (!empty($arr["files"]["js"])) {
									if (!is_array($arr["files"]["js"]))
										$arr["files"]["js"] = array($arr["files"]["js"]);
								
									$new_files = array();
									$t = count($arr["files"]["js"]);
									for ($i = 0; $i < $t; $i++) {
										$new_file_path = $task_webroot_folder_path . $arr["files"]["js"][$i];
										
										if (is_file($new_file_path)) 
											$new_files[$new_file_path] = $task["webroot_url"] . $arr["files"]["js"][$i];
									}
									
									$arr["files"]["js"] = $new_files;
								}
							}
							
							//This part is to be used in the WorkFlowTaskCodeParser.php file
							$types = array("statements", "reserved_static_method_class_names", "reserved_object_method_names", "reserved_function_names");
							$t = count($types);
							for ($i = 0; $i < $t; $i++) {
								$type = $types[$i];
								
								if (isset($arr["code_parser"][$type])) {
									$items = array();
								
									$parts = explode(",", $arr["code_parser"][$type]);
									$t2 = count($parts);
									for ($j = 0; $j < $t2; $j++) {
										$part = trim($parts[$j]);
										
										if ($part) 
											$items[] = $part;
									}
								
									$arr["code_parser"][$type] = $items;
								}
							}
				
							$tasks_settings[$folder_id][$task_type] = $arr;
						}
					}
				}
			}
			
			if ($this->WorkFlowTaskCache->isActive()) 
				$this->WorkFlowTaskCache->setCachedLoadedTasksSettings($tasks_settings_id, $tasks_settings);
		}
		
		//echo "<pre>";print_r($tasks_settings);die();
		return $tasks_settings;
	}
	
	private function getHtmlFileContent($html_file, $task, $task_folder_path) {
		if (!empty($html_file)) {
			if (is_array($html_file))
				$html_file = isset($html_file[0]) ? $html_file[0] : null;

			if (file_exists($task_folder_path . $html_file)) {
				$content = file_get_contents($task_folder_path . $html_file);
				
				$ext = pathinfo($task_folder_path . $html_file, PATHINFO_EXTENSION);
				
				if (strtolower($ext) == "php") {
					$external_vars = array("task" => $task, "file_path" => $task_folder_path . $html_file);
					return PHPScriptHandler::parseContent($content, $external_vars);
				}
				else 
					return $content;
			}
		}
		
		return null;
	}	
	
	private function prepareTaskPath(&$task_prefix) {
		if (substr($task_prefix, 0, 1) == "/") 
			$task_prefix = substr($task_prefix, 1);
		
		if (substr($task_prefix, strlen($task_prefix) - 1) == "/") 
			$task_prefix = substr($task_prefix, 0, strlen($task_prefix) - 1);
		
		$task_prefix = str_replace("//", "/", $task_prefix);
		
		return $task_prefix;
	}
	
	public function getTaskTypeByPrefix($task_prefix) {
		$task = $this->getTasksByPrefix($task_prefix, 1);
		
		return !empty($task) && isset($task[0]["type"]) ? $task[0]["type"] : false;
	}
	
	public function getTasksByPrefix($task_prefix, $maximum_number_of_findings = 0) {
		$selected_tasks = array();
		
		if (is_array($this->loaded_tasks)) { 
			$this->prepareTaskPath($task_prefix);
		
			foreach ($this->loaded_tasks as $folder_id => $folder_tasks) {
				foreach ($folder_tasks as $task_type => $task) {
					$item_prefix = isset($task["prefix"]) ? $task["prefix"] : null;
					
					if ($item_prefix == $task_prefix) {
						$selected_tasks[] = $task;
						break;
					}
					else {
						while ( ($pos = strpos($item_prefix, "/")) !== false ) {
							$item_prefix = substr($item_prefix, $pos + 1);
						
							if ($item_prefix == $task_prefix) {
								$selected_tasks[] = $task;
								break;
							}
						}
					}
					
					if ($maximum_number_of_findings > 0 && count($selected_tasks) == $maximum_number_of_findings) {
						break;
					}
				}
			}
		}
		
		return $selected_tasks;
	}
	
	public function getTasksByTag($task_tag, $maximum_number_of_findings = 0) {
		$selected_tasks = array();
		
		$task_tag = strtolower($task_tag);
		
		$tasks_settings = $this->getLoadedTasksSettings();
		
		if (is_array($tasks_settings)) { 
			foreach ($tasks_settings as $folder_id => $folder_tasks) {
				foreach ($folder_tasks as $task_type => $task_settings) {
					$item_tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
					
					if (strtolower($item_tag) == $task_tag) {
						$task = $this->getTaskByType($task_type);
						
						if ($task) {
							$selected_tasks[] = $task;
							
							if ($maximum_number_of_findings > 0 && count($selected_tasks) == $maximum_number_of_findings) {
								break;
							}
						}
					}
				}
			}
		}
		
		return $selected_tasks;
	}
	
	public function getTaskByType($task_type) {
		if (is_array($this->loaded_tasks))  
			foreach ($this->loaded_tasks as $folder_id => $folder_tasks)
				foreach ($folder_tasks as $task) {
					$item_type = isset($task["type"]) ? $task["type"] : null;
					
					if ($item_type == $task_type)
						return $task;
				}
		
		return null;
	}
	
	public static function prepareFolderPath(&$folder_path) {
		$folder_path = file_exists($folder_path) ? realpath($folder_path) : $folder_path; //file_exists is very important bc if file doesn't exists, the realpath will return "/" but bc of the basedir in the php.ini we will get a php error bc the "/" folder is not allowed (bc of security reasons).
		$folder_path = str_replace("//", "/", trim($folder_path));
		$folder_path .= substr($folder_path, strlen($folder_path) - 1) == "/" ? "" : "/";
		
		return $folder_path;
	}
	
	public static function getFolderId($folder_path) {
		$folder_path = self::prepareFolderPath($folder_path);
		$default_folder_path = self::prepareFolderPath(self::getDefaultTasksFolderPath());
		
		return $folder_path == $default_folder_path ? "default" : hash("crc32b", $folder_path);
	}
	
	public static function getDefaultTasksFolderPath() {
		return normalize_windows_path_to_linux(__DIR__) . "/task";
	}
	
	public function addTasksFolderPath($tasks_folder_path) {
		$this->tasks_folder_paths[] = $tasks_folder_path;
	}
	
	public function addTasksFoldersPath($tasks_folders_path) {
		if ($tasks_folders_path)
			foreach ($tasks_folders_path as $tasks_folder_path)
				$this->addTasksFolderPath($tasks_folder_path);
	}
	
	public function setTasksFolderPaths($tasks_folder_paths) { 
		$this->tasks_folder_paths = is_array($tasks_folder_paths) ? $tasks_folder_paths : array($tasks_folder_paths); 
	}
	public function getTasksFolderPaths() { return $this->tasks_folder_paths; }
	
	public function addAllowedTaskType($allowed_task_type) {
		$this->allowed_task_types[] = $this->prepareTaskPath($allowed_task_type);
	}
	public function setAllowedTaskTypes($allowed_task_types) { 
		$this->allowed_task_types = array();
		
		if ($allowed_task_types) {
			$total = count($allowed_task_types);
			for ($i = 0; $i < $total; $i++)
				$this->allowed_task_types[$i] = $this->prepareTaskPath($allowed_task_types[$i]);
		}
	}
	public function getAllowedTaskTypes() { return $this->allowed_task_types; }
	
	public function addAllowedTaskFolder($allowed_task_folder) {
		$this->allowed_task_folders[] = $this->prepareTaskPath($allowed_task_folder);
	}
	public function setAllowedTaskFolders($allowed_task_folders) { 
		$this->allowed_task_folders = array();
		
		if ($allowed_task_folders) {
			$total = count($allowed_task_folders);
			for ($i = 0; $i < $total; $i++)
				$this->allowed_task_folders[$i] = $this->prepareTaskPath($allowed_task_folders[$i]);
		}
	}
	public function getAllowedTaskFolders() { return $this->allowed_task_folders; }
	
	public function addAllowedTaskTagsFromFolders($task_folders) {
		if ($task_folders)
			foreach ($task_folders as $task_folder)
				$this->addAllowedTaskTagsFromFolder($task_folder);
	}
	public function addAllowedTaskTagsFromFolder($task_folder) {
		$tags = $this->getFolderTaskTags($task_folder);
		//print_r($tags);die();
		
		foreach ($tags as $tag)
			$this->allowed_task_tags[] = $tag;
	}
	public function addAllowedTaskTag($allowed_task_tag) {
		$this->allowed_task_tags[] = $allowed_task_tag;
	}
	public function setAllowedTaskTags($allowed_task_tags) { $this->allowed_task_tags = $allowed_task_tags; }
	public function getAllowedTaskTags() { return $this->allowed_task_tags; }
	
	public function isTaskAllowed($task_prefix) {
		if (empty($this->allowed_task_types) && empty($this->allowed_task_folders))
			return true;
		
		return $this->isTaskAllowedByPrefix($task_prefix) && $this->isTaskAllowedByFolder($task_prefix);
	}
	
	public function isTaskAllowedByPrefix($task_prefix) {
		if (empty($this->allowed_task_types) || in_array($task_prefix, $this->allowed_task_types))
			return true;
	
		while ( ($pos = strpos($task_prefix, "/")) !== false ) {
			$task_prefix = substr($task_prefix, $pos + 1);
		
			if (in_array($task_prefix, $this->allowed_task_types))
				return true;
		}
		
		return false;
	}
	public function isTaskAllowedByFolder($task_folder) {
		if (empty($this->allowed_task_folders) || in_array($task_folder, $this->allowed_task_folders))
			return true;
		
		$total = count($this->allowed_task_folders);
		for ($i = 0; $i < $total; $i++) {
			$allowed_task_folder = $this->allowed_task_folders[$i];
		
			$pos = strpos($task_folder, $allowed_task_folder);
		
			if ($pos === 0 && substr($task_folder, strlen($allowed_task_folder), 1) == "/")
				return true;
			else {
				$pos = strpos($task_folder, "default/" . $allowed_task_folder);
				
				if ($pos === 0 && substr($task_folder, strlen("default/" . $allowed_task_folder), 1) == "/")
					return true;
			}
		}
		
		return false;
	}
	public function isTaskAllowedByTag($task_tag) {
		if (empty($this->allowed_task_tags) || in_array($task_tag, $this->allowed_task_tags))
			return true;
	
		if (!isset($task_tag) && in_array("", $this->allowed_task_tags)) //if there is no tag value, check if empty values are allowed.
			return true;
		
		return false;
	}
	
	public function addTasksContainer($container_id, $tasks_container) {
		$this->tasks_containers[ $container_id ] = $tasks_container;
	}
	public function setTasksContainers($tasks_containers) { 
		$this->tasks_containers = $tasks_containers; 
	}
	public function getTasksContainers() { return $this->tasks_containers; }
	public function getParsedTasksContainers() { return $this->parsed_tasks_containers; }
	
	public function getLoadedTasks() { return $this->loaded_tasks; }
	
	public function setCacheRootPath($dir_path) {
		$this->WorkFlowTaskCache->initCacheDirPath($dir_path);
	}
	
	public function flushCache() {
		$status = $this->WorkFlowTaskCache->flushCache();
		
		return $status && CacheHandlerUtil::deleteFolder($this->tasks_webroot_folder_path);
	}
	
	private static function getFolderTaskTags($folder_path) { 
		$tags = array();
		
		if (is_dir($folder_path) && ($dir = opendir($folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					if(is_dir($folder_path . $file)) {
						$folder_tags = self::getFolderTaskTags($folder_path . $file . "/");
						$tags = array_merge($tags, $folder_tags);
					}
					else if ($file == "WorkFlowTaskImpl.php") {
						if (file_exists($folder_path . "settings.xml")) {
							$arr = XMLFileParser::parseXMLFileToArray($folder_path . "settings.xml");
							$arr = MyXML::complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
							$arr = isset($arr["task"]) ? $arr["task"] : null;
							$tag = isset($arr["tag"]) ? $arr["tag"] : null;
							
							if ($tag)
								$tags[] = $tag;
							
							break;
						}
					}
				}
			}
			closedir($dir);
		}
		
		return $tags;
	}
}
?>
