<?php

$type = $_GET['type'];
$zip_code = $_GET['zip_code'];

$sql = "select * from person where zip = '".$zip_code."' order by name asc";

$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");

$result = mysql_query($sql, $dblink) or die("ERROR: query failed<br>sql = ".$sql."<br>");
$num = mysql_numrows($result);

$xmlString = "";

if($type == "map") {
	$xmlString .= "<?xml version=\"1.0\"?>";
	$xmlString .= "<page>";
	$xmlString .= "<title></title>";
	$xmlString .= "<query></query>";
	$xmlString .= "<request>";
	$xmlString .= "<url></url>";
	$xmlString .= "<query></query>";
	$xmlString .= "</request>";

	if($num > 0) {
		$centerLat=mysql_result($result,0,"lat");
		$centerLon=mysql_result($result,0,"lon");
		$xmlString .= "<center lat=\"".$centerLat."\" lng=\"".$centerLon."\"/>";
		$xmlString .= "<span lat=\"0.062134\" lng=\"0.104253\"/>";
		$xmlString .= "<searchcenter lat=\"".$centerLat."\" lng=\"".$centerLon."\"/>";
		$xmlString .= "<searchspan lat=\"0.062134\" lng=\"0.104253\"/>";
	}
	else {
		$xmlString .= "<center lat=\"37.062500\" lng=\"-95.677068\"/>";
		$xmlString .= "<span lat=\"62.625000\" lng=\"105.459315\"/>";
		$xmlString .= "<searchcenter lat=\"37.062500\" lng=\"-95.677068\"/>";
		$xmlString .= "<searchspan lat=\"62.625000\" lng=\"105.459315\"/>";
	}

	$xmlString .= "<overlay panelStyle=\"http://www.somapper.com/xsl/geocodepanel.xsl\">";

	if($num > 0) {
		$i=0;
		while ($i < $num) {
			$person_id=mysql_result($result,$i,"person_id");
			$details_link=mysql_result($result,$i,"details_link");
			$name=mysql_result($result,$i,"name");
			$image=mysql_result($result,$i,"image");
			$address_type=mysql_result($result,$i,"address_type");
			$address=mysql_result($result,$i,"address");
			$state=mysql_result($result,$i,"state");
			$lat=mysql_result($result,$i,"lat");
			$lon=mysql_result($result,$i,"lon");

			$xmlString .= "<location infoStyle=\"http://www.somapper.com/xsl/infostyle_statemap.xsl\" id=\"".$i."\">\n";
			$xmlString .= "<point lat=\"".$lat."\" lng=\"".$lon."\"/>\n";
			$xmlString .= "<icon image=\"http://www.somapper.com/images/society.png\" class=\"local\"/>\n";
			$xmlString .= "<info>\n";
			$xmlString .= "<id>".$i."</id>\n";
			$xmlString .= "<title xml:space=\"preserve\">Details</title>\n";
			$xmlString .= "<url>".str_replace("&", "&amp;", $details_link)."</url>\n";
			$xmlString .= "<imgsrc>".str_replace("&", "&amp;", $image)."</imgsrc>\n";
			$xmlString .= "<name>".str_replace("&nbsp;", " ", $name)."</name>\n";

			if($address_type == "general")
				$xmlString .= "<type>Address</type>\n";
			else if($address_type == "work")
				$xmlString .= "<type>Work Address</type>\n";
			else if($address_type == "school")
				$xmlString .= "<type>School Address</type>\n";
			else if($address_type == "home")
				$xmlString .= "<type>Home Address</type>\n";

			$addresses = array();
			$addresses = explode("<br>", $address);
			$lineCount = count($addresses);
			if($lineCount == 1) {
				$addresses[0] = str_replace("\n;", "", $addresses[0]);
				$addresses[0] = str_replace("&nbsp;", " ", $addresses[0]);

				$xmlString .= "<address1>".$addresses[0]."</address1>\n";
				$xmlString .= "<address2></address2>\n";
			}
			else {
				$addresses[0] = str_replace("\n;", "", $addresses[0]);
				$addresses[0] = str_replace("&nbsp;", " ", $addresses[0]);

				$addresses[1] = str_replace("\n;", "", $addresses[1]);
				$addresses[1] = str_replace("&nbsp;", " ", $addresses[1]);
				$xmlString .= "<address1>".$addresses[0]."</address1>\n";
				$xmlString .= "<address2>".$addresses[1]."</address2>\n";
			}

			$xmlString .= "</info>\n";
			$xmlString .= "</location>\n";

			$i++;
		}
	}
	else {
		$xmlString .= "<location infoStyle=\"http://www.somapper.com/xsl/infostyle_mainmap.xsl\" id=\"0\">";
		$xmlString .= "<point lat=\"38.895000\" lng=\"-77.036667\"/>";
		$xmlString .= "<icon image=\"http://www.somapper.com/images/society.png\" class=\"local\"/>";
		$xmlString .= "<info>";
		$xmlString .= "<id>0</id>\n";
		$xmlString .= "<title xml:space=\"preserve\">Washington DC</title>";
		$xmlString .= "<url>javascript:update(20001);</url>";
		$xmlString .= "<city>Washington DC</city>";
		$xmlString .= "</info>";
		$xmlString .= "</location>";
		$xmlString .= "<location infoStyle=\"http://www.somapper.com/xsl/infostyle_mainmap.xsl\" id=\"1\">";
		$xmlString .= "<point lat=\"39.286580\" lng=\"-76.607221\"/>";
		$xmlString .= "<icon image=\"http://www.somapper.com/images/society.png\" class=\"local\"/>";
		$xmlString .= "<info>";
		$xmlString .= "<id>1</id>\n";
		$xmlString .= "<title xml:space=\"preserve\">Maryland</title>";
		$xmlString .= "<url>javascript:update(21202);</url>";
		$xmlString .= "<city>Maryland</city>";
		$xmlString .= "</info>";
		$xmlString .= "</location>";
	}

	$xmlString .= "</overlay>";
	$xmlString .= "</page>";

}
else if($type == "list") {

	if($num > 0) {
		$xmlString = "<people>";

		$i=0;
		while ($i < $num) {
			$name=mysql_result($result,$i,"name");
			$address=mysql_result($result,$i,"address");

			$name = str_replace("&nbsp;", " ", $name);
			$name = str_replace("nbsp;", " ", $name);
			$name = str_replace("\n", "", $name);
			$name = trim($name);

			$address = str_replace("&nbsp;", " ", $address);
			$address = str_replace("<br>", " ", $address);
			$address = str_replace("\n", "", $address);
			$address = str_replace("nbsp;", " ", $address);
			$address = trim($address);

			$xmlString .= "<person>\n";
			$xmlString .= "<id>".$i."</id>\n";
			$xmlString .= "<name>".$name."</name>\n";
			$xmlString .= "<address>".$address."</address>\n";
			$xmlString .= "</person>\n";

			$name = "";
			$address = "";
			$i++;
		}

		$xmlString .= "</people>";
	}
	else {
		$xmlString .= "<cities>";
		$xmlString .= "<city>";
		$xmlString .= "<id>0</id>\n";
		$xmlString .= "<name><a href=\"javascript:update(20001);\">Washington DC</a></name>";
		$xmlString .= "</city>";
		$xmlString .= "<city>";
		$xmlString .= "<id>1</id>\n";
		$xmlString .= "<name><a href=\"javascript:update(21202);\">Maryland</a></name>";
		$xmlString .= "</city>";
		$xmlString .= "</cities>";
	}

}
	// content type is xml
	header("Content-Type: text/xml");

	// print out the xml file we sucked in
	print($xmlString)

?>