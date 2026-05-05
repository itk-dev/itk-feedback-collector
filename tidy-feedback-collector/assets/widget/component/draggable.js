/**
 * Makes an element draggable by a handle or the element itself
 * @param {HTMLElement} element - The element to make draggable
 * @param {string|HTMLElement} handle - CSS selector or element to use as drag handle (optional)
 * @param {Object} options - Configuration options
 */
export function makeDraggable(element, handle = null, options = {}) {
    const config = {
        constrainToViewport: true,
        onDragStart: null,
        onDrag: null,
        onDragEnd: null,
        ...options,
    };

    let dragging = false;
    let originalX = 0;
    let originalY = 0;
    let originalMouseX = 0;
    let originalMouseY = 0;

    // Determine the drag handle
    const dragHandle = handle
        ? typeof handle === "string"
            ? element.querySelector(handle)
            : handle
        : element;

    if (!dragHandle) {
        console.warn("Drag handle not found");
        return;
    }

    function dragStart(e) {
        e.preventDefault();
        dragging = true;

        // Store initial positions
        const rect = element.getBoundingClientRect();
        originalX = rect.left;
        originalY = rect.top;
        originalMouseX = e.pageX;
        originalMouseY = e.pageY;

        // Add dragging class for styling
        element.classList.add("dragging");
        dragHandle.classList.add("dragging");

        // Add listeners
        document.addEventListener("mousemove", drag);
        document.addEventListener("mouseup", dragEnd);

        // Call callback
        if (config.onDragStart) config.onDragStart(e, element);
    }

    function drag(e) {
        if (!dragging) return;

        e.preventDefault();

        const newX = originalX + (e.pageX - originalMouseX);
        const newY = originalY + (e.pageY - originalMouseY);

        // Constrain to viewport if enabled
        if (config.constrainToViewport) {
            const rect = element.getBoundingClientRect();
            const maxX = window.innerWidth - rect.width;
            const maxY = window.innerHeight - rect.height;

            const constrainedX = Math.max(0, Math.min(newX, maxX));
            const constrainedY = Math.max(0, Math.min(newY, maxY));

            element.style.left = `${constrainedX}px`;
            element.style.top = `${constrainedY}px`;
        } else {
            element.style.left = `${newX}px`;
            element.style.top = `${newY}px`;
        }

        // Call callback
        if (config.onDrag) config.onDrag(e, element);
    }

    function dragEnd(e) {
        e.preventDefault();
        dragging = false;

        // Remove dragging classes
        element.classList.remove("dragging");
        dragHandle.classList.remove("dragging");

        // Clean up listeners
        document.removeEventListener("mousemove", drag);
        document.removeEventListener("mouseup", dragEnd);

        // Call callback
        if (config.onDragEnd) config.onDragEnd(e, element);
    }

    // Attach the drag start event
    dragHandle.addEventListener("mousedown", dragStart);

    // Make sure the element is positioned
    if (getComputedStyle(element).position === "static") {
        element.style.position = "fixed";
    }

    // Return cleanup function
    return () => {
        dragHandle.removeEventListener("mousedown", dragStart);
        document.removeEventListener("mousemove", drag);
        document.removeEventListener("mouseup", dragEnd);
    };
}
