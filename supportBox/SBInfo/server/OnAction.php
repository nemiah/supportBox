<?php

class OnAction {
	public static function allowed($C){
		#$mode = mUserdata::getGlobalSettingValue("supportBoxNewConnection", -1);
		$Q = $C->query("SELECT * FROM Userdata WHERE name = 'supportBoxNewConnection'");
		$R = $Q->fetch_object();
		if(!$R OR $R->wert == -1)
			return true;
		
		if($R->wert == 0 OR ($R->wert > 0 AND time() > $R->wert))
			return false;
		
		return true;
	}
	
	public static function allowedUntil($C){
		$Q = $C->query("SELECT * FROM Userdata WHERE name = 'supportBoxNewConnection'");
		$R = $Q->fetch_object();
		if(!$R OR $R->wert == -1)
			return -1;
		
		if($R->wert == 0 OR ($R->wert > 0 AND time() > $R->wert))
			return 0;
		
		return $R->wert;
	}
	
	public static function connectPort($args) {
		$C = SBUtil::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward WHERE SBForwardID = '".$C->real_escape_string($args[0])."'");
		$R = $Q->fetch_object();
		if(!$R){
			$C->close();
			return SBUtil::error("SBForwardID $args[0] existiert nicht!");
		}
		
		if(!self::allowed($C))
			return SBUtil::error("Aktuell sind keine neuen Verbindungen erlaubt.");
		
		$localIP = SBUtil::localIP();
		#if(SBUtil::serial() != "00000000demodemo" AND ($R->SBForwardIP == $localIP OR trim($R->SBForwardIP) == "localhost" OR substr(trim($R->SBForwardIP), 0, 4) == "127.")){
		#	$C->close();
		#	return SBUtil::error("Diese Verbindung ist unzulässig!");
		#}
		
		SBUtil::log("Connecting $args[1]:$R->SBForwardIP:$R->SBForwardPort ($args[0])");
		
		$command = "ssh -p80 -o StrictHostKeyChecking=no -R$args[1]:$R->SBForwardIP:$R->SBForwardPort -N pipi@$args[2]";
		exec(sprintf("%s > %s 2>&1 & echo $! > %s", $command, "/dev/null", "/home/pi/pids/ssh_".$args[0]));
		
		usleep(500000);
		if(!SBUtil::isConnected(file_get_contents("/home/pi/pids/ssh_".$args[0]))){
			SBUtil::log("No Connection established ($command), trying again!");
			
			unlink("/home/pi/pids/ssh_".$args[0]);
			
			$command = "ssh -p222 -o StrictHostKeyChecking=no -R$args[1]:$R->SBForwardIP:$R->SBForwardPort -N pipi@$args[2]";
			exec(sprintf("%s > %s 2>&1 & echo $! > %s", $command, "/dev/null", "/home/pi/pids/ssh_".$args[0]));
			
			usleep(500000);
			if(!SBUtil::isConnected(file_get_contents("/home/pi/pids/ssh_".$args[0]))){
				SBUtil::log("Connection failed!");
				
				unlink("/home/pi/pids/ssh_".$args[0]);
			}
		}
		
		if(file_exists("/home/pi/pids/ssh_".$args[0])){
			$pid = file_get_contents("/home/pi/pids/ssh_".$args[0]);
			exec("echo \"php ".__DIR__."/suicideSquad.php ".trim($pid)." $args[0]\" | at -M now + 180min 2>&1", $atResult);
			file_put_contents("/home/pi/pids/at_$args[0]", trim($atResult[1]));
		}
		
		$C->close();
		
		return SBUtil::ok("Verbindung aufgebaut");
    } 
	
	public static function disconnectPort($args) {
		$C = SBUtil::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward WHERE SBForwardID = '".$C->real_escape_string($args[0])."'");
		$R = $Q->fetch_object();
		if(!$R){
			$C->close();
			return SBUtil::error("SBForwardID $args[0] existiert nicht!");
		}
		
		SBUtil::log("Disconnecting $R->SBForwardIP:$R->SBForwardPort ($args[0])");

		exec("kill -9 ".file_get_contents("/home/pi/pids/ssh_".$args[0]));
		
		preg_match("/^job ([0-9]+) at /", file_get_contents("/home/pi/pids/at_".$args[0]), $matches);
		exec("atrm ".$matches[1]);
		
		unlink("/home/pi/pids/ssh_".$args[0]);
		unlink("/home/pi/pids/at_".$args[0]);
		
		$C->close();
		
		return SBUtil::ok("Verbindung abgebaut");
    }
	
	public static function getConnections() {
		$C = SBUtil::dbConnection();
		
		$allowed = self::allowed($C);
		
		$Q = $C->query("SELECT * FROM SBForward");
		$connections = array();
		while($R = $Q->fetch_object()){
			$R->SBForwardConnected = 0;
			$R->SBForwardTimeout = 0;
			$R->SBForwardAllowed = $allowed;
			
			$pfile = "/home/pi/pids/ssh_".$R->SBForwardID;
			if(file_exists($pfile) AND SBUtil::isConnected(file_get_contents($pfile))){
				$R->SBForwardConnected = 1;
				$R->SBForwardTimeout = strtotime(preg_replace("/^job [0-9]+ at /", "", file_get_contents("/home/pi/pids/at_".$R->SBForwardID)));
			}
			$R->SBForwardAvailable = false;
			$handle = @fsockopen($R->SBForwardIP, $R->SBForwardPort, $errno, $errstr, 1); 

			if($handle){
				$R->SBForwardAvailable = true;
				fclose($handle);
			}
			
			$connections[] = $R;
		}
		
		SBUtil::log("Sending connections list to server (".count($connections)." entr".(count($connections) == 1 ? "y" : "ies").")");
		
		$add = array();
		$add["allowedUntil"] = self::allowedUntil($C);
		
		$C->close();
		
        return SBUtil::ok("", $connections, $add);
    }
	
