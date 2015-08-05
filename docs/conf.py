import sys
import os
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

lexers['php'] = PhpLexer(startinline=True, linenos=1)
lexers['php-annotations'] = PhpLexer(startinline=True, linenos=1)
primary_domain = 'php'

extensions = []
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'
project = u'Bartacus'
copyright = u'2015, Patrik Karisch'
version = '0.2'
release = '0.2.1'
html_title = "Bartacus Documentation"
html_short_title = "Bartacus"

exclude_patterns = ['_build']
