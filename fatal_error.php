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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo 'Error - '.$this->htmlspecialchars($this->config['o_board_title']) ?></title>
</head>
<body style="margin: 40px; font: 85%/130% verdana, arial, sans-serif; color: #333;">

<h1>An error was encountered</h1>
<hr />

<?php
	if ($this->options['debug'])
	{
		echo "<h2>in $file</h2>\n<ul>\n";
		echo "\t<li><strong>Line:</strong> $line</li>\n";
		echo "\t<li><strong>PunSAPI reported:</strong> $message</li>\n";

		if ($db_error)
		{
			echo "\t<li><strong>Database reported:</strong> ".htmlspecialchars($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '')."</li>\n";

			if ($db_error['error_sql'] != '')
				echo "\t<li><strong>Failed query:</strong> <code>".htmlspecialchars($db_error['error_sql'])."</code></li>\n";
		}

		echo "</ul>\n";
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
