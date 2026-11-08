# Étape 7 — Rédiger les contenus : SEO, GEO, mobile

Éclair note chaque contenu (SEO et GEO sur 100) avec son propre analyseur. Le moteur écrit le détail dans `sortie/<slug>/ANALYSE-SEO-GEO.json` (champ `a_ameliorer`). Je corrige les textes dans le brief, je reconstruis (moins d'une minute), **2 passes au maximum**.

## Objectifs

| Contenu | SEO | GEO |
|---|---|---|
| Pages services/produits, articles | ≥ 80 | ≥ 80 |
| Accueil | ≥ 75 | ≥ 60 (sans résumé « L'essentiel », voulu ; son adresse est `/`, le critère « expression-clé dans l'URL » ne peut pas être rempli) |
| À propos, Contact | ≥ 60 (peu de texte, c'est normal) | ≥ 80 |
| Mentions légales, confidentialité, CGV | sans objet (`noindex` voulu) | sans objet |

Je ne gonfle jamais un texte de remplissage pour gagner des points : si un score reste en dessous parce que la page est volontairement courte, je l'explique en une ligne.

## Ce que regarde l'analyseur d'Éclair (à respecter dès la première rédaction)

**SEO**
- Une **expression-clé** par page (`seo.keyword`), 2 à 4 mots, celle que taperait un client (« ostéopathe Nantes », « conserver un pain au levain »). Elle doit apparaître **telle quelle** dans : le titre SEO, la méta-description, l'adresse (`slug`), les premières lignes, au moins un intertitre (H2/H3).
- Titre SEO (`seo.title`) : 30 à 60 caractères, utilisé tel quel. Sans `seo.title`, Éclair compose `{titre} · {site}` (format `seo.format_titre`) : je compte alors le tout.
- Méta-description (`seo.description`) : 110 à 160 caractères, avec l'expression-clé et un bénéfice concret.
- Longueur : 250 mots et plus pour une page, 150 pour une page avec bannière (`hero`/`features`), 600 pour un article.
- Liens internes vers les autres pages (boutons, mentions dans le texte), images avec texte alternatif.
- Adresse courte (moins de 75 caractères).

**GEO (moteurs de réponse IA)**
- Résumé « L'essentiel » (`resume`) de 25 à 90 mots, autonome, citable tel quel : **sur toutes les pages sauf l'accueil**.
- Introduction directe : la réponse dans les 70 premiers mots.
- Listes ou tableaux ; chiffres concrets **donnés par la personne** (horaires, délais, durées, prix réels).
- Pour les articles : bloc `faq`, intertitres en questions (« Comment… ? »), au moins une source externe fiable (lien officiel). Un article de départ fait **600 mots ou plus** (l'article de l'exemple est volontairement court).
- Auteur avec biographie d'au moins 40 caractères (`admin.bio`) et fonction (`admin.fonction`).
- Paragraphes de moins de 120 mots.

## Réglages GEO appliqués par le moteur

- `seo.robots_ia` : `open` (par défaut, les IA de recherche et d'entraînement peuvent lire le site), `search` (seulement les IA de recherche qui citent leurs sources), `closed`. Je demande seulement si la personne a une raison de les bloquer.
- `seo.a_propos_ia` : une description factuelle de l'entreprise (qui, quoi, où), reprise par Éclair dans `llms.txt`.
- `profil_editorial` : ton, public, valeurs, règles (« ne jamais inventer de prix ») — utilisé ensuite par l'assistant IA intégré à Éclair pour écrire dans le même style.
- Données structurées (JSON-LD) générées par Éclair à partir de `identite` : `LocalBusiness` pour un commerce avec adresse, `Organization` pour une société, `Person` pour un indépendant. `horaires` au format schema.org (`Mo-Fr 09:00-18:00`).

## Formulaire de contact

Éclair ne place pas de mention d'information près du formulaire : j'ajoute **sous le bloc `contact`** un paragraphe court : finalité (« uniquement pour vous répondre »), « merci de ne pas y indiquer de données sensibles » (indispensable pour santé, droit, social), lien vers `@page:confidentialite`.

Les messages arrivent dans l'administration (menu **Messages**) et par e-mail à `identite.email_formulaire`, envoyés depuis `no-reply@<domaine>`. Le guide d'installation demande de faire un essai et de regarder les indésirables ; sans domaine réglé, l'e-mail ne part pas correctement : seuls les Messages de l'administration font foi.

## Mobile

Le thème d'Éclair est conçu mobile d'abord (Lighthouse 100 visé par le projet). Ce qui peut le casser vient du contenu :
- Titres de pages du menu : 22 caractères maximum (le titre SEO peut être plus long).
- Titres de bannière : 60 caractères maximum.
- 4 entrées maximum dans le menu principal.
- Pas d'image décorative énorme : le script d'images limite à 2000 px, Éclair produit le WebP et les tailles adaptées.
- Boutons : 2 maximum par bannière, textes de 3 à 5 mots.

## Écriture

- Langue du site, tutoiement ou vouvoiement selon le ton choisi (vouvoiement par défaut pour les clients d'une entreprise).
- Phrases courtes, concrètes, au service du client. Interdits : « passionnés », « solutions sur mesure innovantes », « n'hésitez pas à », superlatifs non prouvés.
- Aucun fait inventé (`REGLES.md`, règle 5).
