<?php
require_once __DIR__."/SBUtil.php";

$handle = @fsockopen(SBUtil::serverURL(), SBUtil::serverPort()); 

if(!$handle)
	die();
fclose($handle);


require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";

$C = SBUtil::dbConnection();
$token = SBUtil::token($C);
if($token == null)
	exit(0);

$connection = SBUtil::init($C);
$C->close();
$serial = SBUtil::serial();

class amIOnline {
	public static $timer;
	public static $done = false;
}

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial) {
	#SBUtil::log("Connected to server");
	
	amIOnline::$timer = $connection->getClient()->getLoop()->addTimer(10, function() use ($connection, $session){
		#SBUtil::log("No answer from me!");
		
		$connection->close();
	});
			
			
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($connection) {
		if($args[0]->f != ltrim(SBUtil::serial(), "0") OR $args[0]->m != "I'm here")
			return;
		
		#SBUtil::log("Everything OK!");
		amIOnline::$done = true;
		amIOnline::$timer->cancel();
		
		$connection->close();
    });
	
	$session->publish('it.furtmeier.supportbox.'.$serial, [SBUtil::message("amihere")], [], ["acknowledge" => true]);
	
});

$connection->open();
#SBUtil::log("Shutdown.");

if(amIOnline::$done){
	#SBUtil::log("Shutdown.");
	exit(0);
}

exec("sudo /usr/bin/supervisorctl restart all");

#SBUtil::log("Restarted and waiting 90 seconds...");
sleep(90);

$C = SBUtil::dbConnection();
$connection = SBUtil::init($C);
$C->close();

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial) {
	amIOnline::$timer = $connection->getClient()->getLoop()->addTimer(10, function() use ($connection, $session){
		#SBUtil::log("Still no answer from me!!!");
		
		$connection->close();
	});
			
			
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($connection) {
		if($args[0]->f != ltrim(SBUtil::serial(), "0") OR $args[0]->m != "I'm here")
			return;
		
		#SBUtil::log("Everything OK now!");
		
		amIOnline::$done = true;
		amIOnline::$timer->cancel();
		
		$connection->close();
    });
	
	$session->publish('it.furtmeier.supportbox.'.$serial, [SBUtil::message("amihere")], [], ["acknowledge" => true]);
	
});

$connection->open();

if(amIOnline::$done){
	#SBUtil::log("Shutdown.");
	exit(0);
}

SBUtil::log("EXPLODE!!!");

exit(0);