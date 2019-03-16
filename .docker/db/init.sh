#!/bin/sh

echo "INITIALIZE DATABASE"
if [ ! -d /var/lib/mysql/$COMPOSE_PROJECT_NAME ] ; then
    # Create init file
    cp /opt/db/scripts/init/0000-init-database.sql.dist /opt/db/scripts/init/0000-init-database.sql

    # Replace tokens
    sed -i -- "s/\$DATABASE_NAME/$COMPOSE_PROJECT_NAME/g" /opt/db/scripts/init/0000-init-database.sql
    sed -i -- "s/\$ADM_PASSWORD/$MYSQL_ADM_PASSWORD/g" /opt/db/scripts/init/0000-init-database.sql
    sed -i -- "s/\$USR_PASSWORD/$MYSQL_USR_PASSWORD/g" /opt/db/scripts/init/0000-init-database.sql
    sed -i -- "s/\$TST_PASSWORD/$MYSQL_TST_PASSWORD/g" /opt/db/scripts/init/0000-init-database.sql

    # Initialize
    mysql --user=root --password=$MYSQL_ROOT_PASSWORD < /opt/db/scripts/init/0000-init-database.sql

    # Deploy structs & referential data
    /opt/db/scripts/bin/deploy.sh root $MYSQL_ROOT_PASSWORD $COMPOSE_PROJECT_NAME
    /opt/db/scripts/bin/deploy.sh root $MYSQL_ROOT_PASSWORD ${COMPOSE_PROJECT_NAME}_tests
fi
