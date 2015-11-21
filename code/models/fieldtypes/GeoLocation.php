<?php

/**
 * Implements the "Money" pattern.
 * 
 * @package geoform
 * @subpackage model
 */
class GeoLocation extends Location implements CompositeDBField {

	/**
	 * @var string $getAddress()
	 */
	protected $address;
	
	/**
	 * @param array
	 */
	static $composite_db = array(
		"Address" => "Varchar(255)"
	);
	
	public function __construct($name = null) {
		parent::__construct($name);
	}
	
	public function compositeDatabaseFields() {
		return array_merge(self::$composite_db, parent::$composite_db);
	}

	public function requireField() {
		$fields = $this->compositeDatabaseFields();
		if($fields) foreach($fields as $name => $type){
			DB::requireField($this->tableName, $this->name.$name, $type);
		}
	}

	public function writeToManipulation(&$manipulation) {
		if($this->getAddress()) {
			$manipulation['fields'][$this->name.'Address'] = $this->prepValueForDB($this->getAddress());
		} else {
			$manipulation['fields'][$this->name.'Address'] = DBField::create_field('Varchar', $this->getAddress())->nullValue();
		}
                parent::writeToManipulation($manipulation);
	}
	
	public function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->selectField(sprintf('"%sAddress"', $this->name));
	}

	public function setValue($value, $record = null, $markChanged = true) {
		// @todo Allow resetting value to NULL through Money $value field
		if ($value instanceof GeoLocation && $value->exists()) {
			$this->setAddress($value->getAddress(), $markChanged);
			$this->setLatitude($value->getLatitude(), $markChanged);
			$this->setLongditude($value->getLongditude(), $markChanged);
			if($markChanged) $this->isChanged = true;
		} else if($record && isset($record[$this->name . 'Address']) && isset($record[$this->name . 'Latitude']) && isset($record[$this->name . 'Longditude'])) {
			if($record[$this->name . 'Address'] && $record[$this->name . 'Latitude'] && $record[$this->name . 'Longditude']) {
				$this->setAddress($record[$this->name . 'Address'], $markChanged);
				$this->setLatitude($record[$this->name . 'Latitude'], $markChanged);
				$this->setLongditude($record[$this->name . 'Longditude'], $markChanged);
			} else {
				$this->value = $this->nullValue();
			}
			if($markChanged) $this->isChanged = true;
		} else if (is_array($value)) {
			if (array_key_exists('Address', $value)) {
				$this->setAddress($value['Address'], $markChanged);
			}
			if (array_key_exists('Latitude', $value)) {
				$this->setLatitude($value['Latitude'], $markChanged);
			}
			if (array_key_exists('Longditude', $value)) {
				$this->setLongditude($value['Longditude'], $markChanged);
			}
			if($markChanged) $this->isChanged = true;
		} else {
			// @todo Allow to reset a money value by passing in NULL
			//user_error('Invalid value in Money->setValue()', E_USER_ERROR);
		}
	}

	/**
	 * @return string
	 */
	public function Nice($size = 400) {
		$size = $size.'x'.$size;
		$loc = $this->latitude.",".$this->longditude;
		$marker = 'color:blue%7C'.$loc;
		$imageurl = "https://maps.googleapis.com/maps/api/staticmap?center=".$loc."&size=".$size."&language=".i18n::get_tinymce_lang()."&markers=".$marker."&maptype=roadmap&zoom=14&sensor=false";
		return '<img src="'.$imageurl.'" />';
	}

	/**
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}
	
	/**
	 * @param string
	 */
	public function setAddress($address, $markChanged = true) {
		$this->address = $address;
		if($markChanged) $this->isChanged = true;
	}
	
	/**
	 * @return boolean
	 */
	public function exists() {
		return ($this->getAddress() && parent::exists());
	}
	
	/**
	 * Returns a CompositeField instance used as a default
	 * for form scaffolding.
	 *
	 * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
	 * 
	 * @param string $title Optional. Localized title of the generated instance
	 * @return FormField
	 */
	function scaffoldFormField($title = null) {
		$field = new GeoLocationField($this->name);
		$field->setLocale($this->getLocale());
		
		return $field;
	}
	
	/**
	 * 
	 */
	public function __toString() {
		return (string)$this->getAddress();
	}
}