# Mettre en ligne « {site} »

Site construit avec Éclair CMS {version}. Adresse prévue : {url}
Compte : 15 minutes la première fois.

## 1. Préparer l'hébergement

- Hébergement avec **PHP 8.1 ou plus** (8.3 conseillé) et un serveur Apache ou LiteSpeed (presque tous les mutualisés : o2switch, OVHcloud, Hostinger, IONOS, PlanetHoster…).
- L'adresse {url} doit pointer vers cet hébergement (chez ton registraire : serveurs DNS de l'hébergeur, ou domaine ajouté dans l'espace client de l'hébergeur).
- Certificat HTTPS (gratuit, Let's Encrypt) : {ssl}. Dans l'espace client de ton hébergeur, cherche « SSL » ou « Let's Encrypt » et active-le pour ton domaine.

## 2. Envoyer les fichiers

1. Décompresse le ZIP sur ton ordinateur.
2. Ouvre le dossier **site** : c'est son **contenu** qu'il faut envoyer (pas le dossier lui-même).
3. Avec le gestionnaire de fichiers de ton hébergeur ou un logiciel FTP (FileZilla), envoie tout ce contenu dans le dossier public du domaine (`public_html`, `www` ou le dossier du domaine).
   ⚠ Le fichier **.htaccess** est caché : affiche les fichiers cachés (Mac : Cmd + Maj + point) et vérifie qu'il est bien envoyé, à côté de `index.php`. Sans lui, seul l'accueil s'affiche.
4. Droits des dossiers `data`, `cache` et `media` : 755 (souvent déjà le cas).

Astuce : beaucoup d'hébergeurs permettent d'envoyer le ZIP du dossier `site` puis de le décompresser directement en ligne (gestionnaire de fichiers › Extraire). C'est plus rapide.

## 3. Ouvrir le site et l'administration

- Site : {url}
- Administration : {admin}
- E-mail et mot de passe : dans le fichier `<nom-du-site>-ACCES-ADMIN.txt`, livré à côté de ce ZIP (jamais sur l'hébergement).

## 4. Premiers réglages (5 minutes)

1. **Mon profil › Double authentification** : active-la.
2. **Réglages › Général** : vérifie l'adresse du site et clique sur Enregistrer (Éclair vérifie alors que l'adresse mène bien à ton site).
3. **Réglages › Avancé** : si le cadenas HTTPS fonctionne et que ce n'est pas déjà fait, active la redirection vers HTTPS.
4. **Mises à jour** : Éclair te préviendra des nouvelles versions ; fais une sauvegarde avant chaque mise à jour.
5. **Google Search Console** (à relier dans Réglages › SEO) et **Bing Webmaster Tools** : déclare le site et envoie le plan du site ({url}/sitemap.xml).

## 4 bis. Tester le formulaire

Envoie-toi un message depuis la page Contact : il doit apparaître dans l'administration (menu Messages) et, en principe, par e-mail (regarde les indésirables). Le message de l'administration fait toujours foi.

## 5. Ensuite

- Ajouter un article, changer un texte : Contenus, dans l'administration (Mode Visuel pour modifier directement sur la page).
- Assistant IA d'Éclair (Réglages › IA) et connecteur Claude/ChatGPT (MCP) : pour écrire et améliorer tes pages avec ton propre abonnement.
- Ne renvoie jamais un nouveau ZIP complet par-dessus un site en ligne : tes messages reçus et tes changements seraient perdus.

Problème ? Guide complet d'Éclair : https://github.com/Jacckisback/eclair-cms/blob/main/docs/GUIDE.md (section Dépannage).
