// Requires that target object has a node property (Element).
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'descLines'

    this._apply = function (target, value) {
        target.node.querySelectorAll('.card-item__desc-snip')
            .forEach(function (node) {
                node.setAttribute('rows', value)
                node.classList.toggle('filtered', value < 1)
            })
    }
})

// Requires that target object has a node property (Element).
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'descDir'

    this._apply = function (target, value) {
        var value = value ? 1000000 : 0
        target.node.querySelectorAll('.card-item__desc-snip')
            .forEach(function (node) {
                node.scrollTo(value, value)
            })
    }
})

// Requires that target object has a node property (Element).
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'descBreaks'
    this.default = false
    this.preview = null

    this._apply = function (target) {
        var preserve = this.node.checked
        target.node.querySelectorAll('.card-item__desc-snip')
            .forEach(function (node) {
                // en spaces below
                node.value = node.value.replace(preserve ? / /g : /\n/g, preserve ? '\n' : ' ')
            })
    }

    this.serialize = function (prefix) {
        var o = {}
        o[prefix + this.name] = this.node.checked == this.default ? null : +this.node.checked
        return o
    }

    this.unserialize = function (prefix, obj) {
        var name = prefix + this.name
        this.node.checked = obj[name] ? !!obj[name] : false
        this.apply()
    }
})
