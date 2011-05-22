<?php

include("getips.php");
$username = "ed";
$password = "test";
$directory = "./hect/"; //where all the data is stored. Best absolute ref if using cron.
$repeat = 50;
$debug = true;
$timezone = new DateTimeZone('GB');



he($username,$password,$directory,$repeat, $debug,$timezone);

/*

Main function

*/
function he($username, $password,$currentdir,$repeat,$debug,$timezone)
{
	if($debug) print("Current Dir: ".$currentdir."\n");
	//making dir is doesn't exist
	if (!file_exists($currentdir))
	{
		mkdir($currentdir,0700,TRUE);
	}

	$passresults = "resultspass.csv";
	if($debug)
	{ 
		$time =  new DateTime("now",$timezone);
		print "Start:".$time->format(DATE_RFC822)."\n";    
		
	}

	
	getIPs($currentdir,$debug);
	$address = getAddress($currentdir,$debug);

	if($debug) echo "IP:".$address['ip']." Host:".$address['host']."\n";

	//
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://ipv6.he.net/certification/login.php');
    	curl_setopt($ch, CURLOPT_POSTFIELDS,'f_user='.urlencode($username).'&f_pass='.urlencode(md5($password))).'&Login=Login';
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_COOKIEJAR, "");
    	curl_setopt($ch, CURLOPT_COOKIEFILE, $currentdir."my_cookies.txt");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
        $page = curl_exec($ch);
	$fh = fopen($currentdir."login.html", 'w');
	fwrite($fh,$page);
	fclose($fh);
	
	//Test for login

	if (preg_match("/Either your username or your password is invalid./i",$page,$match))
	{
	print "Login failed\n";
	exit(1);
	}
    
	$tests = array(
		array('name' => 'Whois', 'url' => "http://ipv6.he.net/certification/whois.php",'htmloutput' => 'whois.html', 'rawoutput' => 'whoisraw.txt','textareaname' => 'whoistext' ),
		array('name' => 'Ping', 'url' => "http://ipv6.he.net/certification/ping.php",'htmloutput' => 'ping.html', 'rawoutput' => 'pingraw.txt' ,'textareaname' => 'pingtext' ),
		array('name' => 'dig PTR', 'url' => "http://ipv6.he.net/certification/dig2.php",'htmloutput' => 'dig2.html', 'rawoutput' => 'dig2raw.txt' ,'textareaname' => 'digtext' ),
		array('name' => 'dig AAAA', 'url' => "http://ipv6.he.net/certification/dig.php",'htmloutput' => 'dig.html', 'rawoutput' => 'digraw.txt','textareaname' => 'digtext'  ),
		array('name' => 'Traceroute', 'url' => "http://ipv6.he.net/certification/daily_trace.php",'htmloutput' => 'tracert.html', 'rawoutput' => 'traceraw.txt' ,'textareaname' => 'trtext' )
		);

	foreach($tests as $test)
	{
		echo $test['name']."\n";
		curl_setopt($ch, CURLOPT_URL, $test['url']);
		curl_setopt($ch, CURLOPT_POST, 0);

		$pageHtml = curl_exec($ch);
		preg_match("{\<div id='vote_record'\>(.*)\</div\>}",$pageHtml,$match);	
		$nextq = FALSE;
		if (count($match) > 0)
		{
			if (preg_match("/Sorry, you've already submitted an IPv6/i",$match[1]))
			{
				if($debug)	echo "Done\n";
			} else {
				if($debug)
				{
					echo "Something else\n";
					print_r($match);
				}
			}
		} else	{
			if($debug) echo "Not Done\n";
			$nextq =  performRepeat($test,$repeat,$currentdir,$ch,$address,$passresults,$debug,$timezone) || $nextq;
		}	

	}
