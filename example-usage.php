<?
// Put your database login details here
$db_host = 'host';
$db_user = 'user';
$db_pwd = 'password';
$database = 'database';
$pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $database, $db_user, $db_pwd);

// Server path to folder containing EasyEditing 
include 'src/includes.php';

$config = new EasyEditingConfiguration();
$config->userTypes(function() {
	$types = array();
	$types[] = new EasyEditingUserType('Member', 'member');
	$types[] = new EasyEditingUserType('Admin', 'admin');
	$types[] = new EasyEditingUserType('Webmaster', 'webmaster');
	return $types;
});
$config->levelNeededForAdmin = 'webmaster';
$config->currentCodeName(function() {
	return 'webmaster';
});

?>
<html>
	<head>
		<title>PHP Easy Editing Example</title>		
		<? echo EasyEditingConfiguration::getHtmlHeaderTags('src/');?>
	</head>
	<body>
		<? $easyEditingObject1 = new EasyEditingElement($pdo, 1);
		echo $easyEditingObject1->getContent(); ?>
	
		<? $easyEditingObject2 = new EasyEditingElement($pdo, 2);
		echo $easyEditingObject2->getContent();	?>
	</body>
</html>