<?php
class CMSPresentationLayerJoinPointsUIHandler {
	
	public static function convertBlockSettingsArrayToObj($arr) {
		$obj = array();
	
		if (is_array($arr)) {
			$i = 0;
			
			foreach ($arr as $item) {
				if (empty($item["key"]) && isset($item["key_type"]) && $item["key_type"] == "null") {
					if (isset($item["items"]))
						$obj[$i] = self::convertBlockSettingsArrayToObj($item["items"]);
					else
						$obj[$i] = array(
							"value" => isset($item["value"]) ? $item["value"] : null, 
							"value_type" => isset($item["value_type"]) ? $item["value_type"] : null
						);
					
					$i++;
				}
				else {
					$k = isset($item["key"]) ? $item["key"] : null;
					$obj[$k] = $item;
			
					if (isset($obj[$k]["items"]))
						$obj[$k]["items"] = self::convertBlockSettingsArrayToObj($obj[$k]["items"]);
					
					if (is_numeric($k) && (int)$k >= $i) //in case the $k is a numeric value, we need to update the $i with $k + 1
						$i = (int)$k + 1;
				}
			}
		}
		
		return $obj;
	}
	
	public static function getHeader() {
		return '
		<script>
			var join_points_html = \'' . addcslashes(str_replace("\n", "", self::getJoinPointMethodHtml()), "\\'") . '\';
			var input_mapping_from_join_point_to_method_item_html = \'' . addcslashes(str_replace("\n", "", self::getInputMappingFromJoinPointToMethodHtml()), "\\'") . '\';
			var method_arg_html = \'' . addcslashes(str_replace("\n", "", self::getMethodArgHtml()), "\\'") . '\';
			var output_mapping_from_method_to_join_point_item_html = \'' . addcslashes(str_replace("\n", "", self::getOutputMappingFromMethodToJoinPointHtml()), "\\'") . '\';
		</script>';
	}
	
	public static function getRegionBlocksJoinPointsJavascriptObjs($regions_blocks_join_points) {
		$blocks_join_points_settings_objs = array();
		
		if (is_array($regions_blocks_join_points)) {
			foreach ($regions_blocks_join_points as $region => $region_blocks_join_points) {
				foreach ($region_blocks_join_points as $block => $blocks_join_points) {
					foreach ($blocks_join_points as $rb_index => $block_join_points) {
						foreach ($block_join_points as $block_join_point) {
							$join_point_name = isset($block_join_point["join_point_name"]) ? $block_join_point["join_point_name"] : null;
							
							if ($join_point_name) {
								$join_point_settings = isset($block_join_point["join_point_settings"]["key"]) ? array($block_join_point["join_point_settings"]) : (isset($block_join_point["join_point_settings"]) ? $block_join_point["join_point_settings"] : null);
								$join_point_settings_obj = self::convertBlockSettingsArrayToObj($join_point_settings);
								
								$blocks_join_points_settings_objs[$region][$block][$rb_index][$join_point_name][] = $join_point_settings_obj;
							}
						}
					}
				}
			}
		}
		//echo "<pre>";print_r($blocks_join_points_settings_objs);die();
		
		return '
		<script>
			var blocks_join_points_settings_objs = prepareBlocksJoinPointsSettingsObjs(' . json_encode($blocks_join_points_settings_objs) . ');
		</script>';
	}
	
	public static function getBlockJoinPointsJavascriptObjs($block_join_points, $block_local_join_points = null) {
		$block_join_points_settings_objs = array();
		
		if (is_array($block_join_points)) {
			foreach ($block_join_points as $block_join_point) {
				$join_point_name = isset($block_join_point["join_point_name"]) ? $block_join_point["join_point_name"] : null;
				
				if ($join_point_name) {
					$join_point_settings = isset($block_join_point["join_point_settings"]["key"]) ? array($block_join_point["join_point_settings"]) : (isset($block_join_point["join_point_settings"]) ? $block_join_point["join_point_settings"] : null);
					$join_point_settings_obj = self::convertBlockSettingsArrayToObj($join_point_settings);
	
					$block_join_points_settings_objs[$join_point_name][] = $join_point_settings_obj;
				}
			}
		}
		
		$available_block_local_join_point = array();
		if (is_array($block_local_join_points)) {
			foreach ($block_local_join_points as $block_local_join_point) {
				if (!empty($block_local_join_point["join_point_name"])) {
					$available_block_local_join_point[ $block_local_join_point["join_point_name"] ] = true;
				}
			}
		}
		
		//echo "<pre>";print_r($block_join_points_settings_objs);echo "</pre>";die();
		//echo "<pre>";print_r($available_block_local_join_point);echo "</pre>";die();
		
		return '
		<script>
			var block_join_points_settings_objs = prepareBlockJoinPointsSettingsObjs(' . json_encode($block_join_points_settings_objs) . ');
			var available_block_local_join_point = ' . json_encode($available_block_local_join_point) . ';
		</script>';
			
	}
	
