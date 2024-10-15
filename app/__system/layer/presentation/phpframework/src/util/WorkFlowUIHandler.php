<?php
include_once get_lib("org.phpframework.util.web.html.CssAndJSFilesOptimizer");
include_once $EVC->getUtilPath("PHPVariablesFileHandler", "phpframework"); //project if is very important bc this file is called from the test project

class WorkFlowUIHandler {
	
	private $WorkFlowTaskHandler;
	private $webroot_url;
	private $common_webroot_url;
	private $external_libs_url_prefix;
	private $user_global_variables_file_path;
	private $webroot_cache_folder_path;
	private $webroot_cache_folder_url;
	
	private $tasks_settings;
	private $tasks_containers;
	private $tasks_order_by_tag;
	private $tasks_groups_by_tag;
	
	private $menus;
	
	public function __construct($WorkFlowTaskHandler, $webroot_url, $common_webroot_url, $external_libs_url_prefix, $user_global_variables_file_path, $webroot_cache_folder_path, $webroot_cache_folder_url) {
		$this->WorkFlowTaskHandler = $WorkFlowTaskHandler;
		$this->webroot_url = $webroot_url;
		$this->common_webroot_url = $common_webroot_url;
		$this->external_libs_url_prefix = $external_libs_url_prefix;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
		$this->webroot_cache_folder_path = $webroot_cache_folder_path;
		$this->webroot_cache_folder_url = $webroot_cache_folder_url;
		
		$this->init();
	}
	
	private function init() {
		$this->WorkFlowTaskHandler->initWorkFlowTasks();
		
		$this->tasks_settings = $this->WorkFlowTaskHandler->getLoadedTasksSettings();
		$this->tasks_containers = $this->WorkFlowTaskHandler->getParsedTasksContainers();
		
		$this->tasks_order_by_tag = array();
		$this->tasks_groups_by_tag = array();
	}
	
	public function setTasksOrderByTag($tasks_order_by_tag) {
		$this->tasks_order_by_tag = $tasks_order_by_tag;
	}
	
	public function setTasksGroupsByTag($tasks_groups_by_tag) {
		$this->tasks_groups_by_tag = $tasks_groups_by_tag;
	}
	
	public function getDefaultTasksGroupsByTag() {
		$tasks_groups_by_tag = array();
		
		foreach ($this->tasks_settings as $group_id => $group_tasks) 
			foreach ($group_tasks as $task_type => $task_settings) 
				$tasks_groups_by_tag[$group_id][] = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
		
		return $tasks_groups_by_tag;
	}
	
	public function addFoldersTasksToTasksGroups($tasks_folders_path) {
		if ($tasks_folders_path)
			foreach ($tasks_folders_path as $tasks_folder_path)
				$this->addFolderTasksToTasksGroups($tasks_folder_path);
	}
	public function addFolderTasksToTasksGroups($tasks_folder_path) {
		$tasks_folder_path = WorkFlowTaskHandler::prepareFolderPath($tasks_folder_path);
		
		$folder_id = $this->WorkFlowTaskHandler->getFolderId($tasks_folder_path);
		$loaded_tasks = $this->WorkFlowTaskHandler->getLoadedTasks();
		$folder_loaded_tasks = isset($loaded_tasks[$folder_id]) ? $loaded_tasks[$folder_id] : null;
		$folder_loaded_tasks_settings = isset($this->tasks_settings[$folder_id]) ? $this->tasks_settings[$folder_id] : null;
		//print_r($this->tasks_groups_by_tag);die();
		
		if ($folder_loaded_tasks_settings) 
			foreach ($folder_loaded_tasks_settings as $task_id => $task_settings) {
				$loaded_task = isset($folder_loaded_tasks[$task_id]) ? $folder_loaded_tasks[$task_id] : null;
				$loaded_task_path = isset($loaded_task["path"]) ? $loaded_task["path"] : null;
				//print_r($loaded_task);print_r($task_settings);die();
				
				$relative_parent_folder_path = str_replace($tasks_folder_path, "", dirname(dirname($loaded_task_path)));
				$relative_parent_folder_path = substr($relative_parent_folder_path, 0, 1) == "/" ? substr($relative_parent_folder_path, 1) : $relative_parent_folder_path;
				$pos = strpos($relative_parent_folder_path, "/");
				$main_parent_name = $pos ? substr($relative_parent_folder_path, 0, $pos) : $relative_parent_folder_path;
				
				if ($main_parent_name) {
					$main_parent_label = ucwords(strtolower(str_replace(array("_", "-"), " ", $main_parent_name)));
					$task_settings_tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
					
					if (isset($this->tasks_groups_by_tag[$main_parent_label])) 
						$this->tasks_groups_by_tag[$main_parent_label][] = $task_settings_tag;
					else
						$this->tasks_groups_by_tag[$main_parent_label] = array($task_settings_tag);
				}
			}
		//print_r($this->tasks_groups_by_tag);die();
	}
	
