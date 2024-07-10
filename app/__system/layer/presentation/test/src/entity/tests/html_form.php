<?php
//http://localhost/springloops/phpframework/trunk/tests/html_form
//http://jplpinto.localhost/__system/test/tests/html_form

//Html Form Test
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");

$url_prefix = "http://jplpinto.com/";

$form_settings = array(
	//"ptl" => 1,
	"ptl" => array(
		"input_data_var_name" => "my_data", 
		"idx_var_name" => "j"
	),
	"with_form" => 1,
	"form_id" => "",
	"form_method" => "post",
	"form_class" => "",
	"form_type" => "",
	"form_on_submit" => "",
	"form_action" => "",
	"form_containers" => array(
		0 => array(
			"container" => array(
				"class" => "show_featured_objects",
				"href" => "",
				"target" => "",
				"title" => "",
				"title_position" => "auto",
				"previous_html" => "",
				"next_html" => "",
				"elements" => array(
					0 => array(
						"tree" => array(
							"ordered" => 0,
							"recursive" => 1,
							"tree_class" => "",
							"lis_class" => "card card-shadow m-b-0 #[\$idx][tags]#",
							"default_input_data" => "",
							"recursive_input_data" => "#sub_items#",
							"elements" => array(
								0 => array(
									"field" => array(
										"class" => "card-img-top img-fluid w-full h-150 img-cover image no_image",
										"label" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										),
										"input" => array(
											"type" => "image",
											"name" => "",
											"class" => "",
											"value" => "",
											"place_holder" => "",
											"href" => "",
											"target" => "",
											"src" => "#[\$idx][attachments][3007085144][0][url]#",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => "<script>\$(\".show_featured_objects .image img[src='']\").remove();</script>",
											"extra_attributes" => array(
												0 => array(
													"name" => "alt",
													"value" => "No Photo"
												),
												1 => array(
													"name" => "onError",
													"value" => "\$(this).remove()"
												)
											),
											"confirmation" => "",
											"confirmation_message" => "",
											"allow_null" => 1,
											"validation_label" => "",
											"validation_message" => "",
											"validation_type" => "",
											"validation_regex" => "",
											"min_length" => "",
											"max_length" => "",
											"min_value" => "",
											"max_value" => "",
											"min_words" => "",
											"max_words" => ""
										),
										"help" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										)
									)
								),
								1 => array(
									"field" => array(
										"class" => "card-title text-truncate-xs text-primary title",
										"label" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										),
										"input" => array(
											"type" => "link",
											"name" => "",
											"class" => "",
											"value" => "#[\$idx][object][title]#",
											"place_holder" => "",
											"href" => "$url_prefix#[\$idx][object][link]#",
											"target" => "",
											"src" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => "",
											"confirmation" => "",
											"confirmation_message" => "",
											"allow_null" => 1,
											"validation_label" => "",
											"validation_message" => "",
											"validation_type" => "",
											"validation_regex" => "",
											"min_length" => "",
											"max_length" => "",
											"min_value" => "",
											"max_value" => "",
											"min_words" => "",
											"max_words" => ""
										),
										"help" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										)
									)
								),
								2 => array(
									"field" => array(
										"class" => "card-text text-truncate-sm summary",
										"label" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										),
										"input" => array(
											"type" => "label",
											"name" => "",
											"class" => "",
											"value" => "#[\$idx][object][summary]#",
											"place_holder" => "",
											"href" => "",
											"target" => "",
											"src" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => "",
											"confirmation" => "",
											"confirmation_message" => "",
											"allow_null" => 1,
											"validation_label" => "",
											"validation_message" => "",
											"validation_type" => "",
											"validation_regex" => "",
											"min_length" => "",
											"max_length" => "",
											"min_value" => "",
											"max_value" => "",
											"min_words" => "",
											"max_words" => ""
										),
										"help" => array(
											"value" => "",
											"class" => "",
											"title" => "",
											"title_position" => "",
											"width" => "",
											"height" => "",
											"offset" => "",
											"previous_html" => "",
											"next_html" => ""
										)
									)
								),
								3 => array(
									"tree" => array(
										"ordered" => 0,
										"recursive" => 0,
										"tree_class" => "tree_#[0][attachment_id]#",
										"lis_class" => "sub_tree attachment_#[\$idx][attachment_id]#",
										"default_input_data" => "#[attachments][3007085144]#",
										"elements" => array(
											0 => array(
												"field" => array(
													"label" => array(
														"value" => "Image name: ",
													),
													"input" => array(
														"type" => "text",
														"value" => "#[\$idx][name]#",
													),
												)
											),
										)
									)
								),
								4 => array(
									"tree" => array(
										"ordered" => 0,
										"recursive" => 0,
										"tree_class" => "tree_#[0][type]#",
										"lis_class" => "sub_tree attachment_#[\$idx][attachment_id]#",
										"default_input_data" => "#[attachments][3007085144]#",
										"elements" => array(
											0 => array(
												"field" => array(
													"label" => array(
														"value" => "Image type: ",
													),
													"input" => array(
														"type" => "text",
														"value" => "#[\$idx][type]#",
													),
												)
											),
										)
									)
								),
								5 => array(
									"table" => array(
										"table_class" => "table_#[0][type]#",
										"rows_class" => "row row_#[0][type]# attachment_#[\$idx][attachment_id]#",
										"default_input_data" => "#[attachments][3007085144]#",
										"elements" => array(
											0 => array(
												"field" => array(
													"href" => "#",
													"label" => array(
														"value" => "Image type",
													),
													"input" => array(
														"type" => "link",
														"value" => "#[\$idx][name]#",
													),
												)
											),
											1 => array(
												"table" => array(
													"table_class" => "table_#[0][target]#",
													"rows_class" => "row row_#[0][title]#",
													"default_input_data" => "#[other_urls]#",
													"label" => array(
														"value" => "SUB TABLE",
													),
													"elements" => array(
														0 => array(
															"field" => array(
																"href" => "#",
																"label" => array(
																	"value" => "URL",
																),
																"input" => array(
																	"type" => "link",
																	"value" => "#[\$idx][url]#",
																),
															)
														),
													)
												)
											),
										)
									)
								),
							)
						)
					)
				)
			)
		)
	),
	"form_css" => "some css here",
	"form_js" => "some script here"
);

