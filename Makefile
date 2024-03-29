SHELL=/bin/sh

DIST=_dist
CB=$(DIST)/clearbricks

default:
	@echo "make config or make dist"

config:
	mkdir -p ./$(CB)
	
	## Copy needed files and folders
	find ./ -mindepth 1 -maxdepth 1 -type d \
	-not -name '.git' \
	-not -name '_dist' \
	-not -name 'coverage' \
	-not -name 'doxygen' \
	-not -name 'tests' \
	-not -name 'vendor' \
	-exec cp -r \{\} ./$(CB) \;
	
	## Copy _common.php and README files
	cp _common.php LICENSE README.md ./$(CB)/
	
	## Remove .svn folders
	find ./$(CB)/ -type d -name '.svn' -print0 | xargs -0 rm -rf
	
	touch config-stamp

dist: config dist-tgz dist-zip

dist-tgz:
	[ -f config-stamp ]
	cd $(DIST) && tar cfz clearbricks-$$(grep CLEARBRICKS_VERSION clearbricks/_common.php | cut -d"'" -f4).tar.gz ./clearbricks

dist-zip:
	[ -f config-stamp ]
	cd $(DIST) && zip -r9 clearbricks-$$(grep CLEARBRICKS_VERSION clearbricks/_common.php | cut -d"'" -f4).zip ./clearbricks

clean:
	[ -f config-stamp ]
	rm -rf $(DIST)
	rm -f config-stamp build-stamp configure-stamp
