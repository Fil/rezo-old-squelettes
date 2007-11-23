<?php

	# pour admirer le resultat et memoriser en masse :
	# fil@miel> watch "links -dump http://rezo.net/page_memo.php?debug=av; sleep 4"

	// utiliser lynx -dump sauf contrordre
	$use_lynx = true;
	$lynx = '/usr/bin/lynx -dump -crawl -force-html -width=1024';

	// en sortie : une image (astuce timeout)
	if (!$debug) {
		@header("Content-Type: image/png");
		readfile('IMG/test.png');
		flush();
		ob_start();	// absorber les outputs potentiels
	}

	// nice catch vers les pages "imprimer"
	function rectifie_url($url) {
	   $url=ereg_replace("http://infos.samizdat.net/blog/page.php\?p=(.*)",
	   		     "http://infos.samizdat.net/blog/imprimer.php?p=\\1", $url);
	   $url=ereg_replace("http://www.humanite.*fr/journal/.*/....-..-..-(.*)$", 
	                     "http://www.humanite.fr/popup_print.php3?id_article=\\1", $url);
	   $url=ereg_replace("http://www.liberation.fr/page.php\?Article=(.*)",
                             "http://www.liberation.fr/imprimer.php?Article=\\1", $url);
	   $url=ereg_replace("http://bravepatrie.com/article.php3\?id_article=([0-9]+)", 
	   		     "http://bravepatrie.com/imprimersans.php3?id_article=\\1", $url);
           $url=ereg_replace("http://www.terredescale.net/article.php3\?id_article=([0-9]+)",
	                     "http://www.terredescale.net/imprimer.php3?id_article=\\1", $url);
  	   /* $url=ereg_replace("http://lmsi.net/article.php3\?id_article=([0-9]+)", "http://lmsi.net/impression.php3?id_article=\\1", $url); */
		$url=ereg_replace("http://www.balkans.eu.org/article([0-9]+).html",
			"http://www.balkans.eu.org/print_article.php3?id_article=\\1", $url);
		$url=ereg_replace("showarticle.cfm", "print_article.cfm", $url);
		$url=ereg_replace("http://rezo.net/interdit/(.*)",
			"http://www.interdits.net/\\1", $url);
		$url=ereg_replace("http://www.cadtm.org/article.php3\?id_article=([0-9]+)",
			"http://www.cadtm.org/imprimer.php3?id_article=\\1", $url);
		$url=ereg_replace("http://martinwinckler.com/article.php3\?id_article=([0-9]+)",
			"http://martinwinckler.com/imprimer.php3?id_article=\\1", $url);
		$url=ereg_replace("http://www.lemonde.fr/web/article/(.*)",
			"http://www.lemonde.fr/web/imprimer_article/\\1", $url);
	   return $url;
	} 

	// ne pas prendre les sources
	function merdique($url) {
	   $merdique=0;
		if (eregi("passant-ordinaire", $url)) $merdique=1;
		if (eregi("counterpunch", $url)) $merdique=1;
		if (eregi("salon.com", $url)) $merdique=1;
		if (eregi("independent.co.uk", $url)) $merdique=1;
		if (eregi("nybooks.com", $url)) $merdique=1;
	   return $merdique;
	}

	// en cas de saturation serveur : rien
	if (!$debug AND @file_exists('ecrire/data/lock')) die("lock surcharge serveur");


	// connexion a spip
	include('ecrire/inc_version.php3');
	include_ecrire('inc_connect.php3');
	if (!$db_ok) die("pas de base");
	include_ecrire('inc_filtres.php3');
	include_ecrire('inc_charsets.php3');
	include_ecrire('inc_sites.php3');

	// stats
	if ($debug) {
		$stats = array(
			'total'=>"select count(*) from spip_articles where (ps>2)",
			'aspires'=>"select count(*) from spip_articles where texte<>'' and texte<>'x'",
			'en erreur'=>"select count(*) from spip_articles where texte ='x'"
		);
		while (list($critere,$req) = each($stats)) {
			$u = spip_query($req);
			$o = spip_fetch_array($u);
			$n = $o[0];
			if ($t==0) $t=$n; else $pcent = " (".(intval(1000*$n/$t)/10)."%)";
			echo "- $critere : ".$o[0]." articles $pcent<br />\n";
		} echo "<br /><p>\n";
	}

	// lock SQL
	if (!spip_get_lock('memo_portail'. date('D:H'))) die("lock SQL");

	// De quel URL allons-nous nous occuper ?
	// On en prend un au hasard parmi les douze plus recents, et une fois sur 5 on accepte 
	// des URLs problematiques (texte='x')
	if (!$id_article = intval($id_article)) {
		$query = "SELECT id_article,url_site,titre FROM spip_articles WHERE NOT(url_site='') AND (ps>2)";
		if (rand(1,5)>1) {
			$query .= " AND texte=''";
			$limit = 12;
		} else {
			$query .= " AND (texte='' OR texte='x')";
			$limit=100000;
		}
//		$query .= " AND not (url_site REGEXP \"\.(avi|jpg|pps|ram|rm|mp3)$\")";
		# date limite = il y a 2 mois
		$datelimite = date('Y-m-d', time()-60*24*3600);
		$query .= " AND date>'$datelimite' ORDER BY date DESC LIMIT 0,$limit";
		$res = spip_query($query);

		unset ($raw);
		$max = rand (1,spip_num_rows($res));
		while (($row = spip_fetch_array($res)) AND ($max-->0)) $raw=$row;

		$url = $raw['url_site'];
		if (!$id_article = $raw['id_article']) die("pas d'article");
	}
	else
	{
		$query = "SELECT url_site FROM spip_articles WHERE id_article=".$id_article;
		$res = spip_query($query);
		if ($row = spip_fetch_array($res)) $url=$row[0];
	}

	$url=rectifie_url($url);
	echo "<pre>URL: <a href='$url'>$url</a> (article <a href='ecrire/articles.php3?id_article=$id_article'>$id_article</a>)\n".$raw['titre']."\n";
	include_ecrire('inc_filtres.php3');
	echo affdate_court($raw['date'])."\n";

	spip_query("UPDATE spip_articles SET texte='x' WHERE id_article=$id_article");

	// indexation du titre
	include_ecrire ("inc_index.php3");
	indexer_objet('article', $id_article); 
	echo "indexation du titre\n";


	//
	// maintenant on essaie de memoriser cet URL
	//

	// cas particulier fichiers .doc et .pdf
	if (eregi("\.(doc|pdf)$", $url, $regs)) {
		$url = ereg_replace("[\"']","",$url);	// sait on jamais !
		if ($regs[1] == 'pdf') {
			`lynx -dump "$url"> ./CACHE/memopage.pdf ; pdftotext ./CACHE/memopage.pdf`;
			$lapage = join("<br>\n", file("./CACHE/memopage.txt"));
			unlink("./CACHE/memopage.pdf");
			unlink("./CACHE/memopage.txt");
		} else if ($regs[1]=='doc') {
			`lynx -dump "$url"> ./CACHE/memopage.doc ; antiword ./CACHE/memopage.doc > ./CACHE/memopage.txt`;
			$lapage = join("<br>\n", file("./CACHE/memopage.txt"));
			unlink("./CACHE/memopage.doc");
			unlink("./CACHE/memopage.txt");
		}
		$lapage = substr($lapage,0,30*1024);	// pas plus de 30 ko (!)
	}
	else	// cas de fichiers illisibles : indexer leur titre et c'est tout
	if ((eregi("\.(avi|jpg|pps|ram|rm|mp3)$", $url))||(merdique($url))) {
		spip_query("UPDATE spip_articles SET texte='doc' WHERE id_article=$id_article");
		exit;
	} 
	else {
		// attraper la page html
		$lapage = recuperer_page($url, 'munge_charset');
		$lapage = substr($lapage,0,30*1024);	// pas plus de 30 ko (!)
	}

	// PRETRAITEMENTS
	$lapage = str_replace("\n\r", "\r", $lapage); // echapper au greedyness de preg
	$lapage = str_replace("\n", "\r", $lapage);

	// nettoyer les ' fautives (fakir)
	$lapage = str_replace('’', "'", $lapage);

	// virer les commentaires html (qui cachent souvent css et jajascript)
	$lapage = preg_replace("/<!--.+?-->/", "", $lapage);
	$lapage = preg_replace("/<script\b.+?<\/script>/i", "", $lapage);
	// itals
	$lapage = eregi_replace("<(i|em)([[:space:]][^>]*)?".">", "{", $lapage);
	$lapage = eregi_replace("</(i|em)>", "}", $lapage);
	// gras (pas de {{ pour eviter tout conflit avec {)
	$lapage = eregi_replace("<(b|h[4-6])( [^>]*)?".">", "@b@", $lapage);
	$lapage = eregi_replace("</(b|h[4-6])>", "@/b@", $lapage);

	// liens
	$lapage = eregi_replace("<a[[:space:]][^<>]*href=[^<>]*(http[^ >'\"]*)[^<>]*>(([^<]|<[^/]|</[^a])*)<\/a>", "[\\2->\\1]", $lapage);

	// intertitres
	$lapage = eregi_replace("<(h[123])( [^>]*)?".">", " {{{ ", $lapage);
	$lapage = eregi_replace("</(h[123])>", " }}} ", $lapage);

	// e-dans-l'o et apostrophe
	$lapage = str_replace(chr(156),'oe', $lapage);
	$lapage = str_replace('&#8217;','\'', $lapage);


