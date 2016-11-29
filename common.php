<?php
	
// basic script config
ignore_user_abort(false);
set_time_limit(3600*5);
ini_set("memory_limit","500M");
ini_set("error_reporting",1);

// folder parameters
$localpath = getcwd();
$jsonpath = "/json_mail";
$shaid = "/" . sha1($mainurl);
$jsondir = $localpath . $jsonpath . $shaid;


// function for interactive selection of archive to parse
function selectArchive() {

	global $localpath,$jsonpath;

	if ($dh = opendir($localpath . $jsonpath)) {
		while (($file = readdir($dh)) !== false) {
			if(preg_match("/\.log/", $file) ) {
				$content = file_get_contents($localpath . $jsonpath . "/" . $file);
				$logfiles[] = array("id" => preg_replace("/\.log/","", $file),"content" => $content);
			}
		}
		closedir($dh);
	} else {
		echo "Error: could not open files in data directory: " . $jsondir;
	}
	
	
	echo "Select an archive: \n";
	for($i = 0; $i < count($logfiles); $i++) {
		echo "\n" . $i . ":\n";
		echo $logfiles[$i]["content"] . "\n";
	}
	echo "\nType the number of the archive you want to parse: \n";
	$handle = fopen ("php://stdin","r");
	$line = trim(fgets($handle));
	
	return $logfiles[$line];
}


// get mail files from directory
function getMails($jsondir) {
	
	$jsonfiles = array();
	if ($dh = opendir($jsondir)) {
		while (($file = readdir($dh)) !== false) {
			if(preg_match("/\.json/", $file) ) {
				$jsonfiles[] = $file;
			}
		}
		closedir($dh);
	} else {
		echo "Error: could not open files in data directory: " . $jsondir;
	}
	asort($jsonfiles);
	
	return $jsonfiles;
}

?>