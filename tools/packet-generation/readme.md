Contest Packet Generator
========================

[Go up to the HSPC tools readme](../readme.html) ([md](../readme.md))

This PHP script is meant to allow users to enter their problem
information through a web form.  To the submitter, it provides a link
to the PDF of just that problem.  To the admins, there is a packet.pdf
file that contains all of the submitted problems.  It does not allow
loading of previously submitted problems.  Submitting a problem with
the same problem ID is effectively an edit, since it replaces the old
problem with the same ID (the old one is saved).

The PHP script will write all output to a problems/ sub-directory --
that directory must exist and be writable by the web server.  There
must also be an problems/old/ directory as well -- upon an edit
(submitting a new problem with the same problem ID -- the old one is
moved to the old/ directory.

This was developed on an Ubuntu 16.04 system.  It requires pandoc and
pdflatex to be installed (and possibly some pdflatex packages).

To use it for your institution, you will want to make the following changes:

- Change institution-logo.png to your logo; it is currently a modified
  version of the SIGCSE 2018 logo
- Set the balloon colors at the top of packet.php
- Change some values in template.tex (lines 8-12)
- Make any changes to intro.tex as desired
