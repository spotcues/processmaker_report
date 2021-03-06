<?php

require_once 'propel/om/BaseObject.php';

require_once 'propel/om/Persistent.php';


include_once 'propel/util/Criteria.php';

include_once 'classes/model/ProcessPeer.php';

/**
 * Base class that represents a row from the 'PROCESS' table.
 *
 * 
 *
 * @package    workflow.classes.model.om
 */
abstract class BaseProcess extends BaseObject implements Persistent
{

    /**
     * The Peer class.
     * Instance provides a convenient way of calling static methods on a class
     * that calling code may not be able to identify.
     * @var        ProcessPeer
    */
    protected static $peer;

    /**
     * The value for the pro_uid field.
     * @var        string
     */
    protected $pro_uid = '';

    /**
     * The value for the pro_id field.
     * @var        int
     */
    protected $pro_id;

    /**
     * The value for the pro_title field.
     * @var        string
     */
    protected $pro_title;

    /**
     * The value for the pro_description field.
     * @var        string
     */
    protected $pro_description;

    /**
     * The value for the pro_parent field.
     * @var        string
     */
    protected $pro_parent = '0';

    /**
     * The value for the pro_time field.
     * @var        double
     */
    protected $pro_time = 1.0;

    /**
     * The value for the pro_timeunit field.
     * @var        string
     */
    protected $pro_timeunit = 'DAYS';

    /**
     * The value for the pro_status field.
     * @var        string
     */
    protected $pro_status = 'ACTIVE';

    /**
     * The value for the pro_status_id field.
     * @var        int
     */
    protected $pro_status_id = 1;

    /**
     * The value for the pro_type_day field.
     * @var        string
     */
    protected $pro_type_day = '0';

    /**
     * The value for the pro_type field.
     * @var        string
     */
    protected $pro_type = 'NORMAL';

    /**
     * The value for the pro_assignment field.
     * @var        string
     */
    protected $pro_assignment = 'FALSE';

    /**
     * The value for the pro_show_map field.
     * @var        int
     */
    protected $pro_show_map = 1;

    /**
     * The value for the pro_show_message field.
     * @var        int
     */
    protected $pro_show_message = 1;

    /**
     * The value for the pro_subprocess field.
     * @var        int
     */
    protected $pro_subprocess = 0;

    /**
     * The value for the pro_tri_create field.
     * @var        string
     */
    protected $pro_tri_create = '';

    /**
     * The value for the pro_tri_open field.
     * @var        string
     */
    protected $pro_tri_open = '';

    /**
     * The value for the pro_tri_deleted field.
     * @var        string
     */
    protected $pro_tri_deleted = '';

    /**
     * The value for the pro_tri_canceled field.
     * @var        string
     */
    protected $pro_tri_canceled = '';

    /**
     * The value for the pro_tri_paused field.
     * @var        string
     */
    protected $pro_tri_paused = '';

    /**
     * The value for the pro_tri_reassigned field.
     * @var        string
     */
    protected $pro_tri_reassigned = '';

    /**
     * The value for the pro_tri_unpaused field.
     * @var        string
     */
    protected $pro_tri_unpaused = '';

    /**
     * The value for the pro_type_process field.
     * @var        string
     */
    protected $pro_type_process = 'PUBLIC';

    /**
     * The value for the pro_show_delegate field.
     * @var        int
     */
    protected $pro_show_delegate = 1;

    /**
     * The value for the pro_show_dynaform field.
     * @var        int
     */
    protected $pro_show_dynaform = 0;

    /**
     * The value for the pro_category field.
     * @var        string
     */
    protected $pro_category = '';

    /**
     * The value for the pro_sub_category field.
     * @var        string
     */
    protected $pro_sub_category = '';

    /**
     * The value for the pro_industry field.
     * @var        int
     */
    protected $pro_industry = 1;

    /**
     * The value for the pro_update_date field.
     * @var        int
     */
    protected $pro_update_date;

    /**
     * The value for the pro_create_date field.
     * @var        int
     */
    protected $pro_create_date;

    /**
     * The value for the pro_create_user field.
     * @var        string
     */
    protected $pro_create_user = '';

    /**
     * The value for the pro_height field.
     * @var        int
     */
    protected $pro_height = 5000;

    /**
     * The value for the pro_width field.
     * @var        int
     */
    protected $pro_width = 10000;

    /**
     * The value for the pro_title_x field.
     * @var        int
     */
    protected $pro_title_x = 0;

    /**
     * The value for the pro_title_y field.
     * @var        int
     */
    protected $pro_title_y = 6;

    /**
     * The value for the pro_debug field.
     * @var        int
     */
    protected $pro_debug = 0;

    /**
     * The value for the pro_dynaforms field.
     * @var        string
     */
    protected $pro_dynaforms;

    /**
     * The value for the pro_derivation_screen_tpl field.
     * @var        string
     */
    protected $pro_derivation_screen_tpl = '';

    /**
     * The value for the pro_cost field.
     * @var        double
     */
    protected $pro_cost = 0.0;

    /**
     * The value for the pro_unit_cost field.
     * @var        string
     */
    protected $pro_unit_cost = '';

    /**
     * The value for the pro_itee field.
     * @var        int
     */
    protected $pro_itee = 0;

    /**
     * The value for the pro_action_done field.
     * @var        string
     */
    protected $pro_action_done;

    /**
     * The value for the category_id field.
     * @var        int
     */
    protected $category_id = 0;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     * @var        boolean
     */
    protected $alreadyInSave = false;

    /**
     * Flag to prevent endless validation loop, if this object is referenced
     * by another object which falls in this transaction.
     * @var        boolean
     */
    protected $alreadyInValidation = false;

    /**
     * Get the [pro_uid] column value.
     * 
     * @return     string
     */
    public function getProUid()
    {

        return $this->pro_uid;
    }

    /**
     * Get the [pro_id] column value.
     * 
     * @return     int
     */
    public function getProId()
    {

        return $this->pro_id;
    }

    /**
     * Get the [pro_title] column value.
     * 
     * @return     string
     */
    public function getProTitle()
    {

        return $this->pro_title;
    }

    /**
     * Get the [pro_description] column value.
     * 
     * @return     string
     */
    public function getProDescription()
    {

        return $this->pro_description;
    }

    /**
     * Get the [pro_parent] column value.
     * 
     * @return     string
     */
    public function getProParent()
    {

        return $this->pro_parent;
    }

    /**
     * Get the [pro_time] column value.
     * 
     * @return     double
     */
    public function getProTime()
    {

        return $this->pro_time;
    }

    /**
     * Get the [pro_timeunit] column value.
     * 
     * @return     string
     */
    public function getProTimeunit()
    {

        return $this->pro_timeunit;
    }

    /**
     * Get the [pro_status] column value.
     * 
     * @return     string
     */
    public function getProStatus()
    {

        return $this->pro_status;
    }

