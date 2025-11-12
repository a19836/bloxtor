/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

function saveTable() {
	var table_name = $(".top_bar .title input[name=table_name]").val();
	var instructions = $(".generate_table_with_ai .instructions textarea").val();
	
	if (!table_name) 
		StatusMessageHandler.showError("Table name cannot be blank.");
	else if (!instructions) 
		StatusMessageHandler.showError("Please write what table do you wish to create.");
	else {
		var save_button = $(".top_bar ul li.save a");
		var on_click = save_button.attr("onClick");
		var save_icon = save_button.children(".icon");
		
		save_button.removeAttr("onClick");
		save_icon.addClass("loading");
		
		//send post request to create table
		var data = {
			save: "save",
			table_name: table_name,
			instructions: instructions
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
						StatusMessageHandler.showMessage("Table created!");
						
						if (typeof onSuccessTableGeneration == "function")
							onSuccessTableGeneration();
					}
					else
						StatusMessageHandler.showError(status ? status : "Error generating table. Please try again...");
				}
				else
					StatusMessageHandler.showError("Error generating table. Please try again...");
				
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
			},
			error: function (jqXHR, textStatus, errorThrown) {
				save_button.attr("onClick", on_click);
				save_icon.removeClass("loading");
				
				var msg = "Error saving table. Please try again..." + (jqXHR.responseText ? "\n" + jqXHR.responseText : "");
				StatusMessageHandler.showError(msg);
			},
		});
	}
}
