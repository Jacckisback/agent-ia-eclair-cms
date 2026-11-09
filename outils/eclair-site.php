<?php
/**
 * Agent IA Éclair CMS — moteur de construction d'un site prêt à envoyer (nouveau site ou migration WordPress).
 *
 *   php outils/eclair-site.php simuler projet/brief.json   migration : simulation de l'import du paquet Eclair Export, rien n'est livré
 *   php outils/eclair-site.php migrer projet/brief.json    migration : import, réglages, contrôle des anciennes adresses, ZIP
 *
 *   php outils/eclair-site.php version                     dernière version publiée d'Éclair (annonce signée)
 *   php outils/eclair-site.php telecharger                 télécharge et vérifie la dernière version
 *   php outils/eclair-site.php construire projet/brief.json [--zip=archive-eclair.zip]
 *   php outils/eclair-site.php verifier sortie/<slug>/site
 *
 * « construire » enchaîne tout : dernière version vérifiée → installation locale (assistant officiel d'Éclair)
 * → réglages, direction artistique, images, pages, menus, mentions légales → contrôle qualité → ZIP prêt à envoyer.
 * Rien n'est envoyé sur Internet en dehors du téléchargement d'Éclair : le site est construit sur ton ordinateur.
 */
declare(strict_types=1);

const CANAL = 'https://raw.githubusercontent.com/Jacckisback/eclair-cms/main/dist/latest.json';
const CLE_PUBLIQUE = 'H+pk0ph+U+csV7aoGTUZp8hsWBrjvtxavotdKqJuYEs='; // clé de signature des versions d'Éclair (app/lib/Update.php)
const CONTEXTE = 'eclair-cms/annonce/2:';
// Empreintes SHA-256 des archives officielles connues à la date de ce pack (repli hors ligne pour --zip).
const VERSIONS_CONNUES = ['4ba43998712a357d8ffe6f29e9ade9396d56abe821cdb7e2d9da74444434a90f']; // 2.0.0
$SANS_VERIF = in_array('--sans-verif', $argv, true);

$PACK = dirname(__DIR__);
$CACHE = $PACK . '/outils/.cache';

function dire(string $m): void { fwrite(STDOUT, $m . "\n"); }
function echec(string $m, int $code = 1): never { fwrite(STDERR, "✗ " . $m . "\n"); exit($code); }

function http_get(string $url, int $timeout = 60): string
{
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'Agent-IA-Eclair-CMS']);
        $body = curl_exec($c);
        $status = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $err = curl_error($c);
        curl_close($c);
        if ($body === false || $status >= 400) {
            echec("Téléchargement impossible ($url) : " . ($err ?: "HTTP $status"));
        }
        return (string) $body;
    }
    $body = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: Agent-IA-Eclair-CMS\r\n"]]));
    if ($body === false) {
        echec("Téléchargement impossible ($url).");
    }
    return $body;
}

/** Annonce signée de la dernière version : signature Ed25519 vérifiée comme le fait Éclair lui-même. */
function annonce(): array
{
    $outer = json_decode(http_get(CANAL, 30), true);
    $v2 = is_array($outer) ? ($outer['v2'] ?? null) : null;
    if (!is_array($v2) || !is_string($v2['payload'] ?? null)) {
        echec("Annonce de version illisible.");
    }
    if (function_exists('sodium_crypto_sign_verify_detached')) {
        $ok = sodium_crypto_sign_verify_detached((string) base64_decode((string) $v2['signature']), CONTEXTE . $v2['payload'], (string) base64_decode(CLE_PUBLIQUE));
        if (!$ok) {
            echec("Signature de l'annonce invalide : téléchargement refusé.");
        }
    } else {
        dire("⚠ Extension sodium absente : signature non vérifiée (l'empreinte SHA-256 le sera).");
    }
    $r = json_decode($v2['payload'], true);
    if (!is_array($r) || !str_starts_with((string) $r['url'], 'https://raw.githubusercontent.com/Jacckisback/eclair-cms/')) {
        echec("Annonce inattendue.");
    }
    return $r;
}

/** Annonce signée, ou null si le réseau ne répond pas (sans arrêter le programme). */
function annonce_ou_null(): ?array
{
    $raw = function_exists('curl_init') ? (function () {
        $c = curl_init(CANAL);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'Agent-IA-Eclair-CMS']);
        $b = curl_exec($c);
        curl_close($c);
        return is_string($b) ? $b : null;
    })() : null;
    $o = $raw ? json_decode($raw, true) : null;
    $v2 = is_array($o) ? ($o['v2'] ?? null) : null;
    if (!is_array($v2) || !function_exists('sodium_crypto_sign_verify_detached')
        || !sodium_crypto_sign_verify_detached((string) base64_decode((string) $v2['signature']), CONTEXTE . $v2['payload'], (string) base64_decode(CLE_PUBLIQUE))) {
        return null;
    }
    $r = json_decode($v2['payload'], true);
    return is_array($r) ? $r : null;
}

function telecharger(string $cache): string
{
    $r = annonce();
    @mkdir($cache, 0755, true);
    $file = $cache . '/eclair-cms-' . $r['version'] . '.zip';
    if (!is_file($file) || hash_file('sha256', $file) !== $r['sha256']) {
        dire("⬇ Téléchargement d'Éclair CMS " . $r['version'] . "…");
        $bytes = http_get($r['url'], 180);
        if (hash('sha256', $bytes) !== $r['sha256']) {
            echec("Empreinte SHA-256 différente de l'annonce signée : archive refusée.");
        }
        file_put_contents($file, $bytes);
    }
    dire("✓ Éclair CMS " . $r['version'] . " (" . $r['date'] . ") vérifié : " . $file);
    return $file;
}

function rmrf(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

function dezipper(string $zip, string $dest): void
{
    $tmp = $dest . '.tmp';
    rmrf($tmp);
    $z = new ZipArchive();
    if ($z->open($zip) !== true) {
        echec("Archive illisible : $zip");
    }
    $z->extractTo($tmp);
    $z->close();
    $root = is_file($tmp . '/eclair/index.php') ? $tmp . '/eclair' : (is_file($tmp . '/index.php') ? $tmp : null);
    if ($root === null) {
        echec("Archive sans dossier « eclair » : ce n'est pas une archive d'Éclair CMS.");
    }
    rmrf($dest);
    rename($root, $dest);
    rmrf($tmp);
}

function port_libre(): int
{
    $s = stream_socket_server('tcp://127.0.0.1:0');
    $name = stream_socket_get_name($s, false);
    fclose($s);
    return (int) substr((string) strrchr((string) $name, ':'), 1);
}

/** Serveur PHP local le temps d'une étape. */
function serveur(string $root, string $routeur): array
{
    $port = port_libre();
    $cmd = [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $root, $routeur];
    $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes);
    for ($i = 0; $i < 50; $i++) {
        usleep(100000);
        if (@fsockopen('127.0.0.1', $port)) {
            break;
        }
    }
    return [$proc, "http://127.0.0.1:$port"];
}

final class Client
{
    private array $cookies = [];
    public function __construct(public string $base, private bool $https = false) {}

    public function req(string $method, string $path, array $data = [], array $headers = []): array
    {
        $c = curl_init($this->base . $path);
        $h = $headers;
        if ($this->https) {
            $h[] = 'X-Forwarded-Proto: https'; // le site livré force HTTPS : on le simule en local
        }
        if ($this->cookies) {
            $h[] = 'Cookie: ' . implode('; ', array_map(fn ($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies));
        }
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 120, CURLOPT_HTTPHEADER => $h,
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*']);
        if ($method === 'POST') {
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $raw = (string) curl_exec($c);
        $status = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $hs = (int) curl_getinfo($c, CURLINFO_HEADER_SIZE);
        curl_close($c);
        $head = substr($raw, 0, $hs);
        foreach (preg_split('/\r?\n/', $head) as $line) {
            if (preg_match('/^Set-Cookie:\s*([^=;]+)=([^;]*)/i', $line, $m)) {
                $this->cookies[$m[1]] = $m[2];
            }
        }
        return ['status' => $status, 'head' => $head, 'body' => substr($raw, $hs)];
    }
}

function mot_de_passe(): string
{
    $syl = ['ka', 'lo', 'mi', 'ru', 'te', 'va', 'zo', 'ni', 'pe', 'du', 'fa', 'gi', 'so', 'be', 'xo', 'ly'];
    $part = static fn () => ucfirst($syl[random_int(0, 15)] . $syl[random_int(0, 15)] . $syl[random_int(0, 15)]);
    return $part() . '-' . $part() . '-' . $part() . '-' . random_int(10, 99) . ['!', '?', '#', '%'][random_int(0, 3)];
}

function slug(string $s): string
{
    $s = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s), '-'));
    return $s !== '' ? substr($s, 0, 50) : 'site';
}