	public static function getBlockJoinPointsHtml($module_join_points, $block_id, $add_default = false, $show_module_handler_source_code = false) {
		$html = '';
		
		if ($module_join_points) {
			$html .= '<div class="module_join_points">
					<label>Module\'s Join Points:</label>';
			
			if ($block_id && $show_module_handler_source_code) {
				$html .= '<span class="view_module_source_code" onClick="openModuleSourceCode(this, \'' . $block_id . '\')">View join points in the module\'s source code</span>
					
					<div class="module_source_code">
						<span class="icon close" onClick="closeModuleSourceCode(this)"></span>
						<textarea readonly="readonly"></textarea>
					</div>';
			}
			
			$html .= '
					<div class="join_points">';
			
			$t = count($module_join_points);
			for ($i = 0; $i < $t; $i++) {
				$join_point = $module_join_points[$i];
				$join_point_name = isset($join_point["join_point_name"]) ? $join_point["join_point_name"] : null;
				
				if ($join_point_name) {
					$join_point_description = isset($join_point["join_point_description"]) ? $join_point["join_point_description"] : null;
					$join_point_method = isset($join_point["method"]) ? $join_point["method"] : null;
					$join_point_settings = isset($join_point["join_point_settings"]) ? $join_point["join_point_settings"] : null;
					
					$prefix = 'join_point[' . $join_point_name . ']';
					//echo "<pre>";print_r($join_point);die();
					
					$html .= '
							<div class="join_point" joinPointName="' . $join_point_name . '" prefix="' . $prefix . '">
								<label><span>' . $join_point_name . '</span></label>
								<select class="module_join_points_property join_point_active" name="' . $prefix . '[active]" onChange="onChangeJoinPointActive(this);">
									<option value="0">Inactive</option>
									<option value="1">Active - Only here</option>
									<option value="2">Active - Here and on Page Level</option>
								</select>
								<span class="icon maximize" onClick="maximizeJoinPointsSettings(this)" title="Maximize/Minimize join point methods">Toggle</span>
								<span class="icon add" onClick="addJoinPointMethod(this, \'' . $prefix . '\')" title="Add new join point method">Add</span>
								<span class="icon info" onClick="showJoinPointDetails(this)" title="Show join point details">Info</span>
								<div class="join_point_details">
									<div class="join_point_description">
										<label>Join Point Description: "' . $join_point_description . '"</label>
									</div>
									<div class="join_point_method_type">
										<label>Join Point Method Type: "' . $join_point_method . '"</label>
									</div>
									<div class="join_point_args">
										<label>Join Point Method Args: </label>
										<table>
											<tr>
												<th class="table_header key">Key</th>
												<th class="table_header value">Value</th>
												<th class="table_header type">Type</th>
											</tr>';
					if (is_array($join_point_settings)) {
						$join_point_settings = self::convertBlockSettingsArrayToObj($join_point_settings);
						//echo "<pre>";print_r($join_point_settings);die();
				
						foreach ($join_point_settings as $join_point_setting_name => $join_point_setting) {
							$join_point_setting_items = isset($join_point_setting["items"]) ? $join_point_setting["items"] : null;
							$join_point_setting_value = isset($join_point_setting["value"]) ? $join_point_setting["value"] : null;
							$join_point_setting_value_type = isset($join_point_setting["value_type"]) ? $join_point_setting["value_type"] : null;
							
							$value = $join_point_setting_items ? json_encode($join_point_setting_items) : $join_point_setting_value;
							$value_type = $join_point_setting_items ? "array" : $join_point_setting_value_type;
							$value_type = !$value_type && is_numeric($value) ? "numeric" : $value_type;
					
							$html .= '		<tr>
												<td class="key">' . $join_point_setting_name . '</td>
												<td class="value">' . $value . '</td>
												<td class="type">' . $value_type . '</td>
											</tr>';
						}
					}
					else {
						$html .= '			<tr class="empty_table">
												<td colspan="3">Empty Args...</td>
											</tr>';
					}	
			
					$html .= '						
										</table>
									</div>
								</div>
						
								<div class="empty_items">There are NO available elements for this join point... <br/>Please click in the add icon to add a new join point method.</div>
								' . ($add_default ? str_replace("#prefix#", $prefix . "[0]", self::getJoinPointMethodHtml()) : '') . '
							</div>';
				}
			}
			//echo "<pre>";print_r($module_join_points);echo "</pre>";
	
			$html .= '
					</div>
				</div>';
		}
		
		return $html;
	}
	
