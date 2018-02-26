<?php
require_once __DIR__."/util.php";

$handle = @fsockopen(util::serverURL(), util::serverPort()); 

if(!$handle)
	die();
fclose($handle);


require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";

$C = util::dbConnection();
$token = util::token($C);
if($token == null)
	exit(0);

$connection = util::init($C);
$C->close();
$serial = util::serial();

class amIOnline {
	public static $timer;
	public static $done = false;
}

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial) {
	#util::log("Connected to server");
	
	amIOnline::$timer = $connection->getClient()->getLoop()->addTimer(10, function() use ($connection, $session){
		#util::log("No answer from me!");
		
		$connection->close();
	});
			
			
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($connection) {
		if($args[0]->f != ltrim(util::serial(), "0") OR $args[0]->m != "I'm here")
			return;
		
		#util::log("Everything OK!");
		amIOnline::$done = true;
		amIOnline::$timer->cancel();
		
		$connection->close();
    });
	
	$session->publish('it.furtmeier.supportbox.'.$serial, [util::message("amihere")], [], ["acknowledge" => true]);
	
});

$connection->open();
#util::log("Shutdown.");

if(amIOnline::$done){
	#util::log("Shutdown.");
	exit(0);
}

exec("sudo /usr/bin/supervisorctl restart all");

#util::log("Restarted and waiting 90 seconds...");
sleep(90);

$C = util::dbConnection();
$connection = util::init($C);
$C->close();

$connection->on('open', function (\Thruway\ClientSession $session) use ($connection, $serial) {
	amIOnline::$timer = $connection->getClient()->getLoop()->addTimer(10, function() use ($connection, $session){
		#util::log("Still no answer from me!!!");
		
		$connection->close();
	});
			
			
    $session->subscribe('it.furtmeier.supportbox.'.$serial, function ($args) use ($connection) {
		if($args[0]->f != ltrim(util::serial(), "0") OR $args[0]->m != "I'm here")
			return;
		
		#util::log("Everything OK now!");
		
		amIOnline::$done = true;
		amIOnline::$timer->cancel();
		
		$connection->close();
    });
	
	$session->publish('it.furtmeier.supportbox.'.$serial, [util::message("amihere")], [], ["acknowledge" => true]);
	
});

$connection->open();

if(amIOnline::$done){
	#util::log("Shutdown.");
	exit(0);
}

util::log("EXPLODE!!!");

exit(0);