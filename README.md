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

## Security headers

The site uses a strict Content-Security-Policy: everything must come from this origin, and inline
`<script>` blocks are only allowed by SHA-256 hash. The hosting proxy does not pass response headers set in
`static/.htaccess`, so the policy is delivered twice and both copies must be kept in sync:

- `layouts/partials/site/security-meta.html` – CSP and referrer policy as `<meta>` tags, directly after
  `<meta charset>` (a meta CSP only applies to what follows it). This is what browsers actually enforce today.
- `static/.htaccess` – the same CSP plus the headers that cannot be set via `<meta>` (`frame-ancestors`,
  `X-Frame-Options`, HSTS, `X-Content-Type-Options`, COOP, `Permissions-Policy`), effective once the host
  passes headers through.

`npm run build` runs `csp-hashes.mjs`, which writes each page's inline script hashes into its meta tag and the
union of all hashes into `public/.htaccess`. It fails the build on inline event handlers (`onclick=` etc. – use
`addEventListener`), `javascript:` URLs, external scripts, or a CSP meta tag placed after a style/script/`<base>`.
Embedding anything external (fonts, maps, videos) requires extending both policies.
