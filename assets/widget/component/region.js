// https://medium.com/the-z/making-a-resizable-div-in-js-is-not-easy-as-you-think-bda19a1bc53d
import { makeDraggable } from "./draggable.js";

export function makeResizableDiv(element) {
    const resizers = element.querySelectorAll(".resizer");
    const minimum_size = 20;
    let original_width = 0;
    let original_height = 0;
    let original_x = 0;
    let original_y = 0;
    let original_mouse_x = 0;
    let original_mouse_y = 0;

    const top = element.parentNode.querySelector(".overlays .top");
    const left = element.parentNode.querySelector(".overlays .left");
    const right = element.parentNode.querySelector(".overlays .right");
    const bottom = element.parentNode.querySelector(".overlays .bottom");

    const updateOverlay = () => {
        if (top) {
            top.style.height = element.style.top;
        }
        if (left) {
            left.style.top = element.style.top;
            left.style.height = element.style.height;
            left.style.width = element.style.left;
        }
        if (right) {
            right.style.top = element.style.top;
            right.style.height = element.style.height;
            right.style.left =
                parseInt(element.style.left, 10) +
                parseInt(element.style.width, 10) +
                "px";
        }
        if (bottom) {
            bottom.style.top =
                parseInt(element.style.top, 10) +
                parseInt(element.style.height, 10) +
                "px";
        }
    };

    for (let i = 0; i < resizers.length; i++) {
        const currentResizer = resizers[i];
        currentResizer.addEventListener("mousedown", function (e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent drag from triggering

            original_width = parseFloat(
                getComputedStyle(element, null)
                    .getPropertyValue("width")
                    .replace("px", ""),
            );
            original_height = parseFloat(
                getComputedStyle(element, null)
                    .getPropertyValue("height")
                    .replace("px", ""),
            );
            original_x = element.getBoundingClientRect().left;
            original_y = element.getBoundingClientRect().top;
            original_mouse_x = e.pageX;
            original_mouse_y = e.pageY;
            window.addEventListener("mousemove", resize);
            window.addEventListener("mouseup", stopResize);
        });

        function resize(e) {
            if (currentResizer.classList.contains("bottom-right")) {
                const width = original_width + (e.pageX - original_mouse_x);
                const height = original_height + (e.pageY - original_mouse_y);
                if (width > minimum_size) {
                    element.style.width = width + "px";
                }
                if (height > minimum_size) {
                    element.style.height = height + "px";
                }
            } else if (currentResizer.classList.contains("bottom-left")) {
                const height = original_height + (e.pageY - original_mouse_y);
                const width = original_width - (e.pageX - original_mouse_x);
                if (height > minimum_size) {
                    element.style.height = height + "px";
                }
                if (width > minimum_size) {
                    element.style.width = width + "px";
                    element.style.left =
                        original_x + (e.pageX - original_mouse_x) + "px";
                }
            } else if (currentResizer.classList.contains("top-right")) {
                const width = original_width + (e.pageX - original_mouse_x);
                const height = original_height - (e.pageY - original_mouse_y);
                if (width > minimum_size) {
                    element.style.width = width + "px";
                }
                if (height > minimum_size) {
                    element.style.height = height + "px";
                    element.style.top =
                        original_y + (e.pageY - original_mouse_y) + "px";
                }
            } else {
                const width = original_width - (e.pageX - original_mouse_x);
                const height = original_height - (e.pageY - original_mouse_y);
                if (width > minimum_size) {
                    element.style.width = width + "px";
                    element.style.left =
                        original_x + (e.pageX - original_mouse_x) + "px";
                }
                if (height > minimum_size) {
                    element.style.height = height + "px";
                    element.style.top =
                        original_y + (e.pageY - original_mouse_y) + "px";
                }
            }

            updateOverlay();
        }

        function stopResize() {
            window.removeEventListener("mousemove", resize);
            window.removeEventListener("mouseup", stopResize);
        }
    }

    // Add drag functionality using the draggable utility
    makeDraggable(element, ".resizers", {
        onDrag: updateOverlay, // Update overlays when dragging
    });

    updateOverlay();
}
