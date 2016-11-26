<?php

$mainurl = "http://listserv.aoir.org/pipermail/air-l-aoir.org/";
$mainsha = "/" . sha1($mainurl);

$localpath = "/Applications/XAMPP/xamppfiles/htdocs/labs.polsys.net/tools/piperscraper";
$jsonpath = "/json_mail";


// check for json directory, if not there make it
$jsondir = $localpath . $jsonpath . $mainsha;

//echo $jsondir; exit;
if(!file_exists($jsondir)) {
	mkdir($jsondir);
}


// parse main archive page

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $mainurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($ch);

if($content) {
			
	preg_match_all('/<A href="(.*)\/thread.html">/mi', $content, $current);
	
	echo "getting " . count($current[1]) . " months: ";
	
	$counter = 0;
	foreach($current[1] as $link) {
		
		$counter++;
		
		parseMonth($link);
		
		echo $counter . " ";  
	}
}


function parseMonth($month) {
	
	global $mainurl,$jsondir;
	
	$link = $mainurl . $month."/thread.html";
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$content = curl_exec($ch);
	
	$content = preg_replace('/[\r\n]/', '', $content);
	
	//print_r($content);
	
	/*
	<LI><A HREF="021865.html">[Air-L] Mobile Digital Interactive Storytelling 2nd cfp - New Review of Hypermedia and Multimedia
	</A><A NAME="21865">&nbsp;</A>
	<I>Cunliffe D J (AT)
	</I>
	*/	
	
	//preg_match_all('/<UL>(.*?)<\/UL>/', $content, $current);
	
	//print_r($current);
	//exit;
	
	$content = preg_replace("/<\!--htdig_noindex-->/","",$content);
	$content = preg_replace("/<\!--\/htdig_noindex-->/","",$content);
	
	preg_match_all('/<\!--(.*?) (.*?)- --><LI><A HREF="(.*?)">(.*?)<\/A>(.*?)<I>(.*?)<\/I>/i', $content, $current);
	
	for($i = 0; $i < count($current[1]); $i++) {
		
		if($current[1][$i] != 0) {
			$thread = explode("-",$current[2][$i]);
			$id = $thread[count($thread)-1];
			$replytoid = $thread[count($thread)-2];
		} else {
			$id = $current[2][$i];
			$replytoid = "base";
		}
		
		
		$author = $current[6][$i];
		$subject = $current[4][$i];
		$text = "";
		$date = date("Y-m-d H:i:s",strtotime($month));
		$link = $mainurl . $month . "/" . $current[3][$i];
		
		$link_sha = sha1($link);
		
		$tmpjson = array();
		$tmpjson["id"] = $id;
		$tmpjson["author"] = $author;
		$tmpjson["subject"] = $subject;
		$tmpjson["replytoid"] = $replytoid;
		$tmpjson["date"] = $date;
		$tmpjson["link"] = $link;
		
		$jsonfile = $jsondir . "/" . $tmpjson["id"] . ".json";
		
	
		if(file_exists($jsonfile)) {
			continue;
		}
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($ch);
		
		//echo $content; exit;
				
		if($content) {
			
            $content = preg_replace('/[\r\n]/', ' ', $content);

            preg_match_all('/<I>(.*?)<\/I>/mi', $content, $current);

            // create format to parse: 10/Oct/2000:13:55:36 -0700
            
            $current[1][0] = preg_replace("/\s+/"," ",$current[1][0]);
            
            $timeels = explode(" ",$current[1][0]);

            $thedate = $timeels[2] . "/" . $timeels[1] . "/" . $timeels[5] . ":" . $timeels[3] . " -0000";

			
			$newdate = date( 'Y-m-d H:i:s', strtotime($thedate));
			
			$tmpjson["date"] = $newdate;
			
			
			// get the mail body
			preg_match_all('/<PRE>(.*?)<\/PRE>/mi', $content, $current);
		
			$tmpjson["text"] = addslashes($current[1][0]);
			
			sleep(1);
		}
		
		$json = json_encode($tmpjson);
		
		file_put_contents($jsonfile, $json);
	}

}

?>