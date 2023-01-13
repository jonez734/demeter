all:

clean:
	-rm *~

install:
	$(installfile) *.php $(DOCROOT)/

release:
	mkdir -p $(PROJECTRELEASEDIR)php/
	$(installfile) Makefile *.php $(PROJECTRELEASEDIR)php/
