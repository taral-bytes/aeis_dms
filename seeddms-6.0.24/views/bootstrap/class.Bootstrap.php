<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2009-2012 Uwe Steinmann
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


class SeedDMS_Theme_Style extends SeedDMS_View_Common {
	/**
	 * @var string $extraheader extra html code inserted in the html header
	 * of the page
	 *
	 * @access protected
	 */
	protected $extraheader;

	function __construct($params, $theme='bootstrap') {
		parent::__construct($params, $theme);
		$this->extraheader = array('js'=>'', 'css'=>'', 'favicon'=>'', 'logo'=>'', 'logolink'=>'');
		$this->footerjs = array();
		$this->nonces = array();
	}

	/**
	 * Add javascript to an internal array which is output at the
	 * end of the page within a document.ready() function.
	 *
	 * @param string $script javascript to be added
	 */
	function addFooterJS($script) { /* {{{ */
		$this->footerjs[] = $script;
	} /* }}} */

	function htmlStartPage($title="", $bodyClass="", $base="", $httpheader=array()) { /* {{{ */
		if(1 || method_exists($this, 'js')) {
			/* We still need unsafe-eval, because printDocumentChooserHtml and
			 * printFolderChooserHtml will include a javascript file with ajax
			 * which is evaluated by jquery
			 * worker-src blob: is needed for cytoscape
			 * X-WebKit-CSP is deprecated, Chrome understands Content-Security-Policy
			 * since version 25+
			 * X-Content-Security-Policy is deprecated, Firefox understands
			 * Content-Security-Policy since version 23+
			 * 'worker-src blob:' is needed for cytoscape
			 */
			$csp_rules = [];
			$csp_rule = "script-src 'self' 'unsafe-eval'";
			if($this->nonces) {
				$csp_rule .= " 'nonce-".implode("' 'nonce-", $this->nonces)."'";
			}
			$csp_rules[] = $csp_rule;
			$csp_rules[] = "worker-src blob:";
			//$csp_rules[] = "style-src 'self'";
			/* Do not allow to embed myself into frames on foreigns pages */
			$csp_rules[] = "frame-ancestors 'self'";
			if($this->hasHook('getCspRules')) {
				$csp_rules = $this->callHook('getCspRules', $csp_rules);
			}
			foreach (array("X-WebKit-CSP", "X-Content-Security-Policy", "Content-Security-Policy") as $csp) {
				header($csp . ": " . implode('; ', $csp_rules).';');
			}
		}
		header('X-Content-Type-Options: nosniff');
		header('Strict-Transport-Security: max-age=15768000; includeSubDomains; preload');
		if($httpheader) {
			foreach($httpheader as $name=>$value) {
				header($name . ": " . $value);
			}
		}
		if($this->hasHook('startPage'))
				$this->callHook('startPage');
		echo "<!DOCTYPE html>\n";
		echo "<html lang=\"";
		if($this->params['session'] && ($slang = $this->params['session']->getLanguage())) {
			echo str_replace('_', '-', $slang);
		} else {
			echo str_replace('_', '-', $this->params['settings']->_language);
		}
		echo "\">\n<head>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n";
		if($base)
			echo '<base href="'.$base.'">'."\n";
		elseif($this->baseurl)
			echo '<base href="'.$this->baseurl.'">'."\n";
		$sitename = trim(strip_tags($this->params['sitename']));
		if($this->params['session'])
			echo '<link rel="search" type="application/opensearchdescription+xml" href="'.$this->params['settings']->_httpRoot.'out/out.OpensearchDesc.php" title="'.(strlen($sitename)>0 ? $sitename : "").'"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/bootstrap/css/bootstrap.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/bootstrap/css/bootstrap-responsive.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootstrap-datepicker/css/bootstrap-datepicker.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/chosen/css/chosen.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/select2/css/select2.min.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/select2/css/select2-bootstrap.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/jqtree/jqtree.css" rel="stylesheet"/>'."\n";
		echo '<link href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/styles/application.css" rel="stylesheet"/>'."\n";
		if($this->extraheader['css'])
			echo $this->extraheader['css'];
		if(method_exists($this, 'css'))
			echo '<link href="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?action=css'.(!empty($_SERVER['QUERY_STRING']) ? '&'.$_SERVER['QUERY_STRING'] : '').'" rel="stylesheet"/>'."\n";

		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/jquery/jquery.min.js"></script>'."\n";
		if($this->extraheader['js'])
			echo $this->extraheader['js'];
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/passwordstrength/jquery.passwordstrength.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/noty/jquery.noty.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/noty/layouts/topRight.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/noty/layouts/topCenter.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/noty/themes/default.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/jqtree/tree.jquery.js"></script>'."\n";
		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootbox/bootbox.min.js"></script>'."\n";
//		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootbox/bootbox.min.js"></script>'."\n";
//		echo '<script type="text/javascript" src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootbox/bootbox.locales.js"></script>'."\n";
		if(!empty($this->extraheader['favicon']))
			echo $this->extraheader['favicon'];
		else {
			echo '<link rel="icon" href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/images/favicon.svg" type="image/svg+xml"/>'."\n";
			echo '<link rel="apple-touch-icon" sizes="180x180" href="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/images/apple-touch-icon.png">'."\n";
		}
		if($this->params['session'] && $this->params['session']->getSu()) {
?>
<style type="text/css">
.navbar-inverse .navbar-inner {
background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#882222), to(#111111));
background-image: webkit-linear-gradient(top, #882222, #111111);
background-image: linear-gradient(to bottom, #882222, #111111);;
}
</style>
<?php
		}
		echo "<title>".(strlen($sitename)>0 ? $sitename : "SeedDMS").(strlen($title)>0 ? ": " : "").htmlspecialchars($title)."</title>\n";
		echo "</head>\n";
		echo "<body".(strlen($bodyClass)>0 ? " class=\"".$bodyClass."\"" : "").">\n";
		if($this->params['session'] && $flashmsg = $this->params['session']->getSplashMsg()) {
			$this->params['session']->clearSplashMsg();
			echo "<div class=\"splash\" data-type=\"".$flashmsg['type']."\"".(!empty($flashmsg['timeout']) ? ' data-timeout="'.$flashmsg['timeout'].'"': '').">".$flashmsg['msg']."</div>\n";
		}
		echo "<div class=\"statusbar-container\"><h1>".getMLText('recent_uploads')."</h1></div>\n";
		if($this->hasHook('startBody'))
				$this->callHook('startBody');
	} /* }}} */

	function htmlAddHeader($head, $type='js') { /* {{{ */
		if($type == 'logo' || $type == 'favicon' || $type == 'logolink')
			$this->extraheader[$type] = $head;
		else
			$this->extraheader[$type] .= $head;
	} /* }}} */

	function htmlAddJsHeader($script) { /* {{{ */
		$nonce = createNonce();
		$this->nonces[] = $nonce;
		$this->extraheader['js'] .= '<script type="text/javascript" src="'.$script.'" nonce="'.$nonce.'"></script>'."\n";
	} /* }}} */

	function htmlEndPage($nofooter=false) { /* {{{ */
		if(!$nofooter) {
			$html = $this->footNote();
			if($this->hasHook('footNote'))
				$html = $this->callHook('footNote', $html);
			echo $html;
			if($this->params['showmissingtranslations']) {
				$this->missingLanguageKeys();
			}
		}
		echo '<script src="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/bootstrap/js/bootstrap.min.js"></script>'."\n";
		echo '<script src="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/bootstrap/js/bootstrap-typeahead.js"></script>'."\n";
		echo '<script src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>'."\n";
		foreach(array('de', 'es', 'ar', 'el', 'bg', 'ru', 'hr', 'hu', 'ko', 'pl', 'ro', 'sk', 'tr', 'uk', 'ca', 'nl', 'fi', 'cs', 'it', 'fr', 'sv', 'sl', 'pt-BR', 'zh-CN', 'zh-TW') as $lang)
			echo '<script src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/bootstrap-datepicker/locales/bootstrap-datepicker.'.$lang.'.min.js"></script>'."\n";
		echo '<script src="'.$this->params['settings']->_httpRoot.'styles/'.$this->theme.'/chosen/js/chosen.jquery.min.js"></script>'."\n";
		echo '<script src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/vendors/select2/js/select2.min.js"></script>'."\n";
		parse_str($_SERVER['QUERY_STRING'], $tmp);
		$tmp['action'] = 'webrootjs';
		if(isset($tmp['formtoken']))
			unset($tmp['formtoken']);
		if(isset($tmp['referuri']))
			unset($tmp['referuri']);
		if(!empty($this->params['class']))
			echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.htmlentities(http_build_query($tmp)).'"></script>'."\n";
		echo '<script src="'.$this->params['settings']->_httpRoot.'views/'.$this->theme.'/styles/application.js"></script>'."\n";
		if($this->params['enablemenutasks'] && isset($this->params['user']) && $this->params['user']) {
			$this->addFooterJS('SeedDMSTask.run();');
		}
		if($this->params['enabledropfolderlist'] && isset($this->params['user']) && $this->params['user']) {
			$this->addFooterJS("SeedDMSTask.add({name: 'dropfolder', interval: 30, func: function(){\$('#menu-dropfolder > div.ajax').trigger('update', {folderid: seeddms_folder});}});");
		}
		if($this->footerjs) {
			$jscode = "$(document).ready(function () {\n";
			foreach($this->footerjs as $script) {
				$jscode .= $script."\n";
			}
			$jscode .= "});\n";
			$hashjs = md5($jscode);
			if(!is_dir($this->params['cachedir'].'/js')) {
				SeedDMS_Core_File::makeDir($this->params['cachedir'].'/js');
			}
			if(is_dir($this->params['cachedir'].'/js')) {
				file_put_contents($this->params['cachedir'].'/js/'.$hashjs.'.js', $jscode);
			}
			$tmp['action'] = 'footerjs';
			$tmp['hashjs'] = $hashjs;
			echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.htmlentities(http_build_query($tmp)).'"></script>'."\n";
		}
		if(method_exists($this, 'js')) {
			parse_str($_SERVER['QUERY_STRING'], $tmp);
			$tmp['action'] = 'js';
			echo '<script src="'.$this->params['absbaseprefix'].'out/out.'.$this->params['class'].'.php?'.htmlentities(http_build_query($tmp)).'"></script>'."\n";
		}
		echo "</body>\n</html>\n";
	} /* }}} */

	function webrootjs() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
		echo "var seeddms_absbaseprefix=\"".$this->params['absbaseprefix']."\";\n";
		echo "var seeddms_webroot=\"".$this->params['settings']->_httpRoot."\";\n";
		/* Place the current folder id in a js variable, just in case some js code
		 * needs it, e.g. for reloading parts of the page via ajax.
		 */
		if(!empty($_REQUEST['folderid']))
			echo "var seeddms_folder=".(int) $_REQUEST['folderid'].";\n";
		else
			echo "var seeddms_folder=0;\n";
	} /* }}} */

	function footerjs() { /* {{{ */
		header('Content-Type: application/javascript');
		if(file_exists($this->params['cachedir'].'/js/'.$_GET['hashjs'].'.js')) {
			readfile($this->params['cachedir'].'/js/'.$_GET['hashjs'].'.js');
		}
	} /* }}} */

	function missingLanguageKeys() { /* {{{ */
		global $MISSING_LANG, $LANG;
		if($MISSING_LANG) {
			echo '<div class="container-fluid">'."\n";
			$this->rowStart();
			$this->columnStart(12);
			echo $this->errorMsg("This page contains missing translations in the selected language. Please help to improve SeedDMS and provide the translation.");
			echo "<table class=\"table table-condensed\">";
			echo "<tr><th>Key</th><th>engl. Text</th><th>Your translation</th></tr>\n";
			foreach($MISSING_LANG as $key=>$lang) {
				echo "<tr><td>".htmlspecialchars($key)."</td><td>".(isset($LANG['en_GB'][$key]) ? $LANG['en_GB'][$key] : '')."</td><td><div class=\"input-append send-missing-translation\"><input name=\"missing-lang-key\" type=\"hidden\" value=\"".$key."\" /><input name=\"missing-lang-lang\" type=\"hidden\" value=\"".$lang."\" /><input type=\"text\" class=\"input-xxlarge\" name=\"missing-lang-translation\" placeholder=\"Your translation in '".$lang."'\"/><a class=\"btn\">Submit</a></div></td></tr>";
			}
			echo "</table>";
			echo "<div class=\"splash\" data-type=\"error\" data-timeout=\"5500\"><b>There are missing translations on this page!</b><br />Please check the bottom of the page.</div>\n";
			echo "</div>\n";
			$this->columnEnd();
			$this->rowEnd();
		}
	} /* }}} */

	function footNote() { /* {{{ */
		$html = "<div class=\"container-fluid\">\n";
		$html .= '<div class="row-fluid">'."\n";
		$html .= '<div class="span12">'."\n";
		$html .= '<div class="alert alert-info">'."\n";
		if ($this->params['printdisclaimer']){
			$html .= "<div class=\"disclaimer\">".getMLText("disclaimer")."</div>";
		}

		if (isset($this->params['footnote']) && strlen((string)$this->params['footnote'])>0) {
			$html .= "<div class=\"footNote\">".(string)$this->params['footnote']."</div>";
		}
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";
	
		return $html;
	} /* }}} */

	function contentStart() { /* {{{ */
		echo "<main role=\"main\" class=\"container-fluid\">\n";
		echo " <div class=\"row-fluid\">\n";
	} /* }}} */

	function contentEnd() { /* {{{ */
		echo " </div>\n";
		echo "</main>\n";
	} /* }}} */

	function globalBanner() { /* {{{ */
		echo "<div class=\"navbar navbar-inverse navbar-fixed-top\">\n";
		echo " <div class=\"navbar-inner\">\n";
		echo "  <div class=\"container-fluid\">\n";
		echo "   <a href=\"".(!empty($this->extraheader['logolink']) ? $this->extraheader['logolink'] : $this->params['settings']->_httpRoot."out/out.ViewFolder.php")."\">".(!empty($this->extraheader['logo']) ? '<img id="navbar-logo" src="'.$this->extraheader['logo'].'"/>' : '<img id="navbar-logo" src="'.$this->params['settings']->_httpRoot.'views/bootstrap/images/seeddms-logo.svg"/>')."</a>";
		echo "   <a class=\"brand\" href=\"".(!empty($this->extraheader['logolink']) ? $this->extraheader['logolink'] : $this->params['settings']->_httpRoot."out/out.ViewFolder.php")."\">".(strlen($this->params['sitename'])>0 ? $this->params['sitename'] : "")."</a>\n";
		echo "  </div>\n";
		echo " </div>\n";
		echo "</div>\n";
	} /* }}} */

	/**
	 * Returns the html needed for the clipboard list in the menu
	 *
	 * This function renders the clipboard in a way suitable to be
	 * used as a menu
	 *
	 * @param array $clipboard clipboard containing two arrays for both
	 *        documents and folders.
	 * @return string html code
	 */
	function __menuTasks($tasks) { /* {{{ */
		$dms = $this->params['dms'];
		$accessobject = $this->params['accessobject'];
		$content = '';
//		$content .= "   <ul id=\"main-menu-tasks\" class=\"nav pull-right\">\n";
//		$content .= "    <li class=\"dropdown\">\n";
		$content .= "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText('tasks')." (".count($tasks['review'])."/".count($tasks['approval'])."/".count($tasks['receipt'])."/".count($tasks['revision']).") <i class=\"fa fa-caret-down\"></i></a>\n";
		$content .= "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
		if($tasks['review']) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_review")."</a>\n";
		$content .= "       <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['review'] as $t) {
			$doc = $dms->getDocument($t);
			$content .= "      <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revapp\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if($tasks['approval']) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_approve")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['approval'] as $t) {
			$doc = $dms->getDocument($t);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revapp\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if($tasks['receipt']) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_receipt")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['receipt'] as $t) {
			$doc = $dms->getDocument($t);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=recipients\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if($tasks['revision']) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_revise")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['revision'] as $t) {
			$doc = $dms->getDocument($t);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revision\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if ($accessobject->check_view_access('MyDocuments')) {
			$content .= "    <li class=\"divider\"></li>\n";
			$content .= "    <li><a href=\"../out/out.MyDocuments.php\">".getMLText("my_documents")."</a></li>\n";
		}
		$content .= "     </ul>\n";
//		$content .= "    </li>\n";
//		$content .= "   </ul>\n";
		return $content;
	} /* }}} */

