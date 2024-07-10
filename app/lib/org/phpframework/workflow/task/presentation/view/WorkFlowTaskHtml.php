<div class="view_task_html page_content_task_html">
	<ul>
		<li class="settings_tab"><a href="#view_task_html_settings">Settings</a></li>
		<li class="ui_tab"><a href="#view_task_html_ui">UI</a></li>
	</ul>
	
	<div class="settings" id="view_task_html_settings">
		<?php 
			include dirname(dirname($file_path)) . "/common/ChooseDBTableHtml.php";
			include dirname(dirname($file_path)) . "/common/LinksHtml.php";
			include dirname(dirname($file_path)) . "/common/InnerTaskUIHtml.php";
		?>
	</div>
	
	<div class="ui" id="view_task_html_ui">
		<?php include dirname(dirname($file_path)) . "/common/TaskUITabContentHtml.php"; ?>
	</div>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
