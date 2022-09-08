<?php
//last modify: 2022-09-07
use Jspit\Dt;

error_reporting(-1);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

require __DIR__.'/../class/phpcheck.php';
require __DIR__.'/../class/class.debug.php';

$t = new PHPcheck();  //need min 1.3.17   
//call with ?error=1 print only errors
$t->setOutputOnlyErrors(!empty($_GET['error']));

/*
 * Tests Class 
 */
$classFile =  __DIR__.'/../class/Jspit/Dt.php';
require $classFile;

//für class.dt.php ab version 1.97
$t->start('check class version');
$info = $t->getClassVersion(Dt::class);
$t->check($info, !empty($info) AND $info >= 2.0);

$t->start('get hash of file');
$result = hash_file('crc32',$classFile);
$t->checkNotEqual($result,false);

$t->start('get numbers of lines');
$result = $t->getNumberOfLines($classFile);
$t->checkNotEqual($result,false);

$t->start('Check if extension IntlDateFormatter exists');
$msg = extension_loaded("Intl") 
  ? 'ok' 
  : 'missing Intl extension'
;
$t->checkEqual($msg,'ok');

$t->start('set default Timezone and Language');
Dt::default_timezone_set('Europe/Berlin');
$result =Dt::setDefaultLanguage('de');
$t->checkEqual($result,true);

$t->start('get default Language');
$result =Dt::getDefaultLanguage();
$t->checkEqual($result,'de');

//create
$t->start('Create current Time (Now)');
// create dates
$dt = Dt::create('now');
$ok = $dt->format('Y-m-d H:i:s') == date_create('now')->format('Y-m-d H:i:s');
$t->check($dt, $ok);

$t->start('cast dt object to string');
$dt = Dt::create('now');
$result = (string)$dt;
$t->checkEqual($result, $dt->format(Dt::TO_STRING_FORMAT));

$t->start('Create this Date 00:00 (Today)');
$dt = Dt::create('Today');
$t->check($dt, $dt == date_create('Today'));

$t->start('Create a fix Date dd.mm.yyyy HH:ii');
$date = Dt::create('6.11.1875 04:09');
$t->checkEqual((string)$date, '1875-11-06 04:09:00');

$t->start('Create a fix Date dd.mm.yy');
$date = Dt::create('5.6.15');
$t->checkEqual((string)$date, '2015-06-05 00:00:00');

$t->start('Create a fix Date dd.mm.');
$date = Dt::create('5.6.');
$t->checkEqual((string)$date, date('Y').'-06-05 00:00:00');

$t->start('Create a fix Date dd.mm.yy HH:ii');
$date = Dt::create('3.4.81 6:9');
$t->checkEqual((string)$date, '1981-04-03 06:09:00');

$t->start('Create a Date with Microseconds');
$date = Dt::create('1981-04-03 06:09:25.160000');
$t->checkEqual($date->toStringWithMicro(), '1981-04-03 06:09:25.160000');

$t->start('create Date B.C.(before christ)');
$date = Dt::create('-400-05-06');
$t->checkEqual((string)$date, '-0400-05-06 00:00:00');

$t->start('create Date B.C.(before christ)');
$date = Dt::create('-0036/05/06');
$t->checkEqual((string)$date, '-0036-05-06 00:00:00');

$t->start('Create a fix Date with english month');
$date = Dt::create('March 4, 2016');
$t->checkEqual((string)$date, '2016-03-04 00:00:00');

$t->start('Create a fix Date with german month');
$date = Dt::create('4.März 2016');
$t->checkEqual((string)$date, '2016-03-04 00:00:00');

$t->start('Create Date from year and Week-Number');
$date = Dt::create('2015W50');
$t->checkEqual((string)$date, '2015-12-07 00:00:00');

$t->start('Create a Date easter monday 2016 from chain of strings');
$date = Dt::create('2016-1-1|{{easter}}|+1 Day');
$t->checkEqual((string)$date, '2016-03-28 00:00:00');

$t->start('Date easter monday 2016 from chain with alternative spelling');
$date = Dt::create('2016-1-1 and {{easter}} and +1 Day');
$t->checkEqual((string)$date, '2016-03-28 00:00:00');

$t->start('Create Date from DateTime-Object');
$tz = new DateTimeZone("UTC");
$dateTime = date_create('2000-01-02 08:00', $tz);
$date = Dt::create($dateTime);
$expect = Dt::create('2000-01-02 08:00:00',$tz);
$t->checkEqual(json_encode($date), json_encode($expect));

$t->start('Create Date with new Timezone from DateTime-Object');
$tz = new DateTimeZone("UTC");
$dateTime = date_create('2000-01-02 08:00', $tz);
$date = Dt::create($dateTime, "Europe/Berlin");
$expect = Dt::create('2000-01-02 09:00:00',"Europe/Berlin");
$t->checkEqual(json_encode($date), json_encode($expect));

$t->start('Create Date Timezone UTC');
$date = Dt::create('1.1.2014 00:00','UTC');
$t->checkEqual($date->formatL('Y-m-d H:i O'), '2014-01-01 00:00 +0000');

$t->start('Create Date Timezone New York');
$date = Dt::create('1.1.2014 00:00','America/New_York');
$t->checkEqual($date->formatL('Y-m-d H:i O'), '2014-01-01 00:00 -0500');

$t->start('create from Format Y-m-dTH:i:s.uZ');
$date = Dt::create('2020-02-06T17:26:38.27774Z');
$t->checkEqual($date->format('Y-m-d H:i:s.u e'), '2020-02-06 17:26:38.277740 Z');

$t->start('create from influxdb time format');
$date = Dt::create('2020-02-06T17:26:38.277740846Z');
$t->checkEqual($date->format('Y-m-d H:i:s.u e'), '2020-02-06 17:26:38.277741 Z');

$t->start('Create Date from Integer-Timestamp');
$timeStamp = strtotime('2015-12-24');
$date = Dt::create($timeStamp);
$t->checkEqual((string)$date, '2015-12-24 00:00:00');

$t->start('Create Date from Float-Timestamp');
$floatTimeStamp = strtotime('2015-12-25') + 2.5;
$date = Dt::create($floatTimeStamp);
$expected = '2015-12-25 00:00:02.500000';
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'), $expected);

$t->start('Create Date from a big Float Timestamp with Milliseconds');
$date = Dt::create(35573107125.25);
$expected = '3097-04-07 19:38:45.250000';
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'), $expected);

$t->start('Create Date from Millisecond Timestamp');
$milliseconds = '1601735792198';
$date = Dt::create($milliseconds/1000);
$expected = "2020-10-03T16:36:32.198000+02:00";
$t->checkEqual($date->formatL('Y-m-d\TH:i:s.uP'), $expected);

$t->start('Create Date from a big negative Float Timestamp');
$date = Dt::create(-63292409608.0);
$t->checkEqual((string)$date,'-0036-05-06 00:00:00');

$t->start('Create Date from a big negative Float Timestamp with @');
$date = Dt::create('@-2636450170.75','UTC');
$t->checkEqual($date->toStringWithMicro(),"1886-06-15 12:43:49.250000");

$t->start('Create Date from a Javascript Milliseconds Timestamp');
$timeStamp = 1393161787500;
$date = Dt::create($timeStamp/1000);
$t->checkEqual($date->toStringWithMicro(),"2014-02-23 14:23:07.500000");

$t->start('Create Date from a big negativ Float Timestamp');
$date = Dt::create(-22144579200.,'UTC');
$t->checkEqual((string)$date, '1268-04-07 00:00:00');

$t->start('Create from int arguments yaer, month..');
$dt = Dt::create(2019,5,1,12,23,45,12345);
$t->checkEqual($dt->toStringWithMicro(),"2019-05-01 12:23:45.012345");

$t->start('Create from arguments yaer=null, month, day + hour');
$dt = Dt::create(null,12,24,18);  //24.12 18:00 this year
$t->checkEqual($dt->format('Y-m-d H:i:s'),date("Y")."-12-24 18:00:00");

$t->start('Create today at 18:30');
$dt = Dt::create(null,null,null,18,30);
$expected = Dt::create('today 18:30');  //better readable
$t->check((string)$dt, $dt == $expected);

$t->start('Create from int arguments yaer, month, day, timezone');
$dt = Dt::create(2019,5,1,"Europe/Moscow");
$t->checkEqual($dt->format('Y-m-d H:i:s e'),"2019-05-01 00:00:00 Europe/Moscow");

$t->start('Create from int arguments H:i curr.Time + timezone UTC');
$dt = Dt::create(2019,5,1,null,null,"UTC");
$t->checkEqual($dt->format('H:i'),gmdate('H:i'));

$t->start('Create from int arguments with negative year');
$dt = Dt::create(-432,5,1,"UTC");
$t->checkEqual($dt->format('Y-m-d e'),'-0432-05-01 UTC');

//create with wildcards
$t->start('Create Now with Wildcards');
$date = Dt::create("{{Y-m-d}}");
$t->checkEqual((string)$date, date("Y-m-d H:i:s"));

$t->start('Create Today with Wildcards');
$date = Dt::create("{{Y-m-d}} 00:00");
$t->checkEqual((string)$date, (string)Dt::create("Today"));

$t->start('Create easter date this year');
$date = Dt::create("{{easter}}");
$expected = Dt::create("Today")->setEasterDate();
$t->checkEqual((string)$date, (string)$expected);

$t->start('last Monday of October this year with Wildcard');
$date = Dt::create("last Monday of October {{year}}");
$expected = Dt::create("last Monday of October this year");
$t->checkEqual((string)$date, (string)$expected);

$t->start('Basis of Daynumber of 2017-08-01 (date("z")');
$date = Dt::create("2017-08-17 00:00|-{{z}} Days");
$expected = Dt::create("2017-01-01 00:00");
$t->checkEqual((string)$date, (string)$expected);

$t->start("if 2018-07-29 is Sunday (yes), take the next Monday");
$date = Dt::create("2018-07-29|{{?D=Sun}}next Monday");
$t->checkEqual((string)$date, "2018-07-30 00:00:00");

$t->start("if 2018-07-28 is Sunday (no), take the next Monday");
$date = Dt::create("2018-07-28|{{?D=Sun}}next Monday");
$t->checkEqual((string)$date, "2018-07-28 00:00:00");

$t->start('2 Month after 2017-06-15 13:30, Day 5 of this Month, same Time');
$date = Dt::create("2017-06-15 13:30|+2 month|{{Y}}-{{m}}-05");
$t->checkEqual((string)$date, "2017-08-05 13:30:00");

$t->start('crate dt with language en');
Dt::setDefaultLanguage('en');
$dt = Dt::create('2017-03-03T09:06:41.187');
$t->checkEqual($dt->toStringWithMicro(), "2017-03-03 09:06:41.187000");

$t->start('Date with relative Format');
$dt = Dt::create('first Sunday of Jun 2018 23:16:15.4');
$t->checkEqual((string)$dt, "2018-06-03 23:16:15");

$t->start('Date with relative Format language de');
Dt::setDefaultLanguage('de');
$dt = Dt::create('first Sunday of Jun 2018 23:16:15.4');
$t->checkEqual((string)$dt, "2018-06-03 23:16:15");

$t->start('Date with pm time');
$dt = Dt::create('Nov 7 2008 1:45 pm');
$t->checkEqual((string)$dt, "2008-11-07 13:45:00");


//create Dt from format
$t->start('crate dt from format');
$input     = "Apr 19 '15 at 13:56";
$format = "M j 'y ?? H:i";
$dt = Dt::createDtFromFormat($format, $input);
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "2015-04-19 13:56:00"); 

$t->start('crate dt from format');
$input = "2020-01-07T11:55:34:438 GMT+0600";
$format = 'Y-m-d\Th:i:s:u \G\M\TO';
$dt = Dt::createDtFromFormat($format, $input);
$result = $dt ? $dt->format($format) : $dt;
$t->checkEqual($result, "2020-01-07T11:55:34:438000 GMT+0600");

