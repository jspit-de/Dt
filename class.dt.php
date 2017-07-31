<?php
/**
.---------------------------------------------------------------------------.
|  class dt a DateTime extension class                                      |
|   Version: 1.4.15                                                         |
|      Date: 2017-07-31                                                     |
|       PHP: 5.3.8+                                                    |
| ------------------------------------------------------------------------- |
| Copyright © 2014..16, Peter Junk (alias jspit). All Rights Reserved.      |
' ------------------------------------------------------------------------- '
 *
 * 2014-10-07 : + create_from_RegExFormat
 * 2014-10-08 : + float Timestamp
 * 2014-10-27 : + addMonthCut
 * 2014-11-10 : + microsek Timestamp
 * 2014-12-19 : + diff
 * 2015-01-07 : + EasterDay
 * 2015-01-12 : + diffUTC
 * 2015-01-20 : + dt. Format dd.mm.yy
 * 2015-03-31 : + addSeconds, addTime, subTime
 * 2015-04-02 : + setStrictModeCreate, getLastErrorsAsString, isError, getErrorInfo
 * 2015-04-29 : + isWeekend, isPublicHoliday
 * 2015-08-06 : + date_interval_create_from_float
 * 2016-11-15 : + isLeapYear
 * 2017-01-19 : + cloneSelf (>1.4.11 copy())
 * 2017-04-27 : + Regionen (Part 2 Code ISO 3166-2:DE) for isPublicHoliday
 * 2017-05-02 : + chain with optional parameterlist (V 1.4.7)
 * 2017-05-03 : + month, year for diffTotal (V 1.4.8)
 * 2017-07-03 : setTime + microseconds (PHP >= 7.1) (V 1.4.8)
 * 2017-07-11 : new Name getMicroTime, no overwerite getTimestamp
 * 2017-07-12 : add day, week and month to cut() (V 1.4.12)
 * 2017-07-27 : + isCron() : check if  cron expression match (V 1.4.13)
 * 2017-07-28 : + nextCron() : date of next cron expression match (V 1.4.14)
 */

class dt extends DateTime{

  const AUTO = 'auto';  //detect from 'HTTP_ACCEPT_LANGUAGE'
  const EN = 'en';
  const DE = 'de';
  
  const EASTER_WEST = CAL_EASTER_ALWAYS_GREGORIAN;
  const EASTER_EAST = CAL_EASTER_ALWAYS_JULIAN;  //Orthodox
  
  const TO_STRING_FORMAT = 'Y-m-d H:i:s';
  
  const DATE2000Z2 = 40;  //yy < DATE2000Z2 -> 20yy
  
  const PAGE_ENCODING = 'UTF-8';
  
  //24.12 and 31.12 not in list
  const DE_HOLIDAYLIST = '1.1,E-2,E+0,E+1,1.5,E+39,E+49,E+50,3.10,25.12,26.12,31.10.2017,
  6.1:BW.BY.ST,E+60:BW.BY.HE.NW.RP.SL.SN.TH,15.8:BY.SL,31.10:BW.BB.MV.SN.ST.TH,1.11:BW.BY.NW.RP.SL,B:SN';
  
  protected static $defaultLanguage = self::DE ;
  
  //Schlüssel identisch mit ISO 639-1 Language Codes
  protected static $mon_days = array(
    self::EN => array("January","February","March","April","May","June","July","August","September","October","November","December",
                  "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday","Sunday"),     
    self::DE => array("Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember",
                  "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag","Sonntag"),
  );
  
  protected static $strictModeCreate = false;
  
  //dd.mm every year, E-2 Easter - 2 Days, dd.mm.yyyy fix Date
  private static $holidayList = self::DE_HOLIDAYLIST;
  
  //protected $propContainer = null;
  private $errorInfo = "";
    
  //last match-Array from createFromRegExFormat 
  private $lastMatchRegEx = false;
 
