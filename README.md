# janine-lindenmann.de

[![Build State](https://github.com/Tiliavir/janine-lindenmann.de/workflows/ci/badge.svg)](https://github.com/Tiliavir/janine-lindenmann.de/actions)

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

## Machine-readable metadata

- `layouts/_default/baseof.html` contains the schema.org JSON-LD (`ProfessionalService`), canonical link and Open Graph tags.
- `static/llms.txt` is a hand-written summary of the site for LLMs ([llmstxt.org](https://llmstxt.org/)).
  **Update it whenever services, region, process or contact details change.**
- Pages with `noindex: true` and `sitemap.disable: true` in their front matter (e.g. 401/404) are excluded from the sitemap and search engines.
- Slider images need alt texts: `{{< image-slider images="a.webp,b.webp" alts="Text A|Text B" >}}`.
