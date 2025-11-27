/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

var blocks_handler_source_codes = {};
var page_blocks_join_points_htmls = {};
var page_blocks_join_points_htmls_loading = {};
var page_blocks_join_points_htmls_loading_interval_id = {};
var blocks_join_points_settings_objs = {};
var regions_blocks_join_points_settings_latest_objs = {};

$(function () {
	resizeModuleHandlerImplFileContents();
	
	$(window).resize(function() {
		resizeModuleHandlerImplFileContents();
	});
	
	if (!chooseFileFromFileManagerTree) {
		chooseFileFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTree,
		});
		chooseFileFromFileManagerTree.init("choose_file_from_file_manager");
	}
	
	if (!chooseFolderFromFileManagerTree) {
		chooseFolderFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeAllThatIsNotFoldersFromTree,
		});
		chooseFolderFromFileManagerTree.init("choose_folder_from_file_manager");
	}
	
	if (!chooseMethodFromFileManagerTree) {
		chooseMethodFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForMethods,
		});
		chooseMethodFromFileManagerTree.init("choose_method_from_file_manager");
	}
	
	if (!chooseFunctionFromFileManagerTree) {
		chooseFunctionFromFileManagerTree = new MyTree({
			multiple_selection : false,
			toggle_selection : false,
			toggle_children_on_click : true,
			ajax_callback_before : prepareLayerNodes1,
			ajax_callback_after : removeObjectPropertiesAndMethodsAndFunctionsFromTreeForFunctions,
		});
		chooseFunctionFromFileManagerTree.init("choose_function_from_file_manager");
	}
});

/* JOIN POINTS FUNCTIONS  */

function resizeModuleHandlerImplFileContents() {
	var w = $(window);
	
	$(".module_join_points > .module_source_code > textarea").css({
		"width": (w.width() - 20) + "px",
		"height": (w.height() - 20) + "px",
	});
}

function openModuleSourceCode(elm, block_id) {
	if (block_id) {
		var module_source_code = $(elm).parent().children(".module_source_code");
		module_source_code.fadeIn("fast", function() {
			var textarea = module_source_code.children("textarea");
		
			if (textarea.val() == "" && get_block_handler_source_code_url) {
				MyFancyPopup.init({
					parentElement: window,
				});
				MyFancyPopup.showLoading();
				
				if (blocks_handler_source_codes.hasOwnProperty(block_id))
					textarea.val( blocks_handler_source_codes[block_id] );
				else
					$.ajax({
						type : "get",
						url : get_block_handler_source_code_url.replace("#block#", block_id),
						dataType : "text",
						success : function(data, textStatus, jqXHR) {
							blocks_handler_source_codes[block_id] = data;
							textarea.val(data);
					
							MyFancyPopup.hidePopup();
						},
						error : function(jqXHR, textStatus, errorThrown) { 
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							StatusMessageHandler.showError("Error trying to get module file contents.\nPlease try again..." + msg);
				
							MyFancyPopup.hidePopup();
						},
					});
			}
		});
	}
}

function closeModuleSourceCode(elm) {
	$(elm).parent().fadeOut("fast");
}

//used in CMSPresentationLayerJoinPointsUIHandler::getRegionBlocksJoinPointsJavascriptObjs method
function prepareBlocksJoinPointsSettingsObjs(objs) {
	//console.log(objs);
	if ($.isPlainObject(objs))
		$.each(objs, function(r, r_items) {
			if ($.isPlainObject(r_items))
				$.each(r_items, function(b, b_items) {
					if ($.isPlainObject(b_items) || $.isArray(b_items))
						$.each(b_items, function(rb_index, rb_items) {
							objs[r][b][rb_index] = prepareBlockJoinPointsSettingsObjs(rb_items);
						});
				});
		});
	//console.log(objs);
	
	return objs;
}
//used in CMSPresentationLayerJoinPointsUIHandler::getBlockJoinPointsJavascriptObjs method
function prepareBlockJoinPointsSettingsObjs(objs) {
	//console.log(objs);
	if ($.isPlainObject(objs))
		$.each(objs, function(join_point_name, join_points) {
			if ($.isPlainObject(join_points) || $.isArray(join_points))
				$.each(join_points, function(j, join_points_settings) {
					objs[join_point_name][j] = convertBlockSettingsValuesIntoBasicArray(join_points_settings);
				});
		});
	//console.log(objs);
	
	return objs;
}