    /**
     * Get the [pro_status_id] column value.
     * 
     * @return     int
     */
    public function getProStatusId()
    {

        return $this->pro_status_id;
    }

    /**
     * Get the [pro_type_day] column value.
     * 
     * @return     string
     */
    public function getProTypeDay()
    {

        return $this->pro_type_day;
    }

    /**
     * Get the [pro_type] column value.
     * 
     * @return     string
     */
    public function getProType()
    {

        return $this->pro_type;
    }

    /**
     * Get the [pro_assignment] column value.
     * 
     * @return     string
     */
    public function getProAssignment()
    {

        return $this->pro_assignment;
    }

    /**
     * Get the [pro_show_map] column value.
     * 
     * @return     int
     */
    public function getProShowMap()
    {

        return $this->pro_show_map;
    }

    /**
     * Get the [pro_show_message] column value.
     * 
     * @return     int
     */
    public function getProShowMessage()
    {

        return $this->pro_show_message;
    }

    /**
     * Get the [pro_subprocess] column value.
     * 
     * @return     int
     */
    public function getProSubprocess()
    {

        return $this->pro_subprocess;
    }

    /**
     * Get the [pro_tri_create] column value.
     * 
     * @return     string
     */
    public function getProTriCreate()
    {

        return $this->pro_tri_create;
    }

    /**
     * Get the [pro_tri_open] column value.
     * 
     * @return     string
     */
    public function getProTriOpen()
    {

        return $this->pro_tri_open;
    }

    /**
     * Get the [pro_tri_deleted] column value.
     * 
     * @return     string
     */
    public function getProTriDeleted()
    {

        return $this->pro_tri_deleted;
    }

    /**
     * Get the [pro_tri_canceled] column value.
     * 
     * @return     string
     */
    public function getProTriCanceled()
    {

        return $this->pro_tri_canceled;
    }

    /**
     * Get the [pro_tri_paused] column value.
     * 
     * @return     string
     */
    public function getProTriPaused()
    {

        return $this->pro_tri_paused;
    }

    /**
     * Get the [pro_tri_reassigned] column value.
     * 
     * @return     string
     */
    public function getProTriReassigned()
    {

        return $this->pro_tri_reassigned;
    }

    /**
     * Get the [pro_tri_unpaused] column value.
     * 
     * @return     string
     */
    public function getProTriUnpaused()
    {

        return $this->pro_tri_unpaused;
    }

    /**
     * Get the [pro_type_process] column value.
     * 
     * @return     string
     */
    public function getProTypeProcess()
    {

        return $this->pro_type_process;
    }

    /**
     * Get the [pro_show_delegate] column value.
     * 
     * @return     int
     */
    public function getProShowDelegate()
    {

        return $this->pro_show_delegate;
    }

    /**
     * Get the [pro_show_dynaform] column value.
     * 
     * @return     int
     */
    public function getProShowDynaform()
    {

        return $this->pro_show_dynaform;
    }

    /**
     * Get the [pro_category] column value.
     * 
     * @return     string
     */
    public function getProCategory()
    {

        return $this->pro_category;
    }

    /**
     * Get the [pro_sub_category] column value.
     * 
     * @return     string
     */
    public function getProSubCategory()
    {

        return $this->pro_sub_category;
    }

    /**
     * Get the [pro_industry] column value.
     * 
     * @return     int
     */
    public function getProIndustry()
    {

        return $this->pro_industry;
    }

    /**
     * Get the [optionally formatted] [pro_update_date] column value.
     * 
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                          If format is NULL, then the integer unix timestamp will be returned.
     * @return     mixed Formatted date/time value as string or integer unix timestamp (if format is NULL).
     * @throws     PropelException - if unable to convert the date/time to timestamp.
     */
    public function getProUpdateDate($format = 'Y-m-d H:i:s')
    {

        if ($this->pro_update_date === null || $this->pro_update_date === '') {
            return null;
        } elseif (!is_int($this->pro_update_date)) {
            // a non-timestamp value was set externally, so we convert it
            $ts = strtotime($this->pro_update_date);
            if ($ts === -1 || $ts === false) {
                throw new PropelException("Unable to parse value of [pro_update_date] as date/time value: " .
                    var_export($this->pro_update_date, true));
            }
        } else {
            $ts = $this->pro_update_date;
        }
        if ($format === null) {
            return $ts;
        } elseif (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }
    }

    /**
     * Get the [optionally formatted] [pro_create_date] column value.
     * 
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                          If format is NULL, then the integer unix timestamp will be returned.
     * @return     mixed Formatted date/time value as string or integer unix timestamp (if format is NULL).
     * @throws     PropelException - if unable to convert the date/time to timestamp.
     */
    public function getProCreateDate($format = 'Y-m-d H:i:s')
    {

        if ($this->pro_create_date === null || $this->pro_create_date === '') {
            return null;
        } elseif (!is_int($this->pro_create_date)) {
            // a non-timestamp value was set externally, so we convert it
            $ts = strtotime($this->pro_create_date);
            if ($ts === -1 || $ts === false) {
                throw new PropelException("Unable to parse value of [pro_create_date] as date/time value: " .
                    var_export($this->pro_create_date, true));
            }
        } else {
            $ts = $this->pro_create_date;
        }
        if ($format === null) {
            return $ts;
        } elseif (strpos($format, '%') !== false) {
            return strftime($format, $ts);
        } else {
            return date($format, $ts);
        }
    }

    /**
     * Get the [pro_create_user] column value.
     * 
     * @return     string
     */
    public function getProCreateUser()
    {

        return $this->pro_create_user;
    }

    /**
     * Get the [pro_height] column value.
     * 
     * @return     int
     */
    public function getProHeight()
    {

        return $this->pro_height;
    }

    /**
     * Get the [pro_width] column value.
     * 
     * @return     int
     */
    public function getProWidth()
    {

        return $this->pro_width;
    }

    /**
     * Get the [pro_title_x] column value.
     * 
     * @return     int
     */
    public function getProTitleX()
    {

        return $this->pro_title_x;
    }

    /**
     * Get the [pro_title_y] column value.
     * 
     * @return     int
     */
    public function getProTitleY()
    {

        return $this->pro_title_y;
    }

    /**
     * Get the [pro_debug] column value.
     * 
     * @return     int
     */
    public function getProDebug()
    {

        return $this->pro_debug;
    }

    /**
     * Get the [pro_dynaforms] column value.
     * 
     * @return     string
     */
    public function getProDynaforms()
    {

        return $this->pro_dynaforms;
    }

    /**
     * Get the [pro_derivation_screen_tpl] column value.
     * 
     * @return     string
     */
    public function getProDerivationScreenTpl()
    {

        return $this->pro_derivation_screen_tpl;
    }

    /**
     * Get the [pro_cost] column value.
     * 
     * @return     double
     */
    public function getProCost()
    {

        return $this->pro_cost;
    }

