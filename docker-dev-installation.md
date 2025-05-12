# Docker installation
You can edit some variables by copying `docker-compose.yml` file to `docker-compose-[username].yml` file.

## Containers list
- A container with PHP and Apache ([localhost](https://localhost:8585/))
- A container MySQL
- A container Redis

## Enable HTTPS locally
To run local development with HTTPS 
### macOS
> ```shell
> brew install mkcert
> mkcert -install
> cd .docker/apache/certs
> mkcert localhost
> ```
### Linux
> ```shell
> sudo apt update
> sudo apt install mkcert libnss3-tools -y
> mkcert -install
> cd .docker/apache/certs
> mkcert localhost
> ```

## Database configuration
- **Configuration 1 (default)**: Fresh installation with database persistence on a Docker volume.
- Configuration 2: Fresh installation with database persistence on your local machine.
- Configuration 3: Load an existing Tchooz database.

To configure the database, you can comment and uncomment the following lines in the `docker-compose.yml` file:
```yaml
      # (Required) Use a custom MySQL configuration for Tchooz
      - ./.docker/mysql/conf/my.cnf:/etc/mysql/conf.d/my.cnf
      # (Config 1 - default: Tchooz fresh install with database persistant in a Docker volume
      - ./.docker/installation/databases:/docker-entrypoint-initdb.d
      # (Config 2: Tchooz fresh install with database persistant in a host volume
      # - ./.docker/data/mysql:/var/lib/mysql
      # - ./.docker/installation/databases:/docker-entrypoint-initdb.d
      # (Config 3: Tchooz custom install without database persistant
      # - /mypath/tchooz_custom_database.sql:/docker-entrypoint-initdb.d/tchooz_custom_database.sql
```

## Useful commands
- Start the containers:
```shell
docker compose -f docker-compose-[username].yml up --build -d
```

- Stop and remove the containers and database:
```shell
docker compose -f docker-compose-[username].yml down -v && rm -f configuration.php
```

- Stop without removing the database:
```shell
docker compose -f docker-compose-[username].yml down
```

## Project update
To update database and Joomla project, you have 2 options:
- Update the project with the Joomla CLI
```shell
docker exec -it [web_service_name] php cli/joomla.php tchooz:update
```

- Rerun the containers
```shell
docker compose -f docker-compose-[username].yml up --build -d
```

If you want to update a specific component, you can use the following command:
```shell
docker exec -it [web_service_name] php cli/joomla.php tchooz:update -n --component=[component_name]
```

To run an interactive shell in a container
```shell
docker exec -it [web_service_name] /bin/bash
```

## Local development
First you need to install **Tailwind dependencies** of the project. You can do this by running the following command in the root of the project:
```bash
  npm install
```

Then you need to install **Tchooz dependencies**. You can do this by running the following command in `components/com_emundus` folder:
```bash
  cd components/com_emundus && npm install
```

### Watch for changes
To watch for changes in the project, you can run the following command in in `components/com_emundus` folder:
```bash
  npm run watch
```

### Build for production
When you are ready to build the project for production, you can run the following command in `components/com_emundus` folder:
```bash
  npm run build
```
> This command have to be run before any commit

### Profile with Blackfire
Set env variable in your docker-compose.yml file with your Blackfire credentials (https://app.blackfire.io/my/settings/credentials) and restart your docker containers:
```yaml
  environment:
        BLACKFIRE_SERVER_ID: your_blackfire_server_id
        BLACKFIRE_SERVER_TOKEN: your_blackfire_server_token
        BLACKFIRE_CLIENT_ID: your_blackfire_client_id
        BLACKFIRE_CLIENT_TOKEN: your_blackfire_client_token
```
```bash
docker compose -f docker-compose-[username].yml up --build -d
```

Launch the profiler in your browser with the Blackfire extension. You can find the extension in your browser's extension store.

## Tests Back-end
### Installation

```bash
  docker exec -it [web_service_name] libraries/emundus/composer.phar install --working-dir=tests/
```
> Set the arg "test_env: 1" in your docker-compose to enable Xdebug (required for unit test coverage)

### Execution

```bash
  # Without coverage
  docker exec -it [web_service_name]  tests/vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --no-coverage
  # With text coverage
  docker exec -it [web_service_name]  tests/vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --coverage-text
  # With html coverage 
  docker exec -it [web_service_name] tests/vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --coverage-html /var/www/html/tmp/coverage
```
> Open the [Html Dashboard](https://localhost:8585/tmp/coverage/dashboard.html) in your browser

## Developers documentation
### Installation
To install the documentation, you need to run the following command in `components/com_emundus` folder:
```bash
  npm install
```

### Watch for changes
To watch for changes in the documentation, you can run the following command in `components/com_emundus` folder:
```bash
  npm run docs:dev
```
This command will start a local server on [localhost:5555](http://localhost:5555)

### Update the documentation
You can find documentation in `components/com_emundus/docs` folder. This documentation is built with [Vitepress](https://vitepress.vuejs.org/).
You can edit/create markdown files in `components/com_emundus/docs/src` folder to complete the documentation.

## Acknowledgments

Below are several links that are essential for developers working on this project:
* [Joomla](https://manual.joomla.org/docs/next/)
* [Vue 3](https://vuejs.org/guide/introduction.html)
