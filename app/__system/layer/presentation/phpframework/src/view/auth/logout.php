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

$head = '<style>
body:not(.in_popup),
  body:not(.in_popup) #main_column {
	background:#DFE1ED;
}
h1 {width:100%; text-align:center; font-size:14px; font-weight:bold; margin-top:100px;}
h2 {width:100%; text-align:center; margin-top:10px;}
</style>';

$main_content = '<h1>You are logged OUT!</h1>
<h2>To login again click <a href="' . $project_url_prefix . 'auth/login">here</a>...</h2>';
?>
