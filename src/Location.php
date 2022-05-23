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

  function __construct(string $ip=''){
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
  function _instantiate(string $ip=''){
    $info = new \Victorybiz\GeoIPLocation\GeoIPLocation();
    if( !empty($ip) && \filter_var($ip,FILTER_VALIDATE_IP) ){
      $info->setIP($id);
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
    if( \defined('MYSQL_DATA_DB') ){
      $db = MYSQL_DATA_DB;
      $country_name = empty($this->country) ? null : \strtolower($this->country);
      $country_code = empty($this->country_code) ? null : \strtoupper($this->country_code);
      $state_name = empty($this->state) ? null : \strtolower($this->state);
      $city_name = empty($this->city) ? null : \strtolower($this->city);
      $sql = "SELECT
                     (SELECT code FROM `{$db}`.`state` WHERE LOWER(name) = '{$state_name}' LIMIT 1) AS state_code,
                     (SELECT code FROM `{$db}`.`city` WHERE LOWER(name) = '{$city_name}' LIMIT 1) AS city_code
              FROM  `{$db}`.`country`
              LIMIT 1";
      $found = ( new MultiForm($db,'country') )->findBySql($sql);
      if( $found ){
        $this->state_code = $found[0]->state_code;
        $this->city_code = $found[0]->city_code;
      }
    }else{
      $this->errors['Location'][] = [3,256,'MYSQL_DATA_DB => Data storage database is not defined, to ensure better accuracy of location information, complete following steps: \r\n 1. Define constance \'MYSQL_DATA_DB\' storing database name. \r\n 2. Create database with defined name. \r\n 3. Create/Populate table: countries [fields]: id - int(11), iso2 - char(2) [ISO2 country code], name - varchar(150) country name. \r\n 4. Create/Populate table: states [fields]: id - int(11), name - char(30) state name, country_id - int(11). \r\n 5. Create/Populate table: cities [fields]: id - int(11), name - char(30) city name, state_id - int(11). ',__FILE__,__LINE__];
    }
  }

}
