--
-- Table structure for table `tblACLs`
--

CREATE TABLE `tblACLs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `target` INTEGER NOT NULL default '0',
  `targetType` INTEGER NOT NULL default '0',
  `userID` INTEGER NOT NULL default '-1',
  `groupID` INTEGER NOT NULL default '-1',
  `mode` INTEGER NOT NULL default '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblCategory`
--

CREATE TABLE `tblCategory` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblAttributeDefinitions`
--

CREATE TABLE `tblAttributeDefinitions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(100) default NULL,
  `objtype` INTEGER NOT NULL default '0',
  `type` INTEGER NOT NULL default '0',
  `multiple` INTEGER NOT NULL default '0',
  `minvalues` INTEGER NOT NULL default '0',
  `maxvalues` INTEGER NOT NULL default '0',
  `valueset` TEXT default NULL,
  `regex` TEXT DEFAULT NULL,
  UNIQUE(`name`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblRoles`
--

CREATE TABLE `tblRoles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) default NULL,
  `role` INTEGER NOT NULL default '0',
  `noaccess` varchar(30) NOT NULL default '',
  UNIQUE (`name`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblUsers`
--

CREATE TABLE `tblUsers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `login` varchar(50) default NULL,
  `pwd` varchar(50) default NULL,
  `secret` varchar(50) default NULL,
  `fullName` varchar(100) default NULL,
  `email` varchar(70) default NULL,
  `language` varchar(32) NOT NULL,
  `theme` varchar(32) NOT NULL,
  `comment` text NOT NULL,
  `role` INTEGER NOT NULL REFERENCES `tblRoles` (`id`),
  `hidden` INTEGER NOT NULL default '0',
  `pwdExpiration` TEXT default NULL,
  `loginfailures` INTEGER NOT NULL default '0',
  `disabled` INTEGER NOT NULL default '0',
  `quota` INTEGER,
  `homefolder` INTEGER default NULL REFERENCES `tblFolders` (`id`),
  UNIQUE (`login`)
);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserSubstitutes`
--

CREATE TABLE `tblUserSubstitutes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `substitute` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  UNIQUE (`user`, `substitute`)
);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserPasswordRequest`
--

CREATE TABLE `tblUserPasswordRequest` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `hash` varchar(50) default NULL,
  `date` TEXT NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserPasswordHistory`
--

CREATE TABLE `tblUserPasswordHistory` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `pwd` varchar(50) default NULL,
  `date` TEXT NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `tblUserImages`
--

CREATE TABLE `tblUserImages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `image` blob NOT NULL,
  `mimeType` varchar(100) NOT NULL default ''
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblFolders`
--

CREATE TABLE `tblFolders` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(70) default NULL,
  `parent` INTEGER default NULL,
  `folderList` text NOT NULL,
  `comment` text,
  `date` INTEGER default NULL,
  `owner` INTEGER default NULL REFERENCES `tblUsers` (`id`),
  `inheritAccess` INTEGER NOT NULL default '1',
  `defaultAccess` INTEGER NOT NULL default '0',
  `sequence` double NOT NULL default '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblFolderAttributes`
--

CREATE TABLE `tblFolderAttributes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `folder` INTEGER default NULL REFERENCES `tblFolders` (`id`) ON DELETE CASCADE,
  `attrdef` INTEGER default NULL REFERENCES `tblAttributeDefinitions` (`id`),
  `value` text default NULL,
  UNIQUE (`folder`, `attrdef`)
) ;
 
-- --------------------------------------------------------

--
-- Table structure for table `tblDocuments`
--

CREATE TABLE `tblDocuments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(150) default NULL,
  `comment` text,
  `date` INTEGER default NULL,
  `expires` INTEGER default NULL,
  `owner` INTEGER default NULL REFERENCES `tblUsers` (`id`),
  `folder` INTEGER default NULL REFERENCES `tblFolders` (`id`),
  `folderList` text NOT NULL,
  `inheritAccess` INTEGER NOT NULL default '1',
  `defaultAccess` INTEGER NOT NULL default '0',
  `locked` INTEGER NOT NULL default '-1',
  `keywords` text NOT NULL,
  `sequence` double NOT NULL default '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentAttributes`
