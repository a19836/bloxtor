<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="icon" href="data:;base64,=" />
	<?= $head;?>
</head>
<body>
	<div id="container">
		<div id="header">
			<div id="menu_bar"><?php include $EVC->getViewPath("general/menus");?></div>
		</div>
		<div id="left_column"><?= $left_content;?></div>
		<div id="main_column"><?= $main_content;?></div>
	</div>
</body>
</html>
