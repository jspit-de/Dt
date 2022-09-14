# dt 

PHP extension for DateTime API.

### Features

- Create dt-objects from strings, timestamps( float/int/Microsoft),
  Julian Date Number, DateTime-Objects 
- Pipe constructs for multiple modifiers
- Supports many languages for output and input
- Crontab Expressions
- Supports microseconds
- Calculate dates for catholic and orthodox Easter and Passover
- All methods of DateTime can still be used
- One file, PHP >= 7.0, no external requirements



### Installation & loading

- Code -> Download ZIP dt-master.zip
- Extract the file Dt.php to a new Folder 'Jspit'

```php
use Jspit\Dt;
require '/path_to_Jspit/Jspit/Dt.php';
```

If the project uses the Composer autoloader, you can also do the following.

```php
use Jspit\Dt;
$loader = require 'vendor/autoload.php';
$loader->addPsr4('Jspit\\','/path_to_Jspit/Jspit');
```

### Usage

#### Create dt-Objects

```php
echo "now is ".(new Dt);  
//now is 2017-07-13 17:41:41

echo "now in New York ".Dt::create('Now','America/New_York');
//now in New York 2017-07-13 11:41:41

//create from integer year, month..
$dt = Dt::create(2019,5,1,12,23,45,12345); //"2019-05-01 12:23:45.012345"

//create Date Christmas Eve current Year, Time 18:00
$dt = Dt::create(NULL,12,24,18);

//use wildcads for create
$date = Dt::create("{{Y-m}}-15 00:00");  //Day 15 this Month time 00:00
$date = Dt::create("{{easter}}");  //easter date this year 
$date = Dt::create("{{easter}} -2 Days");  //Good Friday this year 
$date = Dt::create("{{easter}} +50 Days");  //Whit Monday this year

//use chain for create Day of Prayer and Repentance germany
$date = Dt::create("Nov 23 | last Wed");  //Wednesday before November 23rd
//or
$date = Dt::create("Nov 23 AND last Wed"); 

//German Input
Dt::setDefaultLanguage('de');  //German
$Date =  Dt::create("1.Mai 17");  //2017-05-01 00:00:00

Dt::setDefaultLanguage('ru');
$date = Dt::create("1 октября 1990");
echo $date->format("l, j F Y"); // Monday, 1 October 1990

//create dt from exotic formats with Regular Expressions
$string = "Y2015M5D17";
$regEx = '~^Y(?<Y>\d{4})M(?<m>\d{1,2})D(?<d>\d{1,2})$~'; 
$date = Dt::createFromRegExFormat($regEx,$string);
echo $date; //2015-05-17 00:00:00

//create from timestamps: float timestamp 
echo Dt::create(1501501622.25)->toStringWithMicro();
//2017-07-31 13:47:02.250000

//create From LDAP Timestamp
$timeStamp = 130981536000000000;
$basisDate = '1601-1-1';
$resolution = 1.E-7; // 100ns 
$date = Dt::createFromSystemTime($timeStamp,$basisDate,$resolution,"UTC"); 
//2016-01-25 00:00:00 


```
#### Formatting for output

```php
Dt::setDefaultLanguage('de');  //German

$date = Dt::create("2016-12-13 08:24:38");
echo $date;  //2016-12-13 08:24:38

$dateOfBirth = Dt::create('16.3.1975');
echo 'Ich bin an einem '.$dateOfBirth->formatL('l \i\m F').' geboren.';
//Ich bin an einem Sonntag im März geboren.

echo Dt::create('2016-01-16')->formatL('l, d F Y','fr');
//samedi, 16 janvier 2016

echo Dt::create('2016-01-16')->formatL('FULL+NONE','de_AT');
//Samstag, 16. Jänner 2016

echo Dt::create('2016-01-16 13:22:45')->formatL('FULL+MEDIUM','zh_Hans_CN');
//2016年1月16日星期六下午1:22:45
```

#### Modify Date

