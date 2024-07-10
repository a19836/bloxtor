<div class="inlinehtml_task_html">
	<ul>
		<li id="inlinehtml_code_editor_tab"><a href="#inlinehtml_code" onClick="InlineHTMLTaskPropertyObj.updateHtmlFromWysiwygEditor(this)">Code</a></li>
		<li id="inlinehtml_wysiwyg_editor_tab"><a href="#inlinehtml_wysiwyg" onClick="InlineHTMLTaskPropertyObj.updateHtmlFromCodeEditor(this)">WYSIWYG</a></li>
	</ul>
	
	<div id="inlinehtml_code">
		<textarea></textarea>
	</div>
	
	<div id="inlinehtml_wysiwyg">
		<textarea></textarea>
	</div>
	
	<!-- MY LAYOUT UI EDITOR -->
	<div class="layout-ui-editor reverse fixed-properties hide-template-widgets-options with_top_bar_menu">
		<ul class="menu-widgets hidden"></ul><!--  Menu widgets will be added later -->
		<div class="template-source"><textarea></textarea></div>
	</div>
	
	<textarea class="task_property_field" name="code" style="display:none"></textarea>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
