#!/bin/bash

if [ $# -lt 3 ] ; then
    echo "Script usage : $0 user password db [--fixtures]"
    exit 1
fi
export DB_USER=$1
export DB_PASSWORD=$2
export DB_NAME=$3

export SQL_WORK_DIR="$(dirname $(dirname `realpath $0`))"

cd $SQL_WORK_DIR

echo "== REGULAR SCRIPTS =="
for filename in *.sql; do
    echo "Executing ${SQL_WORK_DIR}/${filename} on $DB_NAME"
    mysql --host=localhost --user=$DB_USER --password=$DB_PASSWORD --default-character-set=utf8 $DB_NAME < $filename

    res=$?

    if [ $res -ne 0 ]
    then
        echo "Script ${SQL_WORK_DIR}/${filename} failed !"
        exit $res
    fi
done

if [ $# -eq 4 ] && [ $4 = "--fixtures" ] ; then
    echo "== FIXTURES =="
    for filename in fixtures/*.sql; do
        echo "Executing ${SQL_WORK_DIR}/fixtures/${filename} on $DB_NAME"
        mysql --host=localhost --user=$DB_USER --password=$DB_PASSWORD --default-character-set=utf8 $DB_NAME < $filename

        res=$?

        if [ $res -ne 0 ]
        then
            echo "Script ${SQL_WORK_DIR}/fixtures/${filename} failed !"
            exit $res
        fi
    done
fi

echo "All scripts are executed"