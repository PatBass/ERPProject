KGCOM - Kgestion
========================

**Back office d'administration et statisques d'un centre d'appel dans le domaine de la voyance :**

* Astrologue
* Méduim
* Tarologue
* Voyant
* Numérologue
* ...

**Administration des éléments suivants :**

* Consultations des clients (nature, temps, support, planification,...)
* Performance des voyants (nombre de consultations, temps cumulé, ...)
* Facturations
* Statistiques par client, par voyant, totales
* Paramétrage de l'application (constantes)
* ...

Outils, méthodes et technos
-----------------------------

* Redmine ([http://connect.zol.fr/projects/kgestion](http://connect.zol.fr/projects/kgestion))
* BitBucket ([https://bitbucket.org/kgcomdev/kgestion](https://bitbucket.org/kgcomdev/kgestion))
* Esprit Scrum
* Docker
* Fig OU Docker compose
* Proxy nginx automatique via cette excellente image docker [https://github.com/jwilder/nginx-proxy](https://github.com/jwilder/nginx-proxy)
* Symfony :
    * Version courante 2.6.*
    * Doctrine fixtures
    * Doctrine migrations
    * JmsDiExtraBundle

Récupération et installation
------------------------------

Il suffit de cloner le dépôt...

```
git clone git@bitbucket.org:kgcomdev/kgestion.git && cd kgestion
```

Installation de docker et docker-compose (**Attention aux versions**)

```
curl -L https://github.com/docker/compose/releases/download/1.4.0/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
docker-compose --version
```

```
sudo apt-get update
sudo apt-get install curl
curl -sSL https://get.docker.com/ | sh
sudo usermod -aG docker USER_NAME
docker --version
```

Les variables d'environnements :

```
PROJECT_ENV=[dev|preprod|] make install-prod
..
PROJECT_AS=[sudo|] make install-prod
..
PROJECT_ENV=[dev|prod|jenkins] PROJECT_AS=[sudo|] make install-prod
```

Dans votre `.bashrc` ou `.zshrc` :

```
export PROJECT_ENV=prod
export PROJECT_AS=sudo
export SYMFONY_ENV=dev
```

Installation de l'application

```
make kgcom-common
make backup-prod (pour construire un container de données à partir de sql/prod.sql)
make install-prod
```

Starting the REVERSE PROXY

```
make nginx-proxy
```


PROD
----------------------------------

:warning: Il faut penser à utiliser un `screen` pour lancer les scripts en PROD

**Package de prod**

* Pas de app_dev.php
* Définition des bonnes variables d'environnement
* Utilisation du bon parameters.yml
* Mode **HTTPS**
* Encodage de la BDD (UTF8 ?)
* Ne pas charger les fixtures

**Première migration à ne pas executer**

Pour cela on crée la table utilisée par Doctrine Migrations pour fixer une version de BDD, et on ajout la valeur de la première migration :

```
CREATE TABLE IF NOT EXISTS `migration_versions` (version VARCHAR(255) NOT NULL, PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
```

```
INSERT INTO `migration_versions` VALUES ('20150330154726');
```

**A vérifier après une montée de version**

* Mise à jour des mots de passe et suppression de la migration correspondante.
* Mise à jour des numéros de CB et suppression de la migration correspondante.

**Compléments après première migration**

* Sauvegarde de ce qui n'est pas versionné (.htaccess, favicon, script var d'env...)
* Exécution script de var d'env
* Attention .htaccess
* Attention app_dev.php (activation ip protection)
* Attention HTTPS
* Attention PBL mémoire grosse migration
* Attention xdebug.max_nested_level
* chmod -R 777 app/logs app/cache app/sessions

Variables d'environnements
----------------------------

* SYMFONY\_\_APP\_\_SECRET: XXX
* SYMFONY\_\_APP\_\_PUBLIC: XXX
* SYMFONY\_\_CRYPT\_\_KEY: XXX
* SYMFONY\_\_DATABASE\_\_HOST: XXX
* SYMFONY\_\_DATABASE\_\_PORT: XXX
* SYMFONY\_\_DATABASE\_\_NAME: XXX
* SYMFONY\_\_DATABASE\_\_USER: XXX
* SYMFONY\_\_DATABASE\_\_PASSWORD: XXX

Affichage de l'application
----------------------------

* DEV : [http://kgestion.dev/app_dev.php/](http://kgestion.dev/app_dev.php/)
* PROD : [http://kgestion.dev](http://kgestion.dev)


Jenkins
-----------------------------

[http://jenkins.preprod.zol.fr](http://jenkins.preprod.zol.fr)
