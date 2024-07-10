<div class="add_ui_table_attribute">
	Choose an UI Attribute to add: 
	<select></select>
	<i class="icon add" onClick="PresentationTaskUtil.addUITableAttribute(this)"></i>
</div>

<div class="ui_html">Please select first a table in the settings tab...</div>

<div class="ui_table_attribute_properties_popup myfancypopup">
	<div class="type">
		<label>Input Type: </label>
		<select class="#task_property_field_class#" name="attributes[#attribute_idx#][type]">
			<option value="">-- Default --</option>
			<option>text</option>
			<option>select</option>
			<option>checkbox</option>
			<option>radio</option>
			<option>textarea</option>
			<option>password</option>
			<option>file</option>
			<option>search</option>
			<option>email</option>
			<option>url</option>
			<option>tel</option>
			<option>number</option>
			<option>range</option>
			<option>date</option>
			<option>month</option>
			<option>week</option>
			<option>time</option>
			<option>datetime</option>
			<option>datetime-local</option>
			<option>color</option>
			<option>hidden</option>
			<option>button</option>
			<option>submit</option>
			<option>button_img</option>
			<option>link</option>
			<option>image</option>
			<option>label</option>
			<option>h1</option>
			<option>h2</option>
			<option>h3</option>
			<option>h4</option>
			<option>h5</option>
		</select>
	</div>
	
	<div class="class">
		<label>Field Class:</label>
		<input class="#task_property_field_class#" name="attributes[#attribute_idx#][class]" />
	</div>
	
	<ul>
		<li><a href="#ui_table_attribute_properties_list_settings">List Settings</a></li>
		<li><a href="#ui_table_attribute_properties_link_settings">Link Settings</a></li>
		<li><a href="#ui_table_attribute_properties_label_settings">Label Settings</a></li>
		<li><a href="#ui_table_attribute_properties_input_settings">Input Settings</a></li>
	</ul>
	
	<div class="ui_table_attribute_properties_settings" id="ui_table_attribute_properties_list_settings">
		<div class="list_type">	
			<label>Relate this attribute with a: </label>
			<select class="#task_property_field_class#" name="attributes[#attribute_idx#][list_type]" onChange="PresentationTaskUtil.updateUITableAttributeListType(this)">
				<option value=""></option>
				<option value="from_db">List from a DB Table</option>
				<option value="manual">Manual List</option>
			</select>
		</div>
		
		<ul class="list_from_db">
			<li class="db_driver">
				<label>DB Driver:</label>
				<select class="#task_property_field_class#" name="attributes[#attribute_idx#][db_driver]" onChange="PresentationTaskUtil.updateDBTables(this)"></select>
			</li>
			<li class="db_type">
				<label>Type:</label>
				<select class="#task_property_field_class#" name="attributes[#attribute_idx#][db_type]" onChange="PresentationTaskUtil.updateDBTables(this)">
					<option value="db">From DB Server</option>
					<option value="diagram">From DB Diagram</option>
				</select>
			</li>
			<li class="db_table">
				<label>Table:</label>
				<select class="#task_property_field_class#" name="attributes[#attribute_idx#][db_table]" onChange="PresentationTaskUtil.updateDBAttributes(this)"></select>
			</li>
			<li class="db_table_alias">
				<label>Table Alias:</label>
				<input class="#task_property_field_class#" name="attributes[#attribute_idx#][db_table_alias]" />
			</li>
			<li class="db_attribute db_attribute_label">
				<label>Attribute Label:</label>
				<select class="#task_property_field_class#" name="attributes[#attribute_idx#][db_attribute_label]" default_attribute="#attribute#"></select>
			</li>
			<li class="db_attribute db_attribute_fk">
				<label>Attribute FK:</label>
				<select class="#task_property_field_class#" name="attributes[#attribute_idx#][db_attribute_fk]" default_attribute="#attribute#"></select>
			</li>
		</ul>
		
		<table class="manual_list">
			<thead>
				<tr>
					<th class="value">Value</th>
					<th class="label">Label</th>
					<th class="actions">
						<i class="icon add" onclick="PresentationTaskUtil.addUITableAttributeManualListItem(this)"></i>
					</th>
				</tr>
			</thead>
			<tbody index_prefix="attributes[#attribute_idx#][manual_list]">
				<tr class="no_items"><td colspan="3">No items</td></tr>
			</tbody>
		</table>
	</div>
	
	<div class="ui_table_attribute_properties_settings" id="ui_table_attribute_properties_label_settings">
		<div class="label_class">
			<label>Label Class:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][label_class]" />
		</div>
		
		<div class="label_value">
			<label>Label Value:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][label_value]" placeHolder="Leave empty for default" />
		</div>
		
		<div class="label_previous_html">
			<label>Label Previous Html:</label>
			<textarea class="#task_property_field_class#" name="attributes[#attribute_idx#][label_previous_html]"></textarea>
		</div>
		
		<div class="label_next_html">
			<label>Label Next Html:</label>
			<textarea class="#task_property_field_class#" name="attributes[#attribute_idx#][label_next_html]"></textarea>
		</div>
	</div>
	
	<div class="ui_table_attribute_properties_settings" id="ui_table_attribute_properties_input_settings">	
		<div class="input_class">
			<label>Input Class:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][input_class]" />
		</div>
		
		<div class="input_value">
			<label>Input Value:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][input_value]" placeHolder="Leave empty for default" />
		</div>
		
		<div class="input_previous_html">
			<label>Input Previous Html:</label>
			<textarea class="#task_property_field_class#" name="attributes[#attribute_idx#][input_previous_html]"></textarea>
		</div>
		
		<div class="input_next_html">
			<label>Input Next Html:</label>
			<textarea class="#task_property_field_class#" name="attributes[#attribute_idx#][input_next_html]"></textarea>
		</div>
	</div>
	
	<div class="ui_table_attribute_properties_settings" id="ui_table_attribute_properties_link_settings">
		<div class="link">
			<label>Link:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][link]" />
			<i class="icon search" onclick="PresentationTaskUtil.onChooseLinkPageUrl(this)">Search</i>
		</div>
		
		<div class="target">
			<label>Link Target:</label>
			<input class="#task_property_field_class#" name="attributes[#attribute_idx#][target]" />
		</div>
	</div>
</div>
