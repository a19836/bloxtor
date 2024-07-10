window.onload = function() { //for some reason the load is getting called multiple times, so we need to use the is_inited var to avoid calling multiple times the onLoadEditSimpleTemplateLayout function. I think it's because the ajax requests of the simulate html for each region block.
	onLoadEditSimpleTemplateLayout();
};

window.onerror = function(msg, url, line, col, error) {
	//alert("Javascript Error was found!");
	
	return true; //return true, avoids the error to be shown and other scripts to stop.
};

//prepare full screen when press F11 key
if (document.addEventListener) 
	document.addEventListener("keydown", function(event) {
		var code = event.keyCode || event.which;
		
		if (code == 122 && typeof window.parent.onF11KeyPress == "function")
			window.parent.onF11KeyPress(event);
	});

//Note that this function is called in the edit_page_and_template.js file too
function onLoadEditSimpleTemplateLayout() {
	//console.log("onLoadEditSimpleTemplateLayout");
	
	/*if (!window.jQuery) {
		// jQuery not loaded, load it and when it loads call
		// noConflict and the callback (if any).
		var script = document.createElement('script');
		script.onload = function() {
			jQuery.noConflict();
		};
		script.src = "http://code.jquery.com/jquery-1.8.1.min.js";
		var head = document.getElementsByTagName('head')[0];
		var body = document.getElementsByTagName('body')[0];
		
		if (head)
			head.appendChild(script);
		else
			body.appendChild(script);
	}*/
	
	if (typeof parent.pretifyRegionsBlocksComboBox == "function") {
		parent.disableLinksAndButtonClickEvent(document.body);
		
		//prepare template_region
		var template_regions = document.querySelectorAll(".template_region");
		
		if (template_regions)
			for (var i = 0; i < template_regions.length; i++) {
				var tr = template_regions[i];
				//console.log(tr.getAttribute("region")+":"+tr.getAttribute("class"));
				
				//prepare light class - if background of template_region or its parents is dark, change colors to white colors...
  				if (isRegionBlockInDarkBackground(tr))
		  			tr.classList.add("item-light");
		  		
		  		var items_elm = filterSelectorAllInNodes(tr.childNodes, ".template_region_items");
		  		prepareRegionsBlocks(items_elm);
		  	}
	}
	else 
		alert("This page should be called as an iframe from another page. Should not be called directly as a main window bc it needs some javascript of the parent window.");
}

function prepareRegionsBlocks(elms) {
	parent.prepareRegionsBlocksHtmlValue(elms);
	convertRegionBlocksToSimpleHtml(elms);
	
	var items = getRegionsBlocksItemElements(elms);
	
	for (var i = 0; i < items.length; i++) {
		var item = items[i];
		
		prepareRegionBlockConflictsWithLayoutUIEditor(item);
		//createPretifyRegionBlockComboBox(item); //the prepareAvailableBlocksList already calls the parent.pretifyRegionBlockComboBox function
		prepareAvailableBlocksList(item);
		prepareRegionBlockHtmlEditor(item);
  		prepareRegionBlockOverHandlers(item);
  		prepareRegionBlockMoveHandler(item);
		prepareRegionBlockSimulatedHtml(item);
	}
}

function getRegionsBlocksItemElements(elms) {
	var items = [];
	
	if (elms)
		for (var i = 0; i < elms.length; i++) {
			var elm = elms[i];
			var elm_items = elm.classList.contains("template_region_item") ? [elm] : elm.querySelectorAll(".template_region_item");
			
			if (elm_items)
				for (var j = 0; j < elm_items.length; j++)
					items.push(elm_items[j]);
		}
	else
		items = document.querySelectorAll(".template_region_item");
	
	return items;
}

function createPretifyRegionBlockComboBox(item) {
	var block_options = filterSelectorInNodes(item.childNodes, "select.block_options");
	
	if (block_options) {
		parent.pretifyRegionBlockComboBox(block_options, {
			on_pretify_handler: function(select_elm) {
				preparePretifyRegionBlockComboBox(item);
			}
		});
		
		/* The iframe is slow but is not because of the pretty combobox
		//destroy pretty combobox, bc I think the iframe could be a little bit slow bc of it
		parent.destroyPretifyRegionBlockComboBox(block_options);
		
		//show block_options if region_block_type == options
		var region_block_type = filterSelectorInNodes(item.childNodes, "select.region_block_type");
		
		if (region_block_type.value == "options") {
			block_options.classList.remove("hidden");
			block_options.style.display = "block";
		}
		
		//remove invisibles, that are usually removed in parent.pretifyRegionBlockComboBox
		var invisibles = filterSelectorAllInNodes(item.childNodes, ".invisible");
		for (var i = 1; i < invisibles.length; i++)
			invisibles[i].classList.remove("invisible");*/
	}
}

function prepareAvailableBlocksList(item) {
	//console.log("prepareAvailableBlocksList");
	
	parent.loadAvailableBlocksList(item, {
		on_pretify_handler : function(select_elm) {
			preparePretifyRegionBlockComboBox(item);
		},
	});
}

function preparePretifyRegionBlockComboBox(item) {
	var block_options = filterSelectorInNodes(item.childNodes, "select.block_options");
	
	if (block_options) {
		var pretty_select_menu_ul = parent.$(block_options).selectmenu("menuWidget");
		var pretty_select_menu = pretty_select_menu_ul.parent();
		
		pretty_select_menu.addClass("layout-ui-editor-reserved");
	}
}

function prepareRegionBlockHtmlEditor(item) {
	var select = item.querySelector(".type");
	
	if (select) {
		var type = select.value;
		
		if (type == 1) { //is html
			var block_html = filterSelectorInNodes(item.childNodes, ".block_html");
			
			if (block_html && !parent.hasRegionBlockHtmlEditor(block_html))
				createRegionBlockHtmlEditor(block_html);
		}
	}
}

