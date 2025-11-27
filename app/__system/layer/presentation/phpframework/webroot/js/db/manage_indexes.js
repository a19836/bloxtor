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

function refreshPage() {
	var url = document.location;
	document.location= url;
}

function deleteRow(elm) {
	if (confirm("Do you wish to delete this index?")) {
		elm = $(elm);
		var row = elm.parent().closest("tr");
		var constraint_name = row.find("td.constraint_name").text();
		
		if (constraint_name) {
			var constraint_type = row.find("td.constraint_type").text();
			
			$("#main_column").append('<form id="form_index" method="post" style="display:none"><input type="hidden" name="delete" value="1"/><input type="hidden" name="constraint_name" value="' + constraint_name + '"/><input type="hidden" name="constraint_type" value="' + constraint_type + '"/></form>');
			$("#form_index")[0].submit();
		}
		else 
			StatusMessageHandler.showError("Could not get constraint for this index!");
	}
}
