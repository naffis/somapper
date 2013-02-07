<?php

$zip_code = $_GET['zip_code'];

$sql = "select * from person where zip = '".$zip_code."' order by name asc";

$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");

$result = mysql_query($sql, $dblink) or die("ERROR: query failed<br>sql = ".$sql."<br>");
$num = mysql_numrows($result);

$xmlString = "";


if($num > 0)
	$xmlString .= "1";
else
	$xmlString .= "2";

// content type is xml
//header("Content-Type: text/xml");

// print out the xml file we sucked in
print($xmlString)

?>