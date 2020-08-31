;(function () {
    var toggle = document.querySelector('.brd-toc-toggle')
    var toc = document.querySelector('.brd-toc')
    if (!toggle || !toc) { return }

    var visible = false

    function hideToC() {
        visible = false
        toc.classList.remove('brd-toc_float')
        document.body.removeEventListener("mousemove", hideToC)
    }

    toggle.addEventListener('mousemove', function (e) {
        if (!visible && location.hash != '#toc') {
            visible = true
            toc.classList.add('brd-toc_float')
            document.body.addEventListener("mousemove", hideToC)
        }
        e.stopPropagation()
    })

    toggle.addEventListener('click', hideToC)
    toc.addEventListener('click', hideToC)
    toc.addEventListener('mousemove', function (e) {
        e.stopPropagation()
    })
})()