	public function getHeader($options = array("tasks_css_and_js" => true)) {
		$head = '
<!-- Add Jquery Tap-Hold Event JS file -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jquerytaphold/taphold.js"></script>

<!-- Jquery Touch Punch to work on mobile devices with touch -->
<script type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jqueryuitouchpunch/jquery.ui.touch-punch.min.js"></script>';
		
		if (empty($options["taskflowchart_already_included"])) {
			if (!empty($this->external_libs_url_prefix["jsplumb"]))
				$head .= '
<!-- Add JSPlumb main JS and CSS files -->
<script language="javascript" type="text/javascript" src="' . $this->external_libs_url_prefix["jsplumb"] . 'build/1.3.16/js/jquery.jsPlumb-1.3.16-all-min.js"></script>
';
			
			$head .= '
<!-- Add LeaderLine main JS and CSS files -->
<link rel="stylesheet" href="' . $this->external_libs_url_prefix["taskflowchart"] . 'lib/leaderline/leader-line.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $this->external_libs_url_prefix["taskflowchart"] . 'lib/leaderline/leader-line.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->external_libs_url_prefix["taskflowchart"] . 'lib/leaderline/LeaderLineFlowHandler.js"></script>

<!-- Add TaskFlowChart main JS and CSS files -->
<link rel="stylesheet" href="' . $this->external_libs_url_prefix["taskflowchart"] . 'css/style.css" type="text/css" charset="utf-8" />
<link rel="stylesheet" href="' . $this->external_libs_url_prefix["taskflowchart"] . 'css/print.css" type="text/css" charset="utf-8" media="print" />
<script type="text/javascript" src="' . $this->external_libs_url_prefix["taskflowchart"] . 'js/ExternalLibHandler.js"></script>
<script type="text/javascript" src="' . $this->external_libs_url_prefix["taskflowchart"] . 'js/TaskFlowChart.js"></script>

<!-- Add ContextMenu main JS and CSS files -->
<link rel="stylesheet" href="' . $this->common_webroot_url . 'vendor/jquerymycontextmenu/css/style.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jquerymycontextmenu/js/jquery.mycontextmenu.js"></script>

<!-- Parse_Str -->
<script type="text/javascript" src="' . $this->common_webroot_url . 'vendor/phpjs/functions/strings/parse_str.js"></script>

<!-- Add DropDowns main JS and CSS files -->
<link rel="stylesheet" href="' . $this->common_webroot_url . 'vendor/jquerysimpledropdowns/css/style.css" type="text/css" charset="utf-8" />
<!--[if lte IE 7]>
        <link rel="stylesheet" href="' . $this->common_webroot_url . 'vendor/jquerysimpledropdowns/css/ie.css" type="text/css" charset="utf-8" />
<![endif]-->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jquerysimpledropdowns/js/jquery.dropdownPlain.js"></script>
';
		}
		
		if (empty($options["icons_and_edit_code_already_included"]))
			$head .= '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $this->common_webroot_url . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $this->webroot_url . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Edit Layout JS files -->
<script type="text/javascript" src="' . $this->webroot_url . 'js/layout.js"></script>
';
		
		if (!empty($options["ui_editor"]) || $this->WorkFlowTaskHandler->getTasksByPrefix("createform", 1) || $this->WorkFlowTaskHandler->getTasksByPrefix("inlinehtml", 1))
			$head .= '
<!-- Layout UI Editor - Color -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'js/color.js"></script>

<!-- Layout UI Editor - MD5 -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Layout UI Editor - Add ACE-Editor -->
<script type="text/javascript" src="' . $this->common_webroot_url . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script type="text/javascript" src="' . $this->common_webroot_url . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Layout UI Editor - Add Code Beautifier -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

<!-- Layout UI Editor - Add Html/CSS/JS Beautify code -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jsbeautify/js/lib/beautify.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jsbeautify/js/lib/beautify-css.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/myhtmlbeautify/MyHtmlBeautify.js"></script>

<!-- Add Auto complete -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/myautocomplete/js/MyAutoComplete.js"></script>
<link rel="stylesheet" href="' . $this->common_webroot_url . 'vendor/myautocomplete/css/style.css">

<!-- Layout UI Editor - Html Entities Converter -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/he/he.js"></script>

<!-- Layout UI Editor - Material-design-iconic-font -->
<link rel="stylesheet" href="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/materialdesigniconicfont/css/material-design-iconic-font.min.css">

<!-- Layout UI Editor - JQuery Nestable2 -->
<link rel="stylesheet" href="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/nestable2/jquery.nestable.min.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/nestable2/jquery.nestable.min.js"></script>

<!-- Layout UI Editor - HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
	 <script src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/html5_ie8/html5shiv.min.js"></script>
	 <script src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/html5_ie8/respond.min.js"></script>
<![endif]-->

<!-- Layout UI Editor - Add Iframe droppable fix -->
<script type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/jquery-ui-droppable-iframe-fix.js"></script>    

<!-- Layout UI Editor - Add Iframe droppable fix - IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/vendor/jqueryuidroppableiframe/js/ie10-viewport-bug-workaround.js"></script>

<!-- Layout UI Editor - Add Layout UI Editor -->
<link rel="stylesheet" href="' . $this->webroot_url . 'lib/jquerylayoutuieditor/css/some_bootstrap_style.css" type="text/css" charset="utf-8" />
<link rel="stylesheet" href="' . $this->webroot_url . 'lib/jquerylayoutuieditor/css/style.css" type="text/css" charset="utf-8" />
<link rel="stylesheet" href="' . $this->webroot_url . 'lib/jquerylayoutuieditor/css/widget_resource.css" type="text/css" charset="utf-8" />

<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/js/TextSelection.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/js/LayoutUIEditor.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/js/CreateWidgetContainerClassObj.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/js/LayoutUIEditorFormField.js"></script>
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'lib/jquerylayoutuieditor/js/LayoutUIEditorWidgetResource.js"></script>

<!-- Layout UI Editor - Add Layout UI Editor Widget Resource Options/Handlers -->
<link rel="stylesheet" href="' .  $this->webroot_url . 'css/layout_ui_editor_widget_resource_options.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $this->webroot_url . 'js/layout_ui_editor_widget_resource_options.js"></script>
';
		else if (!empty($options["tasks_css_and_js"]))
			$head .= '
<!-- Add MD5 JS File -->
<script language="javascript" type="text/javascript" src="' . $this->common_webroot_url . 'vendor/jquery/js/jquery.md5.js"></script>
';
		
		if (!empty($options["tasks_css_and_js"]))
			$head .= "\n<!-- Add TASKS JS and CSS files -->\n" . $this->printTasksCSSAndJS();
		
		return $head;
	}
	
