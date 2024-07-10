<div class="create_function_task_html">
	<div class="name">
		<label>Name:</label>
		<input class="task_property_field" name="name" type="text" value="" />
	</div>
	<div class="arguments">
		<label>Method Args:</label>
		<table class="function_args">
			<thead>
				<tr>
					<th class="name table_header">Var Name</th>
					<th class="value table_header">Var Value</th>
					<th class="var_type table_header">Var Type</th>
					<th class="icon_cell table_header"><span class="icon add" onClick="FunctionUtilObj.addNewMethodArg(this)">Add Method Arg</span></th>
				</tr>
			</thead>
			<tbody index_prefix="arguments"></tbody>
		</table>
	</div>
	<div class="comments">
		<label>Comments:</label>
		<textarea class="task_property_field" name="comments"></textarea>
	</div>
	<div class="code">
		<label>Edit code:</label>
		<span class="icon update" onClick="FunctionUtilObj.editMethodCode(this)">Edit Code</span>
		<textarea class="task_property_field function_code" name="code" /></textarea>
	</div>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
