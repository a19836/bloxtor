<div class="switch_task_html">
	<div class="object_var">
		<label>Object Var:</label>
		<input type="text" class="task_property_field" name="object_var" value="" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
	</div>
	<div class="object_type">
		<label>Object Type:</label>
		<select class="task_property_field" name="object_type">
			<option>variable</option>
			<option>string</option>
			<option value="">code</option>
		</select>
	</div>
	
	<div class="cases">
		<label>Cases:</label>
		<a class="icon add" onClick="SwitchTaskPropertyObj.addCase(this)">Add</a>
		<ul></ul>
	</div>
	
	<div class="default_exit">
		<label>Default Exit:</label>
		<div class="task_property_field default_property_exit" property_name="default[exit]" value=""></div>
		<div class="task_property_exit default_exit" exit_id="" exit_color="" exit_label="Default"></div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
</div>
