<?php

namespace ProcessMaker\BusinessModel;

use AppDocumentPeer;
use AppHistoryPeer;
use Applications;
use AppSolr;
use Cases as ClassesCases;
use Criteria;
use Exception;
use G;
use Luracast\Restler\RestException;
use PmDynaform;
use ProcessMaker\BusinessModel\DynaForm as BmDynaform;
use ProcessMaker\BusinessModel\Process;
use ProcessMaker\BusinessModel\Task as BmTask;
use ProcessMaker\BusinessModel\Validator as Validator;
use ProcessMaker\Core\System;
use ProcessMaker\Services\Api;
use ResultSet;
use Users as ModelUsers;
use ProcessMaker\Plugins\PluginRegistry;
use ProcessMaker\Services\OAuth2\Server;
use Illuminate\Support\Facades\Cache;

class Cases
{
    const MB_IN_KB = 1024;

    const UNIT_MB = 'MB';

    private static $appsObj = null;

    private static $formatFieldNameInUppercase = true;

    private static $instance = null;

    private static $messageResponse = [];

    private static $processObj = null;

    private static $solr = null;

    private static $solrEnv = null;

    private static $process_completion = [
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

    private static $process_completion_variables = [
        'ontime' => 'On Time',
        'delayed' => 'Delayed',
        'ontrack' => 'On Track',
        'overdue' => 'Overdue',
        '-'      => '-'
    ];

    private static $user_details_keys = [
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

    private static $case_summary_keys = [
        'APP_UID',
        'PRO_UID',
        'case_id',
        'case_title',
        'assignee_count',
        'user_completed_count',
        'due_by',
        'completed_on',
    ];

    private static $system_variable = ['SYS_LANG', 'SYS_SKIN', 'SYS_SYS', 'SYS_VAR_UPDATE_DATE', 'PROCESS', 'TASK', 'APPLICATION', 'APP_NUMBER', 'USER_LOGGED', 'USR_USERNAME', 'INDEX', 'PIN', '__ERROR__', '__VAR_CHANGED__'];

    public static function &getSingleton()
    {
        if (self::$instance == null) {
            self::$instance = new Cases();
        }
        return self::$instance;
    }

    public function __destruct()
    {
        self::$appsObj = null;
        self::$user_details_keys  = null;
        self::$case_summary_keys  = null;
        self::$process_completion  = null;
    }

    public function doGetDynaForms($prj_uid)
    {
        try {
            self::$processObj->setFormatFieldNameInUppercase(false);
            self::$processObj->setArrayFieldNameForException(array("processUid" => "prj_uid"));
            $_SESSION['PROCESS'] = $prj_uid;
            $response            = self::$processObj->getDynaForms($prj_uid);
            $result              = $this->parserDataDynaForm($response);
            $pmDynaForm          = new PmDynaform();
            foreach ($result as $k => $form) {
                $result[$k]['formContent'] = (isset($form['formContent']) && $form['formContent'] != null) ? json_decode($form['formContent']) : "";
                $pmDynaForm->jsonr($result[$k]['formContent']);
                $result[$k]['index'] = $k;
            }
        } catch (Exception $e) {
            throw $e;
        }
        return $result;
    }

    public function doGetProcessDetails($pro_uid = '')
    {
        $response = self::$appsObj->doGetProcessDetails(
            $pro_uid
        );
        if (!empty($response['data'])) {
            foreach ($response['data'] as &$value) {
                $value = array_change_key_case($value, CASE_LOWER);
            }
        }
        return $response;
    }

    public function executeRawSqlQuery($sql)
    {
        $result = self::$appsObj->executeRawSqlQuery($sql);
        return $result;
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

    public function getAllProcessesVersion2($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start    = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit    = isset($dataList["limit"]) ? $dataList["limit"] : null;
        $response = self::$appsObj->getAllProcessesVersion2(
            $start,
            $limit
        );
        return $response;
    }

    public function getCaseDetailsVersion2($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $response                      = self::$appsObj->getCaseDetailsVersion2(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $assignee_list,
            $case_id
        );
        if (isset($response['case_details']) && count($response['case_details']) > 0) {
            foreach ($response['case_details'] as $key => &$value) {
                $checkWFSISTask = @unserialize($value['APP_DATA']);
                
                if (isset($checkWFSISTask['WFS_CASE_TITLE']) && !empty($checkWFSISTask['WFS_CASE_TITLE'])) {
                    $value['case_title'] = $checkWFSISTask['WFS_CASE_TITLE'];
                } else {
                    $value['case_title'] = isset($value['case_id']) ? $value['case_id'] : 'NA';
                }
                $value['APP_DATA'] = null;
                unset($value['APP_DATA']);
                if (isset($value['assignee'])) {
                    $value['assignee'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['assignee']));
                }
                if (isset($value['owner'])) {
                    $value['owner'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['owner']));
                }
                if (isset($value['assignee_user_id'])) {
                    $value['assignee_user_id'] = array_filter(explode(',', $value['assignee_user_id']));
                }
                if (isset($value['completion'])) {
                    // foreach (self::$process_completion as $item) {
                    //     if ($item['process_completion_value'] == $value['completion']) {
                    //         $value['completion'] = $item['process_completion_label'];
                    //     }
                    // }
                    $value['completion'] = self::$process_completion_variables[$value['completion']]?:'-';
                }
            }
        } else {
            $response['case_details']            = [];
            $response['case_details_total_rows'] = 0;
        }
        return $response;
    }

    public function getCaseUserDetailsVersion2($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $response                      = self::$appsObj->getCaseUserDetailsVersion2(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $case_id,
            $assignee_list
        );

        $tempCaseTitle = '';
        if (isset($response['user_details']) && count($response['user_details']) > 0) {
            foreach ($response['user_details'] as $key => &$value) {
                $checkWFSISTask = @unserialize($value['APP_DATA']);

                if (!isset($checkWFSISTask['WFS_IS_TASK']) || empty($checkWFSISTask['WFS_IS_TASK'])) {
                    $value['due_by'] = null;
                } elseif (isset($checkWFSISTask['WFS_IS_TASK'])) {
                    if (is_array($checkWFSISTask['WFS_IS_TASK'])) {
                        $value['due_by'] = (@$checkWFSISTask['WFS_IS_TASK'][0] == 1) ? $value['due_by'] : null;
                    } else {
                        $value['due_by'] = (@$checkWFSISTask['WFS_IS_TASK'] == 1) ? $value['due_by'] : null;
                    }
                }
                if (isset($checkWFSISTask['WFS_CASE_TITLE']) && !empty($checkWFSISTask['WFS_CASE_TITLE'])) {
                    $value['case_title'] = $checkWFSISTask['WFS_CASE_TITLE'];
                } else {
                    $value['case_title'] = isset($value['case_id']) ? $value['case_id'] : 'NA';
                }
                $tempCaseTitle = $value['case_title'];

                unset($value['APP_DATA']);
                $value = $this->formatResponse($value, self::$user_details_keys);
                if (isset($value['assignee_user_id'])) {
                    $value['assignee_user_id'] = array_filter(explode(',', $value['assignee_user_id']));
                }
                if (isset($value['assignee'])) {
                    $value['assignee'] = preg_replace('/[\$]{2}[A-Za-z0-9]+/i', '', preg_replace('/WFS[\$]{2}[A-Za-z0-9]+/i', ' ', $value['assignee']));
                }
                if (isset($value['user_completion'])) {
                    foreach (self::$process_completion as $item) {
                        if ($item['process_completion_value'] == $value['user_completion']) {
                            $value['user_completion'] = $item['process_completion_label'];
                        }
                    }
                }
            }
        } else {
            $response['user_details']            = [];
            $response['user_details_total_rows'] = 0;
        }
        if (isset($response['case_summary'])) {
            $response['case_summary'] = $this->formatResponse($response['case_summary'], self::$case_summary_keys);
            if (isset($response['case_summary']['assignee_user_id'])) {
                $response['case_summary']['assignee_user_id'] = array_filter(explode(',', $response['case_summary']['assignee_user_id']));
            }
        }
         $response['case_summary']['case_title'] = $tempCaseTitle;
        return $response;
    }


    public function getCaseUserDetailsVersion2ExcelData($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $response                      = self::$appsObj->getCaseUserDetailsVersion2ExcelData(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $case_id,
            $assignee_list
        );
        if (count($response['user_details']) < 1) {
            $response['user_details']            = [];
        }
        return $response;
    }

    /**
     * Get Case Variables
     *
     * @access public
     * @param string $app_uid , Uid for case
     * @param string $usr_uid , Uid for user
     * @param string $dynaFormUid , Uid for dynaform
     *
     * @return array
     */
    public function getCaseVariables(
        $app_uid,
        $usr_uid,
        $dynaFormUid = null,
        $pro_uid = null,
        $act_uid = null,
        $app_index = null
    ) {
        Validator::isString($app_uid, '$app_uid');
        Validator::appUid($app_uid, '$app_uid');
        $case              = new ClassesCases();
        $fields            = $case->loadCase($app_uid);
        $arrayCaseVariable = [];
        if (!is_null($dynaFormUid)) {
            $data                     = [];
            $data["APP_DATA"]         = $fields['APP_DATA'];
            $data["CURRENT_DYNAFORM"] = $dynaFormUid;
            $pmDynaForm               = new PmDynaform($data);
            //$arrayDynaFormData        = $pmDynaForm->getDynaform();
            $arrayDynaFormData = $pmDynaForm->getDynaformCustom();
            $arrayDynContent   = G::json_decode($arrayDynaFormData['DYN_CONTENT']);
            $pmDynaForm->jsonr($arrayDynContent);
            $arrayDynContent   = G::json_decode(G::json_encode($arrayDynContent), true);
            $arrayAppData      = $fields['APP_DATA'];
            $arrayCaseVariable = $this->getFieldsAndValuesByDynaFormAndAppData(
                $arrayDynContent['items'][0],
                $arrayAppData,
                $arrayCaseVariable
            );
        } else {
            $arrayCaseVariable = $fields['APP_DATA'];
        }
        return $arrayCaseVariable;
    }

    public function getFormDetailsVersion2($dataList = array())
    {
        $sysConf        = System::getSystemConfiguration();
        $pmDocumentHost = $sysConf['pm_server_host'];
        $serverDetails  = $_SERVER;
        $serverUri      = $serverDetails['HTTP_HOST'];
        $requestScheme  = $serverDetails['REQUEST_SCHEME'];
        $workspace      = config('system.workspace');
        if (empty($workspace)) {
            return false;
        }
        $taskModel       = new BmTask();
        $taskModel->setFormatFieldNameInUppercase(false);
        $taskModel->setArrayParamException(array("taskUid" => "act_uid", "stepUid" => "step_uid"));
        $dynaformModel = new BmDynaform();
        $dynaformModel->setFormatFieldNameInUppercase(false);
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $remove_label                  = @$dataList['remove_label'] ? @$dataList['remove_label'] : '';
        $response                      = self::$appsObj->getFormDetailsVersion2(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $case_id,
            $assignee_list
        );
        if ($task) {
            $dynaforms   = [];
            $tttask      = @str_replace('\'', '', $dataList['task']);
            $org_process = @str_replace('\'', '', $dataList['process']);
            if ($this->getTaskTitle($tttask)) {
                $taskType = $this->getTaskTitle($tttask)[0]['TAS_ASSIGN_TYPE'];
            } else {
                $taskType = null;
            }
            $steps = $taskModel->getSteps($tttask);


                $allDynaformFields = [];
            foreach ($steps as $key => $value) {
                if ($value['step_type_obj'] == 'DYNAFORM') {
                    $allDynaformFields[$value['step_uid_obj']] = self::$appsObj->getDynaformsVariables($value['step_uid_obj']);
                }
            }
 

                $responseDynaFormColumns = [];
            foreach ($response['form_details'] as $key => &$value) {
                $app_uid           = $value['APP_UID'];
                $appData           = @unserialize($value['APP_DATA']);
                $computedDynformFields = [];
                $formdata = [];
                foreach ($allDynaformFields as $key4 => $value4) {
                    $stepFormData            = $this->getCaseVariables($app_uid, $value['assignee_user_id'], $key4, $org_process, $value['task_uid'], null);
                    $stepDynaformFields = $allDynaformFields[$key4];
                    $formdata = array_merge($formdata, $stepFormData);
                    $computedDynformFields           = array_merge($computedDynformFields, $stepDynaformFields);
                }

                
                foreach ($computedDynformFields as $key5 => $value5) {
                    if (array_key_exists($key5, $formdata)) {
                        if ($taskType == 'MULTIPLE_INSTANCE_VALUE_BASED') {
                            $allUserData = isset($appData['WFS_USERS_DATA']) ? $appData['WFS_USERS_DATA'] : @$appData['allUserData'];
                            if (isset($allUserData[$value['assignee_user_id']])) {
                                $userData = $allUserData[$value['assignee_user_id']];
                                if (isset($userData[$key5])) {
                                    $formdata[$key5] = $userData[$key5];
                                }
                            }
                        }
                        switch ($value5['type']) {
                            case 'dropdown':
                            case 'checkbox':
                            case 'radio':
                            case 'suggest':
                            case 'checkgroup':
                                $options = [];
                                foreach (@$computedDynformFields[$key5]['options'] as $key7 => $value7) {
                                    $options[$value7->value] = $value7->label;
                                }
                                if (is_array($formdata[$key5]) && count($formdata[$key5]) > 0) {
                                    if (count($formdata[$key5]) == 1) {
                                        $tfav = array_keys($formdata[$key5])[0];
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : ($formdata[$key5 . '_label'] == null ? $options[$tfav] : $formdata[$key5 . '_label']);
                                        } else {
                                            $tempOptionsValue = $options[$tfav];
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    } elseif (count($formdata[$key5]) > 1) {
                                        $tempOptionsValue = '';
                                        foreach ($formdata[$key5] as $k => $v) {
                                            $tempOptionsValue .= ', '.(isset($options[$k]) ? $options[$k] : $options[$v]);
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    }
                                } elseif (is_array($formdata[$key5]) && count($formdata[$key5]) == 0) {
                                    if (isset($formdata[$key5 . '_label'])) {
                                        $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                    } else {
                                        $tempOptionsValue = null;
                                    }
                                    $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                    $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                } else {
                                    if (array_key_exists($key5, $formdata)) {
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                        } else {
                                            $tempOptionsValue = @$options[$formdata[$key5]];
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    } else {
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                        } else {
                                            $tempOptionsValue = @$formdata[$key5] ? $formdata[$key5] : null;
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    }
                                }
                                break;
                            case 'multipleFile':
                                $links = [];
                                if ($this->isMultiArray($formdata[$key5]) && !empty($formdata[$key5])) {
                                    foreach ($formdata[$key5] as $key7 => $value7) {
                                        $appDocUid = @$value7['appDocUid'];
                                        $version   = @$value7['version'];
                                        $links[]   = "$pmDocumentHost/sys$workspace/en/neoclassic/cases/cases_ShowDocument?a=$appDocUid&version=$version";
                                    }
                                    $formdata[$key5] = implode(', ', array_filter($links));
                                } elseif (!$this->isMultiArray($formdata[$key5]) && !empty($formdata[$key5])) {
                                    $appDocUid       = @$formdata[$key5]['appDocUid'];
                                    $version         = @$formdata[$key5]['version'];
                                    $links[]         = "$pmDocumentHost/sys$workspace/en/neoclassic/cases/cases_ShowDocument?a=$appDocUid&version=$version";
                                    $formdata[$key5] = implode(', ', array_filter($links));
                                } else {
                                    $formdata[$key5] = null;
                                }
                                break;
                            case 'datetime':
                                $formdata[$key5] = !empty($formdata[$key5]) ? $formdata[$key5] : '';
                                break;
                            case 'grid':
                                $gridName = $computedDynformFields[$key5]['label'] ? $computedDynformFields[$key5]['label'] : $computedDynformFields[$key5]['name'];
                                foreach ($formdata[$key5] as $key_grid => $value_grid) {
                                    $grid_key = $gridName . "_row_". $key_grid;
                                    $formdata[$grid_key] = $value_grid;

                                    //$grid_key                                 = $gridName . $key_grid;
                                    //$formdata[$gridName . "$key_grid"]        = $value_grid;
                                    $computedDynformFields[$grid_key]             = $computedDynformFields[$key5];
                                    $computedDynformFields[$grid_key]['variable'] = $grid_key;
                                    $computedDynformFields[$grid_key]['name']     = $grid_key;
                                    $computedDynformFields[$grid_key]['label']    = $grid_key;
                                }
                                break;
                            default:
                                break;
                        }
                        if (strpos($computedDynformFields[$key5]['hint'], 'type:user') !== false) {
                            $formdata[$key5] = @!empty($formdata[$key5 . '_label']) ? @$formdata[$key5 . '_label'] : $formdata[$key5];
                        }
                        if (strpos($computedDynformFields[$key5]['hint'], 'type:cc') !== false) {
                            $formdata[$key5] = @!empty($formdata[$key5 . '_label']) ? @$formdata[$key5 . '_label'] : $formdata[$key5];
                        }
                        if ($computedDynformFields[$key5]['type'] == 'grid') {
                            $unsetGridKey = $computedDynformFields[$key5]['variable'];
                            unset($computedDynformFields[$unsetGridKey]);
                            unset($formdata[$unsetGridKey]);
                        }
                    } else {
                        $formdata[$key5] = null;
                    }
                }
                
                $checkWFSISTask = @unserialize($value['APP_DATA']);
                if (isset($checkWFSISTask['WFS_CASE_TITLE']) && !empty($checkWFSISTask['WFS_CASE_TITLE'])) {
                    $value['case_title'] = $checkWFSISTask['WFS_CASE_TITLE'];
                } else {
                    $value['case_title'] = isset($value['case_id']) ? $value['case_id'] : '';
                }
                unset($value['APP_DATA']);
                $value['form_data']['data']    = $formdata;
                // $value['form_data']['columns']    = $computedDynformFields;
                $responseDynaFormColumns = array_merge($responseDynaFormColumns, $computedDynformFields);
                

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
                    foreach (self::$process_completion as $item) {
                        if ($item['process_completion_value'] == $value['completion']) {
                            $value['completion'] = $item['process_completion_label'];
                        }
                    }
                }
            }
            $response['columns'] = $responseDynaFormColumns;
        }
        
        return $response;
    }

    public function getProcessDashboardDetailsVersion2($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search        = isset($dataList["search"]) ? $dataList["search"] : "";
        $owner_id      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $response      = self::$appsObj->getProcessDashboardDetailsVersion2(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $owner_id,
            $assignee_list
        );
        $result['total_processes'] = $response['total_processes'];
        unset($response['total_processes']);
        $result['data'] = $response;
        return $result;
    }

    public function getTaskTitle($tas_uid = '')
    {
        $response = self::$appsObj->getTaskTitle(
            $tas_uid
        );
        if (!empty($response['data'])) {
            foreach ($response['data'] as &$value) {
                $value = array_change_key_case($value, CASE_LOWER);
            }
        }
        return $response;
    }

    public function isMultiArray($input_array)
    {
        if (is_array($input_array)) {
            $rv = array_filter($input_array, 'is_array');
            if (count($rv) > 0) {
                return true;
            }
            return false;
        }
        return false;
    }

    public function parserDataDynaForm($data)
    {
        $structure = array(
            'dyn_uid'         => 'formId',
            'dyn_title'       => 'formTitle',
            'dyn_description' => 'formDescription',
            'dyn_content'     => 'formContent',
            'dyn_update_date' => 'formUpdateDate',
        );
        $response = $this->replaceFields($data, $structure);
        return $response;
    }

    public function replaceFields($data, $structure)
    {
        $response = array();
        foreach ($data as $field => $d) {
            $field = preg_quote($field, "/");
            if (is_array($d)) {
                $newData = array();
                foreach ($d as $field => $value) {
                    $field = preg_quote($field, "/");
                    if (preg_match(
                        '/\|(' . $field . ')\|/i',
                        '|' . implode('|', array_keys($structure)) . '|',
                        $arrayMatch
                    ) &&
                        !is_array($structure[$arrayMatch[1]])
                    ) {
                        $newName           = $structure[$arrayMatch[1]];
                        $newData[$newName] = is_null($value) ? "" : $value;
                    } else {
                        foreach ($structure as $name => $str) {
                            if (is_array($str) &&
                                preg_match(
                                    '/\|(' . $field . ')\|/i',
                                    '|' . implode('|', array_keys($str)) . '|',
                                    $arrayMatch
                                ) &&
                                !is_array($str[$arrayMatch[1]])
                            ) {
                                $newName                  = $str[$arrayMatch[1]];
                                $newData[$name][$newName] = is_null($value) ? "" : $value;
                            }
                        }
                    }
                }
                if (count($newData) > 0) {
                    $response[] = $newData;
                }
            } else {
                if (preg_match(
                    '/\|(' . $field . ')\|/i',
                    '|' . implode('|', array_keys($structure)) . '|',
                    $arrayMatch
                ) &&
                    !is_array($structure[$arrayMatch[1]])
                ) {
                    $newName            = $structure[$arrayMatch[1]];
                    $response[$newName] = is_null($d) ? "" : $d;
                } else {
                    foreach ($structure as $name => $str) {
                        if (is_array($str) &&
                            preg_match(
                                '/\|(' . $field . ')\|/i',
                                '|' . implode('|', array_keys($str)) . '|',
                                $arrayMatch
                            ) &&
                            !is_array($str[$arrayMatch[1]])
                        ) {
                            $newName                   = $str[$arrayMatch[1]];
                            $response[$name][$newName] = is_null($d) ? "" : $d;
                        }
                    }
                }
            }
        }
        return $response;
    }

    private function __construct()
    {
        if (self::$appsObj === null) {
            self::$appsObj = Applications::getSingleton();
        }

        if (self::$processObj === null) {
            self::$processObj = new Process();
        }
    }

    /**
     * Return the field value to be used in the front-end client.
     *
     * @param type $field
     * @param type $value
     *
     * @return string
     */
    private function getFieldValue($field, $value)
    {
        switch ($field['type']) {
            case 'file':
                return $field['data']['app_doc_uid'];
            default:
                return $value;
        }
    }

    /**
     * Get fields and values by DynaForm
     *
     * @param array $form
     * @param array $appData
     * @param array $caseVariable
     *
     * @return array
     * @throws Exception
     */
    private function getFieldsAndValuesByDynaFormAndAppData(array $form, array $appData, array $caseVariable)
    {
        try {
            foreach ($form['items'] as $value) {
                foreach ($value as $field) {
                    if (isset($field['type'])) {
                        if ($field['type'] != 'form') {
                            foreach ($field as $key => $val) {
                                if (is_string($val) && in_array(substr($val, 0, 2), PmDynaform::$prefixs)) {
                                    $field[$key] = substr($val, 2);
                                }
                            }
                            foreach ($appData as $key => $val) {
                                
                                if (in_array($key, $field, true) != false) {
                                    $caseVariable[$key] = $this->getFieldValue($field, $appData[$key]);
                                    if (isset($appData[$key . '_label'])) {
                                        $caseVariable[$key . '_label'] = $appData[$key . '_label'];
                                    }
                                }
                                // get WFS_CASE_TITLE 
                                if($key == "WFS_CASE_TITLE")
                                    $caseVariable['WFS_CASE_TITLE'] = $val;

                                // get WFS_USERS_DATA
                                if($key == "WFS_USERS_DATA" || $key == "allUserData")
                                    $caseVariable['WFS_USERS_DATA'] = $val;

                            }
                        } else {
                            $caseVariableAux = $this->getFieldsAndValuesByDynaFormAndAppData(
                                $field,
                                $appData,
                                $caseVariable
                            );
                            $caseVariable = array_merge($caseVariable, $caseVariableAux);
                        }
                    }
                }
            }
            return $caseVariable;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify if Solr is Enabled
     *
     * @return bool
     */
    private function isSolrEnabled()
    {
        $solrEnabled   = false;
        self::$solrEnv = !empty(self::$solrEnv) ? self::$solrEnv : System::solrEnv();
        if (self::$solrEnv !== false) {
            $this->solr = !empty($this->solr) ? $this->solr : new AppSolr(
                self::$solrEnv['solr_enabled'],
                self::$solrEnv['solr_host'],
                self::$solrEnv['solr_instance']
            );
            if (self::$solr->isSolrEnabled() && self::$solrEnv["solr_enabled"] == true) {
                $solrEnabled = true;
            }
        }
        return $solrEnabled;
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

    public function getProcessTaskDetailsVersion2($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $process = isset($dataList["process"]) ? $dataList["process"] : "";

        $response = self::$appsObj->getProcessTaskDetailsVersion2(
            $process
        );

        if (!empty($response['data'])) {
            foreach ($response['data'] as &$value) {
                $value = array_change_key_case($value, CASE_LOWER);
            }
        }

        return $response;
    }

    /**
     * Get Global System Variables
     * @param array $appData
     * @param array $dataVariable
     *
     * @return array
     */
    public static function getGlobalVariables($appData = array(), $dataVariable = array())
    {
        $appData = array_change_key_case($appData, CASE_UPPER);
        $dataVariable = array_change_key_case($dataVariable, CASE_UPPER);

        $result = [];
        //we get the appData parameters
        if (!empty($appData['APPLICATION'])) {
            $result['APPLICATION'] = $appData['APPLICATION'];
        }
        if (!empty($appData['PROCESS'])) {
            $result['PROCESS'] = $appData['PROCESS'];
        }
        if (!empty($appData['TASK'])) {
            $result['TASK'] = $appData['TASK'];
        }
        if (!empty($appData['INDEX'])) {
            $result['INDEX'] = $appData['INDEX'];
        }

        //we try to get the missing elements
        if (!empty($dataVariable['APP_UID']) && empty($result['APPLICATION'])) {
            $result['APPLICATION'] = $dataVariable['APP_UID'];
        }
        if (!empty($dataVariable['PRO_UID']) && empty($result['PROCESS'])) {
            $result['PROCESS'] = $dataVariable['PRO_UID'];
        }

        $result['USER_LOGGED'] = '';
        $result['USR_USERNAME'] = '';
        global $RBAC;
        if (isset($RBAC) && isset($RBAC->aUserInfo)) {
            $result['USER_LOGGED'] = isset($RBAC->aUserInfo['USER_INFO']['USR_UID']) ? $RBAC->aUserInfo['USER_INFO']['USR_UID'] : '';
            $result['USR_USERNAME'] = isset($RBAC->aUserInfo['USER_INFO']['USR_USERNAME']) ? $RBAC->aUserInfo['USER_INFO']['USR_USERNAME'] : '';
        }
        if (empty($result['USER_LOGGED'])) {
            $result['USER_LOGGED'] = Server::getUserId();
            if (!empty($result['USER_LOGGED'])) {
                $oUserLogged = new ModelUsers();
                $oUserLogged->load($result['USER_LOGGED']);
                $result['USR_USERNAME'] = $oUserLogged->getUsrUsername();
            }
        }

        //the parameter dataVariable may contain additional elements
        $result = array_merge($dataVariable, $result);

        return $result;
    }

    public function getCaseDetailsVersion2ExcelData($dataList = array())
    {
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $response                      = self::$appsObj->getCaseDetailsVersion2ExcelData(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $assignee_list,
            $case_id
        );
        if (isset($response['case_details']) && count($response['case_details']) == 0) {
            $response['case_details']            = [];
            $response['case_details_total_rows'] = 0;
        }
        return $response;
    }


    public function getFormDetailsVersion2ExcelData($dataList = array())
    {
        $sysConf        = System::getSystemConfiguration();
        $pmDocumentHost = $sysConf['pm_server_host'];
        $serverDetails  = $_SERVER;
        $serverUri      = $serverDetails['HTTP_HOST'];
        $requestScheme  = $serverDetails['REQUEST_SCHEME'];
        $workspace      = config('system.workspace');
        if (empty($workspace)) {
            return false;
        }
        $taskModel       = new BmTask();
        $taskModel->setFormatFieldNameInUppercase(false);
        $taskModel->setArrayParamException(array("taskUid" => "act_uid", "stepUid" => "step_uid"));
        $dynaformModel = new BmDynaform();
        $dynaformModel->setFormatFieldNameInUppercase(false);
        Validator::isArray($dataList, '$dataList');
        $start                         = isset($dataList["start"]) ? $dataList["start"] : "0";
        $limit                         = isset($dataList["limit"]) ? $dataList["limit"] : "";
        $dateFrom                      = (!empty($dataList["dateFrom"])) ? $dataList["dateFrom"] : "";
        $dateTo                        = (!empty($dataList["dateTo"])) ? $dataList["dateTo"] : "";
        $process                       = isset($dataList["process"]) ? $dataList["process"] : '';
        $search                        = isset($dataList["search"]) ? $dataList["search"] : "";
        $task                          = isset($dataList["task"]) ? $dataList["task"] : "";
        $owner_id                      = isset($dataList["owner_id"]) ? $dataList["owner_id"] : "";
        $assignee_list                 = isset($dataList["assignee_list"]) ? $dataList["assignee_list"] : "";
        $status                        = isset($dataList["status"]) ? $dataList["status"] : "";
        $completion                    = isset($dataList["completion"]) ? $dataList["completion"] : "";
        $current_step                  = isset($dataList["current_step"]) ? $dataList["current_step"] : "";
        $case_id                       = isset($dataList["case_id"]) ? $dataList["case_id"] : "";
        $is_custom_attributes_honoured = isset($dataList["is_custom_attributes_honoured"]) ? $dataList["is_custom_attributes_honoured"] : "";
        $remove_label                  = @$dataList['remove_label'] ? @$dataList['remove_label'] : '';
        $response                      = self::$appsObj->getFormDetailsVersion2ExcelData(
            $start,
            $limit,
            $dateFrom,
            $dateTo,
            $process,
            $search,
            $task,
            $owner_id,
            $status,
            $completion,
            $current_step,
            $case_id,
            $assignee_list
        );

        if ($task) {
            $assignee_array = [];
            $dynaforms   = [];
            $tttask      = @str_replace('\'', '', $dataList['task']);
            $org_process = @str_replace('\'', '', $dataList['process']);
            if ($this->getTaskTitle($tttask)) {
                $taskType = $this->getTaskTitle($tttask)[0]['TAS_ASSIGN_TYPE'];
            } else {
                $taskType = null;
            }
            $steps = $taskModel->getSteps($tttask);


            $allDynaformFields = [];
            foreach ($steps as $key => $value) {
                if ($value['step_type_obj'] == 'DYNAFORM') {
                    $allDynaformFields[$value['step_uid_obj']] = self::$appsObj->getDynaformsVariablesExcelData($value['step_uid_obj']);
                }
            }

            $responseDynaFormColumns = [];
            $computedDynformFields = [];
            foreach ($response['form_details'] as $key => &$value) {
                $assignee_array[] = isset($value['assignee']) ? $value['assignee'] : null;
                $formdata = [];
                foreach ($allDynaformFields as $key4 => $value4) {
                    $stepFormData            = $this->getCaseVariables($value['APP_UID'], $value['assignee'], $key4, $org_process, $value['task_uid'], null);
                    $stepDynaformFields = $allDynaformFields[$key4];
                    $formdata = array_merge($formdata, $stepFormData);
                    $computedDynformFields           = array_merge($computedDynformFields, $stepDynaformFields);
                }

                foreach ($computedDynformFields as $key5 => $value5) {

                    if (array_key_exists($key5, $formdata)) {

                        if ($taskType == 'MULTIPLE_INSTANCE_VALUE_BASED') {
                            $allUserData = isset($formdata['WFS_USERS_DATA']) ? $formdata['WFS_USERS_DATA'] : @$formdata['allUserData'];
                            if (isset($allUserData[$value['assignee']])) {
                                $userData = $allUserData[$value['assignee']];
                                if (isset($userData[$key5])) {
                                    $formdata[$key5] = $userData[$key5];
                                }
                            }
                        }

                        switch ($value5['type']) {
                            case 'dropdown':
                            case 'checkbox':
                            case 'radio':
                            case 'suggest':
                            case 'checkgroup':
                                $options = [];
                                foreach (@$computedDynformFields[$key5]['options'] as $key7 => $value7) {
                                    $options[$value7->value] = $value7->label;
                                }
                                if (is_array($formdata[$key5]) && count($formdata[$key5]) > 0) {
                                    if (count($formdata[$key5]) == 1) {
                                        $tfav = array_keys($formdata[$key5])[0];
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : ($formdata[$key5 . '_label'] == null ? $options[$tfav] : $formdata[$key5 . '_label']);
                                        } else {
                                            $tempOptionsValue = $options[$tfav];
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    } elseif (count($formdata[$key5]) > 1) {
                                        $tempOptionsValue = '';
                                        foreach ($formdata[$key5] as $k => $v) {
                                            $tempOptionsValue .= ', '.(isset($options[$k]) ? $options[$k] : $options[$v]);
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    }
                                } elseif (is_array($formdata[$key5]) && count($formdata[$key5]) == 0) {
                                    if (isset($formdata[$key5 . '_label'])) {
                                        $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                    } else {
                                        $tempOptionsValue = null;
                                    }
                                    $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                    $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                } else {
                                    if (array_key_exists($key5, $formdata)) {
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                        } else {
                                            $tempOptionsValue = @$options[$formdata[$key5]];
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    } else {
                                        if (isset($formdata[$key5 . '_label'])) {
                                            $tempOptionsValue = is_array($formdata[$key5 . '_label']) ? implode(', ', array_filter($formdata[$key5 . '_label'])) : $formdata[$key5 . '_label'];
                                        } else {
                                            $tempOptionsValue = @$formdata[$key5] ? $formdata[$key5] : null;
                                        }
                                        $tempOptionsValue = preg_replace('/^[,\s]+|[\s,]+$/', '', $tempOptionsValue);
                                        $formdata[$key5] = $tempOptionsValue ? $tempOptionsValue : null;
                                    }
                                }
                                break;
                            case 'multipleFile':
                                $links = [];
                                if ($this->isMultiArray($formdata[$key5]) && !empty($formdata[$key5])) {
                                    foreach ($formdata[$key5] as $key7 => $value7) {
                                        $appDocUid = @$value7['appDocUid'];
                                        $version   = @$value7['version'];
                                        $links[]   = "$pmDocumentHost/sys$workspace/en/neoclassic/cases/cases_ShowDocument?a=$appDocUid&version=$version";
                                    }
                                    $formdata[$key5] = implode(', ', array_filter($links));
                                } elseif (!$this->isMultiArray($formdata[$key5]) && !empty($formdata[$key5])) {
                                    $appDocUid       = @$formdata[$key5]['appDocUid'];
                                    $version         = @$formdata[$key5]['version'];
                                    $links[]         = "$pmDocumentHost/sys$workspace/en/neoclassic/cases/cases_ShowDocument?a=$appDocUid&version=$version";
                                    $formdata[$key5] = implode(', ', array_filter($links));
                                } else {
                                    $formdata[$key5] = null;
                                }
                                break;
                            case 'datetime':
                                $formdata[$key5] = !empty($formdata[$key5]) ? $formdata[$key5] : '';
                                break;
                            case 'grid':
                                $gridOutputString = $computedDynformFields[$key5]['grid_heading'];
                                $label_mapping = $computedDynformFields[$key5]['label_mapping'];
                                $gridOutputString .= PHP_EOL;
                                foreach ($formdata[$key5] as $form_item) {
                                    $gridOutputString .= "| ";
                                    foreach ($label_mapping as $label_key => $label_value) {
                                        $gridOutputString .= (isset($form_item[$label_key]) ? $form_item[$label_key] : '-') . " | ";
                                    }
                                    $gridOutputString .= PHP_EOL;
                                }

                                $formdata[$key5] = $gridOutputString;
                                break;
                            default:
                                break;
                        }
                        if (strpos($computedDynformFields[$key5]['hint'], 'type:user') !== false) {
                            $formdata[$key5] = @!empty($formdata[$key5 . '_label']) ? @$formdata[$key5 . '_label'] : $formdata[$key5];
                        }
                        if (strpos($computedDynformFields[$key5]['hint'], 'type:cc') !== false) {
                            $formdata[$key5] = @!empty($formdata[$key5 . '_label']) ? @$formdata[$key5 . '_label'] : $formdata[$key5];
                        }

                        if (stripos($computedDynformFields[$key5]['hint'], 'type:signature') !== false) {
                            unset($computedDynformFields[$key5]);
                            unset($formdata[$key5]);
                        }

                    } else {

                        if (stripos($computedDynformFields[$key5]['hint'], 'type:signature') !== false)
                        {
                            unset($computedDynformFields[$key5]);
                            unset($formdata[$key5]);
                        }
                        else
                            $formdata[$key5] = '-';

                    }
                }
                $formdata['case_title'] = isset($formdata['WFS_CASE_TITLE']) ? $formdata['WFS_CASE_TITLE'] : $value['case_id'];
                $formdata['case_id'] = $value['case_id'];
                $formdata['owner'] = $value['owner'];
                $formdata['assignee'] = $value['assignee'];
                //Set case title
                $value['case_title'] = isset($formdata['WFS_CASE_TITLE']) ? $formdata['WFS_CASE_TITLE'] : $value['case_id'];

                $value['form_data']['data']    = $formdata;
                $responseDynaFormColumns = array_merge($responseDynaFormColumns, $computedDynformFields);
            }
            $response['assignee_array'] = $assignee_array;
            $response['columns'] = $responseDynaFormColumns;
        }
        return $response;
    }
}
