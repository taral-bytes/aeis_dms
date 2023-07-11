<?php
/**
 * Implementation of the review and approval tests
 *
 * PHP version 7
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   @package_version@
 * @link      https://www.seeddms.org
 */

use PHPUnit\Framework\SeedDmsTest;

/**
 * Group test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class ReviewApprovalTest extends SeedDmsTest
{

    /**
     * Create a real sqlite database in memory
     *
     * @return void
     */
    protected function setUp(): void
    {
        self::$dbh = self::createInMemoryDatabase();
        self::$contentdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit-'.time();
        mkdir(self::$contentdir);
        //      echo "Creating temp content dir: ".self::$contentdir."\n";
        self::$dms = new SeedDMS_Core_DMS(self::$dbh, self::$contentdir);
    }

    /**
     * Clean up at tear down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        self::$dbh = null;
        //      echo "\nRemoving temp. content dir: ".self::$contentdir."\n";
        exec('rm -rf '.self::$contentdir);
    }

    /**
     * Test method addIndReviewer(), addGrpReviewer(), verifyStatus(),
     * getReviewStatus(), removeReview(), delIndReviewer()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testReviewDocumentByUserAndGroup()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new group which will be the reviewer */
        $reviewergrp = self::$dms->addGroup('reviewer', '');
        $this->assertIsObject($reviewergrp);

        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $content = $document->getLatestContent();
        $this->assertIsObject($content);
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* A missing reviewer or user causes an error */
        $ret = $content->addIndReviewer($reviewer, null);
        $this->assertEquals(-1, $ret);

        /* A missing reviewer or user causes an error */
        $ret = $content->addIndReviewer(null, $user);
        $this->assertEquals(-1, $ret);

        /* Adding a group instead of a user causes an error */
        $ret = $content->addIndReviewer($reviewergrp, $user);
        $this->assertEquals(-1, $ret);

        /* Finally add the reviewer */
        $ret = $content->addIndReviewer($reviewer, $user);
        $this->assertGreaterThan(0, $ret);

        /* Adding the user again will yield in an error */
        $ret = $content->addIndReviewer($reviewer, $user);
        $this->assertEquals(-3, $ret);

        /* Needs to call verifyStatus() in order to recalc the status */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_REV, $newstatus);

        /* Get all reviews */
        $reviewstatus = $content->getReviewStatus();
        $this->assertIsArray($reviewstatus);
        $this->assertCount(1, $reviewstatus);

        /* Get list of individual und group reviewers */
        $reviewers = $content->getReviewers();
        $this->assertIsArray($reviewers);
        $this->assertCount(2, $reviewers);
        $this->assertCount(1, $reviewers['i']);
        $this->assertCount(0, $reviewers['g']);
