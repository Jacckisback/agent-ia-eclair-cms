<?php
/**
 * Agent IA Éclair CMS — génération des images du site à partir du brief.
 *
 *   php outils/images.php projet/brief.json            génère les images manquantes (champ « fichier » absent du disque)
 *   php outils/images.php projet/brief.json --tout     régénère toutes les images
 *   php outils/images.php test                         vérifie que la clé fonctionne (1 petite image, ~0,01 €)
 *
 * La clé est lue dans projet/.env (jamais dans le brief, jamais dans le site livré) :
 *   FAL_KEY=...        fal.ai (recommandé : modèles FLUX)
 *   OPENAI_API_KEY=... OpenAI (gpt-image-1)
 *   BFL_API_KEY=...    Black Forest Labs (FLUX en direct)
 * Le fournisseur se choisit dans le brief : "images_fournisseur": "fal" | "openai" | "bfl" | "fichiers" | "aucun".
 */
declare(strict_types=1);

$PACK = dirname(__DIR__);

function sortir(string $m, int $c = 1): never { fwrite(STDERR, "✗ $m\n"); exit($c); }

function env(string $pack): array
{
    $out = [];
    foreach (@file($pack . '/projet/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
        if (preg_match('/^\s*([A-Z_]+)\s*=\s*"?([^"]*)"?\s*$/', $l, $m)) {
            $out[$m[1]] = trim($m[2]);
        }
    }
    foreach (['FAL_KEY', 'OPENAI_API_KEY', 'BFL_API_KEY'] as $k) {
        if (getenv($k)) {
            $out[$k] = (string) getenv($k);
        }
    }
    return $out;
}

function post(string $url, array $headers, array $body, int $timeout = 180): array
{
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers), CURLOPT_POSTFIELDS => json_encode($body)]);
    $raw = curl_exec($c);
    $st = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    $json = json_decode((string) $raw, true);
    if ($st >= 400 || !is_array($json)) {
        $msg = is_array($json) ? json_encode($json['detail'] ?? $json['error'] ?? $json, JSON_UNESCAPED_UNICODE) : substr((string) $raw, 0, 300);
        sortir("Le fournisseur a refusé la demande (HTTP $st) : $msg" . ($st === 401 || $st === 403 ? "\n  → Clé invalide ou sans crédit : vérifie-la dans ton tableau de bord." : ''));
    }
    return $json;
}

function get(string $url, array $headers = []): string
{
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 120, CURLOPT_HTTPHEADER => $headers]);
    $b = curl_exec($c);
    curl_close($c);
    return (string) $b;
}

/** Formats : paysage (bannière, partage), carre (logo, icônes), portrait. */
function generer(string $fournisseur, array $env, string $prompt, string $format, string $qualite, string $modele = ''): string
{
    switch ($fournisseur) {
        case 'fal':
            $key = $env['FAL_KEY'] ?? sortir('FAL_KEY absente de projet/.env');
            $model = $modele ?: ($qualite === 'haute' ? 'fal-ai/flux/dev' : 'fal-ai/flux/schnell');
            $size = ['paysage' => 'landscape_16_9', 'carre' => 'square_hd', 'portrait' => 'portrait_4_3'][$format] ?? 'landscape_16_9';
            $r = post("https://fal.run/$model", ["Authorization: Key $key"], ['prompt' => $prompt, 'image_size' => $size, 'num_images' => 1, 'enable_safety_checker' => true]);
            $url = $r['images'][0]['url'] ?? sortir('Réponse fal.ai sans image : ' . json_encode($r));
            return get($url);
        case 'openai':
            $key = $env['OPENAI_API_KEY'] ?? sortir('OPENAI_API_KEY absente de projet/.env');
            $size = ['paysage' => '1536x1024', 'carre' => '1024x1024', 'portrait' => '1024x1536'][$format] ?? '1536x1024';
            $r = post('https://api.openai.com/v1/images/generations', ["Authorization: Bearer $key"],
                ['model' => $modele ?: 'gpt-image-1', 'prompt' => $prompt, 'size' => $size, 'quality' => $qualite === 'haute' ? 'high' : 'medium', 'n' => 1]);
            return base64_decode((string) ($r['data'][0]['b64_json'] ?? sortir('Réponse OpenAI sans image.')));
        case 'bfl':
            $key = $env['BFL_API_KEY'] ?? sortir('BFL_API_KEY absente de projet/.env');
            $ratio = ['paysage' => '16:9', 'carre' => '1:1', 'portrait' => '3:4'][$format] ?? '16:9';
            // Même appel que l'assistant IA d'Éclair (app/lib/AiImage.php) ; modèle modifiable par « images_modele ».
            $r = post('https://api.bfl.ai/v1/' . ($modele ?: 'flux-3-image'), ["x-key: $key", 'Accept: application/json'], ['prompt' => $prompt, 'aspect_ratio' => $ratio, 'resolution' => '1k']);
            $poll = (string) ($r['polling_url'] ?? sortir('Réponse BFL inattendue.'));
            for ($i = 0; $i < 90; $i++) {
                sleep(2);
                $s = json_decode(get($poll, ["x-key: $key", 'Accept: application/json']), true);
                if (($s['status'] ?? '') === 'Ready') {
                    return get((string) $s['result']['sample']);
                }
                if (in_array($s['status'] ?? '', ['Error', 'Content Moderated', 'Request Moderated'], true)) {
                    sortir('BFL : ' . $s['status']);
                }
            }
            sortir('BFL : délai dépassé.');
    }
    sortir("Fournisseur inconnu : $fournisseur (fal, openai, bfl)");
}