	public function getJS($get_workflow_file_path = false, $set_workflow_file_path = false, $workflow_options = null) {
		$set_workflow_file_path = empty($set_workflow_file_path) ? $get_workflow_file_path : $set_workflow_file_path;
		
		$head = '
<script>
	workflow_global_variables = ' . json_encode(PHPVariablesFileHandler::getVarsFromFileContent($this->user_global_variables_file_path)) . ';
	
	$(window).resize(function() {
		taskFlowChartObj.Container.automaticIncreaseContainersSize();
		
		taskFlowChartObj.getMyFancyPopupObj().updatePopup();
	});
	
	;(function() {
';
	
	if ($get_workflow_file_path)
		$head .= 'taskFlowChartObj.TaskFile.get_tasks_file_url = "' . $this->webroot_url . 'workflow/get_workflow_file?path=' . $get_workflow_file_path . '";';
	
	if ($set_workflow_file_path)
		$head .= 'taskFlowChartObj.TaskFile.set_tasks_file_url = "' . $this->webroot_url . 'workflow/set_workflow_file?path=' . $set_workflow_file_path . '";';
	
	if ($workflow_options)
		foreach ($workflow_options as $k => $v)
			$head .= '
		taskFlowChartObj.setTaskFlowChartObjOption("' . $k . '", \'' . addcslashes(str_replace(array("\n", "\r"), "", $v), "\\'") . '\')';
	
	$head .= '
		taskFlowChartObj.TaskFlow.default_connection_connector = "Straight";
		taskFlowChartObj.TaskFlow.default_connection_hover_color = null;
		taskFlowChartObj.TaskFlow.main_tasks_flow_obj_id = "taskflowchart > .tasks_flow";
		taskFlowChartObj.TaskFlow.main_tasks_properties_obj_id = "taskflowchart > .tasks_properties";
		taskFlowChartObj.TaskFlow.main_connections_properties_obj_id = "taskflowchart > .connections_properties";
		taskFlowChartObj.ContextMenu.main_tasks_menu_obj_id = "taskflowchart > .tasks_menu";
		taskFlowChartObj.ContextMenu.main_tasks_menu_hide_obj_id = "taskflowchart > .tasks_menu_hide";
		taskFlowChartObj.ContextMenu.main_workflow_menu_obj_id = "taskflowchart > .workflow_menu";
		
		taskFlowChartObj.Property.tasks_settings = ' . $this->getTasksSettingsObj() . ';
		taskFlowChartObj.Container.tasks_containers = ' . $this->getTasksContainersByTaskType() . ';
		
		taskFlowChartObj.TaskFile.save_options = {
			success: function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					StatusMessageHandler.showError("Please Login first!");
			}
		};
		
		taskFlowChartObj.init();
	})();
	
	function flushCache(opts) {
		opts = $.isPlainObject(opts) ? opts : {};
		var do_not_show_messages = opts["do_not_show_messages"];
		var url = \'' . $this->webroot_url . 'admin/flush_cache\';
		
		$.ajax({
			type : "get",
			url : url,
			success : function(data, textStatus, jqXHR) {
				if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
					showAjaxLoginPopup(jquery_native_xhr_object.responseURL, url, function() {
						flushCache();
					});
				else if (!do_not_show_messages) {
					if (data == "1")
						taskFlowChartObj.StatusMessage.showMessage("Cache flushed!");
					else
						taskFlowChartObj.StatusMessage.showError("Error: Cache not flushed!\nPlease try again..." + (data ? "\n" + data : ""));
				}
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (!do_not_show_messages) {
					var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
					taskFlowChartObj.StatusMessage.showError("Error: Cache not flushed!\nPlease try again..." + msg);
				}
			},
			async : opts.hasOwnProperty("async") ? opts["async"] : true
		});
		
		return false;
	}
	
	function emptyDiagam() {
		if (confirm("If you continue, all items will be deleted from this diagram and this diagram will be empty.\nDo you still want to proceed?"))
			taskFlowChartObj.reinit();
	}
	
	function zoomInDiagram(elm) {
		taskFlowChartObj.TaskFlow.zoomIn();
		updateCurrentZoom(elm);
		zoomEventPropagationDiagram(elm);
	}
	
	function zoomOutDiagram(elm) {
		taskFlowChartObj.TaskFlow.zoomOut();
		updateCurrentZoom(elm);
		zoomEventPropagationDiagram(elm);
	}
	
	function zoomDiagram(input) {
		taskFlowChartObj.TaskFlow.zoom(input.value);
		updateCurrentZoom(input);
		zoomEventPropagationDiagram(input);
	}
	
	function zoomResetDiagram(elm) {
		taskFlowChartObj.TaskFlow.zoomReset();
		updateCurrentZoom(elm);
		zoomEventPropagationDiagram(elm);
	}
	
	function zoomEventPropagationDiagram(elm) {
		window.event.stopPropagation();
	}
	
	function updateCurrentZoom(elm) {
		elm = $(elm);
		var current_zoom = taskFlowChartObj.TaskFlow.getCurrentZoom();
		var main_parent = elm.parent().closest("li").parent();
		main_parent.find(".zoom span").html( parseInt(current_zoom * 100) + "%");
		
		if (!elm.is("input[type=range]"))
			main_parent.find(".zoom input[type=range]").val(current_zoom);
	}
	
	function openIframePopup(url, options) {
		options = typeof options != "undefined" && options ? options : {};
		options["url"] = url;
		options["type"] = "iframe";
		
		taskFlowChartObj.getMyFancyPopupObj().init(options);
		taskFlowChartObj.getMyFancyPopupObj().showPopup();
	}
</script>';

		return $head;
	}
	
