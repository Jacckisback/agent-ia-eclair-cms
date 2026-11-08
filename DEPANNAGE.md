# Dépannage

| Problème | Solution |
|---|---|
| `php: command not found` | PHP n'est pas installé : demande à l'agent de l'installer (`.claude/skills/eclair-createur-site/references/preparation.md`). Mac : `brew install php`. |
| `Extension PHP « zip » absente` (ou curl, mbstring…) | Mac (Homebrew) : déjà incluses. Windows : dans `php.ini`, retire le `;` devant `extension=zip` (idem curl, mbstring, gd, sodium). Linux : `sudo apt install php-zip php-curl php-mbstring php-xml php-gd`. |
| `Téléchargement impossible` | Pas d'Internet ou GitHub bloqué. Télécharge `eclair-cms-x.y.z.zip` depuis https://github.com/Jacckisback/eclair-cms (dossier `dist`), puis : `php outils/eclair-site.php construire projet/brief.json --zip=chemin/vers/le/fichier.zip`. |
| `Signature de l'annonce invalide` | Ne pas contourner : l'archive annoncée n'est pas celle du mainteneur. Réessaie plus tard ou signale-le sur le dépôt d'Éclair. |
| `Installation refusée : mot de passe…` | Le mot de passe choisi ne passe pas les contrôles d'Éclair (trop court, contient ton nom…). Laisse `admin.mot_de_passe` vide : il sera généré. |
| `Site NON livré` / `BLOQUANT — [À compléter]` | Une information manque (souvent dans les mentions légales) : aucun ZIP n'est produit. Réponds à la question de l'agent, il reconstruit. |
| `Archive fournie inconnue` | Le fichier passé avec `--zip` n'est pas une version publiée d'Éclair. Retélécharge-la depuis le dépôt officiel. |
| Les messages du formulaire n'arrivent pas par e-mail | Ils sont toujours dans l'administration, menu Messages. Pour l'e-mail : vérifie les indésirables et que l'adresse du site est bien réglée (Réglages › Général). |
| Images : `HTTP 401` / `403` | Clé fausse ou crédit épuisé chez le fournisseur. Vérifie `projet/.env` et ton tableau de bord (fal.ai : https://fal.ai/dashboard/keys). |
| Une icône d'atout ne s'affiche pas | Émoji sans icône dans Éclair : l'agent en choisit un de la liste (`references/design.md`). |
| En ligne, seul l'accueil fonctionne (erreur 404 ailleurs) | Le fichier caché `.htaccess` n'a pas été envoyé : affiche les fichiers cachés et envoie-le à côté de `index.php`. |
| En ligne, page « Éclair a besoin de PHP 8.1 » | Change la version de PHP dans l'espace client de ton hébergeur (8.3 conseillé). |
| En ligne, boucle de redirection ou erreur HTTPS | Le certificat SSL n'est pas encore actif alors que le site force HTTPS : active Let's Encrypt chez l'hébergeur et attends quelques minutes. Si tu ne peux pas l'activer, demande à l'agent de reconstruire avec `domaine.ssl = "non"`, puis active la redirection HTTPS plus tard dans Réglages › Avancé. |
| Mot de passe d'administration perdu | Guide d'Éclair, section Dépannage : https://github.com/Jacckisback/eclair-cms/blob/main/docs/GUIDE.md |