	public static function getJoinPointMethodHtml() {
		$html = '
		<div class="join_point_method">
			<label>Join Point Method</label>
			<span class="icon delete" onClick="removeJoinPointMethod(this)" title="Remove">Remove</span>
						
			<div class="method_file">
				<label>Method File: </label>
				<input class="module_join_points_property" type="text" name="#prefix#[method_file]" value="" />
				<span class="icon add_variable inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				<span class="icon search" onclick="onIncludeFileTaskChooseFile(this)" title="Choose a file to include">Search</span>
			</div>
			
			<div class="method_type">
				<label>Type: </label>
				<select class="module_join_points_property" name="#prefix#[method_type]" onChange="onChangeJoinPointMethodType(this);">
					<option value="function">Function</option>
					<option value="method">Object Method</option>
				</select>
			</div>
	
			<div class="method_obj">
				<label>Method Obj: </label>
				<input class="module_join_points_property" type="text" name="#prefix#[method_obj]" value="" />
				<span class="icon add_variable inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable"><Search Variable</span>
			</div>
	
			<div class="method_name">
				<label>Method Name: </label>
				<input class="module_join_points_property" type="text" name="#prefix#[method_name]" value="" />
				<span class="icon add_variable inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				<span class="icon search" onClick="onChooseJoinPointMethodOrFunction(this)" title="Search Method">Search</span>
			</div>
	
			<div class="method_static">
				<label>Is Method Static: </label>
				<input class="module_join_points_property" type="checkbox" name="#prefix#[method_static]" value="1" />
			</div>
	
			<div class="input_mapping">
				<label>Input mapping from join point to method: </label>
				<span class="info">This mapping consists in translating the input array from the module to the user method input...</span>
				<table>
					<tr>
						<th class="table_header join_point_input">Join Point Input</th>
						<th class="table_header from_to"></th>
						<th class="table_header method_input">Method Input</th>
						<th class="table_header erase_from_input" title="Erase item from input array">Erase</th>
						<th class="table_header icons">
							<span class="icon add" onClick="addJoinPointTableItem(this, \'#prefix#[input_mapping]\', input_mapping_from_join_point_to_method_item_html)" title="Add">Add</span>
						</th>
					</tr>
					<tr class="empty_table">
						<td colspan="5">No items...</td>
					</tr>
				</table>
			</div>
	
			<div class="method_args">
				<label>Method Args: </label>
				<span class="info">Here is where you assign the input items to the correspondent method args, based in the "$input" variable.</span>
				<table>
					<tr>
						<th class="table_header value">Value</th>
						<th class="table_header type">Type</th>
						<th class="table_header icons">
							<span class="icon add" onClick="addJoinPointTableItem(this, \'#prefix#[method_args]\', method_arg_html)" title="Add">Add</span>
						</th>
					</tr>
					<tr class="empty_table hidden">
						<td colspan="3">No items...</td>
					</tr>'
					. str_replace("#prefix#", "#prefix#[method_args][0]", self::getMethodArgHtml(array(
						"value" => '\\$input',
						"type" => "",
					))) . '
				</table>
			</div>
	
			<div class="output_mapping">
				<label>Input mapping from join point to method: </label>
				<span class="info">Here is where you manage the method\'s output result so it can be correctly used in the module.</span>
				<table>
					<tr>
						<th class="table_header method_output">Method Output</th>
						<th class="table_header from_to"></th>
						<th class="table_header join_point_output">Join Point Output</th>
						<th class="table_header erase_from_output" title="Erase item from output array">Erase</th>
						<th class="table_header icons">
							<span class="icon add" onClick="addJoinPointTableItem(this, \'#prefix#[output_mapping]\', output_mapping_from_method_to_join_point_item_html)" title="Add">Add</span>
						</th>
					</tr>
					<tr class="empty_table">
						<td colspan="5">No items...</td>
					</tr>
				</table>
			</div>
		</div>';
	
		return $html;
	}