	public function setMenus($menus) {
		$this->menus = $menus;
	}
	
	public function getDefaultMenus() {
		$default_container = array_keys($this->tasks_containers);
		$default_container = isset($default_container[0]) ? $default_container[0] : null;
	
		//$if_task_type = $this->WorkFlowTaskHandler->getTaskTypeByPrefix("programming/if");
		$default_task_type = isset($this->tasks_containers[$default_container][0]) ? $this->tasks_containers[$default_container][0] : null;
		
		return array(
			"File" => array(
				"childs" => array(
					"Save" => array("click" => "taskFlowChartObj.TaskFile.save();return false;"),
					"Flush Cache" => array("click" => "return flushCache();"),
					"Empty Diagram" => array("click" => "emptyDiagam();return false;"),
					"Flip Diagram" => array("click" => "taskFlowChartObj.ContextMenu.flipPanelsSide();return false;"),
				)
			),
			"WorkFlow" => array(
				"childs" => array(
					"Add new task $default_task_type" => array("click" => "taskFlowChartObj.ContextMenu.addTaskByType('" . $default_task_type . "');return false;"),
					"Zoom In" => array("click" => "zoomInDiagram(this);return false;"),
					"Zoom Out" => array("click" => "zoomOutDiagram(this);return false;"),
					"Zoom" => array("click" => "zoomEventPropagationDiagram(this);return false;", "class" => "zoom", "html" => '<span>100%</span> <input type="range" min="0.5" max="1.5" step=".02" value="1" onInput="zoomDiagram(this);return false;" />'),
					"Zoom Reset" => array("click" => "zoomResetDiagram(this);return false;"),
				)
			),
			"Container" => array(
				"childs" => array(
					"Decrease Containers IF Size" => array("click" => "taskFlowChartObj.Container.changeContainerSize('" . $default_container . "', 400, 100);return false;"),
					"Increase Containers IF Size" => array("click" => "taskFlowChartObj.Container.changeContainerSize('" . $default_container . "', 1300, 250);return false;"),
					"Shrink containers" => array("click" => "taskFlowChartObj.Container.automaticDecreaseContainersSize();return false;"),
					"Enlarge containers" => array("click" => "taskFlowChartObj.Container.automaticIncreaseContainersSize();return false;"),
				)
			),
		);
	}
	
