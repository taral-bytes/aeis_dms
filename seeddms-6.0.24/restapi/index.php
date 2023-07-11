<?php
include("../inc/inc.Settings.php");

require_once("Log.php");
require_once("../inc/inc.Language.php");
require_once("../inc/inc.Utils.php");

$logger = getLogger('restapi-', PEAR_LOG_DEBUG);

require_once("../inc/inc.Init.php");
require_once("../inc/inc.Extension.php");
require_once("../inc/inc.DBInit.php");
require_once("../inc/inc.ClassNotificationService.php");
require_once("../inc/inc.ClassEmailNotify.php");
require_once("../inc/inc.Notification.php");
require_once("../inc/inc.ClassController.php");

require "vendor/autoload.php";

use Psr\Container\ContainerInterface;

class RestapiController { /* {{{ */
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    protected function __getAttributesData($obj) { /* {{{ */
        $attributes = $obj->getAttributes();
        $attrvalues = array();
        if($attributes) {
            foreach($attributes as $attrdefid=>$attribute) {
                $attrdef = $attribute->getAttributeDefinition();
                $attrvalues[] = array(
                    'id'=>(int) $attrdef->getId(),
                    'name'=>$attrdef->getName(),
                    'value'=>$attribute->getValue()
                );
            }
        }
        return $attrvalues;
    } /* }}} */

    protected function __getDocumentData($document) { /* {{{ */
        $data = array(
            'type'=>'document',
            'id'=>(int)$document->getId(),
            'date'=>date('Y-m-d H:i:s', $document->getDate()),
            'name'=>$document->getName(),
            'comment'=>$document->getComment(),
            'keywords'=>$document->getKeywords()
        );
        return $data;
    } /* }}} */

    protected function __getLatestVersionData($lc) { /* {{{ */
        $document = $lc->getDocument();
        $data = array(
            'type'=>'document',
            'id'=>(int)$document->getId(),
            'date'=>date('Y-m-d H:i:s', $document->getDate()),
            'name'=>$document->getName(),
            'comment'=>$document->getComment(),
            'keywords'=>$document->getKeywords(),
            'ownerid'=>(int) $document->getOwner()->getID(),
            'islocked'=>$document->isLocked(),
            'sequence'=>$document->getSequence(),
            'expires'=>$document->getExpires() ? date('Y-m-d H:i:s', $document->getExpires()) : "",
            'mimetype'=>$lc->getMimeType(),
            'filetype'=>$lc->getFileType(),
            'origfilename'=>$lc->getOriginalFileName(),
            'version'=>$lc->getVersion(),
            'version_comment'=>$lc->getComment(),
            'version_date'=>date('Y-m-d H:i:s', $lc->getDate()),
            'size'=>(int) $lc->getFileSize(),
        );
        $cats = $document->getCategories();
        if($cats) {
            $c = array();
            foreach($cats as $cat) {
                $c[] = array('id'=>(int)$cat->getID(), 'name'=>$cat->getName());
            }
            $data['categories'] = $c;
        }
        $attributes = $this->__getAttributesData($document);
        if($attributes) {
            $data['attributes'] = $attributes;
        }
        $attributes = $this->__getAttributesData($lc);
        if($attributes) {
            $data['version_attributes'] = $attributes;
        }
        return $data;
    } /* }}} */

    protected function __getDocumentVersionData($lc) { /* {{{ */
        $data = array(
            'id'=>(int) $lc->getId(),
            'version'=>$lc->getVersion(),
            'date'=>date('Y-m-d H:i:s', $lc->getDate()),
            'mimetype'=>$lc->getMimeType(),
            'filetype'=>$lc->getFileType(),
            'origfilename'=>$lc->getOriginalFileName(),
            'size'=>(int) $lc->getFileSize(),
            'comment'=>$lc->getComment(),
        );
        return $data;
    } /* }}} */

    protected function __getDocumentFileData($file) { /* {{{ */
        $data = array(
            'id'=>(int)$file->getId(),
            'name'=>$file->getName(),
            'date'=>$file->getDate(),
            'mimetype'=>$file->getMimeType(),
            'comment'=>$file->getComment(),
        );
        return $data;
    } /* }}} */

    protected function __getDocumentLinkData($link) { /* {{{ */
        $data = array(
            'id'=>(int)$link->getId(),
            'target'=>$this->__getDocumentData($link->getTarget()),
            'public'=>(boolean)$link->isPublic(),
        );
        return $data;
    } /* }}} */

    protected function __getFolderData($folder) { /* {{{ */
        $data = array(
            'type'=>'folder',
            'id'=>(int)$folder->getID(),
            'name'=>$folder->getName(),
            'comment'=>$folder->getComment(),
            'date'=>date('Y-m-d H:i:s', $folder->getDate()),
        );
        $attributes = $this->__getAttributesData($folder);
        if($attributes) {
            $data['attributes'] = $attributes;
        }
        return $data;
    } /* }}} */

    protected function __getGroupData($u) { /* {{{ */
        $data = array(
            'type'=>'group',
            'id'=>(int)$u->getID(),
            'name'=>$u->getName(),
            'comment'=>$u->getComment(),
        );
        return $data;
    } /* }}} */

    protected function __getUserData($u) { /* {{{ */
        $data = array(
            'type'=>'user',
            'id'=>(int)$u->getID(),
            'name'=>$u->getFullName(),
            'comment'=>$u->getComment(),
            'login'=>$u->getLogin(),
            'email'=>$u->getEmail(),
            'language' => $u->getLanguage(),
            'theme' => $u->getTheme(),
            'role' => array('id'=>(int)$u->getRole()->getId(), 'name'=>$u->getRole()->getName()),
            'hidden'=>$u->isHidden() ? true : false,
            'disabled'=>$u->isDisabled() ? true : false,
            'isguest' => $u->isGuest() ? true : false,
            'isadmin' => $u->isAdmin() ? true : false,
        );
        if($u->getHomeFolder())
            $data['homefolder'] = (int)$u->getHomeFolder();

        $groups = $u->getGroups();
        if($groups) {
            $tmp = [];
            foreach($groups as $group)
                $tmp[] = $this->__getGroupData($group);
            $data['groups'] = $tmp;
        }
        return $data;
    } /* }}} */

    protected function __getAttributeDefinitionData($attrdef) { /* {{{ */
        $data = [
            'id' => (int)$attrdef->getId(),
            'name' => $attrdef->getName(),
            'type'=>(int)$attrdef->getType(),
            'objtype'=>(int)$attrdef->getObjType(),
            'min'=>(int)$attrdef->getMinValues(),
            'max'=>(int)$attrdef->getMaxValues(),
            'multiple'=>$attrdef->getMultipleValues()?true:false,
            'valueset'=>$attrdef->getValueSetAsArray(),
            'regex'=>$attrdef->getRegex()
        ];
        return $data;
    } /* }}} */

    protected function __getCategoryData($category) { /* {{{ */
        $data = [
            'id'=>(int)$category->getId(),
            'name'=>$category->getName()
        ];
        return $data;
    } /* }}} */

