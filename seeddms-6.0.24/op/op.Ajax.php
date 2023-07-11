<?php
//    MyDMS. Document Management System
//    Copyright (C) 2010-2016 Uwe Steinmann
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

require_once("../inc/inc.Settings.php");
require_once("../inc/inc.Utils.php");
require_once("../inc/inc.LogInit.php");
require_once("../inc/inc.Language.php");
require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassNotificationService.php");
require_once("../inc/inc.ClassEmailNotify.php");
require_once("../inc/inc.ClassUI.php");
require_once("../inc/inc.ClassController.php");
require_once("../inc/inc.Notification.php");

require_once("../inc/inc.ClassSession.php");
require_once("../inc/inc.ClassPasswordStrength.php");
require_once("../inc/inc.ClassPasswordHistoryManager.php");

/* Load session */
if (isset($_COOKIE["mydms_session"])) {
	$dms_session = $_COOKIE["mydms_session"];
	$session = new SeedDMS_Session($db);
	if(!$resArr = $session->load($dms_session)) {
		header('Content-Type: application/json');
		echo json_encode(array('error'=>1));
		exit;
	}

	/* Update last access time */
	$session->updateAccess($dms_session);

	/* Load user data */
	$user = $dms->getUser($resArr["userID"]);
	if (!is_object($user)) {
		header('Content-Type: application/json');
		echo json_encode(array('error'=>1));
		exit;
	}

	if($resArr["su"] && $su = $dms->getUser($resArr["su"])) {
		if($user->isAdmin() || $user->maySwitchToUser($su)) {
			$user = $su;
		} else {
			$session->resetSu();
		}
	}
	$dms->setUser($user);
	if($settings->_useHomeAsRootFolder && !$user->isAdmin() && $user->getHomeFolder()) {
		$dms->checkWithinRootDir = true;
		$dms->setRootFolderID($user->getHomeFolder());
	}
	$role = $user->getRole();
	$dms->noReadForStatus = $role->getNoAccess();

	include $settings->_rootDir . "languages/" . $resArr["language"] . "/lang.inc";
} else {
	$user = null;
}

/* make sure the browser doesn't cache the output of this page.
 * Edge doesn't if not explicitly told to not do it, see bug #280
 */
header("Cache-Control: no-cache,no-store");

