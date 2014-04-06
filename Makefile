BOWER = bower_components
VENDOR_JS = $(BOWER)/react/react.js\
            $(BOWER)/keymaster/keymaster.js\
            $(BOWER)/zepto/zepto.js
ADMIN_JS_SRC = $(VENDOR_JS) _build/application.js

.PHONY: all build-setup clean watch assets/admin.css assets

all: build-setup assets

clean:
	rm -rf _build
	rm assets/admin.js
	rm assets/admin.css

build-setup:
	@mkdir -p _build

assets: assets/admin.js assets/admin.css

watch:
	watch --interval=5 make assets

assets/admin.js: $(ADMIN_JS_SRC)
	@echo "concat $(ADMIN_JS_SRC) > assets/admin.js"
	@( for i in $(ADMIN_JS_SRC) ; do cat $$i ; echo ';' ; done ) >assets/admin.js

assets/admin.css: assets/admin/styles/screen.less
	lessc $< | autoprefixer > $@

_build/%.js: assets/admin/scripts/%.coffee
	@echo "coffee $< -- $@"
	@coffee -c -o $(@D) $<
