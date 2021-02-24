<?php
/**
 * databases.php
 *
 * ProcessMaker Open Source Edition
 * Copyright (C) 2004 - 2008 Colosa Inc.23
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * For more information, contact Colosa Inc, 2566 Le Jeune Rd.,
 * Coral Gables, FL, 33134, USA, or email info@colosa.com.
 *
 */

if (defined('PATH_DB') && !empty(config("system.workspace"))) {
    if (!file_exists(PATH_DB . config("system.workspace") . '/db.php')) {
        throw new Exception("Could not find db.php in current workspace " . config("system.workspace"));
    }

    require_once PATH_DB . config("system.workspace") . '/db.php';
    $current_workspace    = config('system.workspace');
    $current_workspace_db = 'wf_' . $current_workspace;
    $systemConfiguration                    = System::getSystemConfiguration();

    $DB_ENCODING = isset($systemConfiguration['DEFAULT_DB_ENCODING']) ? $systemConfiguration['DEFAULT_DB_ENCODING']: "utf8";
    $DB_PORT = isset($systemConfiguration['DEFAULT_DB_PORT']) ? $systemConfiguration['DEFAULT_DB_PORT']: "3306";

    $DB_ADAPTER     = DB_ADAPTER;
    $DB_HOST        = DB_HOST;
    $DB_NAME        = DB_NAME;
    $DB_USER        = DB_USER;
    $DB_PASS        = DB_PASS;
    $DB_RBAC_HOST   = DB_RBAC_HOST;
    $DB_RBAC_NAME   = DB_RBAC_NAME;
    $DB_RBAC_USER   = DB_RBAC_USER;
    $DB_RBAC_PASS   = DB_RBAC_PASS;
    $DB_REPORT_HOST = DB_REPORT_HOST;
    $DB_REPORT_NAME = DB_REPORT_NAME;
    $DB_REPORT_USER = DB_REPORT_USER;
    $DB_REPORT_PASS = DB_REPORT_PASS;


    
    
    $slaveConnectionMap = [];
    $default_index = 0;

    foreach ($systemConfiguration as $key => $value) {
        if (strpos($key, 'DEFAULT_DB_ADAPTER') !== false) {
            $DB_ADAPTER = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_ENCODING') !== false) {
            $DB_ENCODING = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_HOST') !== false) {
            $DB_HOST = trim($value);
            $DB_RBAC_HOST = trim($value);
            $DB_REPORT_HOST = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_USER') !== false) {
            $DB_USER = trim($value);
            $DB_RBAC_USER = trim($value);
            $DB_REPORT_USER = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_PASSWORD') !== false) {
            $DB_PASS = trim($value);
            $DB_RBAC_PASS = trim($value);
            $DB_REPORT_PASS = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_DATABASE') !== false) {
            $DB_NAME = trim($value);
            $DB_RBAC_NAME = trim($value);
            $DB_REPORT_NAME = trim($value);
        }
        if (strpos($key, 'DEFAULT_DB_PORT') !== false) {
            $DB_PORT = trim($value);
            $DB_RBAC_PORT = trim($value);
            $DB_REPORT_PORT = trim($value);
        }
            

        if (strpos($key, 'DB_SLAVE_') !== false) {
            $index = substr($key, strripos($key, "_") + 1);
            if (is_numeric($index)) {
                $slaveConnectionMap[$index][substr($key, 0, strripos($key, "_"))] = $value;
            } else {
                $slaveConnectionMap[$default_index][$key] = $value;
            }
        }
    }

    //to do: enable for other databases
    $dbType = $DB_ADAPTER;
    $dsn    = $DB_ADAPTER . '://' . $DB_USER . ':' . urlencode($DB_PASS) . '@' . $DB_HOST . "/" . $DB_NAME;

    //to do: enable a mechanism to select RBAC Database
    $dsnRbac = $DB_ADAPTER . '://' . $DB_RBAC_USER . ':' . urlencode($DB_RBAC_PASS) . '@' . $DB_RBAC_HOST . "/" . $DB_RBAC_NAME;

    //to do: enable a mechanism to select report Database
    $dsnReport = $DB_ADAPTER . '://' . $DB_REPORT_USER . ':' . urlencode($DB_REPORT_PASS) . '@' . $DB_REPORT_HOST . "/" . $DB_REPORT_NAME;

    switch ($DB_ADAPTER) {
        case 'mysql':
            $dsn .= "?encoding=$DB_ENCODING";
            $dsnRbac .= "?encoding=$DB_ENCODING";
            $dsnReport .= "?encoding=$DB_ENCODING";
            break;
        case 'mssql':
        case 'sqlsrv':
            //$dsn       .= '?sendStringAsUnicode=false';
            //$dsnRbac   .= '?sendStringAsUnicode=false';
            //$dsnReport .= '?sendStringAsUnicode=false';
            break;
        default:
            break;
    }

    $pro['datasources']['workflow']['connection'] = $dsn;
    $pro['datasources']['workflow']['adapter']    = $DB_ADAPTER;

    $pro['datasources']['rbac']['connection'] = $dsnRbac;
    $pro['datasources']['rbac']['adapter']    = $DB_ADAPTER;

    $pro['datasources']['rp']['connection'] = $dsnReport;
    $pro['datasources']['rp']['adapter']    = $DB_ADAPTER;

    $dbHost = explode(':', $DB_HOST);
    config(['database.connections.workflow.host' => $dbHost[0]]);
    config(['database.connections.workflow.database' => $DB_NAME]);
    config(['database.connections.workflow.username' => $DB_USER]);
    config(['database.connections.workflow.password' => $DB_PASS]);
    if (count($dbHost) > 1) {
        config(['database.connections.workflow.port' => $dbHost[1]]);
    }



    if (defined('SYS_SYS') && SYS_SYS == $current_workspace) {
        $slaveConnectionMap = array_values($slaveConnectionMap);
        foreach ($slaveConnectionMap as $key => $value) {
            if (isset($value["DB_SLAVE_HOST"])
            ) {
                $dbSlaveHost = $value["DB_SLAVE_HOST"];
                $dbSlaveUser = isset($value["DB_SLAVE_USER"]) ? $value["DB_SLAVE_USER"] : $DB_USER;
                $dbSlavePass = isset($value["DB_SLAVE_PASS"]) ? $value["DB_SLAVE_PASS"] : $DB_PASS;
                $dbSlaveName = $current_workspace_db;
                $dbSlaveAdpater  = isset($value["DB_SLAVE_ADAPTER"]) ? $value["DB_SLAVE_ADAPTER"] : $DB_ADAPTER;
                $dbSlavePort     = isset($value["DB_SLAVE_PORT"]) ? $value["DB_SLAVE_PORT"] : $DB_PORT;
                $dbSlaveEncoding = isset($value["DB_SLAVE_ENCODING"]) ? $value["DB_SLAVE_ENCODING"] : $DB_ENCODING;

                if ($default_index == 0) {
                    $pro['datasources']['workflow_ro']['connection'] = "$dbSlaveAdpater://$dbSlaveUser:$dbSlavePass@$dbSlaveHost:$dbSlavePort/$dbSlaveName?encoding=$dbSlaveEncoding";
                    $pro['datasources']['workflow_ro']['adapter']    = 'mysql';
                } else {
                    $pro['datasources']["workflow_ro_$default_index"]['connection'] = "$dbSlaveAdpater://$dbSlaveUser:$dbSlavePass@$dbSlaveHost:$dbSlavePort/$dbSlaveName?encoding=$dbSlaveEncoding";
                    $pro['datasources']["workflow_ro_$default_index"]['adapter']    = 'mysql';
                }
                $default_index++;
            }
        }
    }
}

$pro['datasources']['dbarray']['connection'] = 'dbarray://user:pass@localhost/pm_os';
$pro['datasources']['dbarray']['adapter']    = 'dbarray';
return $pro;
