<?php
error_reporting(-1);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

require '../class/phpcheck.php';
require '../class/class.debug.php';
$t = new PHPcheck();  //need min 1.3.17   

/*
 * Tests Class 
 */
require '../class/class.dt.php';

//version 1.4.21
$t->start('exist versions info');
$info = $t->getClassVersion("dt");
$t->check($info, !empty($info));

$t->start('set default Timezone and Language');
dt::default_timezone_set('Europe/Berlin');
$result =dt::setDefaultLanguage('de');
$t->checkEqual($result,true);

$t->start('get default Language');
$result =dt::getDefaultLanguage();
$t->checkEqual($result,'de');

//create
$t->start('Create current Time (Now)');
$dt = dt::create('now');
$ok = $dt->format('Y-m-d H:i:s') == date_create('now')->format('Y-m-d H:i:s');
$t->check($dt, $ok);

$t->start('cast dt object to string');
$dt = dt::create('now');
$result = (string)$dt;
$t->checkEqual($result, $dt->format(dt::TO_STRING_FORMAT));

$t->start('Create this Date 00:00 (Today)');
$dt = dt::create('Today');
$t->check($dt, $dt == date_create('Today'));

$t->start('Create a fix Date dd.mm.yyyy HH:ii');
$date = dt::create('6.11.1875 04:09');
$t->checkEqual((string)$date, '1875-11-06 04:09:00');

$t->start('Create a fix Date dd.mm.yy');
$date = dt::create('5.6.15');
$t->checkEqual((string)$date, '2015-06-05 00:00:00');

$t->start('Create a fix Date dd.mm.');
$date = dt::create('5.6.');
$t->checkEqual((string)$date, date('Y').'-06-05 00:00:00');

$t->start('Create a fix Date dd.mm.yy HH:ii');
$date = dt::create('3.4.81 6:9');
$t->checkEqual((string)$date, '1981-04-03 06:09:00');

$t->start('Create a Date with Microseconds');
$date = dt::create('1981-04-03 06:09:25.160000');
$t->checkEqual($date->toStringWithMicro(), '1981-04-03 06:09:25.160000');

$t->start('Create a fix Date with english month');
$date = dt::create('March 4, 2016');
$t->checkEqual((string)$date, '2016-03-04 00:00:00');

$t->start('Create a fix Date with german month');
$date = dt::create('4.März 2016');
$t->checkEqual((string)$date, '2016-03-04 00:00:00');

$t->start('Create a Date easter monday 2016 from chain of strings');
$date = dt::create('2016-1-1|{{easter}}|+1 Day');
$t->checkEqual((string)$date, '2016-03-28 00:00:00');

$t->start('Create Date from DateTime-Object');
$dateTime = date_create('2000-01-02');
$date = dt::create($dateTime);
$t->checkEqual((string)$date, '2000-01-02 00:00:00');

$t->start('Create Date Timezone UTC');
$date = dt::create('1.1.2014 00:00','UTC');
$t->checkEqual($date->formatL('Y-m-d H:i O'), '2014-01-01 00:00 +0000');

$t->start('Create Date Timezone New York');
$date = dt::create('1.1.2014 00:00','America/New_York');
$t->checkEqual($date->formatL('Y-m-d H:i O'), '2014-01-01 00:00 -0500');

$t->start('Create Date from Integer-Timestamp');
$timeStamp = strtotime('2015-12-24');
$date = dt::create($timeStamp);
$t->checkEqual((string)$date, '2015-12-24 00:00:00');

$t->start('Create Date from Float-Timestamp');
$floatTimeStamp = strtotime('2015-12-25') + 2.5;
$date = dt::create($floatTimeStamp);
$expected = '2015-12-25 00:00:02.500000';
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'), $expected);

$t->start('Create Date from a big Float Timestamp with Milliseconds');
$date = dt::create(35573107125.25);
$expected = '3097-04-07 19:38:45.250000';
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'), $expected);

$t->start('Create Date from a big negativ Float Timestamp');
$date = dt::create(-22144582800.);
$t->checkEqual((string)$date, '1268-04-07 00:00:00');

$t->start('Create Date from not valid String');
$t->setErrorLevel(0);//error reporting off for this test
$date = dt::create('31.0x.2015');
$t->restoreErrorLevel();
$t->checkEqual($date, false);

$t->start('Get last Errormassage');
$errorInfo = dt::getLastErrorsAsString() ;
$t->check($errorInfo, $errorInfo != '');

