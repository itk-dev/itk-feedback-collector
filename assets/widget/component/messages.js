export function showMessage(root, config, message, type = null) {
    const el = root.querySelector(".tidy-feedback-message");
    if (el) {
        el.classList.remove("success", "warning", "danger", "select");

        if (type) {
            el.classList.add(type);
        }

        el.innerHTML = config.messages[message] ?? message;
        el.hidden = !message;
    }
}
