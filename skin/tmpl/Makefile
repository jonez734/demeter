all:

clean:
	-rm *~

install:
	mkdir -p $(STAGE)skin/tmpl/
	$(installfile) *.tmpl $(STAGE)skin/tmpl/

release:
	mkdir -p $(PROJECTBUILDDIR)skin/tmpl/
	-$(installfile) Makefile *.tmpl $(PROJECTBUILDDIR)skin/tmpl/
