$(function () {
	var wh = $(window).height();
	var h = wh - 155;
	h = h < 300 ? 300 : h;
	
	var sql_results = $(".sql_results");
	sql_results.css("height", h + "px");
	
	/*sql_results.find("table").first().DataTable({
		"scrollY": (h - 70) + "px",
		"scrollCollapse": true,
		"scrollX": false,
		"lengthMenu": [[50, 100, 200, 500, -1], [50, 100, 200, 500, "All"]],
	});

	sql_results.find(".dataTables_scrollHead, .dataTables_scrollHead thead tr").each(function(idx, elm){
		$(elm).addClass("table_header");
	});*/
});

function execute() {
	var editor = $(".sql_text_area").data("editor");
	var sql = editor.getValue();

	$("#main_column").append('<form id="form_sql" method="post" style="display:none"><textarea name="sql"></textarea></form>');
	$("#form_sql textarea").val(sql);
	$("#form_sql")[0].submit();
}

function createSQLEditor() {
	var sql_text_area = $(".sql_text_area");
	var textarea = sql_text_area.children("textarea")[0];
	
	ace.require("ace/ext/language_tools");
	var editor = ace.edit(textarea);
	editor.setTheme("ace/theme/chrome");
	editor.session.setMode("ace/mode/sql");
    	editor.setAutoScrollEditorIntoView(true);
	editor.setOption("minLines", 30);
	editor.setOptions({
		enableBasicAutocompletion: true,
		enableSnippets: true,
		enableLiveAutocompletion: false,
	});
	
	sql_text_area.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top.
	
	sql_text_area.data("editor", editor);
}
