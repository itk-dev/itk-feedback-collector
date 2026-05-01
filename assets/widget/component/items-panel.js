export function renderItemsList(ctx, listOnly = false) {
    if (ctx.feedbackItems.length === 0) {
        return;
    }

    ctx.form.querySelector(".tidy-feedback-items")?.remove();

    const container = document.createElement("div");
    container.className = "tidy-feedback-items";

    const list = document.createElement("ul");
    list.className = "tidy-feedback-items-list";

    if (!listOnly) {
        const toggle = document.createElement("button");
        toggle.type = "button";
        toggle.className = "tidy-feedback-items-toggle";
        const label =
            ctx.config.messages["Existing feedback"] ?? "Existing feedback";
        toggle.textContent = `${label} (${ctx.feedbackItems.length})`;
        toggle.addEventListener("click", () => {
            toggle.classList.toggle("open");
            list.hidden = !list.hidden;
        });
        container.appendChild(toggle);
        list.hidden = true;
    }

    for (const item of ctx.feedbackItems) {
        const li = document.createElement("li");
        const a = document.createElement("a");
        a.href = item.url;
        a.target = "_blank";
        a.rel = "noopener noreferrer";
        a.textContent = item.description ?? "";

        if (item.selectedElement) {
            let savedCssText = null;
            li.addEventListener("mouseenter", () => {
                try {
                    const el = document.querySelector(item.selectedElement);
                    if (el) {
                        savedCssText = el.style.cssText;
                        el.style.cssText +=
                            "outline: 2px solid hsla(252, 55%, 46%, 0.6); outline-offset: 2px; border-radius: 4px;";
                    }
                } catch {
                    // Ignore invalid selectors.
                }
            });
            li.addEventListener("mouseleave", () => {
                try {
                    const el = document.querySelector(item.selectedElement);
                    if (el && savedCssText !== null) {
                        el.style.cssText = savedCssText;
                        savedCssText = null;
                    }
                } catch {
                    // Ignore invalid selectors.
                }
            });
        }

        li.appendChild(a);
        list.appendChild(li);
    }

    container.appendChild(list);
    ctx.form.appendChild(container);
}

export function refreshFeedbackData(ctx) {
    if (!ctx.config.checkUrl || !ctx.start) {
        return;
    }

    fetch(
        ctx.config.checkUrl +
            "?url=" +
            encodeURIComponent(document.location.href),
    )
        .then((response) => response.json())
        .then((json) => {
            const count = json?.data?.count;
            ctx.feedbackItems = json?.data?.items ?? [];

            if (ctx.startCount) {
                const badge = ctx.startCount.querySelector(
                    ".tidy-feedback-badge",
                );
                if (badge) {
                    badge.textContent = count;
                }
                ctx.startCount.hidden = !(count > 0);
            }

            if (ctx.itemsPanelMode) {
                renderItemsList(ctx, true);
            }
        })
        .catch(() => {
            // Silently ignore check errors.
        });
}

function setFormFieldsVisibility(form, visible) {
    if (!form) {
        return;
    }

    const selectors = [
        ".tidy-feedback-form-title",
        ".tidy-feedback-form-lead",
        ".form-row",
        'button[type="submit"]',
        ".btn-cancel",
    ];

    for (const selector of selectors) {
        for (const el of form.querySelectorAll(selector)) {
            el.hidden = !visible;
        }
    }
}

export function showItemsPanel(ctx) {
    if (ctx.start) {
        ctx.start.hidden = true;
    }

    ctx.itemsPanelMode = true;

    if (ctx.form) {
        ctx.form.hidden = false;
        setFormFieldsVisibility(ctx.form, false);

        ctx.form.querySelector(".tidy-feedback-items-header")?.remove();
        ctx.form.querySelector(".tidy-feedback-items")?.remove();

        const header = document.createElement("div");
        header.className = "tidy-feedback-items-header";

        const title = document.createElement("span");
        const label =
            ctx.config.messages["Existing feedback"] ?? "Existing feedback";
        title.textContent = `${label} (${ctx.feedbackItems.length})`;

        const closeBtn = document.createElement("button");
        closeBtn.type = "button";
        closeBtn.className = "btn btn-cancel";
        closeBtn.textContent = "\u00D7";
        closeBtn.addEventListener("click", () => {
            hideItemsPanel(ctx);
        });

        header.appendChild(title);
        header.appendChild(closeBtn);
        ctx.form.appendChild(header);

        renderItemsList(ctx, true);
    }

    ctx.makeFormDraggable();
}

export function hideItemsPanel(ctx) {
    ctx.itemsPanelMode = false;

    if (ctx.form) {
        ctx.form.querySelector(".tidy-feedback-items-header")?.remove();
        ctx.form.querySelector(".tidy-feedback-items")?.remove();
        ctx.form.hidden = true;
        setFormFieldsVisibility(ctx.form, true);
    }

    ctx.hideFormDragHandle();

    if (ctx.start) {
        ctx.start.hidden = false;
    }
}
