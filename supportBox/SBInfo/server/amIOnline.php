<?php

require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";
require_once __DIR__."/../../../classes/backend/UnpersistentClass.class.php";
require_once __DIR__."/../SBInfo.class.php";

$C = util::dbConnection();
$Q = $C->query("SELECT * FROM Userdata WHERE UserID = -1 AND name = 'SBCloud'");
if(!$Q)
	exit();

$R = $Q->fetch_object();
if(!$R)
	exit();

$url = SBInfo::$server."/ubiquitous/CustomerPage/?D=supportBox/SBDevice&cloud=".$R->wert."&method=amIOnline&serial=".SBInfo::serial();
$data = file_get_contents($url);
if($data === null)
	exit();

$data = json_decode($data);
#if($data->status == "OK")
#	exit();

exec("sudo /usr/bin/supervisorctl restart all", $output);

var_dump($output);

sleep(10);

$data = file_get_contents($url);
if($data === null)
	exit();


$data = json_decode($data);
print_r($data);