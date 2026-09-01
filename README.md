# GeoScan

Petit outil de renseignement réseau (OSINT) en Laravel qui scrape les pages
publiques HTML de [shodan.io](https://shodan.io) — pas l'API payante — et
conserve un historique de tout ce qui a été consulté.

Projet pédagogique (IT-Akademy, séquence D11 — Laravel) : l'objectif est de
maîtriser le scraping HTML (requêtes HTTP, parsing DOM), pas l'API Shodan.

## Ce que fait l'application

Deux parcours :

1. **Recherche** — tu tapes une requête (`apache`, `nginx`, `webcam`…),
   l'appli va chercher `shodan.io/search?query=...`, en extrait le nombre
   total de résultats et les 5 classements « Top » (pays, ports,
   organisations, produits, systèmes d'exploitation), et **archive** le
   résultat en base. Consulter cette recherche plus tard (page *Historique*)
   ne relance **jamais** de requête vers Shodan : c'est une relecture pure de
   ce qui a été enregistré.

2. **Fiche hôte** — tu tapes une IP, l'appli va chercher
   `shodan.io/host/{ip}` (pays, ville, organisation, FAI, ASN, noms d'hôte,
   domaines, technologies web, ports ouverts, coordonnées GPS) et en garde
   un **instantané** horodaté. Si un instantané pour cette IP existe déjà
   depuis moins de quelques minutes (cooldown configurable), il est
   réutilisé au lieu de refaire une requête. La page affiche l'instantané
   le plus récent — avec une petite carte OpenStreetMap positionnant l'IP —
   plus une ligne du temps de tous les précédents — utile pour voir un hôte
   changer d'organisation ou de ports ouverts entre deux visites.

> ⚠️ Sans compte Shodan connecté, les filtres de recherche avancés
> (`country:`, `org:`, …) sont refusés par Shodan lui-même — utilise des
> requêtes libres (`apache`, `nginx`…) pour la démo. Voir *Aller plus loin*
> ci-dessous.

### Schéma de données

Quatre tables, avec l'entité stable séparée de son historique :

| Table             | Rôle                                                          |
|--------------------|----------------------------------------------------------------|
| `searches`         | Une ligne par recherche lancée (requête, total, date)          |
| `search_rankings`  | Les classements « Top » attachés à chaque recherche            |
| `hosts`            | Une ligne par IP jamais vue plus d'une fois                    |
| `host_snapshots`   | Une nouvelle ligne à **chaque visite** d'une fiche hôte (jamais de mise à jour en place) |

### Politique de crawl

`shodan.io/robots.txt` autorise tout sauf `/domain/*` (l'appli n'y touche
jamais), avec un `Crawl-delay: 10`. En conséquence :

- chaque requête envoie un `User-Agent` identifiable (`SHODAN_USER_AGENT`) ;
- un délai minimum (`SHODAN_MIN_DELAY_SECONDS`, 10s par défaut) est respecté
  entre deux requêtes sortantes, quel que soit l'endroit de l'appli qui les
  déclenche (voir [`ShodanHttpClient`](app/Services/Shodan/ShodanHttpClient.php)).

## Installation

Prérequis : PHP 8.3+, Composer. SQLite suffit, pas de serveur de base de
données à lancer.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Lancer l'application

```bash
php artisan serve
```

Puis ouvre [http://127.0.0.1:8000](http://127.0.0.1:8000) — tu es redirigé
vers le formulaire de recherche.

- **Nouvelle recherche** : tape une requête libre (ex. `apache`) et lance-la.
- **Historique** : liste toutes les recherches déjà archivées ; cliquer sur
  une entrée la réaffiche telle qu'enregistrée, sans nouvelle requête.
- **Fiche hôte : IP** (en haut à droite) : tape une IP (ex. `8.8.8.8`) pour
  voir sa fiche et sa ligne du temps.

## Lancer les tests

```bash
php artisan test
```

Les tests ne font **jamais** de vraie requête vers shodan.io : ils rejouent
soit des pages HTML réelles sauvegardées (`tests/Fixtures/`), soit des
réponses simulées via `Http::fake()`. Ils couvrent :

- le parsing de la page de recherche (`tests/Unit/Services/Shodan/SearchScraperTest.php`) ;
- le parsing de la fiche hôte (`tests/Unit/Services/Shodan/HostScraperTest.php`) ;
- la persistance d'une recherche archivée, y compris qu'un replay depuis
  l'historique ne déclenche aucune requête (`tests/Feature/SearchServiceTest.php`) ;
- le garde-fou de cooldown sur les fiches hôte (`tests/Feature/HostSnapshotServiceTest.php`) ;
- la connexion à un compte Shodan et son cache de session (`tests/Feature/ShodanSessionTest.php`).

## Configuration (`.env`)

| Variable | Défaut | Rôle |
|---|---|---|
| `SHODAN_BASE_URL` | `https://www.shodan.io` | Racine des URLs scrapées |
| `SHODAN_USER_AGENT` | `GeoScanBot/1.0 (...)` | En-tête `User-Agent` envoyé |
| `SHODAN_MIN_DELAY_SECONDS` | `10` | Délai minimum entre deux requêtes sortantes |
| `SHODAN_REQUEST_TIMEOUT_SECONDS` | `30` | Timeout HTTP |
| `SHODAN_SNAPSHOT_COOLDOWN_MINUTES` | `5` | Durée pendant laquelle un instantané d'hôte est réutilisé au lieu d'être rescrapé |
| `SHODAN_CA_BUNDLE` | *(vide)* | À ne renseigner que si ton `php.ini` a un `curl.cainfo` cassé (voir plus bas) |

### Aller plus loin : compte Shodan connecté

Sans connexion, Shodan refuse les filtres de recherche (`country:`, `org:`,
`port:`…) et bloque déjà la page 2 des résultats (« Please create a Shodan
account and log in to access more results. »). Pour lever ces limites,
l'appli peut se connecter à un **vrai compte Shodan** avec tes identifiants :

```env
SHODAN_LOGIN_ENABLED=true
SHODAN_EMAIL=ton-email@exemple.com
SHODAN_PASSWORD=ton-mot-de-passe
```

Ce que ça fait concrètement ([`ShodanSession`](app/Services/Shodan/ShodanSession.php)) :
récupère le jeton CSRF sur `account.shodan.io/login`, poste tes identifiants,
garde les cookies de session obtenus (mis en cache 6h), et
[`ShodanHttpClient`](app/Services/Shodan/ShodanHttpClient.php) les attache
ensuite à chaque requête vers `www.shodan.io`.

**Statut** : le formulaire de connexion a été analysé sur une vraie page
(champs, jeton CSRF), mais **le POST de connexion en lui-même n'a pas pu
être testé en direct avec un vrai compte** pendant le développement — teste-le
avec tes propres identifiants, et si Shodan répond autrement que prévu (une
vérification captcha/2FA, par exemple), il faudra ajuster
`ShodanSession::looksLoggedIn()`. Tes identifiants ne sont envoyés qu'au
formulaire de connexion officiel de Shodan, jamais ailleurs, et ne quittent
jamais ton `.env` local (jamais commité).

### Aller plus loin : carte de la fiche hôte

La fiche hôte affiche une carte [OpenStreetMap](https://www.openstreetmap.org)
(pas besoin de clé API) positionnant l'IP — les coordonnées sont récupérées
directement dans le script Mapbox que Shodan intègre déjà dans la page HTML
(`HostScraper::parseCoordinates()`). Ça ne dépend pas du compte connecté :
ça marche même en anonyme, dès que Shodan expose une position pour l'hôte.

### Problème connu : `cURL error 77` sous Windows/Laragon

Si `php artisan serve` renvoie une erreur 500 dès qu'une requête sort vers
Shodan, avec dans `storage/logs/laravel.log` un message du type
`cURL error 77: error setting certificate file`, c'est que le `curl.cainfo`
de ton `php.ini` pointe vers un chemin qui n'existe pas sur cette machine
(typique après un déplacement d'installation Laragon). Deux options :

1. Corriger `curl.cainfo` dans le `php.ini` utilisé par `php -i` (chemin
   affiché en haut de la sortie) — la vraie correction, à l'échelle de la
   machine.
2. Renseigner `SHODAN_CA_BUNDLE` dans `.env` avec le chemin d'un
   `cacert.pem` valide sur ta machine (ex. `C:/laragon/etc/ssl/cacert.pem`)
   — contournement local, propre à ce projet.