// Check if any test has been done. If so go to next address
	if ($nextq) 
	{
		nextnumber($currentdir,$debug);
	}


	if($debug)
	{
		$time =  new DateTime("now",$timezone);
		print "Done:".$time->format(DATE_RFC822)."\n";  


		
	}
}
function performTest($test,$currentdir,$ch,$address,$debug)
	{

	switch ($test['name']){
		case 'Whois': $test['command'] =  "whois ".$address['ip']; break;
		case 'Ping': $test['command'] =  "ping6 ".$address['ip']." -c 3"; break;
		case 'dig PTR': $test['command'] =  "dig -x ".$address['ip']." PTR"; break;
		case 'dig AAAA': $test['command'] =  "dig ".$address['host']." AAAA"; break;
		case 'Traceroute': $test['command'] =  "traceroute -6 ".$address['ip']; break;
	}

	if ($debug)	echo "Performing Test..\n";
	$command = shell_exec($test['command']);

	curl_setopt($ch, CURLOPT_URL, $test['url']);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST, true);

	$data = array(
    		$test['textareaname'] => $command,
    		'submit' => 'Submit',
    		);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$pageHtml = curl_exec($ch);
	$fh = fopen($currentdir.$test['htmloutput'], 'w');
 	fwrite($fh,$pageHtml );
	fclose($fh);
	$fh = fopen($currentdir.$test['rawoutput'], 'w');
 	fwrite($fh,$command );
	fclose($fh);

	return $pageHtml;

}


/*

Not used
Get the your scores of $username and put it in to csv file called source.cvs

*/

function checkscore($username,$currentdir){

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://ipv6.he.net/certification/scoresheet.php?pass_name='.$username);

   
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    	$pageHtml = curl_exec($ch);
    
	preg_match("{\<td align=right\>Current Score: \<font color=blue\>\<b\>(.*)\</b>}",$pageHtml,$match);	
	$total =str_replace(",", "", $match[1]) ;

	preg_match("{\<b\>Daily Traceroute\</b\> &nbsp;; Score: (.*)/ 100\</div\>}",$pageHtml,$match);
	$traceroot = $match[1];
	preg_match("{\<b\>Daily Dig \(AAAA\)\</b\> &nbsp;; Score: (.*)/ 100\</div\>}",$pageHtml,$match);
	$digaaaa = $match[1];
	preg_match("{\<b\>Daily Ping6\</b\> &nbsp;; Score: (.*)/ 100\</div\>}",$pageHtml,$match);
	$ping = $match[1];
	preg_match("{\<b\>Daily Whois\</b\> &nbsp;; Score: (.*)/ 100\</div\>}",$pageHtml,$match);
	$whois = $match[1];
	preg_match("{\<b\>Daily Dig \(PTR\)\</b\> &nbsp;; Score: (.*)/ 100\</div\>}",$pageHtml,$match);
	$digptr = $match[1];
	echo "Total Score: ".$total."</td><td> Tracert: ".$traceroot." Dig (AAAA): ".$digaaaa." Dig (PTR): ".$digptr." Daily Whois: ".$whois ." Ping6: ".$ping."\n";
	$sorcehandle = fopen($currentdir."score.csv", "a");
	$date = date('c');  
	fwrite($sorcehandle,$date.','.$total.','.$traceroot.','.$ping.','.$whois.','.$digptr.','.$digaaaa."\n");
	fclose($sorcehandle);
}

/*

Get and address from ip

*/

function getAddress($currentdir,$debug) {

	$number = getNumber($currentdir,$debug);
	$iphandle = fopen($currentdir."ip.csv", "r");
	for ($i = 0; $i <= $number; $i++) {
		$ipdata = fgetcsv($iphandle);
	}
	fclose($iphandle);    

	if (count($ipdata) < 2) { 
		print "Ran Out\n";
		getIPs($currentdir,$debug);
		zeronumber($currentdir,$debug);
		$number=0;
		$iphandle = fopen($currentdir."ip.csv", "r");
		for ($i = 0; $i <= $number; $i++) {
			$ipdata = fgetcsv($iphandle);
		}
		fclose($iphandle);    
		if (count($ipdata) != 2)
		{
		print "No IP Addresses to use\n";
		exit(1);
		}
	}

	$host = escapeshellcmd(base64_decode($ipdata[0]));
	$ip = escapeshellcmd(base64_decode($ipdata[1]));
	$address = array ( 'host' => escapeshellcmd(base64_decode($ipdata[0])), 'ip' => escapeshellcmd(base64_decode($ipdata[1])));
	return $address;

}

