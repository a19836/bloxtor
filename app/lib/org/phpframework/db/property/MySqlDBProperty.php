<?php
trait MySqlDBProperty {
	private static $default_schema = ""; //schema is the database name in mysql, so basically this concept doesn't apply like it applies in other DBs like mssql and pgsql.
	
	private static $default_odbc_data_source = null; //To be set by the developer, so it can be used in all drivers
	private static $default_odbc_driver = null; //To be set by the developer, so it can be used in all drivers
	
	private static $available_php_extension_types = array(); //will be init on construct
	
	private static $ignore_connection_options = array("schema"); //will be used in the layers diagram, set DB settings and others
	private static $ignore_connection_options_by_extension = array(
		"mysql" => array("odbc_data_source", "odbc_driver", "extra_dsn", "extra_settings"),
		"mysqli" => array("odbc_data_source", "odbc_driver", "extra_dsn", "extra_settings"),
		"odbc" => array("extra_settings")
	); //will be used in the layers diagram, set DB settings and others
	
	//To get more charsets please query the sql: SHOW CHARACTER SET
	private static $db_table_charsets = array( //used to connect to a DB or to create a DB, table and column
		"utf8" => "UTF-8 Unicode", 
		"latin1" => "cp1252 West European", 
		"latin2" => "ISO 8859-2 Central European", 
		"big5" => "Big5 Traditional Chinese", 
		"dec8" => "DEC West European", 
		"cp850" => "DOS West European", 
		"hp8" => "HP West European", 
		"koi8r" => "KOI8-R Relcom Russian", 
		"swe7" => "7bit Swedish", 
		"ascii" => "US ASCII", 
		"ujis" => "EUC-JP Japanese", 
		"sjis" => "Shift-JIS Japanese", 
		"hebrew" => "ISO 8859-8 Hebrew", 
		"tis620" => "TIS620 Thai", 
		"euckr" => "EUC-KR Korean", 
		"koi8u" => "KOI8-U Ukrainian", 
		"gb2312" => "GB2312 Simplified Chinese", 
		"greek" => "ISO 8859-7 Greek", 
		"cp1250" => "Windows Central European", 
		"gbk" => "GBK Simplified Chinese", 
		"latin5" => "ISO 8859-9 Turkish", 
		"armscii8" => "ARMSCII-8 Armenian", 
		"ucs2" => "UCS-2 Unicode", 
		"cp866" => "DOS Russian", 
		"keybcs2" => "DOS Kamenicky Czech-Slovak", 
		"macce" => "Mac Central European", 
		"macroman" => "Mac West European", 
		"cp852" => "DOS Central European", 
		"latin7" => "ISO 8859-13 Baltic", 
		"utf8mb4" => "UTF-8 Unicode", 
		"cp1251" => "Windows Cyrillic", 
		"utf16" => "UTF-16 Unicode", 
		"utf16le" => "UTF-16LE Unicode", 
		"cp1256" => "Windows Arabic", 
		"cp1257" => "Windows Baltic", 
		"utf32" => "UTF-32 Unicode", 
		"binary" => "Binary pseudo charset", 
		"geostd8" => "GEOSTD8 Georgian", 
		"cp932" => "SJIS for Windows Japanese", 
		"eucjpms" => "UJIS for Windows Japanese", 
		"gb18030" => "China National Standard GB18030",
	);
	
