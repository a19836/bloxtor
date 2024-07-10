<div class="call_object_method_task_html">
	<?php include dirname(dirname($file_path)) . "/common/IncludeFileHtml.php"; ?>
	
	<div class="method_obj_name" title="The object should be a variable; or a class name in case of a static method.">
		<label>Method Obj: <span class="icon edit edit_source" onClick="CallObjectMethodTaskPropertyObj.onEditClass(this)" title="Edit object">Edit</span></label>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
	</div>
	<div class="method_name">
		<label>Method Name: <span class="icon edit edit_source" onClick="CallObjectMethodTaskPropertyObj.onEditMethod(this)" title="Edit method logic">Edit</span></label>
		<input type="text" class="task_property_field" name="method_name" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseObjectMethod(this)">Search</span>
	</div>
	<div class="method_static">
		<label>Is Method static:</label>
		<input type="checkbox" class="task_property_field" name="method_static" value="1" />
	</div>
	<div class="method_args">
		<label>Method Args:</label>
		<div class="args"></div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
