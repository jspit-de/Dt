<?php
//Comment out the following line to use the class without a namespace
namespace Jspit;

use \DateTime as DateTime;
use \DateTimeZone as DateTimeZone;
use \Exception as Exception;
use \LogicException as LogicException;
use \InvalidArgumentException as InvalidArgumentException;
use \IntlDateFormatter as IntlDateFormatter;
use \IntlCalendar as IntlCalendar;
use \DateInterval as DateInterval;

/**
.---------------------------------------------------------------------------.
|  class dt a DateTime extension class                                      |
|   Version: 2.3                                                            |
|      Date: 2023-03-03                                                     |
|       PHP: >= 7.0                                                         |
| ------------------------------------------------------------------------- |
| Copyright © 2014-2022, Peter Junk (alias jspit). All Rights Reserved.     |
'---------------------------------------------------------------------------'
*/

final class Dt extends DateTime{
  //Uncomment the next line if you need macros
  //use \Macro;
  
  const VERSION = '2.3';
  const EN_PHP = 'en_php';
  const EN = 'en';
  const DE = 'de';
  
  const EASTER_WEST = CAL_EASTER_ALWAYS_GREGORIAN;
  const EASTER_EAST = CAL_EASTER_ALWAYS_JULIAN;  //Orthodox
  
  const TO_STRING_FORMAT = 'Y-m-d H:i:s';
  
  const DATE2000Z2 = 40;  //yy < DATE2000Z2 -> 20yy
  
  const PAGE_ENCODING = 'UTF-8';

  protected static $defaultLanguage = self::DE ;

  //Keys ISO 639-1 Language Codes
  protected static $mon_days = array(
    self::EN_PHP => array("January","February","March","April","May","June","July","August","September","October","November","December",
                  "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday","Sunday"),     
    self::DE => array("Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember",
                  "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag","Sonntag"),
  );
  
  //Units for human diffs
  protected static $humanUnits = array(
    self::EN => array('Seconds' => 60,'Minutes' => 60,'Hours' => 24,'Days' => 7,'Weeks' => 1.E9,
      'm' => 'Months','y' => 'Years','y1' => 'Year'),
    self::DE => array('Sekunden' => 60,'Minuten' => 60,'Stunden' => 24,'Tage' => 7,'Wochen' => 1.E9,
      'm' => 'Monate','y' => 'Jahre', 'y1' => 'Jahr'),
    'fr' => array('secondes' => 60,'minutes' => 60,'heures' => 24,'jours' => 7,'semaines' => 1.E9,
      'm' => 'mois','y' => 'années','y1' => 'an'),
  );
  
  protected static $strictModeCreate = false;

  //protected $propContainer = null;
  private $errorInfo = "";
    
  //last match-Array from createFromRegExFormat 
  private $lastMatchRegEx = false;
 
 /* new dt($dt, $timeZone)
  * $dt: string or int/float timestamp or dt-object or DateTime-Object 
  * $timeZone. string or DateTimeZone.Object
  */ 
  final public function __construct($dt = null, $timeZone = null){
    //TimeZone
    if(is_string($timeZone)) {
      try{
        $timeZone = new DateTimeZone($timeZone);
      }
      catch(Exception $e) {
        $tz = self::searchDateTimeZone($timeZone);
        if($tz){
          $timeZone = new DateTimeZone($tz);  
        }
        else {
          throw($e);
        }
      }
    }
    elseif(is_int($timeZone) AND abs($timeZone) < 50400){ //Sec offset
      $hhii = sprintf('%+03d%02d',$timeZone/3600,$timeZone%3600/60);
      $timeZone = new DateTimeZone($hhii);
    }

    if(is_string($dt) AND strpos($dt,"@") === 0){
      //@ at first, process timestamp 
      $dt = (float)substr($dt,1);
    }
    
    //DateTime object
    if($dt instanceof \DateTime or $dt instanceof \DateTimeImmutable) {
      parent::__construct($dt->format('Y-m-d H:i:s.u'), $dt->getTimezone());
      if($timeZone instanceOf \DateTimeZone) {
        parent::setTimeZone($timeZone);
      }      
    }
    
    //null ?
    elseif($dt === null) {
      parent::__construct("now", $timeZone);
    }
    //String and Chain of Strings
    elseif(is_string($dt)) {
      $dt = preg_replace('~ and | AND ~','|',$dt);
      $parts = explode('|', $dt, 2);
      if(strpos($parts[0],"{{") !== false) {
        //wildcards in part 0 
        parent::__construct("now", $timeZone);
        $this->chain($dt);
      }
      else {
        $dtc = $this->santinizeDate($parts[0]);
        parent::__construct($dtc, $timeZone);
        if(isset($parts[1])) {
          //rest of chain
          $this->chain($parts[1]);
        }
      }
    }
    
    //int or float -> Unix Timestamp
    elseif(is_int($dt) or is_float($dt)) {
      if($timeZone === null) {
        $timeZone = new DateTimeZone(date_default_timezone_get());
      }
      $this->setTimestampUTC($dt);
      parent::setTimezone($timeZone);
    }
    //Error
    else {
      throw new Exception("constructor parameter of class ".__CLASS__." must be string, object, int or null");
    }
    //add Property errorInfo
    $errorInfo = self::getLastErrorsAsString();
    //$this->propContainer = (object)null;
    $this->errorInfo = $errorInfo;
    
  }
  
 /*
  * function returns a new dt-object or Bool false if Error
  * first Arg: string or dt-object or DateTime-Object or float-Timestamp or int
  * second Arg: 
  * last Arg: optional $timeZone as string or DateTimeZone.Object
  */ 
  public static function create(/* args */) {
    $args = func_get_args();
    $dt = array_key_exists(0,$args) ? $args[0] : null;
    $timeZone = null;
    //timezone set ?
    if(array_key_exists(1,$args)) {
      $lastArg = array_pop($args);
      if(is_string($lastArg)){
        $timeZone = new DateTimeZone($lastArg);
      }
      elseif(is_int($lastArg)){
        //lastArg back
        array_push($args, $lastArg);
      }
      else {
        $timeZone = $lastArg; 
      }
      
      if(count($args) > 1) {
        //args year,month,..
        $args += array(2019,1,1,0,0,0,0); //+defaults
        $now = explode(" ",date_create('now',$timeZone)->format("Y n j H i s u"));
        for($i=0; $i < 7; $i++){
          if($args[$i] === null) {
            $args[$i] = (int)$now[$i];
            if($i == 6 AND $args[6] == 0) {
              $args[6] = (int)((float)microTime(false) * 1000000);
            }
          }  
        }
        $yearFormat = $args[0] >= 0 ? '%04d' : '%05d';
        $dt = vsprintf($yearFormat.'-%02d-%02d %02d:%02d:%02d.%06d',$args);
      }
    }
    
    try{
      $dateTime = new static($dt,$timeZone);
    }
    catch (Exception $e) {
      trigger_error(
        'Error Method '.__METHOD__
          .', Message: '.$e->getMessage()
          .' stack trace'.$e->getTraceAsString(),
        E_USER_WARNING
      );
      $dateTime = false;
    }
    if(self::$strictModeCreate and self::getLastErrorsAsString() != "") {
      return false;
    }
    return $dateTime;
  }
  
