<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";
require_once __DIR__."/auth.php";

util::log("-------------------------------------------");
util::log("Started...");

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
	
	public static function getConnections($call, $session, $serial) {
		$C = util::dbConnection();
		$Q = $C->query("SELECT * FROM SBForward");
		
		$connections = array();
		while($R = $Q->fetch_object()){
			$R->SBForwardConnected = 0;
			$pfile = "/home/pi/pids/ssh_".$R->SBForwardID;
			if(file_exists($pfile) AND util::isConnected(file_get_contents($pfile)))
				$R->SBForwardConnected = 1;
			
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
#$url = "ws://192.168.7.77:4444/";
#$url = "ws://websocket03.furtmeier.it:4444/";
$seconds = 60 * 20;

Thruway\Logging\Logger::set(new Psr\Log\NullLogger());
$connection = new \Thruway\Connection([
	"realm"   => $realm,
	"url"     => $url
]);

$client = $connection->getClient();
$client->addClientAuthenticator(new ClientPhimAuthenticator($realm, "phimUser", $token));

$connection->on('error', function($reason){
	print_r($reason);
});

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial, $url, $seconds, $cloud) {
	util::log("Connected to $url");
	
	util::sayHo($session);
	
	$connection->getClient()->getLoop()->addPeriodicTimer($seconds, function(React\EventLoop\Timer\Timer $timer) use ($session, $serial){
		util::sayHo($session);
	});
	
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
        	
		util::log("Server says to me '$call->m'");
		
		$method = $call->m;
		if(!method_exists("OnAction", $method))
			throw new Exception ("Method $method does not exist!");
		
		$answer = OnAction::$method(isset($call->a) ? $call->a : null, $session, $serial);
		if($answer)
			$session->publish('it.furtmeier.supportbox.'.$serial, [$answer], [], ["acknowledge" => true]);
    });
	
});

$connection->open();
util::log("Shutdown.");
exit(0);