	//To get more collations please query the sql: SHOW COLLATION
	private static $db_table_column_collations = array(
		"big5_chinese_ci" => "Big5 Chinese Ci", 
		"big5_bin" => "Big5 Bin", 
		"dec8_swedish_ci" => "Dec8 Swedish Ci", 
		"dec8_bin" => "Dec8 Bin", 
		"cp850_general_ci" => "Cp850 General Ci", 
		"cp850_bin" => "Cp850 Bin", 
		"hp8_english_ci" => "Hp8 English Ci", 
		"hp8_bin" => "Hp8 Bin", 
		"koi8r_general_ci" => "Koi8R General Ci", 
		"koi8r_bin" => "Koi8R Bin", 
		"latin1_german1_ci" => "Latin1 German1 Ci", 
		"latin1_swedish_ci" => "Latin1 Swedish Ci", 
		"latin1_danish_ci" => "Latin1 Danish Ci", 
		"latin1_german2_ci" => "Latin1 German2 Ci", 
		"latin1_bin" => "Latin1 Bin", 
		"latin1_general_ci" => "Latin1 General Ci", 
		"latin1_general_cs" => "Latin1 General Cs", 
		"latin1_spanish_ci" => "Latin1 Spanish Ci", 
		"latin2_czech_cs" => "Latin2 Czech Cs", 
		"latin2_general_ci" => "Latin2 General Ci", 
		"latin2_hungarian_ci" => "Latin2 Hungarian Ci", 
		"latin2_croatian_ci" => "Latin2 Croatian Ci", 
		"latin2_bin" => "Latin2 Bin", 
		"swe7_swedish_ci" => "Swe7 Swedish Ci", 
		"swe7_bin" => "Swe7 Bin", 
		"ascii_general_ci" => "Ascii General Ci", 
		"ascii_bin" => "Ascii Bin", 
		"ujis_japanese_ci" => "Ujis Japanese Ci", 
		"ujis_bin" => "Ujis Bin", 
		"sjis_japanese_ci" => "Sjis Japanese Ci", 
		"sjis_bin" => "Sjis Bin", 
		"hebrew_general_ci" => "Hebrew General Ci", 
		"hebrew_bin" => "Hebrew Bin", 
		"tis620_thai_ci" => "Tis620 Thai Ci", 
		"tis620_bin" => "Tis620 Bin", 
		"euckr_korean_ci" => "Euckr Korean Ci", 
		"euckr_bin" => "Euckr Bin", 
		"koi8u_general_ci" => "Koi8U General Ci", 
		"koi8u_bin" => "Koi8U Bin", 
		"gb2312_chinese_ci" => "Gb2312 Chinese Ci", 
		"gb2312_bin" => "Gb2312 Bin", 
		"greek_general_ci" => "Greek General Ci", 
		"greek_bin" => "Greek Bin", 
		"cp1250_general_ci" => "Cp1250 General Ci", 
		"cp1250_czech_cs" => "Cp1250 Czech Cs", 
		"cp1250_croatian_ci" => "Cp1250 Croatian Ci", 
		"cp1250_bin" => "Cp1250 Bin", 
		"cp1250_polish_ci" => "Cp1250 Polish Ci", 
		"gbk_chinese_ci" => "Gbk Chinese Ci", 
		"gbk_bin" => "Gbk Bin", 
		"latin5_turkish_ci" => "Latin5 Turkish Ci", 
		"latin5_bin" => "Latin5 Bin", 
		"armscii8_general_ci" => "Armscii8 General Ci", 
		"armscii8_bin" => "Armscii8 Bin", 
		"utf8_general_ci" => "Utf8 General Ci", 
		"utf8_bin" => "Utf8 Bin", 
		"utf8_unicode_ci" => "Utf8 Unicode Ci", 
		"utf8_icelandic_ci" => "Utf8 Icelandic Ci", 
		"utf8_latvian_ci" => "Utf8 Latvian Ci", 
		"utf8_romanian_ci" => "Utf8 Romanian Ci", 
		"utf8_slovenian_ci" => "Utf8 Slovenian Ci", 
		"utf8_polish_ci" => "Utf8 Polish Ci", 
		"utf8_estonian_ci" => "Utf8 Estonian Ci", 
		"utf8_spanish_ci" => "Utf8 Spanish Ci", 
		"utf8_swedish_ci" => "Utf8 Swedish Ci", 
		"utf8_turkish_ci" => "Utf8 Turkish Ci", 
		"utf8_czech_ci" => "Utf8 Czech Ci", 
		"utf8_danish_ci" => "Utf8 Danish Ci", 
		"utf8_lithuanian_ci" => "Utf8 Lithuanian Ci", 
		"utf8_slovak_ci" => "Utf8 Slovak Ci", 
		"utf8_spanish2_ci" => "Utf8 Spanish2 Ci", 
		"utf8_roman_ci" => "Utf8 Roman Ci", 
		"utf8_persian_ci" => "Utf8 Persian Ci", 
		"utf8_esperanto_ci" => "Utf8 Esperanto Ci", 
		"utf8_hungarian_ci" => "Utf8 Hungarian Ci", 
		"utf8_sinhala_ci" => "Utf8 Sinhala Ci", 
		"utf8_german2_ci" => "Utf8 German2 Ci", 
		"utf8_croatian_ci" => "Utf8 Croatian Ci", 
		"utf8_unicode_520_ci" => "Utf8 Unicode 520 Ci", 
		"utf8_vietnamese_ci" => "Utf8 Vietnamese Ci", 
		"utf8_general_mysql500_ci" => "Utf8 General Mysql500 Ci", 
		"ucs2_general_ci" => "Ucs2 General Ci", 
		"ucs2_bin" => "Ucs2 Bin", 
		"ucs2_unicode_ci" => "Ucs2 Unicode Ci", 
		"ucs2_icelandic_ci" => "Ucs2 Icelandic Ci", 
		"ucs2_latvian_ci" => "Ucs2 Latvian Ci", 
		"ucs2_romanian_ci" => "Ucs2 Romanian Ci", 
		"ucs2_slovenian_ci" => "Ucs2 Slovenian Ci", 
		"ucs2_polish_ci" => "Ucs2 Polish Ci", 
		"ucs2_estonian_ci" => "Ucs2 Estonian Ci", 
		"ucs2_spanish_ci" => "Ucs2 Spanish Ci", 
		"ucs2_swedish_ci" => "Ucs2 Swedish Ci", 
		"ucs2_turkish_ci" => "Ucs2 Turkish Ci", 
		"ucs2_czech_ci" => "Ucs2 Czech Ci", 
		"ucs2_danish_ci" => "Ucs2 Danish Ci", 
		"ucs2_lithuanian_ci" => "Ucs2 Lithuanian Ci", 
		"ucs2_slovak_ci" => "Ucs2 Slovak Ci", 
		"ucs2_spanish2_ci" => "Ucs2 Spanish2 Ci", 
		"ucs2_roman_ci" => "Ucs2 Roman Ci", 
		"ucs2_persian_ci" => "Ucs2 Persian Ci", 
		"ucs2_esperanto_ci" => "Ucs2 Esperanto Ci", 
		"ucs2_hungarian_ci" => "Ucs2 Hungarian Ci", 
		"ucs2_sinhala_ci" => "Ucs2 Sinhala Ci", 
		"ucs2_german2_ci" => "Ucs2 German2 Ci", 
		"ucs2_croatian_ci" => "Ucs2 Croatian Ci", 
		"ucs2_unicode_520_ci" => "Ucs2 Unicode 520 Ci", 
		"ucs2_vietnamese_ci" => "Ucs2 Vietnamese Ci", 
		"ucs2_general_mysql500_ci" => "Ucs2 General Mysql500 Ci", 
		"cp866_general_ci" => "Cp866 General Ci", 
		"cp866_bin" => "Cp866 Bin", 
		"keybcs2_general_ci" => "Keybcs2 General Ci", 
		"keybcs2_bin" => "Keybcs2 Bin", 
		"macce_general_ci" => "Macce General Ci", 
		"macce_bin" => "Macce Bin", 
		"macroman_general_ci" => "Macroman General Ci", 
		"macroman_bin" => "Macroman Bin", 
		"cp852_general_ci" => "Cp852 General Ci", 
		"cp852_bin" => "Cp852 Bin", 
		"latin7_estonian_cs" => "Latin7 Estonian Cs", 
		"latin7_general_ci" => "Latin7 General Ci", 
		"latin7_general_cs" => "Latin7 General Cs", 
		"latin7_bin" => "Latin7 Bin", 
		"utf8mb4_general_ci" => "Utf8Mb4 General Ci", 
		"utf8mb4_bin" => "Utf8Mb4 Bin", 
		"utf8mb4_unicode_ci" => "Utf8Mb4 Unicode Ci", 
		"utf8mb4_icelandic_ci" => "Utf8Mb4 Icelandic Ci", 
		"utf8mb4_latvian_ci" => "Utf8Mb4 Latvian Ci", 
		"utf8mb4_romanian_ci" => "Utf8Mb4 Romanian Ci", 
		"utf8mb4_slovenian_ci" => "Utf8Mb4 Slovenian Ci", 
		"utf8mb4_polish_ci" => "Utf8Mb4 Polish Ci", 
		"utf8mb4_estonian_ci" => "Utf8Mb4 Estonian Ci", 
		"utf8mb4_spanish_ci" => "Utf8Mb4 Spanish Ci", 
		"utf8mb4_swedish_ci" => "Utf8Mb4 Swedish Ci", 
		"utf8mb4_turkish_ci" => "Utf8Mb4 Turkish Ci", 
		"utf8mb4_czech_ci" => "Utf8Mb4 Czech Ci", 
		"utf8mb4_danish_ci" => "Utf8Mb4 Danish Ci", 
		"utf8mb4_lithuanian_ci" => "Utf8Mb4 Lithuanian Ci", 
		"utf8mb4_slovak_ci" => "Utf8Mb4 Slovak Ci", 
		"utf8mb4_spanish2_ci" => "Utf8Mb4 Spanish2 Ci", 
		"utf8mb4_roman_ci" => "Utf8Mb4 Roman Ci", 
		"utf8mb4_persian_ci" => "Utf8Mb4 Persian Ci", 
		"utf8mb4_esperanto_ci" => "Utf8Mb4 Esperanto Ci", 
		"utf8mb4_hungarian_ci" => "Utf8Mb4 Hungarian Ci", 
		"utf8mb4_sinhala_ci" => "Utf8Mb4 Sinhala Ci", 
		"utf8mb4_german2_ci" => "Utf8Mb4 German2 Ci", 
		"utf8mb4_croatian_ci" => "Utf8Mb4 Croatian Ci", 
		"utf8mb4_unicode_520_ci" => "Utf8Mb4 Unicode 520 Ci", 
		"utf8mb4_vietnamese_ci" => "Utf8Mb4 Vietnamese Ci", 
		"cp1251_bulgarian_ci" => "Cp1251 Bulgarian Ci", 
		"cp1251_ukrainian_ci" => "Cp1251 Ukrainian Ci", 
		"cp1251_bin" => "Cp1251 Bin", 
		"cp1251_general_ci" => "Cp1251 General Ci", 
		"cp1251_general_cs" => "Cp1251 General Cs", 
		"utf16_general_ci" => "Utf16 General Ci", 
		"utf16_bin" => "Utf16 Bin", 
		"utf16_unicode_ci" => "Utf16 Unicode Ci", 
		"utf16_icelandic_ci" => "Utf16 Icelandic Ci", 
		"utf16_latvian_ci" => "Utf16 Latvian Ci", 
		"utf16_romanian_ci" => "Utf16 Romanian Ci", 
		"utf16_slovenian_ci" => "Utf16 Slovenian Ci", 
		"utf16_polish_ci" => "Utf16 Polish Ci", 
		"utf16_estonian_ci" => "Utf16 Estonian Ci", 
		"utf16_spanish_ci" => "Utf16 Spanish Ci", 
		"utf16_swedish_ci" => "Utf16 Swedish Ci", 
		"utf16_turkish_ci" => "Utf16 Turkish Ci", 
		"utf16_czech_ci" => "Utf16 Czech Ci", 
		"utf16_danish_ci" => "Utf16 Danish Ci", 
		"utf16_lithuanian_ci" => "Utf16 Lithuanian Ci", 
		"utf16_slovak_ci" => "Utf16 Slovak Ci", 
		"utf16_spanish2_ci" => "Utf16 Spanish2 Ci", 
		"utf16_roman_ci" => "Utf16 Roman Ci", 
		"utf16_persian_ci" => "Utf16 Persian Ci", 
		"utf16_esperanto_ci" => "Utf16 Esperanto Ci", 
		"utf16_hungarian_ci" => "Utf16 Hungarian Ci", 
		"utf16_sinhala_ci" => "Utf16 Sinhala Ci", 
		"utf16_german2_ci" => "Utf16 German2 Ci", 
		"utf16_croatian_ci" => "Utf16 Croatian Ci", 
		"utf16_unicode_520_ci" => "Utf16 Unicode 520 Ci", 
		"utf16_vietnamese_ci" => "Utf16 Vietnamese Ci", 
		"utf16le_general_ci" => "Utf16Le General Ci", 
		"utf16le_bin" => "Utf16Le Bin", 
		"cp1256_general_ci" => "Cp1256 General Ci", 
		"cp1256_bin" => "Cp1256 Bin", 
		"cp1257_lithuanian_ci" => "Cp1257 Lithuanian Ci", 
		"cp1257_bin" => "Cp1257 Bin", 
		"cp1257_general_ci" => "Cp1257 General Ci", 
		"utf32_general_ci" => "Utf32 General Ci", 
		"utf32_bin" => "Utf32 Bin", 
		"utf32_unicode_ci" => "Utf32 Unicode Ci", 
		"utf32_icelandic_ci" => "Utf32 Icelandic Ci", 
		"utf32_latvian_ci" => "Utf32 Latvian Ci", 
		"utf32_romanian_ci" => "Utf32 Romanian Ci", 
		"utf32_slovenian_ci" => "Utf32 Slovenian Ci", 
		"utf32_polish_ci" => "Utf32 Polish Ci", 
		"utf32_estonian_ci" => "Utf32 Estonian Ci", 
		"utf32_spanish_ci" => "Utf32 Spanish Ci", 
		"utf32_swedish_ci" => "Utf32 Swedish Ci", 
		"utf32_turkish_ci" => "Utf32 Turkish Ci", 
		"utf32_czech_ci" => "Utf32 Czech Ci", 
		"utf32_danish_ci" => "Utf32 Danish Ci", 
		"utf32_lithuanian_ci" => "Utf32 Lithuanian Ci", 
		"utf32_slovak_ci" => "Utf32 Slovak Ci", 
		"utf32_spanish2_ci" => "Utf32 Spanish2 Ci", 
		"utf32_roman_ci" => "Utf32 Roman Ci", 
		"utf32_persian_ci" => "Utf32 Persian Ci", 
		"utf32_esperanto_ci" => "Utf32 Esperanto Ci", 
		"utf32_hungarian_ci" => "Utf32 Hungarian Ci", 
		"utf32_sinhala_ci" => "Utf32 Sinhala Ci", 
		"utf32_german2_ci" => "Utf32 German2 Ci", 
		"utf32_croatian_ci" => "Utf32 Croatian Ci", 
		"utf32_unicode_520_ci" => "Utf32 Unicode 520 Ci", 
		"utf32_vietnamese_ci" => "Utf32 Vietnamese Ci", 
		"binary" => "Binary", 
		"geostd8_general_ci" => "Geostd8 General Ci", 
		"geostd8_bin" => "Geostd8 Bin", 
		"cp932_japanese_ci" => "Cp932 Japanese Ci", 
		"cp932_bin" => "Cp932 Bin", 
		"eucjpms_japanese_ci" => "Eucjpms Japanese Ci", 
		"eucjpms_bin" => "Eucjpms Bin", 
		"gb18030_chinese_ci" => "Gb18030 Chinese Ci", 
		"gb18030_bin" => "Gb18030 Bin", 
		"gb18030_unicode_520_ci" => "Gb18030 Unicode 520 Ci", 
	);
	
