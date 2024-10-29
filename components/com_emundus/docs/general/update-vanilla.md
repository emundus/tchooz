# Mettre à jour Vanilla
Pour mettre à jour Vanilla, vous devez suivre les étapes suivantes :
1. Lancer le fichier `.docker/installation/cleandb.sql` sur la base souhaité
2. Exécuter ensuite la commande suivante pour exporter toutes les tables :
    ```bash
    php cli/joomla.php tchooz:vanilla --folder=.docker/installation/vanilla
    ```
3. Exécuter maintenant la commande suivante pour exporter les clés étrangères :
    ```bash
    php cli/joomla.php tchooz:vanilla --action="export_foreign_keys" --folder=.docker/installation/vanilla
    ```
4. Le nouveau Vanilla est maintenant prêt
5. Exécuter les commandes suivantes pour recréer les utilisateurs de Vanilla :
    ```bash
    php cli/joomla.php tchooz:user:add --username="sysadmin" --lastname="EMUNDUS" --firstname="Administrator" --password="$TCHOOZ_SYSADMIN_PASSWORD" --email="sysadmin@emundus.io" --usergroup="Registered,Super Users" --userprofiles="System administrator" --useremundusgroups="Administrateur de plateforme" -n
    php cli/joomla.php tchooz:user:add --username="coord@emundus.fr" --lastname="EMUNDUS" --firstname="Dev" --password="$TCHOOZ_COORD_PASSWORD" --email="dev@emundus.io" --usergroup="Registered,Administrator" --userprofiles="Administrateur de plateforme,Formulaire de base" --useremundusgroups="Administrateur de plateforme" -n
    ```
6. Et enfin réinitialiser la connexion Fabrik :
    ```bash
    php cli/joomla.php tchooz:fabrik_connection_reset
    ```