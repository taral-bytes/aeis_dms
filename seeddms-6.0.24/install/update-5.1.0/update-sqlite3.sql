BEGIN;

CREATE TABLE `new_tblVersion` (
  `date` TEXT default NULL,
  `major` INTEGER,
  `minor` INTEGER,
  `subminor` INTEGER
);

INSERT INTO `new_tblVersion` SELECT * FROM `tblVersion`;

DROP TABLE `tblVersion`;

ALTER TABLE `new_tblVersion` RENAME TO `tblVersion`;

CREATE TABLE `new_tblUserImages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `image` blob NOT NULL,
  `mimeType` varchar(100) NOT NULL default ''
);

INSERT INTO `new_tblUserImages` SELECT * FROM `tblUserImages`;

DROP TABLE `tblUserImages`;

ALTER TABLE `new_tblUserImages` RENAME TO `tblUserImages`;

CREATE TABLE `new_tblDocumentContent` (
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
  UNIQUE (`document`,`version`)
);

INSERT INTO `new_tblDocumentContent` SELECT * FROM `tblDocumentContent`;

DROP TABLE `tblDocumentContent`;

ALTER TABLE `new_tblDocumentContent` RENAME TO `tblDocumentContent`;

CREATE TABLE `new_tblDocumentFiles` (
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

INSERT INTO `new_tblDocumentFiles` SELECT `id`, `document`, 0, `userID`, 0, `comment`, `name`, `date`, `dir`, `orgFileName`, `fileType`, `mimeType` FROM `tblDocumentFiles`;

DROP TABLE `tblDocumentFiles`;

ALTER TABLE `new_tblDocumentFiles` RENAME TO `tblDocumentFiles`;

CREATE TABLE `new_tblUsers` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `login` varchar(50) default NULL,
  `pwd` varchar(50) default NULL,
  `fullName` varchar(100) default NULL,
  `email` varchar(70) default NULL,
  `language` varchar(32) NOT NULL,
  `theme` varchar(32) NOT NULL,
  `comment` text NOT NULL,
  `role` INTEGER NOT NULL default '0',
  `hidden` INTEGER NOT NULL default '0',
  `pwdExpiration` TEXT default NULL,
  `loginfailures` INTEGER NOT NULL default '0',
  `disabled` INTEGER NOT NULL default '0',
  `quota` INTEGER,
  `homefolder` INTEGER default NULL REFERENCES `tblFolders` (`id`),
  UNIQUE (`login`)
);

INSERT INTO `new_tblUsers` SELECT * FROM `tblUsers`;

DROP TABLE `tblUsers`;

ALTER TABLE `new_tblUsers` RENAME TO `tblUsers`;

CREATE TABLE `new_tblUserPasswordRequest` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `hash` varchar(50) default NULL,
  `date` TEXT NOT NULL
);

INSERT INTO `new_tblUserPasswordRequest` SELECT * FROM `tblUserPasswordRequest`;

DROP TABLE `tblUserPasswordRequest`;

ALTER TABLE `new_tblUserPasswordRequest` RENAME TO `tblUserPasswordRequest`;

CREATE TABLE `new_tblUserPasswordHistory` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `pwd` varchar(50) default NULL,
  `date` TEXT NOT NULL
);

INSERT INTO `new_tblUserPasswordHistory` SELECT * FROM `tblUserPasswordHistory`;

DROP TABLE `tblUserPasswordHistory`;

ALTER TABLE `new_tblUserPasswordHistory` RENAME TO `tblUserPasswordHistory`;

CREATE TABLE `new_tblDocumentReviewLog` (
  `reviewLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `reviewID` INTEGER NOT NULL default 0 REFERENCES `tblDocumentReviewers` (`reviewID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default 0,
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default 0 REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
);

INSERT INTO `new_tblDocumentReviewLog` SELECT * FROM `tblDocumentReviewLog`;

DROP TABLE `tblDocumentReviewLog`;

ALTER TABLE `new_tblDocumentReviewLog` RENAME TO `tblDocumentReviewLog`;

CREATE TABLE `new_tblDocumentStatusLog` (
  `statusLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `statusID` INTEGER NOT NULL default '0' REFERENCES `tblDocumentStatus` (`statusID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default '0',
  `comment` text NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ;

INSERT INTO `new_tblDocumentStatusLog` SELECT * FROM `tblDocumentStatusLog`;

DROP TABLE `tblDocumentStatusLog`;

ALTER TABLE `new_tblDocumentStatusLog` RENAME TO `tblDocumentStatusLog`;

CREATE TABLE `new_tblDocumentApproveLog` (
  `approveLogID` INTEGER PRIMARY KEY AUTOINCREMENT,
  `approveID` INTEGER NOT NULL default '0' REFERENCES `tblDocumentApprovers` (`approveID`) ON DELETE CASCADE,
  `status` INTEGER NOT NULL default '0',
  `comment` TEXT NOT NULL,
  `date` TEXT NOT NULL,
  `userID` INTEGER NOT NULL default '0' REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
);

INSERT INTO `new_tblDocumentApproveLog` SELECT * FROM `tblDocumentApproveLog`;

DROP TABLE `tblDocumentApproveLog`;

ALTER TABLE `new_tblDocumentApproveLog` RENAME TO `tblDocumentApproveLog`;

CREATE TABLE `new_tblWorkflowLog` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `document` INTEGER default NULL REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER default NULL,
  `workflow` INTEGER default NULL REFERENCES `tblWorkflows` (`id`) ON DELETE CASCADE,
  `userid` INTEGER default NULL REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  `transition` INTEGER default NULL REFERENCES `tblWorkflowTransitions` (`id`) ON DELETE CASCADE,
  `date` datetime NOT NULL,
  `comment` text
);

INSERT INTO `new_tblWorkflowLog` SELECT * FROM `tblWorkflowLog`;

DROP TABLE `tblWorkflowLog`;

ALTER TABLE `new_tblWorkflowLog` RENAME TO `tblWorkflowLog`;

CREATE TABLE `new_tblWorkflowDocumentContent` (
  `parentworkflow` INTEGER DEFAULT 0,
  `workflow` INTEGER DEFAULT NULL REFERENCES `tblWorkflows` (`id`) ON DELETE CASCADE,
  `document` INTEGER DEFAULT NULL REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  `version` INTEGER DEFAULT NULL,
  `state` INTEGER DEFAULT NULL REFERENCES `tblWorkflowStates` (`id`) ON DELETE CASCADE,
  `date` datetime NOT NULL
);

INSERT INTO `new_tblWorkflowDocumentContent` SELECT * FROM `tblWorkflowDocumentContent`;

DROP TABLE `tblWorkflowDocumentContent`;

ALTER TABLE `new_tblWorkflowDocumentContent` RENAME TO `tblWorkflowDocumentContent`;

UPDATE tblVersion set major=5, minor=1, subminor=0;

COMMIT;

