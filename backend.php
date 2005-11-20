<?php

$fond = "backend";
$delais = 3600;

if (eregi("^bestof", $src))
{ $fond = "backend-bestof"; }

if (eregi("^tout", $src))
{ $fond = "backend-tout"; }

// cette ligne empeche l'affichage des boutons d'administration
// Et les headers !!!!

$flag_preserver = true;

// pas logguer les backends en referers
$GLOBALS['HTTP_REFERER'] = '';
$_SERVER['HTTP_REFERER'] = '';

include ("inc-public.php3");

?>
