Contest Invoice Generation
==========================

[Go up to the HSPC tools readme](../readme.html) ([md](../readme.md))

This utility will generate invoices for the participating high
schools.  Given a CSV file in a given format, as well as some contest
configuration, then the `./generate-invoices.tex` script will run
`pdflatex` to create one invoice per high school.  It will also create
a list of `mailto:` links to quickly email the invoices.

## Installation

This tool was developed under Ubuntu 16.04.  It requires that Python 3
be installed, as well as PDF LaTeX (and a few LaTeX packages).

For Ubuntu, you will need to apt-get install the following packages:
`pdflatex texlive-latex-extra python3`.

One of the files installed by the `texlive-latex-extra` package is
`invoice.sty` -- this file is used by this script.  It has a bug
(described
[here](https://bugs.launchpad.net/ubuntu/+source/texlive-extra/+bug/1324843)),
that requires a change to the
`/usr/share/texlive/texmf-dist/tex/latex/invoice/invoice.sty` file.
You can either run the patch provided on that web page, or maually
edit the file as root or via `sudo` (replace the `\input{fp}` line
with `\RequirePackage{fp}` line; this should be around line 150 in
that file).

## Configuration

There are two files that are needed to be configured for this script
to run.


### Logos

This script assumes that there are two logos to put at the top of each
invoice.  Tyipcally, an institution logo and a contest logo are used.
You can use a blank (white) image if you do not want to use the logos.
They can be in PDF, JPEG, or PNG format.  We'll call them
`left-logo.pdf` and `right-logo.png`, below.


### config.tex

The first is a configuration file for the contest.  The file is stored
as `config.tex.template`, and should be copied to `config.tex`.  The
file contents are as follows:

```
\def\contestname{First Annual High School Programming Contest}
\def\contestlocation{the University of Antartica}
\def\leftlogo{left-logo.pdf}
\def\rightlogo{right-logo.png}
\def\costperteam{40.00}
\def\currency{USD} % USD, Euro, RMB, etc.; see http://ctan.math.washington.edu/tex-archive/macros/latex/contrib/invoice/doc/invoice.pdf for details

\def\bottomtext{
\begin{tabular}{cl}
\hspace{0.75in} & Payment can be submitted by check payable to ``ACM at University'' and sent to the address below. \\
& The Federal EIN for ACM at UVa is 12-3456789. \\
& \\
& Faculty Advisor \\
& Department of Computer Science \\
& Address of the University \\
& Anytown, Anycountry, ZIPCODE \\
\end{tabular}
}
```

This file is LaTeX, and all values have to go in curly brackets.  The fields are:

- **\\contestname:** the name of the contest.
- **\\contestlocation:** the name of the institution.
- **\\leftlogo:** the logo that goes on the left side of the header.
- **\\rightlogo:** the logo that goes on the right side of the header.
- **\\costperteam:** how much team registration costs.
- **\\currency:** the currency used: {USD} % USD, Euro, RMB, etc.; see
  [here](http://ctan.math.washington.edu/tex-archive/macros/latex/contrib/invoice/doc/invoice.pdf)
  for other options.
- **\\bottomtext:** any text that should go at the bottom of the
  invoice.  This is LaTeX code, and should be formatted properly.  In
  the example above, a table is defined (from the
  `\begin{tabular}{cl}` line to the `\end{tabular}` line), but it can
  be any format.  Be sure to end this with the closing curly bracket
  (the last line of the sample file).


### teaminfo.csv

This file is a CSV of the schools.  The CSV file has the following
fields:

- **School name**: The name of the participating school.  Spaces are
  fine, but ber careful about including characters that have meanings
  in LaTeX (an ampersand (`&`)is a common one).
- **Output file name**: the file name that the invoice should be saved
  to.  If left blank, the output name will be based on the school name
  (with all spaces and punctuation removed and a '.pdf' extension).
- **Contact email**: the email for the invoice to be sent to.  This
  script creates a series of `mailto:` links to make sending the
  emails quicker -- it does NOT send the emails automatically.
- **Payment received**: any amount that has already been paid, as a
  number (float or integer) -- if zero, then the "payment received"
  line will not be printed on the invoice.
- **Number of teams**: how many teams are participating from that
  school.
- **Team 1**, **Team 2**, ..., **Team $n$**: the team names.  You can
  have as many columns here as necessary -- the script will read as
  many team columns as the number provided in the *number of teams*
  field.

A sample CSV file is provided as `teaminfo.csv.template`, and is shown
below.

```
School name,Output file name (can be blank),Contact email,Payment received,Number of teams,Team 1,Team 2,Team 3,Team 4,... to team n
Generic High School,generic.pdf,nobody@nowhere.com,80,3,Foo,Bar,Qux,,
Random High School,random.pdf,null@nowhere.com,0,2,Thud,Grunt,,,
```

# Running the script

To run the script, just enter: `python3 generate-invoices.py`.  Or you
can run `make`, which does the same thing.  You can supply a
command-line parameter of the CSV file (if running it via the first
method), but if you do not, then it assumes that it should look for
`teaminfo.csv`.  The script will create an `invoices/` sub-directory
(if one does not exist), and put all the invoices there.  If there is
an error with the creation of a particular school's invoice, then a
message is printed by the script.  This can happen when an ampersand
(`&`) is in the school name, for example.

The script will also create an `emails.html` file, which can be used
to send emails to the contact email listed in the CSV file.  It adds a
subject and body, which can be customized by editing the
`generate-invoices.py` script.  It does not automatically attach the
invoice, since that is not supported by the `mailto:` link in modern
web browsers for security reasons.

That's it!

# Troubleshooting

Here are some common errors run into:

- The school name cannot have an ampersand in it, as that has a
  special meaning in LaTeX; repalce them with 'and' instead.
- Make sure both logo files are present and spelled correctly.
- The `\bottomtext` in the config.tex needs to be correct LaTeX,
  otherwise it will have problems building it.

When the script cannot create a given invoice, it will state so.  To
debug this, move the problematic invoice to the very end of the
teaminfo.csv file.  Run the script again.  Since the last one is now
the one that failed, the input files will be present as `invoice.tex`.
Thus, you can run `pdflatex invoice.tex` and LaTeX will output the
error message.


# How the script works

The script will read in the `teaminfo.csv` file, and create a
temporary teaminfo.tex file for each school.  That file might look
like the following:

```
\schoolname{Generic High School}
\amountpaid{80}
\addteam{Foo}
\addteam{Bar}
\addteam{Qux}
```

The commands shown in that file are defined in the invoice.tex file.
The invoice.tex file is then LaTeX'ed into a PDF via: `pdflatex
-halt-on-error invoice.pdf`.  Assuming it creates the file properly,
that file is then moved into the `invoices/` sub-directory with the
name specified in the teaminfo.csv file.

The .gitignore file will prevent git from seeing any .pdf, .jpg, or
.png images, so that a user can put those files in this directory (for
the logos, etc.) and not have git think changes have been made.
