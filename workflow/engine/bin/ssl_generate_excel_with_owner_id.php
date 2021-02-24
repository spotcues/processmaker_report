<?php
/**
 *
 * ProcessMaker Open Source Edition
 * Copyright (C) 2004 - 2012 Colosa Inc.23
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * For more information, contact Colosa Inc, 5304 Ventura Drive,
 * Delray Beach, FL, 33484, USA, or email info@colosa.com.
 *
 */

if(stripos(getcwd(), '/workflow/engine/bin') === false) {
    echo("Run this script from PM_REPORTS_SERVICE_ROOT_LOCATION/workflow/engine/bin from pm reports service");
    exit(0);
}

require_once __DIR__ . '/../../../gulliver/system/class.g.php';
require_once __DIR__ . '/../../../bootstrap/autoload.php';
require_once __DIR__ . '/../../../bootstrap/app.php';

/** check script parameters
*
* sudo php ssl_generate_excel_with_owner_id.php W5fc6073c57252f118f71056e localhost:3306 7874105195fc6332369fc39070450336,3456637565ff4687c4f7856004893570 2020-11-26T18:30:00.000Z 2021-01-27T18:29:59.999Z
*
*
* sudo php ssl_generate_excel_with_owner_id.php W5fc6073c57252f118f71056e 127.0.0.1:33060 7874105195fc6332369fc39070450336,3456637565ff4687c4f7856004893570 2020-11-26T18:30:00.000Z 2021-01-27T18:29:59.999Z
*
*
* Run this script from $PM_INSTALL_LOCATION/workflow/engine/bin from pm reports service and not actual pm service
**/

use Illuminate\Foundation\Http\Kernel;
use ProcessMaker\Core\System;
use Illuminate\Support\Facades\DB;



$commandLineSyntaxMsg = "Invalid command line arguments: \n " .
  "syntax: ".
  "php ssl_generate_excel_with_owner_id.php [workspace_name] [mysqlhost:port] [comma_separated_owner_ids] [datefrom] [dateto]\n".
  "where [workspace_name] is the pm workspacename for which you want to generate the reports\n".
  "and [mysqlhost:port] is the mysql server details in form of mysqlhost:port. You should always try to use slave if possible\n".
  "and [comma_separated_owner_ids] is the pm comma separated owner_ids for given workspace_name\n".
  "and [datefrom] is the date from which you want the reports to be generated\n".
  "and [dateto] is the date upto which you want the reports to be generated\n";

if ((count($argv) < 6) || count(explode(':', $argv [2])) !== 2 ) {
    print $commandLineSyntaxMsg;
    die();
}

$workspaceName = $argv [1];
$mysqlHost = $argv [2];
$dataList['owner_id'] = $argv [3];
$dataList['date_from'] = $argv [4];
$dataList['date_to'] = $argv [5];

$debug = 1;



define('WORKSPACE', $workspaceName);

$e_all = defined('E_DEPRECATED') ? E_ALL & ~ E_DEPRECATED : E_ALL;
$e_all = defined('E_STRICT') ? $e_all & ~ E_STRICT : $e_all;
$e_all = $debug ? $e_all : $e_all & ~ E_NOTICE;

error_reporting(0);
@ini_set('display_errors', 0);
ini_set('memory_limit', '4096M'); // set enough memory for the script
set_time_limit(0);




$tFile = '';



if (! defined('SYS_LANG')) {
    define('SYS_LANG', 'en');
}

