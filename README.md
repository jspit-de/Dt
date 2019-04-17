# dt 

PHP extension for DateTime.

### Features

- Create dt-objects from strings, float/int timestamps or object
- German and English for input and output,
  other languages can be added
- Supports microseconds
- All methods of DateTime can still be used

### Usage

#### Create dt-Objects

```php
require '/yourpath/class.dt.php';

echo "now is ".(new dt);  
//now is 2017-07-13 17:41:41

echo "now in New York ".dt::create('Now','America/New_York');
//now in New York 2017-07-13 11:41:41

dt::setDefaultLanguage('de');  //German
//German Input
$Date =  dt::create("1.Mai 17");  //2017-05-01 00:00:00

//create dt from float timestamp : 2017-07-31 13:47:02.250000
echo dt::create(1501501622.25)->toStringWithMicro();

//create dt from exotic formats with Regular Expressions
$string = "Y2015M5D17";
$regEx = '~^Y(?<Y>\d{4})M(?<m>\d{1,2})D(?<d>\d{1,2})$~'; 
$date = dt::createFromRegExFormat($regEx,$string);
echo $date; //2015-05-17 00:00:00
```
#### Formatting for output

```php
dt::setDefaultLanguage('de');  //German

$date = dt::create("2016-12-13 08:24:38");
echo $date;  //2016-12-13 08:24:38

$dateOfBirth = dt::create('16.3.1975');
echo 'Ich bin an einem '.$dateOfBirth->formatL('l')
  .' im '.$dateOfBirth->formatL('F').' geboren.';
//Ich bin an einem Sonntag im März geboren.

echo dt::create('2016-01-16')->formatL('l, d F Y','fr');
//samedi, 16 janvier 2016

echo dt::create('2016-01-16')->formatL('FULL+NONE','de_AT');
//Samstag, 16. Jänner 2016

echo dt::create('2016-01-16 13:22:45')->formatL('FULL+MEDIUM','zh_Hans_CN');
//2016年1月16日星期六下午1:22:45
```

#### Modify Date

```php
//Modify the Year
$date = dt::create('2015-2-16 19:28:30')->setdate(2000);  //2000-02-16 19:28:30
//Modify only Month and Day
$date = dt::create('2015-2-16 19:28:30')->setdate(null,12,24);  //2015-12-24 19:28:30

//Modify Time
$date = $date->setTime('12:14');  //2015-12-24 12:14:00

//modify with Time from other dt-Object
$dateWithTime = dt::create('2000-1-1 17:18:19');
$date = dt::create('2015-2-16 19:28:30')->setTime($dateWithTime);  //2015-02-16 17:18:19

//add a relative Time ('1 Hour', '05:03', '00:00:03.5')
$date = dt::create('2015-2-16 01:30:00')->addTime('5:30');  //2015-02-16 07:00:00

//adds a number of months and cut supernumerary
$date = dt::create('2015-1-31 01:30:00')->addMonthCut(1);  //2015-02-28 01:30:00

//cut a interval
$date15min = dt::create('2013-12-18 03:55:07')->cut('15 Minutes'); //2013-12-18 03:45:00
$date2h = dt::create('2013-12-18 03:55:07')->cut('2 hours'); //2013-12-18 02:00:00

//First Day from Week 3 in 2015
$date = dt::create('1.1.2015')->setIsoWeek(3);  //2015-01-12

//When will Easter be celebrated in Greece in 2018?
$easterDate =  dt::create("2018-1-1")->setEasterDate(dt::EASTER_EAST);  //2018-04-08

//modify with chain  1.Advent 2017: 2017-12-03
$firstAdvent = dt::create('2017-1-1')->chain('12/25|last Sunday|-3 weeks');

//modify with chain Spring Bank Holiday 2016 (United Kingdom)
$date = dt::create('2016-1-1')->chain('Last monday of May {{year}}');  //2016-05-30

//chain with conditions : if time after 12:00 next Day
$date = dt::create('2018-09-24 10:30')->chain('{{?Hi>1200}}next weekday');  
//2018-09-24 10:30:00  
$date = dt::create('2018-09-24 12:30')->chain('{{?Hi>1200}}next weekday');
//2018-09-25 10:30:00

//cron
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30 
$dateTime = dt::create('2017-7-27 01:30:00'); 
$nextStart = $dateTime->nextCron($cronStr);  //2017-07-28 01:20:00

//Wintertime (CET)
$date = dt::create('2015-05-15 15:00','Europe/Berlin')->toCET(); //2015-05-15 14:00:00

//copy of object
$date = dt::create('2015-4-1 12:00');
$date2 = $date->copy()->modify('+1 hour'); //2015-04-01 13:00:00
echo $date; //2015-04-01 12:00:00

```

