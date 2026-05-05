export function initKeyboardShortcuts(ctx) {
    document.addEventListener("keydown", (event) => {
        const isFormField =
            event.target.tagName === "INPUT" ||
            event.target.tagName === "TEXTAREA" ||
            event.target.tagName === "SELECT" ||
            event.target.isContentEditable;

        // Shift+C: start new feedback (only when not typing in a field).
        if (
            event.key === "C" &&
            event.shiftKey &&
            !event.ctrlKey &&
            !event.metaKey &&
            !isFormField
        ) {
            if (ctx.start && !ctx.start.hidden) {
                event.preventDefault();
                ctx.showForm();
            }
            return;
        }

        // Ctrl/Cmd+Enter: submit feedback form.
        if (
            event.key === "Enter" &&
            (event.ctrlKey || event.metaKey) &&
            ctx.form &&
            !ctx.form.hidden &&
            !ctx.itemsPanelMode
        ) {
            event.preventDefault();
            ctx.form.requestSubmit();
            return;
        }

        // Escape: cancel feedback or close items panel.
        if (event.key === "Escape") {
            if (ctx.itemsPanelMode) {
                event.preventDefault();
                ctx.hideItemsPanel();
                return;
            }
            if (ctx.form && !ctx.form.hidden) {
                event.preventDefault();
                ctx.showMessage("");
                ctx.hideForm(true);
            }
        }
    });
}
