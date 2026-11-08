# Étape 0 — Préparation (sans déranger la personne)

## Vérifications

```
php -v
php -m | grep -Ei '^(curl|zip|mbstring|dom|zlib|json|sodium|gd)$'
php outils/eclair-site.php version
```

- **PHP 8.1 ou plus** avec `curl`, `zip`, `mbstring`, `dom`, `zlib`, `json` : indispensables au moteur. `gd` : recommandé (images WebP, redimensionnement). `sodium` : recommandé (vérification de la signature d'Éclair ; sans elle, seule l'empreinte SHA-256 est vérifiée).
- `git` (pour charger les skills Éclair Design et le catalogue) : `git --version`. Absent → `xcode-select --install` (Mac), `winget install Git.Git` (Windows). Sans git, je continue sans ces skills : les règles de `design.md` suffisent.
- La commande `version` affiche la dernière version publiée d'Éclair. Si elle échoue (pas d'Internet, GitHub bloqué) : la personne peut télécharger `eclair-cms-<version>.zip` depuis https://github.com/Jacckisback/eclair-cms (dossier `dist/`) et je construis avec `--zip=chemin/vers/eclair-cms-x.y.z.zip`.

## PHP absent ou trop ancien

Je l'explique en une phrase (« Pour fabriquer ton site sur ton ordinateur, j'ai besoin de PHP, un petit programme gratuit. Je l'installe ? ») et j'attends l'accord.

| Système | Commande |
|---|---|
| macOS | `brew install php` (si `brew` est absent : installer Homebrew depuis https://brew.sh, la personne colle la commande et tape son mot de passe Mac) |
| Windows | `winget install PHP.PHP.8.3`, rouvrir le terminal. winget n'active pas `php.ini` : je le crée moi-même (avec accord) en copiant `php.ini-development` en `php.ini` dans le dossier de PHP (`php --ini` le donne), puis je retire le `;` devant `extension_dir = "ext"` et devant `extension=curl`, `zip`, `mbstring`, `gd`, `sodium`, `openssl`. Je revérifie avec `php -m`. |
| Linux (Debian/Ubuntu) | `sudo apt install php-cli php-curl php-zip php-mbstring php-xml php-gd` |

Après installation : je relance `php -v` pour vérifier.

Je préviens honnêtement : l'installation de PHP (et de Homebrew sur Mac) peut prendre 5 à 15 minutes la première fois ; ensuite chaque site ne demande que les questions.

## Ancien projet

Si `projet/brief.json` contient déjà un site (`site.nom` non vide) et que la personne en démarre un nouveau : je copie `projet/brief.json`, `projet/PROGRESSION.md` et `projet/images/` dans `projet/archives/<AAAA-MM-JJ>-<slug>/`, puis je repars du modèle vierge `projet/brief.modele.json`.
