<div class="clear"></div>
<div class="result">
	<div class="result_var_type" title="Assign to a variable the return value of method.">
		<label>Please choose the result variable type:</label>
		<select onChange="ProgrammingTaskUtil.onChangeResultVariableType(this);" class="task_property_field" name="result_var_type">
			<option value="">no variable</option>
			<option value="variable">simple variable</option>
			<option value="obj_prop">object property</option>
			<option value="echo">echo/print</option>
			<option value="return">return</option>
		</select>
	</div>
	<div class="type_variable">
		<div class="result_var_name">
			<label>Variable Name:</label>
			<input type="text" class="task_property_field" name="result_var_name" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		</div>
		<div class="result_var_assignment">
			<label>Assignment:</label>
			<select class="task_property_field" name="result_var_assignment">
				<option>default</option>
				<option>concatenate</option>
				<option>increment</option>
				<option>decrement</option>
			</select>
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
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseObjectProperty(this)">Search</span>
		</div>
		<div class="result_static">
			<label>Is Property Static:</label>
			<input type="checkbox" class="task_property_field" name="result_static" value="1" />
		</div>
		<div class="result_prop_assignment">
			<label>Assignment:</label>
			<select class="task_property_field" name="result_prop_assignment">
				<option>default</option>
				<option>concatenate</option>
				<option>increment</option>
				<option>decrement</option>
			</select>
		</div>
	</div>
	<div class="type_echo">
		<input type="hidden" class="task_property_field" name="result_echo" />
	</div>
	<div class="type_return">
		<input type="hidden" class="task_property_field" name="result_return" />
	</div>
</div>
