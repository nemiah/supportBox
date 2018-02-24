<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";

ini_set('default_socket_timeout', 2);

util::log("-------------------------------------------");
util::log("Started...");

util::log("Sleeping 60 sec");
sleep(60);
util::log("Go...");

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
		
		exec(sprintf("%s > %s 2>&1 & echo $! > %s", "ssh -o StrictHostKeyChecking=no -R$args[1]:$R->SBForwardIP:$R->SBForwardPort -N pipi@$args[2]", "/dev/null", "/home/pi/pids/ssh_".$args[0]));
		
		if(!util::isConnected(file_get_contents("/home/pi/pids/ssh_".$args[0])))
			unlink("/home/pi/pids/ssh_".$args[0]);
		
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
			$handle = @fsockopen($R->SBForwardIP, $R->SBForwardPort, null, null, 2); 

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
		exec("cd /var/www/html && sudo -u pi git pull origin master", $output1);
		
		exec("sudo -u pi php /var/www/html/update.php", $output2);
		
		exec("sudo /usr/bin/supervisorctl restart all", $output3);
		
		return "git pull origin master:\n".implode("\n", $output1)."\n\nupdate.php:\n".implode("\n", $output2)."\n\nsupervisorctl restart all:\n".implode("\n", $output3);
	}
	
	public static function getVersion(){
		require_once __DIR__."/../../../applications/supportBoxApplication.class.php";
		$A = new supportBoxApplication();
		return $A->registerVersion();
	}
}

$C = util::dbConnection();
$Q = $C->query("SELECT * FROM Userdata WHERE UserID = -1 AND name = 'SBToken'");
$RToken = $Q->fetch_object();
if(!$RToken){
	sleep(3);
	exit(2);
}

$Q = $C->query("SELECT * FROM Userdata WHERE UserID = -1 AND name = 'SBCloud'");
$RCloud = $Q->fetch_object();
$cloud = $RCloud->wert;

$C->close();

$serial = util::serial();
$realm = "supportBox_1";
$token = $RToken->wert;

$url = "wss://venus.supportbox.io:4444/";

Thruway\Logging\Logger::set(new Psr\Log\NullLogger());
$connection = new \Thruway\Connection([
	"realm"   => $realm,
	"url"     => $url
]);

$client = $connection->getClient();
$auth = new Thruway\Authentication\ClientWampCraAuthenticator("supportBox", $token);
$client->setAuthId('supportBox');
$client->addClientAuthenticator($auth);

$connection->on('error', function($reason){
	print_r($reason);
});

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial, $url,  $cloud) {
	util::log("Connected to $url");
	
    $session->subscribe('it.furtmeier.supportbox.serversays', function ($args) use ($session) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		util::log("Server says to all '$call->m'");
				
        switch($call->m){
        	case "hi":
        		util::sayHo($session);
	        break;
        }
    });
	
    $session->subscribe('it.furtmeier.supportbox.'.strtolower($cloud), function ($args) use ($session, $cloud) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		util::log("Server says to cloud '$call->m'");
		
        switch($call->m){
        	case "knockknock":
        		util::sayImHere($session, $cloud);
	        break;
        }
    });
	
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($session, $serial) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		util::log("Somebody says to me '$call->m'");
		
        switch($call->m){
        	case "amihere":
        		util::sayImHere($session, $serial);
	        break;
        }
    });
	
	$session->register('it.furtmeier.supportbox.'.$serial.".connectPort", function($args){
		return OnAction::connectPort($args);
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".disconnectPort", function($args){
		return OnAction::disconnectPort($args);
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".getConnections", function(){
		return OnAction::getConnections();
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".doUpdate", function(){
		return util::ok("", OnAction::doUpdate());
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".getVersion", function(){
		return util::ok("", OnAction::getVersion());
	});
	
});

$connection->open();
util::log("Shutdown.");
exit(0);