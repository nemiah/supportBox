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
	public static $server = "https://hq.supportbox.io";
	public static function serial() {
		$cpuinfo = file_get_contents("/proc/cpuinfo");
		if(file_exists("/home/pi/cpuinfo"))
			$cpuinfo = file_get_contents("/home/pi/cpuinfo");

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
		#print_r($output);#
		$status = "";
		foreach($output AS $k => $v){
			if(preg_match("/autoSSH:([0-9]+)[ ]+([A-Z]+)[ ]+pid/", $v, $matches)){
				$status .= "Tunnel $matches[1]: $matches[2]<br>";
			}
			
			if(preg_match("/box[ ]+([A-Z]+)[ ]+pid/", $v, $matches)){
				$status .= "Server $matches[1]<br>";
			}
			
		}
		
		if($echo)
			echo $status;
		
		return $status;
		#supportBox:supportbox            RUNNING   pid 12988, uptime 1:51:01
	}
	
}
?>