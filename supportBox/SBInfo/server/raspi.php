<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";
require_once __DIR__."/OnAction.php";

ini_set('default_socket_timeout', 2);

util::log("-----------------------------------------");
util::log("Started...");

$handle = @fsockopen(util::serverURL(), util::serverPort()); 
while(!$handle){
	sleep(5);
	$handle = @fsockopen(util::serverURL(), util::serverPort()); 
}
fclose($handle);


$C = util::dbConnection();
$cloud = util::cloud($C);
if($cloud == null){
	$connection = util::initNew();
	$connection->on('open', function (\Thruway\ClientSession $session) use ($connection) {
		util::log("Connected to wss://".util::serverURL().":".util::serverPort());
		util::log("Subscribing to channel hiimnew...");
		
		$session->subscribe('it.furtmeier.supportbox.hiimnew', function ($args) use ($session) {
			$call = $args[0];
			if(!$call OR !isset($call->m))
				return;

			util::log("Server says to all '$call->m'");

			switch($call->m){
				case "knockknock":
					$localIP = getHostByName(getHostName());
					util::sayImHere($session, "hiimnew", $localIP);
				break;
			}
		});
	});
	
	$connection->open();
	util::log("Shutdown.");
	
}
$connection = util::init($C);
$C->close();


$serial = util::serial();



$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial,  $cloud) {
	util::log("Connected to wss://".util::serverURL().":".util::serverPort());
	
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