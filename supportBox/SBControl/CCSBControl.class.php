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
		
		$this->loadPlugin("supportBox", "SBForward");
	}
	
	function getCMSHTML() {
		
		$html = "<div style=\"display:inline-block;width:33.33%;vertical-align:top;\">";
		$html .= "<h1>Weiterleitungen</h1>";
		
		$html .= "<ul>";
		$AC = anyC::get("SBForward");
		while($F = $AC->n()){
			$html .= "<li>".$F->A("SBForwardName")." (".$F->A("SBForwardIP").":".$F->A("SBForwardPort").")</li>";
		}
		
		$html .= "</ul>";
		$html .= "</div>";
		
		$html .= "<div style=\"display:inline-block;width:33.33%;vertical-align:top;\">";
		$html .= "<h1>Status supportBox</h1>";
		
		$mode = mUserdata::getGlobalSettingValue("supportBoxNewConnection", -1);
		
		$status = 1;
		if($mode > 0)
			$status = 2;
		if($mode == 0 OR ($mode > 0 AND time() > $mode))
			$status = 3;
		
		$html .= "<div onclick=\"CustomerPage.rme('setMode', [1], function(){ document.location.reload(); });\" style=\"cursor:pointer;padding:1em;".($status != 1 ? "background-color:#BBB;" : "")."\" class=\"".($status === 1 ? "confirm" : "")."\">Verbindungen immer zulassen</div>";
		$html .= "<div onclick=\"CustomerPage.rme('setMode', [2], function(){ document.location.reload(); });\" style=\"cursor:pointer;padding:1em;margin-top:1em;".($status != 2 ? "background-color:#BBB;" : "")."\" class=\"".($status === 2 ? "confirm" : "")."\">Verbindungen für 12 Stunden zulassen ".($status === 2 ? "(bis ".(Util::CLDateTimeParser($mode)).")" : "")."</div>";
		$html .= "<div onclick=\"CustomerPage.rme('setMode', [3], function(){ document.location.reload(); });\" style=\"cursor:pointer;padding:1em;margin-top:1em;".($status != 3 ? "background-color:#BBB;" : "")."\" class=\"".($status === 3 ? "confirm" : "")."\">Keine Verbindungen zulassen</div>";
		
		$html .= "</div>";
		
		return $html;

	}
	
	public function setMode($data){
		if($data["P0"] == 1){
			mUserdata::setUserdataS("supportBoxNewConnection", -1, "", -1);
		}
		
		if($data["P0"] == 2){
			mUserdata::setUserdataS("supportBoxNewConnection", time() + 3600 * 12, "", -1);
		}
		
		if($data["P0"] == 3){
			mUserdata::setUserdataS("supportBoxNewConnection", 0, "", -1);
		}
	}
}
?>