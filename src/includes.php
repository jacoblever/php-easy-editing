<?
class EasyEditingException extends ErrorException {
	function __construct($message) {
		parent::__construct($message);
	}
}

class UserType {

	/** @type string */
	private $displayName;

	/** @type string */
	private $codeName;

	function __construct($displayName, $codeName) {
		$this->displayName = $displayName;
		$this->codeName = $codeName;
		if(is_null($codeName) || $codeName == '') {
			throw new EasyEditingException('A UserType must have a code name');
		}
	}

	/**
	 * @return string
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * @return string
	 */
	public function getCodeName() {
		return $this->codeName;
	}
}

class EasyEditingRow {
	public $content;
	public $clearanceLevel;
	public $inDatabase;
}

class EasyEditingConfiguration {
	/** @type EasyEditingConfiguration */
	public static $current;

	private $userTypesGetter;
	private $currentCodeNameGetter;

	/** @type UserType[] */
	private $userTypesCache;

	/** @type string */
	private $currentCodeNameCache;

	function __construct() {
		self::$current = $this;
	}

	/**
	 * The code name of the minimum UserType needed to administrate EasyEditing.
	 * An administrator can change what UserType is required to edit each item.
	 * @type string
	 */
	public $levelNeededForAdmin;

	/**
	 * The name of the SQL table containing the EasyEditing table.
	 * Defaults to 'easy_editing'
	 * @type string
	 */
	public $tableName = 'easy_editing';

	/**
	 * An action to the UserTypes in your system that can be allowed to edit content ordered by privileges (most
	 * powerful at the bottom).
	 * Each item has a minimum UserType allowed to edit it from this list.
	 * This action will be called when needed and the result will be cached per request for you.
	 * @param () => UserType[] $userTypeGetter
	 */
	public function userTypes($userTypeGetter) {
		$this->userTypesGetter = $userTypeGetter;
	}

	/**
	 * An action top the code name of the UserType of the currently logged in user.
	 * Return '' if the user is not logged in or is not allowed to edit.
	 * This action will be called when needed and the result will be cached per request for you.
	 * @param () => string $currentCodeNameGetter
	 */
	public function currentCodeName($currentCodeNameGetter) {
		$this->currentCodeNameGetter = $currentCodeNameGetter;
	}

	/**
	 * @private Should not be used externally
	 * @return UserType[]
	 */
	public function getUserTypes() {
		if(is_null($this->userTypesCache)) {
			$getter = $this->userTypesGetter;
			$this->userTypesCache = $getter();
		}
		return $this->userTypesCache;
	}

	/**
	 * @private Should not be used externally
	 * @return string
	 */
	public function getCurrentCodeName() {
		if(is_null($this->currentCodeNameCache)) {
			$getter = $this->currentCodeNameGetter;
			$this->currentCodeNameCache = $getter();
		}
		return $this->currentCodeNameCache;
	}

	/**
	 * @private Should not be used externally
	 * @param string $userTypeCodeName
	 * @return bool
	 * @throws EasyEditingException
	 */
	public function isClearedFor($userTypeCodeName) {
		$loggedInUserType = $this->getCurrentCodeName();
		if(is_null($loggedInUserType) || $loggedInUserType == '') {
			return false;
		}

		foreach($this->getUserTypes() as $type) {
			if($type->getCodeName() == $userTypeCodeName) {
				return true;
			}
			if($type->getCodeName() == $loggedInUserType) {
				return false;
			}
		}
		throw new EasyEditingException('User type' . $userTypeCodeName . ' does not exist.');
	}

	/**
	 * @private Should not be used externally
	 * @return bool
	 * @throws EasyEditingException
	 */
	public function isClearedForAdmin() {
		return $this->isClearedFor($this->levelNeededForAdmin);
	}
}

// todo do we need dirname(__FILE__)?
include dirname(__FILE__) . '/config.php';

class EasyEditing {

	/** @type int */
	private $id;

	/** @type PDO */
	private $db;

	/**
	 * @param PDO $db
	 * @param int $id
	 */
	public function __construct(PDO $db, $id) {
		$this->id = $id;
		$this->db = $db;

		$this->handlePostIfNeeded();
		$this->addToDatabaseIfNeeded();
	}

