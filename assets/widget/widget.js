import widgetCss from "./styles/widget.css";
import btnCss from "./styles/btn.css";
import variablesCss from "./styles/variables.css";
import widgetRegionCss from "./styles/widget-region.css";
import { makeResizableDiv } from "./component/region";
import { showMessage } from "./component/messages";
import { enterSelectMode } from "./component/select-mode";
import {
    renderItemsList,
    refreshFeedbackData,
    showItemsPanel,
    hideItemsPanel,
} from "./component/items-panel";
import {
    makeFormDraggable,
    hideFormDragHandle,
    prefillEmail,
    showFormAfterSelection,
    showForm,
    hideForm,
    initFormSubmit,
} from "./component/form";
import { initKeyboardShortcuts } from "./component/keyboard";

(function () {
    "use strict";

    const script = document.currentScript;
    const apiKey = script.getAttribute("data-api-key");
    const srcUrl = new URL(script.src);
    const endpoint = srcUrl.origin + "/api/feedback";

    if (!apiKey) {
        console.error(
            "TidyFeedback: data-api-key attribute is required.",
        );
        return;
    }

    function init() {
        // Create the widget host element with Shadow DOM
        const host = document.createElement("div");
        host.id = "tidy-feedback";
        document.body.appendChild(host);

        const shadow = host.attachShadow({ mode: "open" });

        // Inject CSS into shadow DOM
        const style = document.createElement("style");
        style.textContent = variablesCss + btnCss + widgetCss;
        shadow.appendChild(style);

        // Inject widget HTML into shadow DOM
        shadow.innerHTML += `
            <div class="tidy-feedback">
                <div hidden class="tidy-feedback-message"
                     style="position:fixed;top:0.5em;left:50%;transform:translateX(-50%);z-index:10002"></div>
                <div hidden class="tidy-feedback-start">
                    <button hidden type="button" class="tidy-feedback-start-count">
                        <span class="tidy-feedback-badge">0</span>
                    </button>
                    <button type="button" class="tidy-feedback-start-add"
                            data-tidy-feedback-action="start"
                            title="Shift+C">+ Add feedback</button>
                </div>

                <form hidden class="tidy-feedback-form" method="post">
                    <div hidden class="tidy-feedback-draggable-handle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <h1 class="tidy-feedback-form-title">Your feedback</h1>
                    <p class="tidy-feedback-form-lead">Tell us what you noticed on this page</p>

                    <div class="form-row mb-3">
                        <label class="form-label" for="tidy-created-by">Your email address</label>
                        <input class="form-control" type="email" name="created_by" id="tidy-created-by"
                               placeholder="Your email address">
                    </div>

                    <div class="form-row mb-3">
                        <label class="form-label" for="tidy-description">Description</label>
                        <textarea class="form-control" name="description" id="tidy-description"
                                  placeholder="Describe what happened and what you expected"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" title="Ctrl+Enter">Submit feedback</button>
                    <button type="button" class="btn btn-cancel" data-tidy-feedback-action="cancel" title="Escape">Cancel</button>
                </form>
            </div>
        `;

        // Create region element OUTSIDE shadow DOM
        const regionContainer = document.createElement("div");
        regionContainer.id = "tidy-feedback-region";
        regionContainer.hidden = true;

        const regionStyle = document.createElement("style");
        regionStyle.textContent = variablesCss + widgetRegionCss;
        regionContainer.appendChild(regionStyle);

        const overlays = document.createElement("div");
        overlays.className = "overlays";
        for (const pos of ["top", "left", "right", "bottom"]) {
            const div = document.createElement("div");
            div.className = pos;
            div.textContent = pos;
            overlays.appendChild(div);
        }
        regionContainer.appendChild(overlays);

        const resizable = document.createElement("div");
        resizable.className = "resizable";
        const resizers = document.createElement("div");
        resizers.className = "resizers";
        for (const corner of [
            "top-left",
            "top-right",
            "bottom-left",
            "bottom-right",
        ]) {
            const div = document.createElement("div");
            div.className = `resizer ${corner}`;
            resizers.appendChild(div);
        }
        resizable.appendChild(resizers);
        regionContainer.appendChild(resizable);

        document.body.appendChild(regionContainer);

        // Try to register CSS custom property for animated border
        try {
            CSS.registerProperty({
                name: "--border-angle",
                syntax: "<angle>",
                inherits: false,
                initialValue: "0deg",
            });
        } catch {
            // Already registered or not supported
        }

        // Set up context
        const root = shadow;
        const widget = root;

        const getElement = (selector) => widget.querySelector(selector);
        const getActionElement = (action) =>
            getElement(`[data-tidy-feedback-action="${action}"]`);
        const getDocumentElement = (selector) =>
            document.querySelector(selector);

        const config = {
            endpoint: endpoint,
            apiKey: apiKey,
            messages: {
                "Taking screenshot \u2026": "Taking screenshot \u2026",
                "Error taking screenshot": "Error taking screenshot",
                "Sending feedback \u2026": "Sending feedback \u2026",
                "Feedback created": "Feedback created",
                "Existing feedback": "Existing feedback",
                "Click an element to select": "Click an element to select",
            },
        };

        const ctx = {
            config,
            form: null,
            start: null,
            startCount: null,
            region: null,
            dragCleanup: null,
            feedbackItems: [],
            selectedSelector: null,
            itemsPanelMode: false,

            getElement,
            getDocumentElement,

            showMessage: (message, type) =>
                showMessage(root, config, message, type),
            enterSelectMode: () => enterSelectMode(ctx),
            positionRegion: (rect) => {
                if (ctx.region) {
                    ctx.region.parentNode.hidden = false;
                    ctx.region.style.left = rect.left + "px";
                    ctx.region.style.top = rect.top + "px";
                    ctx.region.style.width = Math.max(rect.width, 20) + "px";
                    ctx.region.style.height = Math.max(rect.height, 20) + "px";
                    makeResizableDiv(ctx.region);
                }
            },
            hideRegion: () => {
                if (ctx.region) {
                    ctx.region.parentNode.hidden = true;
                }
            },
            makeFormDraggable: () => makeFormDraggable(ctx),
            hideFormDragHandle: () => hideFormDragHandle(ctx),
            showFormAfterSelection: () => showFormAfterSelection(ctx),
            showForm: () => showForm(ctx),
            hideForm: (reset) => hideForm(ctx, reset),
            showItemsPanel: () => showItemsPanel(ctx),
            hideItemsPanel: () => hideItemsPanel(ctx),
            refreshFeedbackData: () => refreshFeedbackData(ctx),
            renderItemsList: (listOnly) => renderItemsList(ctx, listOnly),
        };

        ctx.form = getElement(".tidy-feedback-form");
        ctx.start = getElement(".tidy-feedback-start");
        ctx.startCount = getElement(".tidy-feedback-start-count");
        ctx.region = getDocumentElement(
            "#tidy-feedback-region > .resizable",
        );

        const startAdd = getActionElement("start");
        const cancel = getActionElement("cancel");

        if (ctx.form) {
            prefillEmail(ctx.form);
            initFormSubmit(ctx);
        }

        if (ctx.start) {
            ctx.start.hidden = false;

            if (startAdd) {
                startAdd.addEventListener("click", () => {
                    ctx.showForm();
                });
            }

            if (ctx.startCount) {
                ctx.startCount.addEventListener("click", () => {
                    ctx.showItemsPanel();
                });
            }
        }

        if (cancel) {
            cancel.addEventListener("click", () => {
                ctx.showMessage("");
                ctx.hideForm(true);
            });
        }

        initKeyboardShortcuts(ctx);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
