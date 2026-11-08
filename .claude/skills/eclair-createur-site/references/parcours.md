# Étapes 1 et 2 — Comprendre l'activité, proposer le site

## Étape 1 : une seule question

> « Décris ton activité en 2-3 phrases : ce que tu fais, pour qui, et où. Et le nom du site s'il existe déjà. »
> Exemple : « Je suis ostéopathe à Nantes, je soigne surtout des sportifs et des femmes enceintes. Cabinet : Ostéo Erdre. »

J'en tire : métier, cible, zone géographique, langue, nom, ton probable, pays (mentions légales).

## Étape 1 bis : la fiche métier (un seul message)

> « Pour écrire tes pages sans rien inventer, donne-moi en vrac (même en style télégraphique) : »
> 1. Tes services ou produits principaux (3 à 6)
> 2. Ta zone (ville, quartiers, distance, à distance ?)
> 3. Tes horaires, et si tu fais des urgences
> 4. Prix : affichés, « à partir de », ou sur devis (gratuit ?)
> 5. Délais habituels (rendez-vous, intervention, livraison)
> 6. Ce qui te distingue vraiment (expérience, méthode, matériel, labels **réels**)
> 7. Des preuves que tu peux montrer (années d'activité, certifications, avis publics avec lien)
> 8. Tes réseaux sociaux (liens)

Ce qui n'est pas donné n'apparaît pas sur le site. Une page plus courte vaut mieux qu'un fait inventé.

## Étape 2 : la proposition (un seul message)

Je présente, en court :

1. **Le type de site** (choix d'Éclair) :

| Type (brief) | Pour qui | Pages créées par défaut |
|---|---|---|
| `vitrine` | commerce, artisan, cabinet, PME | Accueil, Services (ou Produits), À propos, Contact, Blog, Mentions légales, Confidentialité |
| `freelance` | indépendant, consultant, portfolio | Accueil à son nom, Prestations, À propos, Blog, Contact, pages légales |
| `page-unique` | lancement, offre, événement | Une page de vente (bannière, atouts, FAQ, formulaire) + pages légales |
| `blog` | auteur, passionné, expert | Accueil = derniers articles, À propos, Contact, pages légales |
| `media` | site éditorial, Google Discover | comme blog, tableau de bord orienté Discover |
| `affiliation` | site de comparatifs | comme blog, gestion des liens d'affiliation |

2. **Les pages** (5 à 8 en général) et le **menu principal** (4 entrées max, titres de 22 caractères max pour le mobile).
3. **Le ton** (ex. « chaleureux et précis », « expert et rassurant »).
4. **3 directions artistiques** (voir `design.md`) : nom + une phrase d'univers chacune, la plus adaptée en premier.
5. **1 à 2 articles de départ** si le site a un blog (sujets tirés des questions que se posent ses clients).
6. Si l'activité vend en ligne : la **boutique légère** d'Éclair (liens de paiement Stripe, PayPal, Revolut Business), voir `skills-eclair.md`.
7. Une ligne sur les **robots des IA** : « Ton site sera lisible par les IA (ChatGPT, Perplexity, Google…) pour qu'elles puissent te citer, y compris pour l'entraînement de leurs modèles. Si tu préfères n'autoriser que les IA de recherche qui citent leurs sources, dis-le. » (`seo.robots_ia` : `open` ou `search`)
8. **Ce que le ZIP ne fera pas**, si la personne l'a demandé (`REGLES.md`, règle 17 bis) :
   - **Prise de rendez-vous** : pas de module prêt dans Éclair. Je mets un bouton « Prendre rendez-vous » vers son outil (Calendly, Cal.com, Google Agenda, Doctolib…) ou, à défaut, le formulaire de contact ; l'outil externe est mentionné dans la politique de confidentialité.
   - **Deuxième langue** : le moteur construit le site dans une langue. La seconde se crée ensuite dans l'administration (Éclair relie les traductions et gère les hreflang), ou avec le skill `eclair-multilingual-seo`.
   - **Panier, comptes clients** : pas dans le ZIP ; la boutique légère à liens de paiement couvre la vente de quelques dizaines de produits.

Puis une question à choix (4 options) : « Je pars là-dessus ? » → `Direction 1` / `Direction 2` / `Direction 3` / `Je veux changer quelque chose` (« Conseille-moi » = la direction 1, que je présente comme ma recommandation).

## Ce que je ne demande pas (je décide, la personne pourra changer)

Adresses des pages (courtes, en minuscules, avec le mot-clé), format des titres SEO, robots des IA (ouverts par défaut, voir `contenus-seo-geo.md`), micro-animations (actives), mode sombre (automatique), adresse de l'administration (générée).
