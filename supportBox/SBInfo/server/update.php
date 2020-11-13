<?php
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/SBUtil.php";
require_once __DIR__."/OnAction.php";

$C = SBUtil::dbConnection();

/*$C->query("ALTER TABLE `SBForward` ADD `SBForwardURLAppend` VARCHAR(200) NOT NULL AFTER `SBForwardPort`");

$C->query("CREATE TABLE `SBNetwork` (
  `SBNetworkID` int(10) NOT NULL,
  `SBNetworkInterface` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `SBNetworkMode` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `SBNetworkIP` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `SBNetworkSubnet` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `SBNetworkGateway` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `SBNetworkDNS` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
$C->query("ALTER TABLE `SBNetwork`
  ADD PRIMARY KEY (`SBNetworkID`);");
$C->query("ALTER TABLE `SBNetwork`
  MODIFY `SBNetworkID` int(10) NOT NULL AUTO_INCREMENT;");

exec("echo \"". file_get_contents(__DIR__."/visudo")."\" | sudo tee /etc/sudoers.d/020_supportbox > /dev/null");



$C->query("CREATE TABLE `SBTunnel` (
  `SBTunnelID` int(10) NOT NULL,
  `SBTunnelName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `SBTunnelServerPort` int(10) NOT NULL,
  `SBTunnelIP` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `SBTunnelPort` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
$C->query("ALTER TABLE `SBTunnel`
  ADD PRIMARY KEY (`SBTunnelID`);");
$C->query("ALTER TABLE `SBTunnel`
  MODIFY `SBTunnelID` int(10) NOT NULL AUTO_INCREMENT;");

$C->query("ALTER TABLE `SBTunnel` ADD `SBTunnelAktiv` TINYINT(1) NOT NULL AFTER `SBTunnelName`;");*/

echo "Done!";
