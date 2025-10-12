function toggleAuthorEntry(authorId) {
    const partsOfAuthorId = authorId.split('_');
    const authorNumber = parseInt(partsOfAuthorId.pop());

    const authorElement = document.getElementById(authorId);
    const childFormGroups = authorElement.querySelectorAll('.form-group');

    const recordJSONElement = document.getElementById('recordJSON');
    const decodedJSON = decodeHtmlEntities(recordJSONElement.value);
    let record;

    try {
        record = JSON.parse(decodedJSON);
    } catch (error) {
        console.error("Failed to parse recordJSON:", error);
        record = {};
    }

    const affiliations = Object.values(record['dc.contributor.affiliation']);

    console.log('Affiliations:', affiliations, typeof affiliations);

    childFormGroups.forEach((formGroup) => {
        if (formGroup.classList.contains('d-none')) {
            formGroup.classList.remove('d-none');
        }

        if (formGroup.classList.contains('affiliation') && affiliations.length > 0) {
            const authorsAffiliations = [];

            const hiddenInputs = formGroup.querySelectorAll('input[type="hidden"]');
            hiddenInputs.forEach((input) => {
                authorsAffiliations.push(input.value);
                input.remove();
            });

            affiliations.forEach((affiliation, index) => {
                const checkboxId = `dc.contributor.author_${authorNumber}_dc.contributor.affiliation_${index}`;

                const checkbox = document.createElement('input');
                checkbox.setAttribute('type', 'checkbox');
                checkbox.setAttribute('id', checkboxId);
                checkbox.setAttribute('name', `recordChanges[dc.contributor.author][dc.contributor.affiliation][${authorNumber}][]`);
                checkbox.setAttribute('value', affiliation);
                checkbox.setAttribute('class', 'form-check-input');

                if (authorsAffiliations.includes(affiliation)) {
                    checkbox.checked = true;
                }

                const label = document.createElement('label');
                label.setAttribute('for', checkboxId);
                label.textContent = affiliation;

                formGroup.appendChild(checkbox);
                formGroup.appendChild(label);

                const br = document.createElement('br');
                formGroup.appendChild(br);
            });
        } else {
            const input = formGroup.querySelector('input');
            const inputName = input.getAttribute('name');
            const inputValue = input.value;

            const textarea = document.createElement('textarea');
            textarea.setAttribute('name', inputName.replace('record', 'recordChanges'));
            textarea.setAttribute('class', 'form-control');
            textarea.setAttribute('data-changed', 'false');
            textarea.textContent = inputValue;

            input.parentNode.insertBefore(textarea, input.nextSibling);
            input.remove();
        }

        const spans = formGroup.querySelectorAll('span');
        spans.forEach((span) => {
            span.classList.add('d-none');
        });
    });

    const editButton = authorElement.querySelector('.edit-button');
    if (editButton) {
        editButton.classList.add('d-none');
    }
}

function decodeHtmlEntities(html) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = html;
    return textarea.value;
}
