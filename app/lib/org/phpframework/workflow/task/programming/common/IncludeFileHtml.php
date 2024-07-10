<div class="include_file">
	<label>File Path: <span class="icon edit edit_source" onClick="ProgrammingTaskUtil.onEditIncludeFile(this)" title="Edit file">Edit</span></label>
	<input type="text" class="task_property_field" name="include_file_path" />
	<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
	<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>
	<select class="task_property_field" name="include_file_path_type">
		<option></option>
		<option>string</option>
		<option>variable</option>
	</select>
	<input type="checkbox" class="task_property_field once" name="include_once" value="1" title="Check here to active the include ONCE feature" checked>
</div>
