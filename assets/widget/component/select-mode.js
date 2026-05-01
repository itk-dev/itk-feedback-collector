import unique from "@cypress/unique-selector";

export function enterSelectMode(ctx) {
    const style = document.createElement("style");
    style.dataset.tidyFeedbackSelecting = "";
    style.textContent =
        "body.tidy-feedback-selecting, body.tidy-feedback-selecting * { cursor: crosshair !important; }";
    document.head.appendChild(style);
    document.body.classList.add("tidy-feedback-selecting");
    ctx.showMessage("Click an element to select", "select");

    const primaryColor =
        getComputedStyle(document.documentElement)
            .getPropertyValue("--color-primary")
            .trim() || "hsla(252, 55%, 46%, 1)";
    let hoveredElement = null;

    const cleanup = () => {
        document.removeEventListener("click", onClick, true);
        document.removeEventListener("keydown", onKeydown);
        document.removeEventListener("mousemove", onMousemove);
        document.body.classList.remove("tidy-feedback-selecting");
        style.remove();
        if (hoveredElement) {
            hoveredElement.style.outline = "";
            hoveredElement = null;
        }
    };

    const onClick = (event) => {
        const target = event.target;

        if (
            target.closest("#tidy-feedback") ||
            target.closest("#tidy-feedback-region")
        ) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const rect = target.getBoundingClientRect();
        try {
            ctx.selectedSelector = unique(target);
        } catch {
            ctx.selectedSelector = null;
        }

        cleanup();
        const regionPadding = 20;
        ctx.positionRegion({
            left: rect.left + window.scrollX - regionPadding,
            top: rect.top + window.scrollY - regionPadding,
            width: rect.width + regionPadding * 2,
            height: rect.height + regionPadding * 2,
        });
        ctx.showFormAfterSelection();
    };

    const onKeydown = (event) => {
        if (event.key === "Escape") {
            cleanup();
            ctx.showMessage("");
            if (ctx.start) {
                ctx.start.hidden = false;
            }
        }
    };

    const onMousemove = (event) => {
        const target = event.target;

        if (
            target.closest("#tidy-feedback") ||
            target.closest("#tidy-feedback-region")
        ) {
            if (hoveredElement) {
                hoveredElement.style.outline = "";
                hoveredElement = null;
            }
            return;
        }

        if (hoveredElement && hoveredElement !== target) {
            hoveredElement.style.outline = "";
        }

        if (target !== hoveredElement) {
            target.style.outline = "2px dashed " + primaryColor;
            hoveredElement = target;
        }
    };

    document.addEventListener("click", onClick, true);
    document.addEventListener("keydown", onKeydown);
    document.addEventListener("mousemove", onMousemove);
}
