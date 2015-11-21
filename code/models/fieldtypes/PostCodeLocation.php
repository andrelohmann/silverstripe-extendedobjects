<?php

/**
 * PostCodeLocation
 * 
 * @package geoform
 * @subpackage model
 */
class PostCodeLocation extends DBField implements CompositeDBField {

	/**
	 * @var string $getPostcode()
	 */
	protected $postcode;

	/**
	 * @var string $getCountry()
	 */
	protected $country;

	/**
	 * @var double $getLatitude()
	 */
	protected $latitude;

	/**
	 * @var double $getLongditude()
	 */
	protected $longditude;

	/**
	 * @var boolean $isChanged
	 */
	protected $isChanged = false;
	
	/**
	 * @var string $locale
	 */
	protected $locale = null;
	
	/**
	 * @param array
	 */
	static $composite_db = array(
		"Postcode" => "Varchar(255)",
		"Country" => "Varchar(255)",
		"Latitude" => 'Double',
		"Longditude" => 'Double'
	);
	
	public function __construct($name = null) {
		parent::__construct($name);
	}
	
	public function compositeDatabaseFields() {
		return self::$composite_db;
	}

	public function requireField() {
		$fields = $this->compositeDatabaseFields();
		if($fields) foreach($fields as $name => $type){
			DB::requireField($this->tableName, $this->name.$name, $type);
		}
	}

	public function writeToManipulation(&$manipulation) {
		if($this->getPostcode()) {
			$manipulation['fields'][$this->name.'Postcode'] = $this->prepValueForDB($this->getPostcode());
		} else {
			$manipulation['fields'][$this->name.'Postcode'] = DBField::create_field('Varchar', $this->getPostcode())->nullValue();
		}
		
		if($this->getCountry()) {
			$manipulation['fields'][$this->name.'Country'] = $this->prepValueForDB($this->getCountry());
		} else {
			$manipulation['fields'][$this->name.'Country'] = DBField::create_field('Varchar', $this->getCountry())->nullValue();
		}
		
		if($this->getLatitude()) {
			$manipulation['fields'][$this->name.'Latitude'] = $this->getLatitude();
		} else {
			$manipulation['fields'][$this->name.'Latitude'] = DBField::create_field('Double', $this->getLatitude())->nullValue();
		}
		
		if($this->getLongditude()) {
			$manipulation['fields'][$this->name.'Longditude'] = $this->getLongditude();
		} else {
			$manipulation['fields'][$this->name.'Longditude'] = DBField::create_field('Double', $this->getLongditude())->nullValue();
		}
	}
	
	public function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->selectField(sprintf('"%sPostcode"', $this->name));
		$query->selectField(sprintf('"%sCountry"', $this->name));
		$query->selectField(sprintf('"%sLatitude"', $this->name));
		$query->selectField(sprintf('"%sLongditude"', $this->name));
	}

	public function setValue($value, $record = null, $markChanged = true) {
		// @todo Allow resetting value to NULL through Money $value field
		if ($value instanceof PostCodeLocation && $value->exists()) {
			$this->setPostcode($value->getPostcode(), $markChanged);
			$this->setCountry($value->getCountry(), $markChanged);
			$this->setLatitude($value->getLatitude(), $markChanged);
			$this->setLongditude($value->getLongditude(), $markChanged);
			if($markChanged) $this->isChanged = true;
		} else if($record && isset($record[$this->name . 'Postcode']) && isset($record[$this->name . 'Country']) && isset($record[$this->name . 'Latitude']) && isset($record[$this->name . 'Longditude'])) {
			if($record[$this->name . 'Postcode'] && $record[$this->name . 'Country'] && $record[$this->name . 'Latitude'] && $record[$this->name . 'Longditude']) {
				$this->setPostcode($record[$this->name . 'Postcode'], $markChanged);
				$this->setCountry($record[$this->name . 'Country'], $markChanged);
				$this->setLatitude($record[$this->name . 'Latitude'], $markChanged);
				$this->setLongditude($record[$this->name . 'Longditude'], $markChanged);
			} else {
				$this->value = $this->nullValue();
			}
			if($markChanged) $this->isChanged = true;
		} else if (is_array($value)) {
			if (array_key_exists('Postcode', $value)) {
				$this->setPostcode($value['Postcode'], $markChanged);
			}
			if (array_key_exists('Country', $value)) {
				$this->setCountry($value['Country'], $markChanged);
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
	public function getPostcode() {
		return $this->postcode;
	}
	
	/**
	 * @param string
	 */
	public function setPostcode($postcode, $markChanged = true) {
		$this->postcode = $postcode;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}
	
	/**
	 * @param string
	 */
	public function setCountry($country, $markChanged = true) {
		$this->country = $country;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * @return double
	 */
	public function getLatitude() {
		return $this->latitude;
	}

	/**
	 * @param double $amount
	 */
	public function setLatitude($latitude, $markChanged = true) {
		$this->latitude = (double)$latitude;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * @return double
	 */
	public function getLongditude() {
		return $this->longditude;
	}

	/**
	 * @param double $amount
	 */
	public function setLongditude($longditude, $markChanged = true) {
		$this->longditude = (double)$longditude;
		if($markChanged) $this->isChanged = true;
	}
	
	/**
	 * @return boolean
	 */
	public function exists() {
		return ($this->getPostcode() && $this->getCountry() && is_numeric($this->getLatitude()) && is_numeric($this->getLongditude()));
	}
	
	public function isChanged() {
		return $this->isChanged;
	}
		
	/**
	 * @param string $locale
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
	}
	
	/**
	 * @return string
	 */
	public function getLocale() {
		return ($this->locale) ? $this->locale : i18n::get_locale();
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
	public function scaffoldFormField($title = null) {
		$field = new PostCodeLocationField($this->name);
		$field->setLocale($this->getLocale());
		
		return $field;
	}
	
	/**
	 * 
	 */
	public function __toString() {
		return (string)$this->getPostcode().', '.(string)$this->getCountry();
	}
        
	/**
	 * return a SQL Bounce for WHERE Clause
	 */
	public function getSQLFilter($radius, $scale = 'km'){
		// set Latitude and Longditude Columnnames
		GeoFunctions::$Latitude = $this->name.'Latitude';
		GeoFunctions::$Longditude = $this->name.'Longditude';

		return GeoFunctions::getSQLSquare($this->getLatitude(), $this->getLongditude(), $radius, $scale);
	}

	/**
	 * return the Distance SQL String
	 */
	public function getSQLOrder(){
		// set Latitude and Longditude Columnnames
		return 'geodistance('.$this->name.'Latitude,'.$this->name.'Longditude,'.$this->getLatitude().','.$this->getLongditude().')';
	}

	/**
	 * return the Distance to the given 
	 */
	public function getDistance($lat, $lng, $scale = 'km'){
		return GeoFunctions::getDistance($this->getLatitude(), $this->getLongditude(), $lat, $long, $scale);
	}
}