if (! defined('PATH_HOME')) {
    if (! defined('PATH_SEP')) {
        define('PATH_SEP', (substr(PHP_OS, 0, 3) == 'WIN') ? '\\' : '/');
    }
    $docuroot = explode(PATH_SEP, str_replace('engine' . PATH_SEP . 'methods' . PATH_SEP . 'services', '', dirname(__FILE__)));
    array_pop($docuroot);
    array_pop($docuroot);
    $pathhome = implode(PATH_SEP, $docuroot) . PATH_SEP;
  // try to find automatically the trunk directory where are placed the RBAC and
  // Gulliver directories
  // in a normal installation you don't need to change it.
    array_pop($docuroot);
    $pathTrunk = implode(PATH_SEP, $docuroot) . PATH_SEP;
    array_pop($docuroot);
    $pathOutTrunk = implode(PATH_SEP, $docuroot) . PATH_SEP;
  // to do: check previous algorith for Windows $pathTrunk = "c:/home/";

    define('PATH_HOME', $pathhome);
    define('PATH_TRUNK', $pathTrunk);
    define('PATH_OUTTRUNK', $pathOutTrunk);
    define('PATH_CLASSES', PATH_HOME . "engine" . PATH_SEP . "classes" . PATH_SEP);

    require_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php');
}

print "PATH_HOME: " . PATH_HOME . "\n";
print "PATH_DB: " . PATH_DB . "\n";
print "PATH_CORE: " . PATH_CORE . "\n";


app()->useStoragePath(realpath(PATH_DATA));
app()->make(Kernel::class)->bootstrap();


