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
		if(SBUtil::serial() != "00000000demodemo" AND ($R->SBForwardIP == $localIP OR trim($R->SBForwardIP) == "localhost" OR substr(trim($R->SBForwardIP), 0, 4) == "127.")){
			$C->close();
			return SBUtil::error("Diese Verbindung ist unzulässig!");
		}
		
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
		
		$info = new stdClass();
		$info->ip = SBUtil::localIP();
		$info->uptime = trim($uptime);
		
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