/** Allège l'image : 2000 px de large au plus, JPEG qualité 82 (Éclair produit ensuite le WebP et les tailles). */
function alleger(string $bytes): string
{
    if (!function_exists('imagecreatefromstring') || !($im = @imagecreatefromstring($bytes))) {
        return $bytes;
    }
    $w = imagesx($im);
    if ($w > 2000) {
        $im = imagescale($im, 2000);
    }
    ob_start();
    imagejpeg($im, null, 82);
    return (string) ob_get_clean();
}

$arg = $argv[1] ?? sortir("Usage : php outils/images.php projet/brief.json [--tout] | test");
$env = env($PACK);

if ($arg === 'test') {
    $f = ['FAL_KEY' => 'fal', 'OPENAI_API_KEY' => 'openai', 'BFL_API_KEY' => 'bfl'];
    $four = null;
    foreach ($f as $k => $v) {
        if (!empty($env[$k])) { $four = $v; break; }
    }
    $four ?? sortir('Aucune clé dans projet/.env (FAL_KEY, OPENAI_API_KEY ou BFL_API_KEY).');
    $bytes = generer($four, $env, 'A small green leaf on a white background, minimal photo', 'carre', 'normale');
    @mkdir($PACK . '/projet/images', 0755, true);
    file_put_contents($PACK . '/projet/images/_test.jpg', alleger($bytes));
    echo "✓ Clé $four valide : image de test enregistrée dans projet/images/_test.jpg\n";
    exit(0);
}

$briefFile = realpath($arg) ?: sortir("Brief introuvable : $arg");
$b = json_decode((string) file_get_contents($briefFile), true) ?: sortir('Brief illisible.');
$four = (string) ($b['images_fournisseur'] ?? 'aucun');
if (in_array($four, ['aucun', 'fichiers'], true)) {
    echo "ℹ Fournisseur « $four » : aucune génération (images fournies par la personne, ou site sans image).\n";
    exit(0);
}
$tout = in_array('--tout', $argv, true);
$qualite = (string) ($b['images_qualite'] ?? 'normale');
$fait = 0;
foreach ($b['images'] ?? [] as $i => $img) {
    $file = (string) ($img['fichier'] ?? ('projet/images/' . $img['cle'] . '.jpg'));
    $path = str_starts_with($file, '/') ? $file : $PACK . '/' . $file;
    if (!$tout && is_file($path)) {
        echo "• {$img['cle']} : déjà présente\n";
        continue;
    }
    if (empty($img['prompt'])) {
        echo "⚠ {$img['cle']} : pas de prompt, ignorée\n";
        continue;
    }
    $format = (string) ($img['format'] ?? ($img['cle'] === 'logo' ? 'carre' : 'paysage'));
    echo "⏳ {$img['cle']} ($four, $format)…\n";
    $bytes = alleger(generer($four, $env, (string) $img['prompt'], $format, $qualite, (string) ($b['images_modele'] ?? '')));
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $bytes);
    $b['images'][$i]['fichier'] = $file;
    $fait++;
    echo "✓ {$img['cle']} → $file (" . round(strlen($bytes) / 1024) . " Ko)\n";
}
file_put_contents($briefFile, json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "✓ $fait image(s) générée(s). Regarde-les dans projet/images avant de construire le site.\n";
