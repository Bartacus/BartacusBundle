#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'


# typo3/cms
mkdir -p Resources/patches/typo3/cms
typo3_version="$(composer show typo3/cms | grep versions | awk '{print $4}' | sed s/v//)"

wget -O Resources/patches/typo3/cms/bartacus-entry-scripts.patch https://github.com/Bartacus/TYPO3.CMS/compare/${typo3_version}...patch/${typo3_version}/bartacus-entry-scripts.patch


# typo3/cms-cli
mkdir -p Resources/patches/typo3/cms-cli
typo3_cli_version="$(composer show typo3/cms-cli | grep versions | awk '{print $4}' | awk -F"." '{print $1"."$2}')"

wget -O Resources/patches/typo3/cms-cli/bartacus-entry-scripts.patch https://github.com/Bartacus/cms-cli/compare/master...patch/${typo3_cli_version}/bartacus-entry-script.patch


# helhum/typo3-console
mkdir -p Resources/patches/helhum/typo3-console
typo3_console_version="$(grep helhum/typo3-console composer.json | head -n1 | awk '{ print $2}' | sed s/\"\<//)"

wget -O Resources/patches/helhum/typo3-console/bartacus-entry-script.patch https://github.com/Bartacus/TYPO3-Console/compare/${typo3_console_version}...patch/${typo3_console_version}/bartacus-entry-script.patch
