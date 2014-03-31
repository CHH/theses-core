VENDOR_JS = bower_components/react/react.js bower_components/keymaster/keymaster.js bower_components/zepto/zepto.js
ADMIN_JS_SRC = $(VENDOR_JS) _build/application.js

.PHONY: all build-setup clean watch

all: build-setup assets/admin.js assets/admin.css

clean:
	rm -rf _build
	rm assets/admin.js
	rm assets/admin.css

build-setup:
	@mkdir -p _build

watch:
	watch --interval=2 make

assets/admin.js: $(ADMIN_JS_SRC)
	( for i in $(ADMIN_JS_SRC) ; do cat $$i ; echo ';' ; done ) >assets/admin.js

assets/admin.css: assets/admin/styles/screen.less
	lessc $< | autoprefixer > $@

_build/%.js: assets/admin/scripts/%.coffee
	@echo "coffee $< -- $@"
	@coffee -c -o $(@D) $<