// POST TRAITEMENT
$lapage = str_replace("\r", "\n", $lapage);
if ($use_lynx) {
	$lapage = preg_replace(',<[^>]*charset[^>]*>,ims', '', $lapage); #charset deja regle par recuperer_page()
	$ftmp = fopen("./CACHE/memopage.html", "wb");
	fputs($ftmp, $lapage);
	fclose($ftmp);
	`lynx -dump -crawl -force-html -width=2048 -assume_charset=iso-8859-1 ./CACHE/memopage.html > ./CACHE/memopage.links.txt`;
	$lapage = file("./CACHE/memopage.links.txt");
//	unlink("./CACHE/memopage.html");
//	unlink("./CACHE/memopage.links.txt");
} else {
	$lapage = textebrut($lapage);
	$lapage = split("\n",$lapage);
}

if (sizeof($lapage) < 10) die("Pas de bol ! La page n'est pas passee\n-------------------------------------\n".join("\n",$lapage));

unset($texte);
unset($inbox);
reset ($lapage);

// virer les horreurs lynxiennes
$lapage[0] = ereg_replace("^THE_URL:.*", "", $lapage[0]);
$lapage[1] = ereg_replace("^THE_TITLE:.*", "", $lapage[1]);

while (list($key,$lig) = each($lapage))
{
    $lig=trim(ereg_replace("@(\/?b)@","<\\1>", $lig));
    if (strlen($lig)>200 AND str_word_count($lig)>5) $inbox=1;
    $lig = ereg_replace("[-_]{10,}", "\n------", $lig);
    if ($inbox) {
        $texte.=$lig."\n";
        if ((strlen($lig)>200)AND(strlen($lig)<980))
            $texte.="\n";
    } else
        $chapo .= $lig."\n";
}

if (!$texte) {
	$texte = join ("\n", $lapage);
}

// couper les "mots" de plus de 40 caracteres (histoire de ne pas exploser la mise en page sur des scories genre URL publicitaire)
function couper_mot_long($matches) {
	$mot = $matches[0];
	if (preg_match("/->.*\]/", $mot, $regs))
		return $mot;

	$mot1 = substr($mot,0,30);
	return $mot1."... \n\n";
}
$texte = preg_replace_callback ("/[\n ][^[\n ]{40,}[\n ]/", couper_mot_long, $texte);

	// INSERTION
	spip_query("UPDATE spip_articles SET texte='".addslashes($texte)."' WHERE id_article=$id_article");
	echo "ok !\n";

	echo $texte;

	// indexation
	include_ecrire ("inc_index.php3");
	indexer_objet('article', $id_article); 
	echo "indexation du contenu\n";


	include("page_thema.php");


	// fin
//	if (!$debug) {
//		// absorber les outputs
//		ob_end_clean();
//	}



?>