$t->start('Parse a invalid Date: 31.02.');
$date = dt::create('31.02.2015'); //set to 2015-03-03
$t->checkEqual((string)$date, '2015-03-03 00:00:00');

$t->start('getErrorInfo() ');
$errInfo = $date->getErrorInfo();
$t->check($errInfo, !empty($errInfo));

$t->start('Parse a invalid Date with StrictMode');
dt::setStrictModeCreate(true); //default false
$date = dt::create('31.02.2015');
$t->checkEqual($date, false);

$t->start('Create Date with regular Expressions');
$str = '24 Dezember';
$regEx = '~(?<d>\d{1,2}) (?<F>\w+)~';
$dateTemplate = "1.1.2010 18:00";
$date = dt::createFromRegExFormat($regEx,$str,null,$dateTemplate);
$t->checkEqual((string)$date,'2010-12-24 18:00:00');

$t->start('Create Date with regular Expressions');
$str = '2.5 19 Uhr 30';
$regEx = '~(?<d>\d{1,2})\.(?<m>\d{1,2}) ?(?<H>\d{1,2}) Uhr (?<i>\d{1,2})~';
$date = dt::createFromRegExFormat($regEx,$str);
$t->checkEqual((string)$date,date('Y').'-05-02 19:30:00');

$t->start('get matches from last RegEx ');
$matches = $date->getMatchLastCreateRegEx(); 
$expected = array (
  0 => "2.5 19 Uhr 30",
  'd' => "2",
  1 => "2",
  'm' => "5",
  2 => "5",
  'H' => "19",
  3 => "19",
  'i' => "30",
  4 => "30",
);
$t->checkEqual($matches,$expected);

//formatL
$t->start('format with German short Weekday');
$strDate = dt::create('2014-12-20')->formatL('D, d.m.Y');
$t->checkEqual($strDate,'Sam, 20.12.2014');

$t->start('format with English short Weekday');
$strDate = dt::create('2014-12-20')->formatL('D, d.m.Y','en');
$t->checkEqual($strDate,'Sat, 20.12.2014');

$t->start('Add another language');
$frLanguage = array('fr' => 
       array("Janvier", "Février"," Mars "," Avril ",
       " Mai "," Juin "," Juillet "," Août ",
       " Septembre "," Octobre "," Novembre "," Décembre ",
       "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", 
       "Vendredi", "Samedi"),
);     
dt::addLanguage($frLanguage);
$strDate = dt::create('14.1.2015')->formatL('l, d.F Y','fr'); 
$t->checkEqual($strDate,'Mardi, 14.Janvier 2015');

//setTime
$t->start('setTime(): set the time for a date');
$date =dt::create('4.6.2011 18:00')->setTime(9,15);
$t->checkEqual((string)$date,'2011-06-04 09:15:00');

$t->start('setTime(): set hour and minute to 0 ');
$date =dt::create('4.6.2011 18:15:46')->setTime(9);
$t->checkEqual((string)$date,'2011-06-04 09:00:00');

$t->start('setTime(): set minute to 6, second to 0 ');
$date =dt::create('4.6.2011 18:15:46')->setTime(null,6);
$t->checkEqual((string)$date,'2011-06-04 18:06:00');

$t->start('setTime(hh:mm): set the time for a date');
$date =dt::create('4.6.2011 18:00')->setTime('10:14');
$t->checkEqual((string)$date,'2011-06-04 10:14:00');

$t->start('set to the start of day with setTime(hh:mm)');
$date =dt::create('4.6.2011 18:00')->setTime('00:00');
$t->checkEqual((string)$date,'2011-06-04 00:00:00');

$t->start('setTime from another dt/date object');
$dateWithTime = date_create('2011-1-1 13:45');
$date =dt::create('2012-12-08')->setTime($dateWithTime);
$t->checkEqual((string)$date,'2012-12-08 13:45:00');

$t->start('setTime with microseconds');
$date =dt::create('2012-12-08')->setTime(12,30,15,12345);
$t->checkEqual($date->toStringWithMicro(), '2012-12-08 12:30:15.012345');

//setMicroSeconds
$t->start('set microseconds part');
$date = dt::create('2011-1-1 13:45:10')->setMicroSeconds(250000);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:10.250000');

$t->start('set Seconds and Milliseconds');
$date = dt::create('2011-1-1 13:45:10')->setSecond(3.5);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:03.500000');

