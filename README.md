# 🛠️ Librairie utilitaire PHP/Symfony (`cogepfrance/php-utils`)

Cette librairie regroupe un ensemble de composants utilitaires pour uniformiser le développement des projets (Connecteurs, Mapping, DTO, etc.).

---

## 🏗️ Architecture & Structures
* **DynamicPropertyClass** : Permet de déclarer des classes dont on peut remplir les propriétés dynamiquement sans les avoir préalablement déclarées.
* **DTOInterface** : Interface de marquage pour représenter un `DTO`.
* **EntityInterface** : Interface de marquage pour représenter une `Entity`.

## 🔌 Connectors API
* **ApiConnector (Abstract)** : Gère l'authentification ainsi que la mise en cache du jeton (Token). La configuration par défaut peut être surchargée selon les spécificités de l'API ciblée.
* **ApiConnectorHelper** :
    * `processInBatches` : Traite des requêtes en parallèle par lots avec gestion native du retry.
    * `streamResponse` : Stream les réponses API pour une lecture au fil de l'eau (optimisation mémoire).
* **ApiException (Abstract)** : Classe de base pour traiter les retours d'erreurs API et extraire les détails de l'exception.

## 💾 InMemory (Persistence)
### CSV
* **CsvFetcher** : Permet de lire un fichier CSV et d'en retourner les données sous forme de tableau.
* **CsvPersister** : Écrit un CSV à partir d'un tableau de données ou d'objets.
    * Utilise un `warmupLimit`(X) pour extraire les headers depuis les attributs ou les clés des X premières données.
    * Retourne un objet `PersisterResult`.

### JSON
* **JsonPersister** : Permet d'écrire un fichier JSON depuis un tableau de données ou d'objets.
    * Retourne un objet `PersisterResult`.

## 📝 Logs
* **LoggerFormator** : Gère le formatage et la colorisation des logs dans la console.
* **UrlTruncatorProcessor** : Processeur Monolog qui tronque les messages de plus de 500 caractères en ajoutant `...` pour éviter les logs trop volumineux.
* **SensitiveDataRedactorProcessor** : Masque le host et la query string de toute URL (ainsi que les bearer tokens) présente dans un log. Activé automatiquement, **uniquement sur le channel Monolog `sensitive`** (channel générique, partagé par tout composant de la lib exposant potentiellement des secrets dans une URL — aujourd'hui `AzureBlobClient` via le service `cogep_php_utils.sensitive_http_client`, réutilisable pour de futurs connecteurs).
    * ⚙️ Fonctionne sans configuration dans l'application consommatrice (le channel `sensitive` est créé automatiquement via `#[WithMonologChannel]`).
    * ⚠️ Nécessite que `MonologBundle` soit **activé** (`config/bundles.php`) dans l'application — c'est quasi toujours le cas dès qu'un logger PSR-3 est utilisé. Si ce n'est pas le cas, la fonctionnalité se désactive silencieusement (pas d'erreur, pas de log sur ce channel).
    * 💡 Si votre `monolog.yaml` restreint les handlers à une liste explicite de channels (`channels: ['app', ...]` en mode inclusif), pensez à y ajouter `sensitive` pour voir apparaître ces logs.
    * ℹ️ Les headers HTTP (dont `Authorization: Bearer ...`) ne sont **pas** inclus par défaut dans les logs internes de Symfony HttpClient (seuls méthode/URL/status/durée le sont) : le masquage du channel `sensitive` cible donc avant tout les secrets embarqués dans l'URL elle-même (SAS tokens, presigned URLs...).

## 🔄 Mapping
* **DtoEntityMapper** :
    * `mergeDtosToEntity` : Permet de consolider plusieurs `DTOInterface` vers une `EntityInterface` unique.
    * *Règle* : Les attributs doivent porter le même nom. La priorité est définie du premier au dernier DTO : l'outil ne remplit que les propriétés encore vides de l'entité.

## 🧪 Tests & Qualité
* **Dummy Objects** : Fournit `DummyDynamicDTO` et `DummyDynamicEntity` pour faciliter les tests unitaires.
* **Dev - CoverageChecker** : Outil de contrôle qualité qui analyse le fichier `clover.xml`.
    * Permet de configurer un seuil (`threshold`) de couverture minimum.
    * Stoppe le processus (`exit 1`) si le pourcentage de couverture est insuffisant.


---
