TYPE=mod
BASE=vmassign_groups
PLUGINTYPE=vmcustom
ZIPBASE=opentools
VERSION=1.2

PLUGINFILES=mod_$(BASE).php mod_$(BASE).xml index.html # mod_$(BASE).script.php 

TRANSLATIONS=
#$(call wildcard,language/*/*.plg_$(PLUGINTYPE)_$(BASE).*ini) language/index.html $(call wildcard,language/*/index.html)
# INDEXFILES=$(BASE)/index.html
INDEXFILES=$(call wildcard,language/**/index.html) $(call wildcard,elements/*.html)
ELEMENTS=$(call wildcard,elements/*) 
# TMPLFILES=$(call wildcard,$(BASE)/tmpl/*.php) $(BASE)/index.html $(BASE)/tmpl/index.html
# ASSETS=$(call wildcard,$(BASE)/assets/*.png) $(call wildcard,$(BASE)/assets/*.css) 
ZIPFILE=$(TYPE)_$(ZIPBASE)_$(BASE)_v$(VERSION).zip


zip: $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(TMPLFILES)
	@echo "Packing all files into distribution file $(ZIPFILE):"
	@zip -r $(ZIPFILE) $(PLUGINFILES) $(TRANSLATIONS) $(ELEMENTS) $(INDEXFILES) $(TMPLFILES) $(ASSETS) LICENSE.txt

clean:
	rm -f $(ZIPFILE)

