
init:
	mkdir -p data
	npm install -g @mermaid-js/mermaid-cli@10.1.0
	npm install -g carbon-now-cli
	composer install
	cp config.dist.php config.php

serve:
	php -S 0.0.0.0:9930 -t .

