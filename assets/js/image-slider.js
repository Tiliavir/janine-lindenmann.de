/* ------------------------------------------------------------- */
/* Image slider: cross-fades to the next image once it is decoded */
/* ------------------------------------------------------------- */
{
    const decodeBackground = async (element) => {
        const url = element.style.backgroundImage.match(/url\("?([^")]+)"?\)/);
        const img = new Image();
        img.src = url[1];
        return img.decode();
    };

    const decodeSlide = (slide) =>
        Promise.all([
            ...[...slide.querySelectorAll("img")].map((img) => img.decode()),
            ...[...slide.querySelectorAll(".image-fade-slider__backdrop")].map(
                decodeBackground,
            ),
        ]);

    const startSlider = (slider) => {
        const pending = [
            ...slider.querySelectorAll(".image-fade-slider__pending"),
        ];
        if (pending.length === 0) {
            return;
        }

        const interval = Number.parseInt(slider.dataset.interval, 10);
        const slides = [slider.querySelector(".image-fade-slider__slide")];
        const ready = [Promise.resolve()];
        let current = 0;
        let timer = null;

        const prepare = (index) => {
            if (!ready[index]) {
                const template = pending[index - 1];
                const slide = template.content.firstElementChild;
                template.replaceWith(slide);
                slides[index] = slide;
                ready[index] = decodeSlide(slide);
            }
            return ready[index];
        };

        const total = pending.length + 1;
        let next = 1;

        const show = async () => {
            try {
                await prepare(next);
            } catch (error) {
                console.error("image-slider: skipping image", error);
                next = (next + 1) % total;
                schedule();
                return;
            }
            if (document.hidden || next === current) {
                return;
            }

            const previous = slides[current];
            previous.classList.replace("is-active", "is-leaving");
            slides[next].classList.add("is-active");
            const fadeMs =
                Number.parseFloat(
                    getComputedStyle(slides[next]).transitionDuration,
                ) * 1000;
            setTimeout(() => previous.classList.remove("is-leaving"), fadeMs);
            current = next;
            next = (next + 1) % total;

            prepare(next).catch(() => {});
            schedule();
        };

        const schedule = () => {
            clearTimeout(timer);
            timer = setTimeout(show, interval);
        };

        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                clearTimeout(timer);
            } else {
                schedule();
            }
        });

        prepare(1).catch(() => {});
        schedule();
    };

    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        document.querySelectorAll(".image-fade-slider").forEach(startSlider);
    }
}
