# GeoScan

Petit outil de renseignement réseau (OSINT) en Laravel qui scrape les pages
publiques HTML de [shodan.io](https://shodan.io) — pas l'API payante — et
conserve un historique de tout ce qui a été consulté.

Projet pédagogique (IT-Akademy, séquence D11 — Laravel) : l'objectif est de
maîtriser le scraping HTML (requêtes HTTP, parsing DOM), pas l'API Shodan.

## Ce que fait l'application

Quatre parcours :

1. **Recherche** — tu tapes une requête (`apache`, `nginx`, `webcam`…),
   l'appli va chercher `shodan.io/search?query=...`, en extrait le nombre
   total de résultats et les 5 classements « Top » (pays, ports,
   organisations, produits, systèmes d'exploitation), et **archive** le
   résultat en base. Consulter cette recherche plus tard (page *Historique*)
   ne relance **jamais** de requête vers Shodan : c'est une relecture pure de
   ce qui a été enregistré.

   La page de résultats liste aussi individuellement chaque IP trouvée (une
   dizaine, la limite anonyme). Pour chacune, l'appli va chercher sa fiche
   hôte (`shodan.io/host/{ip}`, avec le même cooldown que le parcours *Fiche
   hôte*) afin d'obtenir sa position GPS **exacte** — contrairement à la
   carte des pays (juste en dessous du total), qui n'est qu'une
   approximation par centroïde de pays puisque la page de recherche ne
   donne que pays + ville en texte, jamais de coordonnées. Conséquence
   directe de la politique de crawl ci-dessous : une recherche peut prendre
   **plusieurs minutes** (délai de 10s × jusqu'à 10 fiches hôte en plus de
   la recherche elle-même) ; un résultat dont la fiche hôte échoue à
   scraper est simplement absent de la carte, sans faire échouer la
   recherche.

2. **Fiche hôte** — tu tapes une IP, l'appli va chercher
   `shodan.io/host/{ip}` (pays, ville, organisation, FAI, ASN, noms d'hôte,
   domaines, technologies web, ports ouverts, coordonnées GPS) et en garde
   un **instantané** horodaté. Si un instantané pour cette IP existe déjà
   depuis moins de quelques minutes (cooldown configurable), il est
   réutilisé au lieu de refaire une requête. La page affiche l'instantané
   le plus récent — avec une petite carte OpenStreetMap positionnant l'IP —
   plus une ligne du temps de tous les précédents — utile pour voir un hôte
   changer d'organisation ou de ports ouverts entre deux visites.

3. **Carte** (`/map`) — filtre **pays / ville / port** sur l'ensemble des
   fiches hôte déjà collectées, toutes recherches et consultations
   confondues, et affiche les correspondances sur une carte. Ce n'est
   jamais une requête vers Shodan : c'est une relecture filtrée de la base
   locale. C'est la façon dont l'appli obtient un filtrage par pays/port
   **sans connexion** — puisque Shodan lui-même le refuse en anonyme (voir
   avertissement ci-dessous), on scrape en requête libre puis on filtre
   nous-mêmes ce qu'on a déjà accumulé. Plus on lance de recherches libres,
   plus la carte a de données à filtrer.

4. **Localiser une photo** (`/photo-location`) — aucun rapport avec
   Shodan : lit les coordonnées GPS dans les métadonnées EXIF d'un fichier
   JPEG/TIFF (celles qu'un téléphone y écrit si la localisation était
   activée). La photo n'est jamais conservée sur le serveur. Un PNG, un
   WEBP ou une capture d'écran ne contiennent jamais d'EXIF, donc jamais de
   position — voir `app/Console/Commands/InspectPhotoExif.php`
   (`php artisan app:inspect-photo-exif chemin/vers/photo.jpg`) pour
   diagnostiquer pourquoi une photo donnée n'a rien donné.

> ⚠️ L'application ne se connecte jamais à un compte Shodan. En anonyme,
> Shodan refuse lui-même les filtres de recherche avancés (`country:`,
> `org:`, `port:`…) et bloque la page 2 des résultats — utilise des
> requêtes libres (`apache`, `nginx`, `webcam`…) ; le filtrage par
> pays/ville/port se fait après coup, localement, via la page *Carte*.
> `images.shodan.io` (navigateur d'images) refuse aussi tout accès anonyme
> (`401 Unauthorized`, testé) — la recherche par image n'est donc pas
> possible dans cette version de l'appli.

### Schéma de données

Cinq tables, avec l'entité stable séparée de son historique :

| Table             | Rôle                                                          |
|--------------------|----------------------------------------------------------------|
| `searches`         | Une ligne par recherche lancée (requête, total, date)          |
| `search_rankings`  | Les classements « Top » attachés à chaque recherche            |
| `hosts`            | Une ligne par IP jamais vue plus d'une fois                    |
| `host_snapshots`   | Une nouvelle ligne à **chaque visite** d'une fiche hôte (jamais de mise à jour en place) |
| `search_results`   | Table pivot : quel instantané d'hôte appartient à quelle recherche, et à quelle position (pour la carte des résultats exacts) |

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
  une entrée la réaffiche telle qu'enregistrée, sans nouvelle requête. Un
  filtre « Du / Au » (date + heure, à la seconde près) permet de ne
  réafficher que les recherches archivées sur une période précise.
- **Fiche hôte : IP** (en haut à droite) : tape une IP (ex. `8.8.8.8`) pour
  voir sa fiche et sa ligne du temps.
- **Carte** : filtre pays/ville/port sur tous les hôtes déjà scrapés et les
  affiche sur une carte.
- **Localiser une photo** : dépose un JPEG/TIFF pour en lire la position GPS
  EXIF, si elle existe.

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
  l'historique ne déclenche aucune requête, et la récupération des
  positions exactes de chaque résultat individuel (`tests/Feature/SearchServiceTest.php`) ;
- le garde-fou de cooldown sur les fiches hôte (`tests/Feature/HostSnapshotServiceTest.php`) ;
- le filtre par date/heure sur l'historique (`tests/Feature/SearchControllerTest.php`) ;
- le filtre pays/ville/port de la Carte, y compris qu'il ne garde que le
  dernier instantané de chaque hôte (`tests/Feature/MapControllerTest.php`).

## Configuration (`.env`)

| Variable | Défaut | Rôle |
|---|---|---|
| `SHODAN_BASE_URL` | `https://www.shodan.io` | Racine des URLs scrapées |
| `SHODAN_USER_AGENT` | `GeoScanBot/1.0 (...)` | En-tête `User-Agent` envoyé |
| `SHODAN_MIN_DELAY_SECONDS` | `10` | Délai minimum entre deux requêtes sortantes |
| `SHODAN_REQUEST_TIMEOUT_SECONDS` | `30` | Timeout HTTP |
| `SHODAN_SNAPSHOT_COOLDOWN_MINUTES` | `5` | Durée pendant laquelle un instantané d'hôte est réutilisé au lieu d'être rescrapé |
| `SHODAN_CA_BUNDLE` | *(vide)* | À ne renseigner que si ton `php.ini` a un `curl.cainfo` cassé (voir plus bas) |

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
