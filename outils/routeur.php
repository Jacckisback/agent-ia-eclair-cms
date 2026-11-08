<?php
/**
 * Routeur du serveur PHP intégré, utilisé seulement en local pendant la construction du site.
 * Reproduit l'essentiel du .htaccess d'Éclair : dossiers privés interdits, fichiers servis, le reste passe par index.php.
 *   php -S 127.0.0.1:8765 -t <dossier-du-site> outils/routeur.php
 */
$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(app|data|cache)(/|$)#i', $path) || preg_match('#^/site/(themes|extensions)(/|$)#i', $path)
    || preg_match('#(^|/)\.(?!well-known/)#', $path)
    || (preg_match('#\.(php\d?|phtml|phar|inc)$#i', $path) && $path !== '/index.php')) {
    http_response_code(404);
    exit('Not found');
}
if ($path !== '/' && is_file($root . $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
require $root . '/index.php';