$t->start('create from day of year starting with 1 and year (DOY)');
$input = "76 2021";
$dt = Dt::createDtFromFormat("!z Y",$input)->modify("-1 Day");
$t->checkEqual((string)$dt, "2021-03-17 00:00:00");

$t->start('crate dt from format with array of formats');
$input = '7/1/2018';
$formats = ['!Y/m/d','!m/d/Y'];
$dt = Dt::createDtFromFormat($formats,$input);
$t->checkEqual((string)$dt, "2018-07-01 00:00:00");

$t->start('crate dt from format strict mode');
Dt::setStrictModeCreate(true);
$input = "13 2017";  //13 is a invalid month
$dt = Dt::createDtFromFormat("!n Y", $input);
$t->checkEqual($dt, false);

$t->start('crate dt from format non strict mode');
Dt::setStrictModeCreate(false); //default false
$input = "13 2017";
$dt = Dt::createDtFromFormat("!n Y", $input);
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "2018-01-01 00:00:00");

$t->start('crate dt from format with french Month');
$input = "7 Août 2020";
Dt::setDefaultLanguage('fr');
$dt = Dt::createDtFromFormat("!d M Y", $input);
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "2020-08-07 00:00:00");

$t->start('Create from the Japanese');
//UTF-8 multibyte characters need * instead of ?
$input ='2008年11月13日';
$dt = Dt::createDtFromFormat('!Y*m*d*',$input);
$t->checkEqual((string)$dt, "2008-11-13 00:00:00");

//reset DefaultLanguage
Dt::setDefaultLanguage('de');

if(extension_loaded("Intl")) {

$t->start('createFromIntlFormat');
$input = "21 Août 2020";
//http://userguide.icu-project.org/formatparse/datetime
$dt = Dt::createFromIntlFormat('d MMMM yyyy',$input,'Europe/Paris','fr'); 
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "2020-08-21 00:00:00");

$t->start('createFromIntlFormat th@calendar=buddhist');
$input = "17 มกราคม 2507";  //January 17, 1964
$dt = Dt::createFromIntlFormat('d MMMM yyyy',$input,"Asia/Bangkok",'th@calendar=buddhist'); 
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "1964-01-17 00:00:00");

$t->start('createFromIntlFormat ja_JP@calendar=japanese');
$input = "平成30年8月1日";  //1 August 2018
$dt = Dt::createFromIntlFormat('LONG+NONE',$input,"Asia/Tokyo",'ja_JP@calendar=japanese'); 
$result = $dt ? (string)$dt : $dt;
$t->checkEqual($result, "2018-08-01 00:00:00");

$t->start('timeStampFromIntlFormat');
$input = "8 Août 20";  //2020-08-08
$ts = Dt::timeStampFromIntlFormat('d MMMM yy',$input,'Europe/Paris','fr');
$expected = date_create('2020-08-08',new DateTimeZone('Europe/Paris'))
  ->getTimeStamp();
$t->checkEqual($ts, $expected);

} //IntlDateFormatter

$t->start('crate from Julian Date Number');
$date = Dt::createFromJD(2458294.65168,"UTC");
$t->checkEqual((string)$date, "2018-06-25 03:38:25");

$t->start('crate from Julian Date Number (int)');
$jdNumber = 2345678;
$date = Dt::createFromJD($jdNumber,"UTC");
$arr = cal_from_jd($jdNumber,CAL_GREGORIAN);
$t->check($date->format('Y-m-d'), $arr['date'] === $date->format('n/j/Y'));

$t->start('crate from Microsoft Excel Timestamp');
$date = Dt::createFromMsTimestamp(43317.54,"UTC");
$t->checkEqual((string)$date, "2018-08-05 12:57:36");

$t->start('crate from Microsoft Excel Timestamp with Milliseconds');
$date = Dt::createFromMsTimestamp(5273.57305851856,"UTC");
$expected = "1914-06-08 13:45:12.256000";
$t->checkEqual($date->toStringWithMicro(), $expected);

//createFromSystemTime
$t->start('createFromSystemTime');
//create a LabVIEW Timestamp from base 1 Jan 1904 with resulution 1ms
$testDate = "2006-12-13 09:45:55";
$basisDate = "1904-1-1";
$resolution = 0.001; //1ms
$timeStamp = -Dt::create($testDate,'UTC')->diffTotal($basisDate,"Seconds")/$resolution;
//createFromSystemTime
$date = Dt::createFromSystemTime($timeStamp,$basisDate,$resolution,"UTC");
$t->checkEqual((string)$date, $testDate);

$t->start('create dt from a LDAP Timestamp');
$timeStamp = 130981536000000000;
$date = Dt::createFromSystemTime($timeStamp,'1601-1-1',1.E-7,"UTC");
$expected = "2016-01-25 00:00:00";
$t->checkEqual((string)$date, $expected);

$t->start('create dt from a LDAP Timestamp with Microseconds');
$timeStamp ="132497313049180481";
$date = Dt::createFromSystemTime($timeStamp,'1601-1-1',1.E-7,"UTC");
$expected = Dt::create('2020-11-13 08:55:04.9180481','UTC');
$ok = abs($date->diffTotal($expected,'Sec')) < 0.00001;  //tolerance 10 µs
$t->check($date->toStringWithMicro(), $ok);

$t->start('create dt from Mac Timestamp: seconds since Jan 1 1904');
$timeStamp = 3662360215;
$date = Dt::createFromSystemTime($timeStamp,'Jan 1 1904',1.0,"UTC");
$expected = "2020-01-20 10:16:55";
$t->checkEqual((string)$date, $expected);

$t->start('create dt from Microsoft Timestamp: days since Dec 31 1899');
$timeStamp = 43850.428414352;
$date = Dt::createFromSystemTime($timeStamp,'Dec 30 1899','1 Day',"UTC");
$expected = "2020-01-20 10:16:55";
$t->checkEqual((string)$date, $expected);

$t->start('convert c# DateTime.ToBinary() to DateTime');
$numFromCsharp = -8586307756854775808;  //2 bit kind + 62 bit ticks
//remove kind bits
$removeKindBits = function($num){
  if(PHP_INT_SIZE > 4) return (int)$num & 0x3fffffffffffffff;
  if($num < 0) $num += 9223372036854775808;
  $b2 = 4611686018427387903;
  if($num > $b2) $num -= $b2;
  return $num;
};
$ticks = $removeKindBits($numFromCsharp);
$date = Dt::createFromSystemTime($ticks,'0001-1-1',1.E-7,"UTC");
$expected = "2019-10-11 22:00:00";
$t->checkEqual((string)$date, $expected);


//
$t->start('Create Date from not valid String');
$t->setErrorLevel(0);//error reporting off for this test
$date = Dt::create('31.0x.2015');
$t->restoreErrorLevel();
$t->checkEqual($date, false);


$t->start('Get last Errormassage');
$errorInfo = Dt::getLastErrorsAsString() ;
$t->check($errorInfo, $errorInfo != '');

$t->start('Parse a invalid Date: 31.02.');
$date = Dt::create('31.02.2015'); //set to 2015-03-03
$t->checkEqual((string)$date, '2015-03-03 00:00:00');

$t->start('getErrorInfo() ');
$errInfo = $date->getErrorInfo();
$t->check($errInfo, !empty($errInfo));

$t->start('Parse a invalid Date with StrictMode');
Dt::setStrictModeCreate(true); //default false
$date = Dt::create('31.02.2015');
$t->checkEqual($date, false);

$t->start('Create Date with regular Expressions');
$str = '24 Dezember';
$regEx = '~(?<d>\d{1,2}) (?<F>\w+)~';
$dateTemplate = "1.1.2010 18:00";
$date = Dt::createFromRegExFormat($regEx,$str,null,$dateTemplate);
$t->checkEqual((string)$date,'2010-12-24 18:00:00');

$t->start('Create Date with regular Expressions');
$str = '2.5 19 Uhr 30';
$regEx = '~(?<d>\d{1,2})\.(?<m>\d{1,2}) ?(?<H>\d{1,2}) Uhr (?<i>\d{1,2})~';
$date = Dt::createFromRegExFormat($regEx,$str);
$t->checkEqual((string)$date,date('Y').'-05-02 19:30:00');

$t->start('Create Date with regular Expressions and negative Year');
$str = '-45/8';
$regEx = '~^(?<Y>-?[\d]{1,4})/(?<m>\d{1,2})$~';
$dateTemplate = "0000-01-01";
$date = Dt::createFromRegExFormat($regEx,$str,null,$dateTemplate);
$t->checkEqual((string)$date,'-0045-08-01 00:00:00');

$t->start('get matches from last RegEx ');
$matches = $date->getMatchLastCreateRegEx(); 
$expected = [
  0 => "-45/8",
  'Y' => "-45",
  1 => "-45",
  'm' => "8",
  2 => "8",
];
$t->checkEqual($matches,$expected);

$t->start('Create date using a array of regular expressions');
$regEx = [
  '~^(?<d>\d{2})/(?<m>\d{2})/(?<Y>\d{4})$~',
  '~^(?<d>\d{2})/(?<M>\p{L}+)/(?<Y>\d{4})$~iu'
];
$date1 = Dt::createFromRegExFormat($regEx , "18/Feb/2020");
$date2 = Dt::createFromRegExFormat($regEx , "05/07/1968");
$result = $date1.'|'.$date2;
$expected = '2020-02-18 00:00:00|1968-07-05 00:00:00';
$t->checkEqual($result,$expected);

$t->start('createFromRegExFormat with strict Mode');
$t->setErrorLevel(0);//error reporting off for this test
Dt::setStrictModeCreate(true);
$regEx = '~^(?<d>\d{2})(?<m>\d{2})(?<Y>\d{4})$~';
$dt = Dt::createFromRegExFormat($regEx,'33112022');
Dt::setStrictModeCreate(false);
$t->restoreErrorLevel();
$t->checkEqual($dt,false);

//formatL
$t->start('format with German short Weekday');
// format dates 
$strDate = Dt::create('2014-12-20')->formatL('D, d.m.Y');
$t->checkEqual($strDate,'Sam, 20.12.2014');

$t->start('format with English short Weekday');
$strDate = Dt::create('2014-12-20')->formatL('D, d.m.Y','en');
$t->checkEqual($strDate,'Sat, 20.12.2014');

