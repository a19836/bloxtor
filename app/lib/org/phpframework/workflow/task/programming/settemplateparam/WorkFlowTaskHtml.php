<div class="set_template_param_task_html">
	<div class="broker_method_obj" title="Write here the CMS Template Layer variable">
		<label>Template Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="name">
		<label>Param Name:</label>
		<input type="text" class="task_property_field" name="name" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="name_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="value">
		<label>Param Value:</label>
		<input type="text" class="task_property_field" name="value" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="value_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
		<span class="icon search" onclick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)" title="Search Page">Search page</span>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
