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
	$types[] = new EasyEditingUserType('Webmaster', 'webmaster');
	$types[] = new EasyEditingUserType('Admin', 'admin');
	$types[] = new EasyEditingUserType('Normal Member', 'member');
	return $types;
});

// Only a user logged in as webmaster (eg currentCodeName below returns 'webmaster')
// and above can change who can edit elements
$config->levelNeededForAdmin = 'webmaster';

$config->currentCodeName(function() {
	// This should return the code for the EasyEditingUserType of the currently
	// logged in user
	// Here we are hard coding that everyone is a webmaster (don't do this!)
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