	private static $charsets_to_collations = array(
		"big5" => "big5_chinese_ci", 
		"dec8" => "dec8_swedish_ci", 
		"cp850" => "cp850_general_ci", 
		"hp8" => "hp8_english_ci", 
		"koi8r" => "koi8r_general_ci", 
		"latin1" => "latin1_swedish_ci", 
		"latin2" => "latin2_general_ci", 
		"swe7" => "swe7_swedish_ci", 
		"ascii" => "ascii_general_ci", 
		"ujis" => "ujis_japanese_ci", 
		"sjis" => "sjis_japanese_ci", 
		"hebrew" => "hebrew_general_ci", 
		"tis620" => "tis620_thai_ci", 
		"euckr" => "euckr_korean_ci", 
		"koi8u" => "koi8u_general_ci", 
		"gb2312" => "gb2312_chinese_ci", 
		"greek" => "greek_general_ci", 
		"cp1250" => "cp1250_general_ci", 
		"gbk" => "gbk_chinese_ci", 
		"latin5" => "latin5_turkish_ci", 
		"armscii8" => "armscii8_general_ci", 
		"utf8" => "utf8_general_ci", 
		"ucs2" => "ucs2_general_ci", 
		"cp866" => "cp866_general_ci", 
		"keybcs2" => "keybcs2_general_ci", 
		"macce" => "macce_general_ci", 
		"macroman" => "macroman_general_ci", 
		"cp852" => "cp852_general_ci", 
		"latin7" => "latin7_general_ci", 
		"utf8mb4" => "utf8mb4_general_ci", 
		"cp1251" => "cp1251_general_ci", 
		"utf16" => "utf16_general_ci", 
		"utf16le" => "utf16le_general_ci", 
		"cp1256" => "cp1256_general_ci", 
		"cp1257" => "cp1257_general_ci", 
		"utf32" => "utf32_general_ci", 
		"binary" => "binary", 
		"geostd8" => "geostd8_general_ci", 
		"cp932" => "cp932_japanese_ci", 
		"eucjpms" => "eucjpms_japanese_ci", 
		"gb18030" => "gb18030_chinese_ci", 
	); 
	
