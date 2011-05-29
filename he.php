<?php

print "Start";
require_once "getips.php";
print ".";
require_once 'commands.php';
print ".";
require_once 'setting.php';
print ".";
$settings = loadSettings();
print ".\n";
$settings = he($settings);
//saveSettings($settings);



print "End\n";

//$settings->username = "";
///$password = "";
//$directory = "/HECT/";
//$settings->repeat = 50;
//$settings->debug = true;
//$settings->timezone = new DateTimeZone('GB');
//he($settings->username,$password,$directory,$settings->repeat, $settings->debug,$settings->timezone);

/*

  Main function

 */
function he($settings) {
    date_default_timezone_set($settings->timezone);
    if ($settings->debug)
        print("Current Dir: " . $settings->directory . "\n");
    //making dir is doesn't exist
    if (!file_exists($settings->directory)) {
        mkdir($settings->directory, 0700, TRUE);
    }
    print "Next\n";

    if ($settings->debug) {

        $time = new DateTime("now");

        print "Start:" . $time->format(DATE_RFC822) . "\n";
    }
    print "Next\n";

    getIPs($settings);
    print "Get Address\n";
    $address = getAddress($settings);

    if ($settings->debug)
        echo "IP:" . $address['ip'] . " Host:" . $address['host'] . "\n";

    //
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://ipv6.he.net/certification/login.php');
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'f_user=' . urlencode($settings->username) . '&f_pass=' . urlencode(md5($settings->password))) . '&Login=Login';
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "");
    curl_setopt($ch, CURLOPT_COOKIEFILE, $settings->directory . "my_cookies.txt");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");

    $fh = fopen($settings->directory . "login.txt", 'w');
    fwrite($fh, curl_exec($ch));
    fclose($fh);

    $tests = $settings->tests;
    foreach ($tests as $test) {
        echo $test['name'] . "\n";
        curl_setopt($ch, CURLOPT_URL, $test['url']);
        curl_setopt($ch, CURLOPT_POST, 0);

        $pageHtml = curl_exec($ch);
        preg_match("{\<div id='vote_record'\>(.*)\</div\>}", $pageHtml, $match);
        $nextq = FALSE;
        if (count($match) > 0) {
            if (preg_match("/Sorry, you've already submitted an IPv6/i", $match[1])) {
                if ($settings->debug)
                    echo "Done\n";
            } else {
                if ($settings->debug) {
                    echo "Something else\n";
                    print_r($match);
                }
            }
        } else {
            if ($settings->debug)
                echo "Not Done\n";
            $nextq = performRepeat($test, $ch, $address, $settings) || $nextq;
        }
    }
// Check if any test has been done. If so go to next address
    if ($nextq) {
        nextnumber($settings);
    }


    if ($settings->debug) {
        $time = new DateTime("now");
        print "Done:" . $time->format(DATE_RFC822) . "\n";
    }
}

function performTest($test, $settings, $ch, $address) {
    $cmd = str_replace("{ip}", $address['ip'], $test['command']);
    $cmd = str_replace("{host}", $address['host'], $cmd);


    if ($settings->debug)
        echo "Performing Test..\n";
    if ($settings->debug)
        $cmd . "\n";
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
    $fh = fopen($settings->directory . $test['htmloutput'], 'w');
    fwrite($fh, $pageHtml);
    fclose($fh);
    $fh = fopen($settings->directory . $test['rawoutput'], 'w');
    fwrite($fh, $command);
    fclose($fh);

    return $pageHtml;
}

/*

  Not used
  Get the your scores of $settings->username and put it in to csv file called source.cvs

 */

function checkscore($settings) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://ipv6.he.net/certification/scoresheet.php?pass_name=' . $settings->username);


    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
    $pageHtml = curl_exec($ch);

    preg_match("{\<td align=right\>Current Score: \<font color=blue\>\<b\>(.*)\</b>}", $pageHtml, $match);
    $total = str_replace(",", "", $match[1]);

    preg_match("{\<b\>Daily Traceroute\</b\> &nbsp;; Score: (.*)/ 100\</div\>}", $pageHtml, $match);
    $traceroot = $match[1];
    preg_match("{\<b\>Daily Dig \(AAAA\)\</b\> &nbsp;; Score: (.*)/ 100\</div\>}", $pageHtml, $match);
    $digaaaa = $match[1];
    preg_match("{\<b\>Daily Ping6\</b\> &nbsp;; Score: (.*)/ 100\</div\>}", $pageHtml, $match);
    $ping = $match[1];
    preg_match("{\<b\>Daily Whois\</b\> &nbsp;; Score: (.*)/ 100\</div\>}", $pageHtml, $match);
    $whois = $match[1];
    preg_match("{\<b\>Daily Dig \(PTR\)\</b\> &nbsp;; Score: (.*)/ 100\</div\>}", $pageHtml, $match);
    $digptr = $match[1];
    echo "Total Score: " . $total . "</td><td> Tracert: " . $traceroot . " Dig (AAAA): " . $digaaaa . " Dig (PTR): " . $digptr . " Daily Whois: " . $whois . " Ping6: " . $ping . "\n";
    $sorcehandle = fopen($settings->directory . "score.csv", "a");
    $date = date('c');
    fwrite($sorcehandle, $date . ',' . $total . ',' . $traceroot . ',' . $ping . ',' . $whois . ',' . $digptr . ',' . $digaaaa . "\n");
    fclose($sorcehandle);
}

