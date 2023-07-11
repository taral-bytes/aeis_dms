6.0.24 (2023-xx-xx)
---------------------
- all changes from 5.1.31 merged
- fix searching for revision date

6.0.23 (2023-04-01)
---------------------
- all changes from 5.1.30 merged

6.0.22 (2022-12-10)
---------------------
- all changes from 5.1.29 merged
- allow check in of document only if same user or unrestricted access rights

6.0.21 (2022-11-18)
---------------------
- all changes from 5.1.28 merged
   

6.0.20 (2022-09-18)
---------------------
- all changes from 5.1.27 merged
- SeedDMЅ_Core_DMS::getDocumentsInRevision() returns status from revision log

6.0.19 (2022-05-20)
---------------------
- all changes from 5.1.26
- removeFromProcesses() will not touch documents for which the new user does not have at least read access

6.0.18 (2022-04-22)
---------------------
- all changes from 5.1.25
- fix searching for document content with a custom attribute having a value set
- SeedDMS_Core_DocumentContent::getWorkflow() has optional parameter to return data from table tblWorkflowDocumentContent

6.0.17 (2021-12-11)
---------------------
- all changes from 5.1.24

6.0.16 (2021-05-07)
---------------------

6.0.15 (2021-04-13)
---------------------
- add searching for revision date
- expired documents can be skipped from counting in countTasks()
- SeedDMS_Core_DMS::getDocumentList() uses ambiguous column name when sorting by status
- add list type SleepingReviseByMe to SeedDMS_Core_DMS::getDocumentList()
- parameter 2 of SeedDMS_Core_DMS::getDocumentList() is treated as number of
  days for list DueRevisions

6.0.14 (2021-01-04)
---------------------
better error checking in SeedDMS_Core_Document::cancelCheckOut()

6.0.13 (2020-09-29)
---------------------
- SeedDMS_Folder_DMS::getAccessList() and getDefaultAccess() do not return fals anymore if the parent does not exists. They just stop inheritance.

6.0.12 (2020-06-05)
---------------------

6.0.11 (2020-06-05)
---------------------
SeedDMS_Core_DMS::filterAccess() properly checks for documents

6.0.10 (2020-05-22)
---------------------
SeedDMS_Core_DocumentContent::delRevisor() returns -4 if user has already made a revision

6.0.9 (2020-05-14)
---------------------
- no changes, just keep same version as seeddms application

6.0.8 (2020-03-02)
---------------------
- no changes, just keep same version as seeddms application

6.0.7 (2020-02-17)
---------------------
SeedDMS_Core_Document::getTimeline() returns revision only for latest content
add callback onSetStatus in SeedDMS_Core_DocumentContent::setStatus()
add new list type 'DueRevision' in SeedDMS_Core_DMS::getDocumentList()
a revision can also be started if some revisors have already reviewed the document
remove a user from all its process can also be used to set a new user

6.0.6 (2018-11-13)
---------------------
SeedDMS_Core_Folder::addContent() uses currently logged in user as uploader instead of owner
SeedDMS_Core_DocumentContent::verifyStatus() will not set status to S_RELEASED
if currently in S_DRAFT status und no workflow, review, approval, or revision
is pending.

6.0.5 (2018-02-27)
---------------------
add list 'NeedsCorrectionOwner' to SeedDMS_Core_DMS::getDocumentList()

6.0.4 (2018-02-14)
---------------------
add lists of drafts and obsolete docs in SeedDMS_Core_DMS::getDocumentList()
add fast sql statement to SeedDMS_Core_Document::getReceiptStatus() if limit=1
add callback onCheckAccessDocument to SeedDMS_Core_Document::getAccessMode()
add new document status 'needs correction' (S_NEEDS_CORRECTION)
do not use views as a replacement for temp. tables anymore, because they are much
slower.
add SeedDMS_Core_DocumentContent::getInstance()