 /*
  * parse a string with a DateTime information with regular expressions
  * regExFormat: string or array with regular Expressions
  * dateStr: String with date information
  * named identifier: <d> Tag, <m> Monat, <Y> Jahr(4 Ziff), <H> Stunde, <i> Minute, <s> Sekunde
  * Example: '~^(?<d>\d{1,2})/(?<m>\d{1,2})/(?<y>\d\d)~';
  * timeZone: String or Object, NULL -> date_default_timezone_get()
  * dateTemplate: String or Object, the template provides the missing information   
  */
  public static function createFromRegExFormat($regExFormats,$dateStr,$timeZone = null, $dateTemplate = 'today') {
    if(is_string($regExFormats)) {
      $regExFormats = [$regExFormats];
    }
    $ok = false;
    $match = [];
    foreach($regExFormats as $regExFormat){
      if($ok = preg_match($regExFormat,(string)$dateStr,$match)) break;
    }
    if(! $ok) return false;
    //remove empty elements
    $match = array_filter($match);
    //TimeZone
    if($timeZone === null) {
      $timeZone = date_default_timezone_get();
    }
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    $dtDateTemplate = self::create($dateTemplate);
    if($dtDateTemplate === false) {
      trigger_error(
        'Error Method '.__METHOD__.', Message: invalid Parameter dateTemplate in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    $bc = isset($match['Y']) && $match['Y'] < 0;  //Before Christ
    if($bc) $match['Y'] = -$match['Y'];
    
    $formatList = 'Y,m,d,H,i,s,u';
    if(isset($match['O'])) $formatList .= ',O';
    $dateTemplateArr = array_combine(
      //array('Y','m','d','H','i','s','u','O'), 
      explode(',',$formatList),
      explode(',',self::create($dateTemplate)->format($formatList))
    );
    $dateArr = array_merge($dateTemplateArr,$match);
    
    $dateStr = '';
    $format = '';
    foreach($dateArr as $formatEl => $value) {
      if(ctype_alpha((string)$formatEl)) {
        $format .= $formatEl.' ';
        if($formatEl == 'F' OR $formatEl == 'M'){  //text Month
          $value = self::translateMonth($value);  
        }
        $dateStr .= $value.' ';        
      }      
    }
    $dateTime = date_create_from_format(trim($format),trim($dateStr),$timeZone);
    $lastErrors = DateTime::getLastErrors();
    if($dateTime === false){
      $errMsg = implode(', ',$lastErrors['errors']);
      trigger_error(
        'Error '.$errMsg.' Method '.__METHOD__.', Message: invalid Parameter regExFormat in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    //strict 
    if(self::$strictModeCreate && $lastErrors && $lastErrors['warning_count'] > 0){
      $errMsg = implode(', ',$lastErrors['warnings']);
      trigger_error(
        $errMsg.'. Method '.__METHOD__.', Message: invalid Date String in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    $dt = self::create($dateTime); 
    if($bc){
      $dt->setDate(-$dt->year);
      $match['Y'] = (string)-$match['Y'];
    }
    $dt->lastMatchRegEx = $match;   
    return $dt;
  }

 /*
  * Parses a time string according to a specified format
  * $format string Format or array with formats 
  * $time string representing the time
  * $timezone dateTimeZone object or string, defeault null for default Timezone
  * Returns a new dt instance or FALSE on failure.
  */
  public static function createDtFromFormat($formats, $time , $timeZone = null )
  {
    if($timeZone === null) {
        $timeZone = date_default_timezone_get();
    }
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    if(is_string($formats)) {
      $formats = [$formats];
    }
    $ok = $dt = false;
    foreach($formats as $format){
      //translate monthname if format contain F or M
      if(strpbrk($format,"FM") !== false) {
        $timeEn = self::translateMonth($time);
        if($timeEn !== false) $time = $timeEn;     
      }
      //Change non ASCII UTF to Space
      $dt = parent::createFromFormat($format, $time , $timeZone);
      if($dt === false) continue;
      if(self::$strictModeCreate) {
        $errInfo = $dt->getLastErrors();
        if( $errInfo['warning_count']+$errInfo['error_count'] > 0 ) {
          continue;
        }
      }
      $ok = true;
      break;
    }
    return $ok ? new static($dt) : false;
  }

 /*
  * Parses a date-time string according to a specified Intl format
  * $format string Format 
  *  http://userguide.icu-project.org/formatparse/datetime
  * $strDateTime string representing the time
  * $timeZone dateTimeZone object or string, defeault null for default Timezone
  * $language string locale-info
  * Returns a new dt instance or FALSE on failure.
  */
  public static function createFromIntlFormat($format, $strDateTime , $timeZone = null, $language = null)
  {
    if(!function_exists('datefmt_create')) {
      throw new Exception(__METHOD__.' need the IntlDateFormatter');
    }
    $timeStamp = self::timeStampFromIntlFormat($format, $strDateTime , $timeZone, $language);
    if($timeStamp !== false) {
      return self::create($timeStamp, $timeZone);    
    }
    //error
    trigger_error(
      'Error Method '.__METHOD__.': parse Error "'.$strDateTime.'" format "'.$format.'"',
      E_USER_WARNING
    );
    return false;
  }    
  
 /*
  * Parses a date-time string according to a specified Intl format
  * $format string Format 
  * $strDateTime string representing the time
  * $timeZone dateTimeZone object or string, defeault null for default Timezone
  * $language string locale-info
  * Returns a timestamp or FALSE on failure.
  */
  public static function timeStampFromIntlFormat($format, $strDateTime , $timeZone = null, $language = null)
  {
    if(!function_exists('datefmt_create')) {
      throw new Exception(__METHOD__.' need the IntlDateFormatter');
    }

    if($timeZone === null) {
        $timeZone = new DateTimeZone(date_default_timezone_get());
    }
    elseif(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    
    if($language === null) $language = self::$defaultLanguage;
    //check calendar in $language
    $calType = (stripos($language,"@calendar") > 0 
      AND stripos($language,"gregorian") === false)
      ? IntlDateFormatter::TRADITIONAL
      : IntlDateFormatter::GREGORIAN;
      
    //format analysis
    $dateType = $timeType = IntlDateFormatter::FULL;
    $regEx = '~^(NONE|FULL|LONG|MEDIUM|SHORT)\+(NONE|FULL|LONG|MEDIUM|SHORT)$~';
    if(preg_match($regEx,$format,$matchIntl)){
      //format contain intl constants
      $dateType = constant("\IntlDateFormatter::".$matchIntl[1]);
      $timeType = constant("\IntlDateFormatter::".$matchIntl[2]);
      $format = NULL;
    }
    $fmt = new IntlDateFormatter($language, $dateType, $timeType, $timeZone, $calType, $format);
    $timeStamp = $fmt->parse($strDateTime);
   
    return $timeStamp;
  }
  
  

 /*
  * create dt from a Julian Date Number
  * $julianDateNumber float Julian Date Number (Days since 1.Jan -4712 12:00)
  * $timezone dateTimeZone object or string, defeault null for default Timezone
  * Returns a new dt instance or FALSE on failure.
  */
  public static function createFromJD($julianDateNumber, $timeZone = null )
  {
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    $jdSeconds = ((float)$julianDateNumber - 1721426) * 86400;
    return Dt::create("12:00 UTC")
      ->setDate(1,1,1)
      ->addSeconds($jdSeconds)
      ->round()
      ->setTimeZone($timeZone)
    ;
  }

 /*
  * create dt from a Microsoft Excel Timestamp (days since Dec 31 1899)
  * @param float/Integer/string timestamp Microsoft Timestamp days since Dec 31 1899
  * If $timestamp is a string, a comma is replaced with a decimal point
  * @param timezone dateTimeZone object or string, default is "UTC" , null for default Timezone
  * @return new dt instance or FALSE on failure
  */
  public static function createFromMsTimestamp($timestamp, $timeZone = "UTC" )
  {
    if(is_string($timestamp)) {
      $timestamp = floatval(str_replace(',','.',$timestamp));
    }
    $unixTs = round(($timestamp - 25569) * 86400, 3);  //ms
    return self::create($unixTs, $timeZone);
  }
  
 /*
  * create from System Time
  * @param $time : date/time get from specific system
  * @param $base : date/time for start epoch or range
  *   default 1970-01-01 Linux/Unix
  * @param float $resolution: resolution of $time in seconds
  *   default 1 second
  * @param timeZone, default default Timezone
  * @return new dt instance or FALSE on failure
  */
  public static function createFromSystemTime(
    $time, 
    $basis = "1970-01-01", 
    $resolution = 1.0, 
    $timeZone = null
  )
  {
    $date = self::create($basis,'UTC');
    if($date === false) return false;
    if(is_string($resolution) AND !is_numeric($resolution)){
      $resolution = self::totalRelTime($resolution);
    }
    return $date->addSeconds(round($time * $resolution,6))
      ->setTimeZone($timeZone);
  }

 /*
  * Convert date from Calendar to Gregorian 
  * @param string $calendar
  * Japanese,Buddhist,Chinese,Persian,Indian,Islamic,Hebrew,Indian,Coptic,Ethiopic
  */
  public function toGregorianFrom($calendar){
    if(!extension_loaded('intl')){
      throw new Exception(__METHOD__.' need the Intl Extension'); 
    }  
    $tz = $this->getTimezone()->getName();
    $cal = IntlCalendar::createInstance($tz, "en_US@calendar=".$calendar);
    if(strtolower($cal->getType()) == 'gregorian') {
      throw new Exception("invalid Parameter '".$calendar."' for ".__METHOD__);  
    }
    $dateArr = explode(" ",$this->format('Y n j H i s'));
    $cal->set((int)$dateArr[0],(int)$dateArr[1]-1,(int)$dateArr[2],(int)$dateArr[3],(int)$dateArr[4],(int)$dateArr[5]);
    $formatter = IntlDateFormatter::create("en_US",
      IntlDateFormatter::SHORT, 
      IntlDateFormatter::MEDIUM,
      $tz, 
      IntlDateFormatter::GREGORIAN,
      "YYYY-M-d HH:mm:ss"
    );
    $strDate = $formatter->format($cal);
    parent::__construct($strDate, new DateTimeZone($tz));
    return $this;
  }
  
 /*
  * get a Microsoft Timestamp (days since Dec 31 1899)
  * @return float (days since Dec 31 1899)
  */
  public function getMsTimestamp()
  {
    return $this->getMicroTime()/86400 + 25569;
  }

  
 /*
  * clone self
  */
  public function copy(){
    return clone $this;
  }

 /*
  * return last match Array from createFromRegExFormat
  * return false, if instance not createt by createFromRegExFormat()
  */
  public function getMatchLastCreateRegEx() {
    return $this->lastMatchRegEx;
  }

 /*
  * set strict Mode for create
  * if strict Mode set, Dt::create return false for invalid dates how 31 feb
  */ 
  public static function setStrictModeCreate($OnOff = true) {
    self::$strictModeCreate = $OnOff;
  }
  
  
 /*
  * set DefaultLanguage 'en','de' used for format
  * return: true ok, false if Error with a E_USER_WARNING  
  */ 
  public static function setDefaultLanguage($defaultLanguage = self::DE) {
    if(array_key_exists($defaultLanguage,self::$mon_days) or $defaultLanguage == self::EN) {
      self::$defaultLanguage = $defaultLanguage;
      return true;
    }
    elseif(($langConfig = self::createLanguageConfig($defaultLanguage))){
      $addOk = self::addLanguage($defaultLanguage,$langConfig);
      if($addOk) self::$defaultLanguage = $defaultLanguage;
      return $addOk;      
    }
    else {
      trigger_error('Unknown Parameter "'.$defaultLanguage.'" for '.__METHOD__, E_USER_WARNING);
      return false;
    }
  }

 /*
  * get DefaultLanguage how 'en','de'
  */ 
  public static function getDefaultLanguage() {
    return self::$defaultLanguage;
  }
  
 /*
  * Dt::addLanguage
  * @param string short language Name ("fr")
  * @param string Name List "janvier,février,.."
  */ 
  public static function addLanguage($language, $nameList) {
    if($language == 'en_php') {
      return false; //don't use
    }
    $list = array_map('trim',explode(",",$nameList));
    //add month and day names
    self::$mon_days[$language] = array_slice($list,0,19);
    //add units
    $unitNames = array_slice($list,19);
    if(count($unitNames) == 8){
      //array('secondes' => 60,'minutes' => 60,'heures' => 24,'jours' => 7,'semaines' => 1.E9,
      //'m' => 'mois','y' => 'années','y1' => 'an'
      $newUnits = array(
        $unitNames[0] => 60, $unitNames[1] => 60, $unitNames[2] => 24,
        $unitNames[3] => 7, $unitNames[4] => 1.E9, 
        'm' => $unitNames[5], 'y' => $unitNames[6], 'y1' => $unitNames[7]
      );
      static::$humanUnits[$language] = $newUnits;
    }    
    return true;
  }
  
 /*
  * default_timezone_set(string $timezone)
  * alias for date_default_timezone_set()
  */ 
  public static function default_timezone_set($timezone) {
    date_default_timezone_set($timezone);
  }
  
 /*
  * returns a formatted date string
  * param $language: 'en' or 'de'
  * throw warnings
  */ 
  public function formatL($format, $language = null) {
    $language = $language === null ? self::$defaultLanguage : $language;
    $regEx = '~^(NONE|FULL|LONG|MEDIUM|SHORT)\+(NONE|FULL|LONG|MEDIUM|SHORT)$~';
    $formatContainIntlConst = (bool)preg_match($regEx,$format,$matchIntl);
    
    if($language == 'en' AND !$formatContainIntlConst) {
      return parent::format($format);
    }

    if(array_key_exists($language,self::$mon_days) AND !$formatContainIntlConst) {
      $strDate = parent::format($format);
      if(preg_match('/[lF]/',$format)) {
        $strDate = str_replace(self::$mon_days[self::EN_PHP],self::$mon_days[$language],$strDate);
      }
      if(preg_match('/[DM]/',$format)) {
        $strDate = str_replace(
          array_map(self::class.'::substr3',self::$mon_days[self::EN_PHP]),
          array_map(self::class.'::substr3',self::$mon_days[$language]),
          $strDate);
      }
    }
    elseif(function_exists('datefmt_create')){
      //check calendar in $language
      $calType = (stripos($language,"@calendar") > 0 
        AND stripos($language,"gregorian") === false)
        ? IntlDateFormatter::TRADITIONAL
        : IntlDateFormatter::GREGORIAN;

      $curTz = $this->getTimezone();
      if($curTz->getName() === 'Z'){
        //INTL don't know Z
        $curTz = new DateTimeZone('UTC');
      }
        
      if($formatContainIntlConst) {
        $fmt = datefmt_create( $language ,
          constant("IntlDateFormatter::".$matchIntl[1]), 
          constant("IntlDateFormatter::".$matchIntl[2]),
          $curTz,
          $calType);
        $strDate = datefmt_format( $fmt ,$this);
      }
      elseif($calType === IntlDateFormatter::TRADITIONAL){
        $fmt = datefmt_create( $language ,
          IntlDateFormatter::FULL, 
          IntlDateFormatter::FULL,
          $curTz,
          $calType,
          $format);
        $strDate = datefmt_format( $fmt ,$this);
      }
      else {
        $formatPattern = strtr($format,array(
          'D' => '{#1}',
          'l' => '{#2}',
          'M' => '{#3}',
          'F' => '{#4}',
        ));
        $strDate = parent::format($formatPattern);
        $regEx = '~\{#\d\}~';
        while(preg_match($regEx,$strDate,$match)) {
          $IntlFormat = strtr($match[0],array(
            '{#1}' => 'E',
            '{#2}' => 'EEEE',
            '{#3}' => 'MMM',
            '{#4}' => 'MMMM',
          ));
          $fmt = datefmt_create( $language ,IntlDateFormatter::FULL, IntlDateFormatter::FULL,
          $curTz,$calType ,$IntlFormat);
          $replace = $fmt ? datefmt_format( $fmt ,$this) : "???";
          $strDate = str_replace($match[0], $replace, $strDate);
        }
      }
    }
    elseif($formatContainIntlConst) {
      //error
      trigger_error('Format '.$format.' for '.__METHOD__.' need IntlDateFormatter', E_USER_WARNING);
      $strDate =  "Error";
    }
    else{
      trigger_error('Language '.$language.' for '.__METHOD__.' not supported', E_USER_WARNING);
      $strDate = parent::format($format);      
    }

    return $strDate;
  }
  
 /*
  * returns a formatted date string
  * @param string $format: Intl ICU format
  * @param string $language: en, de_AT, 
  * throw exeption if error
  */ 
  public function formatIntl($format = null, $language = null) {
    if(!function_exists('datefmt_create')) {
      throw new Exception(__METHOD__.' need the IntlDateFormatter');
    }
    $language = $language === null ? self::$defaultLanguage : $language;
    //check calendar in $language
    $calType = (stripos($language,"@calendar") > 0 
      AND stripos($language,"gregorian") === false)
      ? IntlDateFormatter::TRADITIONAL
      : IntlDateFormatter::GREGORIAN;
      
    $intlFormatter = datefmt_create( $language ,
          IntlDateFormatter::FULL, 
          IntlDateFormatter::FULL,
          $this->getTimezone(),
          $calType,
          $format);
    return datefmt_format($intlFormatter ,$this);
  }
  
 /*
  * returns a formatted date string
  * @param string $format: default 'Y-m-d H:i:s'
  */ 
  public function utcFormat($format = 'Y-m-d H:i:s') {
    return $this
      ->copy()
      ->setTimeZone('UTC')
      ->format($format)
    ;
  }  
  
 /*
  * set Time of day
  * setTime('12:00'), setTime('12:00:05')
  * setTime(13,30), setTime(13), setTime(13,15,45),
  * setTime($date) //use time from $date  
  */ 
  public function setTime($par, $minute = null, $seconds = 0, $microseconds = NULL): self {
    if(is_string($par) && $minute === null && preg_match('/^([0-9]{2}):([0-9]{2})(:([0-9]{2}))?$/',$par,$match)) {
      //00:00[:00]
      $hour = (int)$match[1];
      $minute = (int)$match[2];
      $seconds = isset($match[4]) ? (int)$match[4] : 0;
    }
    elseif($par instanceof \DateTime) {
      //extract time from $par
      list($hour,$minute,$seconds,$microseconds) = explode(' ',$par->format('H i s u'));
    }
    else {
      $hour = $par !== null ? (int)$par : (int)$this->format('H');
      $minute = ($minute !== null) ? (int)$minute : 0;
    }
    if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
      parent::setTime($hour, $minute, $seconds, (int)$microseconds);
    }
    else {
      parent::setTime($hour, $minute, $seconds);
      if($microseconds) $this->setMicroSeconds($microseconds);
    }
  
    return $this;
  }
 
 /*
  * set Date or Year or Month or Day
  * setDate('2000-1-1'), setDate('today')
  * setDate(2000),setDate(null,1),setDate(null,1),setDate(null,null,1)
  * setDate($date) set Date with Date-Part from $date
  */ 
  public function setDate($par=null, $month = null, $day = null): self {
    if(is_string($par) && $month === null && $day === null && ($mdate=date_create($par)) !== false) {
      //a string with date
      $year = (int)$mdate->format('Y');
      $month = (int)$mdate->format('m');
      $day = (int)$mdate->format('d');
    }
    elseif($par instanceof \DateTime) {
      //extract date from $par
      $year = (int)$par->format('Y');
      $month = (int)$par->format('m');
      $day = (int)$par->format('d');
    }
    else {
      $year = ($par !== null) ? (int)$par : (int)$this->format('Y');
      $month = ($month !== null) ? (int)$month : (int)$this->format('m');
      $day = ($day !== null) ? (int)$day : (int)$this->format('d');
    }
    //param: int $year , int $month , int $day
    
    parent::setDate($year, $month, $day);
    return $this;
  }

 /*
  * set Date, Time and Timezone from dt or DateTime-Object
  * @param DateTime $dt
  * @return $this
  */  
  public function setDateTimeFrom(DateTime $dt){
    parent::__construct($dt->format('Y-m-d H:i:s.u'),$dt->getTimeZone());
    return $this;     
  }
  
 /*
  * set Date to Week-number
  * setISOweek(49) //Monday 49.Week, Year and Time not modify
  */
  public function setISOweek($week, $day = 1){
    $year = (int)$this->format('Y');
    parent::setISODate($year , $week, $day);
    return $this;
  }

 /*
  * set Date to Eastern
  * 1600-1-1 < date < 2100-1-1
  * throw exeption if error
  */
  public function setEasterDate($flag = self::EASTER_WEST){
    parent::setTime(0,0,0);
    $year = (int)parent::format('Y');
    $dt = self::modifyToEaster($this, $year, $flag);
    if($dt === false) { 
      throw new Exception('Year for '.__METHOD__.' must between 1600 and 2100');
    }
    return $this;
  }

 /*
  * set Date to first Day of Passover
  * 1600-1-1 < date < 2100-1-1
  * throw Exception if error
  */
  public function setPassoverDate(){
    $year = (int)parent::format('Y');
    if($year < 1900 or $year >= 2100){
      throw new Exception('Year for '.__METHOD__.' must between 1900 and 2100');
    }      
    $modifier = static::passover($year)->format("Y-m-d H:i:s");  
    return parent::modify($modifier);
  }

   /*
  * get Sun Ifo of date
  * @param $lat array [$lat, $lon] or float latitude 
  * @param $lon float Longitude
  * @return array 
  */
  public function getSunInfo($lat = null, $lon = null): array {
    if(is_array($lat)){
      list($lat,$lon) = array_merge(array_values($lat),[null,null]);  
    }
    $location = $this->getTimezone()->getLocation();
    $lat = $lat ?: $location["latitude"];
    $lon = $lon ?: $location["longitude"];
    return date_sun_info($this->getTimeStamp(),$lat,$lon);
  }

 /*
  * set Time to sunset of this Day
  * @param $lat array [$lat, $lon, $zenith] or float latitude 
  * @param $lon float Longitude
  * @param $zenith float zenith
  */
  public function setSunset($lat = null, $lon = null){
    $info = $this->getSunInfo($lat,$lon);
    return $this->setTimeStamp($info['sunset']);
  }

 /*
  * set Time to sunrise of this Day
  * @param $lat array [$lat, $lon, $zenith] or float latitude 
  * @param $lon float Longitude
  * @param $zenith float zenith
  */
  public function setSunrise($lat = null, $lon = null){
    $info = $this->getSunInfo($lat,$lon);
    return $this->setTimeStamp($info['sunrise']);
  }  

 /*
  * setTimezone(string Timezone), convert date in new Timezone
  * Dt::create('24.12.2013 18:00')->setTimezone('America/New_York'); //12:00 America/New_York
  * no $timeZone or NULL: default_timezone
  */
  public function setTimezone($timeZone = null): self
  {
    if(empty($timeZone)) {
      $timeZone = date_default_timezone_get();
    }
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    parent::setTimezone($timeZone);
      
    return $this;  
  }
  
 /*
  * return true if Summertime
  */  
  public function isSummertime(){
    return (bool)parent::format('I');
  }
  
 /*
  * toCET: set Timezone for CET 
  * return Standard-Time (Wintertime)
  */
  public function toCET(){
    $this->setTimezone(new DateTimeZone('CET'));
    return $this;
  }
  
 /*
  * return true if saturday or sunday 
  */  
  public function isWeekend(){
    return parent::format('N') > 5;
  }

 /*
  * return true if the day is from monday to friday 
  */  
  public function isWeekday(){
    return !$this->isWeekend();
  }

 /*
  * is('datestring')
  * @param string $partialDateString(|$partialDateString)..
  * @return bool
  */
  public function is($partialDateString){
    $regExFmt = [
      '/^(?<int>\d{3,4})$/' => 'Y',
      '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)/i' => 'l',
      '/^weekend|weekday|summertime|LeapYear|past|future$/i' => 'function',
      '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]{0,7}$/i' => 'F',
      '/^\d{3,4}-\d\d-\d\d$/' => 'Y-m-d',
      '/^\d{3,4}-\d\d$/' => 'Y-m',
      '/^\d\d-\d\d$/' => 'm-d',
      '/^(?<int>\d{1,2})h$/i' => 'H',
      '/^\d\d:\d\d$/' => 'H:i',
      '/^\d\d:\d\d:\d\d$/' => 'H:i:s'
    ];
    foreach(explode('|',$partialDateString) as $pStr){
      $ret = $this->_is(trim($pStr),$regExFmt);
      if($ret === true) return true;
      elseif($ret !== false){
        throw new LogicException("Unknown Parameter '$partialDateString' ");
      }
    }
    return false;
  }

 /*
  * isSameAs($dateFormat, $dateTime)
  * compare this->format($dateFormat) with $dateTime->format($dateFormat)
  * @param string $dateFormat
  * @param mixed $dateTime: string, dt-object, DateTime-Object, int- or int-Timestamp
  * @return bool 
  */
  public function isSameAs(string $dateFormat = 'YmdHis', $dt = 'Now'){
    $dtObj = self::create($dt,$this->getTimeZone());
    if(!$dtObj) {
      throw new Exception("invalid date '".$dt."' for ".__METHOD__); 
    } 
    $thisFormat = str_replace('t','d',$dateFormat);
    $strNowFmt = $dtObj->format($dateFormat);
    return parent::format($thisFormat) === $strNowFmt;
  }

 /*
  * compare this->format($dateFormat) with $dt->format($dateFormat) use operator $op
  * @param string $op: =,!=,<,>,<=,>=
  * @param mixed $dt: string, dt-object, DateTime-Object, int- or int-Timestamp
  * @param string $dateFormatMask: any datetime format specification, def. 'YmdHisu'
  * @return bool 
  * @throws Exception if invalid $op
  */
  public function isCmp(string $op, $dt, string $dateFormatMask = 'YmdHisu') {
    $dtObj = self::create($dt,$this->getTimeZone());
    if(!$dtObj) {
      throw new Exception("invalid date '".$dt."' for ".__METHOD__); 
    } 
    $strDtFmt = $dtObj->format($dateFormatMask);
    $thisFmt = parent::format($dateFormatMask);
    $result = $this->dynCmp($thisFmt, $op, $strDtFmt);
    if($result !== NULL) return $result;
    //Error
    throw new Exception("invalid comparison operator '".$op."' for ".__METHOD__); 
  }

 /*
  * isCurrent($dateFormat)
  * compare this->format($dateFormat) with now($dateFormat)
  * @param string $dateFormat
  * @return bool 
  */
  public function isCurrent($dateFormat = 'YmdHis'){
    $thisFormat = str_replace('t','d',$dateFormat);
    $strNowFmt = date_create('now',$this->getTimeZone())->format($dateFormat);
    return parent::format($thisFormat) === $strNowFmt;
  }

 /*
  * return true if year is a leap year
  */  
  public function isLeapYear(){
    return (bool)parent::format('L');
  }
  
 /*
  * return true if the current Date is in $dateWeek
  * param $dateWeek string or dt-object or DateTime-Object or int-Timestamp
  * param null for Now
  * return true/false or null if error
  */  
  public function isInWeek($dateWeek = null) {
    $dtWeek = self::create($dateWeek);
    if($dtWeek) return $this->isSameAs('o-W',$dtWeek);
    return null;
  } 

 /*
  * return true if the time of Date
  *  is greater equal then $timeFrom and later then $timeTo 
  * @param string $timeFrom format H:i or H:i:s
  * @param string $timeTo format H:i or H:i:s
  * if $timeFrom > $timeTo -> Interval= $timefrom-24:00 and 00:00-$timeTo
  * @return bool 
  */  
  public function isTimeBetween(string $timeFrom, string $timeTo) : bool {
    if(preg_match('~^\d\d:\d\d$~',$timeFrom)) $timeFrom .= ':00';
    if(preg_match('~^\d\d:\d\d$~',$timeTo)) $timeTo .= ':00';
    if(preg_match('~^(\d\d:\d\d:\d\d){2}$~',$timeFrom.$timeTo)){
      $curTime = parent::format('H:i:s');
      if($timeFrom < $timeTo){
        return $curTime >= $timeFrom AND $curTime < $timeTo;
      }
      else {
        return $curTime >= $timeFrom OR $curTime < $timeTo;
      }
    }
    //error
    throw new Exception('Parameter for '.__METHOD__.' is not a valid time');
  } 

 /*
  * @return true if the current Date is <= now
  */  
  public function isPast() {
    return $this <= date_create('now');
  }

 /*
  * @return true if the current Date is > now
  */  
  public function isFuture() {
    return $this > date_create('now');
  }

 /*
  * @return true if the Date is last Day of the Month
  */  
  public function isLastDayOfMonth() {
    return parent::format('d') === parent::format('t');
  }

  
 /*
  * return integer Seconds since midnight 
  */
  public function getDaySeconds() {
    $secondsDay = ((int)parent::format('H') * 60 + (int)parent::format('i')) * 60 + (int)parent::format('s');
    return $secondsDay;
  }

 /*
  * return integer Minutes since midnight 
  */
  public function getDayMinutes() {
    return (int)parent::format('H') * 60 + (int)parent::format('i');
  }
  
 /*
  * return numeric representation of the quarter (1..4)
  */
  public function getQuarter() {
    $quarter = ((int)parent::format('n')+2)/3;
    return (int)$quarter;
  }

 /*
  * return int age = diff in years from date to now
  */
  public function age($timeToZero = false) {
    $diff = $this->diff();
    $minus = $diff->invert ? -1 : 1;
    if(!$timeToZero) return $minus * $diff->y;
    return $minus * $this->copy()->setTime(0)->diff()->y;
  }

  
 /*
  * return the average Date between current Date and refDate
  */
  public function average($refDate) {
    $ref = self::create($refDate)->getMicroTime();
    $avTs = $ref + ($this->getMicroTime() - $ref)/2;
    return $this->setTimestamp($avTs);
  }


 /*
  * return Count of Days after 0001-01-01 (+1 : counting the first day)
  * Common Era (Gregorian)
  */
  public function toDays() {
    $diff = date_create('today')->setDate(1,1,1)->diff($this);
    return $diff->days+1;
  }

 /*
  * return float JD (Julian Date Number)
  */
  public function toJD() {
    list($y,$m,$d,$h,$i,$s) = explode(' ', parent::format('Y m d H i s'));
    if($m <= 2){
      $y -= 1;
      $m += 12;
    }
    $d += $h/24 + $i/1440 + $s/86400;
    $b = 2 - floor($y/100) + floor($y/400);
    return floor(365.25*($y+4716)) + floor(30.6001*($m+1)) + $d + $b -1524.5;
  }
  
  
 /* 
  * cut rest (floor number units)
  * $Interval: number Seconds or String x Seconds, x Minutes, x Hours (reference 00:00)
  * x days (reference first Day of Month),  x weeks (reference 1900-01-01),
  * x month , x years (reference year 0)
  * round with $round = true
  * example: Dt::create('2013-12-18 03:48')->cut('15 Minutes') //2013-12-18 03:45:00
  */
  public function cut($interval=1, $round = false) {
    if(is_string($interval)) {
      if(preg_match('/^(hour|minute|second|day|week|month|year)$/i',$interval)) {
        $interval = "1 ".$interval;
      }
      if(preg_match('/^[0-9]{1,2} *(hour|min|sec)[ a-z]*$/i',$interval)) {
        $dinterval = DateInterval::createFromDateString($interval);
        $cutSec = ($dinterval->h *60 + $dinterval->i) * 60 + $dinterval->s;
      }
      elseif(preg_match('/^([0-9]{1,2}) *(day|week|mon|year)[ a-z]*$/i',$interval,$match)) {
        $number = (int)$match[1];
        if($number < 1) {
          trigger_error('Intervall for '.__METHOD__.' must > 0', E_USER_WARNING);
          return $this;
        }
        $thisCopy = $this->copy();
        if(stripos($interval,'day')) { 
          $day = (int)parent::format('d');
          $rest = ($day-1)%$number;
          $day -= $rest;
          $this->setDate(null,null,$day);
        }
        elseif(stripos($interval,'mon')) {
          $mon = (int)(parent::format('Y') * 12 + parent::format('n'));
          $mon -= ($mon-1)%$number;
          $this->setDate(0,$mon,1);
        }
        elseif(stripos($interval,'year')) { 
          $year = (int)parent::format('Y');
          $year -= $year%$number;
          $this->setDate($year,1,1);
        }
        else {
          $weeks = $this->weekOf1900 -1;
          $weeks -= ($weeks)%$number;
          $this->setDate(1900,1,1)->modify($weeks.' weeks');
        }
        parent::setTime(0, 0, 0); 
        $this->setMicroSeconds(0);
        
        if($round) {
          $average = $this->copy()->modify($interval)->average($this);
          if($thisCopy >= $average) $this->modify($interval);
        }
        
        return $this;
      }
      else {
        trigger_error('illegal Intervall for '.__METHOD__, E_USER_WARNING);
        return $this;        
      }
    }
    else {
      $cutSec = (int)$interval;
    }
    if($cutSec < 1) {
      trigger_error('Intervall for '.__METHOD__.' must > 0', E_USER_WARNING);
      return $this;
    }
    $daySec = $this->getDaySeconds();
    if($round) {
      $daySec += (int)($cutSec/2);
      $daySec += (int)($this->getMicroSeconds() > 500000);
    }
    $daySec -= $daySec % $cutSec;
    $hour = (int) ($daySec/3600);
    $daySec -= $hour * 3600;
    $minute = (int) ($daySec/60);
    $seconds =  $daySec % 60;
    parent::setTime($hour, $minute, $seconds);
    $this->setMicroSeconds(0);
    return $this;
  }

 /* 
  * get DateTime to closest next interval
  * $interval: number Seconds or String x Seconds, x Minutes, x Hours, 
  * x weeks, x month
  * example: Dt::create('2013-12-18 03:38')->next('15 Minutes') //2013-12-18 03:45
  */
  public function next($interval = '1 sec') {
    if(preg_match('/^(hour|minute|second|day|week|month|year)$/i',$interval)) {
      $interval = "1 ".$interval;
    }
    return $this->cut($interval)->modify($interval);
  }
  
 /* 
  * Round DateTime
  * $interval: number Seconds or String x Seconds, x Minutes, x Hours, 
  * x weeks, x month
  * example: Dt::create('2013-12-18 03:38')->round('15 Minutes') //2013-12-18 03:45:00
  */
  public function round($interval=1) {
    return $this->cut($interval, true);
  }
  
 /*
  * get Modulo (Rest) after cut a Intervall
  * return integer/float
  * param string $interval (p.E. "5 Minutes")
  * param string $unit: Week, Day, Hour, Minute, Second, Year, Month
  *   default $unit get from Interval
  */
  public function getModulo($interval, $unitP = null) {
    if(empty($unitP)) {
      //default Unit from interval
      $unitP = preg_replace('/[^A-Za-z]/', '', $interval);  
      if(empty($unitP)) $unitP = "Second";
    }
    return $this->copy()->cut($interval)->diffTotal($this, $unitP);
  }

 /*
  * provides a copy of the smallest date
  * @param $dates : a array of dates or date1,[date2,..]
  * @return: dt-object
  */  
  public function min(...$dates) {
    $min = min($this->handleParametersMinMax($dates,__METHOD__));
    if($min < $this) {
      parent::__construct($min->format('Y-m-d H:i:s.u'),$min->getTimeZone());
    }
    return $this;
      
  }

 /*
  * provides a copy of the biggest date
  * @param $dates : a array of dates or date1,[date2,..]
  * @return: dt-object
  */  
  public function max(...$dates) {
    $max =  max($this->handleParametersMinMax($dates,__METHOD__));
    if($max > $this) {
      parent::__construct($max->format('Y-m-d H:i:s.u'),$max->getTimeZone());
    }
    return $this;
  }
  
  //internal
  private function handleParametersMinMax(array $dates, $methodName){
    if(array_key_exists(0,$dates) AND is_array($dates[0])){
      $dates = $dates[0];
    }
    foreach($dates as $key => $date){
      if(is_string($date) and substr($date,0,1) == "|"){
        //string start with "|" then use copy and chain
        $date = $this->copy()->chain(substr($date,1));
      }
      else {
        $date = self::create($date,$this->getTimeZone());
      }
      if($date === false) {
        throw new Exception('Parameter for '.$methodName.' is not a valid date');
      }
      $dates[$key] = $date;
    }
    array_unshift($dates, $this);
    return $dates;
  }  

  
 /* 
  * return DateInterval object
  * diff accept as parameter object, string or timestamp 
  */
  public function diff($date = null, $absolute = false): DateInterval {
    if($date instanceof \DateTime) {
      return parent::diff($date, $absolute);
    }
    $dateTime = self::create($date);
    return parent::diff($dateTime, $absolute);
  }

 /*
  * diffTotal return a difference as int/float
  * in the selected unit
  * $date: string or objekt DateTime
  * $unit: Week, Day, Hour, Minute, Second, Year, Month
  * for Year and Month return full Years or Month (integer)
  */
  public function diffTotal($date=null, $unitP = "sec",$monthDependWeighting = false) {

    $refDate = self::create($date,parent::getTimezone());
    if($refDate === false) {
      trigger_error('First Parameter for '.__METHOD__.' is not a valid Date', E_USER_WARNING);
      return false;
    }
    $diff = $this->diff($refDate);
    
    if(strpos($unitP,':') === false){
      //pure unit hour, min..
      $unitList = array(
        'y' => 'Years', 'm' => 'Month', 'w' => 'Weeks','d' => 'Days', 
        'h' => 'Hours', 'i' => 'Minutes', 's' => 'Seconds',
        'ms' => 'Milliseconds', 'mc' => 'MicroSeconds'
      );
      $match = preg_grep('/^'.preg_quote($unitP,'/').'/i',$unitList);
      
      if(empty($match)) {
        trigger_error('Unknown Parameter "'.$unitP.'" for '.__METHOD__, E_USER_WARNING);
        return false;
      }
      else {
        $unit = array_pop($match);
      }
      if($unit == $unitList['y']) { //years
        $minus = (bool)$diff->invert;
        $days = (clone $this)
          ->modify($diff->y.' years')
          ->diff($refDate)
          ->days;
        $floatYears = $diff->y + $days/365.25;
        return $diff->invert ? -$floatYears : $floatYears;
      }
      elseif($unit == $unitList['m']){
        $fMonth = ($refDate->format('Y') - $this->format('Y')) * 12;
        $fMonth += ($refDate->format('m') - $this->format('m'));
        $tsThis = $this->getTimeStamp();
        $tsThis1 = (clone $this)
          ->modify('first day of this month 00:00')
          ->getTimeStamp();
        $fracThis = $tsThis - $tsThis1;
        $monthSecThis = $this->format('t') * 86400;
        $tsRef = $refDate->getTimeStamp();
        $tsRef1 = $refDate
          ->modify('first day of this month 00:00')
          ->getTimeStamp();
        $fracRef = $tsRef - $tsRef1;
        $monthSecRef = $refDate->format('t') * 86400;
        if($monthDependWeighting === false && $fracThis > 0 && $fracRef > 0){
          $monthSecRef = $monthSecThis = 0.5 * ($monthSecRef + $monthSecThis);
        }
        return (float)$fMonth - $fracThis/$monthSecThis + $fracRef/$monthSecRef;
      }
      else {
        $total = $diff->days;
        if($total == 0) {
          if($diff->y != 0 || $diff->m != 0) return false;
          $total = $diff->d;
        }
      }
      $total = $total * 24 + $diff->h;
      $total = $total * 60 + $diff->i;
      $total = $total * 60 + $diff->s;
      //micro Sec
      if(isset($diff->f)) {
        $total += $diff->f;
        $microSec = 0.0;
      }
      else {
        $microSec = (float)('0.'.$refDate->format('u')) - (float)('0.'.$this->format('u')); 
        $total +=  $diff->invert ? -$microSec : $microSec;    
      }
      $total = $diff->invert ? -$total : $total;
      //$total += $microSec; 
      if($unit == $unitList['s']) return $total;
      if($unit == $unitList['ms']) return $total * 1000;
      if($unit == $unitList['mc']) return $total * 1E6;
      $total /= 60;
      if($unit == $unitList['i']) return $total;
      $total /= 60;
      if($unit == $unitList['h']) return $total;
      $total /= 24;
      if($unit == $unitList['d']) return $total;
      $total /= 7;
      return $total;
    }
    else{
      //special time H:i ..
      $minus = $diff->invert ? "-" : "";
      $totalHours = $diff->days * 24 + $diff->h;
      if($unitP == 'H:i'){
        return sprintf('%s%02d:%02d',$minus,$totalHours,round($diff->i + $diff->s/60)); 
      }
      elseif($unitP == 'H:i:s'){
        return sprintf('%s%02d:%02d:%02d',$minus,$totalHours,$diff->i,$diff->s); 
      }
      else{
        trigger_error('Unknown Parameter "'.$unitP.'" for '.__METHOD__, E_USER_WARNING);
        return false;
      }        
    }
  }
  
 /*
  * diffHuman return a difference as human readable string example "3 Month"
  * $date: string, objekt DateTime or int/float Timestamp
  * $language: 'de','en'
  */
  public function diffHuman($date=null, $language = null) {
    //language handling
    $language = $language === null ? self::$defaultLanguage : $language;
    if(!array_key_exists($language,static::$humanUnits)) $language = self::EN;
    $units = static::$humanUnits[$language];
    
    $diff = $this->diffTotal($date);
    if($diff === false) return false; //Warning from diffTotal
    $div = 1;
    $unit = '?';
    foreach($units as $unit => $div){
      if(abs($diff) < $div * 2) break;
      $diff /= $div;
    }
    if($div > 1000 AND abs($diff) > 7) {
      //Month and Years exact
      $dateDiff = $this->diff($date);
      if($dateDiff->y) {
        $diff = $dateDiff->y;
        $unit = $diff == 1 ? $units['y1'] : $units['y'];
        if($dateDiff->invert) $diff = -$diff;
      }
      elseif($dateDiff->m >= 2) {
        $diff = $dateDiff->m;
        $unit = $units['m'];
      }
    }
    return sprintf('%.0F %s',(int)$diff,$unit);
  }
  
 /*
  * diffUTC return the real time differenz as float
  * fractions of seconds as decimal
  * $date: string or objekt DateTime
  * $unit: Hour, Minute, Second
  */
  public function diffUTC($date, $unitP = "sec") {
    $unitList = array('h'=>'Hours','i'=>'Minutes','s'=>'Seconds');
    $match = preg_grep('/^'.$unitP.'/i',$unitList);
    
    if(empty($match)) {
      trigger_error('Unknown Parameter "'.$unitP.'" for '.__METHOD__, E_USER_WARNING);
      return false;
    }
    else {
      $unit = array_pop($match);
    }
    $diffUTC = self::create($date)->getMicroTime() - $this->getMicroTime();
    if($unit == $unitList['i']) {
      $diffUTC /= 60;
    }
    elseif($unit == $unitList['h']) {
      $diffUTC /= 3600;
    }
    return (float)$diffUTC;
  }

 /*
  * calculates the difference and formats the result
  * supports microseconds for php >= 7.1
  * @param mixed $date 
  * @param mixed $format: DateInterval::formats + %G, %v
  * @return string or bool false if error
  */  
  public function diffFormat($date , $format) {
    $dateTime = self::create($date);
    if($dateTime === false) return false;
    $diff = parent::diff($dateTime,false);
    return self::formatDateInterval($format, $diff);
  }

 /*
  * get count of speciific weekdays between dates
  * @param mixed $dayIdentList (int 0=Sun .. 6 =Sat or string 'Sun','Mon'..'Sat'
  *  or rel.DateString or DateString or a comma separated list
  *  null or 'all' is a short for '0,1,2,3,4,5,6'
  * @param mixed $dateTo: string, timestamp or object
  * @param bool $excludeDateTo exclude the top Date
  * $return int, bool false if error
  */
  public function countDaysTo($dayIdentList, $dateTo, $excludeDateTo = false) {
    $weekDays = $this->parseDayIdentList($dayIdentList);
    if($weekDays === false) {
      trigger_error(
        'Error Method '.__METHOD__.', invalid Parameter $dayIdent in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    $start = $this->copy()->setTime(0,0,0);
    $dateTo = self::create($dateTo);
    if($dateTo === false) {
      trigger_error(
        'Error Method '.__METHOD__.', invalid Parameter $dateTo in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    //dateTo < start ?
    $minus = false;
    if($dateTo < $start) {
      $minus = true;
      $dTmp = $start;
      $start = $dateTo;
      $dateTo = $dTmp;
    }
    if($excludeDateTo) $dateTo->modify('-1 Day');
    $fullWeeks = (int)($start->diff($dateTo)->days/7);
    $dayCount = $fullWeeks * count($weekDays);
    $start->modify(($fullWeeks * 7). " Days");
    //partial count
    for( ; $start <= $dateTo; $start->modify("+1 Day")){
      if(in_array($start->format('w'), $weekDays)) ++$dayCount; 
    }
    return $minus ? -$dayCount : $dayCount;
  }
  
 /* 
  * modify with a chain of modifier separated with |
  * $chainModifier : modifier-list
  * $para : Array with optional parameters
  */
  public function chain($chainModifier, array $para = array()) {
    $chainModifier = preg_replace('~ and | AND ~','|',$chainModifier);
    foreach(explode('|',$chainModifier) as $modifier) {
      if(strpos($modifier,"{{") !== false) {
        //special modifier {{ .. }}
        if(!empty($para)) {
          //split $para in strings and closures 
          foreach($para as $key => $par){
            if(is_object($par) && ($par instanceof \Closure)){ 
              $para[$key] = $par($this);
            }
          }
        }
        //replace {{year xxxx}} -> Jan xxxx
        //$modifier = preg_replace('~\{\{year *(\d{4})\}\}~i',' Jan $1 ',$modifier);
        //replace wildcards
        $year = parent::format('Y');
        $replacements = array_merge($para, array(
          '{{year}}' => $year,
          '{{month}}' => parent::format('m'),
          '{{day}}' => parent::format('d'),
          '{{easter}}' => self::easter($year)->format('m/d 00:00'),
          '{{easter_o}}' => self::easter($year,self::EASTER_EAST)->format('m/d 00:00'),
          '{{passover}}' => self::Passover($year)->format('m/d 00:00'),
        ));
        $modifier = str_replace(array_keys($replacements), $replacements, $modifier); 
        //replacements with date formats
        $modifier = $this->replaceWildcards($modifier);
        //condition how "{{?D=Wed}}+1 Day"
        if(preg_match('~^\{\{\?([DdmLYIHisW]+)(!?=|<|>)([^}]+)\}\}(.*)~',$modifier,$match)) {
          $dateDetail = parent::format($match[1]);
          $cmpStr = $match[3];
          $newModifier = $match[4];
          //compare with NOW how {{?Ymd<NOW}}
          $curDate = date_create("now", parent::getTimeZone());
          if(strtoupper($cmpStr) === "NOW") {
            $cmpStr = $curDate->format($match[1]);
          }
          if(($match[2] == "=" AND $dateDetail == $cmpStr)
            OR ($match[2] == "!=" AND $dateDetail != $cmpStr)
            OR ($match[2] == ">" AND $dateDetail > $cmpStr)
            OR ($match[2] == "<" AND $dateDetail < $cmpStr)
          ) $this->modify($newModifier);
        }
        elseif($modifier != "") {
          $this->modify($modifier);
        }
        
      }
      elseif(preg_match('~^([0-9\-/*,]+ ){5}$~',$modifier.' ')){
        //ident Cron-Tab-String how "15 1 * * *" 
        $this->nextCron($modifier);
      }
      else {
        $this->modify($this->santinizeDate($modifier));
      }
    }
  
    return $this;
  }
  
 /*
  * return int/float value in the selected unit from
  * a relativ Time or a DateInterval-Object or number Seconds
  * or time-string 00:03:04.7
  * return false if error
  * unit: 's','m','h','d','w' return float
  * unit: 'y','mo(nth)' return int
  * basis: date basis, date reference: used for month and years in Time
  * Dt::totalRelTime('1 week','days'); //7
  * 
  */
  public static function totalRelTime($relTime, $unit = 'sec', $basis = '2000-1-1') {
    $dateBasis = self::create($basis)->setTime(0,0,0);
    if($dateBasis === false) {
      trigger_error('Third Parameter for '.__METHOD__.' is not a valid Date', E_USER_WARNING);
      return false;
    }
    if($basis !== '2000-1-1') {
      $basis = $dateBasis->format('Y-m-d');
    }
    if(is_float($relTime) OR is_int($relTime)){
      $fullSeconds = floor($relTime);
      $relTime = "00:00:0".sprintf("%0.6F",$relTime-$fullSeconds)." +".$fullSeconds." Seconds";
      return $dateBasis->diffTotal($basis.' '.$relTime, $unit);
    }
    if(is_string($relTime)) {
      if(!preg_match('~[a-z]~i',$relTime)){
        //hh:ii no units
        $seconds = self::timeToSeconds($relTime);
        if($seconds === false) {
          trigger_error('First Parameter "'.$relTime.'" for '.__METHOD__.' is not a valid Time', E_USER_WARNING); 
          return false;          
        }
        $fullSeconds = floor($seconds);
        $relTime = "00:00:0".sprintf("%0.6F",$seconds-$fullSeconds)." +".$fullSeconds." Seconds";
      }
      else {
        $replacements = [
          'y' => 'year ','M' => 'month ','w'=>'week ','d' =>'day ','h'=>'hour ','m' =>'minute ', 's' => 'second '
        ];
        // complete d-> day; h -> hour with space after shortcuts
        // or 2d13m30s -> 2day 13minute 30sec
        $shorts = implode(array_keys($replacements));
        $relTimeSant = preg_replace_callback('~(\d+ ?)(['.$shorts.'])(?=\d| |$)~',
          function($m) use($replacements){return $m[1].$replacements[$m[2]].' ';},
          $relTime
        );
        if($relTimeSant) $relTime = $relTimeSant;
      }
      return $dateBasis->diffTotal($basis.' '.$relTime, $unit);
    } 
    elseif ($relTime instanceof \DateInterval) {
      $dateTo = $dateBasis->copy()->add($relTime);
      return $dateBasis->diffTotal($dateTo, $unit);
    } 
    trigger_error('First Parameter for '.__METHOD__.' is not a valid Time', E_USER_WARNING);    
    return false;
  }
  
 /*
  * timeStrToSeconds: Convert a string "ii:ss,ms" to Seconds (Float)
  * @param string time: hhh:ii hhh:ii:ss hhh:ii.ss,m ii:ss.m
  * >= v1.95 accept negative times
  * return Float or bool false if error
  */
  public static function timeToSeconds($timeString){
    $posTimeString = ltrim($timeString,'-');
    $minus = $posTimeString != (string)$timeString;
    if(!preg_match('~^(\d+)(:\d{1,2}){0,2}([\.,]\d+)?$~',$posTimeString)) {
      return false; 
    }
    $splitP = preg_split("~[.,]~",$posTimeString);
    $splitDp = explode(":",$splitP[0]);
    $seconds = 0.0;
    foreach($splitDp as $val) {
      $seconds = $seconds * 60 + $val;
    }      
    if(isset($splitP[1])){
      //millisec
      $seconds += (float)("0.".$splitP[1]);
    }
    elseif(count($splitDp) == 2) {
      //hh:ii
      $seconds *= 60;
    }
    return $minus ? -$seconds : $seconds;
  }

 /*
  * Calculated from a value (int / float) and a unit a DateInterval
  * support microseconds for PHP >= 7.1
  * unitP: 'week', 'day','hour','minute','second'
  * return DateInterval-Object , false bei Fehler
  */
  public static function date_interval_create_from_float($timeValue, $unitP = "Second"){
    $units = array (
      1 => "seconds",
      60 => "minutes",
      3600 => "hours",
      86400 => "days",
      604800 => "weeks",
    );
    $match = preg_grep('/^'.$unitP.'/i',$units);
    if(! is_array($match) or empty($match)) {
      trigger_error('Second Parameter for '.__METHOD__.' is not a valid Unit', E_USER_WARNING);
      return false;
    }
    $timeValue = (float) $timeValue;
    $absValue = abs($timeValue);
    $unit = reset($match);
    $faktor = array_search($unit,$units);
    $seconds = $absValue * $faktor;
    $fraction = sprintf('%0.6F', fmod($seconds,1));
    $ref = date_create('2000-1-1 00:00:0'.$fraction.' UTC')->modify(floor($seconds).' Seconds');
    $base = date_create('2000-1-1 UTC');
    return $timeValue < 0.0 ? $ref->diff($base) : $base->diff($ref);
  }

 /*
  * formats a DateInterval
  * @param string $format: DateInterval::formats + %G, %v, %w (full weeks!)
  * @param DateInterval $dateInterval
  * @return string 
  */  
  public static function formatDateInterval($format, DateInterval $dateInterval) {
    $days = max($dateInterval->days, $dateInterval->d);
    $weeks = (int)($days/7);
    $totalHours = sprintf('%d',$days * 24 + $dateInterval->h);
    $milliseconds = isset($dateInterval->f) //php 
      ? sprintf('%03d',$dateInterval->f*1000+0.5)
      : 0.0
    ;
    $format = str_replace(
      array('%G','%v','%w'),
      array($totalHours,$milliseconds,$weeks),
      $format
    );
    return  $dateInterval->format($format);
  }

 /*
  * Dateformat for Output (echo) and string casting
  */  
  public function __toString() {
    return $this->format(self::TO_STRING_FORMAT);
  }
  
 /*
  * Format the instance as 'Y-m-d H:i:s.u'
  */  
  public function toStringWithMicro() {
    return parent::format('Y-m-d H:i:s.u');
  }
  
  
 /*
  * get dt as new DateTime (copy)
  */
  public function getDateTime() {
    return date_create(parent::format('Y-m-d H:i:s.u'),parent::getTimezone());
  }  
  
 /*
  * return the Microseconds from DateTime
  */
  public function getMicroSeconds(){
    return (int)$this->format('u');
  }

 /*
  * set microSeconds
  * $microSeconds (Integer) : microSeconds
  */
  public function setMicroSeconds($microSeconds){
    $micro = (int)((int)$microSeconds%1000000);
    $strDateTime = $this->format('Y-m-d H:i:s');
    parent::__construct($strDateTime.'.'.sprintf('%06d',$micro),parent::getTimezone());
    return $this;
  }

 /*
  * set second ($newSecond Integer) and MicroSeconds
  * $param newSecond integer, float or dt, datetime Object
  */ 
  public function setSecond($newSecond = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    if($newSecond instanceof \DateTime){
      $newSecond = $newSecond->format('s.u') + 0.0000001;
    }
    parent::setTime($hour, $minute, (int)$newSecond);
    $secFragment = fmod($newSecond, 1.0);
    if($secFragment >= 0.000001) {
      $this->setMicroSeconds($secFragment * 1000000);
    }
    return $this;
  }

 /*
  * set only minute
  * $param $newMinute integer, float or dt, datetime Object
  */ 
  public function setMinute($newMinute = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    if($newMinute instanceof \DateTime){
      $newMinute = $newMinute->format('i');
    }
    parent::setTime($hour, (int)$newMinute, $second);
    return $this;
  }

 /*
  * set only hour
  * $param $newHour integer, float or dt, datetime Object
  */ 
  public function setHour($newHour = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    if($newHour instanceof \DateTime){
      $newHour = $newHour->format('H');
    }
    parent::setTime((int)$newHour, $minute, $second);
    return $this;
  }

 /*
  * set only the year
  * $param $newYear integer, float or dt, datetime Object or bool
  * if true then the current or the following year is set 
     so that the value lies in the future
  * if false Parameter set current year (Default)
  */ 
  public function setYear($newYear = false) {
    list($month, $day) = explode(':',$this->format('m:d'));
    if(is_int($newYear)) {
      $year = $newYear;
    }
    else{
      $tz = parent::getTimeZone();
      $now = date_create('now', $tz);
      if(is_bool($newYear)) $year = $now->format('Y');
      elseif($newYear instanceof \DateTime){
        $year = $newYear->format('Y');
      }
      elseif(is_string($newYear) AND ($date = date_create($newYear, $tz)) !== false){
        $year = (int)$date->format("Y"); 
      }
      else $year = (int)$newYear;
      if($newYear === true AND $month.$day < $now->format('md')) $year++;
    }
    parent::setDate($year, $month, $day);
    return $this;
  }
  
 /*
  * get a float Timestamp
  */  
  public function getMicroTime() {
    $ts = self::create('1970/1/1','GMT')->diffTotal($this);
    return (float)$ts;
  }
  
 /*
  * set DateTime from int/float-Timestamp
  */  
  public function setTimestamp($unixtimestamp): self
  {
    if(!is_numeric($unixtimestamp)) {
      throw new InvalidArgumentException('Parameter for '.__METHOD__.' is not a valid timestamp');
    }
    $timeZone = parent::getTimezone();
    $this->setTimestampUTC($unixtimestamp);
    parent::setTimezone($timeZone);
    
    return $this;
  }
  
 /*
  * adds a number of months and cut supernumerary
  * Result is always in the current month as DATE_ADD MySQL
  * 2014-1-31 + 1 Month -> 2014-02-28
  * 2014-3-30 - 1 Month -> 2014-02-28  
  */  
  public function addMonthCut($month = 1) {
    $dateAdd = clone $this;
    $dateLast = clone $this;
    $strAdd = '+'.(int)$month.' Month';
    $strLast = 'last Day of '.(int)$month.' Month';
    if ($dateAdd->modify($strAdd) < $dateLast->modify($strLast)) {
      $this->modify($strAdd);
    }
    else {
      $this->modify($strLast);
    }
    return $this;
  }
  
 /*
  * add a relative Time ('1 Hour', '05:03', '00:00:03.5')
  * accept also DateInterval, but DateTime::add is better
  * accept "-10:44" > v 1.89
  */  
  public function addTime($timeInterval) {
    if(is_string($timeInterval) AND substr($timeInterval,0,1) == "-") {
      $seconds = -self::totalRelTime(substr($timeInterval,1));
    }
    else {
      $seconds = self::totalRelTime($timeInterval,'sec',$this);
    }
    if($seconds === false) {
      throw new Exception('Parameter for '.__METHOD__.' is not a valid time');
    }
    return $this->addSeconds($seconds);
  }  

 /*
  * sub a relative Time ('1 Hour', '05:03', '00:00:03.5')
  * accept also DateInterval, but DateTime::sub is better
  */  
  public function subTime($timeInterval) {
    $seconds = self::totalRelTime($timeInterval);
    if($seconds === false) {
      throw new Exception('Parameter for '.__METHOD__.' is not a valid time');
    }
    return $this->addSeconds(-$seconds);
  }

 /*
  * add a number of Seconds (Int/Float)
  * throw exception if error
  * negative values are permissible
  */  
  public function addSeconds($seconds) {
    $seconds -= 0;
    if(is_int($seconds)) {
      $this->modify(sprintf('%+d',$seconds).' Seconds');
    }
    elseif(is_float($seconds)) {  //float
      $seconds += $this->getMicroSeconds()/1000000;
      $fullSeconds = floor($seconds);
      $secFragment = ($seconds - $fullSeconds);
      if($secFragment >= 0.000001) {
        $this->setMicroSeconds($secFragment * 1000000);
      }
      $this->modify(sprintf('%+.0F',$fullSeconds).' Seconds');
    }
    else {
      throw new InvalidArgumentException('Parameter for '.__METHOD__.' is not Int or Float');
    }
    return $this;
  }

 /*
  * Adds daynumber days to the date. 
  * Only the days of the week that are in $dayIdentList are counted.
  *
  * @param $dayNumber > 0 
  * @param mixed $dayIdentList (int 0=Sun .. 6 =Sat or string 'Sun','Mon'..'Sat'
  *  or rel.DateString or DateString or a comma separated list
  *  null or 'all' is a short for '0,1,2,3,4,5,6'
  * @param $filter  optional filter function
  * $return $this
  */
  function addDays($dayNumber,$dayIdentList = "all",$filter = ""){
    $weekDays = $this->parseDayIdentList($dayIdentList);
    if($weekDays === false) {
      throw new Exception('Error Method '.__METHOD__.', invalid Parameter $dayIdentList in '. self::backtraceFileLine());
    }
    $filterExists = is_callable($filter);
    while($dayNumber > 0){
      $this->modify('+1 Day');
      
      if(in_array($this->format('w'),$weekDays) AND (!$filterExists OR $filter($this))){
        --$dayNumber;
      }
    }
    return $this;
  }

 /*
  * check if  cron expression match
  * @return true if dateTime match cron expression
  * @return null if ERROR
  * @para $cronStr cron-expression e.g "15 * * * *"
  */
  public function isCron($cronStr){
    $currArr = explode(' ',$this->format('i H d m w'));
    $cronArr = preg_split('/\s+/',$cronStr); 
    //Array(Minute,Hour, Day, Month, Weekday)
    $ret = NULL;
    if(count($cronArr) == 5) {
      foreach($cronArr as $i => $cronEntry){
        $curr = (int)$currArr[$i];
        $ret = $this->matchCronEntry($curr, $cronEntry);
        if($ret === false) return false;
        if(is_null($ret)) break;
      }
    }
    if($ret === true) return true;
    //error
    $msg = 'invalid Parameter $cronStr "'.$cronStr.'" for '.__METHOD__;
    trigger_error($msg, E_USER_WARNING);
    $this->errorInfo = $msg;
    return null;
  }
  
 /*
  * get the next run date of a cron expression
  * @return dt
  *  if error return 1970-01-01, isError() return true,
  *  more Info with getErrorInfo()  
  * @para $cronStr cron-expression e.g "15 * * * *"
  */
  public function nextCron($cronStr){
    if(preg_match('~^([0-9,*\-/]+ ){4}[0-6,*\-]+~',$cronStr)){
      $this->setSecond(0)->modify("+1 Minute");
      $cronArr = preg_split('/\s+/',$cronStr); 
      //Array(Second, Minute,Hour, Day, Month, Weekday)
      $error = false;  
      if(count($cronArr) == 5) {
        $maxZyk = 1000;
        $sequence = array(3,2,4,1,0);  //month, day, week, hour, minute
        //preparse
        $cronSeq = array();
        foreach($sequence as $i){
          $cronEntry = $cronArr[$i];
          if($cronEntry == "*") continue;
          $cronSeq[$i] = $cronEntry; //not '*'
        }
        while($maxZyk--){
          foreach($cronSeq as $i => $cronEntry){
            $currArr = explode(' ',$this->format('i H d m w'));
            $cronMatch = $this->matchCronEntry($currArr[$i], $cronEntry);
            if($cronMatch === false){
              if($i==3) $this->modify('first Day of next Month  00:00'); //month
              elseif($i == 2 OR $i == 4) $this->modify('next Day 00:00'); //day,weekday
              elseif($i == 1) {
                //hour
                if(ctype_digit($cronEntry)) {
                  parent::setTime($cronEntry,0,0);
                  if($cronEntry < $currArr[1]) $this->modify('+1 day');
                }
                else $this->modify('+1 hour')->setTime(null,0,0); //hour
              }
              else {
                //minute
                if(ctype_digit($cronEntry)){
                  $this->setTime(null,$cronEntry,0);
                  if($cronEntry < $currArr[0]) $this->modify('+1 hour');
                }
                else $this->modify('+1 minute');
              }
              continue 2;
            }
            elseif($cronMatch === null) {
              $error = true;
              break 2;
            }
          }
          break;
        }
        if($maxZyk <= 0) $error = true;
      }
    }
    else $error = true;
    if($error) {
      $msg = 'invalid Parameter $cronStr "'.$cronStr.'" for '.__METHOD__;
      $this->errorInfo = $msg;
      throw new Exception($msg);
    }
    return $this;
  }

 /*
  * get the previous run date of a cron expression
  * @return dt
  *  if error return 1970-01-01, isError() return true,
  *  more Info with getErrorInfo()  
  * @para $cronStr cron-expression e.g "15 * * * *"
  */
  public function previousCron($cronStr){
    if(preg_match('~^([0-9,*\-/]+ ){4}[0-6,*\-]+~',$cronStr)){
      $this->setSecond(0)->modify("-1 Minute");
      $cronArr = preg_split('/\s+/',$cronStr); 
      //Array(Second, Minute,Hour, Day, Month, Weekday)
      $error = false;  
      if(count($cronArr) == 5) {
        $maxZyk = 1000;
        $sequence = array(3,2,4,1,0);  //month, day, week, hour, minute
        //preparse
        $cronSeq = array();
        foreach($sequence as $i){
          $cronEntry = $cronArr[$i];
          if($cronEntry == "*") continue;
          $cronSeq[$i] = $cronEntry; //not '*'
        }
        while($maxZyk--){
          foreach($cronSeq as $i => $cronEntry){
            $currArr = explode(' ',$this->format('i H d m w'));
            $cronMatch = $this->matchCronEntry($currArr[$i], $cronEntry);
            if($cronMatch === false){
              if($i==3) $this->modify('last Day of previous Month  23:59'); //month
              elseif($i == 2 OR $i == 4) $this->modify('previous Day 23:59'); //day,weekday
              elseif($i == 1) {
                //hour
                if(ctype_digit($cronEntry)) {
                  parent::setTime($cronEntry,59,0);
                  if($cronEntry > $currArr[1]) $this->modify('-1 day');
                }
                else $this->modify('-1 hour')->setTime(null,59,0); //hour
              }
              else {
                //minute
                if(ctype_digit($cronEntry)){
                  $this->setTime(null,$cronEntry,0);
                  if($cronEntry > $currArr[0]) $this->modify('-1 hour');
                }
                else $this->modify('-1 minute');
              }
              continue 2;
            }
            elseif($cronMatch === null) {
              $error = true;
              break 2;
            }
          }
          break;
        }
        if($maxZyk <= 0) $error = true;
      }
    } else $error = true;
    if($error) {
      $msg = 'invalid Parameter $cronStr "'.$cronStr.'" for '.__METHOD__;
      $this->errorInfo = $msg;
      throw new Exception($msg);
    }
    return $this;
  }
  
 /*
  * get a cron string from datetime
  * @return string
  */
  public function toCron(){
    return preg_replace('~^0(\d)~','$1',$this->format('i G j n *'));
  }
  
 /*
  * @return: DateTime of Clockchange to Summertime ($toWintertime = false) or to Wintertime,
  *   null if no Clockchange or false if error
  * @params: $toWintertime false or true 
  * @params: $timeZone string or timezoneobject
  */
  public function setClockChange($toWintertime = false){
    $year = $this->format('Y');
    $timeZone = $this->getTimeZone();
    $strDate = self::getClockChangeAsString($year, $toWintertime, $timeZone);
    if(empty($strDate)) return $strDate;
    $this->setTimeZone('UTC')
      ->setDate(substr($strDate,0,10))
      ->setTime(substr($strDate,11,8))
      ->setTimezone($timeZone) ;
    return $this;
  }
    
  
 /*
  * @return: DateTime of Clockchange to Summertime ($toWintertime = false) or to Wintertime,
  *   null if no Clockchange or false if error
  * @params: $year as YYYY, max. 2037
  * @params: $toWintertime false or true 
  * @params: $timeZone string or timezoneobject
  */
  public static function getClockChange($year, $toWintertime = false, $timeZone = null){
    if($timeZone === null) $timeZone = date_default_timezone_get();
    if(is_string($timeZone)) $timeZone = new DateTimeZone($timeZone);
    if(!($timeZone instanceof \DateTimeZone)) return false; //Error
    $strDate = self::getClockChangeAsString($year, $toWintertime, $timeZone);
    if(empty($strDate)) return $strDate;
    $dt = Dt::create($strDate);
    return $dt->setTimeZone($timeZone);
  }
  
  
 /*
  * create Easter-Date for a given year
  * $year : integer 
  * $flag : Dt::EASTER_WEST or Dt::EASTER_EAST
  * return dt object or false if error
  */
  public static function Easter($year, $flag = self::EASTER_WEST){
    return self::modifyToEaster(static::create("today"), $year, $flag);
  }
  
 /*
  * calculate the first full day of Passover (Gauß)
  * @params: $year integer as YYYY, interval 1900 to 2099
  */
  public static function Passover($year){
    $a = (12*$year+12)%19; 
    $b = $year%4;
    $m = 20.0955877 + 1.5542418 * $a + 0.25 * $b - 0.003177794 * $year; 
    $mi = (int)$m;
    $mn = $m-$mi;
    $c = ($mi + 3 * $year + 5 * $b + 1)%7; 
    if($c==2 OR $c==4 OR $c==6) {
      $mi += 1;
    } elseif($c==1 AND $a > 6 AND $mn >= (1367/2160)) {
      $mi += 2;
    } elseif ($c==0 AND $a > 11 AND $mn > (23269/25920)) {
      $mi += 1;
    }
    return static::create($year."-3-13")->modify($mi." Days");    
  }
  
 /*
  * calculate Equinox (Äquinoktium primär)
  * approximate formula
  * @params: $year integer as YYYY
  * @params: string/object $timezone
  * @return date Object
  */
  public static function getEquinox($year,$timeZone = null){
    if(empty($timeZone)) {
      $timeZone = date_default_timezone_get();
    }

    if(! $timeZone instanceof \DateTimeZone) {
      $timeZone = new DateTimeZone($timeZone);
    }
    
    $date = date_create("2000-03-20 07:30", new DateTimeZone("UTC"))
      ->modify((int)(31556925.187471 * ($year-2000))." Sec")
      ->setTimezone($timeZone);
    return $date;
  }  
  
  
 /*
  * returns true if an error or a warning is present
  */  
  public function isError() {
    return $this->errorInfo !== "";
  }

 /*
  * return Errors and Warnings as a string of the form "A parse date what invalid (w)"
  * return empty string if no error
  */  
  public function getErrorInfo() {
    return $this->errorInfo;
  }
  
 /*
  * return the last Date/Time error as string
  * (w) warning, (e) error
  */
  public static function getLastErrorsAsString() {
    $errInfo = self::getLastErrors();
    $errInfoStr = "";
    if($errInfo && $errInfo['warning_count']+$errInfo['error_count'] > 0 ) {
      $errors = array_map(function($p){return $p.'(e)';},$errInfo['errors']);
      $warnings = array_map(function($p){return $p.'(w)';},$errInfo['warnings']);
      $errInfoStr = implode(',',array_merge($errors,$warnings));
    }
    return $errInfoStr;
  }

 /**
  * Get a property of dt
  * @param string $name
  * @return mixed, throw LogicException if error
  * Propertys
  * @property int   $second : format('s')
  * @property int   $minute : format('i')
  * @property int   $hour   : format('G')
  * @property int   $day    : format('j')
  * @property int   $month  : format('n')
  * @property int   $year   : format('Y')
  * @property int   $microsecond : format('u')
  * @property int   $dayOfWeek   : format('N')
  * @property int   $weekOfYear  : format('W')
  * @property int   $daysInMonth : format('t')
  * @property string  $dayName   : format('D') Mon..Sun
  * @property string  $fullDayName   : format('l') Mon..Sun
  * @property int   $dayOfYear : format('z') + 1
  * @property int   $weekOfMonth : start with 1
  * @property int   $weekOf1900 : number of weeks from 1900-01-01 start at 1
  * @property string  $tzName   : Timezone Name
  * @property int   $tzType : Timezone type
  */
  public function __get($name) {
    $identifier = array(
      'year' => 'Y',
      'month' => 'n',
      'day' => 'j',
      'hour' => 'G',
      'minute' => 'i',
      'second' => 's',
      'microSecond' => 'u',
      'dayOfWeek' => 'N',
      'weekOfYear' => 'W',
      'daysInMonth' => 't',
      'dayName' => 'D', //short Name Mon..Sun
      'fullDayName' => 'l'
    );
    if(isset($identifier[$name])) {
      $val = $this->format($identifier[$name]);      
      return is_numeric($val) ? (int)$val : $val;
    }
    if($name === 'dayOfYear') {
      return $this->format('z')+1;
    }
    if($name === 'weekOfMonth') {
      //get integer week number of the current month, 
      //weeks start on Monday and counting always starts with 1
      $dayFirstMonday = date_create('first monday of '.$this->format('F Y'))->format('j');
      $startedWeek = $dayFirstMonday > 1 ? 1 : 0;
      return (int)(($this->format('j') - $dayFirstMonday +7)/7) + $startedWeek;
    }
    if($name === 'weekOf1900'){
      $diff = date_create('1900-01-01')->diff($this);
      $weeks = (int)($diff->days/7);
      return $diff->invert ? -$weeks : $weeks + 1;
    }
    if($name === 'tzName') {
      return $this->getTimezone()->getName();
    }
    if($name === 'tzType') {
      $jsObj = json_decode(json_encode($this));
      return $jsObj->timezone_type;     
    }
    throw new LogicException("Property '$name' not exist");
  }
  
  /**
   * Returns the list of properties to dump on serialize() called on.
   *
   * @return array
   */
  public function __sleep()
  {
    return array('date', 'timezone_type', 'timezone');
  }

  
 /*
  * create array for language translation
  * @param $locale string language 'fr','it'..
  * @return string with config or false if error
  */
  public static function createLanguageConfig($locale = "de")
  {
    if(!function_exists('datefmt_create')) return false; 
  
    $namesMonthDays = array();
    
    //check calendar in $language
    $calType = (stripos($locale,"@calendar") > 0 
      AND stripos($locale,"gregorian") === false)
        ? IntlDateFormatter::TRADITIONAL
        : IntlDateFormatter::GREGORIAN;
    
    $intlDateFormatter = new intlDateFormatter($locale,
      IntlDateFormatter::FULL,IntlDateFormatter::NONE,NULL,$calType,
      "MMMM"
    );
    //check locale
    if(stripos($locale,$intlDateFormatter->getLocale()) === false){
      return false;
    }
    $refDate = date_create("2000-01-01");
    for($i=0;$i<12;$i++){
      $namesMonthDays[] = $intlDateFormatter->format($refDate);
      $refDate->modify("+1 Month");      
    }        
    $intlDateFormatter = new intlDateFormatter($locale,
      IntlDateFormatter::FULL,IntlDateFormatter::NONE,NULL,$calType,
      "EEEE"
    );
    $refDate->modify("next Monday");
    for($i=0;$i<7;$i++){
      $namesMonthDays[] = $intlDateFormatter->format($refDate);
      $refDate->modify("+1 Day");      
    }        
    
    return implode(",",$namesMonthDays);
  }
    
 /*
  * private
  */
  
  //Setzt Float-Timestamp mit UTC-Zeitzone
  private function setTimestampUTC($unixtimestamp){
    $seconds = floor($unixtimestamp);
    $secondsFraktion = $unixtimestamp - $seconds;
    $basis = '1970-01-01 00:00:0'.sprintf('%0.6F',$secondsFraktion);
    
    parent::__construct($basis, new DateTimeZone('UTC'));
    parent::modify(sprintf('%+.0F',(float)$seconds).' Seconds');
    
    return $this;
  }
  
 /*
  * modifications for string expressions
  */
  private function santinizeDate($dt) {
    $dt = trim($dt);
    if(stripos(self::$defaultLanguage,self::DE) === 0) {
      // German notation 13.2 -> 13.2.YYYY
      if(preg_match('/^[0-9]{1,2}\.[0-9]{1,2}\.?$/',$dt)) {
        $dt = rtrim($dt,'.') . date_create('')->format('.Y');
      }
      // German notation 13.2.15 -> 13.2.YYYY
      elseif(preg_match('/^([0-9]{1,2}\.[0-9]{1,2}\.)([0-9]{2})([^0-9][:0-9]+|$)/',$dt,$match)){
        $dt = $match[1].($match[2] < self::DATE2000Z2 ? '20' : '19').$match[2].$match[3];
      }
    }
    //translate if not en
    if(stripos(self::$defaultLanguage,self::EN) !== 0) {
      //no EN notation  27. Mai 2015 -> 27. May 2015
      $dtTrans = $this->translateMonth($dt);
      if($dtTrans !== false) {
        $dt = $dtTrans;
        //remove controls and non AsCII
        $dt = preg_replace('/\p{C}|[^\x1F-\x7F]/u', ' ', $dt);
        $resTags = 'ago|am|apr|aug|day|dec|eig|ele|feb|fif|fir|for|fou|fri|'
         .'hou|jan|jul|jun|las|mar|may|mid|min|mon|nex|nin|nov|now|oct|of|'
         .'pm|pre|sat|sec|sep|sev|six|sun|ten|thi|thu|tod|tue|twe|wed|wee|yea|yes';
        $re = '~((?:'.$resTags.')\p{L}*|utc|[a-z]+/[a-z]+)|(?:\p{L}+)~iu';
        $dt = preg_replace($re,'$1',$dt);
      }
    }
    //reduce microseconds to a maximum of 6
    $dt = preg_replace_callback('~\.\d{7,}~',function($match){
      return ltrim(sprintf('%0.6F',$match[0]),'0');
    },$dt);
    //negative years up to -9999
    $dt = preg_replace_callback(
      '~^(-\d{1,4})([\-/])(\d{1,2})\2(\d{1,2})( |$)~',
      function($m){return sprintf('%05d-%02d-%02d%s',$m[1],$m[3],$m[4],$m[5]);},
      $dt
    );
    return $dt;
  }
  
  //swaps month from defaultLanguage from StrDate -> Eng.
  //Dt::translateMonth("3. Mai 1985") -> ""3. May 1985""
  //return string if ok, otherwise false
  public static function translateMonth($strDate){
    $curLanguage = self::$defaultLanguage === self::EN ? self::EN_PHP : self::$defaultLanguage;
    $month = array_slice(self::$mon_days[$curLanguage],0,12);  //only month
    if(str_ireplace($month,'',$strDate) !== $strDate) {
      //full Month
      return str_ireplace($month, self::$mon_days[self::EN_PHP],$strDate);
    }
    //check short Month
    $shortMonth = array_map(self::class.'::substr3',$month);
    $shortMonthEN = array_map(self::class.'::substr3',self::$mon_days[self::EN_PHP]);
    foreach($shortMonth as $key => $curMon){
      if(preg_match('~\b'.$curMon.'\p{L}*~iu',$strDate,$match)){
        return str_replace($match[0],$shortMonthEN[$key], $strDate);
      }  
    }
    return false;
  }
  
  //return first 3 letters from string
  private static function substr3($str){
    return mb_substr($str,0,3,self::PAGE_ENCODING);
  }
  
  //backtaceInfo
  private static function backtraceFileLine(){
    if (version_compare(PHP_VERSION, '5.4.0', '<') ) {
      $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
    else {
      $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
    }
    return basename($bt[1]['file']).' Line '.$bt[1]['line'];
  }
  
 /* 
  * for isCron
  * return true or false, null if error
  * $curr: integer (Minute, Day..)
  * $cronEnry: string 
  */
  protected function matchCronEntry($curr, $cronEntry){
    if($cronEntry == "*") return true;
    //number ?
    if(ctype_digit($cronEntry)){
      return (bool)((int)$cronEntry == $curr); 
    }
    if(strpos($cronEntry,',')) {
      //list 3,4,5
      if(!preg_match('~^[0-9]+(,[0-9]+)+$~',$cronEntry)) return null;
      return in_array($curr,explode(',',$cronEntry)) ;
    }

    if(strpos($cronEntry,'/')) {
      // */i or n-m/i
      if(preg_match('~^\*/(\d{1,2})$~',$cronEntry,$match)) {
        return ($curr%($match[1]) == 0);
      }
      elseif(preg_match('~^(\d+)-(\d+)/(\d+)$~',$cronEntry,$match)) {
        $n = $match[1];
        if($curr < $n OR $curr > $match[2]) return false;
        $curr -= $n;
        return ($curr%($match[3]) == 0);
      }
      else return null;
    }
    elseif(strpos($cronEntry,'-')){
      //list n-m
      if(!preg_match('~^[0-9]+-[0-9]+$~',$cronEntry)) return null;
      $numberList = explode('-',$cronEntry);
      return ($curr >= $numberList[0] AND $curr <= $numberList[1]);
    }
      //every x how */5
      //
    return null;
  }

 /*
  * @return: DateTime-String of Clockchange to Summertime ($toWintertime = false) or to Wintertime,
  *   null if no Clockchange or false if error
  * @params: $year as YYYY, max. 2037
  * @params: $toWintertime false or true 
  * @params: $timeZone timezoneobject
  */
  private static function getClockChangeAsString($year, $toWintertime,DateTimeZone $timeZone ){
    if($year > 2037) return false;
    $filter = function($value) use ($year,$toWintertime) {
      $isdst = !$toWintertime ;
      return substr($value['time'],0,4) == $year && $value['isdst'] != $toWintertime;
    };
    $tsFrom = strtotime(($year-1).'-01-01');
    $sel = array_filter($timeZone->getTransitions($tsFrom,$tsFrom + 94000000),$filter);
    if(empty($sel))return null;
    $sel = reset($sel);
    return $sel['time'];
  }

 /*
  * helper for Easter-Date
  * $dt : dt object
  * $year : integer 
  * $flag : Dt::EASTER_WEST or Dt::EASTER_EAST
  * return dt object or false if error
  */
  public static function modifyToEaster(Dt $dt, $year, $flag = self::EASTER_WEST) {
    if($year < 1600 or $year >= 2100){
      return false;
    }
    if($flag == self::EASTER_WEST) {
      $basis = $year."-03-21";
    }
    elseif( $flag == self::EASTER_EAST) {
      $basis = $year."-04-03";
    }
    else {
      return false;
    }
    $dt->modify($basis)->modify(easter_days($year, $flag).' Days');
    $korr = -(int) $dt->format('w');
    
    return $dt->modify($korr.' Days');
  }

 /*
  * get DateTimeZone from name fragment or country code
  * @param: string $name, countrycode how "de" or fragment how 'berlin'
  * @param: bool $allAsArray, default false return 1 result or false as error
  * $allAsArray true return array
  * @return: mixed string|array|bool
  */
  public static function searchDateTimeZone($name, $allAsArray = false)
  {
    if(preg_match('~^[a-z][a-z]$~i', $name)) {
      //2 character country code
      $tz = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY,strtoupper($name));
    }
    elseif(strlen($name) < 4) {
      return false;
    }
    else { 
      $regEx = '~'.preg_quote($name,"~").'~iu';
      $tz = preg_grep($regEx, DateTimeZone::listIdentifiers());
    }
    return $allAsArray ? $tz : reset($tz);
  }
    
 /*
  *  replace date format expressions how {{Y-m}} with Values
  */
  private function replaceWildcards($pattern)
  {
    $now = date_create('now',parent::getTimezone());
    $prev = $this;
    return preg_replace_callback(
      '~\{\{#?([YmdHistuvNwWz\-: ]+)\}\}~',
      function($m) use($prev,$now){
        if($m[0][2] == "#") return $now->format($m[1]);
        return $prev->format($m[1]);
      },
      $pattern
    );
  }

  //return true, false or null if error
  private function _is($partialDateString,$regExFmt){
    foreach($regExFmt as $regEx => $fmt){
      if(preg_match($regEx, (string)$partialDateString, $match)){
        if($fmt === 'function'){
          $fct = 'is'.$partialDateString;
          return $this->$fct();
        }
        $strDate = parent::format($fmt);
        if(isset($match['int'])) {
          return (int)$match['int'] === (int) $strDate;
        }
        return stripos($strDate,$partialDateString) === 0;
      }
    }
    // check if rel date how today, yesterday, tomorrow
    $refDate = date_create($partialDateString);
    if(is_object($refDate)){
      return $refDate->format("Y-m-d") === parent::format("Y-m-d");
    }
    return NULL;
  }

  //compare var1 and var2 with dynamic operator in string $op
  private function dynCmp($var1,string $op, $var2){
    switch ($op) {
      case "=":  return $var1 == $var2;
      case ">":  return $var1 >  $var2;
      case "<":  return $var1 <  $var2;
      case "!=": return $var1 != $var2;
      case ">=": return $var1 >= $var2;
      case "<=": return $var1 <= $var2;
      case "==": return $var1 == $var2;
      default:   return null;
    }
  }

  //return array with int or false if error
  protected function parseDayIdentList($dayIdentList){
    $dayIdentList = strtolower($dayIdentList);
    if($dayIdentList !== 'all') {
      $weekDays = explode(",",$dayIdentList);
      foreach($weekDays as &$dayIdent) {
        $dayIdent = trim($dayIdent);
        if(is_numeric($dayIdent)) {
          $dayIdent = (int)$dayIdent;
        }
        elseif($dayIdent !== "") {
          $wDate = date_create($dayIdent);
          $dayIdent = $wDate ? (int)($wDate->format('w')) : false;
        }
        else {
          $dayIdent = false;
        }
        if($dayIdent < 0 or $dayIdent > 6 or $dayIdent === false) {
          return false;
        }
      }
    }
    else {
      //all days of week
      $weekDays = [0,1,2,3,4,5,6];
    }
    return $weekDays;
  }
  
 /*
  * for Test and Development
  */
  public static function getTranslateArray($language = null){
    if($language === null) $language = self::$defaultLanguage;
    if(array_key_exists($language,self::$mon_days)) {
      return self::$mon_days[$language];
    }
    return false;  
  }  
}