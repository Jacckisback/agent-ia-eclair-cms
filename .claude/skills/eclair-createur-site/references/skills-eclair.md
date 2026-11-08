# Aller chercher les skills Éclair officiels selon le besoin

Le catalogue officiel (57 skills pour agents, maintenus avec Éclair) est sur GitHub. Je le charge **seulement** quand le site a besoin de plus qu'un site vitrine, un blog ou une boutique légère.

```
git clone --depth 1 https://github.com/Jacckisback/eclair-skills outils/.cache/eclair-skills
php outils/verifier-skills.php outils/.cache/eclair-skills
```

- La vérification utilise la clé publique du mainteneur **intégrée à ce pack** (pas un script du dépôt téléchargé). Résultat différent de `✓ Authentique` → je n'utilise pas ces skills et je le dis.
- Je lis `outils/.cache/eclair-skills/AGENTS.md`, `catalog.json`, puis le `SKILL.md` du skill concerné dans `skills/<id>/`.
- Ces skills décrivent souvent un **thème** ou une **fonction** (paquet `.zip` à importer dans l'administration, activé par une personne). Je prépare le paquet dans `sortie/<slug>/a-importer/` et j'explique dans `INSTALLATION.md` comment l'importer (Thèmes ou Fonctions, droit Sécurité + mot de passe). Je ne l'active jamais à la place de la personne.
- Au 8 novembre 2026, `catalog.json` porte le statut `workflow-library-not-ready-to-deploy-commerce-lms-forum` : commerce complet, formations et forum sont des **méthodes de travail, pas des fonctions prêtes à mettre en production**. Je relis ce statut à chaque fois et je le dis à la personne avant de promettre quoi que ce soit.
- Limites annoncées par le catalogue : pas de vraie commande avec panier, LMS ou forum en écriture publique tant qu'Éclair ne fournit pas l'interface qualifiée. Je ne promets pas ce qui n'existe pas.

## Table de correspondance

| Besoin exprimé | Ce que je fais |
|---|---|
| Vendre quelques produits | **Boutique légère intégrée** (brief : `boutique`, `produits`, liens Stripe/PayPal/Revolut). Pas de skill nécessaire. Skills utiles pour aller plus loin : `eclair-product-catalog`, `eclair-product-variants` |
| Panier, commandes, stock, factures | `eclair-commerce-pro`, `eclair-cart-checkout`, `eclair-orders-payments`, `eclair-inventory`, `eclair-tax-invoicing`, `eclair-shipping` (vérifier leur statut dans `catalog.json`) |
| Rendez-vous, réservations | `eclair-appointments-calendar`, `eclair-bookings` ; sinon lien vers l'outil de réservation de la personne dans un bouton |
| Formations en ligne | `eclair-lms`, `eclair-course-builder`, `eclair-enrollment-access` |
| Espace membres | `eclair-memberships`, `eclair-auth-roles` |
| Annuaire, offres d'emploi, immobilier | `eclair-directory`, `eclair-job-board`, `eclair-real-estate` |
| Événements, billetterie | `eclair-events-ticketing` |
| Site en plusieurs langues | `eclair-multilingual-seo` (Éclair relie les traductions et produit les hreflang) |
| Reprendre un site WordPress | `eclair-wordpress-migration` (Éclair inclut aussi un import WordPress dans ses fonctions livrées) |
| Formulaires avancés, CRM | `eclair-forms-crm`, `eclair-crm-integration` |
| Thème entièrement sur mesure | `eclair-theme-builder`, `eclair-design-system` ; sinon la direction artistique suffit dans 95 % des cas |
| Audit SEO, GEO, accessibilité après mise en ligne | `eclair-seo-audit`, `eclair-geo-optimization`, `eclair-accessibility`, `eclair-structured-data` |
