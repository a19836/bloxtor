<?
//More info in: https://code.iamkate.com/php/diff-implementation/
$path_1 = __DIR__ . "/old.txt";
$path_2 = __DIR__ . "/new.txt";

// include the Diff class
require_once __DIR__ . '/Differ.php';

use \Diff\Differ as Differ;

/*
The compare function is used to compare two strings and determine the differences between them on a line-by-line basis.
Setting the optional third parameter to true will change the comparison to be character-by-character.

The result of calling the Differ::compareFiles function is an array. Each value in the array is itself an array containing two values. The first value is a line (or character, if the third parameter was set to true) from one of the strings or files being compared. The second value is one of the following constants:
- Differ::UNMODIFIED: The line or character is present in both strings or files
- Differ::DELETED: The line or character is present only in the first string or file
- Differ::INSERTED: The line or character is present only in the second string or file
*/
$diff = Differ::compareFiles($path_1, $path_2);
//$diff =Differ::compareFiles($path_1, $path_2, true);

/*
Outputs the result of comparing two files.

Differ::toString:
	Each line in the resulting string is a line (or character) from one of the strings or files being compared, prefixed by two spaces, a minus sign and a space, or a plus sign and a space, indicating which string or file contained the lines. For example:
	An unmodified line
	- A deleted line
	+ An inserted line

Differ::toHTML:
	The toHTML function behaves similarly to the toString function, except that unmodified, deleted, and inserted lines are wrapped in span, del, and ins elements respectively, and the default separator is <br>.
	
Differ::toTable:
	The toTable function produces a more advanced output. It returns the code for an HTML table whose columns contain the text of the two strings or files. Each row corresponds either to a set of lines that have not been modified, or to a set of lines that have been deleted from the first string or file and a set of lines that have been added to the second string or file. The function takes three parameters: the differences array, an amount of extra indentation to use in each line of the resulting HTML (which defaults to no extra indentation), and a separator (which defaults to <br>).
*/
//$output = Differ::toString($diff);
//$output = Differ::toHTML($diff);
$output = Differ::toTable($diff);

/*
Styling the differences table
	The toTable function applies various classes to the code it returns, including the class ‘diff’ on the table element itself. At a minimum the table cells should be styled so that text appears at the top, as neighbouring cells may contain differing amounts of text. If the strings or files being compared are source code, white space should be preserved and the text should be shown in a monospace typeface. For example:
	.diff td{
	  vertical-align : top;
	  white-space    : pre;
	  white-space    : pre-wrap;
	  font-family    : monospace;
	}
*/
?>
<html>
<head>
	<style>
	.diff td{
	  vertical-align : top;
	  white-space    : pre;
	  white-space    : pre-wrap;
	  font-family    : monospace;
	}
	.diffDeleted {
		background:red;
	}
	.diffInserted {
		background:green;
	}
	</style>
</head>
<body>
	<h1>Diff between 2 files</h1>
	<?= $output ?>
</body>
</html>
