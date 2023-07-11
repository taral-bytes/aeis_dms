<?php
/**
 * Implementation of access restricitions
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

require_once "inc.ClassAcl.php";

/**
 * Class to check certain access restrictions
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_AccessOperation {
	/**
	 * @var object $dms reference to dms
	 * @access protected
	 */
	private $dms;

	/**
	 * @var object $user user requesting the access
	 * @access protected
	 */
	protected $user;

	/**
	 * @var object $settings SeedDMS Settings
	 * @access protected
	 */
	protected $settings;

	/**
	 * @var object $aro access request object for caching
	 * @access protected
	 */
	private $_aro;

	function __construct($dms, $user, $settings) { /* {{{ */
		$this->dms = $dms;
		$this->user = $user;
		$this->settings = $settings;
	} /* }}} */

	/**
	 * Check if editing of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayEditVersion($document, $vno=0) { /* {{{ */
		if($document->isType('document')) {
			if($vno)
				$version = $document->getContentByVersion($vno);
			else
				$version = $document->getLatestContent();
			if (!isset($this->settings->_editOnlineFileTypes) || !is_array($this->settings->_editOnlineFileTypes) || (!in_array(strtolower($version->getFileType()), $this->settings->_editOnlineFileTypes) && !in_array(strtolower($version->getMimeType()), $this->settings->_editOnlineFileTypes)))
				return false;
			if ($document->getAccessMode($this->user) == M_ALL || $this->user->isAdmin()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if removal of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayRemoveVersion($document) { /* {{{ */
		if($document->isType('document')) {
			$versions = $document->getContent();
			if ((($this->settings->_enableVersionDeletion && ($document->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin() ) && (count($versions) > 1)) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document status may be overwritten
	 *
	 * This check can only be done for documents. Overwriting the document
	 * status is
	 * only allowed if this is turned on in the settings and the current
	 * status is either 'releaѕed' or 'obsoleted'.
	 * The admin may even modify the status
	 * even if is disallowed in the settings.
	 */
	function mayOverrideStatus($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ((($this->settings->_enableVersionModification && ($document->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_DRAFT || $status["status"]==S_RELEASED || $status["status"]==S_REJECTED || $status["status"]==S_OBSOLETE || $status["status"]==S_NEEDS_CORRECTION)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if reviewers/approvers may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * reviewers/approvers is only allowed if version modification is turned on
	 * in the settings and the document has not been reviewed/approved by any
	 * user/group already.
	 * The admin may even set reviewers/approvers after the review/approval
	 * process has been started, but only if _allowChangeRevAppInProcess
	 * explicitly allows it.
	 */
	function maySetReviewersApprovers($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				$reviewstatus = $latestContent->getReviewStatus();
				$hasreview = false;
				foreach($reviewstatus as $r) {
					if($r['status'] == 1 || $r['status'] == -1)
						$hasreview = true;
				}
				$approvalstatus = $latestContent->getApprovalStatus();
				$hasapproval = false;
				foreach($approvalstatus as $r) {
					if($r['status'] == 1 || $r['status'] == -1)
						$hasapproval = true;
				}
				if ((($this->settings->_enableVersionModification && ($document->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && (($status["status"]==S_DRAFT_REV && (!$hasreview || ($this->user->isAdmin() && $this->settings->_allowChangeRevAppInProcess))) || ($status["status"]==S_DRAFT_APP && ((!$hasreview && !$hasapproval) || ($this->user->isAdmin() && $this->settings->_allowChangeRevAppInProcess))) || $status["status"]==S_DRAFT)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if recipients may be edited
	 *
	 * This check can only be done for documents. Setting the document
	 * recipients is only allowed if version modification is turned on
	 * in the settings.  The
	 * admin may even set recipients if is disallowed in the
	 * settings.
	 */
	function maySetRecipients($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if (($this->settings->_enableVersionModification && ($document->getAccessMode($this->user) >= M_READWRITE)) || $this->user->isAdmin()) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if revisors may be edited
	 *
	 * This check can only be done for documents. Setting the document
	 * revisors is only allowed if version modification is turned on
	 * in the settings.  The
	 * admin may even set revisors if is disallowed in the
	 * settings.
	 */
	function maySetRevisors($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ((($this->settings->_enableVersionModification && ($document->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_RELEASED || $status["status"]==S_IN_REVISION)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if workflow may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * workflow is only allowed if version modification is turned on
	 * in the settings and the document is in it's initial status.  The
	 * admin may even set the workflow if is disallowed in the
	 * settings.
	 */
	function maySetWorkflow($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$workflow = $latestContent->getWorkflow();
				$workflowstate = $latestContent->getWorkflowState();
				if ((($this->settings->_enableVersionModification && ($document->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && (!$workflow || ($workflowstate && ($workflow->getInitState()->getID() == $workflowstate->getID())))) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if expiration date may be set
	 *
	 * This check can only be done for documents. Setting the documents
	 * expiration date is only allowed if the document has not been obsoleted.
	 */
	function maySetExpires($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ((($document->getAccessMode($this->user) >= M_READWRITE) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if comment may be edited
	 *
	 * This check can only be done for documents. Setting the documents
	 * comment date is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted or expired.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditComment($document) { /* {{{ */
		if($document->isType('document')) {
			if($document->getAccessMode($this->user) < M_READWRITE)
				return false;
			if($document->isLocked()) {
				$lockingUser = $document->getLockingUser();
				if (($lockingUser->getID() != $this->user->getID()) && ($document->getAccessMode($this->user) != M_ALL)) {
					return false;
				}
			}
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if (($this->settings->_enableVersionModification || $this->user->isAdmin()) && !in_array($status["status"], array(S_OBSOLETE, S_EXPIRED))) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if attributes may be edited
	 *
	 * Setting the object attributes
	 * is only allowed if version modification is turned on in
	 * the settings or the document is still in an approval/review
	 * or intial workflow step.
	 */
	function mayEditAttributes($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				$workflow = $latestContent->getWorkflow();
				$workflowstate = $latestContent->getWorkflowState();
				if($document->getAccessMode($this->user) < M_READWRITE)
					return false;
				if ($this->settings->_enableVersionModification || in_array($status["status"], array(S_DRAFT_REV, S_DRAFT_APP, S_IN_REVISION)) || ($workflow && $workflowstate && $workflow->getInitState()->getID() == $workflowstate->getID())) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be reviewed
	 *
	 * Reviewing a document content is only allowed if the document is in
	 * review. There are other requirements which are not taken into
	 * account here.
	 */
	function mayReview($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($document->getAccessMode($this->user) >= M_READ && $status["status"]==S_DRAFT_REV) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if a review maybe edited
	 *
	 * A review may only be updated by the user who originaly addedd the
	 * review and if it is allowed in the settings
	 */
	function mayUpdateReview($document, $updateUser) { /* {{{ */
		if($document->isType('document')) {
			if($this->settings->_enableUpdateRevApp && ($updateUser == $this->user) && $document->getAccessMode($this->user) >= M_READ && !$document->hasExpired()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if a approval maybe edited
	 *
	 * An approval may only be updated by the user who originaly addedd the
	 * approval and if it is allowed in the settings
	 */
	function mayUpdateApproval($document, $updateUser) { /* {{{ */
		if($document->isType('document')) {
			if($this->settings->_enableUpdateRevApp && ($updateUser == $this->user) && $document->getAccessMode($this->user) >= M_READ && !$document->hasExpired()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be approved
	 *
	 * Approving a document content is only allowed if the document is either
	 * in approval status or released. In the second case the approval can be
	 * edited.
	 * There are other requirements which are not taken into
	 * account here.
	 */
	function mayApprove($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($document->getAccessMode($this->user) >= M_READ && $status["status"]==S_DRAFT_APP) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be receipted
	 *
	 * Reviewing a document content is only allowed if the document was not
	 * obsoleted. There are other requirements which are not taken into
	 * account here.
	 */
	function mayReceipt($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($document->getAccessMode($this->user) >= M_READ && $status["status"]==S_RELEASED) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if a review maybe edited
	 *
	 * A review may only be updated by the user who originaly addedd the
	 * review and if it is allowed in the settings
	 */
	function mayUpdateReceipt($document, $updateUser) { /* {{{ */
		if($document->isType('document')) {
			if($this->settings->_enableUpdateReceipt && ($updateUser == $this->user) && $document->getAccessMode($this->user) >= M_READ && !$document->hasExpired()) {
				return true;
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be revised
	 *
	 * Revising a document content is only allowed if the document was not
	 * obsoleted. There may be other requirements which are not taken into
	 * account here.
	 */
	function mayRevise($document) { /* {{{ */
		if($document->isType('document')) {
			if($latestContent = $document->getLatestContent()) {
				$status = $latestContent->getStatus();
				if ($document->getAccessMode($this->user) >= M_READ && $status["status"]!=S_OBSOLETE) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

	/**
	 * Check if document content may be checked in
	 *
	 *
	 */
	function mayCheckIn($document) { /* {{{ */
		if($document->isType('document')) {
			$checkoutinfo = $document->getCheckOutInfo();
			if(!$checkoutinfo)
				return false;
			$info = $checkoutinfo[0];
			if($this->user->getID() == $info['userID'] || $document->getAccessMode($this->user) == M_ALL) {
				return true;
			}
		}
		return false;
	} /* }}} */

	protected function check_view_legacy_access($view, $get=array()) { /* {{{ */
		if($this->user->isAdmin())
			return true;

		if(is_string($view)) {
			$scripts = array($view);
		} elseif(is_array($view)) {
			$scripts = $view;
		} elseif(is_subclass_of($view, 'SeedDMS_View_Common')) {
			$scripts = array($view->getParam('class'));
		} else {
			return false;
		}

		if($this->user->isGuest()) {
			$user_allowed = array(
				'Calendar',
				'ErrorDlg',
				'Help',
				'Login',
				'Search',
				'ViewDocument',
				'ViewFolder',
			);
		} else {
			$user_allowed = array(
				'AddDocument',
				'AddDocumentLink',
				'AddEvent',
				'AddFile',
				'AddSubFolder',
				'AddToTransmittal',
				'ApprovalSummary',
				'ApproveDocument',
				'Calendar',
				'CategoryChooser',
				'ChangePassword',
				'CheckInDocument',
				'Clipboard',
				'DocumentAccess',
				'DocumentChooser',
				'DocumentNotify',
				'DocumentVersionDetail',
				'DropFolderChooser',
				'EditAttributes',
				'EditComment',
				'EditDocumentFile',
				'EditDocument',
				'EditEvent',
				'EditFolder',
				'EditOnline',
				'EditUserData',
				'ErrorDlg',
				'FolderAccess',
				'FolderChooser',
				'FolderNotify',
				'ForcePasswordChange',
				'GroupView',
				'Help',
				'KeywordChooser',
				'Login',
				'ManageNotify',
				'MoveDocument',
				'MoveFolder',
				'MyAccount',
				'MyDocuments',
				'OpensearchDesc',
				'OverrideContentStatus',
				'PasswordForgotten',
				'PasswordSend',
				'ReceiptDocument',
				'ReceiptSummary',
				'RemoveDocumentFile',
				'RemoveDocument',
				'RemoveEvent',
				'RemoveFolderFiles',
				'RemoveFolder',
				'RemoveTransmittal',
				'RemoveVersion',
				'RemoveWorkflowFromDocument',
				'ReturnFromSubWorkflow',
				'ReviewDocument',
				'ReviewSummary',
				'ReviseDocument',
				'RevisionSummary',
				'RewindWorkflow',
				'RunSubWorkflow',
				'Search',
				'Session',
				'SetExpires',
				'SetRecipients',
				'SetReviewersApprovers',
				'SetRevisors',
				'SetWorkflow',
				'SubstituteUser',
				'Tasks',
				'TransmittalMgr',
				'TriggerWorkflow',
				'UpdateDocument',
				'UserDefaultKeywords',
				'UserImage',
				'UsrView',
				'ViewDocument',
				'ViewEvent',
				'ViewFolder',
				'WorkflowGraph',
				'WorkflowSummary');
		}

		if(array_intersect($scripts, $user_allowed))
			return true;

		return false;
	} /* }}} */

	/**
	 * Check for access permission on view
	 *
	 * If the parameter $view is an array then each element is considered the
	 * name of a view and true will be returned if one of them is accessible.
	 * Whether access is allowed also depends on the currently logged in user
	 * stored in the view object. If the user is an admin the access 
	 * on a view must be explicitly disallowed. For regular users the access
	 * must be explicitly allowed.
	 *
	 * If advanced access control is turn off, this function will always return
	 * true for admins and false for other users.
	 *
	 * @param mixed $view Instanz of view, name of view or array of view names
	 * @param string $get query parameters possible containing the element 'action'
	 * @return boolean true if access is allowed, false if access is disallowed
	 * no specific access right is set, otherwise false
	 */
	function check_view_access($view, $get=array()) { /* {{{ */
		if(!$this->settings->_advancedAcl) {
			return $this->check_view_legacy_access($view, $get);
		}
		if(is_string($view)) {
			$scripts = array($view);
		} elseif(is_array($view)) {
			$scripts = $view;
		} elseif(is_subclass_of($view, 'SeedDMS_View_Common')) {
			$scripts = array($view->getParam('class'));
		} else {
			return false;
		}
		$scope = 'Views';
		$action = (isset($get['action']) && $get['action']) ? $get['action'] : 'show';
		$acl = new SeedDMS_Acl($this->dms);
		if(!$this->_aro)
			$this->_aro = SeedDMS_Aro::getInstance($this->user->getRole(), $this->dms);
		foreach($scripts as $script) {
			$aco = SeedDMS_Aco::getInstance($scope.'/'.$script.'/'.$action, $this->dms);
			$ll = $acl->check($this->_aro, $aco);
			if($ll === 1 && !$this->user->isAdmin() || $ll !== -1 && $this->user->isAdmin())
				return true;
		}
		return false;
	} /* }}} */

	/**
	 * Check for access permission on controller
	 *
	 * If the parameter $controller is an array then each element is considered the
	 * name of a controller and true will be returned if one is accesible.
	 * If advanced access controll is turn off, this function will return false
	 * for guest users and true otherwise.
	 *
	 * @param mixed $controller Instanz of controller, name of controller or array of controller names
	 * @param string $get query parameters
	 * @return boolean true if access is allowed otherwise false
	 */
	function check_controller_access($controller, $get=array()) { /* {{{ */
		if(!$this->settings->_advancedAcl) {
			if($this->user->isGuest())
				return false;
			elseif($this->user->isAdmin())
				return true;
			else {
				if($controller == 'AddDocument' && isset($get['action']) && $get['action'] == 'setOwner')
					return false;
				return true;
			}
		}
		if(is_string($controller)) {
			$scripts = array($controller);
		} elseif(is_array($controller)) {
			$scripts = $controller;
		} elseif(is_subclass_of($controller, 'SeedDMS_Controller_Common')) {
			$scripts = array($controller->getParam('class'));
		} else {
			return false;
		}
		$scope = 'Controllers';
		$action = (isset($get['action']) && $get['action']) ? $get['action'] : 'run';
		$acl = new SeedDMS_Acl($this->dms);
		if(!$this->_aro)
			$this->_aro = SeedDMS_Aro::getInstance($this->user->getRole(), $this->dms);
		foreach($scripts as $script) {
			$aco = SeedDMS_Aco::getInstance($scope.'/'.$script.'/'.$action, $this->dms);
			$ll = $acl->check($this->_aro, $aco);
			if($ll === 1 && !$this->user->isAdmin() || $ll !== -1 && $this->user->isAdmin())
				return true;
		}
		return false;
	} /* }}} */
}
