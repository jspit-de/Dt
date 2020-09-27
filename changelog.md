# DateTime Extension dt

## Version 1.89 (2020-09-27)
* Added method setSunrise
* Added method setSunset
* Redesign method createFromRegExFormat (now accepts an array of formats)
* Redesign method createDtFromFormat (now accepts an array of formats)

## Version 1.88 (2020-04-09)
* Redesign method diff

## Version 1.87 (2020-03-31)
* Method toGregorianFrom added
* Method setDateTimeFrom added
* Redesign method setYear

## Version 1.86 (2020-03-22)
* Methods min and max added

## Version 1.85 (2020-02-17)
* Redesign method translateMonth
* Method createDtFromFormat now support languages
* Added getTranslateArray() method for testing and development


## Version 1.83 (2020-01-30)
* Added method timeToSeconds
  convert time hh:ii:ss.millisec to seconds (float)
* totalRelTime and addTime works now also with milliseconds

## Version 1.81 (2020-01-20)
* Added method createFromSystemTime
  dt-objects from arbitrary timestamps

## Version 1.78 (2019-12-11)
* method "totalRelTime" accept now hours>24 in the HH::ii formats
* method "totalRelTime" extends for time formats HH:ii

## Version 1.77 (2019-12-09)
* the replacement of wildcards extended

## Version 1.76 (2019-11-30)
* Added methods isPast(), isFuture()

## Version 1.75 (2019-11-18)
* added human units for the French language

## Version 1.74 (2019-10-07)
* modify Method countDaysTo, accept now also a list of weekdays

## Version 1.73 (2019-09-25)
* Add Method createFromIntlFormat
* Add Method timeStampFromIntlFormat
* Add Method formatIntl
* Add Method countDaysTo

## Version 1.7 (2019-07-17)
* Handle cron-tab-strings in chains

## Version 1.67 (2019-07-09)
* Create also from int arguments year, month, day..
* Add Method __sleep()

## Version 1.66 (2019-07-04)
* Create also from DateTimeImmutable

## Version 1.65 (2019-02-07)
* Add Method getMsTimestamp : get a Microsoft Timestamp (days since Dec 31 1899)
* modify Method diffTotal (use now dateInterval->f)

## Version 1.64 (2019-02-06)
* Add Method createFromMsTimestamp : creates from Microsoft Timestamp

## Version 1.6 (2019-01-16)
* formatL Method has been extended. Use IntlDateFormatter if available.

## Version 1.5.2 (2018-09-25)
* modify chain, conditions accept !=, < and >
* modify chain, array of user parameters accept closures 

## Version 1.5 (2018-08-03)
* modify chain, add conditions with =
* remove isPublicHoliday, setHolidayList (take class holiday)
* remove loadLocales

## Version 1.4.30 (2018-07-12) 
* Add Method getDayMinutes : get Number of Minutes since Midnight
* Add Method getModulo 

## Version 1.4.28 (2018-07-12) 
* Add Method toJD : get the Julian Date Number as Float 
* Add Method createFromJD : creates from a Julian Date Number a dt object

## Version 1.4.26 (2018-04-17)
* Add Method passover
* Add Method setPassoverDate 

## Version 1.4.25 (2018-01-15)
* Add Method round
* Add Method diffHuman

## Version 1.4.22 (2017-11-20)
* Remove Bug for PHP5.3

## Version 1.4.21 (2017-11-17)
* Add Method previousCron

## Version 1.4.14 (2017-07-31)
first Version 

