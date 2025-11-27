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

$(function() {
	//init auto save
	addAutoSaveMenu(".top_bar li.sub_menu li.save");
	enableAutoSave(onToggleAutoSave);
	initAutoSave(".top_bar li.sub_menu li.save a");
});

function createSingleMapParameterOrResultMapAutomatically(type) {
	$(".edit_map .data_access_obj > .relationships").children(".parameters_maps, .results_maps").children(".parameters, .results").children(".map").children(".update_automatically").trigger("click");
}
