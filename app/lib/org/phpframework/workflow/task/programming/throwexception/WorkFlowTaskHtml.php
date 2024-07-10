<div class="throw_exception_task_html">
	<div class="exception_type">
		<label>Exception Type:</label>
		<select class="task_property_field" name="exception_type" onChange="ThrowExceptionTaskPropertyObj.onChangeExceptionType(this);">
			<option value="existent">Throw Existent Variable</option>
			<option value="new">Throw New Exception</option>
		</select>
	</div>
	
	<hr/>
	
	<div class="class_name">
		<label>Exception Class Name:</label>
		<input type="text" class="task_property_field" name="class_name" />
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseClassName(this)">Search</span>
	</div>
	<div class="class_args">
		<label>Exception Class Args:</label>
		<div class="args"></div>
	</div>
	
	<div class="exception_var_name">
		<label>Variable Name:</label>
		<input type="text" class="task_property_field" name="exception_var_name" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
	</div>
	<div class="exception_var_type">
		<label>Variable Type: </label>
		<select class="task_property_field" name="exception_var_type" onChange="showOrHideDateButton(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	
	<div class="result">
		<div class="result_var_type" title="Assign to a variable the return value of method.">
			<label>Please choose the result variable type:</label>
			<select onChange="ProgrammingTaskUtil.onChangeResultVariableType(this);" class="task_property_field" name="result_var_type">
				<option value="">no variable</option>
				<option value="variable">simple variable</option>
				<option value="obj_prop">object property</option>
			</select>
		</div>
		<div class="type_variable">
			<div class="result_var_name">
				<label>Variable Name:</label>
				<input type="text" class="task_property_field" name="result_var_name" />
				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
			</div>
		</div>
		<div class="type_obj_prop">
			<div class="result_obj_name" title="The object should be a variable; or a class name in case of a static property.">
				<label>Object:</label>
				<input type="text" class="task_property_field" name="result_obj_name" />
				<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
			</div>
			<div class="result_prop_name">
				<label>Property Name:</label>
				<input type="text" class="task_property_field" name="result_prop_name" />
				<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseObjectProperty(this)">Search</span>
			</div>
			<div class="result_static">
				<label>Is Property Static:</label>
				<input type="checkbox" class="task_property_field" name="result_static" value="1" />
			</div>
		</div>
	</div>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