```php
//Modify the Year
$date = Dt::create('2015-2-16 19:28:30')->setdate(2000);  //2000-02-16 19:28:30
//Modify only Month and Day
$date = Dt::create('2015-2-16 19:28:30')->setdate(null,12,24);  //2015-12-24 19:28:30

//Modify Time
$date = $date->setTime('12:14');  //2015-12-24 12:14:00

//modify with Time from other dt-Object
$dateWithTime = Dt::create('2000-1-1 17:18:19');
$date = Dt::create('2015-2-16 19:28:30')->setTime($dateWithTime);  //2015-02-16 17:18:19

//add a relative Time ('1 Hour', '05:03', '00:00:03.5', '1h30m')
$date = Dt::create('2015-2-16 01:30:00')->addTime('5:30');  //2015-02-16 07:00:00

//adds a number of months and cut supernumerary
$date = Dt::create('2015-1-31 01:30:00')->addMonthCut(1);  //2015-02-28 01:30:00

//Add 2 days, from Sunday 2022-09-04 only count Mondays
$date = Dt::create('2022-09-04')->addDays(2,'Mon');  //Mon, 2022-09-12

//cut a interval
$date15min = Dt::create('2013-12-18 03:55:07')->cut('15 Minutes'); //2013-12-18 03:45:00
$date2h = Dt::create('2013-12-18 03:55:07')->cut('2 hours'); //2013-12-18 02:00:00

//First Day from Week 3 in 2015
$date = Dt::create('1.1.2015')->setIsoWeek(3);  //2015-01-12

//When will Easter be celebrated in Greece in 2018?
$easterDate =  Dt::create("2018-1-1")->setEasterDate(Dt::EASTER_EAST);  //2018-04-08

//modify with chain  1.Advent 2017: 2017-12-03
$firstAdvent = Dt::create('2017-1-1')->chain('12/25|last Sunday|-3 weeks');

//modify with chain Spring Bank Holiday 2016 (United Kingdom)
$date = Dt::create('2016-1-1')->chain('Last monday of May {{year}}');  //2016-05-30

//Sunset Berlin Today
$location = [52.520008, 13.404954]; //lat lon Berlin
$dt = Dt::create('today')->setSunset($location);
//Local time Sunrise tomorrow in New York
$dt = Dt::create('tomorrow','America/New_York')->setSunrise();

//chain with conditions : if it is after 12:00, take next working day at 8:00
$date = Dt::create('2018-09-24 10:30')->chain('{{?Hi>1200}}next weekday 8:00');  
//2018-09-24 10:30:00  
$date = Dt::create('2018-09-24 12:30')->chain('{{?Hi>1200}}next weekday 8:00');
//2018-09-25 08:00:00

//cron
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30 
$dateTime = Dt::create('2017-7-27 01:30:00'); 
$nextStart = $dateTime->nextCron($cronStr);  //2017-07-28 01:20:00

//Wintertime (CET)
$date = Dt::create('2015-05-15 15:00','Europe/Berlin')->toCET(); //2015-05-15 14:00:00

//copy of object
$date = Dt::create('2015-4-1 12:00');
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
$strDiff = Dt::create($start)->diffHuman($stop,'en'); //"3 Hours"
$strDiff = Dt::create($start)->diffHuman($stop,'de'); //"3 Stunden"

//Seconds since midnight 
$seconds = Dt::create('Now')->getDaySeconds(); 
//or
$nowWithMicroseconds = Dt::create(microtime(true));  
$seconds = Dt::create('today')->diffTotal($nowWithMicroseconds,"Second");
//or
$seconds = Dt::create('today')->diffTotal(microtime(true),"Second");

//Minutes since midnight from a date
$minutes = Dt::create('2014-5-6 07:04:30')->getDayMinutes();  //424 = 7*60+4

//get Julian Date Number
$jd = Dt::create('2018-06-25 03:38:25 UTC')->toJD();

//get Float-Timestamp
$timeStamp = Dt::create('1716-12-24')->getMicroTime(); //-7984573200.0

//Quarter
$quarter = Dt::create('2015-08-10')->getQuarter(); //3

//Average between Dates 
$date = Dt::create('2015-4-1 12:00')->average('2015-4-3 04:00'); //2015-04-02 08:00:00

//Rest or Modulo
$restMinutes = Dt::create('2013-12-18 03:47')->getModulo('5 Minutes');  //2

//get count of specific weekday between dates
//example: how many Saturdays and Sundays does June 2019 have?
$weekEndDaysJun2019 = Dt::create('1 Jun 2019')->countDaysTo('Sat,Sun','30 Jun 2019'); //10

```

#### Checks

