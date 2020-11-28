<?php
require_once "/var/www/html/open3A/current/applications/open3AApplication.class.php";

$c = new open3AApplication();
echo $c->registerVersion();