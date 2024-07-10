var saved_record_obj_id = null;

$(function () {
	if (!is_popup)
		$(window).bind('beforeunload', function () {
			if (isRecordChanged()) {
				if (window.parent && window.parent.iframe_overlay)
					window.parent.iframe_overlay.hide();
				
				return "If you proceed your changes won't be saved. Do you wish to continue?";
			}
			
			return null;
		});
	
	var manage_record = $(".manage_record");
	prepareRecordFields(manage_record);
	
	//set saved_record_obj_id
	saved_record_obj_id = getRecordObjId();
});

function getRecordObjId() {
	var manage_record = $(".manage_record");
	var attributes = getAttributesObj(manage_record);
	
	return $.md5(JSON.stringify(attributes));
}

function isRecordChanged() {
	var new_record_obj_id = getRecordObjId();
	
	return saved_record_obj_id != new_record_obj_id;
}

function downloadFile(elm, attr_name) {
	if (attr_name) {
		elm = $(elm);
		var manage_record = elm.parent().closest(".manage_record");
		var pks = getPKsObj(manage_record);
		
		if (!$.isEmptyObject(pks)) {
			var query_string = "";
			for (var k in pks)
				query_string += "&pks[" + k + "]=" + pks[k];
			
			var url = manage_record_action_url + "&download=1&attr_name=" + attr_name + query_string;
			window.open(url, "download_file");
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
	else 
		StatusMessageHandler.showError("No attribute name specify!");
}

function deleteRecord(elm) {
	if (confirm("Do you wish to delete this record?")) {
		elm = $(elm);
		var manage_record = elm.parent().closest(".manage_record");
		var pks = getPKsObj(manage_record);
		
		if (!$.isEmptyObject(pks)) {
			$.ajax({
				type : "post",
				url : manage_record_action_url,
				data : {"action" : "delete", "conditions" : pks},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					if (data == "1") {
						manage_record.find("table, .buttons").remove();
						manage_record.append('<div class="error">Record deleted successfully</div>');
						
						//set saved_record_obj_id
						saved_record_obj_id = getRecordObjId();
						
						StatusMessageHandler.showMessage("Record deleted successfully!", "", "bottom_messages", 1500);
						
						window.parent.deleteCurrentRow();
					}
					else
						StatusMessageHandler.showError(data ? data : "Error deleting this record. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			});
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
}

function saveRecord(elm, do_not_confirm) {
	if (do_not_confirm || confirm("Do you wish to save this record?")) {
		elm = $(elm);
		var manage_record = elm.parent().closest(".manage_record");
		var pks = getPKsObj(manage_record);
		var attributes = getAttributesObj(manage_record);
		var file_inputs_exist = fileInputsContainsNewFiles(manage_record.find("input[type=file]"));
		
		if (!$.isEmptyObject(pks) && (!$.isEmptyObject(attributes) || file_inputs_exist)) {
			var ajax_options = {
				type : "post",
				url : manage_record_action_url,
				data : {"action" : "update", "attributes": attributes, "conditions" : pks},
				dataType : "text",
				success : function(data, textStatus, jqXHR) {
					if (data == "1") {
						//set saved_record_obj_id
						saved_record_obj_id = getRecordObjId();
						
						StatusMessageHandler.showMessage("Record saved successfully!", "", "bottom_messages", 1500);
						
						//update pks in case the user change them
						if (attributes)
							for (var k in pks)
								if (attributes[k] != pks[k]) {
									pks[k] = attributes[k];
									
									//update pks in html
									manage_record.find("input[type=hidden][name=" + k + "]").val(attributes[k]);
								}
						
						if (file_inputs_exist)
							updateNewFileAttributes(elm, pks);
						
						window.parent.updateCurrentRow(pks);
					}
					else
						StatusMessageHandler.showError(data ? data : "Error saving this record. Please try again...");
				},
				error : function(jqXHR, textStatus, errorThrown) { 
					if (jqXHR.responseText)
						StatusMessageHandler.showError(jqXHR.responseText);
				},
			};
			
			if (file_inputs_exist) {
				ajax_options["data"] = getFormDataObjectWithUploadedFiles(ajax_options["data"], manage_record.find("input[type=file]"));
				ajax_options["contentType"] = false;
				ajax_options["processData"] = false;
				ajax_options["cache"] = false;
			}
			
			$.ajax(ajax_options);
		}
		else 
			StatusMessageHandler.showError("Could not get primary keys for this record!");
	}
}

function addRecord(elm) {
	elm = $(elm);
	var manage_record = elm.parent().closest(".manage_record");
	var attributes = getAttributesObj(manage_record);
	var file_inputs_exist = fileInputsContainsNewFiles(manage_record.find("input[type=file]"));
	
	if (!$.isEmptyObject(attributes) || file_inputs_exist) {
		var ajax_options = {
			type : "post",
			url : manage_record_action_url,
			data : {"action" : "insert", "attributes": attributes},
			dataType : "text",
			success : function(data, textStatus, jqXHR) {
				if (!$.isNumeric(data) && ("" + data).substr(0, 1) == "{") {
					try {
						data = JSON.parse(data);
					}
					catch (e) {}
				}
				
				if ( ($.isNumeric(data) && data > 0) || $.isPlainObject(data) ) {
					//set saved_record_obj_id
					saved_record_obj_id = getRecordObjId();
					
					var msg = $('<div class="ok">Record added successfully!<br/>To add another record click <a href="#">here</a></div>');
					msg.find("a").click(function(e) {
						msg.remove();
						manage_record.find("table, .buttons").show();
					});
					
					manage_record.find("table").first().before(msg);
					manage_record.find("table, .buttons").hide();
					
					StatusMessageHandler.showMessage("Record added successfully!", "", "bottom_messages", 1500);
					
					//update attributes with new values
					if (attributes)
						if ($.isPlainObject(data))
							for (var k in data)
								if (attributes.hasOwnProperty(k))
									attributes[k] = data[k];
					
					//call function to add new record in table
					window.parent.addCurrentRow(attributes);
					
					//update file in table
					if (file_inputs_exist && $.isPlainObject(data))
						window.parent.updateCurrentRow(data);
				}
				else
					StatusMessageHandler.showError(data ? data : "Error saving this record. Please try again...");
			},
			error : function(jqXHR, textStatus, errorThrown) { 
				if (jqXHR.responseText)
					StatusMessageHandler.showError(jqXHR.responseText);
			},
		};
		
		if (file_inputs_exist) {
			ajax_options["data"] = getFormDataObjectWithUploadedFiles(ajax_options["data"], manage_record.find("input[type=file]"));
			ajax_options["contentType"] = false;
			ajax_options["processData"] = false;
			ajax_options["cache"] = false;
		}
		
		$.ajax(ajax_options);
	}
}

function updateNewFileAttributes(elm, pks) {
	$.ajax({
		type : "post",
		url : manage_record_action_url,
		data : {"action" : "get", "conditions" : pks},
		dataType : "json",
		success : function(data, textStatus, jqXHR) {
			StatusMessageHandler.removeLastShownMessage("info");
			
			if (data && $.isPlainObject(data)) { //update record with new data
				var manage_record = $(elm).parent().closest(".manage_record");
				var file_inputs = manage_record.find("input[type=file]");
				
				$.each(file_inputs, function(idx, input) {
					var value = input.name && data.hasOwnProperty(input.name) ? data[input.name] : "";
					$(input).parent().children(".file_content").html(value);
				});
			}
			else 
				StatusMessageHandler.showError("Error: Could not get record data. Please refresh this page.");
		},
		error : function(jqXHR, textStatus, errorThrown) { 
			StatusMessageHandler.removeLastShownMessage("info");
			
			if (jqXHR.responseText)
				StatusMessageHandler.showError(jqXHR.responseText);
		},
	});
}

function fileInputsContainsNewFiles(file_inputs) {
	var exists = false;
	
	$.each(file_inputs, function(idx, input) {
		if (input.files && input.files.length > 0) {
			exists = true;
			return false;
		}
	});
	
	return exists;
}

function getFormDataObjectWithUploadedFiles(data, file_inputs) {
	var formData = convertValueToFormData(data);
	
	$.each(file_inputs, function(idx, input) {
		if (input.files)
			for (var i = 0; i < input.files.length; i++)
				formData.append(input.name, input.files[i]);
	});
	
	return formData
}

function convertValueToFormData(val, formData, namespace) {
	if (!formData)
		formData = new FormData();
	
	var is_plain_object = $.isPlainObject(val) && !(val instanceof File);
	
	if (namespace || is_plain_object) {
		if (val instanceof Date)
			formData.append(namespace, val.toISOString());
		else if (val instanceof Array) {
			for (var i = 0; i < val.length; i++)
				convertValueToFormData(val[i], formData, namespace + '[' + i + ']');
		}
		else if (is_plain_object) {
			for (var property_name in val)
				if (val.hasOwnProperty(property_name))
					convertValueToFormData(val[property_name], formData, namespace ? namespace + '[' + property_name + ']' : property_name);
		}
		else if (val instanceof File) {
			if (val.name)
				formData.append(namespace, val, val.name);
			else
				formData.append(namespace, val);
		}
		else if (typeof val !== 'undefined' || val == null)
			formData.append(namespace, val);
		else
			formData.append(namespace, val.toString());
	}
	
	return formData;
}

function getPKsObj(manage_record) {
	manage_record = $(manage_record);
	var inputs = manage_record.children("input[type=hidden]");
	var obj = {};
	
	$.each(inputs, function(idx, input) {
		var input = $(input);
		var field_name = input.attr("name");
		
		if (field_name) {
			var v = input.val();
			v = typeof v == "undefined" ? "" : v;
			
			obj[field_name] = v;
		}
	});
	
	return obj;
}

function getAttributesObj(manage_record) {
	manage_record = $(manage_record);
	
	var inputs = manage_record.children("table").find("input, select, textarea");
	var obj = {};
	
	$.each(inputs, function(idx, input) {
		var input = $(input);
		var field_name = input.attr("name");
		
		if (field_name && input[0].type != "file") //Do not include inputs with type=file
			obj[field_name] = getAttributeValue(input);
	});
	
	return obj;
}

function getAttributeValue(input) {
	var v = "";
	
	if (input[0]) {
		if (input.attr("type") == "checkbox" || input.attr("type") == "radio") {
			if (input.is(":checked"))
				v = input[0].hasAttribute("value") ? input.val() : 1;
			else
				v = 0; //This should be empty and not null otherwise it messes the values from the checkboxes/radiobuttons when the field name is an array with numeric keys.
		}
		else {
			v = input.val();
			
			if (input.is("select") && v == null) //v can be null if the select field does not have any options.
				v = "";
		}
		
		v = typeof v == "undefined" ? "" : v; //if a field doesn't have the value attribute then the v can be undefined
	}
	
	return v;
}

function prepareRecordFields(item) {
	//Only add date plugin if browser doesn't have a default date field or if Firefox (bc the Modernizr does not work properly in the new firefox browsers)
	var is_firefox = navigator.userAgent.toLowerCase().indexOf('firefox') != -1;
	var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') != -1;
	
	if (typeof Modernizr == "undefined" || !Modernizr || !Modernizr.inputtypes.date || is_firefox) { 
		if (typeof item.datetimepicker != "undefined") {
			item.find('input[type="datetime"]').each(function(idx, input) {
				input = $(input);
				
				input.datetimepicker({
					controlType: 'select',
					oneLine: true,
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					showSecond: true,
			   		dateFormat: input.attr('dateFormat') ? input.attr('dateFormat') : 'yy-mm-dd',
					timeFormat: input.attr('timeFormat') ? input.attr('timeFormat') : 'HH:mm:ss',
				});
			});
			
			item.find('input[type="date"]').each(function(idx, input) {
				input = $(input);
				
				input.datepicker({
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					dateFormat: item.attr('dateFormat') ? item.attr('dateFormat') : 'yy-mm-dd',
				});
			});
			
			item.find('input[type="time"]').each(function(idx, input) {
				input = $(input);
				
				input.datetimepicker({
					showSecond: true,
			   		dateFormat: '',
					timeFormat: item.attr('timeFormat') ? item.attr('timeFormat') : 'HH:mm:ss'
				});
			});
		}
	}
	else if (typeof Modernizr != "undefined" && Modernizr && Modernizr.inputtypes.date && is_chrome) { 
		if (typeof item.datetimepicker != "undefined") {
			item.find('input[type="datetime"]').each(function(idx, input) {
				input = $(input);

				input.datetimepicker({
					controlType: 'select',
					oneLine: true,
					minDate: new Date(1970, 01, 01), //add min date to 1970-01-01, otherwise mysql will fail
					showSecond: true,
					dateFormat: input.attr('dateFormat') ? input.attr('dateFormat') : 'yy-mm-dd',
					timeFormat: input.attr('timeFormat') ? input.attr('timeFormat') : 'HH:mm:ss',
				});
			});
		}
	}
	else //Replace yyy-mm-dd hh:ii by yyy-mm-ddThh:ii if input is datetime-local
		item.find('input[type="datetime-local"]').each(function(idx, input) {
			input = $(input);
			var v = input.attr("value");
			 
			if (v && (/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/).test(v))
				input.val( v.replace(' ', 'T') );
		});
	
	//preparing editors
	createEditor( item.find('textarea') );
}

function createEditor(items) {
  if (typeof tinymce != "undefined")
    $.each(items, function(idx, textarea) {
        if (textarea) {
            textarea = $(textarea);
            var parent = textarea.parent();
            
            //prepare textarea id for tabs below
            if (!textarea[0].id)
            	textarea[0].id = "textarea_" + textarea[0].name + "_" + parseInt(Math.random() * 1000000);
            
            var textarea_id = textarea[0].id;
            var textarea_value = textarea.val();
            var h = parseInt(textarea.css("height"));
            var mh = parseInt(textarea.css("max-height"));
            mh = mh > 0 ? mh : $(window).height() - 200;
            h = h > 0 && h < mh ? h : mh;
            h = h > 200 ? h : 200;
        	
            var upload_url = textarea.attr("editor-upload-url");
                
           var menubar = textarea[0].hasAttribute("menubar") ? (textarea.attr("menubar") == "" || textarea.attr("menubar") == 0 || textarea.attr("menubar").toLowerCase() == "false" ? false : true) : true;
           var toolbar = textarea[0].hasAttribute("toolbar") ? textarea.attr("toolbar") : 'bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | formatselect fontselect fontsizeselect | forecolor backcolor | bullist numlist | outdent indent | link unlink image media | blockquote';
           
           var tinymce_opts = {
               //theme: 'modern',
               height: h,
               plugins: [
                 'advlist autolink link image lists charmap preview hr anchor pagebreak',
                 'searchreplace wordcount visualblocks visualchars code insertdatetime media nonbreaking',
                 'table contextmenu directionality emoticons paste textcolor textcolor colorpicker textpattern',
               ],
               toolbar: toolbar, 
               menubar: menubar,
               toolbar_items_size: 'small',
               image_title: false, // disable title field in the Image dialog
               image_description: false,// disable title field in the Image dialog
               convert_urls: false,// disable convert urls for images and other urls, this is, the url that the user inserts is the url that will be in the HTML.
               setup: function(editor) {
				editor.on('init', function(e) {
					//set tabs
			   		var tabs_id = "tabs_for_" + textarea_id;
					
					if (parent.children("#" + tabs_id).length == 0) {
					  var tinymce_elm = parent.children(".mce-tinymce");
					  var tinymce_id = tinymce_elm.attr("id");
					  
					  if (tinymce_id) {
					  	var cloned_textarea_id = "cloned_" + textarea_id;
						var cloned_textarea = $('<textarea class="cloned_textarea" id="' + cloned_textarea_id + '"></textarea>');
						textarea.after(cloned_textarea);
						
						cloned_textarea.val(textarea_value);
						cloned_textarea.attr("name", textarea.attr("name"));
						textarea.removeAttr("name");
						
						var ul = $('<ul class="tabs tabs_transparent tabs_right" id="' + tabs_id + '">'
							  + '<li><a href="#' + cloned_textarea_id + '">Code</a></li>'
							  + '<li><a href="#' + tinymce_id + '">Editor</a></li>'
						  + '</ul>');
						
						ul.find("li a[href='#" + tinymce_id + "']").click(function() {
							tinyMCE.get(textarea_id).setContent( cloned_textarea.val() );
							
							if (cloned_textarea.attr("name")) {
								textarea.attr("name", cloned_textarea.attr("name"));
								cloned_textarea.removeAttr("name");
							}
						});
						ul.find("li a[href='#" + cloned_textarea_id + "']").click(function() {
							cloned_textarea.val( tinyMCE.get(textarea_id).getContent() );
							
							if (textarea.attr("name")) {
								cloned_textarea.attr("name", textarea.attr("name"));
								textarea.removeAttr("name");
							}
						});
						tinymce_elm.before(ul);
						
						parent.tabs();
						parent.closest("tr").addClass("with_editor");
					  }
					}
				});
			}
           }
           
           if (upload_url) {
               tinymce_opts.paste_data_images = true;//enable direct paste of images and drag and drop.
               tinymce_opts.automatic_uploads = true;// enable automatic uploads of images represented by blob or data URIs
               tinymce_opts.file_picker_types = 'image';// here we add custom filepicker only to Image dialog
               
               tinymce_opts.file_picker_callback = function(cb, value, meta) {// and here's our custom image picker
                   var input = document.createElement('input');
                   input.setAttribute('type', 'file');
                   input.setAttribute('accept', 'image/*');
                   
                   // Note: In modern browsers input[type="file"] is functional without even adding it to the DOM, but that might not be the case in some older or quirky browsers like IE, so you might want to add it to the DOM just in case, and visually hide it. And do not forget do remove it once you do not need it anymore.
                   input.onchange = function() {
                       var file = this.files[0];
                       
                       // Note: Now we need to register the blob in TinyMCEs image blob registry. In the next release this part hopefully won't be necessary, as we are looking to handle it internally.
                       var id = 'userblobid' + (new Date()).getTime();//id will correspond to the uploaded file name.
                       if (file.name) {
                           id = file.name;
                           var pos = id.lastIndexOf(".");
                           id = pos != -1 ? id.substr(0, pos) : id;//remove extension
                       }
                       
                       var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                       var blobInfo = blobCache.create(id, file);
                       blobCache.add(blobInfo);
                       
                       // call the callback and populate the Title field with the file name
                       cb(blobInfo.blobUri(), { title: file.name });
                   };
                   
                   //input must be added to DOM, otherwise click event on IE and safari doesn't work.
                   input = $(input);
                   input.css("display", "none");
                   textarea.parent().append(input);
                   input.trigger('click');
               };
               
               tinymce_opts.images_upload_handler = function (blobInfo, success, failure) {// and here's our custom image  upload handler
                   if (("" + blobInfo.id()).indexOf('blobid') !== 0) {
                       // Show progress for the active editor
                       tinymce.activeEditor.setProgressState(true);

                       var xhr, formData;
                       
                       xhr = new XMLHttpRequest();
                       xhr.withCredentials = false;
                       xhr.open('POST', upload_url);
                       
                       xhr.onerror = function() {
                           failure("Image upload failed due to a XHR Transport error. Code: " + xhr.status);
                       };
                       
                       xhr.onload = function() {
                           var json;
                           
                           if (xhr.status != 200) {
                               failure('HTTP Error: ' + xhr.status);
                           
                               // Hide progress for the active editor
                               tinymce.activeEditor.setProgressState(false);
                               return;
                           }
                           
                           if (!xhr.responseText) {
                               failure('Invalid null response');
                           
                               // Hide progress for the active editor
                               tinymce.activeEditor.setProgressState(false);
                               return;
                           }
                           
                           try {
                               json = JSON.parse(xhr.responseText);
                           }
                           catch(e) {
                               json = null;
                           }
                           
                           if (!json || typeof json.url != 'string') {
                               failure('Invalid JSON: ' + xhr.responseText);
                           
                               // Hide progress for the active editor
                               tinymce.activeEditor.setProgressState(false);
                               return;
                           }
                           
                           success(json.url);
                           
                           // Hide progress for the active editor
                           tinymce.activeEditor.setProgressState(false);
                       };
                       
                       formData = new FormData();
                       formData.append('some_post_variable', 1);//bc the upload url must be a non-empty POST request
                       formData.append('image', blobInfo.blob(), blobInfo.filename());
                       
                       xhr.send(formData);
                   }
               };
           }
           
		textarea.tinymce(tinymce_opts);
   		
   		//set form on submit function. TinyMCE already does this, but only after our formChecker runs, which returns an error bc our formChecker detects that the textarea is empty.
		var f = parent.closest('form');
		var os = f.attr('onSubmit');
		
		if (!os || os.indexOf('tinyMCE.triggerSave(this)') == -1)
			f.attr('onSubmit', 'tinyMCE.triggerSave(this);' + (os ? os : ''));
       }
    });
}