/*
select og.*, z.`group`, z.`order`, z.tag_group, z.tag_order, z.tags_count 
from mog_objects_group og 
inner join (
select og.objects_group_id, oog.`group` `group`, oog.`order` `order`, ot.`group` tag_group, ot.`order` tag_order, count(t.tag) tags_count
from mog_objects_group og
inner join mog_object_objects_group oog on oog.objects_group_id=og.objects_group_id and oog.object_type_id=1001 and oog.object_id=5 and oog.`group`=0
inner join mt_object_tag ot on ot.object_type_id=4 and ot.object_id=og.objects_group_id
inner join mt_tag t on t.tag_id=ot.tag_id and t.tag in ('Destaques')
group by og.objects_group_id, oog.`group`, oog.`order`, ot.`group`, ot.`order` having count(t.tag) >= 1
) z on z.objects_group_id=og.objects_group_id;
*/
$input_data = array(
	array(
		"objects_group_id" => "6",
		"object" => array(
			"title" => "Crie e divulgue os seus eventos",
			"summary" => "<p>Utilize esta funcionalidade e seja um facilitador de experi&ecirc;ncias para os seus vizinhos</p>",
			"link" => "/condo/private/tutorial/tutorial_properties?article_id=150",
		),
		"created_date" => "2017-04-24 11:56:30",
		"modified_date" => "2017-04-24 11:56:30",
		"group" => "0",
		"order" => "0",
		"tag_group" => "0",
		"tag_order" => "0",
		"tags_count" => "1",
		"tags" => "destaques",
		"attachments" => array(
			"3007085144" => array(
				array(
					"attachment_id" => "520",
					"name" => "featured_gim.jpeg",
					"type" => "image/jpeg",
					"size" => "38452",
					"path" => "e5202995/cf6/735/5a3/attachment_520_58fde7ee95444",
					"created_date" => "2017-04-24 11:56:30",
					"modified_date" => "2017-04-24 11:56:30",
					"object_type_id" => "4",
					"object_id" => "6",
					"group" => "3007085144",
					"order" => "0",
					"absolute_path" => "/var/www/html/livingroop/default/files//e5202995/cf6/735/5a3/attachment_520_58fde7ee95444",
					"url" => "http://jplpinto.localhost/condo/read_attachment?path=e5202995/cf6/735/5a3/attachment_520_58fde7ee95444",
					"other_urls" => array(
						array("url" => "xxx", "title" => "XXX", "target" => "_blank"),
						array("url" => "yyy", "title" => "YYY", "target" => ""),
					),
				),
			),
		),
		"sub_items" => array(),
	),
	array(
		"objects_group_id" => "7",
		"object" => array(
			"title" => "Gerir a sua fracção é simples",
			"summary" => "<p>Como cond&oacute;mino pode gerir os utilizadores da sua frac&ccedil;&atilde;o. Veja como!</p>",
			"link" => "/condo/private/tutorial/tutorial_properties?article_id=180",
		),
		"created_date" => "2017-04-24 11:56:30",
		"modified_date" => "2017-04-24 11:56:30",
		"group" => "0",
		"order" => "0",
		"tag_group" => "0",
		"tag_order" => "0",
		"tags_count" => "1",
		"tags" => "destaques",
		"attachments" => array(
			"3007085144" => array(
				array(
					"attachment_id" => "521",
					"name" => "featured_dinner.png",
					"type" => "image/png",
					"size" => "336182",
					"path" => "e5202995/075/63a/3fe/attachment_521_58fde7ef24247",
					"created_date" => "2017-04-24 11:56:31",
					"modified_date" => "2017-04-24 11:56:31",
					"object_type_id" => "4",
					"object_id" => "7",
					"group" => "3007085144",
					"order" => "0",
					"absolute_path" => "/var/www/html/livingroop/default/files//e5202995/075/63a/3fe/attachment_521_58fde7ef24247",
					"url" => "http://jplpinto.localhost/condo/read_attachment?path=e5202995/075/63a/3fe/attachment_521_58fde7ef24247",
				),
			),
		),
		"sub_items" => array(),
	),
	array(
		"objects_group_id" => "8",
		"object" => array(
			"title" => "Comunique com o condomínio.",
			"summary" => "<p>Use o Chat. &Eacute; muito mais pr&aacute;tico e comunica a qualquer hora por computador, tablet ou telem&oacute;vel.</p>",
			"link" => "",
		),
		"created_date" => "2017-04-24 11:56:32",
		"modified_date" => "2017-04-24 11:56:32",
		"group" => "0",
		"order" => "0",
		"tag_group" => "0",
		"tag_order" => "0",
		"tags_count" => "1",
		"tags" => "destaques",
		"attachments" => array(
			"3007085144" => array(
				array(
					"attachment_id" => "522",
					"name" => "featured_family.jpeg",
					"type" => "image/jpeg",
					"size" => "29894",
					"path" => "e5202995/53f/de9/6fc/attachment_522_58fde7f038e51",
					"created_date" => "2017-04-24 11:56:32",
					"modified_date" => "2017-04-24 11:56:32",
					"object_type_id" => "4",
					"object_id" => "8",
					"group" => "3007085144",
					"order" => "0",
					"absolute_path" => "/var/www/html/livingroop/default/files//e5202995/53f/de9/6fc/attachment_522_58fde7f038e51",
					"url" => "http://jplpinto.localhost/condo/read_attachment?path=e5202995/53f/de9/6fc/attachment_522_58fde7f038e51",
				),
			),
		),
		"sub_items" => array(),
	),
	array(
		"objects_group_id" => "9",
		"object" => array(
			"title" => "Assembleias Gerais Electrónicas",
			"summary" => "<p>Comece a preparar o condom&iacute;nio. Veja como e as vantagens que traz.</p>",
			"link" => "/condo/private/article/channel_articles?tag=Assembleias Electrónicas&tag_name=Assembleias Electrónicas",
		),
		"created_date" => "2017-04-24 11:56:32",
		"modified_date" => "2017-04-24 11:56:32",
		"group" => "0",
		"order" => "0",
		"tag_group" => "0",
		"tag_order" => "0",
		"tags_count" => "1",
		"tags" => "destaques",
		"attachments" => array(
			"3007085144" => array(
				array(
					"attachment_id" => "523",
					"name" => "featured_tech.jpeg",
					"type" => "image/jpeg",
					"size" => "23033",
					"path" => "e5202995/2bb/232/c0b/attachment_523_58fde7f098897",
					"created_date" => "2017-04-24 11:56:32",
					"modified_date" => "2017-04-24 11:56:32",
					"object_type_id" => "4",
					"object_id" => "9",
					"group" => "3007085144",
					"order" => "0",
					"absolute_path" => "/var/www/html/livingroop/default/files//e5202995/2bb/232/c0b/attachment_523_58fde7f098897",
					"url" => "http://jplpinto.localhost/condo/read_attachment?path=e5202995/2bb/232/c0b/attachment_523_58fde7f098897",
				),
			),
		),
		"sub_items" => array(),
	),
);

//$form_settings["ptl"] = false;
$html = HtmlFormHandler::createHtmlForm($form_settings, $input_data);

if (!empty($form_settings["ptl"])) {
	echo "<!--";
	echo "********************* TEMPLATE ***************************\n";
	$HtmlFormHandler = new HtmlFormHandler($form_settings);
	$template = $HtmlFormHandler->getHtmlForm($input_data);
	echo $template;
	echo "\n\n********************* CODE ***************************\n";
	$PHPTemplateLanguage = new PHPTemplateLanguage();
	echo $PHPTemplateLanguage->getTemplateCode($template);
	echo "\n\n********************* HTML ***************************\n";
	echo "-->";
	echo $html;
}
else
	echo $html;

die();

?>
