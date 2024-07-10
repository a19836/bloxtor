<?php

/*
 * copy this script into your server, as index.php info/index.php and info/news.php,
 * and call it with your browser, to generate data to include new FakeServer tests
 * with new server configurations
 */

 
$varsToTest = array('HTTP_HOST', 'HTTPS', 'REQUEST_METHOD', 'SERVER_NAME','SERVER_ADDR',
              'SERVER_PORT', 'REQUEST_URI', 'QUERY_STRING', 'DOCUMENT_ROOT', 
              'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PATH_INFO', 'PATH_TRANSLATED',
              'PHP_SELF', 'ORIG_PATH_INFO', 'ORIG_PATH_TRANSLATED', 'ORIG_SCRIPT_FILENAME',
              'PHPRC', 'REDIRECT_URL', 'CONTENT_TYPE'
              );
 
header('Content-type: text/plain');
foreach($varsToTest as $name) {
    if (isset($_SERVER[$name])) {
        $l = 40-strlen($_SERVER[$name]);
        if ($l  < 0)
            $l =1;
        echo '            $this->assertEquals(\''.$_SERVER[$name].'\','.str_repeat(' ',$l).' $_SERVER[\''.$name."']);\n";
    }
    else {
        echo '            $this->assertFalse('.str_repeat(' ', 38).' isset($_SERVER[\''.$name."']));\n";
    }
}

echo '            $this->assertEquals('.var_export($_GET,true).','.str_repeat(' ', 38).' $_GET);'."\n";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo '            $this->assertEquals('.var_export($_POST,true).','.str_repeat(' ', 38).' $_POST);'."\n";
}
else if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo '            global $HTTP_RAW_POST_DATA;'."\n";
    echo '            $this->assertEquals(\''.file_get_contents("php://input").'\','.str_repeat(' ', 38).' $HTTP_RAW_POST_DATA);'."\n";
}
