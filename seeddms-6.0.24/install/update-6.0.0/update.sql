START TRANSACTION;

ALTER TABLE `tblDocumentContent` ADD COLUMN `revisiondate` datetime DEFAULT NULL;

ALTER TABLE `tblUsers` ADD COLUMN `secret` varchar(50) DEFAULT NULL AFTER `pwd`;

ALTER TABLE `tblWorkflows` ADD COLUMN `layoutdata` text DEFAULT NULL AFTER `initstate`;

ALTER TABLE `tblWorkflowDocumentContent` ADD COLUMN `id` int(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);

ALTER TABLE `tblWorkflowLog` ADD COLUMN `workflowdocumentcontent` int(11) NOT NULL DEFAULT '0' AFTER `id`;

UPDATE `tblWorkflowLog` a, `tblWorkflowDocumentContent` b SET a.`workflowdocumentcontent` = b.`id` WHERE a.`document` = b.`document` AND a.`version` = b.`version` AND a.`workflow` = b.`workflow`;

INSERT INTO `tblWorkflowDocumentContent` (`parentworkflow`, `workflow`, `document`, `version`, `state`, `date`) SELECT 0 AS `parentworkflow`, `workflow`, `document`, `version`, NULL AS `state`, max(`date`) AS `date` FROM `tblWorkflowLog` WHERE `workflowdocumentcontent` = 0 GROUP BY `workflow`, `document`, `version`;

UPDATE `tblWorkflowLog` a, `tblWorkflowDocumentContent` b SET a.`workflowdocumentcontent` = b.`id` WHERE a.`document` = b.`document` AND a.`version` = b.`version` AND a.`workflow` = b.`workflow`;

ALTER TABLE `tblWorkflowLog` ADD CONSTRAINT `tblWorkflowLog_workflowdocumentcontent` FOREIGN KEY (`workflowdocumentcontent`) REFERENCES `tblWorkflowDocumentContent` (`id`) ON DELETE CASCADE;

ALTER TABLE `tblWorkflowDocumentContent` ADD COLUMN `parent` int(11) DEFAULT NULL AFTER `id`;

ALTER TABLE `tblWorkflowDocumentContent` ADD CONSTRAINT `tblWorkflowDocumentContent_parent` FOREIGN KEY (`parent`) REFERENCES `tblWorkflowDocumentContent` (`id`) ON DELETE CASCADE;

ALTER TABLE `tblWorkflowDocumentContent` DROP COLUMN `parentworkflow`;

ALTER TABLE `tblWorkflowLog` DROP FOREIGN KEY `tblWorkflowLog_document`;

ALTER TABLE `tblWorkflowLog` DROP COLUMN `document`;

ALTER TABLE `tblWorkflowLog` DROP COLUMN `version`;

ALTER TABLE `tblWorkflowLog` DROP FOREIGN KEY `tblWorkflowLog_workflow`;

ALTER TABLE `tblWorkflowLog` DROP COLUMN `workflow`;

