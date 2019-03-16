# Docker stack for Symfony Flex API

![](.docker/doc/schema.png)

This complete stack run with docker and [docker-compose (1.7 or higher)](https://docs.docker.com/compose/).

## Installation

1. Create a `.env` from the `.env.dist` file. Adapt it according to your needs.

    ```bash
    cp .env.dist .env
    ```
    
    Customisable vars :
      * `COMPOSE_PROJECT_NAME`: Your project name, the containers will be prefixed by this name. Snake case is advised.
      * `SGBD_TYPE`: SGBD type, `mysql` or `mariadb`, see the Docker Hub for available versions : [MySQL](https://hub.docker.com/_/mysql), [MariaDB](https://hub.docker.com/_/mariadb).
      * `MYSQL_ROOT_PASSWORD`: Your MySQL root password.
      * `MYSQL_ADM_PASSWORD`: Your MySQL structs admin password.
      * `MYSQL_USR_PASSWORD`: Your MySQL user password (for the API regular database).
      * `MYSQL_TST_PASSWORD`: Your MySQL test password (for the API test database).
      * `PHP_VERSION`: Your PHP version (See Docker Hub for [PHP](https://hub.docker.com/_/php).
      * `TIMEZONE`: The project timezone
      * `HOST_IP`: Your host IP address

1. Prepare your Symfony app, setting your global parameters:

    Create a `.env.local` file inside your `symfony` directory, an put this:
    ```
    ###> symfony/framework-bundle ###
    APP_SECRET=79fb12ef4d3647b5ddf567e8cc4c6b3c5e93173b
    ###< symfony/framework-bundle ###
    
    ###> doctrine/doctrine-bundle ###
    DATABASE_URL=mysql://u_your_project_rw:cfuHWkBkzjk4W2YJ@127.0.0.1:3306/your_project
    ###< doctrine/doctrine-bundle ###
    
    ###> lexik/jwt-authentication-bundle ###
    JWT_PASSPHRASE=ChangeMe
    ###< lexik/jwt-authentication-bundle ###
    ```
    
    At least this:
      * `APP_SECRET`: Random 40 characters string for Symfony, you can generate it with [http://nux.net/secret](http://nux.net/secret).
      * `DATABASE_URL`: Database url, using parameters defined in `.env` file in root:
        * Your url has this format : `DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name`
        * `db_user`: `u_<COMPOSE_PROJECT_NAME>_rw` => `u_your_project_rw` for example
        * `db_password`: `<MYSQL_USR_PASSWORD>` => `usr_password` for example
        * `db_name`: `<COMPOSE_PROJECT_NAME>` => `your_project` for example
      * `JWT_PASSPHRASE`: Your passphrase used for SSH keys for JWT.

1. Build/run containers (with and without detached mode)

    ```bash
    $ docker-compose build --no-cache
    $ docker-compose up -d
    ```

1. Finalize Symfony app : See the dedicated [README](symfony/README.md)

1. Enjoy :-)

## Entry points & usage

Just run `docker-compose up -d`, then:

* API (Symfony): [http://locahost](http://locahost)
* Logs (Kibana in ELK stack): [http://locahost:82](http://locahost:82)  
* PhpMyAdmin: [http://locahost:83](http://locahost:83)
* Logs (files location): logs/nginx and logs/symfony

## How it works?

Have a look at the `docker-compose.yml` file, here are the `docker-compose` built images:

* `db`: This is the databases container,
* `php`: This is the PHP-FPM container in which the API (Symfony) volume is mounted,
* `nginx`: This is the Nginx webserver container in which API volume is mounted too,
* `elk`: This is a ELK stack container which uses Logstash to collect logs, send them into Elasticsearch and visualize them with Kibana,
* `phpmyadmin`: This is the PhpMyAdmin container, to manage your databases,

This results in the following running containers:

```bash
$ docker-compose ps
         Name                        Command               State                 Ports
----------------------------------------------------------------------------------------------------
your_project_db           docker-entrypoint.sh --def ...   Up      0.0.0.0:3306->3306/tcp, 33060/tcp
your_project_elk          /usr/bin/supervisord -n -c ...   Up      0.0.0.0:82->80/tcp
your_project_nginx        nginx                            Up      0.0.0.0:80->80/tcp
your_project_php          docker-entrypoint php-fpm        Up      9000/tcp
your_project_phpmyadmin   /run.sh supervisord -n -j  ...   Up      0.0.0.0:83->80/tcp, 9000/tcp
```

## What happens when containers are built the first time?

* `db`: The SQL installation scripts are run (create regular and test database, create structs and referential data).
* `php`: The Symfony API is built, Composer install required vendors and make API ready to get queries.

## Useful commands

```bash
# bash commands
# docker-compose exec <service> bash
$ docker-compose exec php bash

# Composer (e.g. composer update)
$ docker-compose exec php composer update

# SF commands
$ docker-compose exec php bin/console cache:clear

# Retrieve an IP Addresses
$ docker inspect -f '{{.Name}} => {{range $key, $value := .NetworkSettings.Networks}}{{.IPAddress}} on {{$key}}{{end}}' $(docker ps -q)

# MySQL commands
$ docker-compose exec db mysql -uroot -p"root"

# Reinitialize database (adapt your database name and root credentials)
$ docker-compose exec db bash
$ /opt/db/scripts/bin/deploy.sh root root your_project
$ /opt/db/scripts/bin/deploy.sh root root your_project --fixtures
$ /opt/db/scripts/bin/deploy.sh root root your_project_tests

# Check CPU consumption
$ docker stats $(docker inspect -f "{{ .Name }}" $(docker ps -q))

# Delete all project containers
$ docker-compose stop
$ docker-compose rm

# Generate UUID
$ docker-compose exec php bash
$ vendor/bin/uuid generate

# See logs
$ docker logs your_project_php -f
```

## Use Kibana!

You can also use Kibana to visualize Nginx & Symfony logs by visiting `http://localhost:82`.

## Use XDebug!

To use XDebug change set your machine IP address in .env file.
If your IDE default port is not set to 5902 you should do that, too.