$t->start('set Seconds and Milliseconds from dt object');
$dt = dt::create('2016-10-09 10:00:55.567');
$date = dt::create('2011-1-1 13:45:10')->setSecond($dt);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:55.567000');

$t->start('set Minute');
$date = dt::create('2011-1-1 13:45:10')->setMinute(30);
$t->checkEqual((string)$date, '2011-01-01 13:30:10');

$t->start('set Hour');
$date = dt::create('2011-1-1 13:45:10')->setHour(5);
$t->checkEqual((string)$date, '2011-01-01 05:45:10');

//setDate
$t->start('setDate: set the year');
$date = dt::create('2011-1-1 13:45')->setDate(2015);
$t->checkEqual((string)$date, '2015-01-01 13:45:00');

$t->start('setDate: set the month to May');
$date = dt::create('2011-1-1 13:45')->setDate(null,5);
$t->checkEqual((string)$date, '2011-05-01 13:45:00');

$t->start('setDate: set the day to 16');
$date = dt::create('2011-1-1 13:45')->setDate(null,null,16);
$t->checkEqual((string)$date, '2011-01-16 13:45:00');

$t->start('setDate: set year, month and day');
$date = dt::create('2011-1-1 13:45')->setDate(2015,5,16);
$t->checkEqual((string)$date, '2015-05-16 13:45:00');

$t->start('setDate: set year, month and day from datestring');
$date = dt::create('2011-1-1 13:45')->setDate("16.5.2015");
$t->checkEqual((string)$date, '2015-05-16 13:45:00');

$t->start('setDate: full date from dt/date-object');
$dateObject = dt::create("2015/5/16");
$dateWithTime = dt::create('2011-1-1 13:45')->setDate($dateObject);
$t->checkEqual((string)$dateWithTime, '2015-05-16 13:45:00');

$t->start('setYear: get year from dt/date-object');
$dateObject = dt::create("2015/5/16");
$dateWithTime = dt::create('2011-1-1 13:45')->setYear($dateObject);
$t->checkEqual((string)$dateWithTime, '2015-01-01 13:45:00');

$t->start('setYear: get current year');
$dateWithTime = dt::create('2011-06-12 14:15')->setYear();
$t->checkEqual((string)$dateWithTime, date('Y').'-06-12 14:15:00');

$t->start('setYear: get next year in future');
$dateWithTime = dt::create('2011-01-01 00:00:56')->setYear(true);
$t->checkEqual((string)$dateWithTime, (date('Y')+1).'-01-01 00:00:56');

$t->start('setYear: set year with fix number');
$dateWithTime = dt::create('2012-10-15 16:25')->setYear(2015);
$t->checkEqual((string)$dateWithTime, '2015-10-15 16:25:00');

$t->start('set the ISOweek');
$date =dt::create('2014-5-6 07:04:30')->setISOweek(51);
$t->checkEqual((string)$date, '2014-12-15 07:04:30');

//get..
$t->start('get Seconds of Day');
$seconds = dt::create('2014-5-6 07:04:30')->getDaySeconds();
$t->checkEqual($seconds, 7*60*60 + 4*60 + 30);

$t->start('get microseconds part');
$microSeconds = dt::create('2014-5-6 07:04:30.25')->getMicroSeconds();
$t->checkEqual($microSeconds, 250000);

$t->start('get count Days after 0001-01-01');
$dayCount = dt::create('18.12.2014')->toDays();
$t->checkEqual($dayCount, 735585);

$t->start('get quarter number of 2015-3-31');
$result = dt::create('2015-3-31')->getQuarter();
$t->checkEqual($result, 1);

$t->start('get quarter number of 2015-4-1');
$result = dt::create('2015-4-1')->getQuarter();
$t->checkEqual($result, 2);

$t->start('get year as integer');
$result = dt::create('2015-4-1 06:15:46')->year;
$t->checkEqual($result, 2015);

$t->start('get month as integer');
$result = dt::create('2015-4-1 06:15:46')->month;
$t->checkEqual($result, 4);

$t->start('get day as integer');
$result = dt::create('2015-04-07 06:15:46')->day;
$t->checkEqual($result, 7);

$t->start('get hour as integer');
$result = dt::create('2015-4-14 06:15:46')->hour;
$t->checkEqual($result, 6);

$t->start('get minute as integer');
$result = dt::create('2015-4-14 06:15:46')->minute;
$t->checkEqual($result, 15);