if(extension_loaded("Intl")){ //with IntlDateFormatter
  
$t->start('IntlDateFormatter exists: language "fr"');  
$strDate = Dt::create('14.1.2015')->formatL('l, d F Y','fr');  
$t->checkEqual($strDate,'mercredi, 14 janvier 2015');

$t->start('format are IntlDateFormatter Constants');  
$strDate = Dt::create('14.1.2015')->formatL('FULL+SHORT','pl');  
$t->checkEqual($strDate,'środa, 14 stycznia 2015 00:00');
 
$t->start('format with buddhist calendar');  
$strDate = Dt::create('14.1.2015 16:45')->formatL('FULL+SHORT','es_ES@calendar=buddhist');  
$t->checkContains($strDate,'miércoles,14,enero,2558,BE,16:45');

$t->start('format with persian calendar + user-format');
$icuFormat = "yyyy-MM-dd"; //ICU must use if calendar not gregorian
$lang = "ir_IR@calendar=persian";
$date = Dt::create("2018-2-19")->formatL($icuFormat,$lang); 
$t->checkEqual($date, "1396-11-30");

$t->start('format with persian calendar + user-format');
$icuFormat = "yyyy-MM-dd"; //ICU must use if calendar not gregorian
$lang = "ir_IR@calendar=persian";
$date = Dt::create("2018-2-19")->formatL($icuFormat,$lang); 
$t->checkEqual($date, "1396-11-30");

$t->start('format fa_IR with persian calendar + user-format');
$icuFormat = "yyyy:MM:dd"; //ICU must use if calendar not gregorian
$lang = "fa_IR@calendar=persian";
$date = Dt::create("2018-2-19")->formatL($icuFormat,$lang); 
$t->checkEqual($date, "۱۳۹۶:۱۱:۳۰");

$t->start('format with persian calendar,user-format');
$icuFormat = "MMMM d, yyyy"; //ICU must use if calendar not gregorian
$lang = "ir_IR@calendar=persian";
$date = Dt::create("2018-2-19")->formatL($icuFormat,$lang); 
$t->checkEqual($date, "Bahman 30, 1396");

$t->start('formatIntl with quarter');
$date = Dt::create("2018-2-19")->formatIntl("qqqq y","en");
$t->checkEqual($date, '1st quarter 2018'); 

$t->start('formatIntl with persian calendar + user-format');
$icuFormat = "yyyy-MM-dd"; //must use if calendar not gregorian
$lang = "ir_IR@calendar=persian";
$date = Dt::create("2018-2-19")->formatIntl($icuFormat,$lang); 
$t->checkEqual($date, "1396-11-30");
/* 
 * input other language
 */

$t->start('Input in Russian');
// date from strings given in various languages
Dt::setDefaultLanguage('ru');
$date = Dt::create("1 октября 1990");
$t->check($date, $date == Dt::create("1990-10-01"));

$t->start('Input in danish');  
Dt::setDefaultLanguage('DA');
$date = Dt::create("3. maj 2018 06:39");
$t->check($date, $date == Dt::create("2018-05-03 06:39:00"));

$t->start('Input in Spanish');  
Dt::setDefaultLanguage('es');
$date = Dt::create('2 de enero de 1992');
$t->check($date, $date == Dt::create("1992-01-02 00:00:00"));

$t->start('Input in Greek');  
Dt::setDefaultLanguage('el');
$date = Dt::create('13 Δεκεμβρίου 2007 12:45 μ.μ.');
$t->check($date, $date == Dt::create("2007-12-13 12:45:00"));

$t->start('createDtFromFormat with with Finnish month name');
Dt::setDefaultLanguage('fi');
$input = "18/Helmi/2020";
$date = Dt::createDtFromFormat('|d/M/Y', $input);
$t->check($date, $date == Dt::create("2020-02-18 00:00:00"));

$t->start('createDtFromFormat with abbreviations of Italian month names');
//A list is required for names with less than 3 letters
$list = 'genn,febbr,mar,apr,magg,giugno,luglio,ag,sett,ott,nov,dic,
lunedì,martedì,mercoledì,giovedì,venerdì,sabato,domenica';
Dt::addLanguage("it",$list);
Dt::setDefaultLanguage('it');
$dt = dt::createDtFromFormat('!My','ag20');
$strDate = $dt->formatL('l j F Y');
$t->checkEqual($strDate, "sabato 1 ag 2020");

$t->start('createFromRegExFormat with with Estonian month name');
Dt::setDefaultLanguage('et');  //estnisch
$str = '26/Veebr/2005/13:45'; 
$regEx = '~(?<d>\d{1,2})/(?<M>\w+)/(?<Y>\d{4,4})/(?<H>\d{1,2}):(?<i>\d{1,2})~'; 
$date = Dt::createFromRegExFormat($regEx,$str);
$result = $date ? $date->formatL('l, d.F Y H:i') : "Error";
$expected = "laupäev, 26.veebruar 2005 13:45";
$t->checkEqual($result, $expected);

//set default for checks
Dt::setDefaultLanguage('de');
//end checke with IntlDateFormatter
}
else {
$t->start("IntlDateFormatter not available");
$s = "IntlDateFormatter not available, neeed for some test's";
$t->check($s,true);
}

//utcFormat
$t->start('utcFormat: time is UTC/GMT');
$dt = Dt::create('2019-10-10 03:00', 'Europe/Bucharest');
$utcTime = $dt->utcFormat();
$t->checkEqual($utcTime, "2019-10-10 00:00:00");

$t->start('utcFormat: time is UTC/GMT');
$dt = Dt::create('2019-07-10 02:45', 'Europe/Berlin');
$utcTime = $dt->utcFormat('d.m.Y H:i');
$t->checkEqual($utcTime, "10.07.2019 00:45");

$t->start('Add another language');
$list = "janvier,février,mars,avri,mai,juin,juillet,août,septembre,octobre,novembre,décembre,
lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche,
secondes,minutes,heures,jours,semaines,mois,années,an";
Dt::addLanguage("fr",$list);
$strDate = Dt::create('14.1.2015')->formatL('l, d F Y','fr'); 
$t->checkEqual($strDate,'mercredi, 14 janvier 2015');

//setTime
$t->start('setTime(): set the time for a date');
// Time manipulation with setTime
$date =Dt::create('4.6.2011 18:00')->setTime(9,15);
$t->checkEqual((string)$date,'2011-06-04 09:15:00');

$t->start('setTime(): set hour and minute to 0 ');
$date =Dt::create('4.6.2011 18:15:46')->setTime(9);
$t->checkEqual((string)$date,'2011-06-04 09:00:00');

$t->start('setTime(): set minute to 6, second to 0 ');
$date =Dt::create('4.6.2011 18:15:46')->setTime(null,6);
$t->checkEqual((string)$date,'2011-06-04 18:06:00');

$t->start('setTime(hh:mm): set the time for a date');
$date =Dt::create('4.6.2011 18:00')->setTime('10:14');
$t->checkEqual((string)$date,'2011-06-04 10:14:00');

$t->start('set to the start of day with setTime(hh:mm)');
$date =Dt::create('4.6.2011 18:00')->setTime('00:00');
$t->checkEqual((string)$date,'2011-06-04 00:00:00');

$t->start('setTime from another dt/date object');
$dateWithTime = date_create('2011-1-1 13:45');
$date =Dt::create('2012-12-08')->setTime($dateWithTime);
$t->checkEqual((string)$date,'2012-12-08 13:45:00');

$t->start('setTime with microseconds');
$date =Dt::create('2012-12-08')->setTime(12,30,15,12345);
$t->checkEqual($date->toStringWithMicro(), '2012-12-08 12:30:15.012345');

//setMicroSeconds
$t->start('set microseconds part');
$date = Dt::create('2011-1-1 13:45:10')->setMicroSeconds(250000);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:10.250000');

$t->start('set Seconds and Milliseconds');
$date = Dt::create('2011-1-1 13:45:10')->setSecond(3.5);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:03.500000');

$t->start('set Seconds and Milliseconds from dt object');
$dt = Dt::create('2016-10-09 10:00:55.567');
$date = Dt::create('2011-1-1 13:45:10')->setSecond($dt);
$t->checkEqual($date->toStringWithMicro(), '2011-01-01 13:45:55.567000');

$t->start('set Minute');
$date = Dt::create('2011-1-1 13:45:10')->setMinute(30);
$t->checkEqual((string)$date, '2011-01-01 13:30:10');

$t->start('set Hour');
$date = Dt::create('2011-1-1 13:45:10')->setHour(5);
$t->checkEqual((string)$date, '2011-01-01 05:45:10');

//setDate
$t->start('setDate: set the year');
// Manipulate the date with setDate
$date = Dt::create('2011-1-1 13:45')->setDate(2015);
$t->checkEqual((string)$date, '2015-01-01 13:45:00');

$t->start('setDate: set the month to May');
$date = Dt::create('2011-1-1 13:45')->setDate(null,5);
$t->checkEqual((string)$date, '2011-05-01 13:45:00');

$t->start('setDate: set the day to 16');
$date = Dt::create('2011-1-1 13:45')->setDate(null,null,16);
$t->checkEqual((string)$date, '2011-01-16 13:45:00');

$t->start('setDate: set year, month and day');
$date = Dt::create('2011-1-1 13:45')->setDate(2015,5,16);
$t->checkEqual((string)$date, '2015-05-16 13:45:00');

$t->start('setDate: set year, month and day from datestring');
$date = Dt::create('2011-1-1 13:45')->setDate("16.5.2015");
$t->checkEqual((string)$date, '2015-05-16 13:45:00');

$t->start('setDate: full date from dt/date-object');
$dateObject = Dt::create("2015/5/16");
$dateWithTime = Dt::create('2011-1-1 13:45')->setDate($dateObject);
$t->checkEqual((string)$dateWithTime, '2015-05-16 13:45:00');

$t->start('setYear: get year from dt/date-object');
$dateObject = Dt::create("2015/5/16");
$dateWithTime = Dt::create('2011-1-1 13:45')->setYear($dateObject);
$t->checkEqual((string)$dateWithTime, '2015-01-01 13:45:00');

$t->start('setYear: get year from a date string');
$strDate = "2016-12-24 18:54";
$dateWithTime = Dt::create('2011-3-12 11:45:10')->setYear($strDate);
$t->checkEqual((string)$dateWithTime, '2016-03-12 11:45:10');

$t->start('setYear: get year from a relative date string');
$dateWithTime = Dt::create('2011-3-12 11:45:10')->setYear('next year');
$expected = (date('Y')+1).'-03-12 11:45:10'; 
$t->checkEqual((string)$dateWithTime, $expected);

$t->start('setYear: use current year');
$dateWithTime = Dt::create('2011-06-12 14:15')->setYear();
$t->checkEqual((string)$dateWithTime, date('Y').'-06-12 14:15:00');

$t->start('setYear: get next year in future');
$dateWithTime = Dt::create('2011-01-01 00:00:56')->setYear(true);
$t->checkEqual((string)$dateWithTime, (date('Y')+1).'-01-01 00:00:56');

$t->start('setYear: set year with fix number');
$dateWithTime = Dt::create('2012-10-15 16:25')->setYear(2015);
$t->checkEqual((string)$dateWithTime, '2015-10-15 16:25:00');

$t->start('set the ISOweek');
$date =Dt::create('2014-5-6 07:04:30')->setISOweek(51);
$t->checkEqual((string)$date, '2014-12-15 07:04:30');

$t->start('setDateTimeFrom DateTime');
$dateTime = date_create('2019-08-31 UTC');
$date = Dt::create('1988-01-02 11:24','Europe/Berlin')
  ->setDateTimeFrom($dateTime);
$testOk = ($date == $dateTime AND $date !== $dateTime);
$t->check($date->format('Y-m-d H:i:s.u e'), $testOk);

//get..
$t->start('get Seconds of Day');
$seconds = Dt::create('2014-5-6 07:04:30')->getDaySeconds();
$t->checkEqual($seconds, 7*60*60 + 4*60 + 30);

$t->start('get Minutes of Day');
$seconds = Dt::create('2014-5-6 07:04:30')->getDayMinutes();
$t->checkEqual($seconds, 7*60 + 4);

$t->start('get microseconds part');
$microSeconds = Dt::create('2014-5-6 07:04:30.25')->getMicroSeconds();
$t->checkEqual($microSeconds, 250000);

$t->start('get count Days after 0001-01-01');
$dayCount = Dt::create('18.12.2014','UTC')->toDays();
$t->checkEqual($dayCount, 735585);

$t->start('get age in years');
$age = Dt::create('now -14 years')->age();
$t->checkEqual($age, 14);

$t->start('get age in years with time 23:59');
$date = Dt::create('now -14 years')->setTime('23:59:59');
$age = $date->age();
$t->checkEqual($age, 13);

$t->start('get age in years with time 23:59 and param true');
$date = Dt::create('now -14 years')->setTime('23:59:59');
$age = $date->age(true);  //true set time for age to 00:00
$t->checkEqual($age, 14);

//countDaysTo
$t->start('get count Sundays between 31.8.-9.9.19');
$result = Dt::create('2019-8-31')->countDaysTo('Sun','2019-9-9');
$t->checkEqual($result, 2);

$t->start('get count Sundays between 1.9.-9.9.19');
$result = Dt::create('2019-9-1')->countDaysTo('Sun','2019-9-9');
$t->checkEqual($result, 2);

