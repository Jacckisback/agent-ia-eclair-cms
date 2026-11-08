---
name: eclair-createur-site
description: "Crée un site professionnel complet avec Éclair CMS en 5 minutes de questions : type de site, pages, textes, direction artistique, images IA (fal.ai, OpenAI, FLUX), SEO, GEO, mobile, mentions légales FR/ES, compte d'administration, puis livre un ZIP prêt à envoyer sur l'hébergement. À utiliser dès qu'on veut créer, refaire ou compléter un site Éclair CMS."
---

# Créer un site Éclair CMS de A à Z

Règles : `REGLES.md` (prioritaires). Exemple complet à imiter : `exemples/fournil-des-pentes/brief.json`.

Le principe : je pose peu de questions, je **déduis et je propose**, la personne valide. Tout est écrit dans `projet/brief.json` (format : `references/brief.md`), puis le moteur construit le site réel :

```
php outils/images.php projet/brief.json          # images IA (si choisi)
php outils/eclair-site.php construire projet/brief.json
```

Le moteur télécharge **la dernière version d'Éclair** (annonce signée Ed25519 + empreinte SHA-256 vérifiées), l'installe avec l'assistant officiel d'Éclair sur un serveur local, applique le brief (pages, articles, produits, images, menus, réglages SEO/GEO, direction artistique, profil d'auteur, CSS de finition), contrôle chaque page (mobile, H1 unique, description, JSON-LD, sitemap, robots.txt, llms.txt, aucun « [À compléter] ») puis produit `sortie/<slug>-eclair-v<version>.zip`.

## Le parcours (cible : 5 minutes de questions)

| Étape | Ce que je fais | Référence |
|---|---|---|
| 0. Préparation (silencieuse) | `php -v` (8.1+ avec curl, zip, mbstring), `php outils/eclair-site.php version`. PHP absent → je propose de l'installer (accord demandé). Ancien projet → archivé. | `references/preparation.md` |
| 1. L'activité | **Une seule question ouverte** : « Décris ton activité en 2-3 phrases : ce que tu fais, pour qui, où — et le nom du site s'il existe. » | `references/parcours.md` |
| 1 bis. La fiche métier | Une liste de 6 à 8 faits à donner en un message (services, zone, horaires, prix ou devis, délais, ce qui la distingue, preuves réelles). Sans ces faits, je ne peux pas écrire des pages complètes sans inventer. | `references/parcours.md` |
| 2. Ma proposition | En un message : type de site, liste des pages, menu, ton, langue, + **3 directions artistiques** au choix. La personne valide ou corrige d'un mot. | `references/parcours.md`, `references/design.md` |
| 3. L'adresse du site | Domaine : déjà acheté / à acheter (je propose des noms) / plus tard. Hébergeur, HTTPS. | `references/domaine-hebergement.md` |
| 4. Les images | Ses photos / images générées par l'IA (fournisseur et clé) / sans images. | `references/images.md` |
| 5. Identité et mentions légales | Une fiche courte, adaptée au pays et au statut, à remplir en un message. | `references/mentions-legales.md` |
| 6. Le compte d'administration | Nom, e-mail, fonction et courte bio (page auteur, E-E-A-T). Mot de passe généré solide. | `references/brief.md` |
| 7. Construction | Je rédige tous les textes (règles SEO/GEO), j'écris le brief, je génère les images, je construis, je lis `sortie/<slug>/ANALYSE-SEO-GEO.json` et j'améliore les textes jusqu'aux objectifs (2 passes max). | `references/contenus-seo-geo.md` |
| 8. Livraison | ZIP + 5 étapes d'installation + premiers réglages. | `references/livraison.md` |

Besoin au-delà d'un site vitrine/blog (boutique, réservations, formations, espace membres, multilingue, migration WordPress) → je vais chercher le skill Éclair officiel correspondant : `references/skills-eclair.md`.

## Façon de questionner

- Une question par message, avec choix cliquables (outil de question si disponible) + « Conseille-moi ».
- 4 choix au maximum, « Conseille-moi » compris.
- Exceptions autorisées pour tenir les 5 minutes : la **fiche métier (1 bis)**, la **proposition de l'étape 2** (un seul message à valider) et la **fiche légale de l'étape 5** (champs à remplir d'un coup, avec un exemple de réponse).
- Si la personne répond « je ne sais pas » : je choisis l'option la plus sûre, je le dis en une ligne, je continue.

## Après chaque construction

1. Je lis la sortie du moteur : chaque ligne `⚠` est corrigée dans le brief, puis je reconstruis.
2. Je lis `ANALYSE-SEO-GEO.json` : objectifs dans `references/contenus-seo-geo.md`.
3. Si un navigateur est disponible, je regarde l'accueil en mobile (390 px) et en ordinateur : `references/livraison.md`, section « Regarder le site ».
4. Je mets à jour `projet/PROGRESSION.md` (fait / reste / prochaine action).

## Sortie

Je montre : le chemin du ZIP et celui du fichier d'accès (à côté du ZIP), l'adresse de l'administration, les scores SEO/GEO des pages principales, les 3 priorités au maximum pour la suite. Je n'affiche jamais le mot de passe dans la conversation : il est dans `sortie/<slug>-ACCES-ADMIN.txt`.

Si le moteur s'arrête avec « Site NON livré » (passage « [À compléter] »), je pose la question qui manque, je complète le brief et je relance : aucun ZIP n'est produit tant que ce n'est pas réglé.