$t->start('get second as integer');
$result = dt::create('2015-4-14 06:15:46')->second;
$t->checkEqual($result, 46);

$t->start('get microSecond as integer');
$result = dt::create('2015-4-14 06:15:46.123')->microSecond;
$t->checkEqual($result, 123000);

$t->start('get dayOfWeek ISO 8601 Monday: 1');
//ISO 8601 1: Monday .. 7: Sunday 
$result = dt::create('next Monday')->dayOfWeek;
$t->checkEqual($result, 1);

$t->start('get dayOfWeek ISO 8601 Saturday: 6');
//ISO 8601 1: Monday .. 7: Sunday 
$result = dt::create('next Saturday')->dayOfWeek;
$t->checkEqual($result, 6);

$t->start('get dayOfWeek ISO 8601 Sunday: 7');
//ISO 8601 1: Monday .. 7: Sunday 
$result = dt::create('next Sunday')->dayOfWeek;
$t->checkEqual($result, 7);

$t->start('get dayOfYear from 2017-11-18 as integer');
//Day of the year starts with 1 on 1.1.
$result = dt::create('2017-11-18')->dayOfYear;
$t->checkEqual($result, 322);

$t->start('get unknown property : false');
$result = dt::create('2017-11-18')->unknown;
$t->checkEqual($result, false);

$t->start('average of 2 dates');
$result = dt::create('2015-4-1')->average('2015-4-2');
$expect = '2015-04-01 12:00:00.000000';
$t->checkEqual($result->toStringWithMicro(), $expect);

//cut
$t->start('get as DateTime');
$dateTime1 = date_create('Now',new DateTimeZone('UTC'));
$dateTime = dt::create($dateTime1)->getDateTime();
$checkOk = ($dateTime == $dateTime1 
  AND $dateTime->getTimeZone() == $dateTime1->getTimeZone()
  AND $dateTime instanceOf DateTime
  AND !$dateTime instanceOf dt
  );
$t->check($dateTime, $checkOk);

$t->start('cut to full 15 minutes');
$date = dt::create('2013-12-18 03:55')->cut('15 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:45:00');

$t->start('cut to nearest 15 minutes');
$date = dt::create('2013-12-18 03:55')->cut('15 Minutes', true);
$t->checkEqual((string)$date, '2013-12-18 04:00:00');

$t->start('cut Microseconds');
$date = dt::create('2013-12-18 03:48:34.6')->cut();
$t->checkEqual($date->toStringWithMicro(), '2013-12-18 03:48:34.000000');

$t->start('cut to full 2 hours');
$date = dt::create('2013-12-18 03:48')->cut('2 hours');
$t->checkEqual((string)$date, '2013-12-18 02:00:00');

$t->start('cut to full day');
$date = dt::create('2013-12-18 03:48')->cut('1 day');
$t->checkEqual((string)$date, '2013-12-18 00:00:00');

$t->start('cut to full day');
$date = dt::create('2013-12-18 03:48')->cut('1 day');
$t->checkEqual((string)$date, '2013-12-18 00:00:00');

$t->start('cut to full 2 days');
$date = dt::create('2013-12-18 03:48')->cut('2 days');
$t->checkEqual((string)$date, '2013-12-17 00:00:00');

$t->start('cut to start of last quarter');
$date = dt::create('2013-12-18 03:48')->cut('3 month');
$t->checkEqual((string)$date, '2013-10-01 00:00:00');

$t->start('cut to start of current week');
$date = dt::create('2013-12-18 03:48')->cut('1 week');
$t->checkEqual((string)$date, '2013-12-16 00:00:00');

$t->start('diff: with DateTime');
$dateTo = new DateTime('2013-12-18 00:15:00');
$result = dt::create('2013-12-18')->diff($dateTo)->i;  //Minutes
$t->checkEqual($result, 15);

$t->start('diff: with dt');
$dateTo = new dt('2013-12-18 00:16:00');
$result = dt::create('2013-12-18')->diff($dateTo)->i;  //Minutes
$t->checkEqual($result, 16);

$t->start('diff: with String-Dates');
$days = dt::create('2013-12-18')->diff('24.12.2013')->days;
$t->checkEqual($days, 6);

$t->start('diff: with timestamp');
$timeStamp = strtotime('24.12.2013');
$days = dt::create('2013-12-18')->diff($timeStamp)->days;
$t->checkEqual($days, 6);

