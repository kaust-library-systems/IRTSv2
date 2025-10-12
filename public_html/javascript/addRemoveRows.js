$(document).ready(function(){
    $(".metadata-table").on('click', ".add-row", function(e){
        e.preventDefault();

        var oldInputGroupID = e.target.id.replace("add_", "");
        var escapedOldInputGroupID = "#"+oldInputGroupID.replace(/\./g, "\\.");
        var partsOfClickedId = oldInputGroupID.split("_");
        var clickedIdNumber = parseInt(partsOfClickedId.pop());
        var fieldOfTargetedId = partsOfClickedId.join("_");
        var escapedFieldOfTargetedId = fieldOfTargetedId.replace(/\./g, "\\.");

        $(escapedOldInputGroupID).nextAll(`[id^=${escapedFieldOfTargetedId}]`).each(function(){
            var partsOfId = this.id.split("_");
            var place = parseInt(partsOfId.pop());
            var field = partsOfId.join("_");
            this.id = `${field}_${place+1}`;
        });

        var newInputGroupID = `${fieldOfTargetedId}_${clickedIdNumber+1}`;
        var escapedNewInputGroupID = "#"+newInputGroupID.replace(/\./g, "\\.");

        var newInput = $(escapedOldInputGroupID).closest('tr').clone(false);

        $("textarea", newInput).text("");

        $("*", newInput).add(newInput).each(function() {
            if (this.id) {
                this.id = this.id.replace(oldInputGroupID,newInputGroupID);
            }
            if (this.name) {
                this.name = this.name.replace(clickedIdNumber,`${clickedIdNumber+1}`);
            }
        });

        $(escapedOldInputGroupID).closest('table').append(newInput);
    });

    $(".metadata-table").on('click', ".remove-row", function(e){
        e.preventDefault();

        var inputGroupID = e.target.id.replace("remove_", "");
        var escapedInputGroupID = "#"+inputGroupID.replace(/\./g, "\\.");
        
        $(escapedInputGroupID).remove();

        var partsOfClickedId = inputGroupID.split("_");
        var clickedIdNumber = parseInt(partsOfClickedId.pop());
        var fieldOfTargetedId = partsOfClickedId.join("_");
        var escapedFieldOfTargetedId = fieldOfTargetedId.replace(/\./g, "\\.");
        var previousInputGroupID = `${fieldOfTargetedId}_${clickedIdNumber-1}`;
        var escapedPreviousInputGroupID = "#"+previousInputGroupID.replace(/\./g, "\\.");

        $(escapedPreviousInputGroupID).nextAll(`[id^=${escapedFieldOfTargetedId}]`).each(function(){
            var partsOfId = this.id.split("_");
            var place = parseInt(partsOfId.pop());
            var field = partsOfId.join("_");
            this.id = `${field}_${place-1}`;
        });
    });
});
