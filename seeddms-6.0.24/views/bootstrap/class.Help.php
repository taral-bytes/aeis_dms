<?php
/**
 * Implementation of Help view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Help view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Help extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$context = $this->params['context'];

		$this->htmlStartPage(getMLText("help"));
		$this->globalNavigation();
		$this->contentStart();
//		$this->pageNavigation(getMLText("help").": ".getMLText('help_'.strtolower($context), array(), $context), "");
		$this->rowStart();
		$this->columnStart(4);
?>
	<legend>Table of contents</legend>
<?php
		$dir = "../languages/".$this->params['session']->getLanguage()."/help";
		$d = dir($dir);
		echo "<ul>";
		while (false !== ($entry = $d->read())) {
			if($entry != '..' && $entry != '.') {
				$path_parts = pathinfo($dir."/".$entry);
				if(isset($path_parts['extension']) && ($path_parts['extension'] == 'html' || $path_parts['extension'] == 'md')) {
					echo "<li><a href=\"../out/out.Help.php?context=".$path_parts['filename']."\">".getMLText('help_'.$path_parts['filename'], array(), $path_parts['filename'])."</a></li>";
				}
			}
		}
		echo "</ul>";
		$this->columnEnd();
		$this->columnStart(8);
?>
<legend><?= htmlspecialchars(getMLText('help_'.strtolower($context), array(), $context)); ?></legend>
<?php

		$helpfile = "../languages/".$this->params['session']->getLanguage()."/help/".$context.".html";
		if(file_exists($helpfile))
			readfile($helpfile);
		else {
			$helpfile = "../languages/".$this->params['session']->getLanguage()."/help/".$context.".md";
			if(file_exists($helpfile)) {
				require_once('parsedown/Parsedown.php');
				$Parsedown = new Parsedown();
				echo $Parsedown->text(file_get_contents($helpfile));
			} else
			readfile("../languages/".$this->params['session']->getLanguage()."/help.htm");
		}

		$this->columnEnd();
		$this->rowEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
