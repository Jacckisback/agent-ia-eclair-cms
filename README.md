# Agent IA Éclair CMS

**Crée ton site professionnel de A à Z avec Claude Code ou Codex.** L'agent te pose environ 5 minutes de questions, puis construit ton site complet sur ton ordinateur avec [Éclair CMS](https://eclaircms.org) : pages, textes, design, images, SEO, GEO, version mobile, mentions légales et compte d'administration. Il te livre **un fichier ZIP prêt à envoyer** sur ton hébergement.

Version 1.0.0 · testée avec Éclair 2.0.0 · présentation complète : [eclaircms.org/agent-ia](https://eclaircms.org/agent-ia)

## Installer l'agent

### Option 1 : télécharger le ZIP (le plus simple)

1. Télécharge [`Agent-IA-Eclair-CMS-v1.0.0.zip`](https://github.com/Jacckisback/agent-ia-eclair-cms/raw/main/dist/Agent-IA-Eclair-CMS-v1.0.0.zip) (99 Ko, empreinte SHA-256 dans [`dist/SHA256SUMS.txt`](dist/SHA256SUMS.txt)), ou clique sur le bouton vert **Code › Download ZIP** de cette page.
2. Décompresse-le où tu veux, par exemple dans `Documents/Agent-IA-Eclair-CMS` (avec **Code › Download ZIP**, le dossier s'appelle `agent-ia-eclair-cms-main` : tu peux le renommer).

### Option 2 : cloner le dépôt

```bash
git clone https://github.com/Jacckisback/agent-ia-eclair-cms.git
cd agent-ia-eclair-cms
```

## Lancer la création de ton site

1. Ouvre le dossier dans **Claude Code** (« Open folder ») ou lance `claude` dans ce dossier. Avec **Codex**, ouvre-le de la même façon.
2. Colle ce message (il est aussi dans `PROMPT-DEMARRAGE.txt`) :

   ```
   Bonjour ! Lis CLAUDE.md puis REGLES.md, et lance le parcours de création de mon site Éclair CMS. Pose-moi les questions une par une.
   ```

3. Réponds aux questions. À la fin, tout est dans `sortie/` : le ZIP du site et, à côté, le fichier d'accès à l'administration.

## Ce qu'il te faut

- **Claude Code** (application de bureau ou terminal) ou **Codex**.
- **PHP 8.1 ou plus** sur ton ordinateur : l'agent vérifie et te propose de l'installer s'il manque.
- **git**, souvent déjà présent : l'agent vérifie aussi.
- Facultatif : un compte **fal.ai** (recommandé), OpenAI ou Black Forest Labs avec quelques euros de crédit, pour des images générées par l'IA.
- Un **hébergement web avec PHP** (o2switch, OVHcloud, Hostinger, IONOS…).

## Les 8 étapes

1. Ton activité, en 2-3 phrases.
2. La proposition : type de site, pages, ton et 3 directions artistiques, à valider d'un clic.
3. L'adresse du site et la vérification de l'hébergement.
4. Les images : tes photos ou des images générées par l'IA.
5. Les mentions légales et la confidentialité, adaptées à ton pays.
6. Ton compte d'administration, avec un mot de passe solide.
7. La construction : téléchargement vérifié de la dernière version d'Éclair, contrôle de chaque page (mobile, SEO, GEO, données structurées), amélioration des textes.
8. La livraison : le ZIP et un guide d'installation en 5 étapes.

## Ce que l'agent ne fait jamais

- Inventer un prix, un avis, un label ou une année d'expérience.
- Mettre tes clés d'API dans le site : elles restent dans `projet/.env`.
- Mettre le mot de passe dans le ZIP : il est dans un fichier à côté.
- Envoyer quoi que ce soit sur ton hébergement, acheter un domaine ou installer un logiciel sans ton accord.

## Contenu du dépôt

| Fichier ou dossier | Rôle |
|---|---|
| `COMMENCER-ICI.md` | Le guide de démarrage |
| `CLAUDE.md`, `AGENTS.md`, `REGLES.md` | Les instructions de l'agent pour Claude Code et Codex |
| `.claude/skills/`, `.agents/skills/` | La méthode complète (parcours, design, images, SEO et GEO, mentions légales, livraison) |
| `outils/` | Le moteur de construction et de contrôle (PHP) |
| `projet/` | Ton brief et ta progression |
| `exemples/fournil-des-pentes/` | Un exemple complet (boulangerie fictive) |
| `DEPANNAGE.md` | Les solutions aux problèmes courants |
| `dist/` | Le ZIP prêt à télécharger et son empreinte |

## Liens

- Éclair CMS : [eclaircms.org](https://eclaircms.org) · [dépôt GitHub](https://github.com/Jacckisback/eclair-cms)
- Guide pas à pas : [Créer un site de A à Z avec l'Agent IA Éclair CMS](https://eclaircms.org/blog/creer-site-a-z-agent-ia-eclair-cms)

Auteur : Thierry Gustin — GustinAI
