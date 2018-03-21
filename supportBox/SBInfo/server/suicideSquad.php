<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";
require_once __DIR__."/OnAction.php";

$C = util::dbConnection();
$cloud = util::cloud($C);
$connection = util::init($C);
$C->close();

exec("kill -9 $argv[1]");

$connection->on('open', function (\Thruway\ClientSession $session) use ($argv,  $cloud, $connection) {
	$session->publish('it.furtmeier.supportbox.'.strtolower($cloud), [util::message("disconnected", $argv[2])], [], ["acknowledge" => true]);
	$connection->close();
});

$connection->open();
exit(0);