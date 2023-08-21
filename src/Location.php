<?php
namespace TymFrontiers;
class Location{

  public $user;
  public $ip;
  public $city;
  public $city_code;
  public $state;
  public $state_code;
  public $country;
  public $country_code;
  public $currency_code;
  public $currency_symbol;
  public $latitude;
  public $longitude;
  public $errors = [];

  function __construct(string|null $ip=''){
    $this->_checkSession();
    $this->_instantiate($ip);
  }
  function _checkSession(){
    global $session;
    if( !($session instanceof Session )  ){
      throw new \Exception('There must be an instance of TymFrontiers\Session in the name of \'$session\' on global scope', 1);
		}
    $this->user = $session->name;
  }
  function _instantiate(string|null $ip=''){
    $info = new \Victorybiz\GeoIPLocation\GeoIPLocation();
    if( !empty($ip) && \filter_var($ip,FILTER_VALIDATE_IP) ){
      $info->setIP($ip);
    }
    $this->ip = $info->getIP();
    $this->city = $info->getCity();
    $this->state = $info->getRegion();
    $this->country = $info->getCountry();
    $this->country_code = $info->getCountryCode();
    $this->currency_code = $info->getCurrencyCode();
    $this->currency_symbol = $info->getCurrencySymbol();
    $this->latitude = $info->getLatitude();
    $this->longitude = $info->getLongitude();
    global $database, $conn;
    $conn = !empty($conn) && $conn instanceof MySQLDatabase ? $conn : (!empty($database) && $database instanceof MySQLDatabase ? $database :null);
    $data_db = \defined("MYSQL_DATA_DB") ? MYSQL_DATA_DB : (\function_exists("\\get_database") ? \get_database("data") : null);
    if ( $conn && $data_db ) {
      $country_name = empty($this->country) ? null : \strtolower($this->country);
      $country_code = empty($this->country_code) ? null : \strtoupper($this->country_code);
      $state_name = empty($this->state) ? null : \strtolower($this->state);
      $city_name = empty($this->city) ? null : \strtolower($this->city);
      $found = (!empty($state_name) && !empty($city_name)) ? ( new MultiForm($data_db, 'countries', "code", $conn) )
        ->findBySql("SELECT
          (SELECT `code` FROM :db:.`states` WHERE LOWER(name) = '{$conn->escapeValue($state_name)}' LIMIT 1) AS state_code,
          (SELECT `code` FROM :db:.`cities` WHERE LOWER(name) = '{$conn->escapeValue($city_name)}' LIMIT 1) AS city_code
        FROM  :db:.`countries`
        LIMIT 1") : null;
      if( $found ){
        $this->state_code = $found[0]->state_code;
        $this->city_code = $found[0]->city_code;
      }
    }
  }

}