6.0.3 (2018-01-23)
---------------------
pass 0 as default to getObjects()
SeedDMS_Core_AttributeDefinition::getStatistics() returns propper values for each item in a value set
SeedDMS_Core_DMS::getDocumentList() returns list of documents without a receiver

6.0.2 (2017-12-19)
---------------------
- speed up getting list of locked documents
- setting _logfile in inc.DBAccessPDO.php will log execution times in file
- fix sql statement to create temp table ttrevisionid and ttreceiptid
- SeedDMS_Core_DMS::noReadForStatus no longer needed
- SeedDMS_Core_Document::checkForDueRevisionWorkflow() also checks if there
are any waiting or pending revisions at all
- SeedDMS_Core_User::getReverseSubstitutes() works with new roles
- fix field name in getDocumentList() to make it work for pgsql
- views instead of temp. tables can be used
- ReceiveOwner list does not contain old versions anymore
- all changes up to 5.1.5 merged
- getTimeline() also returns data for documents with a scheduled revision

6.0.1 (2017-05-28)
---------------------
- speed up getting list of locked documents
- setting _logfile in inc.DBAccessPDO.php will log execution times in file

6.0.0 (2017-02-28)
---------------------
- all changes from 5.0.14 merged
- SeedDMS_Core_User::getReceiptStatus() and SeedDMS_Core_User::getReviewStatus()
only return entries of the latest document version if not specific document and
version is passed
- temp. table for revisions can be created
- new methods SeedDMS_Core_DMS::getDocumentsInReception() and
SeedDMS_Core_DMS::getDocumentsInRevision()
- limit hits of sql statement in SeedDMЅ_Core_DMS::getDuplicateDocumentContent() to 1000
- finishRevsion() puts all revisors into state waiting, so a new revision can be started
- fix SeedDMS_Core_Group::getRevisionStatus(), which did not always return the last
log entry first
- add roles
- use classname from SeedDMS_Core_DMS::_classnames for SeedDMS_Core_DocumentContent
- add virtual access mode for document links and attachments plus callbacks to
  check access mode in a hook
- add new method SeedDMS_Core_DMS::getDocumentsExpired()

5.1.31 (2023-xx-xx)
---------------------
- get mimetype from extension if application/octet-stream
- add method SeedDMS_Core_DMS::getLatestChanges()
- add caching of certain objects in SeedDMS_Core_DMS (experimental)
- fix search query when custom attribute value contains "'"
- SeedDMS_Core_DMS::search() checks ranges for attributes of type int or float
- SeedDMS_Core_DMS::search() takes timestamps for creation start/end date


5.1.30 (2023-04-01)
---------------------
- init intransaction counter to 0
- fix logging of intransaction counter
- log microtime as float
- run callbacks when changing name, comment, keywords, categories, owner

5.1.29 (2023-02-08)
---------------------
- SeedDMS_Core_Folder::addDocument() does rollback transaction propperly when setting document categories fail
- add $skiproot and $sep parameter to SeedDMS_Core_Folder::getFolderPathPlain()
- add class name for 'documentfile'
- add method SeedDMS_Core_KeywordCategory::countKeywordLists()

5.1.28 (2022-11-07)
---------------------
- fix SeedDMS_Core_User::getDocumentContents()
- fix SeedDMS_Core_File::fileExtension()
- SeedDMS_Core_DMS::createPasswordRequest() creates a cryptographically secure hash
- fix sql error when deleting a folder attribute
- add SeedDMS_Core_Attribute::getParsedValue() and use it in SeedDMS_Core_Object::getAttributeValue()
- add SeedDMS_Core_DMS::getDuplicateSequenceNo() and SeedDMS_Core_Folder::reorderDocuments()
- add SeedDMS_Core_File::mimetype(), fix SeedDMS_Core_File::moveDir()
- all file operations use methods of SeedDMS_Core_File
- change namespace of iterators from SeedDMS to SeedDMS\Core

5.1.27 (2022-08-31)
---------------------
- fix SeedDMS_Core_DMS::addAttributeDefinition() when objtype is 0
- sort search result even if sortorder is 'i' or 'n'
- pass an array as an attribute to search() will OR each element

