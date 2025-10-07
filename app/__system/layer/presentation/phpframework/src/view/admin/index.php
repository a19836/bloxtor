<?php
if (empty($beans_xml_exception))
	include $EVC->getViewPath($entity_view_id);
else {
	echo '<html>
	<head>
		<link rel="stylesheet" href="' . $project_common_url_prefix . 'css/global.css" type="text/css" charset="utf-8" />
		<link rel="stylesheet" href="' . $project_url_prefix. 'phpframework/css/global.css" type="text/css" charset="utf-8" />
		
		<!-- Add Layout CSS and JS files -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
			
		<!-- Add Local JS and CSS files -->
		<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/admin_advanced.css" type="text/css" charset="utf-8" />
		
		<style>
			a {color:inherit;}
			#top_panel ul.left {padding-left:0;}
			#bottom_panel {padding:70px 15px 10px; color:red; font-size:14px;}
		</style>
	</head>
	<body>
		<div id="top_panel">
			<ul class="left">
				<li class="logo"><a href="' . $project_url_prefix . '"></a></li>
			</ul>
			<ul class="center"></ul>
			<ul class="right">
				<li>
					<a href="' . $project_url_prefix . 'setup?step=3.1&iframe=1&hide_setup=1" target="layers">Layers Setup</a>
				</li>
				' . (!empty($is_admin_ui_expert_allowed) ? '
					<li class="separator">|</li>
					<li>
						<a href="' . $project_url_prefix . 'admin?admin_type=expert" target="expert">Expert Workspace</a>
					</li>
				' : '') . '
			</ul>
		</div>

		<div id="bottom_panel">
			A system exception occurred, likely due to incorrectly configured bean XML files.<br/>
			Access the \'<a href="' . $project_url_prefix . 'setup?step=3.1&iframe=1&hide_setup=1" target="layers">Layers Setup</a>\'' . (
			!empty($is_admin_ui_expert_allowed) ? ' 
				and \'<a href="' . $project_url_prefix . 'admin?admin_type=expert" target="expert">Expert Workspace</a>\'
			' : ''
			) . ' to correct this issue.
		</div>';
	
	launch_exception($beans_xml_exception);
}
?>
