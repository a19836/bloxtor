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