    function doLogin($request, $response) { /* {{{ */
        global $session;

        $dms = $this->container->dms;
        $settings = $this->container->config;
        $logger = $this->container->logger;
        $authenticator = $this->container->authenticator;

        $params = $request->getParsedBody();
        if(empty($params['user']) || empty($params['pass'])) {
            $logger->log("Login without username or password failed", PEAR_LOG_INFO);
            return $response->withJson(array('success'=>false, 'message'=>'No user or password given', 'data'=>''), 400);
        }
        $username = $params['user'];
        $password = $params['pass'];
        $userobj = $authenticator->authenticate($username, $password);

        if(!$userobj) {
            setcookie("mydms_session", '', time()-3600, $settings->_httpRoot);
            $logger->log("Login with user name '".$username."' failed", PEAR_LOG_ERR);
            return $response->withJson(array('success'=>false, 'message'=>'Login failed', 'data'=>''), 403);
        } else {
            require_once("../inc/inc.ClassSession.php");
            $session = new SeedDMS_Session($dms->getDb());
            if(!$id = $session->create(array('userid'=>$userobj->getId(), 'theme'=>$userobj->getTheme(), 'lang'=>$userobj->getLanguage()))) {
              return $response->withJson(array('success'=>false, 'message'=>'Creating session failed', 'data'=>''), 500);
            }

            // Set the session cookie.
            if($settings->_cookieLifetime)
                $lifetime = time() + intval($settings->_cookieLifetime);
            else
                $lifetime = 0;
            setcookie("mydms_session", $id, $lifetime, $settings->_httpRoot);
            $dms->setUser($userobj);

            $logger->log("Login with user name '".$username."' successful", PEAR_LOG_INFO);
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getUserData($userobj)), 200);
        }
    } /* }}} */

    function doLogout($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;

        setcookie("mydms_session", '', time()-3600, $settings->_httpRoot);
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
    } /* }}} */

    function setFullName($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
            return;
        }

        $params = $request->getParsedBody();
        $userobj->setFullName($params['fullname']);
        $data = $this->__getUserData($userobj);
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function setEmail($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
            return;
        }

        $params = $request->getParsedBody();
        $userobj->setEmail($params['email']);
        $data = $this->__getUserData($userobj);
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function getLockedDocuments($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(false !== ($documents = $dms->getDocumentsLockedByUser($userobj))) {
            $documents = SeedDMS_Core_DMS::filterAccess($documents, $userobj, M_READ);
            $recs = array();
            foreach($documents as $document) {
                $lc = $document->getLatestContent();
                if($lc) {
                    $recs[] = $this->__getLatestVersionData($lc);
                }
            }
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
        }
    } /* }}} */

    function getFolder($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;

        $params = $request->getQueryParams();
        $forcebyname = isset($params['forcebyname']) ? $params['forcebyname'] : 0;
        $parent = isset($params['parentid']) ? $dms->getFolder($params['parentid']) : null;

        if (!isset($args['id']) || !$args['id'])
            $folder = $dms->getFolder($settings->_rootFolderID);
        elseif(ctype_digit($args['id']) && empty($forcebyname))
            $folder = $dms->getFolder($args['id']);
        else {
            $folder = $dms->getFolderByName($args['id'], $parent);
        }
        if($folder) {
            if($folder->getAccessMode($userobj) >= M_READ) {
                $data = $this->__getFolderData($folder);
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
    } /* }}} */

    function getFolderParent($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $id = $args['id'];
        if($id == 0) {
            return $response->withJson(array('success'=>true, 'message'=>'id is 0', 'data'=>''), 200);
        }
        $root = $dms->getRootFolder();
        if($root->getId() == $id) {
            return $response->withJson(array('success'=>true, 'message'=>'id is root folder', 'data'=>''), 200);
        }
        $folder = $dms->getFolder($id);
        if($folder) {
            $parent = $folder->getParent();
            if($parent) {
                if($parent->getAccessMode($userobj) >= M_READ) {
                    $rec = $this->__getFolderData($parent);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$rec), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
    } /* }}} */

    function getFolderPath($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(empty($args['id'])) {
            return $response->withJson(array('success'=>true, 'message'=>'id is 0', 'data'=>''), 200);
        }
        $folder = $dms->getFolder($args['id']);
        if($folder) {
            if($folder->getAccessMode($userobj) >= M_READ) {
                $path = $folder->getPath();
                $data = array();
                foreach($path as $element) {
                    $data[] = array('id'=>$element->getId(), 'name'=>$element->getName());
                }
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
    } /* }}} */

    function getFolderAttributes($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $folder = $dms->getFolder($args['id']);
        if($folder) {
            if ($folder->getAccessMode($userobj) >= M_READ) {
                $attributes = $this->__getAttributesData($folder);
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$attributes), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
    } /* }}} */

    function getFolderChildren($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(empty($args['id'])) {
            $folder = $dms->getRootFolder();
            $recs = array($this->$this->__getFolderData($folder));
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
        } else {
            $folder = $dms->getFolder($args['id']);
            if($folder) {
                if($folder->getAccessMode($userobj) >= M_READ) {
                    $recs = array();
                    $subfolders = $folder->getSubFolders();
                    $subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $userobj, M_READ);
                    foreach($subfolders as $subfolder) {
                        $recs[] = $this->__getFolderData($subfolder);
                    }
                    $documents = $folder->getDocuments();
                    $documents = SeedDMS_Core_DMS::filterAccess($documents, $userobj, M_READ);
                    foreach($documents as $document) {
                        $lc = $document->getLatestContent();
                        if($lc) {
                            $recs[] = $this->__getLatestVersionData($lc);
                        }
                    }
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
            }
        }
    } /* }}} */

    function createFolder($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;
        $logger = $this->container->logger;
        $fulltextservice = $this->container->fulltextservice;
        $notifier = $this->container->notifier;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No parent folder given', 'data'=>''), 400);
            return;
        }
        $parent = $dms->getFolder($args['id']);
        if($parent) {
            if($parent->getAccessMode($userobj, 'addFolder') >= M_READWRITE) {
                $params = $request->getParsedBody();
                if(!empty($params['name'])) {
                    $comment = isset($params['comment']) ? $params['comment'] : '';
                    if(isset($params['sequence'])) {
                        $sequence = str_replace(',', '.', $params["sequence"]);
                        if (!is_numeric($sequence))
                            return $response->withJson(array('success'=>false, 'message'=>getMLText("invalid_sequence"), 'data'=>''), 400);
                    } else {
                        $dd = $parent->getSubFolders('s');
                        if(count($dd) > 1)
                            $sequence = $dd[count($dd)-1]->getSequence() + 1;
                        else
                            $sequence = 1.0;
                    }
                    $newattrs = array();
                    if(!empty($params['attributes'])) {
                        foreach($params['attributes'] as $attrname=>$attrvalue) {
                            if((is_int($attrname) || ctype_digit($attrname)) && ((int) $attrname) > 0)
                                $attrdef = $dms->getAttributeDefinition((int) $attrname);
                            else
                                $attrdef = $dms->getAttributeDefinitionByName($attrname);
                            if($attrdef) {
                                $newattrs[$attrdef->getID()] = $attrvalue;
                            }
                        }
                    }
                    /* Check if name already exists in the folder */
                    if(!$settings->_enableDuplicateSubFolderNames) {
                        if($parent->hasSubFolderByName($params['name'])) {
                            return $response->withJson(array('success'=>false, 'message'=>getMLText("subfolder_duplicate_name"), 'data'=>''), 409);
                        }
                    }

                    $controller = Controller::factory('AddSubFolder');
                    $controller->setParam('dms', $dms);
                    $controller->setParam('user', $userobj);
                    $controller->setParam('fulltextservice', $fulltextservice);
                    $controller->setParam('folder', $parent);
                    $controller->setParam('name', $params['name']);
                    $controller->setParam('comment', $comment);
                    $controller->setParam('sequence', $sequence);
                    $controller->setParam('attributes', $newattrs);
                    $controller->setParam('notificationgroups', []);
                    $controller->setParam('notificationusers', []);
                    if($folder = $controller()) {
    $rec = $this->__getFolderData($folder);
                        $logger->log("Creating folder '".$folder->getName()."' (".$folder->getId().") successful", PEAR_LOG_INFO);
                        if($notifier) {
                            $notifier->sendNewFolderMail($folder, $userobj);
                        }
                        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$rec), 201);
                    } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not create folder', 'data'=>''), 500);
                    }
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Missing folder name', 'data'=>''), 400);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'Could not find parent folder', 'data'=>''), 404);
        }
    } /* }}} */

    function moveFolder($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No source folder given', 'data'=>''), 400);
        }

        if(!ctype_digit($args['folderid']) || $args['folderid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No destination folder given', 'data'=>''), 400);
        }

        $mfolder = $dms->getFolder($args['id']);
        if($mfolder) {
            if ($mfolder->getAccessMode($userobj, 'moveFolder') >= M_READ) {
                if($folder = $dms->getFolder($args['folderid'])) {
                    if($folder->getAccessMode($userobj, 'moveFolder') >= M_READWRITE) {
                        if($mfolder->setParent($folder)) {
                            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
                        } else {
                            return $response->withJson(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''), 500);
                        }
                    } else {
                        return $response->withJson(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''), 403);
                    }
                } else {
                    if($folder === null)
                        $status = 404;
                    else
                        $status = 500;
                    return $response->withJson(array('success'=>false, 'message'=>'No destination folder', 'data'=>''), $status);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($mfolder === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No folder', 'data'=>''), $status);
        }
    } /* }}} */

    function deleteFolder($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'id is 0', 'data'=>''), 400);
        }
        $mfolder = $dms->getFolder($args['id']);
        if($mfolder) {
            if ($mfolder->getAccessMode($userobj, 'removeFolder') >= M_READWRITE) {
                if($mfolder->remove()) {
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Error deleting folder', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($mfolder === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No folder', 'data'=>''), $status);
        }
    } /* }}} */

    function uploadDocument($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;
        $notifier = $this->container->notifier;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No parent folder id given', 'data'=>''), 400);
        }

        if($settings->_quota > 0) {
            $remain = checkQuota($userobj);
            if ($remain < 0) {
    return $response->withJson(array('success'=>false, 'message'=>'Quota exceeded', 'data'=>''), 400);
            }
        }

        $mfolder = $dms->getFolder($args['id']);
        if($mfolder) {
            $uploadedFiles = $request->getUploadedFiles();
            if ($mfolder->getAccessMode($userobj, 'addDocument') >= M_READWRITE) {
                $params = $request->getParsedBody();
                $docname = isset($params['name']) ? $params['name'] : '';
                $keywords = isset($params['keywords']) ? $params['keywords'] : '';
                $comment = isset($params['comment']) ? $params['comment'] : '';
                if(isset($params['sequence'])) {
                    $sequence = str_replace(',', '.', $params["sequence"]);
                    if (!is_numeric($sequence))
                        return $response->withJson(array('success'=>false, 'message'=>getMLText("invalid_sequence"), 'data'=>''), 400);
                } else {
                    $dd = $mfolder->getDocuments('s');
                    if(count($dd) > 1)
                        $sequence = $dd[count($dd)-1]->getSequence() + 1;
                    else
                        $sequence = 1.0;
                }
                if(isset($params['expdate'])) {
                    $tmp = explode('-', $params["expdate"]);
                    if(count($tmp) != 3)
                        return $response->withJson(array('success'=>false, 'message'=>getMLText('malformed_expiration_date'), 'data'=>''), 400);
                    $expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]);
                } else
                    $expires = 0;
                $version_comment = isset($params['version_comment']) ? $params['version_comment'] : '';
                $reqversion = (isset($params['reqversion']) && (int) $params['reqversion'] > 1) ? (int) $params['reqversion'] : 1;
                $origfilename = isset($params['origfilename']) ? $params['origfilename'] : null;
                $categories = isset($params["categories"]) ? $params["categories"] : array();
                $cats = array();
                foreach($categories as $catid) {
                    if($cat = $dms->getDocumentCategory($catid))
                        $cats[] = $cat;
                }
                $owner = null;
                if($userobj->isAdmin() && isset($params["owner"]) && ctype_digit($params['owner'])) {
                    $owner = $dms->getUser($params["owner"]);
                }
                $attributes = isset($params["attributes"]) ? $params["attributes"] : array();
                foreach($attributes as $attrdefid=>$attribute) {
                    if((is_int($attrdefid) || ctype_digit($attrdefid)) && ((int) $attrdefid) > 0)
                        $attrdef = $dms->getAttributeDefinition((int) $attrdefid);
                    else
                        $attrdef = $dms->getAttributeDefinitionByName($attrdefid);
                    if($attrdef) {
                        if($attribute) {
                            if(!$attrdef->validate($attribute)) {
                                return $response->withJson(array('success'=>false, 'message'=>getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute), 'data'=>''), 400);
                            }
                        } elseif($attrdef->getMinValues() > 0) {
                            return $response->withJson(array('success'=>false, 'message'=>getMLText("attr_min_values", array("attrname"=>$attrdef->getName())), 'data'=>''), 400);
                        }
                    }
                }
                if (count($uploadedFiles) == 0) {
                    return $response->withJson(array('success'=>false, 'message'=>'No file detected', 'data'=>''), 400);
                }
                $file_info = array_pop($uploadedFiles);
                if ($origfilename == null)
                    $origfilename = $file_info->getClientFilename();
                if (trim($docname) == '')
                    $docname = $origfilename;
                /* Check if name already exists in the folder */
                if(!$settings->_enableDuplicateDocNames) {
                    if($mfolder->hasDocumentByName($docname)) {
                        return $response->withJson(array('success'=>false, 'message'=>getMLText("document_duplicate_name"), 'data'=>''), 409);
                    }
                }
                $temp = $file_info->file;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $userfiletype = finfo_file($finfo, $temp);
                $fileType = ".".pathinfo($origfilename, PATHINFO_EXTENSION);
                finfo_close($finfo);
                $res = $mfolder->addDocument($docname, $comment, $expires, $owner ? $owner : $userobj, $keywords, $cats, $temp, $origfilename ? $origfilename : basename($temp), $fileType, $userfiletype, $sequence, array(), array(), $reqversion, $version_comment, $attributes);
                unlink($temp);
                if($res) {
                    $doc = $res[0];
                    if($notifier) {
                        $notifier->sendNewDocumentMail($doc, $userobj);
                    }
                    return $response->withJson(array('success'=>true, 'message'=>'Upload succeded', 'data'=>$this->__getLatestVersionData($doc->getLatestContent())), 201);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Upload failed', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($mfolder === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No folder', 'data'=>''), $status);
        }
    } /* }}} */

    function updateDocument($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;
        $notifier = $this->container->notifier;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document id given', 'data'=>''), 400);
        }

        if($settings->_quota > 0) {
            $remain = checkQuota($userobj);
            if ($remain < 0) {
    return $response->withJson(array('success'=>false, 'message'=>'Quota exceeded', 'data'=>''), 400);
            }
        }

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj, 'updateDocument') >= M_READWRITE) {
                $params = $request->getParsedBody();
                $origfilename = isset($params['origfilename']) ? $params['origfilename'] : null;
                $comment = isset($params['comment']) ? $params['comment'] : null;
                $attributes = isset($params["attributes"]) ? $params["attributes"] : array();
                foreach($attributes as $attrdefid=>$attribute) {
                    if((is_int($attrdefid) || ctype_digit($attrdefid)) && ((int) $attrdefid) > 0)
                        $attrdef = $dms->getAttributeDefinition((int) $attrdefid);
                    else
                        $attrdef = $dms->getAttributeDefinitionByName($attrdefid);
                    if($attrdef) {
                        if($attribute) {
                            if(!$attrdef->validate($attribute)) {
                                return $response->withJson(array('success'=>false, 'message'=>getAttributeValidationText($attrdef->getValidationError(), $attrdef->getName(), $attribute), 'data'=>''), 400);
                            }
                        } elseif($attrdef->getMinValues() > 0) {
                            return $response->withJson(array('success'=>false, 'message'=>getMLText("attr_min_values", array("attrname"=>$attrdef->getName())), 'data'=>''), 400);
                        }
                    }
                }
                $uploadedFiles = $request->getUploadedFiles();
                if (count($uploadedFiles) == 0) {
                    return $response->withJson(array('success'=>false, 'message'=>'No file detected', 'data'=>''), 400);
                }
                $file_info = array_pop($uploadedFiles);
                if ($origfilename == null)
                    $origfilename = $file_info->getClientFilename();
                $temp = $file_info->file;

                /* Check if the uploaded file is identical to last version */
                $lc = $document->getLatestContent();
                if($lc->getChecksum() == SeedDMS_Core_File::checksum($temp)) {
                    return $response->withJson(array('success'=>false, 'message'=>'Uploaded file identical to last version', 'data'=>''), 400);
                }
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $userfiletype = finfo_file($finfo, $temp);
                $fileType = ".".pathinfo($origfilename, PATHINFO_EXTENSION);
                finfo_close($finfo);
                $oldexpires = $document->getExpires();
                $res=$document->addContent($comment, $userobj, $temp, $origfilename, $fileType, $userfiletype, array(), array(), 0, $attributes);

                unlink($temp);
                if($res) {
                    if($notifier) {
                        $notifier->sendNewDocumentVersionMail($document, $userobj);

                        /* Actually there is not need to even try sending this mail
                         * because the expiration date cannot be set when calling
                         * this rest api endpoint.
                         */
                        $notifier->sendChangedExpiryMail($document, $userobj, $oldexpires);
                    }
                    $rec = array('id'=>(int)$document->getId(), 'name'=>$document->getName(), 'version'=>$document->getLatestContent()->getVersion());
                    return $response->withJson(array('success'=>true, 'message'=>'Upload succeded', 'data'=>$rec), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Upload failed', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), 404);
        }
    } /* }}} */

    /**
     * Old upload method which uses put instead of post
     */
    function uploadDocumentPut($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;
        $notifier = $this->container->notifier;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document id given', 'data'=>''), 400);
        }

        if($settings->_quota > 0) {
            $remain = checkQuota($userobj);
            if ($remain < 0) {
                return $response->withJson(array('success'=>false, 'message'=>'Quota exceeded', 'data'=>''), 400);
            }
        }

        $mfolder = $dms->getFolder($args['id']);
        if($mfolder) {
            if ($mfolder->getAccessMode($userobj, 'addDocument') >= M_READWRITE) {
                $params = $request->getQueryParams();
                $docname = isset($params['name']) ? $params['name'] : '';
                $keywords = isset($params['keywords']) ? $params['keywords'] : '';
                $origfilename = isset($params['origfilename']) ? $params['origfilename'] : null;
                $content = $request->getBody();
                $temp = tempnam('/tmp', 'lajflk');
                $handle = fopen($temp, "w");
                fwrite($handle, $content);
                fclose($handle);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $userfiletype = finfo_file($finfo, $temp);
                $fileType = ".".pathinfo($origfilename, PATHINFO_EXTENSION);
                finfo_close($finfo);
                /* Check if name already exists in the folder */
                if(!$settings->_enableDuplicateDocNames) {
                    if($mfolder->hasDocumentByName($docname)) {
                    return $response->withJson(array('success'=>false, 'message'=>getMLText("document_duplicate_name"), 'data'=>''), 409);
                    }
                }
                $res = $mfolder->addDocument($docname, '', 0, $userobj, '', array(), $temp, $origfilename ? $origfilename : basename($temp), $fileType, $userfiletype, 0);
                unlink($temp);
                if($res) {
                    $doc = $res[0];
                    if($notifier) {
                        $notifier->sendNewDocumentMail($doc, $userobj);
                    }
                    $rec = array('id'=>(int)$doc->getId(), 'name'=>$doc->getName());
                    return $response->withJson(array('success'=>true, 'message'=>'Upload succeded', 'data'=>$rec), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Upload failed', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($mfolder === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No folder', 'data'=>''), $status);
        }
    } /* }}} */

    function uploadDocumentFile($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document id given', 'data'=>''), 400);
        }
        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj, 'addDocumentFile') >= M_READWRITE) {
                $uploadedFiles = $request->getUploadedFiles();
                $params = $request->getParsedBody();
                $docname = $params['name'];
                $keywords = isset($params['keywords']) ? $params['keywords'] : '';
                $origfilename = $params['origfilename'];
                $comment = isset($params['comment']) ? $params['comment'] : '';
                $version = empty($params['version']) ? 0 : $params['version'];
                $public = empty($params['public']) ? 'false' : $params['public'];
                if (count($uploadedFiles) == 0) {
                    return $response->withJson(array('success'=>false, 'message'=>'No file detected', 'data'=>''), 400);
                }
                $file_info = array_pop($uploadedFiles);
                if ($origfilename == null)
                    $origfilename = $file_info->getClientFilename();
                if (trim($docname) == '')
                    $docname = $origfilename;
                $temp = $file_info->file;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $userfiletype = finfo_file($finfo, $temp);
                $fileType = ".".pathinfo($origfilename, PATHINFO_EXTENSION);
                finfo_close($finfo);
                $res = $document->addDocumentFile($docname, $comment, $userobj, $temp,
                            $origfilename ? $origfilename : utf8_basename($temp),
                            $fileType, $userfiletype, $version, $public);
                unlink($temp);
                if($res) {
                    return $response->withJson(array('success'=>true, 'message'=>'Upload succeded', 'data'=>$res), 201);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Upload failed', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), $status);
        }
    } /* }}} */

    function addDocumentLink($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No source document given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['documentid']) || $args['documentid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No target document given', 'data'=>''), 400);
            return;
        }
        $sourcedoc = $dms->getDocument($args['id']);
        $targetdoc = $dms->getDocument($args['documentid']);
        if($sourcedoc && $targetdoc) {
            if($sourcedoc->getAccessMode($userobj, 'addDocumentLink') >= M_READ) {
                $params = $request->getParsedBody();
                $public = !isset($params['public']) ? true : false;
                if ($sourcedoc->addDocumentLink($targetdoc->getId(), $userobj->getID(), $public)){
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not create document link', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on source document', 'data'=>''), 403);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'Could not find source or target document', 'data'=>''), 500);
        }
    } /* }}} */

    function getDocument($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $lc = $document->getLatestContent();
                if($lc) {
                    $data = $this->__getLatestVersionData($lc);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function deleteDocument($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj, 'deleteDocument') >= M_READWRITE) {
                if($document->remove()) {
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'Error removing document', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function moveDocument($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj, 'moveDocument') >= M_READ) {
                if($folder = $dms->getFolder($args['folderid'])) {
                    if($folder->getAccessMode($userobj, 'moveDocument') >= M_READWRITE) {
                        if($document->setFolder($folder)) {
                            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
                        } else {
                            return $response->withJson(array('success'=>false, 'message'=>'Error moving document', 'data'=>''), 500);
                        }
                    } else {
                        return $response->withJson(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''), 403);
                    }
                } else {
                  if($folder === null)
                      $status=404;
                  else
                      $status=500;
                    return $response->withJson(array('success'=>false, 'message'=>'No destination folder', 'data'=>''), $status);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentContent($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $lc = $document->getLatestContent();
                if($lc) {
                    if (pathinfo($document->getName(), PATHINFO_EXTENSION) == $lc->getFileType())
                        $filename = $document->getName();
                    else
                        $filename = $document->getName().$lc->getFileType();

                    $file = $dms->contentDir . $lc->getPath();
                    if(!($fh = @fopen($file, 'rb'))) {
                        return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
                    }
                    $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

                    return $response->withHeader('Content-Type', $lc->getMimeType())
                        ->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                        ->withHeader('Content-Length', filesize($dms->contentDir . $lc->getPath()))
                        ->withHeader('Expires', '0')
                        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                        ->withHeader('Pragma', 'no-cache')
                        ->withBody($stream);

                  sendFile($dms->contentDir . $lc->getPath());
                } else {
                  return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }

    } /* }}} */

    function getDocumentVersions($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $recs = array();
                $lcs = $document->getContent();
                foreach($lcs as $lc) {
                    $recs[] = $this->__getDocumentVersionData($lc);
                }
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentVersion($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id']) || !ctype_digit($args['version'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $lc = $document->getContentByVersion($args['version']);
                if($lc) {
                    if (pathinfo($document->getName(), PATHINFO_EXTENSION) == $lc->getFileType())
                        $filename = $document->getName();
                    else
                        $filename = $document->getName().$lc->getFileType();

                    $file = $dms->contentDir . $lc->getPath();
                    if(!($fh = @fopen($file, 'rb'))) {
                        return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
                    }
                    $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

                    return $response->withHeader('Content-Type', $lc->getMimeType())
                        ->withHeader('Content-Description', 'File Transfer')
                        ->withHeader('Content-Transfer-Encoding', 'binary')
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                        ->withHeader('Content-Length', filesize($dms->contentDir . $lc->getPath()))
                        ->withHeader('Expires', '0')
                        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                        ->withHeader('Pragma', 'no-cache')
                        ->withBody($stream);

                    sendFile($dms->contentDir . $lc->getPath());
                } else {
                  return $response->withJson(array('success'=>false, 'message'=>'No such version', 'data'=>''), 404);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function updateDocumentVersion($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $lc = $document->getContentByVersion($args['version']);
                if($lc) {
                  $params = $request->getParsedBody();
                  if (isset($params['comment'])) {
                    $lc->setComment($params['comment']);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
                  }
                } else {
                  return $response->withJson(array('success'=>false, 'message'=>'No such version', 'data'=>''), 404);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentFiles($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);

        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $recs = array();
                $files = $document->getDocumentFiles();
                foreach($files as $file) {
                    $recs[] = $this->__getDocumentFileData($file);
                }
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentFile($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id']) || !ctype_digit($args['fileid'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);

        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $lc = $document->getDocumentFile($args['fileid']);
                if($lc) {
                    $file = $dms->contentDir . $lc->getPath();
                    if(!($fh = @fopen($file, 'rb'))) {
                        return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
                    }
                    $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

                    return $response->withHeader('Content-Type', $lc->getMimeType())
                          ->withHeader('Content-Description', 'File Transfer')
                          ->withHeader('Content-Transfer-Encoding', 'binary')
                          ->withHeader('Content-Disposition', 'attachment; filename="' . $document->getName() . $lc->getFileType() . '"')
                          ->withHeader('Content-Length', filesize($dms->contentDir . $lc->getPath()))
                          ->withHeader('Expires', '0')
                          ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                          ->withHeader('Pragma', 'no-cache')
                          ->withBody($stream);

                    sendFile($dms->contentDir . $lc->getPath());
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'No document file', 'data'=>''), 404);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentLinks($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);

        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $recs = array();
                $links = $document->getDocumentLinks();
                foreach($links as $link) {
                    $recs[] = $this->__getDocumentLinkData($link);
                }
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentAttributes($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                $attributes = $this->__getAttributesData($document);
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$attributes), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentContentAttributes($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $document = $dms->getDocument($args['id']);
        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {

                $version = $document->getContentByVersion($args['version']);
                if($version) {
                    if($version->getAccessMode($userobj) >= M_READ) {
                        $attributes = $this->__getAttributesData($version);
                        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$attributes), 200);
                    } else {
                        return $response->withJson(array('success'=>false, 'message'=>'No access on version', 'data'=>''), 403);
                    }
                } else {
                    return $response->withJson(array('success'=>false, 'message'=>'No version', 'data'=>''), 404);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function getDocumentPreview($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $settings = $this->container->config;
        $conversionmgr = $this->container->conversionmgr;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);

        if($document) {
            if ($document->getAccessMode($userobj) >= M_READ) {
                if($args['version'])
                    $object = $document->getContentByVersion($args['version']);
                else
                    $object = $document->getLatestContent();
                if(!$object)
                    exit;

                if(!empty($args['width']))
                    $previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $args['width']);
                else
                    $previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir);
                if($conversionmgr)
                    $previewer->setConversionMgr($conversionmgr);
                else
                    $previewer->setConverters($settings->_converters['preview']);
                if(!$previewer->hasPreview($object))
                    $previewer->createPreview($object);

                $file = $previewer->getFileName($object, $args['width']).".png";
                if(!($fh = @fopen($file, 'rb'))) {
                  return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
                }
                $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body

                return $response->withHeader('Content-Type', 'image/png')
                      ->withHeader('Content-Description', 'File Transfer')
                      ->withHeader('Content-Transfer-Encoding', 'binary')
                      ->withHeader('Content-Disposition', 'attachment; filename="preview-' . $document->getID() . "-" . $object->getVersion() . "-" . $width . ".png" . '"')
                      ->withHeader('Content-Length', $previewer->getFilesize($object))
                      ->withBody($stream);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No document', 'data'=>''), $status);
        }
    } /* }}} */

    function addDocumentCategory($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['catid']) || $args['catid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No category given', 'data'=>''), 400);
            return;
        }
        $cat = $dms->getDocumentCategory($args['catid']);
        $doc = $dms->getDocument($args['id']);
        if($doc && $cat) {
            if($doc->getAccessMode($userobj, 'addDocumentCategory') >= M_READ) {
                if ($doc->addCategories([$cat])){
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not add document category', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on document', 'data'=>''), 403);
            }
        } else {
            if(!$doc)
                return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), 404);
            if(!$cat)
                return $response->withJson(array('success'=>false, 'message'=>'No such category', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'Could not find category or document', 'data'=>''), 500);
        }
    } /* }}} */

    function removeDocumentCategory($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id']) || !ctype_digit($args['catid'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);
        $category = $dms->getDocumentCategory($args['catid']);

        if($document && $category) {
            if ($document->getAccessMode($userobj, 'removeDocumentCategory') >= M_READWRITE) {
                $ret = $document->removeCategories(array($category));
                if ($ret)
                    return $response->withJson(array('success'=>true, 'message'=>'Deleted category successfully.', 'data'=>''), 200);
                else
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if(!$document)
                return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), 404);
            if(!$category)
                return $response->withJson(array('success'=>false, 'message'=>'No such category', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
        }
    } /* }}} */

    function removeDocumentCategories($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $document = $dms->getDocument($args['id']);

        if($document) {
            if ($document->getAccessMode($userobj, 'removeDocumentCategory') >= M_READWRITE) {
                if($document->setCategories(array()))
                    return $response->withJson(array('success'=>true, 'message'=>'Deleted categories successfully.', 'data'=>''), 200);
                else
                    return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>''), 500);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access', 'data'=>''), 403);
            }
        } else {
            if($document === null)
                $status=404;
            else
                $status=500;
            return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), $status);
        }
    } /* }}} */

    function setDocumentOwner($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }
        if(!$userobj->isAdmin()) {
            return $response->withJson(array('success'=>false, 'message'=>'No access on document', 'data'=>''), 403);
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['userid']) || $args['userid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No user given', 'data'=>''), 400);
            return;
        }
        $owner = $dms->getUser($args['userid']);
        $doc = $dms->getDocument($args['id']);
        if($doc && $owner) {
            if($doc->getAccessMode($userobj, 'setDocumentOwner') > M_READ) {
                if ($doc->setOwner($owner)){
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not set owner of document', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on document', 'data'=>''), 403);
            }
        } else {
            if(!$doc)
                return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), 404);
            if(!$owner)
                return $response->withJson(array('success'=>false, 'message'=>'No such user', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'Could not find user or document', 'data'=>''), 500);
        }
    } /* }}} */

    function setDocumentAttribute($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $logger = $this->container->logger;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
            return;
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['attrdefid']) || $args['attrdefid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No attribute definition id given', 'data'=>''), 400);
            return;
        }
        $attrdef = $dms->getAttributeDefinition($args['attrdefid']);
        $doc = $dms->getDocument($args['id']);
        if($doc && $attrdef) {
            if($attrdef->getObjType() !== SeedDMS_Core_AttributeDefinition::objtype_document) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute definition not suitable for documents', 'data'=>''), 409);
            }

            $params = $request->getParsedBody();
            if(!isset($params['value'])) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute value not set', 'data'=>''), 400);
            }
            $new = $doc->getAttributeValue($attrdef) ? true : false;
            if(!$attrdef->validate($params['value'], $doc, $new)) {
                return $response->withJson(array('success'=>false, 'message'=>'Validation of attribute value failed: '.$attrdef->getValidationError(), 'data'=>''), 400);
            }
            if($doc->getAccessMode($userobj, 'setDocumentAttribute') > M_READ) {
                if ($doc->setAttributeValue($attrdef, $params['value'])) {
                    $logger->log("Setting attribute '".$attrdef->getName()."' (".$attrdef->getId().") to '".$params['value']."' successful", PEAR_LOG_INFO);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not set attribute value of document', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on document', 'data'=>''), 403);
            }
        } else {
            if(!$doc)
                return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), 404);
            if(!$attrdef)
                return $response->withJson(array('success'=>false, 'message'=>'No such attr definition', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'Could not find user or document', 'data'=>''), 500);
        }
    } /* }}} */

    function setDocumentContentAttribute($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $logger = $this->container->logger;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
            return;
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No document given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['version']) || $args['version'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No version number given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['attrdefid']) || $args['attrdefid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No attribute definition id given', 'data'=>''), 400);
            return;
        }
        $attrdef = $dms->getAttributeDefinition($args['attrdefid']);
        if($doc = $dms->getDocument($args['id']))
            $version = $doc->getContentByVersion($args['version']);
        if($doc && $attrdef && $version) {
            if($attrdef->getObjType() !== SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute definition not suitable for document versions', 'data'=>''), 409);
            }

            $params = $request->getParsedBody();
            if(!isset($params['value'])) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute value not set', 'data'=>''), 400);
            }
            $new = $version->getAttributeValue($attrdef) ? true : false;
            if(!$attrdef->validate($params['value'], $version, $new)) {
                return $response->withJson(array('success'=>false, 'message'=>'Validation of attribute value failed: '.$attrdef->getValidationError(), 'data'=>''), 400);
            }
            if($doc->getAccessMode($userobj, 'setDocumentContentAttribute') > M_READ) {
                if ($version->setAttributeValue($attrdef, $params['value'])) {
                    $logger->log("Setting attribute '".$attrdef->getName()."' (".$attrdef->getId().") to '".$params['value']."' successful", PEAR_LOG_INFO);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not set attribute value of document content', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on document', 'data'=>''), 403);
            }
        } else {
            if(!$doc)
                return $response->withJson(array('success'=>false, 'message'=>'No such document', 'data'=>''), 404);
            if(!$version)
                return $response->withJson(array('success'=>false, 'message'=>'No such version', 'data'=>''), 404);
            if(!$attrdef)
                return $response->withJson(array('success'=>false, 'message'=>'No such attr definition', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'Could not find user or document', 'data'=>''), 500);
        }
    } /* }}} */

    function setFolderAttribute($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $logger = $this->container->logger;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
            return;
        }

        if(!ctype_digit($args['id']) || $args['id'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No folder given', 'data'=>''), 400);
            return;
        }
        if(!ctype_digit($args['attrdefid']) || $args['attrdefid'] == 0) {
            return $response->withJson(array('success'=>false, 'message'=>'No attribute definition id given', 'data'=>''), 400);
            return;
        }
        $attrdef = $dms->getAttributeDefinition($args['attrdefid']);
        $obj = $dms->getFolder($args['id']);
        if($obj && $attrdef) {
            if($attrdef->getObjType() !== SeedDMS_Core_AttributeDefinition::objtype_folder) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute definition not suitable for folders', 'data'=>''), 409);
            }

            $params = $request->getParsedBody();
            if(!isset($params['value'])) {
                return $response->withJson(array('success'=>false, 'message'=>'Attribute value not set', 'data'=>''.$request->getHeader('Content-Type')[0]), 400);
            }
            if(strlen($params['value'])) {
                $new = $obj->getAttributeValue($attrdef) ? true : false;
                if(!$attrdef->validate($params['value'], $obj, $new)) {
                    return $response->withJson(array('success'=>false, 'message'=>'Validation of attribute value failed: '.$attrdef->getValidationError(), 'data'=>''), 400);
                }
            }
            if($obj->getAccessMode($userobj, 'setFolderAttribute') > M_READ) {
                if ($obj->setAttributeValue($attrdef, $params['value'])) {
                    $logger->log("Setting attribute '".$attrdef->getName()."' (".$attrdef->getId().") to '".$params['value']."' successful", PEAR_LOG_INFO);
                    return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 201);
                } else {
                        return $response->withJson(array('success'=>false, 'message'=>'Could not set attribute value of folder', 'data'=>''), 500);
                }
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'No access on folder', 'data'=>''), 403);
            }
        } else {
            if(!$obj)
                return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
            if(!$attrdef)
                return $response->withJson(array('success'=>false, 'message'=>'No such attr definition', 'data'=>''), 404);
            return $response->withJson(array('success'=>false, 'message'=>'Could not find user or folder', 'data'=>''), 500);
        }
    } /* }}} */

    function getAccount($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if($userobj) {
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getUserData($userobj)), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }
    } /* }}} */

    /**
     * Search for documents in the database
     *
     * If the request parameter 'mode' is set to 'typeahead', it will
     * return a list of words only.
     */
    function doSearch($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $params = $request->getQueryParams();
        $querystr = $params['query'];
        $mode = isset($params['mode']) ? $params['mode'] : '';
        if(!isset($params['limit']) || !$limit = $params['limit'])
            $limit = 5;
        if(!isset($params['offset']) || !$offset = $params['offset'])
            $offset = 0;
        if(!isset($params['searchin']) || !$searchin = explode(",",$params['searchin']))
            $searchin = array();
        if(!isset($params['objects']) || !$objects = $params['objects'])
            $objects = 0x3;
        $sparams = array(
            'query'=>$querystr,
            'limit'=>$limit,
            'offset'=>$offset,
            'logicalmode'=>'AND',
            'searchin'=>$searchin,
            'mode'=>$objects,
//            'creationstartdate'=>array('hour'=>1, 'minute'=>0, 'second'=>0, 'year'=>date('Y')-1, 'month'=>date('m'), 'day'=>date('d')),
        );
        $resArr = $dms->search($sparams);
//        $resArr = $dms->search($querystr, $limit, $offset, 'AND', $searchin, null, null, array(), array('hour'=>1, 'minute'=>0, 'second'=>0, 'year'=>date('Y')-1, 'month'=>date('m'), 'day'=>date('d')), array(), array(), array(), array(), array(), $objects);
        if($resArr === false) {
            return $response->withJson(array(), 200);
        }
        $entries = array();
        $count = 0;
        if($resArr['folders']) {
            foreach ($resArr['folders'] as $entry) {
                if ($entry->getAccessMode($userobj) >= M_READ) {
                    $entries[] = $entry;
                    $count++;
                }
                if($count >= $limit)
                    break;
            }
        }
        $count = 0;
        if($resArr['docs']) {
            foreach ($resArr['docs'] as $entry) {
                $lc = $entry->getLatestContent();
                if ($entry->getAccessMode($userobj) >= M_READ && $lc) {
                    $entries[] = $entry;
                    $count++;
                }
                if($count >= $limit)
                    break;
            }
        }

        switch($mode) {
            case 'typeahead';
                $recs = array();
                foreach ($entries as $entry) {
                /* Passing anything back but a string does not work, because
                 * the process function of bootstrap.typeahead needs an array of
                 * strings.
                 *
                 * As a quick solution to distingish folders from documents, the
                 * name will be preceeded by a 'F' or 'D'

                    $tmp = array();
                    if(get_class($entry) == 'SeedDMS_Core_Document') {
                        $tmp['type'] = 'folder';
                    } else {
                        $tmp['type'] = 'document';
                    }
                    $tmp['id'] = $entry->getID();
                    $tmp['name'] = $entry->getName();
                    $tmp['comment'] = $entry->getComment();
                 */
                    if(get_class($entry) == 'SeedDMS_Core_Document') {
                        $recs[] = 'D'.$entry->getName();
                    } else {
                        $recs[] = 'F'.$entry->getName();
                    }
                }
                if($recs)
    //                array_unshift($recs, array('type'=>'', 'id'=>0, 'name'=>$querystr, 'comment'=>''));
                    array_unshift($recs, ' '.$querystr);
                return $response->withJson($recs, 200);
                break;
            default:
                $recs = array();
                foreach ($entries as $entry) {
                    if(get_class($entry) == 'SeedDMS_Core_Document') {
                        $document = $entry;
                        $lc = $document->getLatestContent();
                        if($lc) {
                            $recs[] = $this->__getLatestVersionData($lc);
                        }
                    } elseif(get_class($entry) == 'SeedDMS_Core_Folder') {
                        $folder = $entry;
                        $recs[] = $this->__getFolderData($folder);
                    }
                }
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs));
                break;
        }
    } /* }}} */

    /**
     * Search for documents/folders with a given attribute=value
     *
     */
    function doSearchByAttr($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $params = $request->getQueryParams();
        $attrname = $params['name'];
        $query = $params['value'];
        if(empty($params['limit']) || !$limit = $params['limit'])
            $limit = 50;
        if(ctype_digit($attrname) && ((int) $attrname) > 0)
            $attrdef = $dms->getAttributeDefinition((int) $attrname);
        else
            $attrdef = $dms->getAttributeDefinitionByName($attrname);
        $entries = array();
        if($attrdef) {
            $resArr = $attrdef->getObjects($query, $limit);
            if($resArr['folders']) {
                foreach ($resArr['folders'] as $entry) {
                    if ($entry->getAccessMode($userobj) >= M_READ) {
                        $entries[] = $entry;
                    }
                }
            }
            if($resArr['docs']) {
                foreach ($resArr['docs'] as $entry) {
                    if ($entry->getAccessMode($userobj) >= M_READ) {
                        $entries[] = $entry;
                    }
                }
            }
        }
        $recs = array();
        foreach ($entries as $entry) {
            if(get_class($entry) == 'SeedDMS_Core_Document') {
                $document = $entry;
                $lc = $document->getLatestContent();
                if($lc) {
                    $recs[] = $this->__getLatestVersionData($lc);
                }
            } elseif(get_class($entry) == 'SeedDMS_Core_Folder') {
                $folder = $entry;
                $recs[] = $this->__getFolderData($folder);
            }
        }
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$recs), 200);
    } /* }}} */

    function checkIfAdmin($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!$userobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Not logged in', 'data'=>''), 403);
        }
        if(!$userobj->isAdmin()) {
            return $response->withJson(array('success'=>false, 'message'=>'You must be logged in with an administrator account to access this resource', 'data'=>''), 403);
        }

        return true;
    } /* }}} */

    function getUsers($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $users = $dms->getAllUsers();
        $data = [];
        foreach($users as $u)
        $data[] = $this->__getUserData($u);

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function createUser($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $params = $request->getParsedBody();
        if(empty(trim($params['user']))) {
            return $response->withJson(array('success'=>false, 'message'=>'Missing user login', 'data'=>''), 400);
        }
        $userName = $params['user'];
        $password = isset($params['pass']) ? $params['pass'] : '';
        if(empty(trim($params['name']))) {
            return $response->withJson(array('success'=>false, 'message'=>'Missing full user name', 'data'=>''), 400);
        }
        $fullname = $params['name'];
        $email = isset($params['email']) ? $params['email'] : '';
        $language = isset($params['language']) ? $params['language'] : null;;
        $theme = isset($params['theme']) ? $params['theme'] : null;
        $comment = isset($params['comment']) ? $params['comment'] : null;
        $role = isset($params['role']) ? $params['role'] : null;
        $roleid = $role == 'admin' ? SeedDMS_Core_User::role_admin : ($role == 'guest' ? SeedDMS_Core_User::role_guest : SeedDMS_Core_User::role_user);

        $newAccount = $dms->addUser($userName, $password, $fullname, $email, $language, $theme, $comment, $roleid);
        if ($newAccount === false) {
            return $response->withJson(array('success'=>false, 'message'=>'Account could not be created, maybe it already exists', 'data'=>''), 500);
        }

        $result = $this->__getUserData($newAccount);
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$result), 201);
    } /* }}} */

    function deleteUser($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if($user = $dms->getUser($args['id'])) {
            if($result = $user->remove($userobj, $userobj)) {
                return $response->withJson(array('success'=>$result, 'message'=>'', 'data'=>''), 200);
            } else {
                return $response->withJson(array('success'=>$result, 'message'=>'Could not delete user', 'data'=>''), 500);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such user', 'data'=>''), 404);
        }
    } /* }}} */

    /**
     * Updates the password of an existing Account, the password must be PUT as a md5 string
     *
     * @param      <type>  $id     The user name or numerical identifier
     */
    function changeUserPassword($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $params = $request->getParsedBody();
        if ($params['password'] == null) {
            return $response->withJson(array('success'=>false, 'message'=>'You must supply a new password', 'data'=>''), 400);
        }

        $newPassword = $params['password'];

        if(ctype_digit($args['id']))
            $account = $dms->getUser($args['id']);
        else {
            $account = $dms->getUserByLogin($args['id']);
        }

        /**
         * User not found
         */
        if (!$account) {
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>'User not found.'), 404);
            return;
        }

        $operation = $account->setPwd($newPassword);

        if (!$operation){
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>'Could not change password.'), 404);
        }

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
    } /* }}} */

    function getUserById($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;
        if(ctype_digit($args['id']))
            $account = $dms->getUser($args['id']);
        else {
            $account = $dms->getUserByLogin($args['id']);
        }
        if($account) {
            $data = $this->__getUserData($account);
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such user', 'data'=>''), 404);
        }
    } /* }}} */

    function setDisabledUser($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;
        $params = $request->getParsedBody();
        if (!isset($params['disable'])) {
            return $response->withJson(array('success'=>false, 'message'=>'You must supply a disabled state', 'data'=>''), 400);
        }

        $isDisabled = false;
        $status = $params['disable'];
        if ($status == 'true' || $status == '1') {
            $isDisabled = true;
        }

        if(ctype_digit($args['id']))
            $account = $dms->getUser($args['id']);
        else {
            $account = $dms->getUserByLogin($args['id']);
        }

        if($account) {
            $account->setDisabled($isDisabled);
            $data = $this->__getUserData($account);
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such user', 'data'=>''), 404);
        }
    } /* }}} */

    function getGroups($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $groups = $dms->getAllGroups();
        $data = [];
        foreach($groups as $u)
        $data[] = $this->__getGroupData($u);

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function createGroup($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;
        $params = $request->getParsedBody();
        if (empty($params['name'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Need a category.', 'data'=>''), 400);
        }

        $groupName = $params['name'];
        $comment = isset($params['comment']) ? $params['comment'] : '';

        $newGroup = $dms->addGroup($groupName, $comment);
        if ($newGroup === false) {
            return $response->withJson(array('success'=>false, 'message'=>'Group could not be created, maybe it already exists', 'data'=>''), 500);
        }

    //    $result = array('id'=>(int)$newGroup->getID());
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getGroupData($newGroup)), 201);
    } /* }}} */

    function getGroup($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;
        if(ctype_digit($args['id']))
            $group = $dms->getGroup($args['id']);
        else {
            $group = $dms->getGroupByName($args['id']);
        }
        if($group) {
            $data = $this->__getGroupData($group);
            $data['users'] = array();
            foreach ($group->getUsers() as $user) {
                $data['users'][] =  array('id' => (int)$user->getID(), 'login' => $user->getLogin());
            }
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such group', 'data'=>''), 404);
        }
    } /* }}} */

    function changeGroupMembership($request, $response, $args, $operationType) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if(ctype_digit($args['id']))
            $group = $dms->getGroup($args['id']);
        else {
            $group = $dms->getGroupByName($args['id']);
        }

       $params = $request->getParsedBody();
        if (empty($params['userid'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Missing userid', 'data'=>''), 200);
        }
        $userId = $params['userid'];
        if(ctype_digit($userId))
            $user = $dms->getUser($userId);
        else {
            $user = $dms->getUserByLogin($userId);
        }

        if (!($group && $user)) {
            return $response->withStatus(404);
        }

        $operationResult = false;

        if ($operationType == 'add')
        {
            $operationResult = $group->addUser($user);
        }
        if ($operationType == 'remove')
        {
            $operationResult = $group->removeUser($user);
        }

        if ($operationResult === false)
        {
            $message = 'Could not add user to the group.';
            if ($operationType == 'remove')
            {
                $message = 'Could not remove user from group.';
            }
            return $response->withJson(array('success'=>false, 'message'=>'Something went wrong. ' . $message, 'data'=>''), 500);
        }

        $data = $this->__getGroupData($group);
        $data['users'] = array();
        foreach ($group->getUsers() as $userObj) {
            $data['users'][] =  array('id' => (int)$userObj->getID(), 'login' => $userObj->getLogin());
        }
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function addUserToGroup($request, $response, $args) { /* {{{ */
        return changeGroupMembership($request, $response, $args, 'add');
    } /* }}} */

    function removeUserFromGroup($request, $response, $args) { /* {{{ */
        return changeGroupMembership($request, $response, $args, 'remove');
    } /* }}} */

    function setFolderInheritsAccess($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;
        $params = $request->getParsedBody();
        if (empty($params['enable']))
        {
            return $response->withJson(array('success'=>false, 'message'=>'You must supply an "enable" value', 'data'=>''), 200);
        }

        $inherit = false;
        $status = $params['enable'];
        if ($status == 'true' || $status == '1')
        {
            $inherit = true;
        }

        if(ctype_digit($args['id']))
            $folder = $dms->getFolder($args['id']);
        else {
            $folder = $dms->getFolderByName($args['id']);
        }

        if($folder) {
            $folder->setInheritAccess($inherit);
            $folderId = $folder->getId();
            $folder = null;
            // reread from db
            $folder = $dms->getFolder($folderId);
            $success = ($folder->inheritsAccess() == $inherit);
            return $response->withJson(array('success'=>$success, 'message'=>'', 'data'=>$data), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
    } /* }}} */

    function addUserAccessToFolder($request, $response, $args) { /* {{{ */
        return $this->changeFolderAccess($request, $response, $args, 'add', 'user');
    } /* }}} */

    function addGroupAccessToFolder($request, $response, $args) { /* {{{ */
        return $this->changeFolderAccess($request, $response, $args, 'add', 'group');
    } /* }}} */

    function removeUserAccessFromFolder($request, $response, $args) { /* {{{ */
        return $this->changeFolderAccess($request, $response, $args, 'remove', 'user');
    } /* }}} */

    function removeGroupAccessFromFolder($request, $response, $args) { /* {{{ */
        return $this->changeFolderAccess($request, $response, $args, 'remove', 'group');
    } /* }}} */

    function changeFolderAccess($request, $response, $args, $operationType, $userOrGroup) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if(ctype_digit($args['id']))
            $folder = $dms->getfolder($args['id']);
        else {
            $folder = $dms->getfolderByName($args['id']);
        }
        if (!$folder) {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }

        $params = $request->getParsedBody();
        $userOrGroupIdInput = $params['id'];
        if ($operationType == 'add')
        {
            if ($params['id'] == null)
            {
                return $response->withJson(array('success'=>false, 'message'=>'Please PUT the user or group Id', 'data'=>''), 200);
            }

            if ($params['mode'] == null)
            {
                return $response->withJson(array('success'=>false, 'message'=>'Please PUT the access mode', 'data'=>''), 200);
            }

            $modeInput = $params['mode'];

            $mode = M_NONE;
            if ($modeInput == 'read')
            {
                $mode = M_READ;
            }
            if ($modeInput == 'readwrite')
            {
                $mode = M_READWRITE;
            }
            if ($modeInput == 'all')
            {
                $mode = M_ALL;
            }
        }


        $userOrGroupId = $userOrGroupIdInput;
        if(!ctype_digit($userOrGroupIdInput) && $userOrGroup == 'user')
        {
            $userOrGroupObj = $dms->getUserByLogin($userOrGroupIdInput);
        }
        if(!ctype_digit($userOrGroupIdInput) && $userOrGroup == 'group')
        {
            $userOrGroupObj = $dms->getGroupByName($userOrGroupIdInput);
        }
        if(ctype_digit($userOrGroupIdInput) && $userOrGroup == 'user')
        {
            $userOrGroupObj = $dms->getUser($userOrGroupIdInput);
        }
        if(ctype_digit($userOrGroupIdInput) && $userOrGroup == 'group')
        {
            $userOrGroupObj = $dms->getGroup($userOrGroupIdInput);
        }
        if (!$userOrGroupObj) {
            return $response->withStatus(404);
        }
        $userOrGroupId = $userOrGroupObj->getId();

        $operationResult = false;

        if ($operationType == 'add' && $userOrGroup == 'user')
        {
            $operationResult = $folder->addAccess($mode, $userOrGroupId, true);
        }
        if ($operationType == 'remove' && $userOrGroup == 'user')
        {
            $operationResult = $folder->removeAccess($userOrGroupId, true);
        }

        if ($operationType == 'add' && $userOrGroup == 'group')
        {
            $operationResult = $folder->addAccess($mode, $userOrGroupId, false);
        }
        if ($operationType == 'remove' && $userOrGroup == 'group')
        {
            $operationResult = $folder->removeAccess($userOrGroupId, false);
        }

        if ($operationResult === false)
        {
            $message = 'Could not add user/group access to this folder.';
            if ($operationType == 'remove')
            {
                $message = 'Could not remove user/group access from this folder.';
            }
            return $response->withJson(array('success'=>false, 'message'=>'Something went wrong. ' . $message, 'data'=>''), 500);
        }

        $data = array();
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function getCategories($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(false === ($categories = $dms->getDocumentCategories())) {
            return $response->withJson(array('success'=>false, 'message'=>'Could not get categories', 'data'=>null), 500);
        }
        $data = [];
        foreach($categories as $category)
            $data[] = $this->__getCategoryData($category);

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    function getCategory($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $category = $dms->getDocumentCategory($args['id']);
        if($category) {
            return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getCategoryData($category)), 200);
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such category', 'data'=>''), 404);
        }
    } /* }}} */

    function createCategory($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $logger = $this->container->logger;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $params = $request->getParsedBody();
        if (empty($params['name'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Need a category.', 'data'=>''), 400);
        }

        $catobj = $dms->getDocumentCategoryByName($params['name']);
        if($catobj) {
            return $response->withJson(array('success'=>false, 'message'=>'Category already exists', 'data'=>''), 409);
        } else {
            if($data = $dms->addDocumentCategory($params['name'])) {
                $logger->log("Creating category '".$data->getName()."' (".$data->getId().") successful", PEAR_LOG_INFO);
                return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getCategoryData($data)), 201);
            } else {
                return $response->withJson(array('success'=>false, 'message'=>'Could not add category', 'data'=>''), 500);
            }
        }
    } /* }}} */

    function deleteCategory($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if($category = $dms->getDocumentCategory($args['id'])) {
            if($result = $category->remove()) {
                return $response->withJson(array('success'=>$result, 'message'=>'', 'data'=>''), 200);
            } else {
                return $response->withJson(array('success'=>$result, 'message'=>'Could not delete category', 'data'=>''), 500);
            }
        } else {
            return $response->withJson(array('success'=>false, 'message'=>'No such category', 'data'=>''), 404);
        }
    } /* }}} */

    /**
     * Updates the name of an existing category
     *
     * @param      <type>  $id     The user name or numerical identifier
     */
    function changeCategoryName($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $params = $request->getParsedBody();
        if (empty($params['name']))
        {
            return $response->withJson(array('success'=>false, 'message'=>'You must supply a new name', 'data'=>''), 400);
        }

        $newname = $params['name'];

        $category = $dms->getDocumentCategory($args['id']);

        /**
         * Category not found
         */
        if (!$category) {
            return $response->withJson(array('success'=>false, 'message'=>'No such category', 'data'=>''), 404);
        }

        if (!$category->setName($newname)) {
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>'Could not change name.'), 400);
        }

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getCategoryData($category)), 200);
    } /* }}} */

    function getAttributeDefinitions($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $attrdefs = $dms->getAllAttributeDefinitions();
        $data = [];
        foreach($attrdefs as $attrdef)
            $data[] = $this->__getAttributeDefinitionData($attrdef);

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

    /**
     * Updates the name of an existing attribute definition
     *
     * @param      <type>  $id     The user name or numerical identifier
     */
    function changeAttributeDefinitionName($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if(!ctype_digit($args['id'])) {
            return $response->withJson(array('success'=>false, 'message'=>'Invalid parameter', 'data'=>''), 400);
        }

        $params = $request->getParsedBody();
        if ($params['name'] == null) {
            return $response->withJson(array('success'=>false, 'message'=>'You must supply a new name', 'data'=>''), 400);
        }

        $newname = $params['name'];

        $attrdef = $dms->getAttributeDefinition($args['id']);

        /**
         * Attribute definition not found
         */
        if (!$attrdef) {
            return $response->withJson(array('success'=>false, 'message'=>'No such attribute defintion', 'data'=>''), 404);
        }

        if (!$attrdef->setName($newname)) {
            return $response->withJson(array('success'=>false, 'message'=>'', 'data'=>'Could not change name.'), 400);
            return;
        }

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$this->__getAttributeDefinitionData($attrdef)), 200);
    } /* }}} */

    function clearFolderAccessList($request, $response, $args) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;

        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        if(ctype_digit($args['id']))
            $folder = $dms->getFolder($args['id']);
        else {
            $folder = $dms->getFolderByName($args['id']);
        }
        if (!$folder) {
            return $response->withJson(array('success'=>false, 'message'=>'No such folder', 'data'=>''), 404);
        }
        if (!$folder->clearAccessList()) {
            return $response->withJson(array('success'=>false, 'message'=>'Something went wrong. Could not clear access list for this folder.', 'data'=>''), 500);
        }
        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>''), 200);
    } /* }}} */

    function getStatsTotal($request, $response) { /* {{{ */
        $dms = $this->container->dms;
        $userobj = $this->container->userobj;
        $check = $this->checkIfAdmin($request, $response);
        if($check !== true)
            return $check;

        $data = [];
        foreach(array('docstotal', 'folderstotal', 'userstotal') as $type) {
            $total = $dms->getStatisticalData($type);
            $data[$type] = $total;
        }

        return $response->withJson(array('success'=>true, 'message'=>'', 'data'=>$data), 200);
    } /* }}} */

} /* }}} */

