# Migrer un site WordPress vers Éclair CMS

Promesse : le même site, plus rapide et plus sûr, **sans perdre une seule adresse** (donc sans perdre son référencement). Outils officiels :
- côté WordPress, l'extension **Eclair Export** (https://github.com/Jacckisback/eclair-export) : diagnostic, puis export en un paquet ZIP (contenus, médias, SEO Yoast/Rank Math/AIOSEO, menus, redirections, liste des anciennes adresses) ;
- côté Éclair, la fonction **Import WordPress** livrée avec Éclair : le moteur l'utilise en local (`simuler`, puis `migrer`) ; c'est le même code qu'en ligne.

Règles propres à la migration (en plus de `REGLES.md`) :
- **Je ne touche jamais au WordPress en ligne.** C'est la personne qui installe l'extension et télécharge l'export. Son site reste en ligne et intact jusqu'à la bascule.
- **Zéro 404 ou pas de livraison** : chaque ancienne adresse doit répondre 200, ou une seule redirection 301 vers la bonne page. Le moteur bloque sinon.
- **Rien n'est supprimé côté WordPress** avant 3 mois et une vérification dans Google Search Console.
- Ce qui ne se migre pas est dit **avant** l'import (simulation), pas découvert après.

## Le parcours

L'étape 0 de `preparation.md` (PHP, archivage d'un projet précédent) s'applique aussi.