	public function getMenusContent() {
		if (empty($this->menus) || !is_array($this->menus))
			$this->menus = $this->getDefaultMenus();
		
		return '<div id="workflow_menu" class="workflow_menu" onClick="openSubmenu(this)">' . $this->getMenusContentAux($this->menus, "dropdown") . '</div>';
	}
	
	private function getMenusContentAux($menus, $class = false) {
		$html = '';
		
		if (!empty($menus) && is_array($menus)) {
			$html .= '<ul' . ($class ? ' class="' . $class . '"' : '') . '>';
			
			foreach ($menus as $menu_name => $menu) {
				$html .= '<li class="' . (isset($menu["class"]) ? $menu["class"] : "") . '" title="' . (!empty($menu["title"]) ? $menu["title"] : $menu_name) . '">';
				
				if (!empty($menu["html"]))
					$html .= $menu["html"];
				else
					$html .= '<a' . (!empty($menu["click"]) ? ' onClick="' . $menu["click"] . '"' : '') . '>' . $menu_name . '</a>';
				
				if (!empty($menu["childs"]))
					$html .= $this->getMenusContentAux($menu["childs"]);
			
				$html .= '</li>';
			}
			
			$html .= '</ul>';
		}
		
		return $html;
	}
	
	public function getContent($main_div_id = "taskflowchart") {
		$reverse_class = isset($_COOKIE["main_navigator_side"]) && $_COOKIE["main_navigator_side"] == "main_navigator_reverse" ? "" : "reverse";
		
		$main_content = '
	<div id="' . $main_div_id . '" class="taskflowchart ' . $reverse_class . '">
		' . $this->getMenusContent() . '
		
		<div class="tasks_menu scroll">
			' . $this->printTasksList() . '
		</div>
		
		<div class="tasks_menu_hide">
			<div class="button" onclick="taskFlowChartObj.ContextMenu.toggleTasksMenuPanel(this)"></div>
		</div>
		
		<div class="tasks_flow scroll">
			' . $this->printTasksContainers() . '
		</div>
	
		<div class="tasks_properties hidden">
			' . $this->printTasksProperties() . '
		</div>
	
		<div class="connections_properties hidden">
			' . $this->printConnectionsProperties() . '
		</div>
	</div>
';

		return $main_content;
	}
	
