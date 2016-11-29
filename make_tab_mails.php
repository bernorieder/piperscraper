<?php 

// file includes
include("common.php");


// get archive to work on
$archive = selectArchive();
$jsondir = $localpath . $jsonpath . "/" . $archive["id"];
$filename_csv = $localpath . $jsonpath . $shaid . "_mails.csv";

// read json file list from directory
$jsonfiles = getMails($jsondir);


// open and write to csv file
$fp = fopen($filename_csv, "w");

$counter = 0;
foreach($jsonfiles as $jsonfile) {
	
	$file = $jsondir . "/" . $jsonfile;
	$data = json_decode(file_get_contents($file));
	
	if($counter == 0) {
		$keys = array();
		foreach($data as $key => $value) {
			$keys[] = $key;
		}
		fwrite($fp, "\xEF\xBB\xBF" . implode(",", $keys) . "\n");
	}
	$counter++;
	
	$mail = array();
	foreach($data as $key => $value) {
		$mail[$key] = $value;
	}
	
	$mail["id"] = "id_" . $mail["id"];
	$mail["replytoid"] = "id_" . $mail["replytoid"];
	$mail["author"] = html_entity_decode(strtolower($mail["author"]));
	$mail["subject"] = html_entity_decode($mail["subject"]);
	$mail["text"] = html_entity_decode($mail["text"]);

	fputcsv($fp,$mail,",","\"","\\");
}

fclose($fp);

//file_put_contents($filename_tab, "\xEF\xBB\xBF".$content);

echo "\nprocessed " . count($jsonfiles) . " mails, file written\n\n";

?>