<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$main_content = '<div id="project_name">
	<form method="post" onSubmit="return MyJSLib.FormHandler.formCheck(this);">
		<div class="title">
			<h1>Project Name</h1>
		</div>
		<div class="info">
			Please insert a name for your project. This will be for the default project.<br/>
			If apply, in the future you can create more projects too...
		</div>
		<div class="setup_input name">
			Project Name:
			<input type="text" name="project_name" value="' . $project_name . '" placeHolder="' . $default_project_name . '" allownull="false" validationmessage="Please insert the project name." />
		</div>
	</form>
</div>';

$continue_function = "$('#project_name form').submit()";
?>