	public function printTasksList() {
		$html = '<ul class="tasks_groups">';
		
		//PREPARING TASKS BY TAG
		$tasks_by_tag = array();
		foreach ($this->tasks_settings as $group_id => $group_tasks)
			foreach ($group_tasks as $task_type => $task_settings) {
				$tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
				
				if ($tag)
					$tasks_by_tag[$tag] = array($task_type, $task_settings);
			}
		
		$added = array();
		
		//PREPARING TASKS GROUPS - IF EXISTS
		//error_log(print_r($this->tasks_groups_by_tag, 1), 3, "/var/www/html/livingroop/default/tmp/test.log");
		if (is_array($this->tasks_groups_by_tag)) {
			foreach ($this->tasks_groups_by_tag as $group_name => $tags) {
				if ($tags) {
					$group_html = "";
					
					$t = count($this->tasks_order_by_tag);
					for ($i = 0; $i < $t; $i++) {
						$tag = $this->tasks_order_by_tag[$i];
						
						if (in_array($tag, $tags)) {
							$task = isset($tasks_by_tag[$tag]) ? $tasks_by_tag[$tag] : null;
							$task_type = isset($task[0]) ? $task[0] : null;
							$task_settings = isset($task[1]) ? $task[1] : null;
							
							if ($task_type && $task_settings) {
								$added[] = $task_type;
								
								$group_html .= $this->printTaskList($task_type, $task_settings);
							}
						}
					}
					
					$t = count($tags);
					for ($i = 0; $i < $t; $i++) {
						$tag = $tags[$i];
						
						$task = isset($tasks_by_tag[$tag]) ? $tasks_by_tag[$tag] : null;
						$task_type = isset($task[0]) ? $task[0] : null;
						$task_settings = isset($task[1]) ? $task[1] : null;
						
						if ($task_type && $task_settings && !in_array($task_type, $added)) {
							$added[] = $task_type;
							
							$group_html .= $this->printTaskList($task_type, $task_settings);
						}
					}
					
					//only show group if there is any task inside, otherwise it shows an empty group, which does not make sense.
					if ($group_html) {
						$group_class = "tasks_group_" . str_replace(array(" ", "-"), "_", strtolower($group_name));
						
						$html .= '<li class="tasks_group ' . $group_class . '">';
						$html .= '<div class="tasks_group_label">' . $group_name . '</div>';
						$html .= '<div class="tasks_group_tasks">';
						$html .= $group_html;
						$html .= '</div>
							<div style="clear:left; float:none;"></div>
						</li>';
					}
				}
			}
		}
		
		//PREPARING OTHER TASKS THAT ARE NOT IN THE TASKS GROUPS or IF TASKS GROUPS ARE EMPTY, SHOW ALL TASKS
		$contains_multiple_groups = count($added) > 0;
		$html_others = "";
		//print_r($this->tasks_settings);die();
		//print_r(array_keys($this->tasks_settings));die();
		//print_r($this->tasks_order_by_tag);die();
		
		foreach ($this->tasks_settings as $group_id => $group_tasks) {
			if ($group_tasks) {
				$t = count($this->tasks_order_by_tag);
				for ($i = 0; $i < $t; $i++) {
					$tag = $this->tasks_order_by_tag[$i];
					$task = isset($tasks_by_tag[$tag]) ? $tasks_by_tag[$tag] : null;
					$task_type = isset($task[0]) ? $task[0] : null;
					
					if (!empty($group_tasks[$task_type]) && !in_array($task_type, $added)) {
						$added[] = $task_type;
						
						$html_others .= $this->printTaskList($task_type, isset($group_tasks[$task_type]) ? $group_tasks[$task_type] : null);
					}
				}
				
				foreach ($group_tasks as $task_type => $task_settings) 
					if (!in_array($task_type, $added)) 
						$html_others .= $this->printTaskList($task_type, $task_settings);
			}
		}
		
		if ($html_others) {
			$html .= '<li class="tasks_group tasks_group_others">';
			$html .= $contains_multiple_groups ? '<div class="tasks_group_label">Others</div>' : '';
			$html .= '<div class="tasks_group_tasks">' . $html_others . '</div>
				<div style="clear:left; float:none;"></div>
			</li>';
		}
		
		$html .= '</ul>';
		
		return $html;
	}
	
