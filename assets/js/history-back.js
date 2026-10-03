/* ------------------------------------------------------------- */
/* "Zurück" buttons on error pages (no inline onclick, see CSP)  */
/* ------------------------------------------------------------- */
document.querySelectorAll("[data-history-back]").forEach((button) => {
    button.addEventListener("click", () => history.back());
});
