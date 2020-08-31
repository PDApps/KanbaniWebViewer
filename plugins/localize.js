// For Board only.
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || "lang"
    this.preview = null

    this._apply = function (target, value) {
        if (document.documentElement.getAttribute("lang") != value) {
            setTimeout(function () { location.reload() }, 0)
        }
    }
})

// For Board only.
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || "tz"
    this.preview = null

    this._apply = function (target, value) {
        if (document.body.getAttribute("data-tz") != value) {
            setTimeout(function () { location.reload() }, 0)
        }
    }
})
