$(document).ready(function () {
    // Clear the removedEntries hidden input value on page load
    $("#removedEntries").val("");
});

document.addEventListener("DOMContentLoaded", function () {
    // Track changes in textareas
    document.querySelectorAll("textarea").forEach((textarea) => {
        textarea.addEventListener("input", function () {
            this.setAttribute("data-changed", "true");
        });
    });

    // Prepare form for submission by excluding unchanged textarea inputs
    const form = document.querySelector("form");
    form.addEventListener("submit", function () {
        this.querySelectorAll("textarea").forEach((textarea) => {
           if (textarea.getAttribute("data-changed") === "false") {
               textarea.setAttribute("name", ""); // Exclude from submission
           }
       });

        this.querySelectorAll("input").forEach((input) => {
            if (input.getAttribute("data-changed") === "false") {
                input.setAttribute("name", ""); // Exclude from submission
            }
        });
   });
});