/* ───────────────────────── Étape « appliquer » (processus séparé, Éclair chargé) ───────────────────────── */

/** Charge Éclair dans ce processus (sans serveur web), pour agir sur ses données comme le fait l'administration. */
function charger(string $root): void
{
    $index = (string) file_get_contents($root . '/index.php');
    preg_match("/define\('ECLAIR_VERSION', '([^']+)'\)/", $index, $m);
    define('ECLAIR', true);
    define('ECLAIR_VERSION', $m[1] ?? '0');
    define('ECLAIR_ROOT', $root);
    define('ECLAIR_DATA', $root . '/data');
    define('ECLAIR_DEBUG', true);
    $_SERVER += ['HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET', 'SERVER_PORT' => '80'];
    spl_autoload_register(static function (string $class) use ($root): void {
        if (strncmp($class, 'Eclair\\', 7) === 0) {
            $f = $root . '/app/lib/' . str_replace('\\', '/', substr($class, 7)) . '.php';
            if (is_file($f)) {
                require $f;
            }
        }
    });
    require $root . '/app/lib/helpers.php';
}

/** Migration, avant l'import : contenus de démonstration retirés, fonction « Import WordPress » ajoutée. */
function migrer_preparer(string $root): void
{
    charger($root);
    foreach (array_keys(Eclair\Content::index()) as $id) {
        Eclair\Content::delete((string) $id);
    }
    foreach (Eclair\Categories::all() as $cat) {
        Eclair\Categories::delete($cat['id']);
    }
    Eclair\Menus::save('main', []);
    Eclair\Menus::save('footer', []);
    Eclair\Settings::save(['home' => '']);
    $r = Eclair\Extensions::add('wordpress');
    echo json_encode(['ok' => (bool) ($r['ok'] ?? false), 'error' => $r['error'] ?? ''], JSON_UNESCAPED_UNICODE);
}

