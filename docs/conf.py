import sys
import os
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

sys.path.append(os.path.abspath('_exts'))

lexers['php'] = PhpLexer(startinline=True)
lexers['php-annotations'] = PhpLexer(startinline=True)
lexers['php-standalone'] = PhpLexer(startinline=True)
lexers['php-symfony'] = PhpLexer(startinline=True)
primary_domain = 'php'

extensions = ['sensio.sphinx.configurationblock']
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'
project = u'Bartacus'
copyright = u'2015, Patrik Karisch'
version = ''
release = ''
html_title = "Bartacus Documentation"
html_short_title = "Bartacus"

exclude_patterns = ['_build']

on_rtd = os.environ.get('READTHEDOCS', None) == 'True'

if not on_rtd:  # only import and set the theme if we're building docs locally
    import sphinx_rtd_theme
    html_theme = 'sphinx_rtd_theme'
    html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]
