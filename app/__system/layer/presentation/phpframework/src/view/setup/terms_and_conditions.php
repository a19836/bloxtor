<?php
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<script>
	function selectDependenciesInstallation(elm) {
		var btn = $("#setup .buttons .ok")[0];
		
		if (elm.checked)
			btn.value = "Continue with dependencies";
		else
			btn.value = "Continue without dependencies";
	}
	
	function continueSetup() {
		if ($("#terms_and_conditions .acceptance input").is(":checked") && $("#terms_and_conditions .dependencies input").is(":checked")) {
			var btn = $("#setup .buttons .ok")[0];
			btn.value = "Downloading and installing dependencies...";
			btn.setAttribute("disabled", "disabled");
		}
		
		$("#terms_and_conditions form").submit();
	}
</script>';

$main_content = '<div id="terms_and_conditions">
	<form method="post" onSubmit="return MyJSLib.FormHandler.formCheck(this);">
		<div class="title">
			<h1>Terms and Conditions</h1>
		</div>
		<iframe class="license" src="' . $project_url_prefix . 'license"></iframe>
		<div class="acceptance">
			<label>
				<input type="checkbox" name="acceptance" value="1" allownull="false" validationmessage="Please accept the terms and conditions first." required ' . (!empty($_POST["acceptance"]) ? "checked" : "") . ' />
				Please accept the terms and conditions.
			</label>
		</div>
		<div class="dependencies">
			<label>
				<input type="checkbox" name="dependencies" value="1" checked onChange="selectDependenciesInstallation(this)" />
				Select this option if you want to install third-party libraries to get full functionality.
			</label>
		</div>
	</form>
</div>
<style> #setup .buttons .cancel {display:none;} </style>
<script>selectDependenciesInstallation( $("#terms_and_conditions .dependencies input")[0] );</script>';

$continue_function = "continueSetup()";
?>
