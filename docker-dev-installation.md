# Docker installation
You can edit some variables by copying `docker-compose.yml` file

Docker compose will create 3 containers :
- A container with PHP and Apache ([localhost:8383](http://localhost:8383))
- A container with MySQL

```shell
docker compose -f docker-compose-[username].yml up --build -d
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