	//To get more storage_engines please query the sql: SHOW STORAGE ENGINES
	private static $storage_engines = array( //used to create table
		"innodb" => "Innodb", 
		"mrg_myisam" => "Mrg Myisam", 
		"memory" => "Memory", 
		"blackhole" => "Blackhole", 
		"myisam" => "Myisam", 
		"csv" => "Csv", 
		"archive" => "Archive", 
		"performance_schema" => "Performance Schema", 
		"federated" => "Federated", 
	);
	
	//see more in: http://dev.mysql.com/doc/workbench/en/wb-migration-database-postgresql-typemapping.html
	private static $php_to_db_column_types = array(//PHP TO MYSQL
		'bit' => 'bit',
		'smallserial' => array("type" => 'smallint', "null" => false, "unique" => true, "unsigned" => true, "auto_increment" => true), //note that smallserial and bigserial does not exists in MYSQL. Only serial that corresponds to a bigint not null unsigned unique auto_increment
		'serial' => 'serial',
		'bigserial' => array("type" => 'serial', "null" => false, "unique" => true, "unsigned" => true, "auto_increment" => true), //note that smallserial and bigserial does not exists in MYSQL. Only serial that corresponds to a bigint not null unsigned unique auto_increment
		'tinyint' => 'tinyint',
		'smallint' => 'smallint',
		'int' => 'int',
		'bigint' => 'bigint',
		'decimal' => 'decimal',
		'money' => 'decimal(19,2)',
		'coordinate' => 'decimal(18,15)',
		'double' => 'double',
		'float' => 'float',
		'boolean' => 'tinyint(1)',
		
		'date' => 'date',
		'datetime' => 'datetime',
		'timestamp' => 'timestamp',
		'time' => 'time',
		
		'char' => 'char',
		'varchar' => 'varchar',
		'text' => 'text',
		'mediumtext' => 'mediumtext',
		'longtext' => 'longtext',
		'blob' => 'blob',
		'longblob' => 'longblob',
		
		'varchar(36)' => 'uuid',
		'varchar(44)' => 'cidr',
		'varchar(43)' => 'inet',
		'varchar(17)' => 'macaddr',
	);
	
	private static $db_to_php_column_types = array(//MYSQL TO PHP
		'bit' => 'bit',
		'serial' => 'serial', 
		'tinyint' => 'tinyint',
		'smallint' => 'smallint',
		'int' => 'int',
		'bigint' => 'bigint',
		'mediumint' => 'int',
		'decimal' => 'decimal',
		'decimal(19,2)' => 'money',
		'decimal(18,15)' => 'coordinate',
		'double' => 'double',
		'float' => 'float',
		'tinyint(1)' => 'boolean',
		'real' => 'float',
		'numeric' => 'numeric',
		'integer' => 'integer',
		
		'date' => 'date',
		'datetime' => 'datetime',
		'time' => 'time',
		'timestamp' => 'timestamp',
		'year' => 'int',
		
		'char' => 'char',
		'varchar' => 'varchar',
		'text' => 'text',
		'tinytext' => 'text',
		'mediumtext' => 'longtext',
		'longtext' => 'longtext',
		'blob' => 'blob',
		'tinyblob' => 'blob',
		'mediumblob' => 'longblob',
		'longblob' => 'longblob',
		'string' => 'varchar',
		'enum' => 'varchar',
		
		'varchar(36)' => 'varchar(36)',
		'varchar(44)' => 'varchar(44)',
		'varchar(43)' => 'varchar(43)',
		'varchar(17)' => 'varchar(17)',
	);
	
