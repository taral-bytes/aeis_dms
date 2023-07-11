<?php declare(strict_types=1);
/**
 * Implementation of the database tests
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

namespace PHPUnit\Framework;

use PHPUnit\Framework\TestCase;

/**
 * Database test class
 *
 * @category  SeedDMS
 * @package   Tests
 * @author    Uwe Steinmann <uwe@steinmann.cx>
 * @copyright 2021 Uwe Steinmann
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: @package_version@
 * @link      https://www.seeddms.org
 */
class SeedDmsTest extends TestCase
{

    public static $dbh;

    public static $dms;

    public static $contentdir;

    public static $dbversion;

    /**
     * Create a sqlite database in memory
     *
     * @return void
     */
    public static function createInMemoryDatabase(): object
    {
        $dbh = new \SeedDMS_Core_DatabaseAccess('sqlite', '', '', '', ':memory:');
        $dbh->connect();
        $queries = file_get_contents(getenv("SEEDDMS_CORE_SQL"));
        // generate SQL query
        $queries = explode(";", $queries);

        // execute queries
        $errorMsg = '';
        foreach ($queries as $query) {
            //echo $query;
            $query = trim($query);
            if (!empty($query)) {
                $dbh->getResult($query);

                if ($dbh->getErrorNo() != 0) {
                    //echo $dbh->getErrorMsg()."\n";
                    $errorMsg .= $dbh->getErrorMsg()."\n";
                }
            }
        }
        return $dbh;
    }

    /**
     * Create a mocked root folder object
     *
     * @return \SeedDMS_Core_Folder
     */
    protected function getMockedRootFolder($id=1, $name='DMS')
    {
        $folder = new \SeedDMS_Core_Folder($id, $name, 0, 'DMS root', time(), 1, 0, 0, 0.0);
        return $folder;
    }

    /**
     * Create a mocked document object
     *
     * @return \SeedDMS_Core_Document
     */
    protected function getMockedDocument($id=1, $name='Document')
    {
        $document = new \SeedDMS_Core_Document($id, $name, '', time(), null, 1, 1, 1, M_READ, 0, '', 1.0);
        return $document;
    }

    /**
     * Create a mocked user object
     *
     * @return \SeedDMS_Core_User
     */
    protected function getMockedUser()
    {
        $user = new \SeedDMS_Core_User(1, 'login', '', 'New User', 'email@seeddms.org', 'de_DE', 'bootstrap', '', null);
        return $user;
    }

    /**
     * Create a temporary file with random content and the given length.
     *
     * @param integer $length length of file
     *
     * @return string name of temporary file
     */
    protected static function createTempFile($length=200, $dir='')
    {
        if($tmpfname = @tempnam($dir ? $dir : sys_get_temp_dir(), 'foo')) {
            file_put_contents($tmpfname, substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', (int) ceil($length/strlen($x)) )),1,$length));
            return $tmpfname;
        } else
            return false;
    }

    /**
     * Create a temporary directory with random name in systems temp dir.
     *
     * @param integer $mode access mode of new directory
     *
     * @return string name of temporary directory
     */
    protected static function createTempDir(string $dir = null, int $mode = 0700): string {
        /* Use the system temp dir by default. */
        if (is_null($dir)) {
            $dir = sys_get_temp_dir();
        }

        do { $tmp = $dir . '/' . mt_rand(); }
        while (!@mkdir($tmp, $mode));
        return $tmp;
    }

    /**
     * Create a simple document.
     *
     * @param \SeedDMS_Core_Folder $parent parent folder
     * @param \SeedDMS_Core_User $owner owner of document
     * @param string $name name of document
     * @param integer $length length of file
     *
     * @return string name of temporary file
     */
    protected static function createDocument($parent, $owner, $name, $length=200)
    {
        $filename = self::createTempFile($length);
        list($document, $res) = $parent->addDocument(
            $name, // name
            '', // comment
            null, // no expiration
            $owner, // owner
            '', // keywords
            [], // categories
            $filename, // name of file
            'file1.txt', // original file name
            '.txt', // file type
            'text/plain', // mime type
            1.0 // sequence
        );
        unlink($filename);
        return $document;
    }

    /**
     * Create a simple folder structure without documents
     *
     * DMS root -+- Subfolder 1 -+- Subsubfolder 1 -+- Subsubsubfolder 1
     *           |
     *           +- Subfolder 2
     *           |
     *           +- Subfolder 3
     *
     * The sequence field of Subfolder x is:
     * Subfolder 1: 2.0
     * Subfolder 2: 1.0
     * Subfolder 1: 0.5
     *
     * @return void
     */
    protected static function createSimpleFolderStructure()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        /* Set up a folder structure */
        $subfolder = $rootfolder->addSubFolder('Subfolder 1', '', $user, 2.0);
        $subsubfolder = $subfolder->addSubFolder('Subsubfolder 1', '', $user, 1.0);
        $subsubsubfolder = $subsubfolder->addSubFolder('Subsubsubfolder 1', '', $user, 1.0);
        $rootfolder->addSubFolder('Subfolder 2', '', $user, 1.0);
        $rootfolder->addSubFolder('Subfolder 3', '', $user, 0.5);
    }

