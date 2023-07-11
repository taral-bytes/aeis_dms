<?php
//    SeedDMS (Formerly MyDMS) Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

require("inc/inc.Settings.php");

if(true) {
	require_once("inc/inc.Utils.php");
	require_once("inc/inc.LogInit.php");
	require_once("inc/inc.Language.php");
	require_once("inc/inc.Init.php");
	require_once("inc/inc.Extension.php");
	require_once("inc/inc.DBInit.php");

	$c = new \Slim\Container(); //Create Your container
	$c['notFoundHandler'] = function ($c) use ($settings, $dms) {
		return function ($request, $response) use ($c, $settings, $dms) {
			$uri = $request->getUri();
			if($uri->getBasePath())
				$file = $uri->getPath();
			else
				$file = substr($uri->getPath(), 1);
			if(file_exists($file) && is_file($file)) {
				$_SERVER['SCRIPT_FILENAME'] = basename($file);
//				include($file);
				exit;
			}
			if($request->isXhr()) {
				exit;
			}
//			print_r($request->getUri());
//			exit;
			return $c['response']
				->withStatus(302)
				->withHeader('Location', isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_httpRoot.$settings->_siteDefaultPage : $settings->_httpRoot."out/out.ViewFolder.php");
		};
	};
	$app = new \Slim\App($c);
	$container = $app->getContainer();
	$container['dms'] = $dms;
	$container['config'] = $settings;
	$container['conversionmgr'] = $conversionmgr;
	$container['logger'] = $logger;
	$container['fulltextservice'] = $fulltextservice;
	$container['notifier'] = $notifier;
	$container['authenticator'] = $authenticator;

	if(isset($GLOBALS['SEEDDMS_HOOKS']['initDMS'])) {
		foreach($GLOBALS['SEEDDMS_HOOKS']['initDMS'] as $hookObj) {
			if (method_exists($hookObj, 'addRoute')) {
				$hookObj->addRoute(array('dms'=>$dms, 'app'=>$app, 'settings'=>$settings, 'conversionmgr'=>$conversionmgr, 'authenticator'=>$authenticator, 'fulltextservice'=>$fulltextservice, 'logger'=>$logger));
//			} else {
//				include("inc/inc.Authentication.php");
//				if (method_exists($hookObj, 'addRouteAfterAuthentication')) {
//					$hookObj->addRouteAfterAuthentication(array('dms'=>$dms, 'app'=>$app, 'settings'=>$settings, 'user'=>$user));
//				}
			}
		}
	}

	/*
	$app->get('/out/[{path:.*}]', function($request, $response, $path = null) use ($app) {
		$uri = $request->getUri();
		if($uri->getBasePath())
			$file = $uri->getPath();
		else
			$file = substr($uri->getPath(), 1);
		if(file_exists($file) && is_file($file)) {
			$_SERVER['SCRIPT_FILENAME'] = basename($file);
			include($file);
			exit;
		}
	});
	 */

	$app->run();
} else {

	header("Location: ". (isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php"));
?>
<html>
<head>
	<title>SeedDMS</title>
</head>

<body>


</body>
</html>
<?php } ?>
