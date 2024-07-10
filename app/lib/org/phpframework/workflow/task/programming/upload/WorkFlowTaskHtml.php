<div class="upload_task_html">
	
	<div class="info">This task needs the file 'lib/org/phpframework/util/web/UploadHandler.php' to be included before! If is not included yet, please add it by clicking <a class="include_file_before" href="javascript:void(0)" onClick="ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskFromSelectedTaskProperties(UploadTaskPropertyObj.dependent_file_path_to_include, '', 1)">here</a>. 
	</div>
	
	<div class="file" title="example: $_FILES['field_name']">
		<label>File Props:</label>
		<input type="text" class="task_property_field" name="file" placeHolder="$_FILES['field_name']" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="file_type">
			<option>string</option>
			<option selected>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="dst_folder" title="Folder path where the uploaded file should be moved">
		<label>Destination Folder:</label>
		<input type="text" class="task_property_field" name="dst_folder" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFolderPath(this)">Search</span>
		<select class="task_property_field value_type" name="dst_folder_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="vldt_type">
		<label class="main_label">Validation Type:</label>
		<input type="text" class="task_property_field validation_code" name="validation" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field validation_type" name="validation_type" onChange="UploadTaskPropertyObj.onChangeValidationTypeType(this)">
			<option selected>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>array</option>
		</select>
		<div class="validation array_items"></div>
		<div class="info">Please write the allowed mime-types or one of the following types: image, video, text, doc.</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
