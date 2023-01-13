all:

clean:
	-rm *~
	-$(MAKE) -C art clean
	echo "bar!"
	-$(MAKE) -C scss clean
	-$(MAKE) -C css clean
	-$(MAKE) -C ttf clean
	-$(MAKE) -C tmpl clean

install:
	mkdir -p $(DOCROOT)skin/css/
	mkdir -p $(DOCROOT)skin/art/
	mkdir -p $(DOCROOT)skin/ttf/
	mkdir -p $(DOCROOT)skin/tmpl/
	-$(MAKE) -C scss install
	-$(MAKE) -C art install
	-$(MAKE) -C ttf install
	-$(MAKE) -C tmpl install


