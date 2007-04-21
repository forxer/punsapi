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


# Réglé ici les format de date et d'heure par défaut
$GLOBALS['locales_dates']['default_date_format'] = '%A %e %B %Y';
$GLOBALS['locales_dates']['default_time_format'] = '%Hh%M';

/*  Les caractères suivants sont utilisés pour spécifier le format de la date :

	%a - nom abrégé du jour de la semaine
	%A - nom complet du jour de la semaine
	%b - nom abrégé du mois
	%B - nom complet du mois
	%c - représentation préférée pour les dates et heures, en local
	%C - numéro de siècle (l'année, divisée par 100 et arrondie entre 00 et 99)
	%d - jour du mois en numérique (intervalle 01 à 31)
	%D - identique à %m/%d/%y
	%e - numéro du jour du mois. Les chiffres sont précédés d'un espace (de '1' à '31')
	%g - identique à %G, sur 2 chiffres
	%G - L'année sur 4 chiffres correspondant au numéro de semaine (voir %V). Même format et valeur que %Y, excepté que si le numéro de la semaine appartient à l'année précédente ou suivante, l'année courante sera utilisé à la place
	%h - identique à %b
	%H - heure de la journée en numérique, et sur 24-heures (intervalle de 00 à 23)
	%I - heure de la journée en numérique, et sur 12-heures (intervalle 01 à 12)
	%j - jour de l'année, en numérique (intervalle 001 à 366)
	%m - mois en numérique (intervalle 1 à 12)
	%M - minute en numérique
	%n - caractère de nouvelle ligne
	%p - soit `am' ou `pm' en fonction de l'heure absolue, ou en fonction des valeurs enregistrées en local
	%r - l'heure au format a.m. et p.m
	%R - l'heure au format 24h
	%S - secondes en numérique
	%t - tabulation
	%T - l'heure actuelle (égal à %H:%M:%S)
	%u - le numéro de jour dans la semaine, de 1 à 7. (1 représente Lundi)
	%U - numéro de semaine dans l'année, en considérant le premier dimanche de l'année comme le premier jour de la première semaine
	%V - le numéro de semaine comme défini dans l'ISO 8601:1988, sous forme décimale, de 01 à 53. La semaine 1 est la première semaine qui a plus de 4 jours dans l'année courante, et dont Lundi est le premier jour. (Utilisez %G ou %g pour les éléments de l'année qui correspondent au numéro de la semaine pour le timestamp donné.)
	%W - numéro de semaine dans l'année, en considérant le premier lundi de l'année comme le premier jour de la première semaine
	%w - jour de la semaine, numérique, avec Dimanche = 0
	%x - format préféré de représentation de la date sans l'heure
	%X - format préféré de représentation de l'heure sans la date
	%y - l'année, numérique, sur deux chiffres (de 00 à 99)
	%Y - l'année, numérique, sur quatre chiffres
	%Z ou %z - fuseau horaire, ou nom ou abréviation
	%% - un caractère `%' littéral
*/


# Ici les chaines pour les dates localisées
$GLOBALS['locales_dates']['Jan'] = 'jan';
$GLOBALS['locales_dates']['Feb'] = 'fév';
$GLOBALS['locales_dates']['Mar'] = 'mar';
$GLOBALS['locales_dates']['Apr'] = 'avr';
$GLOBALS['locales_dates']['May'] = 'mai';
$GLOBALS['locales_dates']['Jun'] = 'juin';
$GLOBALS['locales_dates']['Jul'] = 'juil';
$GLOBALS['locales_dates']['Aug'] = 'aoû';
$GLOBALS['locales_dates']['Sep'] = 'sep';
$GLOBALS['locales_dates']['Oct'] = 'oct';
$GLOBALS['locales_dates']['Nov'] = 'nov';
$GLOBALS['locales_dates']['Dec'] = 'dec';
$GLOBALS['locales_dates']['January'] = 'janvier';
$GLOBALS['locales_dates']['February'] = 'février';
$GLOBALS['locales_dates']['March'] = 'mars';
$GLOBALS['locales_dates']['April'] = 'avril';
$GLOBALS['locales_dates']['June'] = 'juin';
$GLOBALS['locales_dates']['July'] = 'juillet';
$GLOBALS['locales_dates']['August'] = 'août';
$GLOBALS['locales_dates']['September'] = 'septembre';
$GLOBALS['locales_dates']['October'] = 'octobre';
$GLOBALS['locales_dates']['November'] = 'novembre';
$GLOBALS['locales_dates']['December'] = 'décembre';
$GLOBALS['locales_dates']['Mon'] = 'lun';
$GLOBALS['locales_dates']['Tue'] = 'mar';
$GLOBALS['locales_dates']['Wed'] = 'mer';
$GLOBALS['locales_dates']['Thu'] = 'jeu';
$GLOBALS['locales_dates']['Fri'] = 'ven';
$GLOBALS['locales_dates']['Sat'] = 'sam';
$GLOBALS['locales_dates']['Sun'] = 'dim';
$GLOBALS['locales_dates']['Monday'] = 'lundi';
$GLOBALS['locales_dates']['Tuesday'] = 'mardi';
$GLOBALS['locales_dates']['Wednesday'] = 'mercredi';
$GLOBALS['locales_dates']['Thursday'] = 'jeudi';
$GLOBALS['locales_dates']['Friday'] = 'vendredi';
$GLOBALS['locales_dates']['Saturday'] = 'samedi';
$GLOBALS['locales_dates']['Sunday'] = 'dimanche';

?>