function convertRegionBlocksToSimpleHtml(elms) {
	//loop for all the .template_region_items
	for (var i = 0; i < elms.length; i++) {
		var template_region_items = elms[i];
		var items = getRegionsBlocksItemElements( [template_region_items] );
		var html = "";
		var first_node = null;
		
		//loop for all .template_region_item inside of .template_region_items
		for (var j = 0; j < items.length; j++) {
			var item = items[j];
			var values = parent.getSettingsTemplateRegionBlockValues(item);
			var type = values["type"];
			var is_html = type == 1;
			
			//prepare main html and remove this item
			if (values && is_html) {
				var sub_html = values["block"];
				
				//add sub_html
				if (sub_html)
					html += sub_html;
					
				//remove item, bc the sub_html was already inserted before the item
				item.parentNode.removeChild(item);
			}
			else { //for all the other items, create a dummy div to be replaced below
				var get_html_id = "get_html_id_" + Math.random() + "_" + Math.random() + "_" + Math.random(); //create unique id for each template_region_item
				get_html_id = get_html_id.replace(/\./g, "_"); //replace "." from Math.random
				
				html += '<div id="' + get_html_id + '"></div>';
				
				item.get_html_id = get_html_id;
				
				if (!first_node)
					first_node = item;
			}
		}
		
		//add main html
		if (first_node)
			first_node.insertAdjacentHTML("beforebegin", html);
		else
			template_region_items.innerHTML = html;
		
		//replace .template_region_item in the right html elements
		for (var j = 0; j < items.length; j++) {
			var item = items[j];
			
			if (item && item.get_html_id) {
				var dummy_div = template_region_items.querySelector("#" + item.get_html_id);
				
				//add .template_region_item before dummy_div and delete dummy_div
				if (dummy_div) {
					var p = dummy_div.parentNode;
					
					p.insertBefore(item, dummy_div);
					p.removeChild(dummy_div);
				}
				
				//remove get_html_id
				item.get_html_id = null;
				delete item.get_html_id;
			}
		}
		
		//console.log(template_region_items.innerHTML);
	}
}

function prepareRegionBlockConflictsWithLayoutUIEditor(item, force) {
	if (!item.are_layout_ui_editor_conflicts_fixed || force) { //This method is called from the src/view/presentation/template_editor_widget/advanced/others/region_block.xml too, and so we don't execute this twice for the same item, we set a flag named: are_layout_ui_editor_conflicts_fixed
		item.are_layout_ui_editor_conflicts_fixed = true;
		//console.log(item);
		
		//prepare items to don't be in conflict with the layout-ui-editor
		item.setAttribute("contenteditable", "false");
		item.setAttribute("spellcheck", "false");
		item.classList.add("not-widget");
		
		//avoids when click in the item, to open the .widget-header of the parent.template-widget. Note that the widget-header will always over the item header buttons. So we need to avoid this case, by stopping the propagation of the item.
		item.addEventListener("click", function(e) {
			//prevents event to continue with the default.
			e.stopPropagation();
			//Do not use return; or preventDefault
		});
		
		//avoids the enter key in the textarea, where it will paste something in the parent element (.template_region_items)
		var block_html = filterSelectorInNodes(item.childNodes, ".block_html");
		var block_text = filterSelectorInNodes(item.childNodes, ".block_text");
		var block_text_area = filterSelectorInNodes(block_text.childNodes, "textarea");
		
		block_text_area.addEventListener("keydown", function(e) {
			//prevents event to continue with the default.
			e.stopPropagation();
			//Do not use return; or preventDefault
		});
		
		block_html.addEventListener("keydown", function(e) {
			//prevents event to continue with the default.
			e.stopPropagation();
			//Do not use return; or preventDefault
		});
	}
}
 
function prepareRegionBlockOverHandlers(item) {
	if (item) {
		//prepare handlers
		var item_mouseover_handler = function() {
  			var timeout_id = this.hasOwnProperty("out_timeout_id") ? this["out_timeout_id"] : null;
  			if (timeout_id)
  				clearTimeout(timeout_id);
  			
  			this.classList.add("item-hover");
  		};
  		
  		var item_mouseout_handler = function() {
  			var timeout_id = this.hasOwnProperty("out_timeout_id") ? this["out_timeout_id"] : null;
  			if (timeout_id)
  				clearTimeout(timeout_id);
  			
  			var elm = this;
  			var timeout_id = setTimeout(function() {
  				elm.classList.remove("item-hover");
  			}, 500);
  			
  			this["out_timeout_id"] = timeout_id;
  		};
  		
		if (item.addEventListener) {
			item.addEventListener("mouseover", item_mouseover_handler);
			item.addEventListener("mouseout", item_mouseout_handler);
		}
  		else if (item.attachEvent) {
		   	item.attachEvent('mouseover', item_mouseover_handler);
		   	item.attachEvent('mouseout', item_mouseout_handler);
  		}
  	}
}