/*

  Get and address from ip

 */

function getAddress($settings) {


    $iphandle = fopen($settings->directory . "ip.csv", "r");
    for ($i = 0; $i <= $settings->number; $i++) {
        $ipdata = fgetcsv($iphandle);
    }
    fclose($iphandle);

    if (count($ipdata) < 2) {
        print "Ran Out\n";
        getIPs($settings);
        zeronumber($settings);
        $number = 0;
        $iphandle = fopen($settings->directory . "ip.csv", "r");
        $ipdata = fgetcsv($iphandle);
        fclose($iphandle);
        if (count($ipdata) != 2) {
            print "No IP Addresses to use\n";
            saveSetting($settings);
            exit(1);
        }
    }

    $host = escapeshellcmd(base64_decode($ipdata[0]));
    $ip = escapeshellcmd(base64_decode($ipdata[1]));
    $address = array('host' => escapeshellcmd(base64_decode($ipdata[0])), 'ip' => escapeshellcmd(base64_decode($ipdata[1])));
    return $address;
}

//function getNumber($settings)
//{
//	touch($settings->directory."number.txt");
//	$numberhandle = fopen($settings->directory."number.txt","r");
//	$number = fgets($numberhandle);
//	if (!is_numeric($number))
//	{
//		$number = 0; 
//	}
//	fclose($numberhandle);
//	if($settings->debug) print "Number:".$number."\n";
//	return $number;
//}

function zeronumber($settings) {
    $settings->number = 0;
    saveSettings($settings);
    //     return $settings; //Not needed as PHP is pass by ref.
//	$fh = fopen($settings->directory."number.txt","w");
//	fclose($fh);    
}

function nextnumber($settings) {
    //$number = getNumber($settings->directory,$settings->debug);
    $settings->number = $settings->number + 1;
    saveSettings($settings);
    return $settings;
//$nextnum = fopen($settings->directory."number.txt","w");
    //fwrite($nextnum,$number);
    //fclose($nextnum);    
    //if($settings->debug) print "Next number:".$number." Written bytes:".$number."\n";
    //getNumber($settings->directory,$settings->debug);
}

function performRepeat($test, $ch, $address, $settings) {
    for ($i = 0; $i < $settings->repeat; $i++) {
        print "Performing again : " . $i . "\n";

        $pageHtml = performTest($test, $settings, $ch, $address);
        if (preg_match("/Result\: Pass/i", $pageHtml, $match)) {
            print "Pass\n";
            recordSuccess($test, "Pass", $address, $settings);
            return TRUE;
            break;
        } else {
//			preg_match("{\<div id='vote_record'\>(.*)\</div\>}",$pageHtml,$match);	
//			echo $match[1]; 

            $failinfo = proccessFails($pageHtml, $settings);

            recordSuccess($test, "F-" . $failinfo, $address, $settings);

            nextnumber($settings);
            $address = getAddress($settings);
        }
    }
}

function recordSuccess($test, $success, $address, $settings) {
    $time = new DateTime("now");
    $date = $time->format("c");
    
    $fh = fopen($settings->directory.$settings->passresults, 'a');


    fwrite($fh, $date . ',' . $test['name'] . ',' . $success . ',' . $address['host'] . ',' . $address['ip'] . "\n");
    fclose($fh);
}

function proccessFails($pageHtml, $settings) {
    if (preg_match("/Sorry, you've already submitted a whois query that has the same netblock/i", $pageHtml, $match)) {

        if ($settings->debug)
            print "Fail - whois same netblock\n";
        return "same netblock";
    }
    elseif (preg_match("/Sorry, this page is for dig forward submission only/i", $pageHtml, $match)) {
        if ($settings->debug)
            print "Fail - forward dig\n";
        return "forward dig";
    } elseif (preg_match("/Sorry, you've already submitted a traceroute/i", $pageHtml, $match)) {
        if ($settings->debug)
            print "Fail - same  traceroute\n";
        return "same address";
    } elseif (preg_match("/Sorry, you've already submitted a ping output to the same destination/i", $pageHtml, $match)) {
        if ($settings->debug)
            print "Fail - same ping\n";

        return "same address";
    } elseif (preg_match("/Sorry, you've already submitted a dig query to the same destination/i", $pageHtml, $match)) {
        if ($settings->debug)
            print "Fail - same  address dig\n";
        return "same address";
    } elseif (preg_match("/Result\: Fail/i", $pageHtml, $match)) {
        if ($settings->debug)
            print "Fail - error in submission\n";
        $fh = fopen($settings->directory . "fail.html", 'a');
        fwrite($fh, $pageHtml);
        fclose($fh);

        return "error in submission";
    } else {
        if ($settings->debug)
            print "Fail\n";
        if ($settings->debug)
            print "Fail - unknown reason\n";
        $fh = fopen($settings->directory . "fail.html", 'w');
        fwrite($fh, $pageHtml);
        fclose($fh);

        preg_match("{\<div id='vote_record'\>(.*)\</div\>}", $pageHtml, $match);
        if (count($match) > 2) {
            if ($settings->debug)
                echo $match[1];

            return "M-" . $match[1];
        }
        else {
            return "unknown error";
        }
    }
}

?>
