document.querySelectorAll('.import__group-input').forEach(function (node) {
    node.addEventListener('keydown', function (e) {
        if (e.keyCode == 13) {
            // Import form has multiple connected buttons which all have name=format.
            // User selects the format by clicking the appropriate button, which
            // then submits the form. But if he submits the form via Enter in an input
            // (such as encryption secret or board ID) - the form will be submitted
            // with the first submission button's format, e.g. "xcsv" instead of "kanbani".
            node.parentNode.lastElementChild.click()
            e.preventDefault()
        }
    })
})