5.1.26 (2022-05-20)
---------------------
- fix validating multi value attributes
- SeedDMS_Core_User::removeFromProcesses() can be limited to a list of documents. In that case only the last version will be modified.
- add more types to getStatisticalData()
- add optional parameter $op to SeedDMS_Core_AttributeDefinition::getObjects()
- SeedDMS_Core_AttributeDefinition::getObjects() will not filter by value if null is passed
- SeedDMS_Core_DMS::getAllAttributeDefinitions() has second parameter to filter attributes by type

5.1.25 (2022-04-22)
---------------------
- rename getLastWorkflowTransition() to getLastWorkflowLog()
- getLastWorkflowLog() returns a workflow entry even if the workflow has ended
- backport setFileType() from 6.0.x
- add SeedDMS_Core_File::fileExtension()
- add callbacks on onPostUpdateAttribute, onPostRemoveAttribute, onPostAddAttribute
- fix searching for document content with a custom attribute having a value set

5.1.24 (2021-12-11)
---------------------
- in SeedDMS_Core_DocumentContent::removeWorkflow() remove records from tblWorklflowLog before tblDWorkflowDocumentContent
- make all class variables of SeedDMS_Core_User protected
- fix various errors in SeedDMS_Core_AttributeDefinition::validate()
- add lots of unit tests
- replace incorrect use of array_search() by in_array()
- move method SeedDMS_Core_DMS::createDump() into SeedDMS_Core_DatabaseAccess
- lots of parameter checking when calling methods()
- make sure callbacks are callable
- SeedDMS_Core_Folder::getParent() returns null if there is no parent (used to be false)
- SeedDMS_Core_DMS::search() will not find document without an expiration date anymore, if the search is limited by an expiration end date but no start date
- add method SeedDMS_Core_Folder::getFoldersMinMax()
- init internal cache variables of SeedDMS_Core_Folder/SeedDMS_Core_Document and add method clearCache()
- SeedDMS_Core_Folder::hasDocuments() does not use the interal document cache anymore
- SeedDMS_Core_Document::addDocumentLink() returns an object of type SeedDMS_Core_DocumentLink in case of success
- trim email, comment, language, theme when setting data of user
- more checks whether an id > 0 when getting a database record

5.1.23 (2021-08-19)
---------------------
- SeedDMS_Core_DMS::getTimeline() uses status log instead of document content
- add methods SeedDMS_Core_DocumentContent::getReviewers() and SeedDMS_Core_DocumentContent::getApprovers()
- add methods SeedDMS_Core_DocumentContent::getApproveLog() and SeedDMS_Core_DocumentContent::getReviewLog()
- better handling of document with an empty workflow state
- fix checking of email addresses by using filter_var instead of regex
- add new method SeedDMS_Core_Document::hasCategory()
- add new method SeedDMS_Core_DocumentContent::removeReview()
- add new method SeedDMS_Core_DocumentContent::removeApproval()
- add new method SeedDMS_Core_User::getFolders()
- add new method SeedDMS_Core_User::getDocumentContents()
- add new method SeedDMS_Core_User::getDocumentFiles()
- add new method SeedDMS_Core_User::getDocumentLinks()
- add new type 'foldersperuser' to method SeedDMS_Core_DMS::getStatisticalData()

5.1.22 (2021-03-15)
---------------------
- add SeedDMS_Core_DatabaseAccess::hasTable()
- add SeedDMS_Core_User->isType() and SeedDMS_Core_Group->isType()
- add SeedDMS_Core_User->getDMS() and SeedDMS_Core_Group->getDMS()
- add new parameter to SeedDMS_Core_DMS->getDocumentList() for skipping expired documents
- add parameter $incdisabled to SeedDMS_Core_Folder::getNotifyList()
- do not validate value in SeedDMS_Core_Attribute::setValue(), it should have been done before
- SeedDMS_Core_DMS::search() can search for last date of document status change
- smarter caching in SeedDMS_Core_Document::getDocumentFiles() which fixes a potential
  problem when removing a document

