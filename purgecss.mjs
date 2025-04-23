'use strict';

import fs from "fs/promises";
import { PurgeCSS } from "purgecss";
import glob from "glob-all";

const extractInlineCSS = (html) => {
    const match = html.match(/<style[^>]*>([\s\S]*?)<\/style>/);
    return match ? match[1] : null;
};

const replaceInlineCSS = (html, newCSS) => {
    return html.replace(/<style[^>]*>[\s\S]*?<\/style>/, `<style>\n${newCSS.trim()}\n</style>`);
};

const processHTMLFile = async (filepath) => {
    console.log(`🔍 Processing: ${filepath}`);
    const htmlContent = await fs.readFile(filepath, "utf-8");
    const cssContent = extractInlineCSS(htmlContent);

    if (!cssContent) {
        console.warn(`⚠️ No <style> block found in: ${filepath}`);
        return;
    }

    const purgeCSSResult = await new PurgeCSS().purge({
        content: [{ raw: replaceInlineCSS(htmlContent, "<style></style>"), extension: "html" }],
        css: [{ raw: cssContent }],
        safelist: ['banner__nav--open', 'banner--float', 'message-box', 'success', 'error', 'fade-out']
    });

    const cleanedCSS = purgeCSSResult[0].css;
    const updatedHTML = replaceInlineCSS(htmlContent, cleanedCSS);
    await fs.writeFile(filepath, updatedHTML, "utf-8");
    console.log(`✅ Cleaned: ${filepath}`);
};

const rootDir = process.argv[2] || "public/";
const htmlFiles = glob.sync([`${rootDir}/**/*.html`]);

for (const file of htmlFiles) {
    await processHTMLFile(file);
}
