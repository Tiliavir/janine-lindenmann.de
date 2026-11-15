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

## Testimonials

The swipeable testimonials row on the start page is rendered by the `testimonials` shortcode
from `data/testimonials.yaml`. Quote **verbatim**: exactly as written, typos and emojis included;
mark omissions with `[...]`. First names only, no surnames. No links to Google on the page:
the optional `url` (Google review) is kept in the data for reference and is not rendered.

## Scroll effects

Elements with `fx-up`, `fx-left` or `fx-right` fade in once when they scroll into view
(`assets/js/scroll-effects.js` + `assets/scss/utility/_scroll-effects.scss`). Without JavaScript or
with reduced motion everything is shown right away. `js-fx` and `is-visible` are added by script and
therefore safelisted in `purgecss.mjs`.

## Machine-readable metadata

- `layouts/_default/baseof.html` contains the schema.org JSON-LD (`ProfessionalService`), canonical link, `vcs-git` link to this repo and Open Graph tags.
- `static/llms.txt` is a hand-written summary of the site for LLMs ([llmstxt.org](https://llmstxt.org/)).
  **Update it whenever services, region, process or contact details change.**
- Pages with `noindex: true` and `sitemap.disable: true` in their front matter (e.g. 401/404) are excluded from the sitemap and search engines.
- Slider images need alt texts: `{{< image-slider images="a.webp,b.webp" alts="Text A|Text B" >}}`.

## Security headers

`static/.htaccess` sets a strict Content-Security-Policy: everything must come from this origin, and inline
`<script>` blocks are only allowed by SHA-256 hash. `npm run build` runs `csp-hashes.mjs`, which hashes all inline
scripts in `public/` and writes them into `public/.htaccess`. It fails the build on inline event handlers
(`onclick=` etc. – use `addEventListener`) or external scripts. Embedding anything external (fonts, maps, videos)
requires extending the CSP.
