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
class SBNetworkGUI extends SBNetwork implements iGUIHTML2 {
	function getHTML($id){
		$gui = new HTMLGUIX($this);
		$gui->name("Netzwerk");
	
		$gui->label("SBNetworkInterface", "Schnittstelle");
		$gui->label("SBNetworkMode", "Modus");
		$gui->label("SBNetworkSubnet", "Subnetz");
		
		$gui->type("SBNetworkSubnet", "select", array("24" => "255.255.255.0 (24)"));
		$gui->type("SBNetworkInterface", "select", array("eth0" => "eth0"));
		$gui->type("SBNetworkMode", "select", array("DHCP" => "DHCP", "static" => "Statisch"));
		
		$gui->descriptionField("SBNetworkDNS", "Mehrere Server mit Leerzeichen trennen");
		
		$gui->toggleFields("SBNetworkMode", "static", array("SBNetworkSubnet", "SBNetworkIP", "SBNetworkGateway", "SBNetworkDNS"));
		
		$info = "<p class=\"highlight\" style=\"padding:5px;\">Nach dem Speichern wird die supportBox automatisch neu gestartet und ist dann unter der neuen IP erreichbar.</p>";
		
		$gui->addToEvent("onNew", OnEvent::rme($this, "reboot"));
		$gui->addToEvent("onSave", OnEvent::rme($this, "reboot"));
		
		return $gui->getEditHTML().$info;
	}
}
?>