```php
//check if 12.10.2005 was a saturday or sunday
$weekEnd = Dt::create('12.10.2005')->isWeekend();  //false

//2016 was a leap year ?
if(Dt::create('1.1.2016')->isLeapYear()) {
  echo 'yes';
}

//Are the 2016-01-15 and the 2016-01-17 in the same week
$isInWeek = Dt::create('2016-01-15')->isInWeek('2016-01-12');  //true

//is 2016-04-15 in Week 34 ?
$isInWeek = Dt::create('2016-04-15')->isInWeek('2016W34');  //false

//was in moscow in june 2015 summertime ? No
$isSummertime = Dt::create('Jun 2015','Europe/Moscow')->isSummertime();  //false
 
//cron
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30 
$dateTime = Dt::create('2017-7-27 01:30:00'); 
$cronStart = $dateTime->isCron($cronStr);  //true 

//is
$dt = Dt::create('Jun 7 2021 13:46:01');  
//Monday, 2021-06-07 13:46:01

$isJun = $dt->is('June');  //bool(true)
$isMon = $dt->is('Monday');  //bool(true)
$isWeekDay = $dt->is('weekday');  //bool(true)
$isyear2021 = $dt->is('2021');  //bool(true)
$is13h46 = $dt->is('13:46');  //bool(true)
$isJun2021 = $dt->is('2021-06');  //bool(true)
$isPast = $dt->is('past');  //bool(true)

$isToday = $dt->is('today');  //bool(false)
$isFuture = $dt->is('future');  //bool(false)
$isSunday = $dt->is('sun');  //bool(false)

//isCurrent
$born = Dt::create('1991-08-25');
$isBirthday = $born->isCurrent('md');
//true if today is the same month(m) and day(d)

```
#### Calculations with times and DateIntervals

```php
//relative Time to a fixed Unit
$hours = Dt::totalRelTime("1 Week 3 Days 5 Hours","h"); //float(245)
$hours = Dt::totalRelTime("1w3d5h","h"); //short syntax

//Convert 124 hours and 6 minutes to seconds
$seconds = Dt::totalRelTime("124:06",'seconds');  //float(446760)

//Convert 02:05:30 to minutes
$minutes = Dt::totalRelTime("02:05:30",'minutes');  //float(125.5)

//Convert a DateInterval to seconds
$dateInterval = new DateInterval('PT2H3S');  //2 hours, 3 s
$seconds = Dt::totalRelTime($dateInterval);  //float(7203)

//Convert float seconds and milliseconds to a DateInterval
$dateInterval = Dt::date_interval_create_from_float(157.25);
echo $dateInterval->format('%H:%I:%S.%F');  //00:02:37.250000

//formatDateInterval with format character %G for output of hours >= 24
$dateInterval = DateInterval::createFromDateString('3 Days 4 Hours 6 Minutes');
echo Dt::formatDateInterval('%G:%I', $dateInterval);  //76:06

//Convert seconds to time format H:i:s
$dateInterval = Dt::date_interval_create_from_float(446762);
echo Dt::formatDateInterval('%G:%I:%S', $dateInterval); //124:06:02

//2.5 days to DateInterval (2 Days, 12 hours)
$dateInterval = Dt::date_interval_create_from_float(2.5,'days');

//add times
$times = ['7:35','8:45','10:16','-00:30'];
$seconds = 0.0;
foreach($times as $time){
  $seconds += Dt::totalRelTime($time);
}
$dateInterval = Dt::date_interval_create_from_float($seconds);
echo Dt::formatDateInterval('%r%G:%I:%S', $dateInterval); //26:06:00

$floatHours = Dt::totalRelTime($seconds,'hour');
echo $floatHours.' hours';  //26.1 hours

```


#### Example Shophours

```php
//use cron-syntax
$shopOpen = [
  ['open','* 10-11 * * 1-5'], //Mo-Sa 10h-11:59
  ['open','* 15-17 * * 1-5'], //Mo-Sa 15h-17:59
  ['close','* * 24,30 12 *'], //Christmas + New Year's Eve
];

$date = Dt::create('Now');
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

### Documentation

http://jspit.de/tools/classdoc.php?class=Jspit\Dt

### Demo and Test

http://jspit.de/check/phpcheck.jspit.dt.php

### Requirements

- PHP 7.x
- IntlDateFormatter class for full language support
