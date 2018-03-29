<?php
require_once __DIR__."/../../../libraries/PhpFileDB.class.php";
require_once __DIR__."/SBUtil.php";
require_once __DIR__."/OnAction.php";

$C = SBUtil::dbConnection();

$C->query("ALTER TABLE `SBForward` ADD `SBForwardURLAppend` VARCHAR(200) NOT NULL AFTER `SBForwardPort`");

echo "Done!";