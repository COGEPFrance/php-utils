# 🛠️ Librairie utilitaire PHP/Symfony (`cogep/php-utils`)

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