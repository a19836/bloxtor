<div class="get_block_id_from_file_path_task_html">
	<div class="broker_method_obj" title="Write here the CMS Block Layer variable">
		<label>CMS Block Layer Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="value">
		<label>File Path:</label>
		<input type="text" class="task_property_field" name="value" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
	</div>
	<div class="type">
		<label>Type: </label>
		<select class="task_property_field" name="type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