5.1.21 (2020-09-29)
---------------------
- SeedDMS_Folder_DMS::getAccessList() and getDefaultAccess() do not return fals anymore if the parent does not exists. They just stop inheritance.
- pass attribute value to callback 'onAttributeValidate'
- new paramter 'new' of methode SeedDMЅ_Core_AttributeDefinition::validate()
- check if folder/document is below rootDir can be turned on (default off)
- SeedDMS_Core_User::setHomeFolder() can be used to unset the home folder
- check if attribute definition exists when setting attributes of folders and documents

5.1.20 (2020-09-29)
---------------------
- SeedDMS_Core_DMS::getDocumentList() returns false, if an unknown list is passed
- SeedDMS_Core_Document::getDocumentFiles() has new parameter to select only those files attached to a specific version of the document
- removing a document version will not remove attachments of the document anymore
- set dms of new user instances in SeedDMS_Core_Group

5.1.19 (2020-07-30)
---------------------
- add method SeedDMS_Core_Document::setParent() as an alias for setFolder()
- clear the save content list and latest content in SeedDMS_Core_Document after
  a version has been deleted.
- new method SeedDMS_Core_Document::isLatestVersion()
- add new attribute types 'document', 'folder', 'user', 'group'

5.1.18 (2020-05-28)
---------------------
- fixed remaining todos
- fixed parsing of file size in SeedDMS_Core_File::parse_filesize()
- fix SeedDMS_Core_DMS::getDocumentByOriginalFilename()

5.1.17 (2020-05-22)
---------------------
- add new callback onSetStatus
- fix SeedDMS_Core_DMS::getExpiredDocuments(), sql statement failed because temp. tables were not created
- add parameters $orderdir, $orderby, $update to SeedDMS_Core::getExpiredDocuments()

5.1.16 (2020-04-14)
---------------------
- fix call of hooks in SeedDMS_Core
- add variable lasterror in SeedDMS_Core_DMS which can be set by hooks to pass an
  error msg to the calling application
- better error checking in SeedDMS_Core_Document::addDocumentFile()

5.1.15 (2020-03-02)
---------------------
- no changes, just keep same version as seeddms application

5.1.14 (2020-02-17)
---------------------
- speed up SeedDMS_Core_Folder::getSubFolders() SeedDMS_Core_Folder::getDocuments() by minimizing the number of sql queries.

5.1.13 (2019-09-06)
---------------------
- add decorators
- add new methods SeedDMS_Core_Document::isType(), SeedDMS_Core_Folder::isType(), SeedDMS_Core_DocumentContent::isType(). Use them instead of checking the class name.
- skip a fileType with just a '.'

5.1.12 (2019-07-01)
---------------------
- parameter $orderby passed to SeedDMS_Core_Folder::getDocuments() and SeedDMS_Core_Folder::getSubFolders() can be a string, but only the first char is evaluated
- SeedDMS_Core_DMS::search() excepts parameters as array, added orderby
- add SeedDMS_Core_Folder::hasSubFolderByName()
- fix SeedDMS_Core_Folder::hasDocumentByName() which returned an int > 0 if documents
  has been loaded before and even if the document searching for was not among them.
- add new method SeedDMS_Core_Folder::empty()

5.1.11 (2019-05-03)
---------------------
- ???

5.1.10 (2019-04-04)
---------------------
- fix php warning if workflow state doesn' have next transition
- add method SeedDMS_Core_DatabaseAccess::setLogFp()

5.1.9 (2018-11-13)
---------------------
- context can be passed to getAccessMode()
- call hook in SeedDMS_Core_Folder::getAccessMode()
- new optional parameter $listguest for SeedDMS_Core_Document::getReadAccessList()
- remove deprecated methods SeedDMS_Core_Document::convert(), SeedDMS_Core_Document::wasConverted(), SeedDMS_Core_Document::viewOnline(), SeedDMS_Core_Document::getUrl()

