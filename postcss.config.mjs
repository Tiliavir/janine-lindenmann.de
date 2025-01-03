import {purgeCSSPlugin} from '@fullhuman/postcss-purgecss';

const purgecss = purgeCSSPlugin({
    content: ["./hugo_stats.json"],
    defaultExtractor: (content) => {
        try {
            const els = JSON.parse(content).htmlElements;
            return [...(els.tags || []), ...(els.classes || []), ...(els.ids || [])];
        } catch (e) {
            console.warn("⚠️ Warnung: hugo_stats.json konnte nicht geparst werden.");
            return [];
        }
    },
    safelist: ["banner__nav--open", "banner--float", "message-box", "success", "error", "fade-out"],
});

export default {
    plugins: [
        ...(process.env.HUGO_ENVIRONMENT === "production" ? [purgecss] : []),
    ],
};
