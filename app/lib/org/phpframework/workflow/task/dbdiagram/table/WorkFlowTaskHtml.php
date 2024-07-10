<?php
include_once get_lib("org.phpframework.db.DB");

$column_types = DB::getAllSharedColumnTypes();
$numeric_column_types = DB::getAllSharedColumnNumericTypes();
$column_types_ignored_props = DB::getAllSharedColumnTypesIgnoredProps();
$column_types_hidden_props = DB::getAllSharedColumnTypesHiddenProps();
$charsets = null;
$table_collations = null;
$column_collations = null;
$table_storage_engines = null;
$column_simple_types = DB::getAllSharedColumnSimpleTypes();

echo '<script>
//These types will be re-defined again in the diagram.php according with the correspondent DB DRIVER. Only define here, if not yet defined. Note that this will be called everytime that the Task Table Properties gets loaded!
DBTableTaskPropertyObj.column_types = DBTableTaskPropertyObj.column_types ? DBTableTaskPropertyObj.column_types : ' . json_encode($column_types) . ';
DBTableTaskPropertyObj.column_simple_types = DBTableTaskPropertyObj.column_simple_types ? DBTableTaskPropertyObj.column_simple_types : ' . json_encode($column_simple_types) . ';
DBTableTaskPropertyObj.column_numeric_types = DBTableTaskPropertyObj.column_numeric_types ? DBTableTaskPropertyObj.column_numeric_types : ' . json_encode($numeric_column_types) . ';
DBTableTaskPropertyObj.column_types_ignored_props = DBTableTaskPropertyObj.column_types_ignored_props ? DBTableTaskPropertyObj.column_types_ignored_props : ' . json_encode($column_types_ignored_props) . ';
DBTableTaskPropertyObj.column_types_hidden_props = DBTableTaskPropertyObj.column_types_hidden_props ? DBTableTaskPropertyObj.column_types_hidden_props : ' . json_encode($column_types_hidden_props) . ';
DBTableTaskPropertyObj.charsets = DBTableTaskPropertyObj.charsets ? DBTableTaskPropertyObj.charsets : ' . json_encode($charsets) . ';
DBTableTaskPropertyObj.table_collations = DBTableTaskPropertyObj.table_collations ? DBTableTaskPropertyObj.table_collations : ' . json_encode($table_collations) . ';
DBTableTaskPropertyObj.column_collations = DBTableTaskPropertyObj.column_collations ? DBTableTaskPropertyObj.column_collations : ' . json_encode($column_collations) . ';
DBTableTaskPropertyObj.table_storage_engines = DBTableTaskPropertyObj.table_storage_engines ? DBTableTaskPropertyObj.table_storage_engines : ' . json_encode($table_storage_engines) . ';
</script>';
?>
<div class="db_table_task_html simple_ui_shown attributes_table_shown">
	<div class="table_name">
		<label>Table Name:</label>
		<input type="text" name="table_name" value="" />
	</div>
	
	<ul>
		<li><a href="#simple_ui" onClick="DBTableTaskPropertyObj.updateSimpleAttributesHtmlWithTableAttributes(this)">Simple UI</a></li>
		<li><a href="#advanced_ui" onClick="DBTableTaskPropertyObj.updateTableAttributesHtmlWithSimpleAttributes(this)">Advanced UI</a></li>
	</ul>
	
	<div id="simple_ui">
		<div class="simple_attributes">
			<label>Table Attributes: <a class="icon add" onClick="DBTableTaskPropertyObj.addSimpleAttribute(this)" title="Add new Attribute">ADD</a></label>
			
			<ul>
				<li class="no_simple_attributes">There are no attributes defined!</li>
			</ul>
		</div>
	</div>
	
	<div id="advanced_ui">
		<div class="table_charset">
			<label>Table Charset:</label>
			<select class="task_property_field" name="table_charset"></select>
		</div>
		
		<div class="table_collation">
			<label>Table Collation:</label>
			<select class="task_property_field" name="table_collation"></select>
		</div>
		
		<div class="table_storage_engine">
			<label>Table Storage Engine:</label>
			<select class="task_property_field" name="table_storage_engine"></select>
		</div>
		
		<div class="attributes">
			<label>Table Attributes: <a class="icon add" onClick="DBTableTaskPropertyObj.addTableAttribute(this)" title="Add new attribute">ADD</a> <a class="icon switch" onClick="DBTableTaskPropertyObj.toggleTableAndListView(this)" title="toggle from table to list view and vice-versa">CONVERT</a></label>
		</div>
		
		<div class="responsive_table">
			<table>
				<thead>
					<tr>
						<th class="table_attr_primary_key table_header">PK</th>
						<th class="table_attr_name table_header">Name</th>
						<th class="table_attr_type table_header">Type</th>
						<th class="table_attr_length table_header">Length</th>
						<th class="table_attr_null table_header">Null</th>
						<th class="table_attr_unsigned table_header">Unsigned</th>
						<th class="table_attr_unique table_header">Unique</th>
						<th class="table_attr_auto_increment table_header">Auto Increment</th>
						<th colspan="2" class="table_attr_default table_header">Default</th>
						<th class="table_attr_extra table_header">Extra</th>
						<th class="table_attr_charset table_header">Charset</th>
						<th class="table_attr_collation table_header">Collation</th>
						<th class="table_attr_comment table_header">Comments</th>
						<th class="table_attr_icons">
							<a class="icon add" onClick="DBTableTaskPropertyObj.addTableAttribute(this)">ADD</a>
						</th>
					</tr>
				</thead>
				<tbody class="table_attrs"></tbody>
			</table>
		</div>
		
		<div class="list_attributes">
			<ul class="list_attrs"></ul>
		</div>
	</div>
	
	<div class="task_property_exit" exit_id="layer_exit" exit_color="#31498f" exit_type="Flowchart" exit_overlay="No Arrows"></div>
</div>
