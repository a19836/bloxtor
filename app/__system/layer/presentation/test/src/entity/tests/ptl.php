<?php
//http://localhost/springloops/phpframework/trunk/tests/ptl
//http://jplpinto.localhost/__system/test/tests/ptl

//PTL - PHP Template Language test
include_once get_lib("org.phpframework.ptl.PHPTemplateLanguage");

function pau($x = null, $y = null, $w = null, $t = null) {}
function funcXXX($x = null, $y = null, $w = null, $t = null) {}
function callFuncXX($x = null, $y = null, $w = null, $t = null) {}
function callArray($x = null, $y = null, $w = null, $t = null) {return array();}
$GLOBALS["arr"] = array();

$template = '
<ptl:var:PaginationLayout new PaginationLayout(0, 50, array("current_page" =&gt; 0), "current_page") />
<php:funcXXX>
<php:funcXXX jp 12>
<php:funcXXX jp . pau (1 + 12.3412 1 < 2)>
<php:funcXXX (12.3412 as) jp>
<php:funcXXX (we rr) as as>
<ptl:funcXXX ("we rr") as as />
<ptl:echo foo((12.3412 as) jp) />
<ptl:echo foo((we rr) as as) />
<php:print jp . "pauá" (1 + 12.3412 1 < 2)>';
/*$template = '
<php:echo $_GET?print_r($_GET, 1):"NO GET ARRAY">
<php:echo $_GET ? 123.234 asd : "">
<php:echo $_GET ?"":"asd" asd212>
<php:echo   (     $_GET?"":"" )>
<php:echo +$_GET[name]>
<php:echo +.$_GET[name]>
<php:echo +pau()>
<php:echo + ()>
<php:echo as +asdsd-12>
<php:echo as +asd " " sd-12>
<php:echo as +asd" "sd-12>
<ptl:echo asd/ss{$var[asd][asd(asd 12)]}ss/asd>
<ptl:echo asd/ss${var[asd][asd(asd 12)]}ss/asd>
<ptl:echo asd/s"s{$var[asd][asdasd(f)]}ss/asd">
<ptl:echo "asd/ss$var[asd][asdasd]ss/asd">
<ptl:echo "bla ble ." $input[username] ". bli blo." $input[name] ". blu"/>';*/
/*$template = '
<php:define NAME name>
<php:definevar:name NAME>
<php:var:name NAME>
<php:var:name NAME $name>
<php:var:y 12>
<php:echo name intval($y) "," callFuncXX (asd, floatVal(123), array(1,2,asd))>
<php:echo $name intval($y) "," callFuncXX (asd, floatVal(123), array(1,2,asd))>
<ptl:echo $arr[$_GET[0]]$arr[ $_GET[$name ] ] or $arr[ $_GET[name ]][joao][paulo] or $arr[str_replace(search replacement, $name)]>
<php:var:name \'.\' intval($y) . callFuncXX (asd, floatVal(123), array(1,2,asd), paulo>
- <php:var:name intval($y) + callFuncXX (asd floatVal(123) . 2 array(1 2 asd) (joao paulo) pinto)>
';*/
/*$template = '
<php:if $name == joao || intval($y) &gt; 1 && callFuncXX (12 floatVal(sads), array(1,2,asd))>
<php:elseif $name == joao || intval($y) &gt; 1 && !callFuncXX (12 floatVal(sads), array(1,2,asd))>
</php:if>

<php:for $i = $y; $i < intval($y) . callFuncXX (asd, floatVal(123), array(1,2,asd)); $i++></php:for>
<php:for $i = 0 $i < (intval($y) callFuncXX (asd, floatVal(123), array(1,2,asd))) $i++></php:for>

<php:foreach $GLOBALS[arr] $item></php:foreach>
<php:foreach array((arr jp) 12) k $item></php:foreach>
<php:foreach array(arr jp 12) k $item></php:foreach>
<php:foreach callArray (asd, floatVal(123)) k item></php:foreach>
';*/
/*$template = '
<php:switch $name>
	<php:case joao>
		<php:echo JP>
		<php:break>
	<php:default>
		<php:echo OTHER>
</php:switch>
<php:switch name>
	<php:default>
		<php:echo OTHER>
</php:switch>

<php:try>
	<php:echo try some code here>
	
	<php:throw new Exception("asdasd", 123)>
	<php:throw funcXXX(new Exception("asdasd", 123))>
	<php:throw $e>
	<php:throw e>
	<php:throw:Exception asdasd 123>
<?:catch Exception exc>
	<php:echo CATCHED>
</php:try>
';*/
/*$template = '

<php:class:MyClass:extends:stdClass>
	<php:var:public:bar 123>
	<php:var:CONST:bar2 123>
	
	<php:function:public:static:foo $x $y = 0>
		<php:return INSIDE OF FUNCTION>
	</php:function>
</php:class:MyClass>

<?:function:foo x y = 0>
	<php:return INSIDE OF FUNCTION>
</php:function:foo>
';*/
/*$template = '
<?:code $i = 0 * 2; $x = "asd"; $obj = new MyClass(); $obj-&gt;bar="asd"; echo MyClass::bar2; >
<ptl:code $obj-&gt;bar = 123;>
<php:code $obj-&gt;bar = MyClass::foo(1);>

<!--php:code sad asd-->
';*/
/*$template = '
<ptl:var:asd new XXX(asd, 123, array(12,s))/>
<ptl:var:asd "new" XXX(asd, 123, array(12,s))/>
<ptl:var:asd-&gt;xxx 234 asd/>
<php:var:Obj::bar $MyClass-&gt;foo(1)>
<ptl:var:asd["xxx"] $foo["bar"]/>
<ptl:var:asd-&gt;xxx/>
<ptl:var:asd["xxx"]/>
<ptl:var:asd[asd]/>
<ptl:var:asd/>
<ptl:var:asd />
<ptl:incvar:asd 1 />
<ptl:decvar:asd 1 />
<ptl:joinvar:asd joao />
<ptl:concatvar:asd paulo />

<php:var:x "asd&gt;">
<php:var:x asd&gt;as2&gt;32as>
<php:var:x 2&gt;32>
<php:var:x "2&gt;32">

<php:var:x "false" >
<php:var:x true>
<php:var:x false >
<php:var:x is $asd false>?
<php:var:x is $asd . false sd>
<ptl:if $x == true || false != $x || "false" != 12 ></ptl:if>

<php:var:x &gt; &amp;gt; &amp;amp;gt; >
<php:var:x "&gt; &amp;gt; <> &amp;amp;gt;" >
';*/
/*$template = '
\\<ptl:echo \'asd"asd\'/>
<ptl:echo asd\"asd />
<ptl:echo asd\\"asd />\\
<ptl:echo "asd\"asd" />
<ptl:echo assd"as2sd />\\

<!--php:include $path /asd/qwe/$asd/as12s.php>
bla ble comments here
<php:include $path \'/asd/qwe/$asd/as12s.php\'-->

<!-- but leave this comments -->
some html here
';*/

