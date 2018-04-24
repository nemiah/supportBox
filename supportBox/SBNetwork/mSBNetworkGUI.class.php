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

class mSBNetworkGUI extends anyC implements iGUIHTMLMP2 {

	public function getHTML($id, $page){
		$N = anyC::getFirst("SBNetwork");
		
		$id = -1;
		if($N)
			$id = $N->getID();
		
		$SB = new SBNetworkGUI($id);
		echo $SB->getHTML($id);
		/*$this->loadMultiPageMode($id, $page, 0);

		$gui = new HTMLGUIX($this);
		$gui->version("mSBNetwork");
		$gui->screenHeight();

		$gui->name("SBNetwork");
		
		$gui->attributes(array());
		
		return $gui->getBrowserHTML($id);*/
	}

	public static function writeDHCPDConf(){
		$static = "";
		$AC = anyC::get("SBNetwork");
		while($N = $AC->n()){
			if($N->A("SBNetworkMode") == "DHCP")
				continue;
			
			$static .= "
interface ".$N->A("SBNetworkInterface")."
static ip_address=".$N->A("SBNetworkIP")."/".$N->A("SBNetworkSubnet")."
static routers=".$N->A("SBNetworkGateway")."
static domain_name_servers=".$N->A("SBNetworkDNS");
		}
		
		$config = "hostname
clientid
persistent
option rapid_commit
option domain_name_servers, domain_name, domain_search, host_name
option classless_static_routes
option ntp_servers
option interface_mtu

require dhcp_server_identifier

slaac private

$static";
		
		exec("echo \"$config\" | sudo tee /etc/dhcpcd.conf > /dev/null");
	}

}
?>