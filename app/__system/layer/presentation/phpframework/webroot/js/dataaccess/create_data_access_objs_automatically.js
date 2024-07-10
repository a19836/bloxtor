function submitForm(elm, on_submit_func) {
	elm = $(elm);
	var oForm = elm.parent().closest(".top_bar").parent().children("form");
	var status = typeof on_submit_func == "function" ? on_submit_func( oForm[0] ) : true;
	
	if (status) {
		var on_click = elm.attr("onClick");
		elm.addClass("loading").removeAttr("onClick");
		
		oForm.submit();
		
		/*setTimeout(function() {
			elm.removeClass("loading").attr("onClick", on_click);
		}, 2000);*/
	}
	
	return status;
}

function checkSelectedTables(oForm) {
	if ($(oForm).find(".tables .table input[type=checkbox]:checked").length == 0) {
		alert("You must select at least 1 table.");
		return false;
	}
	return true;
}

function updateDBDrivers(elm) {
	var db_broker = $(elm).val();
	var options = "";
	
	if (db_broker && db_drivers[db_broker]) {
		options = "<option></option>";
		
		for (var db_driver_name in db_drivers[db_broker]) {
			var db_driver_props = db_drivers[db_broker][db_driver_name];
			
			options += '<option value="' + db_driver_name + '">' + db_driver_name + (db_driver_props && db_driver_props.length > 0 ? '' : ' (Rest)') + '</option>'; 
		}
	}
	
	var db_driver_elm = $(elm).parent().parent().find(".db_driver");
	db_driver_elm.find("select").html(options);
	db_driver_elm.show();
}

function addTableAlias(elm) {
	elm = $(elm);
	var clicked = elm.attr("clicked");
	elm.attr("clicked", "1");
	
	var p = elm.parent();
	var table_name = p.children("input[type='checkbox']").val();
	var table_alias = p.children("input[type='hidden']").val();
	
	var alias = prompt("Please enter the new table alias:", clicked && table_alias ? table_alias : table_name);
	
	if (typeof alias == "string") {
		alias = alias.replace(/ /g, "");
		
		if (alias == table_name)
			alias = "";

		p.children("input[type='hidden']").val(alias);

		if (alias)
			elm.html(table_name + " => " + alias);
		else
			elm.html(table_name);
	}
}