/*$template = '
<div class="div_class">
	<form action="?name=<?:echo $_GET[name]>" method="post">
		<php:for ($i = 0) ($i < 2) $i++>
			<php:if $_GET[name] == 123 && 1 == 1>
				<input type="text" name="name" value="<php:echo $_GET[name]>" />
			<php:elseif asd == 123>
				<input type="text" name="name" value="<php:echo $_GET[name]>" />
			<ptl:else>
				<textarea name="name"><?:echo $_GET[name]></textarea>
			</?:if>
		</php:for>
		
		<?:echo joao 12.3>
		<!--?:echo "joao 123"-->
	</form>
</div>';*/
/*$template = "<div class=\"form-group photo_id hidden\">
   <div class=\"form-input\">
      <input type=\"hidden\" class=\"form-control \" value=\"<ptl:echo str_replace('\"', '&quot;', (\$input[photo_id] )) />\" photoUrl=\"<ptl:echo str_replace('\"', '&quot;', (\$input[photo_url] )) />\" onRemovePhotoConfirmationMessage=\"<ptl:echo str_replace('\"', '&quot;', (translateProjectText(\$EVC, 'Do you really want to delete this photo?'))) />\" name=\"photo_id\" />
   </div>
</div>";*/

/*$template = '<div class="nav-text<ptl:if strlen(\$item[label]) &gt; 22> nav-text-slide</ptl:if>"><ptl:echo \$item[label]/>:<ptl:echo strlen(\$item[label])></div>
<div class="nav-text<ptl:echo strlen(\$item[label]) &gt; 22? " nav-text-slide" : ""/>"><ptl:echo \$item[label]/>:<ptl:echo strlen(\$item[label])></div>
<div class="nav-text<ptl:if strlen($item[label]) &gt; 22> nav-text-slide</ptl:if>"><ptl:echo $item[label]/>:<ptl:echo strlen($item[label])></div>
<div class="nav-text<ptl:echo strlen($item[label]) &gt; 22? " nav-text-slide" : ""/>"><ptl:echo $item[label]/>:<ptl:echo strlen($item[label])></div>';*/