$t->start('diffTotal: with String-Dates');
$days = dt::create('2003-10-1 6:30')->diffTotal('2003-10-5 6:30',"days");
$t->checkEqual($days, 4);

$t->start('diffTotal: 3,5 Days');
$days = dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"days");
$t->checkEqual($days, 3.5);

$t->start('diffTotal: Hours');
$hours = dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"hours");
$t->checkEqual($hours, 84);

$t->start('diffTotal: Minutes');
$hours = dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"Minutes");
$t->checkEqual($hours, 84*60);

$t->start('diffTotal: full Years');
$years = dt::create('1950-10-1 0:00')->diffTotal('2003-11-4 12:00',"Years");
$t->checkEqual($years, 53);

$t->start('diffTotal: full Month');
$month = dt::create('1950-10-1 0:00')->diffTotal('2003-11-4 12:00',"Month");
$t->checkEqual($month, 53*12+1);

$t->start('diffTotal: Seconds');
$seconds = dt::create('1950-10-1 0:00')->diffTotal('2033-11-4 12:00');
$t->check($seconds, $seconds == 2622283200);

$t->start('diffTotal: Seconds and Microseconds');
$seconds = dt::create('2016-06-03 15:30:45.2')->diffTotal('2016-06-03 15:30:47.4');
$t->checkEqual($seconds, 2.2,"",1E-10); //Delta for Float

$t->start('diffTotal: change winter/summer time');
$hours = dt::create('2014-03-30 00:00')->diffTotal('2014-03-30 06:00','hour');
$expected = (version_compare(PHP_VERSION, '5.5.8') >= 0) ? 5 : 6;
$t->checkEqual($hours, $expected);

$t->start('diffUTC: change winter/summer time');
$hours = dt::create('2014-03-30 00:00')->diffUTC('2014-03-30 06:00','hour');
$t->checkEqual($hours, 5.0);

$t->start('addSeconds: add 2,5 Seconds');
$date = dt::create("2015-03-31 12:00:01.5")->addSeconds(2.5);
$t->checkEqual((string)$date, "2015-03-31 12:00:04");

$t->start('addSeconds: sub 2,5 Seconds');
$date = dt::create("2015-03-31 12:00:01.5")->addSeconds(-2.5);
$t->checkEqual((string)$date, "2015-03-31 11:59:59");

$t->start('addTime: add 01:05:30');
$date = dt::create("2015-03-31 12:00")->addTime('01:05:30');
$t->checkEqual((string)$date, "2015-03-31 13:05:30");

$t->start('addTime: add 02:15');
$date = dt::create("2015-03-31 12:00")->addTime('02:15');
$t->checkEqual((string)$date, "2015-03-31 14:15:00");

$t->start('subTime: sub 02:30');
$date = dt::create("2015-03-31 12:00")->subTime('02:30');
$t->checkEqual((string)$date, "2015-03-31 09:30:00");

$t->start('add Month and cut');
$date = dt::create("31.01.2014")->addMonthCut(1);
$t->checkEqual((string)$date, "2014-02-28 00:00:00");

$t->start('add Month and cut');
$date = dt::create("31.01.2014")->addMonthCut(3);
$t->checkEqual((string)$date, "2014-04-30 00:00:00");

$t->start('totalRelTime: 3 Days 5 Hours to hours');
$hours = dt::totalRelTime("3 Days 5 Hours","h");
$t->checkEqual($hours, 3*24 + 5);

$t->start('totalRelTime: 1 year to days, basis 1.1.2005');
$days = dt::totalRelTime("1 year","days","2005-1-1");
$t->checkEqual($days, 365);

$t->start('totalRelTime: 1 month to days, basis 1.2.2004');
$days = dt::totalRelTime("1 month","days","Feb 2004");
$t->checkEqual($days, 29);

$t->start('totalRelTime: 1h 30min to hours');
$hours = dt::totalRelTime("1hour 30min","hours");
$t->checkEqual($hours, 1.5);

$t->start('totalRelTime: DateInterval-Object to hours');
$i = new DateInterval('P1DT12H');  //1 day + 12 hours
$hours = dt::totalRelTime($i,'h');
$t->checkEqual($hours, 36);

$t->start('create a date_interval from float');
$dateInterval = dt::date_interval_create_from_float(4.5,'min');
$t->checkEqual($dateInterval->format("%H:%I:%S"), '00:04:30');

