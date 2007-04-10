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

// Make sure no one attempts to run this script "directly"
if (!defined('IN_PUNSAPI'))
	exit;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo 'Error - '.$this->htmlspecialchars($this->config['o_board_title']) ?></title>
<style type="text/css">
<!--
#pun-error {margin: 10% 20% auto 20%}
.pun {font: normal 75%/130% Verdana, Arial, Helvetica, sans-serif}
#pun-error .c-any {border: 1px solid #b84623}
h1 {margin: 0; color: #fff; background-color: #b84623; padding: 0.4em 1em; font-size: 1em}
h1 span {font-size: 1.2em}
.pun .c-any {padding: 1em 1em 0.2em 1em; background-color: #f7f7f7}
.pun .c-any p {padding: 0 0 0.8em 0; margin: 0;font-size: 1.1em}
.pun .c-any p span {display: block}
-->
</style>
</head>
<body>

<div id="pun-error">

<div id="pun-main1" class="a-section a-main">

	<h1 class="main-title"><span>An error was encountered</span></h1>

	<div class="b-sectionion b-message">
		<div class="c-section c-any">
<?php

	if ($this->options['debug'])
	{
		if ($file && $line)
		{
			echo "\t\t\t".'<p><span><strong>File:</strong> '.$file.'</span>'.
			"\n\t\t\t".'<span><strong>Line:</strong> '.$line.'</span></p>';
		}
		
		echo "\t\t\t".'<p><strong>PunSAPI reported</strong>: '.$message.'</p>'."\n";

		if ($db_error)
		{
			echo "\t\t\t".'<p><strong>Database reported:</strong> '.htmlspecialchars($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '').'</p>'."\n";

			if ($db_error['error_sql'] != '')
				echo "\t\t\t".'<p><strong>Failed query:</strong> '.htmlspecialchars($db_error['error_sql']).'</p>'."\n";
		}
	}
	else
		echo "\t\t\t".'<p><strong>Error: </strong>'.$message.'.</p>'."\n";

?>
		</div>
	</div>

</div>

</div>
</body>
</html>