$t->start('get count Sat + Sundays between 30.8.-9.9.19');
$result = Dt::create('2019-8-30')->countDaysTo('Sat,Sun','2019-9-9');
$t->checkEqual($result, 4);

$t->start('get count Sundays between 1.9.-8.9.19');
$result = Dt::create('2019-9-1')->countDaysTo('Sun','2019-9-8');
$t->checkEqual($result, 2);

$t->start('get count Sundays between 2.9.-8.9.19');
$result = Dt::create('2019-9-2')->countDaysTo('Sun','2019-9-8');
$t->checkEqual($result, 1);

$t->start('get count Sundays between 1.9.-7.9.19');
$result = Dt::create('2019-9-1')->countDaysTo('Sun','2019-9-7');
$t->checkEqual($result, 1);

$t->start('get count Sundays between 2.9.-7.9.19');
$result = Dt::create('2019-9-2')->countDaysTo('Sun','2019-9-7');
$t->checkEqual($result, 0);

$t->start('get count Sundays between 9.9.-1.9.19');
$result = Dt::create('2019-9-9')->countDaysTo('Sun','2019-9-1');
$t->checkEqual($result, -2);

$t->start('get count the Weekday(current Day) from Today to +1 week exclude dateTo');
$result = Dt::create('Today')->countDaysTo('Today','+1 week', true);
$t->checkEqual($result, 1);

$t->start('Count all weekdays in October 2019, Mon-Fri as Numbers 1..5');
$result = Dt::create('2019-10-01')->countDaysTo('1,2,3,4,5','2019-10-31');
$t->checkEqual($result, 23);

$t->start('Count all days in October 2019');
$result = Dt::create('2019-10-01')->countDaysTo('all','2019-10-31');
$t->checkEqual($result, 31);

$t->start('get count with wrong day');
$t->setErrorLevel(0);//error reporting off for next 2 tests
$result = Dt::create('2019-9-9')->countDaysTo('Sam','2019-9-1');
$t->checkEqual($result, false);

$t->start('get count with wrong dateTo');
$result = Dt::create('2019-9-9')->countDaysTo('Sat','xx');
$t->restoreErrorLevel();
$t->checkEqual($result, false);

$t->start('get quarter number of 2015-3-31');
$result = Dt::create('2015-3-31')->getQuarter();
$t->checkEqual($result, 1);

$t->start('get quarter number of 2015-4-1');
$result = Dt::create('2015-4-1')->getQuarter();
$t->checkEqual($result, 2);

$t->start('get DateTime-Object');
$dateTime = Dt::create('2014-09-23','America/New_York')
  ->getDateTime()
;
$ok = $dateTime instanceof DateTime 
  && !($dateTime instanceof dt)
  && $dateTime->format('Y-m-d e') === '2014-09-23 America/New_York'
;
$t->check($dateTime, $ok);

$t->setErrorLevel(0);//error reporting off for this test 
$t->start('get unknown property : false');
/*
 *  Properties
 */
$closure = function(){
    $result = Dt::create('2017-11-18')->unknown;
};
$t->checkException($closure);
$t->restoreErrorLevel(); 

$t->start('get year as integer');
$result = Dt::create('2015-4-1 06:15:46')->year;
$t->checkEqual($result, 2015);

$t->start('get month as integer');
$result = Dt::create('2015-4-1 06:15:46')->month;
$t->checkEqual($result, 4);

$t->start('get day as integer');
$result = Dt::create('2015-04-07 06:15:46')->day;
$t->checkEqual($result, 7);

$t->start('get short day name');
$result = Dt::create('2015-04-07 06:15:46')->dayName;
$t->checkEqual($result, "Tue");

$t->start('get full day name');
$result = Dt::create('2015-04-07 06:15:46')->fullDayName;
$t->checkEqual($result, "Tuesday");

$t->start('get hour as integer');
$result = Dt::create('2015-4-14 06:15:46')->hour;
$t->checkEqual($result, 6);

$t->start('get minute as integer');
$result = Dt::create('2015-4-14 06:15:46')->minute;
$t->checkEqual($result, 15);

$t->start('get second as integer');
$result = Dt::create('2015-4-14 06:15:46')->second;
$t->checkEqual($result, 46);

$t->start('get microSecond as integer');
$result = Dt::create('2015-4-14 06:15:46.123')->microSecond;
$t->checkEqual($result, 123000);

$t->start('get dayOfWeek ISO 8601 Monday: 1');
//ISO 8601 1: Monday .. 7: Sunday 
$result = Dt::create('next Monday')->dayOfWeek;
$t->checkEqual($result, 1);

$t->start('get dayOfWeek ISO 8601 Saturday: 6');
//ISO 8601 1: Monday .. 7: Sunday 
$result = Dt::create('next Saturday')->dayOfWeek;
$t->checkEqual($result, 6);

$t->start('get dayOfWeek ISO 8601 Sunday: 7');
//ISO 8601 1: Monday .. 7: Sunday 
$result = Dt::create('next Sunday')->dayOfWeek;
$t->checkEqual($result, 7);

$t->start('get daysInMonth');
//Nov has 30 days
$result = Dt::create('2017-11-18')->daysInMonth;
$t->checkEqual($result, 30);

$t->start('get dayOfYear from 2017-11-18 as integer');
//Day of the year starts with 1 on 1.1.
$result = Dt::create('2017-11-18')->dayOfYear;
$t->checkEqual($result, 322);

$t->start('get weekOfMonth from 2017-11-18 as integer');
$result = Dt::create('2017-11-18')->weekOfMonth;
$t->checkEqual($result, 3);

$t->start('get weekOfMonth from Sun 2016-10-02 as integer');
$result = Dt::create('2016-10-02')->weekOfMonth;
$t->checkEqual($result, 1);

$t->start('get weekOfMonth from Mon 2016-10-03 as integer');
$result = Dt::create('2016-10-03')->weekOfMonth;
$t->checkEqual($result, 2);

$t->start('get weekOfMonth from 2016-10-31 as integer');
$result = Dt::create('2016-10-31')->weekOfMonth;
$t->checkEqual($result, 6);

$t->start('get weekOfMonth from Mon 2019-12-30 as integer');
$result = Dt::create('2019-12-30')->weekOfMonth;
$t->checkEqual($result, 6);

$t->start('application example for weekOfMonth');
$strDate= '18.3.2017';
$dt = Dt::create($strDate);
$str = $strDate.' ist der '
  .$dt->weekOfMonth
  .$dt->formatL('\. l \i\m F Y','de') //names in german
;
$t->checkContains($str, '3.,Sam');

$t->start('create a relative Date-String with weekOfMonth');
$no = [1 => 'first','second','third','fourth','fifth'];
$strDate = '18.3.2017';
$dt = Dt::create($strDate);
$str = $no[$dt->weekOfMonth].$dt->format(' l \o\f F Y'); 
//'third Saturday of March 2017'
$t->check($str, date_create($str) == date_create($strDate));

$t->start('get weekOfYear from 2017-11-18 as integer');
// ISO-8601 week number of year, weeks starting on Monday
$result = Dt::create('2017-11-18')->weekOfYear;
$t->checkEqual($result, 46);

$t->start('get weekOf1900 from 1900-01-01 as integer');
$result = Dt::create('1900-01-01')->weekOf1900;
$t->checkEqual($result, 1);

$t->start('get weeks from 1900 to 2013-01-16 06:48 as integer');
$result = Dt::create('2013-01-16 06:48')->weekOf1900;
$t->checkEqual($result, 5899);

$t->start('get timezone name');
$result = Dt::create('now','America/New_York')->tzName;
$t->checkEqual($result, 'America/New_York');

$t->start('get timezone type');
$result = Dt::create('now','America/New_York')->tzType;
$t->checkEqual($result, 3);

//end properties

$t->start('get Date as JD Julian Date Number');
$result = Dt::create('2018-06-25 03:38:25 UTC')->toJD();
$t->checkEqual($result,2458294.6516782,"",1.E-7);

$t->start('get JD from old date');
$result = Dt::create('1695-11-25 12:00','UTC')->toJD();
$expect = (float)gregoriantojd ( 11 , 25 , 1695 );
$t->checkEqual($result,$expect);

$t->start('get JD from date in future');
$result = Dt::create('2081-02-23 12:00','UTC')->toJD();
$expect = (float)gregoriantojd ( 2 , 23 , 2081 );
$t->checkEqual($result,$expect);

$t->start('create dt from jdn UTC');
$result = Dt::createFromJD(2458294.6516782,"UTC");
$expect = Dt::create('2018-06-25 03:38:25',"UTC");
$t->check((string)$result,$result == $expect);

$t->start('create dt from jdn Europe/Berlin');
$result = Dt::createFromJD(2458294.6516782);
$expect = '2018-06-25 05:38:25';
$t->checkEqual((string)$result,$expect);

$t->start('average of 2 dates');
$result = Dt::create('2015-4-1')->average('2015-4-2');
$expect = '2015-04-01 12:00:00.000000';
$t->checkEqual($result->toStringWithMicro(), $expect);

$t->start('get as DateTime');
$dateTime1 = date_create('Now',new DateTimeZone('UTC'));
$dateTime = Dt::create($dateTime1)->getDateTime();
$checkOk = ($dateTime == $dateTime1 
  AND $dateTime->getTimeZone() == $dateTime1->getTimeZone()
  AND $dateTime instanceOf DateTime
  AND !$dateTime instanceOf dt
  );
$t->check($dateTime, $checkOk);

//cut
$t->start('cut to full 15 minutes');
$date = Dt::create('2013-12-18 03:55')->cut('15 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:45:00');

$t->start('cut to nearest 15 minutes');
$date = Dt::create('2013-12-18 03:55')->cut('15 Minutes', true);
$t->checkEqual((string)$date, '2013-12-18 04:00:00');

$t->start('cut Microseconds');
$date = Dt::create('2013-12-18 03:48:34.6')->cut();
$t->checkEqual($date->toStringWithMicro(), '2013-12-18 03:48:34.000000');

$t->start('cut to full 2 hours');
$date = Dt::create('2013-12-18 03:48')->cut('2 hours');
$t->checkEqual((string)$date, '2013-12-18 02:00:00');

$t->start('cut to full day');
$date = Dt::create('2013-12-18 03:48')->cut('1 day');
$t->checkEqual((string)$date, '2013-12-18 00:00:00');

$t->start('cut to full day');
$date = Dt::create('2013-12-18 03:48')->cut('1 day');
$t->checkEqual((string)$date, '2013-12-18 00:00:00');

$t->start('cut to full 2 days');
$date = Dt::create('2013-12-18 03:48')->cut('2 days');
$t->checkEqual((string)$date, '2013-12-17 00:00:00');

$t->start('cut to start of quarter');
$date = Dt::create('2013-12-18 03:48')->cut('3 month');
$t->checkEqual((string)$date, '2013-10-01 00:00:00');

$t->start('cut to start of current week');
$date = Dt::create('2013-12-18 03:48')->cut('1 week');
$t->checkEqual((string)$date, '2013-12-16 00:00:00');

$t->start('cut 2 weeks');
$date = Dt::create('2013-12-18 03:48');  
$date->cut('2 week'); 
$t->checkEqual((string)$date, "2013-12-16 00:00:00");

$t->start('cut 4 weeks');
$date = Dt::create('2013-12-18 03:48');  
$date->cut('4 week'); 
$t->checkEqual((string)$date, "2013-12-02 00:00:00");

$t->start('cut 24 month');
$date = Dt::create('2013-10-16 06:48'); 
$date->cut('24 month');
$t->checkEqual((string)$date, "2012-01-01 00:00:00"); 

$t->start('cut 2 years');
$date = Dt::create('2013-10-16 06:48'); 
$date->cut('2 years');
$t->checkEqual((string)$date, "2012-01-01 00:00:00"); 

