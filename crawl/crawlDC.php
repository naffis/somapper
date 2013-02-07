<?php

	$newline = "<br />";

	// connect to the database
	//$dbcon = mysql_connect("localhost","naffis_naffis","naffis04host") or die ("Can not connect to given host");
	//mysql_select_db("eventdata") or die ("Can not connect to database");

	$url = "http://www.somapper.com/crawl/dcdata.txt";

	// get the number of records found
	echo "Beginning parsing...<br>";

	$content = file_get_contents($url);
	get_chunks($content);

	echo "Done parsing...<br>";

	echo "Getting coordinates...<br>";
	getCoords();
	echo "Done getting coordinates...<br>";

	echo "deleting empty coordinates...<br>";
	deleteNoCoords();
	echo "Done deleting empty coordinates...<br>";

//
function get_chunks($pageContent) {
	$marker = "<input type=checkbox name=";

	$currentPos = 0;

	$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");
	$sql = "DELETE FROM personstage where state = \"DC\"";
	$result = mysql_query($sql, $dblink) or die("ERROR: delete failed<br>sql = ".$sql."<br>");

	while(strpos($pageContent, $marker, $currentPos)) {
		$indexStart = strpos($pageContent, $marker, $currentPos);
		echo "indexStart = ".$indexStart."<br>";

		if(strpos($pageContent, $marker, $indexStart + strlen($marker)))
			$indexEnd = strpos($pageContent, $marker, $indexStart + strlen($marker));
		else
			$indexEnd = strlen($pageContent);
		echo "indexEnd = ".$indexEnd."<br>";

		$currentPos = $indexEnd;
		//echo "currentPos = ".$currentPos."<br>";

		$chunk = substr($pageContent, $indexStart, $indexEnd - $indexStart);

		//echo "chunk =".$chunk."<br>";

		// parse and insert the chunk into the database
		parse($chunk);
		echo "currentPos = ".$currentPos."<br>";
	}

}

function parse($chunk) {

	$currentP = 0;

	$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");

	$detailsLink = "";
	$name = "";
	$image = "";
	$homeAddress = "";
	$schoolAddress = "";
	$workAddress = "";
	$state = "DC";

	// get the details link
	if(strpos($chunk, "genDataSheet.asp?id=", $currentP)) {
		$start = strpos($chunk, "genDataSheet.asp?id=", $currentP);
		echo "start = ".$start."<br>";

		$end = strpos($chunk, "\"", $start);
		echo "end = ".$end."<br>";

		$detailsLink = substr($chunk, $start, $end-$start);
		$detailsLink = "http://sor.csosa.net/sor/public/".$detailsLink;
		echo "detailsLink = ".$detailsLink."<br>";
		$currentP = $end;
	}

	// get the name
	if(strpos($chunk, ">", $currentP)) {
		$start = strpos($chunk, ">", $currentP) + strlen(">");
		$end = strpos($chunk, "<", $start);

		$name = substr($chunk, $start, $end - $start);
		echo "name = ".$name."<br>";
		$currentP = $end;
	}
	$name = str_replace("\n;", "", $name);
	$name = str_replace("&nbsp;", "", $name);

	// get the thumbnail
	if(strpos($chunk, "ThumbnailsPDID/", $currentP)) {
		$baseurl = "http://sor.csosa.net/sor/clientPics/ThumbnailsPDID/";
		$start = strpos($chunk, "ThumbnailsPDID/", $currentP) + strlen("ThumbnailsPDID/");
		$end = strpos($chunk, "\"", $start);

		$image = $baseurl.substr($chunk, $start, $end-$start);
		echo "image = ".$image."<br>";
		$currentP = $end;
	}


	// get the home address
	if(strpos($chunk, "Home Address", $currentP)) {
		$start = strpos($chunk, "Home Address", $currentP);
		$start = strpos($chunk, "<br>", $start) + strlen("<br>");
		$end = strpos($chunk, "</td>", $start);

		$homeAddress = substr($chunk, $start, $end-$start);
		echo "homeAddress = ".$homeAddress."<br>";
		$currentP = $end;
	}
	$homeAddress = str_replace("\n;", "", $homeAddress);

	// get the school address
	if(strpos($chunk, "School", $currentP)) {
		$start = strpos($chunk, "School", $currentP);
		$start = strpos($chunk, "<br>", $start) + strlen("<br>");
		$end = strpos($chunk, "</td>", $start);

		$schoolAddress = substr($chunk, $start, $end-$start);
		echo "schoolAddress = ".$schoolAddress."<br>";
		$currentP = $end;
	}
	$schoolAddress = str_replace("\n;", "", $schoolAddress);

	// get the work address
	if(strpos($chunk, "Work", $currentP)) {
		$start = strpos($chunk, "Work", $currentP);
		$start = strpos($chunk, "<br>", $start) + strlen("<br>");
		$end = strpos($chunk, "</td>", $start);

		$workAddress = substr($chunk, $start, $end-$start);
		echo "workAddress = ".$workAddress."<br>";
		$currentP = $end;
	}
	$workAddress = str_replace("\n;", "", $workAddress);


	$countinsert = 0;
	while($countinsert < 3) {
		$sql = "";
		$addressType = "";
		$address = "";
		echo "addressType = ".$addressType."<br>";
		echo "address = ".$address."<br>";

		if($countinsert == 0 && $workAddress != "") {
			$addressType = "work";
			$address = $workAddress;
		}
		else if($countinsert == 1 && $schoolAddress != "") {
			$addressType = "school";
			$address = $schoolAddress;
		}
		else if($countinsert == 2 && $homeAddress != "") {
			$addressType = "home";
			$address = $homeAddress;
		}

		if($addressType != "") {
			// now insert into the database;
			$sql = "INSERT INTO personstage (
							person_id,
							details_link,
							name,
							image,
							address_type,
							address,
							state,
							zip
						) VALUES (
							'',
							'$detailsLink',
							'$name',
							'$image',
							'$addressType',
							'$address',
							'$state',
							'$zip'
					)";
			echo $sql."<br>";
			$result = mysql_query($sql, $dblink) or die("ERROR: insert failed<br>sql = ".$sql."<br>");
			echo "inserted row<br>";
		}
		echo "countinsert=".$countinsert."<br>";
		$countinsert++;

	}
}

