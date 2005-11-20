<?php

include_ecrire("inc_filtres.php3");
include_ecrire('inc_cookie.php');
//include_local("wap_mes_fonctions.php3");

// Ne pas remplacer le "-" des citations par une puce Spip
$GLOBALS['puce']='-&nbsp;';

// Signification des champs PS pour l'admin
$GLOBALS['libps']=array("dépêche","article","dépêche","article","très bien","à la une");

function enleve_bayesiens($texte) {
  	$texte=ereg_replace("AUTO (.*)","\\1",$texte);
	$texte=ereg_replace("([^ ]+)-[0-9]+","\\1",$texte);
	$texte=ereg_replace("___","",$texte);
	$texte=ereg_replace("_"," ",$texte);
	return $texte;
}

function url_backend($id_rubrique) {
	if (!$id = intval($id_rubrique)) return;

	// est-ce une source xml ?
	if ($row = spip_fetch_array(spip_query("SELECT * FROM fetch WHERE xml=1 AND source=$id")))
		return $row['url'];

	// sinon url sur le rezo
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique=$id"));
	return "http://rezo.net/backend/".$row['descriptif'];
}

function corriger_img ($texte) {
	$texte = ereg_replace("^IMG/", "http://rezo.net/IMG/", $texte);
	$texte = preg_replace(",alt=\".*?\",", "alt=''", $texte);
	return $texte;
}


// Majdate : afficher une date de mise à jour arrondie, c'est plus joli
function majdate($date_articles='')
{
  if ($date_articles) $new_dt = @strtotime($date_articles); 
  if ($new_dt<1000) { $date=date("Y-m-d H:i:s"); } else { $date=date("Y-m-d H:i:s", $new_dt); }
  $minutes=(int)minutes($date);
  $lesminutes="00";
  if ($minutes>=45) { $lesminutes="45"; }
  else { if ($minutes>=30) { $lesminutes="30"; } 
  else { if ($minutes>=15) $lesminutes="15"; } }
  $ladate=nom_jour($date)." ".jour($date)." ".nom_mois($date)." à ".heures($date)."h".$lesminutes;
  return $ladate;
}
    
// Retitrage : calcule les titres, auteurs et date à afficher selon le "mode"
// d'affichage de la page
// article : col. centrale dans sommaire.html (auteur=nom d'auteur et tjs la source)
// depeche : col. de droite dans sommaire.html (auteur=nom de la source seul)
// rubrique: pour la page "+" d'une source (auteur=nom d'auteur et source si précisée)
// bloc    : col. centrale en mode regroupé par source
//           (auteur=nom d'auteur, date et source si précisée entre parenthèses)
// blocrezo: titrage pour slots ancien Rezo

function retitrage($titre='...', $extraire='tout', $mode='article', $date='', $titre_rub='')
{
  // Ne jamais prendre la source de référence pour les affichages mono-sources
  if (($mode!="rubrique")&&($mode!="bloc")&&($mode!="blocrezo")) 
  	$auteur=ereg_replace(" ","&nbsp;",$titre_rub);
  
  // Calculer la date à afficher (heure, date JJ/MM ou JJ/MM/YY)
  if (substr($date,0,10)!=date("Y-m-d")) { $ladate=substr($date,8,2)."/".substr($date,5,2); }
  else { $ladate=ereg_replace(":","h",substr($date,11,5)); }
  if (substr($date,0,4)!=date("Y")) { $ladate.="/".substr($date,2,2); }

  // Extraire d'abord la source précisée entre parenthèses, s'il y en a une
  // Dans ce cas elle écrase la source de référence - vrai dans tous les modes
  if (ereg("(.*) \((.*)\)$",$titre,$regs) AND !ereg("^[0-9]+$",$regs[2]))
  {
    $titre=$regs[1];
    $auteur=ereg_replace(" ","&nbsp;",$regs[2]);
    $oversource=1;
  }

  // Extraire le nom d'auteur précisé par ", par", s'il y en a un
  // On l'ignore en mode "depeche" (moins de place) 
  if (strpos($titre,", par "))
  {
     if ($mode!="depeche")
       $par=substr($titre,strpos($titre,", par ")+2);
     if ($mode!="blocrezo")
       $titre=substr($titre,0,strpos($titre,", par "));
  }
  else if (strpos($titre,", by ")) // idem en anglais
  {
     if ($mode!="depeche")
       $par=substr($titre,strpos($titre,", by ")+2);
     if ($mode!="blocrezo")
       $titre=substr($titre,0,strpos($titre,", by "));
  }

  // Vrai auteur puis source vraie ou précisée
  if ($mode=="article")
    $auteur=trim($par.(($auteur) ? " (".$auteur.")" : ""));

  // Vrai auteur puis source si précisée
  if ($mode=="rubrique")
    $auteur=trim($par.(($oversource) ? " (".$auteur.")" : ""));

  // Seulement la source, vraie ou précisée, en non-sécable
  if ($mode=="depeche")
    $auteur=ereg_replace(" ","&nbsp;",$auteur);
 
  // Vrai auteur, et entre parenthèses source si précisée et date
  if ($mode=="bloc")
    $auteur=trim($par." (".trim($auteur." ".$ladate).")");

  // Entre parenthèses source si précisée 
  if ($mode=="blocrezo")
        if ($auteur) $auteur="(".trim($auteur).")";

  $ret["titre"]=$titre;
  $ret["auteur"]=$auteur;
  $ret["date"]=$ladate;
  if ($extraire == 'tout')
    return $ret;
  else
    return $ret[$extraire];
}

