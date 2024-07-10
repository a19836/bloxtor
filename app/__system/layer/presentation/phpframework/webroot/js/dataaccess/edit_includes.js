$(function() {
	//init auto save
	addAutoSaveMenu(".top_bar li.sub_menu li.save");
	enableAutoSave(onToggleAutoSave);
	initAutoSave(".top_bar li.sub_menu li.save a");
	
	//init ui
	$(".description").html("Add new Include:");
});

