function reindexInputs(fieldOfTargetedId) {
    var escapedFieldOfTargetedId = fieldOfTargetedId.replace(/\./g, "\\.");
    $(`[id^=${escapedFieldOfTargetedId}]`).each(function (index) {
        var newId = `${fieldOfTargetedId}_${index}`;
        this.id = newId;

        $(this)
            .children()
            .each(function () {
                if (this.id) {
                    var idParts = this.id.split("_");
                    idParts[idParts.length - 1] = index; // Update the index part
                    this.id = idParts.join("_");
                }
                if (this.name) {
                    var nameMatch = this.name.match(/(.+?)\[(\d+)\]$/);
                    if (nameMatch) {
                        var baseName = nameMatch[1]; // The part before the index
                        this.name = `${baseName}[${index}]`;
                    }
                }

                // Set data-changed attribute to true
                $(this).attr("data-changed", "true");
            });
    });
}

$(".form-group").on("click", ".add-more", function (e) {
    e.preventDefault();

    var oldInputGroupID = e.target.id.replace("add_", "");
    var escapedOldInputGroupID = "#" + oldInputGroupID.replace(/\./g, "\\.");
    var partsOfClickedId = oldInputGroupID.split("_");
    var clickedIdNumber = parseInt(partsOfClickedId.pop());
    var fieldOfTargetedId = partsOfClickedId.join("_");

    var newInput = $(escapedOldInputGroupID).clone(true);

    // Clear textarea and set data-changed attribute
    $("textarea", newInput).text("");
    $("textarea", newInput).attr("data-changed", "true");

    // Append the new input group
    $(escapedOldInputGroupID).after(newInput);

    // Reindex all input groups for this field
    reindexInputs(fieldOfTargetedId);
});

$(".form-group").on("click", ".remove-me", function (e) {
    e.preventDefault();

    var inputGroupID = e.target.id.replace("remove_", "");
    var escapedInputGroupID = "#" + inputGroupID.replace(/\./g, "\\.");
    var partsOfClickedId = inputGroupID.split("_");
    var fieldOfTargetedId = partsOfClickedId.slice(0, -1).join("_");

    // Add the removed entry ID to the hidden input
    var removedEntries = $("#removedEntries").val();
    removedEntries = removedEntries ? removedEntries.split(",") : [];
    removedEntries.push(inputGroupID);
    $("#removedEntries").val(removedEntries.join(","));

    // Remove the input group from the DOM
    $(escapedInputGroupID).remove();

    // Reindex all input groups for this field
    reindexInputs(fieldOfTargetedId);
});