//next
$t->start('next full 15 minutes');
$date = Dt::create('2013-12-18 03:55')->next('15 Minutes');
$t->checkEqual((string)$date, '2013-12-18 04:00:00');

$t->start('next full 15 minutes');
$date = Dt::create('2013-12-18 03:30:00')->next('15 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:45:00');

$t->start('next: Monday next week 00:00');
$date = Dt::create('2013-12-18 03:48')->next('week');
$t->checkEqual((string)$date, '2013-12-23 00:00:00');

//round
$t->start('round to nearest 10 minutes');
$date = Dt::create('2013-12-18 03:45:34')->round('10 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:50:00');

$t->start('round to nearest 10 minutes');
$date = Dt::create('2013-12-18 03:44:54')->round('10 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:40:00');

$t->start('round to nearest 30 minutes');
$date = Dt::create('2013-12-18 03:44:54')->round('30 Minutes');
$t->checkEqual((string)$date, '2013-12-18 03:30:00');

$t->start('round microseconds to nearest second');
$date = Dt::create('2013-12-18 03:44:54.66')->round();
$t->checkEqual($date->toStringWithMicro(), '2013-12-18 03:44:55.000000');

//getModulo
$t->start('getModulo: get Rest of Minutes after cut 5 Minutes');
$result = Dt::create('2013-12-18 03:47')->getModulo('5 Minutes');
$t->checkEqual((int)$result, 2);

$t->start('getModulo: get float Rest after cut 10 Minutes');
$result = Dt::create('2013-12-18 03:48:30')->getModulo('10 Minutes');
$t->checkEqual($result, 8.5);

$t->start('getModulo: get float Rest after cut 10 Minutes in Seconds');
$result = Dt::create('2013-12-18 03:48:30')->getModulo('10 Minutes',"seconds");
$t->checkEqual((int)$result, 8 * 60 + 30);

//min and max
$t->start('min: get smallest date');
$result = Dt::create('2013-12-18')->min('2013-12-17');
$t->checkEqual((string)$result, '2013-12-17 00:00:00');

$t->start('max: get biggest date');
$result = Dt::create('2013-12-18')->max('2013-12-17','2013-11-17');
$t->checkEqual((string)$result, '2013-12-18 00:00:00');

$t->start('max: modify date object');
$date = Dt::create('2013-12-18');
$date->max('2013-12-19','2013-11-17');
$t->checkEqual((string)$date, '2013-12-19 00:00:00');

$t->start('min: use relative date format basis now');
$result = Dt::create('2008-01-01')->max('+1 Day');
$expected = date_create('now +1 Day')->format('Y-m-d H:i:s');
$t->checkEqual((string)$result, $expected);

$t->start('min: use relative date format basis self');
$result = Dt::create('2008-01-01 12:13:14')->max('|+1 Day','|+25 Hours');
$expected = '2008-01-02 13:13:14';
$t->checkEqual((string)$result, $expected);

$t->setErrorLevel(0);//error reporting off for this test 
$t->start('min: bad Date -> exception');
$closure = function(){
  $result = Dt::create('2013-12-18')->min('2013.12.17');
};
$t->checkException($closure);
$t->restoreErrorLevel(); 

//diff
$t->start('diff: with DateTime');
$dateTo = new DateTime('2013-12-18 00:15:00');
$result = Dt::create('2013-12-18')->diff($dateTo)->i;  //Minutes
$t->checkEqual($result, 15);

$t->start('diff: with dt');
$dateTo = new dt('2013-12-18 00:16:00');
$result = Dt::create('2013-12-18')->diff($dateTo)->i;  //Minutes
$t->checkEqual($result, 16);

$t->start('diff: with String-Dates');
$days = Dt::create('2013-12-18')->diff('24.12.2013')->days;
$t->checkEqual($days, 6);

$t->start('diff: with timestamp');
$timeStamp = strtotime('24.12.2013');
$days = Dt::create('2013-12-18')->diff($timeStamp)->days;
$t->checkEqual($days, 6);

//diffTotal
$t->start('diffTotal: with String-Dates');
$days = Dt::create('2003-10-1 6:30')->diffTotal('2003-10-5 6:30',"days");
$t->checkEqual($days, (float)(4));

$t->start('diffTotal: 3,5 Days');
$days = Dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"days");
$t->checkEqual($days, 3.5);

$t->start('diffTotal: Hours');
$hours = Dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"hours");
$t->checkEqual($hours, (float)(84));

$t->start('diffTotal: Minutes');
$hours = Dt::create('2003-10-1 0:00')->diffTotal('2003-10-4 12:00',"Minutes");
$t->checkEqual($hours, (float)(84*60));

$t->start('diffTotal: full Years');
$years = Dt::create('1950-10-1 0:00')->diffTotal('2003-11-4 12:00',"Years");
$t->checkEqual($years, 53);

$t->start('diffTotal: full Month');
$month = Dt::create('1950-10-1 0:00')->diffTotal('2003-11-4 12:00',"Month");
$t->checkEqual($month, 53*12+1);

$t->start('diffTotal: full Month');
$month = Dt::create('2018-01-01')->diffTotal('2018-03-01',"Month");
$t->checkEqual($month, 2);

$t->start('diffTotal: full Month');
$month = Dt::create('2017-12-31')->diffTotal('2019-03-02',"Month");
$t->checkEqual($month, 14);

$t->start('diffTotal: full Month');
$month = Dt::create('2018-02-15 12:00:01')
  ->diffTotal('2018-03-15 12:00:00',"Month");
$t->checkEqual($month, 0);

$t->start('diffTotal: Seconds');
$seconds = Dt::create('1950-10-1 0:00')->diffTotal('2033-11-4 12:00');
$t->check($seconds, $seconds == 2622283200);

$t->start('diffTotal: Seconds and Microseconds');
$seconds = Dt::create('2016-06-03 15:30:45.2')
  ->diffTotal('2016-06-03 15:30:47.4');
$t->checkEqual($seconds, 2.2,"",1E-10); //Delta for Float

$t->start('diffTotal: Milliseconds');
$seconds = Dt::create('2016-06-03 15:30:45.2')
  ->diffTotal('2016-06-03 15:30:47.4','Millisecond');
$t->checkEqual($seconds, 2200.0); 

$t->start('diffTotal: Microseconds');
$seconds = Dt::create('2016-06-03 15:30:45.2')
  ->diffTotal('2016-06-03 15:30:47.4','Microseconds');
$t->checkEqual($seconds, 2200000.0); 

$t->start('diffTotal: change winter/summer time');
$hours = Dt::create('2014-03-30 00:00')
  ->diffTotal('2014-03-30 06:00','hour');
$expected = (version_compare(PHP_VERSION, '5.5.8') >= 0) ? 5.0 : 6.0;
$t->checkEqual($hours, $expected);

$t->start('diffTotal H:i hours and minutes, seconds round');
$hours = Dt::create('2019-12-06 00:00')
  ->diffTotal('2019-12-07 13:45:31','H:i');
$t->checkEqual($hours, "37:46");

$t->start('diffTotal H:i:s hours, minutes and seconds');
$hours = Dt::create('2019-12-06 00:00')
  ->diffTotal('2019-12-07 13:45:31','H:i:s');
$t->checkEqual($hours, "37:45:31");

$t->start('diffTotal H:i hours and minutes, seconds round');
$hours = Dt::create('2019-12-08 00:00')
  ->diffTotal('2019-12-07 13:45:31','H:i');
$t->checkEqual($hours, "-10:14");

//diffFormat
$t->start('diffFormat: total hours:minutes');
$strDuration = Dt::create("2018-03-04 08:00")->diffFormat("2018-03-06 16:15","%G:%I");
$t->checkEqual($strDuration, "56:15");

$t->start('diffFormat: min:sec.millisec');
//Error for PHP < 7.0
$strDuration = Dt::create("08:00:01.500000")->diffFormat("08:12:10.750000","%I:%S.%v");
$t->checkEqual($strDuration, "12:09.250");

$t->start('diffFormat full Weeks %w');
$weeks = Dt::create('2018-06-01')->diffFormat('2018-06-16','%w');  //15 Days
$t->checkEqual($weeks, "2");

//diffHuman
$t->start('diffHuman: de');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-01 00:05:20','de');
$t->checkEqual($result, '5 Minuten');

$t->start('diffHuman: de');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-01 00:05:20','de');
$t->checkEqual($result, '5 Minuten');

$t->start('diffHuman: de');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-02 02:05:20','de');
$t->checkEqual($result, '26 Stunden');

$t->start('diffHuman: de');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-03 02:05:20','de');
$t->checkEqual($result, '2 Tage');

$t->start('diffHuman: de 9 Tage');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-10 02:05:20','de');
$t->checkEqual($result, '9 Tage');

$t->start('diffHuman: de 2 Wochen');
$result = Dt::create('2017-01-01')->diffHuman('2017-01-15 02:05:20','de');
$t->checkEqual($result, '2 Wochen');

$t->start('diffHuman: de 6 Monate');
$result = Dt::create('2157-02-01')->diffHuman('2157-08-02 02:05:20','de');
$t->checkEqual($result, '6 Monate');

$t->start('diffHuman: default parameter now, de');
$result = Dt::create('-4 month -8 days')->diffHuman();
$t->checkEqual($result, '4 Monate');

$t->start('diffHuman: Age Years de');
//Age Albrecht Dürer * 21. Mai 1471 † 6. April 1528
$result = Dt::create('21. Mai 1471')->diffHuman('6. April 1528','de');
$t->checkEqual($result, '56 Jahre');

$t->start('diffHuman: Age Years en');
//Age Albrecht Dürer * 21. Mai 1471 † 6. April 1528
$result = Dt::create('21. Mai 1471')->diffHuman('6. April 1528','en');
$t->checkEqual($result, '56 Years');

$t->start('diffHuman: negative default-language');
$result = Dt::create('2017-01-07')->diffHuman('2017-01-01');
$t->checkEqual($result, '-6 Tage');

//diffUTC
$t->start('diffUTC: change winter/summer time');
$hours = Dt::create('2014-03-30 00:00')->diffUTC('2014-03-30 06:00','hour');
$t->checkEqual($hours, 5.0);

$t->start('addSeconds: add 2,5 Seconds');
$date = Dt::create("2015-03-31 12:00:01.5")->addSeconds(2.5);
$t->checkEqual((string)$date, "2015-03-31 12:00:04");

$t->start('addSeconds: sub 2,5 Seconds');
$date = Dt::create("2015-03-31 12:00:01.5")->addSeconds(-2.5);
$t->checkEqual((string)$date, "2015-03-31 11:59:59");

$t->start('addSeconds: convert Chrome Timestamp to DateTime');
//Google Chrome Epoche Timestamp: Microseconds since 1601-1-1
$ts = 13209562668824233;
$expected = "2019-08-06 10:57:48.824232";
$dateTime = Dt::create("1601-1-1 UTC")->addSeconds($ts/1000000);
$t->checkEqual($dateTime->toStringWithMicro(), $expected);

//add Days
$t->start('addDays: add 1 Monday');
$start = '2022-09-04 13:45'; //Sunday
$date = Dt::create($start)->addDays(1,'Mon');
$t->checkEqual($date->format('D, d.m.Y H:i'),'Mon, 05.09.2022 13:45');

$t->start('addDays: add 2 Mondays');
$start = '2022-09-04'; //Sunday
$date = Dt::create($start)->addDays(2,'Mon');
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 12.09.2022');

$t->start('addDays: add 2 Days, Monday and Friday');
$start = '2022-09-04'; //Sunday
$date = Dt::create($start)->addDays(2,'Mon,Fri');
$t->checkEqual($date->format('D, d.m.Y'),'Fri, 09.09.2022');

$t->start('addDays: add 3 Days, Monday and Friday');
$start = '2022-09-04'; //Sunday
$date = Dt::create($start)->addDays(3,'Mon,Fri');
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 12.09.2022');

