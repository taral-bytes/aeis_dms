START TRANSACTION;

ALTER TABLE "tblDocumentContent" ADD COLUMN "revisiondate" TIMESTAMP default NULL;

ALTER TABLE "tblUsers" ADD COLUMN "secret" varchar(50) default NULL;

ALTER TABLE "tblWorkflows" ADD COLUMN "layoutdata" text default NULL;

ALTER TABLE "tblWorkflowDocumentContent" ADD COLUMN "id" SERIAL UNIQUE;

ALTER TABLE "tblWorkflowLog" ADD COLUMN "workflowdocumentcontent" INTEGER NOT NULL DEFAULT '0';

UPDATE "tblWorkflowLog" SET "workflowdocumentcontent" = "tblWorkflowDocumentContent"."id" FROM "tblWorkflowDocumentContent" WHERE "tblWorkflowLog"."document" = "tblWorkflowDocumentContent"."document" AND "tblWorkflowLog"."version" = "tblWorkflowDocumentContent"."version" AND "tblWorkflowLog"."workflow" = "tblWorkflowDocumentContent"."workflow";

INSERT INTO "tblWorkflowDocumentContent" ("parentworkflow", "workflow", "document", "version", "state", "date") SELECT 0 AS "parentworkflow", "workflow", "document", "version", NULL AS "state", max("date") AS "date" FROM "tblWorkflowLog" WHERE "workflowdocumentcontent" = 0 GROUP BY "workflow", "document", "version";

UPDATE "tblWorkflowLog" SET "workflowdocumentcontent" = "tblWorkflowDocumentContent"."id" FROM "tblWorkflowDocumentContent" WHERE "tblWorkflowLog"."document" = "tblWorkflowDocumentContent"."document" AND "tblWorkflowLog"."version" = "tblWorkflowDocumentContent"."version" AND "tblWorkflowLog"."workflow" = "tblWorkflowDocumentContent"."workflow";

ALTER TABLE "tblWorkflowLog" ADD CONSTRAINT "tblWorkflowLog_workflowdocumentcontent" FOREIGN KEY ("workflowdocumentcontent") REFERENCES "tblWorkflowDocumentContent" ("id") ON DELETE CASCADE;

ALTER TABLE "tblWorkflowDocumentContent" ADD COLUMN "parent" INTEGER DEFAULT NULL;

ALTER TABLE "tblWorkflowDocumentContent" ADD CONSTRAINT "tblWorkflowDocumentContent_parent" FOREIGN KEY ("parent") REFERENCES "tblWorkflowDocumentContent" ("id") ON DELETE CASCADE;

ALTER TABLE "tblWorkflowDocumentContent" DROP COLUMN "parentworkflow";

ALTER TABLE "tblWorkflowLog" DROP COLUMN "document";

ALTER TABLE "tblWorkflowLog" DROP COLUMN "version";

ALTER TABLE "tblWorkflowLog" DROP COLUMN "workflow";

CREATE TABLE "tblUserSubstitutes" (
  "id" SERIAL UNIQUE,
  "user" INTEGER default null,
  "substitute" INTEGER default null,
  UNIQUE ("user", "substitute"),
  CONSTRAINT "tblUserSubstitutes_user" FOREIGN KEY ("user") REFERENCES "tblUsers" ("id") ON DELETE CASCADE,
  CONSTRAINT "tblUserSubstitutes_substitute" FOREIGN KEY ("user") REFERENCES "tblUsers" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblDocumentCheckOuts" (
  "document" INTEGER NOT NULL default '0',
  "version" INTEGER NOT NULL default '0',
  "userID" INTEGER NOT NULL default '0',
  "date" TIMESTAMP NOT NULL,
  "filename" varchar(255) NOT NULL default '',
  CONSTRAINT "tblDocumentCheckOuts_document" FOREIGN KEY ("document") REFERENCES "tblDocuments" ("id") ON DELETE CASCADE,
  CONSTRAINT "tblDocumentCheckOuts_user" FOREIGN KEY ("userID") REFERENCES "tblUsers" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblDocumentRecipients" (
  "receiptID" SERIAL UNIQUE,
  "documentID" INTEGER NOT NULL default '0',
  "version" INTEGER NOT NULL default '0',
  "type" INTEGER NOT NULL default '0',
  "required" INTEGER NOT NULL default '0',
  UNIQUE ("documentID","version","type","required"),
  CONSTRAINT "tblDocumentRecipients_document" FOREIGN KEY ("documentID") REFERENCES "tblDocuments" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblDocumentReceiptLog" (
  "receiptLogID" SERIAL UNIQUE,
  "receiptID" INTEGER NOT NULL default '0',
  "status" INTEGER NOT NULL default '0',
  "comment" text NOT NULL,
  "date" TIMESTAMP NOT NULL,
  "userID" INTEGER NOT NULL default '0',
  CONSTRAINT "tblDocumentReceiptLog_recipient" FOREIGN KEY ("receiptID") REFERENCES "tblDocumentRecipients" ("receiptID") ON DELETE CASCADE,
  CONSTRAINT "tblDocumentReceiptLog_user" FOREIGN KEY ("userID") REFERENCES "tblUsers" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblDocumentRevisors" (
  "revisionID" SERIAL UNIQUE,
  "documentID" INTEGER NOT NULL default '0',
  "version" INTEGER NOT NULL default '0',
  "type" INTEGER NOT NULL default '0',
  "required" INTEGER NOT NULL default '0',
  "startdate" TIMESTAMP default NULL,
  UNIQUE ("documentID","version","type","required"),
  CONSTRAINT "tblDocumentRevisors_document" FOREIGN KEY ("documentID") REFERENCES "tblDocuments" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblDocumentRevisionLog" (
  "revisionLogID" SERIAL UNIQUE,
  "revisionID" INTEGER NOT NULL default '0',
  "status" INTEGER NOT NULL default '0',
  "comment" text NOT NULL,
  "date" TIMESTAMP NOT NULL,
  "userID" INTEGER NOT NULL default '0',
  CONSTRAINT "tblDocumentRevisionLog_revision" FOREIGN KEY ("revisionID") REFERENCES "tblDocumentRevisors" ("revisionID") ON DELETE CASCADE,
  CONSTRAINT "tblDocumentRevisionLog_user" FOREIGN KEY ("userID") REFERENCES "tblUsers" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblTransmittals" (
  "id" SERIAL UNIQUE,
  "name" text NOT NULL,
  "comment" text NOT NULL,
  "userID" INTEGER NOT NULL default '0',
  "date" TIMESTAMP default NULL,
  "public" INTEGER NOT NULL default '0',
  CONSTRAINT "tblTransmittals_user" FOREIGN KEY ("userID") REFERENCES "tblUsers" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblTransmittalItems" (
  "id" SERIAL UNIQUE,
	"transmittal" INTEGER NOT NULL DEFAULT '0',
  "document" INTEGER default NULL,
  "version" INTEGER NOT NULL default '0',
  "date" TIMESTAMP default NULL,
  UNIQUE ("transmittal", "document", "version"),
  CONSTRAINT "tblTransmittalItems_document" FOREIGN KEY ("document") REFERENCES "tblDocuments" ("id") ON DELETE CASCADE,
  CONSTRAINT "tblTransmittalItem_transmittal" FOREIGN KEY ("transmittal") REFERENCES "tblTransmittals" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblRoles" (
  "id" SERIAL UNIQUE,
  "name" varchar(50) default NULL,
  "role" INTEGER NOT NULL default '0',
  "noaccess" varchar(30) NOT NULL default '',
  UNIQUE ("name")
);

