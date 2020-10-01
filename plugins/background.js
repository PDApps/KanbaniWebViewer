// For Board only.
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || "background"

    this._apply = function (target, value) {
        document.body.style.backgroundImage = 'url("' + value + '")'
    }
})
