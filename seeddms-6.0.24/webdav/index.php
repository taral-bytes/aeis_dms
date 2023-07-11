<?php
include("../inc/inc.Settings.php");

require_once("Log.php");
require_once("../inc/inc.Language.php");
require_once("../inc/inc.Utils.php");

$logger = getLogger('webdav-');

require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassNotificationService.php");
require_once("../inc/inc.ClassEmailNotify.php");
require_once("../inc/inc.Notification.php");
require_once("../inc/inc.ClassController.php");

include("webdav.php");
$server = new HTTP_WebDAV_Server_SeedDMS();
$server->ServeRequest($dms, $settings, $logger, $notifier, $authenticator);
//$files = array();
//$options = array('path'=>'/Test1/subdir', 'depth'=>1);
//echo $server->MKCOL(&$options);

?>
