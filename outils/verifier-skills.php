<?php
/**
 * Vérifie un dépôt de skills Éclair téléchargé, avec la clé publique du mainteneur INTÉGRÉE ICI
 * (et non avec un script venu du dépôt lui-même, qu'un dépôt piégé pourrait falsifier).
 *
 *   php outils/verifier-skills.php outils/.cache/eclair-skills
 *
 * Contrôles : signature Ed25519 de SIGNATURE.json, dépôt nommé dans la charge signée, empreinte de SHA256SUMS.json,
 * empreinte de chaque fichier listé, et aucun fichier non listé (hors .git). Code de sortie 0 seulement si tout est bon.
 */
declare(strict_types=1);

const CLE = 'H+pk0ph+U+csV7aoGTUZp8hsWBrjvtxavotdKqJuYEs='; // même clé que les mises à jour d'Éclair CMS
const CONTEXTE = 'eclair-skills/release/1:';
const DEPOT = 'Jacckisback/eclair-skills';

function non(string $m): never { fwrite(STDERR, "✗ Non authentique : $m\n→ Ne pas utiliser ces skills.\n"); exit(1); }

$dir = rtrim($argv[1] ?? '', '/');
if ($dir === '' || !is_dir($dir)) { non('dossier introuvable'); }
if (!function_exists('sodium_crypto_sign_verify_detached')) { non('extension PHP sodium absente : vérification impossible'); }
$sig = json_decode((string) @file_get_contents("$dir/SIGNATURE.json"), true);
if (!is_array($sig) || !is_string($sig['payload'] ?? null) || !is_string($sig['signature'] ?? null)) { non('SIGNATURE.json absent ou illisible'); }
if (!sodium_crypto_sign_verify_detached((string) base64_decode($sig['signature'], true), CONTEXTE . $sig['payload'], (string) base64_decode(CLE, true))) {
    non('signature invalide');
}
$p = json_decode($sig['payload'], true);
if (!is_array($p) || ($p['repository'] ?? '') !== DEPOT || ($p['sums_file'] ?? '') !== 'SHA256SUMS.json') { non('la charge signée ne décrit pas ce dépôt'); }
if (!hash_equals((string) ($p['sums_sha256'] ?? ''), (string) @hash_file('sha256', "$dir/SHA256SUMS.json"))) { non('SHA256SUMS.json modifié'); }
$sums = json_decode((string) file_get_contents("$dir/SHA256SUMS.json"), true);
if (!is_array($sums)) { non('SHA256SUMS.json illisible'); }
foreach ($sums as $rel => $sha) {
    if (str_contains((string) $rel, '..') || !hash_equals((string) $sha, (string) @hash_file('sha256', "$dir/$rel"))) { non("fichier modifié ou absent : $rel"); }
}
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($dir) + 1);
    if (str_starts_with($rel, '.git/') || in_array($rel, ['SIGNATURE.json', 'SHA256SUMS.json'], true)) { continue; }
    if (!isset($sums[$rel])) { non("fichier ajouté non signé : $rel"); }
}
echo "✓ Authentique : Éclair Skills " . ($p['version'] ?? '?') . ", " . count($sums) . " fichiers vérifiés avec la clé intégrée.\n";