    /**
     * Get the [pro_unit_cost] column value.
     * 
     * @return     string
     */
    public function getProUnitCost()
    {

        return $this->pro_unit_cost;
    }

    /**
     * Get the [pro_itee] column value.
     * 
     * @return     int
     */
    public function getProItee()
    {

        return $this->pro_itee;
    }

    /**
     * Get the [pro_action_done] column value.
     * 
     * @return     string
     */
    public function getProActionDone()
    {

        return $this->pro_action_done;
    }

    /**
     * Get the [category_id] column value.
     * 
     * @return     int
     */
    public function getCategoryId()
    {

        return $this->category_id;
    }

    /**
     * Set the value of [pro_uid] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProUid($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_uid !== $v || $v === '') {
            $this->pro_uid = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_UID;
        }

    } // setProUid()

    /**
     * Set the value of [pro_id] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProId($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_id !== $v) {
            $this->pro_id = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_ID;
        }

    } // setProId()

    /**
     * Set the value of [pro_title] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTitle($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_title !== $v) {
            $this->pro_title = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TITLE;
        }

    } // setProTitle()

    /**
     * Set the value of [pro_description] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProDescription($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_description !== $v) {
            $this->pro_description = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_DESCRIPTION;
        }

    } // setProDescription()

    /**
     * Set the value of [pro_parent] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProParent($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_parent !== $v || $v === '0') {
            $this->pro_parent = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_PARENT;
        }

    } // setProParent()

    /**
     * Set the value of [pro_time] column.
     * 
     * @param      double $v new value
     * @return     void
     */
    public function setProTime($v)
    {

        if ($this->pro_time !== $v || $v === 1.0) {
            $this->pro_time = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TIME;
        }

    } // setProTime()

    /**
     * Set the value of [pro_timeunit] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTimeunit($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_timeunit !== $v || $v === 'DAYS') {
            $this->pro_timeunit = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TIMEUNIT;
        }

    } // setProTimeunit()

    /**
     * Set the value of [pro_status] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProStatus($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_status !== $v || $v === 'ACTIVE') {
            $this->pro_status = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_STATUS;
        }

    } // setProStatus()

    /**
     * Set the value of [pro_status_id] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProStatusId($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_status_id !== $v || $v === 1) {
            $this->pro_status_id = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_STATUS_ID;
        }

    } // setProStatusId()

    /**
     * Set the value of [pro_type_day] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTypeDay($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_type_day !== $v || $v === '0') {
            $this->pro_type_day = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TYPE_DAY;
        }

    } // setProTypeDay()

    /**
     * Set the value of [pro_type] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProType($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_type !== $v || $v === 'NORMAL') {
            $this->pro_type = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TYPE;
        }

    } // setProType()

    /**
     * Set the value of [pro_assignment] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProAssignment($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_assignment !== $v || $v === 'FALSE') {
            $this->pro_assignment = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_ASSIGNMENT;
        }

    } // setProAssignment()

    /**
     * Set the value of [pro_show_map] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProShowMap($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_show_map !== $v || $v === 1) {
            $this->pro_show_map = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SHOW_MAP;
        }

    } // setProShowMap()

    /**
     * Set the value of [pro_show_message] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProShowMessage($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_show_message !== $v || $v === 1) {
            $this->pro_show_message = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SHOW_MESSAGE;
        }

    } // setProShowMessage()

    /**
     * Set the value of [pro_subprocess] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProSubprocess($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_subprocess !== $v || $v === 0) {
            $this->pro_subprocess = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SUBPROCESS;
        }

    } // setProSubprocess()

    /**
     * Set the value of [pro_tri_create] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriCreate($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_create !== $v || $v === '') {
            $this->pro_tri_create = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_CREATE;
        }

    } // setProTriCreate()

    /**
     * Set the value of [pro_tri_open] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriOpen($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_open !== $v || $v === '') {
            $this->pro_tri_open = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_OPEN;
        }

    } // setProTriOpen()

    /**
     * Set the value of [pro_tri_deleted] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriDeleted($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_deleted !== $v || $v === '') {
            $this->pro_tri_deleted = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_DELETED;
        }

    } // setProTriDeleted()

    /**
     * Set the value of [pro_tri_canceled] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriCanceled($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_canceled !== $v || $v === '') {
            $this->pro_tri_canceled = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_CANCELED;
        }

    } // setProTriCanceled()

    /**
     * Set the value of [pro_tri_paused] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriPaused($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_paused !== $v || $v === '') {
            $this->pro_tri_paused = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_PAUSED;
        }

    } // setProTriPaused()

    /**
     * Set the value of [pro_tri_reassigned] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriReassigned($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_reassigned !== $v || $v === '') {
            $this->pro_tri_reassigned = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_REASSIGNED;
        }

    } // setProTriReassigned()

    /**
     * Set the value of [pro_tri_unpaused] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTriUnpaused($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_tri_unpaused !== $v || $v === '') {
            $this->pro_tri_unpaused = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TRI_UNPAUSED;
        }

    } // setProTriUnpaused()

    /**
     * Set the value of [pro_type_process] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProTypeProcess($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_type_process !== $v || $v === 'PUBLIC') {
            $this->pro_type_process = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TYPE_PROCESS;
        }

    } // setProTypeProcess()

    /**
     * Set the value of [pro_show_delegate] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProShowDelegate($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_show_delegate !== $v || $v === 1) {
            $this->pro_show_delegate = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SHOW_DELEGATE;
        }

    } // setProShowDelegate()

    /**
     * Set the value of [pro_show_dynaform] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProShowDynaform($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_show_dynaform !== $v || $v === 0) {
            $this->pro_show_dynaform = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SHOW_DYNAFORM;
        }

    } // setProShowDynaform()

    /**
     * Set the value of [pro_category] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProCategory($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_category !== $v || $v === '') {
            $this->pro_category = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_CATEGORY;
        }

    } // setProCategory()

    /**
     * Set the value of [pro_sub_category] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProSubCategory($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_sub_category !== $v || $v === '') {
            $this->pro_sub_category = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_SUB_CATEGORY;
        }

    } // setProSubCategory()

    /**
     * Set the value of [pro_industry] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProIndustry($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_industry !== $v || $v === 1) {
            $this->pro_industry = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_INDUSTRY;
        }

    } // setProIndustry()

    /**
     * Set the value of [pro_update_date] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProUpdateDate($v)
    {

        if ($v !== null && !is_int($v)) {
            $ts = strtotime($v);
            //Date/time accepts null values
            if ($v == '') {
                $ts = null;
            }
            if ($ts === -1 || $ts === false) {
                throw new PropelException("Unable to parse date/time value for [pro_update_date] from input: " .
                    var_export($v, true));
            }
        } else {
            $ts = $v;
        }
        if ($this->pro_update_date !== $ts) {
            $this->pro_update_date = $ts;
            $this->modifiedColumns[] = ProcessPeer::PRO_UPDATE_DATE;
        }

    } // setProUpdateDate()

    /**
     * Set the value of [pro_create_date] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProCreateDate($v)
    {

        if ($v !== null && !is_int($v)) {
            $ts = strtotime($v);
            //Date/time accepts null values
            if ($v == '') {
                $ts = null;
            }
            if ($ts === -1 || $ts === false) {
                throw new PropelException("Unable to parse date/time value for [pro_create_date] from input: " .
                    var_export($v, true));
            }
        } else {
            $ts = $v;
        }
        if ($this->pro_create_date !== $ts) {
            $this->pro_create_date = $ts;
            $this->modifiedColumns[] = ProcessPeer::PRO_CREATE_DATE;
        }

    } // setProCreateDate()

    /**
     * Set the value of [pro_create_user] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProCreateUser($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_create_user !== $v || $v === '') {
            $this->pro_create_user = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_CREATE_USER;
        }

    } // setProCreateUser()

    /**
     * Set the value of [pro_height] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProHeight($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_height !== $v || $v === 5000) {
            $this->pro_height = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_HEIGHT;
        }

    } // setProHeight()

    /**
     * Set the value of [pro_width] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProWidth($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_width !== $v || $v === 10000) {
            $this->pro_width = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_WIDTH;
        }

    } // setProWidth()

    /**
     * Set the value of [pro_title_x] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProTitleX($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_title_x !== $v || $v === 0) {
            $this->pro_title_x = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TITLE_X;
        }

    } // setProTitleX()

    /**
     * Set the value of [pro_title_y] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProTitleY($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_title_y !== $v || $v === 6) {
            $this->pro_title_y = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_TITLE_Y;
        }

    } // setProTitleY()

    /**
     * Set the value of [pro_debug] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProDebug($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_debug !== $v || $v === 0) {
            $this->pro_debug = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_DEBUG;
        }

    } // setProDebug()

    /**
     * Set the value of [pro_dynaforms] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProDynaforms($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_dynaforms !== $v) {
            $this->pro_dynaforms = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_DYNAFORMS;
        }

    } // setProDynaforms()

    /**
     * Set the value of [pro_derivation_screen_tpl] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProDerivationScreenTpl($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_derivation_screen_tpl !== $v || $v === '') {
            $this->pro_derivation_screen_tpl = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_DERIVATION_SCREEN_TPL;
        }

    } // setProDerivationScreenTpl()

    /**
     * Set the value of [pro_cost] column.
     * 
     * @param      double $v new value
     * @return     void
     */
    public function setProCost($v)
    {

        if ($this->pro_cost !== $v || $v === 0.0) {
            $this->pro_cost = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_COST;
        }

    } // setProCost()

