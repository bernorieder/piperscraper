<?php

// basic configuration, normally the only thing to modify
$mainurl = "http://listserv.aoir.org/pipermail/air-l-aoir.org/";


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


// check for json directories, if not there make them
if(!file_exists($localpath . $jsonpath)) { mkdir($localpath . $jsonpath); }
if(!file_exists($jsondir)) { mkdir($jsondir); }

// create a basic log file
if(!file_exists($jsondir . ".log")) {
	$content = "Archive URL: " . $mainurl . "\n";
	$content .= "Scrape started: " . date("Y-m-d H:i:s",time());
	file_put_contents($jsondir . ".log", $content);
}


// parse main archive page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $mainurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($ch);

if($content) {
			
	preg_match_all('/<A href="(.*)\/thread.html">/mi', $content, $current);
	
	echo "getting " . count($current[1]) . " months:\n\n";
	
	$counter = 0;
	foreach($current[1] as $link) {
		
		getMonth($link);
		
		$counter++;
		echo "\n\n" . $counter . " " . $link . " ";
	}
}


function getMonth($month) {
	
	global $mainurl,$jsondir;
	
	$link = $mainurl . $month ."/thread.html";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$content = curl_exec($ch);
	
	$content = preg_replace('/[\r\n]/', '', $content);
	$content = preg_replace("/<\!--htdig_noindex-->/","",$content);
	$content = preg_replace("/<\!--\/htdig_noindex-->/","",$content);
	
	preg_match_all('/<\!--(.*?) (.*?)- --><LI><A HREF="(.*?)">(.*?)<\/A>(.*?)<I>(.*?)<\/I>/i', $content, $regthread);
	
	echo count($regthread[1])." mails: ";
	
	for($i = 0; $i < count($regthread[1]); $i++) {
		
		echo $i . " ";
		
		if($regthread[1][$i] != 0) {
			$thread = explode("-",$regthread[2][$i]);
			$id = $thread[count($thread)-1];
			$replytoid = $thread[count($thread)-2];
		} else {
			$id = $regthread[2][$i];
			$replytoid = "base";
		}
		
		$author = $regthread[6][$i];
		$subject = $regthread[4][$i];
		$text = "";
		$date = date("Y-m-d H:i:s",strtotime($month));
		$link = $mainurl . $month . "/" . $regthread[3][$i];
		
		$link_sha = sha1($link);
		
		$tmpjson = array();
		$tmpjson["id"] = $id;
		$tmpjson["author"] = $author;
		$tmpjson["subject"] = $subject;
		$tmpjson["replytoid"] = $replytoid;
		$tmpjson["date"] = $date;
		$tmpjson["link"] = $link;
		
		$jsonfile = $jsondir . "/" . $tmpjson["id"] . ".json";
	
		if(!file_exists($jsonfile)) {
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $link);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$content = curl_exec($ch);
			
			if($content) {
				
	            $content = preg_replace('/[\r\n]/', ' ', $content);
				
				// parse date
	            preg_match_all('/<I>(.*?)<\/I>/mi', $content, $regdate);
				$newdate = date( 'Y-m-d H:i:s', strtotime($regdate[1][0]));
				$tmpjson["date"] = $newdate;
				
				// parse mail body
				preg_match_all('/<PRE>(.*?)<\/PRE>/mi', $content, $regtext);
				$tmpjson["text"] = $regtext[1][0];
				
				sleep(0.1);
			}
			
			$json = json_encode($tmpjson);
			
			file_put_contents($jsonfile, $json);
		}
	}
}

?>