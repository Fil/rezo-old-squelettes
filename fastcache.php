<?php

# un script pour passer une page en mod gzip si possible, le plus vite possible

// ne pas utiliser ce systeme si on a les boutons d'admin ou mode POST...
if ($_SERVER['REQUEST_METHOD']<>'GET' OR $_GET['var_mode']) {
  include('inc-public.php3');
  exit;
}

header('Vary: Accept-Encoding');
header('Content-Type: text/html; charset=iso-8859-1');

// on va stocker le fichier cache dans IMG/cache_fast/
// pour pouvoir l'adresser directement depuis apache si besoin
// (mais alors il faut lui donner un nom + explicite)
$file = 'IMG/cache_fast/'.substr(md5(
    $_SERVER['REQUEST_URI'] . serialize($_COOKIE)
  ),0,10).'.html';

// il manque la gestion des cookies :(
// il manque la gestion fine du fast_cache


# si le fichier source est trop vieux, le recalculer
if (!@file_exists($file)
OR (time()-@filemtime($file) > 300)) {
  ob_start();
  include('inc-public.php3');
  $out = ob_get_clean();
  if (strlen($out)) {
    @mkdir('IMG/cache_fast');
    ecrire_fichier($file.'.tmp', $out);
    rename($file.'.tmp', $file);
    ecrire_fichier($file.'.tmp.gz', $out);
    rename($file.'.tmp.gz', $file.'.gz');
  }
}
  
# envoyer le resultat compresse ou non
if (strstr(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
  header("Content-Encoding: gzip");
  @readfile($file.'.gz');
} else {
  @readfile($file);
}

?>