//$template = '<ptl:echo addcslashes(str_replace("\n", "", foo($x)), "\'") />';
//$template = "<ptl:echo str_replace('</textarea', '&lt;/textarea', (\$_POST[description] ? \$_POST[description] : \$input[appointment][description])) />";
//$template = "<ptl:echo str_replace('</textarea', '&lt;/textarea', \$asd !== \$_POST[description] ? \$_POST[description] : \$input[appointment][description]) />";
//$template = "<span class=\"badge badge-<ptl:echo \$item[active] ? 'success' : 'secondary'/>\"><ptl:echo translateProjectText(\$EVC, \$item[active] ? 'Active' : 'Inactive')/></span>";
//$template = '<ptl:echo $asd && "asd" />';
//$template = '<ptl:echo $asd || "asd" />';
//$template = '<php:for $i = $y ? 2 : 7; $i < ($g ? 10 : 11); $i++></php:for>';
//$template = '<php:for $i = $y ? 2 : 7; $i < 10 ? true : false; $i++></php:for>';
//$template = '<php:for $i = ($x ? 2 : 1) ? ($w ? 4 : 5) : ($u ? 6 : 7); $i < 10 ? true : false; $i++></php:for>';
//$template = '<php:for $i = ($x ? 2 : 1) ? ($w ? 4 : 5) : ($u ? 6 : 7); $i < 10 ? (4 ? 4:0) : ($h?true:false); $i++></php:for>';
//$template = '<php:for $i = $x ? ($y ? 2 : 1) : 7; $i < 10; $i++></php:for>';
//$template = '<php:for $i = ($x ? ($y ? 2 : 1) : 7); ($i < 10 ? true : false); $i++></php:for>';
/*$template = '<php:funcXXX (12.3412 as) jp>
<php:funcXXX (we rr) as as>
<ptl:echo foo((12.3412 as) jp) />
<ptl:echo foo((we rr) as as) />
<php:throw:Exception (we rr) as as>
<php:throw:Exception (12.3412 as) jp>
<php:function:foo $x $y = a(3 as, (as ass))></php:function>
<php:function:foo $x $y = a((as ass) 3 as)></php:function>
<php:foreach array((12.3412 as) jp) $item></php:foreach>
<php:foreach array((we rr) as as) k $item></php:foreach>';*/

echo "********************* TEMPLATE ***************************\n";
echo "$template\n";
echo "\n\n********************* CODE ***************************\n";
$PHPTemplateLanguage = new PHPTemplateLanguage();
echo $PHPTemplateLanguage->getTemplateCode($template);
echo "\n\n********************* HTML ***************************\n";
echo $PHPTemplateLanguage->parseTemplate($template);
die();

?>