// define the site name (instance name)
if (empty(config("system.workspace"))) {
    $sObject = $workspaceName;

    $oDirectory = dir(PATH_DB);

    if (is_dir(PATH_DB . $sObject)) {
        if (file_exists(PATH_DB . $sObject . PATH_SEP . 'db.php')) {
            define('SYS_SYS', $sObject);
            config(["system.workspace" => $sObject]);

          // ****************************************
          // read initialize file
            require_once PATH_HOME . 'engine' . PATH_SEP . 'classes' . PATH_SEP . 'class.system.php';
            $config = System::getSystemConfiguration('', '', config("system.workspace"));
            define('MEMCACHED_ENABLED', $config ['memcached']);
            define('MEMCACHED_SERVER', $config ['memcached_server']);
            define('TIME_ZONE', $config ['time_zone']);

            date_default_timezone_set(TIME_ZONE);


            $filter = new InputFilter();
            $TIME_ZONE = $filter->xssFilterHard(TIME_ZONE);
            $MEMCACHED_ENABLED = $filter->xssFilterHard(MEMCACHED_ENABLED);
            $MEMCACHED_SERVER = $filter->xssFilterHard(MEMCACHED_SERVER);
      
            print "TIME_ZONE: " . $TIME_ZONE . "\n";
            print "MEMCACHED_ENABLED: " . $MEMCACHED_ENABLED . "\n";
            print "MEMCACHED_SERVER: " . $MEMCACHED_SERVER . "\n";
          // ****************************************

            include_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths_installed.php');
            include_once(PATH_HOME . 'engine' . PATH_SEP . 'config' . PATH_SEP . 'paths.php');

          // ***************** PM Paths DATA **************************
            define('PATH_DATA_SITE', PATH_DATA . 'sites/' . config("system.workspace") . '/');
            define('PATH_DOCUMENT', PATH_DATA_SITE . 'files/');
            define('PATH_DATA_MAILTEMPLATES', PATH_DATA_SITE . 'mailTemplates/');
            define('PATH_DATA_PUBLIC', PATH_DATA_SITE . 'public/');
            define('PATH_DATA_REPORTS', PATH_DATA_SITE . 'reports/');
            define('PATH_DYNAFORM', PATH_DATA_SITE . 'xmlForms/');
            define('PATH_IMAGES_ENVIRONMENT_FILES', PATH_DATA_SITE . 'usersFiles' . PATH_SEP);
            define('PATH_IMAGES_ENVIRONMENT_USERS', PATH_DATA_SITE . 'usersPhotographies' . PATH_SEP);

          // server info file
            if (is_file(PATH_DATA_SITE . PATH_SEP . '.server_info')) {
                $SERVER_INFO = file_get_contents(PATH_DATA_SITE . PATH_SEP . '.server_info');
                $SERVER_INFO = unserialize($SERVER_INFO);
                // print_r($SERVER_INFO);
                define('SERVER_NAME', $SERVER_INFO ['SERVER_NAME']);
                define('SERVER_PORT', $SERVER_INFO ['SERVER_PORT']);
            } else {
                eprintln("WARNING! No server info found!", 'red');
            }

          // read db configuration
            $sContent = file_get_contents(PATH_DB . $sObject . PATH_SEP . 'db.php');

            $sContent = str_replace('<?php', '', $sContent);
            $sContent = str_replace('<?', '', $sContent);
            $sContent = str_replace('?>', '', $sContent);
            $sContent = str_replace('define', '', $sContent);
            $sContent = str_replace("('", "$", $sContent);
            $sContent = str_replace("',", '=', $sContent);
            $sContent = str_replace(");", ';', $sContent);

            eval($sContent);

            $mysqlEncoding = 'utf8';

            $mysqlAdpater = $DB_ADAPTER;

            $mysqlUser = $DB_USER;
            $mysqlPass = $DB_PASS;
            $mysqlName = $DB_NAME;

            $mysqlRBACUser = $DB_RBAC_USER;
            $mysqlRBACPass = $DB_RBAC_PASS;
            $mysqlRBACName = $DB_RBAC_NAME;

            $mysqlREPORTUser = $DB_REPORT_USER;
            $mysqlREPORTPass = $DB_REPORT_PASS;
            $mysqlREPORTName = $DB_REPORT_NAME;

            $dsn = $mysqlAdpater . '://' . $mysqlUser . ':' . urlencode($mysqlPass) . '@' . $mysqlHost . '/' . $mysqlName;
            $dsnRbac = $mysqlAdpater . '://' . $mysqlRBACUser . ':' . urlencode($mysqlRBACPass) . '@' . $mysqlHost . '/' . $mysqlRBACName;
            $dsnRp = $mysqlAdpater . '://' . $mysqlREPORTUser . ':' . urlencode($mysqlREPORTPass) . '@' . $mysqlHost . '/' . $mysqlREPORTName;


            switch ($mysqlAdpater) {
                case 'mysql':
                    $dsn .= "?encoding=$mysqlEncoding";
                    $dsnRbac .= "?encoding=$mysqlEncoding";
                    $dsnRp .= "?encoding=$mysqlEncoding";
                    break;
                case 'mssql':
                  // $dsn .= '?sendStringAsUnicode=false';
                  // $dsnRbac .= '?sendStringAsUnicode=false';
                    break;
                default:
                    break;
            }
            
            


                 
            // initialize db
            $pro ['datasources'] ['workflow'] ['connection'] = $dsn;
            $pro ['datasources'] ['workflow'] ['adapter'] = $mysqlAdpater;
            $pro ['datasources'] ['rbac'] ['connection'] = $dsnRbac;
            $pro ['datasources'] ['rbac'] ['adapter'] = $mysqlAdpater;
            $pro ['datasources'] ['rp'] ['connection'] = $dsnRp;
            $pro ['datasources'] ['rp'] ['adapter'] = $mysqlAdpater;

            $pro ['datasources'] ['workflow_ro'] = $pro['datasources']['workflow'];

            $oFile = fopen(PATH_CORE . 'config/_databases_.php', 'w');
            fwrite($oFile, '<?php global $pro;return $pro; ?>');
            fclose($oFile);
            Propel::init(PATH_CORE . 'config/_databases_.php');
            $tempMysql = explode(':', $mysqlHost);
            $workflowDB = [
                    'driver' => $mysqlAdpater,
                    'host' => $tempMysql[0],
                    'port' => $tempMysql[1],
                    'database' => $mysqlName,
                    'username' => $mysqlUser,
                    'password' => $mysqlPass,
                    'unix_socket' => '',
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' => '',
                    'strict' => false,
                    'engine' => null,
                ];
            config(['database.connections.workflow' => $workflowDB]);
            config(['database.connections.workflow_ro' => $workflowDB]);
            
            eprintln("Processing workspace: " . $sObject, 'green');
            try {
                processWorkspace($dataList);
                eprintln("Finished genrating excel for workspace - ".WORKSPACE.PHP_EOL.PHP_EOL, 'green');
            } catch (Exception $e) {
                $token = strtotime("now");
                PMException::registerErrorLog($e, $token);
                G::outRes(G::LoadTranslation("ID_EXCEPTION_LOG_INTERFAZ", array($token)));
                eprintln("Problem in workspace: " . $sObject . ' it was omitted.', 'red');
                eprintln("Error was: ". $e->getMessage(), 'red');
            }
            eprintln();
            exit(0);
        }
    }
} else {
    processWorkspace($dataList);
    eprintln("Finished genrating excel for workspace - ".WORKSPACE.PHP_EOL.PHP_EOL, 'green');
    exit(0);
}


