# Exemple : migration d'un WordPress fictif (Atelier Exemple)

- `paquet/` contient un **paquet Eclair Export de démonstration**, fabriqué à la main au format officiel (2 articles en `/2024/03/…/`, 3 pages dont une faite avec Elementor, une catégorie, une étiquette, un auteur, une image, du SEO Yoast, une redirection de l'extension Redirection et un formulaire Contact Form 7). Ce n'est pas un vrai site.
- `brief.json` montre un brief de migration : champs du site laissés vides (lus dans le paquet), direction artistique `argile`, mentions légales importées réécrites (`remplace`), formulaire Contact Form 7 remplacé par celui d'Éclair, pied de page relié.

Tester (depuis la racine du dossier) :

```
php outils/eclair-site.php simuler exemples/migration-wordpress/brief.json
php outils/eclair-site.php migrer exemples/migration-wordpress/brief.json
```

Résultat attendu : 5 contenus importés, `✓ Anciennes adresses WordPress : 9 / 9 répondent (zéro 404)`, puis `sortie/atelier-exemple-eclair-v<version>.zip`.
