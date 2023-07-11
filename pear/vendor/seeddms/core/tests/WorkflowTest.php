<?php
/**
 * Implementation of the workflow tests
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
class WorkflowTest extends SeedDmsTest
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
        self::$dms = new \SeedDMS_Core_DMS(self::$dbh, self::$contentdir);
        self::$dbversion = self::$dms->getDBVersion();
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
     * Test method getInitState() and setInitState()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetInitState()
    {
        $ws_nr = self::$dms->addWorkflowState('needs review', S_IN_WORKFLOW);
        $ws_na = self::$dms->addWorkflowState('needs approval', S_IN_WORKFLOW);
        $workflow = self::$dms->addWorkflow('traditional workflow', $ws_nr);
        $initstate = $workflow->getInitState();
        $this->assertEquals($ws_nr->getName(), $initstate->getName());
        $ret = $workflow->setInitState($ws_na);
        $this->assertTrue($ret);
        $initstate = $workflow->getInitState();
        $this->assertEquals($ws_na->getName(), $initstate->getName());
    }

    /**
     * Test method getName() and setName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetStateName()
    {
        $state = self::$dms->addWorkflowState('needs review', S_IN_WORKFLOW);
        $name = $state->getName();
        $this->assertEquals('needs review', $name);
        $ret = $state->setName('foobar');
        $this->assertTrue($ret);
        $name = $state->getName();
        $this->assertEquals('foobar', $name);
    }

    /**
     * Test method getName() and setName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetActionName()
    {
        $action = self::$dms->addWorkflowAction('action');
        $name = $action->getName();
        $this->assertEquals('action', $name);
        $ret = $action->setName('foobar');
        $this->assertTrue($ret);
        $name = $action->getName();
        $this->assertEquals('foobar', $name);
    }

    /**
     * Test method getName() and setName()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetWorkflowName()
    {
        $ws_nr = self::$dms->addWorkflowState('needs review', S_IN_WORKFLOW);
        $workflow = self::$dms->addWorkflow('traditional workflow', $ws_nr);
        $name = $workflow->getName();
        $this->assertEquals('traditional workflow', $name);
        $ret = $workflow->setName('foo');
        $this->assertTrue($ret);
        $name = $workflow->getName();
        $this->assertEquals('foo', $name);
    }

    /**
     * Test method getDocumentStatus() and setDocumentStatus()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testGetAndSetDocumentStatus()
    {
        $state = self::$dms->addWorkflowState('some name', S_RELEASED);
        $docstatus = $state->getDocumentStatus();
        $this->assertEquals(S_RELEASED, $docstatus);
        $ret = $state->setDocumentStatus(S_REJECTED);
        $this->assertTrue($ret);
        $docstatus = $state->getDocumentStatus();
        $this->assertEquals(S_REJECTED, $docstatus);
    }

    /**
     * Test method workflow->remove()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCreateAndRemoveWorkflow()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        $workflow = self::createWorkflow($reviewer, $approver);
        $this->assertIsObject($workflow);

        $ret = $workflow->remove();
        $this->assertTrue($ret);

        $states = self::$dms->getAllWorkflowStates();
        $this->assertIsArray($states);
        $this->assertCount(4, $states);
        foreach($states as $state)
          $this->assertFalse($state->isUsed());

        $actions = self::$dms->getAllWorkflowActions();
        $this->assertIsArray($actions);
        $this->assertCount(3, $actions);
        foreach($actions as $action)
          $this->assertFalse($action->isUsed());

    }

    /**
     * Test method remove()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCreateAndRemoveAction()
    {
        $action = self::$dms->addWorkflowAction('action');
        $this->assertIsObject($action);
        $actions = self::$dms->getAllWorkflowActions();
        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
        $ret = $action->remove();
        $this->assertTrue($ret);
        $actions = self::$dms->getAllWorkflowActions();
        $this->assertIsArray($actions);
        $this->assertCount(0, $actions);
    }

    /**
     * Test method remove()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testCreateAndRemoveState()
    {
        $state = self::$dms->addWorkflowState('needs review', S_IN_WORKFLOW);
        $this->assertIsObject($state);
        $states = self::$dms->getAllWorkflowStates();
        $this->assertIsArray($states);
        $this->assertCount(1, $states);
        $ret = $state->remove();
        $this->assertTrue($ret);
        $states = self::$dms->getAllWorkflowStates();
        $this->assertIsArray($states);
        $this->assertCount(0, $states);
    }

    /**
     * Test method setWorkflow(), getWorkflow(), getWorkflowState()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testAssignWorkflow()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        $workflow = self::createWorkflow($reviewer, $approver);
        $this->assertIsObject($workflow);

        /* Check for cycles */
        $cycles = $workflow->checkForCycles();
        $this->assertFalse($cycles);

        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $content = $document->getLatestContent();
        $this->assertIsObject($content);
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* Assign the workflow */
        $ret = $content->setWorkflow($workflow, $user);
        $this->assertTrue($ret);

        /* Assign a workflow again causes an error */
        $ret = $content->setWorkflow($workflow, $user);
        $this->assertFalse($ret);

        /* Get a fresh copy of the content from the database and get the workflow */
        $again = self::$dms->getDocumentContent($content->getId());
        $this->assertIsObject($again);
        $w = $again->getWorkflow();
        $this->assertEquals($workflow->getId(), $w->getId());

        /* Status of content should be S_IN_WORKFLOW now */
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_IN_WORKFLOW, $status['status']);

        /* Get current workflow state */
        $state = $content->getWorkflowState();
        $this->assertEquals('needs review', $state->getName());

        $workflowlog = $content->getWorkflowLog();
        $this->assertIsArray($workflowlog);
        $this->assertCount(0, $workflowlog);

        /* The workflow has altogether 4 states */
        $states = $workflow->getStates();
        $this->assertIsArray($states);
        $this->assertCount(4, $states);

        /* Check the initial state */
        $initstate = $workflow->getInitState();
        $this->assertEquals('needs review', $initstate->getName());

        /* init state is definitely used */
        $ret = $initstate->isUsed();
        $this->assertTrue($ret);

        /* init state has two transistions linked to it */
        $transitions = $initstate->getTransitions();
        $this->assertIsArray($transitions);
        $this->assertCount(2, $transitions);

        /* Check if workflow is used by any document */
        $isused = $workflow->isUsed();
        $this->assertTrue($isused);

    }

    /**
     * Test method setWorkflow(), getWorkflow(), getWorkflowState()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testStepThroughWorkflow()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        $workflow = self::createWorkflow($reviewer, $approver);

        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $content = $document->getLatestContent();
        $this->assertIsObject($content);
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* Assign the workflow */
        $ret = $content->setWorkflow($workflow, $user);
        $this->assertTrue($ret);

        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_IN_WORKFLOW, $status['status']);

        /* Remove the workflow */
        $ret = $content->removeWorkflow($user);
        $this->assertTrue($ret);

        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* Remove the workflow again is just fine */
        $ret = $content->removeWorkflow($user);
        $this->assertTrue($ret);

        /* Assign the workflow again */
        $ret = $content->setWorkflow($workflow, $user);
        $this->assertTrue($ret);

        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_IN_WORKFLOW, $status['status']);


        /* Check if workflow needs action by the reviewer/approver */
        $ret = $content->needsWorkflowAction($reviewer);
        $this->assertTrue($ret);
        $ret = $content->needsWorkflowAction($approver);
        $this->assertFalse($ret);

        /* Get current workflow state*/
        $state = $content->getWorkflowState();
        $this->assertEquals('needs review', $state->getName());

        /* There should be two possible transitions now
         * NR -- review -> NA
         * NR -- reject -> RJ
         */
        $nexttransitions = $workflow->getNextTransitions($state);
        $this->assertIsArray($nexttransitions);
        $this->assertCount(2, $nexttransitions);

        /* But of course, there were no previous transitions */
        $prevtransitions = $workflow->getPreviousTransitions($state);
        $this->assertIsArray($prevtransitions);
        $this->assertCount(0, $prevtransitions);

        /* Check if reviewer is allowed to trigger the transition.
         * As we are still in the intitial state, the possible transitions
         * may both be triggered by the reviewer but not by the approver.
         */
        foreach($nexttransitions as $nexttransition) {
            if($nexttransition->getNextState()->getDocumentStatus() == S_REJECTED)
                $rejecttransition = $nexttransition;
            elseif($nexttransition->getNextState()->getDocumentStatus() == S_IN_WORKFLOW)
                $reviewtransition = $nexttransition;
            $ret = $content->triggerWorkflowTransitionIsAllowed($reviewer, $nexttransition);
            $this->assertTrue($ret);
            $ret = $content->triggerWorkflowTransitionIsAllowed($approver, $nexttransition);
            $this->assertFalse($ret);
        }

        /* Trigger the successful review transition.
         * As there is only one reviewer the transition will fire and the workflow
         * moves forward into the next state. triggerWorkflowTransition() returns the
         * next state.
         */
        $nextstate = $content->triggerWorkflowTransition($reviewer, $reviewtransition, 'Review succeeded');
        $this->assertIsObject($nextstate);
        $this->assertEquals('needs approval', $nextstate->getName());

        $state = $content->getWorkflowState();
        $this->assertEquals($nextstate->getId(), $state->getId());
        $this->assertEquals('needs approval', $state->getName());

        /* The workflow log has one entry now */
        $workflowlog = $content->getLastWorkflowLog();
        $this->assertIsObject($workflowlog);
        $this->assertEquals('Review succeeded', $workflowlog->getComment());

        /* There should be two possible transitions now
         * NA -- approve -> RL
         * NA -- reject -> RJ
         */
        $nexttransitions = $workflow->getNextTransitions($state);
        $this->assertIsArray($nexttransitions);
        $this->assertCount(2, $nexttransitions);

        /* But of course, there is one previous transitions, the one that led to
         * the current state of the workflow.
         */
        $prevtransitions = $workflow->getPreviousTransitions($state);
        $this->assertIsArray($prevtransitions);
        $this->assertCount(1, $prevtransitions);
        $this->assertEquals($reviewtransition->getId(), $prevtransitions[0]->getId());

        /* Check if approver is allowed to trigger the transition.
         * As we are now in 'needs approval' state, the possible transitions
         * may both be triggered by the approver but not by the reviewer.
         */
        foreach($nexttransitions as $nexttransition) {
            if($nexttransition->getNextState()->getDocumentStatus() == S_REJECTED)
                $rejecttransition = $nexttransition;
            elseif($nexttransition->getNextState()->getDocumentStatus() == S_RELEASED)
                $releasetransition = $nexttransition;
            $ret = $content->triggerWorkflowTransitionIsAllowed($approver, $nexttransition);
            $this->assertTrue($ret);
            $ret = $content->triggerWorkflowTransitionIsAllowed($reviewer, $nexttransition);
            $this->assertFalse($ret);
        }

        /* Trigger the successful approve transition.
         * As there is only one approver the transition will fire and the workflow
         * moves forward into the next state. triggerWorkflowTransition() returns the
         * next state.
         */
        $nextstate = $content->triggerWorkflowTransition($approver, $releasetransition, 'Approval succeeded');
        $this->assertIsObject($nextstate);
        $this->assertEquals('released', $nextstate->getName());

        /* The workflow log has two entries now */
        $workflowlog = $content->getLastWorkflowLog();
        $this->assertIsObject($workflowlog);
        $this->assertEquals('Approval succeeded', $workflowlog->getComment());

        /* Because the workflow has reached a final state, the workflow will no
         * longer be attached to the document.
         */
        $workflow = $content->getWorkflow();
        $this->assertFalse($workflow);

        /* There is also no way to get the state anymore */
        $state = $content->getWorkflowState();
        $this->assertFalse($state);

        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* Even after the workflow has been finished the log can still be retrieved */
        $workflowlog = $content->getLastWorkflowLog();
        $this->assertIsObject($workflowlog);
        $this->assertEquals('Approval succeeded', $workflowlog->getComment());
    }

    /**
     * Test method rewindWorkflow()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testRewindWorkflow()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        $workflow = self::createWorkflow($reviewer, $approver);

        /* Add a new document */
        $document = self::createDocument($rootfolder, $user, 'Document 1');
        $content = $document->getLatestContent();
        $this->assertIsObject($content);
        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_RELEASED, $status['status']);

        /* Assign the workflow */
        $ret = $content->setWorkflow($workflow, $user);
        $this->assertTrue($ret);

        $status = $content->getStatus();
        $this->assertIsArray($status);
        $this->assertEquals(S_IN_WORKFLOW, $status['status']);

        /* Check if workflow needs action by the reviewer */
        $ret = $content->needsWorkflowAction($reviewer);
        $this->assertTrue($ret);

        /* Get current workflow state*/
        $state = $content->getWorkflowState();
        $this->assertEquals('needs review', $state->getName());

        /* There should be two possible transitions now
         * NR -- review -> NA
         * NR -- reject -> RJ
         */
        $nexttransitions = $workflow->getNextTransitions($state);
        $this->assertIsArray($nexttransitions);
        $this->assertCount(2, $nexttransitions);

        /* Check if reviewer is allowed to trigger the transition.
         * As we are still in the intitial state, the possible transitions
         * may both be triggered by the reviewer but not by the approver.
         */
        foreach($nexttransitions as $nexttransition) {
            if($nexttransition->getNextState()->getDocumentStatus() == S_IN_WORKFLOW)
                $reviewtransition = $nexttransition;
        }

        /* Trigger the successful review transition.
         * As there is only one reviewer the transition will fire and the workflow
         * moves forward into the next state. triggerWorkflowTransition() returns the
         * next state.
         */
        $nextstate = $content->triggerWorkflowTransition($reviewer, $reviewtransition, 'Review succeeded');
        $this->assertIsObject($nextstate);
        $this->assertEquals('needs approval', $nextstate->getName());

        /* Get current workflow state*/
        $state = $content->getWorkflowState();
        $this->assertEquals('needs approval', $state->getName());

        /* The workflow log has one entry now */
        $workflowlogs = $content->getWorkflowLog();
        $this->assertIsArray($workflowlogs);
        $this->assertCount(1, $workflowlogs);
        if(self::$dbversion['major'] > 5)
            $this->assertEquals('Review succeeded', $workflowlogs[1][0]->getComment());
        else
            $this->assertEquals('Review succeeded', $workflowlogs[0]->getComment());

        $ret = $content->rewindWorkflow();
        $this->assertTrue($ret);

        /* After rewinding the workflow the initial state is set ... */
        $state = $content->getWorkflowState();
        $this->assertEquals('needs review', $state->getName());

        /* and the workflow log has been cleared */
        $workflowlogs = $content->getWorkflowLog();
        $this->assertIsArray($workflowlogs);
        $this->assertCount(0, $workflowlogs);
    }

    /**
     * Test method getTransitionsByStates()
     *
     * This method uses a real in memory sqlite3 database.
     *
     * @return void
     */
    public function testTransitionsByStateWorkflow()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        $this->assertIsObject($user);

        /* Add a new user who will be the reviewer */
        $reviewer = self::$dms->addUser('reviewer', 'reviewer', 'Reviewer One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($reviewer);

        /* Add a new user who will be the approver */
        $approver = self::$dms->addUser('approver', 'approver', 'Approver One', 'user1@seeddms.org', 'en_GB', 'bootstrap', '');
        $this->assertIsObject($approver);

        $workflow = self::createWorkflow($reviewer, $approver);

        /* Check the initial state */
        $initstate = $workflow->getInitState();
        $this->assertEquals('needs review', $initstate->getName());

        /* init state has two transistions linked to it */
        $transitions = $initstate->getTransitions();
        $this->assertIsArray($transitions);
        $this->assertCount(2, $transitions);

        $t = $workflow->getTransitionsByStates($initstate, $transitions[1]->getNextState());
        $this->assertEquals($transitions[1]->getId(), $t[0]->getId());
    }

}
