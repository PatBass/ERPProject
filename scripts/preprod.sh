#!/usr/bin/env bash

REBUILD_PROD="$1"
REBUILD_ELASTIC="$2"

echo "================ START =============="

make stop

# --------------------------------------------------------
# Getting new sources
# --------------------------------------------------------
git checkout develop
git fetch origin
git pull


# --------------------------------------------------------
# Installing new version
# --------------------------------------------------------
make zol-common
if [ "1" == "${REBUILD_PROD}" ]; then
    SYMFONY_ENV=dev make backup-prod || true
fi
make install-prod

# --------------------------------------------------------
# Elasticsearch
# --------------------------------------------------------
if [ "1" == "${REBUILD_ELASTIC}" ]; then
    rm elasticsearch/ -rf
    make elastic-restart
    make console "f:e:p --env=prod --no-debug -n"

    make remove && make start
fi


echo "================ END =============="
