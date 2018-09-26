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
 *  2007 - 2017, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */
class SBTunnelGUI extends SBTunnel implements iGUIHTML2 {
	function getHTML($id){
		$gui = new HTMLGUIX($this);
		$gui->name("Tunnel");
		
		$cloudID = mUserdata::getGlobalSettingValue("SBCloud", null);
		$data = file_get_contents(SBInfo::$server."/ubiquitous/CustomerPage/?D=supportBox/SBDevice&cloud=$cloudID&method=tunnelPorts&serial=".SBInfo::serial());
		$data = json_decode($data);
		if(!$data)
			die("<p class=\"error\">Server nicht erreichbar!</p>");
		#var_dump($data);
		
		if($data->status == "Error")
			die("<p class=\"error\">$data->message</p>");
			
		$ports = array();
		foreach($data->data AS $port)
			$ports[$port] = $port;
		
		$gui->label("SBTunnelServerPort", "Server-Port");
		$gui->type("SBTunnelServerPort", "select", $ports);
		
		return $gui->getEditHTML();
	}
}
?>