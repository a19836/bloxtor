<div class="include_file_task_html">
	<div class="file_path">
		<label>File Path: <span class="icon edit edit_source" onClick="IncludeFileTaskPropertyObj.onEditFile(this)" title="Edit file">Edit</span></label>
		<input type="text" class="task_property_field" name="file_path" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>
	</div>
	<div class="type">
		<label>Type:</label>
		<select class="task_property_field" name="type">
			<option>string</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="once">
		<label>Once:</label>
		<input type="checkbox" class="task_property_field" name="once" value="1" title="Include Once" />
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