$t->start('addDays: add 1 Monday');
$start = '2022-09-05'; //Monday
$date = Dt::create($start)->addDays(1,'Mon');
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 12.09.2022');

$t->start('addDays: add 30 Days, exclude Sunday');
$start = '2022-09-04'; //Sunday
$date = Dt::create($start)->addDays(30,'1,2,3,4,5,6'); //0=Sun
$t->checkEqual($date->format('D, d.m.Y'),'Sat, 08.10.2022');

$t->start('addDays: add 30 Days, exclude Sunday');
$start = '2022-09-05'; //Monday
$date = Dt::create($start)->addDays(30,'1,2,3,4,5,6');  //0=Sun
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 10.10.2022');

$t->start('addDays: from Sun next Mon,Tue,Thu or Fri');
$start = '2022-09-04'; //Sunday
$date = Dt::create($start)->addDays(1,'1,2,4,5');  
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 05.09.2022');

$t->start('addDays: from Mon next Mon,Tue,Thu or Fri');
$start = '2022-09-05'; //Monday
$date = Dt::create($start)->addDays(1,'1,2,4,5');  
$t->checkEqual($date->format('D, d.m.Y'),'Tue, 06.09.2022');

$t->start('addDays: from Tue next Mon,Tue,Thu or Fri');
$start = '2022-09-06'; //Tue
$date = Dt::create($start)->addDays(1,'1,2,4,5');  
$t->checkEqual($date->format('D, d.m.Y'),'Thu, 08.09.2022');

$t->start('add 0 Days return always startdate');
$start = '2022-09-06'; //Tue
$date = Dt::create($start)->addDays(0,'Sat');  
$t->checkEqual($date->format('D, d.m.Y'),'Tue, 06.09.2022');

$t->start('add Days with filter');
$start = '2020-12-31'; //Thu
//filter for not New Year's Day
$filter = function($dt){return !$dt->is('01-01');};
//Only Weekdays Mo-Fr and not 01-01 
$date = Dt::create($start)->addDays(1,'1,2,3,4,5',$filter); 
//2021-01-01 Fri, 2021-01-02 Sat, 2021-01-03 Sun
$t->checkEqual($date->format('D, d.m.Y'),'Mon, 04.01.2021');

$t->start('addDays: Throw Exeption for invalid argument type');
$closure = function(){
  $dt = Dt::create()->addDays(5,'Mon,Tus'); //invalid arg
};
$t->checkException($closure, "Exception");

//addTime
$t->start('addTime: add 01:05:30');
$date = Dt::create("2015-03-31 12:00")->addTime('01:05:30');
$t->checkEqual((string)$date, "2015-03-31 13:05:30");

$t->start('addTime: add 02:15');
$date = Dt::create("2015-03-31 12:00")->addTime('02:15');
$t->checkEqual((string)$date, "2015-03-31 14:15:00");

$t->start('addTime: 48:15');
$date = Dt::create("2015-02-05 12:00")->addTime('48:15');
$t->checkEqual((string)$date, "2015-02-07 12:15:00");

$t->start('addTime: -48:30');
$date = Dt::create("2015-02-05 01:00")->addTime('-48:30');
$t->checkEqual((string)$date, "2015-02-03 00:30:00");

$t->start('addTime: 48hours 15 minutes');
$date = Dt::create("2015-02-05 01:00")->addTime('48hours 15 minutes');
$t->checkEqual((string)$date, "2015-02-07 01:15:00");

$t->start('addTime: 1w2d1h3m15s');
$date = Dt::create("2015-02-05 01:00")->addTime('1w2d1h3m15s');
$t->checkEqual((string)$date, "2015-02-14 02:03:15");

$t->start('subTime: sub 02:30');
$date = Dt::create("2015-03-31 12:00")->subTime('02:30');
$t->checkEqual((string)$date, "2015-03-31 09:30:00");

$t->start('add Month and cut');
$date = Dt::create("31.01.2014")->addMonthCut(1);
$t->checkEqual((string)$date, "2014-02-28 00:00:00");

$t->start('add Month and cut');
$date = Dt::create("31.01.2014")->addMonthCut(3);
$t->checkEqual((string)$date, "2014-04-30 00:00:00");

//totalRelTime
$t->start('totalRelTime: 3 Days 5 Hours to hours');
$hours = Dt::totalRelTime("3 Days 5 Hours","h");
$t->checkEqual($hours, (float)(3*24 + 5));

$t->start('totalRelTime: 1 year to days, basis 1.1.2005');
$days = Dt::totalRelTime("1 year","days","2005-1-1");
$t->checkEqual($days, (float)(365));

$t->start('totalRelTime: 1 month to days, basis 1.2.2004');
$days = Dt::totalRelTime("1 month","days","Feb 2004");
$t->checkEqual($days, (float)(29));

$t->start('totalRelTime: 2 month 3 days short syntax to days, basis 1.1.2003');
$days = Dt::totalRelTime("2M3d","days","Jan 2003");
$expected = (float)(31 + 28 + 3); //no leap year
$t->checkEqual($days, $expected);

$t->start('totalRelTime: 1h 30min to hours');
$hours = Dt::totalRelTime("1h 30min","hours");
$t->checkEqual($hours, 1.5);

$t->start('totalRelTime: 2d13m30s to Seconds');
$seconds = Dt::totalRelTime("2d13m30s");
$t->checkEqual($seconds, (float)(2*24*60*60+13*60+30));

$t->start('totalrelTime: hours:minutes');
$minutes = Dt::totalRelTime("124:06",'minutes');
$t->checkEqual($minutes, (float)(124*60+6));

$t->start('totalrelTime: minutes:seconds,millisec');
$minutes = Dt::totalRelTime("01:65,25",'minutes');
$t->checkEqual($minutes, (60 + 65.25)/60);

$t->start('totalrelTime: hours:minutes:seconds.millisec');
$seconds = Dt::totalRelTime("124:06:01.56"); //default unit seconds
$t->checkEqual($seconds, 124*3600+6*60+1.56);

$t->start('totalrelTime: int seconds');
$minutes = Dt::totalRelTime(150,'min'); //minutes
$t->checkEqual($minutes, 2.5);  //2.5=150/60

$t->start('totalrelTime: float seconds');
$minutes = Dt::totalRelTime(120.3,'min'); //minutes
$t->checkEqual($minutes, 2.005);  //2.005=120.3/60

$t->start('totalRelTime: DateInterval-Object with Month to hours');
$di = new DateInterval('P4M3DT2H');  //4 month + 3 Days + 2 Hours
$hours = Dt::totalRelTime($di,'h',"2016-1-1");
//2016 Days jan-apr: 31+29+31+30
$expected = (31+29+31+30+3)*24 + 2.;
$t->checkEqual($hours, $expected);

$t->start('totalRelTime: DateInterval-Object to hours');
$i = new DateInterval('P1DT12H');  //1 day + 12 hours
$hours = Dt::totalRelTime($i,'h');
$t->checkEqual($hours, (float)(36));

/*
$t->start('totalrelTime: hours:minutes:seconds');
$seconds = Dt::totalRelTime("2 days 124:06:15");
$expected = 2*24*60*60 + 124*60*60 + 6*60 + 15;
$t->checkEqual($seconds, (float)$expected);
*/

//timeToSeconds
$t->start('timeToSeconds ');
$tests = array(
  "hhh:ii" => array("245:23" , 245. * 3600 + 23 * 60),
  "hhh:ii:ss" => array("245:23:15" , 245. * 3600 + 23 * 60 +15),
  "hhh:ii:ss.m" => array("234:45:34.6" , (234 * 60 + 45) * 60 + 34.6),
  "hhh:ii:ss,m" => array("234:45:34,6" , (234 * 60 + 45) * 60 + 34.6),
  "-hh:ii" => array("-00:30" , -30 * 60),
  "ss" => array("23" , 23.),
  "ss.m" => array("23.25" , 23.25),
  "hh:i" => array("34:8" , 34 * 3600 + 8. * 60),

  "Error no ms" => array("56." , false),
  "Error letter" => array("a34:54" , false),
  "Error format" => array("1:2:3:4" , false),
  "Error2 format" => array("3 hours" , false),
);
$t->checkMultiple(Dt::class.'::timeToSeconds',$tests);

$t->start('create a date_interval from float');
$dateInterval = Dt::date_interval_create_from_float(4.5,'min');
$t->checkEqual($dateInterval->format("%H:%I:%S"), '00:04:30');

$t->start('create a date_interval from float : idustrytime');
$dateInterval = Dt::date_interval_create_from_float(0.071525,'hour');
$t->checkEqual($dateInterval->format("%H:%I:%S"), '00:04:17');

$t->start('create a date_interval from float : seconds.mikrosekonds');
//Error for PHP < 7.1: no support for Microseconds and %F
$dateInterval = Dt::date_interval_create_from_float(-5.125,'seconds');
$t->checkEqual($dateInterval->format("%R%S.%F"), '-05.125000');

//Timestamps
$t->start('get a Float Timestamp 1.1.1970');
$timeStamp = Dt::create('1970-01-01','UTC')->getMicroTime();
$t->checkEqual($timeStamp, 0.0);

$t->start('get a Timestamp before 1970');
$timeStamp = Dt::create('1716-12-24','UTC')->getMicroTime();
$t->checkEqual($timeStamp, -7984569600.);

$t->start('get a Timestamp after 2038');
$timeStamp = Dt::create('2065-06-12')->getMicroTime();
$t->checkEqual($timeStamp, 3011986800.);

$t->start('get a Float-Timestamp with fractions of seconds');
$timeStamp = Dt::create('2006-06-12 13:45:56.25')->getMicroTime();
$t->checkEqual($timeStamp, strtotime('2006-06-12 13:45:56')+0.25);

$t->start('get a Microsoft-Timestamp with milliseconds');
$msTimestamp = Dt::create("1914-06-08 13:45:12.256","UTC")
  ->getMsTimestamp();
$eps = 1/(24*60*60*1000);  //1 ms
$t->checkEqual($msTimestamp, 5273.573058518562,"",$eps); 

$t->start('create Date from the Timestamp');
$date = Dt::create(3011986800);
$t->checkEqual((string)$date ,'2065-06-12 00:00:00');

$t->start('set Timestamp');
$date = Dt::create()->setTimestamp(3011986800);
$t->checkEqual((string)$date,'2065-06-12 00:00:00');

$t->start('set Float-Timestamp with Milliseconds');
$date = Dt::create()->setTimestamp(3011986800.12);
$t->checkEqual($date->formatL('Y-m-d H:i:s.u'),'2065-06-12 00:00:00.120000');

//easter
$t->start('create Easter-Date for year 1775');
$easterDate = Dt::Easter(1775);
$t->checkEqual((string)$easterDate, '1775-04-16 00:00:00');

$t->start('set date 2015 to easter');
$easterDate =  Dt::create("1.1.2015 12:15")->setEasterDate();
$t->checkEqual((string)$easterDate, '2015-04-05 00:00:00');

$t->start('set date 1526 to easter: bad Date -> exception');
$closure = function(){
  Dt::create("1526-1-1")->setEasterDate();
};
$t->checkException($closure);
  
$t->start('set date 1626 to easter');
$easterDate =  Dt::create("1626-1-1")->setEasterDate();
$t->checkEqual((string)$easterDate, '1626-04-12 00:00:00');

$t->start('set date 1626 to easter orthodox');
$easterDate =  Dt::create("1626-1-1")->setEasterDate(Dt::EASTER_EAST);
$t->checkEqual((string)$easterDate, '1626-04-19 00:00:00');

//sunset + sunrise
$t->start('sunrise at 24 sep 2020 Berlin');
$location = [52.520008, 13.404954]; //lat lon Berlin
$dt = Dt::create('24 sep 2020')->setSunrise($location);
$t->checkEqual((string)$dt, '2020-09-24 06:56:32');

