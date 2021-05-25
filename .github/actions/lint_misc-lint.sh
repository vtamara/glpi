#!/bin/bash -e

echo "Check for missing headers"
patch -f -p1 -d vendor/glpi-project/tools/ < tools/licence-headers-check.patch
vendor/bin/licence-headers-check

echo "Check for SCSS compilation errors"
bin/console build:compile_scss --ansi

echo "Check for gettext errors/warnings"
tools/locale/extract_template.sh 2>&1 | tee extract.log
if [[ -n $(grep "warning" extract.log) ]]; then exit 1; fi
