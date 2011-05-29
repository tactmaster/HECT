<?php

class Setting {

public $username = "";
public $password = "";
public $directory = "/HECT/";
public $repeat = 50;
public $number = 0;
public $debug = true;
public $timezone = 'GB';
public $passresults = "resultspass.csv";
public $tests;
   function  __construct(){
        $this->tests = array(
                array('name' => 'Whois', 'url' => "http://ipv6.he.net/certification/whois.php",'htmloutput' => 'whois.html', 'rawoutput' => 'whoisraw.txt','textareaname' => 'whoistext','command' => "whois {ip}",'lastdone' => "" ),
                array('name' => 'Ping', 'url' => "http://ipv6.he.net/certification/ping.php",'htmloutput' => 'ping.html', 'rawoutput' => 'pingraw.txt' ,'textareaname' => 'pingtext','command' => "ping6 {ip} -c 3",'lastdone' => "" ),
                array('name' => 'dig PTR', 'url' => "http://ipv6.he.net/certification/dig2.php",'htmloutput' => 'dig2.html', 'rawoutput' => 'dig2raw.txt' ,'textareaname' => 'digtext','command' => "dig -x {ip} PTR",'lastdone' => ""  ),
                array('name' => 'dig AAAA', 'url' => "http://ipv6.he.net/certification/dig.php",'htmloutput' => 'dig.html', 'rawoutput' => 'digraw.txt','textareaname' => 'digtext' ,'command' => "dig {host} AAAA" ,'lastdone' => "" ),
                array('name' => 'Traceroute', 'url' => "http://ipv6.he.net/certification/daily_trace.php",'htmloutput' => 'tracert.html', 'rawoutput' => 'traceraw.txt' ,'textareaname' => 'trtext','command' =>  "traceroute -6 {ip}" ,'lastdone' => "" )
                );


    }


}



?>