function getNumber($currentdir,$debug)
{
	touch($currentdir."number.txt");
	$numberhandle = fopen($currentdir."number.txt","r");
	$number = fgets($numberhandle);
	if (!is_numeric($number))
	{
		$number = 0; 
	}
	fclose($numberhandle);
	if($debug) print "Number:".$number."\n";
	return $number;
}

function zeronumber($currentdir,$debug)
{
	$number = 0;
	$fh = fopen($currentdir."number.txt","w");
	
	fclose($fh);    
}

function nextnumber($currentdir,$debug)
{
	$number = getNumber($currentdir,$debug);
	$number = $number + 1;
	$nextnum = fopen($currentdir."number.txt","w");
	fwrite($nextnum,$number);
	fclose($nextnum);    
	if($debug) print "Next number:".$number." Written bytes:".$number."\n";
	//getNumber($currentdir,$debug);
}


function performRepeat($test,$repeat,$currentdir,$ch,$address,$passresults,$debug,$timezone)
{
	for ($i = 0; $i < $repeat;$i++){
		print "Performing again : ".$i."\n";

		$pageHtml =  performTest($test,$currentdir,$ch,$address,$debug);
		if (preg_match("/Result\: Pass/i",$pageHtml,$match)){
			print "Pass\n";	
			recordSuccess($test,$passresults,"Pass",$address,$currentdir,$timezone);
			return TRUE;
			break;
		} else	{
//			preg_match("{\<div id='vote_record'\>(.*)\</div\>}",$pageHtml,$match);	
//			echo $match[1]; 

			$failinfo =	proccessFails($pageHtml,$currentdir,$debug);
		
			recordSuccess($test,$passresults,"F-".$failinfo,$address,$currentdir,$timezone);

			nextnumber($currentdir,$debug);
			$address = getAddress($currentdir,$debug);

		}

	}
}









function recordSuccess($test,$file,$success,$address,$currentdir,$timezone)
{
$time =  new DateTime("now",$timezone);
		$date =  $time->format("c");   
//	$date = date('c');  

	
	$fh = fopen($currentdir.$file, 'a');
	
	
	fwrite($fh,$date.','.$test['name'].','.$success.','.$address['host'].','.$address['ip']."\n");
	fclose($fh);

}


function proccessFails($pageHtml,$currentdir,$debug) {
		if (preg_match("/Sorry, you've already submitted a whois query that has the same netblock/i",$pageHtml,$match))
		{

			if ($debug) print "Fail - whois same netblock\n";	
			return "same netblock";

		}
		elseif (preg_match("/Sorry, this page is for dig forward submission only/i",$pageHtml,$match))
		{
			 if ($debug) print "Fail - forward dig\n";	
			return "forward dig";

		} elseif (preg_match("/Sorry, you've already submitted a traceroute/i",$pageHtml,$match) ){
			if ($debug) print "Fail - same  traceroute\n";	
			return "same address";
			
		} elseif (preg_match("/Sorry, you've already submitted a ping output to the same destination/i",$pageHtml,$match) ){
			if ($debug) print "Fail - same ping\n";	
		
			return "same address";
		
	
		} elseif (preg_match("/Sorry, you've already submitted a dig query to the same destination/i",$pageHtml,$match) ){
			if ($debug) print "Fail - same  address dig\n";
			return "same address";
		} elseif (preg_match("/Either your username or your password is invalid./i",$pageHtml,$match)) { 
			if ($debug) print "Fail - username and password invalid\n";
			return "Username and password invalid";
		}
		elseif (preg_match("/Result\: Fail/i",$pageHtml,$match))
		{
		if ($debug) print "Fail - error in submission\n";
 		 $fh = fopen($currentdir."fail.html", 'a');
                 fwrite($fh,$pageHtml);
                 fclose($fh);

			return "error in submission";	
		} else {
			if ($debug) print "Fail - unknown reason\n";
 		 $fh = fopen($currentdir."fail.html", 'a');
                 fwrite($fh,$pageHtml);
                 fclose($fh);

		preg_match("{\<div id='vote_record'\>(.*)\</div\>}",$pageHtml,$match);	

		if (!empty($match[1]))
		{			
			if ($debug)	echo $match[1]; 
			return "M-".$match[1];
		} else
		{
			return "unknown error";
		}
		}
}	

?>