	public static function getInfo(){
		$uptime = shell_exec("uptime");
		$df = shell_exec("df");
		
		$info = new stdClass();
		$info->ip = SBUtil::localIP();
		$info->uptime = trim($uptime);
		
		$softwareC = new stdClass();
		$softwareC->php = phpversion();
		if(file_exists("/var/www/html/open3A/current/applications"))
			$softwareC->open3A = shell_exec("php ".__DIR__."/open3AVersion.php");
		$info->software = $softwareC;
		
		$osC = new stdClass();
		$osC->debian = trim(shell_exec("cat /etc/debian_version"));
		$osC->linux = trim(shell_exec("uname -a"));
		$info->os = $osC;
		
		$hardwareC = new stdClass();
		$hardwareC->serial = SBUtil::serial();
		$hardwareC->model = SBUtil::model();
		$hardwareC->cpuTemp = str_replace(["temp=", "'C"], "", trim(shell_exec("/opt/vc/bin/vcgencmd measure_temp")));
		$hardwareC->df = trim($df);
		$info->hardware = $hardwareC;
		
		$smart = trim(shell_exec("sudo smartctl -a /dev/sda"));
		if($smart != ""){
			$smartC = new stdClass();
			preg_match("/SMART overall-health self-assessment test result: ([A-Z0-9a-z]*)/", $smart, $matches);
			
			$smartC->self_assessment = $matches[1];
			
			$result = substr($smart, strpos($smart, "RAW_VALUE") + 10);
			$lines = explode("\n", $result);
			foreach($lines AS $line){
				if(trim($line) == "")
					break;
				
				$cols = preg_split('/\s+/', trim($line));
				if($cols[1] == "Unknown_Attribute")
					continue;
					
				$smartValue = $cols[0]."_".$cols[1];

				$smartC->$smartValue = $cols[9];
			}
			
			$info->smart_sda = $smartC;
		}
		
		if(file_exists("/home/pi/log")){
			$logs = [];
			$dir = new DirectoryIterator("/home/pi/log");
			foreach ($dir as $file) {
				if($file->isDot()) 
					continue;
				if($file->isDir()) 
					continue;

				$logs[] = $file->getPathname();
			}

			arsort($logs);

			$backupC = new stdClass();
			
			if(!count($logs)){
				$backupC->lastLog = "No backup log!";
				$backupC->status = "ERROR";
			} else {
				$current = current($logs);
				$log = trim(file_get_contents($current));

				$backupC->lastLog = $log;
				if(strpos($log, "ERROR") === false){
					$backupC->status = "OK";
				} else 
					$backupC->status = "ERROR";
				
				
				$lines = explode("\n", $log);

				$df = "";
				$last = "";
				$ls = "";
				$lsCollect = false;
				foreach($lines AS $line){
					if(strpos($line, "INFO: Stamp ") !== false)
						$last = $line;
					
					if(strpos($line, "INFO: DF ") !== false)
						$df = $line;
					
					if(strpos($line, "INFO: LS ") !== false){
						$lsCollect = true;
					}
					
					if($lsCollect AND strpos($line, "INFO") !== false)
						$lsCollect = false;
					
					if($lsCollect)
						$ls .= $line;
				}
				$backupC->df = str_replace("INFO: DF ", "", $df);
				$backupC->ls = str_replace("INFO: LS ", "", $ls);
				
				$last = preg_replace("/[^0-9]*/", "", $last);
				
				$backupC->time = $last;
				if(time() - $last > 3600 * 48){
					$backupC->status = "ERROR";
					#$info->backupMessage = "Last backup older than two days";
				}

			}
			
			$info->backup = $backupC;
		}
		
		return $info;
	}
	
	public static function hi($call, $session, $serial) {
		#print_r($call); //do nothing, that's fine!
		return null;
    }
	
	public static function doUpdate(){
		exec("cd ".realpath(__DIR__."/../../../")." && sudo -u pi git pull origin master 2>&1", $output1);
		
		exec("sudo -u pi php ".__DIR__."/update.php", $output2);
		
		exec("echo \"sudo /usr/bin/supervisorctl restart all\" | at -M now + 2min", $output3);
		
		return "git pull origin master:\n".implode("\n", $output1)."\n\nupdate.php:\n".implode("\n", $output2)."\n\nScheduling restart in 2 minute.\n".implode("\n", $output3);
	}
	
	public static function getVersion(){
		require_once __DIR__."/../../../applications/supportBoxApplication.class.php";
		$A = new supportBoxApplication();
		exec("cd ".realpath(__DIR__."/../../../")." && git rev-parse --short HEAD", $output);
		
		return $A->registerVersion()." ".$output[0];
	}
}