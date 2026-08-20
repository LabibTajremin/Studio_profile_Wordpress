#!/usr/bin/env bash
#
# Installs the WordPress core test suite so integration tests can run
# against a real WordPress + real MariaDB.
#
# Usage:
#   bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-create]
#
# Example (matches .github/workflows/ci.yml):
#   bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1:3306 latest
#
# Honours WP_CORE_DIR and WP_TESTS_DIR; both default under /tmp.
#
# Adapted from the canonical script shipped by wp-cli/scaffold-command.

set -euo pipefail

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-create]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

# Every fetch is bounded. An unbounded download is what let a stalled
# mirror hold CI open for seventeen hours: a request that neither answers
# nor fails should end the run in minutes, with a non-zero status the
# caller can act on, rather than waiting forever.
download() {
	if command -v curl >/dev/null 2>&1; then
		curl -sSL --connect-timeout 15 --max-time 300 --retry 3 --retry-delay 2 -o "$2" "$1"
	elif command -v wget >/dev/null 2>&1; then
		wget -nv --timeout=15 --tries=3 -O "$2" "$1"
	else
		echo "Neither curl nor wget is available." >&2
		exit 1
	fi
}

# ---------------------------------------------------------------------
# Resolve the version to download
# ---------------------------------------------------------------------
if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
	WP_BRANCH=${WP_VERSION%\-*}
	WP_TESTS_TAG="branches/$WP_BRANCH"
elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
	WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
	if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0]$ ]]; then
		# .0 releases are branched without the trailing zero.
		WP_TESTS_TAG="tags/${WP_VERSION%??}"
	else
		WP_TESTS_TAG="tags/$WP_VERSION"
	fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# 'latest' — ask the API which stable release that currently is.
	download http://api.wordpress.org/core/version-check/1.7/ "$TMPDIR"/wp-latest.json
	grep -Eq '[0-9]+\.[0-9]+(\.[0-9]+)?' "$TMPDIR"/wp-latest.json || {
		echo "Latest WordPress version could not be determined." >&2
		exit 1
	}
	LATEST_VERSION=$(grep -o '"version":"[^"]*' "$TMPDIR"/wp-latest.json | sed 's/"version":"//' | head -1)
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be determined." >&2
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set +u # WP_VERSION comparisons below tolerate unset optional vars.

