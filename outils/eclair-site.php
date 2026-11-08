<?php
/**
 * Agent IA Éclair CMS — moteur de construction d'un site prêt à envoyer.
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

function appliquer(string $root, string $briefFile, string $packDir): void
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

    $b = json_decode((string) file_get_contents($briefFile), true);
    $site = $b['site'];
    $user = array_values(Eclair\Users::all())[0] ?? echec("Aucun compte après l'installation.");
    $journal = [];

    // 1. Contenus de démonstration retirés : le site ne garde que ce qui vient du brief.
    foreach (array_keys(Eclair\Content::index()) as $id) {
        Eclair\Content::delete((string) $id);
    }
    foreach (Eclair\Categories::all() as $cat) {
        Eclair\Categories::delete($cat['id']);
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
    Eclair\Settings::save(['lang' => $lang0, 'url' => rtrim((string) ($b['domaine']['url'] ?? ''), '/'),
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
            }
        }
        return $out;
    };
    Eclair\Menus::save('main', $menu($b['menus']['principal'] ?? []));
    Eclair\Menus::save('footer', $menu($b['menus']['pied'] ?? []));

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
        'name' => $site['nom'],
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
            'entity_name' => $id['nom_legal'] ?? $site['nom'],
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
    Eclair\Cache::purge();
    $journal[] = "✓ réglages : identité, SEO, GEO, apparence" . ($direction ? " (direction « " . ($direction['name'] ?: $direction['id']) . " »)" : '');
    echo json_encode(['journal' => $journal, 'chemins' => $paths, 'analyse' => $analyse], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    $liste = array_values(array_unique($liste));
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
            'images_sans_alt' => preg_match_all('/<img(?![^>]*\balt=)[^>]*>/i', $h),
        ];
        $rapport['pages'][] = $info;
        if ($info['statut'] !== 200) { $rapport['problemes'][] = "$p : statut {$info['statut']}"; }
        if (!$info['viewport']) { $rapport['problemes'][] = "$p : balise viewport mobile absente"; }
        if ($info['h1'] !== 1) { $rapport['problemes'][] = "$p : {$info['h1']} titre(s) H1 au lieu d'un seul"; }
        $noindex = (bool) preg_match('/<meta name="robots" content="[^"]*noindex/i', $h);
        if ($info['description'] === '' && !$noindex) { $rapport['problemes'][] = "$p : pas de meta description"; }
        if ($info['jsonld'] === 0) { $rapport['problemes'][] = "$p : pas de données structurées JSON-LD"; }
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

function construire(string $briefFile, ?string $zipLocal, string $pack, string $cache): void
{
    foreach (['curl', 'zip', 'mbstring', 'json', 'dom', 'zlib'] as $ext) {
        if (!extension_loaded($ext)) {
            echec("Extension PHP « $ext » absente sur ton ordinateur (voir DEPANNAGE.md).");
        }
    }
    $briefPath = realpath($briefFile) ?: echec("Brief introuvable : $briefFile");
    $b = json_decode((string) file_get_contents($briefPath), true) ?: echec("Brief illisible (JSON invalide) : $briefFile");
    foreach (['site.nom', 'site.langue', 'site.type', 'admin.nom', 'admin.email'] as $k) {
        [$a, $c] = explode('.', $k);
        if (empty($b[$a][$c])) {
            echec("Brief incomplet : champ « $k » manquant.");
        }
    }
    $slug = slug((string) (($b['slug'] ?? '') !== '' ? $b['slug'] : $b['site']['nom']));
    $out = $pack . '/sortie/' . $slug;
    $root = $out . '/site';
    @mkdir($out, 0755, true);
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
    dire("✓ Éclair $version décompressé dans sortie/$slug/site");

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

    // 3. Contenus, images, design, réglages (processus séparé où Éclair est chargé).
    $res = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' __appliquer ' . escapeshellarg($root) . ' ' . escapeshellarg($briefPath) . ' ' . escapeshellarg($pack) . ' 2>&1');
    $json = json_decode((string) substr((string) $res, (int) strpos((string) $res, '{"journal"')), true);
    if (!is_array($json)) {
        echec("Échec pendant la personnalisation :\n" . $res);
    }
    foreach ($json['journal'] as $l) {
        dire("  " . $l);
    }

    // 4. Contrôle qualité : mobile, H1, descriptions, JSON-LD, sitemap, robots, llms.txt.
    $rapport = verifier($root, $routeur);
    dire($rapport['problemes'] ? "⚠ Contrôle qualité : " . count($rapport['problemes']) . " point(s) à revoir" : "✓ Contrôle qualité : aucun problème détecté");
    foreach ($rapport['problemes'] as $p) {
        dire("  - " . $p);
    }

    $bloquants = array_filter($rapport['problemes'], static fn ($p) => str_contains($p, 'BLOQUANT'));
    if ($bloquants) {
        file_put_contents($out . '/RAPPORT-QUALITE.json', json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echec("Site NON livré : " . count($bloquants) . " page(s) contiennent « [À compléter] ». Complète le brief et relance.", 2);
    }

    // 5. Nettoyage : ni session, ni cache, ni journal de construction dans le site livré.
    foreach (['sessions', 'limits', 'logs'] as $d) {
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
    $guide = (string) @file_get_contents($pack . '/outils/modele-installation.md');
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
        appliquer($argv[2], $argv[3], $argv[4]);
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
    case 'construire':
        $zipOpt = null;
        foreach (array_slice($argv, 3) as $a) {
            if (str_starts_with($a, '--zip=')) {
                $zipOpt = realpath(substr($a, 6)) ?: echec("Archive introuvable : " . substr($a, 6));
            }
        }
        construire($argv[2] ?? echec('Indique le brief : php outils/eclair-site.php construire projet/brief.json'), $zipOpt, $PACK, $CACHE);
        break;
    default:
        dire("Usage : php outils/eclair-site.php version | telecharger | construire projet/brief.json | verifier <dossier>");
}