5.1.8 (2018-07-02)
---------------------
- SeedDMS_Core_DMS::search() returns false in case of an error
- do not use views in DBAccessPDO by default anymore, use temp. tables
- SeedDMS_Core_Document::getNotifyList() has new parameter to include disabled user in list
- fix possible sql injection in SeedDMS_Core_User

5.1.7 (2018-04-05)
---------------------
- just bump version

5.1.6 (2018-02-14)
---------------------
- add SeedDMS_Core_Folder::getDocumentsMinMax()
- add lots of DocBlocks from merge request #8
- add SeedDMS_Core_AttributeDefinition::removeValue()

5.1.5 (2017-11-07)
---------------------
- use views instead of temp. tables
- add list of expired documents in SeedDMS_Core_DMS::getDocumentList()
- add methods to set comment, name, public, version of document files
- add method SeedDMS_Core_Document::transferToUser()
- SeedDMS_Core_Document::addDocumentFile() returns object of file
- add SeedDMS_Core_DocumentFile::setDate()
- remove SeedDMS_Core_DocumentCategory::addCategory() and getCategories()
- add optional parameters $limit and $offset to SeedDMS_Core_Folder::getDocuments()
  and SeedDMS_Core_Folder::getSubFolders()
- getInstance() returns now null instead of false if the object was not found in the db
- add new methods SeedDMS_Core_Document::addCategories() and
  SeedDMS_Core_Document::removeCategories()

5.1.4 (2017-09-05)
---------------------
- add virtual access mode for document links and attachments plus callbacks to
  check access mode in a hook
- add new method SeedDMS_Core_DMS::getDocumentsExpired()
- all changes from 5.0.14 merged

5.1.3 (2017-08-23)
---------------------
- SeedDMS_Core_Document::getNotifyList() and SeedDMS_Core_Folder::getNotifyList()
returns just users which are not disabled
- add new methods removeFromProcesses(), getWorkflowsInvolved(), getKeywordCategories() to SeedDMS_Core_User
- add methods isMandatoryReviewerOf() and isMandatoryApproverOf()
- add methods transferDocumentsFolders() and transferEvents()
- add method SeedDMS_Core_DMS::getDocumentByOriginalFilename()

5.1.2 (2017-03-23)
---------------------
- SeedDMS_Core_DMS::filterDocumentFiles() returns also documents which are not public
  if the owner tries to access them
- Check return value of onPreRemove[Document
Folder], return from calling method if bool
- Add SeedDMS_Core_DMS::getDocumentList()
- Limit number of duplicate files to 1000
- Add hook on(Pre
Post)RemoveContent
- Add hook onAttributeValidate

5.1.1 (2017-02-20)
---------------------
- all changes from 5.0.11 merged

5.1.0 (2017-02-20)
---------------------
- added postgres support

5.0.13 (2017-07-13)
---------------------
- all changes from 4.3.36 merged

5.0.12 (2017-03-23)
---------------------
all sql statements can be logged to a file
do not sort some temporary tables anymore, because it causes an error in mysql if            sql_mode=only_full_group_by is set

5.0.11 (2017-02-28)
---------------------
- all changes from 4.3.34 merged

5.0.10 (2017-02-20)
---------------------
- all changes from 4.3.33 merged

5.0.9 (2016-11-02)
---------------------
- all changes from 4.3.32 merged

5.0.8 (2016-11-02)
---------------------
- all changes from 4.3.31 merged

5.0.7 (2016-11-02)
---------------------
- all changes from 4.3.30 merged
- better attribute value checking

5.0.6 (2016-09-06)
---------------------
- all changes from 4.3.29 merged

5.0.5 (2016-08-09)
---------------------
- all changes from 4.3.28 merged

5.0.4 (2016-05-03)
---------------------
- all changes from 4.3.27 merged

5.0.3 (2016-04-04)
---------------------
- use classname from SeedDMS_Core_DMS::_classnames for SeedDMS_Core_DocumentContent
- all changes from 4.3.26 merged

