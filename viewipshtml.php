<html>
<head>
<title>Ips</title>
</head>
<body>

<table border=1>
<tr><th>line</th><th>IP</th><th>Host</th><tr>
<?php  

  $iphandle = fopen("ip.csv", "r");
                        $ipdata = fgetcsv($iphandle);
$counter = 0;
                do
                {
$counter++;
  $host = escapeshellcmd(base64_decode($ipdata[0]));
        $ip = escapeshellcmd(base64_decode($ipdata[1]));

	print "<tr><td>$counter</td><td>$ip</td><td>$host</td></tr>";
                        $ipdata = fgetcsv($iphandle);

                } while (count($ipdata) == 2);
                fclose($iphandle);


        
?>
</table>
</body>
</html>
