# Mises à jour

## 1.1.0 — 9 novembre 2026
- **Migration WordPress → Éclair** : première question « nouveau site ou migration », parcours guidé avec l'extension Eclair Export (installation, diagnostic, mesure de vitesse, export), commandes `simuler` et `migrer` du moteur, import par la fonction officielle d'Éclair, contrôle de chaque ancienne adresse (aucun ZIP livré tant qu'une adresse finit en 404), codes courts remplacés (formulaires → bloc Contact d'Éclair), pages importées réécrites sans doublon, redirections manuelles, guide de bascule (même hébergeur ou nouveau, retour en arrière).
- Les anciennes adresses des fichiers (`/wp-content/uploads/…`, miniatures, PDF) sont redirigées vers les médias d'Éclair et contrôlées, comme le flux et les liens `?p=`.
- Livraison bloquée si : ancienne adresse en 404 ou renvoyée vers l'accueil, code court visible, page légale à remplacer introuvable, pied de page vide, entrée de menu introuvable, redirection masquée par une page en ligne.
- Brief de migration : modèle dédié, `modifications` (dépublier, ajouter un bloc, lien de paiement d'un produit importé), pages remplacées en gardant langue et traductions, balise Search Console reprise, langue et fuseau toujours lus dans l'export.
- Export vérifié (format, empreintes, parties) avant toute installation ; `simuler` ne touche plus à `sortie/`.
- Guide de bascule : fichiers WordPress seulement, version de PHP du `.htaccess`, e-mails (MX) et TTL, ordre certificat → HTTPS, désactivation de l'import après contrôle.
- Testée avec Éclair 2.1.0.

## 1.0.0 — 8 novembre 2026
- Première version : création d'un site Éclair CMS de A à Z et livraison en ZIP.
