Install requirements for docs
=============================

To write/preview the docs, install the following:

```bash
$ sudo pip install sphinx sphinx-autobuild sphinx-rtd-theme
```

Build the docs
--------------

Run the Makefile to build your changes:

```bash
$ make html
$ xdg-open _build/html/index.html
```

Autobuild
---------

You can also autobuild your changes and automatically reload your browser:

```bash
sphinx-autobuild ./ _build/html/
```

Now open your browser at http://127.0.0.1:8000 (or the address `sphinx-autobuild reports).
