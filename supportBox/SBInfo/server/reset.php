<?php
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/util.php";

$C = util::dbConnection();
$Q = $C->query("SHOW TABLES;");
while($R = $Q->fetch_object())
	$C->query("DROP TABLE ".$R->Tables_in_supportbox);


$aliases = "postmaster:    root
root: pi
";

exec("echo \"$aliases\" | sudo tee /etc/aliases > /dev/null");
exec("sudo postalias /etc/aliases");

exec("sudo -u pi rm /home/pi/.ssh/id_rsa");
exec("sudo -u pi rm /home/pi/.ssh/id_rsa.pub");

exec("echo \"\" | sudo tee /etc/postfix/sasl_passwd  > /dev/null");
exec("sudo postmap /etc/postfix/sasl_passwd");

exec("sudo /usr/bin/supervisorctl restart all");