function appliquer(string $root, string $briefFile, string $packDir, string $mode = 'site', string $sourceUrl = ''): void
{
    charger($root);
    $mig = $mode === 'migration';
    $bloquants = [];
    $b = json_decode((string) file_get_contents($briefFile), true);
    $site = $b['site'] ?? [];
    if ($mig && trim((string) ($b['domaine']['url'] ?? '')) === '') {
        $b['domaine']['url'] = $sourceUrl; // même adresse que l'ancien site WordPress, sauf choix contraire
    }
    $user = array_values(Eclair\Users::all())[0] ?? echec("Aucun compte après l'installation.");
    $journal = [];

    // 1. Contenus de démonstration retirés : le site ne garde que ce qui vient du brief (en migration : déjà fait avant l'import).
    if (!$mig) {
        foreach (array_keys(Eclair\Content::index()) as $id) {
            Eclair\Content::delete((string) $id);
        }
        foreach (Eclair\Categories::all() as $cat) {
            Eclair\Categories::delete($cat['id']);
        }
    }

    // 2. Images : fichiers locaux (générés ou fournis) importés dans la médiathèque, en WebP si possible.
    $medias = [];
    foreach ($b['images'] ?? [] as $img) {
        $file = (string) ($img['fichier'] ?? '');
        $path = str_starts_with($file, '/') ? $file : $packDir . '/' . $file;
        if ($file === '' || !is_file($path)) {
            $journal[] = "⚠ image « {$img['cle']} » absente ($file) : section gardée sans image";
            continue;
        }
        $media = Eclair\Media::import((string) file_get_contents($path), basename($path), $user['id']);
        $media = Eclair\Media::update($media['id'], ['alt' => (string) ($img['alt'] ?? '')]) ?? $media;
        $medias[$img['cle']] = $media['id'];
        $journal[] = "✓ image « {$img['cle']} » importée";
    }
    $ref = static function (mixed $v) use (&$ref, $medias): mixed {
        if (is_array($v)) {
            return array_map($ref, $v);
        }
        if (is_string($v) && str_starts_with($v, '@img:')) {
            return $medias[substr($v, 5)] ?? '';
        }
        return $v;
    };

    // Réglages de base avant les contenus : langue, adresse du blog et du site (les adresses et l'analyse SEO en dépendent).
    $lang0 = (string) ($site['langue'] ?? 'fr');
    Eclair\Settings::save($mig ? ['url' => rtrim((string) ($b['domaine']['url'] ?? ''), '/')] : ['lang' => $lang0, 'url' => rtrim((string) ($b['domaine']['url'] ?? ''), '/'),
        'blog' => ['enabled' => (bool) ($b['blog']['actif'] ?? false), 'base' => $b['blog']['adresse'] ?? 'blog']]);
    Eclair\Settings::forget();
    // Profil d'auteur (page auteur, données Person pour Google et les IA).
    Eclair\Users::update($user['id'], static fn (array $u): array => array_merge($u, array_filter([
        'job' => (string) ($b['admin']['fonction'] ?? ''),
        'bio' => (string) ($b['admin']['bio'] ?? ''),
    ], static fn ($v) => $v !== '')));

    // 3. Catégories du blog.
    $cats = [];
    foreach ($b['categories'] ?? [] as $c) {
        $saved = Eclair\Categories::save(['name' => $c['nom'], 'description' => $c['description'] ?? '', 'kind' => ($c['contient'] ?? 'articles') === 'produits' ? 'product' : 'post']);
        $cats[$c['cle']] = $saved['id'];
    }

    // 4. Pages et articles (publiés), dans l'ordre : les liens « @page:cle » sont résolus après coup.
    $ids = [];
    $paths = [];
    $analyse = [];
    $lang = (string) ($site['langue'] ?? 'fr');
    foreach (['pages' => 'page', 'articles' => 'post', 'produits' => 'product'] as $key => $type) {
        foreach ($b[$key] ?? [] as $p) {
            $data = [
                'type' => $type,
                'status' => ($p['brouillon'] ?? false) ? 'draft' : 'published',
                'lang' => $lang,
                'title' => $p['titre'],
                'slug' => $p['slug'] ?? '',
                'summary' => $p['resume'] ?? '',
                'image' => $ref($p['image'] ?? ''),
                'category' => $cats[$p['categorie'] ?? ''] ?? '',
                'seo' => $p['seo'] ?? [],
                'options' => $p['options'] ?? [],
                'blocks' => $ref($p['blocs'] ?? []),
                'author' => $user['id'],
            ];
            if ($mig && !empty($p['remplace'])) {
                // Page importée de WordPress réécrite (mentions légales complétées…) : même contenu, même adresse.
                $target = substr((string) $p['remplace'], 8);
                $found = Eclair\Content::byRoute($target) ?? Eclair\Content::findByPath(rtrim($target, '/'));
                if ($found) {
                    $old = Eclair\Content::get($found['id']);
                    $data['id'] = $found['id'];
                    $data['slug'] = $old['slug'];
                    $data['route'] = $old['route'] ?? '';
                    $data['lang'] = $old['lang'];        // une page anglaise réécrite reste anglaise
                    $data['tgroup'] = $old['tgroup'] ?? ''; // et reste reliée à ses traductions
                    foreach (['title', 'description', 'keyword'] as $k) {
                        if (($data['seo'][$k] ?? '') === '' && ($old['seo'][$k] ?? '') !== '') {
                            $data['seo'][$k] = $old['seo'][$k];
                        }
                    }
                } else {
                    $bloquants[] = "« {$p['titre']} » : page à remplacer introuvable ({$p['remplace']}) — l'ancienne resterait en ligne";
                    continue;
                }
            }
            if ($type === 'product') {
                $data['product'] = $ref($p['produit'] ?? []); // prix, prix barré, lien de paiement, galerie (@img:…), variantes
            }
            $saved = Eclair\Content::save($data, $user);
            $ids[$p['cle']] = $saved['id'];
            foreach ($saved['blocks'] as $blk) {
                foreach ($blk['type'] === 'features' ? $blk['items'] : [] as $it) {
                    if ($it['icon'] !== '' && Eclair\Design::icon($it['icon']) === '') {
                        $journal[] = "⚠ « {$p['titre']} » : l'icône {$it['icon']} n'existe pas dans Éclair (voir references/design.md), elle ne s'affichera pas";
                    }
                }
            }
            if (mb_strlen($p['titre']) > 22 && in_array($p['cle'], array_merge($b['menus']['principal'] ?? []), true)) {
                $journal[] = "⚠ « {$p['titre']} » est dans le menu : titre trop long pour le mobile (22 caractères max, le titre SEO peut rester long)";
            }
            if ($p['cle'] === ($b['accueil'] ?? '') && trim((string) ($p['resume'] ?? '')) !== '') {
                $journal[] = "⚠ accueil : le résumé « L'essentiel » s'affiche au-dessus de la bannière, à retirer";
            }
            $an = Eclair\Analyzer::run($saved);
            $analyse[$p['cle']] = ['titre' => $p['titre'], 'seo' => $an['seo'], 'geo' => $an['geo'], 'mots' => $an['words'],
                'a_ameliorer' => array_values(array_map(fn ($c) => "[{$c['group']}] {$c['label']}", array_filter($an['checks'], fn ($c) => $c['status'] !== 'good' && in_array($c['group'], ['seo', 'geo'], true))))];
            $paths[$p['cle']] = Eclair\Content::path(Eclair\Content::entry($saved));
            if (isset($p['langue'])) {
                $saved = Eclair\Content::save(['id' => $saved['id'], 'lang' => (string) $p['langue']] + $saved, $user);
            }
            if ($p['cle'] === ($b['accueil'] ?? '')) {
                $paths[$p['cle']] = '/';
            }
            $journal[] = "✓ " . (['page' => 'page', 'post' => 'article', 'product' => 'produit'][$type]) . " « {$p['titre']} » → " . $paths[$p['cle']]
                . " (SEO {$saved['score']['seo']}, GEO {$saved['score']['geo']})";
        }
    }
    // Liens internes « @page:cle » dans les boutons et bannières.
    foreach (array_merge($b['pages'] ?? [], $b['articles'] ?? [], $b['produits'] ?? []) as $p) {
        $json = json_encode($p['blocs'] ?? [], JSON_UNESCAPED_UNICODE);
        if (!str_contains((string) $json, '@page:')) {
            continue;
        }
        $item = Eclair\Content::get($ids[$p['cle']]);
        $fix = static function (mixed $v) use (&$fix, $paths): mixed {
            if (is_array($v)) {
                return array_map($fix, $v);
            }
            return is_string($v) && str_starts_with($v, '@page:') ? ($paths[substr($v, 6)] ?? '/') : $v;
        };
        $item['blocks'] = $ref($fix($p['blocs']));
        Eclair\Content::save(['id' => $item['id']] + $item, $user);
    }

    // 5. Menus.
    $menu = static function (array $keys) use ($ids): array {
        $out = [];
        foreach ($keys as $k) {
            if ($k === '@blog') {
                $out[] = ['type' => 'blog'];
            } elseif ($k === '@boutique') {
                $out[] = ['type' => 'shop'];
            } elseif (isset($ids[$k])) {
                $out[] = ['type' => 'content', 'id' => $ids[$k]];
            } elseif (str_starts_with((string) $k, 'adresse:')) {
                // Contenu importé de WordPress, désigné par son adresse (ex. « adresse:/mentions-legales/ »).
                $found = Eclair\Content::byRoute(substr((string) $k, 8)) ?? Eclair\Content::findByPath(rtrim(substr((string) $k, 8), '/'));
                if ($found) {
                    $out[] = ['type' => 'content', 'id' => $found['id']];
                } else {
                    $GLOBALS['MENU_INTROUVABLES'][] = (string) $k;
                }
            }
        }
        return $out;
    };
    // En migration, les menus importés sont gardés ; une liste donnée dans le brief les remplace, « +cle » ajoute une entrée.
    foreach (['principal' => 'main', 'pied' => 'footer'] as $k => $name) {
        $keys = $b['menus'][$k] ?? [];
        if ($mig && !$keys) {
            continue;
        }
        $add = array_values(array_filter($keys, static fn ($x) => str_starts_with((string) $x, '+')));
        if ($mig && $add && count($add) === count($keys)) {
            Eclair\Menus::save($name, array_merge(Eclair\Menus::raw($name), $menu(array_map(static fn ($x) => substr($x, 1), $add))));
        } else {
            Eclair\Menus::save($name, $menu($keys));
        }
    }

    foreach ($GLOBALS['MENU_INTROUVABLES'] ?? [] as $k) {
        $bloquants[] = "entrée de menu introuvable : $k";
    }
    if (!Eclair\Menus::raw('footer')) {
        $bloquants[] = "pied de page vide : relie les mentions légales et la confidentialité (menus.pied, ex. [\"adresse:/mentions-legales/\", \"adresse:/politique-de-confidentialite/\"] ou les clés des pages du brief)";
    }

    // 4 bis. Modifications de contenus existants (importés) : dépublier, ajouter des blocs, lien de paiement d'un produit.
    foreach ($b['modifications'] ?? [] as $mod) {
        $addr = (string) ($mod['adresse'] ?? '');
        $found = Eclair\Content::byRoute($addr) ?? Eclair\Content::findByPath(rtrim($addr, '/') ?: '/');
        if (!$found && rtrim($addr, '/') === '' && Eclair\Settings::get('home')) {
            $found = ['id' => (string) Eclair\Settings::get('home')];
        }
        if (!$found) {
            $bloquants[] = "modification impossible, contenu introuvable : $addr";
            continue;
        }
        $item = Eclair\Content::get($found['id']);
        if (($mod['statut'] ?? '') === 'brouillon') {
            $item['status'] = 'draft';
        }
        if (!empty($mod['ajouter_blocs'])) {
            $item['blocks'] = array_merge($item['blocks'], $ref($mod['ajouter_blocs']));
        }
        if (!empty($mod['lien_paiement']) && $item['type'] === 'product') {
            $item['product']['link'] = (string) $mod['lien_paiement'];
        }
        Eclair\Content::save(['id' => $item['id']] + $item, $user);
        $journal[] = "✓ modifié : $addr" . (($mod['statut'] ?? '') === 'brouillon' ? ' (dépublié)' : '');
    }

    // 5 bis. Codes courts WordPress restés en texte (formulaires…) : remplacés par un bloc Éclair, ou retirés.
    $map = $b['remplacer_codes_courts'] ?? [];
    if ($map) {
        $done = 0;
        foreach (array_keys(Eclair\Content::index()) as $cid) {
            $item = Eclair\Content::get((string) $cid);
            if (!$item) {
                continue;
            }
            $changed = false;
            $blocks = [];
            foreach ($item['blocks'] as $blk) {
                $json = json_encode($blk, JSON_UNESCAPED_UNICODE);
                foreach ($map as $code => $with) {
                    $re = '~\[' . preg_quote((string) $code, '~') . '\b[^\]]*\](?:.*?\[/' . preg_quote((string) $code, '~') . '\])?~su';
                    if (!preg_match($re, (string) $json)) {
                        continue;
                    }
                    $changed = true;
                    $text = trim(strip_tags((string) preg_replace($re, '', (string) ($blk['text'] ?? ''))));
                    if ($with === 'contact') {
                        if ($text !== '' && in_array($blk['type'], ['paragraph', 'heading'], true)) {
                            $blk['text'] = (string) preg_replace($re, '', (string) $blk['text']);
                            $blocks[] = $blk;
                        }
                        $blk = ['type' => 'contact', 'title' => '', 'button' => ''];
                    } elseif ($with === 'retirer') {
                        if ($text === '') {
                            continue 2;
                        }
                        $blk['text'] = (string) preg_replace($re, '', (string) $blk['text']);
                    }
                }
                $blocks[] = $blk;
            }
            if ($changed) {
                $item['blocks'] = $blocks;
                Eclair\Content::save(['id' => $item['id']] + $item, $user);
                $done++;
            }
        }
        $journal[] = "✓ codes courts remplacés dans $done contenu(s)";
    }

    // 5 ter. Redirections ajoutées à la main (anciennes adresses restées en 404).
    $pairs = [];
    foreach ($b['redirections'] ?? [] as $r) {
        $pairs[] = [(string) ($r['de'] ?? ''), (string) (str_starts_with((string) ($r['vers'] ?? ''), '@page:') ? ($paths[substr((string) $r['vers'], 6)] ?? '') : ($r['vers'] ?? ''))];
    }
    foreach ($pairs as [$from]) {
        if (Eclair\Content::byRoute($from) ?? Eclair\Content::findByPath(rtrim($from, '/') ?: '/')) {
            $bloquants[] = "redirection sans effet : $from est encore une page en ligne (dépublie-la avec « modifications »)";
        }
    }
    if ($pairs) {
        $n = Eclair\Redirects::addMany($pairs, false, 301, true);
        $journal[] = "✓ $n redirection(s) ajoutée(s)";
    }

    // 6. Réglages : identité, SEO, GEO, sécurité, apparence.
    $id = $b['identite'] ?? [];
    $url = rtrim((string) ($b['domaine']['url'] ?? ''), '/');
    $https = str_starts_with($url, 'https://') && (($b['domaine']['ssl'] ?? 'inconnu') === 'oui');
    $direction = null;
    $dir = $b['design']['direction'] ?? null;
    if (is_string($dir) && $dir !== '') {
        $direction = Eclair\Design::normalize(Eclair\Design::DIRECTIONS[$dir] ?? null);
        if ($direction) {
            $direction['id'] = $dir;
        }
    } elseif (is_array($dir)) {
        $direction = Eclair\Design::normalize($dir);
    }
    $logo = $medias[$b['design']['logo'] ?? ''] ?? '';
    $patch = [
        'name' => (string) ($site['nom'] ?? ''),
        'tagline' => $site['slogan'] ?? '',
        'description' => $site['description'] ?? ($site['slogan'] ?? ''),
        'url' => $url,
        'lang' => $lang,
        'timezone' => $site['fuseau'] ?? (['fr' => 'Europe/Paris', 'es' => 'Europe/Madrid', 'en' => 'Europe/London', 'de' => 'Europe/Berlin', 'it' => 'Europe/Rome', 'pt' => 'Europe/Lisbon'][$lang] ?? 'UTC'),
        'home' => $ids[$b['accueil'] ?? ''] ?? '',
        'blog' => ['enabled' => (bool) ($b['blog']['actif'] ?? false), 'title' => $b['blog']['titre'] ?? 'Blog', 'description' => $b['blog']['description'] ?? '',
            'base' => $b['blog']['adresse'] ?? 'blog'],
        'seo' => array_filter([
            'index' => (bool) ($b['seo']['indexer'] ?? true),
            'title_format' => $b['seo']['format_titre'] ?? '{title} · {site}',
            'image' => $medias[$b['seo']['image_partage'] ?? ''] ?? '',
            'entity' => $id['type'] ?? 'Organization',
            'entity_name' => $id['nom_legal'] ?? ($site['nom'] ?? ''),
            'phone' => $id['telephone'] ?? '',
            'email' => $id['email'] ?? '',
            'street' => $id['adresse'] ?? '',
            'city' => $id['ville'] ?? '',
            'zip' => $id['code_postal'] ?? '',
            'region' => $id['region'] ?? '',
            'country' => $id['pays'] ?? '',
            'hours' => $id['horaires'] ?? '',
            'price_range' => $id['gamme_prix'] ?? '',
        ], static fn ($v) => $v !== null),
        'social' => $b['reseaux'] ?? [],
        'geo' => ['ai' => $b['seo']['robots_ia'] ?? 'open', 'about' => $b['seo']['a_propos_ia'] ?? ''],
        'contact' => ['email' => $id['email_formulaire'] ?? ($id['email'] ?? ''), 'thanks' => $b['contact']['merci'] ?? ''],
        'indexnow' => (bool) ($b['seo']['indexer'] ?? true) && $https,
        'security' => ['https' => $https, 'verified' => ''],
        'appearance' => array_filter([
            'logo' => $logo,
            'favicon' => $logo,
            'accent' => $direction['accent'] ?? ($b['design']['accent'] ?? null),
            'direction' => $direction ?? [],
            'motion' => true,
            'dark' => $b['design']['sombre'] ?? 'auto',
            'footer' => $b['design']['pied_de_page'] ?? '',
        ], static fn ($v) => $v !== null),
        'ai' => ['profile' => array_merge(['about' => '', 'audience' => '', 'tone' => '', 'values' => '', 'rules' => '', 'visual' => ''], $b['profil_editorial'] ?? [])],
        'onboarding' => ['done' => true],
    ];
    if ($mig) {
        // Ce que l'import a repris (nom, accueil, blog, catégories) n'est pas écrasé par des champs vides du brief.
        foreach (['name', 'tagline', 'description'] as $k) {
            if (trim((string) $patch[$k]) === '') {
                unset($patch[$k]);
            }
        }
        unset($patch['lang'], $patch['timezone']);
        if (!empty($site['fuseau'])) {
            $patch['timezone'] = $site['fuseau'];
        }
        if ($patch['home'] === '') {
            unset($patch['home']);
        }
        // Le blog importé (adresse, titre) n'est modifié que sur demande explicite.
        if (empty($b['blog']['remplacer_import'])) {
            unset($patch['blog']);
        }
        if (!empty($b['seo_verification'])) {
            $patch['seo']['google'] = (string) $b['seo_verification'];
        }
        if (empty($b['reseaux'])) {
            unset($patch['social']);
        }
        if ($patch['contact']['email'] === '') {
            unset($patch['contact']);
        }
        if (empty($b['profil_editorial'])) {
            unset($patch['ai']);
        }
        $patch['seo'] = array_filter($patch['seo'], static fn ($v) => $v !== '');
    }
    if (!empty($b['boutique']['active'])) {
        $bt = $b['boutique'];
        $patch['shop'] = array_filter([
            'enabled' => true,
            'title' => $bt['titre'] ?? null,
            'description' => $bt['description'] ?? null,
            'currency' => $bt['devise'] ?? 'EUR',
            'shipping' => $bt['livraison'] ?? null,
            'returns' => $bt['retours'] ?? null,
            'terms' => $ids[$bt['page_cgv'] ?? ''] ?? '',
            'legal' => $ids[$bt['page_mentions'] ?? ''] ?? '',
            'thanks' => $ids[$bt['page_merci'] ?? ''] ?? '',
        ], static fn ($v) => $v !== null);
    }
    Eclair\Settings::save($patch);
    if (!empty($b['css_personnalise'])) {
        Eclair\Theme::saveCustom((string) $b['css_personnalise']); // nettoyé par Éclair, rangé dans data/
    }
    Eclair\Settings::forget();
    try {
        Eclair\Media::icons($logo !== '' ? Eclair\Media::get($logo) : null, (string) Eclair\Settings::get('appearance.accent'));
    } catch (\Throwable $e) {
        $journal[] = "⚠ icônes du site : " . $e->getMessage();
    }
    if ($direction && !empty($direction['display'])) {
        try {
            Eclair\Design::fetchFont((string) $direction['display']);
        } catch (\Throwable) {
            $journal[] = "⚠ police de titre non téléchargée : Éclair la récupérera à la première visite";
        }
    }
    // Migration : anciennes adresses des fichiers (/wp-content/uploads/…, miniatures comprises) redirigées vers les médias d'Éclair.
    if ($mig && is_file($root . '/../.media-index.json')) {
        $index = json_decode((string) file_get_contents($root . '/../.media-index.json'), true) ?: [];
        $eclair = array_values(Eclair\Media::all());
        $docs = [];
        foreach (glob($root . '/media/files/*/*/*') ?: [] as $f) {
            $docs[] = substr($f, strlen($root));
        }
        $pairs = [];
        $manque = 0;
        foreach ($index as $m) {
            $base = basename((string) ($m['file'] ?? parse_url((string) ($m['url'] ?? ''), PHP_URL_PATH)));
            $target = null;
            if (str_starts_with((string) ($m['mime'] ?? ''), 'image/')) {
                $c = array_values(array_filter($eclair, static fn ($e) => strcasecmp((string) $e['name'], $base) === 0));
                if (count($c) > 1) {
                    $c = array_values(array_filter($c, static fn ($e) => (int) $e['w'] === (int) ($m['width'] ?? 0) || (int) $e['h'] === (int) ($m['height'] ?? 0)));
                }
                $target = count($c) === 1 ? Eclair\Media::url($c[0]) : null;
            } else {
                $stem = slugify(pathinfo($base, PATHINFO_FILENAME));
                $c = array_values(array_filter($docs, static fn ($d) => str_starts_with(slugify(pathinfo($d, PATHINFO_FILENAME)), $stem)));
                $target = count($c) === 1 ? $c[0] : null;
            }
            if ($target === null) {
                $manque++;
                continue;
            }
            foreach (array_merge([(string) $m['url']], (array) ($m['variants'] ?? [])) as $u) {
                $path = (string) parse_url((string) $u, PHP_URL_PATH);
                if ($path !== '' && $path !== $target) {
                    $pairs[] = [$path, $target];
                }
            }
        }
        $n = $pairs ? Eclair\Redirects::addMany($pairs, true, 301, true) : 0;
        $journal[] = "✓ anciennes adresses de fichiers (/wp-content/uploads/…) : $n redirection(s)" . ($manque ? ", $manque fichier(s) sans correspondance" : '');
    }
    Eclair\Cache::purge();
    $journal[] = "✓ réglages : identité, SEO, GEO, apparence" . ($direction ? " (direction « " . ($direction['name'] ?: $direction['id']) . " »)" : '');
    echo json_encode(['journal' => $journal, 'chemins' => $paths, 'analyse' => $analyse, 'bloquants' => $bloquants], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ───────────────────────── Contrôle qualité ───────────────────────── */

function verifier(string $root, string $routeur): array
{
    [$proc, $base] = serveur($root, $routeur);
    $c = new Client($base, true);
    $rapport = ['pages' => [], 'fichiers' => [], 'problemes' => []];
    // Pages à contrôler : celles du plan du site (sitemap.xml) et tous les liens internes de l'accueil (menus, pied de page).
    $home = $c->req('GET', '/');
    $sitemap = $c->req('GET', '/sitemap.xml');
    preg_match_all('#<loc>([^<]+)</loc>#', $sitemap['body'], $locs);
    preg_match_all('#href="(/[^"?]*)"#', $home['body'], $links);
    $liste = ['/'];
    foreach (array_merge($locs[1], $links[1]) as $u) {
        $p = (string) (parse_url(html_entity_decode($u), PHP_URL_PATH) ?: '/');
        if (!preg_match('#\.(css|js|png|jpe?g|webp|avif|svg|ico|xml|txt|md|woff2?)$#i', $p) && !str_starts_with($p, '/media/') && !str_starts_with($p, '/assets/')) {
            $liste[] = $p;
        }
    }
    $liste = array_slice(array_values(array_unique($liste)), 0, 80); // 80 pages suffisent pour juger le gabarit
    foreach ($liste as $p) {
        $r = $c->req('GET', $p);
        $h = $r['body'];
        $info = [
            'adresse' => $p,
            'statut' => $r['status'],
            'poids_ko' => round(strlen($h) / 1024, 1),
            'viewport' => (bool) preg_match('/<meta name="viewport"[^>]*width=device-width/i', $h),
            'lang' => (bool) preg_match('/<html[^>]+lang="[a-z]{2}/i', $h),
            'h1' => preg_match_all('/<h1[\s>]/i', $h),
            'titre' => preg_match('#<title>([^<]+)</title>#', $h, $t) ? html_entity_decode($t[1]) : '',
            'description' => preg_match('/<meta name="description" content="([^"]+)"/', $h, $d) ? html_entity_decode($d[1]) : '',
            'canonical' => preg_match('/<link rel="canonical" href="([^"]+)"/', $h, $cn) ? $cn[1] : '',
            'jsonld' => substr_count($h, 'application/ld+json'),
            'a_completer' => substr_count($h, '[À compléter'),
            'codes_courts' => preg_match_all('~\[/?[a-z][a-z0-9_-]{2,}(?:\s+[a-z0-9_-]+=(?:"[^"]*"|\'[^\']*\'|[^\s\]]+))*\s*/?\]~', (string) preg_replace('~<(script|style|code|pre)\b.*?</\1>~si', '', $h), $sc) ? array_values(array_unique($sc[0])) : [],
            'images_sans_alt' => preg_match_all('/<img(?![^>]*\balt=)[^>]*>/i', $h),
        ];
        $rapport['pages'][] = $info;
        if ($info['statut'] !== 200) { $rapport['problemes'][] = "$p : statut {$info['statut']}"; }
        if (!$info['viewport']) { $rapport['problemes'][] = "$p : balise viewport mobile absente"; }
        if ($info['h1'] !== 1) { $rapport['problemes'][] = "$p : {$info['h1']} titre(s) H1 au lieu d'un seul"; }
        $noindex = (bool) preg_match('/<meta name="robots" content="[^"]*noindex/i', $h);
        if ($info['description'] === '' && !$noindex) { $rapport['problemes'][] = "$p : pas de meta description"; }
        if ($info['jsonld'] === 0) { $rapport['problemes'][] = "$p : pas de données structurées JSON-LD"; }
        if ($info['codes_courts']) { $rapport['problemes'][] = "$p : BLOQUANT — code(s) court(s) WordPress visible(s) : " . implode(' ', array_slice($info['codes_courts'], 0, 3)); }
        if ($info['a_completer'] > 0) { $rapport['problemes'][] = "$p : BLOQUANT — {$info['a_completer']} passage(s) « [À compléter] » visibles en ligne"; }
        if ($info['images_sans_alt'] > 0) { $rapport['problemes'][] = "$p : {$info['images_sans_alt']} image(s) sans texte alternatif"; }
        if (mb_strlen($info['titre']) > 65) { $rapport['problemes'][] = "$p : titre de " . mb_strlen($info['titre']) . " caractères (vise 65 max)"; }
    }
    foreach (['/sitemap.xml', '/robots.txt', '/llms.txt'] as $f) {
        $r = $c->req('GET', $f);
        $rapport['fichiers'][$f] = $r['status'];
        if ($r['status'] !== 200) {
            $rapport['problemes'][] = "$f : statut {$r['status']}";
        }
    }
    proc_terminate($proc);
    return $rapport;
}

/* ───────────────────────── Construction complète ───────────────────────── */

/** Manifeste et adresses publiques (urls.csv) du paquet Eclair Export : la partie qui contient manifest.json. */
function paquet_lire(array $parts): array
{
    foreach ($parts as $file) {
        $z = new ZipArchive();
        if ($z->open($file) === true && $z->locateName('manifest.json') !== false) {
            $m = json_decode((string) $z->getFromName('manifest.json'), true);
            $urls = [];
            $h = fopen('php://temp', 'r+');
            fwrite($h, (string) $z->getFromName('urls.csv'));
            rewind($h);
            fgetcsv($h, 0, ',', '"', '\\');
            while (($row = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
                if (isset($row[0]) && $row[0] !== '') {
                    $urls[] = ['url' => $row[0], 'kind' => $row[1] ?? '', 'ref' => $row[2] ?? ''];
                }
            }
            $report = json_decode((string) $z->getFromName('report.json'), true);
            // Contrôles AVANT toute installation : format, empreintes, parties.
            if (!is_array($m) || ($m['format'] ?? '') !== 'eclair-export' || !preg_match('~^1\.\d+$~', (string) ($m['format_version'] ?? ''))) {
                echec("Ce fichier n'est pas un export Eclair Export 1.x : mets l'extension à jour sur WordPress et refais l'export.");
            }
            foreach ((array) ($m['files'] ?? []) as $name => $sum) {
                $bytes = $z->getFromName((string) $name);
                if ($bytes === false || !hash_equals((string) ($sum['sha256'] ?? ''), hash('sha256', $bytes))) {
                    echec("Export abîmé ou incomplet (« $name ») : retélécharge les fichiers, ou refais l'export.");
                }
            }
            $have = array_map('basename', $parts);
            $missing = [];
            foreach ((array) ($m['parts'] ?? []) as $part) {
                $n = basename((string) ($part['file'] ?? ''));
                if ($n !== '' && !in_array($n, $have, true)) {
                    $missing[] = $n;
                }
            }
            if ($missing) {
                echec("Parties de l'export manquantes : " . implode(', ', $missing) . " (dépose-les toutes dans projet/migration/).");
            }
            $media = json_decode((string) $z->getFromName('media/index.json'), true);
            $seo = json_decode((string) $z->getFromName('seo.json'), true);
            $z->close();
            return ['manifest' => $m, 'urls' => $urls, 'report' => is_array($report) ? $report : [], 'media' => is_array($media) ? $media : [],
                'seo_global' => is_array($seo['global'] ?? null) ? $seo['global'] : []];
        }
    }
    echec("Aucune partie ne contient manifest.json : ajoute la première partie de l'export (le fichier sans « -part » dans son nom).");
}

/** Chaque ancienne adresse WordPress doit répondre 200, ou une seule redirection 301 vers une page qui répond 200. */
function verifier_anciennes(string $root, string $routeur, array $urls): array
{
    [$proc, $base] = serveur($root, $routeur);
    $c = new Client($base, true);
    $ok = 0;
    $ko = [];
    foreach ($urls as $u) {
        $parts = parse_url($u['url']);
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        $estAccueil = rtrim((string) ($parts['path'] ?? '/'), '/') === '' && !isset($parts['query']);
        $statuts = [];
        $cur = $path;
        $final = 0;
        for ($i = 0; $i < 3; $i++) { // 2 redirections au plus (ex. ajout du « / » final, puis 301 de migration)
            $r = $c->req('GET', $cur);
            $statuts[] = $r['status'];
            if (in_array($r['status'], [301, 302, 307, 308], true) && preg_match('/^Location:\\s*(\\S+)/mi', $r['head'], $loc)) {
                $to = parse_url(trim($loc[1]));
                $cur = ($to['path'] ?? '/') . (isset($to['query']) ? '?' . $to['query'] : '');
                continue;
            }
            $final = $r['status'];
            break;
        }
        $versAccueil = !$estAccueil && count($statuts) > 1 && rtrim(parse_url($cur, PHP_URL_PATH) ?: '/', '/') === '';
        if ($final === 200 && !$versAccueil) {
            $ok++;
            continue;
        }
        $ko[] = ['ancienne' => $path, 'statut' => implode(' → ', $statuts) . ($versAccueil ? ' (renvoie vers l’accueil : à rediriger vers la page équivalente)' : ''), 'type' => $u['kind']];
    }
    proc_terminate($proc);
    return ['total' => count($urls), 'ok' => $ok, 'erreurs' => $ko];
}

function construire(string $briefFile, ?string $zipLocal, string $pack, string $cache, string $mode = 'site'): void
{
    foreach (['curl', 'zip', 'mbstring', 'json', 'dom', 'zlib'] as $ext) {
        if (!extension_loaded($ext)) {
            echec("Extension PHP « $ext » absente sur ton ordinateur (voir DEPANNAGE.md).");
        }
    }
    $briefPath = realpath($briefFile) ?: echec("Brief introuvable : $briefFile");
    $b = json_decode((string) file_get_contents($briefPath), true) ?: echec("Brief illisible (JSON invalide) : $briefFile");
    $migration = $mode !== 'site';
    $briefMode = (string) ($b['mode'] ?? '');
    if (!$migration && $briefMode === 'migration') {
        echec("Ce brief est une migration WordPress : lance « simuler » puis « migrer », pas « construire ».");
    }
    if ($migration && $briefMode !== 'migration') {
        echec("« $mode » est réservé aux migrations WordPress : ce brief n'a pas \"mode\": \"migration\".");
    }
    $paquet = null;
    if ($migration) {
        $parts = [];
        foreach ($b['migration']['paquets'] ?? [] as $f) {
            $parts[] = (str_starts_with((string) $f, '/') ? $f : $pack . '/' . $f);
        }
        foreach ($parts as $f) {
            is_file($f) || echec("Partie de l'export introuvable : $f");
        }
        $parts || echec("Brief incomplet : migration.paquets vide (dépose les fichiers d'Eclair Export dans projet/migration/).");
        $paquet = paquet_lire($parts);
        $src = $paquet['manifest']['source'] ?? [];
        // Ce que le paquet sait déjà n'est pas redemandé.
        $b['site']['nom'] = ($b['site']['nom'] ?? '') !== '' ? $b['site']['nom'] : (string) ($src['name'] ?? 'Mon site');
        $b['site']['langue'] = substr((string) ($src['language'] ?? 'fr'), 0, 2); // toujours la langue du site WordPress
        $b['site']['fuseau'] = ($b['site']['fuseau'] ?? '') !== '' ? $b['site']['fuseau'] : (string) ($src['timezone'] ?? '');
        $verif = (string) ($paquet['seo_global']['verification']['google'] ?? '');
        if ($verif !== '' && empty($b['seo_verification'])) {
            $b['seo_verification'] = $verif; // balise Google Search Console de Yoast/Rank Math reprise
        }
        if (!empty($paquet['seo_global']['search_engines_discouraged'])) {
            dire("⚠ Le WordPress demandait aux moteurs de ne pas l'indexer : le site Éclair sera indexable (seo.indexer). Vérifie que c'est voulu.");
        }
        $b['site']['type'] = ($b['site']['type'] ?? '') !== '' ? $b['site']['type'] : 'blog';
        if (trim((string) ($b['domaine']['url'] ?? '')) === '') {
            $b['domaine']['url'] = (string) ($src['url'] ?? '');
        }
        if (!in_array($b['site']['langue'], ['fr', 'es', 'en', 'de', 'it', 'pt'], true)) {
            $b['site']['langue'] = 'en';
        }
        $briefPath = $pack . '/outils/.cache/brief-migration.json';
        @mkdir(dirname($briefPath), 0755, true);
        file_put_contents($briefPath, json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        dire("✓ Paquet Eclair Export lu : " . ($src['url'] ?? '?') . " (" . json_encode($paquet['manifest']['counts']['content'] ?? []) . ")");
    }
    foreach (['site.nom', 'site.langue', 'site.type', 'admin.nom', 'admin.email'] as $k) {
        [$a, $c] = explode('.', $k);
        if (empty($b[$a][$c])) {
            echec("Brief incomplet : champ « $k » manquant.");
        }
    }
    $slug = slug((string) (($b['slug'] ?? '') !== '' ? $b['slug'] : $b['site']['nom']));
    $out = $mode === 'simulation' ? $cache . '/simulation/' . $slug : $pack . '/sortie/' . $slug;
    $root = $out . '/site';
    @mkdir($out, 0755, true);
    // Un ZIP d'une construction précédente ne doit jamais être livré par erreur si celle-ci échoue (la simulation n'y touche pas).
    foreach ($mode === 'simulation' ? [] : (glob($pack . '/sortie/' . $slug . '-eclair-v*.zip') ?: []) as $old) {
        unlink($old);
    }
    $mode === 'simulation' || @unlink($pack . '/sortie/' . $slug . '-ACCES-ADMIN.txt');
    $routeur = $pack . '/outils/routeur.php';

    // 1. Dernière version d'Éclair, vérifiée.
    $zip = $zipLocal ?? telecharger($cache);
    if ($zipLocal !== null) {
        // Archive fournie à la main : comparée à l'annonce signée si le réseau répond, sinon refusée sauf --sans-verif.
        $r = @annonce_ou_null();
        $sha = hash_file('sha256', $zipLocal);
        if ($r !== null && $r['sha256'] === $sha) {
            dire("✓ Archive fournie identique à la version signée {$r['version']}");
        } elseif (in_array($sha, VERSIONS_CONNUES, true)) {
            dire("✓ Archive fournie reconnue (empreinte d'une version publiée d'Éclair)");
        } elseif (!$GLOBALS['SANS_VERIF']) {
            echec("Archive fournie inconnue : son empreinte ne correspond à aucune version publiée d'Éclair. Télécharge-la depuis https://github.com/Jacckisback/eclair-cms (dossier dist) ou ajoute --sans-verif en connaissance de cause.");
        } else {
            dire("⚠ Archive non vérifiée (--sans-verif)");
        }
    }
    dezipper($zip, $root);
    preg_match("/define\('ECLAIR_VERSION', '([^']+)'\)/", (string) file_get_contents($root . '/index.php'), $vm);
    $version = $vm[1] ?? '?';
    dire("✓ Éclair $version décompressé dans " . substr($root, strlen($pack) + 1));

    // 2. Installation par l'assistant officiel d'Éclair (mêmes contrôles qu'en ligne).
    $pass = (string) ($b['admin']['mot_de_passe'] ?? '') ?: mot_de_passe();
    $adminPath = (string) ($b['admin']['adresse'] ?? '') ?: 'gestion-' . bin2hex(random_bytes(3));
    [$proc, $base] = serveur($root, $routeur);
    $c = new Client($base);
    $page = $c->req('GET', '/');
    if (!preg_match('/name="_ft" value="([a-f0-9]{32})"/', $page['body'], $ft)) {
        proc_terminate($proc);
        echec("Assistant d'installation introuvable (statut {$page['status']}).");
    }
    $types = ['vitrine' => 'business', 'blog' => 'blog', 'freelance' => 'freelance', 'page-unique' => 'landing', 'media' => 'media', 'affiliation' => 'affiliate'];
    $r = $c->req('POST', '/', [
        '_ft' => $ft[1],
        'ui' => $b['site']['langue'],
        'site_name' => $b['site']['nom'],
        'site_tagline' => $b['site']['slogan'] ?? '',
        'site_lang' => $b['site']['langue'],
        'site_type' => $types[$b['site']['type']] ?? $b['site']['type'],
        'user_name' => $b['admin']['nom'],
        'user_email' => $b['admin']['email'],
        'user_pass' => $pass,
        'admin_path' => $adminPath,
        'index' => ($b['seo']['indexer'] ?? true) ? '1' : '',
        'ai' => $b['seo']['robots_ia'] ?? 'open',
    ], ['Accept: application/json', 'Origin: ' . $base, 'Sec-Fetch-Site: same-origin']);
    proc_terminate($proc);
    if (!str_contains($r['body'], '"ok":true')) {
        echec("Installation refusée : " . trim(strip_tags($r['body'])));
    }
    dire("✓ Installation faite (administration : /$adminPath)");

    // 2 bis. Migration : simulation puis import du paquet par l'importeur officiel d'Éclair (même code qu'en ligne).
    if ($migration) {
        $prep = json_decode((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' __migrer_preparer ' . escapeshellarg($root) . ' 2>&1'), true);
        if (!is_array($prep) || !$prep['ok']) {
            echec("Fonction « Import WordPress » non activée : " . ($prep['error'] ?? 'erreur inconnue'));
        }
        $cli = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/app/bundled/wordpress/lib/cli.php') . ' import ' . implode(' ', array_map('escapeshellarg', $parts));
        $opt = !empty($b['migration']['brouillons']) ? ' --drafts' : '';
        $opt .= isset($b['migration']['reprendre_menus']) && !$b['migration']['reprendre_menus'] ? ' --no-menus' : '';
        $resume = static function (array $r): array {
            $l = ["Contenus : {$r['posts']} articles, {$r['pages']} pages, {$r['products']} produits, {$r['custom']} autres ({$r['drafts']} en brouillon)",
                "Adresses : {$r['routes']} gardées à l'identique, {$r['moved']} changées et redirigées en 301, {$r['aliases']} anciens liens ?p=",
                "Médias : {$r['images']} images, {$r['documents']} documents, {$r['media_missing']} introuvables",
                "SEO repris sur {$r['seo']} pages ; {$r['redirects']} redirections ({$r['redirects_skipped']} écartées) ; {$r['links']} liens internes réécrits"];
            if (!empty($r['media_skipped'])) {
                $l[] = "! {$r['media_skipped']} média(s) non repris (trop lourds ou format refusé)";
            }
            if (!empty($r['protected'])) {
                $l[] = "! {$r['protected']} contenu(s) protégé(s) par mot de passe : importés en brouillon";
            }
            foreach ($r['not_migrated'] as [$what, $name, $why]) {
                $l[] = "! Non migré : $name — $why";
            }
            foreach ($r['errors'] as $e) {
                $l[] = "✗ $e";
            }
            return $l;
        };
        $raw = (string) shell_exec($cli . ' --dry-run --json' . $opt . ' 2>&1');
        $sim = json_decode((string) substr($raw, (int) strpos($raw, '{')), true);
        if (!is_array($sim) || !isset($sim['report'])) {
            echec("La simulation de l'import a échoué : " . trim($raw));
        }
        file_put_contents($out . '/SIMULATION.json', json_encode($sim, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        dire("✓ Simulation (rien n'est écrit) :");
        foreach ($resume($sim['report']) as $l) {
            dire("  " . $l);
        }
        if ($mode === 'simulation') {
            dire("ℹ Simulation seulement : rien n'est livré, sortie/ n'est pas touché. Montre ce bilan, puis lance « migrer ».");
            rmrf($out);
            return;
        }
        $raw = (string) shell_exec($cli . ' --json' . $opt . ' 2>&1');
        $imp = json_decode((string) substr($raw, (int) strpos($raw, '{')), true);
        if (!is_array($imp) || !empty($imp['report']['errors'])) {
            echec("L'import a échoué : " . json_encode($imp['report']['errors'] ?? $imp, JSON_UNESCAPED_UNICODE));
        }
        file_put_contents($out . '/IMPORT.json', json_encode($imp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        dire("✓ Import terminé");
        // Les archives copiées par l'importeur ne partent pas dans le site livré (le registre du rapport de migration, lui, reste).
        rmrf($root . '/data/ext/wordpress/package');
        foreach (glob($root . '/data/import/*.zip') ?: [] as $f) {
            unlink($f);
        }
    }

    if ($migration) {
        file_put_contents($out . '/.media-index.json', json_encode($paquet['media'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // 3. Contenus, images, design, réglages (processus séparé où Éclair est chargé).
    $res = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' __appliquer ' . escapeshellarg($root) . ' ' . escapeshellarg($briefPath) . ' ' . escapeshellarg($pack)
        . ' ' . ($migration ? 'migration ' . escapeshellarg((string) ($paquet['manifest']['source']['url'] ?? '')) : 'site') . ' 2>&1');
    $json = json_decode((string) substr((string) $res, (int) strpos((string) $res, '{"journal"')), true);
    if (!is_array($json)) {
        echec("Échec pendant la personnalisation :\n" . $res);
    }
    foreach ($json['journal'] as $l) {
        dire("  " . $l);
    }
    @unlink($out . '/.media-index.json');

    // 4. Contrôle qualité : mobile, H1, descriptions, JSON-LD, sitemap, robots, llms.txt.
    $rapport = verifier($root, $routeur);
    dire($rapport['problemes'] ? "⚠ Contrôle qualité : " . count($rapport['problemes']) . " point(s) à revoir" : "✓ Contrôle qualité : aucun problème détecté");
    foreach ($rapport['problemes'] as $p) {
        dire("  - " . $p);
    }

    if ($migration) {
        // Adresses publiques + fichiers (PDF, documents) de l'ancien site.
        $urls = $paquet['urls'];
        foreach ($paquet['media'] as $m) {
            if (!str_starts_with((string) ($m['mime'] ?? ''), 'image/') && !empty($m['url'])) {
                $urls[] = ['url' => (string) $m['url'], 'kind' => 'document', 'ref' => 'media:' . ($m['wp_id'] ?? '')];
            }
        }
        $anciennes = verifier_anciennes($root, $routeur, $urls);
        $acceptees = array_map(static fn ($x) => rtrim((string) $x, '/'), (array) ($b['migration']['accepter_404'] ?? []));
        $anciennes['acceptees'] = array_values(array_filter($anciennes['erreurs'], static fn ($e) => in_array(rtrim($e['ancienne'], '/'), $acceptees, true)));
        $anciennes['erreurs'] = array_values(array_filter($anciennes['erreurs'], static fn ($e) => !in_array(rtrim($e['ancienne'], '/'), $acceptees, true)));
        file_put_contents($out . '/ANCIENNES-ADRESSES.json', json_encode($anciennes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($anciennes['acceptees'] as $e) {
            dire("  ⚠ acceptée en 404 avec l'accord de la personne : {$e['ancienne']}");
        }
        if ($anciennes['erreurs']) {
            dire("⚠ Anciennes adresses WordPress : {$anciennes['ok']} / {$anciennes['total']} répondent ; " . count($anciennes['erreurs']) . " en erreur (détail : sortie/$slug/ANCIENNES-ADRESSES.json)");
            foreach (array_slice($anciennes['erreurs'], 0, 15) as $e) {
                dire("  - {$e['ancienne']} ({$e['statut']})");
            }
            if ($anciennes['erreurs']) {
                echec("Site NON livré : des anciennes adresses finiraient en 404 (perte de référencement). Ajoute-les dans « redirections » du brief, ou valide-les une par une dans « migration.accepter_404 ».", 3);
            }
        } else {
            dire("✓ Anciennes adresses WordPress : {$anciennes['ok']} / {$anciennes['total']} mènent à la bonne page (zéro 404)" . ($anciennes['acceptees'] ? ', ' . count($anciennes['acceptees']) . ' exception(s) acceptée(s)' : ''));
        }
    }

    foreach ($json['bloquants'] ?? [] as $x) {
        $rapport['problemes'][] = "BLOQUANT — $x";
        dire("  - BLOQUANT — $x");
    }
    $bloquants = array_filter($rapport['problemes'], static fn ($p) => str_contains($p, 'BLOQUANT'));
    if ($bloquants) {
        file_put_contents($out . '/RAPPORT-QUALITE.json', json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echec("Site NON livré : " . count($bloquants) . " point(s) BLOQUANT(S) (« [À compléter] », code court visible, page légale non remplacée, menu ou pied de page incomplet…). Corrige le brief et relance.", 2);
    }

    // 5. Nettoyage : ni session, ni cache, ni journal de construction dans le site livré.
    foreach (['sessions', 'limits', 'logs', 'redirect-hits'] as $d) {
        foreach (glob($root . "/data/$d/*") ?: [] as $f) {
            is_file($f) && basename($f) !== 'index.html' && basename($f) !== '.htaccess' ? unlink($f) : null;
        }
    }
    foreach (glob($root . '/cache/pages/*') ?: [] as $f) {
        is_dir($f) ? rmrf($f) : unlink($f);
    }
    @unlink($root . '/cache/runtime.php');

    // 6. Fichiers d'accompagnement + ZIP.
    $url = rtrim((string) ($b['domaine']['url'] ?? ''), '/');
    $admin = ($url !== '' ? $url : 'https://ton-domaine') . '/' . $adminPath . '/';
    $acces = $pack . '/sortie/' . $slug . '-ACCES-ADMIN.txt';
    @unlink($out . '/ACCES-ADMIN.txt');
    file_put_contents($acces, implode("\n", [
        "ACCÈS À L'ADMINISTRATION — " . $b['site']['nom'],
        "",
        "Adresse : $admin",
        "E-mail  : " . $b['admin']['email'],
        "Mot de passe : $pass",
        "",
        "→ Garde ces informations dans ton gestionnaire de mots de passe, puis SUPPRIME ce fichier.",
        "→ À la première connexion : Mon profil › Double authentification, active-la.",
        "→ Ce fichier est volontairement HORS du ZIP : ne l'envoie jamais sur l'hébergement.",
        "",
    ]));
    file_put_contents($out . '/ANALYSE-SEO-GEO.json', json_encode($json['analyse'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($out . '/RAPPORT-QUALITE.json', json_encode($rapport + ['eclair' => $version, 'date' => date('c')], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $guide = (string) @file_get_contents($pack . '/outils/' . ($migration ? 'modele-migration.md' : 'modele-installation.md'));
    file_put_contents($out . '/INSTALLATION.md', strtr($guide, [
        '{site}' => $b['site']['nom'], '{url}' => $url !== '' ? $url : '(ton domaine)', '{admin}' => $admin, '{version}' => $version,
        '{ssl}' => !empty($b['domaine']['ssl']) && $b['domaine']['ssl'] === 'oui' ? 'déjà actif d’après tes réponses' : 'à activer AVANT l’envoi',
    ]));
    $zipOut = $pack . '/sortie/' . $slug . '-eclair-v' . $version . '.zip';
    @unlink($zipOut);
    $z = new ZipArchive();
    $z->open($zipOut, ZipArchive::CREATE);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) {
        $rel = $slug . '/' . substr($f->getPathname(), strlen($out) + 1);
        $f->isDir() ? $z->addEmptyDir($rel) : $z->addFile($f->getPathname(), $rel); // dossiers vides gardés (sessions, cache…)
    }
    $z->close();
    @chmod($acces, 0600);
    dire("✓ ZIP prêt : sortie/" . basename($zipOut));
    dire("✓ Accès (hors ZIP) : sortie/" . basename($acces));
    dire("  Administration : $admin");
}

/* ───────────────────────── Entrée ───────────────────────── */

$cmd = $argv[1] ?? '';
switch ($cmd) {
    case '__appliquer':
        appliquer($argv[2], $argv[3], $argv[4], $argv[5] ?? 'site', $argv[6] ?? '');
        break;
    case 'version':
        $r = annonce();
        dire("Dernière version d'Éclair CMS : {$r['version']} du {$r['date']} (PHP {$r['php']} minimum)");
        break;
    case 'telecharger':
        telecharger($CACHE);
        break;
    case 'verifier':
        $rap = verifier(realpath($argv[2] ?? '') ?: echec('Dossier introuvable.'), $PACK . '/outils/routeur.php');
        echo json_encode($rap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        break;
    case '__migrer_preparer':
        migrer_preparer($argv[2]);
        break;
    case 'simuler':
    case 'migrer':
    case 'construire':
        $zipOpt = null;
        foreach (array_slice($argv, 3) as $a) {
            if (str_starts_with($a, '--zip=')) {
                $zipOpt = realpath(substr($a, 6)) ?: echec("Archive introuvable : " . substr($a, 6));
            }
        }
        construire($argv[2] ?? echec('Indique le brief : php outils/eclair-site.php ' . $cmd . ' projet/brief.json'), $zipOpt, $PACK, $CACHE,
            ['construire' => 'site', 'migrer' => 'migration', 'simuler' => 'simulation'][$cmd]);
        break;
    default:
        dire("Usage : php outils/eclair-site.php version | telecharger | construire projet/brief.json | simuler projet/brief.json | migrer projet/brief.json | verifier <dossier>");
}
