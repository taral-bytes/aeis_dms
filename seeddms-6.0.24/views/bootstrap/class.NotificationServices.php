<?php
/**
 * Implementation of Notification Services view
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
 * Class which outputs the html page for Notification Services view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_NotificationServices extends SeedDMS_Theme_Style {

	/**
	 * List all registered hooks
	 *
	 */
	function list_notification_services($notifier) { /* {{{ */
		if(!$notifier)
			return;

		$services = $notifier->getServices();

		echo "<table class=\"table table-condensed table-sm\">\n";
		echo "<thead>";
		echo "<tr><th>".getMLText('service_name')."</th><th>".getMLText('class_name')."</th><th>".getMLText('service_has_filter')."</th></tr>\n";
		echo "</thead>";
		echo "<tbody>";
		foreach($services as $name=>$service) {
			echo "<tr><td>".$name."</td><td>".get_class($service)."</td><td>".(is_callable([$service, 'filter']) ? '<i class="fa fa-check"></i>' : '')."</td></tr>";
		}
		echo "</tbody>";
		echo "</table>\n";
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$notifier = $this->params['notifier'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("list_notification_services"));

		self::list_notification_services($notifier);

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}