$t->start('sunset at 24 sep 2020 Berlin');
$dt = Dt::create('24 sep 2020')->setSunset(52.520008, 13.404954);
$t->checkEqual((string)$dt, '2020-09-24 18:59:56');

$t->start('sunset at 24 sep 2020 Cologne');
$location = [50.937932, 6.953777];
$dt = Dt::create('24 sep 2020')->setSunset($location);
$t->checkEqual((string)$dt, '2020-09-24 19:25:43');

$t->start('sunset at 24 sep 2020, location from timezone');
$dt = Dt::create('24 sep 2020','Europe/Berlin')->setSunset();
$t->checkEqual((string)$dt, '2020-09-24 19:00:05');

$t->start('get date to first day of Passover 2018');
$passoverDate2018 = Dt::Passover(2018);
$t->checkEqual((string)$passoverDate2018, '2018-03-31 00:00:00');

$t->start('set date to first day of Passover 2019');
$passoverDate2019 = Dt::create("2019-1-1")->setPassoverDate();
$t->checkEqual((string)$passoverDate2019, '2019-04-20 00:00:00');

//calender convert
if(extension_loaded("Intl")) {

$t->start('convert Date from Indian Calendar');
$indianDate = "1942-07-03";
$dt = Dt::create($indianDate)->toGregorianFrom('Indian');
$t->checkEqual((string)$dt, '2020-09-25 00:00:00');

$t->start('convert Date from Persian(Jalaali) Calendar');
$date = "1398-12-29";
//farvardin = 1, ordibeheš = 2, .. esfand=12  
$dt = Dt::create($date)->toGregorianFrom('Persian');
$t->checkEqual((string)$dt, '2020-03-19 00:00:00');

$t->start('convert Date from Persian(Jalaali) Calendar');
$date = "1399-01-01";
//farvardin = 1, ordibeheš = 2, .. esfand=12  
$dt = Dt::create($date)->toGregorianFrom('Persian');
$t->checkEqual((string)$dt, '2020-03-20 00:00:00');

$t->start('convert Date from Ethiopic Calendar');
$date = "1965-09-18";  //May 26, 1973
$dt = Dt::create($date)->toGregorianFrom('Ethiopic');
$t->checkEqual((string)$dt, '1973-05-26 00:00:00');

} //IntlDateFormatter

//
$t->start('was in berlin in june 2011 summertime');
$isSummertime = Dt::create('Jun 2011','Europe/Berlin')->isSummertime();
$t->checkEqual($isSummertime, true);

$t->start('was in moscow in june 2011 summertime');
$isSummertime = Dt::create('Jun 2011','Europe/Moscow')->isSummertime();
$t->checkEqual($isSummertime, false);

$t->start('toCET (from Summertime)');
$result = Dt::create('2015-05-15 15:00','Europe/Berlin')
  ->toCET()
  ->format('Y-m-d H:i:s e')
;
$expect = '2015-05-15 14:00:00 CET';  //14h
$t->checkEqual($result, $expect);

$t->start('toCET (Wintertime)');
$result = Dt::create('2015-02-15 15:00','Europe/Berlin')
  ->toCET()
  ->format('Y-m-d H:i:s e')
;
$expect = '2015-02-15 15:00:00 CET';
$t->checkEqual($result, $expect);


/*
 * is-functions
 */

$t->start('is 2011 a leap year - no');
$isLeapYear = Dt::create('Jun 2011')->isLeapYear();
$t->checkEqual($isLeapYear, false);

$t->start('is 2012 a leap year - yes');
$isLeapYear = Dt::create('2012-1-1')->isLeapYear();
$t->checkEqual($isLeapYear, true);

$t->start('is at 1.May 2016 a weekend - yes');
$isWeekEnd = Dt::create('1 May 2016')->isWeekEnd();
$t->checkEqual($isWeekEnd, true);

$t->start('is at 1.May 2016 a weekday - no');
$isWeekDay = Dt::create('1 May 2016')->isWeekDay();
$t->checkEqual($isWeekDay, false);

$t->start('is today in current week - yes');
$isInWeek = Dt::create('today')->isInWeek();
$t->checkEqual($isInWeek, true);

$t->start('is 23.11.2015 in same week how 29.11.2015 - yes');
$isInWeek = Dt::create('23.11.2015')->isInWeek('29.11.2015');
$t->checkEqual($isInWeek, true);

$t->start('is 23.11.2015 in same week how 30.11.2015 - no');
$isInWeek = Dt::create('23.11.2015')->isInWeek('30.11.2015');
$t->checkEqual($isInWeek, false);

$t->start('is 23.11.2015 in same week how 22.11.2015 - no');
$isInWeek = Dt::create('23.11.2015')->isInWeek('22.11.2015');
$t->checkEqual($isInWeek, false);

$t->start("Determines if a date is in the future");
$result = Dt::create('+2 Second')->isFuture();
$t->checkEqual($result, true);

$t->start("Determines if a date is in the past");
$result = Dt::create('-2 Second')->isPast();
$t->checkEqual($result, true);

$t->start("Determines if a day of date is the last in month");
$result = Dt::create('2020-02-29')->isLastDayOfMonth();
$t->checkEqual($result, true);

$t->start("is('2019')");
$result = Dt::create('2019-06-02 12:23:45')->is('2019');
$t->checkEqual($result, true);

$t->start("is('2018')");
$result = Dt::create('2019-06-02 12:23:45')->is('2018');
$t->checkEqual($result, false);

$t->start("is('2018|2019') = is('2018) or is('2019')");
$result = Dt::create('2019-06-02 12:23:45')->is('2018|2019');
$t->checkEqual($result, true);

$t->start("is('308')");
$result = Dt::create('308-06-02 12:23:45')->is('308');
$t->checkEqual($result, true);

$t->start("is('2019-06')");
$result = Dt::create('2019-06-02 12:23:45')->is('2019-06');
$t->checkEqual($result, true);

$t->start("is('06-02')");
$result = Dt::create('2019-06-02 12:23:45')->is('06-02');
$t->checkEqual($result, true);

$t->start("is('08-02')");
$result = Dt::create('308-02-25 12:23:45')->is('08-02');
$t->checkEqual($result, false);

$t->start("is('2019-06-02')");
$result = Dt::create('2019-06-02 12:23:45')->is('2019-06-02');
$t->checkEqual($result, true);

$t->start("is('Sunday')");
$result = Dt::create('2019-06-02 12:23:45')->is('Sunday');
$t->checkEqual($result, true);

$t->start("is('sun')");
$result = Dt::create('2019-06-02 12:23:45')->is('sun');
$t->checkEqual($result, true);

$t->start("is('sat')");
$result = Dt::create('2019-06-02 12:23:45')->is('sat');
$t->checkEqual($result, false);

$t->start("is('day') - invalid argument");
$closure = function(){
  $result = Dt::create('2019-06-02 12:23:45')->is('day');
};
$t->checkException($closure);

$t->start("is('June')");
$result = Dt::create('2019-06-02 12:23:45')->is('June');
$t->checkEqual($result, true);

$t->start("is('jun')");
$result = Dt::create('2019-06-02 12:23:45')->is('jun');
$t->checkEqual($result, true);

$t->start("is('12h')");
$result = Dt::create('2019-06-02 12:23:45')->is('12h');
$t->checkEqual($result, true);

$t->start("is('23h')");
$result = Dt::create('2019-06-02 12:23:45')->is('23h');
$t->checkEqual($result, false);

$t->start("is('12:23')");
$result = Dt::create('2019-06-02 12:23:45')->is('12:23');
$t->checkEqual($result, true);

$t->start("is('23:45')");
$result = Dt::create('2019-06-02 12:23:45')->is('23:45');
$t->checkEqual($result, false);

$t->start("is('12:23:45')");
$result = Dt::create('2019-06-02 12:23:45')->is('12:23:45');
$t->checkEqual($result, true);

$t->start("is('12:23:45')");
$result = Dt::create('2019-06-02 12:23:45')->is('12:23:00');
$t->checkEqual($result, false);

$t->start("is('weekend')");
$result = Dt::create('2019-06-02 12:23:45')->is('weekend');
$t->checkEqual($result, true);

$t->start("is('today')");
$result = Dt::create('Now')->is('today');
$t->checkEqual($result, true);

$t->start("is('today') -> false");
$result = Dt::create('-24 hours')->is('today');
$t->checkEqual($result, false);

$t->start("is('yesterday')");
$result = Dt::create('-24 hours')->is('yesterday');
$t->checkEqual($result, true);

//isCurrent
$t->start('isCurrent("YmdHi") Minute');
$result = Dt::create('now')->iscurrent("YmdHi");
$t->checkEqual($result, true);

$t->start('isCurrent("YmdHi") Minute');
$result = Dt::create('now -2 minute')->iscurrent("YmdHi");
$t->checkEqual($result, false);

$t->start('isCurrent("m") - same month');
$dt = Dt::create('2000-{{m}}-01'); 
$result = $dt->iscurrent("m");
$t->checkEqual($result, true);

$t->start('isCurrent("Ym") - same year + month');
$dt = Dt::create('2000-{{m}}-01'); 
$result = $dt->iscurrent("Ym");
$t->checkEqual($result, false);

$t->start('isCurrent("Ymt") - last Day of Month');
$result = Dt::create('{{Y-m-t}}')->isCurrent('Ymt');
$t->checkEqual($result, true);

$t->start('isCurrent("Ymt") - last Day of Month');
$dt = Dt::create('{{Y-m}}-27'); //27 never last day of month
$result = Dt::create('{{Y-m}}-27')->isCurrent('Ymt');
$t->checkEqual($result, false);

//isTimeBetween
$t->start("isTimeBetween('08:00','22:00') for Now");
$dt = Dt::create('Now');
$result = $dt->isTimeBetween('08:00','22:00');
$expected = $dt >= date_create('08:00') && $dt < date_create('22:00');
$t->checkEqual($result, $expected);

$t->start("isTimeBetween('08:00','12:00') for 2018-05-04 09:13");
$result = Dt::create('2018-05-04 09:13')->isTimeBetween('08:00','12:00');
$t->checkEqual($result, true);

$t->start("isTimeBetween('08:00','12:00') for 2018-05-04 19:13:00");
$result = Dt::create('2018-05-04 19:13:00')->isTimeBetween('08:00:00','12:00:00');
$t->checkEqual($result, false);

$t->start("isTimeBetween('22:00','06:00') for 2018-05-04 19:13:00");
//22:00-24:00 or 00:00-06:00
$result = Dt::create('2018-05-04 19:13:00')->isTimeBetween('22:00','06:00');
$t->checkEqual($result, false);

$t->start("isTimeBetween('22:00','06:00') for 2018-05-04 23:13:00");
//22:00-24:00 or 00:00-06:00
$result = Dt::create('2018-05-04 23:13:00')->isTimeBetween('22:00','06:00');
$t->checkEqual($result, true);

$t->start("isTimeBetween('22:00','06:00') for 2018-05-04 05:13:00");
//22:00-24:00 or 00:00-06:00
$result = Dt::create('2018-05-04 05:13:00')->isTimeBetween('22:00','06:00');
$t->checkEqual($result, true);

$t->start("exception parameter wrong");
$closure = function() {
  $result = Dt::create('2018-05-04 19:13:00')->isTimeBetween('08','12');
};
$t->checkException($closure);

//isCron
$t->start('isCron 00:01: ok');
$cronStr = "1 0 * * *";  //every Day 00:01 
$result = Dt::create('2015-5-2 00:01:05')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('isCron 5 seconds after midnight: false');
$cronStr = "1 0 * * *";  //every Day 5 seconds after midnight 
$result = Dt::create('2015-5-2 00:02:00')->isCron($cronStr);
$t->checkEqual($result, false);