| Étape | Ce que je fais | Durée pour la personne |
|---|---|---|
| M1. L'ancien site | Adresse du site WordPress. Je la consulte (pages principales, langue, type d'activité) pour ne pas poser de questions inutiles. | 30 s |
| M2. Accès | « As-tu un compte administrateur sur ce WordPress ? » (`Oui` / `Non, c'est un prestataire` / `Je ne sais pas`). Sans accès admin : je rédige le message à envoyer au prestataire (installer Eclair Export et lui transmettre l'export). Puis, en une fiche : qui a accès au **registraire** du domaine (DNS) ? au **compte d'hébergement** ? Des **adresses e-mail** utilisent-elles ce domaine (chez l'hébergeur, Google, Microsoft) ? Un contrat de prestataire à résilier (préavis) ? Sans accès au registraire ni à l'hébergement, la bascule est impossible : je le dis tout de suite. | 1 min |
| M3. Installer Eclair Export | Je télécharge l'extension pour la personne et je la guide pas à pas (ci-dessous). | 2 min |
| M4. Diagnostic | Elle lance le diagnostic et dépose le rapport dans `projet/migration/`. Je le lis et j'explique en 5 lignes ce qui passera, ce qui sera simplifié, ce qui ne passera pas. | 2 min |
| M5. Vitesse avant | Elle mesure l'ancien site sur https://pagespeed.web.dev (mobile) et me donne les 4 notes, ou une capture. Je les note dans `projet/migration/vitesse-avant.md`. **Après la bascule, ce ne sera plus possible.** | 1 min |
| M6. Export | Elle lance l'export et dépose toutes les parties dans `projet/migration/`. | 2 à 15 min selon la taille |
| M7. Mes questions | Seulement ce que l'export ne contient pas : design, identité/mentions légales manquantes, compte d'administration, domaine et hébergement. | 2 min |
| M8. Simulation | `php outils/eclair-site.php simuler projet/brief.json` : je montre le bilan et les éléments non migrés, je propose une solution pour chacun, la personne valide. | 1 min |
| M9. Migration | `php outils/eclair-site.php migrer projet/brief.json` : import, design, réglages, contrôle qualité, **contrôle de chaque ancienne adresse**, ZIP. Je corrige et relance jusqu'au « zéro 404 ». | — |
| M10. Mise en ligne | Guide `INSTALLATION.md` (modèle `outils/modele-migration.md`) : sauvegarde, bascule, vérifications, rapport client. | 15 min |

### M3 — Installer Eclair Export (je guide)

1. Je télécharge la dernière version **depuis le dépôt officiel uniquement** dans `projet/migration/` : la dernière publication (`curl -sL https://api.github.com/repos/Jacckisback/eclair-export/releases/latest`, fichier `eclair-export-x.y.z.zip`), sinon le dossier `dist/` du dépôt (`…/contents/dist` puis `https://github.com/Jacckisback/eclair-export/raw/main/dist/<nom>.zip`). J'annonce la version et je vérifie que l'archive contient bien `eclair-export/eclair-export.php`. Sans réseau côté terminal : je donne le lien https://github.com/Jacckisback/eclair-export.
2. Dans WordPress : **Extensions › Ajouter › Téléverser une extension** → choisir ce ZIP → **Installer maintenant** → **Activer**. (WordPress 6.0 ou plus, PHP 7.4 ou plus.)
3. Ouvrir **Outils › Exporter vers Éclair**.

Erreurs fréquentes : « Le lien que vous avez suivi a expiré » = fichier trop gros pour l'hébergeur (rare : l'extension est petite) → installer par FTP dans `wp-content/plugins/`. Bouton « Téléverser » absent = compte non administrateur.

### M4 — Diagnostic

« Clique sur **Lancer le diagnostic**, puis télécharge le **rapport** (HTML) et glisse-le dans le dossier `projet/migration/` (ou copie-colle ici les points d'attention). »

Je lis et je classe pour la personne :
- **Passe tel quel** : contenus Gutenberg et éditeur classique, images, PDF, SEO, menus, redirections, catégories, auteurs.
- **Simplifié** : mises en page Elementor/Divi (textes, titres, images, boutons, vidéos, listes, accordéons, témoignages gardés ; colonnes, animations, styles perdus). Le design est refait avec une direction artistique d'Éclair : c'est l'occasion d'avoir un site plus net.
- **À refaire dans Éclair** (je propose la solution) :
  - formulaires (Contact Form 7, WPForms, Gravity Forms, formulaires Elementor) → bloc Contact d'Éclair (`remplacer_codes_courts`, valeur `contact`) ;
  - carrousels, tableaux de prix, widgets → blocs Éclair (`features`, `table`, `faq`) ajoutés après coup ;
  - réservation, newsletter, chat → bouton vers l'outil externe ;
  - autres codes courts → retirés (`retirer`) s'ils n'affichent rien d'utile, ou remplacés à la main.
- **Non migré** : commentaires, commandes et clients WooCommerce, comptes utilisateurs et mots de passe (aucun mot de passe n'est exporté : c'est voulu). WooCommerce : les produits passent dans la boutique légère d'Éclair, **sans panier** ; chaque produit aura besoin de son lien de paiement Stripe/PayPal/Revolut (à créer).
- **Multilingue** (WPML, Polylang) : les langues et les liens entre traductions sont repris par l'import. Je vérifie au moins une page dans chaque langue.

**Boutique WooCommerce** : je pose une question avant d'aller plus loin — « Combien de commandes en ligne par mois, et utilises-tu des dates ou créneaux de livraison ? » (`Presque aucune` / `Quelques-unes par semaine` / `Beaucoup, c'est mon activité` / `Conseille-moi`). Avec des créneaux de livraison ou beaucoup de commandes, la boutique légère (un lien de paiement par produit, sans panier) est un recul : je le dis et je propose de migrer la vitrine et le blog seulement, ou d'attendre. Si la personne continue : pages Panier, Commande, Mon compte → `modifications` en brouillon + `redirections` vers la boutique ; **liens de paiement créés avant la bascule** et posés avec `modifications[].lien_paiement` (sinon les fiches seraient en ligne sans bouton d'achat) ; page CGV et médiateur obligatoires (`mentions-legales.md`).

**Formulaires Elementor** (sans code court, signalés dans le diagnostic comme widgets) : j'ajoute le bloc Contact à la page avec `modifications[].ajouter_blocs`.

Si le diagnostic montre un site très dépendant d'une extension métier (boutique avec des centaines de commandes, espace membres, LMS), je le dis franchement : la migration n'est pas une bonne idée aujourd'hui, ou seulement de la partie vitrine/blog.

### M6 — Export

« Clique sur **Lancer l'export**. Coche tous les types de contenus. Médias : **inclure dans l'archive** (recommandé). Laisse l'onglet ouvert ; si ça coupe, rouvre la page et clique sur **Reprendre l'export**. Télécharge **toutes les parties** (le fichier principal et les `-part2`, `-part3`…) et glisse-les dans `projet/migration/`. »

- Chaque partie est supprimée du serveur WordPress dès qu'elle est téléchargée : il faut les garder.
- Mode « médias à distance » : l'import les téléchargera depuis l'ancien site ; il faut alors migrer **avant** la bascule du domaine.
- En SSH avec WP-CLI : `wp eclair export --output=<dossier>` (je peux donner la commande si la personne a cet accès).

### M7 — Mes questions (seulement ce qui manque)

L'export contient déjà le nom, la langue, les pages, le SEO, les menus. Je demande :
1. **Design** : 3 directions artistiques proposées à partir du site actuel et de l'activité (`design.md`). « Garder exactement l'ancien thème » n'est pas possible : je le dis et je propose la direction la plus proche.
2. **Logo** : je le récupère sur l'ancien site (en-tête, favicon) s'il est accessible, sinon la personne le dépose dans `projet/images/`.
3. **Mentions légales et confidentialité** : je lis les pages importées et je les compare à `mentions-legales.md`. Il manque souvent l'hébergeur (il change si l'hébergement change !), le médiateur ou la mention des outils retirés (Google Analytics, pixels). Je pose uniquement les questions manquantes et je mets à jour ces pages via `pages` du brief (elles remplacent la page importée de même adresse, voir `brief.md`).
4. **Compte d'administration** (nom, e-mail, fonction, bio).
5. **Domaine et hébergement** : même domaine (par défaut) ; même hébergeur ou nouvel hébergeur ? → choisit le scénario de bascule de `INSTALLATION.md`. HTTPS : la question porte sur l'hébergement **où ira Éclair**. Nouvel hébergeur sans certificat déjà actif sur ce domaine → `domaine.ssl = "non"` (la redirection HTTPS s'active après la bascule, sinon le site serait en erreur pendant la propagation).
6. **Confidentialité** : les messages du formulaire sont désormais stockés dans Éclair (et plus dans l'extension de formulaire) ; les outils retirés (Analytics, pixels, extension de cookies) disparaissent de la politique. Je mets la page à jour avec `remplace`.

### M8 — Simulation

`simuler` travaille dans un dossier temporaire : il ne touche ni à `sortie/` ni à un ZIP déjà livré.

Je montre le bilan en clair (nombre de contenus, adresses gardées ou redirigées, médias, éléments non migrés) et, pour chaque élément non migré, ma solution. Une question : « On lance la migration ? » (`Oui` / `Je veux ajuster quelque chose`).

### M9 — Migration et corrections

Lecture de la sortie du moteur :
- `⚠ Anciennes adresses … en erreur` puis `Site NON livré` : pour chaque adresse, je trouve la bonne page de destination et je l'ajoute dans `redirections` du brief (`{"de": "/ancienne/", "vers": "/nouvelle/"}`). Une adresse sans équivalent valable (ancienne page de test, panier WooCommerce) peut être acceptée **une par une**, avec l'accord de la personne, dans `migration.accepter_404`.
- `code(s) court(s) WordPress visible(s)` : j'ajoute le code court dans `remplacer_codes_courts` (`contact` ou `retirer`) et je relance.
- `pied de page vide` : je relie les pages légales (`menus.pied`, ex. `["adresse:/mentions-legales/", "adresse:/politique-de-confidentialite/"]`).
- Tout le reste qui est `BLOQUANT` (page à remplacer introuvable, menu, pied de page, redirection masquée) : je corrige le brief, jamais de contournement.
- Une ancienne adresse renvoyée vers l'accueil compte comme une erreur : je trouve la page équivalente.
- Scores SEO/GEO : sur un site migré, **je ne réécris pas** les textes de la personne sans son accord. Je liste les 5 pages les plus importantes à améliorer, comme suite possible.
- Je regarde au moins l'accueil, un article et une page Elementor/Divi en mobile (`livraison.md`).

## Ce que je livre

Le ZIP (dossier `site/` + `INSTALLATION.md` spécial migration + `SIMULATION.json`, `IMPORT.json`, `ANCIENNES-ADRESSES.json`, rapports) et le fichier d'accès à côté. Dans ma réponse : le bilan (contenus, adresses, zéro 404), les éléments refaits, ce qui reste à faire à la main (liens de paiement des produits, outils externes), et le scénario de bascule choisi.
