<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
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

require_once('inc.ClassUI_Default.php');
require_once('inc.ClassViewCommon.php');
require_once('inc.ClassAccessOperation.php');

/* $theme was possibly set in inc.Authentication.php */
if (!isset($theme) || strlen($theme)==0) {
	$theme = $settings->_theme;
}
if (strlen($theme)==0) {
	$theme="bootstrap";
}

/* Sooner or later the parent will be removed, because all output will
 * be done by the new view classes.
 */
class UI extends UI_Default {
	/**
	 * Create a view from a class in the given theme
	 *
	 * This method will check for a class file in the theme directory
	 * and returns an instance of it.
	 *
	 * @param string $theme theme
	 * @param string $class name of view class
	 * @param array $params parameter passed to constructor of view class
	 * @return object an object of a class implementing the view
	 */
	static function factory($theme, $class='', $params=array()) { /* {{{ */
		global $settings, $dms, $user, $session, $extMgr, $request;
		if(!$class) {
			$class = 'Bootstrap';
			$class = 'Style';
			$classname = "SeedDMS_Bootstrap_Style";
		} else {
			$classname = "SeedDMS_View_".$class;
		}
		/* Collect all decorators */
		$decorators = array();
		foreach($extMgr->getExtensionConfiguration() as $extname=>$extconf) {
			if(!$settings->extensionIsDisabled($extname)) {
				if($extMgr->checkExtensionByName($extname, $extconf)) {
					if(isset($extconf['decorators'][$class])) {
						$filename = $settings->_rootDir.'ext/'.$extname.'/decorators/'.$theme."/".$extconf['decorators'][$class]['file'];
						if(file_exists($filename)) {
							$decorators[$extname] = $extconf['decorators'][$class];
						}
					}
				}
			}
		}
		/* Do not check for class file anymore but include it relative
		 * to rootDir or an extension dir if it has been set the include path
		 */
		$filename = '';
		$httpbasedir = '';
		foreach($extMgr->getExtensionConfiguration() as $extname=>$extconf) {
			if(!$settings->extensionIsDisabled($extname)) {
				if($extMgr->checkExtensionByName($extname, $extconf)) {
					/* Setting the 'views' element in the configuration can be used to
					 * replace an existing view in views/bootstrap/, e.g. class.ViewFolder.php
					 * without providing an out/out.ViewFolder.php. In that case $httpbasedir
					 * will not be set because out/out.xxx.php is still used.
					 */
					if(isset($extconf['views'][$class])) {
						$filename = $settings->_rootDir.'ext/'.$extname.'/views/'.$theme."/".$extconf['views'][$class]['file'];
						if(file_exists($filename)) {
	//						$httpbasedir = 'ext/'.$extname.'/';
							$classname = $extconf['views'][$class]['name'];
							break;
						}
					}
					/* New views are added by creating a file out/out.xx.php and
					 * views/bootstrap/class.xx.php, without setting the 'views' element
					 * in the configuration
					 */
					$filename = $settings->_rootDir.'ext/'.$extname.'/views/'.$theme."/class.".$class.".php";
					if(file_exists($filename)) {
						$httpbasedir = 'ext/'.$extname.'/';
						break;
					} else {
						$filename = $settings->_rootDir.'ext/'.$extname.'/views/bootstrap/class.'.$class.".php";
						if(file_exists($filename)) {
							$httpbasedir = 'ext/'.$extname.'/';
							break;
						}
					}
					$filename = '';
				}
			}
		}
		if(!$filename)
			$filename = $settings->_rootDir."views/".$theme."/class.".$class.".php";
		/* Fall back onto the view class in bootstrap theme */
		if(!file_exists($filename))
			$filename = $settings->_rootDir."views/bootstrap/class.".$class.".php";
		if(!file_exists($filename))
			$filename = '';
		if($filename) {
			/* Always include the base class which defines class SeedDMS_Theme_Style */
			require_once($settings->_rootDir."views/".$theme."/class.".ucfirst($theme).".php");
			require_once($filename);
			$params['settings'] = $settings;
			$view = new $classname($params, $theme);
			/* Set some configuration parameters */
			$view->setParam('accessobject', new SeedDMS_AccessOperation($dms, $user, $settings));
			$view->setParam('refferer', $_SERVER['REQUEST_URI']);
			$view->setParam('absbaseprefix', $settings->_httpRoot.$httpbasedir);
			$view->setParam('theme', $theme);
			$view->setParam('class', $class);
			$view->setParam('session', $session);
			$view->setParam('request', $request);
//			$view->setParam('settings', $settings);
			$view->setParam('sitename', $settings->_siteName);
			$view->setParam('rootfolderid', $settings->_rootFolderID);
			$view->setParam('disableselfedit', $settings->_disableSelfEdit);
			$view->setParam('enableusersview', $settings->_enableUsersView);
			$view->setParam('enablecalendar', $settings->_enableCalendar);
			$view->setParam('calendardefaultview', $settings->_calendarDefaultView);
			$view->setParam('enablefullsearch', $settings->_enableFullSearch);
			$view->setParam('enablehelp', $settings->_enableHelp);
			$view->setParam('enablelargefileupload', $settings->_enableLargeFileUpload);
			$view->setParam('printdisclaimer', $settings->_printDisclaimer);
			$view->setParam('footnote', $settings->_footNote);
			$view->setParam('logfileenable', $settings->_logFileEnable);
			$view->setParam('expandfoldertree', $settings->_expandFolderTree);
			$view->setParam('enablefoldertree', $settings->_enableFolderTree);
			$view->setParam('enablelanguageselector', $settings->_enableLanguageSelector);
			$view->setParam('enableclipboard', $settings->_enableClipboard);
			$view->setParam('enablemenutasks', $settings->_enableMenuTasks);
			$view->setParam('tasksinmenu', $settings->_tasksInMenu);
			$view->setParam('enabledropfolderlist', $settings->_enableDropFolderList);
			$view->setParam('dropfolderdir', $settings->_dropFolderDir);
			$view->setParam('enablesessionlist', $settings->_enableSessionList);
			$view->setParam('workflowmode', $settings->_workflowMode);
			$view->setParam('checkoutdir', $settings->_checkOutDir);
			$view->setParam('partitionsize', SeedDMS_Core_File::parse_filesize( $settings->_partitionSize));
			$view->setParam('maxuploadsize', $settings->getMaximumUploadSize());
			$view->setParam('showmissingtranslations', $settings->_showMissingTranslations);
			$view->setParam('defaultsearchmethod', $settings->_defaultSearchMethod);
			$view->setParam('cachedir', $settings->_cacheDir);
			$view->setParam('onepage', $settings->_onePageMode);
			foreach($decorators as $extname=>$decorator) {
				$filename = $settings->_rootDir.'ext/'.$extname.'/decorators/'.$theme."/".$decorator['file'];
				require_once($filename);
				$view = new $decorator['name']($view);
			}
			return $view;
		}
		return null;
	} /* }}} */

	static function getStyles() { /* {{{ */
		global $settings;

		$themes = array();
		$path = $settings->_rootDir . "views/";
		$handle = opendir($path);

		while ($entry = readdir($handle) ) {
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($path . $entry) || is_link($path . $entry))
				array_push($themes, $entry);
		}
		closedir($handle);
		return $themes;
	} /* }}} */

	static function exitError($pagetitle, $error, $noexit=false, $plain=false) {
		global $theme, $dms, $user, $settings;
		$view = UI::factory($theme, 'ErrorDlg');
		$request = $view->getParam('request');
		if($request) {
			$request->query->set('action', 'show');
		}
		$view->setParam('dms', $dms);
		$view->setParam('user', $user);
		$view->setParam('pagetitle', $pagetitle);
		$view->setParam('errormsg', $error);
		$view->setParam('plain', $plain);
		$view();
		if($noexit)
			return;
		exit;
	}
}
