#!/bin/bash

#
# Viking-tests executor
#

echo "Running tests ...";

files=`find . -name "*.php" -type f`
for file in $files; do
	(echo "" | php -B "function aCallback() { die(-1); } assert_options(ASSERT_CALLBACK, 'aCallback');" -F $file > /dev/null && echo "[OK] $file" ) || echo "[FAIL] $file"
done

