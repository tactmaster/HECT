<?php  
$directory = "HECT/";

$iphandle = fopen($directory."ip.csv", "r");
$ipdata = fgetcsv($iphandle);
$counter = 0;
do
{
	$counter++;
  	$host = escapeshellcmd(base64_decode($ipdata[0]));
       $ip = escapeshellcmd(base64_decode($ipdata[1]));

	print "$counter,$ip,$host\n";
       $ipdata = fgetcsv($iphandle);

} while (count($ipdata) == 2);

fclose($iphandle);

       
?>


