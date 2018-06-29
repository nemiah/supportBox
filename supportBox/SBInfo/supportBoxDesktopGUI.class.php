<?php
/*
 *  This file is part of lightCRM.

 *  lightCRM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  lightCRM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  2007 - 2018, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */
class supportBoxDesktopGUI extends ADesktopGUI implements iGUIHTML2 {

	public $currentVersion = null;

	public function getHTML($id){
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>

		switch($id){
			case "1":
				return OnEvent::script("contentManager.loadPlugin('contentRight', 'mSBInfo')");
			break;
		}

	}
}
?>