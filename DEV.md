- Use nextcloud docker to run the server `docker run -d -p 8088:80 arm64v8/nextcloud`
- Build and send the app to the docker container

```
cd drawio-nextcloud
npm run build
cd ..
zip -vr drawio.zip drawio-nextcloud -x "*.git* .tx/* screenshots/* src/* package* webpack.config.js"
docker cp drawio.zip 6daf4392bf67:/var/www/html
```
- Connect to the docker container `docker exec -it 6daf4392bf67 sh`
- Deploy inside the docker container

```
rm -rf apps/drawio
unzip drawio.zip
rm drawio.zip
mv drawio-nextcloud apps/drawio
```

Note: Changing the app version in info.xml will break the server