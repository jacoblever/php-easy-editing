<?
$config = new EasyEditingConfiguration();
$config->userTypes(function() {
	$types = array();
	$types[] = new UserType('Member', 'member');
	$types[] = new UserType('Admin', 'admin');
	$types[] = new UserType('Webmaster', 'webmaster');
	return $types;
});
$config->levelNeededForAdmin = 'webmaster';
$config->currentCodeName(function() {
	return 'webmaster';
});
