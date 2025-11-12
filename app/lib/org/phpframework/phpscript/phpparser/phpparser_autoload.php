<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

if (version_compare(PHP_VERSION, '7.4', '>=')) {
	include_once get_lib("lib.vendor.phpparser.phpparser_52_84.vendor.autoload");
	
	include_once get_lib("org.phpframework.phpscript.phpparser.PHP7Parser");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHP8Parser");
	
	define("PHPPARSER_EMULATIVE_VERSION", "52_84");
}
else if (version_compare(PHP_VERSION, '7.1', '>=')) { 
	include_once get_lib("lib.vendor.phpparser.phpparser_52_82.vendor.autoload");

	include_once get_lib("org.phpframework.phpscript.phpparser.PHP5Parser");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHP7Parser");
	
	define("PHPPARSER_EMULATIVE_VERSION", "52_82");
}
else if (version_compare(PHP_VERSION, '5.5', '>=')) {
	include_once get_lib("lib.vendor.phpparser.phpparser_52_71.lib.bootstrap");

	include_once get_lib("org.phpframework.phpscript.phpparser.PHP5Parser");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHP7Parser");
	
	define("PHPPARSER_EMULATIVE_VERSION", "52_71");
}
else if (version_compare(PHP_VERSION, '5.3', '>=')) {
	include_once get_lib("lib.vendor.phpparser.phpparser_52_56.lib.bootstrap");

	include_once get_lib("org.phpframework.phpscript.phpparser.PHP4Parser");
	
	define("PHPPARSER_EMULATIVE_VERSION", "52_56");
}

if (defined("PHPPARSER_EMULATIVE_VERSION")) {
	//extensions
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserLexerEmulative");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPMultipleParser");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserNodeTraverser");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserNodeVisitorAbstract");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserPrettyPrinterStandard");

	//mines
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserTraverserNodeVisitor");
	include_once get_lib("org.phpframework.phpscript.phpparser.PHPParserPrettyPrinter");
}
?>
