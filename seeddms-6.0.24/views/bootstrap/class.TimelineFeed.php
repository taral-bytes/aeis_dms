<?php
/**
 * Implementation of Feed view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C)2016 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
//require_once("class.Bootstrap.php");

require_once("vendor/autoload.php");

use \FeedWriter\RSS2;

/**
 * Class which outputs the html page for UserList view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C)2016 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TimelineFeed extends SeedDMS_Theme_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$httproot = $this->params['httproot'];
		$skip = $this->params['skip'];
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];
		$cachedir = $this->params['cachedir'];
		$sitename = $this->params['sitename'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$timeout = $this->params['timeout'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time();
		}

		$baseurl = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$httproot;
		$feed = new RSS2;
		$feed->setTitle($sitename.': Recent Changes');
		$feed->setLink($baseurl);
		$feed->setDescription('Show recent changes in SeedDMS.');
		// Image title and link must match with the 'title' and 'link' channel elements for RSS 2.0,
		// which were set above.
//		$feed->setImage('Testing & Checking the Feed Writer project', 'https://github.com/mibe/FeedWriter', 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d9/Rss-feed.svg/256px-Rss-feed.svg.png');
		// Use core setChannelElement() function for other optional channel elements.
		// See http://www.rssboard.org/rss-specification#optionalChannelElements
		// for other optional channel elements. Here the language code for American English and
		$feed->setChannelElement('language', str_replace('_', '-', $user->getLanguage()));
		// The date when this feed was lastly updated. The publication date is also set.
		$feed->setDate(date(DATE_RSS, time()));
		$feed->setChannelElement('pubDate', date(\DATE_RSS, time() /*strtotime('2013-04-06')*/));
		// You can add additional link elements, e.g. to a PubSubHubbub server with custom relations.
		// It's recommended to provide a backlink to the feed URL.
		$feed->setSelfLink($baseurl.'out/out.TimelineFeed.php');
//		$feed->setAtomLink('http://pubsubhubbub.appspot.com', 'hub');
		// You can add more XML namespaces for more custom channel elements which are not defined
		// in the RSS 2 specification. Here the 'creativeCommons' element is used. There are much more
		// available. Have a look at this list: http://feedvalidator.org/docs/howto/declare_namespaces.html
//		$feed->addNamespace('creativeCommons', 'http://backend.userland.com/creativeCommonsRssModule');
//		$feed->setChannelElement('creativeCommons:license', 'http://www.creativecommons.org/licenses/by/1.0');
		// If you want you can also add a line to publicly announce that you used
		// this fine piece of software to generate the feed. ;-)
//		$feed->addGenerator();

		if($data = $dms->getTimeline($from, $to)) {
			foreach($data as $i=>$item) {
				switch($item['type']) {
				case 'add_version':
					$msg = getMLText('timeline_'.$item['type'], array(), null, $user->getLanguage());
					break;
				case 'add_file':
					$msg = getMLText('timeline_'.$item['type'], array(), null, $user->getLanguage());
					break;
				case 'status_change':
					$msg = getMLText('timeline_'.$item['type'], array('version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])), null, $user->getLanguage());
					break;
				default:
					$msg = '???';
				}
				$data[$i]['msg'] = $msg;
			}

			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout);
			foreach($data as $item) {
				if($item['type'] == 'status_change')
					$classname = $item['type']."_".$item['status'];
				else
					$classname = $item['type'];
				if(!$skip || !in_array($classname, $skip)) {
					$doc = $item['document'];
					$owner = $doc->getOwner();
					$d = makeTsFromLongDate($item['date']);
					$newItem = $feed->createNewItem();
					$newItem->setTitle($doc->getName()." (".$item['msg'].")");
					$newItem->setLink($baseurl.'out/out.ViewDocument.php?documentid='.$doc->getID());
					$newItem->setDescription("<h2>".$item['msg']."</h2>".
						"<p>".getMLText('comment', array(), null, $user->getLanguage()).": <b>".$doc->getComment()."</b></p>".
						"<p>".getMLText('owner', array(), null, $user->getLanguage()).": <b><a href=\"mailto:".htmlspecialchars($owner->getEmail())."\">".htmlspecialchars($owner->getFullName())."</a></b></p>".
						"<p>".getMLText("creation_date", array(), null, $user->getLanguage()).": <b>".getLongReadableDate($doc->getDate())."</p>"
					);
					$newItem->setDate(date('c', $d));
					$newItem->setAuthor($owner->getFullName(), preg_match('/.+@.+/', $owner->getEmail()) == 1 ? $owner->getEmail() : null);
					$newItem->setId($baseurl.'out/out.ViewDocument.php?documentid='.$doc->getID()."&kkk=".$classname, true);
					if(!empty($item['version'])) {
						$version = $doc->getContentByVersion($item['version']);
						$previewer->createPreview($version);
						if($previewer->hasPreview($version)) {
							$token = new SeedDMS_JwtToken($settings->_encryptionKey);
							$data = array('d'=>$doc->getId(), 'v'=>$item['version'], 'u'=>$user->getId(), 'w'=>$previewwidthdetail,);
							$hash =  $token->jwtEncode($data);
							$newItem->addElement('enclosure', null, array('url' => $baseurl.'op/op.TimelineFeedPreview.php?hash='.$hash, 'length'=>$previewer->getFileSize($version), 'type'=>'image/png'));
						}
					}
					$feed->addItem($newItem);
				}
			}
		}

		// OK. Everything is done. Now generate the feed.
		// If you want to send the feed directly to the browser, use the printFeed() method.
		$myFeed = $feed->generateFeed();
		// Do anything you want with the feed in $myFeed. Why not send it to the browser? ;-)
		// You could also save it to a file if you don't want to invoke your script every time.
		header('Content-Type: application/rss+xml');
		echo $myFeed;
	} /* }}} */
}