	function globalNavigation($folder=null) { /* {{{ */
		$dms = $this->params['dms'];
		$accessobject = $this->params['accessobject'];
		echo "<div class=\"navbar navbar-inverse navbar-fixed-top\">\n";
		echo " <div class=\"navbar-inner\">\n";
		echo "  <div class=\"container-fluid\">\n";
		echo "   <a class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".nav-col1\">\n";
		echo "     <span class=\"fa fa-bars\"></span>\n";
		echo "   </a>\n";
		echo "   <a class=\"btn btn-navbar\" href=\"".$this->params['settings']->_httpRoot."op/op.Logout.php\">\n";
		echo "     <span class=\"fa fa-sign-out\"></span>\n";
		echo "   </a>\n";
		echo "   <a href=\"".(!empty($this->extraheader['logolink']) ? $this->extraheader['logolink'] : $this->params['settings']->_httpRoot."out/out.ViewFolder.php")."\">".(!empty($this->extraheader['logo']) ? '<img id="navbar-logo" src="'.$this->extraheader['logo'].'">' : '<img id="navbar-logo" src="'.$this->params['settings']->_httpRoot.'views/bootstrap/images/seeddms-logo.svg">')."</a>";
		echo "   <a class=\"brand\" href=\"".(!empty($this->extraheader['logolink']) ? $this->extraheader['logolink'] : $this->params['settings']->_httpRoot."out/out.ViewFolder.php")."\"><span class=\"hidden-phone\">".(strlen($this->params['sitename'])>0 ? $this->params['sitename'] : "")."</span></a>\n";

		/* user profile menu {{{ */
		if(isset($this->params['session']) && isset($this->params['user']) && $this->params['user']) {
			/* search form {{{ */
			echo "     <form action=\"".$this->params['settings']->_httpRoot."out/out.Search.php\" class=\"form-inline navbar-search pull-left\" autocomplete=\"off\">";
			if ($folder!=null && is_object($folder) && $folder->isType('folder')) {
				echo "      <input type=\"hidden\" name=\"folderid\" value=\"".$folder->getID()."\" />";
			}
			echo "      <input type=\"hidden\" name=\"navBar\" value=\"1\" />";
			echo "      <input name=\"query\" class=\"search-query\" ".($this->params['defaultsearchmethod'] == 'fulltext_' ? "" : "id=\"searchfield\"")." data-provide=\"typeahead\" type=\"search\" style=\"width: 150px;\" placeholder=\"".getMLText("search")."\"/>";
			if($this->params['defaultsearchmethod'] == 'fulltext')
				echo "      <input type=\"hidden\" name=\"fullsearch\" value=\"1\" />";
//			if($this->params['enablefullsearch']) {
//				echo "      <label class=\"checkbox\" style=\"color: #999999;\"><input type=\"checkbox\" name=\"fullsearch\" value=\"1\" title=\"".getMLText('fullsearch_hint')."\"/> ".getMLText('fullsearch')."</label>";
//			}
	//		echo "      <input type=\"submit\" value=\"".getMLText("search")."\" id=\"searchButton\" class=\"btn\"/>";
			echo "</form>\n";
			/* }}} End of search form */

			echo "   <div class=\"nav-collapse nav-col1\">\n";
			echo "   <ul id=\"main-menu-admin\" class=\"nav pull-right\">\n";
			echo "    <li class=\"dropdown\">\n";
			echo "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".($this->params['session']->getSu() ? getMLText("switched_to") : getMLText("signed_in_as"))." '".htmlspecialchars($this->params['user']->getFullName())."' <i class=\"fa fa-caret-down\"></i></a>\n";
			echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
//			if (!$this->params['user']->isGuest()) {
				$menuitems = array();
				if ($accessobject->check_view_access('Dashboard'))
					$menuitems['dashboard'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Dashboard.php", 'label'=>getMLText('dashboard'));
				if ($accessobject->check_view_access('MyDocuments'))
					$menuitems['my_documents'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MyDocuments.php", 'label'=>getMLText('my_documents'));
				if ($accessobject->check_view_access('MyAccount'))
					$menuitems['my_account'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MyAccount.php", 'label'=>getMLText('my_account'));
				if ($accessobject->check_view_access('TransmittalMgr'))
					$menuitems['my_transmittals'] = array('link'=>$this->params['settings']->_httpRoot."out/out.TransmittalMgr.php", 'label'=>getMLText('my_transmittals'));
				if($this->hasHook('userMenuItems'))
					$menuitems = $this->callHook('userMenuItems', $menuitems);
				if($menuitems) {
					foreach($menuitems as $menuitem) {
						echo "<li><a href=\"".$menuitem['link']."\">".$menuitem['label']."</a></li>";
					}
					echo "    <li class=\"divider\"></li>\n";
				}
//			}
			$showdivider = false;
			if($this->params['enablelanguageselector']) {
				$showdivider = true;
				echo "    <li class=\"dropdown-submenu\">\n";
				echo "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("language")."</a>\n";
				echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
				$languages = getLanguages();
				foreach ($languages as $currLang) {
					if($this->params['session']->getLanguage() == $currLang)
						echo "<li class=\"active\">";
					else
						echo "<li>";
					echo "<a href=\"".$this->params['settings']->_httpRoot."op/op.SetLanguage.php?lang=".$currLang."&referer=".$_SERVER["REQUEST_URI"]."\">";
					echo getMLText($currLang)."</a></li>\n";
				}
				echo "     </ul>\n";
				echo "    </li>\n";
			}
			if(!$this->params['session']->getSu()) {
				if($this->params['user']->isAdmin()) {
					$showdivider = true;
					echo "    <li><a href=\"".$this->params['settings']->_httpRoot."out/out.SubstituteUser.php\">".getMLText("substitute_user")."</a></li>\n";
				} elseif($substitutes = $this->params['user']->getReverseSubstitutes()) {
					if(count($substitutes) == 1) {
						echo "    <li><a href=\"".$this->params['settings']->_httpRoot."op/op.SubstituteUser.php?userid=".$substitutes[0]->getID()."&formtoken=".createFormKey('substituteuser')."\">".getMLText("substitute_to_user", array('username'=>$substitutes[0]->getFullName()))."</a></li>\n";
					} else {
						echo "    <li><a href=\"".$this->params['settings']->_httpRoot."out/out.SubstituteUser.php\">".getMLText("substitute_user")."</a></li>\n";
					}
				}
			}
			if($showdivider)
				echo "    <li class=\"divider\"></li>\n";
			if($this->params['session']->getSu()) {
				echo "    <li><a href=\"".$this->params['settings']->_httpRoot."op/op.ResetSu.php\">".getMLText("sign_out_user")."</a></li>\n";
			} else {
				echo "    <li><a href=\"".$this->params['settings']->_httpRoot."op/op.Logout.php\">".getMLText("sign_out")."</a></li>\n";
			}
			echo "     </ul>\n";
			echo "    </li>\n";
			echo "   </ul>\n";
			/* }}} End of user profile menu */

			/* menu tasks {{{ */
			if($this->params['enablemenutasks']) {
				if($accessobject->check_view_access('Tasks', array('action'=>'menuTasks'))) {
					echo "   <div id=\"menu-tasks\">";
					echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"Tasks\" data-action=\"menuTasks\"></div>";
					echo "   </div>";
				}
			}
			/* }}} End of menu tasks */

			/* drop folder dir {{{ */
			if($this->params['dropfolderdir'] && $this->params['enabledropfolderlist']) {
				echo "   <div id=\"menu-dropfolder\">";
				echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"DropFolderChooser\" data-action=\"menuList\"";
				if ($folder!=null && is_object($folder) && $folder->isType('folder'))
					echo " data-query=\"folderid=".$folder->getID()."\"";
				echo "></div>";
				echo "   </div>";
			}
			/* }}} End of drop folder dir */

			/* session list {{{ */
			if($this->params['enablesessionlist']) {
				echo "   <div id=\"menu-session\">";
				echo "     <div class=\"ajax\" data-no-spinner=\"true\" data-view=\"Session\" data-action=\"menuSessions\"></div>";
				echo "   </div>";
			}
			/* }}} End of session list */

			/* clipboard {{{ */
			if($this->params['enableclipboard']) {
				echo "   <div id=\"menu-clipboard\">";
				echo "     <div class=\"ajax add-clipboard-area\" data-no-spinner=\"true\" data-view=\"Clipboard\" data-action=\"menuClipboard\" data-query=\"folderid=".($folder != null ? $folder->getID() : 0)."\"></div>";
				echo "   </div>";
			}
			/* }}} End of clipboard */

			echo "   <ul class=\"nav\">\n";
			$menuitems = array();
			/* calendar {{{ */
			if ($this->params['enablecalendar'] && $accessobject->check_view_access('Calendar')) $menuitems['calendar'] = array('link'=>$this->params['settings']->_httpRoot.'out/out.Calendar.php?mode='.$this->params['calendardefaultview'], 'label'=>getMLText("calendar"));
			if ($accessobject->check_view_access('AdminTools')) $menuitems['admintools'] = array('link'=>$this->params['settings']->_httpRoot.'out/out.AdminTools.php', 'label'=>getMLText("admin_tools"));
			if($this->params['enablehelp']) {
				$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
				$menuitems['help'] = array('link'=>$this->params['settings']->_httpRoot.'out/out.Help.php?context='.$tmp[1], 'label'=>getMLText("help"));
			}
			/* }}} End of calendar */

			/* Check if hook exists because otherwise callHook() will override $menuitems */
			if($this->hasHook('globalNavigationBar'))
				$menuitems = $this->callHook('globalNavigationBar', $menuitems);
			foreach($menuitems as $menuitem) {
				if(!empty($menuitem['children'])) {
					echo "    <li class=\"dropdown\">\n";
					echo "     <a class=\"dropdown-toggle\" data-toggle=\"dropdown\">".$menuitem['label']." <i class=\"fa fa-caret-down\"></i></a>\n";
					echo "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
					foreach($menuitem['children'] as $submenuitem) {
						echo "      <li><a href=\"".$submenuitem['link']."\"".(isset($submenuitem['target']) ? ' target="'.$submenuitem['target'].'"' : '').">".$submenuitem['label']."</a></li>\n";
					}
					echo "     </ul>\n";
				} else {
					echo "<li><a href=\"".$menuitem['link']."\"".(isset($menuitem['target']) ? ' target="'.$menuitem['target'].'"' : '').">".$menuitem['label']."</a></li>";
				}
			}
			echo "   </ul>\n";
			echo "    </div>\n";
		}
		echo "  </div>\n";
		echo " </div>\n";
		echo "</div>\n";
		return;
	} /* }}} */

	function getFolderPathHTML($folder, $tagAll=false, $document=null) { /* {{{ */
		$path = $folder->getPath();
		$txtpath = "";
		for ($i = 0; $i < count($path); $i++) {
			$txtpath .= "<li>";
			if ($i+1 < count($path)) {
				$txtpath .= "<a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\" data-droptarget=\"folder_".$path[$i]->getID()."\" rel=\"folder_".$path[$i]->getID()."\" data-name=\"".htmlspecialchars($path[$i]->getName())."\" class=\"table-row-folder droptarget\" data-uploadformtoken=\"".createFormKey('')."\" formtoken=\"".createFormKey('')."\">".
					htmlspecialchars($path[$i]->getName())."</a>";
			}
			else {
				$txtpath .= ($tagAll ? "<a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$path[$i]->getID()."&showtree=".showtree()."\" data-droptarget=\"folder_".$path[$i]->getID()."\" rel=\"folder_".$path[$i]->getID()."\" data-name=\"".htmlspecialchars($path[$i]->getName())."\" class=\"table-row-folder droptarget\" data-uploadformtoken=\"".createFormKey('')."\" formtoken=\"".createFormKey('')."\">".htmlspecialchars($path[$i]->getName())."</a>" : htmlspecialchars($path[$i]->getName()));
			}
			$txtpath .= " <span class=\"divider\">/</span></li>";
		}
		if($document)
			$txtpath .= "<li><a href=\"".$this->params['settings']->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getId()."\" class=\"table-document-row\" rel=\"document_".$document->getId()."\" data-name=\"".htmlspecialchars($document->getName())."\" formtoken=\"".createFormKey('')."\">".htmlspecialchars($document->getName())."</a></li>";

		return '<ul class="breadcrumb">'.$txtpath.'</ul>';
	} /* }}} */

	function pageNavigation($pageTitle, $pageType=null, $extra=null) { /* {{{ */

		if ($pageType!=null && strcasecmp($pageType, "noNav")) {
			echo "<div class=\"navbar\">\n";
			echo " <div class=\"navbar-inner\">\n";
			echo "  <div class=\"container\">\n";
			echo "   <a class=\"btn btn-navbar\" data-toggle=\"collapse\" data-target=\".col2\">\n";
			echo " 		<span class=\"fa fa-bars\"></span>\n";
			echo "   </a>\n";
			switch ($pageType) {
				case "view_folder":
					$this->folderNavigationBar($extra);
					break;
				case "view_document":
					$this->documentNavigationBar($extra);
					break;
				case "my_documents":
					$this->myDocumentsNavigationBar();
					break;
				case "my_account":
					$this->accountNavigationBar();
					break;
				case "admin_tools":
					$this->adminToolsNavigationBar();
					break;
				case "calendarold";
					$this->calendarOldNavigationBar($extra);
					break;
				case "calendar";
					$this->calendarNavigationBar($extra);
					break;
				default:
					if($this->hasHook('pageNavigationBar')) {
						$menubar = $this->callHook('pageNavigationBar', $pageType, $extra);
						if(is_string($menubar))
							echo $menubar;
					}
			}
			echo " 	</div>\n";
			echo " </div>\n";
			echo "</div>\n";
			if($pageType == "view_folder" || $pageType == "view_document")
				echo $pageTitle."\n";
		} else {
			echo "<legend>".$pageTitle."</legend>\n";
		}

		return;
	} /* }}} */

	protected function showNavigationBar($menuitems, $options=array()) { /* {{{ */
		$content = '';
		$content .= "<ul".(isset($options['id']) ? ' id="'.$options['id'].'"' : '')." class=\"nav".(isset($options['right']) ? ' pull-right' : '')."\">\n";
		foreach($menuitems as $menuitem) {
			if(!empty($menuitem['children'])) {
				$content .= "    <li class=\"dropdown\">\n";
				$content .= "     <a class=\"dropdown-toggle\" data-toggle=\"dropdown\">".$menuitem['label']." <i class=\"fa fa-caret-down\"></i></a>\n";
				$content .= "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
				foreach($menuitem['children'] as $submenuitem) {
					if(!empty($submenuitem['children'])) {
						$content .= "      <li class=\"dropdown-submenu\">\n";
						$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".$submenuitem['label']."</a>\n";
						$content .= "       <ul class=\"dropdown-menu\" role=\"menu\">\n";
						foreach($submenuitem['children'] as $subsubmenuitem) {
							if(!empty($submenuitem['divider'])) {
								$content .= "      <li class=\"divider\"></li>\n";
							} else {
								$content .= "      <li><a href=\"".$subsubmenuitem['link']."\"".(isset($subsubmenuitem['class']) ? " class=\"".$subsubmenuitem['class']."\"" : "").(isset($subsubmenuitem['rel']) ? " rel=\"".$subsubmenuitem['rel']."\"" : "");
								if(!empty($subsubmenuitem['attributes']))
									foreach($subsubmenuitem['attributes'] as $attr)
										$content .= ' '.$attr[0].'="'.$attr[1].'"';
								$content .= ">".$subsubmenuitem['label']."</a></li>";
							}
						}
						$content .= "       </ul>\n";
						$content .= "      </li>\n";
					} else {
						if(!empty($submenuitem['divider'])) {
							$content .= "      <li class=\"divider\"></li>\n";
						} else {
							$content .= "      <li><a".(isset($submenuitem['link']) ? " href=\"".$submenuitem['link']."\"" : "").(isset($submenuitem['class']) ? " class=\"".$submenuitem['class']."\"" : "").(isset($submenuitem['target']) ? ' target="'.$submenuitem['target'].'"' : '');
							if(!empty($submenuitem['attributes']))
								foreach($submenuitem['attributes'] as $attr)
									$content .= ' '.$attr[0].'="'.$attr[1].'"';
							$content .= ">".$submenuitem['label']."</a></li>\n";
						}
					}
				}
				$content .= "     </ul>\n";
			} else {
				if(!empty($submenuitem['divider'])) {
					$content .= "      <li class=\"divider\"></li>\n";
				} else {
					$content .= "<li><a".(isset($menuitem['link']) ? " href=\"".$menuitem['link']."\"" : "").(isset($menuitem['target']) ? ' target="'.$menuitem['target'].'"' : '');
					if(!empty($menuitem['attributes']))
						foreach($menuitem['attributes'] as $attr)
							$content .= ' '.$attr[0].'="'.$attr[1].'"';
					$content .= ">".$menuitem['label']."</a></li>";
				}
			}
		}
		$content .= "</ul>\n";
		echo $content;
	} /* }}} */

	protected function showNavigationListWithBadges($menuitems, $options=array()) { /* {{{ */
		$content = '';
		$content .= "<ul".(isset($options['id']) ? ' id="'.$options['id'].'"' : '')." class=\"nav nav-list sidenav bs-docs-sidenav\">\n";
		foreach($menuitems as $menuitem) {
			$content .= "  <li class=\"".(!empty($menuitem['active']) ? ' active' : '')."\">\n";
			$content .= '    <a';
			$content .= !empty($menuitem['link']) ? ' href="'.$menuitem['link'].'"' : '';
			if(!empty($menuitem['attributes']))
				foreach($menuitem['attributes'] as $attr)
					$content .= ' '.$attr[0].'="'.$attr[1].'"';
			$content .= '>';
			$content .= $menuitem['label'];
			if(!empty($menuitem['badge']))
				$content .= '<span class="badge'.($menuitem['badge'] > 0 ? ' badge-info' : '').' badge-right">'.$menuitem['badge']."</span>";
			$content .= '    </a>'."\n";
			$content .= "  </li>\n";
		}

		$content .= "</ul>\n";
		echo $content;
	} /* }}} */

	protected function showButtonwithMenu($button, $options=array()) { /* {{{ */
		$content = '';
		$content .= '
<div class="btn-group">
  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		'.$button['label'].'
    <span class="caret"></span>
  </a>
';
		if($button['menuitems']) {
			$content .= '
	<ul class="dropdown-menu">
';
			foreach($button['menuitems'] as $menuitem) {
				$content .= '
		<li><a href="'.$menuitem['link'].'">'.$menuitem['label'].'</a><li>
';
			}
			$content .= '
	</ul>
';
		}
		$content .= '
</div>
';
		echo $content;
	} /* }}} */

	protected function showPaneHeader($name, $title, $isactive) { /* {{{ */
		echo '<li class="nav-item '.($isactive ? 'active' : '').'"><a class="nav-link '.($isactive ? 'active' : '').'" data-target="#'.$name.'" data-toggle="tab" role="button">'.$title.'</a></li>'."\n";
	} /* }}} */

	protected function showStartPaneContent($name, $isactive) { /* {{{ */
		echo '<div class="tab-pane'.($isactive ? ' active' : '').'" id="'.$name.'" role="tabpanel">';
	} /* }}} */

	protected function showEndPaneContent($name, $currentab) { /* {{{ */
		echo '</div>';
	} /* }}} */

	private function folderNavigationBar($folder) { /* {{{ */
		$dms = $this->params['dms'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessobject = $this->params['accessobject'];
		if (!is_object($folder) || !$folder->isType('folder')) {
			self::showNavigationBar(array());
			return;
		}
		$accessMode = $folder->getAccessMode($this->params['user']);
		$folderID = $folder->getID();
		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderID ."&showtree=".showtree()."\" class=\"brand\">".getMLText("folder")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		$menuitems = array();

		if ($accessMode == M_READ && !$this->params['user']->isGuest()) {
			if ($accessobject->check_controller_access('FolderNotify'))
				$menuitems['edit_folder_notify'] = array('link'=>$this->params['settings']->_httpRoot."out/out.FolderNotify.php?folderid=".$folderID."&showtree=".showtree(), 'label'=>getMLText('edit_folder_notify'));
		}
		else if ($accessMode >= M_READWRITE) {
			if ($accessobject->check_controller_access('AddSubFolder'))
				$menuitems['add_subfolder'] = array('link'=>$this->params['settings']->_httpRoot."out/out.AddSubFolder.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('add_subfolder'));
			if ($accessobject->check_controller_access('AddDocument'))
				$menuitems['add_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.AddDocument.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('add_document'));
			if(0 && $this->params['enablelargefileupload'])
				$menuitems['add_multiple_documents'] = array('link'=>$this->params['settings']->_httpRoot."out/out.AddMultiDocument.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('add_multiple_documents'));
			if ($accessobject->check_controller_access('EditFolder')) {
				$menuitems['edit_folder_props'] = array('link'=>$this->params['settings']->_httpRoot."out/out.EditFolder.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('edit_folder_props'));
			}
			if ($accessobject->check_controller_access('MoveFolder')) {
				if ($folderID != $this->params['rootfolderid'] && $folder->getParent())
					$menuitems['move_folder'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MoveFolder.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('move_folder'));
			}

			if ($accessMode == M_ALL) {
				if ($folderID != $this->params['rootfolderid'] && $folder->getParent())
					if ($accessobject->check_view_access('RemoveFolder'))
						$menuitems['rm_folder'] = array('link'=>$this->params['settings']->_httpRoot."out/out.RemoveFolder.php?folderid=". $folderID ."&showtree=".showtree(), 'label'=>getMLText('rm_folder'));
			}
			if ($accessMode == M_ALL) {
				if ($accessobject->check_view_access('FolderAccess'))
					$menuitems['edit_folder_access'] = array('link'=>$this->params['settings']->_httpRoot."out/out.FolderAccess.php?folderid=".$folderID."&showtree=".showtree(), 'label'=>getMLText('edit_folder_access'));
			}
			if ($accessobject->check_controller_access('FolderNotify'))
				$menuitems['edit_existing_notify'] = array('link'=>$this->params['settings']->_httpRoot."out/out.FolderNotify.php?folderid=". $folderID ."&showtree=". showtree(), 'label'=>getMLText('edit_existing_notify'));
		}
		if($enableClipboard) {
			$menuitems['add_to_clipboard'] = array('class'=>'addtoclipboard', 'attributes'=>array(['rel', 'F'.$folder->getId()], ['msg', getMLText('splash_added_to_clipboard')], ['title', getMLText("add_to_clipboard")]), 'label'=>getMLText("add_to_clipboard"));
		}
		if ($accessobject->check_view_access('Indexer') && $this->params['enablefullsearch']) {
			$menuitems['index_folder'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Indexer.php?folderid=". $folderID."&showtree=".showtree(), 'label'=>getMLText('index_folder'));
		}

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'folderNavigationBar')) {
	      $menuitems = $hookObj->folderNavigationBar($this, $folder, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */

	private function documentNavigationBar($document)	{ /* {{{ */
		$accessobject = $this->params['accessobject'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessMode = $document->getAccessMode($this->params['user']);
		$docid=".php?documentid=" . $document->getID();
		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.ViewDocument". $docid ."\" class=\"brand\">".getMLText("document")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		$menuitems = array();

		if ($accessMode >= M_READWRITE) {
			if (!$document->isLocked()) {
				if($accessobject->check_controller_access('UpdateDocument'))
					$menuitems['update_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UpdateDocument".$docid, 'label'=>getMLText('update_document'));
				if($accessobject->check_controller_access('LockDocument'))
					$menuitems['lock_document'] = array('link'=>$this->params['settings']->_httpRoot."op/op.LockDocument".$docid."&formtoken=".createFormKey('lockdocument'), 'label'=>getMLText('lock_document'));
				if($document->isCheckedOut()) {
					if($accessobject->mayCheckIn($document)) {
						$menuitems['checkin_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.CheckInDocument".$docid, 'label'=>getMLText('checkin_document'));
					}
				} else {
					if($this->params['checkoutdir']) {
						$menuitems['checkout_document'] = array('link'=>$this->params['settings']->_httpRoot."op/op.CheckOutDocument".$docid, 'label'=>getMLText('checkout_document'));
					}
				}
				if($accessobject->check_controller_access('EditDocument'))
					$menuitems['edit_document_props'] = array('link'=>$this->params['settings']->_httpRoot."out/out.EditDocument".$docid , 'label'=>getMLText('edit_document_props'));
				if($accessobject->check_controller_access('MoveDocument'))
					$menuitems['move_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MoveDocument".$docid, 'label'=>getMLText('move_document'));
			}
			else {
				$lockingUser = $document->getLockingUser();
				if (($lockingUser->getID() == $this->params['user']->getID()) || ($document->getAccessMode($this->params['user']) == M_ALL)) {
					if($accessobject->check_controller_access('UpdateDocument'))
						$menuitems['update_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UpdateDocument".$docid, 'label'=>getMLText('update_document'));
					if($accessobject->check_controller_access('UnlockDocument'))
						$menuitems['unlock_document'] = array('link'=>$this->params['settings']->_httpRoot."op/op.UnlockDocument".$docid."&formtoken=".createFormKey('unlockdocument'), 'label'=>getMLText('unlock_document'));
					if($accessobject->check_controller_access('EditDocument'))
						$menuitems['edit_document_props'] = array('link'=>$this->params['settings']->_httpRoot."out/out.EditDocument".$docid, 'label'=>getMLText('edit_document_props'));
					if($accessobject->check_controller_access('MoveDocument'))
						$menuitems['move_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MoveDocument".$docid, 'label'=>getMLText('move_document'));
				}
			}
			if($accessobject->maySetExpires($document)) {
				if ($accessobject->check_view_access('SetExpires'))
					$menuitems['expires'] = array('link'=>$this->params['settings']->_httpRoot."out/out.SetExpires".$docid, 'label'=>getMLText('expires'));
			}
		}
		if ($accessMode == M_ALL) {
			if ($accessobject->check_view_access('RemoveDocument'))
				$menuitems['rm_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.RemoveDocument".$docid, 'label'=>getMLText('rm_document'));
			if ($accessobject->check_view_access('DocumentAccess'))
				$menuitems['edit_document_access'] = array('link'=>$this->params['settings']->_httpRoot."out/out.DocumentAccess". $docid, 'label'=>getMLText('edit_document_access'));
		}
		if ($accessMode >= M_READ && !$this->params['user']->isGuest()) {
			if ($accessobject->check_view_access('DocumentNotify'))
				$menuitems['edit_existing_notify'] = array('link'=>$this->params['settings']->_httpRoot."out/out.DocumentNotify". $docid, 'label'=>getMLText('edit_existing_notify'));
		}
		if($enableClipboard) {
			$menuitems['add_to_clipboard'] = array('class'=>'addtoclipboard', 'attributes'=>array(['rel', 'D'.$document->getId()], ['msg', getMLText('splash_added_to_clipboard')], ['title', getMLText("add_to_clipboard")]), 'label'=>getMLText("add_to_clipboard"));
		}
		if ($accessobject->check_view_access('TransferDocument')) {
			$menuitems['transfer_document'] = array('link'=>$this->params['settings']->_httpRoot."out/out.TransferDocument". $docid, 'label'=>getMLText('transfer_document'));
		}

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'documentNavigationBar')) {
	      $menuitems = $hookObj->documentNavigationBar($this, $document, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */

	private function accountNavigationBar() { /* {{{ */
		$accessobject = $this->params['accessobject'];
		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.MyAccount.php\" class=\"brand\">".getMLText("my_account")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";

		$menuitems = array();
		if ($accessobject->check_view_access('EditUserData') || !$this->params['disableselfedit'])
			$menuitems['edit_user_details'] = array('link'=>$this->params['settings']->_httpRoot."out/out.EditUserData.php", 'label'=>getMLText('edit_user_details'));
		
		if (!$this->params['user']->isAdmin()) 
			$menuitems['edit_default_keywords'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UserDefaultKeywords.php", 'label'=>getMLText('edit_default_keywords'));

		if ($accessobject->check_view_access('ManageNotify'))
			$menuitems['edit_notify'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ManageNotify.php", 'label'=>getMLText('edit_existing_notify'));

		$menuitems['2_factor_auth'] = array('link'=>"../out/out.Setup2Factor.php", 'label'=>getMLText('2_factor_auth'));

		if ($this->params['enableusersview']){
			if ($accessobject->check_view_access('UsrView'))
				$menuitems['users'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UsrView.php", 'label'=>getMLText('users'));
			if ($accessobject->check_view_access('GroupView'))
				$menuitems['groups'] = array('link'=>$this->params['settings']->_httpRoot."out/out.GroupView.php", 'label'=>getMLText('groups'));
		}		

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'accountNavigationBar')) {
	      $menuitems = $hookObj->accountNavigationBar($this, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */

	private function myDocumentsNavigationBar() { /* {{{ */
		$accessobject = $this->params['accessobject'];

		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.MyDocuments.php\" class=\"brand\">".getMLText("my_documents")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";

		$menuitems = array();
		if ($accessobject->check_view_access('MyDocuments')) {
			$menuitems['inprocess'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MyDocuments.php?inProcess=1", 'label'=>getMLText('documents_in_process'));
			$menuitems['all_documents'] = array('link'=>$this->params['settings']->_httpRoot."out/out.MyDocuments.php", 'label'=>getMLText('all_documents'));
		}
		if($this->params['workflowmode'] == 'traditional' || $this->params['workflowmode'] == 'traditional_only_approval') {
			if ($accessobject->check_view_access('ReviewSummary'))
				$menuitems['review_summary'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ReviewSummary.php", 'label'=>getMLText('review_summary'));
			if ($accessobject->check_view_access('ApprovalSummary'))
				$menuitems['approval_summary'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ApprovalSummary.php", 'label'=>getMLText('approval_summary'));
		} else {
			if ($accessobject->check_view_access('WorkflowSummary'))
				$menuitems['workflow_summary'] = array('link'=>$this->params['settings']->_httpRoot."out/out.WorkflowSummary.php", 'label'=>getMLText('workflow_summary'));
		}
		if ($accessobject->check_view_access('ReceiptSummary'))
		$menuitems['receipt_summary'] = array('link'=>"../out/out.ReceiptSummary.php", 'label'=>getMLText('receipt_summary'));
		if ($accessobject->check_view_access('RevisionSummary'))
		$menuitems['revision_summary'] = array('link'=>"../out/out.RevisionSummary.php", 'label'=>getMLText('revision_summary'));

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'mydocumentsNavigationBar')) {
	      $menuitems = $hookObj->mydocumentsNavigationBar($this, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */

	private function adminToolsNavigationBar() { /* {{{ */
		$accessobject = $this->params['accessobject'];
		$settings = $this->params['settings'];
		echo "    <id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.AdminTools.php\" class=\"brand\">".getMLText("admin_tools")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";

		$menuitems = array();
		if($accessobject->check_view_access(array('UsrMgr', 'RoleMgr', 'GroupMgr', 'UserList', 'Acl'))) {
			$menuitems['user_group_management'] = array('link'=>"#", 'label'=>getMLText('user_group_management'));
			if ($accessobject->check_view_access('UsrMgr'))
				$menuitems['user_group_management']['children']['user_management'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UsrMgr.php", 'label'=>getMLText('user_management'));
			if ($accessobject->check_view_access('RoleMgr'))
				$menuitems['user_group_management']['children']['role_management'] = array('link'=>$this->params['settings']->_httpRoot."out/out.RoleMgr.php", 'label'=>getMLText('role_management'));
			if ($accessobject->check_view_access('GroupMgr'))
				$menuitems['user_group_management']['children']['group_management'] = array('link'=>$this->params['settings']->_httpRoot."out/out.GroupMgr.php", 'label'=>getMLText('group_management'));
			if ($accessobject->check_view_access('UserList'))
				$menuitems['user_group_management']['children']['user_list'] = array('link'=>$this->params['settings']->_httpRoot."out/out.UserList.php", 'label'=>getMLText('user_list'));
			if ($accessobject->check_view_access('Acl'))
				$menuitems['user_group_management']['children']['access_control'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Acl.php", 'label'=>getMLText('access_control'));
			}

			if($accessobject->check_view_access(array('DefaultKeywords', 'Categories', 'AttributeMgr', 'WorkflowMgr', 'WorkflowStatesMgr', 'WorkflowActionsMgr'))) {
				$menuitems['definitions'] = array('link'=>"#", 'label'=>getMLText('definitions'));
			if ($accessobject->check_view_access('DefaultKeywords'))
				$menuitems['definitions']['children']['default_keywords'] = array('link'=>$this->params['settings']->_httpRoot."out/out.DefaultKeywords.php", 'label'=>getMLText('global_default_keywords'));
			if ($accessobject->check_view_access('Categories'))
				$menuitems['definitions']['children']['document_categories'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Categories.php", 'label'=>getMLText('global_document_categories'));
			if ($accessobject->check_view_access('AttributeMgr'))
				$menuitems['definitions']['children']['attribute_definitions'] = array('link'=>$this->params['settings']->_httpRoot."out/out.AttributeMgr.php", 'label'=>getMLText('global_attributedefinitions'));
			if($this->params['workflowmode'] == 'advanced') {
				if ($accessobject->check_view_access('WorkflowMgr'))
					$menuitems['definitions']['children']['workflows'] = array('link'=>$this->params['settings']->_httpRoot."out/out.WorkflowMgr.php", 'label'=>getMLText('global_workflows'));
				if ($accessobject->check_view_access('WorkflowStatesMgr'))
					$menuitems['definitions']['children']['workflow_states'] = array('link'=>$this->params['settings']->_httpRoot."out/out.WorkflowStatesMgr.php", 'label'=>getMLText('global_workflow_states'));
				if ($accessobject->check_view_access('WorkflowActionsMgr'))
					$menuitems['definitions']['children']['workflow_actions'] = array('link'=>$this->params['settings']->_httpRoot."out/out.WorkflowActionsMgr.php", 'label'=>getMLText('global_workflow_actions'));
			}
		}

		if($this->params['enablefullsearch']) {
			if($accessobject->check_view_access(array('Indexer', 'CreateIndex', 'IndexInfo'))) {
				$menuitems['fulltext'] = array('link'=>"#", 'label'=>getMLText('fullsearch'));
			if ($accessobject->check_view_access('Indexer'))
				$menuitems['fulltext']['children']['update_fulltext_index'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Indexer.php", 'label'=>getMLText('update_fulltext_index'));
			if ($accessobject->check_view_access('CreateIndex'))
				$menuitems['fulltext']['children']['create_fulltext_index'] = array('link'=>$this->params['settings']->_httpRoot."out/out.CreateIndex.php", 'label'=>getMLText('create_fulltext_index'));
			if ($accessobject->check_view_access('IndexInfo'))
				$menuitems['fulltext']['children']['fulltext_info'] = array('link'=>$this->params['settings']->_httpRoot."out/out.IndexInfo.php", 'label'=>getMLText('fulltext_info'));
			}
		}

		if($accessobject->check_view_access(array('BackupTools', 'LogManagement'))) {
			$menuitems['backup_log_management'] = array('link'=>"#", 'label'=>getMLText('backup_log_management'));
			if ($accessobject->check_view_access('BackupTools'))
				$menuitems['backup_log_management']['children'][] = array('link'=>$this->params['settings']->_httpRoot."out/out.BackupTools.php", 'label'=>getMLText('backup_tools'));
			if ($this->params['logfileenable'])
				if ($accessobject->check_view_access('LogManagement'))
					$menuitems['backup_log_management']['children'][] = array('link'=>$this->params['settings']->_httpRoot."out/out.LogManagement.php", 'label'=>getMLText('log_management'));
		}

		if($accessobject->check_view_access(array('ImportFS', 'ImportUsers', 'Statistic', 'Charts', 'Timeline', 'ObjectCheck', 'ExtensionMgr', 'Info'))) {
			$menuitems['misc'] = array('link'=>"#", 'label'=>getMLText('misc'));
			if ($accessobject->check_view_access('ImportFS'))
				$menuitems['misc']['children']['import_fs'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ImportFS.php", 'label'=>getMLText('import_fs'));
			if ($accessobject->check_view_access('ImportUsers'))
				$menuitems['misc']['children']['import_users'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ImportUsers.php", 'label'=>getMLText('import_users'));
			if ($accessobject->check_view_access('Statistic'))
				$menuitems['misc']['children']['folders_and_documents_statistic'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Statistic.php", 'label'=>getMLText('folders_and_documents_statistic'));
			if ($accessobject->check_view_access('Charts'))
				$menuitems['misc']['children']['charts'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Charts.php", 'label'=>getMLText('charts'));
			if ($accessobject->check_view_access('Timeline'))
				$menuitems['misc']['children']['timeline'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Timeline.php", 'label'=>getMLText('timeline'));
			if ($accessobject->check_view_access('SchedulerTaskMgr'))
				$menuitems['misc']['children']['schedulertaskmgr'] = array('link'=>$this->params['settings']->_httpRoot."out/out.SchedulerTaskMgr.php", 'label'=>getMLText('scheduler_task_mgr'));
			if ($accessobject->check_view_access('ObjectCheck'))
				$menuitems['misc']['children']['objectcheck'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ObjectCheck.php", 'label'=>getMLText('objectcheck'));
			if ($accessobject->check_view_access('ExpiredDocuments'))
				$menuitems['misc']['children']['documents_expired'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ExpiredDocuments.php", 'label'=>getMLText('documents_expired'));
			if ($accessobject->check_view_access('ExtensionMgr'))
				$menuitems['misc']['children']['extension_manager'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ExtensionMgr.php", 'label'=>getMLText('extension_manager'));
			if ($accessobject->check_view_access('ClearCache'))
				$menuitems['misc']['children']['clear_cache'] = array('link'=>$this->params['settings']->_httpRoot."out/out.ClearCache.php", 'label'=>getMLText('clear_cache'));
			if ($accessobject->check_view_access('Info'))
				$menuitems['misc']['children']['version_info'] = array('link'=>$this->params['settings']->_httpRoot."out/out.Info.php", 'label'=>getMLText('version_info'));
		}

		if ($settings->_enableDebugMode) {
			if($accessobject->check_view_access(array('Hooks', 'NotificationServices'))) {
				$menuitems['debug'] = array('link'=>"#", 'label'=>getMLText('debug'));
				if ($accessobject->check_view_access('Hooks'))
					$menuitems['debug']['children']['hooks'] = array('link'=>"../out/out.Hooks.php", 'label'=>getMLText('list_hooks'));
				if ($accessobject->check_view_access('NotificationServices'))
					$menuitems['debug']['children']['notification_services'] = array('link'=>"../out/out.NotificationServices.php", 'label'=>getMLText('list_notification_services'));
				if ($accessobject->check_view_access('ConversionServices'))
					$menuitems['debug']['children']['conversion_services'] = array('link'=>"../out/out.ConversionServices.php", 'label'=>getMLText('list_conversion_services'));
			}
		}

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'admintoolsNavigationBar')) {
	      $menuitems = $hookObj->admintoolsNavigationBar($this, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */
	
	private function calendarOldNavigationBar($d){ /* {{{ */
		$accessobject = $this->params['accessobject'];
		$ds="&day=".$d[0]."&month=".$d[1]."&year=".$d[2];
		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.CalendarOld.php?mode=y\" class=\"brand\">".getMLText("calendar")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";
		echo "<ul class=\"nav\">\n";

		echo "<li><a href=\"".$this->params['settings']->_httpRoot."out/out.CalendarOld.php?mode=w".$ds."\">".getMLText("week_view")."</a></li>\n";
		echo "<li><a href=\"".$this->params['settings']->_httpRoot."out/out.CalendarOld.php?mode=m".$ds."\">".getMLText("month_view")."</a></li>\n";
		echo "<li><a href=\"".$this->params['settings']->_httpRoot."out/out.CalendarOld.php?mode=y".$ds."\">".getMLText("year_view")."</a></li>\n";
		if($accessobject->check_view_access(array('AddEvent')))
			echo "<li><a href=\"".$this->params['settings']->_httpRoot."out/out.AddEvent.php\">".getMLText("add_event")."</a></li>\n";
		echo "</ul>\n";
		echo "</div>\n";
		return;
	
	} /* }}} */

	private function calendarNavigationBar($d){ /* {{{ */
		$accessobject = $this->params['accessobject'];
		echo "<id=\"first\"><a href=\"".$this->params['settings']->_httpRoot."out/out.Calendar.php\" class=\"brand\">".getMLText("calendar")."</a>\n";
		echo "<div class=\"nav-collapse col2\">\n";

		$menuitems = array();
		if($accessobject->check_view_access(array('AddEvent')))
			$menuitems['addevent'] = array('link'=>$this->params['settings']->_httpRoot."out/out.AddEvent.php", 'label'=>getMLText('add_event'));

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'calendarNavigationBar')) {
	      $menuitems = $hookObj->calendarNavigationBar($this, $menuitems);
			}
		}

		self::showNavigationBar($menuitems);

		echo "</div>\n";
	} /* }}} */

	function pageList($pageNumber, $totalPages, $baseURI, $params, $dataparams=[]) { /* {{{ */

		$maxpages = 25; // skip pages when more than this is shown
		$range = 5; // pages left and right of current page
		if (!is_numeric($pageNumber) || !is_numeric($totalPages) || $totalPages<2) {
			return;
		}

		// Construct the basic URI based on the $_GET array. One could use a
		// regular expression to strip out the pg (page number) variable to
		// achieve the same effect. This seems to be less haphazard though...
		$resultsURI = $baseURI;
		unset($params['pg']);
		$first=true;
		if($params) {
			$resultsURI .= '?'.http_build_query($params);
			$first=false;
		}

		$datastr = '';
		if($dataparams) {
			$datastr .= ' ';
			foreach($dataparams as $k=>$v)
				$datastr .= 'data-'.$k.'="'.$v.'"';
		}
		echo "<div class=\"pagination pagination-small\">";
		echo "<ul>";
		if($totalPages <= $maxpages) {
			for ($i = 1; $i <= $totalPages; $i++) {
				echo "<li ".($i == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$i."\" data-page=\"".$i."\"".$datastr.">".$i."</a></li>";
			}
		} else {
			if($pageNumber-$range > 1)
				$start = $pageNumber-$range;
			else
				$start = 2;
			if($pageNumber+$range < $totalPages)
				$end = $pageNumber+$range;
			else
				$end = $totalPages-1;
			/* Move start or end to always show 2*$range items */
			$diff = $end-$start-2*$range;
			if($diff < 0) {
				if($start > 2)
					$start += $diff;
				if($end < $totalPages-1)
					$end -= $diff;
			}
			if($pageNumber > 1)
				echo "<li><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".($pageNumber-1)."\" data-page=\"".($pageNumber-1)."\"".$datastr.">&laquo;</a></li>";
			echo "<li ".(1 == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=1\" data-page=\"1\"".$datastr.">1</a></li>";
			if($start > 2)
				echo "<li><span>...</span></li>";
			for($j=$start; $j<=$end; $j++)
				echo "<li ".($j == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$j."\" data-page=\"".$j."\"".$datastr.">".$j."</a></li>";
			if($end < $totalPages-1)
				echo "<li><span>...</span></li>";
			if($end < $totalPages)
				echo "<li ".($totalPages == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".$totalPages."\" data-page=\"".$totalPages."\"".$datastr.">".$totalPages."</a></li>";
			if($pageNumber < $totalPages)
				echo "<li><a href=\"".$resultsURI.($first ? "?" : "&")."pg=".($pageNumber+1)."\" data-page=\"".($pageNumber+1)."\"".$datastr.">&raquo;</a></li>";
		}
		if ($totalPages>1) {
			echo "<li ".(0 == $pageNumber ? 'class="active"' : "" )."><a href=\"".$resultsURI.($first ? "?" : "&")."pg=all\" data-page=\"all\"".$datastr.">".getMLText("all_pages")."</a></li>";
		}
		echo "</ul>";
		echo "</div>";

		return;
	} /* }}} */

	function contentContainer($content) { /* {{{ */
		echo "<div class=\"well\">\n";
		echo $content;
		echo "</div>\n";
		return;
	} /* }}} */

	function contentContainerStart($class='', $id='') { /* {{{ */
		echo "<div class=\"well".($class ? " ".$class : "")."\"".($id ? " id=\"".$id."\"" : "").">\n";
		return;
	} /* }}} */

	function contentContainerEnd() { /* {{{ */

		echo "</div>\n";
		return;
	} /* }}} */

	function contentHeading($heading, $noescape=false) { /* {{{ */

		if($noescape)
			echo "<legend>".$heading."</legend>\n";
		else
			echo "<legend>".htmlspecialchars($heading)."</legend>\n";
		return;
	} /* }}} */

	function contentSubHeading($heading, $first=false) { /* {{{ */

//		echo "<div class=\"contentSubHeading\"".($first ? " id=\"first\"" : "").">".htmlspecialchars($heading)."</div>\n";
		echo "<h5>".$heading."</h5>";
		return;
	} /* }}} */

	function rowStart() { /* {{{ */
		echo "<div class=\"row-fluid\">\n";
		return;
	} /* }}} */

	function rowEnd() { /* {{{ */
		echo "</div>\n";
		return;
	} /* }}} */

	function columnStart($width=6) { /* {{{ */
		echo "<div class=\"span".$width."\">\n";
		return;
	} /* }}} */

	function columnEnd() { /* {{{ */
		echo "</div>\n";
		return;
	} /* }}} */

	function formField($title, $value, $params=array()) { /* {{{ */
		if($title !== null) {
			echo "<div class=\"control-group\">";
			echo "	<label class=\"control-label\"".(!empty($params['help']) ? " title=\"".$params['help']."\" style=\"cursor: help;\"" : "").(!empty($value['id']) ? ' for="'.$value['id'].'"' : '').">".$title.":</label>";
			echo "	<div class=\"controls\">";
		}
		if(isset($params['field_wrap'][0]))
			echo $params['field_wrap'][0];
		if(is_string($value)) {
			echo $value;
		} elseif(is_array($value)) {
			switch($value['element']) {
			case 'select':
				$allowempty = empty($value['allow_empty']) ? false : $value['allow_empty'];
				echo '<select'.
					(!empty($value['id']) ? ' id="'.$value['id'].'"' : '').
					(!empty($value['name']) ? ' name="'.$value['name'].'"' : '').
					(!empty($value['class']) ? ' class="'.$value['class'].'"' : '').
					(!empty($value['placeholder']) ? ' data-placeholder="'.$value['placeholder'].'"' : '').
					($allowempty	? ' data-allow-clear="true"' : '').
					(!empty($value['multiple']) ? ' multiple' : '');
				if(!empty($value['attributes']) && is_array($value['attributes']))
					foreach($value['attributes'] as $a)
						echo ' '.$a[0].'="'.$a[1].'"';
				echo ">";
				if(isset($value['options']) && is_array($value['options'])) {
					if($allowempty)
						echo "<option value=\"\"></option>";
					foreach($value['options'] as $val) {
						if(is_string($val)) {
							echo '<optgroup label="'.$val.'">';
						} elseif(is_array($val)) {
						echo '<option value="'.$val[0].'"'.(!empty($val[2]) ? ' selected' : '');
						if(!empty($val[3]) && is_array($val[3]))
							foreach($val[3] as $a)
								echo ' '.$a[0].'="'.$a[1].'"';
						echo '>'.$val[1].'</option>';
						}
					}
				}
				echo '</select>';
				break;
			case 'textarea':
				echo '<textarea'.
					(!empty($value['id']) ? ' id="'.$value['id'].'"' : '').
					(!empty($value['name']) ? ' name="'.$value['name'].'"' : '').
					(!empty($value['class']) ? ' class="'.$value['class'].'"' : '').
					(!empty($value['rows']) ? ' rows="'.$value['rows'].'"' : '').
					(!empty($value['cols']) ? ' cols="'.$value['cols'].'"' : '').
					(!empty($value['placeholder']) ? ' placeholder="'.$value['placeholder'].'"' : '').
					(!empty($value['required']) ? ' required="required"' : '').">".(!empty($value['value']) ? $value['value'] : '')."</textarea>";
				break;
			case 'plain':
				echo $value['value'];
				break;
			case 'input':
			default:
				switch($value['type']) {
				default:
					if(!empty($value['addon']))
						echo "<span class=\"input-append\">";
					echo '<input'.
						(!empty($value['type']) ? ' type="'.$value['type'].'"' : '').
						(!empty($value['id']) ? ' id="'.$value['id'].'"' : '').
						(!empty($value['name']) ? ' name="'.$value['name'].'"' : '').
						(!empty($value['class']) ? ' class="'.$value['class'].'"' : '').
						((isset($value['value']) && is_string($value['value'])) || !empty($value['value']) ? ' value="'.$value['value'].'"' : '').
						(!empty($value['placeholder']) ? ' placeholder="'.$value['placeholder'].'"' : '').
						(!empty($value['autocomplete']) ? ' autocomplete="'.$value['autocomplete'].'"' : '').
						(isset($value['min']) ? ' min="'.$value['min'].'"' : '').
						(!empty($value['checked']) ? ' checked' : '').
						(!empty($value['required']) ? ' required="required"' : '');
					if(!empty($value['attributes']) && is_array($value['attributes']))
						foreach($value['attributes'] as $a)
							echo ' '.$a[0].'="'.$a[1].'"';
					echo "/>";
					if(!empty($value['addon'])) {
						echo '<span class="add-on">'.$value['addon'].'</span>';
						echo "</span>\n";
					}
					break;
				}
				break;
			}
		}
		if(isset($params['field_wrap'][1]))
			echo $params['field_wrap'][1];
		if($title !== null) {
			echo "</div>";
			echo "</div>";
		}
		return;
	} /* }}} */

	function formSubmit($value, $name='', $target='', $type='primary') { /* {{{ */
		switch($type) {
		case 'danger':
			$class = 'btn-danger';
			break;
		case 'secondary':
			$class = 'btn-secondary';
			break;
		case 'neutral':
			$class = '';
			break;
		case 'primary':
		default:
			$class = 'btn-primary';
		}
		echo "<div class=\"controls\">\n";
		if(is_string($value)) {
			echo "<button type=\"submit\" class=\"btn ".$class."\"".($name ? ' name="'.$name.'" id="'.$name.'"' : '').($target ? ' formtarget="'.$target.'"' : '').">".$value."</button>\n";
		} else {
			if(is_array($value)) {
				foreach($value as $i=>$v)
					echo "<button type=\"submit\" class=\"btn ".$class."\"".(!empty($name[$i]) ? ' name="'.$name[$i].'" id="'.$name[$i].'"' : '').(!empty($target[$i]) ? ' formtarget="'.$name[$i].'"' : '').">".$v."</button>\n";
			}
		}
		echo "</div>\n";
	} /* }}} */

	function getMimeIcon($fileType) { /* {{{ */
		// for extension use LOWER CASE only
		$icons = array();
		$icons["txt"]  = "text-x-preview.svg";
		$icons["text"] = "text-x-preview.svg";
		$icons["tex"]  = "text-x-preview.svg";
		$icons["doc"]  = "office-document.svg";
		$icons["dot"]  = "office-document.svg";
		$icons["docx"] = "office-document.svg";
		$icons["dotx"] = "office-document.svg";
		$icons["rtf"]  = "office-document.svg";
		$icons["xls"]  = "office-spreadsheet.svg";
		$icons["xlt"]  = "office-spreadsheet.svg";
		$icons["xlsx"] = "office-spreadsheet.svg";
		$icons["xltx"] = "office-spreadsheet.svg";
		$icons["ppt"]  = "office-presentation.svg";
		$icons["pot"]  = "office-presentation.svg";
		$icons["pptx"] = "office-presentation.svg";
		$icons["potx"] = "office-presentation.svg";
		$icons["exe"]  = "executable.svg";
		$icons["html"] = "web.svg";
		$icons["htm"]  = "web.svg";
		$icons["gif"]  = "image.svg";
		$icons["jpg"]  = "image.svg";
		$icons["jpeg"] = "image.svg";
		$icons["bmp"]  = "image.svg";
		$icons["png"]  = "image.svg";
		$icons["tif"]  = "image.svg";
		$icons["tiff"] = "image.svg";
		$icons["log"]  = "text-x-preview.svg";
		$icons["midi"] = "audio.svg";
		$icons["pdf"]  = "gnome-mime-application-pdf.svg";
		$icons["wav"]  = "audio.svg";
		$icons["mp3"]  = "audio.svg";
		$icons["m4a"]  = "audio.svg";
		$icons["ogg"]  = "audio.svg";
		$icons["opus"]  = "audio.svg";
		$icons["c"]    = "text-x-preview.svg";
		$icons["cpp"]  = "text-x-preview.svg";
		$icons["h"]    = "text-x-preview.svg";
		$icons["java"] = "text-x-preview.svg";
		$icons["py"]   = "text-x-preview.svg";
		$icons["tar"]  = "package.svg";
		$icons["gz"]   = "package.svg";
		$icons["7z"]   = "package.svg";
		$icons["bz"]   = "package.svg";
		$icons["bz2"]  = "package.svg";
		$icons["tgz"]  = "package.svg";
		$icons["zip"]  = "package.svg";
		$icons["rar"]  = "package.svg";
		$icons["mpg"]  = "video.svg";
		$icons["mp4"]  = "video.svg";
		$icons["avi"]  = "video.svg";
		$icons["webm"]  = "video.svg";
		$icons["mkv"]  = "video.svg";
		$icons["ods"]  = "office-spreadsheet.svg";
		$icons["ots"]  = "office-spreadsheet.svg";
		$icons["sxc"]  = "office-spreadsheet.svg";
		$icons["stc"]  = "office-spreadsheet.svg";
		$icons["odt"]  = "office-document.svg";
		$icons["ott"]  = "office-document.svg";
		$icons["sxw"]  = "office-document.svg";
		$icons["stw"]  = "office-document.svg";
		$icons["odp"]  = "office-presentation.svg";
		$icons["otp"]  = "office-presentation.svg";
		$icons["sxi"]  = "office-presentation.svg";
		$icons["sti"]  = "office-presentation.svg";
		$icons["odg"]  = "office-drawing.svg";
		$icons["otg"]  = "office-drawing.svg";
		$icons["sxd"]  = "office-drawing.svg";
		$icons["std"]  = "office-drawing.svg";
		$icons["odf"]  = "ooo_formula.png";
		$icons["sxm"]  = "ooo_formula.png";
		$icons["smf"]  = "ooo_formula.png";
		$icons["mml"]  = "ooo_formula.png";
		$icons["folder"]  = "folder.svg";

		$icons["default"] = "text-x-preview.svg"; //"default.png";

		$ext = strtolower(substr($fileType, 1));
		if (isset($icons[$ext])) {
			return $this->imgpath.$icons[$ext];
		}
		else {
			return $this->imgpath.$icons["default"];
		}
	} /* }}} */

function getOverallStatusIcon($status) { /* {{{ */
	if (is_null($status)) {
		return '';
	} else {
		$icon = '';
		$color = '';
		switch($status) {
			case S_IN_WORKFLOW:
				$icon = 'fa fa-circle in-workflow';
				break;
			case S_DRAFT_REV:
				$icon = 'fa fa-circle in-workflow';
				break;
			case S_DRAFT_APP:
				$icon = 'fa fa-circle in-workflow';
				break;
			case S_RELEASED:
				$icon = 'fa-circle released';
				break;
			case S_REJECTED:
				$icon = 'fa-circle rejected';
				break;
			case S_OBSOLETE:
				$icon = 'fa-circle obsolete';
				break;
			case S_EXPIRED:
				$icon = 'fa-circle expired';
				break;
			case S_IN_REVISION:
				$icon = 'fa-refresh';
				break;
			case S_DRAFT:
				$icon = 'fa-circle-o';
				break;
			case S_NEEDS_CORRECTION:
				$icon = 'fa-circle in-workflow';
				break;
			default:
				$icon = 'fa fa-question';
				break;
		}
		return '<div style="display: inline-block; white-space: nowrap;"><i class="fa '.$icon.'"'.($color ? ' style="color: '.$color.';"' : '').'  title="'.getOverallStatusText($status).'"></i> <span class="visible-desktop">'.getOverallStatusText($status).'</span></div>';
	}
} /* }}} */

	/**
	 * Get attributes for a button opening a modal box
	 *
	 * @param array $config contains elements
	 *   target: id of modal box
	 *   remote: URL of data to be loaded into box
	 * @return string
	 */
	function getModalBoxLinkAttributes($config) { /* {{{ */
		$attrs = array();
		$attrs[] = array('data-target', '#'.$config['target']);
		if(isset($config['remote']))
			$attrs[] = array('href', $config['remote']);
		$attrs[] = array('data-toggle', 'modal');
		$attrs[] = array('role', 'button');
		if(isset($config['class'])) {
			if($config['class'])
				$attrs[] = array('class', $config['class']);
		} else
			$attrs[] = array('class', 'btn');
		return $attrs;
	} /* }}} */

	/**
	 * Get html for button opening a modal box
	 *
	 * @param array $config contains elements
	 *   target: id of modal box
	 *   remote: URL of data to be loaded into box
	 *   title: text on button
	 * @return string
	 */
	function getModalBoxLink($config) { /* {{{ */
//		$content = '';
//		$content .= "<a data-target=\"#".$config['target']."\"".(isset($config['remote']) ? " href=\"".$config['remote']."\"" : "")." role=\"button\" class=\"".(isset($config['class']) ? $config['class'] : "btn")."\" data-toggle=\"modal\"";
		$attrs = self::getModalBoxLinkAttributes($config);
		$content = '<a';
		if($attrs) {
			foreach($attrs as $attr)
				$content .= ' '.$attr[0].'="'.$attr[1].'"';
		}
		if(!empty($config['attributes'])) {
			foreach($config['attributes'] as $attrname=>$attrval)
				$content .= ' '.$attrname.'="'.$attrval.'"';
		}
		$content .= ">".$config['title']."</a>\n";
		return $content;
	} /* }}} */

	/**
	 * Get html for a modal box with buttons
	 *
	 * @param array $config contains elements
	 *   id: id of modal box (must match target of getModalBoxLink())
	 *   title: title of modal box
	 *   content: content to be shown in the body of the box. Can be left
	 *   empty if the body is loaded from the remote link passed to the button
	 *   to open this box.
	 *   buttons: array of buttons, each having a title and an optional id
	 * @return string
	 */
	function getModalBox($config) { /* {{{ */
		$content = '
<div class="modal modal-wide hide" id="'.$config['id'].'" tabindex="-1" role="dialog" aria-labelledby="'.$config['id'].'Label" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="'.$config['id'].'Label">'.$config['title'].'</h3>
  </div>
	<div class="modal-body">
';
		if(!empty($config['content']))
			$content .= $config['content'];
		else
			$content .= '<p>'.getMLText('data_loading').'</p>';
		$content .= '
  </div>
	<div class="modal-footer">
';
		if($config['buttons']) {
			foreach($config['buttons'] as $button)
				$content .= '<button class="btn'.(!empty($button['id']) ? ' btn-primary" id="'.$button['id'].'"': '" ').'data-dismiss="modal" aria-hidden="true">'.$button['title'].'</button>';
		}
	$content .= '
  </div>
</div>
';
	return $content;
	} /* }}} */

	function printFileChooserJs() { /* {{{ */
?>
$(document).ready(function() {
	/* Triggered after the file has been selected */
	$(document).on('change', '.btn-file :file', function() {
		var input = $(this),
		numFiles = input.get(0).files ? input.get(0).files.length : 1,
		label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
		input.trigger('fileselect', [numFiles, label]);
	});

	$(document).on('fileselect', '.upload-file .btn-file :file', function(event, numFiles, label) {
		var input = $(this).parents('.input-append').find(':text'),
		log = numFiles > 1 ? numFiles + ' files selected' : label;

		if( input.length ) {
			input.val(log);
		} else {
//			if( log ) alert(log);
		}
	});
});
<?php
	} /* }}} */

	function getFileChooserHtml($varname='userfile', $multiple=false, $accept='') { /* {{{ */
		$id = preg_replace('/[^A-Za-z]/', '', $varname);
		$html = '
	<div id="'.$id.'-upload-files">
		<div id="'.$id.'-upload-file" class="upload-file">
			<div class="input-append">
				<input type="text" class="form-control fileupload-group" id="kkll'.$id.'" readonly>
				<span class="btn btn-secondary btn-file">
					'.getMLText("browse").'&hellip; <input id="'.$id.'" type="file" name="'.$varname.'"'.($multiple ? " multiple" : "").($accept ? ' accept="'.$accept.'"' : "").' data-target-highlight="kkll'.$id.'">
				</span>
			</div>
		</div>
	</div>
';
		return $html;
	} /* }}} */

	function printFileChooser($varname='userfile', $multiple=false, $accept='') { /* {{{ */
		echo self::getFileChooserHtml($varname, $multiple, $accept);
	} /* }}} */

	function printDateChooser($defDate = '', $varName, $lang='', $dateformat='', $startdate='', $enddate='', $weekstart=null) { /* {{{ */
		echo self::getDateChooser($defDate, $varName, $lang, $dateformat, $startdate, $enddate, $weekstart);
	} /* }}} */

	function getDateChooser($defDate = '', $varName, $lang='', $dateformat='', $startdate='', $enddate='', $weekstart=null, $placeholder='', $nogroup=false) { /* {{{ */
		if(!$dateformat)
			$dateformat = getConvertDateFormat();
		$content = '';
		$content = '
			<span class="input-append date span4 datepicker" id="'.$varName.'date" '.($weekstart == null ? '' : 'data-date-week-start="'.intval($weekstart).'" ').'data-date="'.$defDate.'" data-selectmenu="presetexpdate" data-date-format="'.$dateformat.'"'.($lang ? ' data-date-language="'.str_replace('_', '-', $lang).'"' : '').($startdate ? ' data-date-start-date="'.$startdate.'"' : '').($enddate ? ' data-date-end-date="'.$enddate.'"' : '').'>
				<input class="span12" size="16" name="'.$varName.'" id="'.$varName.'" type="text" placeholder="'.htmlspecialchars($placeholder).'" value="'.$defDate.'" autocomplete="off">
';
		if(!$nogroup)
			$content .= '
				<span class="add-on"><i class="fa fa-calendar"></i></span>
';
			$content .= '
			</span>';
		return $content;
	} /* }}} */

	function __printDateChooser($defDate = -1, $varName) { /* {{{ */
	
		if ($defDate == -1)
			$defDate = mktime();
		$day   = date("d", $defDate);
		$month = date("m", $defDate);
		$year  = date("Y", $defDate);

		print "<select name=\"" . $varName . "day\">\n";
		for ($i = 1; $i <= 31; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($day) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "month\">\n";
		for ($i = 1; $i <= 12; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($month) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select> \n";
		print "<select name=\"" . $varName . "year\">\n";	
		for ($i = $year-5 ; $i <= $year+5 ; $i++)
		{
			print "<option value=\"" . $i . "\"";
			if (intval($year) == $i)
				print " selected";
			print ">" . $i . "</option>\n";
		}
		print "</select>";
	} /* }}} */

	function printSequenceChooser($objArr, $keepID = -1) { /* {{{ */
		echo $this->getSequenceChooser($objArr, $keepID);
	} /* }}} */

	function getSequenceChooser($objArr, $keepID = -1) { /* {{{ */
		if (count($objArr) > 0) {
			$max = $objArr[count($objArr)-1]->getSequence() + 1;
			$min = $objArr[0]->getSequence() - 1;
		}
		else {
			$max = 1.0;
		}
		$content = "<select name=\"sequence\">\n";
		if ($keepID != -1) {
			$content .= "  <option value=\"keep\">" . getMLText("seq_keep");
		}
		if($this->params['defaultposition'] != 'start')
			$content .= "  <option value=\"".$max."\">" . getMLText("seq_end");
		if (count($objArr) > 0) {
			$content .= "  <option value=\"".$min."\">" . getMLText("seq_start");
		}
		if($this->params['defaultposition'] == 'start')
			$content .= "  <option value=\"".$max."\">" . getMLText("seq_end");
		for ($i = 0; $i < count($objArr) - 1; $i++) {
			if (($objArr[$i]->getID() == $keepID) || (($i + 1 < count($objArr)) && ($objArr[$i+1]->getID() == $keepID))) {
				continue;
			}
			$index = ($objArr[$i]->getSequence() + $objArr[$i+1]->getSequence()) / 2;
			$content .= "  <option value=\"".$index."\">" . getMLText("seq_after", array("prevname" => htmlspecialchars($objArr[$i]->getName())));
		}
		$content .= "</select>";
		return $content;
	} /* }}} */
	
	function getDocumentChooserHtml($form, $accessMode=M_READ, $exclude = -1, $default = false, $formname = '', $folder='', $partialtree=0, $skiptree=false) { /* {{{ */
		if(!$formname)
			$formname = "docid";
		$formid = md5($formname.$form);
		if(!$folder)
			$folderid = $this->params['dms']->getRootFolder()->getId();
		else
			$folderid = $folder->getID();
		$content = '';
		$content .= "<input type=\"hidden\" class=\"fileupload-group\" id=\"".$formid."\" name=\"".$formname."\" data-target-highlight=\"choosedocsearch".$formid."\" value=\"". (($default) ? $default->getID() : "") ."\">";
		$content .= "<div class=\"input-append\">\n";
		$content .= "<input type=\"text\" id=\"choosedocsearch".$formid."\" data-target=\"".$formid."\" data-provide=\"typeahead\" name=\"docname".$formid."\" value=\"". (($default) ? htmlspecialchars($default->getName()) : "") ."\" placeholder=\"".getMLText('type_to_search')."\" autocomplete=\"off\"".($default ? ' title="'.htmlspecialchars($default->getFolder()->getFolderPathPlain().' / '.$default->getName()).'"' : '')." />";
		$content .= "<button type=\"button\" class=\"btn\" id=\"cleardocument".$form."\" data-target=\"".$formid."\"><i class=\"fa fa-remove\"></i></button>";
		if(!$skiptree)
			$content .= $this->getModalBoxLink(
				array(
					'target' => 'docChooser'.$formid,
					'remote' => $this->params['settings']->_httpRoot."out/out.DocumentChooser.php?form=".$formid."&folderid=".$folderid."&partialtree=".$partialtree,
					'class' => 'btn btn-secondary',
					'title' => getMLText('document').'…'
				));
		$content .= "</div>\n";
		if(!$skiptree)
			$content .= $this->getModalBox(
				array(
					'id' => 'docChooser'.$formid,
					'title' => getMLText('choose_target_document'),
					'buttons' => array(
						array('title'=>getMLText('close'))
					)
				));
		return $content;
	} /* }}} */

	function printDocumentChooserHtml($form, $accessMode=M_READ, $exclude = -1, $default = false, $formname = '', $folder='', $partialtree=0) { /* {{{ */
		echo self::getDocumentChooserHtml($form, $accessMode, $exclude, $default, $formname, $folder, $partialtree);
	} /* }}} */

	/**
	 * This function is deprecated. Don't use it anymore. There is a generic
	 * folderSelected and documentSelected function in application.js
	 * If you extra functions to be called then define them in your own js code
	 */
	function printDocumentChooserJs($form, $formname='') { /* {{{ */
		if(!$formname)
			$formname = "docid";
		$formid = md5($formname.$form);
?>
function documentSelected<?php echo $formid ?>(id, name) {
	$('#<?php echo $formid ?>').val(id);
	$('#choosedocsearch<?php echo $formid ?>').val(name);
	$('#docChooser<?php echo $formid ?>').modal('hide');
}
function folderSelected<?php echo $formid ?>(id, name) {
}
<?php
	} /* }}} */

	function printDocumentChooser($form, $accessMode=M_READ, $exclude = -1, $default = false, $formname = '', $folder='', $partialtree=0) { /* {{{ */
		$this->printDocumentChooserHtml($form, $accessMode, $exclude, $default, $formname, $folder, $partialtree);
?>
		<script language="JavaScript">
<?php
		$this->printDocumentChooserJs($form);
?>
		</script>
<?php
	} /* }}} */

	function getFolderChooserHtml($form, $accessMode, $exclude = -1, $default = false, $formname = '', $skiptree = false) { /* {{{ */
		if(!$formname)
			$formname = "targetid";
		$formid = md5($formname.$form);
		$content = '';
		$content .= "<input type=\"hidden\" id=\"".$formid."\" name=\"".$formname."\" value=\"". (($default) ? $default->getID() : "") ."\" data-target-highlight=\"choosefoldersearch".$formid."\">";
		$content .= "<div class=\"input-append\">\n";
		$content .= "<input type=\"text\" id=\"choosefoldersearch".$formid."\" data-target=\"".$formid."\" data-provide=\"typeahead\" name=\"targetname".$formid."\" value=\"". (($default) ? htmlspecialchars($default->getName()) : "") ."\" placeholder=\"".getMLText('type_to_search')."\" autocomplete=\"off\" target=\"".$formid."\"".($default ? ' title="'.htmlspecialchars($default->getFolderPathPlain()).'"' : '')."/>";
		$content .= "<button type=\"button\" class=\"btn\" id=\"clearfolder".$formid."\" data-target=\"".$formid."\"><i class=\"fa fa-remove\"></i></button>";
		if(!$skiptree) {
			$content .= $this->getModalBoxLink(
				array(
					'target' => 'folderChooser'.$formid,
					'remote' => $this->params['settings']->_httpRoot."out/out.FolderChooser.php?form=".$formid."&mode=".$accessMode."&exclude=".$exclude,
					'class' => 'btn btn-secondary',
					'title' => getMLText('folder').'…'
				));
		}
		$content .= "</div>\n";
		if(!$skiptree) {
			$content .= $this->getModalBox(
				array(
					'id' => 'folderChooser'.$formid,
					'title' => getMLText('choose_target_folder'),
					'buttons' => array(
						array('title'=>getMLText('close'))
					)
				));
		}
		return $content;
	} /* }}} */

	function printFolderChooserHtml($form, $accessMode, $exclude = -1, $default = false, $formname = '') { /* {{{ */
		echo self::getFolderChooserHtml($form, $accessMode, $exclude, $default, $formname);
	} /* }}} */

	/**
	 * This function is deprecated. Don't use it anymore. There is a generic
	 * folderSelected and documentSelected function in application.js
	 * If you extra functions to be called then define them in your own js code
	 */
	function printFolderChooserJs($form, $formname='') { /* {{{ */
		if(!$formname)
			$formname = "targetid";
		$formid = md5($formname.$form);
?>
function folderSelected<?php echo $formid ?>(id, name) {
	$('#<?php echo $formid ?>').val(id);
	$('#choosefoldersearch<?php echo $formid ?>').val(name);
	$('#folderChooser<?php echo $formid ?>').modal('hide');
}
/*
$(document).ready(function() {
	$('#clearfolder<?php print $formid ?>').click(function(ev) {
		$('#choosefoldersearch<?php echo $formid ?>').val('');
		$('#<?php echo $formid ?>').val('');
	});
});
*/
<?php
	} /* }}} */

	function printFolderChooser($form, $accessMode, $exclude = -1, $default = false, $formname='') { /* {{{ */
		$this->printFolderChooserHtml($form, $accessMode, $exclude, $default, $formname);
?>
		<script language="JavaScript">
<?php
		$this->printFolderChooserJs($form, $formname);
?>
		</script>
<?php
	} /* }}} */

	function printKeywordChooserHtml($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		echo self::getKeywordChooserHtml($formName, $keywords, $fieldname); 
	} /* }}} */

	function getKeywordChooserHtml($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		$content = '';
		$content .= '
		    <div class="input-append">
				<input type="text" name="'.$fieldname.'" id="'.$fieldname.'" value="'.htmlspecialchars($keywords).'"'.($strictformcheck ? ' required="required"' : '').' />';
		$content .= $this->getModalBoxLink(
			array(
				'target' => 'keywordChooser',
				'remote' => $this->params['settings']->_httpRoot."out/out.KeywordChooser.php?target=".$formName,
				'class' => 'btn btn-secondary',
				'title' => getMLText('keywords').'…'
			));
		$content .= '
			</div>
';
		$content .= $this->getModalBox(
			array(
				'id' => 'keywordChooser',
				'title' => getMLText('use_default_keywords'),
				'buttons' => array(
					array('id'=>'acceptkeywords', 'title'=>getMLText('save')),
					array('title'=>getMLText('close')),
				)
			));
		return $content;
	} /* }}} */

	function printKeywordChooserJs($formName) { /* {{{ */
?>
$(document).ready(function() {
	$('#acceptkeywords').click(function(ev) {
		acceptKeywords();
	});
});
<?php
	} /* }}} */

	function printKeywordChooser($formName, $keywords='', $fieldname='keywords') { /* {{{ */
		$this->printKeywordChooserHtml($formName, $keywords, $fieldname);
?>
		<script language="JavaScript">
<?php
		$this->printKeywordChooserJs($formName);
?>
		</script>
<?php
	} /* }}} */

	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */
	protected function printAttributeValue($attribute) { /* {{{ */
		echo self::getAttributeValue($attribute);
	} /* }}} */

	function getAttributeValue($attribute) { /* {{{ */
		$dms = $this->params['dms'];
		$attrdef = $attribute->getAttributeDefinition();
		switch($attrdef->getType()) {
		case SeedDMS_Core_AttributeDefinition::type_url:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$tmp[] = '<a href="'.htmlspecialchars($attr).'">'.htmlspecialchars($attr).'</a>';
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_email:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$tmp[] = '<a mailto="'.htmlspecialchars($attr).'">'.htmlspecialchars($attr).'</a>';
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_folder:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				if($targetfolder = $dms->getFolder(intval($attr)))
					$tmp[] = '<a href="'.$this->params['settings']->_httpRoot.'out/out.ViewFolder.php?folderid='.$targetfolder->getId().'">'.htmlspecialchars($targetfolder->getName()).'</a>';
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_document:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				if($targetdoc = $dms->getDocument(intval($attr)))
					$tmp[] = '<a href="'.$this->params['settings']->_httpRoot.'out/out.ViewDocument.php?documentid='.$targetdoc->getId().'">'.htmlspecialchars($targetdoc->getName()).'</a>';
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_user:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$curuser = $dms->getUser((int) $attr);
				$tmp[] = htmlspecialchars($curuser->getFullname()." (".$curuser->getLogin().")");
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_group:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$curgroup = $dms->getGroup((int) $attr);
				$tmp[] = htmlspecialchars($curgroup->getName());
			}
			return implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_date:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$tmp[] = getReadableDate($attr);
			}
			return implode(', ', $tmp);
			break;
		default:
			return htmlspecialchars(implode(', ', $attribute->getValueAsArray()));
		}
	} /* }}} */

	function printAttributeEditField($attrdef, $attribute, $fieldname='attributes', $norequire=false, $namepostfix='', $alwaysmultiple=false) { /* {{{ */
		echo self::getAttributeEditField($attrdef, $attribute, $fieldname, $norequire, $namepostfix, $alwaysmultiple);
	} /* }}} */

	function getAttributeEditField($attrdef, $attribute, $fieldname='attributes', $norequire=false, $namepostfix='', $alwaysmultiple=false) { /* {{{ */
		$dms = $this->params['dms'];
		$attr_id = $fieldname.'_'.$attrdef->getId().($namepostfix ? '_'.$namepostfix : '');
		$attr_name = $fieldname.'['.$attrdef->getId().']'.($namepostfix ? '['.$namepostfix.']' : '');
		$content = '';
		switch($attrdef->getType()) {
		case SeedDMS_Core_AttributeDefinition::type_boolean:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
			$content .= "<input type=\"hidden\" name=\"".$attr_name."\" value=\"\" />";
			$content .= "<input type=\"checkbox\" id=\"".$attr_id."\" name=\"".$attr_name."\" value=\"1\" ".($objvalue ? 'checked' : '')." />";
			break;
		case SeedDMS_Core_AttributeDefinition::type_date:
			$objvalue = $attribute ? getReadableDate((is_object($attribute) ? $attribute->getValue() : $attribute)) : '';
			$dateformat = getConvertDateFormat($this->params['settings']->_dateformat);
       $content .= '<span class="input-append date span12 datepicker" data-date="'.getReadableDate().'" data-date-format="'.$dateformat.'" data-date-language="'.str_replace('_', '-', $this->params['session']->getLanguage()).'">
					<input id="'.$attr_id.'" class="span6" size="16" name="'.$attr_name.'" type="text" value="'.($objvalue ? getReadableDate($objvalue) : '').'">
          <span class="add-on"><i class="fa fa-calendar"></i></span>
				</span>';
			break;
		case SeedDMS_Core_AttributeDefinition::type_email:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
			$content .= "<input type=\"text\" id=\"".$attr_id."\" name=\"".$attr_name."\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required="required"' : '').' data-rule-email="true"'." />";
			break;
		/* case SeedDMS_Core_AttributeDefinition::type_float:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
			$content .= "<input type=\"text\" id=\"".$attr_id."\" name=\"".$attr_name."\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required="required"' : '')." data-rule-number=\"true\"/>";
			break; */
		case SeedDMS_Core_AttributeDefinition::type_folder:
			$objvalue = $attribute ? (is_object($attribute) ? (int) $attribute->getValue() : (int) $attribute) : 0;
			if($objvalue)
				$target = $dms->getFolder($objvalue);
			else
				$target = null;
			$content .= $this->getFolderChooserHtml("attr".$attrdef->getId(), M_READWRITE, -1, $target, $attr_name, false);
			break;
		case SeedDMS_Core_AttributeDefinition::type_document:
			$objvalue = $attribute ? (is_object($attribute) ? (int) $attribute->getValue() : (int) $attribute) : 0;
			if($objvalue)
				$target = $dms->getDocument($objvalue);
			else
				$target = null;
			$content .= $this->getDocumentChooserHtml("attr".$attrdef->getId(), M_READ, -1, $target, $attr_name);
			break;
		case SeedDMS_Core_AttributeDefinition::type_user:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValueAsArray() : (is_string($attribute) ? [$attribute] : $attribute)) : array();
			$users = $dms->getAllUsers();
			if($users) {
				$allowempty = $attrdef->getMinValues() == 0;
				$allowmultiple = $attrdef->getMultipleValues() || $alwaysmultiple;
				$content .= "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" id=\"".$attr_id."\" name=\"".$attr_name.($allowmultiple ? '[]' : '')."\"".($allowmultiple ? " multiple" : "")." data-placeholder=\"".getMLText("select_user")."\">";
				if($allowempty)
					$content .= "<option value=\"\"></option>";
				foreach($users as $curuser) {
					$content .= "<option value=\"".$curuser->getID()."\"";
					if(in_array($curuser->getID(), $objvalue))
						$content .= " selected";
					$content .= ">".htmlspecialchars($curuser->getLogin()." - ".$curuser->getFullName())."</option>";
				}
				$content .= "</select>";
			} else {
				$content .= getMLText('no_users');
			}
			break;
		case SeedDMS_Core_AttributeDefinition::type_group:
			$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValueAsArray() : (is_string($attribute) ? [$attribute] : $attribute)) : array();
			$groups = $dms->getAllGroups();
			if($groups) {
				$allowempty = $attrdef->getMinValues() == 0;
				$allowmultiple = $attrdef->getMultipleValues() || $alwaysmultiple;
				$content .= "<select class=\"chzn-select\"".($allowempty ? " data-allow-clear=\"true\"" : "")."\" id=\"".$attr_id."\" name=\"".$attr_name.($allowmultiple ? '[]' : '')."\"".($allowmultiple ? " multiple" : "")." data-placeholder=\"".getMLText("select_group")."\">";
				if($allowempty)
					$content .= "<option value=\"\"></option>";
				foreach($groups as $curgroup) {
					$content .= "<option value=\"".$curgroup->getID()."\"";
					if(in_array($curgroup->getID(), $objvalue))
						$content .= " selected";
					$content .= ">".htmlspecialchars($curgroup->getName())."</option>";
				}
				$content .= "</select>";
			} else {
				$content .= getMLText('no_groups');
			}
			break;
		default:
			if($valueset = $attrdef->getValueSetAsArray()) {
				$content .= "<input type=\"hidden\" name=\"".$attr_name."\" value=\"\"/>";
				$content .= "<select id=\"".$attr_id."\" name=\"".$attr_name;
				if($attrdef->getMultipleValues() || $alwaysmultiple) {
					$content .= "[]\" multiple";
				} else {
					$content .= "\" data-allow-clear=\"true\"";
				}
				$content .= "".((!$norequire && $attrdef->getMinValues() > 0) ? ' required="required"' : '')." class=\"chzn-select\" data-placeholder=\"".getMLText("select_value")."\">";
				if(!$attrdef->getMultipleValues() && !$alwaysmultiple) {
					$content .= "<option value=\"\"></option>";
				}
				$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValueAsArray() : $attribute) : array();
				foreach($valueset as $value) {
					if($value) {
						$content .= "<option value=\"".htmlspecialchars($value)."\"";
						if(is_array($objvalue) && in_array($value, $objvalue))
							$content .= " selected";
						elseif($value == $objvalue)
							$content .= " selected";
						$content .= ">".htmlspecialchars($value)."</option>";
					}
				}
				$content .= "</select>";
			} else {
				$objvalue = $attribute ? (is_object($attribute) ? $attribute->getValue() : $attribute) : '';
				if(strlen($objvalue) > 80) {
					$content .= "<textarea id=\"".$attr_id."\" class=\"input-xxlarge\" name=\"".$attr_name."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required="required"' : '').">".htmlspecialchars($objvalue)."</textarea>";
				} else {
					$content .= "<input type=\"text\" id=\"".$attr_id."\" name=\"".$attr_name."\" value=\"".htmlspecialchars($objvalue)."\"".((!$norequire && $attrdef->getMinValues() > 0) ? ' required="required"' : '').(in_array($attrdef->getType(), [SeedDMS_Core_AttributeDefinition::type_int, SeedDMS_Core_AttributeDefinition::type_float]) ? ' data-rule-digits="true"' : '')." />";
				}
			}
			break;
		}
		return $content;
	} /* }}} */

	function printDropFolderChooserHtml($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		echo self::getDropFolderChooserHtml($formName, $dropfolderfile, $showfolders);
	} /* }}} */

	function getDropFolderChooserHtml($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		$content =  "<div class=\"input-append\">\n";
		$content .= "<input readonly type=\"text\" class=\"fileupload-group\" id=\"dropfolderfile".$formName."\" name=\"dropfolderfile".$formName."\" value=\"".htmlspecialchars($dropfolderfile)."\">";
		$content .= "<button type=\"button\" class=\"btn\" id=\"clearfilename".$formName."\"><i class=\"fa fa-remove\"></i></button>";
		$content .= $this->getModalBoxLink(
			array(
				'target' => 'dropfolderChooser',
				'remote' => $this->params['settings']->_httpRoot."out/out.DropFolderChooser.php?form=".$formName."&dropfolderfile=".urlencode($dropfolderfile)."&showfolders=".$showfolders,
				'class' => 'btn btn-secondary',
				'title' => ($showfolders ? getMLText("choose_target_folder"): getMLText("choose_target_file")).'…'
			));
		$content .= "</div>\n";
		$content .= $this->getModalBox(
			array(
				'id' => 'dropfolderChooser',
				'title' => ($showfolders ? getMLText("choose_target_folder"): getMLText("choose_target_file")),
				'buttons' => array(
					array('title'=>getMLText('close')),
				)
			));
		return $content;
	} /* }}} */

	function printDropFolderChooserJs($formName, $showfolders=0) { /* {{{ */
?>
/* Set up a callback which is called when a folder in the tree is selected */
modalDropfolderChooser = $('#dropfolderChooser');
function fileSelected(name, form) {
//	$('#dropfolderfile<?php echo $formName ?>').val(name);
	$('#dropfolderfile'+form).val(name);
	modalDropfolderChooser.modal('hide');
}
<?php if($showfolders) { ?>
function folderSelected(name, form) {
//	$('#dropfolderfile<?php echo $formName ?>').val(name);
	$('#dropfolderfile'+form).val(name);
	modalDropfolderChooser.modal('hide');
}
<?php } ?>
$(document).ready(function() {
	$('#clearfilename<?php print $formName ?>').click(function(ev) {
		$('#dropfolderfile<?php echo $formName ?>').val('');
	});
});
<?php
	} /* }}} */

	function printDropFolderChooser($formName, $dropfolderfile="", $showfolders=0) { /* {{{ */
		$this->printDropFolderChooserHtml($formName, $dropfolderfile, $showfolders);
?>
		<script language="JavaScript">
<?php
		$this->printDropFolderChooserJs($formName, $showfolders);
?>
		</script>
<?php
	} /* }}} */

	function getImgPath($img) { /* {{{ */

//		if ( is_file($this->imgpath.$img) ) {
			return $this->imgpath.$img;
//		}
		return "";
	} /* }}} */

	function getCountryFlag($lang) { /* {{{ */
		switch($lang) {
		case "en_GB":
			return 'flags/gb.png';
			break;
		default:
			return 'flags/'.substr($lang, 0, 2).'.png';
		}
	} /* }}} */

	function printImgPath($img) { /* {{{ */
		print $this->getImgPath($img);
	} /* }}} */

	function infoMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-info\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function warningMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-warning\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function errorMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-error\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function successMsg($msg) { /* {{{ */
		echo "<div class=\"alert alert-success\">\n";
		echo $msg;
		echo "</div>\n";
	} /* }}} */

	function ___exitError($pagetitle, $error, $noexit=false, $plain=false) { /* {{{ */

		/* This is just a hack to prevent creation of js files in an error
		 * case, because they will contain this error page again. It would be much
		 * better, if there was extra error() function similar to show() and calling
		 * $view() after setting the action to 'error'. This would also allow to
		 * set separate error pages for each view.
		 */
		if(!$noexit && isset($_REQUEST['action'])) {
			if(in_array($_REQUEST['action'], array('js', 'footerjs'))) {
				exit;
			}

			if($_REQUEST['action'] == 'webrootjs') {
				$this->webrootjs();
				exit;
			}
		}

		if(!$plain) {	
			$this->htmlStartPage($pagetitle);
			$this->globalNavigation();
			$this->contentStart();
		}

		$html = '';
		$html .= "<h4>".getMLText('error')."!</h4>";
		$html .= htmlspecialchars($error);
		$this->errorMsg($html);
		if(!$plain) {	
			print "<div><button class=\"btn history-back\">".getMLText('back')."</button></div>";

			$this->contentEnd();
			$this->htmlEndPage();
		}
		
//		add_log_line(" UI::exitError error=".$error." pagetitle=".$pagetitle, PEAR_LOG_ERR);

		if($noexit)
			return;

		exit;	
	} /* }}} */

	function printNewTreeNavigation($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='') { /* {{{ */
		$this->printNewTreeNavigationHtml($folderid, $accessmode, $showdocs, $formid, $expandtree, $orderby);
?>
		<script language="JavaScript">
<?php
		$this->printNewTreeNavigationJs($folderid, $accessmode, $showdocs, $formid, $expandtree, $orderby);
?>
	</script>
<?php
	} /* }}} */

	function printNewTreeNavigationHtml($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='') { /* {{{ */
		//echo "<div id=\"jqtree".$formid."\" style=\"margin-left: 10px;\" data-url=\"../op/op.Ajax.php?command=subtree&showdocs=".$showdocs."&orderby=".$orderby."\"></div>\n";
		echo "<div id=\"jqtree".$formid."\" data-url=\"".$_SERVER['SCRIPT_NAME']."?action=subtree\"></div>\n";
	} /* }}} */

	/**
	 * Create a tree of folders using jqtree.
	 *
	 * The tree can contain folders only or include documents.
	 *
	 * @param integer $folderid current folderid. If set the tree will be
	 *   folded out and the all folders in the path will be visible
	 * @param integer $accessmode use this access mode when retrieving folders
	 *   and documents shown in the tree
	 * @param boolean $showdocs set to true if tree shall contain documents
	 *   as well.
	 * @param integer $expandtree level to which the tree shall be opened
	 * @param boolean $partialtree set to true if the given folder is the start folder
	 */
	function printNewTreeNavigationJs($folderid=0, $accessmode=M_READ, $showdocs=0, $formid='form1', $expandtree=0, $orderby='', $partialtree=false) { /* {{{ */
		function jqtree($obj, $path, $folder, $user, $accessmode, $showdocs=1, $expandtree=0, $orderby='', $level=0) { /* {{{ */
			$orderdir = (isset($orderby[1]) ? ($orderby[1] == 'd' ? 'desc' : 'asc') : 'asc');
			if($path/* || $expandtree>=$level*/) {
				if($path)
					$pathfolder = array_shift($path);
				$children = array();
				if($expandtree) {
					$subfolders = $folder->getSubFolders(isset($orderby[0]) ? $orderby[0] : '', $orderdir);
					$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, $accessmode);
				} else {
					$subfolders = array($pathfolder);
				}
				foreach($subfolders as $subfolder) {
					$node = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>(1 && ($subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs))) ? true : false, 'is_folder'=>true);
					/* if the subfolder is in the path then further unfold the tree. */
					if(/*$expandtree>=$level ||*/ $path && ($path[0]->getID() == $subfolder->getID())) {
						$node['children'] = jqtree($obj, $path, $subfolder, $user, $accessmode, $showdocs, $expandtree, $orderby, $level+1);
						if($showdocs) {
							$documents = $subfolder->getDocuments(isset($orderby[0]) ? $orderby[0] : '', $orderdir);
							$documents = SeedDMS_Core_DMS::filterAccess($documents, $user, $accessmode);
							if($obj->hasHook('filterTreeDocuments'))
								$documents = $obj->callHook('filterTreeDocuments', $folder, $documents);
							foreach($documents as $document) {
								$node2 = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
								$node['children'][] = $node2;
							}
						}
					}
					$children[] = $node;
				}
				return $children;
			} else {
				$subfolders = $folder->getSubFolders(isset($orderby[0]) ? $orderby[0] : '', $orderdir);
				$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, $accessmode);
				$children = array();
				foreach($subfolders as $subfolder) {
					$node = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>($subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs)) ? true : false, 'is_folder'=>true);
					$children[] = $node;
				}
				return $children;
			}
			return array();
		} /* }}} */

		$orderdir = (isset($orderby[1]) ? ($orderby[1] == 'd' ? 'desc' : 'asc') : 'asc');
		if($folderid && ($folder = $this->params['dms']->getFolder($folderid))) {
			if(!$partialtree) {
				$path = $folder->getPath();
				/* Get the first folder (root folder) of path */
				$folder = array_shift($path);
			}
			$node = array('label'=>$folder->getName(), 'id'=>$folder->getID(), 'load_on_demand'=>false, 'is_folder'=>true);
			if(!$folder->hasSubFolders()) {
				$node['load_on_demand'] = true;
				$node['children'] = array();
			} else {
				$node['children'] = jqtree($this, $path, $folder, $this->params['user'], $accessmode, $showdocs, 1 /*$expandtree*/, $orderby, 0);
				if($showdocs) {
					$documents = $folder->getDocuments(isset($orderby[0]) ? $orderby[0] : '', $orderdir);
					$documents = SeedDMS_Core_DMS::filterAccess($documents, $this->params['user'], $accessmode);
					if($this->hasHook('filterTreeDocuments'))
						$documents = $this->callHook('filterTreeDocuments', $folder, $documents);
					foreach($documents as $document) {
						$node2 = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
						$node['children'][] = $node2;
					}
				}
			}
			/* Nasty hack to remove the highest folder */
			if(isset($this->params['remove_root_from_tree']) && $this->params['remove_root_from_tree']) {
				foreach($node['children'] as $n)
					$tree[] = $n;
			} else {
				$tree[] = $node;
			}
			
		} else {
			if($root = $this->params['dms']->getFolder($this->params['rootfolderid']))
				$tree = array(array('label'=>$root->getName(), 'id'=>$root->getID(), 'load_on_demand'=>false, 'is_folder'=>true));
			else
				$tree = array();
		}
?>
var data = <?php echo json_encode($tree); ?>;
$(function() {
	const $tree = $('#jqtree<?php echo $formid ?>');
	$tree.tree({
//		saveState: false,
		selectable: false,
		data: data,
		saveState: 'jqtree<?php echo $formid; ?>',
		openedIcon: $('<i class="fa fa-minus-circle"></i>'),
		closedIcon: $('<i class="fa fa-plus-circle"></i>'),
/*
		_onCanSelectNode: function(node) {
			if(node.is_folder) {
				folderSelected<?= $formid ?>(node.id, node.name);
				treeFolderSelected('<?= $formid ?>', node.id, node.name);
			} else {
				documentSelected<?= $formid ?>(node.id, node.name);
				treeDocumentSelected('<?= $formid ?>', node.id, node.name);
			}
		},
*/
		autoOpen: false,
		drapAndDrop: true,
		onCreateLi: function(node, $li) {
			// Add 'icon' span before title
			if(node.is_folder)
				$li.find('.jqtree-title').prepend('<i class="fa fa-folder-o"></i> ').attr('data-name', node.name).attr('rel', 'folder_' + node.id).attr('formtoken', '<?php echo createFormKey(''); ?>').attr('data-uploadformtoken', '<?php echo createFormKey(''); ?>').attr('data-droptarget', 'folder_' + node.id).addClass('droptarget');
			else
				$li.find('.jqtree-title').prepend('<i class="fa fa-file"></i> ');
		}
	});
	// Unfold node for currently selected folder
	$('#jqtree<?php echo $formid ?>').tree('selectNode', $('#jqtree<?php echo $formid ?>').tree('getNodeById', <?php echo $folderid ?>), false, true);
	$('#jqtree<?php echo $formid ?>').on(
		'tree.click',
		function(event) {
			var node = event.node;
			if(!node)
				return;
			if(node.is_folder) {
				$('#jqtree<?php echo $formid ?>').tree('openNode', node);
<?php if($showdocs) { ?>
//			event.preventDefault();
				if(typeof node.fetched == 'undefined') {
					node.fetched = true;
					$(this).tree('loadDataFromUrl', node, function () {
						$(this).tree('openNode', node);
					});
				}
<?php } ?>
				/* folderSelectedXXXX() can still be set, e.g. for the main tree
				 * to update the folder list.
				 */
				if (typeof folderSelected<?= $formid ?> === 'function') { 
					folderSelected<?= $formid ?>(node.id, node.name);
				}
				treeFolderSelected('<?= $formid ?>', node.id, node.name);
			} else {
<?php if($showdocs) { ?>
				if (typeof documentSelected<?= $formid ?> === 'function') { 
					documentSelected<?= $formid ?>(node.id, node.name);
				}
				treeDocumentSelected('<?= $formid ?>', node.id, node.name);
<?php } ?>
			}
		}
	);
	$('#jqtree<?php echo $formid ?>').on(
		'tree.contextmenu',
		function(event) {
			// The clicked node is 'event.node'
			var node = event.node;
			if(typeof node.fetched == 'undefined') {
				node.fetched = true;
				$(this).tree('loadDataFromUrl', node);
			}
			$(this).tree('openNode', node);
		}
	);
	$("#jqtree").on('dragenter', function (e) {
		attr_rel = $(e.srcElement).attr('rel');
		if(typeof attr_rel == 'undefined')
			return;
		target_type = attr_rel.split("_")[0];
		target_id = attr_rel.split("_")[1];
		var node = $(this).tree('getNodeById', parseInt(target_id));
		if(typeof node.fetched == 'undefined') {
			node.fetched = true;
			$(this).tree('loadDataFromUrl', node, function() {$(this).tree('openNode', node);});
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Return json data for sub tree of navigation tree
	 */
	function printNewTreeNavigationSubtree($folderid, $showdocs=0, $orderby='') { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$folder = $dms->getFolder($folderid);
		if (!is_object($folder)) return '';
		
		$subfolders = $folder->getSubFolders($orderby);
		$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, M_READ);
		$tree = array();
		foreach($subfolders as $subfolder) {
			$loadondemand = $subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs);
			$level = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>$loadondemand, 'is_folder'=>true);
			if(!$subfolder->hasSubFolders())
				$level['children'] = array();
			$tree[] = $level;
		}
		if($showdocs) {
			$documents = $folder->getDocuments($orderby);
			$documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);
			foreach($documents as $document) {
				$level = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
				$tree[] = $level;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($tree);
	} /* }}} */

	/**
	 * Deprecated!
	 */
	function __printTreeNavigation($folderid, $showtree){ /* {{{ */
		if ($showtree==1){
			$this->contentHeading("<a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderid."&showtree=0\"><i class=\"fa fa-minus-circle\"></i></a>", true);
			$this->contentContainerStart();
?>
	<script language="JavaScript">
	function folderSelected(id, name) {
		window.location = '<?= $this->params['settings']->_httpRoot ?>out/out.ViewFolder.php?folderid=' + id;
	}
	</script>
<?php
			$this->printNewTreeNavigation($folderid, M_READ, 0, '');
			$this->contentContainerEnd();
		} else {
			$this->contentHeading("<a href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderid."&showtree=1\"><i class=\"fa fa-plus-circle\"></i></a>", true);
		}
	} /* }}} */

	/**
	 * Print clipboard in div container
	 *
	 * @param array clipboard
	 */
	function printClipboard($clipboard, $previewer){ /* {{{ */
		echo "<div id=\"clipboard-container\" class=\"_clipboard-container\">\n";
		$this->contentHeading(getMLText("clipboard").'<span id="clipboard-float"><i class="fa fa-sort"></i></span>', true);
		echo "<div id=\"main-clipboard\">\n";
?>
		<div class="ajax" data-view="Clipboard" data-action="mainClipboard"></div>
<?php
		echo "</div>\n";
		echo "</div>\n";
	} /* }}} */

	/**
	 * Wrap text in inline editing tags
	 *
	 * @param string text
	 */
	function printInlineEdit($text, $object){ /* {{{ */
		if(!empty($this->params['settings']->_inlineEditing)) {
			echo "<span class=\"editable\" contenteditable=\"true\"";
			if($object->isType('document'))
				echo " data-document=\"".$object->getId()."\" data-formtoken=\"".createFormKey('setdocumentname')."\"";
			echo ">".$text;
			echo "</span>\n";
		} else
			echo $text;
	} /* }}} */

	/**
	 * Print button with link for deleting a document
	 *
	 * This button is used in document listings (e.g. on the ViewFolder page)
	 * for deleting a document. In seeddms version < 4.3.9 this was just a
	 * link to the out/out.RemoveDocument.php page which asks for confirmation
	 * an than calls op/op.RemoveDocument.php. Starting with version 4.3.9
	 * the button just opens a small popup asking for confirmation and than
	 * calls the ajax command 'deletedocument'. The ajax call is called
	 * in the click function of 'button.removedocument'. That button needs
	 * to have two attributes: 'rel' for the id of the document, and 'msg'
	 * for the message shown by notify if the document could be deleted.
	 *
	 * @param object $document document to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteDocumentButton($document, $msg, $return=false){ /* {{{ */
		$docid = $document->getID();
		$content = '';
		$content .= '<a class="delete-document-btn" rel="'.$docid.'" msg="'.getMLText($msg).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_document", array ("documentname" => $document->getName())), ENT_QUOTES).'" title="'.getMLText("delete").'"><i class="fa fa-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteDocumentButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-document-btn').click(function(ev) {
			$('body').on('click', 'a.delete-document-btn', function(ev){
				ev.stopPropagation();
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removedocument')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("rm_document")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.get('".$this->params['settings']->_httpRoot."op/op.Ajax.php',
							{ command: 'deletedocument', id: id, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-document-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	/**
	 * Print button with link for deleting a folder
	 *
	 * This button works like document delete button
	 * {@link SeedDMS_Bootstrap_Style::printDeleteDocumentButton()}
	 *
	 * @param object $folder folder to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteFolderButton($folder, $msg, $return=false){ /* {{{ */
		$folderid = $folder->getID();
		$content = '';
		$content .= '<a class="delete-folder-btn" rel="'.$folderid.'" msg="'.getMLText($msg).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_folder", array ("foldername" => $folder->getName())), ENT_QUOTES).'" title="'.getMLText("delete").'"><i class="fa fa-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteFolderButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-folder-btn').click(function(ev) {
			$('body').on('click', 'a.delete-folder-btn', function(ev){
				ev.stopPropagation();
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removefolder')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("rm_folder")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.get('".$this->params['settings']->_httpRoot."op/op.Ajax.php',
							{ command: 'deletefolder', id: id, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-folder-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	function printLockButton($document, $msglock, $msgunlock, $return=false) { /* {{{ */
		$accessobject = $this->params['accessobject'];
		$docid = $document->getID();
		if($document->isLocked()) {
			if(!$accessobject->check_controller_access('UnlockDocument'))
				return '';
			$icon = 'unlock';
			$msg = $msgunlock;
			$title = 'unlock_document';
		} else {
			if(!$accessobject->check_controller_access('LockDocument'))
				return '';
			$icon = 'lock';
			$msg = $msglock;
			$title = 'lock_document';
		}
		$content = '';
		$content .= '<a class="lock-document-btn" rel="'.$docid.'" msg="'.getMLText($msg).'" title="'.getMLText($title).'" data-formtoken="'.createFormKey('tooglelockdocument').'"><i class="fa fa-'.$icon.'"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printAccessButton($object, $return=false) { /* {{{ */
		$accessobject = $this->params['accessobject'];
		$content = '';
		$objid = $object->getId();
		if($object->isType('document')) {
			if($accessobject->check_view_access('DocumentAccess'))
				$content .= '<a class="access-document-btn" href="'.$this->params['settings']->_httpRoot.'out/out.DocumentAccess.php?documentid='.$objid.'" title="'.getMLText('edit_document_access').'"><i class="fa fa-bolt"></i></a>';
		} elseif($object->isType('folder')) {
			if($accessobject->check_view_access('FolderAccess'))
				$content .= '<a class="access-folder-btn" href="'.$this->params['settings']->_httpRoot.'out/out.FolderAccess.php?folderid='.$objid.'" title="'.getMLText('edit_folder_access').'"><i class="fa fa-bolt"></i></a>';
		}
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a number of ids into
	 * a select box.
	 *
	 * Clicking in the button will preset the comma seperated list of ids
	 * in data-ref as options in the select box with name $name
	 *
	 * @param string $name id of select box
	 * @param array $ids list of option values
	 */
	function getSelectPresetButtonHtml($name, $ids) { /* {{{ */
		return '<span id="'.$name.'_btn" class="selectpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOver".$name).'" data-ref="'.$name.'" data-ids="'.implode(",", $ids).'"><i class="fa fa-arrow-left"></i></span>';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a number of ids into
	 * a select box.
	 *
	 * Clicking in the button will preset the comma seperated list of ids
	 * in data-ref as options in the select box with name $name
	 *
	 * @param string $name id of select box
	 * @param array $ids list of option values
	 */
	function printSelectPresetButtonHtml($name, $ids) { /* {{{ */
		echo self::getSelectPresetButtonHtml($name, $ids);
	} /* }}} */

	/**
	 * Javascript code for select preset button
	 */
	function printSelectPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.selectpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('ids') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			// Use attr() instead of data() because data() converts to int which cannot be split
			items = $(ev.currentTarget).attr('data-ids');
			arr = items.split(",");
			for(var i in arr) {
				$("#"+target+" option[value='"+arr[i]+"']").attr("selected", "selected");
			}
//			$("#"+target).trigger("chosen:updated");
			$("#"+target).trigger("change");
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Get HTML for left-arrow with link which takes over a string into
	 * a input field.
	 *
	 * Clicking on the button will preset the string
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function getInputPresetButtonHtml($name, $text, $sep='') { /* {{{ */
		return '<span id="'.$name.'_btn" class="inputpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOverAttributeValue").'" data-ref="'.$name.'" data-text="'.(is_array($text) ? implode($sep, $text) : htmlspecialchars($text)).'"'.($sep ? " data-sep=\"".$sep."\"" : "").'><i class="fa fa-arrow-left"></i></span>';
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a string into
	 * a input field.
	 *
	 * Clicking on the button will preset the string
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function printInputPresetButtonHtml($name, $text, $sep='') { /* {{{ */
		echo self::getInputPresetButtonHtml($name, $text, $sep);
	} /* }}} */

	/**
	 * Javascript code for input preset button
	 * This code workѕ for input fields and single select fields
	 */
	function printInputPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.inputpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('text') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			value = $(ev.currentTarget).data('text');
			sep = $(ev.currentTarget).data('sep');
			if(sep) {
				// Use attr() instead of data() because data() converts to int which cannot be split
				arr = value.split(sep);
				for(var i in arr) {
					$("#"+target+" option[value='"+arr[i]+"']").attr("selected", "selected");
				}
			} else {
				$("#"+target).val(value);
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Get HTML for left-arrow with link which takes over a boolean value
	 * into a checkbox field.
	 *
	 * Clicking on the button will preset the checkbox
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function getCheckboxPresetButtonHtml($name, $text) { /* {{{ */
?>
		return '<span id="'.$name.'_btn" class="checkboxpreset_btn" style="cursor: pointer;" title="'.getMLText("takeOverAttributeValue").'" data-ref="'.$name.'" data-text="'.(is_array($text) ? implode($sep, $text) : htmlspecialchars($text)).'"'.($sep ? " data-sep=\"".$sep."\"" : "").'><i class="fa fa-arrow-left"></i></span>';
<?php
	} /* }}} */

	/**
	 * Output left-arrow with link which takes over a boolean value
	 * into a checkbox field.
	 *
	 * Clicking on the button will preset the checkbox
	 * in data-ref the value of the input field with name $name
	 *
	 * @param string $name id of select box
	 * @param string $text text
	 */
	function printCheckboxPresetButtonHtml($name, $text) { /* {{{ */
		self::getCheckboxPresetButtonHtml($name, $text);
	} /* }}} */

	/**
	 * Javascript code for checkboxt preset button
	 * This code workѕ for checkboxes
	 */
	function printCheckboxPresetButtonJs() { /* {{{ */
?>
$(document).ready( function() {
	$('.checkboxpreset_btn').click(function(ev){
		ev.preventDefault();
		if (typeof $(ev.currentTarget).data('text') != 'undefined') {
			target = $(ev.currentTarget).data('ref');
			value = $(ev.currentTarget).data('text');
			if(value) {
				$("#"+target).attr('checked', '');
			} else {
				$("#"+target).removeAttribute('checked');
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Print button with link for deleting an attribute value
	 *
	 * This button is used in document listings (e.g. on the ViewFolder page)
	 * for deleting a document. In seeddms version < 4.3.9 this was just a
	 * link to the out/out.RemoveDocument.php page which asks for confirmation
	 * an than calls op/op.RemoveDocument.php. Starting with version 4.3.9
	 * the button just opens a small popup asking for confirmation and than
	 * calls the ajax command 'deletedocument'. The ajax call is called
	 * in the click function of 'button.removedocument'. That button needs
	 * to have two attributes: 'rel' for the id of the document, and 'msg'
	 * for the message shown by notify if the document could be deleted.
	 *
	 * @param object $document document to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	function printDeleteAttributeValueButton($attrdef, $value, $msg, $return=false){ /* {{{ */
		$content = '';
		$content .= '<a class="delete-attribute-value-btn" rel="'.$attrdef->getID().'" msg="'.getMLText($msg).'" attrvalue="'.htmlspecialchars($value, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_attr_value", array ("attrdefname" => $attrdef->getName())), ENT_QUOTES).'"><i class="fa fa-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	function printDeleteAttributeValueButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
//			$('.delete-attribute-value-btn').click(function(ev) {
			$('body').on('click', 'a.delete-attribute-value-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				attrvalue = $(ev.currentTarget).attr('attrvalue');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removeattrvalue')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("rm_attr_value")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.post('".$this->params['settings']->_httpRoot."op/op.AttributeMgr.php',
							{ action: 'removeattrvalue', attrdefid: id, attrvalue: attrvalue, formtoken: formtoken },
							function(data) {
								if(data.success) {
									$('#table-row-attrvalue-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
							'json'
						);
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	function printClickDocumentJs() { /* {{{ */
		$onepage = $this->params['onepage'];
		if($onepage) {
?>
/* catch click on a document row in the list folders and documents */
$('body').on('click', '[id^=\"table-row-document\"] td:nth-child(2)', function(ev) {
	if(ev.shiftKey) {
		$(ev.currentTarget).parent().toggleClass('selected');
	} else {
		attr_id = $(ev.currentTarget).parent().attr('id').split('-')[3];
		window.location = '<?= $this->params['settings']->_httpRoot ?>out/out.ViewDocument.php?documentid=' + attr_id;
	}
});
<?php
		}
	} /* }}} */

	/**
	 * Print js code which catches clicks on folder rows
	 *
	 * This method will catch a click on a folder row and changes the
	 * window.location to the out.ViewFolder.php page
	 * This code is not needed on the out.ViewFolder.php page itself, because
	 * a click will just reload the list of folders and documents.
	 */
	function printClickFolderJs() { /* {{{ */
		$onepage = $this->params['onepage'];
		if($onepage) {
?>
/* catch click on a document row in the list folders and documents */
$('body').on('click', '[id^=\"table-row-folder\"] td:nth-child(2)', function(ev) {
	if(ev.shiftKey) {
		$(ev.currentTarget).parent().toggleClass('selected');
	} else {
		attr_id = $(ev.currentTarget).parent().data('target-id');
		if(typeof attr_id == 'undefined')
			attr_id = $(ev.currentTarget).parent().attr('id').split('-')[3];
		window.location = '<?= $this->params['settings']->_httpRoot ?>out/out.ViewFolder.php?folderid=' + attr_id;
	}
});
<?php
		}
	} /* }}} */

	/**
	 * Return HTML containing the path of a document or folder
	 *
	 * This is used for showing the path of a document/folder below the title
	 * in document/folder lists like on the search page.
	 *
	 * @param object $object
	 * @return string
	 */
	function getListRowPath($object) { /* {{{ */
		if(!$object)
			return '';
		$belowtitle = '';
		$folder = $object->getParent();
		if($folder) {
			$belowtitle .= "<br /><span style=\"font-size: 85%;\">".getMLText('in_folder').": /";
			$path = $folder->getPath();
			for ($i = 1; $i  < count($path); $i++) {
				$belowtitle .= htmlspecialchars($path[$i]->getName())."/";
			}
			$belowtitle .= "</span>";
		}
		return $belowtitle;
	} /* }}} */

	public function folderListHeaderImage() { /* {{{ */
		$folder = $this->getParam('folder');
		$onepage = $this->params['onepage'];
		$parent = ($folder && $onepage) ? $folder->getParent() : null;
		$headcol = ($parent ? '<button class="btn btn-mini btn-secondary btn-sm" id="goto-parent" data-parentid="'.$parent->getID().'"><i class="fa fa-arrow-up"></i></button>' : '')."</th>\n";	
		return $headcol;	
	} /* }}} */

	public function folderListHeaderName() { /* {{{ */
		$folder = $this->getParam('folder');
		$headcol = getMLText("name");
		if($folder) {
			$folderid = $folder->getId();
			$orderby = $this->params['orderby'];
			$orderdir = (isset($orderby[1]) ? ($orderby[1] == 'd' ? 'desc' : 'asc') : 'asc');
			$headcol .= " <a class=\"order-btn\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderid .($orderby=="n"||$orderby=="na"?"&orderby=nd":"&orderby=n")."\" data-orderby=\"".($orderby=="n"||$orderby=="na"?"nd":"n")."\"title=\"".getMLText("sort_by_name")."\">".($orderby=="n"||$orderby=="na"?' <i class="fa fa-sort-alpha-asc selected"></i>':($orderby=="nd"?' <i class="fa fa-sort-alpha-desc selected"></i>':' <i class="fa fa-sort-alpha-asc"></i>'))."</a>";
			$headcol .= " <a class=\"order-btn\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderid .($orderby=="s"||$orderby=="sa"?"&orderby=sd":"&orderby=s")."\" data-orderby=\"".($orderby=="s"||$orderby=="sa"?"sd":"s")."\" title=\"".getMLText("sort_by_sequence")."\">".($orderby=="s"||$orderby=="sa"?' <i class="fa fa-sort-numeric-asc selected"></i>':($orderby=="sd"?' <i class="fa fa-sort-numeric-desc selected"></i>':' <i class="fa fa-sort-numeric-asc"></i>'))."</a>";
			$headcol .= " <a class=\"order-btn\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=". $folderid .($orderby=="d"||$orderby=="da"?"&orderby=dd":"&orderby=d")."\" data-orderby=\"".($orderby=="d"||$orderby=="da"?"dd":"d")."\" title=\"".getMLText("sort_by_date")."\">".($orderby=="d"||$orderby=="da"?' <i class="fa fa-sort-amount-asc selected"></i>':($orderby=="dd"?' <i class="fa fa-sort-amount-desc selected"></i>':' <i class="fa fa-sort-amount-asc"></i>'))."</a>";
		}
		return $headcol;	
	} /* }}} */

	public function folderListHeader() { /* {{{ */
		$content = "<table id=\"viewfolder-table\" class=\"table table-condensed table-sm table-hover\">";
		$content .= "<thead>\n<tr>\n";
		$headcols = array();
		$headcols['image'] = $this->folderListHeaderImage();	
		$headcols['name'] = $this->folderListHeaderName();
		if($ec = $this->callHook('folderListHeaderExtraColumns'))
				$headcols = array_merge($headcols, $ec);
		$headcols['status'] = getMLText("status");
		$headcols['action'] = getMLText("action");
		foreach($headcols as $headcol)
			$content .= "<th>".$headcol."</th>\n";
		$content .= "</tr>\n</thead>\n";
		return $content;
	} /* }}} */

	/**
	 * Start the row for a folder in list of documents and folders
	 *
	 * For a detailed description see
	 * {@link SeedDMS_Bootstrap_Style::folderListRowStart()}
	 */
	function documentListRowStart($document, $class='') { /* {{{ */
		$docID = $document->getID();
		return "<tr id=\"table-row-document-".$docID."\" data-target-id=\"".$docID."\" class=\"table-row-document droptarget ".($class ? ' '.$class : '')."\" data-droptarget=\"document_".$docID."\" rel=\"document_".$docID."\" formtoken=\"".createFormKey('')."\" draggable=\"true\" data-name=\"".htmlspecialchars($document->getName(), ENT_QUOTES)."\">";
	} /* }}} */

	function documentListRowEnd($document) { /* {{{ */
			return "</tr>\n";
	} /* }}} */

	function documentListRowStatus($latestContent) { /* {{{ */
		$user = $this->params['user'];
		$workflowmode = $this->params['workflowmode'];
		$document = $latestContent->getDocument();

		$status = $latestContent->getStatus();
		$attentionstr = '';
		if ( $document->isLocked() ) {
			$attentionstr .= "<i class=\"fa fa-lock\" title=\"". getMLText("locked_by").": ".htmlspecialchars($document->getLockingUser()->getFullName())."\"></i> ";
		}
		if($workflowmode == 'advanced') {
			$workflow = $latestContent->getWorkflow();
			if($workflow && $latestContent->needsWorkflowAction($user)) {
				$attentionstr .= "<i class=\"fa fa-exclamation-triangle\" title=\"". getMLText("workflow").": ".htmlspecialchars($workflow->getName())."\"></i> ";
			}
		}
		$content = '';
		if($attentionstr)
			$content .= $attentionstr."<br />";

		/* Retrieve attacheѕ files */
		$files = $document->getDocumentFiles($latestContent->getVersion());
		$files = SeedDMS_Core_DMS::filterDocumentFiles($user, $files);

		/* Retrieve linked documents */
		$links = $document->getDocumentLinks();
		$links = SeedDMS_Core_DMS::filterDocumentLinks($user, $links);

		/* Retrieve reverse linked documents */
		$revlinks = $document->getReverseDocumentLinks();
		$revlinks = SeedDMS_Core_DMS::filterDocumentLinks($user, $revlinks);

		$content .= "<div style=\"font-size: 85%;\">";
		if(count($files))
			$content .= '<i class="fa fa-paperclip" title="'.getMLText("linked_files").'"></i> '.count($files)."<br />";
		if(count($links) || count($revlinks))
			$content .= '<i class="fa fa-link" title="'.getMLText("linked_documents").'"></i> '.count($links)."/".count($revlinks)."<br />";
		if($status["status"] == S_IN_WORKFLOW && $workflowmode == 'advanced') {
			if($workflowstate = $latestContent->getWorkflowState())
				$content .= '<span title="'.getOverallStatusText($status["status"]).': '.($workflow ? htmlspecialchars($workflow->getName()) : '').'">'.($workflowstate ? htmlspecialchars($workflowstate->getName()) : '').'</span>';
		} else {
			$content .= $this->getOverallStatusIcon($status['status']);
		}
		$content .= "</div>";
		return $content;
	} /* }}} */

	function documentListRowAction($document, $previewer, $skipcont=false, $version=0, $extracontent=array()) { /* {{{ */
		$user = $this->params['user'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessop = $this->params['accessobject'];
		$onepage = $this->params['onepage'];

		$content = '';
		$content .= "<div class=\"list-action\">";
		$actions = array();
		if(!empty($extracontent['begin_action_list']))
			$content .= $extracontent['begin_action_list'];
		if($accessop->check_view_access('RemoveDocument')) {
			if($document->getAccessMode($user) >= M_ALL) {
				$actions['remove_document'] = $this->printDeleteDocumentButton($document, 'splash_rm_document', true);
			} else {
				$actions['remove_document'] = '<span style="padding: 2px; color: #CCC;"><i class="fa fa-remove"></i></span>';
			}
		}
		$docID = $document->getID();
		if($document->getAccessMode($user) >= M_READWRITE) {
			$actions['edit_document'] = '<a href="'.$this->params['settings']->_httpRoot.'out/out.EditDocument.php?documentid='.$docID.'" title="'.getMLText("edit_document_props").'"><i class="fa fa-edit"></i></a>';
		} else {
			$actions['edit_document'] = '<span style="padding: 2px; color: #CCC;"><i class="fa fa-edit"></i></span>';
		}
		if($document->getAccessMode($user) >= M_READWRITE) {
			$actions['lock_document'] = $this->printLockButton($document, 'splash_document_locked', 'splash_document_unlocked', true);
		}
		if($document->getAccessMode($user) >= M_READWRITE) {
			$actions['document_access'] = $this->printAccessButton($document, true);
		}
		if($enableClipboard) {
			$actions['add_to_clipboard'] = '<a class="addtoclipboard" rel="D'.$docID.'" msg="'.getMLText('splash_added_to_clipboard').'" title="'.getMLText("add_to_clipboard").'"><i class="fa fa-copy"></i></a>';
		}
		if($onepage)
			$actions['view_document'] = '<a href="'.$this->params['settings']->_httpRoot.'out/out.ViewDocument.php?documentid='.$docID.'" title="'.getMLText("view_document").'"><i class="fa fa-eye"></i></a>';

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'documentRowAction')) {
	      $actions = $hookObj->documentRowAction($this, $document, $actions);
			}
		}

		foreach($actions as $action) {
			if(is_string($action))
				$content .= $action;
		}

		if(!empty($extracontent['end_action_list']))
			$content .= $extracontent['end_action_list'];
		$content .= "</div>";
		return $content;
	} /* }}} */

	/**
	 * Return HTML of a single row in the document list table
	 *
	 * @param object $document
	 * @param object $previewer
	 * @param boolean $skipcont set to true if embrasing tr shall be skipped
	 */
	function documentListRow($document, $previewer, $skipcont=false, $version=0, $extracontent=array()) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$showtree = $this->params['showtree'];
		$workflowmode = $this->params['workflowmode'];
		$previewwidth = $this->params['previewWidthList'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessop = $this->params['accessobject'];
		$onepage = $this->params['onepage'];

		$content = '';

		$owner = $document->getOwner();
		$comment = $document->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
		$docID = $document->getID();

		if($version) {
			$latestContent = $this->callHook('documentContent', $document, $version);
			if($latestContent === null)
				$latestContent = $document->getContentByVersion($version);
		} else {
			$latestContent = $this->callHook('documentLatestContent', $document);
			if($latestContent === null)
				$latestContent = $document->getLatestContent();
		}

		if($latestContent) {
			if(!$skipcont)
				$content .= $this->documentListRowStart($document);

			$previewer->createPreview($latestContent);
			$version = $latestContent->getVersion();
			
			if($ec = $this->callHook('documentListRowExtraContent', $document, $latestContent))
				$extracontent = array_merge($extracontent, $ec);

			$content .= "<td>";
			if (file_exists($dms->contentDir . $latestContent->getPath())) {
				$previewhtml = $this->callHook('documentListPreview', $previewer, $document, $latestContent);
				if(is_string($previewhtml))
					$content .= $previewhtml;
				else {
					if($accessop->check_controller_access('Download', array('action'=>'version')))
						$content .= "<a draggable=\"false\" href=\"".$this->params['settings']->_httpRoot."op/op.Download.php?documentid=".$docID."&version=".$version."\">";
					if($previewer->hasPreview($latestContent)) {
						$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->params['settings']->_httpRoot."op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
					} else {
						$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" ".($previewwidth ? "width=\"".$previewwidth."\"" : "")."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
					}
					if($accessop->check_controller_access('Download', array('action'=>'version')))
						$content .= "</a>";
				}
			} else
				$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"".$previewwidth."\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
			$content .= "</td>";

			$content .= "<td class=\"wordbreak\"".($onepage ? ' style="cursor: pointer;"' : '').">";
			if($onepage)
				$content .= "<b".($onepage ? ' title="Id:'.$document->getId().'"' : '').">".htmlspecialchars($document->getName()) . "</b>";
			else
				$content .= "<a draggable=\"false\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewDocument.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>";
			if(isset($extracontent['below_title']))
				$content .= $extracontent['below_title'];
			$content .= "<br />";
			if($belowtitle = $this->callHook('documentListRowBelowTitle', $document, $latestContent))
				$content .= $belowtitle;
			else
				$content .= "<span style=\"font-size: 85%; font-style: italic; color: #666; \">".getMLText('owner').": <b>".htmlspecialchars($owner->getFullName())."</b>, ".getMLText('creation_date').": <b>".getReadableDate($document->getDate())."</b>, ".getMLText('version')." <b>".$version."</b> - <b>".getReadableDate($latestContent->getDate())."</b>".($document->expires() ? ", ".getMLText('expires').": <b>".getReadableDate($document->getExpires())."</b>" : "")."</span>";
			if($comment) {
				$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
			}
			if($categories = $document->getCategories()) {
				$content .= "<br />";
				foreach($categories as $category) {
					$color = substr(md5($category->getName()), 0, 6);
					$content .= "<span class=\"badge\" style=\"background-color: #".$color."; color: #".self::getContrastColor($color).";\">".$category->getName()."</span> ";
				}
			}
			if(!empty($extracontent['bottom_title']))
				$content .= $extracontent['bottom_title'];
			$content .= "</td>\n";

			if(!empty($extracontent['columns'])) {
				foreach($extracontent['columns'] as $col)
					$content .= '<td>'.$col.'</td>';
			}

			$content .= "<td nowrap>";
			$content .= $this->documentListRowStatus($latestContent);
			if($accessop->check_view_access($this, array('action'=>'receptionBar')) /*$owner->getID() == $user->getID()*/ && $receiptStatus = $latestContent->getReceiptStatus()) {
				$rstat = array('-1'=>0, '0'=>0, '1'=>0, '-2'=>0);
				$allcomments = array('-1'=>array(), '1'=>array());
				foreach ($receiptStatus as $r) {
					$rstat[''.$r['status']]++;
					if($r['comment']) {
//						$allcomments[''.$r['status']][] = htmlspecialchars($r['comment']);
						$m5 = md5(trim($r['comment']));
						if(isset($allcomments[''.$r['status']][$m5]))
							$allcomments[''.$r['status']][$m5]['n']++;
						else
							$allcomments[''.$r['status']][$m5] = array('n'=>1, 'c'=>htmlspecialchars(trim($r['comment'])));
					}
				}
				$totalreceipts = $rstat['-1'] + $rstat['0'] + $rstat['1'];
				if($totalreceipts) {
					$content .= "
<div class=\"progress\">
<div class=\"bar bar-success\" style=\"width: ".round($rstat['1']/$totalreceipts*100)."%;\">".($rstat['1'] ? $rstat['1']."/".$totalreceipts : '').($allcomments['1'] ? " ".$this->printPopupBox('<i class="fa fa-comment"></i>', implode('<br />', formatComment($allcomments['1'])), true) : "")."</div>
	<div class=\"bar bar-danger\" style=\"width: ".round($rstat['-1']/$totalreceipts*100)."%;\">".($rstat['-1'] ? $rstat['-1']."/".$totalreceipts : '').($allcomments['-1'] ? " ".$this->printPopupBox('<i class="fa fa-comment"></i>', implode('<br />', formatComment($allcomments['-1'])), true) : "")."</div>
</div>";
				}
			}
			$content .= "</small></td>";
//				$content .= "<td>".$version."</td>";
			$content .= "<td>";
			$content .= $this->documentListRowAction($document, $previewer, $skipcont, $version, $extracontent);
			$content .= "</td>";
			if(!empty($extracontent['columns_last'])) {
				foreach($extracontent['columns_last'] as $col)
					$content .= '<td>'.$col.'</td>';
			}

			if(!$skipcont)
				$content .= $this->documentListRowEnd($document);
		}
		return $content;
	} /* }}} */

	/**
	 * Start the row for a folder in list of documents and folders
	 *
	 * This method creates the starting tr tag for a new table row containing
	 * a folder list entry. The tr tag contains various attributes which are
	 * used for removing the table line and to make drap&drop work.
	 *
	 * id=table-row-folder-<id> : used for identifying the row when removing the table
	 *   row after deletion of the folder by clicking on the delete button in that table
	 *   row.
	 * data-droptarget=folder_<id> : identifies the folder represented by this row
	 *   when it used as a target of the drag&drop operation.
	 *   If an element (either a file or a dragged item) is dropped on this row, the
	 *   data-droptarget will be evaluated to identify the underlying dms object.
	 *   Dropping a file on a folder will upload that file into the folder. Droping
	 *   an item (which is currently either a document or a folder) from the page will
	 *   move that item into the folder.
	 * rel=folder_<id> : This data is put into drag data when a drag starts. When the
	 *   item is dropped on some other item this data will identify the source object.
	 *   The attributes data-droptarget and rel are usually equal. At least there is
	 *   currently no scenario where they are different.
	 * formtoken=<token> : token made of key 'movefolder'
	 *   formtoken is also placed in the drag data just like the value of attibute 'rel'.
	 *   This is always set to a value made of 'movefolder'.
	 * data-uploadformtoken=<token> : token made of key 'adddocument'
	 * class=table-row-folder : The class must have a class named 'table-row-folder' in
	 *   order to be draggable and to extract the drag data from the attributes 'rel' and
	 *   'formtoken'
	 *
	 * @param object $folder
	 * @return string starting tr tag for a table
	 */
	function folderListRowStart($folder, $class='') { /* {{{ */
		return "<tr id=\"table-row-folder-".$folder->getID()."\" draggable=\"true\" data-droptarget=\"folder_".$folder->getID()."\" rel=\"folder_".$folder->getID()."\" class=\"folder table-row-folder droptarget".($class ? ' '.$class : '')."\" data-uploadformtoken=\"".createFormKey('')."\" formtoken=\"".createFormKey('')."\" data-name=\"".htmlspecialchars($folder->getName(), ENT_QUOTES)."\">";
	} /* }}} */

	function folderListRowEnd($folder) { /* {{{ */
			return "</tr>\n";
	} /* }}} */

	function folderListRowAction($subFolder, $skipcont=false, $extracontent=array()) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
//		$folder = $this->params['folder'];
		$showtree = $this->params['showtree'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessop = $this->params['accessobject'];
		$onepage = $this->params['onepage'];

		$content = '';
		$content .= "<div class=\"list-action\">";
		$actions = array();
		if(!empty($extracontent['begin_action_list']))
			$content .= $extracontent['begin_action_list'];
		$subFolderAccessMode = $subFolder->getAccessMode($user);
		if ($accessop->check_view_access('RemoveFolder')) {
			if($subFolderAccessMode >= M_ALL) {
				$actions['remove_folder'] = $this->printDeleteFolderButton($subFolder, 'splash_rm_folder', true);
			} else {
				$actions['remove_folder'] = '<span style="padding: 2px; color: #CCC;"><i class="fa fa-remove"></i></span>';
			}
		}
		if ($accessop->check_view_access('EditFolder')) {
			if($subFolderAccessMode >= M_READWRITE) {
				$actions['edit_folder'] = '<a class_="btn btn-mini" href="'.$this->params['settings']->_httpRoot.'out/out.EditFolder.php?folderid='.$subFolder->getID().'" title="'.getMLText("edit_folder_props").'"><i class="fa fa-edit"></i></a>';
			} else {
				$actions['edit_folder'] = '<span style="padding: 2px; color: #CCC;"><i class="fa fa-edit"></i></span>';
			}
		}
		if($subFolderAccessMode >= M_READWRITE) {
			$actions['folder_access'] = $this->printAccessButton($subFolder, true);
		}
		if($enableClipboard) {
			$actions['add_to_clipboard'] = '<a class="addtoclipboard" rel="F'.$subFolder->getID().'" msg="'.getMLText('splash_added_to_clipboard').'" title="'.getMLText("add_to_clipboard").'"><i class="fa fa-copy"></i></a>';
		}
		if($onepage)
			$actions['view_folder'] = '<a href="'.$this->params['settings']->_httpRoot.'out/out.ViewFolder.php?folderid='.$subFolder->getID().'" title="'.getMLText("view_folder").'"><i class="fa fa-eye"></i></a>';

		/* Do not use $this->callHook() because $menuitems must be returned by the the
		 * first hook and passed to next hook. $this->callHook() will just pass
		 * the menuitems to each single hook. Hence, the last hook will win.
		 */
		$hookObjs = $this->getHookObjects();
		foreach($hookObjs as $hookObj) {
			if (method_exists($hookObj, 'folderRowAction')) {
	      $actions = $hookObj->folderRowAction($this, $folder, $actions);
			}
		}

		foreach($actions as $action) {
			if(is_string($action))
				$content .= $action;
		}

		if(!empty($extracontent['end_action_list']))
			$content .= $extracontent['end_action_list'];
		$content .= "</div>";
		return $content;
	} /* }}} */

	function folderListRowStatus($subFolder) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$showtree = $this->params['showtree'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];

		$content = "<div style=\"font-size: 85%;\">";
		if($enableRecursiveCount) {
			if($user->isAdmin()) {
				/* No need to check for access rights in countChildren() for
				 * admin. So pass 0 as the limit.
				 */
				$cc = $subFolder->countChildren($user, 0);
				if($cc['folder_count'])
					$content .= '<i class="fa fa-folder" title="'.getMLText("folders").'"></i> '.$cc['folder_count']."<br />";
				if($cc['document_count'])
					$content .= '<i class="fa fa-file" title="'.getMLText("documents").'"></i> '.$cc['document_count'];
			} else {
				$cc = $subFolder->countChildren($user, $maxRecursiveCount);
				if($maxRecursiveCount > 5000)
					$rr = 100.0;
				else
					$rr = 10.0;
				if($cc['folder_count'])
					$content .= '<i class="fa fa-folder" title="'.getMLText("folders").'"></i> '.(!$cc['folder_precise'] ? '~'.(round($cc['folder_count']/$rr)*$rr) : $cc['folder_count'])."<br />";
				if($cc['document_count'])
					$content .= '<i class="fa fa-file" title="'.getMLText("documents").'"></i> '.(!$cc['document_precise'] ? '~'.(round($cc['document_count']/$rr)*$rr) : $cc['document_count']);
			}
		} else {
			/* FIXME: the following is very inefficient for just getting the number of
			 * subfolders and documents. Making it more efficient is difficult, because
			 * the access rights need to be checked.
			 */
			$subsub = $subFolder->getSubFolders();
			$subsub = SeedDMS_Core_DMS::filterAccess($subsub, $user, M_READ);
			$subdoc = $subFolder->getDocuments();
			$subdoc = SeedDMS_Core_DMS::filterAccess($subdoc, $user, M_READ);
			if(count($subsub))
				$content .= '<i class="fa fa-folder" title="'.getMLText("folders").'"></i> '.count($subsub)."<br />";
			if(count($subdoc))
				$content .= '<i class="fa fa-file" title="'.getMLText("documents").'"></i> '.count($subdoc);
		}
		$content .= "</div>";
		return $content;
	} /* }}} */

	function folderListRow($subFolder, $skipcont=false, $extracontent=array()) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
//		$folder = $this->params['folder'];
		$showtree = $this->params['showtree'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$enableClipboard = $this->params['enableclipboard'];
		$accessop = $this->params['accessobject'];
		$onepage = $this->params['onepage'];

		if(!$subFolder)
			return '';

		$owner = $subFolder->getOwner();
		$comment = $subFolder->getComment();
		if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";

		if($ec = $this->callHook('folderListRowExtraContent', $subFolder))
			$extracontent = array_merge($extracontent, $ec);

		$content = '';
		if(!$skipcont)
			$content .= $this->folderListRowStart($subFolder);
		$content .= "<td><a draggable=\"false\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\"><img draggable=\"false\" src=\"".$this->getMimeIcon(".folder")."\" width=\"24\" height=\"24\" border=0></a></td>\n";
		if($onepage)
			$content .= "<td class=\"wordbreak\" style=\"cursor: pointer;\">" . "<b title=\"Id:".$subFolder->getId()."\">".htmlspecialchars($subFolder->getName())."</b>";
		else
			$content .= "<td class=\"wordbreak\"><a draggable=\"false\" href=\"".$this->params['settings']->_httpRoot."out/out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\">" . htmlspecialchars($subFolder->getName()) . "</a>";
		if(isset($extracontent['below_title']))
			$content .= $extracontent['below_title'];
		$content .= "<br /><span style=\"font-size: 85%; font-style: italic; color: #666;\">".getMLText('owner').": <b>".htmlspecialchars($owner->getFullName())."</b>, ".getMLText('creation_date').": <b>".date('Y-m-d', $subFolder->getDate())."</b></span>";
		if($comment) {
			$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
		}
		if(isset($extracontent['bottom_title']))
			$content .= $extracontent['bottom_title'];
		$content .= "</td>\n";
//		$content .= "<td>".htmlspecialchars($owner->getFullName())."</td>";
		$content .= "<td colspan=\"1\" nowrap>";
		$content .= $this->folderListRowStatus($subFolder);
		$content .= "</td>";
		$content .= "<td>";
		$content .= $this->folderListRowAction($subFolder, $skipcont, $extracontent);
		$content .= "</td>";
		if(!$skipcont)
			$content .= $this->folderListRowEnd($subFolder);
		return $content;
	} /* }}} */

	function show(){ /* {{{ */
		parent::show();
	} /* }}} */

	function error(){ /* {{{ */
		parent::error();
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$pagetitle = $this->params['pagetitle'];
		$errormsg = $this->params['errormsg'];
		$plain = $this->params['plain'];
		$noexit = $this->params['noexit'];

		if(!$plain) {	
			$this->htmlStartPage($pagetitle);
			$this->globalNavigation();
			$this->contentStart();
		}

		$html = '';
		$html .= "<h4>".getMLText('error')."!</h4>";
		$html .= htmlspecialchars($errormsg);
		$this->errorMsg($html);
		print "<div><button class=\"btn history-back\">".getMLText('back')."</button></div>";
		
		$this->contentEnd();
		$this->htmlEndPage();
		
		add_log_line(" UI::exitError error=".$errormsg." pagetitle=".$pagetitle, PEAR_LOG_ERR);

		if($noexit)
			return;

		exit;	
	} /* }}} */

	/**
	 * Return HTML Template for jumploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function getFineUploaderTemplate() { /* {{{ */
		return '
<script type="text/template" id="qq-template">
<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="'.getMLText('drop_files_here').'">
	<div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
		<div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
		</div>
	<div class="input-append">
	<div class="qq-upload-drop-area-selector qq-upload-drop-area" _qq-hide-dropzone>
		<span class="qq-upload-drop-area-text-selector"></span>
	</div>
	<span class="btn qq-upload-button-selector qq-upload-button">'.getMLText('browse').'&hellip;</span>
	</div>
	<span class="qq-drop-processing-selector qq-drop-processing">
		<span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
	</span>
	<ul class="qq-upload-list-selector qq-upload-list unstyled" aria-live="polite" aria-relevant="additions removals">
		<li>
			<div class="progress qq-progress-bar-container-selector">
				<div class="bar qq-progress-bar-selector qq-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<span class="qq-upload-spinner-selector qq-upload-spinner"></span>
			<img class="qq-thumbnail-selector" qq-max-size="100" qq-server-scale>
			<span class="qq-upload-file-selector qq-upload-file"></span>
			<span class="qq-upload-size-selector qq-upload-size"></span>
			<button class="btn btn-mini qq-btn qq-upload-cancel-selector qq-upload-cancel">Cancel</button>
			<span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
		</li>
	</ul>
	<dialog class="qq-alert-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
		</div>
	</dialog>

	<dialog class="qq-confirm-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
			<button class="btn qq-ok-button-selector">Ok</button>
		</div>
	</dialog>

	<dialog class="qq-prompt-dialog-selector">
		<div class="qq-dialog-message-selector"></div>
		<input type="text">
		<div class="qq-dialog-buttons">
			<button class="btn qq-cancel-button-selector">Cancel</button>
			<button class="btn qq-ok-button-selector">Ok</button>
		</div>
	</dialog>
</div>
</script>
';
	} /* }}} */

	/**
	 * Output HTML Code for Fine Uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function printFineUploaderHtml($prefix='userfile') { /* {{{ */
		echo self::getFineUploaderHtml($prefix);
	} /* }}} */

	/**
	 * Get HTML Code for Fine Uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function getFineUploaderHtml($prefix='userfile') { /* {{{ */
		$html = '<div id="'.$prefix.'-fine-uploader"></div>
		<input type="hidden" '.($prefix=='userfile' ? 'class="do_validate" ' : '').'id="'.$prefix.'-fine-uploader-uuids" name="'.$prefix.'-fine-uploader-uuids" value="" />
		<input type="hidden" id="'.$prefix.'-fine-uploader-names" name="'.$prefix.'-fine-uploader-names" value="" />';
		return $html;
	} /* }}} */

	/**
	 * Output Javascript Code for fine uploader
	 *
	 * @param string $uploadurl URL where post data is send
	 * @param integer $folderid id of folder where document is saved
	 * @param integer $maxfiles maximum number of files allowed to upload
	 * @param array $fields list of post fields
	 */
	function printFineUploaderJs($uploadurl, $partsize=0, $maxuploadsize=0, $multiple=true, $prefix='userfile', $formname='form1') { /* {{{ */
?>
$(document).ready(function() {
	<?php echo $prefix; ?>uploader = new qq.FineUploader({
		debug: false,
		autoUpload: false,
		multiple: <?php echo ($multiple ? 'true' : 'false'); ?>,
		element: $('#<?php echo $prefix; ?>-fine-uploader')[0],
		template: 'qq-template',
		request: {
			endpoint: '<?php echo $uploadurl."?formkey=".md5($this->params['settings']->_encryptionKey.'uploadchunks'); ?>'
		},
<?php echo ($maxuploadsize > 0 ? '
		validation: {
			sizeLimit: '.$maxuploadsize.'
		},
' : ''); ?>
		chunking: {
			enabled: true,
			<?php echo $partsize ? 'partSize: '.(int)$partsize.",\n" : ''; ?>
			mandatory: true
		},
		messages: {
			sizeError: '{file} is too large, maximum file size is {sizeLimit}.'
		},
		callbacks: {
			onComplete: function(id, name, json, xhr) {
			},
			onAllComplete: function(succeeded, failed) {
				var uuids = Array();
				var names = Array();
				for (var i = 0; i < succeeded.length; i++) {
					uuids.push(this.getUuid(succeeded[i]))
					names.push(this.getName(succeeded[i]))
				}
				$('#<?php echo $prefix; ?>-fine-uploader-uuids').val(uuids.join(';'));
				$('#<?php echo $prefix; ?>-fine-uploader-names').val(names.join(';'));
				/* Run upload only if all files could be uploaded */
				if(succeeded.length > 0 && failed.length == 0)
					document.getElementById('<?= $formname ?>').submit();
			},
			onError: function(id, name, reason, xhr) {
				noty({
					text: reason,
					type: 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 3500,
				});
			}
		}
	});
});
<?php
	} /* }}} */

	/**
	 * Output a protocol
	 *
	 * @param object $attribute attribute
	 */
	protected function printProtocol($latestContent, $type="") { /* {{{ */
		$dms = $this->params['dms'];
		$document = $latestContent->getDocument();
		$accessop = $this->params['accessobject'];
?>
		<legend><?php printMLText($type.'_log'); ?></legend>
		<table class="table table-condensed">
			<tr><th><?php printMLText('name'); ?></th><th><?php printMLText('last_update'); ?>, <?php printMLText('comment'); ?></th><th><?php printMLText('status'); ?></th></tr>
<?php
		switch($type) {
		case "review":
			$statusList = $latestContent->getReviewStatus(10);
			break;
		case "approval":
			$statusList = $latestContent->getApprovalStatus(10);
			break;
		case "revision":
			$statusList = $latestContent->getRevisionStatus(10);
			break;
		case "receipt":
			$statusList = $latestContent->getReceiptStatus(10);
			break;
		default:
			$statusList = array();
		}
		foreach($statusList as $rec) {
			echo "<tr>";
			echo "<td>";
			switch ($rec["type"]) {
				case 0: // individual.
					$required = $dms->getUser($rec["required"]);
					if (!is_object($required)) {
						$reqName = getMLText("unknown_user")." '".$rec["required"]."'";
					} else {
						$reqName = htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
					}
					break;
				case 1: // Approver is a group.
					$required = $dms->getGroup($rec["required"]);
					if (!is_object($required)) {
						$reqName = getMLText("unknown_group")." '".$rec["required"]."'";
					}
					else {
						$reqName = "<i>".htmlspecialchars($required->getName())."</i>";
					}
					break;
			}
			echo $reqName;
			echo "</td>";
			echo "<td>";
			echo "<i style=\"font-size: 80%;\">".getLongReadableDate($rec['date'])." - ";
			$updateuser = $dms->getUser($rec["userID"]);
			if(!is_object($updateuser))
				echo getMLText("unknown_user");
			else
				echo htmlspecialchars($updateuser->getFullName()." (".$updateuser->getLogin().")");
			echo "</i>";
			if($rec['comment'])
				echo "<br />".htmlspecialchars($rec['comment']);
			switch($type) {
			case "review":
				if($accessop->check_controller_access('Download', array('action'=>'review')))
					if($rec['file']) {
						echo "<br />";
						echo "<a href=\"".$this->params['settings']->_httpRoot."op/op.Download.php?documentid=".$document->getID()."&reviewlogid=".$rec['reviewLogID']."\" class=\"btn btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
					}
				break;
			case "approval":
				if($accessop->check_controller_access('Download', array('action'=>'approval')))
					if($rec['file']) {
						echo "<br />";
						echo "<a href=\"".$this->params['settings']->_httpRoot."op/op.Download.php?documentid=".$document->getID()."&approvelogid=".$rec['approveLogID']."\" class=\"btn btn-mini\"><i class=\"fa fa-download\"></i> ".getMLText('download')."</a>";
					}
				break;
			}
			echo "</td>";
			echo "<td>";
			switch($type) {
			case "review":
				echo getReviewStatusText($rec["status"]);
				break;
			case "approval":
				echo getApprovalStatusText($rec["status"]);
				break;
			case "revision":
				echo getRevisionStatusText($rec["status"]);
				break;
			case "receipt":
				echo getReceiptStatusText($rec["status"]);
				break;
			default:
			}
			echo "</td>";
			echo "</tr>";
		}
?>
				</table>
<?php
	} /* }}} */

	/**
	 * Show progressbar
	 *
	 * @param double $value value
	 * @param double $max 100% value
	 */
	protected function getProgressBar($value, $max=100.0) { /* {{{ */
		if($max > $value) {
			$used = (int) ($value/$max*100.0+0.5);
			$free = 100-$used;
		} else {
			$free = 0;
			$used = 100;
		}
		$html = '
		<div class="progress">
			<div class="bar bar-danger" style="width: '.$used.'%;"></div>
		  <div class="bar bar-success" style="width: '.$free.'%;"></div>
		</div>';
		return $html;
	} /* }}} */

	/**
	 * Output a timeline for a document
	 *
	 * @param object $document document
	 */
	protected function printTimelineJs($timelineurl, $height=300, $start='', $end='', $skip=array(), $onselect="") { /* {{{ */
		if(!$timelineurl)
			return;
?>
		var timeline;
		var data;

		// specify options
		var options = {
			'width':  '100%',
			'height': '100%',
<?php
		if($start) {
			$tmp = explode('-', $start);
			echo "\t\t\t'min': new Date(".$tmp[0].", ".($tmp[1]-1).", ".$tmp[2]."),\n";
		}
		if($end) {
			$tmp = explode('-', $end);
			echo "'\t\t\tmax': new Date(".$tmp[0].", ".($tmp[1]-1).", ".$tmp[2]."),\n";
		}
?>
			'editable': false,
			'selectable': true,
			'style': 'box',
			'locale': '<?php echo $this->params['session']->getLanguage() ?>'
		};

		$(document).ready(function () {
		// Instantiate our timeline object.
		timeline = new links.Timeline(document.getElementById('timeline'), options);
<?php
		if($onselect):
?>
		links.events.addListener(timeline, 'select', <?= $onselect ?>);
<?php
		endif;
?>
		$.getJSON(
			'<?php echo $timelineurl ?>', 
			function(data) {
				$.each( data, function( key, val ) {
					val.start = new Date(val.start);
				});
				timeline.draw(data);
			}
		);
		});
<?php
	} /* }}} */

	protected function printTimelineHtml($height) { /* {{{ */
?>
	<div id="timeline" style="height: <?php echo $height ?>px;"></div>
<?php
	} /* }}} */

	protected function printTimeline($timelineurl, $height=300, $start='', $end='', $skip=array()) { /* {{{ */
		echo "<script type=\"text/javascript\">\n";
		$this->printTimelineJs($timelineurl, $height, $start, $end, $skip);
		echo "</script>";
		$this->printTimelineHtml($height);
	} /* }}} */

	public function printPopupBox($title, $content, $ret=false) { /* {{{ */
		$id = md5(uniqid());
		/*
		$this->addFooterJS('
$("body").on("click", "span.openpopupbox", function(e) {
	$(""+$(e.target).data("href")).toggle();
//	$("div.popupbox").toggle();
});
');
		 */
		$html = '
		<span class="openpopupbox" data-href="#'.$id.'">'.$title.'</span>
		<div id="'.$id.'" class="popupbox" style="display: none;">
		'.$content.'
			<span class="closepopupbox"><i class="fa fa-remove"></i></span>
		</div>';
		if($ret)
			return $html;
		else
			echo $html;
	} /* }}} */

	public function printAccordion($title, $content, $open=false) { /* {{{ */
		$id = substr(md5(uniqid()), 0, 4);
?>
		<div class="accordion" id="accordion<?php echo $id; ?>">
			<div class="accordion-group">
				<div class="accordion-heading">
					<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion<?php echo $id; ?>" href="#collapse<?php echo $id; ?>">
						<?php echo $title; ?>
					</a>
				</div>
				<div id="collapse<?php echo $id; ?>" class="accordion-body collapse<?= $open ? " in" : "" ?>">
					<div class="accordion-inner">
<?php
		echo $content;
?>
					</div>
				</div>
			</div>
		</div>
<?php
	} /* }}} */

	public function printAccordion2($title, $content) { /* {{{ */
		$id = substr(md5(uniqid()), 0, 4);
?>
		<div class="accordion2" id="accordion<?php echo $id; ?>">
			<a class="accordion2-toggle" data-toggle="collapse" data-parent="#accordion<?php echo $id; ?>" href="#collapse<?php echo $id; ?>">
<?php
			$this->contentHeading($title);
?>
			</a>
			<div id="collapse<?php echo $id; ?>" class="collapse" style="height: 0px;">
<?php
		echo $content;
?>
			</div>
		</div>
<?php
	} /* }}} */
}

class_alias('SeedDMS_Theme_Style', 'SeedDMS_Bootstrap_Style');