	private function printTaskList($task_type, $task_settings) {
		$task_tag = isset($task_settings["tag"]) ? str_replace(" ", "_", $task_settings["tag"]) : "";
		$task_label = isset($task_settings["label"]) ? $task_settings["label"] : null;
		
		return '<div class="task task_menu task_' . $task_type . ' task_' . $task_tag . '" type="' . $task_type . '" tag="' . $task_tag . '" title="' . str_replace('"', "&quot;", $task_label) . '"><span>' . $task_label . '</span></div>';
	}

	public function printTasksProperties() {
		$html = "";
	
		if (!empty($this->tasks_order_by_tag)) {
			$tasks_settings = $this->tasks_settings;
			
			$t = count($this->tasks_order_by_tag);
			for ($i = 0; $i < $t; $i++) {
				$tag = $this->tasks_order_by_tag[$i];
				
				foreach ($tasks_settings as $group_id => $group_tasks) {
					foreach ($group_tasks as $task_type => $task_settings) {
						$task_tag = isset($task_settings["tag"]) ? $task_settings["tag"] : null;
						
						if ($task_tag == $tag) {
							if (!empty($task_settings["task_properties_html"])) {
								$html .= '<div class="task_properties task_properties_' . $task_type . '">' . (is_array($task_settings) ? $task_settings["task_properties_html"] : $task_settings) . '</div>';
							}
							
							unset($tasks_settings[$group_id][$task_type]);
							break;
						}
					}
				}
			}
			
			foreach ($tasks_settings as $group_id => $group_tasks) {
				foreach ($group_tasks as $task_type => $task_settings) {
					if (!empty($task_settings["task_properties_html"])) {
						$html .= '<div class="task_properties task_properties_' . $task_type . '">' . (is_array($task_settings) ? $task_settings["task_properties_html"] : $task_settings) . '</div>';
					}
				}
			}
		}
		else {
			foreach ($this->tasks_settings as $group_id => $group_tasks) {
				foreach ($group_tasks as $task_type => $task_settings) {
					if (!empty($task_settings["task_properties_html"])) {
						$html .= '<div class="task_properties task_properties_' . $task_type . '">' . (is_array($task_settings) ? $task_settings["task_properties_html"] : $task_settings) . '</div>';
					}
				}
			}
		}
		
		return $html;
	}

	public function printConnectionsProperties() {
		$html = "";
	
		foreach ($this->tasks_settings as $group_id => $group_tasks) {
			foreach ($group_tasks as $task_type => $task_settings) {
				if (!empty($task_settings["connection_properties_html"])) {
					$html .= '<div class="connection_properties connection_properties_' . $task_type . '">' . (is_array($task_settings) ? $task_settings["connection_properties_html"] : $task_settings) . '</div>';
				}
			}
		}
		
		return $html;
	}

