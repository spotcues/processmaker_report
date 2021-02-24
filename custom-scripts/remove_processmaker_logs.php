<?php
/**
 * php script to remove processmaker log files for all workspaces.
 * Usage : sudo php remove_processmaker_logs.php
 * @var string
 */
$default_pm_root = '/opt/processmaker';
$pm_root = '';
$confirm_choice = 'n';
$files_found = 0;
$files_deleted = 0;


do {
	echo "Enter the root directory of processmaker without trailing / . [DEFAULT : /opt/processmaker]. To exit press X ".PHP_EOL;
	$handle = fopen ("php://stdin","r");
	$user_input_pm_root = fgets($handle);
	fclose($handle);
	if (!is_string($user_input_pm_root)){
		echo "Bad input, Aborting !!!";
		exit(0);
	}

	$user_input_pm_root = trim($user_input_pm_root);
	if($user_input_pm_root === '') {
		echo "Empty value is provided. Please run the script again with proper values.";
		exit(0);
	}

	if(strtolower($user_input_pm_root) === 'x')
		exit(0);

	echo "You provided processmaker root location as $user_input_pm_root. Do you want to proceed [Y/N]. To exit press X ".PHP_EOL;
	$handle = fopen ("php://stdin","r");
	$confirm_choice = fgets($handle);
	fclose($handle);
	if (!is_string($confirm_choice)){
		echo "Bad input, Aborting !!!";
		exit(0);
	}
	$confirm_choice = trim($confirm_choice);
	if($confirm_choice === '') {
		echo "Empty value is provided. Please run the script again with proper values.";
		exit(0);
	}
	$confirm_choice = strtolower($confirm_choice);

	if($confirm_choice === 'x')
		exit(0);

	if($confirm_choice !== 'y' && $confirm_choice !== 'n') {
		echo "Incorrect input. Try again.".PHP_EOL;
		$confirm_choice = 'n';
	}

} while ($confirm_choice === 'n');
$confirm_choice = 'n';




do {
	echo "Enter number of days (integer number like 1,2 etc. ). Processmaker logs older than this value would get deleted. [DEFAULT : 1]. To exit press X ".PHP_EOL;
	$handle = fopen ("php://stdin","r");
	$number_of_days = fgets($handle);
	fclose($handle);
	if (!is_string($number_of_days)){
		echo "Bad input, Aborting !!!";
		exit(0);
	}
	$number_of_days = trim($number_of_days);
	if(!is_numeric($number_of_days)) {
		echo "Bad input, Aborting !!!";
		exit(0);
	}
	if($number_of_days === '') {
		echo "Empty value is provided. Please run the script again with proper values.";
		exit(0);
	}

	if(strtolower($number_of_days) === 'x')
		exit(0);
	

	$number_of_days = (int)$number_of_days;
	if($number_of_days < 1){
		echo "Value less than 1 was given. This would delete yesterday's log files also and only preserve files which got generated today. Do you want to proceed [Y/N]. To exit press X ".PHP_EOL;
		$handle = fopen ("php://stdin","r");
		$confirm_choice = fgets($handle);
		fclose($handle);

		if (!is_string($confirm_choice)){
			echo "Bad input, Aborting !!!";
			exit(0);
		}
		$confirm_choice = trim($confirm_choice);
		if($confirm_choice === '') {
			echo "Empty value is provided. Please run the script again with proper values.";
			exit(0);
		}
		$confirm_choice = strtolower($confirm_choice);

		if($confirm_choice === 'x' || $confirm_choice === 'n') {
			echo "Exiting !!!!.";
			exit(0);
		}
		if($confirm_choice !=='y') {
			echo "Incorrect input. Exiting !!!";
			exit(0);
		}
		$confirm_choice = 'n';
	}

	echo "You provided number of days as $number_of_days. Do you want to proceed [Y/N]. To exit press X ".PHP_EOL;
	$handle = fopen ("php://stdin","r");
	$confirm_choice = fgets($handle);
	fclose($handle);
	if (!is_string($confirm_choice)){
		echo "Bad input, Aborting !!!";
		exit(0);
	}
	$confirm_choice = trim($confirm_choice);
	if($confirm_choice === '') {
		echo "Empty value is provided. Please run the script again with proper values.";
		exit(0);
	}

	$confirm_choice = strtolower($confirm_choice);
	if($confirm_choice === 'x')
		exit(0);
	if($confirm_choice !== 'y' && $confirm_choice !== 'n') {
		echo "Incorrect input. Try again.".PHP_EOL;
		$confirm_choice = 'n';
	}
} while ($confirm_choice === 'n');
$confirm_choice = 'n';



