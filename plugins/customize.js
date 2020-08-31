// For Board only.
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'view'
    this.preview = null     // switching is too heavy.

    this._apply = function (target, value) {
        document.body.className = document.body.className
            .replace(/\bbv_\w+|$/, ' bv_' + value)
    }
})

KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'archived'

    this._apply = function (target, value) {
        target.filter(this.name, function (card) {
            return +value ? card.data.archived : !card.data.archived
        })
    }
})

KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'relatedName'

    this._apply = function (target, value) {
        target.node.classList.add("cust-rnfilter")

        target.filter(this.name, function (card) {
            return card.data.related_name == value
        })
    }

    var inherited = this.cancel
    this.cancel = function (target) {
        ;(target || this.target).node.classList.remove("cust-rnfilter")
        return inherited.apply(this, arguments)
    }
})

KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'sort'

    this._apply = function (target, value) {
        var prop = value.replace(/-/, '')
        target.sort(function (a, b) {
            a = a.data
            b = b.data
            if (typeof a[prop] == 'number') {
                var res = a[prop] - b[prop]
            } else {
                var res = a[prop] > b[prop] ? +1 : -1
            }
            return (value[0] == '-' ? -1 : +1) * res
        })
    }

    this.cancel = function (target) {
        ;(target || this.target).sort()
        target || this.hooks.dispatchEvent(new Event('cancel'))
    }
})

// For Board only.
// Requires that target object has a node property (Element).
KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'cardColors'

    this._apply = function (target, value) {
        target.node.className = target.node.className
            .replace(/\blists_bg-mode_\w+/g, '')
            + ' lists_bg-mode_' + value
    }
})

KanbaniWeb.customizers.push(function () {
    KanbaniWeb.Customizer.apply(this, arguments)
    this.name = this.name || 'filter'
    this.default = ""
    var timer = null

    this.hook = function () {
        this.node.addEventListener('change', this.schedule)
        this.node.addEventListener('click', this.schedule)
        this.node.addEventListener('keypress', this.schedule)
        this.node.addEventListener('keyup', this.schedule)
    }

    this.schedule = function (e) {
        if (e.keyCode == 13) {
            // Prevent form submission.
            e.preventDefault()
        }
        clearTimeout(timer)
        timer = setTimeout(this.apply.bind(this, null, null), 50)
    }.bind(this)

    this.apply = function (target) {
        var re = this.node.value.split(/\s+/)
            .map(KanbaniWeb.escapeRegExp).join('|')

        if (re.length) {
            re = new RegExp(re, 'i')
            ;(target || this.target).filter(this.name, function (card) {
                return re.test(card.data.title + card.data.description)
            })
            target || this.hooks.dispatchEvent(new Event('apply'))
        } else {
            this.cancel(target)
        }
    }
})
