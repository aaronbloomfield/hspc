markdown:
	@echo Converting markdown files to html format...
	@chmod 755 tools/convert-markdown-to-html
	@tools/convert-markdown-to-html
	@echo done!

touchall:
	find . | grep "\.md$$" | awk '{print "touch "$$1}' | bash

clean:
	/bin/rm -rf *~ */*~ */*/*~