	private static $db_column_types = array( //MYSQL SERVER TYPES
		'bit' => 'Bit',
		'serial' => 'Serial', 
		'tinyint' => 'Tiny Int',
		'smallint' => 'Small Int',
		'int' => 'Int',
		'bigint' => 'Big Int',
		'mediumint' => 'Medium Int',
		'decimal' => 'Decimal',
		'double' => 'Double',
		'float' => 'Float',
		'real' => 'Real',
		'numeric' => 'Numeric',
		'integer' => 'Integer',
		
		'date' => 'Date',
		'datetime' => 'Date Time',
		'time' => 'Time',
		'timestamp' => 'Timestamp',
		'year' => 'Year',
		
		'char' => 'Char',
		'varchar' => 'Varchar',
		'text' => 'Text',
		'tinytext' => 'Tiny Text',
		'mediumtext' => 'Medium Text',
		'longtext' => 'Long Text',
		'blob' => 'Blob',
		'tinyblob' => 'Tiny Blob',
		'mediumblob' => 'Medium Blob',
		'longblob' => 'Long Blob',
		'string' => 'String',
		
		//other types
		'enum' => 'Enum',
	);
	
	private static $db_column_simple_types = array( //MYSQL SERVER TYPES
		'simple_auto_primary_key' => array("type" => "bigint", "label" => "Automatic Primary Key", "length" => 20, "null" => false, "primary_key" => true, "auto_increment" => true, "unsigned" => true, "name" => "id"),
		'simple_manual_primary_key' => array("type" => "varchar", "label" => "Manual Primary Key", "length" => 255, "null" => false, "primary_key" => true, "name" => "id"),
		'simple_fk_primary_key' => array("type" => "bigint", "label" => "Primary Key From Another Table", "length" => 20, "null" => false, "primary_key" => true, "auto_increment" => false, "unsigned" => true, "name" => "id"),
		'simple_fk' => array("type" => array("bigint", "int"), "label" => "Foreign Key From Another Table", "length" => "20", "null" => true, "primary_key" => false, "auto_increment" => false, "unsigned" => true, "name" => "id"),
		'simple_current_date' => array("type" => "date", "label" => "Current Date (yyyy-mm-dd)", "default" => "CURRENT_DATE"),
		'simple_date' => array("type" => "date", "label" => "Date (yyyy-mm-dd)"),
		'simple_current_date_time' => array("type" => "datetime", "label" => "Current Date Time (yyyy-mm-dd hh:mm:ss)", "default" => "CURRENT_TIMESTAMP"),
		'simple_date_time' => array("type" => "datetime", "label" => "Date Time (yyyy-mm-dd hh:mm:ss)", "name" => array(
			"date", "data", "fecha"
		)),
		'simple_current_time' => array("type" => "time", "label" => "Current Time (hh:mm:ss)", "default" => "CURRENT_TIME"),
		'simple_time' => array("type" => "time", "label" => "Time (hh:mm:ss)", "name" => array(
			"time", "tempo", "tiempo"
		)),
		'simple_2_digits' => array("type" => array("tinyint", "smallint", "int"), "label" => "Hour, Minute or Seconds (2 digits)", "length" => 2, "name" => array(
			"hour", "hora", "heure", 
			"minute", "minuto", "minute", 
			"second", "segundo", "secondes"
		)),
		'simple_email' => array("type" => "varchar", "label" => "Email", "length" => 320, "name" => array(
			"email", "e_email"
		)),  //email max length according with RFC 5321 and RFC 5322. "name" is a different property that will check if value is in the attribute name (this is, contains searching string).
		'simple_address' => array("type" => "varchar", "label" => "Address", "length" => 300, "name" => array(
			"address", "endereco", "endereço", "morada", "habitacao", "habitação", "direccion", "dirección", "alojamiento", "adresse", "logement"
		)),
		'simple_numeric_zip_code' => array("type" => "int", "label" => "Numeric Zip Code", "length" => 4, "name" => array(
			"zip", "code", "codigo", "código", "postal"
		)),
		'simple_alpha_numeric_zip_code' => array("type" => "int", "label" => "Alpha-Numeric Zip Code", "length" => 10, "name" => array(
			"zip", "code", "codigo", "código", "postal"
		)),
		'simple_location' => array("type" => "varchar", "label" => "Location", "length" => 50, "name" => array(
			"location", "localizacao", "localização", "ubicacion", "ubicación", "localizacion", "localización", "emplacement", "localisation",
			"zone", "zona", "lugar", "local", "lugar", "sitio", "sítio", "zone", "lieu",
			"village", "vila", "aldea", "ville", 
			"city", "cidade", "ciudad", 
			"state", "estado", "État",
			"region", "regiao", "região", "region", "región", "région",
			"country", "pais", "país", "pays"
		)),
		'simple_phone' => array("type" => "varchar", "label" => "Phone", "length" => 20, "name" => array(
			"phone", "fone", "fono",
			"contact", "contacto", "contato"
		)),
		'simple_username' => array("type" => "varchar", "label" => "Username", "length" => 320, "name" => array(
			"username", "usuario", "usuário", "utilizador", "utilisateur"
		)), //email max length according with RFC 5321 and RFC 5322
		'simple_password' => array("type" => "varchar", "label" => "Password", "length" => 255, "name" => array(
			"password", "chave", "contrasena", "contraseña", "passe"
		)),
		'simple_type' => array("type" => "varchar", "label" => "Long Name", "length" => 100, "name" => array(
			"type", "tipo", "taper"
		)),
		'simple_category' => array("type" => "varchar", "label" => "Category", "length" => 150, "name" => array(
			"category", "categoria", "categoría", "categorie", "catégorie"
		)),
		'simple_summary' => array("type" => "varchar", "label" => "Summary", "length" => 1000, "name" => array(
			"summary", "sumario", "sumário", "resumen", "resume", "résumé", "sommaire"
		)),
		'simple_article' => array("type" => "varchar", "label" => "Article", "length" => 50000, "name" => array(
			"article", "artigo", "articulo", "artículo"
		)),
		'simple_comment' => array("type" => "varchar", "label" => "Comment", "length" => 2000, "name" => array(
			"comment", "comentario", "comentário", "commentaire"
		)),
		'simple_svg' => array("type" => "varchar", "label" => "SVG", "length" => 60000, "name" => "svg"),
		'simple_path' => array("type" => "varchar", "label" => "Path", "length" => 4096, "name" => array(
			"path", "rua", "caminho", "camino", "route", "chemin"
		)),
		'simple_single_name' => array("type" => "varchar", "label" => "Single Name", "length" => 30),
		'simple_short_name' => array("type" => "varchar", "label" => "Short Name", "length" => 50),
		'simple_long_name' => array("type" => "varchar", "label" => "Long Name", "length" => 200),
		'simple_name' => array("type" => "varchar", "label" => "Name", "name" => array(
			"name", "nome", "nombre", "nom",
			"title", "titulo", "título", "titre",
			"label", "etiqueta", "etiquette", "étiquette"
		)),
		'simple_description' => array("type" => array("text", "longblob"), "label" => "Description", "name" => array(
			"description", "descricao", "descrição", "descripcion", "descripción",
			"note", "nota"
		)),
		'simple_boolean' => array("type" => array("bit", "tinyint", "smallint"), "label" => "Boolean/Bit", "length" => 1, "name" => array(
			"status", "estado", "condicao", "condição", "statut",
			"enable", "allow", "habilitar", "permitir", "habiliter",
			"disable",
			"active", "ativar", "activar", "activer", 
			"inactive", "desativar", "desactivar", "desactiver", "désactiver", 
			"mandatory", "obrigatori", "obrigatóri", "obligatori", "obligatoire",
			"optional", "opcional", "facultativo", "facultative",
			"closed", "fechado", "fechada", "cerrado", "cerrada", "fermé",
			"open", "aberto", "aberta", "abierto", "abierta", "ouvrir"
		)),
		'simple_order' => array("type" => array("tinyint", "smallint", "int"), "label" => "Order", "length" => 5, "name" => array(
			"order", "ordem", "orden", "ordre"
		)),
		'simple_rating' => array("type" => array("tinyint", "smallint", "int"), "label" => "Rating", "length" => 2, "name" => array(
			"rating", "evaluation", "classificacao", "classificação", "avaliacao", "avaliação", "clasificacion", "clasificación", "valuacion", "valuación", "notation", "évaluation"
		)),
		'simple_age' => array("type" => array("tinyint", "smallint", "int"), "label" => "Age", "length" => 3, "name" => array(
			"age", "idade", "edad", "âge"
		)),
		'simple_height' => array("type" => array("decimal", "double", "float"), "label" => "Height", "length" => "4,1", "name" => array(
			"height", "altura", "hauteur"
		)),
		'simple_weight' => array("type" => array("decimal", "double", "float"), "label" => "Weight", "length" => "5,2", "name" => array(
			"weight", "peso", "poids"
		)),
		'simple_big_weight' => array("type" => array("decimal", "double", "float"), "label" => "Big Weight", "length" => "10,2"),
		'simple_currency' => array("type" => array("decimal", "double", "float"), "label" => "Currency", "length" => "37,8", "name" => array(
			"currency", "price", "moeda", "preco", "preço", "divisa", "moneda", "precio", "devise", "monnaie", "prix"
		)),
		'simple_size' => array("type" => "bigint", "label" => "Size", "name" => array(
			"size", "tamanho", "tamaño", "taille"
		)),
		'simple_binary' => array("type" => array("longblob", "mediumblob", "tinyblob", "blob"), "label" => "Binary/File", "name" => array(
			"attachment", "anexo", "adjunto", "jointe", "attachement",
			"image", "imagem", "imagen",
			"video", "movie", "vídeo", "filme", "pelicula", "película", "vidéo",
			"document", "documento",
			"file", "ficheiro", "archivo", "dossier", "deposer", "déposer"
		)),
		'simple_int' => array("type" => "int", "label" => "Integer", "name" => "int"),
		'simple_big_int' => array("type" => "bigint", "label" => "Big Integer"),
		'simple_decimal_2_digits' => array("type" => array("decimal", "double", "float"), "label" => "Decimal 2 digits", "length" => "10,8"),
		'simple_coordinate' => array("type" => array("decimal", "double", "float"), "label" => "Coordinate", "length" => "12,8", "name" => array(
			"coordinate", "coordenada", "coordonne", "coordonné",
			"latitude", "latitud",
			"longitude", "longitud"
		)),
		'simple_decimal' => array("type" => array("decimal", "double", "float"), "label" => "Decimal", "name" => array(
			"decimal", "décimale"
		)),
		'simple_numeric' => array("type" => "numeric", "label" => "Number", "name" => array(
			"number", "numero", "número", "numéro"
		)),
	);
	