    /**
     * Set the value of [pro_unit_cost] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProUnitCost($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_unit_cost !== $v || $v === '') {
            $this->pro_unit_cost = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_UNIT_COST;
        }

    } // setProUnitCost()

    /**
     * Set the value of [pro_itee] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setProItee($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->pro_itee !== $v || $v === 0) {
            $this->pro_itee = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_ITEE;
        }

    } // setProItee()

    /**
     * Set the value of [pro_action_done] column.
     * 
     * @param      string $v new value
     * @return     void
     */
    public function setProActionDone($v)
    {

        // Since the native PHP type for this column is string,
        // we will cast the input to a string (if it is not).
        if ($v !== null && !is_string($v)) {
            $v = (string) $v;
        }

        if ($this->pro_action_done !== $v) {
            $this->pro_action_done = $v;
            $this->modifiedColumns[] = ProcessPeer::PRO_ACTION_DONE;
        }

    } // setProActionDone()

    /**
     * Set the value of [category_id] column.
     * 
     * @param      int $v new value
     * @return     void
     */
    public function setCategoryId($v)
    {

        // Since the native PHP type for this column is integer,
        // we will cast the input value to an int (if it is not).
        if ($v !== null && !is_int($v) && is_numeric($v)) {
            $v = (int) $v;
        }

        if ($this->category_id !== $v || $v === 0) {
            $this->category_id = $v;
            $this->modifiedColumns[] = ProcessPeer::CATEGORY_ID;
        }

    } // setCategoryId()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (1-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param      ResultSet $rs The ResultSet class with cursor advanced to desired record pos.
     * @param      int $startcol 1-based offset column which indicates which restultset column to start with.
     * @return     int next starting column
     * @throws     PropelException  - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate(ResultSet $rs, $startcol = 1)
    {
        try {

            $this->pro_uid = $rs->getString($startcol + 0);

            $this->pro_id = $rs->getInt($startcol + 1);

            $this->pro_title = $rs->getString($startcol + 2);

            $this->pro_description = $rs->getString($startcol + 3);

            $this->pro_parent = $rs->getString($startcol + 4);

            $this->pro_time = $rs->getFloat($startcol + 5);

            $this->pro_timeunit = $rs->getString($startcol + 6);

            $this->pro_status = $rs->getString($startcol + 7);

            $this->pro_status_id = $rs->getInt($startcol + 8);

            $this->pro_type_day = $rs->getString($startcol + 9);

            $this->pro_type = $rs->getString($startcol + 10);

            $this->pro_assignment = $rs->getString($startcol + 11);

            $this->pro_show_map = $rs->getInt($startcol + 12);

            $this->pro_show_message = $rs->getInt($startcol + 13);

            $this->pro_subprocess = $rs->getInt($startcol + 14);

            $this->pro_tri_create = $rs->getString($startcol + 15);

            $this->pro_tri_open = $rs->getString($startcol + 16);

            $this->pro_tri_deleted = $rs->getString($startcol + 17);

            $this->pro_tri_canceled = $rs->getString($startcol + 18);

            $this->pro_tri_paused = $rs->getString($startcol + 19);

            $this->pro_tri_reassigned = $rs->getString($startcol + 20);

            $this->pro_tri_unpaused = $rs->getString($startcol + 21);

            $this->pro_type_process = $rs->getString($startcol + 22);

            $this->pro_show_delegate = $rs->getInt($startcol + 23);

            $this->pro_show_dynaform = $rs->getInt($startcol + 24);

            $this->pro_category = $rs->getString($startcol + 25);

            $this->pro_sub_category = $rs->getString($startcol + 26);

            $this->pro_industry = $rs->getInt($startcol + 27);

            $this->pro_update_date = $rs->getTimestamp($startcol + 28, null);

            $this->pro_create_date = $rs->getTimestamp($startcol + 29, null);

            $this->pro_create_user = $rs->getString($startcol + 30);

            $this->pro_height = $rs->getInt($startcol + 31);

            $this->pro_width = $rs->getInt($startcol + 32);

            $this->pro_title_x = $rs->getInt($startcol + 33);

            $this->pro_title_y = $rs->getInt($startcol + 34);

            $this->pro_debug = $rs->getInt($startcol + 35);

            $this->pro_dynaforms = $rs->getString($startcol + 36);

            $this->pro_derivation_screen_tpl = $rs->getString($startcol + 37);

            $this->pro_cost = $rs->getFloat($startcol + 38);

            $this->pro_unit_cost = $rs->getString($startcol + 39);

            $this->pro_itee = $rs->getInt($startcol + 40);

            $this->pro_action_done = $rs->getString($startcol + 41);

            $this->category_id = $rs->getInt($startcol + 42);

            $this->resetModified();

            $this->setNew(false);

            // FIXME - using NUM_COLUMNS may be clearer.
            return $startcol + 43; // 43 = ProcessPeer::NUM_COLUMNS - ProcessPeer::NUM_LAZY_LOAD_COLUMNS).

        } catch (Exception $e) {
            throw new PropelException("Error populating Process object", $e);
        }
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      Connection $con
     * @return     void
     * @throws     PropelException
     * @see        BaseObject::setDeleted()
     * @see        BaseObject::isDeleted()
     */
    public function delete($con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getConnection(ProcessPeer::DATABASE_NAME);
        }

        try {
            $con->begin();
            ProcessPeer::doDelete($this, $con);
            $this->setDeleted(true);
            $con->commit();
        } catch (PropelException $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Stores the object in the database.  If the object is new,
     * it inserts it; otherwise an update is performed.  This method
     * wraps the doSave() worker method in a transaction.
     *
     * @param      Connection $con
     * @return     int The number of rows affected by this insert/update
     * @throws     PropelException
     * @see        doSave()
     */
    public function save($con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getConnection(ProcessPeer::DATABASE_NAME);
        }

        try {
            $con->begin();
            $affectedRows = $this->doSave($con);
            $con->commit();
            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Stores the object in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      Connection $con
     * @return     int The number of rows affected by this insert/update and any referring
     * @throws     PropelException
     * @see        save()
     */
    protected function doSave($con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;


            // If this object has been modified, then save it to the database.
            if ($this->isModified()) {
                if ($this->isNew()) {
                    $pk = ProcessPeer::doInsert($this, $con);
                    $affectedRows += 1; // we are assuming that there is only 1 row per doInsert() which
                                         // should always be true here (even though technically
                                         // BasePeer::doInsert() can insert multiple rows).

                    $this->setNew(false);
                } else {
                    $affectedRows += ProcessPeer::doUpdate($this, $con);
                }
                $this->resetModified(); // [HL] After being saved an object is no longer 'modified'
            }

            $this->alreadyInSave = false;
        }
        return $affectedRows;
    } // doSave()

    /**
     * Array of ValidationFailed objects.
     * @var        array ValidationFailed[]
     */
    protected $validationFailures = array();

    /**
     * Gets any ValidationFailed objects that resulted from last call to validate().
     *
     *
     * @return     array ValidationFailed[]
     * @see        validate()
     */
    public function getValidationFailures()
    {
        return $this->validationFailures;
    }

    /**
     * Validates the objects modified field values and all objects related to this table.
     *
     * If $columns is either a column name or an array of column names
     * only those columns are validated.
     *
     * @param      mixed $columns Column name or an array of column names.
     * @return     boolean Whether all columns pass validation.
     * @see        doValidate()
     * @see        getValidationFailures()
     */
    public function validate($columns = null)
    {
        $res = $this->doValidate($columns);
        if ($res === true) {
            $this->validationFailures = array();
            return true;
        } else {
            $this->validationFailures = $res;
            return false;
        }
    }

    /**
     * This function performs the validation work for complex object models.
     *
     * In addition to checking the current object, all related objects will
     * also be validated.  If all pass then <code>true</code> is returned; otherwise
     * an aggreagated array of ValidationFailed objects will be returned.
     *
     * @param      array $columns Array of column names to validate.
     * @return     mixed <code>true</code> if all validations pass; 
                   array of <code>ValidationFailed</code> objects otherwise.
     */
    protected function doValidate($columns = null)
    {
        if (!$this->alreadyInValidation) {
            $this->alreadyInValidation = true;
            $retval = null;

            $failureMap = array();


            if (($retval = ProcessPeer::doValidate($this, $columns)) !== true) {
                $failureMap = array_merge($failureMap, $retval);
            }



            $this->alreadyInValidation = false;
        }

        return (!empty($failureMap) ? $failureMap : true);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TYPE_PHPNAME,
     *                     TYPE_COLNAME, TYPE_FIELDNAME, TYPE_NUM
     * @return     mixed Value of field.
     */
    public function getByName($name, $type = BasePeer::TYPE_PHPNAME)
    {
        $pos = ProcessPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
        return $this->getByPosition($pos);
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return     mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch($pos) {
            case 0:
                return $this->getProUid();
                break;
            case 1:
                return $this->getProId();
                break;
            case 2:
                return $this->getProTitle();
                break;
            case 3:
                return $this->getProDescription();
                break;
            case 4:
                return $this->getProParent();
                break;
            case 5:
                return $this->getProTime();
                break;
            case 6:
                return $this->getProTimeunit();
                break;
            case 7:
                return $this->getProStatus();
                break;
            case 8:
                return $this->getProStatusId();
                break;
            case 9:
                return $this->getProTypeDay();
                break;
            case 10:
                return $this->getProType();
                break;
            case 11:
                return $this->getProAssignment();
                break;
            case 12:
                return $this->getProShowMap();
                break;
            case 13:
                return $this->getProShowMessage();
                break;
            case 14:
                return $this->getProSubprocess();
                break;
            case 15:
                return $this->getProTriCreate();
                break;
            case 16:
                return $this->getProTriOpen();
                break;
            case 17:
                return $this->getProTriDeleted();
                break;
            case 18:
                return $this->getProTriCanceled();
                break;
            case 19:
                return $this->getProTriPaused();
                break;
            case 20:
                return $this->getProTriReassigned();
                break;
            case 21:
                return $this->getProTriUnpaused();
                break;
            case 22:
                return $this->getProTypeProcess();
                break;
            case 23:
                return $this->getProShowDelegate();
                break;
            case 24:
                return $this->getProShowDynaform();
                break;
            case 25:
                return $this->getProCategory();
                break;
            case 26:
                return $this->getProSubCategory();
                break;
            case 27:
                return $this->getProIndustry();
                break;
            case 28:
                return $this->getProUpdateDate();
                break;
            case 29:
                return $this->getProCreateDate();
                break;
            case 30:
                return $this->getProCreateUser();
                break;
            case 31:
                return $this->getProHeight();
                break;
            case 32:
                return $this->getProWidth();
                break;
            case 33:
                return $this->getProTitleX();
                break;
            case 34:
                return $this->getProTitleY();
                break;
            case 35:
                return $this->getProDebug();
                break;
            case 36:
                return $this->getProDynaforms();
                break;
            case 37:
                return $this->getProDerivationScreenTpl();
                break;
            case 38:
                return $this->getProCost();
                break;
            case 39:
                return $this->getProUnitCost();
                break;
            case 40:
                return $this->getProItee();
                break;
            case 41:
                return $this->getProActionDone();
                break;
            case 42:
                return $this->getCategoryId();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param      string $keyType One of the class type constants TYPE_PHPNAME,
     *                        TYPE_COLNAME, TYPE_FIELDNAME, TYPE_NUM
     * @return     an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = BasePeer::TYPE_PHPNAME)
    {
        $keys = ProcessPeer::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getProUid(),
            $keys[1] => $this->getProId(),
            $keys[2] => $this->getProTitle(),
            $keys[3] => $this->getProDescription(),
            $keys[4] => $this->getProParent(),
            $keys[5] => $this->getProTime(),
            $keys[6] => $this->getProTimeunit(),
            $keys[7] => $this->getProStatus(),
            $keys[8] => $this->getProStatusId(),
            $keys[9] => $this->getProTypeDay(),
            $keys[10] => $this->getProType(),
            $keys[11] => $this->getProAssignment(),
            $keys[12] => $this->getProShowMap(),
            $keys[13] => $this->getProShowMessage(),
            $keys[14] => $this->getProSubprocess(),
            $keys[15] => $this->getProTriCreate(),
            $keys[16] => $this->getProTriOpen(),
            $keys[17] => $this->getProTriDeleted(),
            $keys[18] => $this->getProTriCanceled(),
            $keys[19] => $this->getProTriPaused(),
            $keys[20] => $this->getProTriReassigned(),
            $keys[21] => $this->getProTriUnpaused(),
            $keys[22] => $this->getProTypeProcess(),
            $keys[23] => $this->getProShowDelegate(),
            $keys[24] => $this->getProShowDynaform(),
            $keys[25] => $this->getProCategory(),
            $keys[26] => $this->getProSubCategory(),
            $keys[27] => $this->getProIndustry(),
            $keys[28] => $this->getProUpdateDate(),
            $keys[29] => $this->getProCreateDate(),
            $keys[30] => $this->getProCreateUser(),
            $keys[31] => $this->getProHeight(),
            $keys[32] => $this->getProWidth(),
            $keys[33] => $this->getProTitleX(),
            $keys[34] => $this->getProTitleY(),
            $keys[35] => $this->getProDebug(),
            $keys[36] => $this->getProDynaforms(),
            $keys[37] => $this->getProDerivationScreenTpl(),
            $keys[38] => $this->getProCost(),
            $keys[39] => $this->getProUnitCost(),
            $keys[40] => $this->getProItee(),
            $keys[41] => $this->getProActionDone(),
            $keys[42] => $this->getCategoryId(),
        );
        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name peer name
     * @param      mixed $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TYPE_PHPNAME,
     *                     TYPE_COLNAME, TYPE_FIELDNAME, TYPE_NUM
     * @return     void
     */
    public function setByName($name, $value, $type = BasePeer::TYPE_PHPNAME)
    {
        $pos = ProcessPeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return     void
     */
    public function setByPosition($pos, $value)
    {
        switch($pos) {
            case 0:
                $this->setProUid($value);
                break;
            case 1:
                $this->setProId($value);
                break;
            case 2:
                $this->setProTitle($value);
                break;
            case 3:
                $this->setProDescription($value);
                break;
            case 4:
                $this->setProParent($value);
                break;
            case 5:
                $this->setProTime($value);
                break;
            case 6:
                $this->setProTimeunit($value);
                break;
            case 7:
                $this->setProStatus($value);
                break;
            case 8:
                $this->setProStatusId($value);
                break;
            case 9:
                $this->setProTypeDay($value);
                break;
            case 10:
                $this->setProType($value);
                break;
            case 11:
                $this->setProAssignment($value);
                break;
            case 12:
                $this->setProShowMap($value);
                break;
            case 13:
                $this->setProShowMessage($value);
                break;
            case 14:
                $this->setProSubprocess($value);
                break;
            case 15:
                $this->setProTriCreate($value);
                break;
            case 16:
                $this->setProTriOpen($value);
                break;
            case 17:
                $this->setProTriDeleted($value);
                break;
            case 18:
                $this->setProTriCanceled($value);
                break;
            case 19:
                $this->setProTriPaused($value);
                break;
            case 20:
                $this->setProTriReassigned($value);
                break;
            case 21:
                $this->setProTriUnpaused($value);
                break;
            case 22:
                $this->setProTypeProcess($value);
                break;
            case 23:
                $this->setProShowDelegate($value);
                break;
            case 24:
                $this->setProShowDynaform($value);
                break;
            case 25:
                $this->setProCategory($value);
                break;
            case 26:
                $this->setProSubCategory($value);
                break;
            case 27:
                $this->setProIndustry($value);
                break;
            case 28:
                $this->setProUpdateDate($value);
                break;
            case 29:
                $this->setProCreateDate($value);
                break;
            case 30:
                $this->setProCreateUser($value);
                break;
            case 31:
                $this->setProHeight($value);
                break;
            case 32:
                $this->setProWidth($value);
                break;
            case 33:
                $this->setProTitleX($value);
                break;
            case 34:
                $this->setProTitleY($value);
                break;
            case 35:
                $this->setProDebug($value);
                break;
            case 36:
                $this->setProDynaforms($value);
                break;
            case 37:
                $this->setProDerivationScreenTpl($value);
                break;
            case 38:
                $this->setProCost($value);
                break;
            case 39:
                $this->setProUnitCost($value);
                break;
            case 40:
                $this->setProItee($value);
                break;
            case 41:
                $this->setProActionDone($value);
                break;
            case 42:
                $this->setCategoryId($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TYPE_PHPNAME, TYPE_COLNAME, TYPE_FIELDNAME,
     * TYPE_NUM. The default key type is the column's phpname (e.g. 'authorId')
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return     void
     */
    public function fromArray($arr, $keyType = BasePeer::TYPE_PHPNAME)
    {
        $keys = ProcessPeer::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setProUid($arr[$keys[0]]);
        }

        if (array_key_exists($keys[1], $arr)) {
            $this->setProId($arr[$keys[1]]);
        }

        if (array_key_exists($keys[2], $arr)) {
            $this->setProTitle($arr[$keys[2]]);
        }

        if (array_key_exists($keys[3], $arr)) {
            $this->setProDescription($arr[$keys[3]]);
        }

        if (array_key_exists($keys[4], $arr)) {
            $this->setProParent($arr[$keys[4]]);
        }

        if (array_key_exists($keys[5], $arr)) {
            $this->setProTime($arr[$keys[5]]);
        }

        if (array_key_exists($keys[6], $arr)) {
            $this->setProTimeunit($arr[$keys[6]]);
        }

        if (array_key_exists($keys[7], $arr)) {
            $this->setProStatus($arr[$keys[7]]);
        }

        if (array_key_exists($keys[8], $arr)) {
            $this->setProStatusId($arr[$keys[8]]);
        }

        if (array_key_exists($keys[9], $arr)) {
            $this->setProTypeDay($arr[$keys[9]]);
        }

        if (array_key_exists($keys[10], $arr)) {
            $this->setProType($arr[$keys[10]]);
        }

        if (array_key_exists($keys[11], $arr)) {
            $this->setProAssignment($arr[$keys[11]]);
        }

        if (array_key_exists($keys[12], $arr)) {
            $this->setProShowMap($arr[$keys[12]]);
        }

        if (array_key_exists($keys[13], $arr)) {
            $this->setProShowMessage($arr[$keys[13]]);
        }

        if (array_key_exists($keys[14], $arr)) {
            $this->setProSubprocess($arr[$keys[14]]);
        }

        if (array_key_exists($keys[15], $arr)) {
            $this->setProTriCreate($arr[$keys[15]]);
        }

        if (array_key_exists($keys[16], $arr)) {
            $this->setProTriOpen($arr[$keys[16]]);
        }

        if (array_key_exists($keys[17], $arr)) {
            $this->setProTriDeleted($arr[$keys[17]]);
        }

        if (array_key_exists($keys[18], $arr)) {
            $this->setProTriCanceled($arr[$keys[18]]);
        }

        if (array_key_exists($keys[19], $arr)) {
            $this->setProTriPaused($arr[$keys[19]]);
        }

        if (array_key_exists($keys[20], $arr)) {
            $this->setProTriReassigned($arr[$keys[20]]);
        }

        if (array_key_exists($keys[21], $arr)) {
            $this->setProTriUnpaused($arr[$keys[21]]);
        }

        if (array_key_exists($keys[22], $arr)) {
            $this->setProTypeProcess($arr[$keys[22]]);
        }

        if (array_key_exists($keys[23], $arr)) {
            $this->setProShowDelegate($arr[$keys[23]]);
        }

        if (array_key_exists($keys[24], $arr)) {
            $this->setProShowDynaform($arr[$keys[24]]);
        }

        if (array_key_exists($keys[25], $arr)) {
            $this->setProCategory($arr[$keys[25]]);
        }

        if (array_key_exists($keys[26], $arr)) {
            $this->setProSubCategory($arr[$keys[26]]);
        }

        if (array_key_exists($keys[27], $arr)) {
            $this->setProIndustry($arr[$keys[27]]);
        }

        if (array_key_exists($keys[28], $arr)) {
            $this->setProUpdateDate($arr[$keys[28]]);
        }

        if (array_key_exists($keys[29], $arr)) {
            $this->setProCreateDate($arr[$keys[29]]);
        }

        if (array_key_exists($keys[30], $arr)) {
            $this->setProCreateUser($arr[$keys[30]]);
        }

        if (array_key_exists($keys[31], $arr)) {
            $this->setProHeight($arr[$keys[31]]);
        }

        if (array_key_exists($keys[32], $arr)) {
            $this->setProWidth($arr[$keys[32]]);
        }

        if (array_key_exists($keys[33], $arr)) {
            $this->setProTitleX($arr[$keys[33]]);
        }

        if (array_key_exists($keys[34], $arr)) {
            $this->setProTitleY($arr[$keys[34]]);
        }

        if (array_key_exists($keys[35], $arr)) {
            $this->setProDebug($arr[$keys[35]]);
        }

        if (array_key_exists($keys[36], $arr)) {
            $this->setProDynaforms($arr[$keys[36]]);
        }

        if (array_key_exists($keys[37], $arr)) {
            $this->setProDerivationScreenTpl($arr[$keys[37]]);
        }

        if (array_key_exists($keys[38], $arr)) {
            $this->setProCost($arr[$keys[38]]);
        }

        if (array_key_exists($keys[39], $arr)) {
            $this->setProUnitCost($arr[$keys[39]]);
        }

        if (array_key_exists($keys[40], $arr)) {
            $this->setProItee($arr[$keys[40]]);
        }

        if (array_key_exists($keys[41], $arr)) {
            $this->setProActionDone($arr[$keys[41]]);
        }

        if (array_key_exists($keys[42], $arr)) {
            $this->setCategoryId($arr[$keys[42]]);
        }

    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return     Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(ProcessPeer::DATABASE_NAME);

        if ($this->isColumnModified(ProcessPeer::PRO_UID)) {
            $criteria->add(ProcessPeer::PRO_UID, $this->pro_uid);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_ID)) {
            $criteria->add(ProcessPeer::PRO_ID, $this->pro_id);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TITLE)) {
            $criteria->add(ProcessPeer::PRO_TITLE, $this->pro_title);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_DESCRIPTION)) {
            $criteria->add(ProcessPeer::PRO_DESCRIPTION, $this->pro_description);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_PARENT)) {
            $criteria->add(ProcessPeer::PRO_PARENT, $this->pro_parent);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TIME)) {
            $criteria->add(ProcessPeer::PRO_TIME, $this->pro_time);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TIMEUNIT)) {
            $criteria->add(ProcessPeer::PRO_TIMEUNIT, $this->pro_timeunit);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_STATUS)) {
            $criteria->add(ProcessPeer::PRO_STATUS, $this->pro_status);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_STATUS_ID)) {
            $criteria->add(ProcessPeer::PRO_STATUS_ID, $this->pro_status_id);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TYPE_DAY)) {
            $criteria->add(ProcessPeer::PRO_TYPE_DAY, $this->pro_type_day);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TYPE)) {
            $criteria->add(ProcessPeer::PRO_TYPE, $this->pro_type);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_ASSIGNMENT)) {
            $criteria->add(ProcessPeer::PRO_ASSIGNMENT, $this->pro_assignment);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SHOW_MAP)) {
            $criteria->add(ProcessPeer::PRO_SHOW_MAP, $this->pro_show_map);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SHOW_MESSAGE)) {
            $criteria->add(ProcessPeer::PRO_SHOW_MESSAGE, $this->pro_show_message);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SUBPROCESS)) {
            $criteria->add(ProcessPeer::PRO_SUBPROCESS, $this->pro_subprocess);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_CREATE)) {
            $criteria->add(ProcessPeer::PRO_TRI_CREATE, $this->pro_tri_create);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_OPEN)) {
            $criteria->add(ProcessPeer::PRO_TRI_OPEN, $this->pro_tri_open);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_DELETED)) {
            $criteria->add(ProcessPeer::PRO_TRI_DELETED, $this->pro_tri_deleted);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_CANCELED)) {
            $criteria->add(ProcessPeer::PRO_TRI_CANCELED, $this->pro_tri_canceled);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_PAUSED)) {
            $criteria->add(ProcessPeer::PRO_TRI_PAUSED, $this->pro_tri_paused);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_REASSIGNED)) {
            $criteria->add(ProcessPeer::PRO_TRI_REASSIGNED, $this->pro_tri_reassigned);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TRI_UNPAUSED)) {
            $criteria->add(ProcessPeer::PRO_TRI_UNPAUSED, $this->pro_tri_unpaused);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TYPE_PROCESS)) {
            $criteria->add(ProcessPeer::PRO_TYPE_PROCESS, $this->pro_type_process);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SHOW_DELEGATE)) {
            $criteria->add(ProcessPeer::PRO_SHOW_DELEGATE, $this->pro_show_delegate);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SHOW_DYNAFORM)) {
            $criteria->add(ProcessPeer::PRO_SHOW_DYNAFORM, $this->pro_show_dynaform);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_CATEGORY)) {
            $criteria->add(ProcessPeer::PRO_CATEGORY, $this->pro_category);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_SUB_CATEGORY)) {
            $criteria->add(ProcessPeer::PRO_SUB_CATEGORY, $this->pro_sub_category);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_INDUSTRY)) {
            $criteria->add(ProcessPeer::PRO_INDUSTRY, $this->pro_industry);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_UPDATE_DATE)) {
            $criteria->add(ProcessPeer::PRO_UPDATE_DATE, $this->pro_update_date);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_CREATE_DATE)) {
            $criteria->add(ProcessPeer::PRO_CREATE_DATE, $this->pro_create_date);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_CREATE_USER)) {
            $criteria->add(ProcessPeer::PRO_CREATE_USER, $this->pro_create_user);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_HEIGHT)) {
            $criteria->add(ProcessPeer::PRO_HEIGHT, $this->pro_height);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_WIDTH)) {
            $criteria->add(ProcessPeer::PRO_WIDTH, $this->pro_width);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TITLE_X)) {
            $criteria->add(ProcessPeer::PRO_TITLE_X, $this->pro_title_x);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_TITLE_Y)) {
            $criteria->add(ProcessPeer::PRO_TITLE_Y, $this->pro_title_y);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_DEBUG)) {
            $criteria->add(ProcessPeer::PRO_DEBUG, $this->pro_debug);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_DYNAFORMS)) {
            $criteria->add(ProcessPeer::PRO_DYNAFORMS, $this->pro_dynaforms);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_DERIVATION_SCREEN_TPL)) {
            $criteria->add(ProcessPeer::PRO_DERIVATION_SCREEN_TPL, $this->pro_derivation_screen_tpl);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_COST)) {
            $criteria->add(ProcessPeer::PRO_COST, $this->pro_cost);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_UNIT_COST)) {
            $criteria->add(ProcessPeer::PRO_UNIT_COST, $this->pro_unit_cost);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_ITEE)) {
            $criteria->add(ProcessPeer::PRO_ITEE, $this->pro_itee);
        }

        if ($this->isColumnModified(ProcessPeer::PRO_ACTION_DONE)) {
            $criteria->add(ProcessPeer::PRO_ACTION_DONE, $this->pro_action_done);
        }

        if ($this->isColumnModified(ProcessPeer::CATEGORY_ID)) {
            $criteria->add(ProcessPeer::CATEGORY_ID, $this->category_id);
        }


        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return     Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(ProcessPeer::DATABASE_NAME);

        $criteria->add(ProcessPeer::PRO_UID, $this->pro_uid);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return     string
     */
    public function getPrimaryKey()
    {
        return $this->getProUid();
    }

    /**
     * Generic method to set the primary key (pro_uid column).
     *
     * @param      string $key Primary key.
     * @return     void
     */
    public function setPrimaryKey($key)
    {
        $this->setProUid($key);
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of Process (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @throws     PropelException
     */
    public function copyInto($copyObj, $deepCopy = false)
    {

        $copyObj->setProId($this->pro_id);

        $copyObj->setProTitle($this->pro_title);

        $copyObj->setProDescription($this->pro_description);

        $copyObj->setProParent($this->pro_parent);

        $copyObj->setProTime($this->pro_time);

        $copyObj->setProTimeunit($this->pro_timeunit);

        $copyObj->setProStatus($this->pro_status);

        $copyObj->setProStatusId($this->pro_status_id);

        $copyObj->setProTypeDay($this->pro_type_day);

        $copyObj->setProType($this->pro_type);

        $copyObj->setProAssignment($this->pro_assignment);

        $copyObj->setProShowMap($this->pro_show_map);

        $copyObj->setProShowMessage($this->pro_show_message);

        $copyObj->setProSubprocess($this->pro_subprocess);

        $copyObj->setProTriCreate($this->pro_tri_create);

        $copyObj->setProTriOpen($this->pro_tri_open);

        $copyObj->setProTriDeleted($this->pro_tri_deleted);

        $copyObj->setProTriCanceled($this->pro_tri_canceled);

        $copyObj->setProTriPaused($this->pro_tri_paused);

        $copyObj->setProTriReassigned($this->pro_tri_reassigned);

        $copyObj->setProTriUnpaused($this->pro_tri_unpaused);

        $copyObj->setProTypeProcess($this->pro_type_process);

        $copyObj->setProShowDelegate($this->pro_show_delegate);

        $copyObj->setProShowDynaform($this->pro_show_dynaform);

        $copyObj->setProCategory($this->pro_category);

        $copyObj->setProSubCategory($this->pro_sub_category);

        $copyObj->setProIndustry($this->pro_industry);

        $copyObj->setProUpdateDate($this->pro_update_date);

        $copyObj->setProCreateDate($this->pro_create_date);

        $copyObj->setProCreateUser($this->pro_create_user);

        $copyObj->setProHeight($this->pro_height);

        $copyObj->setProWidth($this->pro_width);

        $copyObj->setProTitleX($this->pro_title_x);

        $copyObj->setProTitleY($this->pro_title_y);

        $copyObj->setProDebug($this->pro_debug);

        $copyObj->setProDynaforms($this->pro_dynaforms);

        $copyObj->setProDerivationScreenTpl($this->pro_derivation_screen_tpl);

        $copyObj->setProCost($this->pro_cost);

        $copyObj->setProUnitCost($this->pro_unit_cost);

        $copyObj->setProItee($this->pro_itee);

        $copyObj->setProActionDone($this->pro_action_done);

        $copyObj->setCategoryId($this->category_id);


        $copyObj->setNew(true);

        $copyObj->setProUid(''); // this is a pkey column, so set to default value

    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return     Process Clone of current object.
     * @throws     PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);
        return $copyObj;
    }

    /**
     * Returns a peer instance associated with this om.
     *
     * Since Peer classes are not to have any instance attributes, this method returns the
     * same instance for all member of this class. The method could therefore
     * be static, but this would prevent one from overriding the behavior.
     *
     * @return     ProcessPeer
     */
    public function getPeer()
    {
        if (self::$peer === null) {
            self::$peer = new ProcessPeer();
        }
        return self::$peer;
    }
}

