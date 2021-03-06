# this is part of the https://github.com/aaronbloomfield/hspc repo
#
# it is released under the GPL license
# (https://www.gnu.org/licenses/gpl-3.0.en.html)

import csv 
import sys
from subprocess import call, run
import subprocess
from pathlib import Path
import re
import string

print ("Making invoices/ sub-directory...")
call (["mkdir","-p","invoices"])

emails = open("emails.html", 'w')
emails.write ("<!doctype html>\n<head><title>Invoice emails</title></head>\n<body><h3>Invoice email links</h3><ol>\n")

csvfile = "teaminfo.csv"
if len(sys.argv) > 1:
    csvfile = sys.argv[1]

f = open(csvfile, 'r')
reader = csv.reader(f)
for row in reader:
    if row[0] == "School name":
        continue

    # determine output file name
    outputname = row[1]
    if outputname == "":
        #translator = row[0].maketrans('', '', string.punctuation)
        #outputname = row[0].translate(translator) + ".pdf"
        outputname = row[0]
        outputname = re.sub('[' + string.punctuation + ' ]', '', outputname) + ".pdf"
    
    fp = open("teaminfo.tex", "w")
    print ("Generating invoice for '" + row[0] + "' as '" + outputname + "'...")
    fp.write ("\\schoolname{" + row[0] + "}\n")
    fp.write ("\\amountpaid{" + row[3] + "}\n")
    count = 0
    while count < int(row[4]):
        fp.write ("\\addteam{" + row[5+count] + "}\n")
        count = count +1
    fp.close()

    # execute pdflatex command
    call (["/bin/rm","-f","invoice.pdf"])
    output = run (["pdflatex","-halt-on-error","invoice.tex"], stdout=subprocess.PIPE)

    # check if there was an error
    file = Path("invoice.pdf")
    if file.is_file():
        call (["/bin/mv","-f","invoice.pdf","invoices/"+outputname])
        emails.write ("<li><a href='mailto:" + row[2] + "?subject=Programming contest invoice&body=Attached is your invoice for the upcoming programming contest.  Please let me know if you have any questions.'>" + row[0] + "</a> (attach " + row[1] + ")</li>\n")
    else:
        print ("    ERROR: invoice did not create properly!")
        emails.write ("<li>The invoice for " + row[0] + " did not create properly</li>\n")

emails.write("</ol></body></html>\n")
emails.close()
f.close()
