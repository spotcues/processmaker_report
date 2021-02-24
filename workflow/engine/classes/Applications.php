<?php
use Illuminate\Support\Facades\Cache;

class Applications
{
    private static $connObj  = null;
    private static $instance = null;
    private $workspace = null;

    private function __construct()
    {
        if (self::$connObj === null) {
            self::$connObj = Propel::getConnection('workflow_ro') ? Propel::getConnection('workflow_ro') : Propel::getConnection(ApplicationPeer::DATABASE_NAME);
        }

        if ($this->workspace === null) {
            $this->workspace = config('system.workspace');
        }
    }

    public static function &getSingleton()
    {
        if (self::$instance == null) {
            self::$instance = new Applications();
        }
        return self::$instance;
    }

    public function getConnObj()
    {
        if (self::$connObj === null) {
            return self::$connObj = Propel::getConnection('workflow_ro') ? Propel::getConnection('workflow_ro') : Propel::getConnection(ApplicationPeer::DATABASE_NAME);
        } else {
            return self::$connObj;
        }
    }

    public function setConnObj($conn = null)
    {
        if (self::$connObj === null) {
            throw new \Exception('connection cannot be empty');
        } else {
            self::$connObj = $connection;
        }
    }

    /*
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     * METHODS FOR REPORTS VERSION2
     * AUTHOR PAWAN SAXENA <pawan@spotcues.com>
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     */
    
    public function getTaskTitle($tas_uid)
    {
        $sqlData = "SELECT TAS_TITLE, TAS_UID, TAS_ASSIGN_TYPE FROM TASK WHERE TAS_UID IN ('" . $tas_uid . "') ORDER BY TAS_START DESC";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        return $rows;
    }

