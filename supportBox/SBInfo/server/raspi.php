<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/SBUtil.php";
require_once __DIR__."/OnAction.php";

ini_set('default_socket_timeout', 2);

SBUtil::log("-----------------------------------------");
SBUtil::log("Started...");

$handle = @fsockopen(SBUtil::serverURL(), SBUtil::serverPort()); 
while(!$handle){
	sleep(5);
	$handle = @fsockopen(SBUtil::serverURL(), SBUtil::serverPort()); 
}
fclose($handle);


$C = SBUtil::dbConnection();
$cloud = SBUtil::cloud($C);
if($cloud == null){
	$connection = SBUtil::initNew();
	$connection->on('open', function (\Thruway\ClientSession $session) use ($connection) {
		SBUtil::log("Connected to wss://".SBUtil::serverURL().":".SBUtil::serverPort());
		SBUtil::log("Subscribing to channel hiimnew...");
		
		$session->subscribe('it.furtmeier.supportbox.hiimnew', function ($args) use ($session) {
			$call = $args[0];
			if(!$call OR !isset($call->m))
				return;

			SBUtil::log("Server says to all '$call->m'");

			switch($call->m){
				case "knockknock":
					$localIP = SBUtil::localIP();
					SBUtil::sayImHere($session, "hiimnew", $localIP);
				break;
			}
		});
	});
	
	$connection->open();
	SBUtil::log("Shutdown.");
	
}
$connection = SBUtil::init($C);
$C->close();


$serial = SBUtil::serial();



$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial,  $cloud) {
	SBUtil::log("Connected to wss://".SBUtil::serverURL().":".SBUtil::serverPort());
	
    $session->subscribe('it.furtmeier.supportbox.serversays', function ($args) use ($session) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		SBUtil::log("Server says to all '$call->m'");
				
        switch($call->m){
        	case "hi":
        		SBUtil::sayHo($session);
	        break;
        }
    });
	
    $session->subscribe('it.furtmeier.supportbox.'.strtolower($cloud), function ($args) use ($session, $cloud) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		SBUtil::log("Server says to cloud '$call->m'");
		
        switch($call->m){
        	case "knockknock":
        		SBUtil::sayImHere($session, $cloud);
	        break;
        }
    });
	
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($session, $serial) {
        $call = $args[0];
        if(!$call OR !isset($call->m))
        	return;
        	
		SBUtil::log("Somebody says to me '$call->m'");
		
        switch($call->m){
        	case "amihere":
        		SBUtil::sayImHere($session, $serial);
	        break;
        }
    });
	
	$session->register('it.furtmeier.supportbox.'.$serial.".connectPort", function($args) use ($session, $cloud){
		$result = OnAction::connectPort($args);
		$session->publish('it.furtmeier.supportbox.'.strtolower($cloud), [SBUtil::message("connected", $args[0])], [], ["acknowledge" => true]);
		return $result;
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".disconnectPort", function($args) use ($session, $cloud){
		$result = OnAction::disconnectPort($args);
		$session->publish('it.furtmeier.supportbox.'.strtolower($cloud), [SBUtil::message("disconnected", $args[0])], [], ["acknowledge" => true]);
		return $result;
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".getConnections", function(){
		return SBUtil::ok("", OnAction::getInfo());
		#return OnAction::getConnections();
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".doUpdate", function(){
		return SBUtil::ok("", OnAction::doUpdate());
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".getVersion", function(){
		return SBUtil::ok("", OnAction::getVersion());
	});
	
	$session->register('it.furtmeier.supportbox.'.$serial.".getInfo", function(){
		return SBUtil::ok("", OnAction::getInfo());
	});
	
});

$connection->open();
SBUtil::log("Shutdown.");
exit(0);