/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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

