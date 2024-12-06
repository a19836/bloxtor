<div class="create_class_task_html">
	<div class="name">
		<label>Name:</label>
		<input class="task_property_field" name="name" type="text" value="" />
	</div>
	<div class="extends">
		<label>Extends:</label>
		<input class="task_property_field" name="extends" type="text" value="" />
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseClassName(this)" do_not_update_args="1">Search</span>
	</div>
	<div class="implements">
		<label>Implements:</label>
		<input class="task_property_field" name="implements" type="text" value="" />
		<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseClassName(this)" do_not_update_args="1">Search</span>
	</div>
	<div class="abstract">
		<label>Is Abstract:</label>
		<input class="task_property_field" name="abstract" type="checkbox" value="1" />
	</div>
	<div class="interface">
		<label>Is Interface:</label>
		<input class="task_property_field" name="interface" type="checkbox" value="1" />
	</div>
	<div class="trait">
		<label>Is Trait:</label>
		<input class="task_property_field" name="trait" type="checkbox" value="1" />
	</div>
	<div class="properties">
		<label>Properties:</label>
		<table>
			<thead>
				<tr>
					<th class="name table_header">Name</th>
					<th class="value table_header">Value</th>
					<th class="type table_header">Type</th>
					<th class="static table_header">Static</th>
					<th class="var_type table_header">Var Type</th>
					<th class="comments table_header">Comments</th>
					<th class="icon_cell table_header"><span class="icon add" onClick="CreateClassTaskPropertyObj.addProperty(this)">Add Property</span></th>
				</tr>
			</thead>
			<tbody index_prefix="properties">
				<tr class="empty"><td colspan="7">There are no properties...</td></tr>
			</tbody>
		</table>
	</div>
	<div class="methods">
		<label>Methods: <span class="icon add" onClick="CreateClassTaskPropertyObj.addMethod(this)">Add Method</span></label>
		<ul index_prefix="methods"></ul>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
