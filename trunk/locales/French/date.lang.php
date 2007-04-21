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


# R�gl� ici les format de date et d'heure par d�faut
$GLOBALS['locales_dates']['default_date_format'] = '%A %e %B %Y';
$GLOBALS['locales_dates']['default_time_format'] = '%Hh%M';

/*  Les caract�res suivants sont utilis�s pour sp�cifier le format de la date :

	%a - nom abr�g� du jour de la semaine
	%A - nom complet du jour de la semaine
	%b - nom abr�g� du mois
	%B - nom complet du mois
	%c - repr�sentation pr�f�r�e pour les dates et heures, en local
	%C - num�ro de si�cle (l'ann�e, divis�e par 100 et arrondie entre 00 et 99)
	%d - jour du mois en num�rique (intervalle 01 � 31)
	%D - identique � %m/%d/%y
	%e - num�ro du jour du mois. Les chiffres sont pr�c�d�s d'un espace (de '1' � '31')
	%g - identique � %G, sur 2 chiffres
	%G - L'ann�e sur 4 chiffres correspondant au num�ro de semaine (voir %V). M�me format et valeur que %Y, except� que si le num�ro de la semaine appartient � l'ann�e pr�c�dente ou suivante, l'ann�e courante sera utilis� � la place
	%h - identique � %b
	%H - heure de la journ�e en num�rique, et sur 24-heures (intervalle de 00 � 23)
	%I - heure de la journ�e en num�rique, et sur 12-heures (intervalle 01 � 12)
	%j - jour de l'ann�e, en num�rique (intervalle 001 � 366)
	%m - mois en num�rique (intervalle 1 � 12)
	%M - minute en num�rique
	%n - caract�re de nouvelle ligne
	%p - soit `am' ou `pm' en fonction de l'heure absolue, ou en fonction des valeurs enregistr�es en local
	%r - l'heure au format a.m. et p.m
	%R - l'heure au format 24h
	%S - secondes en num�rique
	%t - tabulation
	%T - l'heure actuelle (�gal � %H:%M:%S)
	%u - le num�ro de jour dans la semaine, de 1 � 7. (1 repr�sente Lundi)
	%U - num�ro de semaine dans l'ann�e, en consid�rant le premier dimanche de l'ann�e comme le premier jour de la premi�re semaine
	%V - le num�ro de semaine comme d�fini dans l'ISO 8601:1988, sous forme d�cimale, de 01 � 53. La semaine 1 est la premi�re semaine qui a plus de 4 jours dans l'ann�e courante, et dont Lundi est le premier jour. (Utilisez %G ou %g pour les �l�ments de l'ann�e qui correspondent au num�ro de la semaine pour le timestamp donn�.)
	%W - num�ro de semaine dans l'ann�e, en consid�rant le premier lundi de l'ann�e comme le premier jour de la premi�re semaine
	%w - jour de la semaine, num�rique, avec Dimanche = 0
	%x - format pr�f�r� de repr�sentation de la date sans l'heure
	%X - format pr�f�r� de repr�sentation de l'heure sans la date
	%y - l'ann�e, num�rique, sur deux chiffres (de 00 � 99)
	%Y - l'ann�e, num�rique, sur quatre chiffres
	%Z ou %z - fuseau horaire, ou nom ou abr�viation
	%% - un caract�re `%' litt�ral
*/


# Ici les chaines pour les dates localis�es
$GLOBALS['locales_dates']['Jan'] = 'jan';
$GLOBALS['locales_dates']['Feb'] = 'f�v';
$GLOBALS['locales_dates']['Mar'] = 'mar';
$GLOBALS['locales_dates']['Apr'] = 'avr';
$GLOBALS['locales_dates']['May'] = 'mai';
$GLOBALS['locales_dates']['Jun'] = 'juin';
$GLOBALS['locales_dates']['Jul'] = 'juil';
$GLOBALS['locales_dates']['Aug'] = 'ao�';
$GLOBALS['locales_dates']['Sep'] = 'sep';
$GLOBALS['locales_dates']['Oct'] = 'oct';
$GLOBALS['locales_dates']['Nov'] = 'nov';
$GLOBALS['locales_dates']['Dec'] = 'dec';
$GLOBALS['locales_dates']['January'] = 'janvier';
$GLOBALS['locales_dates']['February'] = 'f�vrier';
$GLOBALS['locales_dates']['March'] = 'mars';
$GLOBALS['locales_dates']['April'] = 'avril';
$GLOBALS['locales_dates']['June'] = 'juin';
$GLOBALS['locales_dates']['July'] = 'juillet';
$GLOBALS['locales_dates']['August'] = 'ao�t';
$GLOBALS['locales_dates']['September'] = 'septembre';
$GLOBALS['locales_dates']['October'] = 'octobre';
$GLOBALS['locales_dates']['November'] = 'novembre';
$GLOBALS['locales_dates']['December'] = 'd�cembre';
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