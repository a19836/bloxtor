<div class="choose_db_table">
	<label>Please select a DB Table: <i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedChooseTableSettings(this)"></i></label>
	
	<ul>
		<li class="db_driver">
			<label>DB Driver:</label>
			<select class="task_property_field" name="choose_db_table[db_driver]" onChange="PresentationTaskUtil.updateDBTables(this)"></select>
		</li>
		<li class="db_type">
			<label>Type:</label>
			<select class="task_property_field" name="choose_db_table[db_type]" onChange="PresentationTaskUtil.updateDBTables(this)">
				<option value="db">From DB Server</option>
				<option value="diagram">From DB Diagram</option>
			</select>
		</li>
		<li class="db_table">
			<label>Table:</label>
			<select class="task_property_field" name="choose_db_table[db_table]" onChange="PresentationTaskUtil.onChangeDBTable(this)" updateTableUIHandler=""></select>
		</li>
		<li class="db_table_alias advanced_table_settings">
			<label>Table Alias:</label>
			<input class="task_property_field" name="choose_db_table[db_table_alias]" />
		</li>
		<li class="db_table_parent advanced_table_settings">
			<span class="info">Relate the Main Table above with another Parent Table, this is, join the Main Table based on the parent Parent Table.</span>
			<label>Table Parent:</label>
			<select class="task_property_field" name="choose_db_table[db_table_parent]" onChange="PresentationTaskUtil.onChangeDBTableParent(this)"></select>
		</li>
		<li class="db_table_parent_alias advanced_table_settings">
			<label>Table Parent Alias:</label>
			<input class="task_property_field" name="choose_db_table[db_table_parent_alias]" />
		</li>
		<li class="db_table_conditions advanced_table_settings">
			<label>Conditions:</label>
			<table>
				<thead>
					<tr>
						<th class="attribute">Attribute</th>
						<th class="value">Value</th>
						<th class="actions">
							<i class="icon add" onclick="PresentationTaskUtil.addDBTableCondition(this)"></i>
						</th>
					</tr>
				</thead>
				<tbody index_prefix="choose_db_table[db_table_conditions]">
					<tr class="no_conditions"><td colspan="3">No conditions</td></tr>
				</tbody>
			</table>
		</li>
	</ul>
</div>