$t->start('create a date_interval from float : idustrytime');
$dateInterval = dt::date_interval_create_from_float(0.071525,'hour');
$t->checkEqual($dateInterval->format("%H:%I:%S"), '00:04:17');

//Timestamps
$t->start('get a Float Timestamp 1.1.1970');
$timeStamp = dt::create('1970-01-01','UTC')->getMicroTime();
$t->checkEqual($timeStamp, 0.0);

$t->start('get a Timestamp before 1970');
$timeStamp = dt::create('1716-12-24')->getMicroTime();
$t->checkEqual($timeStamp, -7984573200.);

$t->start('get a Timestamp after 2038');
$timeStamp = dt::create('2065-06-12')->getMicroTime();
$t->checkEqual($timeStamp, 3011986800.);

$t->start('get a Float-Timestamp with fractions of seconds');
$timeStamp = dt::create('2006-06-12 13:45:56.25')->getMicroTime();
$t->checkEqual($timeStamp, strtotime('2006-06-12 13:45:56')+0.25);

$t->start('create Date from the Timestamp');
$date = dt::create(3011986800);
$t->checkEqual((string)$date ,'2065-06-12 00:00:00');

$t->start('set Timestamp');
$date = dt::create()->setTimestamp(3011986800);
$t->checkEqual((string)$date,'2065-06-12 00:00:00');

$t->start('set Float-Timestamp with Milliseconds');
$date = dt::create()->setTimestamp(3011986800.12);
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'),'2065-06-12 00:00:00.120000');

$t->start('set date 2015 to easter');
$easterDate =  dt::create("1.1.2015 12:15")->setEasterDate();
$t->checkEqual((string)$easterDate, '2015-04-05 00:00:00');

$t->start('set date 1526 to easter: bad Date -> exception');
$closure = function(){
  dt::create("1526-1-1")->setEasterDate();
};
$t->checkException($closure);
  
$t->start('set date 1626 to easter');
$easterDate =  dt::create("1626-1-1")->setEasterDate();
$t->checkEqual((string)$easterDate, '1626-04-12 00:00:00');

$t->start('set date 1626 to easter orthodox');
$easterDate =  dt::create("1626-1-1")->setEasterDate(dt::EASTER_EAST);
$t->checkEqual((string)$easterDate, '1626-04-19 00:00:00');

//
$t->start('was in berlin in june 2011 summertime');
$isSummertime = dt::create('Jun 2011','Europe/Berlin')->isSummertime();
$t->checkEqual($isSummertime, true);

$t->start('was in moscow in june 2011 summertime');
$isSummertime = dt::create('Jun 2011','Europe/Moscow')->isSummertime();
$t->checkEqual($isSummertime, false);

$t->start('toCET (from Summertime)');
$result = dt::create('2015-05-15 15:00','Europe/Berlin')->toCET();
$expect = '2015-05-15 14:00:00';
$t->check((string)$result, (string)$result == $expect);

$t->start('toCET (Wintertime)');
$result = dt::create('2015-02-15 15:00','Europe/Berlin')->toCET();
$expect = '2015-02-15 15:00:00';
$t->check((string)$result, (string)$result == $expect);

$t->start('is 2011 a leap year - no');
$isLeapYear = dt::create('Jun 2011')->isLeapYear();
$t->checkEqual($isLeapYear, false);

$t->start('is 2012 a leap year - yes');
$isLeapYear = dt::create('2012-1-1')->isLeapYear();
$t->checkEqual($isLeapYear, true);

$t->start('is at 1.May 2016 a weekend - yes');
$isWeekEnd = dt::create('1 May 2016')->isWeekEnd();
$t->checkEqual($isWeekEnd, true);

$t->start('is today in current week - yes');
$isInWeek = dt::create('today')->isInWeek();
$t->checkEqual($isInWeek, true);

$t->start('is 23.11.2015 in same week how 29.11.2015 - yes');
$isInWeek = dt::create('23.11.2015')->isInWeek('29.11.2015');
$t->checkEqual($isInWeek, true);

$t->start('is 23.11.2015 in same week how 30.11.2015 - no');
$isInWeek = dt::create('23.11.2015')->isInWeek('30.11.2015');
$t->checkEqual($isInWeek, false);

$t->start('is 23.11.2015 in same week how 22.11.2015 - no');
$isInWeek = dt::create('23.11.2015')->isInWeek('22.11.2015');
$t->checkEqual($isInWeek, false);

