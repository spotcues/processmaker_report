<?php

namespace ProcessMaker\Services\Api;

if (file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/phpexcel/vendor/autoload.php")) {
    require_once dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/phpexcel/vendor/autoload.php";
}

use AppDelegation;
use Exception;
use G;
use Luracast\Restler\RestException;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Settings;
use ProcessMaker\BusinessModel\Cases as BusinessModelCases;
use ProcessMaker\BusinessModel\Process;
use ProcessMaker\BusinessModel\Task;
use ProcessMaker\BusinessModel\Validator;
use ProcessMaker\Core\System;
use ProcessMaker\Services\Api;
use ProcessMaker\Util\DateTime;
use RBAC;
use stdclass;

use ProcessMaker\Util\Logger as EsDataSyncLogger;

/**
 *
 * Process Api Controller
 *
 * @protected
 */
class Elastic extends Api
{

    private $esDataSyncLogger = null;
    private $regexNull         = '/^null$/i';
    private $arrayFieldIso8601 = [
        // request lists
        'newerThan',
        'oldestthan',
        //return lists
        'date',
        'delegateDate',
        'dueDate',
        'delRiskDate',

        'createDate',
        'updateDate',
        'finishDate',
        'initDate',

        'APP_CREATE_DATE',
        'APP_INIT_DATE',
        'APP_FINISH_DATE',
        'APP_UPDATE_DATE',

        'app_create_date',
        'app_init_date',
        'app_finish_date',
        'app_update_date',

        'DEL_INIT_DATE',
        'DEL_FINISH_DATE',
        'DEL_TASK_DUE_DATE',
        'DEL_RISK_DATE',

        'del_init_date',
        'del_finish_date',
        'del_task_due_date',
        'del_risk_date',
        'date_from',
        'date_to',
        'dateFrom',
        'dateTo',
        'due_by',
        'create_date',
        'completed_on',
        'start_date',
        'taskDeadline',
        "del_delegate_date",
        "note_date"
    ];

    private $process_status = [
        [
            'process_status_label' => 'In Progress',
            'process_status_value' => 'to_do',
        ],
        [
            'process_status_label' => 'Completed',
            'process_status_value' => 'completed',
        ],
        [
            'process_status_label' => 'Open',
            'process_status_value' => 'unassigned',
        ],
    ];

    private $process_completion = [
        [
            'process_completion_label' => 'On Time',
            'process_completion_value' => 'ontime',
            'process_status_label'     => 'Completed',
            'process_status_value'     => 'completed',
        ],
        [
            'process_completion_label' => 'Delayed',
            'process_completion_value' => 'delayed',
            'process_status_label'     => 'Completed',
            'process_status_value'     => 'completed',
        ],
        [
            'process_completion_label' => 'On Track',
            'process_completion_value' => 'ontrack',
            'process_status_label'     => 'In Progress',
            'process_status_value'     => 'to_do',
        ],
        [
            'process_completion_label' => 'Overdue',
            'process_completion_value' => 'overdue',
            'process_status_label'     => 'In Progress',
            'process_status_value'     => 'to_do',
        ],
    ];

    private $user_details_keys = [
        'APP_UID',
        'PRO_UID',
        'case_id',
        'assignee',
        'assignee_user_id',
        'user_start_date',
        'user_completed_on',
        'user_status',
        'user_completion',
    ];

    private $case_summary_keys = [
        'APP_UID',
        'PRO_UID',
        'case_id',
        'case_title',
        'assignee_count',
        'user_completed_count',
        'due_by',
        'completed_on',
    ];

    private $system_process_keys = [
        'SYS_SYS',
        'APPLICATION',
        'PROCESS',
        'TASK',
        'USER_LOGGED',
        'USR_USERNAME',
        'APP_NUMBER',
        'FirstUser',
        'SYS_LANG',
        'SYS_SKIN',
        'INDEX',
        'PIN',
        '__VAR_CHANGED__',
        'SYS_VAR_UPDATE_DATE',
        '__ERROR__'
    ];

    private $cases = null;

