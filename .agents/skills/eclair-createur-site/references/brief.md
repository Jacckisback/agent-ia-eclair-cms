# Format de `projet/brief.json`

Je l'écris moi-même au fil des réponses. Modèles vierges : `projet/brief.modele.json` (nouveau site) et `projet/brief.modele-migration.json` (migration WordPress). Exemple complet : `exemples/fournil-des-pentes/brief.json`.

## Racine

`mode` : `nouveau-site` (moteur : `construire`) ou `migration` (moteur : `simuler` puis `migrer`).

### Champs propres à la migration

| Champ | Contenu |
|---|---|
| `migration.paquets` | chemins des parties de l'export Eclair Export (`projet/migration/eclair-export-….zip`, `…-part2.zip`…) |
| `migration.brouillons` | `true` = tout importer en brouillon (rare) |
| `migration.reprendre_menus` | `false` = ne pas reprendre les menus WordPress |
| `migration.accepter_404` | anciennes adresses acceptées en 404, **une par une, avec l'accord de la personne** |
| `redirections` | `[{"de": "/ancienne-adresse/", "vers": "/nouvelle/" ou "@page:cle"}]` (utilisable aussi pour un nouveau site qui remplace un ancien) |
| `remplacer_codes_courts` | `{"contact-form-7": "contact", "wpforms": "contact", "gallery": "retirer"}` : `contact` = bloc formulaire d'Éclair, `retirer` = supprimé |
| `modifications` | contenus importés à retoucher sans les réécrire : `[{"adresse": "/panier/", "statut": "brouillon"}, {"adresse": "/contact/", "ajouter_blocs": [{"type": "contact"}]}, {"adresse": "/produit/bouquet-rond/", "lien_paiement": "https://buy.stripe.com/…"}]` |
| `seo_verification` | balise Google Search Console (reprise automatiquement depuis Yoast/Rank Math si elle y était) |
| `blog.remplacer_import` | `true` seulement pour changer l'adresse ou le titre du blog importé (sinon `blog` est ignoré en migration) |
| `pages[].remplace` | `"adresse:/mentions-legales/"` : la page du brief **réécrit** la page importée à cette adresse (même adresse, même langue, mêmes traductions, pas de doublon). Introuvable = livraison bloquée |
| `pages[].langue` | langue d'une page ajoutée (site multilingue), sinon celle du site |
| `menus.principal` / `menus.pied` | vides = menus importés gardés ; liste = **remplace** (cas normal pour le pied de page : toutes les pages légales) ; uniquement des `+cle` = ajoute à la fin ; `adresse:/chemin/` = désigne une page importée. Une entrée introuvable bloque la livraison |

En migration, je pars de `projet/brief.modele-migration.json` (pas du modèle « nouveau site »). La langue et le fuseau viennent toujours du paquet ; `site.nom`, `site.type` et `domaine.url` peuvent rester vides. Les champs laissés vides n'écrasent rien de ce que l'import a repris. Les anciennes adresses des fichiers (`/wp-content/uploads/…`, miniatures et PDF compris) sont redirigées automatiquement.