5.0.2 (2016-04-26)
---------------------
- all changes from 4.3.25 merged

5.0.1 (2016-01-22)
---------------------
- all changes from 4.3.24 merged

5.0.0 (2016-01-22)
---------------------
- classes can be overloaded
- clean workflow log when a document version was deleted

4.3.37 (2018-02-14)
---------------------
- SeedDMS_Core_DMS::search() finds documents without a status log

4.3.36 (2017-03-22)
---------------------
- fix sql statement for creating temp. tables (sqlite)

4.3.35 (2017-07-11)
---------------------
do not sort some temporary tables anymore, because it causes an error in mysql if sql_mode=only_full_group_by is set

4.3.34 (2017-02-28)
---------------------
SeedDMS_Core_DMS::getDuplicateDocumentContent() returns complete document

4.3.33 (2017-02-22)
---------------------
- SeedDMЅ_Core_DMS::getTimeline() no longer returns duplicate documents
- SeedDMЅ_Core_Document::addContent() sets workflow after status was set
- SeedDMЅ_Core_Keyword::setOwner() fix sql statement
- SeedDMЅ_Core_User::setFullname() minor fix in sql statement

4.3.32 (2017-01-12)
---------------------
- order groups by name returned by getReadAccessList()
- add optional parameter to SeedDMS_Core_DMS::filterDocumentLinks()
- SeedDMS_Core_DMS::search() can search for document/folder id

4.3.31 (2016-11-02)
---------------------
- new method SeedDMЅ_Core_WorkflowAction::getTransitions() 
- new method SeedDMЅ_Core_WorkflowState::getTransitions() 
- new method SeedDMЅ_Core_AttributeDefinition::parseValue() 
- add check for cycles in workflow SeedDMS_Core_Workflow::checkForCycles()

4.3.30 (2016-10-07)
---------------------
- new method SeedDMЅ_Core_AttributeDefinition::getValueSetSeparator() 
- trim each value of a value set before saving the complete value set as a string

4.3.29 (2016-09-06)
---------------------
- SeedDMЅ_Core_Object::getAttributes() orders attributes by name of attribute definition
- SeedDMЅ_Core_Workflow::addTransition() force reload of transition list after adding a
- SeedDMЅ_Core_Document::rewrite[Review
Approval]Log() will also copy file if it exists
- add method SeedDMЅ_Core_Document::rewriteWorkflowLog()

4.3.28 (2016-08-24)
---------------------
- SeedDMЅ_Core_DMS::search() searches also comment of document version

4.3.27 (2016-04-26)
---------------------
- callbacks can have more then one user function
- fix some sql statements, because they didn't work with mysql 5.7.5 anymore

4.3.26 (2016-04-04)
---------------------
- add more callbacks

4.3.25 (2016-03-08)
---------------------
- rename SeedDMS_Core_Group::getNotificationsByGroup() to getNotifications()
- use __construct() for all constructors
- fix setting multi value attributes for versions

4.3.24 (2016-01-22)
---------------------
- make sure boolean attribute is saved as 0/1
- add SeedDMS_Core_User::[g
s]etMandatoryWorkflows()
- add SeedDMS_Core_User::getNotifications()
- add SeedDMS_Core_Group::getNotifications()
- SeedDMS_Core_DMS::getNotificationsByGroup() and
SeedDMS_Core_DMS::getNotificationsByUser() are deprecated
- SeedDMS_Core_DocumentCategory::getDocumentsByCategory() now returns the documents
- add SeedDMS_Core_Group::getWorkflowStatus()
- SeedDMS_Core_User::getDocumentsLocked() sets locking user propperly

4.3.24 (2016-01-21)
---------------------
- make sure boolean attribute is saved as 0/1
- add SeedDMS_Core_User::[g
s]etMandatoryWorkflows()
- add SeedDMS_Core_User::getNotifications()
- add SeedDMS_Core_Group::getNotifications()
- SeedDMS_Core_DMS::getNotificationsByGroup() and
SeedDMS_Core_DMS::getNotificationsByUser() are deprecated
- SeedDMS_Core_DocumentCategory::getDocumentsByCategory() now returns the documents
- add SeedDMS_Core_Group::getWorkflowStatus()
- SeedDMS_Core_User::getDocumentsLocked() sets locking user propperly

