/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original JQuery Layout UI Editor Repo: https://github.com/a19836/jquerylayoutuieditor/
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */
 
 //TODO: resize of tables and images

function TextSelection() {
	/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
	
	//Note: more info about the document.execCommand in: http://help.dottoro.com/larpvnhw.php
		
	var me = this;
	
	//top and left should be the position for the text selection in the layoutUIEditor. Don't forget that the TextSelection will be for elements inside of an iframe: template_widgets_iframe
	me.options = {
		top: 0,
		left: 0,
		width: null,
		height: null,
		sticky_menu: false,
		
		on_before_show_menu: null,
		on_after_show_menu: null,
		on_before_hide_menu: null,
		on_after_hide_menu: null,
		on_before_click_menu_item: null,
		on_after_click_menu_item: null,
		on_create_node: null,
		on_create_node_attributes: null,
		on_parse_node_attributes: null,
		on_paste_widget: null,
	};
	
	me.is_active = false;
	me.selection_elm = null;
	me.selection_opts = null;
	me.menu_elm = null;
	me.messages_elm = null;
	me.editor_elm = null;
	me.elm_to_append_menu = null;
	me.saved_range = null;
	
	me.available_menu_items_props = getAvailableMenuItemsProps();
	
	me.getAvailableMenuItemProps = function(id) {
		return me.available_menu_items_props[id];
	};
	
	me.menu_items_props = [
		me.getAvailableMenuItemProps("undo"),
		me.getAvailableMenuItemProps("redo"),
		me.getAvailableMenuItemProps("paste"),
		me.getAvailableMenuItemProps("format-block"),
		me.getAvailableMenuItemProps("font-family"),
		me.getAvailableMenuItemProps("font-size"),
		me.getAvailableMenuItemProps("bold"),
		me.getAvailableMenuItemProps("italic"),
		me.getAvailableMenuItemProps("underline"),
		me.getAvailableMenuItemProps("strikethrough"),
		me.getAvailableMenuItemProps("text-color"),
		me.getAvailableMenuItemProps("text-color-caret"),
		me.getAvailableMenuItemProps("back-color"),
		me.getAvailableMenuItemProps("back-color-caret"),
		me.getAvailableMenuItemProps("reset-color"),
		me.getAvailableMenuItemProps("remove-format"),
		'<i class="separator">|</i>',
		me.getAvailableMenuItemProps("subscript"),
		me.getAvailableMenuItemProps("superscript"),
		'<br/>',
		me.getAvailableMenuItemProps("align-left"),
		me.getAvailableMenuItemProps("align-center"),
		me.getAvailableMenuItemProps("align-right"),
		me.getAvailableMenuItemProps("align-justify"),
		me.getAvailableMenuItemProps("indent"),
		me.getAvailableMenuItemProps("outdent"),
		me.getAvailableMenuItemProps("insert-ul"),
		me.getAvailableMenuItemProps("insert-ol"),
		'<i class="separator">|</i>',
		me.getAvailableMenuItemProps("link"),
		me.getAvailableMenuItemProps("unlink"),
		'<i class="separator">|</i>',
		me.getAvailableMenuItemProps("image"),
		me.getAvailableMenuItemProps("insert-hr"),
		me.getAvailableMenuItemProps("insert-table"),
		me.getAvailableMenuItemProps("insert-p"),
		me.getAvailableMenuItemProps("insert-html"),
		'<i class="separator">|</i>',
		me.getAvailableMenuItemProps("html-element-props"),
		me.getAvailableMenuItemProps("delete"),
		me.getAvailableMenuItemProps("spellcheck"),
		me.getAvailableMenuItemProps("select-all"),
		me.getAvailableMenuItemProps("unselect"),
		me.getAvailableMenuItemProps("close-menu"),
		me.getAvailableMenuItemProps("toggle-menu"),
	];
	
	me.init = function(elm_to_append_menu, editor_elm) {
		me.elm_to_append_menu = elm_to_append_menu instanceof jQuery ? elm_to_append_menu : $(elm_to_append_menu);
		me.editor_elm = editor_elm instanceof jQuery ? editor_elm : $(editor_elm);
		var editor_doc = me.editor_elm[0].ownerDocument;
		var doc_to_append_menu = elm_to_append_menu[0].ownerDocument;
		
		var o = me.elm_to_append_menu.offset();
		me.options.top = o.top;
		me.options.left = o.left;
		
		me.menu_elm = me.elm_to_append_menu.children(".text-selection-menu");
		
		if (!me.menu_elm[0]) 
			me.createMenu(me.elm_to_append_menu);
		
		me.messages_elm = me.menu_elm.children(".messages");
		
		if (!me.messages_elm[0]) {
			me.messages_elm = $('<div class="messages"></div>');
			me.messages_elm.hide();
			me.menu_elm.append(me.messages_elm);
		}
		
		initMessages();
		
		me.menu_elm.hide(); //do not use me.hideMenu(). we only want to change the display style to none
		me.menu_elm.disableSelection(); //set disableSelection for options
		
		if (me.options.sticky_menu)
			me.menu_elm.addClass("sticky-text-selection-menu");
		else
			me.menu_elm.removeClass("sticky-text-selection-menu");
		
		//Save previous getRangeAt, because of the error explained in: https://stackoverflow.com/questions/22935320/uncaught-indexsizeerror-failed-to-execute-getrangeat-on-selection-0-is-not
		editor_doc.addEventListener("selectionchange", function() {
			var selection = me.getSelection();
			
			if (selection && selection.rangeCount > 0)
				me.saved_range = selection.getRangeAt(0);
		}, false);
		
		me.initEditorElm();
		
		//on scroll handler update menu position
		if (!me.options.sticky_menu) {
			$(doc_to_append_menu).scroll(function(e) {
				if (me.selection_opts && me.selection_opts.has_range)
					me.refreshMenu(e);
			});
		
			$(editor_doc).scroll(function(e) {
				if (me.selection_opts && me.selection_opts.has_range)
					me.refreshMenu(e);
			});
		}
		
		me.is_active = true;
	};
	
	me.initEditorElm = function() {	
		for (var i = 0; i < me.editor_elm.length; i++) {
			var item = me.editor_elm[i];
			
			//turn off spellcheck
			if ('spellcheck' in item) // Firefox
				item.spellcheck = false;
			
			if ('contentEditable' in item) // allow contentEditable
				item.contentEditable = true;
			else {  // Firefox before version 3
				if ('designMode' in editor_doc) // turn on designMode
					editor_doc.designMode = "on"; 
			}
		}
		
		var ctrl_key_down = false;
		
		me.editor_elm.mouseup(function(e) {
			e.preventDefault();
	  		e.stopPropagation();
	  		
			me.setSelectionElm(e.target);
			
			if (me.selection_opts) {
				//console.log(e);
				//console.log(me.options);
				//console.log(me.editor_elm.offset());
				
				var selection = me.getSelection();
				//console.log(selection);
				
				if (!selection)
					me.hideMenu(); //Note: DO NOT clear the document.range, otherwise the contenteditable element will stop working
				else {
					me.showMenu(e);
					me.loadMenu();
				}
			}
			else
				me.hideMenu(); //Note: DO NOT clear the docuemnt.range, otherwise the contenteditable element will stop working
			
	  		return true;
		})
		.keydown(function(e) { //change enter key in template_widgets_droppable to be the same than the switch key, otherwise it will clone the target element
			var enter_key_with_only_non_editable_children = false;
			
			//Fix issue when the main-droppable have some elements inside with contenteditable=false, and then we want to write something below that items. By default the main-droppable doesn't get focus because the focus goes to the non-editable objects inside of the main-droppable. So we must place the caret inside of the main-droppable and after the non-editable elements.
			//This issue only happends if there are any children, otherwise the problem will not happen.
			if (this.childNodes.length > 0 && me.selection_opts) {
				var contains_char = me.isPressedKeyPrintable(e); //checks if pressed key contains any char
				
				//if contains a char (this already includes the enter key)
				if (contains_char) {
					//checks if there are only non editable children
					var only_non_editable_children = true;
					
					for (var j = 0; j < this.childNodes.length; j++) {
						var child = this.childNodes[j];
						
						if ((child.nodeType == Node.TEXT_NODE && child.nodeValue != "") || child.contentEditable === true) {
							only_non_editable_children = false;
							break;
						}
					}
					
					//if only .item children or if enter/return key
					if (only_non_editable_children || e.keyCode === 13) { 
						var selection = me.selection_opts.selection;
						var node = me.selection_opts.first_node;
						
						//only if the caret is a main-droppable
						if (node == null || node == this) {
							//set cursor at the end of main-droppable
							var range = new Range();
							var offset = this.childNodes.length;
							range.setStart(this, offset);
							range.setEnd(this, offset);
							
							//add zero with space char
							var doc = this.ownerDocument; //please do not use "document" here, bc the selection_elm can be inside of an iframe
							var ze = doc.createTextNode("\u200B"); //\u200B: &Zero with Space invisble char. in alternative, we can replace this by &nbsp;
							range.insertNode(ze);
							range.setStartBefore(ze);
							range.setEndBefore(ze);
							
							// apply the selection, explained later below
							selection.removeAllRanges();
							selection.addRange(range);
							
							if (e.keyCode === 13) //enter/return key
								enter_key_with_only_non_editable_children = only_non_editable_children;
						}
					}
				}
			}
			
			if (e.keyCode === 13) { //enter/return key
				var is_shift = window.event ? window.event.shiftKey : e.shiftKey;
				
				if (!is_shift) {
					//prevents event to continue with the default.
					e.preventDefault();
					e.stopPropagation();
					
					//avoids add a another br, when this was already taken care from the above code.
					if (enter_key_with_only_non_editable_children) //this must be here, after the preventDefault and stopPropagation
						return false;
					
					var selection = me.selection_opts ? me.selection_opts.selection : null;
					
					if (selection) {
						//restore saved_range in case doesn't exists. This is because of the error explained in: https://stackoverflow.com/questions/22935320/uncaught-indexsizeerror-failed-to-execute-getrangeat-on-selection-0-is-not
						if (selection.rangeCount == 0 && me.saved_range != null)
							selection.addRange(me.saved_range);
						
						if (selection.rangeCount > 0) {
							var range = selection.getRangeAt(0);
							var node = me.selection_opts.first_node == null ? this : me.selection_opts.first_node;
							
							//if node is not editable, returns false. This wil be usefull in the edit_entity_simple when trying to insert an enter char when other "region blocks .item" elements already exists. 
							if (node.nodeType == Node.ELEMENT_NODE && (node.contentEditable === false || node.getAttribute("contentEditable") == "false"))
								return false;
							
							var pNode = node.parentNode;
							var doc = node.ownerDocument;
							
							range.deleteContents();
							
							var br = doc.createElement('br');
							range.insertNode(br);
							range.setStartAfter(br);
							range.setEndAfter(br);
						
							var char_exists = false;
							var aux = br.nextSibling;
							
							while (aux) {
								if (aux.nodeType == Node.ELEMENT_NODE || (aux.nodeType == Node.TEXT_NODE && aux.nodeValue != "")) {
									char_exists = true;
									break;
								}
								else
									aux = aux.nextSibling;
							};
							
							if (!char_exists) {
								var ze = doc.createTextNode("\u200B"); //\u200B: &Zero with Space invisble char. in alternative, we can replace this by &nbsp;
								range.insertNode(ze);
								range.setStartBefore(ze);
								range.setEndBefore(ze);
							}
							
							selection.removeAllRanges();
							selection.addRange(range);
						}
					}
					
					return false; //prevents event to continue with the default.
				}
			}
			else if (e.keyCode === 17 || e.keyCode == 91) { //Key codes: ctrlKey = 17, cmdKey = 91
				ctrl_key_down = true;
			}
			else if (ctrl_key_down && e.keyCode == 86) { //paste key. Key codes: vKey = 86
				setTimeout(function() { //must be with setTimeout bc the paste will only happen after this code gets executed.
					var opts = me.selection_opts;
					
					if (opts) {
						var node = opts.common_parent;
						
						if (typeof me.options.on_create_node == "function")
							me.options.on_create_node(e, null, $(node), {paste_action: true, common_parents: opts.common_parents});
					}
				}, 100);
				
				return true; //so the system can paste what was copied.
			}
		})
		.keyup(function(e) {
			if (e.keyCode === 17 || e.keyCode == 91) { //Key codes: ctrlKey = 17, cmdKey = 91
				ctrl_key_down = false;
			}
		});
	};
	
	me.isPressedKeyPrintable = function(e) {
		//checks if pressed key contains any char
		var contains_char = false;
		
		if (typeof e.which == "undefined") //This is IE, which only fires keypress events for printable keys
			contains_char = true;
		else if (typeof e.which == "number" && e.which > 0) {
			//In other browsers except old versions of WebKit, e.which is only greater than zero if the keypress is a printable key.
			//We need to filter out backspace and ctrl/alt/meta key combinations
			contains_char = !e.ctrlKey && !e.metaKey && !e.altKey && e.which != 8;
		}
		
		return contains_char;
	};
	
	me.isPressedKeyCharPrintable = function(e) {
		var contains_char = me.isPressedKeyPrintable(e);
		
		if (e.key.length !== 1) //means that only char keys are allowed: Arrows will not be allowed
			contains_char = false;
		
		return contains_char;
	};
	
	me.isActiveElement = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		
		return doc.activeElement === elm;
	};
	
	me.getLastCaretCharacterOffset = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		var selection = null;
		var l = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (typeof selection && typeof doc.createRange != "undefined") {
			var range = doc.createRange();
			range.selectNodeContents(elm);
			l = range.toString().length;
		}
		else if (typeof doc.body.createTextRange != "undefined") {
			var textRange = doc.body.createTextRange();
			textRange.moveToElementText(elm);
			l = textRange.toString().length;
		}
		
		return l;
	};
	
	me.getCaretCharacterOffsetWithin = function(elm) {
		var caret_offset = null; //must be null so we can detect if the cursor caret was place inside of element or not. However sometimes the caret is not placed but the caret_offset is 0. So this is not very realiable.
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		var sel;
		
		if (typeof win.getSelection != "undefined") {
			sel = win.getSelection();
			
			if (sel.rangeCount > 0) {
				var range = sel.getRangeAt(0);
				var pre_caret_range = range.cloneRange();
				pre_caret_range.selectNodeContents(elm);
				pre_caret_range.setEnd(range.endContainer, range.endOffset);
				caret_offset = pre_caret_range.toString().length;
			}
		} 
		else if ( (sel = doc.selection) && sel.type != "Control") {
			var textRange = sel.createRange();
			var pre_caret_text_range = doc.body.createTextRange();
			pre_caret_text_range.moveToElementText(elm);
			pre_caret_text_range.setEndPoint("EndToEnd", textRange);
			caret_offset = pre_caret_text_range.text.length;
		}
		
		return caret_offset;
	};
	
	me.isCaretAtStartOfElement = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		
		//Bail if there is text selected
		if (typeof win.getSelection != "undefined") {
			var sel = win.getSelection();
			
			if (sel.rangeCount > 0 && sel.getRangeAt(0).toString().length > 0)
				return false;
		}
		
		return me.getCaretCharacterOffsetWithin(elm) === 0;
	};
	
	me.isCaretAtEndOfElement = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		
		//Bail if there is text selected
		if (typeof win.getSelection != "undefined") {
			var sel = win.getSelection();
			
			if (sel.rangeCount > 0 && sel.getRangeAt(0).toString().length > 0)
				return false;
		}
		
		var offset = me.getCaretCharacterOffsetWithin(elm);
		return offset !== null && offset === me.getLastCaretCharacterOffset(elm);
	};
	
	me.isCaretOnFirstLine = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		var selection = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (selection.rangeCount === 0) 
			return false;
		
		//Get the client rect of the current selection
		var start_range = selection.getRangeAt(0);
		
		//Bail if there is text selected
		if (start_range.toString().length > 0) 
			return false;

		var start_rect = start_range.getBoundingClientRect();

		//Create a range at the end of the last text node
		var end_range = doc.createRange();
		end_range.selectNodeContents(elm);

		//The endContainer might not be an actual text node, try to find the last text node inside
		var start_container = end_range.endContainer;
		var start_offset = 0;
		
		while (start_container.hasChildNodes() && !(start_container instanceof Text))
			start_container = start_container.firstChild;

		end_range.setStart(start_container, start_offset);
		end_range.setEnd(start_container, start_offset);
		var end_rect = end_range.getBoundingClientRect();

		return start_rect.top === end_rect.top;
	};

	me.isCaretOnLastLine = function(elm) {
		var doc = elm.ownerDocument || elm.document;
		var win = doc.defaultView || doc.parentWindow;
		var selection = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (selection.rangeCount === 0) 
			return false;
		
		//Get the client rect of the current selection
		var start_range = selection.getRangeAt(0);
		
		//Bail if there is text selected
		if (start_range.toString().length > 0)
			return false;

		var start_rect = start_range.getBoundingClientRect();

		//Create a range at the end of the last text node
		var end_range = document.createRange();
		end_range.selectNodeContents(elm);

		//The endContainer might not be an actual text node, try to find the last text node inside
		var end_container = end_range.endContainer;
		var end_offset = 0;
		
		while (end_container.hasChildNodes() && !(end_container instanceof Text)) {
			end_container = end_container.lastChild;
			end_offset = end_container.length ?? 0;
		}

		end_range.setEnd(end_container, end_offset);
		end_range.setStart(end_container, end_offset);
		var end_rect = end_range.getBoundingClientRect();

		return start_rect.bottom === end_rect.bottom;
	};
	
	me.placeCaretAtStartOfElement = function(elm) {
		if (elm.nodeType == Node.ELEMENT_NODE)
			elm.focus();
		
		var doc = elm.ownerDocument; //please do not use "document" here, bc the selection_elm can be inside of an iframe
		var win = doc.defaultView || doc.parentWindow; //please do not use "window" here, bc the selection_elm can be inside of an iframe
		var selection = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (typeof selection && typeof doc.createRange != "undefined") {
			var range = doc.createRange();
			range.setStart(elm, 0);
			range.setEnd(elm, 0);
			range.collapse(false);
			var sel = selection;
			sel.removeAllRanges();
			sel.addRange(range);
		} 
		else if (typeof doc.body.createTextRange != "undefined") {
			var textRange = doc.body.createTextRange();
			textRange.moveStart(elm, 0);
			textRange.moveEnd(elm, 0);
			textRange.collapse(false);
			textRange.select();
		}
	};
	
	me.placeCaretAtEndOfElement = function(elm) {
		if (elm.nodeType == Node.ELEMENT_NODE)
			elm.focus();
		
		var doc = elm.ownerDocument; //please do not use "document" here, bc the selection_elm can be inside of an iframe
		var win = doc.defaultView || doc.parentWindow; //please do not use "window" here, bc the selection_elm can be inside of an iframe
		var selection = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (typeof selection && typeof doc.createRange != "undefined") {
			var range = doc.createRange();
			range.selectNodeContents(elm);
			range.collapse(false);
			var sel = selection;
			sel.removeAllRanges();
			sel.addRange(range);
		} 
		else if (typeof doc.body.createTextRange != "undefined") {
			var textRange = doc.body.createTextRange();
			textRange.moveToElementText(elm);
			textRange.collapse(false);
			textRange.select();
		}
	};
	
	me.removeCaretFromElement = function(elm) {
		if (elm.nodeType == Node.ELEMENT_NODE)
			elm.blur();
		
		var doc = elm.ownerDocument; //please do not use "document" here, bc the selection_elm can be inside of an iframe
		var win = doc.defaultView || doc.parentWindow; //please do not use "window" here, bc the selection_elm can be inside of an iframe
		var selection = null;
		
		if (doc.selection && doc.selection.createRange) 
			selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
		else if (win.getSelection)
			selection = win.getSelection();
		
		if (typeof selection && typeof selection.removeAllRanges != "undefined") {
			selection.removeAllRanges();
		} 
	};
	
	me.setEditorElm = function(editor_elm) {
		if (me.is_active) {
			me.editor_elm = editor_elm instanceof jQuery ? editor_elm : $(editor_elm);
			
			me.initEditorElm();
		}
	};
	
	/* selection functions */
	
	me.prepareSelection = function() {
		var selection = me.getSelection();
		
		if (selection) {
			//console.log(selection);
			
			//get nodes
			var first_node = selection.anchorNode;
			var last_node = selection.focusNode;
			var is_same_node = first_node == last_node;
			
			//get nodes offsets
			var first_offset = selection.anchorOffset;
			var last_offset = selection.focusOffset;
			
			//get range
			var has_range = true;
			
			if (selection.createRange) {
				var range = selection.createRange();
				
				if (range.htmlText == "")
					has_range = false;
			}
			else if (selection.rangeCount == 0)
				has_range = false;
			
			if (is_same_node && first_offset == last_offset)
				has_range = false;
			
			//get nodes parents
			var first_node_parents = $(first_node).parentsUntil("body").toArray();
			var last_node_parents = $(last_node).parentsUntil("body").toArray();
			
			//if the first parents are text nodes, remove them. Only ELEMENT_NODEs allowed
			if (first_node_parents.length)
				while (first_node_parents[0].nodeType == Node.TEXT_NODE)
					first_node_parents.shift();
			
			if (last_node_parents.length)
				while (last_node_parents[0].nodeType == Node.TEXT_NODE)
					last_node_parents.shift();
			
			//find common parent
			var common_parents = $(first_node_parents).filter(last_node_parents).toArray();
			var common_parent = common_parents[0];
			
			//if selection is selected from right to left
			var is_inversed_selection = false;
			
			if (is_same_node && first_offset > last_offset) //if same node, check if selection is inverse
				is_inversed_selection = true;
			else if (!is_same_node) { //if not the same node
				//get the parents that are not common for both nodes
				var first_ps = first_node_parents.slice(0, first_node_parents.length - common_parents.length);
				var last_ps = last_node_parents.slice(0, last_node_parents.length - common_parents.length);
				
				//from the common parent, get the first child for both parents of each nodes
				var first_common_parent_child = first_ps.length > 0 ? first_ps[first_ps.length - 1] : null;
				var last_common_parent_child = last_ps.length > 0 ? last_ps[last_ps.length - 1] : null;
				first_common_parent_child = first_common_parent_child ? first_common_parent_child : first_node;
				last_common_parent_child = last_common_parent_child ? last_common_parent_child : last_node;
				
				//get the index of the parent nodes and check if the selection is inverse
				var first_node_index = 0;
				var last_node_index = 0;
				var doc = me.selection_elm.ownerDocument;
				var children = common_parent ? common_parent.childNodes : doc.body.childNodes;
				
				if (children)
					for (var i = 0; i < children.length; i++) {
						var child = children[i];
						
						if (child == first_common_parent_child)
							first_node_index = i;
						else if (child == last_common_parent_child)
							last_node_index = i;
					}
				
				if (first_node_index > last_node_index) 
					is_inversed_selection = true;
			}
				
			//flip nodes bc the selection is inverse
			if (is_inversed_selection) {
				var aux = first_offset;
				first_offset = last_offset;
				last_offset = aux;
				
				aux = first_node;
				first_node = last_node;
				last_node = aux;
			}
			
			//prepare response
			var response = {
				selection: selection,
				first_node: first_node,
				last_node: last_node,
				first_offset: first_offset,
				last_offset: last_offset,
				first_node_parents: first_node_parents,
				last_node_parents: last_node_parents,
				common_parents: common_parents,
				common_parent: common_parent,
				has_range: has_range,
				is_same_node: is_same_node,
			};
			
			return response;
		}
	};
	
	me.getSelection = function() {
		var selection = null;
		
		if (me.selection_elm) {
			var doc = me.selection_elm.ownerDocument; //please do not use "document" here, bc the selection_elm can be inside of an iframe
			var win = doc.defaultView || doc.parentWindow; //please do not use "window" here, bc the selection_elm can be inside of an iframe
			
			if (doc.selection && doc.selection.createRange) 
				selection = typeof doc.selection == "function" ? doc.selection() : doc.selection;
			else if (win.getSelection)
				selection = win.getSelection();
			
			return selection;
		}
		
		return null;
	};
	
	me.setSelectionElm = function(selection_elm) {
		me.selection_elm = $(selection_elm)[0];//This is very important, bc if the selection_elm object comes from a "event.target", it doesn't contain the ownerDocument property and other important properties. So we must convert it to a jquery object and then get the raw object again. This avoids a lot of erros in this code when tryin gto access the me.selection_elm.ownerDocument
		
		me.selection_opts = me.prepareSelection();
	};
	
	/* Menu Items functions */
	
	me.createMenu = function(elm_to_append_menu) {
		me.menu_elm = $('<div class="text-selection-menu"></div>');
		
		for (var i = 0; i < me.menu_items_props.length; i++) {
			var menu_item_props = me.menu_items_props[i];
			
			if ($.isPlainObject(menu_item_props))
				me.createMenuItem(menu_item_props);
			else
				me.menu_elm.append(menu_item_props);
		}
		
		elm_to_append_menu.append(me.menu_elm);
	};
	
	me.createMenuItem = function(menu_item_props) {
		var allowed_attrs = ["title", "command", "command-value", "command-show-ui", "hide-menu", "dependencies", "button-behaviour", "style"];
		var menu_item_elm = $('<i class="text-selection-item"></i>');
		
		if (menu_item_props["class"])
			menu_item_elm.addClass(menu_item_props["class"]);
		
		for (var j = 0; j < allowed_attrs.length; j++) {
			var attr = allowed_attrs[j];
			if (menu_item_props[attr])
				menu_item_elm.attr(attr, menu_item_props[attr]);
		}
		
		if (menu_item_props["icon"])
			menu_item_elm.append('<i class="' + menu_item_props["icon"] + '"></i>');
		
		if (menu_item_props["html"])
			menu_item_elm.append(menu_item_props["html"]);
		
		if (typeof menu_item_props["init"] == "function")
			menu_item_props["init"](menu_item_elm[0]);
		
		if (typeof menu_item_props["click"] == "function") {
			menu_item_elm.click(function(e) {
				me.executeMenuItemClick(menu_item_props["click"], e, menu_item_elm[0], menu_item_props);
			});
		}
		else if (menu_item_elm.hasClass("text-selection-command-item"))
			menu_item_elm.click(function(e) {
				me.executeMenuItemClick( me.executeMenuItemSimpleCommand, e, menu_item_elm[0], menu_item_props);
			});
		
		me.menu_elm.append(menu_item_elm);
	};
	
	me.getMenuItemElmById = function(menu_item_class) {
		return me.menu_elm.find(".menu_item_class");
	};
	
	me.executeMenuItemClick = function(func, e, menu_item_elm, menu_item_props) {
		if (me.is_active) {
			me.menu_elm.children(".menu-item-popup").hide(); //it's very important otherwise the menu height will be bigger than the expected. Do not pass any argument in the hide(..) function otherwise it will become assynchronous and when we get the menu height, we will get the wrong height. Wihtout any argument the hide function is synchronous.
			
			if (typeof me.options.on_before_click_menu_item == "function")
				me.options.on_before_click_menu_item(e, menu_item_elm, menu_item_props);
			
			func(e, menu_item_elm);
			
			if (typeof me.options.on_after_click_menu_item == "function")
				me.options.on_after_click_menu_item(e, menu_item_elm, menu_item_props);
		}
	};
	
	me.executeMenuItemSimpleCommand = function(e, elm) {
		if (me.is_active && me.selection_elm) {
			elm = $(elm);
			var command = elm.attr("command");
			var command_value = elm[0].hasAttribute("command-value") ? elm.attr("command-value") : null;
			
			me.executeMenuItemCommand(e, elm[0], command, command_value);
		}
	};
	
	me.executeMenuItemCommandWithHandlers = function(e, menu_item_elm, command, command_value) {
		if (me.is_active) {
			var menu_item_props = me.getMenuItemPropsByMenuItemElement(menu_item_elm);
			
			if (typeof me.options.on_before_click_menu_item == "function")
				me.options.on_before_click_menu_item(e, menu_item_elm, menu_item_props);
			
			me.executeMenuItemCommand(e, menu_item_elm, command, command_value);
			
			if (typeof me.options.on_after_click_menu_item == "function")
				me.options.on_after_click_menu_item(e, menu_item_elm, menu_item_props);
		}
	};
	
	me.executeMenuItemCommand = function(e, menu_item_elm, command, command_value) {
		if (me.is_active && me.selection_elm && command) {
			menu_item_elm = $(menu_item_elm);
			var hide_menu = menu_item_elm.attr("hide-menu");
			var show_ui = menu_item_elm.attr("command-show-ui");
			var dependencies = menu_item_elm.attr("dependencies");
			var button_behaviour = menu_item_elm.attr("button-behaviour");
			
			show_ui = show_ui ? show_ui : false;
			hide_menu = hide_menu ? hide_menu : false;
			
			me.executeMenuItemCommandInSelection(command, show_ui, command_value);
			
			if (button_behaviour) {
				menu_item_elm.addClass("active");
				
				setTimeout(function() {
					menu_item_elm.removeClass("active");
				}, 300);
			}
			else
				menu_item_elm.toggleClass("active");
			
			if (hide_menu)
				me.hideMenu();
			
			if (dependencies) //dependencies is a selector
				me.menu_elm.find(dependencies).each(function(idx, item) {
					me.loadMenuItemByMenuItemElement(item);
				});
		}
	};
	
	me.executeMenuItemCommandInSelection = function(command, show_ui, command_value) {
		if (me.is_active)
			me.selection_elm.ownerDocument.execCommand(command, show_ui, command_value);
	};
	
	me.getMenuItemCommandValue = function(command) {
		var value = me.editor_elm[0].ownerDocument.queryCommandValue(command);
		value = value == "false" ? false : value;
		//console.log(command+":"+value);
		
		return value;
	};
	
	/* OLd version - DEPRECATED
	me.loadMenu = function() {
		if (me.selection_opts) {
			me.menu_elm.find(".text-selection-item").removeClass("active");
			
			var common_parents = me.selection_opts.common_parents;
			var tags = [];
			
			for (var i = 0; i < opts.common_parents.length; i++)
				if (common_parents[i].nodeType == 1)
					tags.push(common_parents[i].nodeName.toLowerCase());
			
			if (tags)
				for (var i = 0; i < tags.length; i++)
					switch(tags[i]) {
						case "strong":
						case "b":
							me.menu_elm.find(".bold").addClass("active");
							break;
						case "i":
							me.menu_elm.find(".italic").addClass("active");
							break;
						case "u":
							me.menu_elm.find(".underline").addClass("active");
							break;
						case "s":
						case "strike":
							me.menu_elm.find(".strikethrough").addClass("active");
							break;
					}
		}
	};*/
	
	me.loadMenu = function() {
		if (me.selection_opts) {
			me.menu_elm.find(".text-selection-item").removeClass("active");
			
			for (var i = 0; i < me.menu_items_props.length; i++) 
				if ($.isPlainObject(me.menu_items_props[i]))
					me.loadMenuItem(me.menu_items_props[i]);
		}
	};
	
	me.loadMenuItem = function(menu_item_props) {
		if (menu_item_props) {
			var menu_item_class = menu_item_props["class"];
					
			if (menu_item_class) {
				menu_item_class = "." + menu_item_class.replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ").replace(/ /g, ".");
				var menu_item_elm = me.menu_elm.find(menu_item_class);
				
				if (menu_item_elm[0]) {
					if (typeof menu_item_props["load"] == "function")
						menu_item_props["load"](menu_item_elm[0]);
					else if (menu_item_elm.hasClass("text-selection-command-item"))
						me.loadSimpleMenuItem(menu_item_elm[0], menu_item_props["command"]);
				}
			}
		}
	};
	
	me.loadMenuItemByClass = function(classes_selector) {
		var menu_item_props = me.getMenuItemPropsByClass(classes_selector);
		me.loadMenuItem(menu_item_props);
	};
	
	me.loadMenuItemByMenuItemElement = function(menu_item_elm) {
		var menu_item_props = me.getMenuItemPropsByMenuItemElement(menu_item_elm);
		me.loadMenuItem(menu_item_props);
	};
	
	me.loadSimpleMenuItem = function(menu_item_elm, command) {
		if (command) {
			var value = me.getMenuItemCommandValue(command);
			
			if (value)
				$(menu_item_elm).addClass("active");
			else
				$(menu_item_elm).removeClass("active");
		}
	};
	
	me.getMenuItemPropsByClass = function(classes_selector) {
		if (classes_selector) {
			classes_selector = classes_selector.replace(/[.]+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ").toLowerCase();
			var classes = classes_selector.split(" ");
			
			if (classes.length) 
				for (var i = 0; i < me.menu_items_props.length; i++) {
					var menu_item_props = me.menu_items_props[i];
					
					if ($.isPlainObject(menu_item_props)) {
						var menu_item_class = menu_item_props["class"];
						
						if (menu_item_class) {
							menu_item_class = menu_item_class.replace(/[.]+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "").replace(/\s\s+/g, " ").toLowerCase();
							var menu_item_classes = menu_item_class.split(" ");
							
							if (menu_item_classes) {
								var exists = true;
								
								for (var j = 0; j < classes.length; j++) 
									if (classes[j] != "" && menu_item_classes.indexOf(classes[j]) == -1) {
										exists = false;
										break;
									}
								
								if (exists) 
									return menu_item_props;
							}
						}
					}
				}
		}
		
		return null;
	};
	
	me.getMenuItemPropsByMenuItemElement = function(menu_item_elm) {
		var classes = menu_item_elm.getAttribute("class");
		classes = classes ? classes.replace("text-selection-item", "").replace("active", "") : "";
		
		return me.getMenuItemPropsByClass(classes);
	};
	
	me.addMenuItemProps = function(new_menu_item_props, add_after_menu_item_class) {
		if (add_after_menu_item_class) {
			var afer_menu_item_props = me.getMenuItemPropsByClass(add_after_menu_item_class);
			var new_menu_items_props = [];
			
			for (var i = 0; i < me.menu_items_props.length; i++) {
				var menu_item_props = me.menu_items_props[i];
				new_menu_items_props.push(menu_item_props);
				
				if (afer_menu_item_props == menu_item_props)
					new_menu_items_props.push(new_menu_item_props);
			}
			
			me.menu_items_props = new_menu_items_props;
		}
		else
			me.menu_items_props.push(new_menu_item_props);
	};
	
	/* Menu functions */
	
	me.setMenuWidth = function(width) {
		if (width) 
			me.menu_elm.css("width", width + "px");
		else
			me.menu_elm.css("width", ""); //remove width property if it was previously set
	};
	me.getMenuWidth = function() {
		return me.menu_elm.width() + parseInt(me.menu_elm.css("padding-left")) + parseInt(me.menu_elm.css("padding-right")) + parseInt(me.menu_elm.css("border-left-width")) + parseInt(me.menu_elm.css("border-right-width"));
	};
		
	me.setMenuHeight = function(height) {
		if (height)
			me.menu_elm.css("height", height + "px");
		else
			me.menu_elm.css("height", ""); //remove height property if it was previously set
	};
	me.getMenuHeight = function() {
		return me.menu_elm.height() + parseInt(me.menu_elm.css("padding-top")) + parseInt(me.menu_elm.css("padding-bottom")) + parseInt(me.menu_elm.css("border-top-width")) + parseInt(me.menu_elm.css("border-bottom-width"));
	};
	
	me.refreshMenu = function(e) {
		if (me.isMenuShown()) {
			me.hideMenu(e); //hide is very important in order to execute the on_before_hide_menu andon_after_hide_menu. This handlers may change the layouts, like it happens in the LayoutUIEditor.js
			me.showMenu(e); //hide is very important in order to execute the on_before_show_menu andon_after_show_menu. This handlers may change the layouts, like it happens in the LayoutUIEditor.js
		}
	};
	
	me.isMenuShown = function(e) {
		return me.is_active && me.menu_elm.css("display") != "none";
	};
	
	me.showMenu = function(e) {
		if (me.is_active) {
			me.menu_elm.children(".menu-item-popup").hide(); //it's very important otherwise the menu height will be bigger than the expected. Do not pass any argument in the hide(..) function otherwise it will become assynchronous and when we get the menu height, we will get the wrong height. Wihtout any argument the hide function is synchronous.
			
			if (typeof me.options.on_before_show_menu == "function")
				me.options.on_before_show_menu(e);
			
			me.setMenuWidth(me.options.width);
			me.setMenuHeight(me.options.height);
			
			if (!me.options.sticky_menu) {
				var top = null;
				var left = null;
				var selection_doc = $(me.editor_elm[0].ownerDocument);
				var selection_scroll_top = selection_doc.scrollTop();
				var selection_scroll_left = selection_doc.scrollLeft();
				var menu_height = me.getMenuHeight(); //only gets height after sets width
				
				//top = pos.top + me.options.top;
				top = e.clientY + me.options.top - selection_scroll_top;
				//top = e.screenY + me.options.top - selection_scroll_top;
				//left = pos.left + me.options.left;
				left = e.clientX + me.options.left - selection_scroll_left;
				//left = e.screenX + me.options.left - selection_scroll_left;
				
				if (me.selection_opts && me.selection_opts.first_node) {
					var first_node = me.selection_opts.first_node;
					
					if (first_node.nodeType == Node.TEXT_NODE)
						first_node = first_node.parentNode;
					
					if (me.editor_elm.is(first_node)) {
						top = me.options.top;
						left = me.options.left;
					}
					else {
						var offset = $(first_node).offset();
							
						top = offset.top + me.options.top - selection_scroll_top - menu_height;
						left = offset.left + me.options.left - selection_scroll_left;
					}
				}
				
				if (top < me.options.top)
					top = me.options.top;
				
				if (left < me.options.left)
					left = me.options.left;
				
				me.menu_elm.css({
					"position": "fixed",
					"top": top + "px",
					"left": left + "px",
					"right": "auto",
					"bottom": "auto",
				});
			}
			
			me.menu_elm.show();
			
			if (typeof me.options.on_after_show_menu == "function")
				me.options.on_after_show_menu(e);
		}
	};
	
	me.hideMenu = function() {
		if (me.is_active) {
			
			if (typeof me.options.on_before_hide_menu == "function")
				me.options.on_before_hide_menu();
			
			//Note: DO NOT clear the document.range, otherwise the contenteditable element will stop working
			me.menu_elm.hide(); //do not use me.hideMenu(), otherwise we will have an infinity loop. we only want to change the display style to none.
			
			if (typeof me.options.on_after_hide_menu == "function")
				me.options.on_after_hide_menu();
		}
	};
	
	/* Not working - DEPRECATED
	me.getScrollBarWidth = function() {
		var selection_doc = me.editor_elm[0].ownerDocument;
		var selection_doc_win = selection_doc.defaultView || selection_doc.parentWindow;
		var scrollbar_width = selection_doc_win.innerWidth - selection_doc.documentElement.clientWidth;
		
		return scrollbar_width;
	};*/
	me.getScrollBarWidth = function() {
		// Creating invisible container
		var selection_doc = me.editor_elm[0].ownerDocument;
		const outer = selection_doc.createElement('div');
		outer.style.visibility = 'hidden';
		outer.style.overflow = 'scroll'; // forcing scrollbar to appear
		outer.style.msOverflowStyle = 'scrollbar'; // needed for WinJS apps
		selection_doc.body.appendChild(outer);

		// Creating inner element and placing it in the container
		const inner = selection_doc.createElement('div');
		outer.appendChild(inner);

		// Calculating difference between container's full width and the child width
		const scrollbar_width = (outer.offsetWidth - inner.offsetWidth);

		// Removing temporary elements from the DOM
		outer.parentNode.removeChild(outer);

		return scrollbar_width;
	};
	
	function getAvailableMenuItemsProps() {
		return {
			bold: {
				class: "text-selection-command-item show-first bold", 
				icon: "zmdi zmdi-format-bold", 
				command: "bold", 
				title: "Bold text"
			}, 
			italic: {
				class: "text-selection-command-item show-first italic", 
				icon: "zmdi zmdi-format-italic", 
				command: "italic", 
				title: "Italic text"
			}, 
			underline: {
				class: "text-selection-command-item show-first underline", 
				icon: "zmdi zmdi-format-underlined", 
				command: "underline", 
				title: "Underline text"
			}, 
			strikethrough: {
				class: "text-selection-command-item strikethrough", 
				icon: "zmdi zmdi-format-strikethrough-s", 
				command: "strikethrough", 
				title: "Strikethrough text"
			}, 
			delete: {
				class: "text-selection-command-item delete", 
				icon: "zmdi zmdi-delete", 
				command: "delete", 
				//"hide-menu": 1, //no need for hidding menu. If menu is open, will continue open
				"button-behaviour": 1, 
				title: "Delete text"
			}, 
			indent: {
				class: "text-selection-command-item show-first indent", 
				icon: "zmdi zmdi-format-indent-increase", 
				command: "indent", 
				"button-behaviour": 1, 
				title: "Indent"
			}, 
			outdent: {
				class: "text-selection-command-item show-first outdent", 
				icon: "zmdi zmdi-format-indent-decrease", 
				command: "outdent", 
				"button-behaviour": 1, 
				title: "Outdent"
			}, 
			"insert-hr": {
				class: "text-selection-command-item insert-hr", 
				icon: "zmdi zmdi-minus", 
				command: "insertHorizontalRule", 
				"button-behaviour": 1, 
				title: "Insert horizontal line"
			},  
			"insert-ul": {
				class: "text-selection-command-item insert-ul", 
				icon: "zmdi zmdi-format-list-bulleted", 
				command: "insertUnorderedList", 
				title: "Insert unordered list"
			}, 
			"insert-ol": {
				class: "text-selection-command-item insert-ol",
				icon: "zmdi zmdi-format-list-numbered", 
				command: "insertOrderedList", 
				title: "Insert ordered list"
			}, 
			"insert-p": {
				class: "text-selection-command-item insert-p", 
				icon: "zmdi zmdi-wrap-text", 
				command: "insertParagraph", 
				"button-behaviour": 1, 
				title: "Insert paragraph"
			}, 
			"align-left": {
				class: "text-selection-command-item show-first align-left", 
				icon: "zmdi zmdi-format-align-left", 
				command: "justifyLeft", 
				dependencies: ".align-center, .align-justify, .align-right", 
				title: "Align left",
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue( menu_item_elm.getAttribute("command") );
					//console.log(menu_item_elm.getAttribute("command")+"!"+value+"!"+typeof value);
					
					if (value === true || value == "true" || value == "left")
						$(menu_item_elm).addClass("active");
					else
						$(menu_item_elm).removeClass("active");
				}
			}, 
			"align-center": {
				class: "text-selection-command-item show-first align-center", 
				icon: "zmdi zmdi-format-align-center", 
				command: "justifyCenter", 
				dependencies: ".align-justify, .align-left, .align-right", 
				title: "Align center",
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue( menu_item_elm.getAttribute("command") );
					//console.log(menu_item_elm.getAttribute("command")+"!"+value+"!"+typeof value);
					
					if (value === true || value == "true" || value == "center")
						$(menu_item_elm).addClass("active");
					else
						$(menu_item_elm).removeClass("active");
				}
			}, 
			"align-right": {
				class: "text-selection-command-item show-first align-right", 
				icon: "zmdi zmdi-format-align-right", 
				command: "justifyRight", 
				dependencies: ".align-center, .align-justify, .align-left", 
				title: "Align right",
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue( menu_item_elm.getAttribute("command") );
					//console.log(menu_item_elm.getAttribute("command")+"!"+value+"!"+typeof value);
					
					if (value === true || value == "true" || value == "right")
						$(menu_item_elm).addClass("active");
					else
						$(menu_item_elm).removeClass("active");
				}
			}, 
			"align-justify": {
				class: "text-selection-command-item show-first align-justify", 
				icon: "zmdi zmdi-format-align-justify", 
				command: "justifyFull", 
				dependencies: ".align-center, .align-left, .align-right", 
				title: "Align justify",
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue( menu_item_elm.getAttribute("command") );
					//console.log(menu_item_elm.getAttribute("command")+"!"+value+"!"+typeof value);
					
					if (value === true || value == "true" || value == "justify")
						$(menu_item_elm).addClass("active");
					else
						$(menu_item_elm).removeClass("active");
				}
			}, 
			"remove-format": {
				class: "text-selection-command-item remove-format", 
				icon: "zmdi zmdi-format-clear", 
				command: "removeFormat", 
				dependencies: ".bold, .italic, .underline, .strikethrough, .align-center, .align-justify, .align-left, .align-right, .subscript, .superscript, .font-size, .font-family", 
				"button-behaviour": 1, 
				title: "Remove format"
			}, 
			"select-all": {
				class: "text-selection-command-item select-all", 
				icon: "zmdi zmdi-select-all", 
				command: "selectAll", 
				"button-behaviour": 1, 
				title: "Select all"
			}, 
			"unselect": {
				class: "text-selection-command-item unselect", 
				icon: "zmdi zmdi-select-all", 
				command: "unselect", 
				"button-behaviour": 1, 
				title: "Unselect",
				style: "opacity:.2",
			}, 
			"subscript": {
				class: "text-selection-command-item subscript", 
				command: "subscript", 
				html: 'sub', 
				dependencies: ".superscript", 
				title: "Subscript"
			}, 
			"superscript": {
				class: "text-selection-command-item superscript", 
				command: "superscript", 
				html: 'sup', 
				dependencies: ".subscript", 
				title: "Superscript"
			}, 
			"undo": {
				class: "text-selection-command-item undo", 
				icon: "zmdi zmdi-undo", 
				command: "undo", 
				"button-behaviour": 1, 
				title: "Undo"
			},
			"redo": {
				class: "text-selection-command-item redo", 
				icon: "zmdi zmdi-redo", 
				command: "redo", 
				"button-behaviour": 1, 
				title: "Redo"
			},
			"paste": {
				class: "paste", 
				icon: "zmdi zmdi-paste", 
				title: "Paste previously copied widget",
				click: function(e, menu_item_elm) {
					if (typeof me.options.on_paste_widget == "function")
						me.options.on_paste_widget();
					else
						me.showError("No paste function defined! Please report this issue to the web-developer.");
				},
			},
			"unlink": {
				class: "text-selection-command-item show-first unlink", 
				icon: "zmdi zmdi-link", 
				command: "unlink", 
				style: "opacity:.5;", 
				dependencies: ".link", 
				"button-behaviour": 1, 
				title: "Remove link"
			},
			"link": {
				class: "show-first link", 
				icon: "zmdi zmdi-link", 
				command: "createLink", 
				title: "Insert link", 
				click: function(e, menu_item_elm) {
					var link_elm = me.selection_opts.is_same_node && me.selection_opts.first_node && me.selection_opts.first_node.nodeType == Node.ELEMENT_NODE && me.selection_opts.first_node.nodeName == "A" ? me.selection_opts.first_node : $(me.selection_opts.common_parents).filter("a")[0];
					var current_url = link_elm && link_elm.hasAttribute("href") ? link_elm.getAttribute("href") : "";
					
					var url = prompt("Please write the url for the link:", current_url ? current_url : "");
					url = url ? url.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
					
					if (url) {
						var first_node = me.selection_opts.first_node;
						var node = first_node.nodeType == Node.TEXT_NODE ? first_node.parentNode : first_node;
						
						me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), url);
						
						if (typeof me.options.on_create_node == "function")
							$(node).children("a").each(function(idx, a) {
								if (a.getAttribute("href") == url)
									me.options.on_create_node(e, menu_item_elm, a);
							});
					}
				},
				load: function(menu_item_elm) {
					me.loadSimpleMenuItem(menu_item_elm, menu_item_elm.getAttribute("command"));
					
					var link_elm = me.selection_opts.is_same_node && me.selection_opts.first_node && me.selection_opts.first_node.nodeType == Node.ELEMENT_NODE && me.selection_opts.first_node.nodeName == "A" ? me.selection_opts.first_node : $(me.selection_opts.common_parents).filter("a")[0];
					
					if (link_elm)
						$(menu_item_elm).addClass("active");
				},
			},
			image: {
				class: "image", 
				icon: "zmdi zmdi-image", 
				command: "insertImage", 
				title: "Insert image",
				click: function(e, menu_item_elm) {
					var img_elm = me.selection_opts.is_same_node && me.selection_opts.first_node && me.selection_opts.first_node.nodeType == Node.ELEMENT_NODE && me.selection_opts.first_node.nodeName == "IMG" ? me.selection_opts.first_node : (me.selection_opts.common_parent && me.selection_opts.common_parent.nodeName == "IMG" ? me.selection_opts.common_parent : null);
					var current_src = img_elm && img_elm.hasAttribute("src") ? img_elm.getAttribute("src") : "";
					
					var src = prompt("Please write the src for the image:", current_src ? current_src : "");
					src = src ? src.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
					
					if (src) {
						var first_node = me.selection_opts.first_node;
						var node = first_node.nodeType == Node.TEXT_NODE ? first_node.parentNode : first_node;
						
						me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), src);
						
						if (typeof me.options.on_create_node == "function")
							$(node).children("img").each(function(idx, img) {
								if (img.getAttribute("src") == src)
									me.options.on_create_node(e, menu_item_elm, img);
							});
					}
				},
				load: function(menu_item_elm) {
					me.loadSimpleMenuItem(menu_item_elm, menu_item_elm.getAttribute("command"));
				},
			},
			spellcheck: {
				class: "spellcheck", 
				icon: "zmdi zmdi-spellcheck", 
				title: "Activate or inactivate spell-checker",
				click: function(e, menu_item_elm) {
					menu_item_elm = $(menu_item_elm);
					
					for (var i = 0; i < me.editor_elm.length; i++)
						me.editor_elm[i].spellcheck = menu_item_elm.hasClass("active") ? false : true;
					
					menu_item_elm.toggleClass("active");
				},
				load: function(menu_item_elm) {
					if (me.editor_elm[0].spellcheck)
						$(menu_item_elm).addClass("active");
				},
			},
			"text-color": {
				class: "back-hover show-first text-color", 
				icon: "zmdi zmdi-format-color-text set-color", 
				command: "foreColor", 
				"button-behaviour": 1, 
				style: "padding-left:2px; padding-right:2px", 
				title: "Set text color",
				click: function(e, menu_item_elm) {
					var input = me.menu_elm.find(".text-color-caret > input");
					var color = input.val();
					
					if (color) 
						me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), color);
					else
						input.trigger("click");
				},
			},
			"text-color-caret": {
				class: "back-hover show-first text-color-caret", 
				command: "foreColor", 
				"button-behaviour": 1, 
				style: "padding-left:2px; padding-right:2px", 
				title: "Choose a text color",
				html: '<i class="zmdi zmdi-caret-down caret"></i><input type="color" style="display:none" />',
				init: function(menu_item_elm) {
					menu_item_elm = $(menu_item_elm);
					var input = menu_item_elm.children("input");
					
					input.on('input', function(e) {
						var color = input.val();
						color = color ? color : null;
						
						me.executeMenuItemCommandWithHandlers(e, menu_item_elm[0], menu_item_elm.attr("command"), color);
						
						menu_item_elm.css("color", color);
						me.menu_elm.find(".text-color").css("color", color);
					});
					
					menu_item_elm.children("i").on('click', function(menu_item_elm) {
						input.trigger("click");
					});
				},
			},
			"back-color": {
				class: "back-hover show-first back-color", 
				icon: "zmdi zmdi-border-color set-color", 
				command: "backColor", 
				"button-behaviour": 1, 
				style: "padding-left:2px; padding-right:2px", 
				title: "Set a text background color",
				click: function(e, menu_item_elm) {
					var input = me.menu_elm.find(".back-color-caret > input");
					var color = input.val();
					
					if (color) 
						me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), color);
					else
						input.trigger("click");
				},
			},
			"back-color-caret": {
				class: "back-hover show-first back-color-caret", 
				command: "backColor", 
				"button-behaviour": 1, 
				style: "padding-left:2px; padding-right:2px", 
				title: "Choose a background color",
				html: '<i class="zmdi zmdi-caret-down caret"></i><input type="color" style="display:none" />',
				init: function(menu_item_elm) {
					menu_item_elm = $(menu_item_elm);
					var input = menu_item_elm.children("input");
					
					input.on('input', function(e) {
						var color = input.val();
						color = color ? color : null;
						
						me.executeMenuItemCommandWithHandlers(e, menu_item_elm[0], menu_item_elm.attr("command"), color);
						
						menu_item_elm.css("color", color);
						me.menu_elm.find(".back-color").css("color", color);
					});
					
					menu_item_elm.children("i").on('click', function(menu_item_elm) {
						input.trigger("click");
					});
				},
			},
			"reset-color": {
				class: "reset-color", 
				icon: "zmdi zmdi-format-color-reset", 
				"button-behaviour": 1, 
				title: "Reset color",
				click: function(e, menu_item_elm) {
					var color = "#1C00ff00";
					
					me.executeMenuItemCommand(e, menu_item_elm, "foreColor", color);
					me.executeMenuItemCommand(e, menu_item_elm, "backColor", color);
				},
			},
			"font-size": {
				class: "show-first font-size", 
				style: "border:0;", 
				command: "fontSize", 
				title: "Font size",
				html: '<select><option disabled selected>Size</option><option value="inherit"></option><option value="1">8pt</option><option value="2">10pt</option><option value="3">12pt</option><option value="4">14pt</option><option value="5">18pt</option><option value="6">24pt</option><option value="7">36pt</option></select>',
				init: function(menu_item_elm) {
					$(menu_item_elm).children("select").on('change', function(e) {
						me.executeMenuItemCommandWithHandlers(e, menu_item_elm, menu_item_elm.getAttribute("command"), $(this).val());
					});
				},
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue(menu_item_elm.getAttribute("command"));
					var select = $(menu_item_elm).children("select");
					select.find("option.dynamic").remove();
					select.val(value);
					
					if (select.val() != value) {
						if (value || parseInt(value) === 0) {
							select.append('<option class="dynamic">' + value + '</option>');
							select.val(value);
						}
						else
							select.children("option").first().attr("selected", "selected");
					}
				},
			},
			"font-family": {
				class: "font-family", 
				style: "border:0;", 
				command: "fontName", 
				title: "Font family",
				html: '<select style="width:100px;"><option disabled selected>Font</option></select>',
				init: function(menu_item_elm) {
					var font_families = ["avenir,arial,helvetica,sans-serif", "andale mono,times", "arial,helvetica,sans-serif", "arial black,avant garde", "book antiqua,palatino", "comic sans ms,sans-serif", "courier new,courier", "georgia,palatino", "helvetica", "impact,chicago", "symbol", "tahoma,arial,helvetica,sans-serif", "terminal,monaco", "times new roman,times", "trebuchet ms,geneva", "verdana,geneva", "'Lucida Sans Unicode', 'Lucida Grande', sans-serif", "'Lucida Console', Monaco, monospace"];
					
					var html = '<option value="inherit"></option>';
					
					for (var i = 0; i < font_families.length; i++)
						html += '<option>' + font_families[i] + '</option>';
					
					var select = $(menu_item_elm).children("select");
					select.append(html);
					select.on('change', function(e) {
						var font_family = $(this).val();
						
						if (font_family)
							me.executeMenuItemCommandWithHandlers(e, menu_item_elm, menu_item_elm.getAttribute("command"), font_family);
					});
				},
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue(menu_item_elm.getAttribute("command"));
					var value = value ? value.replace(/["' ]+/g, "").replace(/^,+/g, "").replace(/,+$/g, "").toLowerCase() : "";
					var selected_fonts = value.split(",");
					
					var select = $(menu_item_elm).children("select");
					var options = select.children("option");
					var selected_option = null;
					var selected_centered_option = null;
					var selected_descentered_option = null;
					var selected_centered_points = 0;
					var selected_descentered_points = 0;
					
					select.find("option.dynamic").remove();
					
					for (var i = 1; i < options.length; i++) {
						var option = options[i];
						var option_value = option.value ? option.value.replace(/["' ]+/g, "").replace(/^,+/g, "").replace(/,+$/g, "").toLowerCase() : "";
						var option_fonts = option_value.split(",");
						
						if (value == option_value) {
							selected_option = option;
							break;
						}
						else {
							var centered_points = 0;
							var descentered_points = 0;
							
							for (var j = 0; j < option_fonts.length; j++) {
								var font = option_fonts[j];
								var pos = font ? selected_fonts.indexOf(font) : -1;
								
								if (font && pos != -1) {
									if (pos == j)
										centered_points++;
									else
										descentered_points++;
								}
							}
							
							if (centered_points > selected_centered_points) {
								selected_centered_points = centered_points;
								selected_centered_option = option;
							}
							
							if (descentered_points > selected_descentered_points) {
								selected_descentered_points = descentered_points;
								selected_descentered_option = option;
							}
						}
					}
					
					if (!selected_option) {
						if (selected_centered_option)
							selected_option = selected_centered_option;
						else if (selected_descentered_option)
							selected_option = selected_descentered_option;
					}
					
					options.removeAttr("selected");
					select.val(value);
					
					/*if (selected_option) //selected_option disabled bc it use is confusing.
						$(selected_option).attr("selected", "selected");
					else */if (select.val() != value) {
						if (value) {
							select.append('<option class="dynamic">' + value + '</option>');
							select.val(value);
						}
						else
							options.first().attr("selected", "selected");
					}
				},
			},
			"format-block": {
				class: "format-block", 
				style: "border:0;", 
				command: "formatBlock", 
				title: "Enclose selection in a block",
				html: '<select><option disabled selected>Enclose within:</option><option value="p">Paragraph</option><option value="h1">Heading 1</option><option value="h2">Heading 2</option><option value="h3">Heading 3</option><option value="h4">Heading 4</option><option value="h5">Heading 5</option><option value="h6">Heading 6</option><option value="pre">Preformatted</option><option value="div">Block</option><option value="section">Section</option><option value="article">Article</option></select>',
				init: function(menu_item_elm) {
					$(menu_item_elm).children("select").on('change', function(e) {
						var node_name = $(this).val();
						
						if (node_name && me.selection_opts.common_parent && me.selection_opts.common_parent.parentNode) {
							//Do not use me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), node_name.toUpperCase()), bc it will mess the html
							
							var node = me.selection_opts.common_parent;
							var node_data = $(node).data();
							var node_events = $._data(node, 'events');
							var new_node = document.createElement(node_name);
							var old_node_name = node.nodeName;
							
							if (node.attributes)
								for (var i = 0; i < node.attributes.length; i++) {
									var node_attribute = node.attributes[i];
									new_node.setAttribute(node_attribute.name, node_attribute.value);
								}
							
							if (node_data)
								$(new_node).data(node_data);
							
							if (node.childNodes)
								while (node.firstChild)
									new_node.appendChild(node.firstChild);
							
							if (node_events)
								for (var type in node_events) {
									var node_event = node_events[type];
									
									for (var i = 0; i < node_event.length; i++) {
										var handler = node_event[i].handler;
										
										if (handler) {
											if (node_event[i].origType)
												type = node_event[i].origType;
											
											//console.log(type);
											$(new_node).on(type, handler);
										}
									}
								}
							//console.log($._data(new_node, 'events'));
							
							node.parentNode.insertBefore(new_node, node);
							node.parentNode.removeChild(node);
							
							//change the me.selection_opts with the new object, otherwise if we execute this action again, it will not work bc it lost the reference for the node.
							me.selection_elm = new_node.firstChild;
							
							var selection = me.getSelection();
							var range = document.createRange();
							selection.removeAllRanges();
							range.selectNodeContents(me.selection_elm);
							range.collapse(false);
							selection.addRange(range);
							new_node.focus();
							
							me.setSelectionElm(me.selection_elm);
							
							if (typeof me.options.on_create_node == "function")
								me.options.on_create_node(e, menu_item_elm, $(new_node), {changed_node_name: true, old_node_name: old_node_name});
						}
					});
				},
				load: function(menu_item_elm) {
					var value = me.getMenuItemCommandValue(menu_item_elm.getAttribute("command"));
					var select = $(menu_item_elm).children("select");
					select.find("option.dynamic").remove();
					select.val(value);
					
					if (select.val() != value) {
						if (value) {
							select.append('<option class="dynamic">' + value + '</option>');
							select.val(value);
						}
						else
							select.children("option").first().attr("selected", "selected");
					}
				},
			},
			"insert-html": {
				class: "insert-html", 
				icon: "zmdi zmdi-code", 
				//command: "insertHTML", 
				"button-behaviour": 1, 
				title: "Insert Html",
				click: function(e, menu_item_elm) {
					var html = prompt("Please write your html:");
					html = html ? html.replace(/^\s+/g, "").replace(/\s+$/g, "") : "";
					
					if (html) {
						//me.executeMenuItemCommand(e, menu_item_elm, menu_item_elm.getAttribute("command"), html); //Do not use this bc it will mess the html from widgets
						
						var opts = me.selection_opts;
						var node = opts.first_node;
						
						var html_elm = $(html);
						html_elm.insertAfter(node);
						
						if (node.nodeType == Node.TEXT_NODE) {
							//insert widget in between the text, where the cursor is.
							var offset = $.isNumeric(opts.first_offset) ? opts.first_offset : 0;
							var text = node.nodeValue;
							var first_text = text.substr(0, offset);
							var last_text = text.substr(offset);
							
							node.nodeValue = first_text;
							
							if (last_text != "") {
								var last_text_node = $(document.createTextNode(last_text));
								last_text_node.insertAfter(table);
							}
						}
						
						if (typeof me.options.on_create_node == "function") 
							me.options.on_create_node(e, menu_item_elm, html_elm);
					}
				},
			},
			"html-element-props": {
				class: "html-element-props", 
				icon: "zmdi zmdi-settings", 
				"button-behaviour": 1, 
				title: "Edit html element properties",
				click: function(e, menu_item_elm) {
					if (me.selection_opts && !me.selection_opts.has_range) {
						var first_node = me.selection_opts.first_node;
						var node = first_node && first_node.nodeType == Node.TEXT_NODE ? first_node.parentNode : first_node;
						
						if (node && node.nodeType == node.ELEMENT_NODE) {
							//console.log(node);
							
							//prepare popup_elm html
							var popup_elm = me.menu_elm.children(".menu-item-popup");
							
							if (popup_elm[0]) 
								popup_elm.remove();
							
							popup_elm = $('<div class="menu-item-popup element-props">'
									+ '	<div class="title">Html Element Props for "<span></span>"</div>'
									+ '	<div class="node-class">'
									+ '		<label>Class:</label>'
									+ '		<input />'
									+ '	</div>'
									+ '	<div class="node-attributes">'
									+ '		<label>Attributes: <i class="zmdi zmdi-hc-lg zmdi-plus add"></i></label>'
									+ '		<ul>'
									+ '			<li class="no-attributes">There are no other attributes</li>'
									+ '		</ul>'
									+ '	</div>'
									+ '	<div class="buttons">'
									+ '		<button class="apply"><i class="zmdi zmdi-hc-lg zmdi-check"></i> Apply</button>'
									+ '		<button class="cancel"><i class="zmdi zmdi-hc-lg zmdi-close"></i> Cancel</button>'
									+ '	</div>'
									+ '</div>');
							
							popup_elm.hide();
							me.menu_elm.append(popup_elm);
							
							var addItem = function() {
								var item = $('<li>'
									+ '	<input class="attribute-name" placeHolder="Attribute name">'
									+ '	<input class="attribute-value" placeHolder="Attribute value">'
									+ '	<i class="zmdi zmdi-hc-lg zmdi-delete delete"></i>'
									+ '</li>');
								
								item.find(".delete").on("click", function(e2) {
									if (confirm("Do you wish to remove this attribute?")) {
										var li = $(this).parent();
										var ul = li.parent();
										li.remove();
										
										if (ul.children().length == 1)
											ul.children(".no-attributes").show();
									}
								});
								
								var ul = popup_elm.find(".node-attributes").children("ul");
								ul.append(item);
								ul.children(".no-attributes").hide();
								
								return item;
							};
							
							popup_elm.find(".node-attributes .add").on("click", addItem);
							
							popup_elm.find(".buttons .cancel").on("click", function(e2) {
								popup_elm.hide("slow");
							});
							
							popup_elm.find(".buttons .apply").on("click", function(e2) {
								var main_elm = $(this).parent().closest(".element-props");
								var node_class = main_elm.find(".node-class input").val();
								var lis = main_elm.find(".node-attributes > ul li:not(.no-attributes)");
								var node_attributes = {};
								
								$.each(lis, function(idx, li) {
									li = $(li);
									var attr_name = li.find(".attribute-name").val();
									var attr_value = li.find(".attribute-value").val();
									attr_name = attr_name.replace(/\s+/g, "");
									
									if (attr_name)
										node_attributes[ attr_name.toLowerCase() ] = attr_value;
								});
								
								if (node_class)
									node_attributes["class"] = node_class + (node_attributes["class"] ? " " + node_attributes["class"] : "");
								
								if (typeof me.options.on_create_node_attributes == "function")
									me.options.on_create_node_attributes(e, menu_item_elm, node, node_attributes);
								
								var j_node = $(node);
								
								while (node.attributes.length > 0)
									j_node.removeAttr(node.attributes[0].name); //use j_node.removeAttr instead of node.removeAttribute otherwise if attribute is style, it won't be removed.
								
								for (var attr_name in node_attributes)
									j_node.attr(attr_name, node_attributes[attr_name]);
								
								popup_elm.hide("slow");
							});
							
							//clean values
							popup_elm.find(".node-class input").val("");
							
							var lis = popup_elm.find(".node-attributes > ul li");
							lis.filter(":not(.no-attributes)").remove();
							lis.filter(".no-attributes").show();
							
							//show popup
							popup_elm.show("slow");
							
							//load values
							popup_elm.find(".title span").html(node.nodeName);
							
							var node_attributes = {};
							for (var i = 0; i < node.attributes.length; i++)
								node_attributes[ node.attributes[i].name ] = node.attributes[i].value;
							
							if (typeof me.options.on_parse_node_attributes == "function")
								me.options.on_parse_node_attributes(e, menu_item_elm, node, node_attributes);
							
							if (node_attributes) 
								for (var attr_name in node_attributes) {
									if (attr_name == "class")
										popup_elm.find(".node-class input").val(node_attributes[attr_name]);
									else {
										var item = addItem();
										item.find(".attribute-name").val(attr_name);
										item.find(".attribute-value").val(node_attributes[attr_name]);
									}
								}
						}
					}
				},
				load: function(menu_item_elm) {
					if (me.selection_opts && !me.selection_opts.has_range) 
						$(menu_item_elm).css("opacity", "");
					else
						$(menu_item_elm).css("opacity", ".1");
				},
			},
			"insert-table": {
				class: "insert-table", 
				icon: "zmdi zmdi-grid", 
				"button-behaviour": 1, 
				title: "Insert table",
				click: function(e, menu_item_elm) {
					//prepare popup_elm html
					var popup_elm = me.menu_elm.children(".menu-item-popup");
					
					if (popup_elm[0]) 
						popup_elm.remove();
					
					popup_elm = $('<div class="menu-item-popup table-props">'
							+ '	<div class="title">Table Props</div>'
							+ '	<div class="thead">'
							+ '		<label>Table Head: <i class="zmdi zmdi-hc-lg zmdi-plus add"></i></label>'
							+ '		<div class="no-rows">There are no rows</div>'
							+ '		<table><tbody></tbody></table>'
							+ '	</div>'
							+ '	<div class="tbody">'
							+ '		<label>Table Body: <i class="zmdi zmdi-hc-lg zmdi-plus add"></i></label>'
							+ '		<div class="no-rows">There are no rows</div>'
							+ '		<table><tbody></tbody></table>'
							+ '	</div>'
							+ '	<div class="tfoot">'
							+ '		<label>Table Foot: <i class="zmdi zmdi-hc-lg zmdi-plus add"></i></label>'
							+ '		<div class="no-rows">There are no rows</div>'
							+ '		<table><tbody></tbody></table>'
							+ '	</div>'
							+ '	<div class="buttons">'
							+ '		<button class="apply"><i class="zmdi zmdi-hc-lg zmdi-check"></i> Apply</button>'
							+ '		<button class="cancel"><i class="zmdi zmdi-hc-lg zmdi-close"></i> Cancel</button>'
							+ '	</div>'
							+ '</div>');
					
					popup_elm.hide();
					me.menu_elm.append(popup_elm);
					
					popup_elm.children(".thead, .tbody, .tfoot").find(" > label > .add").each(function(idx, add) {
						add = $(add);
						
						add.on("click", function(e2) {
							var p = add.parent().parent();
							var tbody = p.find(" > table > tbody");
							
							var row = $('<tr><td class="actions"><i class="zmdi zmdi-hc-lg zmdi-plus add"></i><i class="zmdi zmdi-hc-lg zmdi-delete delete"></i><input placeHolder="Row attributes" /></td></tr>');
							
							row.find('.add').on("click", function(e3) {
								var td = $('<td><input placeHolder="Column attributes"/><i class="zmdi zmdi-hc-lg zmdi-close delete"></i></td>');
								td.find(".delete").on("click", function(e4) {
									td.remove();
								});
								
								row.append(td);
							});
							
							row.find('.delete').on("click", function(e3) {
								row.remove();
								
								if (tbody.children().length == 0)
									p.children(".no-rows").show();
							});
							
							p.children(".no-rows").hide();
							
							tbody.append(row);
							
							row.find('.add').trigger("click");
						});
					});
					
					popup_elm.find(".buttons .cancel").on("click", function(e2) {
						popup_elm.hide("slow");
					});
					
					popup_elm.find(".buttons .apply").on("click", function(e2) {
						var id = "temp_table_html_element_" + parseInt(Math.random() * 1000) + "_" + parseInt(Math.random() * 1000);
						var html = '<table id="' + id + '" style="width:100%;">';
						var sections = [ popup_elm.children(".thead")[0], popup_elm.children(".tbody")[0], popup_elm.children(".tfoot")[0] ];
						
						$.each(sections, function(idx, table_section) {
							table_section = $(table_section);
							var type = table_section.attr("class");
							var trs = table_section.find(" > table > tbody > tr");
							
							if (trs.length > 0) {
								html += '<' + type + '>';
								
								$.each(trs, function(idy, tr) {
									tr = $(tr);
									var tds = tr.children(":not(.actions)");
									var tr_attributes = tr.children(".actions").children("input").val();
									
									html += '<tr' + (tr_attributes ? ' ' + tr_attributes : '') + '>';
									
									$.each(tds, function(idw, td) {
										var td_attributes = $(td).children("input").val();
										var td_type = type == "thead" ? "th" : "td";
										
										html += '<' + td_type + (td_attributes ? ' ' + td_attributes : '') + '>&nbsp;</' + td_type + '>';
									});
									
									html += '</tr>';
								});
								
								html += '</' + type + '>';
							}
						});
						
						html += '</table>';
						
						var opts = me.selection_opts;
						var node = opts.first_node;
						
						var table = $(html);
						table.insertAfter(node);
						
						if (node.nodeType == Node.TEXT_NODE) {
							//insert widget in between the text, where the cursor is.
							var offset = $.isNumeric(opts.first_offset) ? opts.first_offset : 0;
							var text = node.nodeValue;
							var first_text = text.substr(0, offset);
							var last_text = text.substr(offset);
							
							node.nodeValue = first_text;
							
							if (last_text != "") {
								var last_text_node = $(document.createTextNode(last_text));
								last_text_node.insertAfter(table);
							}
						}
						
						if (typeof me.options.on_create_node == "function") 
							me.options.on_create_node(e, menu_item_elm, table);
						
						popup_elm.hide("slow");
					});
					
					//show popup
					popup_elm.show("slow");
				},
			},
			"toggle-menu": {
				class: "no-tooltip show-first toggle-menu", 
				icon: "zmdi zmdi-hc-lg zmdi-chevron-down", 
				"button-behaviour": 1, 
				title: "Toggle Menu",
				style: "float:right; position:absolute; top:5px; right:20px;", 
				click: function(e, menu_item_elm) {
					me.menu_elm.toggleClass("maximized-menu");
					me.showMenu(e);
				},
			},
			"close-menu": {
				class: "no-tooltip show-first close-menu", 
				icon: "zmdi zmdi-hc-lg zmdi-close", 
				"button-behaviour": 1, 
				title: "Hide Menu",
				style: "float:right; position:absolute; top:5px; right:0;", 
				click: function(e, menu_item_elm) {
					me.hideMenu();
				},
			},
		};
	}
	
	/* MESSAGES METHODS */
	
	function initMessages() {
		me.messages_elm.show();
		
		me.messages_elm.click(function() {
			me.removeMessages();
		});
	}
	
	me.showMessage = function(text, class_name) {
		me.prepareMessage(text, "info" + (class_name ? " " + class_name : ""));
	};
	
	me.showError = function(text, class_name) {
		me.prepareMessage(text, "error" + (class_name ? " " + class_name : ""));
	};
	
	me.prepareMessage = function(text, class_name, timeout) {
		if (text) {
			class_name = class_name.replace(/\s+/g, " ").replace(/^\s+/g, "").replace(/\s+$/g, "");
			timeout = timeout > 0 ? timeout : 5000;
			
			var class_id = class_name.replace(/ /g, "_");
			var class_selector = class_name.replace(/ /g, ".");
			var msg_id = class_id + "_" + (typeof $.md5 == "function" ? $.md5(text) : text.replace("/\s*/g", ""));
			
			//only show if message is not repeated
			if (me.messages_elm.children('#' + msg_id + '.message').length == 0) {
				var created_time = (new Date()).getTime();
				var last_msg_elm = me.messages_elm.children().last();
				
				//prepare message element
				if (last_msg_elm.is(".message.message-" + class_selector) && last_msg_elm.data("created_time") + 1500 > created_time) { //if there is already a message created in the previous 1.5seconds, combine this text with that message element.
					var clone = last_msg_elm.clone();
					clone.children(".close").remove();
					var previous_text = clone.html();
					var new_text = previous_text + "<br/>" + text;
					msg_id = class_id + "_" + (typeof $.md5 == "function" ? $.md5(new_text) : new_text.replace("/\s*/g", ""));
					clone.remove();
					
					last_msg_elm.attr("id", msg_id).children(".close").last().before( "<br/>" + text );
					
					//renew timeout
					var close_icon = last_msg_elm.children(".close");
					var timeout_id = last_msg_elm.data("timeout_id");
					timeout_id && clearTimeout(timeout_id);
					
					timeout_id = setTimeout(function() { 
						close_icon.trigger("click");
					}, timeout);
					last_msg_elm.data("timeout_id", timeout_id);
				}
				else { //if new message element
					var html = $('<div id="' + msg_id + '" class="message message-' + class_name + '">' + text + '<i class="zmdi zmdi-close close"></i></div>');
					var close_icon = html.children(".close");
					
					var timeout_id = setTimeout(function() {
						close_icon.trigger("click");
					}, timeout);
					
					html.click(function(event) {
						event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from .messages
					});
					
					close_icon.click(function(event) {
						event && typeof event.stopPropagation == "function" && event.stopPropagation(); //avoids to call the onClick event from .messages
						
						me.removeMessage(this);
						me.showMenu(window.event);
						
						if (timeout_id)
							clearTimeout(timeout_id);
					});
					
					html.data("created_time", created_time);
					
					me.messages_elm.append(html);
				}
				
				me.showMenu(window.event);
			}
		}
	};
	
	me.removeMessage = function(elm) {
		$(elm).parent().remove();
	};
	
	me.removeMessages = function(type) {
		var selector = type ? ".message-" + type : ".message";
		me.messages_elm.children(selector).remove();
	};
}
