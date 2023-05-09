
init:
	npm install -g @mermaid-js/mermaid-cli
	npm install -g carbon-now-cli
	composer install
	sudo apt-get install xclip

serve:
	php -S 0.0.0.0:9930 -t .

