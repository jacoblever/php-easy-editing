# phpEasyEditing
---------
A very simple PHP library to allow simple content management for your website's admins.

See https://github.com/jacoblever/phpEasyEditing/

#### Getting started
Upload the /src/ directory to your web server. On each page that will use phpEasyEditing include `includes.php` and construct an `EasyEditingConfiguration`:
```php
include 'src/includes.php'; // Include the phpEasyEditing source
$config = new EasyEditingConfiguration();
```
`EasyEditingConfiguration` contains all the configuration phpEasyEditing needs to function. There are 3 types of users who can interact with a phpEasyEditing element:
 - _Anonymous_ visitors who can only view the element,
 - _Registered_ users who are allowed to edit element,
 - _Administrator_ users who can edit elements and set what types of registered users can edit them (this is configurable per element).

So now we tell phpEasyEditing what types of user exist in the system. The users are ordered, ie users have all the powers of a user below them in the list. We also specify the user level needed to be an Administrator, all users below this one will only count as Regestered users.
```php
$config->userTypes(function() {
	$types = array();
	$types[] = new EasyEditingUserType('Webmaster', 'webmaster');
	$types[] = new EasyEditingUserType('Admin', 'admin');
	$types[] = new EasyEditingUserType('Normal Member', 'member');
	return $types;
});
$config->levelNeededForAdmin = 'webmaster';
```
You'll notice each `EasyEditingUserType` has a human readable name (which you might want to localise) and a code, which phpEasyEditing stores in its database.

Now we need to tell phpEasyEditing how to find out what type of user is currently logged in. This function will be called once the first time phpEasyEditing needs it and the request will be cached.
```php
$config->currentCodeName(function() {
	return $myExistingMembershipSystem->userTypeCode; // This should return one of the EasyEditingUserType's codes
});
```
Finally in the `<head>` of your html document add this line to add the phpEasyEditing JavaScript and CSS.
```html
<?php echo EasyEditingConfiguration::getHtmlHeaderTags('src/'); ?>
```
#### Usage
Now we have phpEasyEditing set up, all we need is a database connection in the form of a [PDO](http://php.net/manual/en/book.pdo.php) object and the id of a `EasyEditingElement` in the phpEasyEditing database.
```html
<?php
    $pdo = new PDO(...); // Use your normal database connection string
    $elementId = 1; // This corresponds to the id column of the phpEasyEditing table
    $element = new EasyEditingElement($pdo, $elementId);
    echo $element->getContent(); // Print the content, including editing controls if allowed
?>
```
When an Administrator first loads the page phpEasyEditing will create the phpEasyEditing table and row with this id, if either does not exist.