#### Calculations

```php

//diffTotal: units Week, Day, Hour, Minute, Second, Year, Month
$myAge = $dateOfBirth->diffTotal('today','Years');

//diffHuman
$start = "Now";
$stop = "Now +3 Hours +20 Minutes";
$strDiff = dt::create($start)->diffHuman($stop,'en'); //"3 Hours"
$strDiff = dt::create($start)->diffHuman($stop,'de'); //"3 Stunden"

//Seconds since midnight 
$seconds = dt::create('Now')->getDaySeconds(); 
//or
$nowWithMicroseconds = dt::create(microtime(true));  
$seconds = dt::create('today')->diffTotal($nowWithMicroseconds,"Second");
//or
$seconds = dt::create('today')->diffTotal(microtime(true),"Second");

//Minutes since midnight from a date
$minutes = dt::create('2014-5-6 07:04:30')->getDayMinutes();  //424 = 7*60+4

//relative Time to a fixed Unit
$hours = dt::totalRelTime("1 Week 3 Days 5 Hours","h"); //245

//get Julian Date Number
$jd = dt::create('2018-06-25 03:38:25 UTC')->toJD();

//get Float-Timestamp
$timeStamp = dt::create('1716-12-24')->getMicroTime(); //-7984573200.0

//Quarter
$quarter = dt::create('2015-08-10')->getQuarter(); //3

//Average between Dates 
$date = dt::create('2015-4-1 12:00')->average('2015-4-3 04:00'); //2015-04-02 08:00:00

//Rest or Modulo
$restMinutes = dt::create('2013-12-18 03:47')->getModulo('5 Minutes');  //2
```

#### Checks

```php
//check if 12.10.2005 was a saturday or sunday
$weekEnd = dt::create('12.10.2005')->isWeekend();  //false

//2016 was a leap year ?
if(dt::create('1.1.2016')->isLeapYear()) {
  echo 'yes';
}

//Are the 2016-01-15 and the 2016-01-17 in the same week
$isInWeek = dt::create('2016-01-15')->isInWeek('2016-01-12');  //true

//is 2016-04-15 in Week 34 ?
$isInWeek = dt::create('2016-04-15')->isInWeek('2016W34');  //false

//was in moscow in june 2015 summertime ? No
$isSummertime = dt::create('Jun 2015','Europe/Moscow')->isSummertime();  //false
 
//cron
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30 
$dateTime = dt::create('2017-7-27 01:30:00'); 
$cronStart = $dateTime->isCron($cronStr);  //true 

```

#### Example Shophours

```php
//use cron-syntax
$shopOpen = [
  ['open','* 10-11 * * 1-5'], //Mo-Sa 10h-11:59
  ['open','* 15-17 * * 1-5'], //Mo-Sa 15h-17:59
  ['close','* * 24,30 12 *'], //Christmas + New Year's Eve
];

$date = dt::create('Now');
$open = false;
$holiday = new JspitHoliday('de-BY');  //Germany Bavaria

if(!$holiday->isHoliday($date)){
  foreach($shopOpen as list($openClose,$cronStr)){
    $isCron = $date->isCron($cronStr);
    if($openClose == 'open') $open = $open || $isCron;
    if($openClose == 'close') $open = $open && !$isCron;
  }
}
 
echo $date.' : '.($open ? 'Open' : 'Close');
```

### Demo and Test

http://jspit.de/check/phpcheck.class.dt.php

### Documentation

http://jspit.de/tools/classdoc.php?class=dt

### Requirements

- PHP 5.3.8+