class TestController { /* {{{ */
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function echoData($request, $response, $args) { /* {{{ */
        return $response->withJson(array('success'=>true, 'message'=>'This is the result of the echo call.', 'data'=>$args['data']), 200);
    } /* }}} */

    public function version($request, $response, $args) { /* {{{ */
        $logger = $this->container->logger;

        $v = new SeedDMS_Version();
        return $response->withJson(array('success'=>true, 'message'=>'This is '.$v->banner(), 'data'=>['major'=>$v->majorVersion(), 'minor'=>$v->minorVersion(), 'subminor'=>$v->subminorVersion()]), 200);
    } /* }}} */
} /* }}} */

/* Middleware for authentication */
class RestapiAuth { /* {{{ */

    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        // $this->container has the DI
        $dms = $this->container->dms;
        $settings = $this->container->config;
        $logger = $this->container->logger;
        $userobj = null;
        if($this->container->has('userobj'))
            $userobj = $this->container->userobj;

        if($userobj) {
            $response = $next($request, $response);
            return $response;
        }

        $logger->log("Invoke middleware for method ".$request->getMethod()." on '".$request->getUri()->getPath()."'", PEAR_LOG_INFO);
        $logger->log("Access with method ".$request->getMethod()." on '".$request->getUri()->getPath()."'".(isset($this->container->environment['HTTP_ORIGIN']) ? " with origin ".$this->container->environment['HTTP_ORIGIN'] : ''), PEAR_LOG_INFO);
        if($settings->_apiOrigin && isset($this->container->environment['HTTP_ORIGIN'])) {
            $logger->log("Checking origin", PEAR_LOG_DEBUG);
            $origins = explode(',', $settings->_apiOrigin);
            if(!in_array($this->container->environment['HTTP_ORIGIN'], $origins)) {
                return $response->withStatus(403);
            }
        }
        /* The preflight options request doesn't have authorization in the header. So
         * don't even try to authorize.
         */
        if($request->getMethod() == 'OPTIONS') {
            $logger->log("Received preflight options request", PEAR_LOG_DEBUG);
        } elseif(!in_array($request->getUri()->getPath(), array('login')) && substr($request->getUri()->getPath(), 0, 5) != 'echo/' && $request->getUri()->getPath() != 'version') {
            $userobj = null;
					if(!empty($this->container->environment['HTTP_AUTHORIZATION']) && !empty($settings->_apiKey) && !empty($settings->_apiUserId)) {
							$logger->log("Authorization key: ".$this->container->environment['HTTP_AUTHORIZATION'], PEAR_LOG_DEBUG);
							if($settings->_apiKey == $this->container->environment['HTTP_AUTHORIZATION']) {
									if(!($userobj = $dms->getUser($settings->_apiUserId))) {
											return $response->withJson(array('success'=>false, 'message'=>'Invalid user associated with api key', 'data'=>''), 403);
									}
							} else {
									return $response->withJson(array('success'=>false, 'message'=>'Wrong api key', 'data'=>''), 403);
							}
							$logger->log("Login with apikey as '".$userobj->getLogin()."' successful", PEAR_LOG_INFO);
					} else {
                require_once("../inc/inc.ClassSession.php");
                $session = new SeedDMS_Session($dms->getDb());
                if (isset($_COOKIE["mydms_session"])) {
                    $dms_session = $_COOKIE["mydms_session"];
                    $logger->log("Session key: ".$dms_session, PEAR_LOG_DEBUG);
                    if(!$resArr = $session->load($dms_session)) {
                        /* Delete Cookie */
                        setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
                        $logger->log("Session for id '".$dms_session."' has gone", PEAR_LOG_ERR);
												return $response->withJson(array('success'=>false, 'message'=>'Session has gone', 'data'=>''), 403);
                    }

                    /* Load user data */
                    $userobj = $dms->getUser($resArr["userID"]);
                    if (!is_object($userobj)) {
                        /* Delete Cookie */
                        setcookie("mydms_session", $dms_session, time()-3600, $settings->_httpRoot);
                        if($settings->_enableGuestLogin) {
                            if(!($userobj = $dms->getUser($settings->_guestID)))
																return $response->withJson(array('success'=>false, 'message'=>'Could not get guest login', 'data'=>''), 403);
                        } else
														return $response->withJson(array('success'=>false, 'message'=>'Login as guest disabled', 'data'=>''), 403);
                    }
                    if($userobj->isAdmin()) {
                        if($resArr["su"]) {
                            if(!($userobj = $dms->getUser($resArr["su"])))
																return $response->withJson(array('success'=>false, 'message'=>'Cannot substitute user', 'data'=>''), 403);
                        }
                    }
//                    $logger->log("Login with user name '".$userobj->getLogin()."' successful", PEAR_LOG_INFO);
                    $dms->setUser($userobj);
                } else {
										return $response->withJson(array('success'=>false, 'message'=>'Missing session cookie', 'data'=>''), 403);
                }
            }
            $this->container['userobj'] = $userobj;
        }
        $response = $next($request, $response);
        return $response;
    }
} /* }}} */

