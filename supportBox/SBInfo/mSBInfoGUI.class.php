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

class mSBInfoGUI extends UnpersistentClass implements iGUIHTMLMP2 {
	private function serial() {
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
	
	public function getHTML($id, $page){
		$T = new HTMLTable(2);
		$T->setColWidth(1, 140);
		$T->setTableStyle("table-layout:fixed");
		
		$uptime = shell_exec("uptime");
		
		$B = new Button("Cloud ändern", "./supportBox/SBInfo/key18.png", "icon");
		$B->style("float:right;");
		$B->popup("", "In Cloud registrieren", "mSBInfo", "-1", "cloudPopup");
		
		$BT = new Button("Token aktualisieren", "./images/i2/refresh.png", "icon");
		$BT->style("float:right;");
		$BT->popup("", "Token aktualisieren", "mSBInfo", "-1", "tokenRefresh");
		
		$BP = new Button("Passwort aktualisieren", "./images/i2/refresh.png", "icon");
		$BP->style("float:right;");
		$BP->popup("", "Passwort aktualisieren", "mSBInfo", "-1", "passwordRefresh");
		
		#$BS = new Button("Supervisor-Status", "./supportBox/SBInfo/administrator.png", "icon");
		#$BS->style("float:right;");
		#$BS->popup("", "Supervisor-Status", "mSBInfo", "-1", "status");
		
		if(!mUserdata::getGlobalSettingValue("SBCloud", "")){
			$B = "";
			$BT = "";
		}
		
		$result = exec("sudo -u pi ssh-keygen -lf /home/pi/.ssh/id_rsa.pub");
		$content = explode(' ', $result);
		
		$T->addLV("Seriennummer:", $this->serial());
		$T->addLV("supportBox Version:", Applications::i()->getRunningVersion());
		$T->addLV("Cloud:", $B.mUserdata::getGlobalSettingValue("SBCloud", ""));
		$T->addLV("Uptime:", $uptime);
		$T->addLV("Token:", $BT."<div style=\"width:calc(100% - 25px);overflow:hidden;text-overflow:ellipsis;\">".mUserdata::getGlobalSettingValue("SBToken", "")."</div>");
		$T->addLV("SSH-Passwort (pi):", $BP."<div style=\"width:calc(100% - 25px);overflow:hidden;text-overflow:ellipsis;\">".mUserdata::getGlobalSettingValue("SBSSHPass", "")."</div>");
		$T->addLV("SSH-Public key (pi):", $content[1]);
		$T->addCellStyle(2, "word-wrap:break-word");
		$T->addLV("Supervisor:", "<span id=\"supervisorStatus\">Prüfe Status…</span>");
		
		$ST = new HTMLSideTable("left");
		
		if(!mUserdata::getGlobalSettingValue("SBCloud", "")){
			$B = $ST->addButton("In Cloud\nregistrieren", "./supportBox/SBInfo/key.png");
			$B->popup("", "In Cloud registrieren", "mSBInfo", "-1", "cloudPopup");
		}
		
		$js = OnEvent::script(OnEvent::rme($this, "status", array("1"), "function(t){ \$j('#supervisorStatus').html(t.responseText); }"));
		
		echo $ST.$T.$js;
	}

	public function status($echo = false){
		exec("sudo /usr/bin/supervisorctl status", $output);
		preg_match("/box[ ]+([A-Z]+)[ ]+pid/", $output[0], $matches);
		
		if($echo)
			echo $matches[1];
		
		return $matches[1];
		#supportBox:supportbox            RUNNING   pid 12988, uptime 1:51:01
	}
	
	public function cloudPopup(){
		$F = new HTMLForm("cloudReg", array(
			"cloud",
			"comment"
		));
		
		$F->getTable()->setColWidth(1, 120);
		
		$F->setLabel("comment", "Kommentar");
		
		$F->setDescriptionField("cloud", "Bitte tragen Sie den Furtmeier.IT Cloud-Zugang ein, wie er im Browser hinter wolke_ oder app_ angegeben ist.");
		$F->setValue("cloud", mUserdata::getGlobalSettingValue("SBCloud", ""));
		#$F->setSaveRMEPCR("Speichern", "", "mSBInfo", "-1", "cloudSave", "function(t){ \$j('#editDetailsContentmSBInfo').html(t.responseText); ".OnEvent::reload("Right")." }");
		
		echo $F;
		
		$B = new Button("Speichern", "save");
		$B->style("margin:10px;float:right;");
		$B->onclick("contentManager.rmePCR('mSBInfo', '-1', 'cloudSave', [$('cloudReg').cloud.value, $('cloudReg').comment.value], function(t){ \$j('#editDetailsContentmSBInfo').html(t.responseText); contentManager.reloadFrame('contentRight'); }, '', true, function(){});");
		$B->loading();
		
		echo $B;
	}
	public function passwordRefresh($noJs = false){
		$newPass = Util::genPassword(16);
		
		shell_exec('echo "pi:'.$newPass.'" | sudo /usr/sbin/chpasswd');
		
		mUserdata::setUserdataS("SBSSHPass", $newPass, "", -1);
		
		echo "<p class=\"confirm\">SSH-Passwort geändert!</p>";
		
		if(!$noJs)
			echo OnEvent::script(OnEvent::reload("Right"));
	}
	
	public function tokenRefresh($noJs = false){
		$data = file_get_contents("https://cloud9.furtmeier.it/ubiquitous/CustomerPage/?D=supportBox/SBDevice&cloud=".mUserdata::getGlobalSettingValue("SBCloud", "")."&method=token&serial=".$this->serial());
		$data = json_decode($data);
		
		mUserdata::setUserdataS("SBToken", $data->data, "", -1);
		
		if($data->status == "Error")
			echo "<p class=\"error\">".$data->message."</p>";
		
		
		if($data->status == "OK")
			echo "<p class=\"confirm\">".$data->message."</p>";
		
		exec("sudo /usr/bin/supervisorctl restart all", $output);
		#print_r($output);
		sleep(5);
		if($this->status() != "RUNNING")
			echo "<p class=\"error\">Supervisor-Fehler: ". implode("<br>", $output)."</p>";
		else
			echo "<p class=\"confirm\">Supervisor neu gestartet</p>";
		
		if(!$noJs)
			echo OnEvent::script(OnEvent::reload("Right"));
	}
	
	public function cloudSave($cloudID, $comment = ""){
		$data = file_get_contents("https://cloud9.furtmeier.it/ubiquitous/CustomerPage/?D=supportBox/SBDevice&cloud=$cloudID&method=check&serial=".$this->serial());
		$data = json_decode($data);
		if($data->status == "OK"){
			echo "<p class=\"error\">".$data->message."</p>";
			return;
		}
			
		$this->keyRefresh();
		
		$result = exec('sudo -u pi ssh-keygen -lf /home/pi/.ssh/id_rsa.pub');
		$content = explode(' ', $result);
				
		mUserdata::setUserdataS("SBCloud", $cloudID, "", -1);
		$data = file_get_contents("https://cloud9.furtmeier.it/ubiquitous/CustomerPage/?D=supportBox/SBDevice&cloud=$cloudID&method=register&serial=".$this->serial()."&comment=". urlencode($comment)."&pubkey=".urlencode(file_get_contents("/home/pi/.ssh/id_rsa.pub"))."&fingerprint=". urlencode($content[1]));
		$data = json_decode($data);
		
		if($data->status == "Error"){
			echo "<p class=\"error\">".$data->message."</p>";
			return;
		}
		
		if($data->status == "OK")
			echo "<p class=\"confirm\">".$data->message."</p>";
		
		
		$this->tokenRefresh(true);
		$this->passwordRefresh(true);
		
		echo OnEvent::script(OnEvent::reload("Right"));
	}
	
	private function keyRefresh(){
		exec("sudo -u pi rm /home/pi/.ssh/id_rsa");
		exec("sudo -u pi rm /home/pi/.ssh/id_rsa.pub");
		
		exec("sudo -u pi ssh-keygen -q -N \"\" -f ~/.ssh/id_rsa -f /home/pi/.ssh/id_rsa");
		
		echo "<p class=\"confirm\">SSH-Key erzeugt!</p>";
	}
}
?>