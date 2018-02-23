markdown:
	@echo Converting markdown files to html format...
	@chmod 755 tools/convert-markdown-to-html
	@tools/convert-markdown-to-html
	@echo done!

clean:
	/bin/rm -rf *~ */*~ */*/*~
