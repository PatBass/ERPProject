#!/usr/bin/env bash
set +x

KGC_USER='kgcom'
KGC_IP=${KGCOM_IP}
KGC_PORT=${KGCOM_PORT}
KGC_KEY=${KGCOM_KEY_PATH:-$HOME'/.ssh/kgcom.pem'}
PWD=$JENKINS_PASSWORD_DEFAULT
ROOT_PATH=/var/jenkins_home/jobs
BITBUCKET_FILE=prod.sql
BITBUCKET_USER=jenkins-zol

make_dump_prod() {
  echo "---------------- CREATING DUMP ON PROD ---------------"
  ssh -i ${KGC_KEY} -p ${KGC_PORT} ${KGC_USER}@${KGC_IP} "cd /var/www/kgcom/sd/kgestion && ./make-dump.sh"
  [ 0 -eq $? ] || exit 1
  sleep 5
}

get_dump_prod() {
  echo "---------------- GETTING DUMP FROM PROD ---------------"
  scp -i ${KGC_KEY} -P${KGC_PORT} ${KGC_USER}@${KGC_IP}:/var/www/kgcom/sd/kgestion/dump.sql ./sql/prod.sql
  [ 0 -eq $? ] || exit 1
  sleep 5
}

send_dump_prod() {
  echo "---------------- SENDING DUMP TO BITBUCKET ---------------"
  curl -v -u ${BITBUCKET_USER}:${PWD}beaucoup -F files=@sql/prod.sql https://bitbucket.org/zol/kgestion/downloads/
  [ 0 -eq $? ] || exit 1
  sleep 5
}

build_container() {
  echo "----------------BUILD SAVED DATA-------------------"
  rm data/* -rf
  ls -al data/
  PROJECT_ENV=dev SYMFONY_ENV=dev make backup-prod
  ls -al data/
}

move_tar() {
    local dir=$ROOT_PATH/${1}/workspace/data/
    local file=${dir}save.tar

    [ -d "${dir}" ] || mkdir -p ${dir}

    echo "----------------MOVE DATA (${file})-------------------"
    [ -f "${file}" ] && rm -rf file
    cp data/save.tar ${file}
    ls -al ${dir}
}

all() {

    make_dump_prod
    get_dump_prod
    send_dump_prod

    ## Build a saved container with database data
    for i in {1..5}; do build_container && break || sleep 5; done

    if [ 0 -eq $? ]; then
        move_tar "kgcom-kgestion-test-pr"
        move_tar "kgcom-kgestion-test-develop"

        move_tar "kgcom-myastro-test-pr"
        move_tar "kgcom-myastro-test-develop"
    fi
}

all