4.3.23 (2016-01-21)
---------------------
- new method SeedDMS_Core_DMS::createDump()
- minor improvements int SeedDMS_Core_Document::getReadAccessList()

4.3.22 (2015-11-09)
---------------------
- fix sql statement to reset password
- pass some more information for timeline

4.3.21 (2015-09-28)
---------------------
- add method SeedDMS_Core_Database::getCurrentTimestamp()
- add method SeedDMS_Core_Database::getCurrentDatetime()
- user getCurrentTimestamp() and getCurrentDatetime() whenever possible

4.3.20 (2015-06-26)
---------------------
- add method SeedDMS_Core_DMS::checkDate()
- add method SeedDMS_Core_Document::setDate()
- add method SeedDMS_Core_Folder::setDate()
- date can be passed to SeedDMS_Core_DocumentContent::setStatus()
- add method SeedDMS_Core_DocumentContent::rewriteStatusLog()
- add method SeedDMS_Core_DocumentContent::rewriteReviewLog()
- add method SeedDMS_Core_DocumentContent::rewriteApprovalLog()
- access rights for guest are also taken into account if set in an acl. Previously guest could gain read rights even if the access was probibited
by a group or user right

4.3.19 (2015-06-26)
---------------------
- add optional paramter $noclean to clearAccessList(), setDefaultAccess(), setInheritAccess()
- clearAccessList() will clean up the notifier list
- new method cleanNotifyList()

4.3.18 (2015-06-09)
---------------------
- add optional paramter $msg to SeedDMS_Core_DocumentContent::verifyStatus()
- add method SeedDMS_Core_DMS::getDuplicateDocumentContent()

4.3.17 (2015-03-27)
---------------------
clean workflow log when a document version was deleted

4.3.16 (2015-03-20)
---------------------
no changes

4.3.15 (2015-02-12)
---------------------
users returned by SeedDMS_Core_DMS::getAllUsers() have language and theme set again

