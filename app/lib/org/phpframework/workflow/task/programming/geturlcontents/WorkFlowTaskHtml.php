<?php
$dependent_file_path_to_include = !empty($dependent_file_path_to_include) ? $dependent_file_path_to_include : "lib/org/phpframework/util/web/MyCurl.php";
$dependent_file_path_to_include_js = !empty($dependent_file_path_to_include_js) ? $dependent_file_path_to_include_js : "GetUrlContentsTaskPropertyObj.dependent_file_path_to_include";
?>
<div class="get_url_contents_task_html">
	<div class="info">
		This can be used to connect with <b>webhooks</b> in IPAAS services, like: 
		<a href="https://zapier.com/page/webhooks/" target="zapier">Zapier</a>, 
		<a href="https://tray.io/solutions/webhooks" target="tray.io">Tray.io</a>, 
		<a href="https://apiant.com/connections/Webhook" target="apiant">Apiant</a>, 
		<a href="https://www.zoho.com/developer/help/extensions/automation/webhooks.html" target="zoho">Zoho</a>, 
		<a href="https://www.elastic.io/connectors/webhook-integration/" target="elastic">Elastic</a>, 
		<a href="https://docs.workato.com/connectors/workato-webhooks.html" target="workato">Workato</a>, 
		<a href="https://ifttt.com/maker_webhooks" target="ifttt">IFTTT</a>, 
		<a href="https://www.integromat.com/en/integrations/gateway/http" target="integromat">Integromat</a>, 
		<a href="https://blogs.mulesoft.com/dev-guides/how-to-tutorials/webhooks-integration-fun-with-mule/" target="mulesoft">Mulesoft</a>, 
		<a href="https://docs.celigo.com/hc/en-us/articles/360015827372-Create-webhook-listeners" target="celigo">Celigo</a>, 
		<a href="https://www.appypie.com/connect/apps/webhooks-by-connect/integrations" target="appypie">AppyPie</a>, 
		<a href="https://help.talend.com/r/6SB6Qfc014RWM4mEltupHA/5SzrIShpW6sCuQXlekpBNQ" target="talend">Talend</a>, 
		<a href="https://integrately.com/store/webhook" target="integrately">Integrately</a>, 
		<a href="https://www.torocloud.com/martini" target="torocloud">Torocloud Martini</a>, 
		<a href="https://automate.io/integration/webhooks" target="automate">Automate.io</a>, 
		<a href="https://www.ibm.com/support/knowledgecenter/SSTTDS_cloud/com.ibm.appconnect.dev.doc/how-to-guides-for-apps/configure-marketo-webook.html" target="ibm">IBM</a>, 
		<a href="https://panoply.io/integrations/snaplogic/webhooks/" target="panoply">Panoply</a>, 
		<a href="https://cyclr.com/integrate/generic-webhook" target="cyclr">Cyclr</a>, 
		<a href="https://www.mydbsync.com/product/cloud-workflow" target="mydbsync">MyDBSync</a>, 
		<a href="https://www.blendo.co/documents/incoming-webhook-integration/" target="blendo">Blendo</a> 
		and others more...
	</div>
	
	<div class="info">This task needs the file '<?php echo $dependent_file_path_to_include; ?>' to be included before! If is not included yet, please add it by clicking <a class="include_file_before" href="javascript:void(0)" onClick="ProgrammingTaskUtil.addIncludeFileTaskBeforeTaskFromSelectedTaskProperties(<?php echo $dependent_file_path_to_include_js; ?>, '', 1)">here</a>. 
	</div>
	
	<div class="dts">
		<label>Data:</label>
		<input type="text" class="task_property_field data_code" name="data" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field data_type" name="data_type" onChange="GetUrlContentsTaskPropertyObj.onChangeDataType(this, 'data')">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>array</option>
		</select>
		<div class="data array_items"></div>
		<div class="info">
			Note that the "Other Settings" option can be "Curl Opt" settings too, like CURLOPT_xxx.<br/>
			For more information please visit the CURLOPT site at <a href="https://www.php.net/manual/en/function.curl-setopt.php" target="curlopt">https://www.php.net/manual/en/function.curl-setopt.php</a>.<br/>
			<br/>
			Here is the explanation of the defined "Other Settings" options:
			<ul>
				<li>Header (Boolean) - CURLOPT_HEADER - Include the request header in the server response.</li>
				<li>Connection Timeout (Numeric) - CURLOPT_CONNECTTIMEOUT - The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.</li>
				<li>No Body (Boolean) - CURLOPT_NOBODY - True to exclude the body from the output. Request method is then set to HEAD. Changing this to false does not change it to GET.</li>
				<li>HTTP header (Array|String) - CURLOPT_HTTPHEADER - An array or string (end-line demiliter) of HTTP header fields to set. Array in the format: array('Content-type: text/plain', 'Content-length: 100').</li>
				<li>Referer (String) - CURLOPT_REFERER - The contents of the 'Referer: ' header to be used in a HTTP request.</li>
				<li>Follow Location (Boolean) - CURLOPT_FOLLOWLOCATION - True to follow any 'Location: ' header that the server sends as part of the HTTP header. See also CURLOPT_MAXREDIRS.</li>
				<li>HTTP Auth (String|Defined Var) - CURLOPT_HTTPAUTH - The HTTP authentication method(s) to use. The options are: 'basic', 'digest', CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM, CURLAUTH_ANY, and CURLAUTH_ANYSAFE.</li>
				<li>User+Pwd (String) - CURLOPT_USERPWD - Username and password formatted as '[username]:[password]' to use for the connection.</li>
				<li>Put (Boolean) - CURLOPT_PUT - True to HTTP PUT a file. The file to PUT must be set with CURLOPT_INFILE and CURLOPT_INFILESIZE.</li>
				<li>In File (String) - CURLOPT_INFILE - The file that the transfer should be read from when uploading.</li>
				<li>In File Size (Numeric) - CURLOPT_INFILESIZE - The expected size, in bytes, of the file when uploading a file to a remote site. Note that using this option will not stop libcurl from sending more data, as exactly what is sent depends on CURLOPT_READFUNCTION.</li>
				<li>Read Cookies from File (String) - CURLOPT_COOKIEFILE - The name of the file containing the cookie data. The cookie file can be in Netscape format, or just plain HTTP-style headers dumped into a file. If the name is an empty string, no cookies are loaded, but cookie handling is still enabled.</li>
				<li>Save Cookies to File (String) - CURLOPT_COOKIEJAR - The name of a file to save all internal cookies to when the handle is closed, e.g. after a call to curl_close.</li>
			</ul>
		</div>
	</div>
	<div class="result_type">
		<label>Result Type: </label>
		<input type="text" class="task_property_field" name="result_type" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select name="result_type">
			<option></option>
			<option>header</option>
			<option value="content">content text</option>
			<option value="content_json">content json</option>
			<option value="content_xml">content xml parsed to array</option>
			<option value="content_xml_simple">content xml parsed to simple array</option>
			<option value="content_serialized">content serialized</option>
			<option>settings</option>
		</select>
		<select class="task_property_field" name="result_type_type" onChange="GetUrlContentsTaskPropertyObj.onChangeResultType(this)">
			<option>options</option>
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
		<div class="info">
			The result type can have 3 values:
			<ul>
				<li>blank value: which will return a associative array with the request header, html contents, errors...</li>
				<li>"header": which will return a associative array with the request headers.</li>
				<li>"content": which will return request html contents.</li>
				<li>"settings": which will return request settings.</li>
			</ul>
		</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
