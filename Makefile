BOWER = bower_components
VENDOR_JS = $(BOWER)/react/react.js\
            $(BOWER)/keymaster/keymaster.js\
            $(BOWER)/zepto/zepto.js
ADMIN_JS_SRC = $(VENDOR_JS) _build/application.js

.PHONY: all build-setup clean watch assets

all: build-setup assets

clean:
	rm -rf _build
	rm assets/admin.js
	rm assets/admin.css

build-setup:
	@mkdir -p _build

assets: assets/admin.js assets/admin.css

watch:
	watch --interval=2 make assets

assets/admin.js: $(ADMIN_JS_SRC)
	@echo "concat $(ADMIN_JS_SRC) > $@"
	@( for i in $(ADMIN_JS_SRC) ; do cat $$i ; echo ';' ; done ) >$@

assets/admin.css: assets/admin/styles/screen.less
	lessc -M $< assets/admin.css > _build/admin.css.d
	sed -e 's/^[^:]*: *//' < _build/admin.css.d | \
		tr -s ' ' '\n' | \
		sed -e 's/$$/:/' \
		>> _build/admin.css.d
	lessc $< | autoprefixer > $@

-include _build/admin.css.d

_build/%.js: assets/admin/scripts/%.coffee
	@echo "coffee $< -- $@"
	@coffee -c -o $(@D) $<