function onLoadRegionBlocksJoinPoints(region_blocks_elm) {
	//console.log(blocks_join_points_settings_objs);
	
	var objs = getRegionBlockItems(region_blocks_elm);
	objs = objs[0];
	//console.log(objs);
	
	var items = region_blocks_elm.find(".template_region_items .template_region_item");
	//console.log(items);
	for (var i = 0; i < items.length; i++) {
		var item = items[i];
		var item_obj = objs[i];
		var is_html = $(item).children(".type").val() == 1;
		
		if (item_obj && !is_html) {
			var region = getArgumentCode(item_obj["region"], item_obj["region_type"]);
			var block = getArgumentCode(item_obj["block"], item_obj["block_type"]);
			
			onLoadRegionBlockJoinPoints(item, region, block);
		}
	}
}

function onLoadRegionBlockJoinPoints(item, region, block) {
	if (item) {
		item = $(item);
		item.children(".module_join_points").remove();//remove if exists => important when change of blocks
		
		if (region && block) {
			var b = ("" + block).substr(0, 1) == '"' ? ("" + block).replace(/"/g, "") : block;
			
			if (b) {
				var handler = function(data) {
					if (data) {
						item.append(data);
						
						resizeModuleHandlerImplFileContents();
						
						var join_points_elms = item.find(".module_join_points > .join_points > .join_point");
						join_points_elms.children("select.join_point_active").remove();
						join_points_elms.children(".icon").show();
						
						var rb_index = item.attr("rb_index");
						var block_join_points_settings_objs = getRegionBlockJoinPoints(region, block, rb_index);
						
						if (block_join_points_settings_objs)
							onLoadBlockJoinPoints(join_points_elms, block_join_points_settings_objs, null);
					}
				};
				
				if (page_blocks_join_points_htmls.hasOwnProperty(b))
					handler( page_blocks_join_points_htmls[b] );
				else if (page_blocks_join_points_htmls_loading[b]) { //Bc this function will get executed asynchronous, we need to be sure that we don't get multiple requests to the server of the same thing.
					page_blocks_join_points_htmls_loading_interval_id[b] = setInterval(function() {
						if (!page_blocks_join_points_htmls_loading[b]) {
							clearInterval(page_blocks_join_points_htmls_loading_interval_id[b]);
							
							page_blocks_join_points_htmls_loading_interval_id[b] = null;
							delete page_blocks_join_points_htmls_loading_interval_id[b];
							
							handler( page_blocks_join_points_htmls[b] );
						}
					}, 500);
				}
				else {
					page_blocks_join_points_htmls_loading[b] = true;
					
					$.ajax({
						type : "get",
						url : get_page_block_join_points_html_url.replace("#block#", b),
						dataType : "text",
						success : function(data, textStatus, jqXHR) {
							page_blocks_join_points_htmls[b] = data;
						
							page_blocks_join_points_htmls_loading[b] = null;
							delete page_blocks_join_points_htmls_loading[b];
							
							handler(data);
						},
						error : function(jqXHR, textStatus, errorThrown) { 
							page_blocks_join_points_htmls_loading[b] = null;
							delete page_blocks_join_points_htmls_loading[b];
							
							var msg = jqXHR.responseText ? "\n" + jqXHR.responseText : "";
							StatusMessageHandler.showError("Error trying to get block join points.\nPlease try again..." + msg);
						},
					});
				}
			}
		}
	}
}

function getRegionBlockJoinPoints(region, block, rb_index) {
	var objs = null;
	
	if ($.isNumeric(rb_index)) {
		objs = regions_blocks_join_points_settings_latest_objs && regions_blocks_join_points_settings_latest_objs.hasOwnProperty(region) && regions_blocks_join_points_settings_latest_objs[region].hasOwnProperty(block) && regions_blocks_join_points_settings_latest_objs[region][block].hasOwnProperty(rb_index) && regions_blocks_join_points_settings_latest_objs[region][block][rb_index] ? regions_blocks_join_points_settings_latest_objs[region][block][rb_index] : {};
		
		if (!objs || $.isEmptyObject(objs))
			objs = blocks_join_points_settings_objs && blocks_join_points_settings_objs.hasOwnProperty(region) && blocks_join_points_settings_objs[region] && blocks_join_points_settings_objs[region].hasOwnProperty(block) && blocks_join_points_settings_objs[region][block] && blocks_join_points_settings_objs[region][block][rb_index] ? blocks_join_points_settings_objs[region][block][rb_index] : null;
	}
	
	return objs;
}

function onLoadBlockJoinPoints(block_join_points_elms, block_join_points_settings_objs, available_block_local_join_point) {
	//console.log(block_join_points_elms);
	//console.log(block_join_points_settings_objs);
	//console.log(available_block_local_join_point);
	
	if (block_join_points_elms) {
		for (var i = 0; i < block_join_points_elms.length; i++) {
			var join_point_elm = $(block_join_points_elms[i]);
			var join_point_name = join_point_elm.attr("joinPointName");
			var block_join_points_settings_obj = $.isPlainObject(block_join_points_settings_objs) && ($.isArray(block_join_points_settings_objs[join_point_name]) || $.isPlainObject(block_join_points_settings_objs[join_point_name])) ? block_join_points_settings_objs[join_point_name] : null;
			
			var join_point_active_elm = join_point_elm.children("select.join_point_active");
			if (join_point_active_elm[0]) {
				var active_value = available_block_local_join_point && available_block_local_join_point.hasOwnProperty(join_point_name) ? 2 : (block_join_points_settings_obj ? 1 : 0);
				
				join_point_active_elm.val(active_value);
				if (active_value == 1 || active_value == 2) {
					join_point_elm.children(".icon").show();
				}
			}
			
			if (block_join_points_settings_obj) {
				var join_point_prefix = join_point_elm.attr("prefix");
				var join_point_add_icon = join_point_elm.children(".add")[0];
				
				//Note that block_join_points_settings_obj could be an array or an object with indexes
				$.each(block_join_points_settings_obj, function(j, block_join_points_settings) {
					//console.log(block_join_points_settings);
					
					var join_point_method_elm = addJoinPointMethod(join_point_add_icon, join_point_prefix);
				
					if (join_point_method_elm) {
						join_point_method_elm.children(".method_file").children("input").val( block_join_points_settings["method_file"] );
						
						var select = join_point_method_elm.children(".method_type").children("select");
						select.val( block_join_points_settings["method_type"] );
						onChangeJoinPointMethodType(select[0]);
					
						join_point_method_elm.children(".method_obj").children("input").val( block_join_points_settings["method_obj"] );
					
						join_point_method_elm.children(".method_name").children("input").val( block_join_points_settings["method_name"] );
						if (parseInt(block_join_points_settings["method_static"]) == 1)
							join_point_method_elm.children(".method_static").children("input").attr("checked", "checked").prop("checked", true);
					
						loadJoinPointMappingTableItems(block_join_points_settings, join_point_method_elm, join_point_prefix + "[" + j + "]", "input_mapping", input_mapping_from_join_point_to_method_item_html);
					
						loadJoinPointMappingTableItems(block_join_points_settings, join_point_method_elm, join_point_prefix + "[" + j + "]", "method_args", method_arg_html);
					
						loadJoinPointMappingTableItems(block_join_points_settings, join_point_method_elm, join_point_prefix + "[" + j + "]", "output_mapping", output_mapping_from_method_to_join_point_item_html);
					}
				});
				
				maximizeJoinPointsSettings(join_point_elm.children(".minimize")[0]);
			}
		}
	}
}

function loadJoinPointMappingTableItems(block_join_points_settings, join_point_method_elm, join_point_prefix, type, item_html) {
	var items = block_join_points_settings[type];
	
	var mapping_elm = join_point_method_elm.children("." + type);
	var trs = mapping_elm.find("table tr");
	for (var i = 2; i < trs.length; i++) {
		$(trs[i]).remove();//remove all default items less the header and empty table tr.
	}
	$(trs[1]).show();//show empty values tr
	
	if ($.isArray(items) || $.isPlainObject(items)) {
		var prefix = join_point_prefix + "[" + type + "]";
		var mapping_add_icon = mapping_elm.find("table th.icons .add").first()[0];
		
		//Note that items could be an array or an object with indexes
		$.each(items, function(i, item) {
			var mapping_item = addJoinPointTableItem(mapping_add_icon, prefix, item_html);
			
			if (mapping_item) {
				var item = items[i];
				
				for (var attr_name in item) {
					var input = mapping_item.children("." + attr_name).children("input, select");
					var value = item[attr_name];
					
					if (input.attr("type") == "checkbox") {
						if (input.val() == value)
							input.attr("checked", "checked").prop("checked", true);
						else
							input.removeAttr("checked").prop("checked", false);
					}
					else {
						input.val(value);
					}
				}
			}
		});
	}
}

function updateRegionsBlocksJoinPointsSettingsLatestObjs(regions_blocks_includes_settings) {
	regions_blocks_join_points_settings_latest_objs = {};
	
	var region_blocks_items = getRegionBlockItems( regions_blocks_includes_settings.find(".region_blocks") );
	var other_region_blocks_items = getRegionBlockItems( regions_blocks_includes_settings.find(".other_region_blocks") );
	
	var items = [region_blocks_items, other_region_blocks_items];
	
	$.each(items, function(idx, rb_items) {
		var region_blocks_items_values = rb_items[0];
		var region_blocks_items_join_points = rb_items[2];
		
		for (var i = 0; i < region_blocks_items_join_points.length; i++) {
			var region_block_item_join_points = region_blocks_items_join_points[i];
			
			if (region_block_item_join_points.length) {
				var region_block_item_values = region_blocks_items_values[i];
				var rb_index = region_block_item_values["rb_index"];
				
				if ($.isNumeric(rb_index)) {
					var r = getArgumentCode(region_block_item_values["region"], region_block_item_values["region_type"]);
					var b = getArgumentCode(region_block_item_values["block"], region_block_item_values["block_type"]);
					
					for (var j = 0; j < region_block_item_join_points.length; j++) {
						var join_points = region_block_item_join_points[j]["join_points"];
						
						if ($.isPlainObject(join_points) && !$.isEmptyObject(join_points))
							for (var jp_name in join_points) {
								var jp_value = join_points[jp_name];
								
								if (!regions_blocks_join_points_settings_latest_objs.hasOwnProperty(r))
									regions_blocks_join_points_settings_latest_objs[r] = {};
								
								if (!regions_blocks_join_points_settings_latest_objs[r].hasOwnProperty(b))
									regions_blocks_join_points_settings_latest_objs[r][b] = {};
								
								if (!regions_blocks_join_points_settings_latest_objs[r][b].hasOwnProperty(rb_index))
									regions_blocks_join_points_settings_latest_objs[r][b][rb_index] = {};
								
								regions_blocks_join_points_settings_latest_objs[r][b][rb_index][jp_name] = jp_value;
							}
					}
				}
			}
		}
	});
}

function onChangeJoinPointActive(elm) {
	elm = $(elm);
	
	var value = elm.val();
	var icon = elm.parent().children(".icon");
	
	if (value == 1 || value == 2) {
		icon.show();
		
		if (icon.hasClass("maximize"))
			maximizeJoinPointsSettings(icon[0]);
	}
	else {
		icon.hide();
		
		if (icon.hasClass("minimize"))
			maximizeJoinPointsSettings(icon[0]);
	}
}

function maximizeJoinPointsSettings(elm) {
	elm = $(elm);
	
	var p = elm.parent();
	var items = p.children(".join_point_method");
	items = items.length == 0 ? p.children(".empty_items") : items;
	
	if (elm.hasClass("maximize")) {
		elm.removeClass("maximize").addClass("minimize");
		items.show();
	}
	else {
		elm.removeClass("minimize").addClass("maximize");
		items.hide();
	}
}

function showJoinPointDetails(elm) {
	$(elm).parent().children(".join_point_details").toggle();
}

function addJoinPointMethod(elm, prefix) {
	var p = $(elm).parent();
	
	var count = parseInt(p.attr("count"));
	count = count ? count + 1 : p.children(".join_point_method").length;
	p.attr("count", count);
	
	prefix += "[" + count + "]";
	var item = $(join_points_html.replace(/#prefix#/g, prefix));
	
	p.append(item);
	
	p.children(".empty_items").hide();
	item.show();
	
	var icon = p.children(".maximize")[0];
	if (icon)
		maximizeJoinPointsSettings(icon);
	
	return item;
}

function removeJoinPointMethod(elm) {
	var item = $(elm).parent();
	var p = item.parent();
	
	item.remove();
	
	if (p.children(".join_point_method").length == 0) 
		p.children(".empty_items").show();
}

function onChangeJoinPointMethodType(elm) {
	elm = $(elm);
	
	if (elm.val() == "function")
		elm.parent().parent().children(".method_obj, .method_static").hide();
	else 
		elm.parent().parent().children(".method_obj, .method_static").show();
}

function onChooseJoinPointMethodOrFunction(elm) {
	var is_function = $(elm).parent().parent().find(".method_type select").val() == "function";
	
	var popup = is_function ? $("#choose_function_from_file_manager") : $("#choose_method_from_file_manager");
	
	MyFancyPopup.init({
		elementToShow: popup,
		parentElement: document,
		onOpen: function() {
			popup.find(".method").show();
		},
		
		targetField: $(elm).parent().find("input")[0],
		updateFunction: is_function ? chooseJoinPointFunction : chooseJoinPointMethod
	});
	
	MyFancyPopup.showPopup();
}

function chooseJoinPointFunction(elm) {
	var popup = $("#choose_function_from_file_manager");
	var select = popup.find(".function select");
	var value = select.val();
	
	var dest = $(MyFancyPopup.settings.targetField);
	dest.val(value ? value : "");
	
	//update include file and args
	chooseJoinPointMethodOrFunction(select);
	
	MyFancyPopup.hidePopup();
}

function chooseJoinPointMethod(elm) {
	var popup = $("#choose_method_from_file_manager");
	var select = popup.find(".method select");
	var value = select.val();
	
	var dest = $(MyFancyPopup.settings.targetField);
	dest.val(value ? value : "");
	
	if (value && dest.parent().hasClass("method_name")) {
		var p = dest.parent().parent();
		var obj_field = p.find(".method_obj input");
		var static_field = p.find(".method_static input");
		
		//update obj and method name
		updateObjNameAccordingWithObjectPropertySelection(select, obj_field, static_field);
		
		//update include file and args
		chooseJoinPointMethodOrFunction(select);
	}
	
	MyFancyPopup.hidePopup();
}

function chooseJoinPointMethodOrFunction(select) {	
	var value = select.val();
	
	if (value) {
		var dest = $(MyFancyPopup.settings.targetField);
		var p = dest.parent().parent();
		var is_function = p.find(".method_type select").val() == "function";
		var file_field = p.find(".method_file input");
		
		//update file path
		var node = is_function ? chooseFunctionFromFileManagerTree.getSelectedNodes() : chooseMethodFromFileManagerTree.getSelectedNodes();
		node = node[0];
		var include_path = getNodeIncludePath(node, select.attr("file_path"), select.attr("bean_name"));
		file_field.val(include_path);
		
		//Update join point method args
		if (confirm("Do you wish to update automatically this method arguments?")) {
			var args = is_function ? getFunctionArguments( select.attr("get_file_properties_url"), select.attr("file_path"), value) : getMethodArguments( select.attr("get_file_properties_url"), select.attr("file_path"), select.attr("class_name"), value);
	
			
			if (args) {
				args = args.hasOwnProperty("value") || args.hasOwnProperty("type") || args.hasOwnProperty("name") ? [args] : args;
				var table = p.find(".method_args table");
				var add_icon = table.find("th.icons .add").first();
				var trs = table.find("tr");
				
				//remove old trs
				for (var i = 2; i < trs.length; i++)
					$(trs[i]).remove();
				
				//add new trs
				for (var i in args) {
					var arg = args[i];
					var arg_value = getArgumentCode(arg["value"], arg["type"]);
					var place_holder = "$" + arg["name"] + (arg.hasOwnProperty("value") ? " = " + arg_value : "");
					
					add_icon.click();
					var tr = table.find("tr").last();
					tr.find(".value input").attr("placeHolder", place_holder).val("\\$input['" + arg["name"] + "']");
				}
			}
		}
	}
}

function addJoinPointTableItem(elm, prefix, item_html) {
	var table = $(elm).parent().parent().parent();
	
	var count = parseInt(table.attr("count"));
	count = count ? count + 1 : table.children("tr").length - 2;
	table.attr("count", count);
	
	prefix += "[" + count + "]";
	var item = $(item_html.replace(/#prefix#/g, prefix));
	
	table.append(item);
	table.children(".empty_table").hide();
	
	return item;
}

function removeJoinPointTableItem(elm) {
	var tr = $(elm).parent().parent();
	var table = tr.parent();
	
	tr.remove();
	
	if (table.children("tr").length == 2)
		table.children(".empty_table").show();
}

/* BLOCK SETTINGS FUNCTIONS */

function loadBlockSettings(settings_elm, settings_values) {
	if (settings_values) {
		var fields = settings_elm.find(".module_settings_property");
		for (var i = 0; i < fields.length; i++) {
			var field = $(fields[i]);
			var name = field.attr("name");
		
			if (settings_values.hasOwnProperty(name)) {
				var node_name = field[0].nodeName.toLowerCase();
			
				if (node_name == "input" || node_name == "select" || node_name == "textarea") {
					var value = prepareBlockSettingsItemValue(settings_values[name]);
					
					if (field.attr("type") == "checkbox" || field.attr("type") == "radio") {
						if (field.val() == value) {
							field.attr("checked", "checked").prop("checked", true);
						}
						else {
							field.removeAttr("checked").prop("checked", false);
						}
					}
					else if (!$.isArray(value) && !$.isPlainObject(value)) {
						if (field.attr("value_type") == "string") {
							value = prepareFieldValueIfValueTypeIsString(value);
						}
						
						if (node_name == "input") {
							field.val(value);//Do not add the .replace(/"/g, "&quot;") because the .val() function already takes care of this.
						}
						else {
							field.val(value);
						}
					}
					else {
						field.val("");
					}
				}
			}
		}
	}
}

//if value has hard-coded quotes and by default is a string, removes quotes
function prepareFieldValueIfValueTypeIsString(value) {
	if (value) {
		value = ("" + value);
		
		var fc = value.charAt(0);
		var lc = value.charAt(value.length - 1);
		
		//2019-10-17: Commented the replace \n and \t bc it might exist an intentional escaped end-line, which means this will NOT convert it to a real end-line and it will show the '\n' escaped. If the block_js or form_js have some code like this: "var x = '\n';", we still want to show the escaped the \n and not a end-line.
		//if (fc == '"' && lc == '"') 
			//value = value.replace(/\\t/g, "\t").replace(/\\n/g, "\n");
		
		//2020-01-12: The previous code was: /^"(.*)([^\\])"(.*)"$/.test(value), which means this wasn't work. I added the code for the single quotes too.
		//Checks if exists quotes in the beginning and end of the value. Addidionally checks if there are quotes in the middle and if they are escaped. If all these conditions are true, it means the value is a simple string writen as code, which means, we can simple strip the quotes and echo the value directly. Otherwise it means it is php code.
		//DO NOT USE /^"(.*)([^\\])"(.*)"$/.test(value) because if the value contains an end-line, this regex will never work!
		if (fc == '"' && lc == '"' && !/^"(.*)([^\\])"(.*)"$/.test(value.replace(/\n/g, ""))) 
			value = value.substr(1, value.length - 2).replace(/\\"/g, '"');
		else if (fc == "'" && lc == "'" && !/^'(.*)([^\\])'(.*)'$/.test(value.replace(/\n/g, ""))) 
			value = value.substr(1, value.length - 2).replace(/\\'/g, "'");
	}
	
	return value;
}

function prepareBlockSettingsItemValue(item) {
	if (item) {
		if (item["items"]) {
			var new_item = [];
			
			if ($.isArray(item["items"])) {
				var sub_items = item["items"];
				for (var i = 0; i < sub_items.length; i++) {
					new_item.push(prepareBlockSettingsItemValue(sub_items[i]));
				}
			}
			else if ($.isPlainObject(item["items"])) {
				new_item = {};
				
				var sub_items = item["items"];
				for (var key in sub_items) {
					new_item[key] = prepareBlockSettingsItemValue(sub_items[key]);
				}
			}
			
			return new_item;
		}
		else if ((item.hasOwnProperty("value") && !$.isArray(item["value"]) && !$.isPlainObject(item["value"])) || item.hasOwnProperty("value_type") && !$.isArray(item["value_type"]) && !$.isPlainObject(item["value_type"])) {//only enters here if exists value_type or value attributes, but only if there value is a string/numeric/bool, otherwise it means the item is an associative array with keys "value" or "value_type" and in this case we want to treat the item as a plain object and use the function: convertBlockSettingsValuesIntoBasicArray. (As it happens bellow) - DO NOT CHANGE THIS PLEASE
			if (item["value_type"] == "variable" && item["value"].substr(0, 1) != '$') {
				if (item["value"].substr(0, 2) == '@$')
					return item["value"];
				else if (item["value"].substr(0, 1) == '@')
					return "@$" + item["value"].substr(1);
				else
					return "$" + item["value"];
			}
			else {
				var value = item["value"];
				
				if (item["value_type"] == "string" && (("" + value).indexOf("'") != -1 || ("" + value).indexOf('"') != -1)) { //in case be an integer, it gives a javascript error, so we need to do: ("" + value).indexOf("'"). 
					value = '"' + addcslashes(value, '"') + '"';//Do not add the addcslashes($arg, '\\"') otherwise this will add an extra \\ to all the other \\. By default the $arg already contains the right number of slashes. The only missing slash is the ", because yo are editing php code directly.
				}
				return value;
			}
		}
		else if ($.isPlainObject(item)) {
			return convertBlockSettingsValuesIntoBasicArray(item);
		}
		
		return item;
	}
	return null;
}

function convertBlockSettingsValuesIntoBasicArray(settings_values) {
	var sv = {};
	
	if (settings_values) {
		for (var k in settings_values) {
			sv[k] = prepareBlockSettingsItemValue(settings_values[k]);
		}
	}
	return sv
}

function convertBlockSettingsValuesKeysToLowerCase(settings_values) {
	var sv = {};
	
	if (settings_values) {
		for (var k in settings_values) {
			var nk = "" + k;
			var v = settings_values[k];
			sv[ nk.toLowerCase() ] = $.isArray(v) || $.isPlainObject(v) ? convertBlockSettingsValuesKeysToLowerCase(v) : v;
		}
	}
	return sv
}

/* This function inverts the arrays conversion done from the method CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj in PHP. This should be used for all the values that are arrays this is x["items"]. 
Here is an example: 
	The CMSPresentationLayerJoinPointsUIHandler::convertBlockSettingsArrayToObj will convert the php code:
		"action_value" => array("name" => 12, "companies" => array("xxx" => "asd", "yyy"))
	to the json:
		action_value: {
			"key" => "name", 
			"key_type" => "string", 
			"items": {
				"name": {"key" => "name", "key_type" => "string", "value" => "12", "value_type" => ""},
				"companies": {
					"key" => "companies",
					"key_type" => "string",
					"items": {
						"xxx": {"key" => "xxx", "key_type" => "string", "value" => "asd", "value_type" => "string"},
						0: {"key" => "0", "key_type" => "string", "value" => "yyy", "value_type" => "string"}
					}
				},
			}
		}
	So we need to convert it back to:
			action_value: {
			"key" => "name", 
			"key_type" => "string", 
			"items": {
				"name": {"key" => "name", "key_type" => "string", "value" => "12", "value_type" => ""},
				"companies": {
					"key" => "companies",
					"key_type" => "string",
					"items": [
						{"key" => "xxx", "key_type" => "string", "value" => "asd", "value_type" => "string"},
						{"key" => "0", "key_type" => "string", "value" => "yyy", "value_type" => "string"}
					]
				},
			}
		}
	by calling:
		convertObjectIntoArray(obj["action_value"]["items"])

This function receives one argument that can have multiple different values, this is:
	{
		"name": {"key" => "name", "key_type" => "string", "value" => "jp", "value_type" => "string"},
		"age": {
			"key" => "age",
			"key_type" => "string",
			"items": {
				"age_1": {"key" => "age_1", "key_type" => "string", "value" => "jp", "value_type" => "string"},
				"age_1": {"key" => "age_2", "key_type" => "string", "value" => "jp", "value_type" => "string"}
			}
		},
		"height": {
			"key" => "height",
			"key_type" => "string",
			"items": [
				{"key" => "height_1", "key_type" => "string", "value" => "jp", "value_type" => "string"},
				{"key" => "height_2", "key_type" => "string", "value" => "jp", "value_type" => "string"}
			]
		}
	}
or
	[
		{
			"name": {"key" => "name", "key_type" => "string", "value" => "jp", "value_type" => "string"}
		},
		{
			"name": {"key" => "name", "key_type" => "string", "value" => "jp", "value_type" => "string"},
			"age": {
				"key" => "age",
				"key_type" => "string",
				"items": {
					"age_1": {"key" => "age_1", "key_type" => "string", "value" => "jp", "value_type" => "string"},
					"age_2": {"key" => "age_2", "key_type" => "string", "value" => "jp", "value_type" => "string"}
				}
			}
		}
	]
or 
	[
		{"key" => "name", "key_type" => "string", "value" => "jp", "value_type" => "string"},
		{"key" => "age", "key_type" => "string", "value" => "23", "value_type" => "string"}
	]
*/
function convertObjectIntoArray(obj) {
	var arr = [];
	
	//if this function is called directly from the SLA array action, the obj may be an array
	if ($.isArray(obj)) {
		for (var i = 0, t = obj.length; i < t; i++) {
			var v = obj[i];
			
			if ($.isPlainObject(v))
				for (var k in v) {
					var vv = v[k];
					
					if ($.isPlainObject(vv) && vv.hasOwnProperty("items") && vv["items"])
						v[k]["items"] = convertObjectIntoArray(vv["items"]);
				}
			
			arr.push(v);
		}
	}
	//otherwise is an object
	else if (!$.isEmptyObject(obj)) {
		for (var k in obj) {
			var v = obj[k];
			
			if ($.isPlainObject(v)) {
				if (v.hasOwnProperty("items") && v["items"]) {
					v["items"] = convertObjectIntoArray(v["items"]);
				}
			}
			
			arr.push(v);
		}
	}
	
	return arr;
}

function getArgumentType(arg) {
	return arg.indexOf("\n") > -1 ? "text" : (arg.substr(0, 1) == '"' ? "string" : (
		(arg.substr(0, 1) == '$' || arg.substr(0, 2) == '@$') && arg.indexOf("->") == -1 ? "variable" : "")
	);
}

function getArgumentCode(arg, arg_type) {
	if (typeof arg == "undefined" || arg == null) {
		return arg_type == "string" ? '""' : (!arg_type ? "null" : "");
	}
	else if (arg_type == "variable") {
		var arg_str = "" + arg;
		
		if (arg_str.substr(0, 1) == '$' || arg_str.substr(0, 2) == '@$')
			return arg;
		else if (arg_str.substr(0, 1) == '@')
			return "@$" + arg_str.substr(1);
		else
			return "$" + arg;
	}
	else if (arg_type == "string") {
		return '"' + addcslashes(arg, '"') + '"';//Do not add the addcslashes($arg, '\\"') otherwise this will add an extra \\ to all the other \\. By default the $arg already contains the right number of slashes. The only missing slash is the ", because yo are editing php code directly.
	}
	return arg;
}

/* SAVING FUNCTIONS */

function getBlockJoinPointsObjForSaving(settings_elm, fields_class_name) {
	var joint_points = getBlockSettingsObjForSaving(settings_elm, fields_class_name);
	joint_points = joint_points.hasOwnProperty("join_point") ? joint_points["join_point"] : null;
	//console.log(joint_points);
	
	return joint_points;
}

function getBlockSettingsObjForSaving(settings_elm, fields_class_name) {
	var obj = {};
	
	fields_class_name = fields_class_name ? fields_class_name : "module_settings_property";
	
	var fields = settings_elm.find("." + fields_class_name);
	var query_string = "";
	
	for (var i = 0; i < fields.length; i++) {
		var field = $(fields[i]);
		var name = field.attr("name");
		var node_name = field[0].nodeName.toLowerCase();
		var value_type = field.attr("value_type");
		var value = null;
		
		if (node_name == "input" || node_name == "select" || node_name == "textarea") {
			if (field.attr("type") == "checkbox" || field.attr("type") == "radio") {
				if (field.is(":checked")) {
					value = field[0].hasAttribute("value") ? field.val() : true;
				}
				else {
					value = "";//This should be empty and not null otherwise it messes the values from the checkboxes/radiobuttons when the field name is an array with numeric keys.
				}
			}
			else {
				value = field.val();
			}
		}
		else if (field[0].hasAttribute("value")) {
			value = field.attr("value");
		}
		else {
			value = field.html();
		}
		
		//only if doesn't contains already quotes because we may want to execute some php code, this is: "asd" . foo(12) . "asd". This is useful for the block_js.
		if (value && value_type == "string" 
		    && (value.charAt(0) != '"' || value.charAt(value.length - 1) != '"') 
		    && (value.charAt(0) != "'" || value.charAt(value.length - 1) != "'")
		) { 
			value = '"' + addcslashes(value, '"') + '"';
			//Do not add the addcslashes($arg, '\\"') otherwise this will add an extra \\ to all the other \\. By default the $arg already contains the right number of slashes. The only missing slash is the ", because yo are editing php code directly.
		}
		//console.log(name + "=" + value);
		
		//query_string += (i > 0 ? "&" : "") + escape(name) + "=" + escape(value);
		query_string += (i > 0 ? "&" : "") + encodeURIComponent(name) + "=" + encodeURIComponent(value);
		
	}
	//console.log(query_string);
	
	parse_str(query_string, obj);
	
	//console.log(obj);
	return obj;
}
