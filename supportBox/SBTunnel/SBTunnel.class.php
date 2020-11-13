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
class SBTunnel extends PersistentObject {
	private function updateConfig($id){
		$filename = "/etc/supervisor/conf.d/autossh".$id.".conf";
		exec("echo \"\" | sudo tee $filename > /dev/null");
		exec("sudo /bin/rm $filename");
		
		if(!$this->A("SBTunnelAktiv")){
			exec("sudo /usr/bin/supervisorctl reload");
			exec("sudo /usr/bin/supervisorctl restart all");
		
			return;
		}
		
		$cloudID = mUserdata::getGlobalSettingValue("SBCloud", null);
		$content = "[program:autoSSH]
command                 = autossh -p80 -M0 -N -o ExitOnForwardFailure=yes -o ServerAliveInterval=30 -o ServerAliveCountMax=3 -o ControlPath=none -oStrictHostKeyChecking=no -R".$this->A("SBTunnelServerPort").":".trim($this->A("SBTunnelIP")).":".trim($this->A("SBTunnelPort"))." $cloudID@static01.supportbox.io
process_name            = ".$this->A("SBTunnelServerPort")."
numprocs                = 1
autostart               = true
autorestart             = true
user                    = pi
redirect_stderr         = true
stdout_logfile          = /var/log/supervisor/autoSSH$id.log
stdout_logfile_maxbytes = 2MB";
		
		exec("echo \"$content\" | sudo tee $filename > /dev/null");
		exec("sudo /usr/bin/supervisorctl reload");
		exec("sudo /usr/bin/supervisorctl restart all");
	}
	
	public function saveMe($checkUserData = true, $output = false) {
		$this->updateConfig($this->getID());
		
		return parent::saveMe($checkUserData, $output);
	}
	
	public function deleteMe() {
		$this->changeA("SBTunnelAktiv", "0");
		$this->updateConfig($this->getID());
		
		return parent::deleteMe();
	}
	
	public function newMe($checkUserData = true, $output = false) {
		$id = parent::newMe($checkUserData, false);
		
		$this->updateConfig($id);
		
		return $id;
	}
}
?>