| Champ | Contenu |
|---|---|
| `slug` | identifiant du projet (lettres, chiffres, tirets) : nom du dossier et du ZIP |
| `site` | `nom`, `slogan` (≤ 160 car.), `description`, `langue` (`fr`, `es`, `en`, `de`, `it`, `pt`), `type` (`vitrine`, `freelance`, `page-unique`, `blog`, `media`, `affiliation`), `pays` (`FR`, `ES`…), `fuseau` (facultatif) |
| `admin` | `nom`, `email`, `fonction`, `bio` (≥ 40 caractères), `mot_de_passe` (vide = généré, recommandé), `adresse` (vide = `gestion-xxxxxx`, sinon 3 à 40 caractères a-z 0-9 -) |
| `domaine` | `url` (`https://www.exemple.fr` ou vide), `statut` (`achete`, `a-acheter`, `plus-tard`), `hebergeur`, `ssl` (`oui`, `non`, `inconnu`) |
| `identite` | `type` (`LocalBusiness`, `Organization`, `Person`), `statut_juridique`, `nom_legal`, `telephone`, `email`, `email_formulaire` (reçoit les messages, défaut = `email`), `adresse`, `code_postal`, `ville`, `region`, `pays`, `horaires` (`Mo-Fr 09:00-18:00`), `gamme_prix` (`€`, `€€`…) |
| `seo` | `indexer` (true), `robots_ia` (`open`, `search`, `closed`), `format_titre` (`{title} · {site}`), `image_partage` (clé d'image), `a_propos_ia` |
| `profil_editorial` | `about`, `audience`, `tone`, `values`, `rules`, `visual` (repris par l'assistant IA d'Éclair) |
| `design` | `direction` (identifiant, voir `design.md`, ou objet sur mesure), `logo` (clé d'image), `sombre` (`auto`, `light`, `dark`), `pied_de_page` (texte libre) |
| `css_personnalise` | CSS de finition (facultatif) |
| `images_fournisseur` | `fal`, `openai`, `bfl`, `fichiers`, `aucun` ; `images_qualite` (`normale`, `haute`) ; `images_modele` (facultatif) |
| `images` | liste : `cle`, `fichier` (`projet/images/<cle>.jpg`), `alt`, `prompt` (anglais), `format` (`paysage`, `carre`, `portrait`) |
| `blog` | `actif`, `titre`, `description`, `adresse` (ex. `blog`, `conseils`) |
| `categories` | liste : `cle`, `nom`, `description` (obligatoire : c'est la méta-description de la page catégorie), `contient` (`articles` ou `produits`) |
| `accueil` | clé de la page d'accueil |
| `pages`, `articles`, `produits` | voir ci-dessous |
| `menus` | `principal` et `pied` : listes de clés, plus `@blog` et `@boutique` |
| `reseaux` | `linkedin`, `x`, `facebook`, `instagram`, `youtube`, `tiktok`, `github`, `other` (URL complètes, seulement celles données) |
| `contact` | `merci` (message après envoi du formulaire) |
| `boutique` | `active`, `titre`, `description` (méta-description du catalogue), `devise`, `livraison` {`price`, `free_from`, `countries`, `days_min`, `days_max`}, `retours` {`days`, `free`}, `page_cgv`, `page_mentions`, `page_merci` (clés de pages) |

## Une page, un article, un produit

```json
{
  "cle": "services",
  "titre": "Nos services",
  "slug": "services-osteopathie",
  "resume": "L'essentiel en 25 à 90 mots (pas sur l'accueil).",
  "image": "@img:accueil",
  "categorie": "conseils",
  "seo": {"title": "…", "description": "…", "keyword": "…", "noindex": false},
  "options": {"hide_title": false, "toc": "auto", "wide": false},
  "blocs": [ … ],
  "produit": {"price": "29.90", "compare": "", "availability": "in_stock", "link": "https://buy.stripe.com/…", "gallery": ["@img:produit1"], "brand": "", "sku": "", "variants": []}
}
```

`"brouillon": true` crée le contenu sans le publier. `produit` seulement dans `produits` ; jamais de prix ni de lien de paiement inventés.

## Blocs disponibles

| `type` | Champs |
|---|---|
| `hero` | `title`, `text`, `button`, `url`, `button2`, `url2`, `media` |
| `features` | `title`, `items` [{`icon`, `title`, `text`}] (icônes : `design.md`) |
| `paragraph` | `text` (gras `<strong>`, italique `<em>`, liens `<a href>` permis) |
| `heading` | `level` (2, 3, 4), `text`, `anchor` |
| `list` | `style` (`ul`, `ol`), `items` [texte] |
| `image` | `media`, `alt`, `caption`, `link`, `size` (`normal`, `wide`, `small`) |
| `callout` | `variant` (`info`, `tip`, `warning`, `success`, `note`), `title`, `text` |
| `faq` | `title`, `items` [{`q`, `a`}] |
| `table` | `head` (true), `caption`, `rows` [[cellules]] |
| `quote` | `text`, `cite` (seulement une vraie citation) |
| `button` | `text`, `url`, `style` (`primary`, `secondary`), `align` (`left`, `center`) |
| `team` | `title`, `items` [{`name`, `role`, `bio`, `media`, `link`}] |
| `posts` / `products` | `title`, `count`, `category` |
| `contact` | `title`, `button` (formulaire intégré d'Éclair) |
| `video` | `url` (YouTube/Vimeo), `caption` |
| `divider` | — |

Références : `@img:<cle>` pour une image du brief, `@page:<cle>` pour l'adresse d'une page, d'un article ou d'un produit (résolue automatiquement).