function prepareRegionBlockMoveHandler(item) {
	var template_region_items = item.parentNode.closest(".template_region_items");
	var html = '<span class="icon move" title="Move this region-block">Move</span>';
	var up = filterSelectorInNodes(item.childNodes, ".up");
	up.insertAdjacentHTML("beforebegin", html);
	
	var move_icon = filterSelectorInNodes(item.childNodes, ".move");
	var j_move_icon = parent.$(move_icon);
	var selected_droppable = null;
	var next_node = null;
	var item_parent = null;
	
	j_move_icon.draggable({
		//others settings
	     appendTo: template_region_items,
		containment: template_region_items,
		cursor: "move",
          tolerance: "pointer",
		//refreshPositions: true, //Do not add this bc of performance
		
		/* Cannot set the helper otherwise it will not find the droppable element
		//helper: "clone",
		//revertDuration: 300,
		//revert: true,
		
		//or
		helper: function(event) {
			var helper = item.ownerDocument.createElement('DIV');
			helper.className = "dragging_menu_item";
			helper.innerHTML = "Region-Block Item";
			//helper.style.width = item.outterWidth + "px";
			
			//template_region_items.appendChild(helper);
			//item.parentNode.insertBefore(helper, item);
			console.log("START");
			console.log(helper);
			console.log(helper.parentNode);
			
			return helper;
		},*/
		start: function(event, ui_obj) {
			next_node = item.nextSibling;
			item_parent = item.parentNode;
			template_region_items = item_parent.closest(".template_region_items");
		},
		drag: function(event, ui_obj) {
			//prepare old droppable
			if (selected_droppable)
				selected_droppable.classList.remove("highlight", "droppable_highlight");
			
			selected_droppable = null;
			
			//prepare new droppable
			var droppable = event.toElement ? event.toElement : event.srcElement;
			//console.log(droppable);
			
			if (droppable) {
				droppable = droppable.closest(".droppable");
				
				if (droppable && droppable.closest(".template_region_items") == template_region_items) {
					droppable.classList.add("highlight", "droppable_highlight");
					selected_droppable = droppable;
				}
			}
		},
		stop: function(event, ui_obj) {
			/*console.log("STOP");
			console.log(ui_obj.helper);
			console.log(ui_obj.helper.parentNode);
			console.log(event);*/
			//ui_obj.helper.remove();
			
			//append item to the selected droppable
			if (selected_droppable) {
				selected_droppable.classList.remove("highlight", "droppable_highlight");
				selected_droppable.appendChild(item);
				
				//sync layout with settings
				parent.updateSettingsFromLayoutIframeField();
			}
			else { //put item back where it was
				if (next_node)
					item_parent.insertBefore(item, next_node);
				else
					item_parent.appendChild(item);
				
				showError("Could not drop item here. Please try again...");
			}
		},
	});
}

function getRegionBlockSimulatedHtmlElm(item) {
	var elms = filterSelectorAllInNodes(item.childNodes, ".block_simulated_html");
	
	if (elms.length > 1)
		for (var i = 1; i < elms.length; i++)
			item.removeChild(elms[i]);
	
	return elms[0];
}