    public function __isAllowed()
    {
        try {
            $methodName = $this->restler->apiMethodInfo->methodName;
            $arrayArgs  = $this->restler->apiMethodInfo->arguments;
            switch ($methodName) {
                case 'doIfAlreadyRoute':
                    $applicationUid = $this->parameters[$arrayArgs['app_uid']];
                    $delIndex       = $this->parameters[$arrayArgs['cas_index']];
                    $userUid        = $this->getUserId();
                    //Check if the user has the case
                    $appDelegation = new AppDelegation();
                    $aCurUser      = $appDelegation->getCurrentUsers($applicationUid, $delIndex);
                    if (!empty($aCurUser) && in_array($userUid, $aCurUser)) {
                        return true;
                    }

                    return false;
                    break;
            }

            return false;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     * Constructor of the class
     *
     * return void
     */
    public function __construct()
    {
        try {
            /**
             * The variable $RBAC can be defined as follows:
             *
             * $RBAC = new stdclass();
             * $RBAC->aUserInfo['USER_INFO'] = ["USR_UID" => $this->getUserId()];
             *
             * Please consider removing the use of this variable in model class,
             * or perform a corresponding improvement.
             */
            global $RBAC;
            if (!isset($RBAC)) {

                $RBAC          = RBAC::getSingleton(PATH_DATA, session_id());
                $RBAC->sSystem = 'PROCESSMAKER';
                $RBAC->initRBAC();
                $RBAC->loadUserRolePermission($RBAC->sSystem, $this->getUserId());
            }
            if($this->cases === null)
                $this->cases = new BusinessModelCases();
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    public function formatDateTimeToUtc($data, array $arrayKey = [], $format = 'Y-m-d H:i:s')
    {
        try {

            foreach ($data as $key => &$value) {
                if (in_array($key, $arrayKey)) {
                    if (!empty($value)) {
                        $dt    = new \DateTime($value);
                        $value = $dt->format($format);
                    } else {
                        $value = null;
                    }
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function convertTimeZone($data, array $arrayKey = [], $format = 'Y-m-d H:i:s')
    {
        try {

            foreach ($data as $key => &$value) {
                if (in_array($key, $arrayKey)) {
                    if (!empty($value)) {
                        $dt = new \DateTime($value, new \DateTimeZone('UTC'));
                        $dt->setTimeZone(new \DateTimeZone('Asia/Calcutta'));
                        $value = $dt->format($format);
                    } else {
                        $value = null;
                    }
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sanitizeString($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        return preg_replace('/-+/', '-', $string);
    }

    /**
     * PHPTraceEx() - provide a Java style exception trace
     * @param $exception
     * @param $seen      - array passed to recursive calls to accumulate trace lines already seen
     *                     leave as NULL when calling this function
     * @return array of strings, one entry per trace line
     */
    public function PHPTraceEx($e, $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result  = array();
        if (!$seen) {
            $seen = array();
        }

        $trace    = $e->getTrace();
        $prev     = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file     = $e->getFile();
        $line     = $e->getLine();
        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace) + 1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line);
            if (is_array($seen)) {
                $seen[] = "$file:$line";
            }

            if (!count($trace)) {
                break;
            }

            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join("\n", $result);
        if ($prev) {
            $result .= "\n" . PHPTraceEx($prev, $seen);
        }

        return $result;
    }

    public function formatResponse($data = [], $keys = [])
    {
        if (count($data) > 0 && count($keys) > 0) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $keys)) {
                    unset($data[$key]);
                }

            }
        }
        return $data;
    }


    public function getCaseDetailsVersion2ForESSync(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $assignee_list = '',
        $case_id = '',
        $mysql_connection = ''
    ) {
        try {
            $dataList['start']        = $start;
            $dataList['limit']        = $limit;
            $dataList['dateFrom']     = $date_from;
            $dataList['dateTo']       = $date_to;
            $dataList['search']       = trim($search);
            $dataList['status']       = $status;
            $dataList['completion']   = $completion;
            $dataList['current_step'] = $current_step;
            $dataList['mysql_connection'] = $mysql_connection;

            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['process']                       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']                      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list']                 = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']                          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',',$task))) . "'") : null;
            $dataList['case_id']          = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;

            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, $this->arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, $this->arrayFieldIso8601);


            $response = $this->cases->getCaseDetailsVersion2ForESSync($dataList);

            foreach ($response['case_details'] as $key => &$value) {
                if (isset($value['submitted_by'])) {
                    $value['submitted_by'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['submitted_by']));
                }
                if (isset($value['assignee'])) {
                    $value['assignee'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['assignee']));
                }
                if (isset($value['initiatorName'])) {
                    $value['initiatorName'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['initiatorName']));
                }
                if (isset($value['assignedUsers'])) {
                    $value['assignedUsers'] = array_filter(explode(',', $value['assignedUsers']));
                }
                if (isset($value['completion'])) {
                    foreach ($this->process_completion as $item) {
                        if ($item['process_completion_value'] == $value['completion']) {
                            $value['completion'] = $item['process_completion_label'];
                        }

                    }
                }
            }

            if (empty($owner_id) && (count($response['case_details']) > 0) && $is_custom_attributes_honoured) {
                $response['case_details']            = [];
                $response['case_details_total_rows'] = 0;
            }

            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }


    public function getFormDetailsVersion2ForESSync(
        $start = 0,
        $limit = 10,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = false,
        $case_id = '',
        $assignee_list = '',
        $mysql_connection = ''
    ) {

        try {
            $dataList['start']                         = $start;
            $dataList['limit']                         = $limit;
            $dataList['dateFrom']                      = $date_from;
            $dataList['dateTo']                        = $date_to;
            $dataList['search']                        = trim($search);
            $dataList['status']                        = $status;
            $dataList['completion']                    = $completion;
            $dataList['is_custom_attributes_honoured'] = $is_custom_attributes_honoured;
            $dataList['current_step']                  = $current_step;
            $dataList['mysql_connection']                  = $mysql_connection;

            $dataList['remove_label']                  = true;

            $dataList['process']       = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;
            $dataList['owner_id']      = !empty($owner_id) ? ("'" . implode("', '", array_filter(explode(',', $owner_id))) . "'") : null;
            $dataList['assignee_list'] = !empty($assignee_list) ? ("'" . implode("', '", array_filter(explode(',', $assignee_list))) . "'") : null;
            $dataList['task']          = !empty($task) ? ("'" . implode("', '", array_filter(explode(',', $task))) . "'") : null;
            $dataList['case_id']          = !empty($case_id) ? ("'" . implode("', '", array_filter(explode(',', $case_id))) . "'") : null;

            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, $this->arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, $this->arrayFieldIso8601);


            $response = $this->cases->getFormDetailsVersion2ForESSync($dataList);

            if (isset($response['form_details']) && count($response['form_details']) > 0) {
                foreach ($response['form_details'] as $key => &$value) {
                    if (isset($value['submitted_by'])) {
                        $value['submitted_by'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['submitted_by']));
                    }
                    if (isset($value['owner'])) {
                        $value['owner'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['owner']));
                    }
                    if (isset($value['assignee'])) {
                        $value['assignee'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['assignee']));
                    }
                    if (isset($value['assignee_user_id'])) {
                        $value['assignee_user_id'] = array_filter(explode(',', $value['assignee_user_id']));
                    }
                    if (isset($value['completion'])) {
                        foreach ($this->process_completion as $item) {
                            if ($item['process_completion_value'] == $value['completion']) {
                                $value['completion'] = $item['process_completion_label'];
                            }

                        }
                    }
                }
            } else {
                $response['form_details']            = [];
                $response['form_details_total_rows'] = 0;
            }

