/* ------------------------------------------------------ */
/* Testimonials: arrow buttons page through the swipe row */
/* ------------------------------------------------------ */
const testimonialsRow = document.getElementById("testimonials-row");

if (testimonialsRow) {
    document.querySelectorAll("[data-testimonials-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const step = Number(button.dataset.testimonialsStep);
            testimonialsRow.scrollBy({
                left: step * testimonialsRow.clientWidth,
            });
        });
    });
}
