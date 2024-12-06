<div class="create_form_task_html">
	
	<div class="info">This task needs the file 'lib/org/phpframework/util/web/html/HtmlFormHandler.php' to be included before!</div>
	
	<div class="form_settings">
		<label>Settings: </label>
		<input type="text" class="task_property_field" name="form_settings_data" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="form_settings_data_type" onChange="CreateFormTaskPropertyObj.onChangeFormSettingsType(this);">
			<option value="">code</option>
			<option>string</option>
			<option>variable</option>
			<option>array</option>
			<option>settings</option>
			<option>ptl</option>
		</select>
	</div>
	
	<div class="form_settings_data array_items"></div>
	
	<div class="ptl_settings">
		<textarea class="task_property_field"></textarea><!-- Name will be defined via javascript otherwise it will give an exception when creating the createform task -->
		
		<!-- MY LAYOUT UI EDITOR -->
		<div class="layout-ui-editor ptl_ui reverse fixed-properties hide-template-widgets-options with_top_bar_menu">
			<ul class="menu-widgets hidden"></ul><!--  Menu widgets will be added later -->
			<div class="template-source"><textarea></textarea></div>
		</div>
		
		<div class="input_data_var_name">
			<label>Input Data Var Name:</label>
			<input type="text" class="task_property_field" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		</div>
		<div class="idx_var_name">
			<label>Idx Var Name:</label>
			<input type="text" class="task_property_field" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		</div>
		
		<div class="ptl_external_vars array_items"></div>
	</div>
		
	<div class="inline_settings">
		<div class="with_form">
			<label>With or Without Form:</label>
			<select class="task_property_field" name="with_form" onChange="CreateFormTaskPropertyObj.onChangeWithForm(this)">
				<option value="1">With Form</option>
				<option value="0">Without Form</option>
			</select>
		</div>
		<div class="form_id">
			<label>Form Id:</label>
			<input type="text" class="task_property_field" name="form_id" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		</div>
		<div class="form_method">
			<label>Form Method:</label>
			<select class="task_property_field" name="form_method">
				<option>post</option>
				<option>get</option>
			</select>
		</div>
		<div class="form_class">
			<label>Form Class:</label>
			<input type="text" class="task_property_field" name="form_class" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		</div>
		<div class="form_type" title="This attribute only works if the Bootsstrap Library is included!">
			<label>Form Type:</label>
			<select class="task_property_field" name="form_type">
				<option value="">Vertical</option>
				<option value="horizontal">Horizontal</option>
				<option value="inline">Inline</option>
			</select>
		</div>
		<div class="form_on_submit">
			<label>Form On Submit:</label>
			<input type="text" class="task_property_field" name="form_on_submit" title="return (typeof MyJSLib == 'undefined' || MyJSLib.FormHandler.formCheck(this));" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		</div>
		<div class="form_action">
			<label>Form Action:</label>
			<input type="text" class="task_property_field" name="form_action" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<span class="icon search" onclick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)" title="Search Page">Search page</span>
		</div>
	
		<div class="form_containers">
			<label>Containers:</label>
			<span class="icon add" onClick="FormFieldsUtilObj.addContainer(this, 'form_containers');">add container</span>
			
			<div class="fields"></div>
		</div>
		
		<div class="form_css">
			<label>Form CSS:</label>
			<textarea class="task_property_field" name="form_css"></textarea>
			<textarea class="editor"></textarea>
		</div>
		
		<div class="form_js">
			<label>Form JS:</label>
			<textarea class="task_property_field" name="form_js"></textarea>
			<textarea class="editor"></textarea>
		</div>
	</div>
	
	<hr class="separate_settings_from_input" /> 
	
	<div class="form_input">
		<label>Form Input Data: </label>
		<input type="text" class="task_property_field" name="form_input_data" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="form_input_data_type" onChange="CreateFormTaskPropertyObj.onChangeFormInputType(this);">
			<option value="">code</option>
			<option>variable</option>
			<option>array</option>
			<option value="string">string - N/A</option>
		</select>
	</div>
	
	<div class="form_input_data array_items"></div>
	
	<hr class="separate_input_from_result" /> 
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
