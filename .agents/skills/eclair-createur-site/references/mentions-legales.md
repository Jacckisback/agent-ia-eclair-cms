# Étape 5 — Identité, mentions légales, confidentialité

Repères établis le 8 novembre 2026. Ce sont des modèles : la personne fait relire si son activité est réglementée ou si elle vend en ligne. Sources à rouvrir en cas de doute :
- France : loi n° 2004-575 du 21 juin 2004 (LCEN), obligations d'identification de l'éditeur — article 6, III (repris à l'article 1-1 depuis la loi SREN du 21 mai 2024 ; numérotation **à vérifier** sur https://www.legifrance.gouv.fr) ; fiche officielle https://entreprendre.service-public.fr/vosdroits/F31228 ; CNIL https://www.cnil.fr.
- Espagne : Ley 34/2002 (LSSI-CE), article 10 « Información general » — https://www.boe.es/buscar/act.php?id=BOE-A-2002-13758 ; AEPD https://www.aepd.es.
- Données personnelles (UE) : RGPD, articles 13 et 14 ; en Espagne, en plus, Ley Orgánica 3/2018 (LOPDGDD) — https://www.boe.es/buscar/act.php?id=BOE-A-2018-16673

## La fiche à remplir (un seul message)

Je l'envoie adaptée au pays et au statut (je retire ce qui ne s'applique pas), avec un exemple de réponse :

**France — professionnel**
- Statut : entreprise individuelle / micro-entreprise, ou société (forme : SARL, SAS…)
- Nom et prénom (EI) ou dénomination sociale + capital social (société)
- Adresse (domicile ou siège). ⚠ Pour un entrepreneur à domicile, cette adresse sera publique (page et données Google). Je le dis et je rappelle l'option d'une domiciliation commerciale.
- Téléphone et e-mail affichés (le téléphone est exigé : un formulaire seul ne suffit pas)
- SIREN / SIRET, immatriculation (RNE, RCS + ville)
- N° de TVA intracommunautaire, ou « TVA non applicable, art. 293 B du CGI »
- Directeur ou directrice de la publication
- Profession réglementée ? (ordre, titre, pays d'obtention, règles professionnelles)
- Hébergeur : **obligatoire avant la construction**. Je trouve sa raison sociale, son adresse et son téléphone sur ses propres mentions légales et je les recopie ; si je n'y ai pas accès, je les demande. Pas encore d'hébergeur → on le choisit d'abord (étape 3).
- **Médiateur de la consommation** : obligatoire pour tout professionnel qui vend des biens **ou des services** à des particuliers (artisan, coach, commerçant…), avec ses coordonnées sur le site (Code de la consommation, art. L612-1 et suivants, à vérifier sur Légifrance). Je demande le nom et le site du médiateur auquel la personne adhère ; si elle n'en a pas, je le signale comme point à régler (ce n'est pas un détail : c'est une obligation).

**France — particulier, site non professionnel** : peut rester anonyme en ne publiant que l'identité de l'hébergeur, ses coordonnées étant connues de l'hébergeur.

**Espagne (aviso legal, art. 10 LSSI)** — s'applique dès qu'il y a une activité économique (vente, services, publicité, affiliation)
- Nom ou dénomination sociale, domicile (ou établissement en Espagne)
- NIF
- Adresse e-mail + un autre moyen de contact direct
- Données d'inscription au Registro Mercantil (ou autre registre public) si applicable
- Profession réglementée : colegio profesional **et numéro de colegiado**, titre, État de délivrance, normes professionnelles applicables (avec lien)
- Autorisation administrative préalable si l'activité en exige une

**Autre pays** : je reprends la base commune (identité, adresse, contact, immatriculation, hébergeur), je cherche la loi locale sur une source officielle et je marque « à vérifier » ce que je n'ai pas pu confirmer.

## Professions réglementées (avocat, médecin, kiné, expert-comptable, architecte…)

Je rappelle en une ligne les règles de communication de la profession et j'écris en conséquence : pas de promesse de résultat, pas de comparaison dénigrante, pas de clients ou de dossiers cités sans accord, titres exacts. En cas de doute, la personne vérifie auprès de son ordre ou colegio.

## Confidentialité (RGPD)

Questions (avec les réponses par défaut d'un site Éclair) :
- Responsable du traitement = l'éditeur (déjà connu).
- Données collectées : formulaire de contact (nom, e-mail, message) — c'est tout par défaut.
- Durée de conservation des messages : je propose « 12 mois maximum », la personne valide.
- Outil de mesure d'audience, pixel publicitaire, vidéo intégrée, chat externe ? Par défaut **aucun** : Éclair n'en installe pas, les polices sont servies par le site lui-même. Si la personne en ajoute un → bandeau de consentement nécessaire (France : recommandations CNIL ; Espagne : art. 22.2 LSSI) : je le signale comme point à traiter, je ne l'invente pas.
- Outil externe relié au site (prise de rendez-vous, paiement Stripe/PayPal) : je l'ajoute comme destinataire des données dans la politique, avec un lien vers sa propre politique.
- Droits : accès, rectification, effacement, opposition ; réclamation auprès de la CNIL (France) ou de l'AEPD (Espagne).

## Boutique (vente en ligne)

Éclair refuse d'ouvrir la boutique sans mentions légales **et** conditions générales de vente. Questions supplémentaires : pays livrés, frais et délais de livraison, délai de rétractation (14 jours minimum dans l'UE pour la vente à distance, sauf exceptions), conditions de retour, médiateur de la consommation (France : obligatoire pour vendre à des particuliers). Je rédige des CGV modèles et je recommande une relecture.

## Ce que je produis dans le brief

- Bloc `identite` (sert aussi aux données structurées LocalBusiness/Organization/Person).
- Page `mentions` (France : « Mentions légales » ; Espagne : « Aviso legal ») et page `confidentialite` (« Política de privacidad »), toutes deux `noindex`, reliées dans le menu `pied`.
- Pour une boutique : page `cgv` et page `merci`.
- Zéro passage « [À compléter] » : s'il manque une donnée obligatoire, je la redemande avant de construire.