	private static $db_column_default_values_by_type = array(
		'bit' => '0',
		'serial' => '0',
		'tinyint' => '0',
		'smallint' => '0',
		'int' => '0',
		'bigint' => '0',
		'mediumint' => '0',
		'decimal' => '0',
		'double' => '0',
		'float' => '0',
		'real' => '0',
		'numeric' => '0',
		'integer' => '0',
		
		'date' => '0000-00-00',
		'datetime' => '0000-00-00 00:00:00',
		'time' => '00:00:00',
		'timestamp' => '0000-00-00 00:00:00',
		'year' => '0000',
		
		'char' => '',
		'varchar' => '',
		'text' => '',
		'tinytext' => '',
		'mediumtext' => '',
		'longtext' => '',
		'string' => '',
		'enum' => '',
	);
	
	private static $db_column_types_ignored_props = array(
		'bit' => array("unsigned", "auto_increment", "charset", "collation"),
		'serial' => array("length", "null", "unique", "unsigned", "auto_increment"),
		'tinyint' => array("charset", "collation"),
		'smallint' => array("charset", "collation"),
		'int' => array("charset", "collation"),
		'bigint' => array("charset", "collation"),
		'mediumint' => array("charset", "collation"),
		'decimal' => array("charset", "collation"),
		'double' => array("charset", "collation"),
		'float' => array("charset", "collation"),
		'real' => array("charset", "collation"),
		'numeric' => array("charset", "collation"),
		'integer' => array("charset", "collation"),
		
		'date' => array("length", "unsigned", "auto_increment", "charset", "collation"),
		'datetime' => array("length", "unsigned", "auto_increment", "charset", "collation"),
		'time' => array("length", "unsigned", "auto_increment", "charset", "collation"),
		'timestamp' => array("length", "unsigned", "auto_increment", "charset", "collation"),
		'year' => array("length", "unsigned", "auto_increment", "charset", "collation"),
		
		//if text or blob the length is not allowed
		'char' => array("unsigned", "auto_increment"),
		'varchar' => array("unsigned", "auto_increment"),
		'tinytext' => array("unsigned", "auto_increment", "default"),
		'mediumtext' => array("length", "unsigned", "auto_increment", "default"),
		'text' => array("length", "unsigned", "auto_increment", "default"),
		'longtext' => array("length", "unsigned", "auto_increment", "default"),
		'string' => array("unsigned", "auto_increment"),
		'enum' => array("unsigned", "auto_increment"),
		
		'blob' => array("length", "unsigned", "auto_increment", "default", "charset", "collation"),
		'tinyblob' => array("unsigned", "auto_increment", "default", "charset", "collation"),
		'mediumblob' => array("unsigned", "auto_increment", "default", "charset", "collation"),
		'longblob' => array("length", "unsigned", "auto_increment", "default", "charset", "collation"),
	);
	
