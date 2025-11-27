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

$head = '
<style>
	.diff {
		width:100%;
		table-layout:fixed;
	}
	.diff td {
		width:50%;
		vertical-align: top;
		white-space:pre;
		white-space:pre-wrap;
		overflow-wrap:break-word;
		font-family:var(--main-font-family);
	}
	.diff td:first-child {
		border-right:1px solid #ccc;
		background:#f7f7f7;
	}
	.diff td.diffDeleted {
		background:#ff00002b;
	}
	.diff td.diffInserted {
		background:#00800040;
	}
</style>';

$main_content = isset($html) ? $html : null;
?>