--

CREATE TABLE `tblDocumentAttributes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `document` INTEGER default NULL REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `attrdef` INTEGER default NULL REFERENCES `tblAttributeDefinitions` (`id`),
  `value` text default NULL,
  UNIQUE (`document`, `attrdef`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentApprovers`
--

CREATE TABLE `tblDocumentApprovers` (
  `approveID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `type` INTEGER NOT NULL default '0',
  `required` INTEGER NOT NULL default '0',
  UNIQUE (`documentID`,`version`,`type`,`required`)
) ;
CREATE INDEX `indDocumentApproversRequired` ON `tblDocumentApprovers` (`required`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentApproveLog`
--

CREATE TABLE `tblDocumentApproveLog` (
  `approveLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `approveID` INTEGER NOT NULL default '0' REFERENCES `tblDocumentApprovers` (`approveID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default '0',
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;
CREATE INDEX `indDocumentApproveLogApproveID` ON `tblDocumentApproveLog` (`approveID`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentContent`
--

CREATE TABLE `tblDocumentContent` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `document` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`),
  `version` INTEGER unsigned NOT NULL,
  `comment` text,
  `date` INTEGER default NULL,
  `createdBy` INTEGER default NULL,
  `dir` varchar(255) NOT NULL default '',
  `orgFileName` varchar(150) NOT NULL default '',
  `fileType` varchar(10) NOT NULL default '',
  `mimeType` varchar(100) NOT NULL default '',
  `fileSize` INTEGER,
  `checksum` char(32),
  `revisiondate` TEXT default NULL,
  UNIQUE (`document`,`version`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentContentAttributes`
--

CREATE TABLE `tblDocumentContentAttributes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `content` INTEGER default NULL REFERENCES `tblDocumentContent` (`id`) ON DELETE CASCADE,
  `attrdef` INTEGER default NULL REFERENCES `tblAttributeDefinitions` (`id`),
  `value` text default NULL,
  UNIQUE (`content`, `attrdef`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentLinks`
--

CREATE TABLE `tblDocumentLinks` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `document` INTEGER NOT NULL default 0 REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `target` INTEGER NOT NULL default 0 REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`),
  `public` INTEGER NOT NULL default 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentFiles`
--

CREATE TABLE `tblDocumentFiles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `document` INTEGER NOT NULL default 0 REFERENCES `tblDocuments` (`id`),
  `version` INTEGER unsigned NOT NULL default '0',
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`),
  `public` INTEGER NOT NULL default '0',
  `comment` text,
  `name` varchar(150) default NULL,
  `date` INTEGER default NULL,
  `dir` varchar(255) NOT NULL default '',
  `orgFileName` varchar(150) NOT NULL default '',
  `fileType` varchar(10) NOT NULL default '',
  `mimeType` varchar(100) NOT NULL default ''
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentLocks`
--

CREATE TABLE `tblDocumentLocks` (
  `document` INTEGER REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentCheckOuts`
--

CREATE TABLE `tblDocumentCheckOuts` (
  `document` INTEGER REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`),
  `date` TEXT NOT NULL,
  `filename` varchar(255) NOT NULL default '',
  UNIQUE (`document`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentReviewers`
--

CREATE TABLE `tblDocumentReviewers` (
  `reviewID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `type` INTEGER NOT NULL default '0',
  `required` INTEGER NOT NULL default '0',
  UNIQUE (`documentID`,`version`,`type`,`required`)
) ;
CREATE INDEX `indDocumentReviewersRequired` ON `tblDocumentReviewers` (`required`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentReviewLog`
--

CREATE TABLE `tblDocumentReviewLog` (
  `reviewLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `reviewID` INTEGER NOT NULL default 0 REFERENCES `tblDocumentReviewers` (`reviewID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default 0,
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;
CREATE INDEX `indDocumentReviewLogReviewID` ON `tblDocumentReviewLog` (`reviewID`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentRecipients`
--

CREATE TABLE `tblDocumentRecipients` (
  `receiptID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `type` INTEGER NOT NULL default '0',
  `required` INTEGER NOT NULL default '0',
  UNIQUE (`documentID`,`version`,`type`,`required`)
) ;
CREATE INDEX `indDocumentRecipientsRequired` ON `tblDocumentRecipients` (`required`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentReceiptLog`
--

CREATE TABLE `tblDocumentReceiptLog` (
  `receiptLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `receiptID` INTEGER NOT NULL default 0 REFERENCES `tblDocumentRecipients` (`receiptID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default 0,
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;
CREATE INDEX `indDocumentReceiptLogReceiptID` ON `tblDocumentReceiptLog` (`receiptID`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentRevisors`
--

CREATE TABLE `tblDocumentRevisors` (
  `revisionID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `type` INTEGER NOT NULL default '0',
  `required` INTEGER NOT NULL default '0',
  `startdate` TEXT default NULL,
  UNIQUE (`documentID`,`version`,`type`,`required`)
) ;
CREATE INDEX `indDocumentRevisorsRequired` ON `tblDocumentRevisors` (`required`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentRevisionLog`
--

CREATE TABLE `tblDocumentRevisionLog` (
  `revisionLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `revisionID` INTEGER NOT NULL default 0 REFERENCES `tblDocumentRevisors` (`revisionID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default 0,
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;
CREATE INDEX `indDocumentRevisionLogRevisionID` ON `tblDocumentRevisionLog` (`revisionID`);

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentStatus`
--

CREATE TABLE `tblDocumentStatus` (
  `statusID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  UNIQUE (`documentID`,`version`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentStatusLog`
--

CREATE TABLE `tblDocumentStatusLog` (
  `statusLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `statusID` INTEGER NOT NULL default '0' REFERENCES `tblDocumentStatus` (`statusID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default '0',
  `comment` text NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;
CREATE INDEX `indDocumentStatusLogStatusID` ON `tblDocumentStatusLog` (`StatusID`);

-- --------------------------------------------------------

--
-- Table structure for table `tblGroups`
--

CREATE TABLE `tblGroups` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(50) default NULL,
  `comment` text NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblGroupMembers`
--

CREATE TABLE `tblGroupMembers` (
  `groupID` INTEGER NOT NULL default '0' REFERENCES `tblGroups` (`id`) ON DELETE CASCADE,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `manager` INTEGER NOT NULL default '0',
  UNIQUE  (`groupID`,`userID`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblKeywordCategories`
--

CREATE TABLE `tblKeywordCategories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(255) NOT NULL default '',
  `owner` INTEGER NOT NULL default '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblKeywords`
--

CREATE TABLE `tblKeywords` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `category` INTEGER NOT NULL default '0' REFERENCES `tblKeywordCategories` (`id`) ON DELETE CASCADE,
  `keywords` text NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblDocumentCategory`
--

CREATE TABLE `tblDocumentCategory` (
  `categoryID` INTEGER NOT NULL default '0' REFERENCES `tblCategory` (`id`) ON DELETE CASCADE,
  `documentID` INTEGER NOT NULL default '0' REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblNotify`
--

CREATE TABLE `tblNotify` (
  `target` INTEGER NOT NULL default '0',
  `targetType` INTEGER NOT NULL default '0',
  `userID` INTEGER NOT NULL default '-1',
  `groupID` INTEGER NOT NULL default '-1',
  UNIQUE  (`target`,`targetType`,`userID`,`groupID`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblSessions`
--

CREATE TABLE `tblSessions` (
  `id` varchar(50) PRIMARY KEY,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `lastAccess` INTEGER NOT NULL default '0',
  `theme` varchar(30) NOT NULL default '',
  `language` varchar(30) NOT NULL default '',
  `clipboard` text default NULL,
  `su` INTEGER DEFAULT NULL,
  `splashmsg` text default NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblMandatoryReviewers`
--

CREATE TABLE `tblMandatoryReviewers` (
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `reviewerUserID` INTEGER NOT NULL default '0',
  `reviewerGroupID` INTEGER NOT NULL default '0',
  UNIQUE (`userID`,`reviewerUserID`,`reviewerGroupID`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblMandatoryApprovers`
--

CREATE TABLE `tblMandatoryApprovers` (
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `approverUserID` INTEGER NOT NULL default '0',
  `approverGroupID` INTEGER NOT NULL default '0',
  UNIQUE (`userID`,`approverUserID`,`approverGroupID`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblEvents`
--

CREATE TABLE `tblEvents` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(150) default NULL,
  `comment` text,
  `start` INTEGER default NULL,
  `stop` INTEGER default NULL,
  `date` INTEGER default NULL,
  `userID` INTEGER NOT NULL default '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowStates`
--

CREATE TABLE `tblWorkflowStates` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL,
  `visibility` INTEGER DEFAULT 0,
  `maxtime` INTEGER DEFAULT 0,
  `precondfunc` text DEFAULT NULL,
  `documentstatus` INTEGER DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowActions`
--

CREATE TABLE `tblWorkflowActions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflows`
--

CREATE TABLE `tblWorkflows` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL,
  `initstate` INTEGER NOT NULL REFERENCES `tblWorkflowStates` (`id`) ON DELETE CASCADE,
  `layoutdata` text default NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowTransitions`
--

CREATE TABLE `tblWorkflowTransitions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `workflow` INTEGER default NULL REFERENCES `tblWorkflows` (`id`) ON DELETE CASCADE,
  `state` INTEGER default NULL REFERENCES `tblWorkflowStates` (`id`) ON DELETE CASCADE,
  `action` INTEGER default NULL REFERENCES `tblWorkflowActions` (`id`) ON DELETE CASCADE,
  `nextstate` INTEGER default NULL REFERENCES `tblWorkflowStates` (`id`) ON DELETE CASCADE,
  `maxtime` INTEGER DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowTransitionUsers`
--

CREATE TABLE `tblWorkflowTransitionUsers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `transition` INTEGER default NULL REFERENCES `tblWorkflowTransitions` (`id`) ON DELETE CASCADE,
  `userid` INTEGER default NULL REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowTransitionGroups`
--

CREATE TABLE `tblWorkflowTransitionGroups` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `transition` INTEGER default NULL REFERENCES `tblWorkflowTransitions` (`id`) ON DELETE CASCADE,
  `groupid` INTEGER default NULL REFERENCES `tblGroups` (`id`) ON DELETE CASCADE,
  `minusers` INTEGER default NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowDocumentContent`
--

CREATE TABLE `tblWorkflowDocumentContent` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent` INTEGER DEFAULT NULL REFERENCES `tblWorkflowDocumentContent` (`id`) ON DELETE CASCADE,
  `workflow` INTEGER DEFAULT NULL REFERENCES `tblWorkflows` (`id`) ON DELETE CASCADE,
  `document` INTEGER DEFAULT NULL REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER DEFAULT NULL,
  `state` INTEGER DEFAULT NULL REFERENCES `tblWorkflowStates` (`id`) ON DELETE CASCADE,
  `date` datetime NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowLog`
--

CREATE TABLE `tblWorkflowLog` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `workflowdocumentcontent` INTEGER DEFAULT NULL REFERENCES `tblWorkflowDocumentContent`   (`id`) ON DELETE CASCADE,
  `userid` INTEGER default NULL REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `transition` INTEGER default NULL REFERENCES `tblWorkflowTransitions` (`id`) ON DELETE CASCADE,
  `date` datetime NOT NULL,
  `comment` text
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblWorkflowMandatoryWorkflow`
--

CREATE TABLE `tblWorkflowMandatoryWorkflow` (
  `userid` INTEGER default NULL REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `workflow` INTEGER default NULL REFERENCES `tblWorkflows` (`id`) ON DELETE CASCADE,
  UNIQUE(`userid`, `workflow`)
) ;

-- --------------------------------------------------------

--
-- Table structure for transmittal
--

CREATE TABLE `tblTransmittals` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL,
  `comment` text NOT NULL,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `date` TEXT default NULL,
  `public` INTEGER NOT NULL default '0'
);

-- --------------------------------------------------------

--
-- Table structure for transmittal item
--

CREATE TABLE `tblTransmittalItems` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `transmittal` INTEGER NOT NULL DEFAULT '0' REFERENCES `tblTransmittals` (`id`) ON DELETE CASCADE,
  `document` INTEGER default NULL REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER unsigned NOT NULL default '0',
  `date` TEXT default NULL,
  UNIQUE (`transmittal`, `document`, `version`)
);

-- --------------------------------------------------------

--
-- Table structure for access request objects
--

CREATE TABLE `tblAros` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent` INTEGER,
  `model` TEXT NOT NULL,
  `foreignid` INTEGER NOT NULL DEFAULT '0',
  `alias` TEXT
) ;


-- --------------------------------------------------------

--
-- Table structure for access control objects
--

CREATE TABLE `tblAcos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `parent` INTEGER,
  `model` TEXT NOT NULL,
  `foreignid` INTEGER NOT NULL DEFAULT '0',
  `alias` TEXT
) ;

-- --------------------------------------------------------

--
-- Table structure for acos/aros relation
--

CREATE TABLE `tblArosAcos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `aro` INTEGER NOT NULL DEFAULT '0' REFERENCES `tblAros` (`id`) ON DELETE CASCADE,
  `aco` INTEGER NOT NULL DEFAULT '0' REFERENCES `tblAcos` (`id`) ON DELETE CASCADE,
  `create` INTEGER NOT NULL DEFAULT '-1',
  `read` INTEGER NOT NULL DEFAULT '-1',
  `update` INTEGER NOT NULL DEFAULT '-1',
  `delete` INTEGER NOT NULL DEFAULT '-1',
  UNIQUE (`aco`, `aro`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblSchedulerTask`
--

CREATE TABLE `tblSchedulerTask` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `disabled` INTEGER NOT NULL DEFAULT '0',
  `extension` varchar(100) DEFAULT NULL,
  `task` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `params` TEXT DEFAULT NULL,
  `nextrun` TEXT DEFAULT NULL,
  `lastrun` TEXT DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tblVersion`
--

CREATE TABLE `tblVersion` (
  `date` TEXT NOT NULL,
  `major` INTEGER,
  `minor` INTEGER,
  `subminor` INTEGER
) ;

-- --------------------------------------------------------

--
-- Initial content for database
--

INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (1, 'Admin', 1);
INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (2, 'Guest', 2);
INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (3, 'User', 0);
INSERT INTO `tblUsers` (`id`, `login`, `pwd`, `fullName`, `email`, `language`, `theme`, `comment`, `role`, `hidden`, `pwdExpiration`, `loginfailures`, `disabled`, `quota`, `homefolder`) VALUES (1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'info@seeddms.org', '', '', '', 1, 0, '', 0, 0, 0, NULL);
INSERT INTO `tblUsers` (`id`, `login`, `pwd`, `fullName`, `email`, `language`, `theme`, `comment`, `role`, `hidden`, `pwdExpiration`, `loginfailures`, `disabled`, `quota`, `homefolder`) VALUES (2, 'guest', NULL, 'Guest User', NULL, '', '', '', 2, 0, '', 0, 0, 0, NULL);
INSERT INTO `tblFolders` (`id`, `name`, `parent`, `folderList`, `comment`, `date`, `owner`, `inheritAccess`, `defaultAccess`, `sequence`) VALUES (1, 'DMS', NULL, '', 'DMS root', strftime('%s','now'), 1, 0, 2, 0);
INSERT INTO `tblVersion` VALUES (DATETIME(), 6, 0, 0);
