<div class="db_table_connection_html">
	<div class="relationship_props">
		<div class="source"></div>
		<div class="relationship">
			<select></select>
		</div>
		<div class="target"></div>
	</div>
	
	<div class="buttons">
		<label>Foreign Keys:</label>
	</div>
	
	<table>
		<thead>
			<tr>
				<th class="source_column table_header"></th>
				<th class="target_column table_header"></th>
				<th class="table_attr_icons">
					<a class="icon add" onClick="DBTableTaskPropertyObj.addTableForeignKey()">ADD</a>
				</th>
			</tr>
		</thead>
		<tbody class="table_attrs">
			
		</tbody>
	</table>
	
	<div class="delete_connection_button">
		<button onClick="removeTableConnectionFromConnectionProperties(this)">Delete Connection Between Tables</button>
	</div>
</div>
