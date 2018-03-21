<?php

class OnAction {
	public static function connectPort($args) {
		$C = util::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward WHERE SBForwardID = '".$C->real_escape_string($args[0])."'");
		$R = $Q->fetch_object();
		if(!$R){
			$C->close();
			return util::error("SBForwardID $args[0] existiert nicht!");
		}
		
		util::log("Connecting $args[1]:$R->SBForwardIP:$R->SBForwardPort ($args[0])");
		
		$command = "ssh -o StrictHostKeyChecking=no -R$args[1]:$R->SBForwardIP:$R->SBForwardPort -N pipi@$args[2]";
		exec(sprintf("%s > %s 2>&1 & echo $! > %s", $command, "/dev/null", "/home/pi/pids/ssh_".$args[0]));
		
		usleep(500000);
		if(!util::isConnected(file_get_contents("/home/pi/pids/ssh_".$args[0]))){
			unlink("/home/pi/pids/ssh_".$args[0]);
			
			$command = "ssh -p222 -o StrictHostKeyChecking=no -R$args[1]:$R->SBForwardIP:$R->SBForwardPort -N pipi@$args[2]";
			exec(sprintf("%s > %s 2>&1 & echo $! > %s", $command, "/dev/null", "/home/pi/pids/ssh_".$args[0]));
			
			usleep(500000);
			if(!util::isConnected(file_get_contents("/home/pi/pids/ssh_".$args[0])))
				unlink("/home/pi/pids/ssh_".$args[0]);
			
		}
		
		if(file_exists("/home/pi/pids/ssh_".$args[0])){
			$pid = file_get_contents("/home/pi/pids/ssh_".$args[0]);
			exec("echo \"kill -9 $pid\" | at -M now + 2min");
		}
		
		$C->close();
		
		return util::ok("Verbindung aufgebaut");
    } 
	
	public static function disconnectPort($args) {
		$C = util::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward WHERE SBForwardID = '".$C->real_escape_string($args[0])."'");
		$R = $Q->fetch_object();
		if(!$R){
			$C->close();
			return util::error("SBForwardID $args[0] existiert nicht!");
		}
		
		util::log("Disconnecting $R->SBForwardIP:$R->SBForwardPort ($args[0])");

		exec("kill -9 ".file_get_contents("/home/pi/pids/ssh_".$args[0]));
		unlink("/home/pi/pids/ssh_".$args[0]);
		
		$C->close();
		
		return util::ok("Verbindung abgebaut");
    }
	
	public static function getConnections() {
		$C = util::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward");
		
		$connections = array();
		while($R = $Q->fetch_object()){
			$R->SBForwardConnected = 0;
			$pfile = "/home/pi/pids/ssh_".$R->SBForwardID;
			if(file_exists($pfile) AND util::isConnected(file_get_contents($pfile)))
				$R->SBForwardConnected = 1;
			
			$R->SBForwardAvailable = false;
			$handle = @fsockopen($R->SBForwardIP, $R->SBForwardPort, $errno, $errstr, 2); 

			if($handle){
				$R->SBForwardAvailable = true;
				fclose($handle);
			}
			
			$connections[] = $R;
		}
		
		util::log("Sending connections list to server (".count($connections)." entr".(count($connections) == 1 ? "y" : "ies").")");
		
		$C->close();
		
        return util::ok("", $connections);
    }
	
	public static function hi($call, $session, $serial) {
		#print_r($call); //do nothing, that's fine!
		return null;
    }
	
	public static function doUpdate(){
		exec("cd /var/www/html && sudo -u pi git pull origin master 2>&1", $output1);
		
		exec("sudo -u pi php /var/www/html/supportBox/SBInfo/server/update.php", $output2);
		
		exec("echo \"sudo /usr/bin/supervisorctl restart all\" | at -M now + 2min", $output3);
		
		return "git pull origin master:\n".implode("\n", $output1)."\n\nupdate.php:\n".implode("\n", $output2)."\n\nScheduling restart in 2 minutes.\n".implode("\n", $output3);
	}
	
	public static function getVersion(){
		require_once __DIR__."/../../../applications/supportBoxApplication.class.php";
		$A = new supportBoxApplication();
		exec("cd /var/www/html && git rev-parse --short HEAD", $output);
		
		return $A->registerVersion()." ".$output[0];
	}
}