	private function handlePostIfNeeded() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST'
			&& array_key_exists('easy-editing-request', $_POST)
			&& array_key_exists('easy-editing-id', $_POST)
			&& $_POST['easy-editing-id'] == $this->id) {

			if($_POST['easy-editing-request'] == 'update') {
				if (get_magic_quotes_gpc()) {
					$value = htmlspecialchars(stripslashes((string)$_POST['easy-editing-' . $this->id]));
				} else {
					$value = htmlspecialchars((string)$_POST['easy-editing-' . $this->id]);
				}
				$this->update(html_entity_decode($value));
			} else if($_POST['easy-editing-request'] == 'change-clearance-level') {
				$this->changeClearanceLevel($_POST['easy-editing-clearance-level']);
			}
		}
	}

	/**
	 * @return string
	 * @throws EasyEditingException
	 */
	public function getContent() {
		
		$item = $this->getItem($this->db, $this->id);

		if(!EasyEditingConfiguration::$current->isClearedFor($item->clearanceLevel)) {
			return $item->content;
		}

		$content = '';

		// Content viewer
		$content .= '
			<div id="easy-editing-view-' . $this->id . '" style="position: relative;">
				<div class="easy-editing-toolbar">';
		$content .= '
					<div class="easy-editing-toolbar__button easy-editing-toolbar__button--edit" onclick="easyEditing.createEditor(' . $this->id . ');"></div>';
		if (EasyEditingConfiguration::$current->isClearedForAdmin()) {
			$content .= '
					<div class="easy-editing-toolbar__button easy-editing-toolbar__button--settings" onclick="easyEditing.showAdminModal(' . $this->id . ');"></div>';
		}
		$content .= '
				</div>
				<div id="easy-editing-current-content-' . $this->id . '">' . $item->content . '</div>
			</div>';

		// Content edit form
		$content .= '
			<div id="easy-editing-form-' . $this->id . '" style="display:none">
				<form method="post">
					<textarea id="easy-editing-textarea-' . $this->id . '" name="easy-editing-' . $this->id . '"></textarea>
					<input type="hidden" name="easy-editing-request" value="update" />
					<input type="hidden" name="easy-editing-id" value="' . $this->id . '" />
					<div class="easy-editing-top-bar">
						Save regularly, two people cannot edit at the same time.
						<a href="javascript:easyEditing.cancelEditor(' . $this->id . ');" class="btn">Cancel</a>
						<button type="submit" class="btn">Save</button>
					</div>
				</form>
			</div>';

		// Admin modal
		if(EasyEditingConfiguration::$current->isClearedForAdmin()) {
			$content .= '
			<div id="easy-editing-admin-modal-' . $this->id . '" class="modal-dialog">
				<div class="modal-header">
					<h2>Settings for item id "' . $this->id . '"</h2>
					<a href="javascript:easyEditing.hideAdminModal(' . $this->id . ');" class="modal__btn-close">×</a>
				</div>
				<div class="modal-body">
					<label>This item is editable by: ' . $this->makeClearanceLevelDropDown($item) . '</label>
					<p id="easy-editing-admin-status-' . $this->id . '"></p>
				</div>
				<div class="modal-footer">
					<a href="javascript:easyEditing.hideAdminModal(' . $this->id . ');" class="btn">Close</a>
				</div>
			</div>
			<div class="modal-dialog-overlay" onclick="easyEditing.hideAdminModal(' . $this->id . ');"></div>';
		}
		return $content;
	}

	/**
	 * @param EasyEditingRow $item
	 * @return string
	 */
	private function makeClearanceLevelDropDown(EasyEditingRow $item) {
		$text = '<select name="clearance_level" id="easy-editing-admin-select-' . $this->id . '" onchange="easyEditing.saveNewClearanceLevel(' . $this->id . ');">';
		foreach(EasyEditingConfiguration::$current->getUserTypes() as $type) {
			$codeName = $type->getCodeName();
			$isSelected = $item->clearanceLevel == $type->getCodeName();
			$text .= '<option value="' . $codeName . '"' . ($isSelected ? " selected" : "") . '>';
			$text .= $type->getDisplayName();
			$text .= '</option>';
		}
		$text .='</select>';
		return $text;
	}

	/**
	 * @return EasyEditingRow
	 */
	private function getItem() {
		$stmt = $this->db->prepare(sprintf('SELECT content, clearance_level FROM %s WHERE id = :id',
			EasyEditingConfiguration::$current->tableName));
		$stmt->execute(array(
			'id' => $this->id
		));
		$row = $stmt->fetch();

		$content = new EasyEditingRow();
		if($row) {
			$content->content = $row['content'];
			$content->clearanceLevel = $row['clearance_level'];
			$content->inDatabase = true;
		} else {
			$content->content = '';
			$content->clearanceLevel = EasyEditingConfiguration::$current->levelNeededForAdmin;
			$content->inDatabase = false;
		}
		return $content;
	}

	/**
	 * @param string $content
	 * @throws EasyEditingException
	 */
	private function update($content) {
		if(EasyEditingConfiguration::$current->isClearedFor(self::getItem()->clearanceLevel)) {
			$stmt = $this->db->prepare(sprintf('UPDATE %s SET content = :content WHERE id = :id LIMIT 1;',
				EasyEditingConfiguration::$current->tableName));
			$stmt->execute(array(
				'content' => addslashes($content),
				'id' => $this->id
			));
		} else {
			throw new EasyEditingException("User not allowed to edit content");
		}
	}

	/**
	 *
	 * @param string $level
	 * @throws EasyEditingException
	 */
	private function changeClearanceLevel($level) {
		if(EasyEditingConfiguration::$current->isClearedForAdmin()) {
			$stmt = $this->db->prepare(sprintf('UPDATE %s SET clearance_level = :newLevel WHERE id = :id LIMIT 1;',
			EasyEditingConfiguration::$current->tableName));
			$stmt->execute(array(
				'newLevel' => addslashes($level),
				'id' => $this->id
			));
		} else {
			throw new EasyEditingException("Only admins are allowed to change clearance level");
		}
	}

	/**
	 * Create database table and add this id to the database (only for admins)
	 * @throws EasyEditingException
	 */
	private function addToDatabaseIfNeeded() {
		if (!EasyEditingConfiguration::$current->isClearedForAdmin()) {
			return;
		}

		$stmt = $this->db->prepare(sprintf("SHOW TABLES LIKE '%s'",
			EasyEditingConfiguration::$current->tableName));
		$stmt->execute();
		if (!$stmt->fetch()) {
			// Table does not exist, so let's create it
			$stmt = $this->db->prepare(sprintf('
					CREATE TABLE %s (
						id int(10) NOT NULL AUTO_INCREMENT,
						content text NOT NULL,
						clearance_level varchar(15) NOT NULL,
						info text NOT NULL,
						KEY id (id)
					) ENGINE=MyISAM DEFAULT CHARSET=latin1;',
				EasyEditingConfiguration::$current->tableName));
			$stmt->execute();
		}

		if ($this->getItem()->inDatabase == false) {
			$stmt = $this->db->prepare(sprintf('INSERT INTO %s (id, clearance_level) VALUES (:id, :clearanceLevel);',
				EasyEditingConfiguration::$current->tableName));
			$stmt->execute(array(
				'id' => $this->id,
				'clearanceLevel' => EasyEditingConfiguration::$current->levelNeededForAdmin
			));
		}
	}

	/**
	 * The script and link tags to put in your html head.
	 *
	 * @param string $basePath The path to the folder containing the EasyEditing code (defaults to '/easy-editing/')
	 * @return string
	 */
	public static function getHtmlHeader($basePath = '/easy-editing/') {
		if(substr($basePath, -1) != '/') {
			$basePath .= '/';
		}

		return '<script type="text/javascript" src="' . $basePath . 'ckeditor/ckeditor.js"></script>
		<link rel="stylesheet" type="text/css" href="' . $basePath . 'styles.css" />
		<script type="text/javascript" src="' . $basePath . 'scripts.js"></script>';
	}
}
?>