function processWorkspace($dataList = [])
{
    $finalRes['succeded_processes'] = [];
    $finalRes['failed_processes'] = [];
    if (empty($dataList['owner_id']) || empty($dataList['date_from']) || empty($dataList['date_to'])) {
        throw new Exception("Error Processing Request, Missing params", 1);
    }
    
    $reports = new \ProcessMaker\Services\Api\Reports();
    $total_processes = $reports->getAllProcessesVersion2();
    $search = $task = null;


        foreach ($total_processes['processes'] as $key => $value) {
            $process_id = $value['PRO_UID'];
            eprintln("Trying to generate excel for workspace - ".WORKSPACE." and process - $process_id", 'green');
            try {
            $finalRes['succeded_processes'][$value['PRO_TITLE']] = $reports->getFormDetailsVersion2Excel(
                0,
                18446744073709551615,
                $dataList['date_from'],
                $dataList['date_to'],
                $process_id,
                $search,
                $task,
                $dataList['owner_id']
            );
                eprintln("Finished generating excel for workspace - ".WORKSPACE." and process - $process_id", 'green');
                eprintln("Continuing with next process...!!!! ", 'green');
                eprintln();
                eprintln();
            } catch (Exception $e) {
                eprintln("Excel generation failed for process - $process_id. Logged it.... Continuing with next process".PHP_EOL, 'red');
                eprintln();
                $finalRes['failed_processes'][$process_id] = $e->getMessage();
                continue;
            }
        }
        if (file_exists(__DIR__.'/shared') && is_dir(__DIR__.'/shared') && !file_exists(__DIR__.'/shared/sites') &&  ((time() - filemtime(__DIR__.'/shared')) < 24*3600)) {
            eprintln("Cleaning Up.....!!!", 'green');
            eprintln();
            rmdir(__DIR__.'/shared');
        }
        if (file_exists(PATH_CORE . 'config/_databases_.php')) {
                unlink(PATH_CORE . 'config/_databases_.php');
        }
    DB::purge('workflow');
    DB::purge('workflow_ro');

    if (count($finalRes['failed_processes']) == 0) {
        eprintln("Reports Generation for all processes succeeded. Writing reponse to file . After utilising please delete this file manually --- ".PATH_DB.WORKSPACE.'/public/reports/_temp_reports_data_.txt'.' --- ', 'green');
        eprintln();
    } else {
        eprintln("Reports Generation for some processes failed. Maunal Intervention needed. Please generate reports excel for failed processes manually and append the report_excel_link value to same file --- ".PATH_DB.WORKSPACE.'/public/reports/_temp_reports_data_.txt'.' --- '. 'orange');
        eprintln();
    }

    $tFile = fopen(PATH_DB.WORKSPACE.'/public/reports/_temp_reports_data_.txt', 'w');
    fwrite($tFile, "Succeded Process :: ".PHP_EOL.PHP_EOL.PHP_EOL);
    foreach ($finalRes['succeded_processes'] as $key => $value) {
        fwrite($tFile, "Process - $key :: ". $value['report_excel_link'].PHP_EOL.PHP_EOL);
    }
    fwrite($tFile, PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL);
    fwrite($tFile, "Failed Process :: ".PHP_EOL.PHP_EOL.PHP_EOL);
    foreach ($finalRes['failed_processes'] as $key => $value) {
        fwrite($tFile, "Process - $key - reason :: ".$value.PHP_EOL.PHP_EOL);
    }
    fclose($tFile);
}