    /**
     * Create a simple folder structure with documents
     *
     * Creates the same folder structure like createSimpleFolderStructure()
     * but adds 30 documents to 'Subfolder 1'. They are named 'Document 1'
     * to 'Document 30'.
     *
     * @return void
     */
    protected static function createSimpleFolderStructureWithDocuments()
    {
        $rootfolder = self::$dms->getRootFolder();
        $user = self::$dms->getUser(1);
        self::createSimpleFolderStructure();
        /* Add documents to 'Subfolder 1' */
        $subfolder = self::$dms->getFolderByName('Subfolder 1');
        for ($i=1; $i<=15; $i++) {
            $filename = self::createTempFile(200);
            list($document, $res) = $subfolder->addDocument(
                'Document 1-'.$i, // name
                '', // comment
                null,
                $user, // owner
                '', // keywords
                [], // categories
                $filename, // name of file
                'file-1-'.$i.'.txt', // original file name
                '.txt', // file type
                'text/plain', // mime type
                1.0+$i // sequence
            );
            unlink($filename);
        }
        /* Add documents to 'Subfolder 2' */
        $subfolder = self::$dms->getFolderByName('Subfolder 2');
        for ($i=1; $i<=15; $i++) {
            $filename = self::createTempFile(200);
            list($document, $res) = $subfolder->addDocument(
                'Document 2-'.$i, // name
                '', // comment
                null,
                $user, // owner
                '', // keywords
                [], // categories
                $filename, // name of file
                'file-2-'.$i.'.txt', // original file name
                '.txt', // file type
                'text/plain', // mime type
                1.0+$i // sequence
            );
            unlink($filename);
        }
    }

    /**
     * Create two groups with 3 users each
     * The groups are named 'Group 1' and 'Group 2'. The users in Group 1
     * are named 'User-1-1', 'User-1-2', 'User-1-3'. The users in Group 2
     * are named 'User-2-1', 'User-2-2', 'User-2-3'.
     * The login name is the lower case of the name.
     *
     * @return void
     */
    protected static function createGroupsAndUsers()
    {
        for($i=1; $i<=2; $i++) {
            $group = self::$dms->addGroup('Group '.$i, '');
            for($j=1; $j<=3; $j++) {
                $user = self::$dms->addUser('user-'.$i.'-'.$j, '', 'User '.$j.' in group '.$i, 'user@seeddms.org', 'en_GB', 'bootstrap', '');
                $user->joinGroup($group);
            }
        }
    }