$app = new \Slim\App();

$container = $app->getContainer();
$container['dms'] = $dms;
$container['config'] = $settings;
$container['conversionmgr'] = $conversionmgr;
$container['logger'] = $logger;
$container['fulltextservice'] = $fulltextservice;
$container['notifier'] = $notifier;
$container['authenticator'] = $authenticator;

$app->add(new RestapiAuth($container));

if(isset($GLOBALS['SEEDDMS_HOOKS']['initRestAPI'])) {
    foreach($GLOBALS['SEEDDMS_HOOKS']['initRestAPI'] as $hookObj) {
        if (method_exists($hookObj, 'addMiddleware')) {
            $hookObj->addMiddleware($app);
        }
    }
}

// Make CORS preflighted request possible
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $req->getHeader('Origin') ? $req->getHeader('Origin') : '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
// use post for create operation
// use get for retrieval operation
// use put for update operation
// use delete for delete operation
$app->post('/login', \RestapiController::class.':doLogin');
$app->get('/logout', \RestapiController::class.':doLogout');
$app->get('/account', \RestapiController::class.':getAccount');
$app->get('/search', \RestapiController::class.':doSearch');
$app->get('/searchbyattr', \RestapiController::class.':doSearchByAttr');
$app->get('/folder', \RestapiController::class.':getFolder');
$app->get('/folder/{id}', \RestapiController::class.':getFolder');
$app->post('/folder/{id}/move/{folderid}', \RestapiController::class.':moveFolder');
$app->delete('/folder/{id}', \RestapiController::class.':deleteFolder');
$app->get('/folder/{id}/children', \RestapiController::class.':getFolderChildren');
$app->get('/folder/{id}/parent', \RestapiController::class.':getFolderParent');
$app->get('/folder/{id}/path', \RestapiController::class.':getFolderPath');
$app->get('/folder/{id}/attributes', \RestapiController::class.':getFolderAttributes');
$app->put('/folder/{id}/attribute/{attrdefid}', \RestapiController::class.':setFolderAttribute');
$app->post('/folder/{id}/folder', \RestapiController::class.':createFolder');
$app->put('/folder/{id}/document', \RestapiController::class.':uploadDocumentPut');
$app->post('/folder/{id}/document', \RestapiController::class.':uploadDocument');
$app->get('/document/{id}', \RestapiController::class.':getDocument');
$app->post('/document/{id}/attachment', \RestapiController::class.':uploadDocumentFile');
$app->post('/document/{id}/update', \RestapiController::class.':updateDocument');
$app->delete('/document/{id}', \RestapiController::class.':deleteDocument');
$app->post('/document/{id}/move/{folderid}', \RestapiController::class.':moveDocument');
$app->get('/document/{id}/content', \RestapiController::class.':getDocumentContent');
$app->get('/document/{id}/versions', \RestapiController::class.':getDocumentVersions');
$app->get('/document/{id}/version/{version}', \RestapiController::class.':getDocumentVersion');
$app->put('/document/{id}/version/{version}', \RestapiController::class.':updateDocumentVersion');
$app->get('/document/{id}/version/{version}/attributes', \RestapiController::class.':getDocumentContentAttributes');
$app->put('/document/{id}/version/{version}/attribute/{attrdefid}', \RestapiController::class.':setDocumentContentAttribute');
$app->get('/document/{id}/files', \RestapiController::class.':getDocumentFiles');
$app->get('/document/{id}/file/{fileid}', \RestapiController::class.':getDocumentFile');
$app->get('/document/{id}/links', \RestapiController::class.':getDocumentLinks');
$app->post('/document/{id}/link/{documentid}', \RestapiController::class.':addDocumentLink');
$app->get('/document/{id}/attributes', \RestapiController::class.':getDocumentAttributes');
$app->put('/document/{id}/attribute/{attrdefid}', \RestapiController::class.':setDocumentAttribute');
$app->get('/document/{id}/preview/{version}/{width}', \RestapiController::class.':getDocumentPreview');
$app->delete('/document/{id}/categories', \RestapiController::class.':removeDocumentCategories');
$app->delete('/document/{id}/category/{catid}', \RestapiController::class.':removeDocumentCategory');
$app->post('/document/{id}/category/{catid}', \RestapiController::class.':addDocumentCategory');
$app->put('/document/{id}/owner/{userid}', \RestapiController::class.':setDocumentOwner');
$app->put('/account/fullname', \RestapiController::class.':setFullName');
$app->put('/account/email', \RestapiController::class.':setEmail');
$app->get('/account/documents/locked', \RestapiController::class.':getLockedDocuments');
$app->get('/users', \RestapiController::class.':getUsers');
$app->delete('/users/{id}', \RestapiController::class.':deleteUser');
$app->post('/users', \RestapiController::class.':createUser');
$app->get('/users/{id}', \RestapiController::class.':getUserById');
$app->put('/users/{id}/disable', \RestapiController::class.':setDisabledUser');
$app->put('/users/{id}/password', \RestapiController::class.':changeUserPassword');
$app->post('/groups', \RestapiController::class.':createGroup');
$app->get('/groups', \RestapiController::class.':getGroups');
$app->get('/groups/{id}', \RestapiController::class.':getGroup');
$app->put('/groups/{id}/addUser', \RestapiController::class.':addUserToGroup');
$app->put('/groups/{id}/removeUser', \RestapiController::class.':removeUserFromGroup');
$app->put('/folder/{id}/setInherit', \RestapiController::class.':setFolderInheritsAccess');
$app->put('/folder/{id}/access/group/add', \RestapiController::class.':addGroupAccessToFolder'); //
$app->put('/folder/{id}/access/user/add', \RestapiController::class.':addUserAccessToFolder'); //
$app->put('/folder/{id}/access/group/remove', \RestapiController::class.':removeGroupAccessFromFolder');
$app->put('/folder/{id}/access/user/remove', \RestapiController::class.':removeUserAccessFromFolder');
$app->put('/folder/{id}/access/clear', \RestapiController::class.':clearFolderAccessList');
$app->get('/categories', \RestapiController::class.':getCategories');
$app->get('/categories/{id}', \RestapiController::class.':getCategory');
$app->delete('/categories/{id}', \RestapiController::class.':deleteCategory');
$app->post('/categories', \RestapiController::class.':createCategory');
$app->put('/categories/{id}/name', \RestapiController::class.':changeCategoryName');
$app->get('/attributedefinitions', \RestapiController::class.':getAttributeDefinitions');
$app->put('/attributedefinitions/{id}/name', \RestapiController::class.':changeAttributeDefinitionName');
$app->get('/echo/{data}', \TestController::class.':echoData');
$app->get('/version', \TestController::class.':version');
$app->get('/statstotal', \RestapiController::class.':getStatsTotal');

if(isset($GLOBALS['SEEDDMS_HOOKS']['initRestAPI'])) {
    foreach($GLOBALS['SEEDDMS_HOOKS']['initRestAPI'] as $hookObj) {
        if (method_exists($hookObj, 'addRoute')) {
            $hookObj->addRoute($app);
        }
    }
}

$app->run();