/*
        $db = self::$dms->getDB();
        $db->createTemporaryTable("ttreviewid", true);
        $queryStr = "SELECT * FROM ttreviewid";
        $recs = $db->getResultArray($queryStr);
        echo $db->getErrorMsg();
        var_dump($recs);
*/

        /* A missing reviewer or user causes an error */
        $ret = $content->addGrpReviewer($reviewergrp, null);
        $this->assertEquals(-1, $ret);

        /* A missing reviewer or user causes an error */
        $ret = $content->addGrpReviewer(null, $user);
        $this->assertEquals(-1, $ret);

        /* Adding a user instead of a group causes an error */
        $ret = $content->addGrpReviewer($reviewer, $user);
        $this->assertEquals(-1, $ret);

        /* Finally add the reviewer */
        $ret = $content->addGrpReviewer($reviewergrp, $user);
        $this->assertGreaterThan(0, $ret);
        $groupstatus = $reviewergrp->getReviewStatus();

        /* Adding the group again will yield in an error */
        $ret = $content->addGrpReviewer($reviewergrp, $user);
        $this->assertEquals(-3, $ret);

        /* Get all reviews */
        $reviewstatus = $content->getReviewStatus();
        $this->assertIsArray($reviewstatus);
        $this->assertCount(2, $reviewstatus);

        /* Get list of individual und group reviewers */
        $reviewers = $content->getReviewers();
        $this->assertIsArray($reviewers);
        $this->assertCount(2, $reviewers);
        $this->assertCount(1, $reviewers['i']);
        $this->assertCount(1, $reviewers['g']);

        $userstatus = $reviewer->getReviewStatus();
        $groupstatus = $reviewergrp->getReviewStatus();

        /* There should be two log entries, one for each reviewer */
        $reviewlog = $content->getReviewLog(5);
        $this->assertIsArray($reviewlog);
        $this->assertCount(2, $reviewlog);

        /* Adding a review without a user of reviewer causes an error */
        $ret = $content->setReviewByInd($reviewer, null, S_LOG_ACCEPTED, 'Comment of individual reviewer');
        $this->assertEquals(-1, $ret);
        $ret = $content->setReviewByInd(null, $user, S_LOG_ACCEPTED, 'Comment of individual reviewer');
        $this->assertEquals(-1, $ret);

        /* Adding a review as an individual but passing a group causes an error */
        $ret = $content->setReviewByInd($reviewergrp, $user, S_LOG_ACCEPTED, 'Comment of individual reviewer');
        $this->assertEquals(-1, $ret);

        /* Individual reviewer reviews document */
        $ret = $content->setReviewByInd($reviewer, $user, S_LOG_ACCEPTED, 'Comment of individual reviewer');
        $this->assertIsInt(0, $ret);
        $this->assertGreaterThan(0, $ret);

        /* Get the last 5 review log entries (actually there are just 3 now) */
        $reviewlog = $content->getReviewLog(5);
        $this->assertIsArray($reviewlog);
        $this->assertCount(3, $reviewlog);
        $this->assertEquals('Comment of individual reviewer', $reviewlog[0]['comment']);
        $this->assertEquals(1, $reviewlog[0]['status']);

        /* Needs to call verifyStatus() in order to recalc the status.
         * It must not be changed because the group reviewer has not done the
         * review.
         */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_REV, $newstatus);

        /* Adding a review without a user of reviewer causes an error */
        $ret = $content->setReviewByGrp($reviewergrp, null, S_LOG_ACCEPTED, 'Comment of group reviewer');
        $this->assertEquals(-1, $ret);
        $ret = $content->setReviewByGrp(null, $user, S_LOG_ACCEPTED, 'Comment of group reviewer');
        $this->assertEquals(-1, $ret);

        /* Adding a review as an group but passing a user causes an error */
        $ret = $content->setReviewByGrp($reviewer, $user, S_LOG_ACCEPTED, 'Comment of group reviewer');
        $this->assertEquals(-1, $ret);

        /* Group reviewer reviews document */
        $ret = $content->setReviewByGrp($reviewergrp, $user, S_LOG_ACCEPTED, 'Comment of group reviewer');
        $this->assertIsInt(0, $ret);
        $this->assertGreaterThan(0, $ret);

        /* Get the last 5 review log entries (actually there are just 4 now) */
        $reviewlog = $content->getReviewLog(5);
        $this->assertIsArray($reviewlog);
        $this->assertCount(4, $reviewlog);
        $this->assertEquals('Comment of group reviewer', $reviewlog[0]['comment']);
        $this->assertEquals(1, $reviewlog[0]['status']);

        /* Now the document has received all reviews */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_RELEASED, $newstatus);

        /* Remove the last review of the user */
        $userstatus = $reviewer->getReviewStatus($document->getId(), $content->getVersion());
        $this->assertIsArray($userstatus);
        $this->assertCount(2, $userstatus);
        $this->assertCount(1, $userstatus['indstatus']);
        $ret = $content->removeReview($userstatus['indstatus'][$document->getId()]['reviewID'], $user, 'Undo review');
        $this->assertTrue($ret);

        /* Get the last 8 review log entries (actually there are just 5 now) */
        $reviewlog = $content->getReviewLog(8);
        $this->assertIsArray($reviewlog);
        $this->assertCount(5, $reviewlog);
        $this->assertEquals('Undo review', $reviewlog[0]['comment']);
        $this->assertEquals(0, $reviewlog[0]['status']);

        /* Now the document must be back in draft mode */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_REV, $newstatus);

        /* Removing the user as a reviewer completly will release the
         * document again, because the group reviewer became the only
         * reviewer and has done the review already.
         */
        $ret = $content->delIndReviewer($reviewer, $user, 'Reviewer removed');
        $this->assertIsInt($ret);
        $this->assertEquals(0, $ret);

        /* Get the last 8 review log entries (actually there are just 6 now) */
        $reviewlog = $content->getReviewLog(8);
        $this->assertIsArray($reviewlog);
        $this->assertCount(6, $reviewlog);
        $this->assertEquals('Reviewer removed', $reviewlog[0]['comment']);
        $this->assertEquals(-2, $reviewlog[0]['status']);

        /* Now the document will be released again */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_RELEASED, $newstatus);
    }

    /**
     * Test method addIndApprover(), addGrpApprover(), verifyStatus(),
     * getApprovalStatus(), removeApproval(), delIndApprover()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testApproveDocumentByUserAndGroup()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        /* Add a new group which will be the approver */
        $approvergrp = self::$dms->addGroup('approver', '');
        $this->assertIsObject($approvergrp);

        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $content = $document->getLatestContent();
        $this->assertIsObject($content);
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* A missing approver or user causes an error */
        $ret = $content->addIndApprover($approver, null);
        $this->assertEquals(-1, $ret);

        /* A missing approver or user causes an error */
        $ret = $content->addIndApprover(null, $user);
        $this->assertEquals(-1, $ret);

        /* Adding a group instead of a user causes an error */
        $ret = $content->addIndApprover($approvergrp, $user);
        $this->assertEquals(-1, $ret);

        /* Finally add the reviewer */
        $ret = $content->addIndApprover($approver, $user);
        $this->assertGreaterThan(0, $ret);

        /* Adding the user again will yield in an error */
        $ret = $content->addIndApprover($approver, $user);
        $this->assertEquals(-3, $ret);

        /* Needs to call verifyStatus() in order to recalc the status */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_APP, $newstatus);

        /* Get all approvals */
        $approvalstatus = $content->getApprovalStatus();
        $this->assertIsArray($approvalstatus);
        $this->assertCount(1, $approvalstatus);

        /* Get list of individual und group approvers */
        $approvers = $content->getApprovers();
        $this->assertIsArray($approvers);
        $this->assertCount(2, $approvers);
        $this->assertCount(1, $approvers['i']);
        $this->assertCount(0, $approvers['g']);

        /* A missing approver or user causes an error */
        $ret = $content->addGrpApprover($approvergrp, null);
        $this->assertEquals(-1, $ret);

        /* A missing approver or user causes an error */
        $ret = $content->addGrpApprover(null, $user);
        $this->assertEquals(-1, $ret);

        /* Adding a user instead of a group causes an error */
        $ret = $content->addGrpApprover($approver, $user);
        $this->assertEquals(-1, $ret);

        /* Finally add the reviewer */
        $ret = $content->addGrpApprover($approvergrp, $user);
        $this->assertGreaterThan(0, $ret);
        $groupstatus = $approvergrp->getApprovalStatus();

        /* Adding the group again will yield in an error */
        $ret = $content->addGrpApprover($approvergrp, $user);
        $this->assertEquals(-3, $ret);

        /* Get all approvals */
        $approvalstatus = $content->getApprovalStatus();
        $this->assertIsArray($approvalstatus);
        $this->assertCount(2, $approvalstatus);

        /* Get list of individual und group approvers */
        $approvers = $content->getApprovers();
        $this->assertIsArray($approvers);
        $this->assertCount(2, $approvers);
        $this->assertCount(1, $approvers['i']);
        $this->assertCount(1, $approvers['g']);

        $userstatus = $approver->getApprovalStatus();
        $groupstatus = $approvergrp->getApprovalStatus();

        /* There should be two log entries, one for each approver */
        $approvallog = $content->getApproveLog(5);
        $this->assertIsArray($approvallog);
        $this->assertCount(2, $approvallog);

        /* Adding a approval without a user of approver causes an error */
        $ret = $content->setApprovalByInd($approver, null, S_LOG_ACCEPTED, 'Comment of individual approver');
        $this->assertEquals(-1, $ret);
        $ret = $content->setApprovalByInd(null, $user, S_LOG_ACCEPTED, 'Comment of individual approver');
        $this->assertEquals(-1, $ret);

        /* Adding a approval as an individual but passing a group causes an error */
        $ret = $content->setApprovalByInd($approvergrp, $user, S_LOG_ACCEPTED, 'Comment of individual approver');
        $this->assertEquals(-1, $ret);

        /* Individual approver approvals document */
        $ret = $content->setApprovalByInd($approver, $user, S_LOG_ACCEPTED, 'Comment of individual approver');
        $this->assertIsInt(0, $ret);
        $this->assertGreaterThan(0, $ret);

        /* Get the last 5 approval log entries (actually there are just 3 now) */
        $approvallog = $content->getApproveLog(5);
        $this->assertIsArray($approvallog);
        $this->assertCount(3, $approvallog);
        $this->assertEquals('Comment of individual approver', $approvallog[0]['comment']);
        $this->assertEquals(1, $approvallog[0]['status']);

        /* Needs to call verifyStatus() in order to recalc the status.
         * It must not be changed because the group approver has not done the
         * approval.
         */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_APP, $newstatus);

        /* Adding a approval without a user of approver causes an error */
        $ret = $content->setApprovalByGrp($approvergrp, null, S_LOG_ACCEPTED, 'Comment of group approver');
        $this->assertEquals(-1, $ret);
        $ret = $content->setApprovalByGrp(null, $user, S_LOG_ACCEPTED, 'Comment of group approver');
        $this->assertEquals(-1, $ret);

        /* Adding a approval as an group but passing a user causes an error */
        $ret = $content->setApprovalByGrp($approver, $user, S_LOG_ACCEPTED, 'Comment of group approver');
        $this->assertEquals(-1, $ret);

        /* Group approver approvals document */
        $ret = $content->setApprovalByGrp($approvergrp, $user, S_LOG_ACCEPTED, 'Comment of group approver');
        $this->assertIsInt(0, $ret);
        $this->assertGreaterThan(0, $ret);

        /* Get the last 5 approval log entries (actually there are just 4 now) */
        $approvallog = $content->getApproveLog(5);
        $this->assertIsArray($approvallog);
        $this->assertCount(4, $approvallog);
        $this->assertEquals('Comment of group approver', $approvallog[0]['comment']);
        $this->assertEquals(1, $approvallog[0]['status']);

        /* Now the document has received all approvals */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_RELEASED, $newstatus);

        /* Remove the last approval of the user */
        $userstatus = $approver->getApprovalStatus($document->getId(), $content->getVersion());
        $this->assertIsArray($userstatus);
        $this->assertCount(2, $userstatus);
        $this->assertCount(1, $userstatus['indstatus']);
        $ret = $content->removeApproval($userstatus['indstatus'][$document->getId()]['approveID'], $user, 'Undo approval');
        $this->assertTrue($ret);

        /* Get the last 8 approval log entries (actually there are just 5 now) */
        $approvallog = $content->getApproveLog(8);
        $this->assertIsArray($approvallog);
        $this->assertCount(5, $approvallog);
        $this->assertEquals('Undo approval', $approvallog[0]['comment']);
        $this->assertEquals(0, $approvallog[0]['status']);

        /* Now the document must be back in draft mode */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_DRAFT_APP, $newstatus);

        /* Removing the user as a approver completly will release the
         * document again, because the group approver became the only
         * approver and has done the approval already.
         */
        $ret = $content->delIndApprover($approver, $user, 'Approver removed');
        $this->assertIsInt($ret);
        $this->assertEquals(0, $ret);

        /* Get the last 8 approval log entries (actually there are just 6 now) */
        $approvallog = $content->getApproveLog(8);
        $this->assertIsArray($approvallog);
        $this->assertCount(6, $approvallog);
        $this->assertEquals('Approver removed', $approvallog[0]['comment']);
        $this->assertEquals(-2, $approvallog[0]['status']);

        /* Now the document will be released again */
        $newstatus = $content->verifyStatus(false, $user);
        $this->assertIsInt($newstatus);
        $this->assertEquals(S_RELEASED, $newstatus);
    }
}
