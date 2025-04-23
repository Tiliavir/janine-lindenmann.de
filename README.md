# janine-lindenmann.de

Janines Website für ihre Tätigkeit als Freie Rednerin (IHK):
https://www.janine-lindenmann.de

## Local Development

You need a working hugo extended installation including dart-sass.

Install all required packages and run the first build to verify everything is working:

```bash
npm i
npm run build
```

To serve the page, use:

```bash
  hugo serve
```

To build the release version, use:

```bash
  hugo build --minify -d html -b https://www.janine-lindenmann.de
```

## Image Preprocessing

```bash
  convert image.jpg image.webp
  mogrify -strip -auto-orient -resize 2000x2000 *.webp
```
