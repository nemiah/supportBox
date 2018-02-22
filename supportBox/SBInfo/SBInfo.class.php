<?php
/**
 *  This file is part of supportBox.

 *  supportBox is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  supportBox is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses></http:>.
 * 
 *  2007 - 2018, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */

class SBInfo extends UnpersistentClass {
	public static $server = "https://cloud.furtmeier.it";
	public static function serial() {
		$cpuinfo = file_get_contents("/proc/cpuinfo");

		$ex = explode("\n", trim($cpuinfo));

		$info = array();
		foreach($ex AS $line){
				$e = explode(":", $line);
				if(count($e) < 2)
						continue;

				$info[trim($e[0])] = trim($e[1]);
		}
		if(isset($info["Serial"]))
			return $info["Serial"];

		return rand(10000, 99999);
	}
	
	public static function status($echo = false){
		exec("sudo /usr/bin/supervisorctl status", $output);
		preg_match("/box[ ]+([A-Z]+)[ ]+pid/", $output[0], $matches);
		
		if($echo)
			echo $matches[1];
		
		return $matches[1];
		#supportBox:supportbox            RUNNING   pid 12988, uptime 1:51:01
	}
	
}
?>