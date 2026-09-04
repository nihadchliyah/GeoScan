# GeoScan

GeoScan est un outil de renseignement réseau (OSINT) en Laravel qui scrape
les pages publiques de [shodan.io](https://shodan.io) et conserve un
historique complet de tout ce qui a été consulté — recherches, fiches
hôte, et leur géolocalisation.

## Ce que fait l'application

Deux parcours, accessibles depuis le menu en haut de chaque page :

### 1. Recherche (`/searches/create`)

Tu tapes une requête libre (`apache`, `nginx`, `webcam`…), l'appli va
chercher `shodan.io/search?query=...`, en extrait le nombre total de
résultats et les 5 classements « Top » (pays, ports, organisations,
produits, systèmes d'exploitation), et **archive** le résultat en base.
Consulter cette recherche plus tard (page *Historique*) ne relance
**jamais** de requête vers Shodan : c'est une relecture pure de ce qui a
été enregistré.

Cette même page affiche aussi, en dessous du formulaire, une **carte de
tout ce qui a déjà été scrapé** — filtrable par pays, ville, port,
organisation, FAI, ASN, produit/techno, nom d'hôte ou domaine. Ce n'est
jamais une requête vers Shodan : une relecture filtrée de la base locale
(uniquement le dernier instantané de chaque hôte). C'est la façon dont
l'appli obtient un filtrage façon Shodan **sans connexion** — puisque
Shodan lui-même refuse ces filtres en anonyme (voir avertissement
ci-dessous) : on scrape en requête libre, puis on filtre nous-mêmes ce
qu'on a déjà accumulé. Cliquer un marqueur sur la carte relance
directement la page filtrée sur le pays ou l'organisation de cet hôte.
Plus on lance de recherches libres, plus la carte a de données à filtrer.

La page de résultats liste aussi individuellement chaque IP trouvée (une
dizaine, la limite anonyme). Pour chacune, l'appli va chercher sa fiche
hôte (`shodan.io/host/{ip}`) afin d'obtenir sa position GPS **exacte** —
contrairement à la carte des pays (juste en dessous du total), qui n'est
qu'une approximation par centroïde de pays puisque la page de recherche ne
donne que pays + ville en texte, jamais de coordonnées.

Ces fiches individuelles sont récupérées **en arrière-plan** (un job en
file d'attente par résultat, voir [`FetchSearchResultLocationJob`](app/Jobs/FetchSearchResultLocationJob.php)) :
la page de résultats s'affiche immédiatement, avec un bandeau « Localisation
en cours (X/Y) » qui se rafraîchit tout seul jusqu'à ce que tout soit
localisé — voir *Lancer l'application* plus bas, ça suppose qu'un worker de
file d'attente tourne. Un résultat dont la fiche hôte échoue à scraper est
simplement absent de la carte, sans faire échouer la recherche.

Un lien vers [images.shodan.io](https://images.shodan.io) (navigateur
d'images de Shodan) est aussi proposé sur cette page, pour comparer
visuellement une capture de webcam que tu aurais de ton côté — mais ce
service refuse tout accès anonyme (`401 Unauthorized`, vérifié), donc ça
demande d'être connecté à un compte Shodan dans ton propre navigateur ;
l'appli ne le fait pas à ta place.

### 2. Fiche hôte (`/hosts/{ip}`)

Tu tapes une IP, l'appli va chercher `shodan.io/host/{ip}` (pays, ville,
organisation, FAI, ASN, noms d'hôte, domaines, technologies web, ports
ouverts, coordonnées GPS) et en garde un **instantané** horodaté. Si un
instantané pour cette IP existe déjà depuis moins de quelques minutes
(cooldown configurable), il est réutilisé au lieu de refaire une requête.
La page affiche l'instantané le plus récent — avec une petite carte
OpenStreetMap positionnant l'IP — plus une ligne du temps de tous les
précédents, utile pour voir un hôte changer d'organisation ou de ports
ouverts entre deux visites.

### Schéma de données

| Table               | Rôle                                                                                                                           |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `searches`        | Une ligne par recherche lancée (requête, total, nombre de résultats individuels attendus, date)                              |
| `search_rankings` | Les classements « Top » attachés à chaque recherche                                                                         |
| `hosts`           | Une ligne par IP jamais vue plus d'une fois                                                                                     |
| `host_snapshots`  | Une nouvelle ligne à**chaque visite** d'une fiche hôte (jamais de mise à jour en place)                                |
| `search_results`  | Table pivot : quel instantané d'hôte appartient à quelle recherche, à quelle position (pour la carte des résultats exacts) |

### Politique de crawl

`shodan.io/robots.txt` autorise tout sauf `/domain/*` (l'appli n'y touche
jamais), avec un `Crawl-delay: 10`. En conséquence :

- chaque requête envoie un `User-Agent` identifiable (`SHODAN_USER_AGENT`) ;
- un délai minimum (`SHODAN_MIN_DELAY_SECONDS`, 10s par défaut) est respecté
  entre deux requêtes sortantes, quel que soit l'endroit de l'appli qui les
  déclenche (voir [`ShodanHttpClient`](app/Services/Shodan/ShodanHttpClient.php)) ;
- les fiches hôte individuelles d'une recherche sont récupérées une par une
  en arrière-plan (jobs en file d'attente), jamais en rafale.

## Installation

Prérequis : PHP 8.3+, Composer. SQLite suffit, pas de serveur de base de
données à lancer. La file d'attente utilise aussi la base de données
(`QUEUE_CONNECTION=database`), pas de Redis à installer.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Lancer l'application

Deux processus séparés doivent tourner en parallèle :

```bash
php artisan serve        # le serveur web
php artisan queue:work   # traite les jobs de géolocalisation en arrière-plan
```

Sans le second, une recherche s'archive bien mais reste indéfiniment sur
« Localisation en cours » — les fiches hôte des résultats individuels ne
sont jamais récupérées.

Ouvre ensuite [http://127.0.0.1:8000](http://127.0.0.1:8000) — tu es
redirigé vers le formulaire de recherche.

- **Nouvelle recherche** : tape une requête libre (ex. `apache`) et lance-la.
  En dessous, la carte de tout ce qui a déjà été scrapé, filtrable
  (pays/ville/port/organisation/FAI/ASN/produit/nom d'hôte) et dont les
  marqueurs filtrent directement au clic.
- **Historique** : liste toutes les recherches déjà archivées ; cliquer sur
  une entrée la réaffiche telle qu'enregistrée, sans nouvelle requête. Un
  filtre « Du / Au » (date + heure, à la seconde près) permet de ne
  réafficher que les recherches archivées sur une période précise.
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
- le job de géolocalisation en arrière-plan d'un résultat de recherche
  (`tests/Feature/Jobs/FetchSearchResultLocationJobTest.php`) ;
- le garde-fou de cooldown sur les fiches hôte (`tests/Feature/HostSnapshotServiceTest.php`) ;
- le filtre par date/heure sur l'historique (`tests/Feature/SearchControllerTest.php`) ;
- tous les filtres de la carte du formulaire de recherche, y compris
  qu'elle ne garde que le dernier instantané de chaque hôte
  (`tests/Feature/SearchCreateMapFilterTest.php`).

## Configuration (`.env`)

| Variable                             | Défaut                   | Rôle                                                                                                                |
| ------------------------------------ | ------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `SHODAN_BASE_URL`                  | `https://www.shodan.io` | Racine des URLs scrapées                                                                                            |
| `SHODAN_USER_AGENT`                | `GeoScanBot/1.0 (...)`  | En-tête`User-Agent` envoyé                                                                                       |
| `SHODAN_MIN_DELAY_SECONDS`         | `10`                    | Délai minimum entre deux requêtes sortantes                                                                        |
| `SHODAN_REQUEST_TIMEOUT_SECONDS`   | `30`                    | Timeout HTTP                                                                                                         |
| `SHODAN_SNAPSHOT_COOLDOWN_MINUTES` | `5`                     | Durée pendant laquelle un instantané d'hôte est réutilisé au lieu d'être rescrapé                             |
| `SHODAN_CA_BUNDLE`                 | *(vide)*                | À ne renseigner que si ton`php.ini` a un `curl.cainfo` cassé (voir plus bas)                                   |
| `QUEUE_CONNECTION`                 | `database`              | Où sont stockés les jobs de géolocalisation en attente ; nécessite`php artisan queue:work` pour être traités |

### Aller plus loin : carte de la fiche hôte

La fiche hôte affiche une carte [OpenStreetMap](https://www.openstreetmap.org)
(pas besoin de clé API) positionnant l'IP — les coordonnées sont récupérées
directement dans le script Mapbox que Shodan intègre déjà dans la page HTML
(`HostScraper::parseCoordinates()`). Ça marche même en anonyme, dès que
Shodan expose une position pour l'hôte.

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