    /**
     * Creates a workflow with two transitions identical to the traditional
     * workflow
     *
     * NR --- review --> NA -+- approve --> RL
     *     +- reject --> RJ  |
     *                       +- reject ---> RJ
     *
     * States:
     * NR = needs review
     * NA = needs approval
     * RL = released
     * RJ = rejected
     *
     * Actions:
     * review
     * approve
     * reject
     *
     * Transitions:
     * NR -- review -> NA   maybe done by reviewer
     * NR -- reject -> RJ   maybe done by reviewer
     * NA -- approve -> RL  maybe done by approver
     * NA -- reject -> RJ   maybe done by approver
     */
    protected function createWorkflow(\SeedDMS_Core_User $reviewer, \SeedDMS_Core_User $approver): \SeedDMS_Core_Workflow
    {
        /* Create workflow states */
        $ws_nr = self::$dms->addWorkflowState('needs review', S_IN_WORKFLOW);
        $ws_na = self::$dms->addWorkflowState('needs approval', S_IN_WORKFLOW);
        $ws_rl = self::$dms->addWorkflowState('released', S_RELEASED);
        $ws_rj = self::$dms->addWorkflowState('rejected', S_REJECTED);

        /* Create workflow actions */
        $wa_rv = self::$dms->addWorkflowAction('review', S_IN_WORKFLOW);
        $wa_rj = self::$dms->addWorkflowAction('reject', S_REJECTED);
        $wa_ap = self::$dms->addWorkflowAction('approve', S_RELEASED);

        /* Create a workflow which starts in state 'needs review' */
        $workflow = self::$dms->addWorkflow('traditional workflow', $ws_nr);
        /* Add transition NR -- review -> NA  */
        $wt_nr_na = $workflow->addTransition($ws_nr, $wa_rv, $ws_na, [$reviewer], []);
        /* Add transition NR -- review -> RJ  */
        $wt_nr_rj = $workflow->addTransition($ws_nr, $wa_rj, $ws_rj, [$reviewer], []);
        /* Add transition NA -- approve -> RL  */
        $wt_na_rl = $workflow->addTransition($ws_na, $wa_ap, $ws_rl, [$approver], []);
        /* Add transition NA -- reject -> RJ  */
        $wt_na_rj = $workflow->addTransition($ws_na, $wa_rj, $ws_rj, [$approver], []);

        return $workflow;
    }

    /**
     * Creates a workflow with one transitions for approving a document
     *
     * NA -+- approve --> RL
     *     |
     *     +- reject ---> RJ
     *
     * States:
     * NA = needs approval
     * RL = released
     * RJ = rejected
     *
     * Actions:
     * approve
     * reject
     *
     * Transitions:
     * NA -- approve -> RL  maybe done by approver
     * NA -- reject -> RJ   maybe done by approver
     */
    protected function createSimpleWorkflow(\SeedDMS_Core_User $approver): \SeedDMS_Core_Workflow
    {
        /* Create workflow states */
        $ws_na = self::$dms->addWorkflowState('simple needs approval', S_IN_WORKFLOW);
        $ws_rl = self::$dms->addWorkflowState('simple released', S_RELEASED);
        $ws_rj = self::$dms->addWorkflowState('simple rejected', S_REJECTED);

        /* Create workflow actions */
        $wa_rj = self::$dms->addWorkflowAction('simple reject', S_REJECTED);
        $wa_ap = self::$dms->addWorkflowAction('simple approve', S_RELEASED);

        /* Create a workflow which starts in state 'needs approval' */
        $workflow = self::$dms->addWorkflow('simple workflow', $ws_na);
        /* Add transition NA -- approve -> RL  */
        $wt_na_rl = $workflow->addTransition($ws_na, $wa_ap, $ws_rl, [$approver], []);
        /* Add transition NA -- reject -> RJ  */
        $wt_na_rj = $workflow->addTransition($ws_na, $wa_rj, $ws_rj, [$approver], []);

        return $workflow;
    }

}

