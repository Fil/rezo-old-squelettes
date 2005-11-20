<?php

## $fond est definie par le fichier appelant (sommaire800 ou sommaire1024)
$delais = 7200;

	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];

	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
	}
	else if (eregi("mozilla", $browser_name) AND $browser_version >= 5) {
		// Numero de version pour Mozilla "authentique"
		if (ereg("rv:([0-9]+\.[0-9]+)", $browser_description, $match))
			$browser_rev = doubleval($match[1]);
		// Autres Gecko => equivalents 1.4 par defaut (Galeon, etc.)
		else if (strpos($browser_description, "Gecko") and !strpos($browser_description, "KHTML"))
			$browser_rev = 1.4;
		// Machins quelconques => equivalents 1.0 par defaut (Konqueror, etc.)
		else $browser_rev = 1.0;
	}

	if (!$browser_name) $browser_name = "Mozilla";


	if (eregi("mozilla", $browser_name) AND $browser_version < 5) {
		//echo "$browser_name / $browser_version";
		@header("Location: http://rezo.net/lautre");
		exit;
	}



include ("inc-public.php3");

?>
