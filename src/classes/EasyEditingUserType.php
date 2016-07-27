<?php

class EasyEditingUserType {

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
