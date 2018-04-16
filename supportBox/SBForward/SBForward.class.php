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
class SBForward extends PersistentObject {
	private function checkIP($ip){
		if(SBUtil::serial() == "00000000demodemo")
			return true;
		
		$localIP = getHostByName(getHostName());
		if(trim($ip) == "localhost")
			return false;
		
		if(trim($ip) == $localIP)
			return false;
		
		if(substr(trim($ip), 0, 4) == "127.")
			return false;
		
		return true;
	}
	
	function newMe($checkUserData = true, $output = false) {
		if(!$this->checkIP($this->A("SBForwardIP")))
			Red::errorD ("Die IP der supportBox darf nicht verwendet werden!");
		
		return parent::newMe($checkUserData, $output);
	}
	
	function saveMe($checkUserData = true, $output = false) {
		if(!$this->checkIP($this->A("SBForwardIP")))
			Red::errorD ("Die IP der supportBox darf nicht verwendet werden!");
		
		return parent::saveMe($checkUserData, $output);
	}
}
?>