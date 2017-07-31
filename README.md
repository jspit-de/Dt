# dt (beta)

PHP extension for DateTime.
### Features
- Create objects from strings, float/int timestamps or object
- German and English for input and output
- Supports microseconds

### Usage

```php
require '/yourpath/class.dt.php';

echo "now is ".dt::create();  
//now is 2017-07-13 17:41:41

echo "now in New York ".dt::create('Now','America/New_York');
//now in New York 2017-07-13 11:41:41

dt::setDefaultLanguage('de');  //German
$dateOfBirth = dt::create('16.3.1975');
echo 'Ich bin an einem '.$dateOfBirth->formatL('l')
  .' im '.$dateOfBirth->formatL('F').' geboren.';
//Ich bin an einem Sonntag im März geboren.

//diffTotal: units Week, Day, Hour, Minute, Second, Year, Month
$myAge = $dateOfBirth->diffTotal('today','Years');

//German Input
$Date =  dt::create("1.Mai 17");  //2017-05-01 00:00:00

//create dt from float timestamp : 2017-07-31 13:47:02.250000
echo dt::create(1501501622.25)->toStringWithMicro();

//cut a interval
$date15min = dt::create('2013-12-18 03:55:07')->cut('15 Minutes'); //2013-12-18 03:45:00
$date2h = dt::create('2013-12-18 03:55:07')->cut('2 hours'); //2013-12-18 02:00:00

//check if 3.10.2017 is a german public holiday
$isHoliday = dt::create('03 Oct 2016')->isPublicHoliday(); //true

//When will Easter be celebrated in Greece in 2018?
$easterDate =  dt::create("2018-1-1")->setEasterDate(dt::EASTER_EAST);  //2018-04-08

//modify with chain  1.Advent 2017: 2017-12-03
$firstAdvent = dt::create('2017-1-1')->chain('12/25|last Sunday|-3 weeks');
 
//cron
$cronStr = "20,30 1 * * 1-5";  //mo-fr 01:20, 1:30 
$dateTime = dt::create('2017-7-27 01:30:00'); 
$cronStart = $dateTime->isCron($cronStr);  //true 
$nextStart = $dateTime->nextCron($cronStr);  //2017-07-28 01:20:00
```

### Requirements

- PHP 5.3.8+
- Class phpcheck.php is required to run phpcheck.class.dt.php
