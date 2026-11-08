# Étape 3 — L'adresse du site (domaine) et l'hébergement

## La question

> « As-tu déjà l'adresse de ton site (ex. monentreprise.fr) ? »
> `Oui, je l'ai` / `Non, propose-moi des noms` / `Je verrai plus tard`

### « Oui, je l'ai »
Je demande l'adresse exacte, puis : « Chez quel hébergeur est (ou sera) ton site ? » (`o2switch` / `OVHcloud` / `Hostinger` / `Autre ou je ne sais pas`).
Puis : « Le cadenas HTTPS est-il déjà actif sur cette adresse ? » (`Oui` / `Non` / `Je ne sais pas`) → `domaine.ssl` (`oui`, `non`, `inconnu`). Si ce n'est pas `oui`, le site est construit avec HTTPS non forcé et le guide d'installation explique comment l'activer puis le forcer.

### « Propose-moi des noms »
- Je propose 5 noms courts : le nom de l'activité, une variante avec la ville, une avec le métier ; sans tiret si possible ; extension du pays (`.fr`, `.es`…) en premier, `.com` en second.
- Je ne prétends pas qu'un nom est libre : la personne vérifie et achète chez un registraire ou directement chez son hébergeur (souvent inclus la première année). Je n'achète rien à sa place.
- Une fois le nom choisi, je l'inscris dans `domaine.url` (avec `https://`, et `www.` si la personne le veut) et `domaine.statut = "a-acheter"`.

### « Plus tard »
Je construis avec `domaine.url` vide. Après l'envoi sur l'hébergement, l'adresse se règle dans **Réglages › Général** (le guide le rappelle). Le SEO fonctionne, mais l'adresse canonique et le plan du site ne seront justes qu'après ce réglage.

## Hébergement compatible

Éclair demande : **PHP 8.1 ou plus** (8.3 conseillé), extensions `mbstring`, `json`, `dom`, `session`, `zlib` (recommandées : `gd`, `zip`, `curl`, `sodium`), un serveur **Apache ou LiteSpeed** (lecture du `.htaccess`), et HTTPS. C'est le cas de presque tous les hébergements mutualisés. Pas de base de données nécessaire.

Si la personne n'a pas d'hébergeur : je cite 3 hébergeurs mutualisés courants de son pays sans en promettre les prix (ils changent) et je lui conseille de vérifier « PHP 8.3 » et « certificat SSL gratuit (Let's Encrypt) » dans l'offre. Nginx seul : possible mais demande une configuration (voir le guide d'Éclair, section Nginx).

## Ce que le moteur en fait

- `site.url` des réglages = `domaine.url` (adresse canonique, sitemap, JSON-LD, llms.txt).
- `security.https` (redirection vers HTTPS) activé seulement si `domaine.ssl = "oui"`.
- IndexNow activé si le site est indexable et en HTTPS.
- L'adresse est « vérifiée » par Éclair une fois le site en ligne (en enregistrant Réglages › Général) : le guide d'installation le rappelle.