function escape($str) {
	return addslashes($str);
}


function getGooglePage($address) {
	$address = fixAddress($address);
	$url = "http://maps.google.com/maps?q=";
	$url = trim($url).trim($address);
	$url = trim($url)."&output=js";
	echo "url = ".$url."<br>";
	return file_get_contents($url);
}

function fixAddress($address) {

	$address = str_replace("&nbsp;&nbsp;", "+", $address);
	$address = str_replace("S.E.", "SE", $address);
	$address = str_replace("S.W.", "SW", $address);
	$address = str_replace("N.E.", "NE", $address);
	$address = str_replace("N.W.", "NW", $address);
	$address = str_replace("<br>", ",+", $address);
	$address = str_replace("Unit Block of ", "1", $address);
	$address = str_replace(" Block of ", "", $address);
	$address = str_replace(" ", "+", $address);
	echo "address = ".$address."<br>";

	return $address;
}

function getCoords() {
	$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");

	$sql = "select person_id, address from personstage";
	$result = mysql_query($sql, $dblink);
	$numrows = mysql_num_rows($result);

	if($numrows > 0) {
		for($i = 0; $i < $numrows; $i++) {
			$row = mysql_fetch_array($result);

			$personId = $row["person_id"];
			$address = $row["address"];

			$content = getGooglePage($address);

			if(strpos($content, "<refinement><query>", $currentP)) {
				// the address was not correct so get the suggested one
				$start = strpos($content, "<refinement><query>", 0) + strlen("<refinement><query>");
				echo "start = ".$start."<br>";
				$end = strpos($content, "</query>", $start);
				echo "end = ".$end."<br>";
				$address = substr($content, $start, $end-$start);
				$content = getGooglePage($address);
			}

			$coords[2] = array("", "");
			$coords = parseCoords($content);
			$zip = parseZip($content);

			$sqlInsert = "UPDATE personstage SET lat = '".$coords[0]."', lon = '".$coords[1]."', zip = '".$zip."' WHERE person_id = ".$personId;
			echo "sqlInsert = ".$sqlInsert."<br>";
			$insertresult = mysql_query($sqlInsert, $dblink) or die("ERROR: insert failed<br>sqlInsert = ".$sqlInsert."<br>");
		}
	}
}

function parseCoords($content) {

	// <point lat="38.891011" lng="-76.982346"/>
	$currentP = 0;
	$coords[2] = array("", "");

	// get the lat
	if(strpos($content, "<point lat=\"", $currentP)) {
		$start = strpos($content, "<point lat=\"", $currentP) + strlen("<point lat=\"");
		echo "start = ".$start."<br>";

		$end = strpos($content, "\"", $start);
		echo "end = ".$end."<br>";

		$coords[0] = substr($content, $start, $end-$start);
		echo "lat = ".$coords[0]."<br>";
		$currentP = $end;
	}

	// get the lon
	if(strpos($content, "lng=\"", $currentP)) {
		$start = strpos($content, "lng=\"", $currentP) + strlen("lng=\"");
		$end = strpos($content, "\"", $start);

		$coords[1] = substr($content, $start, $end-$start);
		echo "lon = ".$coords[1]."<br>";
		$currentP = $end;
	}

	return $coords;
}

function parseZip($content) {

	// 21030</query>
	$currentP = 0;
	$zip = "";

	if(strpos($content, "</query>", $currentP)) {
		$start = strpos($content, "</query>", $currentP) - 5;
		echo "dash pos = ".strpos($content, "-", $start) - $start;
		if(strpos($content, "-", $start)-$start == 0)
			$start = strpos($content, "</query>", $currentP) - 10;

		echo "start = ".$start."<br>";

		$zip = substr($content, $start, 5);
		echo "zip = ".$zip."<br>";
	}

	return $zip;
}

function deleteNoCoords() {
	$dblink = mysql_connect("localhost", "naffis_naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("naffis_mapdata", $dblink) or die("ERROR: count not select db");

	$sql = "delete from personstage where lat = '' OR lon = ''";
	$result = mysql_query($sql, $dblink);
}


?>