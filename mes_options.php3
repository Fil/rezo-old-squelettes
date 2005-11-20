<?php
	# $mysql_debug = true;
	# $mysql_profile = true;
	# $bouton_admin_debug = true;

	# supprimer la limite de taille sur les logos
	define('_LOGO_MAX_WIDTH', 50000);
	define('_LOGO_MAX_HEIGHT', 50000);
	define('_LOGO_MAX_SIZE', 1000);




$index_dico = array(
		"hash"	=> "bigint(30) NOT NULL",
		"dico"	=> "varchar(30) NOT NULL");

$index_dico_key = array(
		"PRIMARY KEY"	=> "dico",
		"KEY dico"	=> "dico");

$tables_principales['index_dico'] =
	array('field' => &$index_dico, 'key' => &$index_dico_key);



$index_articles = array(
		"hash"	=> "bigint(30) NOT NULL",
		"points" => "varchar(30) NOT NULL",
		"id_article" => "varchar(30) NOT NULL");

$index_articles_key = array(
		"PRIMARY KEY"	=> "id_article",
		"KEY id_article"	=> "id_article",
		"KEY id_article"	=> "hash");

$tables_principales['index_articles'] =
	array('field' => &$index_articles, 'key' => &$index_articles_key);




function boucle_INDEX_DICO_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_index_dico AS $id_table";
	return calculer_boucle($id_boucle, $boucles); 
}

function boucle_INDEX_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$boucle->from[] =  "spip_index_articles AS $id_table";
	return calculer_boucle($id_boucle, $boucles); 
}

function balise_POINTS_INDEX_dist ($p) {
	$p->code = champ_sql('points', $p);
	$p->statut = 'html';
	return $p;
}




?>