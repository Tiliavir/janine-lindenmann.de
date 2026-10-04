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

`static/.htaccess` sets a strict Content-Security-Policy: everything must come from this origin, and inline
`<script>` blocks are only allowed by SHA-256 hash. `npm run build` runs `csp-hashes.mjs`, which hashes all inline
scripts in `public/` and writes them into `public/.htaccess`. It fails the build on inline event handlers
(`onclick=` etc. – use `addEventListener`) or external scripts. Embedding anything external (fonts, maps, videos)
requires extending the CSP.

## Deployment

Pushing a tag on `main` builds the site and syncs `public/` to the Alfahosting webspace with `rsync` over SSH
(`.github/workflows/deploy.yml`). Files that no longer exist in the build are deleted on the server, except
`.well-known/`, `logs/`, `stats/` and `cgi-bin/`. Before syncing, the workflow checks that the target directory
already contains `index.html` and `contact.php`, so a wrong path can never be wiped.

One-time setup under *Settings → Secrets and variables → Actions*. The connection data is shown in CloudPit:
*Meine Verträge → Vertrag → Zugänge → CloudPit → Shell-Zugang einrichten*.

| Name | Type | Value |
|---|---|---|
| `SSH_HOST` | variable | SSH server from CloudPit |
| `SSH_USER` | variable | SSH user from CloudPit (primary FTP user) |
| `SSH_PORT` | variable | optional, default `22` |
| `SSH_TARGET_DIR` | variable | web root, relative to the SSH home or absolute – find it with `ssh user@host 'pwd; ls'` |
| `SSH_KNOWN_HOSTS` | secret | output of `ssh-keyscan -p 22 <host>` (for a port other than 22 the line starts with `[host]:port`) |
| `SSH_PRIVATE_KEY` | secret | preferred: private key of a dedicated deploy key; its public key goes into `~/.ssh/authorized_keys` on the server |
| `SSH_PASSWORD` | secret | fallback if keys are not accepted: the primary FTP password |

If `SSH_PRIVATE_KEY` is set it is used, otherwise `SSH_PASSWORD`. Once the first SSH deploy has worked, the old
`ftp_user` / `ftp_password` secrets and `.ftp-deploy-sync-state.json` on the server can be deleted.
