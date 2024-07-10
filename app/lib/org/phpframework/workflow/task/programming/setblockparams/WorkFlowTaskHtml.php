<div class="set_block_params_task_html">
	<input type="hidden" class="task_property_field" name="main_variable_name" value="" />
	
	<div class="value">
		<label>Value: </label>
		<input type="text" class="task_property_field" name="value" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<span class="icon search" onclick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)" title="Search Page">Search page</span>
	</div>
	<div class="type">
		<label>Type: </label>
		<select class="task_property_field" name="type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
