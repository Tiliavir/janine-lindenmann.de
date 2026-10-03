'use strict';

/*
 * Computes SHA-256 hashes of all inline <script> blocks in the built site and writes
 * them into the Content-Security-Policy in public/.htaccess (placeholder __CSP_SCRIPT_HASHES__).
 * This lets the CSP allow exactly our own inline scripts instead of 'unsafe-inline'.
 *
 * Must run after every step that modifies the HTML (hugo --minify, purgecss).
 * Fails the build on inline event handlers (onclick=...) or external scripts, which the CSP would block.
 */

import fs from "fs/promises";
import crypto from "crypto";
import glob from "glob-all";

const rootDir = (process.argv[2] || "public").replace(/\/$/, "");
const placeholder = "__CSP_SCRIPT_HASHES__";
const htaccessPath = `${rootDir}/.htaccess`;

const scriptRe = /<script(\s[^>]*)?>([\s\S]*?)<\/script>/gi;
const handlerRe = /<[a-z][^>]*\son[a-z]+\s*=/i;

const hashes = new Set();
const problems = [];

for (const file of glob.sync([`${rootDir}/**/*.html`])) {
    const html = await fs.readFile(file, "utf-8");

    if (handlerRe.test(html)) {
        problems.push(`${file}: inline event handler (on...=) found – use addEventListener instead`);
    }

    for (const [, attrs = "", body] of html.matchAll(scriptRe)) {
        if (/\ssrc\s*=/i.test(attrs)) {
            if (/src\s*=\s*["']?(https?:)?\/\//i.test(attrs)) {
                problems.push(`${file}: external script ${attrs.trim()} is not allowed by the CSP`);
            }
            continue;
        }
        // data blocks (JSON-LD etc.) are not executed and not subject to script-src
        const type = /\stype\s*=\s*["']?([^"'\s>]+)/i.exec(attrs)?.[1]?.toLowerCase();
        if (type && !["text/javascript", "module", "application/javascript"].includes(type)) {
            continue;
        }
        hashes.add(`'sha256-${crypto.createHash("sha256").update(body, "utf8").digest("base64")}'`);
    }
}

if (problems.length) {
    console.error(`❌ CSP check failed:\n  ${problems.join("\n  ")}`);
    process.exit(1);
}

const htaccess = await fs.readFile(htaccessPath, "utf-8");
if (!htaccess.includes(placeholder)) {
    console.error(`❌ ${htaccessPath} does not contain ${placeholder}`);
    process.exit(1);
}

await fs.writeFile(htaccessPath, htaccess.replaceAll(placeholder, [...hashes].sort().join(" ")), "utf-8");
console.log(`🔒 CSP: ${hashes.size} inline script hash(es) written to ${htaccessPath}`);
