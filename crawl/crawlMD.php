<?php

	$newline = "<br />";

	$url = "http://www.dpscs.state.md.us/sorSearch/search.do?searchType=byName&anchor=false&lastnm=&firstnm=";

	$numRecs = get_num_records_md($url);

	// get the number of records found
	$num_records = get_num_records_md($url);
	echo "num_records = ".$num_records."<br>";

	// get the number of records found
	echo "Beginning parsing...<br>";

	$dblink = mysql_connect("localhost", "naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("peoplestage", $dblink) or die("ERROR: count not select db");
	$sql = "DELETE FROM personstage where state = \"MD\"";
	$result = mysql_query($sql, $dblink) or die("ERROR: delete failed<br>sql = ".$sql."<br>");

	// go through each page and parse out the events
	for ($counter = 1; $counter <= $num_records; $counter += 10) {
		echo "count = ".$counter."<br>";

		$pageurl = $url."&start=".$counter;
		$content = file_get_contents($pageurl);

		get_chunks($content);
	}

	echo "Done parsing...<br>";

	echo "Getting coordinates...<br>";
	getCoords();
	echo "Done getting coordinates...<br>";

	echo "deleting empty coordinates...<br>";
	deleteNoCoords();
	echo "Done deleting empty coordinates...<br>";


// get the number of records in washington post
function get_num_records_md($url)
{
	$pageCont = file_get_contents($url);

	$tokenBefore = "Total Registrant Found: ";
	$tokenAfter = "</td>";
	$indexBeforeToken = strpos($pageCont, $tokenBefore) + strlen($tokenBefore);
	$indexAfterToken = strpos($pageCont, $tokenAfter, $indexBeforeToken);

	$numRecords = trim(substr($pageCont, $indexBeforeToken, $indexAfterToken-$indexBeforeToken));

	return (int)$numRecords;
}

function get_chunks($pageContent) {
	$marker = "<div class=\"smallPrint\">";

	$currentPos = 0;

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

	$dblink = mysql_connect("localhost", "naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("peoplestage", $dblink) or die("ERROR: count not select db");

	$detailsLink = "";
	$name = "";
	$image = "";
	$address = "";
	$homeAddress = "";
	$schoolAddress = "";
	$workAddress = "";
	$state = "MD";

	// get the name
	if(strpos($chunk, "<td class=\"smallPrint\">Name:</td>", $currentP)) {
		$currentP = strpos($chunk, "<td class=\"smallPrint\">Name:</td>", $currentP) + strlen("<td class=\"smallPrint\">Name:</td>");
		$start = strpos($chunk, "<td class=\"smallPrint\">", $currentP) + strlen("<td class=\"smallPrint\">");
		echo "start = ".$start."<br>";

		$end = strpos($chunk, "</td>", $start);
		echo "end = ".$end."<br>";

		$name = substr($chunk, $start, $end-$start);
		$name = str_replace("\n;", "", $name);
		$name = escape($name);
		echo "name = ".$name."<br>";
		$currentP = $end;
	}

	if(strpos($chunk, "<td class=\"smallPrint\">Address:</td>", $currentP)) {
		$currentP = strpos($chunk, "<td class=\"smallPrint\">Address:</td>", $currentP) + strlen("<td class=\"smallPrint\">Address:</td>");
		$start = strpos($chunk, "<td class=\"smallPrint\">", $currentP) + strlen("<td class=\"smallPrint\">");
		echo "start = ".$start."<br>";

		$end = strpos($chunk, "</td>", $start);
		echo "end = ".$end."<br>";

		$address = substr($chunk, $start, $end-$start);
		$address = str_replace("\n;", "", $address);
		$address = escape($address);
		echo "address = ".$address."<br>";
		$currentP = $end;
	}

	if(strpos($chunk, "/sorSearch/search.do?searchType=detail&anchor=false&id=", $currentP)) {
		$start = strpos($chunk, "/sorSearch/search.do?searchType=detail&anchor=false&id=", $currentP);
		echo "start = ".$start."<br>";

		$end = strpos($chunk, "\">", $start);
		echo "end = ".$end."<br>";

		$detailsLink = "http://www.dpscs.state.md.us".substr($chunk, $start, $end-$start);
		$detailsLink = str_replace("\n;", "", $detailsLink);
		echo "detailsLink = ".$detailsLink."<br>";
		$currentP = $end;
	}

	if(strpos($chunk, "<img src=\"", $currentP)) {
		$start = strpos($chunk, "<img src=\"", $currentP) + strlen("<img src=\"");
		echo "start = ".$start."<br>";

		$end = strpos($chunk, "\"", $start);
		echo "end = ".$end."<br>";

		$image = "http://www.dpscs.state.md.us".substr($chunk, $start, $end-$start);
		$image = str_replace("\n;", "", $image);
		echo "image = ".$image."<br>";
		$currentP = $end;
	}

	$addressType = "general";

	// now insert into the database;
	$sql = "INSERT INTO personstage (
					person_id,
					details_link,
					name,
					image,
					address_type,
					address,
					state
				) VALUES (
					'',
					'$detailsLink',
					'$name',
					'$image',
					'$addressType',
					'$address',
					'$state'
			)";
	echo $sql."<br>";
	$result = mysql_query($sql, $dblink) or die("ERROR: insert failed<br>sql = ".$sql."<br>");
	echo "inserted row<br>";

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
	$dblink = mysql_connect("localhost", "naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("peoplestage", $dblink) or die("ERROR: count not select db");

	$sql = "select person_id, address from personstage where state = 'MD'";
	$result = mysql_query($sql, $dblink);
	$numrows = mysql_num_rows($result);

	if($numrows > 0) {
		for($i = 0; $i < $numrows; $i++) {
			echo "getCoords i = ".$i."<br>";

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
	$dblink = mysql_connect("localhost", "naffis", "naffis04host") or die("ERROR: count not connect");
	mysql_select_db("peoplestage", $dblink) or die("ERROR: count not select db");

	$sql = "delete from personstage where lat = '' OR lon = ''";
	$result = mysql_query($sql, $dblink);
}


?>