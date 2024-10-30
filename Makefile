start_tunnel:
	$(info To enable webhook callback start the ngrok tunnel with 'ngrok http 8888' and copy the public url to the .wp-env.json file. You then need to rerun the application)

run: start_tunnel
	 @. "${NVM_DIR}/nvm.sh"; nvm use 16; npm run dev; 

reset: 
	npx wp-env destroy

create-zip:
	nvm use 16; npm install; npm run build;

