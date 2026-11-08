# Étape 8 — Construire, regarder, livrer

## Construire

```
php outils/images.php projet/brief.json        # si images IA, puis je regarde chaque image
php outils/eclair-site.php construire projet/brief.json
```

Sortie attendue : lignes `✓`, éventuellement des `⚠` (je corrige le brief et relance), puis `✓ Contrôle qualité : aucun problème détecté` et `✓ ZIP prêt`. Une ligne **BLOQUANT** = jamais livrée en l'état.

## Regarder le site (si un navigateur est disponible)

```
php -S 127.0.0.1:8765 -t sortie/<slug>/site outils/routeur.php
```

J'ouvre `http://127.0.0.1:8765/` en largeur 390 px (mobile) puis 1366 px, je vérifie : menu lisible sans débordement, bannière, images, contrastes, boutons, pied de page avec les pages légales. Si le site force HTTPS (`domaine.ssl = oui`), j'ajoute l'en-tête `X-Forwarded-Proto: https` aux requêtes locales, sinon le serveur local redirige. J'arrête le serveur ensuite. Sans navigateur, je m'en tiens au rapport du moteur.

## Contenu du ZIP

```
sortie/
├── <slug>-eclair-v<version>.zip
│   └── <slug>/
│       ├── site/                 ← son contenu va dans le dossier public de l'hébergement
│       ├── INSTALLATION.md       ← les étapes, adaptées à ce site
│       ├── RAPPORT-QUALITE.json  ← contrôle de chaque page
│       └── ANALYSE-SEO-GEO.json  ← scores et pistes d'amélioration
└── <slug>-ACCES-ADMIN.txt        ← adresse de l'administration, e-mail, mot de passe : HORS du ZIP, jamais en ligne
```

## Message de livraison (court)

1. Le ZIP `sortie/<slug>-eclair-v<version>.zip` et le fichier d'accès `sortie/<slug>-ACCES-ADMIN.txt` (je les envoie avec l'outil d'envoi de fichiers s'il existe).
2. L'adresse de l'administration (le mot de passe est dans le fichier d'accès, je ne l'écris pas dans la conversation).
3. Les 5 étapes d'installation (résumé de `INSTALLATION.md`).
4. Les scores SEO/GEO des pages principales.
5. 3 priorités au maximum pour la suite (ex. activer la double authentification, déclarer le site dans Google Search Console, publier un article par mois).

Puis mise à jour de `projet/PROGRESSION.md`.

## Modifier le site plus tard

- **Avant la mise en ligne** : je modifie le brief et je reconstruis (nouveau ZIP, nouveau mot de passe sauf si `admin.mot_de_passe` est fixé — je préviens la personne).
- **Après la mise en ligne** : ne jamais renvoyer un nouveau ZIP complet par-dessus (les messages reçus, comptes et modifications faites en ligne seraient écrasés). Les changements se font dans l'administration d'Éclair, avec son assistant IA intégré ou le connecteur MCP (Claude, ChatGPT), et le skill Éclair Design pour l'apparence.
- **Mises à jour d'Éclair** : en un clic dans l'administration (Mises à jour), après une sauvegarde.
