<?php
/***********************************************************************

  This file is part of PunBB Simple API (PunSAPI).

  PunSAPI is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunSAPI is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/

# Set here the default date and time format
$GLOBALS['locales_dates']['default_date_format'] = '%A, %B %e %Y';
$GLOBALS['locales_dates']['default_time_format'] = '%H:%M';

/* The following conversion specifiers are recognized in the format string:

	%a - abbreviated weekday name according to the current locale
	%A - full weekday name according to the current locale
	%b - abbreviated month name according to the current locale
	%B - full month name according to the current locale
	%c - preferred date and time representation for the current locale
	%C - century number (the year divided by 100 and truncated to an integer, range 00 to 99)
	%d - day of the month as a decimal number (range 01 to 31)
	%D - same as %m/%d/%y
	%e - day of the month as a decimal number, a single digit is preceded by a space (range ' 1' to '31')
	%g - like %G, but without the century.
	%G - The 4-digit year corresponding to the ISO week number (see %V). This has the same format and value as %Y, except that if the ISO week number belongs to the previous or next year, that year is used instead.
	%h - same as %b
	%H - hour as a decimal number using a 24-hour clock (range 00 to 23)
	%I - hour as a decimal number using a 12-hour clock (range 01 to 12)
	%j - day of the year as a decimal number (range 001 to 366)
	%m - month as a decimal number (range 01 to 12)
	%M - minute as a decimal number
	%n - newline character
	%p - either `am' or `pm' according to the given time value, or the corresponding strings for the current locale
	%r - time in a.m. and p.m. notation
	%R - time in 24 hour notation
	%S - second as a decimal number
	%t - tab character
	%T - current time, equal to %H:%M:%S
	%u - weekday as a decimal number [1,7], with 1 representing Monday
	%U - week number of the current year as a decimal number, starting with the first Sunday as the first day of the first week
	%V - The ISO 8601:1988 week number of the current year as a decimal number, range 01 to 53, where week 1 is the first week that has at least 4 days in the current year, and with Monday as the first day of the week. (Use %G or %g for the year component that corresponds to the week number for the specified timestamp.)
	%W - week number of the current year as a decimal number, starting with the first Monday as the first day of the first week
	%w - day of the week as a decimal, Sunday being 0
	%x - preferred date representation for the current locale without the time
	%X - preferred time representation for the current locale without the date
	%y - year as a decimal number without a century (range 00 to 99)
	%Y - year as a decimal number including the century
	%Z or %z - time zone or name or abbreviation
	%% - a literal `%' character 
*/


# here the localized strings for the dates
$GLOBALS['locales_dates']['Jan'] = 'Jan';
$GLOBALS['locales_dates']['Feb'] = 'Feb';
$GLOBALS['locales_dates']['Mar'] = 'Mar';
$GLOBALS['locales_dates']['Apr'] = 'Apr';
$GLOBALS['locales_dates']['May'] = 'May';
$GLOBALS['locales_dates']['Jun'] = 'Jun';
$GLOBALS['locales_dates']['Jul'] = 'Jul';
$GLOBALS['locales_dates']['Aug'] = 'Aug';
$GLOBALS['locales_dates']['Sep'] = 'Sep';
$GLOBALS['locales_dates']['Oct'] = 'Oct';
$GLOBALS['locales_dates']['Nov'] = 'Nov';
$GLOBALS['locales_dates']['Dec'] = 'Dec';
$GLOBALS['locales_dates']['January'] = 'January';
$GLOBALS['locales_dates']['February'] = 'February';
$GLOBALS['locales_dates']['March'] = 'March';
$GLOBALS['locales_dates']['April'] = 'April';
$GLOBALS['locales_dates']['June'] = 'June';
$GLOBALS['locales_dates']['July'] = 'July';
$GLOBALS['locales_dates']['August'] = 'August';
$GLOBALS['locales_dates']['September'] = 'September';
$GLOBALS['locales_dates']['October'] = 'October';
$GLOBALS['locales_dates']['November'] = 'November';
$GLOBALS['locales_dates']['December'] = 'December';
$GLOBALS['locales_dates']['Mon'] = 'Mon';
$GLOBALS['locales_dates']['Tue'] = 'Tue';
$GLOBALS['locales_dates']['Wed'] = 'Wed';
$GLOBALS['locales_dates']['Thu'] = 'Thu';
$GLOBALS['locales_dates']['Fri'] = 'Fri';
$GLOBALS['locales_dates']['Sat'] = 'Sat';
$GLOBALS['locales_dates']['Sun'] = 'Sun';
$GLOBALS['locales_dates']['Monday'] = 'Monday';
$GLOBALS['locales_dates']['Tuesday'] = 'Tuesday';
$GLOBALS['locales_dates']['Wednesday'] = 'Wednesday';
$GLOBALS['locales_dates']['Thursday'] = 'Thursday';
$GLOBALS['locales_dates']['Friday'] = 'Friday';
$GLOBALS['locales_dates']['Saturday'] = 'Saturday';
$GLOBALS['locales_dates']['Sunday'] = 'Sunday';

?>