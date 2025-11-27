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

var CodeTaskPropertyObj = {
	editor: null,
	
	onLoadTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.createTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".code_task_html");
		
		if (CodeTaskPropertyObj.existsPretifyCodeClass())
			task_html_elm.find(".pretify").show();
		else
			task_html_elm.find(".pretify").hide();
		
		var code = task_property_values["code"];
		code = CodeTaskPropertyObj.prepareCodeToShow(code);
		
		var textarea = task_html_elm.find(".code textarea").first();
		textarea.val(code);
		CodeTaskPropertyObj.createEditor(textarea[0]);
	},
	
	onSubmitTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		ProgrammingTaskUtil.saveTaskLabelField(properties_html_elm, task_id);
		
		var task_html_elm = $(properties_html_elm).find(".code_task_html");
		var code = CodeTaskPropertyObj.getEditorCode(task_html_elm);
		code = CodeTaskPropertyObj.prepareCodeToSave(code);
		
		task_html_elm.find("textarea.task_property_field").first().val(code);
		
		return true;
	},
	
	onCompleteTaskProperties : function(properties_html_elm, task_id, task_property_values, status) {
		if (status) {
			var label = CodeTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		}
	},
	
	onCancelTaskProperties : function(properties_html_elm, task_id, task_property_values) {
		return true;	
	},
	
	onCompleteLabel : function(task_id) {
		return ProgrammingTaskUtil.onEditLabel(task_id);
	},
	
	onTaskCreation : function(task_id) {
		setTimeout(function() {
			var task_property_values = myWFObj.getTaskFlowChart().TaskFlow.tasks_properties[task_id];
			var label = CodeTaskPropertyObj.getDefaultExitLabel(task_property_values);
			ProgrammingTaskUtil.updateTaskDefaultExitLabel(task_id, label);
		
			onEditLabel(task_id);
			
			ProgrammingTaskUtil.onTaskCreation(task_id);
		}, 80);
	},
	
	getDefaultExitLabel : function(task_property_values) {
		var code = task_property_values["code"] ? task_property_values["code"] : "";
		code = this.prepareCodeToShow(code);
		return ProgrammingTaskUtil.getValueString( code.substr(0, 100) );
	},
	
	prepareCodeToShow : function(code) {
		code = code && typeof code != "undefined" ? code.trim() : "";
		code = "<?php\n" + code + "\n?>";
		
		if (code != "") {
			while(code.indexOf("<?php\n?>") != -1) 
				code = code.replace("<?php\n?>", "");
			
			code = code.trim();
		}
		
		if (code == "")
			code = "<?php\n\n?>";
		
		return code;
	},
	
	prepareCodeToSave : function(code) {
		code = code ? code.trim() : "";
		
		if (code != "") {
			if (code.substr(0, 2) == "<?")
				code = code.substr(0, 5) == "<?php" ? code.substr(5) : (code.substr(0, 2) == "<?" ? code.substr(2) : code);
			else
				code = "?>\n" + code;
			
			if (code.substr(code.length - 2) == "?>")
				code = code.substr(0, code.length - 2);
			
			else if (code.lastIndexOf("<?") < code.lastIndexOf("?>")) //this means that exists html elements at the end of the file
				code += "\n<?php";
			
			while(code.indexOf("<?php\n?>") != -1) 
				code = code.replace("<?php\n?>", "");
			
			code = code.trim();
		}
		
		return code;
	},
	
	existsPretifyCodeClass : function() {
		return typeof MyHtmlBeautify != "undefined" && typeof MyHtmlBeautify.beautify == "function";
	},
	
	pretifyCode : function(elm) {
		if (this.existsPretifyCodeClass()) {
			var task_html_elm = $(elm).parent().closest(".code_task_html");
			var code = this.getEditorCode(task_html_elm);
			code = MyHtmlBeautify.beautify(code);
			this.setEditorCode(task_html_elm, code);
		}
	},
	
	getEditorCode : function(task_html_elm) {
		var code = "";
		
		if (CodeTaskPropertyObj.editor)
			code = CodeTaskPropertyObj.editor.getValue();
		else
			code = task_html_elm.find(".code textarea").first().val();
		
		return code;
	},
	
	setEditorCode : function(task_html_elm, code) {
		if (CodeTaskPropertyObj.editor)
			CodeTaskPropertyObj.editor.setValue(code);
		else
			task_html_elm.find(".code textarea").first().val(code);
	},
	
	createEditor : function(textarea) {
		if (ace && ace.edit && textarea) {
			var parent = $(textarea).parent();
			
			ace.require("ace/ext/language_tools");
			var editor = ace.edit(textarea);
			editor.setTheme("ace/theme/chrome");
			editor.session.setMode("ace/mode/php");
			editor.setAutoScrollEditorIntoView(true);
			editor.setOption("minLines", 30);
			editor.setOptions({
				enableBasicAutocompletion: true,
				enableSnippets: true,
				enableLiveAutocompletion: false,
			});
		
			CodeTaskPropertyObj.editor = editor;
			
			parent.find("textarea.ace_text-input").removeClass("ace_text-input"); //fixing problem with scroll up, where when focused or pressed key inside editor the page scrolls to top
			
			editor.focus();
		}
	},
};
