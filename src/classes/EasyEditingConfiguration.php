<?php

class EasyEditingConfiguration {
	/** @type EasyEditingConfiguration */
	public static $current;

	private $userTypesGetter;
	private $currentCodeNameGetter;

	/** @type EasyEditingUserType[] */
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
	 * @return EasyEditingUserType[]
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

	/**
	 * The script and link tags to put in your html head.
	 *
	 * @param string $basePath The path to the folder containing the EasyEditing code (defaults to '/easy-editing/')
	 * @return string
	 */
	public static function getHtmlHeaderTags($basePath = '/easy-editing/') {
		if(substr($basePath, -1) != '/') {
			$basePath .= '/';
		}

		return '<script type="text/javascript" src="' . $basePath . 'ckeditor/ckeditor.js"></script>
		<link rel="stylesheet" type="text/css" href="' . $basePath . 'styles.css" />
		<script type="text/javascript" src="' . $basePath . 'scripts.js"></script>';
	}
}