	public function printTasksCSSAndJS() {
		$css_files = $js_files = array();
		
		foreach ($this->tasks_settings as $group_id => $group_tasks)
			foreach ($group_tasks as $task_type => $task_settings)
				if (is_array($task_settings)) {
					if (!empty($task_settings["files"]["css"]))
						$css_files = array_merge($css_files, $task_settings["files"]["css"]);
					
					if (!empty($task_settings["files"]["js"]))
						$js_files = array_merge($js_files, $task_settings["files"]["js"]);
				}
		
		$wcfp = $this->webroot_cache_folder_path ? $this->webroot_cache_folder_path . "/" . WorkFlowTaskHandler::TASKS_WEBROOT_FOLDER_PREFIX . "files/" : null;
		$wcfu = $this->webroot_cache_folder_url ? $this->webroot_cache_folder_url . "/" . WorkFlowTaskHandler::TASKS_WEBROOT_FOLDER_PREFIX . "files/" : null;
		
		$CssAndJSFilesOptimizer = new CssAndJSFilesOptimizer($wcfp, $wcfu);
		$html = $CssAndJSFilesOptimizer->getCssAndJSFilesHtml($css_files, $js_files);
		
		//prepare inline css and js
		$inline_css = $inline_js = "";
		
		foreach ($this->tasks_settings as $group_id => $group_tasks) {
			foreach ($group_tasks as $task_type => $task_settings) {
				if (is_array($task_settings)) {
					if (isset($task_settings["css"]) && trim($task_settings["css"]))
						$inline_css .= $task_settings["css"] . "\n";
			
					if (isset($task_settings["js_code"]) && trim($task_settings["settings"]["js_code"]))
						$inline_js .= $task_settings["settings"]["js_code"] . "\n";
				}
			}
		}
		
		if (trim($inline_css))
			$html .= '<style type="text/css">' . $inline_css . '</style>' . "\n";
		
		if (trim($inline_js))
			$html .= '<script language="javascript" type="text/javascript">' . $inline_js . '</script>' . "\n";
		
		return $html;
	}

	private function printTasksContainers() {
		$html = "";
	
		if (is_array($this->tasks_containers))
			foreach ($this->tasks_containers as $container_id => $container_tasks) 
				$html .= '<div class="task_container" id="' . $container_id . '"></div>';
		
		return $html;
	}

	private function getTasksContainersByTaskType() {
		$containers_per_task = array();
	
		foreach ($this->tasks_containers as $container_id => $task_types) 
			if ($task_types) {
				$t = count($task_types);
				for ($i = 0; $i < $t; $i++)
					$containers_per_task[ $task_types[$i] ][] = $container_id;
			}
		
		return json_encode($containers_per_task);
	}

	public function getTasksSettingsObj() {
		$html = "{";
	
		foreach ($this->tasks_settings as $group_id => $group_tasks) {
			foreach ($group_tasks as $task_type => $task_settings) {
				if (is_array($task_settings)) {
					if (isset($task_settings["settings"]) && is_array($task_settings["settings"])) {
						$html .= ($html != "{" ? ", " : "") . '"' . $task_type . '" : {';
						
						$idx = 0;
						foreach ($task_settings["settings"] as $key => $value) {
							if (is_array($value)) {
								$new_value = "{";
								foreach ($value as $sub_key => $sub_value) {
									if (strlen(trim($sub_value)) > 0) {
										$new_value .= ($new_value != "{" ? ", " : "") . '"' . strtolower($sub_key) . '" : ' . $sub_value;
									}
								}
								$new_value .= "}";
								
								$value = $new_value;
							}
							
							if (strlen(trim($value)) > 0) {
								$html .= ($idx > 0 ? ", " : "") . '"' . strtolower($key) . '" : ' . $value;
								$idx++; 
							}
						}
						
						$html .= '}';
						
						//MyArray::arrKeysToLowerCase($task_settings["settings"], true);
						//$html .= $html != "{" ? ", " : "";
						//$html .= '"' . $task_type . '" : ' . json_encode($task_settings["settings"]);
					}
				}
			}
		}
		
		$html .= "}";
	
		return $html;
	}
}
?>