$command = isset($_REQUEST["command"]) ? $_REQUEST["command"] : '';
switch($command) {
	case 'checkpwstrength': /* {{{ */
		$ps = new Password_Strength();
		$ps->set_password($_REQUEST["pwd"]);
		if($settings->_passwordStrengthAlgorithm == 'simple')
			$ps->simple_calculate();
		else
			$ps->calculate();
		$score = $ps->get_score();
		if($settings->_passwordStrength) {
			if($score >= $settings->_passwordStrength) {
				header('Content-Type: application/json');
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>1));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>0));
			}
		} else {
			header('Content-Type: application/json');
			echo json_encode(array('error'=>0, 'strength'=>$score));
		}
		break; /* }}} */

	case 'sessioninfo': /* {{{ */
		if($user) {
			header('Content-Type: application/json');
			echo json_encode($resArr);
		}	
		break; /* }}} */

		/* Used for document chooser */
	case 'searchdocument': /* {{{ */
		if($user) {
			$query = $_GET['query'];
			$status = isset($_GET['status']) ? (is_array($_GET['status']) ? $_GET['status'] : array($_GET['status'])) : array();

			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=$dms->getRootFolder(), $owner=null, $status, $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x1, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['docs'] as $hit) {
					if($hit->getAccessMode($user, 'search') >= M_READ) {
						if($hit->getLatestContent()) {
							$result[] = $hit->getID().'#'.$hit->getName();
						}
					}
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

		/* Used for folder chooser */
	case 'searchfolder': /* {{{ */
		if($user) {
			$query = $_GET['query'];

			if(false !== ($pos = strpos($query, '/'))) {
				$subquery = substr($query, 0, $pos);
				$hits = $dms->search($subquery, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=$dms->getRootFolder(), $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x2, $expirationstartdate=array(), $expirationenddate=array());
				if($hits) {
					if(count($hits['folders']) == 1) {
						$hit = $hits['folders'][0];
						$basefolder = $dms->getFolder($hit->getID());
						if($basefolder->getAccessMode($user, 'search') >= M_READ) {
							if($subquery = substr($query, $pos+1)) {
								$hits = $dms->search($subquery, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=$basefolder, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x2, $expirationstartdate=array(), $expirationenddate=array());
								if($hits) {
									$result = array();
									foreach($hits['folders'] as $hit) {
										if($hit->getAccessMode($user, 'search') >= M_READ)
											//$result[] = $hit->getID().'#'.$basefolder->getName().'/'.$hit->getName();
											$result[] = $hit->getID().'#'.$hit->getFolderPathPlain(true, '/');
									}
									header('Content-Type: application/json');
									echo json_encode($result);
									return;
								}
							} else {
								$subfolders = $basefolder->getSubFolders();
								$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, M_READ);
								$result = array();
								foreach($subfolders as $subfolder) {
									//$result[] = $subfolder->getID().'#'.$basefolder->getName().'/'.$subfolder->getName();
									$result[] = $subfolder->getID().'#'.$subfolder->getFolderPathPlain(true, '/');
								}
								header('Content-Type: application/json');
								echo json_encode($result);
								return;
							}
						}
					}
				}
			}
			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=$dms->getRootFolder(), $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x2, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['folders'] as $hit) {
					if($hit->getAccessMode($user, 'search') >= M_READ)
						$result[] = $hit->getID().'#'.$hit->getFolderPathPlain(true, '/');
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

		/* The subtree command is deprecated. It has been moved into view */
	case 'subtree': /* {{{ */
		if($user) {
			if(empty($_GET['node']))
				$folder = $dms->getHomeFolder();
			else
				$folder = $dms->getFolder((int) $_GET['node']);
			if (!is_object($folder)) return '';
			if(empty($_GET['showdocs']))
				$showdocs = false;
			else
				$showdocs = true;
			if(empty($_GET['orderby']))
				$orderby = $settings->_sortFoldersDefault;
			else
				$orderby = $_GET['orderby'];

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
	//		echo json_encode(array(array('label'=>'test1', 'id'=>1, 'load_on_demand'=> true), array('label'=>'test2', 'id'=>2, 'load_on_demand'=> true)));
		}
		break; /* }}} */

	case 'addtoclipboard': /* {{{ */
		if($user) {
			if (isset($_GET["id"]) && is_numeric($_GET["id"]) && isset($_GET['type'])) {
				switch($_GET['type']) {
					case "folder":
						$session->addToClipboard($dms->getFolder($_GET['id']));
						break;
					case "document":
						$session->addToClipboard($dms->getDocument($_GET['id']));
						break;
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>getMLText('splash_added_to_clipboard')));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('error')));
			}
		}
		break; /* }}} */

	case 'removefromclipboard': /* {{{ */
		if($user) {
			if (isset($_GET["id"]) && is_numeric($_GET["id"]) && isset($_GET['type'])) {
				switch($_GET['type']) {
					case "folder":
						$session->removeFromClipboard($dms->getFolder($_GET['id']));
						break;
					case "document":
						$session->removeFromClipboard($dms->getDocument($_GET['id']));
						break;
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('error')));
			}
		}
		break; /* }}} */

	case 'testmail': /* {{{ */
		if($user && $user->isAdmin()) {
			if($user->getEmail()) {
				$emailobj = new SeedDMS_EmailNotify($dms, $settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword);
				$emailobj->setDebug(true);
				$params = array();

				ob_start();
				$ret = $emailobj->toIndividual($settings->_smtpSendFrom, $user, "testmail_subject", "testmail_body", $params);
				$debugoutput = ob_get_clean();
				if($ret) {
					echo json_encode(array("error"=>0, "msg"=>"Sending email succeded", "data"=>$debugoutput));
				} else {
					echo json_encode(array("error"=>1, "msg"=>"Sending email failed", "data"=>$debugoutput));
				}
			} else {
				echo json_encode(array("error"=>1, "msg"=>"Your account has no email address set", "data"=>""));
			}
		}
		break; /* }}} */

	case 'clearclipboard': /* {{{ */
		if($user) {
			$session->clearClipboard();
			header('Content-Type: application/json');
			echo json_encode(array('success'=>true, 'message'=>getMLText('splash_cleared_clipboard')));
		}
		break; /* }}} */

	case 'movefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mfolder = $dms->getFolder($_REQUEST['folderid']);
				if($mfolder) {
					if ($mfolder->getAccessMode($user, 'moveFolder') >= M_READWRITE) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user, 'moveFolder') >= M_READWRITE) {
								$oldFolder = $mfolder->getParent();
								if($mfolder->setParent($folder)) {
									if($notifier) {
										$notifier->sendMovedFolderMail($mfolder, $user, $oldFolder);
									}
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>getMLText('splash_move_folder'), 'data'=>''));
									add_log_line();
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_folder_id'), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_folder_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'movedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mdocument = $dms->getDocument($_REQUEST['docid']);
				if($mdocument) {
					if ($mdocument->getAccessMode($user, 'moveDocument') >= M_READWRITE) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user, 'moveDocument') >= M_READWRITE) {
								$oldFolder = $mdocument->getFolder();
								if($mdocument->setFolder($folder)) {
									if(isset($_REQUEST['sequence'])) {
										$mdocument->setSequence((float) $_REQUEST['sequence']);
									}
									if($notifier) {
										$notifier->sendMovedDocumentMail($mdocument, $user, $oldFolder);
									}
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>getMLText('splash_move_document'), 'data'=>''));
									add_log_line();
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving document', 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_folder_id'), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'transfercontent': /* {{{ */
		if($user) {
			if(!checkFormKey('', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mdocument = $dms->getDocument($_REQUEST['docid']);
				if($mdocument) {
					if ($mdocument->getAccessMode($user, 'transferContent') >= M_READWRITE) {
						/* Check if source document has more than one version */
						$versions = $mdocument->getContent();
						if(count($versions) == 1) {
							if($targetdoc = $dms->getDocument($_REQUEST['targetdocumentid'])) {
								if($targetdoc->getAccessMode($user, 'transferContent') >= M_READWRITE) {
									if($targetdoc->addContent($versions[0]->getComment(), $versions[0]->getUser(), $dms->contentDir . $versions[0]->getPath(), $versions[0]->getOriginalFileName(), $versions[0]->getFileType(), $versions[0]->getMimeType())) { //, $reviewers=array(), $approvers=array(), $version=0, $attributes=array(), $workflow=null) {
										if($mdocument->remove()) {
											header('Content-Type: application/json');
											echo json_encode(array('success'=>true, 'message'=>getMLText('splash_transfer_content'), 'data'=>''));
											add_log_line();
										} else {
											header('Content-Type: application/json');
											echo json_encode(array('success'=>false, 'message'=>'Error removing source document', 'data'=>''));
										}
									} else {
										header('Content-Type: application/json');
										echo json_encode(array('success'=>false, 'message'=>'Error transfering content', 'data'=>''));
									}
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('splash_source_doc_has_versions'), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'linkdocument': /* {{{ */
		if($user) {
			if(!checkFormKey('', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mdocument = $dms->getDocument($_REQUEST['docid']);
				if($mdocument) {
					if ($mdocument->getAccessMode($user, 'linkDocument') >= M_READ) {
						if($targetdoc = $dms->getDocument($_REQUEST['targetdocumentid'])) {
							if($targetdoc->getAccessMode($user, 'linkDocument') >= M_READWRITE) {
								if($targetdoc->addDocumentLink($mdocument->getID(), $user->getID(), false)) {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>getMLText('splash_link_document'), 'data'=>''));
									add_log_line();
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error linking document', 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('removefolder', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$folder = $dms->getFolder($_REQUEST['id']);
				if($folder) {
					if ($folder->getAccessMode($user, 'removeFolder') >= M_READWRITE) {
						$parent=$folder->getParent();
						$fnl =	$folder->getNotifyList();
						$pnl =	$parent->getNotifyList();
						$nl = array(
							'users'=>array_unique(array_merge($fnl['users'], $pnl['users']), SORT_REGULAR),
							'groups'=>array_unique(array_merge($fnl['groups'], $pnl['groups']), SORT_REGULAR)
						);
						$foldername = $folder->getName();
						function removePreviews($arr, $document) {
							$previewer = $arr[0];

							$previewer->deleteDocumentPreviews($document);
							return null;
						}
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
						$dms->addCallback('onPreRemoveDocument', 'removePreviews', array($previewer));

						if($folder->remove()) {
							if ($notifier) {
								$notifier->sendDeleteFolderMail($folder, $user);
							}
							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>getMLText('splash_folder_deleted'), 'data'=>''));
							add_log_line();
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('error_remove_folder'), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_folder_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('removedocument', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user, 'removeDocument') >= M_READWRITE) {
						$folder = $document->getFolder();
						/* Get the notify list before removing the document */
						$dnl =	$document->getNotifyList();
						$fnl =	$folder->getNotifyList();
						$docname = $document->getName();

						$controller = Controller::factory('RemoveDocument', array('dms'=>$dms, 'user'=>$user));
						$controller->setParam('document', $document);
						$controller->setParam('fulltextservice', $fulltextservice);
						if($controller()) {
							if ($notifier){
								/* $document still has the data from the just deleted document,
								 * which is just enough to send the email.
								 */
								$notifier->sendDeleteDocumentMail($document, $user);
							}

							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_deleted'), 'data'=>''));
							add_log_line();
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('error_remove_document')."<br />".getMLText($controller->getErrorMsg()), 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'tooglelockdocument': /* {{{ */
		if($user) {
			if(checkFormKey('tooglelockdocument', 'GET')) {
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user) >= M_READWRITE) {
						if ($document->isLocked()) {
							$lockingUser = $document->getLockingUser();
							if (($lockingUser->getID() == $user->getID()) || ($document->getAccessMode($user) == M_ALL)) {
								if (!$document->setLocked(false)) {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error unlocking document', 'data'=>''));
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_unlocked'), 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
							}
						} else {
							if (!$document->setLocked($user)) {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'Error locking document', 'data'=>''));
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_locked'), 'data'=>''));
								add_log_line();
							}
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

	case 'setdocumentname': /* {{{ */
		if($user) {
			if(checkFormKey('setdocumentname')) {
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user) >= M_READWRITE) {
						$oldname = $document->getName();
						if (!$document->setName($_REQUEST['name'])) {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error setting name', 'data'=>''));
						} else {
							if($notifier) {
								$notifier->sendChangedNameMail($document, $user, $oldname);
							}
							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_name_changed'), 'data'=>''));
							add_log_line();
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

	case 'setmimetype': /* {{{ */
		if($user && $user->isAdmin()) {
			if(checkFormKey('setmimetype', 'GET')) {
				$content = $dms->getDocumentContent($_REQUEST['contentid']);
				if($content) {
					$document = $content->getDocument();
					if ($document->getAccessMode($user) >= M_READWRITE) {
						$realmimetype = SeedDMS_Core_File::mimetype($dms->contentDir . $content->getPath());
						if (!$content->setMimeType($realmimetype)) {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error setting mimetype', 'data'=>''));
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>getMLText('splash_mimetype_changed'), 'data'=>''));
							add_log_line();
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText('access_denied'), 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

	case 'submittranslation': /* {{{ */
		if($settings->_showMissingTranslations) {
			if($user && !empty($_POST['phrase'])) {
				if($fp = fopen($settings->_cacheDir.'/newtranslations.txt', 'a+')) {
					fputcsv($fp, array(date('Y-m-d H:i:s'), $user->getLogin(), $_POST['key'], $_POST['lang'], $_POST['phrase']));
					fclose($fp);
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>'Thank you for your contribution', 'data'=>''));
			}	else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>'Missing translation', 'data'=>''));
			}
		}
		break; /* }}} */

	case 'uploaddocument': /* {{{ */
		if($user) {
			if(checkFormKey('')) {
				if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_folder_id")));
					exit;
				}

				$folderid = $_POST["folderid"];
				$folder = $dms->getFolder($folderid);

				if (!is_object($folder)) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_folder_id")));
					exit;
				}

				if ($folder->getAccessMode($user, 'addDocument') < M_READWRITE) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("access_denied")));
					exit;
				}

				if($settings->_quota > 0) {
					$remain = checkQuota($user);
					if ($remain < 0) {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain))))));
						exit;
					}
				}

				if (!is_uploaded_file($_FILES["userfile"]["tmp_name"]) || $_FILES['userfile']['error']!=0){
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_failed")));
					exit;
				}
				if ($_FILES["userfile"]["size"]==0) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_zerosize")));
					exit;
				} 

				$maxuploadsize = SeedDMS_Core_File::parse_filesize($settings->_maxUploadSize);
				if ($maxuploadsize && $_FILES["userfile"]["size"] > $maxuploadsize) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_maxsize")));
					exit;
				}

				$userfiletmp = $_FILES["userfile"]["tmp_name"];
				$userfiletype = $_FILES["userfile"]["type"];
				$userfilename = $_FILES["userfile"]["name"];

				$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

				if($settings->_overrideMimeType) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$userfiletype = finfo_file($finfo, $userfiletmp);
				}

				if (!empty($_POST["name"]))
					$name = $_POST["name"];
				else
					$name = utf8_basename($userfilename);

				/* Check if name already exists in the folder */
				if(!$settings->_enableDuplicateDocNames) {
					if($folder->hasDocumentByName($name)) {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText("document_duplicate_name")));
						exit;
					}
				}

				if(isset($_POST["attributes"]))
					$attributes = $_POST["attributes"];
				else
					$attributes = array();

				if(isset($_POST["comment"]))
					$comment = trim($_POST["comment"]);
				else
					$comment = '';

				// Get the list of reviewers and approvers for this document.
				$reviewers = array();
				$approvers = array();
				$reviewers["i"] = array();
				$reviewers["g"] = array();
				$approvers["i"] = array();
				$approvers["g"] = array();
				$workflow = null;

				if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
					// add mandatory reviewers/approvers
					if($settings->_workflowMode == 'traditional') {
						$mreviewers = getMandatoryReviewers($folder, $user);
						if($mreviewers['i'])
							$reviewers['i'] = array_merge($reviewers['i'], $mreviewers['i']);
						if($mreviewers['g'])
							$reviewers['g'] = array_merge($reviewers['g'], $mreviewers['g']);
					}
					$mapprovers = getMandatoryApprovers($folder, $user);
					if($mapprovers['i'])
						$approvers['i'] = array_merge($approvers['i'], $mapprovers['i']);
					if($mapprovers['g'])
						$approvers['g'] = array_merge($approvers['g'], $mapprovers['g']);

				} elseif($settings->_workflowMode == 'advanced') {
					$workflow = $user->getMandatoryWorkflow();
				}

				$expires = false;
				if($settings->_presetExpirationDate) {
					$expires = strtotime($settings->_presetExpirationDate);
				}

				$keywords = isset($_POST["keywords"]) ? trim($_POST["keywords"]) : '';

				$categories = isset($_POST["categories"]) ? $_POST["categories"] : null;
				$cats = array();
				if($categories) {
					foreach($categories as $catid) {
						if($cat = $dms->getDocumentCategory($catid))
							$cats[] = $cat;
					}
				}

				$controller = Controller::factory('AddDocument', array('dms'=>$dms, 'user'=>$user));
				$controller->setParam('documentsource', 'upload');
				$controller->setParam('folder', $folder);
				$controller->setParam('fulltextservice', $fulltextservice);
				$controller->setParam('name', $name);
				$controller->setParam('comment', $comment);
				$controller->setParam('expires', $expires);
				$controller->setParam('keywords', $keywords);
				$controller->setParam('categories', $cats);
				$controller->setParam('owner', $user);
				$controller->setParam('userfiletmp', $userfiletmp);
				$controller->setParam('userfilename', $userfilename);
				$controller->setParam('filetype', $fileType);
				$controller->setParam('userfiletype', $userfiletype);
				$minmax = $folder->getDocumentsMinMax();
				$deviation = rand(10, 1000)/10;
				if($settings->_defaultDocPosition == 'start')
					$controller->setParam('sequence', $minmax['min'] - $deviation);
				else
					$controller->setParam('sequence', $minmax['max'] + $deviation);
				$controller->setParam('reviewers', $reviewers);
				$controller->setParam('approvers', $approvers);
				$controller->setParam('reqversion', 1);
				$controller->setParam('versioncomment', '');
				$controller->setParam('attributes', $attributes);
				$controller->setParam('attributesversion', array());
				$controller->setParam('workflow', $workflow);
				$controller->setParam('notificationgroups', array());
				$controller->setParam('notificationusers', array());
				$controller->setParam('maxsizeforfulltext', $settings->_maxSizeForFullText);
				$controller->setParam('defaultaccessdocs', $settings->_defaultAccessDocs);

				if(!$document = $controller()) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText($controller->getErrorMsg())));
					exit;
				} else {
					// Send notification to subscribers of folder.
					if($notifier) {
						$notifier->sendNewDocumentMail($document, $user);
					}
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_added'), 'data'=>$document->getID()));
				add_log_line();
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

		/* Deprecated, has moved to op/op.TransmittalMgr.php */
	case '___removetransmittalitem': /* {{{ */
		if($user) {
			if(!checkFormKey('removetransmittalitem', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$item = SeedDMS_Core_TransmittalItem::getInstance((int) $_REQUEST['id'], $dms);
				if($item) {
					$transmittal = $item->getTransmittal();
					if($transmittal) {
						if ($transmittal->getUser()->getID() == $user->getID()) {
							if($item->remove()) {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'Error removing transmittal item', 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No transmittal', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No transmittal item', 'data'=>''));
				}
			}
		}
		break; /* }}} */

		/* Deprecated, has moved to op/op.TransmittalMgr.php */
	case '___updatetransmittalitem': /* {{{ */
		if($user) {
			if(!checkFormKey('updatetransmittalitem', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$item = SeedDMS_Core_TransmittalItem::getInstance((int) $_REQUEST['id'], $dms);
				if($item) {
					$transmittal = $item->getTransmittal();
					if($transmittal) {
						if ($transmittal->getUser()->getID() == $user->getID()) {
							if($item->updateContent()) {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'Error removing transmittal item', 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No transmittal', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No transmittal item', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'addfile': /* {{{ */
		if($user) {
			if(checkFormKey('addfile')) {
				if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_doc_id")));
					exit;
				}

				$documentid = $_POST["documentid"];
				$document = $dms->getDocument($documentid);

				if (!is_object($document)) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_doc_id")));
					exit;
				}

				if ($document->getAccessMode($user, 'addDocumentFile') < M_READWRITE) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("access_denied")));
					exit;
				}

				if (!is_uploaded_file($_FILES["userfile"]["tmp_name"]) || $_FILES['userfile']['error']!=0){
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_failed")));
					exit;
				}
				if ($_FILES["userfile"]["size"]==0) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_zerosize")));
					exit;
				} 

				$maxuploadsize = SeedDMS_Core_File::parse_filesize($settings->_maxUploadSize);
				if ($maxuploadsize && $_FILES["userfile"]["size"] > $maxuploadsize) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_maxsize")));
					exit;
				}

				$userfiletmp = $_FILES["userfile"]["tmp_name"];
				$userfiletype = $_FILES["userfile"]["type"];
				$userfilename = $_FILES["userfile"]["name"];

				$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

				if($settings->_overrideMimeType) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$userfiletype = finfo_file($finfo, $userfiletmp);
				}

				if (!empty($_POST["name"]))
					$name = $_POST["name"];
				else
					$name = utf8_basename($userfilename);
				$comment = '';
				$version = 0;
				$public = $settings->_uploadedAttachmentIsPublic;
				$res = $document->addDocumentFile($name, $comment, $user, $userfiletmp, 
					utf8_basename($userfilename),$fileType, $userfiletype, $version, $public);
				if (is_bool($res) && !$res) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('error_occured'), 'data'=>''));
				} else {
					if($notifier) {
						$notifier->sendNewFileMail($res, $user);
					}
					header('Content-Type: application/json');
					echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_added'), 'data'=>$document->getID()));
					add_log_line();
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

	case 'indexdocument':
	case 'indexfolder': /* {{{ */
		if($user && $user->isAdmin()) {
			if($fulltextservice) {
				if($command == 'indexdocument') {
					$hook = 'indexDocument';
					$object = $dms->getDocument($_REQUEST['id']);
					$prefix = 'D';
				} else {
					$hook = 'indexFolder';
					$object = $dms->getFolder($_REQUEST['id']);
					$prefix = 'F';
				}
				if($object) {
					if($index = $fulltextservice->Indexer()) {
						$index->init($settings->_stopWordsFile);
						$idoc = $fulltextservice->IndexedDocument($object, true);
							$error = $idoc->getErrorMsg();
							if(!$error) {
								$ires = null;
								if(isset($GLOBALS['SEEDDMS_HOOKS'][$hook])) {
									foreach($GLOBALS['SEEDDMS_HOOKS'][$hook] as $hookObj) {
										if (method_exists($hookObj, 'pre'.ucfirst($hook))) {
											$ires = $hookObj->{'pre'.ucfirst($hook)}(null, $object, $idoc);
										}
									}
								}
								if(false !== $ires)
									$ires = $index->addDocument($idoc);
								header('Content-Type: application/json');
								if(false === $ires) {
									echo json_encode(array('success'=>false, 'message'=>getMLText('error_document_indexed', ['name'=>$object->getName()]), 'data'=>$prefix.$object->getID(), 'mimetype'=>$idoc->getMimeType(), 'cmd'=>$idoc->getCmd()));
								} else {
									echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_indexed', ['name'=>$object->getName()]), 'data'=>$prefix.$object->getID(), 'cmd'=>$idoc->getCmd()));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>$error, 'data'=>$object->getID(), 'mimetype'=>$idoc->getMimeType(), 'cmd'=>$idoc->getCmd()));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>getMLText('error_occured'), 'data'=>$prefix.$object->getID()));
						}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_doc_id'), 'data'=>''));
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('error_occured'), 'data'=>''));
			}
		}
		break; /* }}} */
}
?>
