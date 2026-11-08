# Agent IA Éclair CMS — Commencer ici

Un assistant qui crée ton site professionnel avec **Éclair CMS** en environ 5 minutes de questions (plus 5 à 15 minutes la toute première fois s'il faut installer PHP), et te le livre dans un fichier ZIP prêt à envoyer sur ton hébergement : pages, textes, design, images, SEO, GEO, version mobile, mentions légales, administration et mot de passe.

## Ce qu'il te faut

- **Claude Code** (application de bureau ou terminal) ou **Codex**.
- **git** (souvent déjà présent ; l'agent vérifie).
- **PHP 8.1 ou plus** sur ton ordinateur. Pas de panique : l'agent vérifie et te propose de l'installer s'il manque.
- Pour des images générées par l'IA (facultatif) : un compte **fal.ai** (recommandé), OpenAI ou Black Forest Labs, avec quelques euros de crédit. L'agent t'explique comment récupérer la clé.
- Un hébergement web avec PHP (o2switch, OVHcloud, Hostinger, IONOS…) et, idéalement, ton nom de domaine. Si tu n'en as pas encore, l'agent t'aide à choisir.

## Démarrer (1 minute)

1. Décompresse ce dossier où tu veux (ex. `Documents/Agent-IA-Eclair-CMS`).
2. Ouvre-le dans Claude Code (« Open folder ») ou lance `claude` dans ce dossier.
3. Copie-colle le contenu de `PROMPT-DEMARRAGE.txt`.
4. Réponds aux questions. À la fin, tout est dans `sortie/` : le site `<ton-site>-eclair-v<version>.zip` et, à côté, `<ton-site>-ACCES-ADMIN.txt` (adresse de l'administration et mot de passe).

## Ce que fait l'agent, dans l'ordre

1. Comprend ton activité (une question).
2. Te propose le type de site, les pages, le ton et 3 directions artistiques.
3. Règle l'adresse de ton site (domaine) et vérifie ton hébergement.
4. Prépare les images : tes photos, ou des images générées par l'IA.
5. Récupère les informations des mentions légales et de la confidentialité (France, Espagne et autres pays).
6. Crée ton compte d'administration (mot de passe solide généré).
7. Télécharge **la dernière version d'Éclair** (signature vérifiée), construit le site, contrôle chaque page (mobile, SEO, GEO, données structurées) et améliore les textes.
8. Te livre le ZIP avec un guide d'installation pas à pas.

## Bon à savoir

- Tout est construit **sur ton ordinateur**. L'agent n'envoie rien sur ton hébergement et n'achète rien à ta place.
- Tes clés d'API restent dans `projet/.env`, jamais dans le site.
- Un exemple complet est dans `exemples/fournil-des-pentes/` (boulangerie fictive).
- Un souci ? `DEPANNAGE.md`.
