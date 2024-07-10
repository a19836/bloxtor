<?php
$head = '
<!-- Add ACE editor -->
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
<script src="' . $project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>

<!-- Add DataTables Plugin -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerydatatables/media/css/jquery.dataTables.min.css" charset="utf-8" />
<script src="' . $project_common_url_prefix . 'vendor/jquerydatatables/media/js/jquery.dataTables.min.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/execute_sql.css" charset="utf-8" />
<script src="' . $project_url_prefix . 'js/db/execute_sql.js"></script>';

$main_content .= '
	<div class="top_bar' . ($popup ? ' in_popup' : '') . '">
		<header>
			<div class="title">Execute SQL in DB: \'' . $bean_name . '\'</div>
			<ul>
				<li class="execute" data-title="Execute"><a onClick="execute()"><i class="icon continue"></i> Execute</a></li>
			</ul>
		</header>
	</div>

	<div class="sql_text_area' . ($popup ? ' in_popup' : '') . '">
		<textarea>' . "\n" . htmlspecialchars($sql, ENT_NOQUOTES) . '</textarea>
	</div>
	<div class="sql_results' . ($popup ? ' in_popup' : '') . '">
		<table class="display compact">';

$fields = $results["fields"];
$rows = $results["result"];

if ($fields) {
	$main_content .= '<thead><tr>';
	$t = count($fields);
	for ($i = 0; $i < $t; $i++) {
		$name = $fields[$i]->name;
	
		$main_content .= '<th class="table_header">' . $name . '</th>';
	}
	$main_content .= '</tr></thead>';
}
$main_content .= '<tbody>';

if ($_POST) {
	if ($exception_message) {
		$main_content .= '<tr>
			<td class="message error">
				' . $exception_message . '
			</td>
		</tr>';
	}
	else if (!$is_select_sql) {
		$message = $results ? "SQL executed successfully." : "SQL executed unsuccessfully.";
		
		$main_content .= '<tr>
			<td class="message ' . ($results ? 'success' : 'error') . '" colspan="' . ($fields ? count($fields) : 0) . '">
				' . $message . '
				<script>
					if (typeof window.parent.refreshAndShowLastNodeChilds == "function")
						window.parent.refreshAndShowLastNodeChilds();
				</script>
			</td>
		</tr>';
	}
	else if (!$results || !is_array($rows) || empty($rows))
		$main_content .= '<tr><td class="empty" colspan="' . ($fields ? count($fields) : 0) . '">Empty results...</tr>';
	else {
		$t = count($rows);
		for ($i = 0; $i < $t; $i++) {
			$row = $rows[$i];
			
			$main_content .= '<tr>';
			foreach ($row as $column_name => $column_value)
				$main_content .= '<td>' . $column_value . '</td>';
			
			$main_content .= '</tr>';
		}
	}
}
else
	$main_content .= '<tr><td class="empty">Click in the button above to execute the query and show its results...</tr>';
	
$main_content .= '</tbody>
		</table>
	</div>
	
	<script>
		createSQLEditor();
	</script>
';
?>