// [(#TITRE|retitre)] ou [(#TITRE|retitre{auteur})]
function retitre($titre,$quoi='titre') {
	$s = retitrage($titre,$quoi);
	if ($quoi=='auteur') return ereg_replace("^(par|by) ", "", $s);
	return $s;
}

function href_rezo($url_site,$id_article,$cookie=true) {
	// $cookie=false pour ignorer le cookie (cas de distrib.html)
	global $_COOKIE;

	if($url_site) {
		$url = " href=\"$url_site\"";
		if ($GLOBALS['auteur_session']) 
		{ 
			$url.= " onclick=\"this.href=editeroupas($id_article);\""; 
		}
		else
		{
			if ($cookie AND $_COOKIE['rezoclick'])
			  $url.= " onclick=\"this.href='/$id_article';\"";
		}
	} else
		$url = " href='/$id_article' ";

	return $url;	
}

function gerer_cookie_rezo() {
	global $_COOKIE;

	// cookie 'rezoclick' pour tracking des clics sur les liens
	if (! $_COOKIE['rezoclick']) {
		// un an
		spip_setcookie('rezoclick', 'asimbonanga', time()+60*60*24*365, '/');
	}
}

function gerer_cookie_source() {
        global $_COOKIE;
// pas fini
	// cookie 'rezosource' pour le classement (note Fil : ????)
	if (! $_COOKIE['rezosource']) {
		// un an
		spip_setcookie('rezosource', 'asimbonanga', time()+60*60*24*365, '/');
	}
}

// virer les intros des articles mal scannés (x)
// ou des documents illisibles (.ram, .jpeg...) (doc)
function embellir_intro($texte) {
	return ereg_replace("^(x|doc)$", "", trim($texte));
}


// gere le </head><body> avec un truc special admin
function gerer_body($style='', $bodyplus='') {

	if ($GLOBALS['auteur_session'])
		$feuille = '<link rel="alternate stylesheet" href="/linkadmin.css" type="text/css" title="admin" />'."\n";

	$feuille .= '<link rel="stylesheet" href="/link.css" type="text/css" title="normal" />'."\n";

	if ($style)
		$feuille .= '<link rel="stylesheet" href="/'.$style.'.css" type="text/css" />'."\n";
		
	$feuille .= '<link rel="stylesheet" href="/mots.css" type="text/css" />'."\n";

?>
 	<script language="JavaScript" type="text/javascript">
		function MM_findObj(n, d) { //v4.0
			var p,i,x;
			if(!d) d=document; 
			if((p=n.indexOf("?"))>0&&parent.frames.length) {
				d=parent.frames[n.substring(p+1)].document; 
				n=n.substring(0,p);
			}
			if(!(x=d[n])&&d.all) x=d.all[n]; 
			for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
		  	for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
		  	if(!x && document.getElementById) x=document.getElementById(n); return x;
		}
		
		function changestyle(couche, style) {
			if (!(layer = MM_findObj(couche))) return;
			layer.style.visibility = style;
		}
		
		function changeclass(objet, myClass)
		{ 
		  objet.className = myClass;
		}
	</script>
<?


	if (!$GLOBALS['auteur_session']) echo $feuille .

'<script type="text/javascript"><!--
// --></script>
</head>
<body '.$bodyplus.'>
';

	else echo $feuille .

'<script type="text/javascript"><!--
var admin=false;
function setActiveStyleSheet(title) {
   var i, a, main;
   for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
     if(a.getAttribute("rel").indexOf("style") != -1
        && a.getAttribute("title")) {
       a.disabled = true;
       if(a.getAttribute("title") == title) a.disabled = false;
     }
   }
}
function switchadmin() {
	if (admin) {
		admin = false;
		setActiveStyleSheet("normal");
	} else {
		admin = true;
		setActiveStyleSheet("admin");
	}
}
function editeroupas(id) {
  if (admin) { return "/ecrire/rezo_edit.php3?id_article="+id; }
  else { return "/"+id; }
}
// --></script>
</head>
<body ondblclick="switchadmin(); return false;" '.$bodyplus.'>
';

}


## recopie la fonction normale d'introduction, pour rezosearch.php3
function my_intro ($texte, $chapo) {
	return PtoBR(propre(supprimer_tags(couper_intro($chapo."\n\n\n".$texte, 500))));
}

## retourne la date de comparaison au format MySQL
## a utiliser comme critere {date>(#REM|limite_age{850})}
function limite_age($maintenant, $jours) {
	if ($maintenant)
		$time = strtotime($maintenant);
	else
		$time = time();
	return date('Y-m-d', $time-$jours*24*3600);
}

## cd filtre prend une date MySQL et regarde si elle est plus vieille que n
## jours ; a utiliser comme [(#DATE|test_age{850}|?{si VIEUX, si RECENT})]
function test_age($date, $jours) {
	return $date < limite_age('', $jours);
}

?>
