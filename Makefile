.PHONY: behat

# ================================================================================
# PROJECT VARS - YOU CAN UPDATE
# ================================================================================
step=/////////////////////////////////////////////////////
project=Kgestion
projectCompose=kgestion
db_name=kgestion
db_user=root
db_pwd=kgestion
console_path=app/console
makefiles_path=.kgcomdev/kgcom-common/makefile
optional_containers=
dump_exclude_pattern="app/Resources/public/js/6.jquery.dataTables.min.js app/Resources/public/js/8.moment.js app/Resources/public/js/bootstrap-wysiwyg.min.js"

# ================================================================================
# COMMON VARS - YOU MUST NOT UPDATE
# ================================================================================

ifeq "$(wildcard $(makefiles_path) )" "$(makefiles_path)"
	include $(makefiles_path)/common.mk
	include $(makefiles_path)/composer.mk
	include $(makefiles_path)/symfony.mk
	include $(makefiles_path)/docker.mk
	include $(makefiles_path)/hooks.mk
	include $(makefiles_path)/mysql.mk
	include $(makefiles_path)/proxy.mk
endif

$(call check_defined, USER_CMD)

install-app: remove build start clean-kgestion composer-dump composer-install
install-db-prod: database-restore migrations-apply
install-assets: assets cache-clear install-pre-commit

install-prod: install-app install-db-prod install-assets

jenkins-install-prod: install-app install-db-prod tests remove

clean-kgestion: clean-app
	@echo "$(step) Clean $(project) $(step)"
	@$(compose) run --rm web bash -ci "$(USER_CMD) rm -rf app/sessions/* web/bundles web/css web/js/main*"

elastic-restart:
	@echo "$(step) Elastic Restart $(project) $(step)"
	@$(compose) stop search
	@$(compose) rm -f search
	@$(compose) up -d search

kgcom-common:
	@rm -rf .kgcomdev
	@mkdir -p .kgcomdev
	@cd .kgcomdev && git clone --branch v3.1.8.2 git@bitbucket.org:kgcomdev/kgcom-common.git

tests: units vardump behat

# ================================================================================
# PROD DATA, BACKUP, RESTORE
# ================================================================================

database-prod-create:
	@echo "$(step) MySQL RESET $(project) $(step)"
	@$(compose) run --rm web sh -c "$(USER_CMD) ./scripts/reset_prod.sh"

database-backup:
	@echo "$(step) Backup Database $(project) $(step)"
	@$(compose) run --rm db /bin/bash -c "cd /var/lib/mysql && tar --exclude='save.tar*' -cvf save.tar ."

database-restore:
	@echo "$(step) Restore Database $(project) $(step)"
	@$(compose) stop db
	@$(compose) run --rm db /bin/bash -c "cd /var/lib/mysql && rm -rf kgestion && tar -xvf save.tar"
	@$(compose) start db

backup-prod: install-app database-prod-create database-backup

# ================================================================================

gen-ssl-certificate:
	sudo openssl genrsa -out $(NGINX_CERT_DIR)/kgestion.key 2048
	sudo openssl req -new -key $(NGINX_CERT_DIR)/kgestion.key -out $(NGINX_CERT_DIR)/kgestion.csr
	sudo openssl req -x509 -days 365 -key $(NGINX_CERT_DIR)/kgestion.key -in $(NGINX_CERT_DIR)/kgestion.csr -out $(NGINX_CERT_DIR)/kgestion.crt
