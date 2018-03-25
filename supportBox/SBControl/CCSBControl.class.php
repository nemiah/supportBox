<?php
/**
 *  This file is part of FCalc.

 *  FCalc is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  FCalc is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2018, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */

ini_set('session.gc_maxlifetime', 24 * 60 * 60);

class CCSBControl extends CCPage implements iCustomContent {
	function getLabel(){
		return "supportBox";
	}
	
	function __construct() {
		parent::__construct();
		
		#$this->loadPlugin("supportBox", "SBDevice");
		/*$this->loadPlugin("open3A", "Stammdaten");
		$this->loadPlugin("open3A", "Adressen");
		$this->loadPlugin("open3A", "Textbausteine");
		$this->loadPlugin("open3A", "Kategorien");
		$this->loadPlugin("open3A", "Artikel");
		$this->loadPlugin("open3A", "Kunden");
		$this->loadPlugin("multiPOS", "Kassen");
		$this->loadPlugin("multiPOS", "Tische");
		$this->loadPlugin("multiPOS", "Bondrucker");*/
	}
	
	function getCMSHTML() {
		
		return "Hi";

	}
	
	
}

?>