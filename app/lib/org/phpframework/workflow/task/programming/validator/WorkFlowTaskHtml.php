<div class="validator_task_html">
	
	<div class="info">This task needs the file 'lib/org/phpframework/util/text/TextValidator.php' to be included before! If is not included yet, please add it by clicking <a class="include_file_before" href="javascript:void(0)" onClick="ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskFromSelectedTaskProperties(ValidatorTaskPropertyObj.dependent_file_path_to_include, '', 1)">here</a>. 
	</div>
	
	<div class="method">
		<label>Check if:</label>
		<select class="task_property_field" name="method" onChange="ValidatorTaskPropertyObj.onChangeMethodName(this)">
			<optgroup label="Value Validations">
				<option value="TextValidator::isBinary">value is an binary</option>
				<option value="TextValidator::isEmail">value is an email</option>
				<option value="TextValidator::isDomain">value is a domain</option>
				<option value="TextValidator::isPhone">value is a phone number</option>
				<option value="TextValidator::isNumber">value is a number</option>
				<option value="TextValidator::isDecimal">value is decimal</option>
				<option value="TextValidator::isSmallInt">value is a small int</option>
				<option value="TextValidator::isDate">value is a date</option>
				<option value="TextValidator::isDateTime">value is a date time</option>
				<option value="TextValidator::isTime">value is a time</option>
				<option value="TextValidator::isIPAddress">value is an ip address</option>
				<option value="TextValidator::isFileName">value is a file name</option>
				
				<option value="TextValidator::checkMinLength">value has min length</option>
				<option value="TextValidator::checkMaxLength">value has max length</option>
				<option value="TextValidator::checkMinValue">value has min value</option>
				<option value="TextValidator::checkMaxValue">value has max value</option>
				<option value="TextValidator::checkMinWords">value has min words</option>
				<option value="TextValidator::checkMaxWords">value has max words</option>
				<option value="TextValidator::checkMinDate">value has min date</option>
				<option value="TextValidator::checkMaxDate">value has max date</option>
			</optgroup>
			
			<option value="" disabled></option>
			
			<optgroup label="Other Validations">
				<option value="ObjTypeHandler::isPHPTypeNumeric">type is php numeric</option>
				<option value="ObjTypeHandler::isDBTypeNumeric">type is db numeric</option>
				<option value="ObjTypeHandler::isDBTypeDate">type is db date</option>
				<option value="ObjTypeHandler::isDBTypeText">type is db text</option>
				<option value="ObjTypeHandler::isDBTypeBlob">type is db blob</option>
				<option value="ObjTypeHandler::isDBTypeBoolean">type is db boolean</option>
				<option value="ObjTypeHandler::isDBAttributeNameATitle">attribute name is a db title</option>
				<option value="ObjTypeHandler::isDBAttributeNameACreatedDate">attribute name is a db created date</option>
				<option value="ObjTypeHandler::isDBAttributeNameAModifiedDate">attribute name is a db modified date</option>
				<option value="ObjTypeHandler::isDBAttributeValueACurrentTimestamp">attribute value is a db current timestamp</option>
				<option value="ObjTypeHandler::isDBAttributeNameACreatedUserId">attribute value is a db created user id</option>
				<option value="ObjTypeHandler::isDBAttributeNameAModifiedUserId">attribute value is a db modified user id</option>
			</optgroup>
		</select>
	</div>
	<div class="value">
		<label>Value:</label>
		<input type="text" class="task_property_field" name="value" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="value_type">
			<option>string</option>
			<option selected>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="offset">
		<label>Offset:</label>
		<input type="text" class="task_property_field" name="offset" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="offset_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
