<?php
require '/home/pi/thruway/vendor/autoload.php';
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/SBUtil.php";
require_once __DIR__."/OnAction.php";

$C = SBUtil::dbConnection();
$cloud = SBUtil::cloud($C);
$connection = SBUtil::init($C);
$C->close();

exec("kill -9 $argv[1]");

$connection->on('open', function (\Thruway\ClientSession $session) use ($argv,  $cloud, $connection) {
	$session->publish('it.furtmeier.supportbox.'.strtolower($cloud), [SBUtil::message("disconnected", $argv[2])], [], ["acknowledge" => true]);
	$connection->close();
});

$connection->open();
exit(0);