 /* new dt($dt, $timeZone)
  * $dt: string or int/float timestamp or dt-object or DateTime-Object 
  * $timeZone. string or DateTimeZone.Object
  */ 
  public function __construct($dt=null, $timeZone = null){
    //TimeZone
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    
    //DateTime object
    if($dt instanceof DateTime) {
      if($timeZone === null) {
        $timeZone = $dt->getTimezone();
      }      
      parent::__construct($dt->format('Y-m-d H:i:s.u'), $timeZone);
    }
    
    //null
    elseif($dt === null) {
      parent::__construct($dt, $timeZone);
    }
    //String and Chain of Strings
    elseif(is_string($dt)) {
      $aDateExpr = explode('|', $dt);
      $dtc = $this->santinizeGermanDate($aDateExpr[0]);
      parent::__construct($dtc, $timeZone);
      if(count($aDateExpr) > 1) {
        array_shift($aDateExpr);  //remove first
        foreach($aDateExpr as $dateExpr) {
          $dateExpr = trim($dateExpr);
          if($dateExpr === '{{easter}}') $this->setEasterDate();
          else $this->modify($this->santinizeGermanDate($dateExpr));
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
  * $dt: string or dt-object or DateTime-Object
  * $timeZone. string or DateTimeZone.Object
  */ 
  public static function create($dt = null, $timeZone = null) {
    try{
      $dateTime = new self($dt,$timeZone);
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
  * dateStr: String with date information
  * named identifier: <d> Tag, <m> Monat, <Y> Jahr(4 Ziff), <H> Stunde, <i> Minute, <s> Sekunde
  * Example: '~^(?<d>\d{1,2})/(?<m>\d{1,2})/(?<y>\d\d)~';
  * timeZone: String or Object, NULL -> date_default_timezone_get()
  * dateTemplate: String or Object, the template provides the missing information   
  */
  public static function createFromRegExFormat($regExFormat,$dateStr,$timeZone = null, $dateTemplate = 'today') {
    if(! preg_match($regExFormat,(string)$dateStr,$match)) {
      return false;
    }
    //remove empty elements
    $match = array_filter($match);
    //TimeZone
    if($timeZone === null) {
      $timeZone = date_default_timezone_get();
    }
    if(is_string($timeZone)) {
      $timeZone = new DateTimeZone($timeZone);
    }
    //date Template
    $dtDateTemplate = self::create($dateTemplate);
    if($dtDateTemplate === false) {
      trigger_error(
        'Error Method '.__METHOD__.', Message: invalid Parameter dateTemplate in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
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
      if(ctype_alpha($formatEl)) {
        $format .= $formatEl.' ';
        if($formatEl == 'F' OR $formatEl == 'M'){  //text Month
          $value = self::translateMonth($value);  
        }
        $dateStr .= $value.' ';        
      }      
    }
    $dateTime = date_create_from_format(trim($format),trim($dateStr),$timeZone);
    if($dateTime === false){
      $lastErrors = date_get_last_errors();
      $errMsg = implode(', ',$lastErrors['errors']);
      trigger_error(
        'Error '.$errMsg.' Method '.__METHOD__.', Message: invalid Parameter regExFormat in '. self::backtraceFileLine(),
        E_USER_WARNING
      );
      return false;
    }
    $dt = self::create($dateTime); 
    $dt->lastMatchRegEx = $match;   
    return $dt;
  }
  
 /*
  * clone self
  */
  public function copy(){
    $clone = clone $this;
    return $clone;
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
  * if strict Mode set, dt::create return false for invalid dates how 31 feb
  */ 
  public static function setStrictModeCreate($OnOff = true) {
    self::$strictModeCreate = $OnOff;
  }
  
  
 /*
  * set DefaultLanguage 'en','de' used for format
  * return: true ok, false if Error with a E_USER_WARNING  
  */ 
  public static function setDefaultLanguage($defaultLanguage = self::DE) {
    if(array_key_exists($defaultLanguage,self::$mon_days) or $defaultLanguage == self::AUTO) {
      self::$defaultLanguage = $defaultLanguage;
      return true;
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
  * dt::addLanguage
  * par: array with 12 full month names and 7 day names
  * example array: ['fr' => ["Janvier",..,"Décembre","Dimanche" ..]]
  */ 
  public static function addLanguage(array $month_and_days) {
    if(array_key_exists('en',$month_and_days)) {
      return false;
    }
    self::$mon_days = array_merge(self::$mon_days,$month_and_days);
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
  * extends the format method of the base class
  * param $language: 'en' or 'de'
  */ 
  public function formatL($format, $language = null) {
    $language = $language === null ? self::$defaultLanguage : $language;
    if($language == self::AUTO && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $language = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2)); 
    }
  
    $strDate = parent::format($format);

    if($language != self::EN && array_key_exists($language,self::$mon_days)) {
      if(preg_match('/[lF]/',$format)) {
        $strDate = str_replace(self::$mon_days['en'],self::$mon_days[$language],$strDate);
      }
      if(preg_match('/[DM]/',$format)) {
        $strDate = str_replace(
          array_map(array('self','substr3'),self::$mon_days['en']),
          array_map(array('self','substr3'),self::$mon_days[$language]),
          $strDate);
      }
    }
    
    return $strDate;
  }
  
 /*
  * set Time of day
  * setTime('12:00'), setTime('12:00:05')
  * setTime(13,30), setTime(13), setTime(13,15,45),
  * setTime($date) //use time from $date  
  */ 
  public function setTime($par, $minute = null, $seconds = 0, $microseconds = NULL) {
    if(is_string($par) && $minute === null && preg_match('/^([0-9]{2}):([0-9]{2})(:([0-9]{2}))?$/',$par,$match)) {
      //00:00[:00]
      $hour = (int)$match[1];
      $minute = (int)$match[2];
      $seconds = isset($match[4]) ? (int)$match[4] : 0;
    }
    elseif($par instanceof DateTime) {
      //extract time from $par
      $hour = (int)$par->format('H');
      $minute = (int)$par->format('i');
      $seconds = (int)$par->format('s');
    }
    else {
      $hour = $par !== null ? (int)$par : (int)$this->format('H');
      $minute = ($minute !== null) ? (int)$minute : 0;
    }
    if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
      parent::setTime($hour, $minute, $seconds, $microseconds);
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
  public function setDate($par=null, $month = null, $day = null) {
    if(is_string($par) && $month === null && $day === null && ($mdate=date_create($par)) !== false) {
      //a string with date
      $year = (int)$mdate->format('Y');
      $month = (int)$mdate->format('m');
      $day = (int)$mdate->format('d');
    }
    elseif($par instanceof DateTime) {
      //extract time from $par
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
  */
  public function setEasterDate($flag = self::EASTER_WEST){
    parent::setTime(0,0,0);
    $year = (int)parent::format('Y');
    if($year < 1600 or $year >= 2100){
      trigger_error('Year for '.__METHOD__.' must between 1600 and 2100', E_USER_WARNING); 
      return false;
    }      
    $easterDays = easter_days($year, $flag);
    if($flag == self::EASTER_WEST) {
      parent::setDate($year,3,21);
    }
    elseif( $flag == self::EASTER_EAST) {
      parent::setDate($year,4,3);
    }
    else return false;
    parent::modify(easter_days($year, $flag).' Days');
    $korr = -(int) $this->format('w');
    
    return parent::modify($korr.' Days');
  }
  
 /*
  * setTimezone(string Timezone), convert date in new Timezone
  * dt::create('24.12.2013 18:00')->setTimezone('America/New_York'); //12:00 America/New_York
  * no $timeZone or NULL: default_timezone
  */
  public function setTimezone($timeZone = null){
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
  * toCET: set Timezone for CET or UTC (Fallback)
  * return Standard-Time (Wintertime)
  */
  public function toCET(){
     try{
      $tz = new DateTimeZone('+01:00');
      $utc = false;
    }
    catch(exception $e){
      $tz = new DateTimeZone('UTC');
      $utc = true;
    }
    $this->setTimezone($tz);
    if($utc) $this->modify("+1 hour");
    return $this;
  }
  
 /*
  * return true if saturday or sunday 
  */  
  public function isWeekend(){
    return parent::format('N') > 5;
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
    $strDateWeek = self::create($dateWeek)->format('o-W');
    if($strDateWeek) return $strDateWeek === $this->format('o-W');
    return null;
  }  

 /*
  * return true if a german holiday
  * param $region: 2 chars for the Region
  */  
  public function isPublicHoliday($region = null){
    $holidays = explode(",",self::$holidayList);
    $year = parent::format('Y');
    foreach($holidays as $dateRegion){
      $aDateRegion = explode(':',$dateRegion); //15.8:BY.SL]
      $day = trim($aDateRegion[0]);
      $dateRegion = isset($aDateRegion[1]) ? trim($aDateRegion[1]) : "";  //"" for All
      if(stripos($day,"e") === 0) {
        //e+39=easterdate +39 Days
        $date = clone $this;
        $date->setEasterDate()->modify(substr($day,1)." Days");
      }
      elseif(stripos($day,"b") === 0) { 
        //B = Buß- Bettag
        $date = self::create("23.11.".$year)->modify("last Wed");
      }
      else {
        $date = preg_match('/^\d{1,2}\.\d{1,2}$/',$day) 
          ? date_create($day.".".$year) 
          : date_create($day);
      }
      if($this == $date) { 
        if(empty($dateRegion)) return true;  //All Regions
        elseif(strlen($region) == 2 && stripos($dateRegion,$region) !== false) {
          //regional Holiday 
          return true;
        }
      }
    }
    return false;   
  }

 /*
  * set holidayList
  * dd.mm every year, E-2 Easter - 2 Days, dd.mm.yyyy fix Date, 
  * without list or null : set the default list (de)
  * return true if ok, false if Error in list
  * use detectHolidayListError() as help
  */ 
  public static function setHolidayList($list=null) {
    if(empty($list)) {
      self::$holidayList = self::DE_HOLIDAYLIST;
    } 
    else {
      //validate list
      if(self::detectHolidayListError($list)) return false;
      self::$holidayList = $list;
    }
    return true;
  }
  
 /*
  * add a holidayList
  * dd.mm every year, E-2 Easter - 2 Days, dd.mm.yyyy fix Date, 
  */ 
  public static function addHolidayList($list) {
    if(self::detectHolidayListError($list)) return false;
    self::$holidayList .= ",".$list;
    return true;
  }
  
 /*
  * check holidayList and get first Error Element as string
  * return "" if list ok
  */
  public static function detectHolidayListError($list){
    foreach(explode(',', $list) as $holidayEx) {
      $regEx = '~^((\d\d?\.\d\d?(\.\d{4})?)|(E[+\-]\d*)|B)(:[a-z][a-z](\.[a-z][a-z])*)?$~i';
      if(! preg_match($regEx, trim($holidayEx))) return $holidayEx;
      
    } 
    return "";
  }
 
  
 /*
  * return Seconds after midnight as the clock shows
  */
  public function getDaySeconds() {
    $secondsDay = (parent::format('H') * 60 + parent::format('i'))*60 + parent::format('s');
    return $secondsDay;
  }
  
 /*
  * return numeric representation of the quarter (1..4)
  */
  public function getQuarter() {
    $quarter = ceil(parent::format('n')/3);
    return (int)$quarter;
  }

 /*
  * return the average Date between current Date and refDate
  */
  public function average($refDate) {
    $ref = self::create($refDate);
    $avTs = ($ref->getMicroTime() + $this->getMicroTime())/2;
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
  * cut rest
  * $Interval: number Seconds or String x Hours, x Minutes or x Seconds
  * round with $round = true
  * example: dt::create('2013-12-18 03:48')->cut('15 Minutes') //2013-12-18 03:45:00
  */
  public function cut($Interval=1, $round = false) {
    if(is_string($Interval)) {
      if(preg_match('/^[0-9]{1,2} *(hour|min|sec)[ a-z]*$/i',$Interval)) {
        $dinterval = DateInterval::createFromDateString($Interval);
        $cutSec = ($dinterval->h *60 + $dinterval->i) * 60 + $dinterval->s;
      }
      elseif(preg_match('/^([0-9]{1,2}) *(day|week|mon)[ a-z]*$/i',$Interval,$match)) {
        $number = (int)$match[1];
        if($number < 1) {
          trigger_error('Intervall for '.__METHOD__.' must > 0', E_USER_WARNING);
          return $this;
        }
        $thisCopy = $this->copy();
        if(stripos($Interval,'day')) { 
          $day = (int)parent::format('d');
          $rest = ($day-1)%$number;
          $day -= $rest;
          $this->setDate(null,null,$day);
        }
        elseif(stripos($Interval,'mon')) { 
          $mon = (int)parent::format('n');
          $mon -= ($mon-1)%$number;
          $this->setDate(null,$mon,1);
        }
        else {
          //cut weeks
          $this->modify('next Monday')->modify('-1 week'); //start of week
          if($number > 1) { 
            $weeks = (int)parent::format('W');
            $mon = (int)parent::format('n');
            $year = (int)$this->format('Y');
            //Week from the previous year ?
            if($mon == 1 AND $weeks > 50) --$year;
            //Week from next year ?
            if($mon == 12 AND $weeks < 10) ++$year;

            $weeks -= ($weeks-1)%$number;          
            parent::setISODate($year , $weeks, 1);
          }
        }
        parent::setTime(0, 0, 0); 
        $this->setMicroSeconds(0);
        
        if($round) {
          $average = $this->copy()->modify($Interval)->average($this);
          if($thisCopy >= $average) $this->modify($Interval);
        }
        
        return $this;
      }
      else {
        trigger_error('illegal Intervall for '.__METHOD__, E_USER_WARNING);
        return $this;        
      }
    }
    else {
      $cutSec = (int)$Interval;
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
  * return DateInterval object
  * diff accept as parameter object, string or timestamp 
  */
  public function diff($date = null, $absolute = false) {
    if($date instanceof DateTime) {
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
  public function diffTotal($date=null, $unitP = "sec") {
    $unitList = array(
      'y' => 'Years', 'm' => 'Month', 'w' => 'Weeks','d' => 'Days', 
      'h' => 'Hours', 'i' => 'Minutes', 's' => 'Seconds' 
    );
    $match = preg_grep('/^'.preg_quote($unitP,'/').'/i',$unitList);
    
    if(empty($match)) {
      trigger_error('Unknown Parameter "'.$unitP.'" for '.__METHOD__, E_USER_WARNING);
      return false;
    }
    else {
      $unit = array_pop($match);
    }
    $refDate = self::create($date);
    if($refDate === false) {
      trigger_error('First Parameter for '.__METHOD__.' is not a valid Date', E_USER_WARNING);
      return false;
    }
    //micro Sec
    $microSec = (float)('0.'.$refDate->format('u')) - (float)('0.'.$this->format('u'));

    $diff = $this->diff($refDate);
    if($unit == $unitList['y']) {
      return $diff->y;
    }
    elseif($unit == $unitList['m']){
      return $diff->y * 12 + $diff->m;
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
    $total = $diff->invert ? -$total : $total;
    if($microSec != 0.0) $total += + $microSec; 
    if($unit == $unitList['s']) return $total;
    $total /= 60;
    if($unit == $unitList['i']) return $total;
    $total /= 60;
    if($unit == $unitList['h']) return $total;
    $total /= 24;
    if($unit == $unitList['d']) return $total;
    $total /= 7;
    return $total;
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
  * modify with a chain of modifier separated with |
  * $chainModifier : modifier-list
  * $para : Array with optional parameters
  */
  public function chain($chainModifier, array $para = array()) {
    $thisDate = clone $this;
    $replacements = array_merge($para, array(
      '{{year}}' => parent::format('Y'),
      '{{month}}' => parent::format('m'),
      '{{day}}' => parent::format('d'),
      '{{easter}}' => $thisDate->setEasterDate()->format('m/d 00:00'),
    ));
    
    foreach($replacements as $key => $value){
      $chainModifier = str_replace($key, $value, $chainModifier); 
    }
    foreach(explode('|',$chainModifier) as $modifier) {
      $this->modify($modifier);
    }
  
    return $this;
  }
  
 /*
  * return int/float value in the selected unit from
  * a relativ Time or a DateInterval-Object
  * return false if error
  * unit: 's', 'm','h','d','w'
  * basis: date basis
  * dt::totalRelTime('1 week','days'); //7
  * 
  */
  public static function totalRelTime($relTime, $unit = 'sec', $basis = '2000-1-1') {
    $dateBasis = self::create($basis);
    if($dateBasis === false) {
      trigger_error('Third Parameter for '.__METHOD__.' is not a valid Date', E_USER_WARNING);
      return false;
    }
    if($basis != '2000-1-1') {
      $basis = $dateBasis->format('Y-m-d');
    }
    if(is_string($relTime)) {
      return $dateBasis->diffTotal($basis.' '.$relTime, $unit);
    } elseif ($relTime instanceof DateInterval) {
      return  $dateBasis->sub($relTime)->diffTotal($basis,$unit);
    } 
    trigger_error('First Parameter for '.__METHOD__.' is not a valid Time', E_USER_WARNING);    
    return false;
  }
  
  

 /*
  * Calculated from a value (int / float) and a unit a DateInterval
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
    
    $timeValue = (float) $timeValue;
    
    $match = preg_grep('/^'.$unitP.'/i',$units);
    if(! is_array($match)) {
      trigger_error('Second Parameter for '.__METHOD__.' is not a valid Unit', E_USER_WARNING);
      return false;
    }
    $unit = reset($match);
    $faktor = array_search($unit,$units);
    $seconds = round($timeValue * $faktor);
    return dt::create('2000-1-1')->addSeconds(-$seconds)->diff('2000-1-1');
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
  * get dt as DateTime
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
    $micro = (int)($microSeconds%1000000);
    $strDateTime = $this->format('Y-m-d H:i:s');
    parent::__construct($strDateTime.'.'.sprintf('%06d',$micro),parent::getTimezone());
    return $this;
  }

 /*
  * set second ($newSecond Integer) and MicroSeconds
  */ 
  public function setSecond($newSecond = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    parent::setTime($hour, $minute, (int)$newSecond);
    $secFragment = fmod($newSecond, 1.0);
    if($secFragment >= 0.000001) {
      $this->setMicroSeconds($secFragment * 1000000);
    }
    return $this;
  }

 /*
  * set only minute
  */ 
  public function setMinute($newMinute = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    parent::setTime($hour, (int)$newMinute, $second);
    return $this;
  }

 /*
  * set only hour
  */ 
  public function setHour($newHour = 0) {
    list($hour,$minute,$second) = explode(':',$this->format('H:i:s'));
    parent::setTime((int)$newHour, $minute, $second);
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
  public function setTimestamp($unixtimestamp) {
    if(!is_numeric($unixtimestamp)) return false;

    $timeZone = parent::getTimezone();
    $this->setTimestampUTC($unixtimestamp);
    parent::setTimezone($timeZone);
    
    return $this;
  }
  
 /*
  * adds a number of months and days and cut supernumerary
  * Result is always in the current month as DATE_ADD MySQL
  * 2014-1-31 + 1 Month -> 2014-02-28
  * 2014-3-30 - 1 Month -> 2014-02-28  
  */  
  public function addMonthCut($month) {
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
  */  
  public function addTime($timeInterval) {
    $seconds = self::totalRelTime($timeInterval);
    if($seconds === false) {
      trigger_error('Parameter for '.__METHOD__.' is not a valid time', E_USER_WARNING);
      return $this;
    }
    return $this->addSeconds($seconds);
  }  

 /*
  * sub a relative Time ('1 Hour', '05:03', '00:00:03.5')
  * accept also DateInterval, but DateTime::sub is better
  */  
  public function subTime($timeInterval) {
    $seconds = -self::totalRelTime($timeInterval);
    if($seconds === false) {
      trigger_error('Parameter for '.__METHOD__.' is not a valid time', E_USER_WARNING);
      return $this;
    }
    return $this->addSeconds($seconds);
  }

 /*
  * add a number of Seconds (Int/Float)
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
      $this->modify(sprintf('%+.0f',$fullSeconds).' Seconds');
    }
    else {
      trigger_error('Parameter for '.__METHOD__.' is not Int or Float', E_USER_WARNING);
      return false;
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
    //Array(Second, Minute,Hour, Day, Month, Weekday)
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
    $this->setSecond(0)->modify("+ 1 Minute");
    $cronArr = preg_split('/\s+/',$cronStr); 
    //Array(Second, Minute,Hour, Day, Month, Weekday)
    $error = false;  
    if(count($cronArr) == 5) {
      $maxZyk = 200;
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
    if($error) {
      $msg = 'invalid Parameter $cronStr "'.$cronStr.'" for '.__METHOD__;
      trigger_error($msg, E_USER_WARNING);
      $this->errorInfo = $msg;
      $this->modify("1970-1-1 00:00");
    }
    return $this;
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
    if( $errInfo['warning_count']+$errInfo['error_count'] > 0 ) {
      $errors = array_map(function($p){return $p.'(e)';},$errInfo['errors']);
      $warnings = array_map(function($p){return $p.'(w)';},$errInfo['warnings']);
      $errInfoStr = implode(',',array_merge($errors,$warnings));
    }
    return $errInfoStr;
  }

  
    
 /*
  * private
  */
  
  //Setzt Float-Timestamp mit UTC-Zeitzone
  private function setTimestampUTC($unixtimestamp){
    $seconds = floor($unixtimestamp);
    $secondsFraktion = $unixtimestamp - $seconds;
    $basis = '1970-01-01 00:00:0'.sprintf('%0.6f',$secondsFraktion);
    
    parent::__construct($basis, new DateTimeZone('UTC'));
    parent::modify(sprintf('%+.0f',(float)$seconds).' Seconds');
    
    return $this;
  }
  
  private function santinizeGermanDate($dt) {
    // German notation 13.2 -> 13.2.YYYY
    if(preg_match('/^[0-9]{1,2}\.[0-9]{1,2}\.?$/',$dt)) {
      $dt = rtrim($dt,'.') . date_create('')->format('.Y');
    }
    // German notation 13.2.15 -> 13.2.YYYY
    elseif(preg_match('/^([0-9]{1,2}\.[0-9]{1,2}\.)([0-9]{2})([^0-9][:0-9]+|$)/',$dt,$match)){
      $dt = $match[1].($match[2] < self::DATE2000Z2 ? '20' : '19').$match[2].$match[3];
    }
    else {
      //no EN notation  27. Mai 2015 -> 27. May 2015
      $dtTrans = $this->translateMonth($dt);
      if($dtTrans !== false) $dt = $dtTrans;
    }
    return $dt;
  }
  
  //tauscht Monat von defaultLanguage aus String -> Eng.
  //return string wenn ok, sonst false
  private static function translateMonth($strDate){
    $month = array_slice(self::$mon_days[self::$defaultLanguage],0,12);  //only month
    if(str_ireplace($month,'',$strDate) !== $strDate) {
      //full Month
      return str_ireplace($month, self::$mon_days[self::EN],$strDate);
    }
    //check short Month
    $shortMonth = array_map(array('self','substr3'),$month);
    $shortMonthEN = array_map(array('self','substr3'),self::$mon_days[self::EN]);
    if(str_ireplace($shortMonth,'',$strDate) !== $strDate) {
      return str_ireplace($shortMonth, $shortMonthEN,$strDate);
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
    //list 3,4,5
    $numberList = explode(',',$cronEntry);
    if(count($numberList) >= 2) {
      return in_array($curr,$numberList) ;
    }
    //list 1-5
    $numberList = explode('-',$cronEntry);
    if(count($numberList) == 2) {
      return ($curr >= $numberList[0] AND $curr <= $numberList[1]);
    }
    //every x how */5
    if(preg_match('~^\*/(\d{1,2})$~',$cronEntry,$match)) {
      return ($curr%($match[1]) == 0);
    }
    return null;
  }    
}