@define(USER_INPUT_NUMBER_OF_DAYS, $number_of_days);

if(trim($user_input_pm_root) == null || trim($user_input_pm_root) == '') {

	$pm_root = $default_pm_root;

} elseif (trim($user_input_pm_root) != null || trim($user_input_pm_root) != ''){

	
	$user_input_pm_root = rtrim($user_input_pm_root, "/");
	$user_input_pm_root = trim($user_input_pm_root)."/shared";

	echo PHP_EOL;

	$pathinfo = pathinfo($user_input_pm_root);
	$dirname = $pathinfo['dirname'];
	$basename = $pathinfo['basename'];
	$temp_filename = $pathinfo['filename'];

	if(!file_exists($dirname) || !is_dir($dirname)) {
		echo "Processmaker install directory info is incorrect. If you think path is correct, then permissions issue might be there. Run the script as root or use sudo. Aborting !!!!";
		exit(0);
	}

	if(!file_exists($user_input_pm_root) || !is_dir($user_input_pm_root)) {
		echo "shared directory for given pm install location does not exists or is not a directory. Enter correct path. Aborting !!!!". PHP_EOL;
		exit(0);
	}
	$pm_root = $user_input_pm_root;
	$workspaces = glob($pm_root . '/sites/*' , GLOB_ONLYDIR);




	foreach ($workspaces as $workspace_path) {
		$workspace_name = @array_pop(explode("/", $workspace_path));
		$workspace_logs_path = "$workspace_path/log";

		if(file_exists($workspace_logs_path)  && is_dir($workspace_logs_path)) {
			echo "log directory exists for workspace - $workspace_name. checking for log files now - ".PHP_EOL;
			$scan_arr = scandir($workspace_logs_path);
			$files_arr = array_diff($scan_arr, array('.','..') );

			foreach ($files_arr as $file) {

				$file_path = "$workspace_logs_path/".$file;
				$file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
				$file_name = pathinfo($file_path, PATHINFO_FILENAME);
				$base_file_name = pathinfo($file_path, PATHINFO_BASENAME);

				if ($file_ext == "log" && strpos($file_name, 'processmaker-') !== false) {
					echo 'Found log file : ' . $base_file_name . PHP_EOL;
					$files_found++;
					$file_creation_date = filemtime($file_path);
					if ( (time() - $file_creation_date ) > USER_INPUT_NUMBER_OF_DAYS*24*3600) {

						echo "$base_file_name is older than specified number of days, trying to delete it..." . PHP_EOL;

						try{
							unlink($file_path);
							echo "Deleted log file $base_file_name from $workspace_name successfully.".PHP_EOL;
							$files_deleted++;
						} catch(\Exception $e)
						{
							echo "Failed to deleted $base_file_name for $workspace_name. ".json_encode($e->getMessage()).". Continuing with next log file.".PHP_EOL;
						}
					} else {
						echo "$base_file_name is not older than specified number of days, skipping..." . PHP_EOL;
					}
					
				}
			}

			echo PHP_EOL.PHP_EOL.PHP_EOL;
		} else {
			echo "No log directory found for workspace - $workspace. Continuing !!!".PHP_EOL;
		}
	}
}
echo PHP_EOL;
echo "Total processmaker log files found per given criteria were : $files_found".PHP_EOL;
echo "Total processmaker log files deleted per given criteria were : $files_deleted".PHP_EOL;

echo "Finished removing old log files from workspaces, exiting...!!!!". PHP_EOL;die;
?>