    public function getAllProcessesVersion2(
        $start = null,
        $limit = null
    ) {
        //Sanitize input variables
        $inputFilter = new InputFilter();
        $start       = (int) $inputFilter->validateInput($start, 'int');
        $limit       = (int) $inputFilter->validateInput($limit, 'int');
        $processSql  = "SELECT PRO_TITLE,PRO_UID FROM PROCESS WHERE PRO_STATUS='ACTIVE'";
        //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $processSql .= " LIMIT $start, " . $limit;
        } else {
            $processSql .= " LIMIT " . $limit;
        }
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($processSql);
        $rows    = [];
        while ($dataset->next()) {
            $row      = $dataset->getRow();
            $tempsql  = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID='" . $row['PRO_UID'] . "' AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
            $stmt2    = self::$connObj->createStatement();
            $dataset2 = $stmt2->executeQuery($tempsql);
            while ($dataset2->next()) {
                $row['steps'][] = $dataset2->getRow();
            }
            $rows[] = $row;
        }
        $result['processes'] = $rows;
        return $result;
    }

    public function getProcessDashboardDetailsVersion2(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $owner_id = null,
        $assignee_list = null
    ) {
        //Sanitize input variables
        $inputFilter                   = new InputFilter();
        $start                         = (int) $inputFilter->validateInput($start, 'int');
        $limit                         = (int) $inputFilter->validateInput($limit, 'int');
        $search                        = $inputFilter->escapeUsingConnection($search, self::$connObj);

            $totalProcessCountSql = "SELECT
                COUNT(DISTINCT P.PRO_UID) AS total_processes
                FROM PROCESS P
                LEFT JOIN APPLICATION A ON A.PRO_UID=P.PRO_UID
                LEFT JOIN APP_CACHE_VIEW AC ON A.APP_UID=AC.APP_UID
                WHERE P.PRO_STATUS ='ACTIVE' AND A.APP_STATUS IN ('TO_DO','COMPLETED') ";
        if (!empty($search)) {
            $totalProcessCountSql .= " AND P.PRO_TITLE LIKE '%{$search}%'";
        }
        if (!empty($process)) {
            $totalProcessCountSql .= " AND P.PRO_UID IN ( $process )";
        }
        if (!empty($owner_id)) {
            $totalProcessCountSql .= " AND A.APP_INIT_USER IN ( $owner_id )";
        }
        if (!empty($assignee_list)) {
            $totalProcessCountSql .= " AND AC.USR_UID IN ( $assignee_list )";
        }
        if (!empty($dateTo)) {
            $totalProcessCountSql .= " AND A.APP_UPDATE_DATE <= '{$dateTo}'";
        }
        if (!empty($dateFrom)) {
            $totalProcessCountSql .= " AND A.APP_UPDATE_DATE >= '{$dateFrom}'";
        }
                $totalProcessCountSql .= " ORDER BY A.APP_UPDATE_DATE DESC";


        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($totalProcessCountSql);
        $total_processes = '0';
        while ($dataset->next()) {
            $row                     = $dataset->getRow();
            $total_processes = isset($row['total_processes']) ? $row['total_processes'] : '0';
        }


            $totalCasesSql = "SELECT
            COUNT(DISTINCT A.APP_UID) AS total_cases,
            A.PRO_UID,
            P.PRO_TITLE
            FROM APPLICATION A
            LEFT JOIN PROCESS P ON A.PRO_UID=P.PRO_UID
            LEFT JOIN APP_CACHE_VIEW AC ON A.APP_UID=AC.APP_UID
            WHERE 1";
        if (!empty($search)) {
            $totalCasesSql .= " AND P.PRO_TITLE LIKE '%{$search}%'";
        }
        if (!empty($process)) {
            $totalCasesSql .= " AND A.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $totalCasesSql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $totalCasesSql .= " AND AC.USR_UID IN (" . $assignee_list . ")";
        }
        if (!empty($dateTo)) {
            $totalCasesSql .= " AND A.APP_CREATE_DATE <= '{$dateTo}'";
        }
        if (!empty($dateFrom)) {
            $totalCasesSql .= " AND A.APP_CREATE_DATE >= '{$dateFrom}'";
        }
            $totalCasesSql .= " AND A.APP_STATUS IN ('TO_DO','COMPLETED')";
            $totalCasesSql .= " GROUP BY A.PRO_UID";
            $totalCasesSql .= " ORDER BY A.APP_CREATE_DATE DESC";

        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($totalCasesSql);
        $total_cases = [];
        while ($dataset->next()) {
            $row                     = $dataset->getRow();
            $total_cases[$row['PRO_UID']] = isset($row['total_cases']) ? $row['total_cases'] : '0';
        }


            $process_dashboard_details_sql = "SELECT
            P.PRO_UID as process_id,
            P.PRO_TITLE as process_title,
            SUM( IF(A.APP_STATUS = 'COMPLETED', 1, 0)) AS case_completed,
            SUM( IF(A.APP_STATUS = 'TO_DO', 1, 0)) AS open_cases,
            SUM( IF((ACD.app_status = 'COMPLETED' AND (ACD.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,ACD.app_finish_date,ACD.curr_step_max_del_task_due_date)>= 0) ),1,0)) AS case_completed_ontime,
            SUM( IF((ACD.app_status = 'COMPLETED' AND (ACD.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,ACD.app_finish_date,ACD.curr_step_max_del_task_due_date) < 0) ),1,0) ) AS case_completed_after_due_date,
            SUM( IF ((ACD.app_status = 'TO_DO' AND ACD.usr_uid ='' AND ACD.previous_usr_uid IS NOT NULL ),1,0)) AS unassigned_cases,
            SUM(IF ((ACD.app_status = 'TO_DO' AND (ACD.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),ACD.curr_step_max_del_task_due_date) < 0) ), 1, 0)) AS open_cases_overdue
            FROM (
            SELECT
            AGTD.APP_UID AS app_uid,
            AGTD.USR_UID AS usr_uid,
            AGTD.PREVIOUS_USR_UID AS previous_usr_uid,
            AGTD.APP_STATUS AS app_status,
            AGTD.PRO_UID AS pro_uid,
            AGTD.APP_FINISH_DATE AS app_finish_date,
            AGTD.APP_CREATE_DATE AS app_create_date,
            AGTD.APP_UPDATE_DATE AS app_update_date,
            AGTD.DEL_INDEX AS max_del_index,
            AGTD.DEL_TASK_DUE_DATE AS curr_step_max_del_task_due_date,
            AUU.wfs_is_task AS wfs_is_task
            FROM APP_CACHE_VIEW AGTD
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) ACD
            RIGHT JOIN PROCESS P ON ACD.PRO_UID=P.PRO_UID
            LEFT JOIN APPLICATION A ON A.APP_UID=ACD.APP_UID
            WHERE P.PRO_STATUS='ACTIVE' 
            AND A.APP_STATUS IN ('TO_DO','COMPLETED') 
            ";
        if (!empty($search)) {
            $process_dashboard_details_sql .= " AND P.PRO_TITLE LIKE '%{$search}%'";
        }
        if (!empty($process)) {
            $process_dashboard_details_sql .= " AND P.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $process_dashboard_details_sql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $process_dashboard_details_sql .= " AND ACD.usr_uid IN (" . $assignee_list . ")";
        }
        if (!empty($dateFrom)) {
            $process_dashboard_details_sql .= " AND A.APP_UPDATE_DATE >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $process_dashboard_details_sql .= " AND A.APP_UPDATE_DATE <= '{$dateTo}'";
        }
            $process_dashboard_details_sql .= " GROUP BY A.PRO_UID";
            $process_dashboard_details_sql .= " ORDER BY A.APP_UPDATE_DATE DESC";

            //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $process_dashboard_details_sql .= " LIMIT $start, " . $limit;
        } else {
            $process_dashboard_details_sql .= " LIMIT " . $limit;
        }


        $stmt          = self::$connObj->createStatement();
        $dataset       = $stmt->executeQuery($process_dashboard_details_sql);
        $result = [];
        while ($dataset->next()) {
            $row = $dataset->getRow();
            $result[] = $row;
        }

        foreach ($result as $key => &$value) {
            $value['total_cases'] = isset($total_cases[$value['process_id']]) ? $total_cases[$value['process_id']] : '0';
        }
        $result['total_processes'] = $total_processes;
        unset($total_processes);
        unset($total_cases);
        return $result;
    }

    public function getProcessTasks(
        $process = null
    ) {
            $sqlData = "SELECT TAS_UID FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
            $stmt    = self::$connObj->createStatement();
            $dataset = $stmt->executeQuery($sqlData);
            $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        return $rows;
    }

    public function getFormDetailsVersion2(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $case_id = null,
        $assignee_list = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'ACD.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'ACD.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'ACD.APP_CREATE_DATE';
                break;
        }
        $status              = strtoupper($status);
            $formDetailsCountSql = "SELECT
            COUNT(A.APP_UID) AS total_rows
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER = U.USR_UID)
            LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_STATUS,
            AGTD.APP_UPDATE_DATE,
            AGTD.APP_CREATE_DATE,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.APP_TITLE,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

        if (!empty($assignee_list)) {
            $formDetailsCountSql .= " AND AD.USR_UID IN (" . $assignee_list . ")";
        }

            $formDetailsCountSql .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_TITLE,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE 1";
        if (!empty($dateFrom)) {
            $formDetailsCountSql .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $formDetailsCountSql .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $formDetailsCountSql .= " AND AC.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $formDetailsCountSql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $formDetailsCountSql .= " AND ACD.assignee_user_id IS NOT NULL";
        }
        if (!empty($case_id)) {
            $formDetailsCountSql .= " AND AC.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $formDetailsCountSql .= " AND AC.TAS_UID IN (" . $task . ")";
        }
            $formDetailsCountSql .= " AND AC.APP_STATUS IN ('COMPLETED', 'TO_DO')";
        if (!empty($search)) {
            $formDetailsCountSql .= " AND (
                AC.APP_NUMBER LIKE '%{$search}%'
                OR A.APP_TITLE LIKE '%{$search}%'
                OR ACD.app_tas_title LIKE '%{$search}%'
            )";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $formDetailsCountSql .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
            } else {
                $formDetailsCountSql .= " AND AC.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $formDetailsCountSql .= " AND (ACD.wfs_is_task= '1')";
            $formDetailsCountSql .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        
        
        
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($formDetailsCountSql);
        $result['form_details_total_rows'] = 0;
        while ($dataset->next()) {
            $row = $dataset->getRow();
            $result['form_details_total_rows'] = $row['total_rows'];
        }
        
        if (!empty($task)) {
                $sqlData = "SELECT
                AC.APP_UID,
                AC.PRO_UID,
                AC.APP_NUMBER AS case_id,
                CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) AS owner,
                A.APP_INIT_USER as owner_user_id,
                AC.USR_UID as assignee_user_id,
                CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) AS assignee,
                CASE
                WHEN ((AC.PREVIOUS_USR_UID !='' OR AC.PREVIOUS_USR_UID IS NOT NULL) AND (AC.DEL_INIT_DATE IS NULL OR AC.DEL_INIT_DATE='') AND (AC.DEL_FINISH_DATE IS NOT NULL OR AC.DEL_FINISH_DATE!='') AND AC.DEL_THREAD_STATUS='CLOSED') THEN 'true'
                ELSE 'false'
                END AS is_delegated,
                CASE
                WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
                WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
                END AS status,
                ACD.completion,
                ACD.app_tas_title AS current_step,
                IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,'') AS completed_on,
                IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,'') AS user_completed_on,
                A.APP_CREATE_DATE AS create_date,
                A.APP_INIT_DATE AS start_date,
                AC.DEL_DELEGATE_DATE AS delegate_date,
                AC.DEL_INIT_DATE AS user_init_date,
                AC.TAS_UID AS task_uid,
                AC.APP_TAS_TITLE AS user_current_step,
                A.APP_DATA
                FROM
                APP_CACHE_VIEW AC
                LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
                LEFT JOIN USERS U ON (A.APP_INIT_USER = U.USR_UID)
                LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
                LEFT JOIN (SELECT
                AGTD.APP_UID,
                AGTD.PRO_UID,
                AGTD.APP_STATUS,
                AGTD.DEL_INDEX,
                AGTD.APP_CREATE_DATE,
                AGTD.APP_UPDATE_DATE,
                AGTD.TAS_UID AS tas_uid,
                AGTD.APP_TAS_TITLE AS app_tas_title,
                AUU.wfs_is_task AS wfs_is_task,
                AUU.wfs_case_title AS wfs_case_title,
                AUU.app_finish_date AS app_finish_date,
                GTDD.assignee_user_id AS assignee_user_id,
                CASE
                WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
                WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
                WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
                THEN 'delayed'
                WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
                ELSE 'NA'
                END as completion,
                IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
                due_by
                FROM APP_CACHE_VIEW AGTD
                LEFT JOIN ( SELECT
                AD.APP_UID,
                AD.APP_NUMBER,
                AD.DEL_INDEX,
                GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
                GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
                FROM APP_DELEGATION AD
                INNER JOIN ( SELECT
                APP_UID,
                MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
                FROM APP_DELEGATION
                GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
                LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
                LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
                WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

            if (!empty($assignee_list)) {
                $sqlData .= " AND AD.USR_UID IN (" . $assignee_list . ")";
            }

                $sqlData .= " GROUP BY AD.APP_UID
                )
                GTDD ON AGTD.APP_UID=GTDD.APP_UID
                INNER JOIN ( SELECT
                APP_UID AS GTD_APP_UID,
                MAX(DEL_INDEX) AS GTD_DEL_INDEX
                FROM APP_CACHE_VIEW
                GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
                LEFT JOIN (SELECT
                APP_UID,
                APP_FINISH_DATE AS app_finish_date,
                LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task,
                APP_TITLE AS wfs_case_title
                FROM APPLICATION
                GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
                GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
                ) AS ACD ON ACD.APP_UID=AC.APP_UID
                WHERE 1";

            if (!empty($dateFrom)) {
                $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
            }
            if (!empty($dateTo)) {
                $sqlData .= " AND $filterDateType <= '{$dateTo}'";
            }
            if (!empty($process)) {
                $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
            }
            if (!empty($owner_id)) {
                $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
            }
            if (!empty($assignee_list)) {
                $sqlData .= " AND ACD.assignee_user_id IS NOT NULL";
            }
            if (!empty($case_id)) {
                $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
            }
            if (!empty($task)) {
                $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
            }
                $sqlData .= " AND AC.APP_STATUS IN ('COMPLETED', 'TO_DO')";
            if (!empty($search)) {
                $sqlData .= " AND (
                        AC.APP_NUMBER LIKE '%{$search}%'
                        OR ACD.wfs_case_title LIKE '%{$search}%'
                        OR ACD.app_tas_title LIKE '%{$search}%'
                    )";
            }
            if (!empty($status)) {
                if ($status == 'UNASSIGNED') {
                    $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
                } else {
                    $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
                }
            }
            if (!empty($completion)) {
                $sqlData .= " AND (ACD.wfs_is_task = '1')";
                $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
            }
                $sqlData .= " GROUP BY A.PRO_UID,A.APP_UID,AC.TAS_UID,AC.USR_UID,AC.DEL_INDEX";
                $sqlData .= " ORDER BY $filterDateType DESC";
                //Define the number of records by return
            if (empty($limit)) {
                $limit = 25;
            }
            if (!empty($start)) {
                $sqlData .= " LIMIT $start, " . $limit;
            } else {
                $sqlData .= " LIMIT " . $limit;
            }
            
            $stmt    = self::$connObj->createStatement();
            $dataset = $stmt->executeQuery($sqlData);
            $result['form_details'] = [];
            while ($dataset->next()) {
                $row              = $dataset->getRow();
                $row['form_data'] = null;
                $result['form_details'][]           = $row;
            }
            if ($process) {
                    $stepsSql = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
                    $stmt     = self::$connObj->createStatement();
                    $dataset  = $stmt->executeQuery($stepsSql);
                    $rows = [];
                while ($dataset->next()) {
                    $row              = $dataset->getRow();
                    $row['TAS_TITLE'] = @explode('$$', $row['TAS_TITLE'])[0];
                    $rows[]           = $row;
                }
                $result['task_details'] = $rows;
            }
        } else {
                $sqlData = "SELECT
                AC.APP_UID,
                AC.PRO_UID,
                AC.APP_NUMBER AS case_id,
                CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) AS owner,
                A.APP_INIT_USER as owner_user_id,
                AC.USR_UID as assignee_user_id,
                CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) AS assignee,
                CASE
                WHEN ((AC.PREVIOUS_USR_UID !='' OR AC.PREVIOUS_USR_UID IS NOT NULL) AND (AC.DEL_INIT_DATE IS NULL OR AC.DEL_INIT_DATE='') AND (AC.DEL_FINISH_DATE IS NOT NULL OR AC.DEL_FINISH_DATE!='') AND AC.DEL_THREAD_STATUS='CLOSED') THEN 'true'
                ELSE 'false'
                END AS is_delegated,
                CASE
                WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
                WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
                END AS status,
                ACD.completion,
                ACD.app_tas_title as current_step,
                ACD.due_by AS due_by,
                IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,'') as completed_on,
                IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,'') as user_completed_on,
                IF(A.APP_CREATE_DATE IS NOT NULL AND A.APP_CREATE_DATE !='', A.APP_CREATE_DATE,'') as create_date,
                A.APP_INIT_DATE AS start_date,
                AC.DEL_DELEGATE_DATE AS delegate_date,
                AC.DEL_INIT_DATE AS user_init_date,
                AC.TAS_UID as task_uid,
                AC.APP_TAS_TITLE as user_current_step,
                A.APP_DATA
                FROM
                APP_CACHE_VIEW AC
                LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
                LEFT JOIN USERS U ON (A.APP_INIT_USER = U.USR_UID)
                LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
                LEFT JOIN (SELECT
                AGTD.APP_UID,
                AGTD.PRO_UID,
                AGTD.APP_STATUS,
                AGTD.DEL_INDEX,
                AGTD.APP_CREATE_DATE,
                AGTD.APP_UPDATE_DATE,
                AGTD.TAS_UID AS tas_uid,
                AGTD.APP_TAS_TITLE AS app_tas_title,
                AUU.wfs_is_task AS wfs_is_task,
                AUU.wfs_case_title AS wfs_case_title,
                AUU.app_finish_date AS app_finish_date,
                GTDD.assignee_user_id AS assignee_user_id,
                CASE
                WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
                WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
                WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
                THEN 'delayed'
                WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
                ELSE 'NA'
                END as completion,
                IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (AUU.wfs_is_task = '1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
                due_by
                FROM APP_CACHE_VIEW AGTD
                LEFT JOIN ( SELECT
                AD.APP_UID,
                AD.APP_NUMBER,
                AD.DEL_INDEX,
                GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
                GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
                FROM APP_DELEGATION AD
                INNER JOIN ( SELECT
                APP_UID,
                MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
                FROM APP_DELEGATION
                GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
                LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
                LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
                WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

            if (!empty($assignee_list)) {
                $sqlData .= " AND AD.USR_UID IN (" . $assignee_list . ")";
            }

                $sqlData .= " GROUP BY AD.APP_UID
                )
                GTDD ON AGTD.APP_UID=GTDD.APP_UID
                INNER JOIN ( SELECT
                APP_UID AS GTD_APP_UID,
                MAX(DEL_INDEX) AS GTD_DEL_INDEX
                FROM APP_CACHE_VIEW
                GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
                LEFT JOIN (SELECT
                APP_UID,
                APP_FINISH_DATE AS app_finish_date,
                LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task,
                APP_TITLE AS wfs_case_title
                FROM APPLICATION
                GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
                GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
                ) AS ACD ON ACD.APP_UID=AC.APP_UID
                WHERE 1";

            if (!empty($dateFrom)) {
                $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
            }
            if (!empty($dateTo)) {
                $sqlData .= " AND $filterDateType <= '{$dateTo}'";
            }
            if (!empty($process)) {
                $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
            }
            if (!empty($owner_id)) {
                $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
            }
            if (!empty($assignee_list)) {
                $sqlData .= " AND ACD.assignee_user_id IS NOT NULL";
            }
            if (!empty($case_id)) {
                $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
            }
            if (!empty($task)) {
                $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
            }
                $sqlData .= " AND A.APP_STATUS IN ('COMPLETED', 'TO_DO')";
            if (!empty($search)) {
                $sqlData .= " AND (
                        AC.APP_NUMBER LIKE '%{$search}%'
                        OR ACD.wfs_case_title LIKE '%{$search}%'
                        OR ACD.app_tas_title LIKE '%{$search}%'
                    )";
            }
            if (!empty($status)) {
                if ($status == 'UNASSIGNED') {
                    $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
                } else {
                    $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
                }
            }
            if (!empty($completion)) {
                $sqlData .= " AND (ACD.wfs_is_task = '1')";
                $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
            }
                $sqlData .= " GROUP BY A.PRO_UID,A.APP_UID,AC.TAS_UID,AC.USR_UID,AC.DEL_INDEX";
                $sqlData .= " ORDER BY $filterDateType DESC";

                //Define the number of records by return
            if (empty($limit)) {
                $limit = 25;
            }
            if (!empty($start)) {
                $sqlData .= " LIMIT $start, " . $limit;
            } else {
                $sqlData .= " LIMIT " . $limit;
            }
            $stmt    = self::$connObj->createStatement();
            $dataset = $stmt->executeQuery($sqlData);
            $result['form_details'] = [];
            while ($dataset->next()) {
                $row              = $dataset->getRow();
                $row['form_data'] = null;
                $result['form_details'][]           = $row;
            }
            if ($process) {
                        $stepsSql = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
                        $stmt     = self::$connObj->createStatement();
                        $dataset  = $stmt->executeQuery($stepsSql);
                        $rows     = [];
                while ($dataset->next()) {
                    $row              = $dataset->getRow();
                    $row['TAS_TITLE'] = @explode('$$', $row['TAS_TITLE'])[0];
                    $rows[]           = $row;
                }
                $result['task_details'] = $rows;
            }
        }
        return $result;
    }

    public function getCaseDetailsVersion2(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $assignee_list = null,
        $case_id = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'ACD.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'ACD.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'ACD.APP_CREATE_DATE';
                break;
        }
        $status  = strtoupper($status);
            $totalCountSql = "SELECT
            COUNT(DISTINCT ACD.APP_UID) as total_rows
            FROM 
            (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_STATUS,
            AGTD.APP_UPDATE_DATE,
            AGTD.APP_CREATE_DATE,
            AGTD.DEL_INDEX,
            AGTD.APP_NUMBER,
            AGTD.USR_UID,
            AGTD.PREVIOUS_USR_UID,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.DEL_INDEX,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

        if (!empty($assignee_list)) {
            $totalCountSql .= " AND AD.USR_UID IN (" . $assignee_list . ")";
        }

            $totalCountSql .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD
            LEFT JOIN APPLICATION A ON ACD.APP_UID=A.APP_UID
            WHERE
            ACD.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $totalCountSql .= " AND (
                    ACD.APP_NUMBER LIKE '%{$search}%'
                    OR A.APP_TITLE LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $totalCountSql .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $totalCountSql .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $totalCountSql .= " AND ACD.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $totalCountSql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $totalCountSql .= " AND ACD.assignee_user_id IS NOT NULL";
        }
        if (!empty($current_step)) {
            $totalCountSql .= " AND ACD.tas_uid='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $totalCountSql .= " AND ACD.USR_UID = '' AND ACD.PREVIOUS_USR_UID !=''";
            } else {
                $totalCountSql .= " AND ACD.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $totalCountSql .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        if (!empty($case_id)) {
            $totalCountSql .= " AND ACD.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $totalCountSql .= " AND ACD.tas_uid IN (" . $task . ")";
        }
            $totalCountSql .= " GROUP BY ACD.PRO_UID";
        
        

            $sqlData = "SELECT
            ACD.APP_UID,
            ACD.PRO_UID,
            ACD.APP_NUMBER AS case_id,
            A.APP_DATA AS APP_DATA,
            CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) as owner,
            A.APP_INIT_USER as owner_user_id,
            A.APP_CREATE_DATE as create_date,
            IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,NULL) as completed_on,
            IF(A.APP_INIT_DATE IS NOT NULL AND A.APP_INIT_DATE !='', A.APP_INIT_DATE,NULL) as start_date,
            ACD.assignee_user_id,
            ACD.assignee_count,
            IF(ACD.assignee IS NOT NULL AND ACD.assignee !='', ACD.assignee,'NA') AS assignee,
            CASE
            WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
            WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
            END AS status,
            ACD.app_tas_title as current_step,
            ACD.completion AS completion,
            ACD.due_by AS due_by
            FROM
            (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_NUMBER,
            AGTD.APP_UPDATE_DATE,
            AGTD.APP_CREATE_DATE,
            AGTD.APP_STATUS,
            AGTD.USR_UID,
            AGTD.PREVIOUS_USR_UID,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.APP_TITLE AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            GTDD.assignee_count AS assignee_count,
            GTDD.assignee,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (AUU.wfs_is_task = '1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS assignee_count,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
            GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

        if (!empty($assignee_list)) {
            $sqlData .= " AND AD.USR_UID IN (" . $assignee_list . ")";
        }

            $sqlData .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_TITLE,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD
            LEFT JOIN APPLICATION A ON ACD.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER=U.USR_UID)
            WHERE
            ACD.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $sqlData .= " AND (
                    ACD.APP_NUMBER LIKE '%{$search}%'
                    OR ACD.wfs_case_title LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $sqlData .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND ACD.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $sqlData .= " AND ACD.assignee_user_id IS NOT NULL";
        }
            
        if (!empty($current_step)) {
            $sqlData .= " AND ACD.tas_uid='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $sqlData .= " AND ACD.USR_UID = '' AND ACD.PREVIOUS_USR_UID !=''";
            } else {
                $sqlData .= " AND ACD.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        if (!empty($case_id)) {
            $sqlData .= " AND ACD.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $sqlData .= " AND ACD.TAS_UID IN (" . $task . ")";
        }
            $sqlData .= " GROUP BY ACD.PRO_UID,ACD.APP_UID";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }
        
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row                 = $dataset->getRow();
            $row['current_step'] = @explode('$$', $row['current_step'])[0];
            $rows[]              = $row;
        }
        $result['case_details'] = $rows;


        $stmt                   = self::$connObj->createStatement();
        $dataset                = $stmt->executeQuery($totalCountSql);
        $result['case_details_total_rows'] = 0;
        while ($dataset->next()) {
            $row = $dataset->getRow();
            $result['case_details_total_rows'] = isset($row['total_rows']) ? $row['total_rows'] : 0;
        }


        if ($process) {
                $stepsSql = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
                $stmt     = self::$connObj->createStatement();
                $dataset  = $stmt->executeQuery($stepsSql);
                $rows     = [];
            while ($dataset->next()) {
                $row              = $dataset->getRow();
                $row['TAS_TITLE'] = @explode('$$', $row['TAS_TITLE'])[0];
                $rows[]           = $row;
            }
            $result['task_details'] = $rows;
        }
        return $result;
    }

    public function getCaseUserDetailsVersion2(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $case_id = null,
        $assignee_list = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'AC.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
        }
        $status  = strtoupper($status);
            $sqlData = "SELECT
            AC.APP_UID,
            AC.PRO_UID,
            AC.APP_NUMBER AS case_id,
            A.APP_DATA AS APP_DATA,
            AC.USR_UID as assignee_user_id,
            IF(CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) IS NOT NULL AND CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) !='', CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)),'NA') AS assignee,
            IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,NULL) as completed_on,
            AC.DEL_INIT_DATE AS user_start_date,
            IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,NULL) as user_completed_on,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN') THEN 'In Progress'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED') THEN 'Completed'
            ELSE 'NA'
            END AS user_status,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE) < 0)) THEN 'delayed'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END AS user_completion
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER=U.USR_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.wfs_case_title AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            GTDD.assignee_count AS assignee_count,
            GTDD.assignee
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS assignee_count,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
            GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1' 
            GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task,
            APP_TITLE as wfs_case_title
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE
            AC.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $sqlData .= " AND (
                    ACD.assignee LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $sqlData .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $sqlData .= " AND AC.USR_UID IN (" . $assignee_list . ")";
        }
        if (!empty($current_step)) {
            $sqlData .= " AND AC.TAS_UID='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $sqlData .= " AND AC.USR_UID='' AND AC.PREVIOUS_USR_UID !=''";
            } else {
                $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
            }
        }
            $sqlData .= " AND AC.APP_UID='" . $case_id . "'";
            //TO_DO temporary fix
            $sqlData .= " AND AC.DEL_INDEX!='1'";
        if (!empty($task)) {
            $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
        }
            $sqlData .= " GROUP BY AC.PRO_UID,AC.APP_UID,AC.USR_UID";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }

            $totalCountSql = "SELECT
            COUNT(A.APP_UID) as total_rows
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER=U.USR_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            -- LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1' GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE
            AC.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $totalCountSql .= " AND (
                    OR ACD.assignee LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $totalCountSql .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $totalCountSql .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $totalCountSql .= " AND AC.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $totalCountSql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $totalCountSql .= " AND AC.USR_UID IN (" . $assignee_list . ")";
        }
        if (!empty($current_step)) {
            $totalCountSql .= " AND AC.TAS_UID='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $totalCountSql .= " AND AC.USR_UID='' AND AC.PREVIOUS_USR_UID !=''";
            } else {
                $totalCountSql .= " AND AC.APP_STATUS='" . $status . "'";
            }
        }
            $totalCountSql .= " AND AC.APP_UID='" . $case_id . "'";
            //TO_DO temporary fix
            $totalCountSql .= " AND AC.DEL_INDEX!='1'";
        if (!empty($task)) {
            $totalCountSql .= " AND AC.TAS_UID IN (" . $task . ")";
        }

            $caseSummarySql = "SELECT
            AC.APP_UID,
            AC.PRO_UID,
            AC.APP_NUMBER AS case_id,
            ACD.wfs_case_title as case_title,
            ACD.assignee_count,
            IF(ACD.user_completed_count IS NOT NULL AND ACD.user_completed_count !='', ACD.user_completed_count,0) AS user_completed_count,
            ACD.due_by AS due_by,
            IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,NULL) as completed_on
            
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER=U.USR_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.DEL_INDEX,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.wfs_case_title AS wfs_case_title,
            GTDD.assignee_count AS assignee_count,
            AXDD.user_completed_count AS user_completed_count,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS assignee_count,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            -- LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'
            GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS user_completed_count
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) AXD ON AD.APP_UID=AXD.APP_UID AND AD.DEL_PREVIOUS=AXD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1' AND AD.DEL_THREAD_STATUS='CLOSED'
            GROUP BY AD.APP_UID
            ) AXDD ON AGTD.APP_UID=AXDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task,
            APP_TITLE AS wfs_case_title
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE
            1";
            $caseSummarySql .= " AND AC.APP_UID='" . $case_id . "'";
            //TO_DO temporary fix
            $caseSummarySql .= " AND AC.DEL_INDEX!='1'";
            $caseSummarySql .= " GROUP BY AC.PRO_UID,AC.APP_UID";
            $caseSummarySql .= " ORDER BY $filterDateType DESC";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        $result['user_details'] = $rows;
        
        $stmt                   = self::$connObj->createStatement();
        $dataset                = $stmt->executeQuery($totalCountSql);
        $result['user_details_total_rows'] = 0;
        while ($dataset->next()) {
            $row = $dataset->getRow();
            $result['user_details_total_rows'] = isset($row['total_rows']) ? $row['total_rows'] : 0;
        }

        if (!empty($caseSummarySql)) {
            $stmt    = self::$connObj->createStatement();
            $dataset = $stmt->executeQuery($caseSummarySql);
            $row     = [];
            while ($dataset->next()) {
                $row                 = $dataset->getRow();
                $row['current_step'] = @explode('$$', $row['current_step'])[0];
            }
            $result['case_summary'] = $row;
        }

        if ($process) {
                $stepsSql = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
                $stmt     = self::$connObj->createStatement();
                $dataset  = $stmt->executeQuery($stepsSql);
                $rows     = [];
            while ($dataset->next()) {
                $row              = $dataset->getRow();
                $row['TAS_TITLE'] = @explode('$$', $row['TAS_TITLE'])[0];
                $rows[]           = $row;
            }
            $result['task_details'] = $rows;
        }
        return $result;
    }




    public function getCaseUserDetailsVersion2ExcelData(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $case_id = null,
        $assignee_list = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'AC.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
        }
        $status  = strtoupper($status);
            $sqlData = "SELECT
            AC.USR_UID as assignee,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN') THEN 'In Progress'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED') THEN 'Completed'
            ELSE 'NA'
            END AS status,
            IF(AC.DEL_INIT_DATE IS NOT NULL, AC.DEL_INIT_DATE, '-') AS start_date,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE) < 0)) THEN 'delayed'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (ACD.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE '-'
            END AS completion,
            IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,'-') as completed_on
            
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX,
            AUU.wfs_is_task AS wfs_is_task
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1' 
            GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE
            AC.APP_STATUS IN ('COMPLETED','TO_DO')";

        if (!empty($dateFrom)) {
            $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $sqlData .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
        }

            $sqlData .= " AND AC.APP_UID='" . $case_id . "'";
            //TO_DO temporary fix
            $sqlData .= " AND AC.DEL_INDEX!='1'";

            $sqlData .= " GROUP BY AC.PRO_UID,AC.APP_UID,AC.USR_UID";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }
            
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        $result['user_details'] = $rows;
        return $result;
    }

    public function getAllDynaforms(
        $process = null,
        $task = null
    ) {
        $sqlData = "SELECT STEP_UID_OBJ AS DYNF_UID FROM STEP WHERE PRO_UID IN(" . $process . ") AND TAS_UID IN (" . $task . ")";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $rows[] = $dataset->getRow();
        }
        return $rows;
    }

    public function getDynaformsVariables(
        $dynaform_id = null
    ) {
        $sqlData = "SELECT DYN_CONTENT FROM DYNAFORM WHERE DYN_UID ='" . $dynaform_id . "'";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row = json_decode($dataset->getRow()['DYN_CONTENT']);
            $row = $row->items[0]->items;
            foreach ($row as $key => $value) {
                $item = $value[0];
                if (!empty($item->variable) && $item->variable != null) {
                    $rows[@$item->variable]['type']     = @$item->type;
                    $rows[@$item->variable]['dataType'] = @$item->dataType;
                    $rows[@$item->variable]['variable'] = @$item->variable;
                    $rows[@$item->variable]['name']     = @$item->name;
                    $rows[@$item->variable]['label']    = @$item->label;
                    $rows[@$item->variable]['options']  = @$item->options;
                    $rows[@$item->variable]['hint']     = @$item->hint;
                    // pouplating the meta data of columns for the grid type element
                    if ($item->type == "grid") {
                        $rows[@$item->variable]['columns'] = @$item->columns;
                    }
                }
            }
        }
        return $rows;
    }


    public function getDynaformsVariablesExcelData(
        $dynaform_id = null
    ) {
        $sqlData = "SELECT DYN_CONTENT FROM DYNAFORM WHERE DYN_UID ='" . $dynaform_id . "'";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row = json_decode($dataset->getRow()['DYN_CONTENT']);
            $row = $row->items[0]->items;
            foreach ($row as $key => $value) {
                $item = $value[0];
                if (!empty($item->variable) && $item->variable != null) {
                    $rows[@$item->variable]['type']     = @$item->type;
                    $rows[@$item->variable]['dataType'] = @$item->dataType;
                    $rows[@$item->variable]['variable'] = @$item->variable;
                    $rows[@$item->variable]['name']     = @$item->name;
                    $rows[@$item->variable]['label']    = @$item->label;
                    $rows[@$item->variable]['options']  = @$item->options;
                    $rows[@$item->variable]['hint']     = @$item->hint;
                    // pouplating the meta data of columns for the grid type element
                    if ($item->type == "grid") {
                        $columns = @$item->columns;
                        $tempLabelMapping = [];
                        foreach ($columns as $kc => $vc) {
                            $tempLabelMapping = array_merge($tempLabelMapping, [$vc->name => $vc->label]);
                        }
                        ksort($tempLabelMapping);
                        $rows[@$item->variable]['label_mapping'] = @$tempLabelMapping;
                        $rows[@$item->variable]['grid_heading'] =  "| ". implode(' | ',array_values($tempLabelMapping)) . " |";
                        $rows[@$item->variable]['columns'] = $columns;
                    }
                }
            }
        }
        return $rows;
    }

    public function getCaseList(
        $start = null,
        $limit = null,
        $process = '',
        $dateFrom = null,
        $dateTo = null
    ) {
        //Sanitize input variables
        $inputFilter = new InputFilter();
        $start       = (int) $inputFilter->validateInput($start, 'int');
        $limit       = (int) $inputFilter->validateInput($limit, 'int');
        $dateFrom    = $inputFilter->escapeUsingConnection($dateFrom, self::$connObj);
        $dateTo      = $inputFilter->escapeUsingConnection($dateTo, self::$connObj);
        $sqlData     = "SELECT AC.APP_UID, AC.APP_NUMBER AS CASE_ID, AC.APP_TITLE, AC.APP_PRO_TITLE,AC.APP_TAS_TITLE AS CURRENT_STEP, AC.APP_STATUS, AC.DEL_INIT_DATE AS START_TIME, AC.DEL_TASK_DUE_DATE AS DUE_BY, AC.DEL_FINISH_DATE AS COMPLETED_ON, ( SELECT CONCAT(USERS.USR_FIRSTNAME, ' ', USERS.USR_LASTNAME) FROM USERS WHERE USERS.USR_UID=A.APP_INIT_USER) AS OWNER, ( SELECT CONCAT(USERS.USR_FIRSTNAME, ' ', USERS.USR_LASTNAME) FROM USERS WHERE USERS.USR_UID=AD.USR_UID ) AS ASSIGNEE FROM APP_CACHE_VIEW AC LEFT JOIN APPLICATION A ON A.APP_UID=AC.APP_UID LEFT JOIN APP_DELEGATION AD ON AD.APP_UID=AC.APP_UID";
        if (!empty($dateFrom)) {
            $sqlData .= " AND AC.DEL_DELEGATE_DATE >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            // $dateTo = $dateTo . " 23:59:59";
            $sqlData .= " AND AC.DEL_DELEGATE_DATE <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND AC.PRO_UID IN ('" . $process . "')";
        }
        $sqlData .= " GROUP BY date(AC.DEL_DELEGATE_DATE),AC.APP_UID";
        //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        return $rows;
    }

    public function executeRawSqlQuery($sql)
    {
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sql);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        return $rows;
    }

    public function getCaseDetailsVersion2ForESSync(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $assignee_list = null,
        $case_id = null,
        $mysql_connection = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'AC.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'AC.APP_CREATE_DATE';
                break;
        }
        $status  = strtoupper($status);
        $sqlData = "SELECT
        A.APP_INIT_USER as initiator,
        AC.APP_UID as caseId,
        AC.PRO_UID AS processId,
        AC.APP_NUMBER AS caseNumber,
        ACD.tas_uid AS taskId,
        CASE
        WHEN A.APP_STATUS='DRAFT' THEN 'true'
        ELSE 'false'
        END AS isDraft,
        A.APP_DATA AS APP_DATA,
        CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) as InitiatorName,
        A.APP_STATUS AS status,
        DATE_FORMAT(IF(A.APP_INIT_DATE IS NOT NULL AND A.APP_INIT_DATE !='', A.APP_INIT_DATE,NULL), '%Y-%m-%dT%TZ') AS startedAt,
        DATE_FORMAT(IF(A.APP_CREATE_DATE IS NOT NULL AND A.APP_CREATE_DATE !='', A.APP_CREATE_DATE,NULL), '%Y-%m-%dT%TZ') AS createdAt,
        DATE_FORMAT(IF(A.APP_UPDATE_DATE IS NOT NULL AND A.APP_UPDATE_DATE !='', A.APP_UPDATE_DATE,NULL), '%Y-%m-%dT%TZ') AS modifiedAt,
        DATE_FORMAT(IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,NULL), '%Y-%m-%dT%TZ') AS completedAt,
        PR.PRO_TITLE AS processName,
        A.APP_TITLE  AS caseName,
        ACD.assignee_user_id AS assignedUsers,
        ACD.isTask AS isTask,
        DATE_FORMAT(IF(ACD.due_by IS NOT NULL AND ACD.due_by !='', ACD.due_by,NULL), '%Y-%m-%dT%TZ') AS dueDate,
            -- DATE_FORMAT(IF(ACD.due_by IS NOT NULL AND ACD.due_by !='', ACD.due_by,NULL), '%Y-%m-%dT%TZ') AS dueBy,
            -- IF(A.APP_CREATE_DATE IS NOT NULL AND A.APP_CREATE_DATE !='', A.APP_CREATE_DATE,NULL) as create_date,
            -- IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,NULL) as completedAt,
            -- A.APP_INIT_DATE AS start_date,
            -- ACD.assignee_count,
            -- IF(ACD.assignee IS NOT NULL AND ACD.assignee !='', ACD.assignee,'NA') AS assignee,
            ACD.app_tas_title as currentStep,
            ACD.completion AS completion,
            ACD.delayed_by AS delayedBy
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER=U.USR_UID)
            LEFT JOIN PROCESS PR ON AC.PRO_UID=PR.PRO_UID
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AGTD.DEL_TASK_DUE_DATE AS curr_step_max_del_task_due_date,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.wfs_case_title AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            GTDD.assignee_count AS assignee_count,
            GTDD.assignee,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE null
            END as completion,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by,
            IF((SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), 'true','false') AS isTask,
            CASE
            WHEN AGTD.APP_STATUS='COMPLETED'
            THEN
            (SELECT(IF((TIMESTAMPDIFF(SECOND, AUU.app_finish_date, AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Minutes ',
            SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Seconds'
            ), null)))
            WHEN AGTD.APP_STATUS='TO_DO'
            THEN
            (SELECT(IF((TIMESTAMPDIFF(SECOND, NOW(), AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Minutes ',
            SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Seconds'
            ),null)))
            ELSE null
            END
            as delayed_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS assignee_count,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
            GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'
            GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            common_schema.unserialize_column(APP_DATA, 'WFS_IS_TASK') as wfs_is_task,
            common_schema.unserialize_column(APP_DATA, 'WFS_CASE_TITLE') as wfs_case_title
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE
            AC.APP_STATUS IN ('COMPLETED','TO_DO','DRAFT')";
        if (!empty($search)) {
            $sqlData .= " AND (
            AC.APP_NUMBER LIKE '%{$search}%'
            OR ACD.wfs_case_title LIKE '%{$search}%'
            OR A.APP_STATUS LIKE '%{$search}%'
            OR ACD.app_tas_title LIKE '%{$search}%'
            OR A.APP_CREATE_DATE LIKE '%{$search}%'
            OR A.APP_FINISH_DATE LIKE '%{$search}%'
            OR ACD.curr_step_max_del_task_due_date LIKE '%{$search}%'
        )";
        }
        if (!empty($dateFrom)) {
            $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $sqlData .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $sqlData .= " AND AC.USR_UID IN (" . $assignee_list . ")";
        }
        if (!empty($current_step)) {
            $sqlData .= " AND ACD.tas_uid='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
            } else {
                $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $sqlData .= " AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1')";
            $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        if (!empty($case_id)) {
            $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
        }
        $sqlData .= " GROUP BY AC.PRO_UID,AC.APP_UID";
        $sqlData .= " ORDER BY $filterDateType DESC";
        //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }
        $rows    = [];
        $dataset = $mysql_connection->query($sqlData);
        if ($dataset) {
            if ($dataset->num_rows > 0) {
                while ($row = $dataset->fetch_assoc()) {
                    $row['currentStep'] = @explode('$$', $row['currentStep'])[0];
                    $rows[]             = $row;
                }
            }
            $dataset->close();
        }
        $result['case_details'] = $rows;
        return $result;
    }

    public function getFormDetailsVersion2ForESSync(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $case_id = null,
        $assignee_list = null,
        $mysql_connection = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'AC.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'AC.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'AC.APP_CREATE_DATE';
                break;
        }
        $status = strtoupper($status);
        if (!empty($task)) {
            $sqlData = "SELECT
            AC.APP_UID,
            AC.PRO_UID,
            AC.APP_NUMBER AS case_id,
            CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) AS owner,
            A.APP_INIT_USER as owner_user_id,
            AC.USR_UID as assignee_user_id,
            IF(CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) IS NOT NULL AND CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) !='', CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)),null) AS assignee,
            CASE
            WHEN ((AC.PREVIOUS_USR_UID !='' OR AC.PREVIOUS_USR_UID IS NOT NULL) AND (AC.DEL_INIT_DATE IS NULL OR AC.DEL_INIT_DATE='') AND (AC.DEL_FINISH_DATE IS NOT NULL OR AC.DEL_FINISH_DATE!='') AND AC.DEL_THREAD_STATUS='CLOSED') THEN 'true'
            ELSE 'false'
            END AS is_delegated,
            CASE
            WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
            WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
            END AS status,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN') THEN 'In Progress'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED') THEN 'Completed'
            ELSE null
            END AS user_status,
            CASE
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AC.DEL_THREAD_STATUS='OPEN' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE) < 0)) THEN 'delayed'
            WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE null
            END AS user_completion,
            ACD.completion,
            ACD.app_tas_title as current_step,
            CASE
            WHEN AC.APP_STATUS='COMPLETED'
            THEN
            IF((TIMESTAMPDIFF(SECOND, AC.DEL_FINISH_DATE, AC.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') ), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), ' Minutes ',
            SECOND(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), ' Seconds'
            ),null)
            WHEN AC.APP_STATUS='TO_DO'
            THEN
            IF((TIMESTAMPDIFF(SECOND, NOW(), AC.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') ), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), ' Minutes ',
            SECOND(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), ' Seconds'
            ),null)
            ELSE null
            END
            AS user_delayed_by,
            ACD.delayed_by AS delayed_by,
            ACD.due_by AS due_by,
            ACD.due_by as case_due_by,
            AC.DEL_TASK_DUE_DATE as user_case_due_by,
            IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,'') as completed_on,
            IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,'') as user_completed_on,
            IF(A.APP_CREATE_DATE IS NOT NULL AND A.APP_CREATE_DATE !='', A.APP_CREATE_DATE,'') as create_date,
            A.APP_INIT_DATE AS start_date,
            AC.DEL_DELEGATE_DATE AS delegate_date,
            AC.DEL_INIT_DATE AS user_init_date,
            AC.TAS_UID as task_uid,
            AC.APP_TAS_TITLE as user_current_step,
            A.APP_DATA
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN USERS U ON (A.APP_INIT_USER = U.USR_UID)
            LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AGTD.DEL_TASK_DUE_DATE AS curr_step_max_del_task_due_date,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.wfs_case_title AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            GTDD.assignee_count AS assignee_count,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE null
            END as completion,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by,
            CASE
            WHEN AGTD.APP_STATUS='COMPLETED'
            THEN
            (SELECT(IF((TIMESTAMPDIFF(SECOND, AUU.app_finish_date, AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Minutes ',
            SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Seconds'
            ),null)))
            WHEN AGTD.APP_STATUS='TO_DO'
            THEN
            (SELECT(IF((TIMESTAMPDIFF(SECOND, NOW(), AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
            FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))) / 24), ' Days ',
            MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), 24), ' Hours ',
            MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Minutes ',
            SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Seconds'
            ),null)))
            ELSE null
            END
            as delayed_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            COUNT(AD.USR_UID) AS assignee_count,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
            GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'
            GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            common_schema.unserialize_column(APP_DATA, 'WFS_IS_TASK') as wfs_is_task,
            common_schema.unserialize_column(APP_DATA, 'WFS_CASE_TITLE') as wfs_case_title
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE 1";
            if (!empty($dateFrom)) {
                $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
            }
            if (!empty($dateTo)) {
                $sqlData .= " AND $filterDateType <= '{$dateTo}'";
            }
            if (!empty($process)) {
                $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
            }
            if (!empty($owner_id)) {
                $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
            }
            if (!empty($assignee_list)) {
                $sqlData .= " AND AC.USR_UID IN (" . $assignee_list . ")";
            }
            if (!empty($case_id)) {
                $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
            }
            if (!empty($task)) {
                $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
            }
            $sqlData .= " AND AC.APP_STATUS IN ('COMPLETED', 'TO_DO', 'DRAFT')";
            if (!empty($search)) {
                $sqlData .= " AND (
                    AC.APP_NUMBER LIKE '%{$search}%'
                    OR ACD.wfs_case_title LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
            }
            if (!empty($status)) {
                if ($status == 'UNASSIGNED') {
                    $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
                } else {
                    $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
                }
            }
            if (!empty($completion)) {
                $sqlData .= " AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1')";
                $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
            }
            $sqlData .= " GROUP BY A.PRO_UID,A.APP_UID,AC.TAS_UID,AC.USR_UID,AC.DEL_INDEX";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
            if (empty($limit)) {
                $limit = 25;
            }
            if (!empty($start)) {
                $sqlData .= " LIMIT $start, " . $limit;
            } else {
                $sqlData .= " LIMIT " . $limit;
            }
            $rows    = [];
            $dataset = $mysql_connection->query($sqlData);
            if ($dataset) {
                if ($dataset->num_rows > 0) {
                    while ($row = $dataset->fetch_assoc()) {
                        $row['form_data'] = null;
                        $rows[]           = $row;
                    }
                }
                $dataset->close();
            }
            $result['form_details'] = $rows;
        } else {
            $sqlData = "SELECT
        AC.APP_UID,
        AC.PRO_UID,
        AC.APP_NUMBER AS case_id,
        CONCAT(U.USR_FIRSTNAME, ' ', U.USR_LASTNAME) AS owner,
        A.APP_INIT_USER as owner_user_id,
        AC.USR_UID as assignee_user_id,
        CONCAT(SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', -1),' ',SUBSTRING_INDEX(AC.APP_CURRENT_USER, ' ', 1)) AS assignee,
        CASE
        WHEN ((AC.PREVIOUS_USR_UID !='' OR AC.PREVIOUS_USR_UID IS NOT NULL) AND (AC.DEL_INIT_DATE IS NULL OR AC.DEL_INIT_DATE='') AND (AC.DEL_FINISH_DATE IS NOT NULL OR AC.DEL_FINISH_DATE!='') AND AC.DEL_THREAD_STATUS='CLOSED') THEN 'true'
        ELSE 'false'
        END AS is_delegated,
        CASE
        WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
        WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
        END AS status,
        CASE
        WHEN (AC.DEL_THREAD_STATUS='OPEN') THEN 'In Progress'
        WHEN (AC.DEL_THREAD_STATUS='CLOSED') THEN 'Completed'
        ELSE ''
        END AS user_status,
        CASE
        WHEN (AC.DEL_THREAD_STATUS='OPEN' AND  (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
        WHEN (AC.DEL_THREAD_STATUS='OPEN' AND  (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
        WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND  (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE) < 0)) THEN 'delayed'
        WHEN (AC.DEL_THREAD_STATUS='CLOSED' AND  (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AC.DEL_FINISH_DATE,AC.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
        ELSE null
        END AS user_completion,
        ACD.completion,
        ACD.app_tas_title as current_step,
        CASE
        WHEN AC.APP_STATUS='COMPLETED'
        THEN
        IF((TIMESTAMPDIFF(SECOND, AC.DEL_FINISH_DATE, AC.DEL_TASK_DUE_DATE) < 0 AND  (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
        FLOOR(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))) / 24), ' Days ',
        MOD(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), 24), ' Hours ',
        MINUTE(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), ' Minutes ',
        SECOND(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,AC.DEL_FINISH_DATE)))), ' Seconds'
        ),null)
        WHEN AC.APP_STATUS='TO_DO'
        THEN
        IF((TIMESTAMPDIFF(SECOND, NOW(), AC.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
        FLOOR(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))) / 24), ' Days ',
        MOD(HOUR(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), 24), ' Hours ',
        MINUTE(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), ' Minutes ',
        SECOND(SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND,AC.DEL_TASK_DUE_DATE,NOW())))), ' Seconds'
        ),null)
        ELSE null
        END
        AS user_delayed_by,
        ACD.delayed_by AS delayed_by,
        ACD.due_by AS due_by,
        ACD.due_by as case_due_by,
        AC.DEL_TASK_DUE_DATE as user_case_due_by,
        IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,'') as completed_on,
        IF(AC.DEL_FINISH_DATE IS NOT NULL AND AC.DEL_FINISH_DATE !='', AC.DEL_FINISH_DATE,'') as user_completed_on,
        IF(A.APP_CREATE_DATE IS NOT NULL AND A.APP_CREATE_DATE !='', A.APP_CREATE_DATE,'') as create_date,
        A.APP_INIT_DATE AS start_date,
        AC.DEL_DELEGATE_DATE AS delegate_date,
        AC.DEL_INIT_DATE AS user_init_date,
        AC.TAS_UID as task_uid,
        AC.APP_TAS_TITLE as user_current_step,
        A.APP_DATA
        FROM
        APP_CACHE_VIEW AC
        LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
        LEFT JOIN USERS U ON (A.APP_INIT_USER = U.USR_UID)
        LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
        LEFT JOIN (SELECT
        AGTD.APP_UID,
        AGTD.APP_STATUS,
        AGTD.DEL_INDEX,
        AGTD.TAS_UID AS tas_uid,
        AGTD.APP_TAS_TITLE AS app_tas_title,
        AGTD.DEL_TASK_DUE_DATE AS curr_step_max_del_task_due_date,
        AUU.wfs_is_task AS wfs_is_task,
        AUU.wfs_case_title AS wfs_case_title,
        AUU.app_finish_date AS app_finish_date,
        GTDD.assignee_user_id AS assignee_user_id,
        GTDD.assignee_count AS assignee_count,
        CASE
        WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
        WHEN (AGTD.APP_STATUS='TO_DO' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
        WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
        THEN 'delayed'
        WHEN (AGTD.APP_STATUS='COMPLETED' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
        ELSE null
        END as completion,
        IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
        due_by,
        CASE
        WHEN AGTD.APP_STATUS='COMPLETED'
        THEN
        (SELECT(IF((TIMESTAMPDIFF(SECOND, AUU.app_finish_date, AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
        FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))) / 24), ' Days ',
        MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), 24), ' Hours ',
        MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Minutes ',
        SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,AUU.app_finish_date)))), ' Seconds'
        ),null)))
        WHEN AGTD.APP_STATUS='TO_DO'
        THEN
        (SELECT(IF((TIMESTAMPDIFF(SECOND, NOW(), AGTD.DEL_TASK_DUE_DATE) < 0 AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1')), CONCAT(
        FLOOR(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))) / 24), ' Days ',
        MOD(HOUR(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), 24), ' Hours ',
        MINUTE(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Minutes ',
        SECOND(SEC_TO_TIME((TIMESTAMPDIFF(SECOND,AGTD.DEL_TASK_DUE_DATE,NOW())))), ' Seconds'
        ),null)))
        ELSE null
        END
        as delayed_by
        FROM APP_CACHE_VIEW AGTD
        LEFT JOIN ( SELECT
        AD.APP_UID,
        AD.APP_NUMBER,
        AD.DEL_INDEX,
        COUNT(AD.USR_UID) AS assignee_count,
        GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
        GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
        FROM APP_DELEGATION AD
        INNER JOIN ( SELECT
        APP_UID,
        MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
        FROM APP_DELEGATION
        GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
        LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
        LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
        WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'
        GROUP BY AD.APP_UID
        )
        GTDD ON AGTD.APP_UID=GTDD.APP_UID
        INNER JOIN ( SELECT
        APP_UID AS GTD_APP_UID,
        MAX(DEL_INDEX) AS GTD_DEL_INDEX
        FROM APP_CACHE_VIEW
        GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
        LEFT JOIN (SELECT
        APP_UID,
        APP_FINISH_DATE AS app_finish_date,
        common_schema.unserialize_column(APP_DATA, 'WFS_IS_TASK') as wfs_is_task,
        common_schema.unserialize_column(APP_DATA, 'WFS_CASE_TITLE') as wfs_case_title
        FROM APPLICATION
        GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
        GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
        ) AS ACD ON ACD.APP_UID=AC.APP_UID
        WHERE 1";
            if (!empty($dateFrom)) {
                $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
            }
            if (!empty($dateTo)) {
                $sqlData .= " AND $filterDateType <= '{$dateTo}'";
            }
            if (!empty($process)) {
                $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
            }
            if (!empty($owner_id)) {
                $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
            }
            if (!empty($assignee_list)) {
                $sqlData .= " AND AC.USR_UID IN (" . $assignee_list . ")";
            }
            if (!empty($case_id)) {
                $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
            }
            if (!empty($task)) {
                $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
            }
            $sqlData .= " AND A.APP_STATUS IN ('COMPLETED', 'TO_DO', 'DRAFT')";
            if (!empty($search)) {
                $sqlData .= " AND (
                    AC.APP_NUMBER LIKE '%{$search}%'
                    OR ACD.wfs_case_title LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
            }
            if (!empty($status)) {
                if ($status == 'UNASSIGNED') {
                    $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
                } else {
                    $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
                }
            }
            if (!empty($completion)) {
                $sqlData .= " AND (SUBSTRING_INDEX(SUBSTRING_INDEX(ACD.wfs_is_task, ';}' , 1),':',-1)='1')";
                $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
            }
            $sqlData .= " GROUP BY A.PRO_UID,A.APP_UID,AC.TAS_UID,AC.USR_UID,AC.DEL_INDEX";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
            if (empty($limit)) {
                $limit = 25;
            }
            if (!empty($start)) {
                $sqlData .= " LIMIT $start, " . $limit;
            } else {
                $sqlData .= " LIMIT " . $limit;
            }
            $rows    = [];
            $dataset = $mysql_connection->query($sqlData);
            if ($dataset) {
                if ($dataset->num_rows > 0) {
                    while ($row = $dataset->fetch_assoc()) {
                        $row['form_data'] = null;
                        $rows[]           = $row;
                    }
                }
                $dataset->close();
            }
            $result['form_details'] = $rows;
        }
        return $result;
    }

    public function doGetProcessDetails($pro_uid)
    {
        //Start the connection to database
        $con = Propel::getConnection(AppDelegationPeer::DATABASE_NAME);
        // check for transaction if performance issues occur
        if (empty($pro_uid) || empty($con)) {
            return false;
        }
        $sql = "Select PRO_UID,PRO_TITLE from PROCESS where PRO_UID = '$pro_uid'";
        $rs  = $con->executeQuery($sql);
        $rs->next();
        return $row = $rs->getRow();
    }

    public function getProcessTaskDetailsVersion2(
        $process = ''
    ) {
        //Sanitize input variables
        $inputFilter = new InputFilter();

        $processStepsSql = "SELECT TAS_UID, TAS_TITLE FROM TASK WHERE PRO_UID IN (" . $process . ") AND TAS_UID NOT LIKE '%gtg-%' ORDER BY TAS_START DESC";
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($processStepsSql);
        $rows    = [];
        while ($dataset->next()) {
            $row    = $dataset->getRow();
            $rows[] = $row;
        }
        $result['process_steps'] = $rows;

        return $result;
    }

    public function getCaseDetailsVersion2ExcelData(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $assignee_list = null,
        $case_id = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'ACD.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'ACD.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'ACD.APP_CREATE_DATE';
                break;
        }
        $status  = strtoupper($status);
            $totalCountSql = "SELECT
            COUNT(DISTINCT ACD.APP_UID) as total_rows
            FROM 
            (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_STATUS,
            AGTD.APP_UPDATE_DATE,
            AGTD.APP_CREATE_DATE,
            AGTD.DEL_INDEX,
            AGTD.APP_NUMBER,
            AGTD.USR_UID,
            AGTD.PREVIOUS_USR_UID,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee_user_id AS assignee_user_id,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task= '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.DEL_INDEX,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

        if (!empty($assignee_list)) {
            $totalCountSql .= " AND AD.USR_UID IN (" . $assignee_list . ")";
        }

            $totalCountSql .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD
            LEFT JOIN APPLICATION A ON ACD.APP_UID=A.APP_UID
            WHERE
            ACD.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $totalCountSql .= " AND (
                    ACD.APP_NUMBER LIKE '%{$search}%'
                    OR A.APP_TITLE LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $totalCountSql .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $totalCountSql .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $totalCountSql .= " AND ACD.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $totalCountSql .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $totalCountSql .= " AND ACD.assignee_user_id IS NOT NULL";
        }
        if (!empty($current_step)) {
            $totalCountSql .= " AND ACD.tas_uid='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $totalCountSql .= " AND ACD.USR_UID = '' AND ACD.PREVIOUS_USR_UID !=''";
            } else {
                $totalCountSql .= " AND ACD.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $totalCountSql .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        if (!empty($case_id)) {
            $totalCountSql .= " AND ACD.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $totalCountSql .= " AND ACD.tas_uid IN (" . $task . ")";
        }
            $totalCountSql .= " GROUP BY ACD.PRO_UID";
        
        

            $sqlData = "SELECT
            A.APP_UID AS APP_UID,
            ACD.APP_NUMBER AS case_id,
            'case_title' AS case_title,
            A.APP_DATA AS APP_DATA,
            A.APP_INIT_USER as owner,
            ACD.assignee_user_id AS assignee,
            CASE
            WHEN A.APP_STATUS='TO_DO' THEN 'In Progress'
            WHEN A.APP_STATUS='COMPLETED' THEN 'Completed'
            END AS status,
            ACD.app_tas_title as current_step,
            A.APP_CREATE_DATE as create_date,
            IF (ACD.due_by IS NOT NULL, ACD.due_by, '-') AS due_by,
            ACD.completion AS completion,
            IF(A.APP_FINISH_DATE IS NOT NULL AND A.APP_FINISH_DATE !='', A.APP_FINISH_DATE,'-') as completed_on

            FROM
            (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_NUMBER,
            AGTD.APP_UPDATE_DATE,
            AGTD.APP_CREATE_DATE,
            AGTD.APP_STATUS,
            AGTD.USR_UID,
            AGTD.PREVIOUS_USR_UID,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.APP_TITLE AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            GTDD.assignee,
            GTDD.assignee_user_id as assignee_user_id,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task = '1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (AUU.wfs_is_task = '1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX,
            GROUP_CONCAT(IF(AD.USR_UID='', '', AD.USR_UID)) AS assignee_user_id,
            GROUP_CONCAT(IF(APT.APP_CURRENT_USER='', '', APT.APP_CURRENT_USER)) as assignee
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

        if (!empty($assignee_list)) {
            $sqlData .= " AND AD.USR_UID IN (" . $assignee_list . ")";
        }

            $sqlData .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_TITLE,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD
            LEFT JOIN APPLICATION A ON ACD.APP_UID=A.APP_UID
            WHERE
            ACD.APP_STATUS IN ('COMPLETED','TO_DO')";
        if (!empty($search)) {
            $sqlData .= " AND (
                    ACD.APP_NUMBER LIKE '%{$search}%'
                    OR ACD.wfs_case_title LIKE '%{$search}%'
                    OR ACD.app_tas_title LIKE '%{$search}%'
                )";
        }
        if (!empty($dateFrom)) {
            $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
        }
        if (!empty($dateTo)) {
            $sqlData .= " AND $filterDateType <= '{$dateTo}'";
        }
        if (!empty($process)) {
            $sqlData .= " AND ACD.PRO_UID IN (" . $process . ")";
        }
        if (!empty($owner_id)) {
            $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
        }
        if (!empty($assignee_list)) {
            $sqlData .= " AND ACD.assignee_user_id IS NOT NULL";
        }
            
        if (!empty($current_step)) {
            $sqlData .= " AND ACD.tas_uid='" . $current_step . "'";
        }
        if (!empty($status)) {
            if ($status == 'UNASSIGNED') {
                $sqlData .= " AND ACD.USR_UID = '' AND ACD.PREVIOUS_USR_UID !=''";
            } else {
                $sqlData .= " AND ACD.APP_STATUS='" . $status . "'";
            }
        }
        if (!empty($completion)) {
            $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
        }
        if (!empty($case_id)) {
            $sqlData .= " AND ACD.APP_UID IN (" . $case_id . ")";
        }
        if (!empty($task)) {
            $sqlData .= " AND ACD.TAS_UID IN (" . $task . ")";
        }
            $sqlData .= " GROUP BY ACD.PRO_UID,ACD.APP_UID";
            $sqlData .= " ORDER BY $filterDateType DESC";
            //Define the number of records by return
        if (empty($limit)) {
            $limit = 25;
        }
        if (!empty($start)) {
            $sqlData .= " LIMIT $start, " . $limit;
        } else {
            $sqlData .= " LIMIT " . $limit;
        }
        
        $stmt    = self::$connObj->createStatement();
        $dataset = $stmt->executeQuery($sqlData);
        $rows    = [];
        while ($dataset->next()) {
            $row                 = $dataset->getRow();
            $row['current_step'] = @explode('$$', $row['current_step'])[0];
            $rows[]              = $row;
        }
        $result['case_details'] = $rows;


        $stmt                   = self::$connObj->createStatement();
        $dataset                = $stmt->executeQuery($totalCountSql);
        $result['case_details_total_rows'] = 0;
        while ($dataset->next()) {
            $row = $dataset->getRow();
            $result['case_details_total_rows'] = isset($row['total_rows']) ? $row['total_rows'] : 0;
        }
        return $result;
    }


    public function getFormDetailsVersion2ExcelData(
        $start = null,
        $limit = null,
        $dateFrom = null,
        $dateTo = null,
        $process = null,
        $search = null,
        $task = null,
        $owner_id = null,
        $status = null,
        $completion = null,
        $current_step = null,
        $case_id = null,
        $assignee_list = null
    ) {
        //Sanitize input variables
        $inputFilter  = new InputFilter();
        $start        = (int) $inputFilter->validateInput($start, 'int');
        $limit        = (int) $inputFilter->validateInput($limit, 'int');
        $search       = $inputFilter->escapeUsingConnection($search, self::$connObj);
        $caseListRows = null;
        $completion   = preg_replace('/\s+/', '', strtolower($completion));
        $status       = preg_replace('/\s+/', '', strtolower($status));
        // Deduce the process instance status using completion
        if ($completion == 'overdue' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'ontrack' && empty($status)) {
            $status = 'to_do';
        } elseif ($completion == 'delayed' && empty($status)) {
            $status = 'completed';
        } elseif (($completion == 'ontime') && empty($status)) {
            $status = 'completed';
        }
        // Deduce the default date on which ordering needs to be performedon the resultset
        $filterDateType = '';
        switch ($status) {
            case 'to_do':
            case 'unassigned':
                $filterDateType = 'ACD.APP_UPDATE_DATE';
                break;
            case 'completed':
                $filterDateType = 'ACD.APP_FINISH_DATE';
                break;
            default:
                $filterDateType = 'ACD.APP_CREATE_DATE';
                break;
        }
        $status              = strtoupper($status);
        if (!empty($task)) {
            $sqlData = "SELECT
            AC.APP_UID,
            AC.TAS_UID AS task_uid,
            AC.APP_NUMBER AS case_id,
            'case_title' AS case_title,
            A.APP_INIT_USER as owner,
            AC.USR_UID as assignee
            
            FROM
            APP_CACHE_VIEW AC
            LEFT JOIN APPLICATION A ON AC.APP_UID=A.APP_UID
            LEFT JOIN TASK T ON (AC.TAS_UID = T.TAS_UID)
            LEFT JOIN (SELECT
            AGTD.APP_UID,
            AGTD.PRO_UID,
            AGTD.APP_STATUS,
            AGTD.DEL_INDEX,
            AGTD.APP_CREATE_DATE,
            AGTD.APP_UPDATE_DATE,
            AGTD.TAS_UID AS tas_uid,
            AGTD.APP_TAS_TITLE AS app_tas_title,
            AUU.wfs_is_task AS wfs_is_task,
            AUU.wfs_case_title AS wfs_case_title,
            AUU.app_finish_date AS app_finish_date,
            CASE
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE) < 0)) THEN 'overdue'
            WHEN (AGTD.APP_STATUS='TO_DO' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,NOW(),AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontrack'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE) < 0))
            THEN 'delayed'
            WHEN (AGTD.APP_STATUS='COMPLETED' AND (AUU.wfs_is_task='1') AND (TIMESTAMPDIFF(SECOND,AUU.app_finish_date,AGTD.DEL_TASK_DUE_DATE)>= 0)) THEN 'ontime'
            ELSE 'NA'
            END as completion,
            IF(AGTD.DEL_TASK_DUE_DATE IS NOT NULL AND AGTD.DEL_TASK_DUE_DATE !='' AND (SUBSTRING_INDEX(SUBSTRING_INDEX(AUU.wfs_is_task, ';}' , 1),':',-1)='1'), AGTD.DEL_TASK_DUE_DATE,NULL) as
            due_by
            FROM APP_CACHE_VIEW AGTD
            LEFT JOIN ( SELECT
            AD.APP_UID,
            AD.APP_NUMBER,
            AD.DEL_INDEX
            FROM APP_DELEGATION AD
            INNER JOIN ( SELECT
            APP_UID,
            MAX(DEL_PREVIOUS) AS DEL_PREVIOUS
            FROM APP_DELEGATION
            GROUP BY APP_UID ) ADDD ON AD.APP_UID=ADDD.APP_UID AND AD.DEL_PREVIOUS=ADDD.DEL_PREVIOUS
            LEFT JOIN APP_DELAY APD ON AD.APP_UID=APD.APP_UID AND AD.DEL_INDEX = APD.APP_DEL_INDEX AND APD.APP_TYPE ='REASSIGN'
            LEFT JOIN APP_CACHE_VIEW APT ON AD.APP_UID=APT.APP_UID AND AD.DEL_INDEX=APT.DEL_INDEX
            WHERE APD.APP_UID IS NULL AND AD.DEL_INDEX !='1'";

            if (!empty($assignee_list)) {
                $sqlData .= " AND AD.USR_UID IN (" . $assignee_list . ")";
            }

            $sqlData .= " GROUP BY AD.APP_UID
            )
            GTDD ON AGTD.APP_UID=GTDD.APP_UID
            INNER JOIN ( SELECT
            APP_UID AS GTD_APP_UID,
            MAX(DEL_INDEX) AS GTD_DEL_INDEX
            FROM APP_CACHE_VIEW
            GROUP BY APP_UID ) GTD ON AGTD.APP_UID=GTD.GTD_APP_UID AND AGTD.DEL_INDEX=GTD.GTD_DEL_INDEX
            LEFT JOIN (SELECT
            APP_UID,
            APP_FINISH_DATE AS app_finish_date,
            LEFT( SUBSTRING_INDEX(APP_DATA, 'WFS_IS_TASK\";a:1:{i:0;i:' , -1 ), 1) AS wfs_is_task,
            APP_TITLE AS wfs_case_title
            FROM APPLICATION
            GROUP BY APP_UID) AUU ON AGTD.APP_UID=AUU.APP_UID
            GROUP BY AGTD.APP_UID,AGTD.DEL_INDEX
            ) AS ACD ON ACD.APP_UID=AC.APP_UID
            WHERE 1";

            if (!empty($dateFrom)) {
                $sqlData .= " AND $filterDateType >= '{$dateFrom}'";
            }
            if (!empty($dateTo)) {
                $sqlData .= " AND $filterDateType <= '{$dateTo}'";
            }
            if (!empty($process)) {
                $sqlData .= " AND AC.PRO_UID IN (" . $process . ")";
            }
            if (!empty($owner_id)) {
                $sqlData .= " AND A.APP_INIT_USER IN (" . $owner_id . ")";
            }
            // if (!empty($assignee_list)) {
            //     $sqlData .= " AND ACD.assignee_user_id IS NOT NULL";
            // }
            if (!empty($case_id)) {
                $sqlData .= " AND AC.APP_UID IN (" . $case_id . ")";
            }
            if (!empty($task)) {
                $sqlData .= " AND AC.TAS_UID IN (" . $task . ")";
            }
                $sqlData .= " AND AC.APP_STATUS IN ('COMPLETED', 'TO_DO')";
            if (!empty($search)) {
                $sqlData .= " AND (
                        AC.APP_NUMBER LIKE '%{$search}%'
                        OR ACD.wfs_case_title LIKE '%{$search}%'
                        OR ACD.app_tas_title LIKE '%{$search}%'
                    )";
            }
            if (!empty($status)) {
                if ($status == 'UNASSIGNED') {
                    $sqlData .= " AND AC.USR_UID = '' AND AC.PREVIOUS_USR_UID !=''";
                } else {
                    $sqlData .= " AND AC.APP_STATUS='" . $status . "'";
                }
            }
            if (!empty($completion)) {
                $sqlData .= " AND (ACD.wfs_is_task = '1')";
                $sqlData .= " AND ACD.completion  LIKE '%" . $completion . "%'";
            }
                $sqlData .= " GROUP BY A.PRO_UID,A.APP_UID,AC.TAS_UID,AC.USR_UID,AC.DEL_INDEX";
                $sqlData .= " ORDER BY $filterDateType DESC";
                //Define the number of records by return
            if (empty($limit)) {
                $limit = 25;
            }
            if (!empty($start)) {
                $sqlData .= " LIMIT $start, " . $limit;
            } else {
                $sqlData .= " LIMIT " . $limit;
            }
            
            $stmt    = self::$connObj->createStatement();
            $dataset = $stmt->executeQuery($sqlData);
            $result['form_details'] = [];
            while ($dataset->next()) {
                $row              = $dataset->getRow();
                $row['form_data'] = null;
                $result['form_details'][]           = $row;
            }
        }
        return $result;
    }


    public function __destruct()
    {
        if (self::$connObj !== null) {
            self::$connObj->close();
            self::$connObj = null;
        }
    }
}
