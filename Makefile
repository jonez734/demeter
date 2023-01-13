export HOST = hawk
export PROJECT = demeter
export REPODIR = /srv/www/vhosts/projects.zoidtechnologies.com/files/$(PROJECT)/

export PROD = $(HOST):/srv/www/vhosts/demeter.zoidtechnologies.com/
export PRODDOCROOT = $(PROD)80/html/
export STAGEPROD = /srv/staging/demeter.zoidtechnologies.com/
export STAGEPRODDOCROOT = $(STAGEPROD)80/html/

export datestamp = $(shell date +%Y%m%d-%H%M)
export archivename = $(PROJECT)-$(datestamp)
export installfile = install --mode=0660

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--no-owner --no-group \
	--delete-after \
	--links \
	--exclude '.~lock*' \
	--exclude '*~'

all:

clean:
	-rm *~
	-$(MAKE) -C skin clean
	-$(MAKE) -C php clean
	-$(MAKE) -C smarty clean
	-$(MAKE) -C js clean
	-$(MAKE) -C sql clean
	-$(MAKE) -C tty clean

prod:	export DOCROOT = $(STAGEPRODDOCROOT)
prod:	export STAGE = $(STAGEPROD)
prod:
	mkdir -p $(DOCROOT)
	mkdir -p $(STAGE)templates_c/
	mkdir -p $(DOCROOT)captchas/ # for captcha images

	$(MAKE) -C php install
	$(MAKE) -C skin install
	$(MAKE) -C smarty install
	$(MAKE) -C js install
	$(installfile) config-prod.php $(DOCROOT)config.php
	$(installfile) htpasswd $(DOCROOT).htpasswd
	$(installfile) htaccess $(DOCROOT).htaccess
	# google keyfile goes here
	$(RSYNC) $(STAGE) $(PROD)

.PHONY: prod
