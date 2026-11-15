/* --------------------------------------------------------- */
/* Entrance effects: reveal .fx-* elements once they scroll  */
/* into view (see assets/scss/utility/_scroll-effects.scss)  */
/* --------------------------------------------------------- */
const fxTargets = document.querySelectorAll(".fx-up, .profile-picture");

if (
    fxTargets.length > 0 &&
    "IntersectionObserver" in window &&
    !window.matchMedia("(prefers-reduced-motion: reduce)").matches
) {
    // whatever is already on screen stays visible, so nothing flickers on load
    fxTargets.forEach((target) => {
        const rect = target.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            target.classList.add("is-visible");
        }
    });

    const fxObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    fxObserver.unobserve(entry.target);
                }
            });
        },
        { rootMargin: "0px 0px -10% 0px" },
    );

    fxTargets.forEach((target) => {
        if (!target.classList.contains("is-visible")) {
            fxObserver.observe(target);
        }
    });

    document.documentElement.classList.add("js-fx");
}