$t->start('is 25 Dez a public Holiday - yes');
$isHoliday = dt::create('25 Dec 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, true);

$t->start('is 24.12 a public Holiday - no');
$isHoliday = dt::create('24 Dezember 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, false);

$t->start('check Holidaylist for bad list');
$ChrismasAndSylvester = '24.12 and 31.12';
$errStr = dt::detectHolidayListError($ChrismasAndSylvester);  
$t->check($errStr, $errStr !== "");

$t->start('check Holidaylist with Chrismas and Sylvester');
$ChrismasAndSylvester = '24.12,31.12';
$errStr = dt::detectHolidayListError($ChrismasAndSylvester);  
$t->checkEqual($errStr,"");

$t->start('add Chrismas and Sylvester to Holidaylist');
$ChrismasAndSylvester = '24.12,31.12';
$isOk = dt::addHolidayList($ChrismasAndSylvester);  
$t->checkEqual($isOk, true);

$t->start('is 24 Dez now public Holiday - yes');
$isHoliday = dt::create('24 Dec 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, true);

$t->start('is Fronleichnahm 2017 public Holiday - no');
$isHoliday = dt::create('15.06.2017')->isPublicHoliday();
$t->checkEqual($isHoliday, false);

$t->start('is Fronleichnahm 2017 in Berlin Holiday - no');
$isHoliday = dt::create('15.06.2017')->isPublicHoliday('BE');
$t->checkEqual($isHoliday, false);

$t->start('is Fronleichnahm 2017 in BY Holiday - yes');
$isHoliday = dt::create('15.06.2017')->isPublicHoliday('BY');
$t->checkEqual($isHoliday, true);

$t->start('is Buß und Bettag in SN Holiday - yes');
$isHoliday = dt::create('2015-11-18')->isPublicHoliday('SN');
$t->checkEqual($isHoliday, true);

$t->start('is Buß und Bettag Holiday for all in D - no');
$isHoliday = dt::create('2015-11-18')->isPublicHoliday();
$t->checkEqual($isHoliday, false);

$t->start('is 3.10 a public Holiday - yes');
$isHoliday = dt::create('03 Oct 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, true);

$t->start('set Holidaylist for Austria');
$list = '1.1,6.1,E+1,1.5,E+39,E+50,E+60,15.8,26.10,1.11,8.12,25.12,26.12';
$result = dt::setHolidayList($list);
$t->checkEqual($result, true);

$t->start('is 3.10 in AT a public Holiday - no');
$isHoliday = dt::create('03 Oct 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, false);

$t->start('is 26.10 in AT a public Holiday - yes');
$isHoliday = dt::create('26 Oct 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, true);

$t->start('set Holidaylist to default');
$result = dt::setHolidayList();
$t->checkEqual($result, true);

$t->start('is 3.10 now a public  Holiday - yes');
$isHoliday = dt::create('03 Oct 2016')->isPublicHoliday();
$t->checkEqual($isHoliday, true);

//isCron
$t->start('isCron 00:01: ok');
$cronStr = "1 0 * * *";  //every Day 00:01 
$result = dt::create('2015-5-2 00:01:05')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('isCron 5 seconds after midnight: false');
$cronStr = "1 0 * * *";  //every Day 5 seconds after midnight 
$result = dt::create('2015-5-2 00:02:00')->isCron($cronStr);
$t->checkEqual($result, false);

$t->start('isCron mo-fr 01:20, 1:30: ok');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = dt::create('2017-7-27 01:20:00')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('isCron mo-fr 01:20, 1:30: false');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = dt::create('2017-7-27 01:21:00')->isCron($cronStr);
$t->checkEqual($result, false);

$t->start('isCron mo-fr 01:20, 1:30: ok');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = dt::create('2017-7-27 01:30:00')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('nextCron Mi 31.5.2017 : Do 1.6 01:05 : ok');
$cronStr = "5 1 * * *";  //every Day 1:05
$result = dt::create('2017-5-31 01:06:00')->nextCron($cronStr);
$t->checkEqual((string)$result, '2017-06-01 01:05:00');

$t->start(' Sa. 29.7.2017 nextCron Mo. 31.7.2017 01:05');
$cronStr = "*/30 1 * * 1-5";  //1:00, 1:30 every weekday
$result = dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
$t->checkEqual((string)$result, '2017-07-31 01:00:00');

$t->start('nextCron throw Exception for cron"* * * * 7"');
$cronStr = "* * * * 7";  //invalid cron
$closure = function() use($cronStr) {
  $result = dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
};
$t->checkException($closure);

$t->start('nextCron throw Exception for cron"60 * * * *"');
$cronStr = "60 * * * *";  //invalid cron
$closure = function() use($cronStr) {
  $result = dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
};
$t->checkException($closure);

$t->start('previousCron Do 1.6.2017 01:05 : ok');
$cronStr = "5 1 * * *";  //every Day 1:05
$result = dt::create('2017-06-01 01:05:00')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-05-31 01:05:00');

$t->start('previousCron Do 1.6.2017 01:35 : 1:20');
$cronStr = "*/20 * * * *";  //every 20 Minutes
$result = dt::create('2017-06-01 01:35:17')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-06-01 01:20:00');

$t->start('previousCron 1.6.2017 01:05 : 1:20');
$cronStr = "10 1 1 * *";  //every month 1:10
$result = dt::create('2017-06-01 01:05:08')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-05-01 01:10:00');

$t->start('1.6.2017 01:05 toCron');
$result = dt::create('2017-06-01 01:05:08')->toCron();
$t->checkEqual((string)$result, '5 1 1 6 *');

$t->start('chain: german "Buß und Bettag" 2015');
$date = dt::create('2015-1-1')->chain('11/23|last Wed');
$t->checkEqual((string)$date, '2015-11-18 00:00:00');

$t->start('chain: easter Monday 2016');
$date = dt::create('2016-1-1')->chain('{{easter}}|+1 Day');
$t->checkEqual((string)$date, '2016-03-28 00:00:00');

$t->start('chain: 1. advent this Year');
$date = dt::create('today')->chain('12/25|last Sunday|-3 weeks');
$expect = dt::create('26.11')->modify('next sunday');
$t->checkEqual((string)$date, (string)$expect);

$t->start('chain: 4. advent 2017');
$date = dt::create('2017-1-1')->chain('12/25|last Sunday');
$t->checkEqual((string)$date, '2017-12-24 00:00:00');

$t->start('chain: first Day of current month');
$date = dt::create()->chain('first Day of this Month | 00:00');
$t->checkEqual((string)$date, date('Y-m-01 00:00:00'));

$t->start('chain: Spring Bank Holiday 2016 (United Kingdom)');
$date = dt::create('2016-1-1')->chain('Last monday of May {{year}}');
$t->checkEqual((string)$date, '2016-05-30 00:00:00');

$t->start('chain with userparameter');
$para = array('{{theYear}}' => 2018);
$date = dt::create('2000-1-1')->chain('{{theYear}}-12-24 00:00',$para);
$t->checkEqual((string)$date, '2018-12-24 00:00:00');

$t->start('copy: new Instance');
$date = dt::create('2016-1-1');
//alternativ to $cloneDate = clone $date
$cloneDate = $date->copy();
$checkOk = ($date == $cloneDate) AND ($date !== $cloneDate);
$t->check((string)$cloneDate, $checkOk);

$t->start('copy and modify');
$date = dt::create('2016-01-01');
//not modify $date !
$nextDay = $date->copy()->modify('next Day');
$checkOk = ($date == dt::create('2016-01-01')) 
  AND ($nextDay == dt::create('2016-01-02'));
$t->check((string)$nextDay, $checkOk);

$t->start('Date Clock Change to Summertime 2016');
$date = dt::getClockChange(2016, false, "Europe/Berlin");
$t->checkEqual((string)$date, "2016-03-27 03:00:00");

$t->start('Date Clock Change to Wintertime 2016');
$date = dt::getClockChange(2016, true, "Europe/Berlin");
$t->checkEqual((string)$date, "2016-10-30 02:00:00");

$t->start('Date Clock Change to Summertime USA 2017');
$date = dt::getClockChange(2017, false, "America/New_York");
$t->checkEqual((string)$date, "2017-03-12 03:00:00");

$t->start('Date Clock Change to Summertime Moscow 2017');
$date = dt::getClockChange(2016, false, "Europe/Moscow");
$t->checkEqual($date, null);

$t->start('setClockChange to Wintertime USA 2017');
$date = dt::create('2017-1-1', "America/New_York")
  ->setClockChange(true);
$t->checkEqual((string)$date, "2017-11-05 01:00:00");

/*
 * End Tests 
 */

//output as table
echo $t->gethtml();