INSERT INTO "tblRoles" ("id", "name", "role") VALUES (1, 'Admin', 1);
SELECT nextval('"tblRoles_id_seq"');
INSERT INTO "tblRoles" ("id", "name", "role") VALUES (2, 'Guest', 2);
SELECT nextval('"tblRoles_id_seq"');
INSERT INTO "tblRoles" ("id", "name", "role") VALUES (3, 'User', 0);
SELECT nextval('"tblRoles_id_seq"');

ALTER TABLE "tblUsers" ALTER "role" DROP DEFAULT;
ALTER TABLE "tblUsers" ALTER "role" SET NOT NULL;
UPDATE "tblUsers" SET role=3 WHERE role=0;
ALTER TABLE "tblUsers" ADD CONSTRAINT "tblUsers_role" FOREIGN KEY ("role") REFERENCES "tblRoles" ("id");

CREATE TABLE "tblAros" (
  "id" SERIAL UNIQUE,
  "parent" INTEGER,
  "model" text NOT NULL,
  "foreignid" INTEGER NOT NULL DEFAULT '0',
  "alias" varchar(255)
);

CREATE TABLE "tblAcos" (
  "id" SERIAL UNIQUE,
  "parent" INTEGER,
  "model" text NOT NULL,
  "foreignid" INTEGER NOT NULL DEFAULT '0',
  "alias" varchar(255)
);

CREATE TABLE "tblArosAcos" (
  "id" SERIAL UNIQUE,
  "aro" INTEGER NOT NULL DEFAULT '0',
  "aco" INTEGER NOT NULL DEFAULT '0',
  "create" INTEGER NOT NULL DEFAULT '-1',
  "read" INTEGER NOT NULL DEFAULT '-1',
  "update" INTEGER NOT NULL DEFAULT '-1',
  "delete" INTEGER NOT NULL DEFAULT '-1',
  UNIQUE ("aco", "aro"),
  CONSTRAINT "tblArosAcos_acos" FOREIGN KEY ("aco") REFERENCES "tblAcos" ("id") ON DELETE CASCADE,
  CONSTRAINT "tblArosAcos_aros" FOREIGN KEY ("aro") REFERENCES "tblAros" ("id") ON DELETE CASCADE
);

CREATE TABLE "tblSchedulerTask" (
  "id" SERIAL UNIQUE,
  "name" varchar(100) DEFAULT NULL,
  "description" TEXT DEFAULT NULL,
  "disabled" INTEGER NOT NULL DEFAULT '0',
  "extension" varchar(100) DEFAULT NULL,
  "task" varchar(100) DEFAULT NULL,
  "frequency" varchar(100) DEFAULT NULL,
  "params" TEXT DEFAULT NULL,
  "nextrun" TIMESTAMP DEFAULT NULL,
  "lastrun" TIMESTAMP DEFAULT NULL
) ;

UPDATE "tblVersion" set "major"=6, "minor"=0, "subminor"=0;

COMMIT;

