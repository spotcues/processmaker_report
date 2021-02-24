# Processmaker reports service

[![](https://www.groupe.io/wp-content/uploads/2018/05/groupe.png)](https://www.groupe.io)

[![Build Status](https://travis-ci.org/joemccann/dillinger.svg?branch=master)](https://travis-ci.org/joemccann/dillinger)

Processmaker reports service allows to setup one additional service that enables users to have additional insights about workspace details created using groupe.io
  - Enable users to get a quick overview of cases.
  - Enable users to quickly check out the process insights at one place.
  - And a lot more...

# New Features!
  - Excel download.
  - Process dashboard filters.

### Tech
Processmaker reports service uses a number of open source projects to work properly:
* [PHP] - Blazing fast speed of webserver scripting.
* [MySQL] - The world's most popular open source database.
* [Apache] - The Number One HTTP Server On The Internet.

### Installation
Processmaker reports service requires [PHP](https://php.net/) v7.2+ to run.
1. (Skip if pm server is already setup )Setup the pm server as per the documentation here : 
Make sure you have php, apache and mysql installed and configured. Also make sure you have the php extensions and apache modules installed and enabled in the same manner you did while installing actual processmaker server :
https://gitlab.pramati.com/spotcues_server/processmaker/-/wikis/Setup-processmaker-fresh-installation-(-3.4.7-and-php-7.2-)

Below points assumes that actual pm service is setup and we are setting up reports service for the first time. No prior changes are made to actual pm service for setting up reports serivice.

2. Setup a vhost serving contents from root of this direcory i.e; `/$REPORTS_SERVICE_INSTALL_LOCATION/workflow/public_html/` and enable this vhost : `sudo a2ensite $reports.$service.$domain`
3. Increase the timeout values for php and nginx and apache using this guide :
https://gitlab.pramati.com/spotcues_server/processmaker/-/wikis/The-php.ini-settings-for-processmaker-and-reports-service

4. Clone this directory and checkout to master.
    ```sh
    git clone https://gitlab.pramati.com/spotcues_server/processmaker_report
    cd processmaker_report && git checkout master
    ```
5. For any processmaker installation for which you want to deploy the reports service make sure to mount the shared directory from the actual processmaker installation to reports service. For example let's say our actual processmaker server is running on 1.2.3.4 at root directory of `/opt/processmaker`, mount/symlink the `user@1.2.3.4:/$PM_SERVER_INSTALL_LOCATION/shared` to `$REPORTS_SERVICE_INSTALL_LOCATION` root directory (`/usr/local/processmaker_report/shared`).
Make sure it is listed as /PM_INSTALL_DIRECTORY/shared when you run mount command on shell. 
6. Update the database server details in `config/database.php` with default database wf_workflow. This file will be used to generate a default connection using propel mysqli driver if not mysql connection details can be found in `shared/sites/$SITE/db.php` - workspace specific.
7. No need to perform this step now it is deduced autmatically - Update the paths in `/workflow/engine/config/paths_installed.php`.
8. Update the `pm_server_host` uri into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`pm_server_host = "$PM_SERVER_HOST"`
This url should be the url of the actual processmaker server, which will be used to download the excel.
Update the rest of the values in `.env`


Update the `DEFAULT_MONGO_CONNECTION_STRING` uri into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`DEFAULT_MONGO_CONNECTION_STRING = "mongodb://localhost:27021/"`

Update the `DEFAULT_ELASTIC_CONNECTION_STRING` uri into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`DEFAULT_ELASTIC_CONNECTION_STRING = "localhost:9200/_bulk?pretty"`

Update the `workflow_server_host` uri into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`workflow_server_host = "http://dummyio.spotcues.com"`

Update the `MONGO_WORKFLOW_DBNAME` value into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`MONGO_WORKFLOW_DBNAME = "workflow"`

Update the `MONGO_SPOTCUES_DBNAME` value into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`MONGO_SPOTCUES_DBNAME = "spotcues_new"`

Update the `url` value into the `$REPORTS_SERVICE_INSTALL_LOCATION/.env` file :
`url = "http://localhost:80/"`

IMPORTANT : Update other values in .env files accordingly


9. Don't forget to mount the shared directory of actual pm server to reports service. Check with mount command.


10. If you cannot see `phpexcel` and `mongo-php-library` at `$REPORTS_SERVICE_INSTALL_LOCATION/thirdparty/` already then add phpexcel library, unzip phpexcel.zip found in `$REPORTS_SERVICE_INSTALL_LOCATION/extra-plugins/phpexcel.zip` at `/$REPORTS_SERVICE_INSTALL_LOCATION/thirdparty`. Change the ownership and file permissions of the whole direcotires to www-data and 775 respectively.
11. Add mongodb-php library, unzip mongo_php_library.zip found in `$REPORTS_SERVICE_INSTALL_LOCATION/extra-plugins/mongo_php_library.zip` at `/$REPORTS_SERVICE_INSTALL_LOCATION/thirdparty`. Change the ownership and file permissions of the whole direcotires to www-data and 775 respectively.

12. Add new class inside methods directory of actual pm server - `/$PM_SERVER_INSTALL_LOCATION/workflow/engine/methods/reports_Download.php`.
Copy if from `$REPORTS_SERVICE_INSTALL_LOCATION/worklfow/engine/methods/reports_Download.php` .

To make the reports download work we should add following line to `/$PM_SERVER_INSTALL_LOCATION/workflow/public_html/sysGeneric.php` in `$noLoginFiles` fields at line number : 975 : 
`$noLoginFiles[] = 'reports_Download';`
This change should be done to actual processmaker server source code and reports service code.

13. Copy the Reports api file `/$REPORTS_SERVICE_INSTALL_LOCATION/workflow/engine/src/ProcessMaker/Services/Api/Reports.php` to actual pm server at `/$PM_SERVER_INSTALL_LOCATION/workflow/engine/src/ProcessMaker/Services/Api/Reports.php`


14. Add common_schema to store common database methods : 
a. Login into mysql servers as this operation should be performed on all mysql servers ( master and slave )
b. Export the attached file sql on mysql command line (common_schema.sql)
c. 	Run : 
	`SELECT @@GLOBAL.sql_mode;`
	See if the output have `NO_AUTO_CREATE_USER` listed if it does 
	Run below query : 
	`set @@GLOBAL.sql_mode='Whatever you got from above output except NO_AUTO_CREATE_USER';`
    For example : 
        `set @@GLOBAL.sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';`
    Now run :
        `GRANT ALL PRIVILEGES ON common_schema.* TO ''@'%';
        flush privileges;`
d. This is to create a trigger in a common database so that we don't need to create it in all databases.
common_schema.
we would store all the triggers and stored procedures here which can be used across all workspaces without the need to modify tables in all of them
This trigger allows us to unserialize and use regex in a mysql column which contains serialized values.
15. Setup environment variable according to the environment in which you want to deploy the service. 
Required environment varibles to be set are :
debug
~~~
error_reporting
display_errors
process_keys=SYS_SYS,APPLICATION,PROCESS,TASK,USER_LOGGED,USR_USERNAME,APP_NUMBER,FirstUser,SYS_LANG,SYS_SKIN,INDEX,PIN,__VAR_CHANGED__,__ERROR_
env
APP_NAME
APP_ENV
APP_URL
DEFAULT_DB_HOST
DEFAULT_DB_USER
DEFAULT_DB_PASSWORD
DEFAULT_DB_DATABASE
DEFAULT_DB_PORT
DB_SLAVE_HOST
DB_SLAVE_USER
DB_SLAVE_PASS
DB_SLAVE_DATABASE
DB_SLAVE_PORT
workflow_server_host
MONGO_WORKFLOW_DBNAME
MONGO_SPOTCUES_DBNAME
pm_server_host
DEFAULT_MONGO_CONNECTION_STRING
DEFAULT_ELASTIC_CONNECTION_STRING
~~~

16. ( Advanced - skip it if you donot know what it is ) If you want to specify other databsource for a specific workspace to read/write the data from to :
Add following fields in the workspace specific .env file (located inside /shared/sites/$WORKSPACE/.env) 
  DB_ADAPTER    
  DB_HOST       
  DB_NAME       
  DB_USER       
  DB_PASS       
  DB_RBAC_HOST  
  DB_RBAC_NAME  
  DB_RBAC_USER  
  DB_RBAC_PASS  
  DB_REPORT_HOST
  DB_REPORT_NAME
  DB_REPORT_USER
  DB_REPORT_PASS

  After that clone/copy the database from original server to new server.
  Setup a new user (same as mentioned in /shared/sites/$WORKSPACE/db.php or .env) on the new server and grant privileges to that user.

  Re-build the case cache from the processmaker GUI and reset the root password for that workspace.

  - You can create a generic common user for all the migrated databases on the new server and grant necessary privileges to new user on new server. Make sure you update the .env or db.php file accordingly.

  More Info : https://wiki.processmaker.com/3.2/Double_Connection
  To setup double connection make sure /workflow/engine/config/databases.php file is updated on actual pm server and reports server both.

There are many ways to do it :
a. `/etc/environment`
b. `~/.bashrc`
c. `~/.bash_profile`
d. In apache vhost `SetEnv variable_name variable_value`
e. In .env file
Choose the method which best suits your needs. For maximum security go with option a,b,c or d.
For development and test environment we can go with option e.
Rename the .env.sample to .env in root of project and edit the values accordingly.

Optionally you can have below list of env files to quickly copy the execution context of any give environment easily.
.env.development
.env.test
.env.production
To leverage above values from above files set APP_ENV variable accordingly.
For a list of available variable see : https://wiki.processmaker.com/3.3/Configuration_File_env.ini



17. Run `php processmaker flush-cache` from /$REPORTS_SERVICE_INSTALL_LOCATION/ root directory.

18. To setup mysql replication refer : https://gitlab.pramati.com/spotcues_server/processmaker/-/wikis/Setup-mysql-master-slave-replication

After setting up replication test it via logging in from master to slave and vice versa and change the values in .env accordingly.
After you finish the mysql replication : change the values in .env file accordingly form below variables :

DEFAULT_DB_HOST=master_host
DEFAULT_DB_USER=master_root_user
DEFAULT_DB_PASSWORD=master_root_password
DEFAULT_DB_DATABASE=wf_workflow
DEFAULT_DB_PORT=3306

DB_SLAVE_HOST=slave_host
DB_SLAVE_USER=slave_root_user
DB_SLAVE_PASS=slave_root_password
DB_SLAVE_DATABASE=wf_workflow
DB_SLAVE_PORT=slave_port

Here the DB_SLAVE_USER should be the one which can login to slave. Test it by connecting to slace from master over command line using `mysql -h DB_SLAVE_HOST -P DB_SLAVE_PORT -u DB_SLAVE_USER -p DB_SLAVE_PASS` and if it works update the values in .env file. `$REPORTS_SERVICE_INSTALL_LOCATION/.env`

Also maker sure that you configure a database user in slave to connect back to master. Create such user on master and verify it vy connecting to master from slave over command line. You might have to allow the ports in the firewall for this to work.



NOTICE : IMPORTANT : Install same php,mysq and apache extensions which you did on actual procesmaker machine
Also install php-mongo db driver on reports service.

check for mongodb extension
php -r 'phpinfo();' | grep -i mongodb
if not found install and it:

1. sudo apt-get install php{php-version}-mongodb
php-version can be known using ```php -v```
If it fails use below approach.

sudo pecl install mongodb
enable the extension in php.ini
to find the location of loaded php.ini
run : php --ini
and enable following extension by adding this line :
extension=mongodb.so


### Plugins
Processmaker reports service currently requires following plugins in order to work properly.

### Development
1. Checkout do `development` branch
```sh 
git checkout development && git pull --rebase origin development
```
2. Start developing...!!!

### Todos

 - Write Unit Tests
 - Add CI/CD
 - Add build url in README.md

License
----
https://www.groupe.io/privacy/


   [Groupe]: <https://groupe.io/>
   [PHP]: <http://php.net/>
   [MySQL]: <https://www.mysql.com/>
   [Apache]: <https://www.apache.org/>
