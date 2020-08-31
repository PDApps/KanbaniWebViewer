document.querySelectorAll("[data-kwv-inst]").forEach(function (node) {
    var mode = node.getAttribute("data-kwv-inst")

    var url = "?do=install&subdo=" + (mode == "install" ? "authes" : "cfges")
    KanbaniWeb.eventSource(url, function () {
        this.close()
        location.replace("?")   // reload() brings up "resubmit?" prompt if this is a POST page.
    })

    if (mode == "installing") {
        function updateClasses() {
            document.body.className = document.body.className
                .replace(/\binst_transp_\w+|\binst_auth_\w+/g, "")
                + " inst_transp_" + (qrTransport.value ? "yes" : "no")
                + " inst_auth_" + (qrAuthPassword.checked ? "yes" : "no")
        }
        qrTransport.addEventListener("change", updateClasses)
        qrAuthNo.addEventListener("change", updateClasses)
        qrAuthPassword.addEventListener("change", updateClasses)
        updateClasses()
    }
})