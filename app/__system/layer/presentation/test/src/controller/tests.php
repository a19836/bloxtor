<?php
/******* PREPARE EVC *******/
$parameter_0 = isset($parameters[0]) ? $parameters[0] : null;
$entity = file_exists($EVC->getEntityPath("tests/" . $parameter_0)) ? "tests/" . $parameter_0 : "tests/home";

$EVC->setEntity($entity);
$EVC->setView("tests/empty");
$EVC->setTemplate("empty");

/******* SHOW EVC *******/
$entity_path = $EVC->getEntityPath( $EVC->getEntity() );
if(file_exists($entity_path)) {
	include $entity_path;
}
else {
	launch_exception(new EVCException(2, $entity_path));
}

$view_path = $EVC->getViewPath( $EVC->getView() );
if(file_exists($view_path)) {
	include $view_path;
}
else {
	launch_exception(new EVCException(3, $view_path));
}

$template_path = $EVC->getTemplatePath( $EVC->getTemplate() );
if(file_exists($template_path)) {
	include $template_path;
}
else {
	launch_exception(new EVCException(4, $template_path));
}
?>