4.3.13 (2014-11-27)
---------------------
- fix searching for attributes
- add some more documentation
- SeedDMS_Core_DMS::getDocumentCategories() returns categories sorted by name (Bug #181)
- new methode SeedDMS_Core_Document::replaceContent() which replaces the content of a version.
   <release>4.3.14</release>
- add missing start transaction in SeedDMD_Core_Folder::remove()
- SeedDMD_Core_Folder::isSubFolder() doesn't compare object instances anymore (Bug #194)

4.3.12 (2014-11-17)
---------------------
- fix searching folders with multivalue attributes

4.3.11 (2014-11-13)
---------------------
- fixed saving multivalue attributes
- add method SeedDMS_Core_Attribute::getValueAsArray()

4.3.10 (2014-10-22)
---------------------
new release

4.3.9 (2014-07-30)
---------------------
- SeedDMS_Core_KeywordCategory::getKeywordLists() sorts keywords aphabetically
- SeedDMS_Core_DMS::addUser() doesn't throw an error if sql_mode is set to STRICT_TRANS_TABLES and pwdexpiration is not set to a valid date.

4.3.8 (2014-04-09)
---------------------
- new method SeedDMS_Core_DMS::getStatisticalData()

4.3.7 (2014-03-21)
---------------------
no changes

4.3.6 (2014-03-18)
---------------------
- add optional parameters $publiconly=false and $user=null to SeedDMS_Core_Document::getDocumentLinks()
- add new method SeedDMS_Core_Document::getReverseDocumentLinks()

4.3.5 (2014-03-04)
---------------------
no changes

4.3.4 (2014-02-01)
---------------------
- fix handling of multivalue attributes

4.3.3 (2014-02-01)
---------------------
- SeedDMS_Folder::getDocuments() and SeedDMS_Folder::getSubFolders() do not
  do any sorting if $orderby is not set.
- database hostname can have port seperated by ':'
- make all functions in SeedDMS_Core_File static (fixes problem with php 5.5.x)

4.3.2 (2013-11-27)
---------------------
- new method SeedDMS_Core_Folder::isSubFolder()
- check for subFolder in SeedDMS_Core_Folder::setParent()
- new methods SeedDMS_Core_DMS::checkFolders() and SeedDMS_Core_DMS::checkDocuments()

4.3.0 (2013-09-05)
---------------------
- various small corrections
- comment of version is no longer taken from document if version comment is empty
- passing an array of users to SeedDMЅ_Core_DMS::search() instead of a single user ist now allowed
- turn on foreign key constraints for sqlite3
- SeedDMЅ_Core_Folder::getPath() can handle a subfolder treated as a root folder

4.2.2 (2013-05-17)
---------------------
- admins can be added as reviewer/approver again

4.2.1 (2013-04-30)
---------------------
- fixed bug in SeedDMS_Core_DocumentContent::addIndApp()

4.2.0 (2013-04-22)
---------------------
- fixed bug in SeedDMS_Core_DocumentContent::addIndApp()

4.1.3 (2013-04-08)
---------------------
- stay in sync with seeddms application

4.1.2 (2013-04-05)
---------------------
- set propper folderList of sub folders after moving a folder

4.1.1 (2013-04-05)
---------------------
- stay in sync with seeddms application

4.1.0 (2013-03-28)
---------------------
- minor bugfixes

4.0.0 (2013-02-26)
---------------------
- minor bugfixes

4.0.0pre5 (2013-02-14)
---------------------
- changed name from letodms to seeddms
- fixed SeedDMS_Database::TableList()

4.0.0pre4 (2013-02-11)
---------------------
- calculate checksum for document versions
- some bug fixes
- some more documentation
- added new methods SeedDMS_Core_Document::getReadUserList() and
  SeedDMS_Core_Folder::getReadUserList() which replaces getApproversList()
- fixed sql statement in getReadUserList() for sqlite3

4.0.0pre3 (2013-02-08)
---------------------
- minor bug fixes

4.0.0pre2 (2013-02-06)
---------------------
- lots of bug fixes
- replaced more of old var declaration
- more code documentation

4.0.0pre1 (2013-01-24)
---------------------
- added database transactions
- new workflow
- replaced old var declaration

4.0.0RC1 (2013-02-20)
---------------------
- minor bugfixes

3.4.0 (2012-12-13)
---------------------
- added PDO database driver, several sql changes for better compatiblity
- fixed bug when adding a new document category
- make sure the database remains consistent even in case of errors

3.3.9 (2012-09-19)
---------------------
- version update to be in sync with letodms application

3.3.8 (2012-09-16)
---------------------
- more sql injection protection in LetoDMS_Core_User

3.3.7 (2012-08-25)
---------------------
- no changes, just keep same version as letodms application

3.3.6 (2012-07-16)
---------------------
- no changes, just keep same version as letodms application

3.3.5 (2012-04-30)
---------------------
- minor corrections

3.3.4 (2012-04-11)
---------------------
- fixed bug in LetoDMS_Core_DocumentFile::getPath()

3.3.3 (2012-03-28)
---------------------
- fixed bug in LetoDMS_Core_Document::getPath()

3.3.2 (2012-03-22)
---------------------
- fixed bug in LetoDMS_Core_Document::getDir()

3.3.1 (2012-03-21)
---------------------
- new release

3.3.0 (2012-02-08)
---------------------
- added methods to find and repair errors in document and folder records
- removed sendmail parameter from some methods in LetoDMS_Core_Document
- do not use some of the temporay tables anymore
- SetFetchMode(ADODB_FETCH_ASSOC) in LetoDMS_Core_DatabaseAccess::connect()

3.2.0 (2011-07-23)
---------------------
New release

3.0.0 (2010-04-27)
---------------------
Initial release

