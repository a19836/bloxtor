$(function() {
	//init auto save
	/* The edit_relationship page already calls the edit_hbn_obj.js, whch already init the auto save.
	addAutoSaveMenu(".top_bar li.sub_menu li.save");
	enableAutoSave(onToggleAutoSave);
	initAutoSave(".top_bar li.sub_menu li.save a");*/
	
	//init ui
	$(".relationship").css("display", "block");
	$(".relationship .result_map_id .search").css("display", "none");
	
	$(".hbn_obj_relationships .advanced_query_settings").click();
	//$(".hbn_obj_relationships .advanced_query_settings").html("Show More Settings");
	
	//init main settings panel
	initMainSettingsPanel();
});