            if (empty($owner_id) && (count($response['form_details']) > 0) && $is_custom_attributes_honoured) {
                $response['form_details']            = [];
                $response['form_details_total_rows'] = 0;
            }

            return $response;
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    /**
     * Send data to elastic
     * @return boolean
     *
     *
     * @url GET /syncdatatoes
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function syncdatatoes(
        $start = '',
        $limit = 18446744073709551615,
        $date_from = '',
        $date_to = '',
        $process = '',
        $search = '',
        $task = '',
        $owner_id = '',
        $status = '',
        $completion = '',
        $current_step = '',
        $is_custom_attributes_honoured = '',
        $assignee_list = '',
        $case_id = '',
        $workspace_id = ''
    )
    {
        $lock_file = PATH_TRUNK.'syncdatatoes.pid';
        if (! file_exists($lock_file)) {
            if (! touch($lock_file)) {
                error_log("syncdatatoes lock file file can't be created!");
            }
            chmod($lock_file, 0777);
        }
        $lock_file = fopen($lock_file, 'c');
        $got_lock = flock($lock_file, LOCK_EX | LOCK_NB, $wouldblock);
        if ($lock_file === false || (!$got_lock && !$wouldblock)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 
                "Unexpected error opening or locking lock file. Perhaps you " .
                "don't  have permission to write to the lock file or its " .
                "containing directory?");
        }
        else if (!$got_lock && $wouldblock) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 
                "Another instance is already running; terminating");
            exit(0);
        }
        // Lock acquired; let's write our PID to the lock file for the convenience
        // of humans who may wish to terminate the script.
        ftruncate($lock_file, 0);
        fwrite($lock_file, getmypid() . "\n");

        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', 60);

        $logInfo = '';
        $logFile = PATH_TRUNK. 'esdatasync.log';
        $this->esDataSyncLogger = new EsDataSyncLogger($logFile);
        $this->esDataSyncLogger->clog($this->esDataSyncLogger, "Initiating elastic data sync".PHP_EOL);

        if (!empty($task) && empty($process)) {
            throw new RestException(Api::STAT_APP_EXCEPTION, 'process cannot be empty');
        }
        $filterTask = $task;
        if (file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/autoload.php")) {
            require_once (dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/autoload.php");
            require_once (dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . "/thirdparty/mongo_php_library/vendor/mongodb/mongodb/src/functions.php");
        }
        try {
            $sysConfig = System::getSystemConfiguration();
            $sysConfig['DB_SLAVE_HOST'] = isset($sysConfig['DB_SLAVE_HOST']) ? $sysConfig['DB_SLAVE_HOST'] : 'localhost';
            $sysConfig['DB_SLAVE_USER'] = isset($sysConfig['DB_SLAVE_USER']) ? $sysConfig['DB_SLAVE_USER'] : 'root';
            $sysConfig['DB_SLAVE_PASS'] = isset($sysConfig['DB_SLAVE_PASS']) ? $sysConfig['DB_SLAVE_PASS'] : 'toor';
            $sysConfig['DB_SLAVE_DATABASE'] = isset($sysConfig['DB_SLAVE_DATABASE']) ? $sysConfig['DB_SLAVE_DATABASE'] : 'wf_workflow';
            $sysConfig['DB_SLAVE_PORT'] = isset($sysConfig['DB_SLAVE_PORT']) ? $sysConfig['DB_SLAVE_PORT'] : '3306';
            $sysConfig['DEFAULT_MONGO_CONNECTION_STRING'] = isset($sysConfig['DEFAULT_MONGO_CONNECTION_STRING']) ? $sysConfig['DEFAULT_MONGO_CONNECTION_STRING'] : 'mongodb://localhost:27021/';
            $sysConfig['DEFAULT_ELASTIC_CONNECTION_STRING'] = isset($sysConfig['DEFAULT_ELASTIC_CONNECTION_STRING']) ? $sysConfig['DEFAULT_ELASTIC_CONNECTION_STRING'] : 'localhost:9200/_bulk?pretty';

            //Start the connection to database
            $wf_default_db = array_key_exists('DB_SLAVE_DATABASE', $sysConfig) ? $sysConfig['DB_SLAVE_DATABASE'] : (array_key_exists('DEFAULT_DB_DATABASE', $sysConfig) ? $sysConfig['DEFAULT_DB_DATABASE'] : 'wf_workflow');

            $process_keys = array_key_exists('process_keys', $sysConfig) ? array_filter(explode(',', $sysConfig['process_keys'])) : $this->system_process_keys;
            $pm_workspaces = [];
            $errors        = [];

            if ($workspace_id) {
                $pmWorkspace = !empty($workspace_id) ? array_filter(explode(',', $workspace_id)) : [];
                foreach ($pmWorkspace as $key => $value) {
                    $pm_workspaces[]['schema_name'] = 'wf_' . $value;
                }
            } else {
                $logInfo = "Info :: PM workspace info not provided. Opening default mysql connection to get details of all workspaces...". PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                $mysql_connection = $this->getMysqlConnection(
                    [
                        'mysql_host'     => $sysConfig['DB_SLAVE_HOST'],
                        'mysql_user'     => $sysConfig['DB_SLAVE_USER'],
                        'mysql_password' => $sysConfig['DB_SLAVE_PASS'],
                        'mysql_database' => $wf_default_db,
                        'mysql_port'     => $sysConfig['DB_SLAVE_PORT']
                    ]
                );
                $pm_workspaces = $this->getPmWorkspaces(['mysql_connection' => $mysql_connection]);
                $logInfo = "Info :: Closing default mysql connection...". PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                $this->closeMysqlConnection($mysql_connection);
            }

            ob_start();
            echo 'data sync started...!!!!!';
            $serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
            header($serverProtocol . ' 200 OK');
            header('Content-Encoding: none');
            header('Content-Length: ' . ob_get_length());
            header('Connection: close');
            ob_end_flush();
            ob_flush();
            flush();

            $logInfo = "Info :: Opening default mongo connection...". PHP_EOL;
            $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
            $mongo_connection = $this->getMongoConnection($sysConfig['DEFAULT_MONGO_CONNECTION_STRING']);

            foreach ($pm_workspaces as $key => $value) {
                $logInfo = "Info :: Starting Data sync for PM workspace :: ". str_replace('wf_', '', $value['schema_name']) . PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                $logInfo = "Info :: Opening mysql connection for :: ". $value['schema_name'] . PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                $mysql_connection_new = $this->getMysqlConnection(
                    [
                        'mysql_host'     => $sysConfig['DB_SLAVE_HOST'],
                        'mysql_user'     => $sysConfig['DB_SLAVE_USER'],
                        'mysql_password' => $sysConfig['DB_SLAVE_PASS'],
                        'mysql_database' => isset($value['schema_name']) ? $value['schema_name'] : 'wf_workflow',
                        'mysql_port'     => $sysConfig['DB_SLAVE_PORT']
                    ]
                );
                $pmWorkspaceId     = str_replace('wf_', '', $value['schema_name']);
                if (!empty($pmWorkspaceId)) {
                    $targetWorkspaceId = @$this->getWorkflowChannel([
                        'mongo_connection' => $mongo_connection,
                        'workspace'        => $pmWorkspaceId,
                    ])[0]['_targetWorkspace'];
                }

                if(!$targetWorkspaceId) {
                    $logInfo = "WARNING :: No targetWorkspace found for PM workspace - :: ". $pmWorkspaceId . ". Skipping syncing for this pm workspace." . PHP_EOL;
                    $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                    continue;
                }

                $targetWorkspaceId = null;
                $payload           = '';
                $num_cases         = 0;
                
                $case_details                 = $this->getCaseDetailsVersion2ForESSync(
                                                $start,
                                                $limit,
                                                $date_from,
                                                $date_to,
                                                $process,
                                                $search,
                                                $task,
                                                $owner_id,
                                                $status,
                                                $completion,
                                                $current_step,
                                                $is_custom_attributes_honoured,
                                                $assignee_list,
                                                $case_id,
                                                $mysql_connection_new
                                            )['case_details'];

                $logInfo = "Info :: Populated Case details...trying to prepare form details for PM workspace :: ". $pmWorkspaceId . PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);

                foreach ($case_details as $key => &$value) {
                    if (!empty($value['initiator'])) {
                        $value['initiator'] = $this->getWorkflowUser([
                            'mongo_connection' => $mongo_connection,
                            '_pmUser'          => $value['initiator'],
                        ])[0]['_targetUser'];
                        


                        $tempNameValue = $this->getWorkflowUserChannel([
                            'mongo_connection' => $mongo_connection,
                            '_id'              => $value['initiator'],
                        ]);
                        $value['InitiatorName'] = @$tempNameValue[0]['firstName'] . ' ' . @$tempNameValue[0]['lastName'];
                    }

                    if (!empty($value['assignedUsers'])) {
                        foreach ($value['assignedUsers'] as $k => &$v) {
                            $v = $this->getWorkflowUser([
                                'mongo_connection' => $mongo_connection,
                                '_pmUser'          => $v,
                            ])[0]['_targetUser'];
                        }
                    }

                    $value['targetWorkspaceId'] = $targetWorkspaceId;

                    if(!empty($filterTask)){
                        $tasktt = array_filter(explode(',', $filterTask));
                        foreach ($tasktt as $k11 => $v11) {
                            $form_details                 = $this->getFormDetailsVersion2ForESSync(
                                0,
                                18446744073709551615,
                                null,
                                null,
                                $value['processId'],
                                null,
                                $v11,
                                null,
                                null,
                                null,
                                null,
                                false,
                                $value['caseId'],
                                null,
                                $mysql_connection_new
                            )['form_details'];
                            $appendResArray = [];
                            foreach ($form_details as $kte => $vte) {
                                $tempResArray = [];
                                foreach ($vte['form_data']['data'] as $knew => $vnew) {
                                    $tempResArray[] = ['key' => $knew, 'value' => $vnew];
                                }
                                if(!empty($tempResArray) && count($tempResArray) >0) {
                                    $value['variables'][] = $tempResArray;
                                }
                            }
                        }
                    } else {
                        $tasktt = $this->getProcessTaskDetailsVersion2(
                            $start,
                            $limit,
                            $value['processId']
                        );
                        foreach ($tasktt['process_steps'] as $k11 => $v11) {
                            $form_details                 = $this->getFormDetailsVersion2ForESSync(
                                0,
                                18446744073709551615,
                                null,
                                null,
                                $value['processId'],
                                null,
                                $v11['TAS_UID'],
                                null,
                                null,
                                null,
                                null,
                                false,
                                $value['caseId'],
                                null,
                                $mysql_connection_new
                            )['form_details'];
                            $appendResArray = [];
                            foreach ($form_details as $kte => $vte) {
                                $tempResArray = [];
                                foreach ($vte['form_data']['data'] as $knew => $vnew) {
                                    $tempResArray[] = ['key' => $knew, 'value' => $vnew];
                                }
                                if(!empty($tempResArray) && count($tempResArray) >0) {
                                    $value['variables'][] = $tempResArray;
                                }
                            }
                        }
                    }
                    $_uniqid = $value['caseId'];
                    $payload .= '{"index":{"_index":"cases","_type":"case","_id":"'.$_uniqid.'"}}' . PHP_EOL;
                    $payload .= json_encode(json_decode(json_encode($value))) . PHP_EOL;
                    $num_cases++;
                }

                $logInfo = "Info :: Closing mysql connection for :: ". $pmWorkspaceId . PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
                $this->closeMysqlConnection($mysql_connection_new);

                $logInfo = "Info :: Trying to push to elastic for PM workspace :: ". $pmWorkspaceId . PHP_EOL;
                $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);

                
                $dataSyncStatus = [];
                if (empty($payload)) {
                    $dataSyncStatus['success']           = false;
                    $dataSyncStatus['message']           = "no data found in mysql to sync to elastic";
                    $dataSyncStatus['pmWorkspaceId']     = $pmWorkspaceId;
                    $dataSyncStatus['targetWorkspaceId'] = $targetWorkspaceId;
                    $this->esDataSyncLogger->clog($this->esDataSyncLogger, json_encode($dataSyncStatus));
                } else {
                    $curl_response = $this->send_postcurl($sysConfig['DEFAULT_ELASTIC_CONNECTION_STRING'], $payload);
                    if (!$curl_response['success']) {
                        $dataSyncStatus['success']           = false;
                        $dataSyncStatus['message']           = $curl_response['error'];
                        $dataSyncStatus['pmWorkspaceId']     = $pmWorkspaceId;
                        $dataSyncStatus['targetWorkspaceId'] = $targetWorkspaceId;
                        $this->esDataSyncLogger->clog($this->esDataSyncLogger, json_encode($dataSyncStatus));
                    } else {
                        $dataSyncStatus['success']           = true;
                        $dataSyncStatus['message']           = "data sync for targetWorkspaceId - " . $targetWorkspaceId . " ( PM workspace - " . $pmWorkspaceId . ") to elastic - " . $sysConfig['DEFAULT_ELASTIC_CONNECTION_STRING'] . " has completed without any errors. Total number of cases synced are : $num_cases";
                        $dataSyncStatus['pmWorkspaceId']     = $pmWorkspaceId;
                        $dataSyncStatus['targetWorkspaceId'] = $targetWorkspaceId;
                        $this->esDataSyncLogger->clog($this->esDataSyncLogger, json_encode($dataSyncStatus).PHP_EOL);
                    }
                }
            }

            $logInfo = "Info :: Sync finished..Exiting...!!!!!!". PHP_EOL;
            $this->esDataSyncLogger->clog($this->esDataSyncLogger, $logInfo);
            // All done; we blank the PID file and explicitly release the lock 
            // (although this should be unnecessary) before terminating.
            ftruncate($lock_file, 0);
            flock($lock_file, LOCK_UN);
            exit(0);
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }

    private function getMysqlConnection($mysql_connection_details = [])
    {
        try {
            $mysql_host     = $mysql_connection_details['mysql_host'];
            $mysql_user     = $mysql_connection_details['mysql_user'];
            $mysql_password = $mysql_connection_details['mysql_password'];
            $mysql_database = $mysql_connection_details['mysql_database'];
            $mysql_port     = $mysql_connection_details['mysql_port'];

            $mysql_connection = new \mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database, $mysql_port);
            if ($mysql_connection->connect_errno) {
                throw new \Exception("Failed to connect to MySQL: " . $mysql_connection->connect_error);
            }
            return $mysql_connection;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function closeMysqlConnection($connection)
    {
        if($connection)
            return mysqli_close($connection);
    }

    private function getMongoConnection($mongo_connection_string)
    {
        try {
            $mongo_connection = new \MongoDB\Client($mongo_connection_string);
            return $mongo_connection;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getPmWorkspaces($dataList = [])
    {
        try {
            $mysql_connection = $dataList['mysql_connection'];
            $result           = [];
            $sql              = "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'wf_%'";
            $res              = $mysql_connection->query($sql);
            while ($row = $res->fetch_assoc()) {
                $result[] = $row;
            }
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getWorkflowUser($dataList = [])
    {
        try {
            $mongo_connection = $dataList['mongo_connection'];
            $_pmUser          = $dataList['_pmUser'];
            $result           = [];
            $collection       = $mongo_connection->workflow->users;
            $cursor           = $collection->find([
                '_pmUser' => [
                    '$in' => [
                        $_pmUser,
                    ],
                ],
            ],
            ['projection' => ['_targetUser' => 1, '_pmUser' => 1, 'username' => 1]]);

            foreach ($cursor as $document) {
                $temp                = [];
                $temp['_targetUser'] = $document['_targetUser'];
                $temp['_pmUser']     = $document['_pmUser'];
                $result[]            = $temp;
            }
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getWorkflowChannel($dataList = [])
    {
        try {
            $mongo_connection = $dataList['mongo_connection'];
            $workspace        = $dataList['workspace'];
            $result           = [];
            $collection       = $mongo_connection->workflow->workspaces;
            $cursor           = $collection->find([
                'workspace' => [
                    '$in' => [
                        $workspace,
                    ],
                ],
            ],
            ['projection' => ['workspace' => 1, '_targetWorkspace' => 1]]);
            foreach ($cursor as $document) {
                $temp                     = [];
                $temp['workspace']        = $document['workspace'];
                $temp['_targetWorkspace'] = $document['_targetWorkspace'];
                $result[]                 = $temp;
            }
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getWorkflowUserChannel($dataList = [])
    {
        try {
            $mongo_connection = $dataList['mongo_connection'];
            $_id              = $dataList['_id'];
            $result           = [];
            $collection       = $mongo_connection->spotcues_new->users;
            $cursor           = $collection->find([
                '_id' => [
                    '$in' => [
                        new \MongoDB\BSON\ObjectId($_id),
                    ],
                ],
            ],
            ['projection' => ['_id' => 1, 'firstName' => 1, 'lastName' => 1]]);

            foreach ($cursor as $document) {
                $temp              = [];
                $temp['_id']       = $document['_id'];
                $temp['firstName'] = $document['firstName'];
                $temp['lastName']  = $document['lastName'];
                $result[]          = $temp;
            }
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function remove_keys($process_keys, $input_array)
    {
        try {
            if (empty($input_array)) {
                return false;
            }

            if (empty($process_keys)) {
                return $input_array;
            }

            foreach ($input_array as $key => $value) {
                if (in_array($key, $process_keys)) {
                    unset($input_array[$key]);
                }

            }
            return $input_array;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function send_postcurl($url, $payload)
    {
        try {
            $send_body = false;
            $curl_response = [
                'success' => true,
                'error'   => false,
            ];
            
            $headers = [
                'Content-Type: application/json',
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $server_output = curl_exec($ch);
            $httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($server_output === false) {
                $curl_response['success'] = false;
                $curl_response['error']   = "CURL Error: " . curl_error($ch);
            }

            if ($httpCode != 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($server_output, 0, $header_size);
                $body = substr($server_output, $header_size);

                $curl_response['success'] = false;
                $curl_response['error']   = json_encode(json_decode($body));
            }
            curl_close($ch);
            return $curl_response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function parse_app_data($data = [])
    {
        try {
            if (empty($data) || count($data) == 0) {
                return $data;
            }

            $appData = $data['APP_DATA'];
            unset($data['APP_DATA']);
            $data['variables'] = [];
            foreach ($appData as $key => $value) {
                $tempData = [];
                if (is_array($value)) {
                    switch ($key) {
                        case 'attachment':
                        case 'attachments':
                        case 'files':
                        $vn = [];
                        foreach ($value as $k => $v) {
                            if (isset($v['name'])) {
                                $vn[] = $v['name'];
                            }
                        }
                        $tempData['key']   = $key;
                        $tempData['value'] = (implode(',', $vn));
                        break;
                        case 'isActionTask':
                        case 'isActionTask_label':
                        $tempData['key']   = $key;
                        $tempData['value'] = isset($appData['isActionTask_label'][0]) ? $appData['isActionTask_label'][0] : (isset($appData['isActionTask_label']) ? $appData['isActionTask_label'] : '');
                        break;
                        case 'WFS_IS_TASK':
                        $tempData['key']   = $key;
                        $tempData['value'] = isset($appData['WFS_IS_TASK'][0]) ? $appData['WFS_IS_TASK'][0] : (isset($appData['WFS_IS_TASK']) ? $appData['WFS_IS_TASK'] : '');
                        break;

                        case 'isApproved':
                        case 'isReject':
                        case 'approve':
                        case 'reject':
                        $tempData['key']   = $key;
                        $tempData['value'] = isset($appData[$key . '_label'][0]) ?
                        $appData[$key . '_label'][0] :
                        (isset($appData[$key . '_label']) ?
                            $appData[$key . '_label'] :
                            (isset($value[0]) ?
                                $value[0] :
                                (isset($value) ?
                                    $value : '')));
                        break;

                        default:
                        break;
                    }
                } else {
                    $tempData['key']   = $key;
                    $tempData['value'] = $value;
                }
                if (!empty($tempData)) {
                    $data['variables'][] = $tempData;
                }

            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     *
     * @url GET /getProcessTaskDetailsVersion2
     * @author  Pawan Saxena <pawan@spotcues.com>
     */
    public function getProcessTaskDetailsVersion2(
        $start = 0,
        $limit = 10,
        $process = ''
    ) {
        try {
            $dataList['start']   = $start;
            $dataList['limit']   = $limit;
            $dataList['process'] = !empty($process) ? ("'" . implode("', '", array_filter(explode(',', $process))) . "'") : null;

            Validator::throwExceptionIfDataNotMetIso8601Format($dataList, $this->arrayFieldIso8601);
            $dataList = $this->formatDateTimeToUtc($dataList, $this->arrayFieldIso8601);


            return $response = $this->cases->getProcessTaskDetailsVersion2($dataList);
        } catch (Exception $e) {
            throw new RestException(Api::STAT_APP_EXCEPTION, $e->getMessage());
        }
    }
}
