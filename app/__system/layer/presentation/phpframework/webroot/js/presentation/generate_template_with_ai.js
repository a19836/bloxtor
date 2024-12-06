function AddRegion() {
	var html = '<li class="region"><input class="region_name" placeHolder="Region Name" /><input class="region_description" placeHolder="Region Instructions" /><span class="icon remove" onClick="RemoveRegion(this)">Remove</span></li>';
	var ul = $(".generate_template_with_ai .regions ul").append(html);
}

function RemoveRegion(elm) {
	$(elm).parent().closest("li.region").remove();
}

function saveTemplate() {
	var template_name = $(".top_bar .title input[name=template_name]").val();
	var instructions = $(".generate_template_with_ai .instructions textarea").val();
	
	if (!template_name) 
		alert("Template name cannot be blank.");
	else if (!instructions) 
		alert("Please write what template do you wish to create.");
	else {
		var layout_name = $(".top_bar .title input[name=layout_name]").val();
		var save_button = $(".top_bar ul li.save a");
		var on_click = save_button.attr("onClick");
		var save_icon = save_button.children(".icon");
		
		save_button.removeAttr("onClick");
		save_icon.addClass("loading");
		
		//get regions
		var items = $(".generate_template_with_ai .regions ul li.region");
		var regions = [];
		
		for (var i = 0; i < items.length; i++) {
			var item = $(items[i]);
			var region_name = item.find(".region_name").val();
			var region_description = item.find(".region_description").val();
			
			regions.push({
				name: region_name,
				description: region_description,
			});
		}
		
		//send post request to create template
		var data = {
			save: "save",
			template_name: template_name,
			layout_name: layout_name,
			instructions: instructions,
			regions: regions
		};
		
		$.ajax({
			type : "post",
			url : "" + document.location,
			data : data,
			dataType : "html",
			//contentType: 'application/json', //typically 'application/x-www-form-urlencoded', but the service you are calling may expect 'text/json'... check with the service to see what they expect as content-type in the HTTP header.
			success : function(status, textStatus, jqXHR) {
				if (status) {
					if (status == 1) {
						alert("Template Saved!");
						
						if (window.parent.refreshAndShowLastNodeChilds) {
							//Refreshing last node clicked in the entities folder.
							window.parent.refreshAndShowLastNodeChilds();
							
							//Refreshing templates and webroot folder
							var project = window.parent.$("#" + window.parent.last_selected_node_id).parent().closest("li[data-jstree=\'{\"icon\":\"project\"}\']");
							var templates_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"templates_folder\"}\']").attr("id");
							var webroot_folder_id = project.children("ul").children("li[data-jstree=\'{\"icon\":\"webroot_folder\"}\']").attr("id");
							window.parent.refreshAndShowNodeChildsByNodeId(templates_folder_id);
							window.parent.refreshAndShowNodeChildsByNodeId(webroot_folder_id);
						}
					}
					else
						alert(status ? status : "Error generating template. Please try again...");
				}
				else
					alert("Error generating template. Please try again...");
				
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
				
				var msg = "Error saving template. Please try again...";
				alert(msg);
				
				if (jqXHR.responseText)
					StatusMessageHandler.showError(msg + "\n" + jqXHR.responseText);
			},
		});
	}
}
