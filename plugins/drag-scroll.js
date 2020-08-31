;(function () {
    var start = null
    var root = document.body.querySelector(".lists")
    if (!root) { return }

    function scroll(e) {
        root.scrollLeft = start[1] + (e.pageX - start[0].pageX) * -1
        root.scrollTop  = start[2] + (e.pageY - start[0].pageY)
    }

    root.addEventListener("mousedown", function (e) {
        if (e.target.classList.contains("lists") ||
                e.target.classList.contains("list")) {
            start = [e, root.scrollLeft, root.scrollTop]
            document.body.classList.add("body_scrolling")
            document.body.addEventListener("mousemove", scroll)
            e.preventDefault()
        }
    })

    root.addEventListener("mouseup", function (e) {
        document.body.classList.remove("body_scrolling")
        document.body.removeEventListener("mousemove", scroll)
    })
})()