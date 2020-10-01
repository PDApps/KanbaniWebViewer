document.querySelectorAll(".lists__list-resizer").forEach(function (node) {
    var start
    var list = node.previousElementSibling
    var style = document.createElement("style")
    document.head.appendChild(style)

    function scroll(e) {
        var width = start[1] + (e.pageX - start[0].pageX)
        style.textContent = '.bv_horiz [id="' + list.id + '"]{width:' + Math.max(10, width) + 'px}'
        list.classList.toggle("lists__list_collapsed", width < 10)
    }

    node.addEventListener("mousedown", function (e) {
        start = [e, parseInt(getComputedStyle(list).getPropertyValue("width")), Date.now()]
        document.body.addEventListener("mousemove", scroll)
        e.preventDefault()
    })

    document.body.addEventListener("mouseup", function (e) {
        if (start) {
            document.body.removeEventListener("mousemove", scroll)
            if (Date.now() - start[2] < 250 /*ms*/) {
                list.classList.toggle("lists__list_collapsed")
            }
            start = null
        }
    })

    addEventListener("hashchange", function () {
        if (location.hash == "#" + list.id ||
                list.querySelector('[id="' + location.hash.substr(1) + '"]')) {
            list.classList.remove("lists__list_collapsed")
        }
    })
})
