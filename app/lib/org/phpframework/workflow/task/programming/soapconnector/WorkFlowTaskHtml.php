<div class="soap_connector_task_html">
	
	<div class="info">This task needs the file 'lib/org/phpframework/connector/SoapConnector.php' to be included before! If is not included yet, please add it by clicking <a class="include_file_before" href="javascript:void(0)" onClick="ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskFromSelectedTaskProperties(SoapConnectorTaskPropertyObj.dependent_file_path_to_include, '', 1)">here</a>. 
	</div>
	
	<div class="data">
		<label>Data:</label>
		<input type="text" class="task_property_field" name="data" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="data_type" onChange="SoapConnectorTaskPropertyObj.onChangeDataType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>options</option>
		</select>
	</div>
	<div class="type">
		<label>Type:</label>
		<input type="text" class="task_property_field type_code" name="data[type]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="type_options" name="data[type]" onChange="SoapConnectorTaskPropertyObj.onChangeTypeOptions(this)">
			<option value="callSoapFunction">Call Soap Function</option>
			<option value="callSoapClient">Call Soap Client</option>
		</select>
		<select class="task_property_field type_type" name="data[type_type]" onChange="SoapConnectorTaskPropertyObj.onChangeType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>options</option>
		</select>
	</div>
	<div class="wsdl_url">
		<label>Wsdl Url:</label>
		<input type="text" class="task_property_field" name="data[wsdl_url]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="data[wsdl_url_type]">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
		<span class="icon search" onclick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)">Search</span>
	</div>
	<div class="client_options">
		<label>Client options:</label>
		<input type="text" class="task_property_field" name="data[options]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="data[options_type]" onChange="SoapConnectorTaskPropertyObj.onChangeClientOptions(this)">
			<option>variable</option>
			<option value="">code</option>
			<option>options</option>
		</select>
		<table>
			<thead>
				<tr>
					<th class="name table_header">Name</th>
					<th class="value table_header">Value</th>
					<th class="var_type table_header">Type</th>
					<th class="icon_cell table_header"><span class="icon add" onClick="SoapConnectorTaskPropertyObj.addNewOption(this)">Add Option</span></th>
				</tr>
			</thead>
			<tbody index_prefix="data[options]"></tbody>
		</table>
	</div>
	<div class="client_headers">
		<label>Client Headers: </label>
		<input type="text" class="task_property_field" name="data[headers]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="data[headers_type]" onChange="SoapConnectorTaskPropertyObj.onChangeClientHeaders(this)">
			<option>variable</option>
			<option value="">code</option>
			<option>options</option>
		</select>
		<span class="icon add" onClick="SoapConnectorTaskPropertyObj.addNewHeader(this)">Add Header</span>
		<ul index_prefix="data[headers]"></ul>
	</div>
	
	<div class="remote_function_name">
		<label>Remote Function Name:</label>
		<input type="text" class="task_property_field" name="data[remote_function_name]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="data[remote_function_name_type]">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="remote_function_arguments">
		<label>Remote Function Args:</label>
		<input type="text" class="task_property_field remote_function_args_code" name="data[remote_function_args]" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field remote_function_args_type" name="data[remote_function_args_type]" onChange="SoapConnectorTaskPropertyObj.onChangeRemoteFunctionArgsType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>array</option>
		</select>
		<div class="remote_function_args array_items"></div>
	</div>
	
	<div class="result_type">
		<label>Result Type: </label>
		<input type="text" class="task_property_field" name="result_type" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select name="result_type">
			<option></option>
			<option value="content">content text</option>
			<option value="content_json">content json</option>
			<option value="content_xml">content xml parsed to array</option>
			<option value="content_xml_simple">content xml parsed to simple array</option>
			<option value="content_serialized">content serialized</option>
			<option>settings</option>
		</select>
		<select class="task_property_field" name="result_type_type" onChange="SoapConnectorTaskPropertyObj.onChangeResultType(this)">
			<option>options</option>
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
		<div class="info">
			The result type can have 3 values:
			<ul>
				<li>blank value: which will return a associative array with the request header, html contents, errors...</li>
				<li>"header": which will return a associative array with the request headers.</li>
				<li>"content": which will return request html contents.</li>
			</ul>
		</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