	private static $db_column_numeric_types = array('bit', 'serial', 'tinyint', 'smallint', 'int', 'bigint', 'mediumint', 'decimal', 'double', 'float', 'real', 'numeric', 'integer');
	
	private static $db_column_date_types = array('date', 'datetime', 'time', 'timestamp', 'year');
	
	private static $db_column_text_types = array('char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext', 'string', 'enum');
	
	/* geometry: http://bugs.mysql.com/bug.php?id=43544 */
	private static $db_column_blob_types = array('tinyblob', 'blob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'geometry', 'point', 'linestring', 'polygon', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection'); //Do not add text, varchar, bit, xml, json here
	
	private static $db_column_boolean_types = array(); //no boolean support
	
	private static $db_column_mandatory_length_types = array( //https://dev.mysql.com/doc/refman/8.0/en/string-type-syntax.html
		'varchar' => 255, 
		'varbinary' => 255
	); 
	
	private static $db_column_auto_increment_types = array('serial');
	
	private static $db_boolean_type_available_values = array();
	
	private static $db_current_timestamp_available_values = array("CURRENT_TIMESTAMP", "CURRENT_TIMESTAMP()", "NOW()");
	
	//http://mx1.php.net/manual/en/mysqli-result.fetch-field.php
	//http://php.net/manual/en/mysqli.constants.php
	private static $mysqli_data_types = array(
		6 => array("null"), //MYSQLI_TYPE_NULL
		
		//numeric
		0 => array("decimal"), //MYSQLI_TYPE_DECIMAL
		1 => array("tinyint", "bool"), //MYSQLI_TYPE_TINY or MYSQLI_TYPE_CHAR
		2 => array("smallint"), //MYSQLI_TYPE_SHORT
		3 => array("int", "integer"), //MYSQLI_TYPE_LONG
		4 => array("float"), //MYSQLI_TYPE_FLOAT
		5 => array("double"), //MYSQLI_TYPE_DOUBLE
		8 => array("bigint", "serial"), //MYSQLI_TYPE_LONGLONG
		9 => array("mediumint"), //MYSQLI_TYPE_INT24
		16 => array("bit"), //MYSQLI_TYPE_BIT
		246 => array("decimal", "numeric"), //MYSQLI_TYPE_NEWDECIMAL
		
		//dates
		7 => array("timestamp"), //MYSQLI_TYPE_TIMESTAMP
		10 => array("date"), //MYSQLI_TYPE_DATE
		11 => array("time"), //MYSQLI_TYPE_TIME
		12 => array("datetime"), //MYSQLI_TYPE_DATETIME
		13 => array("year"), //MYSQLI_TYPE_YEAR
		14 => array("date"), //MYSQLI_TYPE_NEWDATE
		
		//strings & binary
		245 => array("json"), //MYSQLI_TYPE_JSON
		247 => array("enum", "interval"), //MYSQLI_TYPE_ENUM or MYSQLI_TYPE_INTERVAL
		248 => array("set"), //MYSQLI_TYPE_SET
		249 => array("tinyblob", "tinytext"), //MYSQLI_TYPE_TINY_BLOB
		250 => array("mediumblob", "mediumtext"), //MYSQLI_TYPE_MEDIUM_BLOB
		251 => array("longblob", "longtext"), //MYSQLI_TYPE_LONG_BLOB
		252 => array("blob", "text"), //MYSQLI_TYPE_BLOB
		253 => array("varchar"), //MYSQLI_TYPE_VAR_STRING
		254 => array("char"), //MYSQLI_TYPE_STRING
		255 => array("geometry"), //MYSQLI_TYPE_GEOMETRY
	);
	
