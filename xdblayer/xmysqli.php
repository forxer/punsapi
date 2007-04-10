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


require_once dirname(__FILE__).'/recordset.php';

class xDBLayer extends DBLayer
{
	
	/**
	@function select
		
	@param	string	sql			SQL query
	@return	recordset
	*/
	function select($sql)
	{
		$cur = $this->query($sql, true);
		if ($cur)
		{
			$i = 0;
			$arryRes = array();
			while($res = $this->fetch_row($cur))
			{
				$num = count($res);
				for($j=0; $j<$num; $j++)
				{
  					$finfo = mysqli_fetch_field_direct($cur, $j);
   					$arryRes[$i][strtolower($finfo->name)] = $res[$j];		
				}
				$i++;
			}
			
			return new recordset($arryRes);
		}
		else
			return false;
	}


}

?>