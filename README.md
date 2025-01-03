# janine-lindenmann.de

Janines Website für ihre Tätigkeit als Freie Rednerin (IHK):
https://www.janine-lindenmann.de

## Local Development

To serve the production version, use:
```bash
  hugo serve --minify -d html -e production -b http://localhost:1313
```

to build the release version, use:
```bash
  hugo build --minify -d html -e production -b https://www.janine-lindenmann.de
```

## Image Preprocessing

```bash
  convert image.jpg image.webp
  mogrify -strip -auto-orient -resize 2000x2000 *.webp
```
