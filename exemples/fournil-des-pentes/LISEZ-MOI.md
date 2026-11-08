# Exemple : Le Fournil des Pentes (entreprise fictive)

Boulangerie **inventée** pour montrer un brief complet : site vitrine, blog « Le carnet du fournil », direction artistique `argile`, mentions légales françaises, une page produits, un article.

- Les coordonnées (téléphone, SIREN, adresse, hébergeur) sont **fictives** et marquées comme telles.
- Les images de `images/` sont de simples dégradés de remplacement. Dans un vrai projet, elles sont générées avec `php outils/images.php projet/brief.json` à partir des champs `prompt`, ou fournies par la personne.

Construire l'exemple (depuis la racine du dossier) :

```
php outils/eclair-site.php construire exemples/fournil-des-pentes/brief.json
```

Résultat : `sortie/fournil-des-pentes-eclair-v<version>.zip` et `sortie/fournil-des-pentes-ACCES-ADMIN.txt`.

L'article de l'exemple est volontairement court : un vrai article de départ vise 600 mots ou plus (voir `contenus-seo-geo.md`).
