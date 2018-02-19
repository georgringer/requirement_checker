# TYPO3 Extension `requirement_checker`

This extension is **alpha** only and tries to find hidden requirements of system extensions by analyzing *use import statements*.

## Missing

- Currently it does not find FQDN statements within the core.
- There might be false positives ...

## HowTo

1) Install extension
1) Command line: ` ./typo3/sysext/core/bin/typo3 requirement:list`

## Result

```
about
-----

 * Backend
 * Core

backend
-------

 * Core
 * Extbase
 * Filelist
 * Frontend
 * Recordlist
 * Workspaces

belog
-----

 * Backend
 * Core
 * Extbase


...
...
```