CREATE TABLE `tblUserSubstitutes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) DEFAULT null,
  `substitute` int(11) DEFAULT null,
  PRIMARY KEY (`id`),
  UNIQUE (`user`, `substitute`),
  CONSTRAINT `tblUserSubstitutes_user` FOREIGN KEY (`user`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblUserSubstitutes_substitute` FOREIGN KEY (`user`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblDocumentCheckOuts` (
  `document` int(11) NOT NULL DEFAULT '0',
  `version` smallint(5) unsigned NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`document`),
  CONSTRAINT `tblDocumentCheckOuts_document` FOREIGN KEY (`document`) REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblDocumentCheckOuts_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblDocumentRecipients` (
  `receiptID` int(11) NOT NULL AUTO_INCREMENT,
  `documentID` int(11) NOT NULL DEFAULT '0',
  `version` smallint(5) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `required` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`receiptID`),
  UNIQUE KEY `documentID` (`documentID`,`version`,`type`,`required`),
  CONSTRAINT `tblDocumentRecipients_document` FOREIGN KEY (`documentID`) REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE INDEX `indDocumentRecipientsRequired` ON `tblDocumentRecipients` (`required`);

CREATE TABLE `tblDocumentReceiptLog` (
  `receiptLogID` int(11) NOT NULL AUTO_INCREMENT,
  `receiptID` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `date` datetime NOT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`receiptLogID`),
  KEY `tblDocumentReceiptLog_receipt` (`receiptID`),
  KEY `tblDocumentReceiptLog_user` (`userID`),
  CONSTRAINT `tblDocumentReceiptLog_recipient` FOREIGN KEY (`receiptID`) REFERENCES `tblDocumentRecipients` (`receiptID`) ON DELETE CASCADE,
  CONSTRAINT `tblDocumentReceiptLog_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblDocumentRevisors` (
  `revisionID` int(11) NOT NULL AUTO_INCREMENT,
  `documentID` int(11) NOT NULL DEFAULT '0',
  `version` smallint(5) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `required` int(11) NOT NULL DEFAULT '0',
  `startdate` datetime DEFAULT NULL,
  PRIMARY KEY (`revisionID`),
  UNIQUE KEY `documentID` (`documentID`,`version`,`type`,`required`),
  CONSTRAINT `tblDocumentRevisors_document` FOREIGN KEY (`documentID`) REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE INDEX `indDocumentRevisorsRequired` ON `tblDocumentRevisors` (`required`);

CREATE TABLE `tblDocumentRevisionLog` (
  `revisionLogID` int(11) NOT NULL AUTO_INCREMENT,
  `revisionID` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `date` datetime NOT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`revisionLogID`),
  KEY `tblDocumentRevisionLog_revision` (`revisionID`),
  KEY `tblDocumentRevisionLog_user` (`userID`),
  CONSTRAINT `tblDocumentRevisionLog_revision` FOREIGN KEY (`revisionID`) REFERENCES `tblDocumentRevisors` (`revisionID`) ON DELETE CASCADE,
  CONSTRAINT `tblDocumentRevisionLog_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblTransmittals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `comment` text NOT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  CONSTRAINT `tblTransmittals_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblTransmittalItems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transmittal` int(11) NOT NULL DEFAULT '0',
  `document` int(11) DEFAULT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE (transmittal, document, version),
  CONSTRAINT `tblTransmittalItems_document` FOREIGN KEY (`document`) REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblTransmittalItem_transmittal` FOREIGN KEY (`transmittal`) REFERENCES `tblTransmittals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblRoles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `role` smallint(1) NOT NULL DEFAULT '0',
  `noaccess` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (1, 'Admin', 1);
INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (2, 'Guest', 2);
INSERT INTO `tblRoles` (`id`, `name`, `role`) VALUES (3, 'User', 0);
ALTER TABLE `tblRoles` AUTO_INCREMENT=4;

ALTER TABLE tblUsers CHANGE role role int(11) NOT NULL;
UPDATE `tblUsers` SET role=3 WHERE role=0;
ALTER TABLE tblUsers ADD CONSTRAINT `tblUsers_role` FOREIGN KEY (`role`) REFERENCES `tblRoles` (`id`);

CREATE TABLE `tblAros` (
  `id` int(11) NOT NULL auto_increment,
  `parent` int(11),
  `model` text NOT NULL,
  `foreignid` int(11) NOT NULL DEFAULT '0',
  `alias` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblAcos` (
  `id` int(11) NOT NULL auto_increment,
  `parent` int(11),
  `model` text NOT NULL,
  `foreignid` int(11) NOT NULL DEFAULT '0',
  `alias` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblArosAcos` (
  `id` int(11) NOT NULL auto_increment,
  `aro` int(11) NOT NULL DEFAULT '0',
  `aco` int(11) NOT NULL DEFAULT '0',
  `create` tinyint(4) NOT NULL DEFAULT '-1',
  `read` tinyint(4) NOT NULL DEFAULT '-1',
  `update` tinyint(4) NOT NULL DEFAULT '-1',
  `delete` tinyint(4) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  UNIQUE (aco, aro),
  CONSTRAINT `tblArosAcos_acos` FOREIGN KEY (`aco`) REFERENCES `tblAcos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblArosAcos_aros` FOREIGN KEY (`aro`) REFERENCES `tblAros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tblSchedulerTask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `disabled` smallint(1) NOT NULL DEFAULT '0',
  `extension` varchar(100) DEFAULT NULL,
  `task` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `params` text DEFAULT NULL,
  `nextrun` datetime DEFAULT NULL,
  `lastrun` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE tblVersion set major=6, minor=0, subminor=0;

COMMIT;