function prepareRegionBlockSimulatedHtml(item) {
	if (item) {
		//get block_simulated_html div
		var block_simulated_html = getRegionBlockSimulatedHtmlElm(item);
		
		if (block_simulated_html) {
			block_simulated_html.innerHTML = '';
			
			//disable edition in block_simulated_html
			block_simulated_html.setAttribute("contenteditable", "false");
			block_simulated_html.setAttribute("spellcheck", "false");
		}
		
		//get block path
		var values = parent.getSettingsTemplateRegionBlockValues(item);
		var type = values["type"];
		var region = values["region"];
		var block = values["block"];
		var block_type = values["block_type"];
		var rb_index = values["rb_index"];
		var is_block = type == 2 || type == 3;
		var is_view = type == 4 || type == 5;
		
		var b = ("" + block).charAt(0) == '"' ? ("" + block).substr(1, ("" + block).length - 2).replace(/\\"/g, '"') : block;
		
		if ((is_block || is_view) && b && (block_type == "options" || block_type == "string" || block_type == "text")) {
			//save synchronization functions
			var update_settings_from_layout_iframe_func_bkp = parent.update_settings_from_layout_iframe_func;
			var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
			
			//disable synchronization functions in case some call recursively by mistake
			parent.update_settings_from_layout_iframe_func = null;
			parent.update_layout_iframe_from_settings_func = null;
			
			//prepare project
			var project = values["project"];
			var p = ("" + project).charAt(0) == '"' ? ("" + project).substr(1, ("" + project).length - 2).replace(/\\"/g, '"') : project;
			p = p ? p : selected_project_id;
			
			//preparing urls
			var get_url = system_get_page_block_simulated_html_url.replace(/#project#/g, p).replace(/#block#/g, b);
			var save_url = is_block ? system_save_page_block_simulated_html_setting_url.replace(/#project#/g, p).replace(/#block#/g, b) : null;
  			var post_data = system_get_page_block_simulated_html_data;
			post_data["page_region_block_type"] = is_view ? "view" : "block";
			post_data["page_region_block_params"] = null;
			post_data["page_region_block_join_points"] = null;
			
  			//preparing params and join points
  			if (is_block && typeof parent.getRegionBlockParamValues == "function")
				post_data["page_region_block_params"] = parent.getRegionBlockParamValues(region, block, rb_index);
			
			if (is_block && typeof parent.getRegionBlockJoinPoints == "function")
				post_data["page_region_block_join_points"] = parent.getRegionBlockJoinPoints(region, block, rb_index);
			
			//sets back synchronization functions
  			parent.update_settings_from_layout_iframe_func = update_settings_from_layout_iframe_func_bkp;
			parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
			
			//getting block html
			parent.$.ajax({
				url: get_url,
				type: 'post',
				processData: false,
				contentType: 'text/html',
				data: parent.JSON.stringify(post_data),
				dataType: 'html',
				success: function(response, textStatus, jqXHR) {
					if (response && typeof response == "string" && response.charAt(0) == "{" && response.charAt(response.length - 1) == "}") {
						var response_obj = parent.JSON.parse(response)
						
						if (typeof response_obj == "object" && response_obj && response_obj.hasOwnProperty("html") && response_obj["html"]) { //response_obj could be null. Null is an object!
							//prepare item
							if (item.classList.contains("invalid"))
								item.classList.remove("invalid")
							
							//prepare block_simulated_html div
				  			var simulated_html = response_obj["html"];
				  			var block_simulated_html = getRegionBlockSimulatedHtmlElm(item);
				  			
				  			if (!block_simulated_html) {
				  				var doc = item.ownerDocument || document;
				  				block_simulated_html = doc.createElement("DIV");
								block_simulated_html.className = "block_simulated_html";
								item.appendChild(block_simulated_html);
				  			}
				  			
				  			block_simulated_html.innerHTML = simulated_html;
					  		
					  		if (save_url)
						  		convertBlockSimulatedHtmlIntoEditableContent(block_simulated_html, response_obj, save_url);
				  			
				  			if (typeof parent.disableLinksAndButtonClickEvent == "function")
					  			parent.disableLinksAndButtonClickEvent(block_simulated_html);
				  		}
		  			}
		  		}
			});
		}
	}
}

function convertBlockSimulatedHtmlIntoEditableContent(block_simulated_html, response_obj, save_url) {
	block_simulated_html.setAttribute("block_code_id", response_obj["block_code_id"]);
	block_simulated_html.setAttribute("block_code_time", response_obj["block_code_time"]);
	
	//disable edition in block_simulated_html
	block_simulated_html.setAttribute("contenteditable", "false");
	block_simulated_html.setAttribute("spellcheck", "false");
	
	//prepare editable settings
	if (typeof response_obj["editable_settings"] == "object" && response_obj["editable_settings"]) { //it could be null. Null is an object!
		var editable_settings = response_obj["editable_settings"];
		var editable_elements = editable_settings["elements"];
		
		includeCodeForBlockSimulatedHtmlSetting(block_simulated_html, editable_settings);
		
		if (typeof editable_elements == "object")
			for (var setting_selector in editable_elements) {
				var setting_path = editable_elements[setting_selector];
				var elms = !setting_selector ? [block_simulated_html] : block_simulated_html.querySelectorAll(setting_selector);
				
				for (var i = 0; i < elms.length; i++) 
					convertBlockSimulatedHtmlSettingIntoEditableContent(elms[i], editable_settings, setting_selector, setting_path, save_url);
			}
	}
}

function convertBlockSimulatedHtmlSettingIntoEditableContent(elm, editable_settings, setting_selector, setting_path, save_url) {
	elm.setAttribute("contenteditable", "true");
	elm.setAttribute("spellcheck", "false");
	
	var value = getBlockSimulatedHtmlSettingValue(elm);
	var value_hash = ("" + value).hashCode();
	elm.setAttribute("saved_value_hash", value_hash);
	
	var func = function(e) {
		onBlurBlockSimulatedHtmlSetting(e, elm, editable_settings, setting_selector, setting_path, save_url);
	};
	
	if (elm.addEventListener) 
		elm.addEventListener("blur", func);
	else if (elm.attachEvent) 
	   	elm.attachEvent('blur', func);
}

function onBlurBlockSimulatedHtmlSetting(e, elm, editable_settings, setting_selector, setting_path, save_url) {
	e.preventDefault ? e.preventDefault() : e.returnValue = false;
	e.stopPropagation();
	
	var value = getBlockSimulatedHtmlSettingValue(elm);
	var value_hash = ("" + value).hashCode();
	
	if (elm.getAttribute("saved_value_hash") != value_hash) {
		showMessage("Saving this block property... Wait a while...", "", "bottom_messages", 1500);
		
		var block_simulated_html = elm.closest(".block_simulated_html");
		var block_code_id = block_simulated_html.getAttribute("block_code_id");
		var block_code_time = block_simulated_html.getAttribute("block_code_time");
		//console.log(block_code_id+", "+block_code_time+", "+setting_selector+", "+setting_path+", "+save_url+", "+value);
		
		var post_data = {
			block_code_id: block_code_id,
			block_code_time: block_code_time,
			setting_selector: setting_selector,
			setting_path: setting_path,
			setting_value: value,
		};
		
		var handlers = editable_settings["handlers"];
		handlers = typeof handlers == "object" ? handlers : {};
		eval('var on_prepare_post_data = handlers["on_prepare_post_data"] && typeof ' + handlers["on_prepare_post_data"] + ' == "function" ? ' + handlers["on_prepare_post_data"] + ' : null;');
		
		if (on_prepare_post_data)
			post_data = on_prepare_post_data(elm, post_data);
		
		if (post_data) {
			parent.$.ajax({
				url: save_url,
				type: 'post',
				processData: false,
				contentType: 'text/html',
				data: parent.JSON.stringify(post_data),
				dataType: 'html',
				success: function(response, textStatus, jqXHR) {
					if (parent.StatusMessageHandler)
						parent.StatusMessageHandler.removeLastShownMessage("info");
					
					if (response && typeof response == "string" && response.charAt(0) == "{" && response.charAt(response.length - 1) == "}") {
						var response_obj = parent.JSON.parse(response);
						
						if (response_obj) {
							if (response_obj.old_block_code_id == block_code_id && response_obj.old_block_code_time == block_code_time) {
								if (response_obj.status) {
									block_simulated_html.setAttribute("block_code_id", response_obj.new_block_code_id);
									block_simulated_html.setAttribute("block_code_time", response_obj.new_block_code_time);
									
									elm.setAttribute("saved_value_hash", value_hash)
									
									showMessage("Block setting saved successfully!", "", "bottom_messages", 1500);
								}
								else
									showError("Error trying to save this block property. Please try again!");
							}
							else
								showError("Error: Could NOT save this block property because meanwhile someone already did some other change and this block UI is deprecated. To proceed, please refresh this block and execute your changes again!");
						}
						else
							showError("Error trying to save this block property. Please try again!");
					}
					else
						showError("Error trying to save this block property. Please try again!");
				}
			});
		}
	}
}

function includeCodeForBlockSimulatedHtmlSetting(block_simulated_html, editable_settings) {
	for (var k in editable_settings) {
		var v = editable_settings[k];
		
		if ((k == "css" || k == "js") && v) {
			var doc = block_simulated_html.ownerDocument || document;
			var created_elm = doc.createElement(k == "css" ? "STYLE" : "SCRIPT");
			created_elm.classList.add("not-widget");
			created_elm.textContent = v;
    			block_simulated_html.appendChild(created_elm);
		}
		else if (k == "files" && v) {
			for (var vk in v) {
				var files = v[vk];
				
				if ((vk == "css" || vk == "js") && files) {
					//files could be an array or an object, so we convert it to an object, just in case
					if ((typeof Array.isArray == "function" && Array.isArray(files)) || Object.prototype.toString.call(files) === '[object Array]') {
						var obj = {};
						
						for (var i = 0; i < data.length; i++)
							obj[i] = data[i];
						
						data = obj;
					}
					
					for (var idx in files) {
						var file = files[idx];
						
						if (file) {
							var doc = block_simulated_html.ownerDocument || document;
							var created_elm = null;
							
							if (vk == "css") {
								created_elm = doc.createElement("LINK");
								created_elm.classList.add("not-widget");
								created_elm.setAttribute("rel", "stylesheet");
								created_elm.setAttribute("type", "text/css");
								created_elm.setAttribute("href", file);
					    		}
					    		else {
					    			created_elm = doc.createElement("SCRIPT");
					    			created_elm.classList.add("not-widget");
								created_elm.setAttribute("language", "javascript");
								created_elm.setAttribute("type", "text/javascript");
								created_elm.setAttribute("src", file);
					    		}
					    		
					    		block_simulated_html.appendChild(created_elm);
						}
					}
				}
			}
		}
	}
}

function getBlockSimulatedHtmlSettingValue(elm) {
	var inputs = ["input", "textarea", "select"];
	var tag_name = elm.tagName.toLowerCase();
	var value = elm.innerHTML;
	
	if (inputs.indexOf(tag_name) != -1) {
		var type = tag_name == "input" ? ("" + elm.type).toLowerCase() : "";
		
		if (type == "checkbox" || type == "radio") {
			if (elm.checked)
				value = elm.value;
			else
				value = "";
		}
		else
			value = elm.value;
	}
	
	return value;
}

function showMessage(msg, message_class, message_html_obj_class, timeout) {
	if (parent.StatusMessageHandler)
		parent.StatusMessageHandler.showMessage(msg, message_class, message_html_obj_class, timeout);
}

function showError(msg) {
	if (parent.StatusMessageHandler)
		parent.StatusMessageHandler.showError(msg);
	else
		alert(msg);
}

function createRegionBlockHtmlEditor(block_html, opts) {
	var is_wyswyg_editor = block_html.classList.contains("editor");
	
	if (is_wyswyg_editor) {
		var parent_body = block_html.closest("body"); //this is very important, bc if the textarea does not exists yet, this is, if was not added to the DOM yet, does not create the editor. Which means the textarea creation must be done in manually in all the addRepeatedRegionBlock methods.
		
		if (parent_body && !parent.hasRegionBlockHtmlEditor(block_html))
			parent.createRegionBlockHtmlWyswygEditor(block_html, opts);
	}
}

function onChangeRegionBlock(elm) {
	var p = elm.parentNode
	var item = p.closest(".template_region_item");
	var opt = elm.querySelector("option:checked");
	var block = opt ? opt.getAttribute("value") : "";
	
	if (block != "") {
		item.classList.add("active", "has_edit");
		
		if (opt.hasAttribute("invalid"))
			item.classList.add("invalid");
		else
			item.classList.remove("invalid");
	}
	else
		item.classList.remove("active", "invalid", "has_edit");
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
	
	//simulate block html
	prepareRegionBlockSimulatedHtml(item);
}

function onChangeRegionBlockEditor(elm, str) {
	onBlurRegionBlock(elm);
}

function onBlurRegionBlock(elm) {
	var block = elm.value;
	var p = elm.parentNode
	var item = p.closest(".template_region_item");
	var block_type = item.querySelector("select.region_block_type").value;
	
	if (block != "") 
		item.classList.add("active");
	else
		item.classList.remove("active");
	
	if (block != "" && block_type == "string")
		item.classList.add("has_edit");
	else
		item.classList.remove("has_edit");
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
	
	//simulate block html
	prepareRegionBlockSimulatedHtml(item);
}

function onChangeTemplateRegionItemType(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call parent onChangeTemplateRegionItemType
	parent.onChangeTemplateRegionItemType(elm);
	
	var p = elm.parentNode.closest(".template_region_item");
	prepareRegionBlockHtmlEditor(p);
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
	
	//simulate block
	prepareRegionBlockSimulatedHtml(p);
}

function onChangeRegionBlockType(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call parent onChangeRegionBlockType
	parent.onChangeRegionBlockType(elm);
	
	var p = elm.parentNode.closest(".template_region_item");
	prepareRegionBlockHtmlEditor(p);
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
	
	//simulate block
	prepareRegionBlockSimulatedHtml(p);
}

function addFirstRegionBlock(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call parent addFirstRegionBlock
	parent.addFirstRegionBlock(elm);
	
	//prepare new_region element with new handlers
	var template_region = elm.parentNode.closest(".template_region");
	var items = filterSelectorInNodes(template_region.childNodes, ".template_region_items");
	var item = filterSelectorAllInNodes(items.childNodes, ".template_region_item"); //must be children bc the parent.addFirstRegionBlock method appends a new item to the template_region_item, and we want to get that item.
	var new_region = item.length > 0 ? item[ item.length - 1 ] : null;
	
	prepareNewRegion(new_region);
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
}

function addRepeatedRegionBlock(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call parent addRepeatedRegionBlock
	parent.addRepeatedRegionBlock(elm);
	
	//prepare new_region element with new handlers
	var item = elm.parentNode.closest(".template_region_item");
	
	if (item) {
		var new_region = null;
		
		do {
			new_region = item.nextElementSibling;
		}
		while (new_region && !new_region.classList.contains("template_region_item"));
		
		prepareNewRegion(new_region);
	}
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
}

function prepareNewRegion(new_region) {
	//This is very important otherwise these fields will have the handlers from the parent window. The following code makes the handlers to the functions of this file
	if (new_region) {
		var children = new_region.childNodes;
		var block_text = filterSelectorInNodes(children, ".block_text");
		
		filterSelectorInNodes(children, ".type").setAttribute("onChange", "onChangeTemplateRegionItemType(this)");
		filterSelectorInNodes(children, ".block_options").setAttribute("onChange", "onChangeRegionBlock(this)");
		filterSelectorInNodes(block_text.childNodes, "textarea").setAttribute("onBlur", "onBlurRegionBlock(this)");
		filterSelectorInNodes(children, ".block").setAttribute("onBlur", "onBlurRegionBlock(this)");
		filterSelectorInNodes(children, ".region_block_type").setAttribute("onChange", "onChangeRegionBlockType(this)");
		filterSelectorInNodes(children, ".add").setAttribute("onClick", "addRepeatedRegionBlock(this)");
		filterSelectorInNodes(children, ".up").setAttribute("onClick", "moveUpRegionBlock(this)");
		filterSelectorInNodes(children, ".down").setAttribute("onClick", "moveDownRegionBlock(this)");
		filterSelectorInNodes(children, ".delete").setAttribute("onClick", "deleteRegionBlock(this)");
		
		var block_html = filterSelectorInNodes(new_region.childNodes, ".block_html");
		
		prepareRegionBlockConflictsWithLayoutUIEditor(new_region);
		prepareRegionBlockOverHandlers(new_region);
		prepareRegionBlockMoveHandler(new_region);
		preparePretifyRegionBlockComboBox(new_region);
	}
}

function openTemplateRegionInfoPopup(elm) {
	parent.openTemplateRegionInfoPopup(elm);
}

function editRegionBlock(elm) {
	var item = elm.parentNode;
	var block_html = filterSelectorInNodes(item.childNodes, ".block_html");
	
	parent.editRegionBlock(elm, {
		on_popup_close_func: function(html) {
			//save synchronization function
			var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
			
			//disable synchronization function
			parent.update_layout_iframe_from_settings_func = null;
			
			//call setRegionBlockHtmlEditorValue
			parent.setRegionBlockHtmlEditorValue(block_html, html);
			
			//sets back synchronization function
			parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
			
			//sync layout with settings
			parent.updateSettingsFromLayoutIframeField();
		}
	});
}

function moveUpRegionBlock(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call moveUpRegionBlock
	//parent.moveUpRegionBlock(elm); //do not call parent.moveUpRegionBlock bc this ignores the Text nodes.
	var item = $(elm).parent();
	var p = item.parent();
	var contents = p.contents().filter(function() {
		return this.nodeType === Node.TEXT_NODE || this.nodeType === Node.ELEMENT_NODE;
	});
	var index = contents.index(item);
	
	//Do not add the this item.prev(), otherwise the system will ignore text nodes.
	//Do not add the this item.prev(".template-widget"), otherwise if there is an element which is not a template widget like a text node or a bold element, then the sort won't happen for that element.
	
	if (index - 1 >= 0)
		item.insertBefore(contents[index - 1]);
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
}

function moveDownRegionBlock(elm) {
	//save synchronization function
	var update_layout_iframe_from_settings_func_bkp = parent.update_layout_iframe_from_settings_func;
	
	//disable synchronization function
	parent.update_layout_iframe_from_settings_func = null;
	
	//call moveUpRegionBlock
	//parent.moveDownRegionBlock(elm); //do not call parent.moveDownRegionBlock bc this ignores the Text nodes.
	var item = $(elm).parent();
	var p = item.parent();
	var contents = p.contents().filter(function() {
		return this.nodeType === Node.TEXT_NODE || this.nodeType === Node.ELEMENT_NODE;
	});
	var index = contents.index(item);
	
	//Do not add the this item.next(), otherwise the system will ignore text nodes.
	//Do not add the this item.next(".template-widget"), otherwise if there is an element which is not a template widget like a text node or a bold element, then the sort won't happen for that element.
	
	if (index + 1 < contents.length)
		item.insertAfter(contents[index + 1]);
	
	//sets back synchronization function
	parent.update_layout_iframe_from_settings_func = update_layout_iframe_from_settings_func_bkp;
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
}

function deleteRegionBlock(elm) {
	//bc the parent.deleteRegionBlock only deletes if not the last region, we need to hard code the deletion
	var item = $(elm).parent();
	item.remove();
	
	//sync layout with settings
	parent.updateSettingsFromLayoutIframeField();
}

//used in the edit_page_and_template.js
function getAvailableTemplateRegions() {
	var available_regions = [];
	var template_regions = document.querySelectorAll(".template_region");
	
	for (var i = 0; i < template_regions.length; i++) {
		var region = template_regions[i].getAttribute("region");
		
		available_regions.push(region);
	}
	
	return available_regions;
}

function getTemplateRegionsBlocks() {
	var regions = [];
	var regions_blocks = [];
	var PtlLayoutUIEditor = parent.$(".code_layout_ui_editor .layout-ui-editor").data("LayoutUIEditor");
	var items = document.querySelectorAll(".template_region > .template_region_items");
	//console.log(items);
	
	//loop all template_region > .template_region_items
	for (var i = 0; i < items.length; i++) {
		//loop all .template_region_items children
		var template_region_items = items[i];
		var template_region = template_region_items.parentNode.closest(".template_region");
		var region = template_region.getAttribute("region");
		var template_region_items_children = template_region_items.querySelectorAll(".template_region_item");
		var text_nodes_to_remove = [];
		
		//add dummy text nodes
		for (var j = 0; j < template_region_items_children.length; j++) {
			var template_region_item = template_region_items_children[j];
			var get_html_id = "get_html_id_" + Math.random() + "_" + Math.random() + "_" + Math.random(); //create unique id for each template_region_item
			var text_node = document.createTextNode("#" + get_html_id + "#");
			
			template_region_item.get_html_id = get_html_id;
			template_region_item.parentNode.insertBefore(text_node, template_region_item);
			template_region_item.classList.add("layout-ui-editor-reserved");
			
			text_nodes_to_remove.push(text_node);
		}
		
		//get html
		var children = Array.from(template_region_items.childNodes);
		var html = getTemplateRegionContentsHtml(PtlLayoutUIEditor, children);
		//console.log(html);
		
		//remove dummy text nodes
		for (var j = 0; j < text_nodes_to_remove.length; j++) {
			var text_node = text_nodes_to_remove[j];
			text_node.parentNode.removeChild(text_node);
		}
		
		//prepare html and convert it to regions_blocks
		var start = 0;
		
		for (var j = 0; j < template_region_items_children.length; j++) {
			var template_region_item = template_region_items_children[j];
			var get_html_id = template_region_item.get_html_id;
			
			//remove dummy get_html_id and class
			template_region_item.classList.remove("layout-ui-editor-reserved");
			template_region_item.get_html_id = null;
			delete template_region_item.get_html_id;
			
			//slpit and add html
			var to_search = "#" + get_html_id + "#";
			var pos = html.indexOf(to_search, start);
			
			if (pos != -1) {
				var sub_html = html.substr(start, pos - start);
				sub_html = sub_html.replace(/^\s+/g, "").replace(/\s+$/g, "");
				
				if (sub_html != "") {
					regions.push(region);
					
					if (block != "")
						regions_blocks.push([region, sub_html, null, 1, 0]); //1 == html
				}
				
				start = pos + to_search.length;
			}
			else
				alert("Error trying to convert region html into region blocks, in region: " + region); //I could use showError instead of alert, but for now I prefer to use the alert, bc this should NEVER happen and if it does it means this logic is not 100% correct and I should know it.
			
			//get and add .template_region_item properties
			var values = parent.getSettingsTemplateRegionBlockValues(template_region_item);
			
			if (values) {
				var region = values["region"];
				var type = values["type"];
				var block = values["block"];
				var project = values["project"];
				var rb_index = values["rb_index"];
				
				regions.push(region);
				
				if (block != "")
					regions_blocks.push([region, block, project, type, rb_index]);
			}
		}
		
		//get next html
		if (start < html.length) {
			var sub_html = html.substr(start);
			sub_html = sub_html.replace(/^\s+/g, "").replace(/\s+$/g, "");
			
			if (sub_html != "") {
				regions.push(region);
				
				if (block != "")
					regions_blocks.push([region, sub_html, null, 1, 0]); //1 == html
			}
		}
	}
	//console.log(regions_blocks);
	
	//prepare params
	var params = [];
	if (parent.template_params_values_list)
		for (var k in parent.template_params_values_list) 
			params.push(k);
	
	return {"regions": regions, "regions_blocks": regions_blocks, "params": params};
}

function getTemplateRegionContentsHtml(PtlLayoutUIEditor, children) {
	var html = "";
	
	if (children.length > 0) {
		if (PtlLayoutUIEditor) 
			html = PtlLayoutUIEditor.getElementContentsHtml(children);
		else
			for (var w = 0; w < children.length; w++) {
				var child = children[w];
				var child_html = child.nodeType == Node.ELEMENT_NODE ? child.outerHTML : child.textContent;
				var trimmed = child_html.replace(/^\s+/g, "").replace(/\s+$/g, "");
				
				if (trimmed != "")
					html += trimmed;
			}
	}
	
	return html;
}

function isRegionBlockInDarkBackground(elm) {
	var bg = getStyle(elm, "backgroundColor");
	
	//rgba(0, 0, 0, 0) is the default background-color return, which is transparent. More info in: https://stackoverflow.com/questions/52572823/getcomputedstylebackground-color-returns-transparent-does-not-inherit-ances and https://www.py4u.net/discuss/990140
	if (bg && (("" + bg).toLowerCase() == "transparent" || ("" + bg).replace(/\s+/g, "").toLowerCase() == "rgba(0,0,0,0)"))
		bg = "";
	
	var is_dark = bg ? isDarkColor(bg) : false;
	
	return elm.parentNode && elm.parentNode.nodeName.toLowerCase() != "html" && elm.parentNode != document && !is_dark ? isRegionBlockInDarkBackground(elm.parentNode) : is_dark;
}

function getStyle(elm, style_prop_name) {
	var value;
	var doc = elm.ownerDocument || document;
	var win = doc.defaultView || doc.parentWindow;
	
	// W3C standard way:
	if (win && win.getComputedStyle) {
		// sanitize property name to css notation
		// (hypen separated words eg. font-Size)
		style_prop_name = style_prop_name.replace(/([A-Z])/g, "-$1").toLowerCase();
		
		return win.getComputedStyle(elm, null).getPropertyValue(style_prop_name);
	} 
	else if (elm.currentStyle) { // IE
		// sanitize property name to camelCase
		style_prop_name = style_prop_name.replace(/\-(\w)/g, function(str, letter) {
			return letter.toUpperCase();
		});
		
		value = elm.currentStyle[style_prop_name];
		
		// convert other units to pixels on IE
		if (/^\d+(em|pt|%|ex)?$/i.test(value)) { 
			return (function(value) {
				var old_left = elm.style.left;
				var old_rs_left = elm.runtimeStyle.left;
				
				elm.runtimeStyle.left = elm.currentStyle.left;
				elm.style.left = value || 0;
				value = elm.style.pixelLeft + "px";
				
				elm.style.left = old_left;
				elm.runtimeStyle.left = old_rs_left;
				
				return value;
			})(value);
		}
		
		return value;
	}
	else if (elm.style)
		return elm.style[style_prop_name];
}

function isDarkColor(color) {
	// Variables for red, green, blue values
	var r, g, b, hsp;
	
	// Check the format of the color, HEX or RGB?
	if (color.match(/^rgb/)) {
		// If RGB --> store the red, green, blue values in separate variables
		color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);

		r = color[1];
		g = color[2];
		b = color[3];
	} 
	else {
		// If hex --> Convert it to RGB: http://gist.github.com/983661
		color = +("0x" + color.slice(1).replace( 
		color.length < 5 && /./g, '$&$&'));

		r = color >> 16;
		g = color >> 8 & 255;
		b = color & 255;
	}

	// HSP (Highly Sensitive Poo) equation from http://alienryderflex.com/hsp.html
	hsp = Math.sqrt(
		0.299 * (r * r) +
		0.587 * (g * g) +
		0.114 * (b * b)
	);

	// Using the HSP value, determine whether the color is light or dark
	return hsp > 127.5 ? false : true;
}

/*function getAjaxRequest(options) {
	if(!options) 
		options = {};
	
	var method = options.method ? ("" + options.method).toUpperCase() : "GET";
	var url = options.url ? options.url : null;
	var data = method == "POST" && options.data ? options.data : null;
	var result_type = options.result_type ? ("" + options.result_type).toLowerCase() : "text";
	var is_async = options.hasOwnProperty("async") ? options.async : true;
	var username = options.hasOwnProperty("username") ? options.username : null;
	var password = options.hasOwnProperty("password") ? options.password : null;
	var callback_func = options.callback_func ? options.callback_func : null;
	
	if (!url)
		return false;
	
	var XMLRequestObject = null; //XMLHttpRequest Object
	
	if (window.XMLHttpRequest) { //Mozilla, Safari, ...
		XMLRequestObject = new XMLHttpRequest();
		
		if (XMLRequestObject.overrideMimeType)
			XMLRequestObject.overrideMimeType("text/xml");
	
	} 
	else if (window.ActiveXObject) { //IE
		try {
			XMLRequestObject = new ActiveXObject("Msxml2.XMLHTTP");
		} 
		catch (e) {
			try {
				XMLRequestObject = new ActiveXObject("Microsoft.XMLHTTP");
			} 
			catch (e) {}
		}
	}
	
	if(!XMLRequestObject) {
		//alert("Giving up :( Cannot create an XMLHTTP instance");
		return false;
	}
	
	//fix Firefox error reporting when it tries to parse the response as XML, bc if the server doesn't return the correct response Content-type, by default the XMLRequestObject will try to parse the response as XML.
	if (typeof XMLRequestObject.overrideMimeType == "function")
		XMLRequestObject.overrideMimeType("text/plain");
	
	//open(method, url, is_async, user, psw)
	//	method: the request type GET or POST
	//	url: the file location
	//	async: true (asynchronous) or false (synchronous)
	//	user: optional user name
	//	psw: optional password
	XMLRequestObject.open(method, url, is_async, username, password);
	
	if(method == "POST") {
		XMLRequestObject.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		if(data) {
			data = convertDataToQueryString(data);
			
			//XMLRequestObject.setRequestHeader("Content-length", data.length); //browser error saying: Refused to set unsafe header "Content-length"
		}
		
		//XMLRequestObject.setRequestHeader("Connection", "close"); //browser error saying: Refused to set unsafe header "Connection"
	}
	
	XMLRequestObject.onreadystatechange = function(){
		if(XMLRequestObject.readyState == 4) {
			var res = result_type == "xml" ? XMLRequestObject.responseXML : XMLRequestObject.responseText;
			
			if (typeof callback_func == "function")
				callback_func(XMLRequestObject, res);
		}
	};
	
	XMLRequestObject.send(data);
	
	return true;
}

function convertDataToQueryString(data, prefix) {
	var query_string = "";
	
	if (typeof data == "object") {
		if ((typeof Array.isArray == "function" && Array.isArray(data)) || Object.prototype.toString.call(data) === '[object Array]') {
			var obj = {};
			
			for (var i = 0; i < data.length; i++)
				obj[i] = data[i];
			
			data = obj;
		}
		
		for(name in data) {
			var value = data[name];
			
			if (prefix)
				name = prefix + "[" + name + "]";
			
			query_string += (query_string ? "&" : "");
			
			if (typeof value == "object")
				query_string += convertDataObjectToString(value, name);
			else
				query_string += encodeURIComponent(name) + '=' + encodeURIComponent(value);
		}
	}
	else
		query_string = data;
	
	return query_string;
}*/
