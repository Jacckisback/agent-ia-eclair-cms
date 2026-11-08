# Étape 4 — Les images

## La question

> « Pour les images du site : »
> `J'ai mes propres photos` / `Génère-les avec l'IA` / `Pas d'images pour l'instant` / `Conseille-moi`

« Conseille-moi » → de vraies photos pour l'équipe, le lieu et les produits (la confiance vient de là) ; l'IA pour les ambiances (bannière, illustrations d'articles). Jamais de fausse photo d'équipe, de faux client ou de faux produit présentée comme réelle.

## « J'ai mes propres photos »
- La personne glisse ses fichiers dans `projet/images/` (ou me donne leur chemin). JPG, PNG ou WebP, 20 Mo max chacun ; idéalement 1600 px de large pour une bannière.
- `images_fournisseur = "fichiers"`, une entrée par image dans `images` avec `fichier` et un `alt` descriptif (ce qu'on voit, pas des mots-clés empilés).
- Je regarde chaque photo (outil de lecture d'image) pour écrire un texte alternatif juste et choisir où la placer.

## « Génère-les avec l'IA »

> « Tu as déjà un compte chez un fournisseur d'images IA ? »
> `Oui : fal.ai` / `Oui : OpenAI` / `Oui : Black Forest Labs (FLUX)` / `Non, conseille-moi`

### Recommandation par défaut : fal.ai
Modèles FLUX, rapides, payés à l'usage. Pour obtenir la clé :
1. Crée un compte sur **https://fal.ai** (connexion Google ou GitHub possible).
2. Ajoute quelques euros de crédit (section Billing). Un site complet demande en général 5 à 10 images.
3. Ouvre **https://fal.ai/dashboard/keys**, clique sur « Add key », donne-lui un nom (ex. « eclair »), copie la clé.
4. Colle-la ici dans la conversation.

Repère de prix vu le 8 novembre 2026 sur la fiche du modèle : FLUX.1 [schnell] à 0,003 $ par mégapixel. Les prix changent : la personne vérifie sur fal.ai.

### Ce que je fais de la clé
- Je l'écris dans `projet/.env` : `FAL_KEY=...` (ou `OPENAI_API_KEY=...`, `BFL_API_KEY=...`). Je ne la répète pas en clair, je ne la mets nulle part ailleurs.
- Je teste : `php outils/images.php test` (une petite image). En cas d'erreur 401/403 : clé fausse ou crédit vide.
- `images_fournisseur` = `fal` | `openai` | `bfl`. `images_qualite` = `normale` (fal : FLUX schnell) ou `haute` (fal : FLUX dev). `images_modele` permet d'imposer un modèle précis si un fournisseur en change.

### Autre fournisseur
Si la personne en utilise un autre (Replicate, Ideogram, Stability, Midjourney…) : je lui demande de générer les images elle-même avec mes prompts et de les déposer dans `projet/images/` (mode `fichiers`), ou j'écris un petit appel à son API si sa documentation officielle est accessible — jamais sans la clé dans `projet/.env`.

## Écrire les prompts (en anglais, plus fiables)

Une image par usage : `accueil` (bannière, paysage), une par page clé, une par article, `logo` (carré, symbole sans texte), `partage` (image des réseaux sociaux, souvent = accueil).

Modèle : `[sujet précis tiré de l'activité], [lieu/ambiance], [lumière], [palette de la direction artistique], [style photo ou illustration], no text, no watermark, no logos`.

- Reprendre les couleurs de la direction (ex. `argile` : sand, terracotta, warm clay).
- Pas de visage reconnaissable, pas de personne réelle, pas de marque, pas d'œuvre ou de personnage protégé.
- Pas de texte dans l'image (les IA l'écrivent mal ; le texte est dans le HTML, lisible par Google).

Je génère : `php outils/images.php projet/brief.json`, puis **je regarde chaque image** avant de construire. Une image ratée → je corrige son prompt et relance avec `--tout` ou en supprimant le fichier.

## « Pas d'images »
`images_fournisseur = "aucun"`. Les bannières passent en version texte. Je rappelle qu'on peut en ajouter ensuite dans la Médiathèque d'Éclair, ou avec l'assistant IA intégré à Éclair.
