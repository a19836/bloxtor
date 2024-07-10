<?php
$db_user = "";
$db_pass = "";

//http://jplpinto.localhost/__system/test/tests/db_dumper
include_once get_lib("org.phpframework.db.DBDumperHandler");
include_once get_lib("org.phpframework.db.driver.MSSqlDB");

$PHPFrameWork->loadBeansFile(SYSTEM_BEAN_PATH . "db_driver.xml");

/*$dump_settings = array(
	'include-tables' => array(),
	'exclude-tables' => array(),
	'include-views' => array(),
	'compress' => DBDumperHandler::NONE,
	'no-data' => false,
	'reset-auto-increment' => false,
	'add-drop-database' => false,
	'add-drop-table' => true,
	'add-drop-trigger' => false,
	'add-drop-routine' => true,
	'add-drop-event' => false,
	'add-locks' => true,
	'complete-insert' => true, //must be complete-insert bc postgres gives an error when dumping insert queries without column names.
	'databases' => false,
	'default-character-set' => DBDumperHandler::UTF8,
	'disable-keys' => true,
	'extended-insert' => false,
	'events' => false,
	'hex-blob' => false, //faster than escaped content
	'insert-ignore' => false, 
	'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
	'no-autocommit' => false,
	'no-create-info' => false,
	'lock-tables' => true,
	'routines' => true, //for store procedure
	'single-transaction' => false,
	'skip-triggers' => false,
	'skip-tz-utc' => true,
	'skip-comments' => true,
	'skip-dump-date' => false,
	'skip-definer' => false,
	'where' => '',
);*/
$dump_settings = array(
	'include-tables' => array(),
	'exclude-tables' => array(),
	'include-views' => array(),
	'compress' => DBDumperHandler::NONE,
	'init_commands' => array(),
	'no-data' => false,
	'reset-auto-increment' => false,
	'add-drop-database' => false,
	'add-drop-table' => true,
	'add-drop-trigger' => true,
	'add-drop-routine' => true,
	'add-drop-event' => false,
	'add-locks' => true,
	'complete-insert' => true,
	'databases' => false,
	'default-character-set' => DBDumperHandler::UTF8,
	'disable-keys' => true,
	'extended-insert' => false,
	'events' => false,
	'hex-blob' => false, /* faster than escaped content */
	'insert-ignore' => true,
	'net_buffer_length' => DBDumperHandler::MAX_LINE_SIZE,
	'no-autocommit' => false,
	'no-create-info' => false,
	'lock-tables' => true,
	'routines' => true,
	'single-transaction' => false,
	'skip-triggers' => false,
	'skip-tz-utc' => false,
	'skip-comments' => false, //to avoid comments
	'skip-dump-date' => false,
	'skip-definer' => false,
	'where' => '',
);
$pdo_settings = array(PDO::ATTR_PERSISTENT => true);

$dump_file_path = LAYER_CACHE_PATH . "test-dbdumper-mysql-jp.sql";
print "starting $dump_file_path<br/>";
$DBDriver = $PHPFrameWork->getObject("MySqlDB");
$DBDumperHandler = new DBDumperHandler($DBDriver, $dump_settings, $pdo_settings);
$DBDumperHandler->connect();
$DBDumperHandler->run($dump_file_path);
$DBDumperHandler->disconnect();

if (!file_exists($dump_file_path))
	echo "Error: Could not create '$dump_file_path' file<br/>";
else
	echo "OK<br/>";

$dump_file_path = LAYER_CACHE_PATH . "test-dbdumper-postgres-jp.sql";
print "starting $dump_file_path<br/>";
$DBDriver = $PHPFrameWork->getObject("PostgresDB");
$DBDumperHandler = new DBDumperHandler($DBDriver, $dump_settings, $pdo_settings);
$DBDumperHandler->connect();
$DBDumperHandler->run($dump_file_path);
$DBDumperHandler->disconnect();

if (!file_exists($dump_file_path))
	echo "Error: Could not create '$dump_file_path' file<br/>";
else
	echo "OK<br/>";

$dump_file_path = LAYER_CACHE_PATH . "test-dbdumper-mssql-jp.sql";
print "starting $dump_file_path<br/>";
$DBDriver = new MSSqlDB();
$DBDriver->setOptions(array(
	"extension" => "pdo",
	"host" => "192.168.1.170",
	"port" => "",
	"db_name" => "master",
	"username" => $db_user,
	"password" => $db_pass,
	"persistent" => "1",
	"new_link" => "0",
	"encoding" => "",
	"schema" => "",
	"odbc_data_source" => "",
	"odbc_driver" => "ODBC Driver 17 for SQL Server",
	"extra_dsn" => "",
));
$DBDumperHandler = new DBDumperHandler($DBDriver, $dump_settings, $pdo_settings);
$DBDumperHandler->connect();
$DBDumperHandler->run($dump_file_path);
$DBDumperHandler->disconnect();

if (!file_exists($dump_file_path))
	echo "Error: Could not create '$dump_file_path' file<br/>";
else
	echo "OK<br/>";
?>
