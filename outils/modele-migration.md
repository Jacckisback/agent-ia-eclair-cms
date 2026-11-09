# Mettre en ligne « {site} » sur Éclair (migration depuis WordPress)

Site migré avec Éclair CMS {version}. Adresse : {url}
Administration : {admin} — e-mail et mot de passe dans le fichier `<nom-du-site>-ACCES-ADMIN.txt`, livré à côté de ce ZIP (jamais sur l'hébergement).
HTTPS : {ssl}.

Compte 20 à 30 minutes. Fais-le à un moment calme (le soir, le week-end) : le site est coupé pendant l'envoi des fichiers (de 5 à 15 minutes selon ta connexion ; plus court si tu envoies le ZIP et le décompresses chez l'hébergeur).

## 0. Avant de toucher à quoi que ce soit

- [ ] **Sauvegarde complète du WordPress** (fichiers + base de données) depuis l'espace client de ton hébergeur, ou avec ton extension de sauvegarde. Télécharge-la sur ton ordinateur.
- [ ] **Vitesse de l'ancien site** notée (pagespeed.web.dev). Après la bascule, ce ne sera plus possible.
- [ ] Hébergement compatible : **PHP 8.1 ou plus** (8.3 conseillé), Apache ou LiteSpeed. Presque tous les mutualisés le sont.

## 1A. Même hébergeur : remplacer WordPress par Éclair

1. Dans le gestionnaire de fichiers de ton hébergeur, crée un dossier **en dehors** du dossier public (par exemple à côté de `public_html`, pas dedans) nommé `ancien-wordpress`.
   ⚠ Ne le laisse pas dans le dossier public : l'ancien WordPress resterait accessible et attaquable.
2. Ouvre le `.htaccess` du WordPress et **copie les lignes qui choisissent la version de PHP** s'il y en a (elles contiennent `AddHandler`, `SetHandler` ou `php8`, souvent ajoutées par cPanel « MultiPHP » ou o2switch). Garde-les de côté.
3. Déplace **uniquement les fichiers de WordPress** dans `ancien-wordpress` : `wp-admin`, `wp-content`, `wp-includes`, les fichiers `wp-*.php`, `index.php`, `xmlrpc.php`, `license.txt`, `readme.html` et `.htaccess`. **Ne déplace pas** : `.well-known` (certificat SSL), `cgi-bin`, ni les dossiers d'autres sites ou sous-domaines rangés là. La base de données ne bouge pas.
4. Décompresse ce ZIP sur ton ordinateur, ouvre le dossier **site** et envoie **son contenu** dans le dossier public (gestionnaire de fichiers ou FTP).
   ⚠ Le fichier caché **.htaccess** doit être envoyé (affiche les fichiers cachés : Cmd + Maj + point sur Mac). Colle en haut de ce `.htaccess` les lignes PHP gardées à l'étape 2, ou choisis PHP 8.3 dans l'espace client.
5. Ouvre {url} : c'est Éclair.

**Revenir en arrière** (en cas de problème) : supprime les fichiers d'Éclair du dossier public, remets-y le contenu de `ancien-wordpress`. Le WordPress revient tel quel.

## 1B. Nouvel hébergeur : bascule du domaine

0. **48 h avant** : chez ton registraire, baisse la durée de cache des DNS (TTL) à 5 ou 10 minutes si c'est possible. Note tous les enregistrements actuels, surtout **MX** (e-mails), SPF, DKIM et les TXT de vérification.
1. Chez le nouvel hébergeur, ajoute le domaine {url} à ton offre.
2. Envoie le contenu du dossier **site** dans le dossier public de ce domaine (avec le `.htaccess`).
3. Chez ton registraire, fais pointer le domaine vers le nouvel hébergeur. ⚠ **E-mails** : si tu changes les *serveurs DNS*, recopie d'abord chez le nouvel hébergeur les enregistrements MX, SPF, DKIM et TXT notés, sinon tes e-mails s'arrêtent. Plus simple quand c'est possible : ne change que l'enregistrement **A** (et AAAA) du site. La bascule prend de quelques minutes à quelques heures.
3 bis. Dès que le site répond sur le nouvel hébergement : active le certificat SSL (Let's Encrypt), puis la redirection HTTPS dans Éclair (Réglages › Avancé).
4. **Garde l'ancien hébergement actif au moins un mois**, sans y toucher.

## 2. Premiers réglages (5 minutes)

1. Connecte-toi à {admin}.
2. **Mon profil › Double authentification** : active-la.
3. **Réglages › Général** : vérifie l'adresse du site, clique sur Enregistrer (Éclair vérifie que l'adresse mène bien au site).
4. **Réglages › Avancé** : si le cadenas HTTPS fonctionne et que ce n'est pas déjà fait, active la redirection vers HTTPS.
5. **Réglages › SEO** : vérifie que l'indexation est ouverte. La balise Google Search Console de Yoast ou Rank Math a été reprise si elle existait ; sinon, colle-la ici **avant** de déclarer le sitemap.

## 3. Vérifier la migration (10 minutes)

Dans le menu **Import WordPress** de l'administration, section **Rapport de migration pour le client** :
- [ ] **Vitesse avant/après (Google PageSpeed)** : clique sur **Mesurer** pour le nouveau site (saisis la mesure de l'ancien site notée avant la bascule si on te la demande).
- [ ] **Télécharger** le rapport (FR, ES ou EN) et lis le verdict : il doit dire qu'aucune ancienne adresse ne finit en erreur 404. Ces adresses ont déjà été contrôlées avant la livraison ; ce rapport les contrôle sur le vrai serveur.

À l'œil, ancien site (sauvegarde) et nouveau côte à côte :
- [ ] L'accueil, l'article le plus lu, une page faite avec Elementor ou Divi, une fiche produit si boutique, une page dans chaque langue si site multilingue.
- [ ] Trois anciennes adresses dans la barre du navigateur : une catégorie (`/category/…`), un ancien lien court (`/?p=123`), un PDF (`/wp-content/uploads/…pdf`).
- [ ] Le formulaire de contact : envoie-toi un message (il arrive aussi dans le menu Messages).

## 4. Google

- [ ] **Search Console › Sitemaps** : ajoute `{url}/sitemap.xml`.
- [ ] Une fois par semaine pendant 4 semaines : **Search Console › Pages** ; une nouvelle erreur 404 = une redirection à ajouter dans **Redirections**.

## 5. Ensuite

- **Fonctions › Import WordPress** : désactive-la une fois les vérifications faites (elle accepte l'envoi d'archives ; inutile de la laisser active).
- Garde la sauvegarde WordPress (et `ancien-wordpress` hors du dossier public) **au moins 3 mois**, et ne supprime rien tant que Search Console montre de nouvelles erreurs 404. Ensuite seulement : supprime-les, ainsi que la base de données WordPress.
- Ancien prestataire : résilie son contrat seulement après ces 3 mois.
- Produits importés de WooCommerce : crée leurs liens de paiement (Stripe, PayPal, Revolut Business) et colle-les dans chaque fiche (Contenus › Produits).
- Ne renvoie jamais un nouveau ZIP complet par-dessus le site en ligne : les messages reçus et tes changements seraient perdus.

Problème ? Guide d'Éclair, section Dépannage : https://github.com/Jacckisback/eclair-cms/blob/main/docs/GUIDE.md
