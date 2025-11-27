<?php
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

class VideoTutorialHandler {
	
	public static function getFeaturedTutorialsSectionHtml($tutorials, $online_tutorials_url_prefix) {
		$videos_main_content = self::getFeaturedTutorialsHtml($tutorials);
		
		if ($videos_main_content) {
			$html = '<div class="featured_header">
							<div class="featured_header_tip">Start here</div>
							<div class="featured_header_title">Build your app with confidence</div>
							<div class="featured_header_sub_title">Unlock your potential with these essential tools and guides for beginners.</div>
						</div>
						' . $videos_main_content . '
						<div class="featured_buttons">
							<button onClick="openWindow(this, \'url\', \'videos\')" url="' . $online_tutorials_url_prefix . 'video/simple"><span class="icon video"></span> Click here to watch more videos</button>
							<button onClick="openWindow(this, \'url\', \'documentation\')" url="' . $online_tutorials_url_prefix . '"><span class="icon tutorials"></span> Click here to read our documentation</button>
						</div>';
			
			$script = '<script>
			var videos_html = \'' . addcslashes(str_replace(array("\n", "\r"), "", $html), "\\'") . '\';
			
			$(function() {
				setTimeout(function() { //very important, so the pages that call this function, load faster and do NOT need to wait until all videos be loaded.
					$(".featured_tutorials").html(videos_html);
				}, 1000);
			});
			</script>';
			
			return '<div class="featured_tutorials"></div>' . $script;
			//return '<div class="featured_tutorials">' . $html . '</div>'; //Deprecated bc it makes framework slow by loading videos
		}
		
		return "";
	}
	
	public static function getFeaturedTutorialsHtml($tutorials) {
		$html = "";
		
		if ($tutorials)
			foreach ($tutorials as $tutorial) {
				if (!empty($tutorial["items"]))
					$html .= self::getFeaturedTutorialsHtml($tutorial["items"]);
				else if (!empty($tutorial["video"])) {
					$parts = explode("/embed/", $tutorial["video"]);
					$video_id = isset($parts[1]) ? $parts[1] : null;
					
					if ($video_id) {
						$tutorial_title = isset($tutorial["title"]) ? $tutorial["title"] : null;
						$tutorial_description = isset($tutorial["description"]) ? $tutorial["description"] : null;
						
						$html .= '<div class="featured_tutorial">
										<iframe src="https://www.youtube.com/embed/' . $video_id . '" title="' . str_replace('"', "&quot;", $tutorial_title) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
										<div class="tutorial_title">
											<span class="icon video"></span>
											' . $tutorial_title . '
										</div>
										' . ($tutorial_description ? '<div class="tutorial_description">' . $tutorial_description . '</div>' : '') . '
								</div>';
					}
				}
			}
		
		return $html;
	}
	
	public static function filterTutorials($tutorials, $page, $workspace = null) {
		if (!$page && !$workspace)
			return $tutorials;
		
		$found = array();
		
		if ($tutorials) {
			foreach ($tutorials as $id => $props) 
				if ($props) {
					$page_exists = !$page || (!empty($props["pages"]) && in_array($page, $props["pages"])); //only get tutorial if page is empty or if is inside of pages (meaning that only the tutorials with pages will be returned)
					$workspace_exists = !$workspace || empty($props["workspaces"]) || in_array($workspace, $props["workspaces"]); //only get tutorial if workspace is empty or if tutorial doesn't have any workspaces defined or if inside of workspace (meaning that the tutorials without workspaces defined, will be also returned)
					
					if ($page_exists && $workspace_exists) {
						$skip = ($page && $props["pages"]) || ($workspace && $props["workspaces"]);
						
						if (!$skip && !empty($props["items"]))
							$props["items"] = self::filterTutorials($props["items"], $page, $workspace);
						
						$found[$id] = $props;
					}
					else if (!empty($props["items"]) && $page) {
						$sub_found = self::filterTutorials($props["items"], $page, $workspace);
						
						if ($sub_found)
							$found = array_merge($found, $sub_found);
					}
				}
		}
		
		return $found;
	}
	
