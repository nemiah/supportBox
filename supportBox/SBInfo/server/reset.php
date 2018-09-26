<?php
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/SBUtil.php";

$C = SBUtil::dbConnection();
if(mysqli_connect_error()){
	echo mysqli_connect_error();
	die();
}

echo "Datenbank-Verbindung aufgebaut…\n";

$Q = $C->query("SHOW TABLES;");
while($R = $Q->fetch_object()){
	echo "Lösche Tabelle ".$R->Tables_in_supportbox.": ";
	$C->query("DROP TABLE ".$R->Tables_in_supportbox);
	if($C->error)
		echo $C->error;
	else
		echo "OK";
	echo "\n";
}

$aliases = "postmaster:    root
root: pi
";

echo "Setze Postfix alias zurück\n";
exec("echo \"$aliases\" | sudo tee /etc/aliases > /dev/null");
exec("sudo postalias /etc/aliases");

echo "Entferne Keys\n";
exec("sudo -u pi rm /home/pi/.ssh/id_rsa");
exec("sudo -u pi rm /home/pi/.ssh/id_rsa.pub");

exec("sudo rm /etc/supervisor/conf.d/autossh*.conf");

echo "Setze Postfix relayhost zurück\n";
exec("echo \"\" | sudo tee /etc/postfix/sasl_passwd  > /dev/null");
exec("sudo postmap /etc/postfix/sasl_passwd");

echo "Leere sessions-Verzeichnis\n";
exec("sudo bash -c 'rm /var/lib/php/sessions/sess_*'");

echo "Setze Berechtigungen…\n";
exec("sudo chmod 666 /var/www/html/system/DBData/Installation.pfdb.php");

echo "Setze Passwort von Benutzer 'pi' auf 'pi'\n";
shell_exec('echo "pi:pi" | sudo /usr/sbin/chpasswd');

echo "Starte Dienste neu…\n";
exec("sudo /usr/bin/supervisorctl reload");
exec("sudo /usr/bin/supervisorctl restart all");

echo "Zurücksetzen abgeschlossen!\n";