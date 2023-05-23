#!/bin/sh
cd ..
# Compile Vue.JS
npm run dev
# Move compiled files to public
rm -rf ../js
mv public/js ../js
mv public/mix-manifest.json ../mix-manifest.json
rm -rf public
