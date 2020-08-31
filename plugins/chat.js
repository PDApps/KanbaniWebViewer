document.querySelectorAll(".chat").forEach(function (node) {
    var title    = node.querySelector(".chat__title")
    var messages = node.querySelector(".chat__msgs")
    var textarea = node.querySelector(".chat__text")
    var form  = document.getElementById(textarea.getAttribute("form"))
    var frame = document.getElementById(form.getAttribute("target"))
    var online
    KanbaniWeb.eventSource(form.action, function (e) {
        node.classList.remove("filtered")   // successfully initialized, show the badge.
        var flash
        if (e.type == "online") {
            flash = online < +e.data   // somebody new has come online.
            title.textContent = online = +e.data
            title.classList.toggle("chat__title_others", online > 1)
        } else if (e.type == "update") {
            node.classList.add("chat_update")
        } else {
            flash = e.type != "history"
            var count = messages.children.length
            messages.innerHTML += e.data
            if (flash) {
                var old = messages.querySelector(".chat__new-msg")
                if (old) { old.classList.remove("chat__new-msg") }
                messages.children[count].classList.add("chat__new-msg")
                messages.children[count].scrollIntoView()
            }
        }
        if (flash) {
            title.classList.remove("chat__title_flash")
            setTimeout(function () {
                title.classList.add("chat__title_flash")
            }, 0)
        }
    }, ["online", "history", "update"])
    frame.addEventListener("load", function () {
        textarea.value = ""
        textarea.disabled = false
    })
    textarea.disabled = false
    textarea.addEventListener("keydown", function (e) {
        if (e.keyCode == 13) {
            if (textarea.value.length) {
                form.submit()
                // Prevent user from submitting another message until this one was received.
                textarea.disabled = true
            }
            e.preventDefault()
        }
    })
    node.addEventListener("mousemove", function () {
        title.classList.remove("chat__title_flash")
    })
})
