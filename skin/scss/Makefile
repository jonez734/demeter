SCSS = sass --stop-on-error --trace --style expanded --sourcemap=none

all:

.scss.css:
	$(SCSS) $< $@

install:
	$(SCSS) --update --watch .:$(DOCROOT)skin/css/

clean:
	echo "foo!"
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css