$t->start('isCron mo-fr 01:20, 1:30: ok');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = Dt::create('2017-7-27 01:20:00')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('isCron mo-fr 01:20, 1:30: false');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = Dt::create('2017-7-27 01:21:00')->isCron($cronStr);
$t->checkEqual($result, false);

$t->start('isCron mo-fr 01:20, 1:30: ok');
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30
$result = Dt::create('2017-7-27 01:30:00')->isCron($cronStr);
$t->checkEqual($result, true);

$t->start('nextCron Mi 31.5.2017 : Do 1.6 01:05 : ok');
$cronStr = "5 1 * * *";  //every Day 1:05
$result = Dt::create('2017-5-31 01:06:00')->nextCron($cronStr);
$t->checkEqual((string)$result, '2017-06-01 01:05:00');

$t->start(' Sa. 29.7.2017 nextCron Mo. 31.7.2017 01:05');
$cronStr = "*/30 1 * * 1-5";  //1:00, 1:30 every weekday
$result = Dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
$t->checkEqual((string)$result, '2017-07-31 01:00:00');

$t->start('nextCron throw Exception for cron"* * * * 7"');
$cronStr = "* * * * 7";  //invalid cron
$closure = function() use($cronStr) {
  $result = Dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
};
$t->checkException($closure);

$t->start('nextCron throw Exception for cron"60 * * * *"');
$cronStr = "60 * * * *";  //invalid cron
$closure = function() use($cronStr) {
  $result = Dt::create('2017-7-29 01:06:00')->nextCron($cronStr);
};
$t->checkException($closure);

$t->start('previousCron Do 1.6.2017 01:05 : ok');
$cronStr = "5 1 * * *";  //every Day 1:05
$result = Dt::create('2017-06-01 01:05:00')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-05-31 01:05:00');

$t->start('previousCron Do 1.6.2017 01:35 : 1:20');
$cronStr = "*/20 * * * *";  //every 20 Minutes
$result = Dt::create('2017-06-01 01:35:17')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-06-01 01:20:00');

$t->start('previousCron 1.6.2017 01:05 : 1:20');
$cronStr = "10 1 1 * *";  //every month 1:10
$result = Dt::create('2017-06-01 01:05:08')->previousCron($cronStr);
$t->checkEqual((string)$result, '2017-05-01 01:10:00');

$t->start('1.6.2017 01:05 toCron');
$result = Dt::create('2017-06-01 01:05:08')->toCron();
$t->checkEqual((string)$result, '5 1 1 6 *');

//chain
$t->start('chain: german "Buß und Bettag" 2015');
$date = Dt::create('2015-1-1')->chain('11/23|last Wed');
$t->checkEqual((string)$date, '2015-11-18 00:00:00');

$t->start('chain: german "Buß und Bettag" 2015 alternative spelling');
$date = Dt::create('2015-1-1')->chain('Nov 23 and last Wed');
$t->checkEqual((string)$date, '2015-11-18 00:00:00');

$t->start('chain: easter Monday 2016');
$date = Dt::create('2016-1-1')->chain('{{easter}}|+1 Day');
$t->checkEqual((string)$date, '2016-03-28 00:00:00');

$t->start('Jom Kippur with chain and passover wildcard');
$date = Dt::create('2018-1-1')->chain("{{passover}}|+172 Days");
$t->checkEqual((string)$date, '2018-09-19 00:00:00');

$t->start('chain: 1. advent this Year');
$date = Dt::create('today')->chain('12/25|last Sunday|-3 weeks');
$expect = Dt::create('26.11')->modify('next sunday');
$t->checkEqual((string)$date, (string)$expect);

$t->start('chain: 4. advent 2017');
$date = Dt::create('2017-1-1')->chain('12/25|last Sunday');
$t->checkEqual((string)$date, '2017-12-24 00:00:00');

$t->start('chain: first Day of current month 00:00');
$date = Dt::create()->chain('first Day of this Month | 00:00');
$t->checkEqual((string)$date, date('Y-m-01 00:00:00'));

$t->start('chain: Spring Bank Holiday 2016 (United Kingdom)');
$date = Dt::create('2016-1-1')->chain('Last monday of May {{year}}');
$t->checkEqual((string)$date, '2016-05-30 00:00:00');

$t->start('chain with Year and Week, time is preserved');
$date = Dt::create('2010-06-09 14:45')->chain('2015W50');
$t->checkEqual((string)$date, '2015-12-07 14:45:00');

$t->start('chain with Year and Week, time is set to 00:00');
$date = Dt::create('2010-06-09 14:45')->chain('2015W50|00:00');
$t->checkEqual((string)$date, '2015-12-07 00:00:00');

$t->start('chain with userparameter');
$para = array('{{theYear}}' => 2018);
$date = Dt::create('2000-1-1')->chain('{{theYear}}-12-24 00:00',$para);
$t->checkEqual((string)$date, '2018-12-24 00:00:00');

$t->start('chain with userfunction time 11:59');
$para = array('{{after12fct}}' => function($dateTime){
    return $dateTime->format("H") >= 12 ? "next Day 00:00" : "00:00";
  });
$date = Dt::create('2018-1-1 11:59')->chain('{{after12fct}}',$para);
$t->checkEqual((string)$date, '2018-01-01 00:00:00');

$t->start('chain with userfunction time 12:00');
$para = array('{{after12fct}}' => function($dateTime){
    return $dateTime->format("H") >= 12 ? "next Day 00:00" : "00:00";
  });
$date = Dt::create('2018-1-1 12:00')->chain('{{after12fct}}',$para);
$t->checkEqual((string)$date, '2018-01-02 00:00:00');

$t->start('chain with condition, add 2 days, if result is Wednesday deliver the Thursday');
$date = Dt::create('2018-09-24'); //Monday
$date->chain('+2 Days|{{?D=Wed}}next Day');
$t->checkEqual($date->format("l, Y-m-d"), 'Thursday, 2018-09-27');

$t->start('chain with condition, add 2 days, if result is not Monday');
$date = Dt::create('2018-09-24'); //Monday
$date->chain('{{?D!=Mon}}next Day');
$t->checkEqual($date->format("l, Y-m-d"), 'Monday, 2018-09-24');

$t->start('chain with condition, next Day if Time > 12:01:01');
$date = Dt::create('2018-09-24 12:01:02'); 
$date->chain('{{?His>120101}}next Day 00:00');
$t->checkEqual((string)$date, '2018-09-25 00:00:00');

$t->start('chain with condition, next Day if Time > 12:01:01');
$date = Dt::create('2018-09-24 12:01:01'); 
$date->chain('{{?His>120101}}next Day 00:00');
$t->checkEqual((string)$date, '2018-09-24 12:01:01');

$t->start('chain with condition, if Time < 12:00:00 set time to 12:00');
$date = Dt::create('2018-09-24 11:59:02'); 
$date->chain('{{?H<12}}12:00');
$t->checkEqual((string)$date, '2018-09-24 12:00:00');

$t->start('chain with condition, if Time < 12:00:00 set time to 12:00');
$date = Dt::create('2018-09-24 12:23:45'); 
$date->chain('{{?H<12}}12:00');
$t->checkEqual((string)$date, '2018-09-24 12:23:45');

$t->start('If the date is in the past, it is set to the current time +1 hour');
$date = Dt::create('2000-01-01'); //is in the past
$date->chain('{{?YmdHis<NOW}} {{#Y-m-d H:i}} +1 Hour');
$expected = date_create('now')->format('Y-m-d H:i');
$expected = date_create($expected)->modify('+ 1 Hour')->format('Y-m-d H:i:s');
$t->checkEqual((string)$date, $expected);

$t->start('exclude week 18-2018 from 30.Apr-6.May 2018');
$date = Dt::create('2018-05-02')
  ->chain('{{?YW=201818}}next Monday')
;
$expected = "2018-05-07 00:00:00"; 
$t->checkEqual((string)$date, $expected);

$t->start('chain with cron-tab-string');
$date = Dt::create('2018-09-24 12:23:45'); 
$date->chain('5 8 10 * *');  //Next day 10, hour 8, minute 5
$t->checkEqual((string)$date, '2018-10-10 08:05:00');

$t->start('create with chain and cron-tab');
//first day of month is a sunday after 2019-1-1
$date = Dt::create('2019-01-01|0 0 1 * 0');
$t->checkEqual($date->format('l, d-m-Y'), 'Sunday, 01-09-2019');

$t->start('chain condition = NOW true');
$dt = Dt::create('today 12:00')
  ->chain('{{?Ymd=NOW}} +1 hour');
$expected = Dt::create('today 13:00'); 
$t->checkEqual((string)$dt, (string)$expected);

$t->start('chain condition = NOW false');
$dt = Dt::create('tomorrow 12:00')
  ->chain('{{?Ymd=NOW}} +1 hour');
$expected = Dt::create('tomorrow 12:00'); 
$t->checkEqual((string)$dt, (string)$expected); 

$t->start('chain condition > NOW true');
$dt = Dt::create('tomorrow 12:00')
  ->chain('{{?Ymd>NOW}} +1 hour');
$expected = Dt::create('tomorrow 13:00'); 
$t->checkEqual((string)$dt, (string)$expected);

$t->start('chain condition < NOW true');
$dt = Dt::create('yesterday 12:00')
  ->chain('{{?Ymd<NOW}} {{#Y-m-d H:i:s}}');
$expected = Dt::create('now'); 
$t->checkEqual((string)$dt, (string)$expected);

$t->start('chain condition < NOW true');
$dt = Dt::create('yesterday 12:00')
  ->chain('{{?Ymd<NOW}} {{#Y-m-d}}');
$expected = Dt::create('today 12:00'); 
$t->checkEqual((string)$dt, (string)$expected); 

//

$t->start('copy: new Instance');
$date = Dt::create('2016-1-1');
//alternativ to $cloneDate = clone $date
$cloneDate = $date->copy();
$checkOk = ($date == $cloneDate) AND ($date !== $cloneDate);
$t->check((string)$cloneDate, $checkOk);

$t->start('copy and modify');
$date = Dt::create('2016-01-01');
//not modify $date !
$nextDay = $date->copy()->modify('next Day');
$checkOk = ($date == Dt::create('2016-01-01')) 
  AND ($nextDay == Dt::create('2016-01-02'));
$t->check((string)$nextDay, $checkOk);

$t->start('Date Clock Change to Summertime 2016');
$date = Dt::getClockChange(2016, false, "Europe/Berlin");
$t->checkEqual((string)$date, "2016-03-27 03:00:00");

$t->start('Date Clock Change to Wintertime 2016');
$date = Dt::getClockChange(2016, true, "Europe/Berlin");
$t->checkEqual((string)$date, "2016-10-30 02:00:00");

$t->start('Date Clock Change to Summertime USA 2017');
$date = Dt::getClockChange(2017, false, "America/New_York");
$t->checkEqual((string)$date, "2017-03-12 03:00:00");

$t->start('Date Clock Change to Summertime Moscow 2017');
$date = Dt::getClockChange(2016, false, "Europe/Moscow");
$t->checkEqual($date, null);

$t->start('setClockChange to Wintertime USA 2017');
$date = Dt::create('2017-1-1', "America/New_York")
  ->setClockChange(true);
$t->checkEqual((string)$date, "2017-11-05 01:00:00");

//formatDateInterval($format, DateInterval $dateInterval)
$t->start('formatDateInterval HH:ii');
$dateInterval = DateInterval::createFromDateString('3 Days 4 Hours 6 Minutes');
$result = Dt::formatDateInterval('%G:%I', $dateInterval);
$t->checkEqual($result, "76:06");

$t->start('formatDateInterval -06,125');
//Error for PHP < 7.1: no support for Microseconds
$dateInterval = date_create('2000-01-01 00:00:06.125000')
  ->diff(date_create('2000-1-1'));
$result = Dt::formatDateInterval('%R%S,%v', $dateInterval);
$t->checkEqual($result, "-06,125");

/*
 * End Tests 
 */

//output as table
echo $t->gethtml();


