import { makeDraggable } from "./draggable.js";
import { snapdom } from "@zumer/snapdom";

const updateEdgeClasses = (element) => {
    const rect = element.getBoundingClientRect();
    const threshold = 1;

    element.classList.toggle("at-edge-left", rect.left <= threshold);
    element.classList.toggle(
        "at-edge-right",
        rect.right >= window.innerWidth - threshold,
    );
    element.classList.toggle("at-edge-top", rect.top <= threshold);
    element.classList.toggle(
        "at-edge-bottom",
        rect.bottom >= window.innerHeight - threshold,
    );
};

export function makeFormDraggable(ctx) {
    if (ctx.dragCleanup) {
        return;
    }

    const itkFeedbackDiv = ctx.getElement(".itk-feedback-form");
    const dragHandle = ctx.getElement(".itk-feedback-draggable-handle");

    if (itkFeedbackDiv && dragHandle) {
        dragHandle.hidden = false;

        updateEdgeClasses(itkFeedbackDiv);

        ctx.dragCleanup = makeDraggable(itkFeedbackDiv, dragHandle, {
            constrainToViewport: true,
            onDrag: (_e, el) => updateEdgeClasses(el),
            onDragEnd: (_e, el) => updateEdgeClasses(el),
        });
    }
}

export function hideFormDragHandle(ctx) {
    const itkFeedbackDiv = ctx.getDocumentElement("#itk-feedback");
    const dragHandle = itkFeedbackDiv?.querySelector(
        ".itk-feedback-draggable-handle",
    );

    if (dragHandle) {
        dragHandle.hidden = true;
    }
}

export function prefillEmail(form) {
    const emailInput = form.querySelector('[name="created_by"]');
    if (emailInput && !emailInput.value && !emailInput.readOnly) {
        const cachedEmail = localStorage.getItem("itk-feedback-email");
        if (cachedEmail) {
            emailInput.value = cachedEmail;
        }
    }
}

function restoreCachedEmail(form) {
    const emailInput = form.querySelector('[name="created_by"]');
    if (emailInput && !emailInput.readOnly) {
        const cachedEmail = localStorage.getItem("itk-feedback-email");
        if (cachedEmail) {
            emailInput.value = cachedEmail;
        }
    }
}

export function showFormAfterSelection(ctx) {
    if (ctx.form) {
        ctx.form.hidden = false;
        const description = ctx.form.querySelector('[name="description"]');
        if (description) {
            description.focus();
        }
    }
    ctx.showMessage("");
    makeFormDraggable(ctx);
}

export function showForm(ctx) {
    if (ctx.start) {
        ctx.start.hidden = true;
    }
    ctx.itemsPanelMode = false;
    ctx.selectedSelector = null;
    ctx.enterSelectMode();
}

export function hideForm(ctx, reset) {
    ctx.hideRegion();
    hideFormDragHandle(ctx);
    ctx.itemsPanelMode = false;
    if (ctx.form) {
        ctx.form.querySelector(".itk-feedback-items-header")?.remove();
        ctx.form.hidden = true;

        // Restore form field visibility in case items panel was open.
        const selectors = [
            ".itk-feedback-form-title",
            ".itk-feedback-form-lead",
            ".form-row",
            'button[type="submit"]',
            ".btn-cancel",
        ];
        for (const selector of selectors) {
            for (const el of ctx.form.querySelectorAll(selector)) {
                el.hidden = false;
            }
        }

        if (reset) {
            ctx.form.reset();
            ctx.selectedSelector = null;
            restoreCachedEmail(ctx.form);
        }
    }
    if (ctx.start) {
        ctx.start.hidden = false;
    }

    const itkFeedbackDiv = document.getElementById("itk-feedback");
    const dragHandle = itkFeedbackDiv?.querySelector(
        ".itk-feedback-draggable-handle",
    );
    if (dragHandle) {
        dragHandle.hidden = true;
    }
}

export function initFormSubmit(ctx) {
    if (!ctx.form) {
        return;
    }

    ctx.form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const data = {};

        ctx.showMessage("Taking screenshot \u2026");

        // Hide widget form/messages before capturing screenshot (keep region visible)
        const widgetHost = document.getElementById("itk-feedback");
        if (widgetHost) widgetHost.style.display = "none";

        try {
            const el = document.body;

            // Cap DPR to avoid exceeding mobile canvas size limits
            // (e.g. iOS Safari ~16.7 MP). Using dpr:1 keeps the
            // canvas small while still capturing the full viewport.
            const result = await snapdom(el, { scale: 1, dpr: 1 });

            let image = result.toRaw();
            for (const method of ["toWebp", "toPng", "toJpg"]) {
                try {
                    const img = await result[method]();
                    if (img.src.length < image.length) {
                        image = img.src;
                    }
                } catch {
                    // Format not supported, skip.
                }
            }

            data.image = image;
        } catch (error) {
            console.warn("ItkFeedback: screenshot failed", error);
        }

        // Restore widget after capture
        if (widgetHost) widgetHost.style.display = "";

        ctx.showMessage("Sending feedback \u2026");

        const formData = new FormData(ctx.form);
        formData.forEach((value, key) => (data[key] = value));

        data.context = {
            url: document.location.href,
            referrer: document.referrer,
            document: document.documentElement.outerHTML,
            navigator: {
                userAgent: navigator.userAgent,
            },
            window: {
                innerWidth: window.innerWidth,
                innerHeight: window.innerHeight,
            },
            region: {
                left: ctx.region.style.left,
                top: ctx.region.style.top,
                width: ctx.region.style.width,
                height: ctx.region.style.height,
            },
            selectedElement: ctx.selectedSelector,
        };

        data.apiKey = ctx.config.apiKey;

        fetch(ctx.config.endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        })
            .then((response) => {
                if (201 === response.status) {
                    if (data.created_by) {
                        localStorage.setItem(
                            "itk-feedback-email",
                            data.created_by,
                        );
                    }
                    hideForm(ctx, true);
                    ctx.showMessage("Feedback created", "success");
                    ctx.refreshFeedbackData();
                } else {
                    response
                        .json()
                        .then((data) =>
                            ctx.showMessage(
                                "Error creating feedback: " +
                                    JSON.stringify(data),
                                "danger",
                            ),
                        );
                }
            })
            .catch((reason) => alert(reason));
    });
}
