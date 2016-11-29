<?php 

// file includes
include("common.php");


// get archive to work on
$archive = selectArchive();
$jsondir = $localpath . $jsonpath . "/" . $archive["id"];

// read json file list from directory
$jsonfiles = getMails($jsondir);


$mailbag = array();
$authorlist = array();
$relations = array();

$counter = 0;
foreach($jsonfiles as $jsonfile) {
	
	$file = $jsondir . "/" . $jsonfile;
	$data = json_decode(file_get_contents($file));
	
	//print_r($data); exit;
	
	$data->author = strtolower($data->author);
	
	$mailbag[$data->id] = $data;
	
	if(!isset($authorlist[$data->author])) {
		$authorlist[$data->author] = array();
		$authorlist[$data->author]["id"] = $counter;
		$authorlist[$data->author]["posts"] = 1;
		$counter++;
	} else {
		$authorlist[$data->author]["posts"]++;
	}
}

//print_r($mailbag); exit;


$filename_gdf = $localpath . $jsonpath . $shaid . "_social.gdf";
$content = "";

$content .= "nodedef>name VARCHAR,label VARCHAR,posts INT\n";

foreach($authorlist as $name => $data) {
		$content .= $data["id"] . "," . html_entity_decode(preg_replace("/,/","",$name)) . "," . $data["posts"] . "\n";
}

file_put_contents($filename_gdf, "\xEF\xBB\xBF".$content);


foreach($mailbag as $mail) {
	
	if($mail->replytoid != "base") {
		
		$relation = $authorlist[$mail->author]["id"] . "," . $authorlist[$mailbag[$mail->replytoid]->author]["id"];
		
		//echo $relation . " ";
		
		if(!isset($relations[$relation])) {
			$relations[$relation] = 1;
		} else {
			$relations[$relation]++;
		}
	}
}
	

$content = "edgedef>node1 VARCHAR,node2 VARCHAR,replies INT,directed BOOLEAN\n";

foreach($relations as $name => $value) {
		$content .= $name . "," . $value . ",true\n";
}

file_put_contents($filename_gdf, $content, FILE_APPEND);

echo "\nprocessed " . count($jsonfiles) . " mails, file written\n\n";


?>