# ---------------------------------------------------------------------
# WordPress core
# ---------------------------------------------------------------------
install_wp() {
	if [ -d "$WP_CORE_DIR" ]; then
		echo "WordPress core already present at $WP_CORE_DIR — skipping download."
		return
	fi

	mkdir -p "$WP_CORE_DIR"

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p "$TMPDIR"/wordpress-trunk
		rm -rf "$TMPDIR"/wordpress-trunk/*
		download https://wordpress.org/nightly-builds/wordpress-latest.zip "$TMPDIR"/wordpress-nightly.zip
		unzip -q "$TMPDIR"/wordpress-nightly.zip -d "$TMPDIR"/wordpress-trunk
		mv "$TMPDIR"/wordpress-trunk/wordpress/* "$WP_CORE_DIR"
	else
		if [[ $WP_VERSION == 'latest' ]]; then
			local ARCHIVE_NAME='latest'
		elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
			download https://api.wordpress.org/core/version-check/1.7/ "$TMPDIR"/wp-latest.json
			if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0]$ ]]; then
				# Strip the trailing .0 — wordpress.org does not publish it.
				LATEST_VERSION=${WP_VERSION%??}
			else
				LATEST_VERSION=$WP_VERSION
			fi
			local ARCHIVE_NAME="wordpress-$LATEST_VERSION"
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi

		download https://wordpress.org/"${ARCHIVE_NAME}".tar.gz "$TMPDIR"/wordpress.tar.gz
		tar --strip-components=1 -zxmf "$TMPDIR"/wordpress.tar.gz -C "$WP_CORE_DIR"
	fi

	download https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php "$WP_CORE_DIR"/wp-content/db.php
}

# ---------------------------------------------------------------------
# Test library (includes/ and data/ from wordpress-develop)
# ---------------------------------------------------------------------

# Fetches the test suite over plain HTTPS instead of Subversion.
#
# This used to be two `svn co` calls, which meant CI had to apt-get install
# subversion first. That step hung for seventeen hours on an Ubuntu mirror
# that would neither answer nor fail, and because apt retries indefinitely
# the job never finished on its own. One tarball over HTTPS needs no extra
# package, is a single request rather than hundreds, and also carries
# wp-tests-config-sample.php, so the separate download for that goes too.
#
# svn is still used if it is present and the download fails, so anyone who
# already has it keeps a working fallback — but nothing installs it.
fetch_test_suite() {
	# WP_TESTS_TAG is an svn path: "tags/6.7.1", "branches/6.7" or "trunk".
	# The git mirror needs a ref instead.
	local ref
	case "$WP_TESTS_TAG" in
		tags/*)     ref="refs/tags/${WP_TESTS_TAG#tags/}" ;;
		branches/*) ref="refs/heads/${WP_TESTS_TAG#branches/}" ;;
		*)          ref="refs/heads/${WP_TESTS_TAG}" ;;
	esac

	local tarball="$TMPDIR/wp-develop.tar.gz"
	local extracted="$TMPDIR/wp-develop"

	rm -rf "$extracted"
	mkdir -p "$extracted"

	if download "https://github.com/WordPress/wordpress-develop/archive/${ref}.tar.gz" "$tarball" &&
		tar -zxmf "$tarball" -C "$extracted" --strip-components=1; then
		cp -R "$extracted"/tests/phpunit/includes "$WP_TESTS_DIR"/includes
		cp -R "$extracted"/tests/phpunit/data "$WP_TESTS_DIR"/data
		return 0
	fi

	echo "Could not download the test suite tarball; falling back to Subversion." >&2

	if ! command -v svn >/dev/null 2>&1; then
		echo "Subversion is not installed either, so the test suite cannot be fetched." >&2
		exit 1
	fi

	svn co --quiet --ignore-externals \
		https://develop.svn.wordpress.org/"${WP_TESTS_TAG}"/tests/phpunit/includes/ "$WP_TESTS_DIR"/includes
	svn co --quiet --ignore-externals \
		https://develop.svn.wordpress.org/"${WP_TESTS_TAG}"/tests/phpunit/data/ "$WP_TESTS_DIR"/data
	download https://develop.svn.wordpress.org/"${WP_TESTS_TAG}"/wp-tests-config-sample.php "$extracted"/wp-tests-config-sample.php
}

install_test_suite() {
	# Portable in-place sed.
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i.bak'
	else
		local ioption='-i'
	fi

	if [ ! -d "$WP_TESTS_DIR" ]; then
		mkdir -p "$WP_TESTS_DIR"
		rm -rf "$WP_TESTS_DIR"/{includes,data}
		fetch_test_suite
	else
		echo "Test suite already present at $WP_TESTS_DIR — skipping checkout."
	fi

	if [ ! -f wp-tests-config.php ]; then
		# The sample normally arrives inside the tarball, but the suite
		# directory can outlive $TMPDIR — a re-run that skipped the
		# checkout above would otherwise find nothing to copy.
		if [ ! -f "$TMPDIR"/wp-develop/wp-tests-config-sample.php ]; then
			mkdir -p "$TMPDIR"/wp-develop
			download https://develop.svn.wordpress.org/"${WP_TESTS_TAG}"/wp-tests-config-sample.php \
				"$TMPDIR"/wp-develop/wp-tests-config-sample.php
		fi

		cp "$TMPDIR"/wp-develop/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php

		# Remove any trailing slash so the constant is a clean path.
		WP_CORE_DIR=${WP_CORE_DIR%/}

		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi
}

# ---------------------------------------------------------------------
# Test database
# ---------------------------------------------------------------------
install_db() {
	if [ "${SKIP_DB_CREATE}" = "true" ]; then
		echo "Skipping database creation."
		return 0
	fi

	# Split host into host / socket or port.
	local PARTS
	IFS=':' read -ra PARTS <<< "$DB_HOST"
	local DB_HOSTNAME=${PARTS[0]}
	local DB_SOCK_OR_PORT=${PARTS[1]-}
	local EXTRA=""

	if [ -n "$DB_HOSTNAME" ]; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif [ -n "$DB_SOCK_OR_PORT" ]; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		else
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# The CI service already creates MYSQL_DATABASE, so tolerate "exists".
	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS"$EXTRA 2>/dev/null \
		|| echo "Database $DB_NAME already exists — continuing."
}

install_wp
install_test_suite
install_db

echo "WordPress test suite ready:"
echo "  core:  $WP_CORE_DIR"
echo "  tests: $WP_TESTS_DIR"