	public static function getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix) {
		return array(
			"1_framework_overview" => array(
				"title" => "Framework overview",
				"description" => "Learn the basics of the framework and how to create your first project and page.",
				"image" => "",
				"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
				"video" => "https://www.youtube.com/embed/lkMItcii2fQ", //framework_overview.webm
			),
			"2_page_editor_overview" => array(
				"title" => "Page Editor overview",
				"description" => "Learn how to work with the page editor.",
				"image" => "",
				"pages" => array("presentation/edit_entity", "admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
				"items" => array(
					"2_1_page_editor_overview" => array(
						"title" => "Page Editor overview",
						"description" => "Understand the components of the Page Editor.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/pejyKGOVjcM", //page_editor_overview.webm
					),
					"2_2_page_editor_manipulate_widgets" => array(
						"title" => "How to manipulate widgets in the Page Editor",
						"description" => "Learn how to work with widgets in the page editor, changing their properties, manipulating their dimensions, moving to different parents and positions and other actions...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/XMwbpe9HtMw", //page_editor_manipulate_widgets.webm
					),
					"2_3_page_editor_ai_widget" => array(
						"title" => "How to generate a layout component through AI",
						"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
					),
					"2_4_page_generation_with_ai" => array(
						"title" => "How to create a page through AI",
						"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
					),
					"2_5_image_to_page_conversion_with_ai" => array(
						"title" => "How to convert an image with a layout to an editable page through AI",
						"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
					),
				)
			),
			"3_simple_workspace" => array(
				"title" => "Simple Workspace",
				"description" => "Learn how to work in the simple workspace.",
				"pages" => array("presentation/edit_entity"), //this is used in the TourGuideUIHandler
				"workspaces" => array("simple"), //this is used in the TourGuideUIHandler
				"items" => array(
					"3_1_create_project_with_static_page" => array(
						"title" => "Create a static page",
						"description" => "Learn how to create a static page by drag&drop html widgets.",
						"image" => "",
						"items" => array(
							"3_1_1_create_project_with_static_page_from_scratch" => array(
								"title" => "Create a static page from scratch",
								"description" => "Learn how to create a static page from scratch (with a blank canvas), by drag&drop html widgets.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/qFUwt_OCVHE", //create_project_with_static_page_from_scratch.webm
								"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
								"workspaces" => array("simple"), //this is used in the TourGuideUIHandler
							),
							"3_1_2_create_project_with_static_page_from_template" => array(
								"title" => "Create a static page using a theme template",
								"description" => "Learn how to create a static page using a theme template, by drag&drop html widgets.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/CrEznnR2lkY", //create_project_with_static_page_from_template.webm
								"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
								"workspaces" => array("simple"), //this is used in the TourGuideUIHandler
							),
							"3_1_3_page_editor_ai_widget" => array(
								"title" => "How to generate a layout component through AI",
								"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
							),
							"3_1_4_page_generation_with_ai" => array(
								"title" => "How to create a page through AI",
								"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
							),
							"3_1_5_image_to_page_conversion_with_ai" => array(
								"title" => "How to convert an image with a layout to an editable page through AI",
								"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
		Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
							),
						)
					),
					"3_2_page_with_diferent_attributes_shown" => array(
						"title" => "Create a page that shows information coming from the database",
						"description" => "Learn how to create a static page with some dynamic data coming from the database.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/v1shjjwHrUc", //page_with_diferent_attributes_shown.webm
					),
					"3_3_convert static page to dynamic page" => array(
						"title" => "Create a page with dynamic data",
						"description" => "Learn how to create a page by converting static data into dynamic data and saving it to a database.",
						"image" => "",
						"items" => array(
							"3_3_1_convert static page to dynamic page_a" => array(
								"title" => "Create a page with dynamic data - part 1",
								"description" => "Learn how to create a table in the database and show its records.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/_PPIUK6fS_o", //convert static page to dynamic page_a.webm
							),
							"3_3_2_convert static page to dynamic page_b" => array(
								"title" => "Create a page with dynamic data - part 2",
								"description" => "Learn how to create a table in the database and show its records.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/BSQG9emlcJ8", //convert static page to dynamic page_b.webm
							),
						)
					),
					"3_4_form to add record" => array(
						"title" => "Create a page with a form to add records into a database table",
						"description" => "Learn how to create a page to allow users to add records to your database tables.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/KHFY7ieO1jM", //form to add record.webm
					),
					"3_5_editable list" => array(
						"title" => "Create a page with an editable list to manage the records from a database table",
						"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/edCA35FI8Co", //editable list.webm
					),
					"3_6_edit a record" => array(
						"title" => "Create a page with a form to edit records from a database table",
						"description" => "Learn how to create a page to allow users to edit records from your database tables.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/fDiVJ3E-_bE", //edit a record.webm
					),
					"3_7_editable list with 2 tables related" => array(
						"title" => "Create a page with a list with data of 2 database tables",
						"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Gp1FKy5L8Ds", //editable list with 2 tables related.webm
					),
				)
			),
			"4_advanced_workspace" => array(
				"title" => "Advanced Workspace",
				"description" => "Learn how to work in the advanced workspace.",
				"pages" => array("presentation/edit_entity"), //this is used in the TourGuideUIHandler
				"workspaces" => array("advanced"), //this is used in the TourGuideUIHandler
				"items" => array(
					"4_1_create_project_with_static_page" => array(
						"title" => "Create a static page",
						"description" => "Learn how to create a static page by drag&drop html widgets.",
						"image" => "",
						"items" => array(
							"4_1_1_create_project_with_static_page_from_scratch" => array(
								"title" => "Create a static page from scratch",
								"description" => "Learn how to create a static page from scratch (with a blank canvas), by drag&drop html widgets.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/4I-wmvpP7rg", //create_project_with_static_page_from_scratch.webm
								"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
								"workspaces" => array("advanced"), //this is used in the TourGuideUIHandler
							),
							"4_1_2_create_project_with_static_page_from_template" => array(
								"title" => "Create a static page using a theme template",
								"description" => "Learn how to create a static page using a theme template, by drag&drop html widgets.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/gwDZMLa4kZk", //create_project_with_static_page_from_template.webm
								"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
								"workspaces" => array("advanced"), //this is used in the TourGuideUIHandler
							),
							"4_1_3_page_editor_ai_widget" => array(
								"title" => "How to generate a layout component through AI",
								"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
							),
							"4_1_4_page_generation_with_ai" => array(
								"title" => "How to create a page through AI",
								"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
							),
							"4_1_5_image_to_page_conversion_with_ai" => array(
								"title" => "How to convert an image with a layout to an editable page through AI",
								"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
		Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
							),
						)
					),
					"4_2_page_with_diferent_attributes_shown" => array(
						"title" => "Create a page that shows information coming from the database",
						"description" => "Learn how to create a static page with some dynamic data coming from the database.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/TOWnZdg0tKs", //page_with_diferent_attributes_shown.webm
					),
					"4_3_convert static page to dynamic page" => array(
						"title" => "Create a page with dynamic data",
						"description" => "Learn how to create a page by converting static data into dynamic data and saving it to a database.",
						"image" => "",
						"items" => array(
							"4_3_1_convert static page to dynamic page_a" => array(
								"title" => "Create a page with dynamic data - part 1",
								"description" => "Learn how to create a table in the database and show its records.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/0AT_iacudaI", //convert static page to dynamic page_a.webm
							),
							"4_3_2_convert static page to dynamic page_b" => array(
								"title" => "Create a page with dynamic data - part 2",
								"description" => "Learn how to create a table in the database and show its records.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/T2JuI8CEeok", //convert static page to dynamic page_b.webm
							),
						)
					),
					"4_4_form to add record" => array(
						"title" => "Create a page with a form to add records into a database table",
						"description" => "Learn how to create a page to allow users to add records to your database tables.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/iLGHHfFay9w", //form to add records.webm
					),
					"4_5_editable list" => array(
						"title" => "Create a page with an editable list to manage the records from a database table",
						"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
						"image" => "",
						"items" => array(
							"4_5_1_editable list" => array(
								"title" => "Create a page with an editable list to manage the records from a database table",
								"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/GGEiIftpe1A", //editable list.webm
							),
							"4_5_2_create_page_with_a_list" => array(
								"title" => "Create a page with a list of DB records",
								"description" => "This video explains how to create a Page that prints a list of database records through Html Widgets and corresponding Resources.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/DBgnRedIZIc", //6_3_2_create_page_with_html_widgets
							),
							"4_5_3_create_page_with_a_list_in_3_ways" => array(
								"title" => "Create a page with a list of DB records in 3 different ways",
								"description" => "This video explains, in 3 different ways, how to create a Page using Html Widgets to print a list of records from a database table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/dTziiRZmCkk", //6_3_5_create_page_with_html_widgets
							),
						)
					),
					"4_6_edit a record" => array(
						"title" => "Create a page with a form to edit records from a database table",
						"description" => "Learn how to create a page to allow users to edit records from your database tables.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/z1nwCz1OvWo", //edit a record.webm
					),
					"4_7_editable list with 2 tables related" => array(
						"title" => "Create a page with a list with data of 2 database tables",
						"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other.",
						"image" => "",
						"items" => array(
							"4_7_1_editable list with 2 tables related_diagram" => array(
								"title" => "Create a page with a list of records from 2 database tables connected in the DB Diagram",
								"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other in the DB Diagram.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/TYojwIyEpY8", //editable list with 2 tables related_diagram.webm
							),
							"4_7_2_editable list with 2 tables related_server" => array(
								"title" => "Create a page with a list of records from 2 database tables connected directly in the DB Server",
								"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other in the DB Server.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/aok64tRgoNs", //editable list with 2 tables related_server.webm
							),
							"4_7_3_create_page_with_html_widgets_with_fks" => array(
								"title" => "Create a page a list of DB records with Foreign Keys",
								"description" => "This video explains how to create a Page that prints a list of database records through Html Widgets and corresponding Resources. The record list will contain a foreign key to another table in the database and allow you to edit each record directly.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/2ZbsuMop4n8", //6_3_3_create_page_with_html_widgets
							),
						)
					),
				)
			),
			"5_multiple comboboxes connected" => array(
				"title" => "Create a page with dynamic comboboxes/dropdowns",
				"description" => "Learn how to create a page with comboboxes/dropdowns that load data from the database.",
				"image" => "",
				"items" => array(
					"5_1_multiple comboboxes connected" => array(
						"title" => "Create a page with multiple combobox/dropdowns connected to each other",
						"description" => "Learn how to create a page with 3 comboboxes/dropdowns dependent on each other, where changing one changes the others.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/QJQhjODtqdc", //multiple comboboxes connected.webm
					),
					"5_2_create_page_with_a_combobox" => array(
						"title" => "Create a combobox with the dynamic values of a DB table and according to the user's selection, show the details of that selected record below",
						"description" => "This video explains how to create a Page using Html Widgets to create a combobox with the values of a DB table, which, according to the user's selection, shows the details of that selected record in an html form below. All this will be done via drag and drop based on No-Code.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/89-wxTexCY8", //6_3_11_1_create_page_with_html_widgets
					),
					"5_3_create_page_with_a_combobox_with_dependencies" => array(
						"title" => "Create a combobox with the dynamic values of a DB table and according to an URL value, select that record in the combobox and show its details below",
						"description" => "This video explains how to create a Page using Html Widgets to create a combobox with values from a DB table, which, according to a variable value in the URL, selects that default record in the combobox and also shows its details in an html form below. All this will be done via drag and drop based on No-Code.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/fH0RjNIH2D0", //6_3_11_2_create_page_with_html_widgets
					),
				)
			),
			"6_create_page_with_available_values" => array(
				"title" => "Create a list with Available Values",
				"description" => "This video explains how to change the values of a list of records where numeric columns are replaced with user-firendly values, dynamically obtained or manually created.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/nsofMBHKGAs", //available_values.webm
			),
			"7_create_page_with_charts" => array(
				"title" => "Create a page with Charts",
				"description" => "This video explains how to create a page with Charts, based in values dynamically obtained or created manually.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Oc6PyxiWi88", //charts.webm
			),
			"8_create_excel_reports" => array(
				"title" => "Create Excel Reports",
				"description" => "This video explains how to create Excel reports that contains records coming from database tables or third-parthy services.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/WgTCaDU9pCc", //report.webm
			),
			"8_create_pdf_reports" => array(
				"title" => "Create PDF Reports",
				"description" => "This video explains how to create PDF reports that contains records coming from database tables or third-parthy services.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/k_y1SB72upQ", //stock_manager_11.webm
			),
			"9_understand_the_page_editor_in_detail" => array(
				"title" => "Understand the Page Editor in detail",
				"description" => "This video explains how to work in the Page Editor in detail and what are its key features...",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Ht3-jU-A5HM", //2_5_page_visual_editor
			),
			"9_create_page_with_html_widgets_in_detail" => array(
				"title" => "Create a page with HTML Widgets in detail",
				"description" => "This video explains how to create a Page using Html Widgets to design your page layout and print some resources...",
				"image" => "",
				"video" => "https://www.youtube.com/embed/6j-rtmgCXg0", //6_3_1_create_page_with_html_widgets
			),
			"10_creating_new_resources_through_page_editor" => array(
				"title" => "Create Resources/Actions in the page editor",
				"description" => "Learn how to create new resources/actions in the page editor.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/5Qp9ywQa6cU", //creating_new_resources_through_page_editor.webm
			),
			"11_creating_new_bl_services_through_page_editor" => array(
				"title" => "Create Business Logic services through the page editor",
				"description" => "Learn how to create new Business Logic services through the page editor.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/lblrkSoJNXI", //creating_new_bl_services_through_page_editor.webm
			),
			"12_create_3_pages_connected_to_each_other" => array(
				"title" => "Create 3 Pages that communicate with each other",
				"description" => "This video explains how to create 3 pages that communicate with each other using Html Widgets to design the page layout and print some resources... Basically, the first page will contain a list of records from a parent database table, the second page a list of records from a child database table, and the third page a form to view the details of a record from the parent table.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/7kXqNXQmcvo", //6_3_4_create_page_with_html_widgets
			),
			"13_create_page_with_popup_to_another_page" => array(
				"title" => "Call another page though a popup",
				"description" => "This video explains how to create 2 pages using Html Widgets, where the first page prints a list of records from a database table and when the user clicks on each record, a popup opens with the second page showing the details of that record, allowing the user to edit it.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/rmF8JgJMnxc", //6_3_6_create_page_with_html_widgets
			),
			"14_create_page_based_in_copy_paste_from_external_site" => array(
				"title" => "Copy paste HTML from external site",
				"description" => "This video explains how to copy some HTML from a thrid-party site and paste it into your page.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/rbMdrXEarps", //copy_paste.webm
			),
			"15_create_page_with_dynamic_attributes" => array(
				"title" => "Show specific attributes of a database table through a more manual process",
				"description" => "This video explains how to create a Page using Html Widgets to print database metadata, that is, how to print an attribute of a database table through Html Widgets.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/uTfE3PiYeeY", //6_3_7_create_page_with_html_widgets
			),
			"16_create_quiz_like_in_typeform" => array(
				"title" => "Create a quiz as you build it in the TypeForm tool",
				"description" => "This video explains how to create beautiful questionnaires, with different slide effects, and then save the user's answers in a database. Basically, it shows you how to create quizzes as you build them through the TypeForm tool.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/cj53ar5tOUM", //16_create_quiz_like_in_typeform
			),
			"17_create_page_with_calendar" => array(
				"title" => "Create a page with an events calendar",
				"description" => "This video explains how to create a Page with a calendar, where you can list and edit events from and to a database table.",
				"image" => "",
				"items" => array(
					"17_1_create_page_with_readonly_calendar_events" => array(
						"title" => "Create a page with an events calendar - part 1",
						"description" => "Learn how to create a page with a calendar that shows events pulled from a database table.",
						"image" => "", 
						"video" => "https://www.youtube.com/embed/qiClkzpkacE", //16_1_create_page_with_readonly_calendar_events
					),
					"17_2_create_page_with_editable_calendar_events" => array(
						"title" => "Create a page with an events calendar - part 2",
						"description" => "Learn how to create a page with a calendar that allows to add, edit and remove events from a database table.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/jfaP4jipJF0", //16_2_create_page_with_editable_calendar_events
					),
				)
			),
			"18_create_page_with_gantt_calendar" => array(
				"title" => "Create a page with a GANTT chart/calendar",
				"description" => "Learn how to create a page with a GANTT chart that allows to add, edit and remove tasks to your employees.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/fweaP_bYIfI", //17_create_page_with_gantt_calendar
			),
			"19_create_page_with_matrix_board" => array(
				"title" => "Create a page with a matrix board",
				"description" => "Learn how to create pages with matrix boards: to classify the skills for your employees, or manage their competences or assign tasks or projects, show results from multiple different sports grouped by categories and sub-categories.",
				"image" => "",
				"items" => array(
					"19_1_create_page_with_matrix_board_to_manage_employee_skills" => array(
						"title" => "Create a page with a matrix board to manage the competences of your employees",
						"description" => "Learn how to create a page with a matrix board that shows the list of your employees and the skills available, allowing you to correlate which skills each employee has.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/gjVDFi3GgPE", //18_1_create_page_with_matrix_board_to_manage_employee_skills
					),
					"19_2_create_page_with_matrix_board_to_classify_employee_skills" => array(
						"title" => "Create a page with a matrix board to classify the skills for your employees",
						"description" => "Learn how to create a page with a matrix board that shows the list of your employees and the skills available, allowing you to classify each employee's skill level.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/3bltGNebiNI", //18_2_create_page_with_matrix_board_to_classify_employee_skills
					),
					"19_3_create_page_with_matrix_board_to_assign_employee_projects" => array(
						"title" => "Create a page with a matrix board to assign projects to your employees",
						"description" => "Learn how to create a page with a matrix board that shows the list of your employees and available projects, allowing you to assign projects to your employees and edit project details.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/00USCMwZu7A", //18_3_create_page_with_matrix_board_to_assign_employee_projects
					),
					"19_4_create_page_with_matrix_board_to_group_employee_skills" => array(
						"title" => "Create a page with a matrix board to list employee skills grouped by category",
						"description" => "Learn how to create a matrix board that shows a list of your employees and their skills, where skills are grouped by category.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/HGIUZ_dvftI", //18_4_create_page_with_matrix_board_to_group_employee_skills
					),
					"19_5_create_page_with_matrix_board_to_group_games_by_category" => array(
						"title" => "Create a page with a matrix board to list sport games from multiple categories and sub-categories",
						"description" => "Learn how to create a matrix chart that shows a list of games from a sport, grouped by categories and subcategories. Something like soccer games that belong to a championship, division, group, gender, etc...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/sIcpOdSpOzg", //18_5_create_page_with_matrix_board_to_group_games_by_category
					),
				)
			),
			"20_create_page_with_scrum_board" => array(
				"title" => "Create a page with a SCRUM board to manage your tasks",
				"description" => "Learn how to create a page with a SCRUM board that allows to add, edit and remove tasks from your projects or employees.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/JqKWVWHSiqg", //19_create_page_with_scrum_board
			),
			"21_create_page_with_dynamic_attributes_through_manual_bl" => array(
				"title" => "Show specific attributes of a DB table through a manual business logic service",
				"description" => "This video explains how to create a Page using Html Widgets to print database metadata, that is, based on a business logic service that we create manually to get a record from a database table, print that record's attributes in the html.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/aVO-SYd1Xdc", //6_3_8_create_page_with_html_widgets
			),
			"22_create_page_with_add_form_through_manual_bl" => array(
				"title" => "Enter user input from a html form into a DB table through a manual business logic service",
				"description" => "This video explains how to create a Page using Html Widgets to insert some metadata in a DB table from user input entered in some form fields, based on a business logic service that we create manually to insert a record into a database table.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Snd35lEtMrM", //6_3_9_create_page_with_html_widgets
			),
			"23_create_page_with_edit_form_through_manual_bl" => array(
				"title" => "Show the DB table metadata in a html form and allow the user to edit and save that metadata in the DB through a manual business logic service",
				"description" => "This video explains how to create a Page using Html Widgets to display database metadata in an html form and allow the user to edit and save this metadata to the database. All this will be done through business logic services that we created manually to obtain and save a record from and to a database table, printing the attributes of that record in html.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/NklaG2f2Ceo", //6_3_10_create_page_with_html_widgets
			),
			"24_create_page_with_upload_form_locally" => array(
				"title" => "Create html form to upload files into a folder in your project",
				"description" => "This video explains how to create a page using Html Widgets to create an html form to upload local files to a folder in your project. We will use 3 different ways to do the same thing, including low-code file upload, creating a business logic service for this purpose.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/ZrHiTsQMrng", //6_3_12_1_create_page_with_html_widgets
			),
			"25_create_page_upload_form_dynamically" => array(
				"title" => "Create an html form to upload files to a DB table and then another page to list the uploaded files from that DB table",
				"description" => "This video explains how to create Pages using Html Widgets to create an html form to upload files to a database table and an html table to list the uploaded files from that database table. All of this will be done through business logic services that we create manually to insert records into a database table and also to get records from that table.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/HHW0V7_7xCA", //6_3_12_2_create_page_with_html_widgets
			),
			"26_create_page_with_complex_structures" => array(
				"title" => "Create page showing complex structures",
				"description" => "This video explains how to create pages showing a list with another internal list or objects inside.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/PK_akflyjqI", //complex_structures.webm
			),
			"27_create_page_with_recursive_complex_structures" => array(
				"title" => "Create page showing complex structures recursively",
				"description" => "This video explains how to create pages showing a list with multiple internal lists or objects that can contain another list or internal objects, at multiple levels.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/NJ_jlnZahR4", //recursive_complex_structure.webm
			),
			"28_create_page_to_show_dynamic_recursive_complex_structures" => array(
				"title" => "Create page showing redundant database structures recursively",
				"description" => "This video explains how to create pages showing records from a database table connected to itself, as in this example: a Category can have another Category or belong to another Category.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/nl7NhjbFxJQ", //show_dynamic_recursive_structure.webm
			),
			"29_create_page_to_edit_dynamic_recursive_complex_structures" => array(
				"title" => "Create page to edit redundant database structures recursively",
				"description" => "This video explains how to create pages to edit records from a database table connected to itself, as in this example: a Category can have another Category or belong to another Category.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/YA4e-sW52Zk", //edit_dynamic_recursive_structure.webm
			),
			"30_create_page_for_api" => array(
				"title" => "Create page to be used by external APIs or AJAX requests",
				"description" => "This video explains how to create pages to be used by external APIs, AJAX requests or via the command line. In this video we will create a page that will be used externally to insert records into a database table.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/v40WwynoNSY", //create_page_for_api.webm
			),
			"31_create_page_with_data_from_rest_api" => array(
				"title" => "Create a page that shows results from third-party REST services",
				"description" => "This video explains how to create Pages that communicate with third-party REST services, parse their response in json/xml, displaying that data in html. All of this will be done through page resources and business logic services that we will create manually.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/ZQaEJ2xvWGM", //6_3_13_create_page_with_html_widgets
			),
			"32_creation_of_simple_site_with_modules_integration" => array(
				"title" => "Create Pages that print DB metadata using pre-installed Modules",
				"description" => "This video explains how to create a simple site in the framework, with a homepage, a list of articles and another page to see each article individually.<br/>
		Learn how to create simple sites by creating basic templates and configure pre-installed modules.<br/>
		The purpose of these videos is to teach you how templates and regions, modules and blocks work and how can be integrated into pages.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/ir8WAnVi5Cg",
			),
			"33_install_a_program" => array(
				"title" => "How to install a Program in a project",
				"description" => "This video explains how to install a program from our store in a project, that is, our store has some predefined applications, pages and other files that you can install in your project, and then change them according to your needs.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/rr0cvDV9Gv4",
			),
			"34_create_pages_with_authentication" => array(
				"title" => "Create a Page with authentication",
				"description" => "This video explains how to create a Page with authentication where the user needs to authenticate first to access it. To achieve this result, we will install the 'Auth' program from our store.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/dgBBBBeZ1ZQ",
			),
			"35_manage_user_in_project" => array(
				"title" => "How to manage your application's users within a Project",
				"description" => "This video explains how to manage users within a project, setting permissions for private pages that need authentication to access.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/XSLKry20aXw",
			),
			"36_understand_the_layers" => array(
				"title" => "Understand Layers in more detail",
				"description" => "The following videos explain in detail how layers work and what their characteristics are.",
				"pages" => array("admin/index"), //this is used in the TourGuideUIHandler
				"workspaces" => array("advanced"), //this is used in the TourGuideUIHandler
				"items" => array(
					"36_1_db_layer" => array(
						"title" => "DB Layer",
						"description" => "Learn how to manage your DBs, create tables diagrams...<br/>To know more about this please read the <a href=\"{$online_tutorials_url_prefix}documentation/layers/database\" target=\"documentation\">Data Base Layer</a> section.",
						"items" => array(
							"36_1_1_advanced_workspace_db_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
								"title" => "Understand the DB Layer Tab",
								"description" => "This video explains how to work with the DB Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/X4y7CZwQ700",
							),
							"36_1_2_creation_of_db_diagram" => array(
								"title" => "Create DB Tables Diagram and Reverse Engineering",
								"description" => "This video explains how to create a DataBase diagram, generate the correspondent SQL and insert it to a DB.<br/>
			Additionally shows how to reverse engineering a schema from a DataBase into a tables diagram.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/__FeuikKvUI",
							),
							"36_1_3_table_generation_through_ai" => array(
								"title" => "How to generate a table through AI",
								"description" => "Learn how to generate a new table with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/iHuYUFCcaJ0", //db_table.webm
							),
							"36_1_4_sql_generation_through_ai" => array(
								"title" => "How to generate SQL statements through AI",
								"description" => "Learn how to generate SQL statements with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Ro-AWKAjMr4", //db_sql_statements.webm
							),
						),
					),
					"36_2_business_logic_layer" => array(
						"title" => "Business Logic Layer",
						"description" => "Learn how to create Services in the Business Logic Layer.",
						"pages" => array("businesslogic/edit_method", "businesslogic/edit_function", "admin/edit_file_class_method", "admin/edit_file_function"), //this is used in the TourGuideUIHandler
						"items" => array(
							"36_2_1_advanced_workspace_bll_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
								"title" => "Understand the Business-Logic Layer Tab",
								"description" => "This video explains how to work with the Business-Logic Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Si_aJW46bU8",
							),
							"36_2_2_creation_of_soa" => array(
								"title" => "Create a Business Logic Service",
								"description" => "This video explains how to create a Service in the Business Logic Layer and relate it to the data-access layer.<br/>
			Additionally it shows how to create these services automatically from SQL queries and objects in the Data-Access Layer. Basically the framework will generate automatically the services from the rules of a Data-Access Layer, saving time to the programmer and maximizing his work...<br/>
			Note that if the Business-Logic Layer is connected directly to the Data-Base Layer, it can execute sql directly too, but this explanation is not in this video.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/pEPOW0HfueE",
							),
							"36_2_3_creation_of_manual_soa" => array(
								"title" => "Create a Business Logic Service Manually",
								"description" => "This video explains how to create manually, step by step, a Service in the Business Logic Layer that gets a record from a DB table.<br/>
			Note that if the Business-Logic Layer is connected directly to the DB Layer, which means it can execute sql directly.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Iq8hOqzQCw0",
							),
							"36_2_4_code_generation_through_ai" => array(
								"title" => "How to generate code through AI",
								"description" => "Learn how to generate code with artificial intelligence by writing what you wish in natural language. You can ask AI to explain a specific piece of code, or create test units for it, or generate a new function and logic code according with your requirements, or something else...<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/5OZk4noPa_M", //php_generation.webm
							),
						),
					),
					"36_3_advanced_workspace_pl_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
						"title" => "Understand the Presentation Layer Tab",
						"description" => "This video explains how to work with the Presentation Layer in the Advanced Workspace and what are its funcionalities.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/cqR2SXYJ_EM",
					),
				)
			),
			"37_ai_summary" => array(
				"title" => "How to use AI to help you achieve your goals faster",
				"description" => "Discover how to leverage artificial intelligence to achieve your goals: simply describe what you need in natural language, and AI will generate it for you.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/ImZg10DEWWg", //ai_summary.webm
				"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
			),
			"38_create_stock_manager" => array(
				"title" => "Create a complete Stock Manager from scratch",
				"description" => "Discover how to build a complete stock management software from scratch - creating database tables, installing templates, and designing the UI.<br/>
				<br/>
This tutorial is composed by the following lessons:
<ul>
	<li>- <a href='https://youtu.be/-iywg0oqrAE?si=MIfBIFwHXr8NWRPC' target='youtube'>Lesson 1: Overviewing the data-model in a spreadsheet</a></li>
	<li>- <a href='https://youtu.be/K8Eqi1DZJpo?si=2zupcCxX9r8nU33y' target='youtube'>Lesson 2: Creating the DB structure</a></li>
	<li>- <a href='https://youtu.be/rOWjshrHFY8?si=tPvpXJo16_ICLKvU' target='youtube'>Lesson 3: Creating user interface automatically for DB structure</a></li>
	<li>- <a href='https://youtu.be/2wyUfzY0ulA?si=ODHKnMraB4f883NR' target='youtube'>Lesson 4: Creating page to manage a purchase and its products</a></li>
	<li>- <a href='https://youtu.be/RXC3lzp3i0k?si=WbT5PrUool422Xvp' target='youtube'>Lesson 5: Changing the purchase editing page with a single form</a></li>
	<li>- <a href='https://youtu.be/WJ5aTZnNN2I?si=zThgBOfu5XxfzdyS' target='youtube'>Lesson 6: Creating a page to manage the products of a sale</a></li>
	<li>- <a href='https://youtu.be/ffsjM1BT3PY?si=2BEGipkeQsYbh3ci' target='youtube'>Lesson 7: Listing product batches and auto-update some fields</a></li>
	<li>- <a href='https://youtu.be/ezV_YF-yMzk?si=wk4MiAuk1xTRgEtx' target='youtube'>Lesson 8: Auto-updating some product fields</a></li>
	<li>- <a href='https://youtu.be/iAcmbX1P8Qw?si=QFbLfSpGuEklH4Oe' target='youtube'>Lesson 9: Creating Notifications</a></li>
	<li>- <a href='https://youtu.be/IpvHfP2__rk?si=SEyoSSJMkxKJohqF' target='youtube'>Lesson 10: Creating reports in spreadsheet format</a></li>
	<li>- <a href='https://youtu.be/k_y1SB72upQ?si=5bMt4kZhztgPV7n7' target='youtube'>Lesson 11: Creating reports in PDF format</a></li>
	<li>- <a href='https://youtu.be/mmsN860z46I?si=0725rg_PiZK8Gwrh' target='youtube'>Lesson 12: Displaying SQL query and download it into a report</a></li>
	<li>- <a href='https://youtu.be/-erI6VM_uqI?si=j5PJB2sjC63QlGSO' target='youtube'>Lesson 13: Adding active/inactive status to products</a></li>
	<li>- <a href='https://youtu.be/4A2vB09P1RM?si=avy_Hl10reA8wg1M' target='youtube'>Lesson 14: Adding authentication to your pages</a></li>
	<li>- <a href='https://youtu.be/FU50lRz8JaQ?si=kZSSp3jgJGVflErS' target='youtube'>Lesson 15: Changing user module to list users with a search bar</a></li>
	<li>- <a href='https://youtu.be/nW2FIdsGRqY?si=yZT3eJ4PILpT-2Qe' target='youtube'>Lesson 16: Showing, hiding and removing elements by conditions</a></li>
	<li>- <a href='https://youtu.be/fBo8I2EDbVw?si=BVXJ74h-7gbkxrJq' target='youtube'>Lesson 17: Allowing multi-level categories within categories</a></li>
</ul>",
				"image" => "",
				"video" => "https://www.youtube.com/embed/videoseries?si=_vOXV94CaJX_Q3qN&amp;list=PLqnlfrS34juvmf4eueUMN1Av5QpWCK2wO", //create_stock_manager.webm
				"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
			),
		);
	}

	public static function getAdvancedTutorials($project_url_prefix, $online_tutorials_url_prefix) {
		return array(
			/* Do not show the setup bc if the user is already in the framework, it means that the setup was already executed. The setup should only appear in the bloxtor.com documentation/videos pages.
			"1_setup" => array(
				"title" => "Setup after install the raw version from GitHub",
				"description" => "In case you downloaded the raw version of the framework you should then execute the following the steps described in these videos. Otherwise ignore these videos because your version already has these steps executed.",
				"items" => array(
					"1_1_simple_setup" => array(
						"title" => "Simple Setup",
						"description" => "This video explains how you can execute a step by step installation of the framework, but you only need to execute the following steps if you downloaded the raw version of the framework.<br/>
	Note: We configured the 'localhost:81' virtual host to point to the root folder of the framework, but this is optional...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/ePEKhVf4qUo",
					),
					"1_2_advanced_setup" => array(
						"title" => "Advanced Setup",
						"description" => "This video explains how you can execute a step by step installation of the framework in companies with multiple IT departments and multiple sites, but you only need to execute the following steps if you downloaded the raw version of the framework, otherwise you can change the layers according with your needs in the 'Layers Diagram' of the 'Advanced Admin Panel'.<br/>
	Note: We configured the 'localhost:81' virtual host to point to the root folder of framework, but this is optional...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/rjFNByM0bJI",
					),
				),
			),*/
			"2_get_familiar_with_the_framework" => array(
				"title" => "Get familiar with the framework",
				"description" => "Learn how to work in the Advanced Workspace.",
				"items" => array(
					"2_1_advanced_workspace" => array(
						"title" => "Understand the Advanced Workspace",
						"description" => "Learn how to work in the Advanced Workspace.",
						"pages" => array("admin/index"), //this is used in the TourGuideUIHandler
						"workspaces" => array("advanced"), //this is used in the TourGuideUIHandler
						"items" => array(
							"2_1_0_framework_overview" => array(
								"title" => "Framework overview",
								"description" => "Learn the basics of the framework and how to create your first project and page.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/lkMItcii2fQ", //framework_overview.webm
								"pages" => array("admin/admin_home", "admin/admin_home_project"), //this is used in the TourGuideUIHandler
							),
							"2_1_1_advanced_workspace" => array(
								"title" => "Advanced Workspace in more detail",
								"description" => "This video explains how the Advanced Workspace works and what are its components for.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/QX_CTsCPVF4",
							),
							"2_1_2_advanced_workspace_layers_diagram" => array(
								"title" => "Layers Diagram",
								"description" => "This video explains how to work with the Layers Diagram in the Advanced Workspace and what changes in the framework when we change the diagram.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/stZ2OgBZ2cY",
							),
							"2_1_3_advanced_workspace_db_tab" => array(
								"title" => "DB Layer Tab",
								"description" => "This video explains how to work with the DB Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/X4y7CZwQ700",
							),
							"2_1_4_advanced_workspace_dal_tab" => array(
								"title" => "Data-Access Layer Tab",
								"description" => "This video explains how to work with the Data-Access Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/b7cCvzxwyL0",
							),
							"2_1_5_advanced_workspace_bll_tab" => array(
								"title" => "Business-Logic Layer Tab",
								"description" => "This video explains how to work with the Business-Logic Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Si_aJW46bU8",
							),
							"2_1_6_advanced_workspace_pl_tab" => array(
								"title" => "Presentation Layer Tab",
								"description" => "This video explains how to work with the Presentation Layer Tab in the Advanced Workspace and what are its funcionalities.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/cqR2SXYJ_EM",
							),
							"2_1_7_advanced_workspace_lib_tab" => array(
								"title" => "Library Tab",
								"description" => "",
								"items" => array(
									"2_1_7_1_advanced_workspace_lib_tab" => array(
										"title" => "Overview",
										"description" => "This video explains how to work with the Library Tab in the Advanced Workspace and what are its funcionalities.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/4P1LisyQPIA",
									),
									"2_1_7_2_internal_lib_files" => array(
										"title" => "Internal Library Files",
										"description" => "This video explains what are the internal Library files in the Advanced Workspace and how can you use them...",
										"image" => "",
										"video" => "https://www.youtube.com/embed/skeNqZ-QCj8",
									),
								),
							),
						),
					),
					"2_2_module_installation" => array(
						"title" => "How to install Modules",
						"description" => "After the framework configured, you should install the modules that fulfill your needs, in order to create pages very fast and easily. This video explains how to do this..<br/>
	Note that the modules installation is optional and you only need to do it if you wish to have a wyswyg and no-code UI and use the Template-Region-Block relationship to create pages. Otherwise you can still create pages through our basic system, which is very user-friendly too...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/93bSFSmzmro",
					),
					"2_3_manage_developer_perms" => array(
						"title" => "How to manage developer access to Layers and other framework components",
						"description" => "In this video you can learn to create users and groups and give them permissions to access certain files in the framework.<br/>
	This allows multiple departments working together with different access levels, this is, the developers can have access to all files in the framework, but the webdesigners only to the Presentation/Interface Layer.<br/>
	Additionally when you install a new module, you must edit the correspondent permissions to the modules' files, otherwise you won't be able to access them.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/9HB_hHuOY8c",
					),
					"2_4_manage_layout_types" => array(
						"title" => "How to manage the Layout Types",
						"description" => "In this video you can learn how to configure access to files per project, defining which files belong to each project and how to reference some of these files in other projects.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/DKNLT_IUyf0",
					),
					"2_5_page_visual_editor" => array(
						"title" => "Understand the Page Editor",
						"description" => "",
						"items" => array(
							"2_5_1_page_visual_editor" => array(
								"title" => "Page Editor overview",
								"description" => "Learn how to work in the page editor.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/pejyKGOVjcM", //page_editor_overview.webm
							),
							"2_5_2_page_visual_editor" => array(
								"title" => "Understand the Page Editor in detail",
								"description" => "This video explains how to edit a Page in the visual editor.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Ht3-jU-A5HM",
							),
							"2_5_3_page_visual_editor" => array(
								"title" => "How to manipulate widgets in the Page Editor",
								"description" => "Learn how to work with widgets in the page editor, changing their properties, manipulating their dimensions, moving to different parents and positions and other actions...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/XMwbpe9HtMw", //page_editor_manipulate_widgets.webm
							),
							"2_5_4_page_editor_ai_widget" => array(
								"title" => "How to generate a layout component through AI",
								"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
							),
							"2_5_5_page_generation_with_ai" => array(
								"title" => "How to create a page through AI",
								"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
							),
							"2_5_6_image_to_page_conversion_with_ai" => array(
								"title" => "How to convert an image with a layout to an editable page through AI",
								"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
							),
						)
					),
					"2_6_template_visual_editor" => array(
						"title" => "Understand the Template Editor",
						"description" => "This video explains how to edit a Template in the visual editor.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/fVaDuuS5RxE",
					),
					"2_7_page_resource_actions" => array(
						"title" => "How to use the Page Resource Actions or Sequential Logical Activities",
						"description" => "This video explains how to create Resources and Sequential Logical Actions in the Page Editor, this is, when we edit a page, we can create Resources that get or set dynamic meta-data and then print them in our page...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/pOuHDYDSr04",
					),
					"2_8_hash_variable_code" => array(
						"title" => "How to use the Hash Variable Code - #variable_name#",
						"description" => "This video explains what is the 'Hash Variable Code', where can we use it and for what this is for... Basically this video explains how to print variables and dynamic data in html or sql statements through a specific syntax similar with: '#variable_name#'...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Y1XfeFWyQgE",
					),
					"2_9_manage_user_in_project" => array(
						"title" => "How to manage your application's users within a Project",
						"description" => "This video explains how to manage users within a project, setting permissions for private pages that need authentication to access.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/XSLKry20aXw",
					),
					"2_10_install_a_program" => array(
						"title" => "How to install a Program in a project",
						"description" => "This video explains how to install a program from our store in a project, that is, our store has some predefined applications, pages and other files that you can install in your project, and then change them according to your needs.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/rr0cvDV9Gv4",
					),
					"2_11_how_to_work_with_diagrams" => array(
						"title" => "How to work with Diagrams",
						"description" => "This video explains how to work with diagrams, i.e. what are the possible actions you can do when editing database diagrams, layer diagrams and more...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/jaAtapfIFO0",
					),
					"2_12_ai_summary" => array(
						"title" => "How to use AI to help you achieve your goals faster",
						"description" => "Discover how to leverage artificial intelligence to achieve your goals: simply describe what you need in natural language, and AI will generate it for you.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/ImZg10DEWWg", //ai_summary.webm
					),
				),
			),
			"3_db_layer" => array(
				"title" => "DB Layer",
				"description" => "Learn how to manage your DBs, create tables diagrams...<br/>
	To know more about this please read the <a href=\"{$online_tutorials_url_prefix}documentation/layers/database\" target=\"documentation\">Data Base Layer</a> section.",
				"items" => array(
					"3_1_advanced_workspace_db_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
						"title" => "Understand the DB Layer Tab",
						"description" => "This video explains how to work with the DB Layer Tab in the Advanced Workspace and what are its funcionalities.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/X4y7CZwQ700", //3_1_3_advanced_workspace_db_tab
					),
					"3_2_creation_of_db_diagram" => array(
						"title" => "Create DB Tables Diagram and Reverse Engineering",
						"description" => "This video explains how to create a DataBase diagram, generate the correspondent SQL and insert it to a DB.<br/>
	Additionally shows how to reverse engineering a schema from a DataBase into a tables diagram.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/__FeuikKvUI", //3_1_creation_of_db_diagram
					),
					"3_3_table_generation_through_ai" => array(
						"title" => "How to generate a table through AI",
						"description" => "Learn how to generate a new table with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/iHuYUFCcaJ0", //db_table.webm
					),
					"3_4_sql_generation_through_ai" => array(
						"title" => "How to generate SQL statements through AI",
						"description" => "Learn how to generate SQL statements with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Ro-AWKAjMr4", //db_sql_statements.webm
					),
				),
			),
			"4_data_access_layer" => array(
				"title" => "Data-Access Layer",
				"description" => "Learn how to create iBatis Queries, Hibernate Objects, Parameter and Result Maps and Classes, Object Types and Hibernate extensions...<br/>
	To read more about this please visit the <a href=\"{$online_tutorials_url_prefix}documentation/layers/data_access\" target=\"documentation\">Data Access Layer</a> section.",
				"items" => array(
					"4_1_advanced_workspace_dal_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
						"title" => "Understand the Data-Access Layer Tab",
						"description" => "This video explains how to work with the Data-Access Layer Tab in the Advanced Workspace and what are its funcionalities.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/b7cCvzxwyL0", //2_1_4_advanced_workspace_dal_tab
						"pages" => array("dataaccess/edit_query"), //this is used in the TourGuideUIHandler
					),
					"4_2_creation_of_ibatis" => array(
						"title" => "Create Queries through Ibatis",
						"description" => "This video explains how to create SQL queries to a Data-base Table through the iBatis system.<br/>
	Additionally it shows how to create these rules automatically from a DataBase diagram.<br/>
	Basically the framework will generate automatically the rules from a DataBase diagram, saving time to the programmer and maximizing his work...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/xib4OKTdjY0", //4_1_creation_of_ibatis
						"pages" => array("dataaccess/edit_query"), //this is used in the TourGuideUIHandler
					),
					"4_3_query_generation_through_ai" => array(
						"title" => "How to generate queries through AI",
						"description" => "Learn how to generate SQL queries with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/YunfqbI5cG0", //dal_sql.webm
					),
					"4_4_creation_of_hbn" => array(
						"title" => "Create Objects through Hibernate",
						"description" => "This video explains how to create Data-access objects to a Data-base Table through the Hibernate system.
	Additionally it shows how to create these objects automatically from a DataBase diagram.<br/>
	Basically the framework will generate automatically the objects from a DataBase diagram, saving time to the programmer and maximizing his work...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/PhJMowYIuF8", //4_2_creation_of_hbn
					),
					"4_5_ibatis_and_hbn_xml_files" => array(
						"title" => "Ibatis and Hibernate XML Files",
						"description" => "This video shows that you can edit directly the XML files of Ibatis and Hibernate in case you don't like to do it via the simple UI of the framework... Explains how to create SQL queries and Hibernate Objects directly in the xml file.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/8057YRW73ss", //4_3_ibatis_and_hbn_xml_files
					),
					"4_6_create_obj_types" => array(
						"title" => "Create Object Types",
						"description" => "This video shows how to create your own object type and then use it on Parameter or Result Maps or Classes or Annotations in a Business Logic Service.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/IkoQpKgW4dg", //4_4_create_obj_types
					),
					"4_7_create_hibernate_model_extensions" => array(
						"title" => "Create Hibernate Model Extensions",
						"description" => "This video shows how to extend a Hibernate object with new user-defined methods and properties, that is, it explains how to programmatically create a class that contains its own methods and properties and then use that class to extend a Hibernate object.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/XVY7LaQNx8o", //4_5_create_hibernate_model_extensions
					),
				),
			),
			"5_business_logic_layer" => array(
				"title" => "Business Logic Layer",
				"description" => "Learn how to create Services in the Business Logic Layer.",
				"pages" => array("businesslogic/edit_method", "businesslogic/edit_function", "admin/edit_file_class_method", "admin/edit_file_function"), //this is used in the TourGuideUIHandler
				"items" => array(
					"5_1_advanced_workspace_bll_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
						"title" => "Understand the Business-Logic Layer Tab",
						"description" => "This video explains how to work with the Business-Logic Layer Tab in the Advanced Workspace and what are its funcionalities.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Si_aJW46bU8", //2_1_5_advanced_workspace_bll_tab
					),
					"5_2_creation_of_soa" => array(
						"title" => "Create a Business Logic Service",
						"description" => "This video explains how to create a Service in the Business Logic Layer and relate it to the data-access layer.<br/>
	Additionally it shows how to create these services automatically from SQL queries and objects in the Data-Access Layer. Basically the framework will generate automatically the services from the rules of a Data-Access Layer, saving time to the programmer and maximizing his work...<br/>
	Note that if the Business-Logic Layer is connected directly to the Data-Base Layer, it can execute sql directly too, but this explanation is not in this video.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/pEPOW0HfueE", //5_1_creation_of_soa
					),
					"5_3_creation_of_manual_soa" => array(
						"title" => "Create a Business Logic Service Manually",
						"description" => "This video explains how to create manually, step by step, a Service in the Business Logic Layer that gets a record from a DB table.<br/>
	Note that if the Business-Logic Layer is connected directly to the DB Layer, which means it can execute sql directly.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Iq8hOqzQCw0", //5_2_creation_of_manual_soa
					),
					"5_4_code_generation_through_ai" => array(
						"title" => "How to generate code through AI",
						"description" => "Learn how to generate code with artificial intelligence by writing what you wish in natural language. You can ask AI to explain a specific piece of code, or create test units for it, or generate a new function and logic code according with your requirements, or something else...<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/5OZk4noPa_M", //php_generation.webm
					),
				),
			),
			"6_presentation_layer" => array(
				"title" => "Presentation Layer",
				"description" => "Learn how to create pages, templates, views, blocks and others...",
				"items" => array(
					"2_1_6_advanced_workspace_pl_tab" => array( //this was copied from the 2_get_familiar_with_the_framework section
						"title" => "Understand the Presentation Layer Tab",
						"description" => "This video explains how to work with the Presentation Layer Tab in the Advanced Workspace and what are its funcionalities.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/cqR2SXYJ_EM",
					),
					"6_1_creation_of_simple_page" => array(
						"title" => "Understand the Page creation process",
						"description" => "Learn the relationships between Pages, Views, Templates, Modules and Blocks, by creating a very simple page with or without Views, Templates, Modules, Blocks, Variables...",
						"items" => array(
							"6_1_1_creation_of_simple_page_on_simple_admin" => array(
								"title" => "On Simple Workspace",
								"description" => "",
								"items" => array(
									"6_1_1_1_creation_of_simple_page_on_simple_admin" => array(
										"title" => "Simple Page creation",
										"description" => "This video explains how to create a simple Page with a simple text. Basically we will create a Page with some dummy text.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/uhkSijyV_5Y", //6_1_1_1_creation_of_simple_page_on_simple_admin
									),
									"6_1_1_2_page_generation_with_ai" => array(
										"title" => "How to create a page through AI",
										"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
									),
									"6_1_1_3_image_to_page_conversion_with_ai" => array(
										"title" => "How to convert an image with a layout to an editable page through AI",
										"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
									),
									"6_1_1_4_creation_of_simple_page_with_template_on_simple_admin" => array(
										"title" => "Simple Page creation with Template",
										"description" => "This video explains how to create a Page which will be integrated into a pre-installed Template. Basically we will create a Page and set a variable inside of that Page and then print that variable through the Template.<br/>
	The purpose of this video is to show how Pages and Templates can work together...<br/>
	In these examples you must be familiar with creating and print variables in php.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/f3JHcptQ3h4", //6_1_1_2_creation_of_simple_page_with_template_on_simple_admin
									),
									"6_1_1_5_creation_of_simple_page_with_modules_on_simple_admin" => array(
										"title" => "Simple Page creation with Modules",
										"description" => "This video explains how to create a Page using the Page Simple UI which uses pre-installed Templates and Modules.<br/>
	The purpose of this video is to teach how pages can be used with templates and modules.<br/>
	In this example we will use the 'Menu' Module to create a menu and the 'EchoStr' Module to show some text in the Main Area of the template.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/uCG2_JeAP6k", //6_1_1_3_creation_of_simple_page_with_modules_on_simple_admin
									),
								),
							),
							"6_1_2_creation_of_simple_page_on_advanced_admin" => array(
								"title" => "On Advanced Workspace",
								"description" => "",
								"items" => array(
									"6_1_2_1_creation_of_simple_page_on_advanced_admin" => array(
										"title" => "Simple Page creation",
										"description" => "This video explains how to create a simple Page with a simple html text. Basically we will create a Page with some dummy html.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/aH2ZXe9gIGI", //6_1_2_1_creation_of_simple_page_on_advanced_admin
									),
									"6_1_2_2_page_generation_with_ai" => array(
										"title" => "How to create a page through AI",
										"description" => "Learn how to create a new page with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/dybAfnkItRM", //page_generation.webm
									),
									"6_1_2_3_image_to_page_conversion_with_ai" => array(
										"title" => "How to convert an image with a layout to an editable page through AI",
										"description" => "Discover how to use artificial intelligence to transform an image of a layout from your web designer into an editable page. Customize and refine the converted components as needed, creating a fully ready-to-use page for your end users.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/ylfVfuD6Qvo", //image_page_conversion.webm
									),
									"6_1_2_4_1_creation_of_simple_page_with_view_on_advanced_admin_1" => array(
										"title" => "Simple Page creation with View - step 1",
										"description" => "This video explains how to create a Page with a View with a simple text.<br/>
	Explains how can you use a View in multiple Pages, this is, you can use Views to display data coming from Pages, reusing code instead of repeating it...<br/>
	The purpose of these videos is to show how Pages and Views can work together...<br/>
	In these examples you must be familiar with creating and print variables in php.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/a83yXdijUL0", //6_1_2_2_1_creation_of_simple_page_with_view_on_advanced_admin_1
									),
									"6_1_2_4_2_creation_of_simple_page_with_view_on_advanced_admin_2" => array(
										"title" => "Simple Page creation with View - step 2",
										"description" => "This video explains how to create a Page with a View with a simple text.<br/>
	Explains how can you have Pages with php variables and php logic code, and Views with html code.<br/>
	The purpose of these videos is to show how Pages and Views can work together...<br/>
	In these examples you must be familiar with creating and print variables in php.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/URZZuNQ7HS0", //6_1_2_2_2_creation_of_simple_page_with_view_on_advanced_admin_2
									),
									"6_1_2_5_creation_of_simple_page_with_template_on_advanced_admin" => array(
										"title" => "Simple Page creation with Template",
										"description" => "This video explains how to create a Page which will be integrated into a pre-installed Template. Basically we will create a page and set a variable inside of that page and then print that variable through the template.<br/>
	The purpose of this video is to show how Pages and Templates can work together...<br/>
	In these examples you must be familiar with creating and print variables in php.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/ueHwYCIrPlM", //6_1_2_3_creation_of_simple_page_with_template_on_advanced_admin
									),
									"6_1_2_6_creation_of_simple_page_with_view_and_template_on_advanced_admin" => array(
										"title" => "Simple Page creation with View and Template",
										"description" => "This video explains how to create a Page with a View but that will be integrated into a pre-installed Template.<br/>
	Basically we will create a Page which will call a View and then print the view throught a Template. The View will create the html according with the variables defined in the Page.<br/> 
	The purpose of this video is to show how Pages, Views and Templates can work together...<br/>
	In these examples you must be familiar with creating and print variables in php.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/GLb9lPYJE1g", //6_1_2_4_creation_of_simple_page_with_view_and_template_on_advanced_admin
									),
									"6_1_2_7_creation_of_simple_page_with_modules_on_advanced_admin" => array(
										"title" => "Simple Page creation with Modules",
										"description" => "This video explains how to create a Page using the Page Simple UI which uses pre-installed Templates and Modules.<br/>
	The purpose of this video is to teach how pages can be used with templates and modules.<br/>
	In this example we will use the 'Menu' Module to create a menu and the 'EchoStr' Module to show some text in the Main Area of the template.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/j1s2Qcl1po0", //6_1_2_5_creation_of_simple_page_with_modules_on_advanced_admin
									),
								),
							),
						),
					),
					"6_2_creation_of_simple_site_with_modules_integration" => array(
						"title" => "Create Pages that print DB metadata using pre-installed Modules",
						"description" => "This video explains how to create a simple site in the framework, with a homepage, a list of articles and another page to see each article individually.<br/>
	Learn how to create simple sites by creating basic templates and configure pre-installed modules.<br/>
	The purpose of these videos is to teach you how templates and regions, modules and blocks work and how can be integrated into pages.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/ir8WAnVi5Cg",
					),
					"6_3_create_page_with_html_widgets" => array(
						"title" => "Create a Page with Html Widgets",
						"description" => "Learn how to create pages using the Html Widgets to display metadata from a DB.",
						"items" => array(
							"3_simple_workspace" => array(
								"title" => "Simple Workspace",
								"description" => "Learn how to work in the simple workspace.",
								"items" => array(
									"3_1_create_project_with_static_page" => array(
										"title" => "Create a static page",
										"description" => "Learn how to create a static page by drag&drop html widgets.",
										"image" => "",
										"items" => array(
											"3_1_1_create_project_with_static_page_from_scratch" => array(
												"title" => "Create a static page from scratch",
												"description" => "Learn how to create a static page from scratch (with a blank canvas), by drag&drop html widgets.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/qFUwt_OCVHE", //create_project_with_static_page_from_scratch.webm
											),
											"3_1_2_create_project_with_static_page_from_template" => array(
												"title" => "Create a static page using a theme template",
												"description" => "Learn how to create a static page using a theme template, by drag&drop html widgets.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/CrEznnR2lkY", //create_project_with_static_page_from_template.webm
											),
											"3_1_3_page_editor_ai_widget" => array(
												"title" => "How to generate a layout component through AI",
												"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
											),
										)
									),
									"3_2_page_with_diferent_attributes_shown" => array(
										"title" => "Create a page that shows information coming from the database",
										"description" => "Learn how to create a static page with some dynamic data coming from the database",
										"image" => "",
										"video" => "https://www.youtube.com/embed/v1shjjwHrUc", //page_with_diferent_attributes_shown.webm
									),
									"3_3_convert static page to dynamic page" => array(
										"title" => "Create a page with dynamic data",
										"description" => "Learn how to create a page by converting static data into dynamic data and saving it to a database.",
										"image" => "",
										"items" => array(
											"3_3_1_convert static page to dynamic page_a" => array(
												"title" => "Create a page with dynamic data",
												"description" => "Learn how to create a table in the database and show its records.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/_PPIUK6fS_o", //convert static page to dynamic page_a.webm
											),
											"3_3_2_convert static page to dynamic page_b" => array(
												"title" => "Create a page with dynamic data",
												"description" => "Learn how to create a table in the database and show its records.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/BSQG9emlcJ8", //convert static page to dynamic page_b.webm
											),
										)
									),
									"3_4_form to add record" => array(
										"title" => "Create a page with a form to add records into a database table",
										"description" => "Learn how to create a page to allow users to add records to your database tables.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/KHFY7ieO1jM", //form to add record.webm
									),
									"3_5_editable list" => array(
										"title" => "Create a page with an editable list to manage the records from a database table",
										"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/edCA35FI8Co", //editable list.webm
									),
									"3_6_edit a record" => array(
										"title" => "Create a page with a form to edit records from a database table",
										"description" => "Learn how to create a page to allow users to edit records from your database tables.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/fDiVJ3E-_bE", //edit a record.webm
									),
									"3_7_editable list with 2 tables related" => array(
										"title" => "Create a page with a list with data of 2 database tables",
										"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/Gp1FKy5L8Ds", //editable list with 2 tables related.webm
									),
								)
							),
							"4_advanced_workspace" => array(
								"title" => "Advanced Workspace",
								"description" => "Learn how to work in the advanced workspace.",
								"items" => array(
									"4_1_create_project_with_static_page" => array(
										"title" => "Create a static page",
										"description" => "Learn how to create a static page by drag&drop html widgets.",
										"image" => "",
										"items" => array(
											"4_1_1_create_project_with_static_page_from_scratch" => array(
												"title" => "Create a static page from scratch",
												"description" => "Learn how to create a static page from scratch (with a blank canvas), by drag&drop html widgets.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/4I-wmvpP7rg", //create_project_with_static_page_from_scratch.webm
											),
											"4_1_2_create_project_with_static_page_from_template" => array(
												"title" => "Create a static page using a theme template",
												"description" => "Learn how to create a static page using a theme template, by drag&drop html widgets.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/gwDZMLa4kZk", //create_project_with_static_page_from_template.webm
											),
											"4_1_3_page_editor_ai_widget" => array(
												"title" => "How to generate a layout component through AI",
												"description" => "Learn how to generate a layout component in a specific position with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/tnQqNnRcqz4", //layout_component_generation.webm
											),
										)
									),
									"4_2_page_with_diferent_attributes_shown" => array(
										"title" => "Create a page that shows information coming from the database",
										"description" => "Learn how to create a static page with some dynamic data coming from the database",
										"image" => "",
										"video" => "https://www.youtube.com/embed/TOWnZdg0tKs", //page_with_diferent_attributes_shown.webm
									),
									"4_3_convert static page to dynamic page" => array(
										"title" => "Create a page with dynamic data",
										"description" => "Learn how to create a page by converting static data into dynamic data and saving it to a database.",
										"image" => "",
										"items" => array(
											"4_3_1_convert static page to dynamic page_a" => array(
												"title" => "Create a page with dynamic data",
												"description" => "Learn how to create a table in the database and show its records.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/0AT_iacudaI", //convert static page to dynamic page_a.webm
											),
											"4_3_2_convert static page to dynamic page_b" => array(
												"title" => "Create a page with dynamic data",
												"description" => "Learn how to create a table in the database and show its records.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/T2JuI8CEeok", //convert static page to dynamic page_b.webm
											),
										)
									),
									"4_4_form to add record" => array(
										"title" => "Create a page with a form to add records into a database table",
										"description" => "Learn how to create a page to allow users to add records to your database tables.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/iLGHHfFay9w", //form to add records.webm
									),
									"4_5_editable list" => array(
										"title" => "Create a page with an editable list to manage the records from a database table",
										"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
										"image" => "",
										"items" => array(
											"4_5_1_editable list" => array(
												"title" => "Create a page with an editable list to manage the records from a database table",
												"description" => "Learn how to create a page to allow users to add, edit and remove records of a database table.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/GGEiIftpe1A", //editable list.webm
											),
											"4_5_2_create_page_with_a_list" => array(
												"title" => "Create a page with a list of DB records",
												"description" => "This video explains how to create a Page that prints a list of database records through Html Widgets and corresponding Resources.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/DBgnRedIZIc", //6_3_2_create_page_with_html_widgets
											),
											"4_5_3_create_page_with_a_list_in_3_ways" => array(
												"title" => "Create a page with a list of DB records in 3 different ways",
												"description" => "This video explains, in 3 different ways, how to create a Page using Html Widgets to print a list of records from a database table.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/dTziiRZmCkk", //6_3_5_create_page_with_html_widgets
											),
										)
									),
									"4_6_edit a record" => array(
										"title" => "Create a page with a form to edit records from a database table",
										"description" => "Learn how to create a page to allow users to edit records from your database tables.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/z1nwCz1OvWo", //edit a record.webm
									),
									"4_7_editable list with 2 tables related" => array(
										"title" => "Create a page with a list with data of 2 database tables",
										"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other.",
										"image" => "",
										"items" => array(
											"4_7_1_editable list with 2 tables related_diagram" => array(
												"title" => "Create a page with a list of records from 2 database tables connected in the DB Diagram",
												"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other in the DB Diagram.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/TYojwIyEpY8", //editable list with 2 tables related_diagram.webm
											),
											"4_7_2_editable list with 2 tables related_server" => array(
												"title" => "Create a page with a list of records from 2 database tables connected directly in the DB Server",
												"description" => "Learn how to create a page showing data coming from 2 database tables connected to each other in the DB Server.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/aok64tRgoNs", //editable list with 2 tables related_server.webm
											),
											"4_7_3_create_page_with_html_widgets_with_fks" => array(
												"title" => "Create a page a list of DB records with Foreign Keys",
												"description" => "This video explains how to create a Page that prints a list of database records through Html Widgets and corresponding Resources. The record list will contain a foreign key to another table in the database and allow you to edit each record directly.",
												"image" => "",
												"video" => "https://www.youtube.com/embed/2ZbsuMop4n8", //6_3_3_create_page_with_html_widgets
											),
										)
									),
								)
							),
							"5_multiple comboboxes connected" => array(
								"title" => "Create a page with dynamic comboboxes/dropdowns",
								"description" => "Learn how to create a page with comboboxes/dropdowns that load data from the database.",
								"image" => "",
								"items" => array(
									"5_1_multiple comboboxes connected" => array(
										"title" => "Create a page with multiple combobox/dropdowns connected to each other",
										"description" => "Learn how to create a page with 3 comboboxes/dropdowns dependent on each other, where changing one changes the others.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/QJQhjODtqdc", //multiple comboboxes connected.webm
									),
									"5_2_create_page_with_a_combobox" => array(
										"title" => "Create a combobox with the dynamic values of a DB table and according to the user's selection, show the details of that selected record below",
										"description" => "This video explains how to create a Page using Html Widgets to create a combobox with the values of a DB table, which, according to the user's selection, shows the details of that selected record in an html form below. All this will be done via drag and drop based on No-Code.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/89-wxTexCY8", //6_3_11_1_create_page_with_html_widgets
									),
									"5_3_create_page_with_a_combobox_with_dependencies" => array(
										"title" => "Create a combobox with the dynamic values of a DB table and according to an URL value, select that record in the combobox and show its details below",
										"description" => "This video explains how to create a Page using Html Widgets to create a combobox with values from a DB table, which, according to a variable value in the URL, selects that default record in the combobox and also shows its details in an html form below. All this will be done via drag and drop based on No-Code.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/fH0RjNIH2D0", //6_3_11_2_create_page_with_html_widgets
									),
								)
							),
							"6_create_page_with_available_values" => array(
								"title" => "Create a list with Available Values",
								"description" => "This video explains how to change the values of a list of records where numeric columns are replaced with user-firendly values, dynamically obtained or manually created.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/nsofMBHKGAs", //available_values.webm
							),
							"7_create_page_with_charts" => array(
								"title" => "Create a page with Charts",
								"description" => "This video explains how to create a page with Charts, based in values dynamically obtained or created manually.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Oc6PyxiWi88", //charts.webm
							),
							"8_create_excel_reports" => array(
								"title" => "Create Excel Reports",
								"description" => "This video explains how to create Excel reports that contains records coming from database tables or third-parthy services.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/WgTCaDU9pCc", //report.webm
							),
							"8_create_pdf_reports" => array(
								"title" => "Create PDF Reports",
								"description" => "This video explains how to create PDF reports that contains records coming from database tables or third-parthy services.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/k_y1SB72upQ", //stock_manager_11.webm
							),
							"9_create_page_with_html_widgets_in_detail" => array(
								"title" => "Create a page with HTML Widgets in detail",
								"description" => "This video explains how to create a Page using Html Widgets to design your page layout and print some resources...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/6j-rtmgCXg0", //6_3_1_create_page_with_html_widgets
							),
							"10_creating_new_resources_through_page_editor" => array(
								"title" => "Create Resources/Actions in the page editor",
								"description" => "Learn how to create new resources/actions in the page editor.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/5Qp9ywQa6cU", //creating_new_resources_through_page_editor.webm
							),
							"11_creating_new_bl_services_through_page_editor" => array(
								"title" => "Create Business Logic services through the page editor",
								"description" => "Learn how to create new Business Logic services through the page editor.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/lblrkSoJNXI", //creating_new_bl_services_through_page_editor.webm
							),
							"12_create_3_pages_connected_to_each_other" => array(
								"title" => "Create 3 Pages that communicate with each other",
								"description" => "This video explains how to create 3 pages that communicate with each other using Html Widgets to design the page layout and print some resources... Basically, the first page will contain a list of records from a parent database table, the second page a list of records from a child database table, and the third page a form to view the details of a record from the parent table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/7kXqNXQmcvo", //6_3_4_create_page_with_html_widgets
							),
							"13_create_page_with_popup_to_another_page" => array(
								"title" => "Call another page though a popup",
								"description" => "This video explains how to create 2 pages using Html Widgets, where the first page prints a list of records from a database table and when the user clicks on each record, a popup opens with the second page showing the details of that record, allowing the user to edit it.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/rmF8JgJMnxc", //6_3_6_create_page_with_html_widgets
							),
							"14_create_page_based_in_copy_paste_from_external_site" => array(
								"title" => "Copy paste HTML from external site",
								"description" => "This video explains how to copy some HTML from a thrid-party site and paste it into your page.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/rbMdrXEarps", //copy_paste.webm
							),
							"15_create_page_with_dynamic_attributes" => array(
								"title" => "Show specific attributes of a database table through a more manual process",
								"description" => "This video explains how to create a Page using Html Widgets to print database metadata, that is, how to print an attribute of a database table through Html Widgets.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/uTfE3PiYeeY", //6_3_7_create_page_with_html_widgets
							),
							"16_create_quiz_like_in_typeform" => array(
								"title" => "Create a quiz as you build it in the TypeForm tool",
								"description" => "This video explains how to create beautiful questionnaires, with different slide effects, and then save the user's answers in a database. Basically, it shows you how to create quizzes as you build them through the TypeForm tool.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/cj53ar5tOUM", //16_create_quiz_like_in_typeform
							),
							"17_create_page_with_calendar" => array(
								"title" => "Create a page with an events calendar",
								"description" => "This video explains how to create a Page with a calendar, where you can list and edit events from and to a database table.",
								"image" => "",
								"items" => array(
									"17_1_create_page_with_readonly_calendar_events" => array(
										"title" => "Create a page with an events calendar - part 1",
										"description" => "Learn how to create a page with a calendar that shows events pulled from a database table.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/qiClkzpkacE", //16_1_create_page_with_readonly_calendar_events
									),
									"17_2_create_page_with_editable_calendar_events" => array(
										"title" => "Create a page with an events calendar - part 2",
										"description" => "Learn how to create a page with a calendar that allows to add, edit and remove events from a database table.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/jfaP4jipJF0", //16_2_create_page_with_editable_calendar_events
									),
								)
							),
							"18_create_page_with_gantt_calendar" => array(
								"title" => "Create a page with a GANTT Chart",
								"description" => "Learn how to create a page with a GANTT chart that allows to add, edit and remove tasks to your employees.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/fweaP_bYIfI", //17_create_page_with_gantt_calendar
							),
							"19_create_page_with_matrix_board" => array(
								"title" => "Create a page with a matrix board",
								"description" => "Learn how to create pages with matrix boards: to classify the skills for your employees, or manage their competences or assign tasks or projects, show results from multiple different sports grouped by categories and sub-categories.",
								"image" => "",
								"items" => array(
									"19_1_create_page_with_matrix_board_to_manage_employee_skills" => array(
										"title" => "Create a page with a matrix board to manage the competences of your employees",
										"description" => "Learn how to create a page with a matrix board that shows the list of your employees and the skills available, allowing you to correlate which skills each employee has.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/gjVDFi3GgPE", //18_1_create_page_with_matrix_board_to_manage_employee_skills
									),
									"19_2_create_page_with_matrix_board_to_classify_employee_skills" => array(
										"title" => "Create a page with a matrix board to classify the skills for your employees",
										"description" => "Learn how to create a page with a matrix board that shows the list of your employees and the skills available, allowing you to classify each employee's skill level.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/3bltGNebiNI", //18_2_create_page_with_matrix_board_to_classify_employee_skills
									),
									"19_3_create_page_with_matrix_board_to_assign_employee_projects" => array(
										"title" => "Create a page with a matrix board to assign projects to your employees",
										"description" => "Learn how to create a page with a matrix board that shows the list of your employees and available projects, allowing you to assign projects to your employees and edit project details.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/00USCMwZu7A", //18_3_create_page_with_matrix_board_to_assign_employee_projects
									),
									"19_4_create_page_with_matrix_board_to_group_employee_skills" => array(
										"title" => "Create a page with a matrix board to list employee skills grouped by category",
										"description" => "Learn how to create a matrix board that shows a list of your employees and their skills, where skills are grouped by category.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/HGIUZ_dvftI", //18_4_create_page_with_matrix_board_to_group_employee_skills
									),
									"19_5_create_page_with_matrix_board_to_group_games_by_category" => array(
										"title" => "Create a page with a matrix board to list sport games from multiple categories and sub-categories",
										"description" => "Learn how to create a matrix chart that shows a list of games from a sport, grouped by categories and subcategories. Something like soccer games that belong to a championship, division, group, gender, etc...",
										"image" => "",
										"video" => "https://www.youtube.com/embed/sIcpOdSpOzg", //18_5_create_page_with_matrix_board_to_group_games_by_category
									),
								)
							),
							"20_create_page_with_scrum_board" => array(
								"title" => "Create a page with a SCRUM board to manage your tasks",
								"description" => "Learn how to create a page with a SCRUM board that allows to add, edit and remove tasks from your projects or employees.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/JqKWVWHSiqg", //19_create_page_with_scrum_board
							),
							"21_create_page_with_dynamic_attributes_through_manual_bl" => array(
								"title" => "Show specific attributes of a DB table through a manual business logic service",
								"description" => "This video explains how to create a Page using Html Widgets to print database metadata, that is, based on a business logic service that we create manually to get a record from a database table, print that record's attributes in the html.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/aVO-SYd1Xdc", //6_3_8_create_page_with_html_widgets
							),
							"22_create_page_with_add_form_through_manual_bl" => array(
								"title" => "Enter user input from a html form into a DB table through a manual business logic service",
								"description" => "This video explains how to create a Page using Html Widgets to insert some metadata in a DB table from user input entered in some form fields, based on a business logic service that we create manually to insert a record into a database table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Snd35lEtMrM", //6_3_9_create_page_with_html_widgets
							),
							"23_create_page_with_edit_form_through_manual_bl" => array(
								"title" => "Show the DB table metadata in a html form and allow the user to edit and save that metadata in the DB through a manual business logic service",
								"description" => "This video explains how to create a Page using Html Widgets to display database metadata in an html form and allow the user to edit and save this metadata to the database. All this will be done through business logic services that we created manually to obtain and save a record from and to a database table, printing the attributes of that record in html.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/NklaG2f2Ceo", //6_3_10_create_page_with_html_widgets
							),
							"24_create_page_with_upload_form_locally" => array(
								"title" => "Create html form to upload files into a folder in your project",
								"description" => "This video explains how to create a page using Html Widgets to create an html form to upload local files to a folder in your project. We will use 3 different ways to do the same thing, including low-code file upload, creating a business logic service for this purpose.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ZrHiTsQMrng", //6_3_12_1_create_page_with_html_widgets
							),
							"25_create_page_upload_form_dynamically" => array(
								"title" => "Create an html form to upload files to a DB table and then another page to list the uploaded files from that DB table",
								"description" => "This video explains how to create Pages using Html Widgets to create an html form to upload files to a database table and an html table to list the uploaded files from that database table. All of this will be done through business logic services that we create manually to insert records into a database table and also to get records from that table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/HHW0V7_7xCA", //6_3_12_2_create_page_with_html_widgets
							),
							"26_create_page_with_complex_structures" => array(
								"title" => "Create page showing complex structures",
								"description" => "This video explains how to create pages showing a list with another internal list or objects inside.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/PK_akflyjqI", //complex_structures.webm
							),
							"27_create_page_with_recursive_complex_structures" => array(
								"title" => "Create page showing complex structures recursively",
								"description" => "This video explains how to create pages showing a list with multiple internal lists or objects that can contain another list or internal objects, at multiple levels.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/NJ_jlnZahR4", //recursive_complex_structure.webm
							),
							"28_create_page_to_show_dynamic_recursive_complex_structures" => array(
								"title" => "Create page showing redundant database structures recursively",
								"description" => "This video explains how to create pages showing records from a database table connected to itself, as in this example: a Category can have another Category or belong to another Category. This is also very useful to show menus with other menu items inside.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/nl7NhjbFxJQ", //show_dynamic_recursive_structure.webm
							),
							"29_create_page_to_edit_dynamic_recursive_complex_structures" => array(
								"title" => "Create page to edit redundant database structures recursively",
								"description" => "This video explains how to create pages to edit records from a database table connected to itself, as in this example: a Category can have another Category or belong to another Category. This is also very useful to edit and manage menus with other menu items inside.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/YA4e-sW52Zk", //edit_dynamic_recursive_structure.webm
							),
							"30_create_page_for_api" => array(
								"title" => "Create page to be used by external APIs or AJAX requests",
								"description" => "This video explains how to create pages to be used by external APIs, AJAX requests or via the command line. In this video we will create a page that will be used externally to insert records into a database table.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/v40WwynoNSY", //create_page_for_api.webm
							),
							"31_create_page_with_data_from_rest_api" => array(
								"title" => "Create a page that shows results from third-party REST services",
								"description" => "This video explains how to create Pages that communicate with third-party REST services, parse their response in json/xml, displaying that data in html. All of this will be done through page resources and business logic services that we will create manually.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ZQaEJ2xvWGM", //6_3_13_create_page_with_html_widgets
							),
						),
					),
					"6_4_create_pages_with_authentication" => array(
						"title" => "Create a Page with authentication",
						"description" => "This video explains how to create a Page with authentication where the user needs to authenticate first to access it. To achieve this result, we will install the 'Auth' program from our store.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/dgBBBBeZ1ZQ",
					),
					"6_5_prepare_presentation_page_with_files_diagram" => array(
						"title" => "Create a Page via Files Diagram",
						"description" => "Learn how to create pages through the Files Diagram.<br/>
	\"Files Diagram\" are drag&amp;drop workflows that allows connecting Files with Data-Base Tables' List and Forms, this is, through drag&amp;drop you can build your own Pages, connect them, add Lists or Forms with meta-data from Data-Base Tables, make that Lists and Forms editable, configure users permissions...<br/>
	<br/>
	The image below shows an example of a Files Diagram:<br/>
	<img src=\"{$project_url_prefix}img/tutorial/files_diagram.png\"/>
	<br/>
	<br/>
	The Video tutorials below teach how can you create pages through the Files Diagram Workfllow",
						"items" => array(
							"6_5_1_prepare_presentation_page_calling_business_logic_with_files_diagram" => array(
								"title" => "Using Business Logic Services - on Advanced Workspace",
								"description" => "This video explains how to create a page which calls a service from the Business Logic Layer... It shows that is possible to call a service from the Business Logic Layer through Files Diagram very easy and fast.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/M5vIbTZbXJ8",
							),
							"6_5_2_prepare_presentation_page_calling_ibatis_and_hibernate_with_files_diagram" => array(
								"title" => "Using Ibatis Rules and Hibernate Objects - on Advanced Workspace",
								"description" => "This video explains how to create a page which calls a service from Ibatis and Hibernate Layers... It shows that is possible to call a service from Data-Access Layers through the Files Diagram very easy and fast.<br/>
	<br/>
	Note that this will only be possible if the <a href=\"{$online_tutorials_url_prefix}documentation/layers/presentation\" target=\"documentation\">Presentation Layers</a> is connected directly with a <a href=\"{$online_tutorials_url_prefix}documentation/layers/data_access\" target=\"documentation\">Data-Access Layer</a>.<br/>",
								"image" => "",
								"video" => "https://www.youtube.com/embed/rob3F-7Eivo",
							),
							"6_5_3_prepare_presentation_pages_with_files_diagram" => array(
								"title" => "Learn how to work with the Files Diagram creating multiple pages",
								"description" => "The videos below teach how to create pages from scratch based in the Files Diagram, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.",
								"items" => array(
									"7_5_1_create_new_backoffice_manually_from_scratch" => array(
										"title" => "Learn how to work with the Files Diagram creating multiple pages 1",
										"description" => "This video teaches how to create pages from scratch based in the Files Diagram, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/ieaXBMZavDQ",
									),
									"7_5_2_create_new_backoffice_manually_from_scratch" => array(
										"title" => "Learn how to work with the Files Diagram creating multiple pages 2",
										"description" => "This video teaches how to create pages from scratch based in the Files Diagram, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.",
										"image" => "",
										"video" => "https://www.youtube.com/embed/Fu_TC0wD0s4",
									),
								),
							),
						),
					),
					"6_6_prepare_presentation_page_with_form_module" => array(
						"title" => "Create a Page via Form Module",
						"description" => "Learn how to create pages with the Form Module.<br/>
	\"Form\" Module is a more-less No-Code interface which allows to create Blocks of code with any logic you wish, by adding chunks of pre-defined actions, configure them and connected them between each-other.<br/>
	<br/>
	This module gives 2 different interfaces to accomplish this, through:<ul>
		<li>\"inline chunks of actions\": where all chunk are added inline and sequential.</li>
		<li>\"workflow\": where you create your own workflow by drag&amp;dropping chunks and connect them...</li>
	</ul>
	<br/>
	The image below shows an example of the \"Form\" Module:<br/>
	<img src=\"{$project_url_prefix}img/tutorial/module_form.png\"/>
	<br/>
	The Video tutorials below teach how can you create pages through these ways:",
						"items" => array(
							"6_6_1_prepare_presentation_page_calling_business_logic_with_form_module" => array(
								"title" => "Using Business Logic Services - on Advanced Workspace",
								"description" => "This video explains how to create a page which calls a service from the Business Logic Layer... It shows that is possible to call a service from the Business Logic Layer through the 'Form' Module.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/sCQr-3FULJA",
							),
							"6_6_2_prepare_presentation_page_calling_ibatis_and_hibernate_with_form_module" => array(
								"title" => "Using Ibatis Rules - on Advanced Workspace",
								"description" => "This video explains how to create a page which calls a service from Ibatis and Hibernate Layers... It shows that is possible to call a service from Data-Access Layers through the 'Form' Module.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/2pu9qoost-Y",
							),
							"6_6_3_prepare_presentation_page_using_form_module_wizard" => array(
								"title" => "Using the Form Wizard - on Advanced Workspace",
								"description" => "This video explains how to create a page with DB Table list through the Wizard of the 'Form' Module.<br/>
	Note that in order to use this wizard the Presentation Layers must be directly connected to a Data-Access Layer.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/G5wy8cyJffE",
							),
							"6_6_4_prepare_presentation_page_using_form_module_workflow" => array(
								"title" => "Using the Form Workflow Diagram - on Advanced Workspace",
								"description" => "This video explains how to create a page with DB Table list by drag & dropping through the Workflow of the 'Form' Module.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Iq7HgVuFau8",
							),
							"6_6_5_1_conversion_of_form_settings_to_ptl" => array(
								"title" => "Convert the Form Settings to PTL 1 - on Advanced Workspace",
								"description" => "Our Html template language is a proprietary language called: <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL - PHP Template Language</a> - which allows you to write logic code inside of the html.<br/>
	Is very fast and user-friendly!!!<br/>
	To read more information about this template language please check the <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a> section.<br/>
	This video teaches you how to convert Form settings into <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a>.<br/>",
								"image" => "",
								"video" => "https://www.youtube.com/embed/nuuA6GFazkY",
							),
							"6_6_5_2_conversion_of_form_settings_to_ptl" => array(
								"title" => "Convert the Form Settings to PTL 2 - on Advanced Workspace",
								"description" => "Our Html template language is a proprietary language called: <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL - PHP Template Language</a> which allows you to write logic code inside of the html.<br/>
	Is very fast and user-friendly!!!<br/>
	To read more information about this template language please check the <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a> section.<br/>
	This video teaches you how to convert Form settings into <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a>.<br/>",
								"image" => "",
								"video" => "https://www.youtube.com/embed/j5FJXWX6OyM",
							),
							"6_6_5_3_conversion_of_form_settings_to_ptl" => array(
								"title" => "Convert the Form Settings to PTL 3 - on Advanced Workspace",
								"description" => "Our Html template language is a proprietary language called: <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL - PHP Template Language</a> which allows you to write logic code inside of the html.<br/>
	Is very fast and user-friendly!!!<br/>
	To read more information about this template language please check the <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a> section.<br/>
	This video teaches you how to convert Form settings into <a href=\"{$online_tutorials_url_prefix}documentation/ptl\" target=\"documentation\">PTL</a>.<br/>",
								"image" => "",
								"video" => "https://www.youtube.com/embed/pjCOpAqapG0",
							),
							"6_6_6_creation_of_excel_reports" => array(
								"title" => "Create Excel Reports - on Advanced Workspace",
								"description" => "This video teaches how to create Excel reports in only a few minutes, based in meta-data from a Data-Base...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/9h7vNkAZohQ",
							),
							"6_6_6_creation_of_pdf_reports" => array(
								"title" => "Create PDF Reports - on Advanced Workspace",
								"description" => "This video teaches how to create PDF reports in only a few minutes, based in meta-data from a Data-Base...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/k_y1SB72upQ", //stock_manager_11.webm
							),
						),
					),
					"6_7_creation_of_page_with_business_logic_via_workflow" => array(
						"title" => "Create a Page via Workflow with SOA - on Advanced Workspace",
						"description" => "This video explains how to create a page in the framework, which calls a service from the Business Logic Layer...<br/>
	It shows that is possible to call a service from the Business Logic Layer through a Workflow UI by drag-and-drop or directly by code.<br/>
	Note that it is possible to create back-office interfaces automatically from services of the Business Logic Layer. Basically the framework will generate automatically the interfaces from the services of a Business Logic Layer, saving time to the programmer and maximizing his work... However this example is not shown in this video.<br/>
	Note that if the Presentation is directly connected to the Data-Access Layer, it can execute the correspondent queries and objects directly without passing through the Business Logic Layer.<br/>
	Or if it is directly connected with the Data-Base Layer, it can execute sql directly too, but neither of these explanations are in this video.<br/>
	<br/>
	For more info about the Layers Connections please visit the <a href=\"{$online_tutorials_url_prefix}documentation/layers\" target=\"documentation\">Layers</a> section.<br/>",
						"image" => "",
						"video" => "https://www.youtube.com/embed/fDdsI-iMcRk",
					),
					"6_8_templates" => array(
						"title" => "Templates",
						"description" => "Learn how to edit templates.",
						"items" => array(
							"6_8_1_template_installation_and_creation_of_simple_template_with_modules_integration" => array(
								"title" => "Install a Template",
								"description" => "This video explains how to install a template into a project and then related that template with a page and the blocks with the correspondent regions...<br/>
	Note that the purpose of this video is to teach you how to install templates, so we created a very basic template with few HTML tags, very basic css and raw layout.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/bORyhC9Q1l4",
							),
							"6_8_2_creation_of_simple_template_with_modules_integration" => array(
								"title" => "Create a Template",
								"description" => "This video explains how to create a template with regions and then related that template with a page and the blocks with the correspondent regions...<br/>
	The template in this video is a very basic template with few HTML tags, very basic css and raw layout, in order to teach you how to integrate template-regions and modules-blocks into a page.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/NkEFNv_bAX4",
							),
							"6_8_3_creation_of_beautiful_layouts_and_templates" => array(
								"title" => "Create Nice Layouts and Templates",
								"description" => "This video shows how to design nice layouts through our 'Layout UI Editor' and convert them into usable multi-region templates so that they can be used as a page template when creating pages.<br/>
	<img src=\"{$project_url_prefix}img/tutorial/projects.png\"/>
	<br/>
	Note that:
	<ul>
		<li>In this video, you can learn how to design layouts through our \"<a href=\"{$online_tutorials_url_prefix}documentation/layout_ui_editor\" target=\"documentation\">Layout UI Editor</a>\" and convert them into usable multi-region templates so that they can be used as a page template when creating pages.</li>
	</ul>
	<br/>
	For more information about how to create templates please check the \"<a href=\"{$online_tutorials_url_prefix}documentation/templates/creation\" target=\"documentation\">Create New Templates</a>\" tutorial.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/WWkvs_h4dSA",
							),
							"6_8_4_template_generation_with_ai" => array(
								"title" => "How to create a template through AI",
								"description" => "Learn how to create a new template with artificial intelligence by writing what you wish in natural language.<br/>
Note that, in order to take advantage of the Artificial Intelligence features, you must configure your OpenAI key in the 'Manage Permissions/Users' panel.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/UvXCz1SMmUI", //template_generation.webm
							),
						),
					),
					"6_9_wordpress" => array(
						"title" => "WordPress",
						"description" => "Learn how to use WordPress' Templates and Plugins in our framework when creating Pages.",
						"items" => array(
							"6_9_1_installation_of_wordpress_instances" => array(
								"title" => "Install a WordPress instance",
								"description" => "This video teaches how to install WordPress instances so you can integrate the WordPress' features into your pages in the framework. You can learn how to do this in the next lesson...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/KbBYDIKMIpo",
							),
							"6_9_2_creation_of_simple_page_with_wordpress_features" => array(
								"title" => "Create a simple page with some WordPress features",
								"description" => "This video teaches how to create a simple page and integrate some WordPress features in it, like incorporate individual WordPress menus, sidebars, widgets, sub-pages and other contents...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/rNtgD4hCNqs",
							),
							"6_9_3_creation_of_simple_page_with_wordpress_templates" => array(
								"title" => "Create a simple page using WordPress templates",
								"description" => "This video teaches how to create a simple page using WordPress templates and pages and then add new content to these templates...",
								"image" => "",
								"video" => "https://www.youtube.com/embed/_Fhf8OIzb2Y",
							),
						),
					),
				),
			),
			"7_back_office" => array(
				"title" => "Create a full Back-Office automatically",
				"description" => "Learn how to create an application ready to use based in a backoffice to manage a DB schema.<br/>
	<strong>Create beautifull back-offices in 5 minutes.</strong><br/>
	<br/>
	Create a Data-Base diagram and the framework will automatically build all the sql queries, business logic services and back-office layouts to manage your DB schema...<br/>
	<br/>
	Change the created layouts according with your needs by drag and dropping boxes...",
				"items" => array(
					"7_1_creation_of_back_office_without_authentication" => array(
						"title" => "Create a full Back-Office without Authentication",
						"description" => "Create a full back-office without authentication, where everyone can access it. All users have the same permissions and can execute all actions...<br/>
	<br/>This video shows how to create a full admin panel to manage your DB tables based on a schema, this is, based on a Data-Base diagram the framework will build all the necessary sql queries, then the business logic services and for last, the admin layouts, allowing you to manage your Data-Base without programming a single line of code.<br/>
	Create a full back-office without authentication, where everyone can access it. All users have the same permissions and can execute all actions...<br/>
	After this, switch the layouts across 4 different templates or change them directly accordingly with your needs...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/b0125JFb5Ug",
					),
					"7_2_creation_of_back_office_with_authentication" => array(
						"title" => "Create a full Back-Office with Authentication and Permissions",
						"description" => "Create or change a full back-office with user authentication, where users can have different permissions, access different things and execute different actions...<br/>
	<br/>
	This video shows how to create a full admin panel to manage your DB tables based on a schema, this is, based on a Data-Base diagram the framework will build all the necessary sql queries, then the business logic services and for last, the admin layouts, allowing different users to manage your DB schema depending the permissions you set to them...<br/>
	Create or change a full back-office with user authentication, where users can have different permissions, access different things and execute different actions...<br/>
						After this, switch the layouts across 4 different templates or change them directly accordingly with your needs...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/_rvxojrzVZs",
					),
					"7_3_1_change_existent_back_office_layouts" => array(
						"title" => "Change Existent Back-Office Layouts 1",
						"description" => "This video shows how to manage existent layouts by drag and dropping boxes in our Files Diagram UI.<br/>
	As it shows in the video below, you can change the users permissions for it's page, draw lists and forms for specific tables, define which actions the user can do, connect multiple files by drag and dropping, list data from a table which is children or parent of another...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/rx3y8IrL6oE",
					),
					"7_3_2_change_existent_back_office_layouts" => array(
						"title" => "Change Existent Back-Office Layouts 2",
						"description" => "This video shows how to manage existent layouts by drag and dropping boxes in our Files Diagram UI.<br/>
	As it shows in this video, you can change the users permissions for it's page, draw lists and forms for specific tables, define which actions the user can do, connect multiple files by drag and dropping, list data from a table which is children or parent of another...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/3vx-6nL1JFs",
					),
					"7_4_add_db_table_and_back_office_page" => array(
						"title" => "Add DB Table and Back-Ofice Page",
						"description" => "This video teaches how to create a new table in your Data-Base and then create a new page based in our 'Files Diagram UI' via drag-and-drop.<br/>
	We will show how to create different types of Lists and Forms and multiple ways of showing and connect them with other pages...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/DKc5KK7Jp2o",
					),
					"7_5_create_new_backoffice_manually_from_scratch" => array(
						"title" => "Create New Back-Office Manually from scratch",
						"description" => "The videos below teach how to create from scratch new Back-Office's pages, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.<br/>
	Basically we will create 3 new pages:<br/>
	- Index page: which will be the home-page with the links to the other pages.<br/>
	- Schools page: which will contain the list of all schools. In this list you can edit or delete the existent schools or add new ones.<br/>
	- School's Teachers page: which will contain the list of teachers for a specific school. In this list you can edit or delete the existent teachers or add new ones.",
						"image" => "",
						"items" => array(
							"7_5_1_create_new_backoffice_manually_from_scratch" => array(
								"title" => "Create New Back-Office Manually from scratch 1",
								"description" => "This video teaches how to create from scratch new Back-Office's pages, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.<br/>
	Basically we will create 3 new pages:<br/>
	- Index page: which will be the home-page with the links to the other pages.<br/>
	- Schools page: which will contain the list of all schools. In this list you can edit or delete the existent schools or add new ones.<br/>
	- School's Teachers page: which will contain the list of teachers for a specific school. In this list you can edit or delete the existent teachers or add new ones.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/ieaXBMZavDQ",
							),
							"7_5_2_create_new_backoffice_manually_from_scratch" => array(
								"title" => "Create New Back-Office Manually from scratch 2",
								"description" => "This video teaches how to create from scratch new Back-Office's pages, this is, how to create in a few minutes new pages with lists and forms to view and edit Data-Base meta-data.<br/>
	Basically we will create 3 new pages:<br/>
	- Index page: which will be the home-page with the links to the other pages.<br/>
	- Schools page: which will contain the list of all schools. In this list you can edit or delete the existent schools or add new ones.<br/>
	- School's Teachers page: which will contain the list of teachers for a specific school. In this list you can edit or delete the existent teachers or add new ones.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Fu_TC0wD0s4",
							),
						),
					),
				),
			),
			"8_test_units" => array(
				"title" => "Test Units",
				"description" => "This video explains how to create and run test-units.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/-MIrzj_06hM",
			),
			"9_deployment" => array(
				"title" => "Deployment",
				"description" => "This video explains how to deploy a project to a server.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/8uolDRLFkjw",
			),
			"10_extend_framework" => array(
				"title" => "Extend the framework",
				"description" => "Extend the framework according to your needs.",
				"items" => array(
					"10_1_extend_workflow_tasks" => array(
						"title" => "Extend Tasks in the Business Logic Diagrams",
						"description" => "This video explains how to extend Tasks in the Business Logic Diagrams by adding new user-defined Tasks according to the developer's needs.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/wACAKIB-Uqs",
					),
					"10_2_extend_layoutuieditor_widgets" => array(
						"title" => "Extend Html Widgets in the Page Editor",
						"description" => "Learn how to add new Html Widgets in the Page Editor according to your needs.",
						"items" => array(
							"10_2_1_extend_layoutuieditor_widgets" => array(
								"title" => "How to add Html Widgets in the Page Editor",
								"description" => "This video explains how to extend Html Widgets in the Page Editor by adding new user-defined Widgets according to the user's needs.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/Ha-nZi_dqLI",
							),
							"10_2_2_extend_layoutuieditor_widgets" => array(
								"title" => "Add the Bootstrap Modal Dialog as a Html Widget in the Page Editor",
								"description" => "This video explains how to extend Html Widgets in the Page Editor by adding new Widget that prints a html copied from the web. In this video, the created widget prints the html of a Bootstrap Modal Dialog.",
								"image" => "",
								"video" => "https://www.youtube.com/embed/7hlhyzsxa-s",
							),
						),
					),
				),
			),
			"11_integrate_with_laravel_projects" => array(
				"title" => "Integrate Laravel",
				"description" => "This video explains how to install and leverage Laravel inside of Bloxtor, by calling Laravel's Routers, Controllers, Views and SQL Queries inside of Bloxtor projects.<br/>
	Discover how to install multiple Laravel instances inside of Bloxtor and organize all your Laravel projects in one centralized framework.<br/>
	Although is also possible to call external Laravel installations from Bloxtor, through our REST integrators, the purpose of this tutorial, is to show how you can call the Laravel's Routers, Controllers, Views and SQL Queries internally.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/oM3oZFO4Ypc", //integrate_with_laravel_projects.webm
			),
			"12_create_stock_manager" => array(
				"title" => "Create a complete Stock Manager from scratch",
				"description" => "Discover how to build a complete stock management software from scratch - creating database tables, installing templates, and designing the UI.<br/>
				<br/>
This tutorial is composed by the following lessons:
<ul>
	<li>- <a href='https://youtu.be/-iywg0oqrAE?si=MIfBIFwHXr8NWRPC' target='youtube'>Lesson 1: Overviewing the data-model in a spreadsheet</a></li>
	<li>- <a href='https://youtu.be/K8Eqi1DZJpo?si=2zupcCxX9r8nU33y' target='youtube'>Lesson 2: Creating the DB structure</a></li>
	<li>- <a href='https://youtu.be/rOWjshrHFY8?si=tPvpXJo16_ICLKvU' target='youtube'>Lesson 3: Creating user interface automatically for DB structure</a></li>
	<li>- <a href='https://youtu.be/2wyUfzY0ulA?si=ODHKnMraB4f883NR' target='youtube'>Lesson 4: Creating page to manage a purchase and its products</a></li>
	<li>- <a href='https://youtu.be/RXC3lzp3i0k?si=WbT5PrUool422Xvp' target='youtube'>Lesson 5: Changing the purchase editing page with a single form</a></li>
	<li>- <a href='https://youtu.be/WJ5aTZnNN2I?si=zThgBOfu5XxfzdyS' target='youtube'>Lesson 6: Creating a page to manage the products of a sale</a></li>
	<li>- <a href='https://youtu.be/ffsjM1BT3PY?si=2BEGipkeQsYbh3ci' target='youtube'>Lesson 7: Listing product batches and auto-update some fields</a></li>
	<li>- <a href='https://youtu.be/ezV_YF-yMzk?si=wk4MiAuk1xTRgEtx' target='youtube'>Lesson 8: Auto-updating some product fields</a></li>
	<li>- <a href='https://youtu.be/iAcmbX1P8Qw?si=QFbLfSpGuEklH4Oe' target='youtube'>Lesson 9: Creating Notifications</a></li>
	<li>- <a href='https://youtu.be/IpvHfP2__rk?si=SEyoSSJMkxKJohqF' target='youtube'>Lesson 10: Creating reports in spreadsheet format</a></li>
	<li>- <a href='https://youtu.be/k_y1SB72upQ?si=5bMt4kZhztgPV7n7' target='youtube'>Lesson 11: Creating reports in PDF format</a></li>
	<li>- <a href='https://youtu.be/mmsN860z46I?si=0725rg_PiZK8Gwrh' target='youtube'>Lesson 12: Displaying SQL query and download it into a report</a></li>
	<li>- <a href='https://youtu.be/-erI6VM_uqI?si=j5PJB2sjC63QlGSO' target='youtube'>Lesson 13: Adding active/inactive status to products</a></li>
	<li>- <a href='https://youtu.be/4A2vB09P1RM?si=avy_Hl10reA8wg1M' target='youtube'>Lesson 14: Adding authentication to your pages</a></li>
	<li>- <a href='https://youtu.be/FU50lRz8JaQ?si=kZSSp3jgJGVflErS' target='youtube'>Lesson 15: Changing user module to list users with a search bar</a></li>
	<li>- <a href='https://youtu.be/nW2FIdsGRqY?si=yZT3eJ4PILpT-2Qe' target='youtube'>Lesson 16: Showing, hiding and removing elements by conditions</a></li>
	<li>- <a href='https://youtu.be/fBo8I2EDbVw?si=BVXJ74h-7gbkxrJq' target='youtube'>Lesson 17: Allowing multi-level categories within categories</a></li>
</ul>",
				"image" => "",
				"video" => "https://www.youtube.com/embed/videoseries?si=_vOXV94CaJX_Q3qN&amp;list=PLqnlfrS34juvmf4eueUMN1Av5QpWCK2wO", //create_stock_manager.webm
				"pages" => array("admin/index", "admin/admin_home", "admin/admin_home_project", "admin/choose_available_project"), //this is used in the TourGuideUIHandler
			),
			
			/*"install_final_app" => array(
				"title" => "Install a final application",
				"description" => "This video explains how you can execute a step by step installation of the framework, but you only need to execute the following steps if you downloaded the raw version of the framework.<br/>
				Note: We configured the 'localhost:81' virtual host to point to the root folder of the framework, but this is optional...",
				"image" => "",
				"video" => "https://www.youtube.com/embed/ePEKhVf4qUo",
			),
			"create_free_static_app" => array(
				"title" => "Create free static application with html editor",
				"description" => "Create a static page based in a wyswyg editor without the need of download and select a template.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_template_static_app" => array(
				"title" => "Create static application with a template",
				"description" => "",
				"items" => array(
					array(
						"title" => "Download and install a template",
						"description" => "Download a template from our site, install it and start using it...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create static page with html editor",
						"description" => "Create static page based in a wyswyg editor and include it in the installed template.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create static page with authentication automatically",
						"description" => "Create static page with private access, this is, where the user must login to see it. To do this, you will use a visual interface called 'Files Diagram' that will help you to do this very quickly.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create static page with authentication automatically - method 2",
						"description" => "Create static page with private access, this is, where the user must login to see it. To do this, you will download the 'Auth' app in our site and install it. Then you will only need to relate the correspondent blocks to your static page.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create static page and authentication manually",
						"description" => "Create static page with private access, this is, where the user must login to see it. To do this, you will create manually the login and logout pages, through the 'User Module'.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create static page with authentication and users management panels manually",
						"description" => "Create static page with private access, this is, where the user must login to see it. To do this, you will create manually the login, logout, register, edit and manage users pages, through the 'User Module'.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_static_app_with_menus" => array(
				"title" => "Create static application with a menu",
				"description" => "Create a menu to multiple dummy pages",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_articles_app" => array(
				"title" => "Create an application with Articles",
				"description" => "like a blog or an online newspaper",
				"items" => array(
					array(
						"title" => "Create an app with articles catalog",
						"description" => "Create a simple page which lists a group of articles.<br/>You will use the 'Article Module admin panel' to create and edit articles.", //articles catalog
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a static blog",
						"description" => "Create an app with a menu and articles by menu, this is, create a menu that shows a specific list of articles.<br/>You will use the 'Article Module admin panels' to create and edit articles.", //articles catalog with menus
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a dynamic blog",
						"description" => "Create an app with articles catalogs by menu and a restricted access area where you can create and edit articles and menus.", //articles catalog with menus and admin pages
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_events_app" => array(
				"title" => "Create an application with Events",
				"description" => "Create an app to share events or advertisments.",
				"items" => array(
					array(
						"title" => "Create an app with events catalog",
						"description" => "Create a simple page which lists a group of events.<br/>You will use the 'Event Module admin panel' to create and edit events.", //events catalog
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create an app with a menu and events by menu",
						"description" => "Create a menu that shows a specific list of events.<br/>You will use the 'Event Module admin panels' to create and edit events.", //events catalog with menus
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a dynamic app to show and manage your events",
						"description" => "Create an app with events catalogs by menu and a restricted access area where you can create and edit events and menus.", //events catalog with menus and admin pages
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_quiz_app" => array(
				"title" => "Create an application with Quizzes",
				"description" => "Create an app to share quizzes or polls.",
				"items" => array(
					array(
						"title" => "Create an app with a quiz",
						"description" => "Create a simple page which lists questions and answers.<br/>You will use the 'Quiz Module admin panel' to create and edit questions.", //questions and answers
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create an app with a menu and quizzes by menu",
						"description" => "Create a menu that shows a specific list of questions.<br/>You will use the 'Quiz Module admin panels' to create and edit questions.", //quizzes with menus
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a dynamic app to show and manage your quizzes",
						"description" => "Create an app with quizzes by menu and a restricted access area where you can create and edit questions and menus.", //quizzes with menus and admin pages
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_chat_app" => array(
				"title" => "Create an app with a chat",
				"description" => "Chat with the users from your application.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_task_manager_app" => array(
				"title" => "Create task manager app",
				"description" => "Manage your tasks and assign others to your team members.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_sendemail_app" => array(
				"title" => "Create an app with a form to sendemail",
				"description" => "Create a form page so the users can ask you a question through email.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_app_with_own_db" => array(
				"title" => "Create an app with your own Data-base",
				"description" => "Create your data-base model and pages to manage the correspondent table's records.",
				"items" => array(
					array(
						"title" => "Edit DB Diagram, add new records in the new tables and list records in a page",
						"description" => "Create your data-base model in our db-diagram editor, manage the tables you created, inserting new records, and then create a page with a list of a table records and another table with view each record.", //by drag and dropping items in the edit_entity_simple
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Based on a previous DB diagram, create multiple pages with the 'Files Diagram'",
						"description" => "Create multiple pages in a visual enviroment to manage a DB model.", //from the files diagram
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Check the created files from the 'Files Diagram' feature",
						"description" => "When you create pages from 'Files Diagram', it will create a bunch of files based the 'Form Module'. We will check and explain these files.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_backoffice_automatically" => array(
				"title" => "Create a backoffice app",
				"description" => "",
				"items" => array(
					array(
						"title" => "Create a backoffice app based in a dumy Data-base model",
						"description" => "Create an app to manage automatically your Data-base tables.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Check the automatically created files",
						"description" => "When you create pages automatically, the system will create a bunch of files based the 'Form Module' and others. We will check and explain these files.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a backoffice app based in a Car-Workshop Data-base model",
						"description" => "Create an app to manage a car-workshop business. Change it as you like with the 'Files Diagram' feature.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_new_template" => array(
				"title" => "Create your own template",
				"description" => "Through our template editor you will learn how to create and modify templates.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_page_with_wordpress_module" => array(
				"title" => "Create a page with the 'Wordpress Module'",
				"description" => "Create a page based in Wordpress blocks, this is, use Wordpress to do what you need and then include what you did in our pages.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_and_schedule_action" => array(
				"title" => "Create and Schedule an action",
				"description" => "Create some business logic and then schedule it to execute everyday. Example: send a reminder email if some condition is true.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"form_module" => array(
				"title" => "The 'Form Module'",
				"description" => "Learn how to work with the 'Form Module' and what you do with it.",
				"items" => array(
					array(
						"title" => "Creates a simple form and save it to DB",
						"description" => "Create a page with a simple form and save it to the DB", //Use a simple form with 2 fields and save them to a new DB table. User input data should be validaded in php too.
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Based on a previous DB diagram, create a page with a table's report",
						"description" => "Create a page which creates a report with the list of records from a DB's table.", //do this manually
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Use the Wizard to list some records",
						"description" => "Create a page that gets the records from DB's table and shows them.", //use the Form wizard and then change some logic
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Convert Form blocks into logic workflow",
						"description" => "Based in a created block from the 'Form Module', convert it in a workflow", //this workflow is the workflow inside of the Form Module.
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_page_with_low_code" => array(
				"title" => "Create a page with low-code",
				"description" => "Go to the advanced pages editor and create your own logic based in Low-Code workflows.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_ibatis_query_and_list_results" => array(
				"title" => "Create a page which list the results from a new SQL query. We will show you how to create SQL queries very easily and through our visual interface.",
				"description" => "",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_hibernate_obj_and_list_results" => array(
				"title" => "Create a page which list the results from a new Hibernate object. We will show you how to create Hibernate objects very easily and through our visual interface and then use its inherit methods.",
				"description" => "",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_business_logic_services_and_list_results" => array(
				"title" => "Create a page which list the results from a new Business Logic service. We will show you how to create Business Logic services very easily and through low-code flows.",
				"description" => "",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"manage_db" => array(
				"title" => "Manage your Data-base",
				"description" => "",
				"items" => array(
					array(
						"title" => "Create, modify and delete tables",
						"description" => "Learn how to add new tables, edit and remove existent ones, modify a table's attributes...",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Edit the DB Diagram and execute it in the DB",
						"description" => "Learn how to create a new DB model and execute it into the DB, or to reverse engineering the current DB model.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Manage a table records",
						"description" => "Insert, modify and remove the tables'records with our visual interface.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"create_page_with_translation" => array(
				"title" => "Create a multi-language page",
				"description" => "Create a multi-language page, where user can choose a language and the page content gets translated.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"template" => array(
				"title" => "Templates",
				"description" => "",
				"items" => array(
					array(
						"title" => "Install new Template",
						"description" => "Download and install a template",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a new Template",
						"description" => "Learn how to create templates, assign regions and params",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Convert any site into a Template",
						"description" => "Learn how to convert any site in a template.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),
			"install_module" => array(
				"title" => "Install new Module",
				"description" => "Learn how to download and install a module.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"manage_other_files" => array(
				"title" => "What are the 'Other' files",
				"description" => "Copy any document or file related with your app so all documentation can be centralized.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"deployment" => array(
				"title" => "Deploy an app to a server",
				"description" => "Learn how to export an app to a server.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"test_units" => array(
				"title" => "Create and execute test-units",
				"description" => "Learn how to create a test-unit and then execute it.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"create_new_layer" => array(
				"title" => "Create new Layer",
				"description" => "Learn how manage your layers according with your needs.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"manage_users" => array(
				"title" => "Manage users and permissions",
				"description" => "Restrict access to some parts of the tool, only allowing specific users to access it.",
				"image" => "",
				"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
			),
			"extend_tool" => array(
				"title" => "Extend tool",
				"description" => "Learn how to extend the tool according with your needs",
				"items" => array(
					array(
						"title" => "Import external libraries",
						"description" => "Extend the tool's features by importing external libraries into it.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Import thrid-party applications",
						"description" => "Upload any PHP third-party app into the tool.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a Module",
						"description" => "Learn how to creaet a new module.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a new task to show in the Low-code flows editor",
						"description" => "Learn how to creaet a new module.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
					array(
						"title" => "Create a new widget to show in the Layout UI Editor",
						"description" => "Learn how to creaet a new module.",
						"image" => "",
						"video" => "https://www.youtube.com/embed/Cp5IhDRiWQQ",
					),
				),
			),*/
		);
	}
}
?>
