<div class="set_var_task_html">
	<div class="value">
		<label>Value: </label>
		<input type="text" class="task_property_field" name="value" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<span class="icon search" onclick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)" title="Search Page">Search page</span>
		<span class="icon textarea" onClick="SetVarTaskPropertyObj.changeValueTextField(this)" title="Change from text area to text input and vice-versa.">Change Text Field</span>
		<span class="icon maximize" onClick="SetVarTaskPropertyObj.maximizeEditor(this)">Maximize</span>
		<textarea></textarea>
	</div>
	<div class="var_type">
		<label>Type: </label>
		<select class="task_property_field" name="type" onChange="SetVarTaskPropertyObj.onChangeVarType(this);">
			<option>string</option>
			<option>variable</option>
			<option>date</option>
			<option value="">code</option>
		</select>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