	public static function getInputMappingFromJoinPointToMethodHtml($data = null) {
		return '
		<tr>
			<td class="join_point_input">
				$input["
				<input class="module_join_points_property" type="text" name="#prefix#[join_point_input]" value="' . (isset($data["join_point_input"]) ? $data["join_point_input"] : "") . '" />
				<span class="icon add_variable small inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				"]
			</td>
			<td class="from_to">=&gt;</td>
			<td class="method_input">
				$input["
				<input class="module_join_points_property" type="text" name="#prefix#[method_input]" value="' . (isset($data["method_input"]) ? $data["method_input"] : "") . '" />
				<span class="icon add_variable small inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				"]
			</td>
			<td class="erase_from_input">
				<input class="module_join_points_property" type="checkbox" name="#prefix#[erase_from_input]" value="1" title="Erase item from input array" ' . (!isset($data["erase_from_input"]) || $data["erase_from_input"] ? "checked" : "") . ' />
			</td>
			<td class="icons">
				<span class="icon delete" onClick="removeJoinPointTableItem(this)" title="Remove">Remove</span>
			</td>
		</tr>';
	}

	public static function getMethodArgHtml($data = null) {
		$type = isset($data["type"]) ? $data["type"] : null;
		
		return '
		<tr>
			<td class="value">
				<input class="module_join_points_property" type="text" name="#prefix#[value]" value="' . (isset($data["value"]) ? $data["value"] : "") . '" />
				<span class="icon add_variable small inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
			</td>
			<td class="type">
				<select class="module_join_points_property" name="#prefix#[type]">
					<option value="">code</option>
					<option ' . ($type == "string" ? "selected" : "") . '>string</option>
					<option ' . ($type == "variable" ? "selected" : "") . '>variable</option>
				</select>
			</td>
			<td class="icons">
				<span class="icon delete" onClick="removeJoinPointTableItem(this)" title="Remove">Remove</span>
			</td>
		</tr>';
	}

	public static function getOutputMappingFromMethodToJoinPointHtml($data = null) {
		return '
		<tr>
			<td class="method_output">
				$output["
				<input class="module_join_points_property" type="text" name="#prefix#[method_output]" value="' . (isset($data["method_output"]) ? $data["method_output"] : "") . '" />
				<span class="icon add_variable small inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				"]
			</td>
			<td class="from_to">=&gt;</td>
			<td class="join_point_output">
				$output["
				<input class="module_join_points_property" type="text" name="#prefix#[join_point_output]" value="' . (isset($data["join_point_output"]) ? $data["join_point_output"] : "") . '" />
				<span class="icon add_variable small inline" onclick="onProgrammingTaskChooseCreatedVariable(this)" title="Choose a variable">Search Variable</span>
				"]
			</td>
			<td class="erase_from_output">
				<input class="module_join_points_property" type="checkbox" name="#prefix#[erase_from_output]" value="1" title="Erase item from output array" ' . (!isset($data["erase_from_output"]) || $data["erase_from_output"] ? "checked" : "") . ' />
			</td>
			<td class="icons">
				<span class="icon delete" onClick="removeJoinPointTableItem(this)" title="Remove">Remove</span>
			</td>
		</tr>';
	}
}
?>
