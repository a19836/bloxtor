<?php
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