	//http://mx1.php.net/manual/en/mysqli-result.fetch-field.php
	//http://php.net/manual/en/mysqli.constants.php
	/*
	 * According to dev.mysql.com/sources/doxygen/mysql-5.1/mysql__com_8h-source.html the flag bits are:
		NOT_NULL_FLAG          1         // Field can't be NULL 
		PRI_KEY_FLAG           2         // Field is part of a primary key 
		UNIQUE_KEY_FLAG        4         // Field is part of a unique key 
		MULTIPLE_KEY_FLAG      8         // Field is part of a key 
		BLOB_FLAG             16         // Field is a blob 
		UNSIGNED_FLAG         32         // Field is unsigned 
		ZEROFILL_FLAG         64         // Field is zerofill 
		BINARY_FLAG          128         // Field is binary   
		ENUM_FLAG            256         // field is an enum 
		AUTO_INCREMENT_FLAG  512         // field is a autoincrement field 
		TIMESTAMP_FLAG      1024         // Field is a timestamp 
	*/
	private static $mysqli_flags = array(
		1 => "not_null", //MYSQLI_NOT_NULL_FLAG
		2 => "primary_key", //MYSQLI_PRI_KEY_FLAG
		4 => "unique_key", //MYSQLI_UNIQUE_KEY_FLAG
		8 => "multiple_key", //MYSQLI_MULTIPLE_KEY_FLAG
		16 => "blob", //MYSQLI_BLOB_FLAG
		32 => "unsigned", //MYSQLI_UNSIGNED_FLAG
		64 => "zerofill", //MYSQLI_ZEROFILL_FLAG
		512 => "auto_increment", //MYSQLI_AUTO_INCREMENT_FLAG
		1024 => "timestamp", //MYSQLI_TIMESTAMP_FLAG
		2048 => "set", //MYSQLI_SET_FLAG
		32768 => "numeric", //MYSQLI_NUM_FLAG
		16384 => "multi_index", //MYSQLI_PART_KEY_FLAG
		256 => "enum", //MYSQLI_ENUM_FLAG
		128 => "binary", //MYSQLI_BINARY_FLAG
		4096 => "no_default_value", //
	);
	
	private static $attribute_value_reserved_words = array("CURRENT_DATE", "CURRENT_TIME", "CURRENT_TIMESTAMP", "CURRENT_USER", "DEFAULT", "NULL", "TRUE", "FALSE");
	
	private static $reserved_words = array("*", "ACCESSIBLE", "ADD", "ALL", "ALTER", "ANALYZE", "AND", "AS", "ASC", "ASENSITIVE", "BEFORE", "BETWEEN", "BIGINT", "BINARY", "BLOB", "BOTH", "BY", "CALL", "CASCADE", "CASE", "CHANGE", "CHAR", "CHARACTER", "CHECK", "COLLATE", "COLUMN", "CONDITION", "CONSTRAINT", "CONTINUE", "CONVERT", "CREATE", "CROSS", "CURRENT_DATE", "CURRENT_TIME", "CURRENT_TIMESTAMP", "CURRENT_USER", "CURSOR", "DATABASE", "DATABASES", "DAY_HOUR", "DAY_MICROSECOND", "DAY_MINUTE", "DAY_SECOND", "DEC", "DECIMAL", "DECLARE", "DEFAULT", "DELAYED", "DELETE", "DESC", "DESCRIBE", "DETERMINISTIC", "DISTINCT", "DISTINCTROW", "DIV", "DOUBLE", "DROP", "DUAL", "EACH", "ELSE", "ELSEIF", "ENCLOSED", "ESCAPED", "EXISTS", "EXIT", "EXPLAIN", "FALSE", "FETCH", "FLOAT", "FLOAT4", "FLOAT8", "FOR", "FORCE", "FOREIGN", "FROM", "FULLTEXT", "GET", "GRANT", "GROUP", "HAVING", "HIGH_PRIORITY", "HOUR_MICROSECOND", "HOUR_MINUTE", "HOUR_SECOND", "IF", "IGNORE", "IN", "INDEX", "INFILE", "INNER", 
	"INOUT", "INSENSITIVE", "INSERT", "INT", "INT1", "INT2", "INT3", "INT4", "INT8", "INTEGER", "INTERVAL", "INTO", "IO_AFTER_GTIDS", "IO_BEFORE_GTIDS", "IS", "ITERATE", "JOIN", "KEY", "KEYS", "KILL", "LEADING", "LEAVE", "LEFT", "LIKE", "LIMIT", "LINEAR", "LINES", "LOAD", "LOCALTIME", "LOCALTIMESTAMP", "LOCK", "LONG", "LONGBLOB", "LONGTEXT", "LOOP", "LOW_PRIORITY", "MASTER_BIND", "MASTER_SSL_VERIFY_SERVER_CERT", "MATCH", "MAXVALUE", "MEDIUMBLOB", "MEDIUMINT", "MEDIUMTEXT", "MIDDLEINT", "MINUTE_MICROSECOND", "MINUTE_SECOND", "MOD", "MODIFIES", "NATURAL", "NOT", "NO_WRITE_TO_BINLOG", "NULL", "NUMERIC", "ON", "OPTIMIZE", "OPTION", "OPTIONALLY", "OR", "ORDER", "OUT", "OUTER", "OUTFILE", "PARTITION", "PRECISION", "PRIMARY", "PROCEDURE", "PURGE", "RANGE", "READ", "READS", "READ_WRITE", "REAL", "REFERENCES", "REGEXP", "RELEASE", "RENAME", "REPEAT", "REPLACE", "REQUIRE", "RESIGNAL", "RESTRICT", "RETURN", "REVOKE", "RIGHT", "RLIKE", "SCHEMA", "SCHEMAS", "SECOND_MICROSECOND", 
	"SELECT", "SENSITIVE", "SEPARATOR", "SET", "SHOW", "SIGNAL", "SMALLINT", "SPATIAL", "SPECIFIC", "SQL", "SQLEXCEPTION", "SQLSTATE", "SQLWARNING", "SQL_BIG_RESULT", "SQL_CALC_FOUND_ROWS", "SQL_SMALL_RESULT", "SSL", "STARTING", "STRAIGHT_JOIN", "TABLE", "TERMINATED", "THEN", "TINYBLOB", "TINYINT", "TINYTEXT", "TO", "TRAILING", "TRIGGER", "TRUE", "UNDO", "UNION", "UNIQUE", "UNLOCK", "UNSIGNED", "UPDATE", "USAGE", "USE", "USING", "UTC_DATE", "UTC_TIME", "UTC_TIMESTAMP", "VALUES", "VARBINARY", "VARCHAR", "VARCHARACTER", "VARYING", "WHEN", "WHERE", "WHILE", "WITH", "WRITE", "XOR", "YEAR_MONTH", "ZEROFILL", "GET", "IO_AFTER_GTIDS", "IO_BEFORE_GTIDS", "MASTER_BIND", "ONE_SHOT", "PARTITION", "SQL_AFTER_GTIDS", "SQL_BEFORE_GTIDS");
	
}
?>
