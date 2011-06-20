<?php
/*
Gets all the ip from "http://sixy.ch/feed" and put the in ips.cvs
*/

function getIPs($settings)
{

if ($settings->debug) print "RSS Check ";
// Old method
// $xml= simplexml_load_file("http://sixy.ch/feed");

//Doing stuff to get round php new secruty resistions
//Have long wait for timeout for some reason getting to sixy was slow at time of testing
$xml = loadXML2("sixy.ch","/feed", 100);

touch($settings->directory."ip.csv");

if ($xml && !empty($xml))
{

foreach($xml->entry as $entry) {
	$title = base64_encode($entry->title);
	//getting ip from dig
	$arip = preg_split("/((?<!\\\|\r)\n)|((?<!\\\)\r\n)/",trim(shell_exec("dig ".escapeshellcmd($entry->title)." AAAA +short")));
	//may have multiply ips so adds them a different entries.	
	foreach($arip as $ipraw)
	{
	
	$ipcheck = filter_var($ipraw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6); 
	//print $ipraw." ".$ipcheck."\n";
	$ip = base64_encode($ipcheck);


	// checking to see if ip alread in list. MASSIVELY INEFFICENT. however should be ok as small number of entires. DB better here.
	if ($ip != "" && $title != "")
	{
	        $iphandle = fopen($settings->directory."ip.csv", "r");
				$found = 0;			
//			print "founding:$ip \n";

		do 
		{
                	$ipdata = fgetcsv($iphandle);
			if ($ipdata[0] == $title && $ipdata[1] == $ip)
			{
//			print "found:$title  $ip \n";

				$found = 1;			
				break;
			}

		} while (count($ipdata) == 2);
	        fclose($iphandle);

		if (!$found)
		{
			if ($settings->debug) print "adding:$entry->title $ipcheck $title  $ip \n";
			$handle = fopen($settings->directory."ip.csv", "a");
			fwrite($handle,$title.','.$ip."\n");
			fclose($handle);
		}	
	}
//echo $title."-".$ip."-\n"; 
	}
}
} else 
{
if ($settings->debug){
 print "Feed download failed\n";
var_dump($xml); 
}
}

$count = 0;
$iphandle = fopen($settings->directory."ip.csv", "r");
$ipdata = fgetcsv($iphandle);

	while (count($ipdata) == 2)
	{	
//	print "Counting...:".$count."\n";
	$ipdata = fgetcsv($iphandle);
	$count++;
               
	}

fclose($iphandle);
if ($settings->debug) print "Count:".$count."\n";
}

/*

from http://de2.php.net/manual/en/function.simplexml-load-file.php

thanks to jamie at splooshmedia dot co dot uk

*/
function loadXML2($domain, $path, $timeout = 30) { 

    /* 
        Usage: 
        
        $xml = loadXML2("127.0.0.1", "/path/to/xml/server.php?code=do_something"); 
        if($xml) { 
            // xml doc loaded 
        } else { 
            // failed. show friendly error message. 
        } 
    */ 

    $fp = fsockopen($domain, 80, $errno, $errstr, $timeout); 
   if($fp) { 
        // make request 
        $out = "GET $path HTTP/1.1\r\n"; ;
        $out .= "Host: $domain\r\n"; 
        $out .= "Connection: Close\r\n\r\n"; 
        fwrite($fp, $out); 
        
        // get response 
        $resp = ""; 
        while (!feof($fp)) { 
            $resp .= fgets($fp, 128); 
        } 
 	
        // check status is 200 
        $status_regex = "/HTTP\/1\.\d\s(\d+)/"; 
        if(preg_match($status_regex, $resp, $matches) && $matches[1] == 200) {    
            // load xml as object 
           $parts  = explode("\r\n\r\n", $resp);  
//print $parts[1]; 
 $data = explode('<?xml version="1.0" encoding="utf-8"?>',$resp);
 //var_dump($data);
 
 $feed = explode('</feed>',$data[1]);
            return simplexml_load_string('<?xml version="1.0" encoding="utf-8"?>'.$feed[0].'</feed>');                
        } 
    } 
    return false; 
    
} 
?>
