/* https://pdapps.org/kanbani/web | License: MIT */
"use strict"

document.body.classList.remove("js_no")
document.body.classList.add("js_yes")

document.body.addEventListener("click", function (e) {
    if (e.target.classList.contains("info-hint__title")) {
        // Explicit visibility toggle for touch screens.
        e.target.parentNode.classList.toggle("info-hint_visible")
    } else if (e.target.classList.contains("switch__go")) {
        var parent = e.target
        while (parent && !parent.classList.contains("switch")) {
            parent = parent.parentNode
        }
        var n = e.target.className.match(/\bswitch__go_n_(\w+)/)[1]
        var vis = "switch__item_visible"
        var node = parent.querySelector("." + vis)
        if (node) { node.classList.remove(vis) }
        parent.querySelector(".switch__item_n_" + n).classList.add(vis)
        e.preventDefault()
    }
})

document.querySelectorAll('[data-kwv-do="viewCard"][href]').forEach(function (node) {
    node.addEventListener("click", function (e) {
        var shader = document.createElement("div")
        shader.className = "body-overlay__shader"
        document.body.appendChild(shader)
        var overlay = document.createElement("div")
        overlay.className = "body-overlay"
        document.body.appendChild(overlay)
        overlay.onclick = function (e) {
            if (e.target == this) {
                document.body.removeChild(shader)
                document.body.removeChild(overlay)
            }
        }
        KanbaniWeb.fetch(node.href, null, function (html) {
            overlay.innerHTML = html
            overlay.firstElementChild.classList.add("body-overlay__content")
        })
        e.preventDefault()
    })
})

document.querySelectorAll(".hdr__brds").forEach(function (node) {
    node.addEventListener("change", function () {
        location.href += (location.search ? "&" : "?") +
            encodeURIComponent(node.name) + "=" + encodeURIComponent(node.value)
    })
})

// Ideally, this should be a symbol that is not URL-encoded and is not used in identifiers
// (i.e. is \W). The only such symbol is ".", but PHP's parse_str() turns "." into "_" producing
// different URL when encoded back.
// Therefore using "~" because it is not encoded with JavaScript's encodeURIComponent() so in
// some cases resulting URLs will be shorter, and if such an URL was subject to PHP's encoding
// then it will be longer but will still work.
var idSeparator = "~"
var KanbaniWeb = {
    location: null,
    board: null,
    customizers: [],
    skipSerialize: false,

    escapeRegExp: function (s) {
        return s.replace(/[[\\^$.|?*+(){}]/g, "\\$&")
    },

    eventSource: function (url, onmessage, types) {
        var es
        function connect() {
            if (es && es.readyState != es.CLOSED) { return }
            try {
                es = new EventSource(url)
            } catch (e) {
                return setTimeout(connect, 1000)
            }
            es.onerror = function () { setTimeout(connect, 250) }
            ;["message"].concat(types).forEach(function (type) {
                es.addEventListener(type, onmessage)
            })
        }
        connect()
    },

    fetch: function (url, data, success, error, complete) {
        var xhr = new XMLHttpRequest
        xhr.onreadystatechange = function () {
          if (xhr.readyState == 4) {
              xhr.status == 200 ? (success && success(xhr.responseText)) : (error && error())
              if (complete) { complete() }
          }
        }
        xhr.open(data ? "POST" : "GET", url, true)
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest")
        if (data) {
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
        }
        xhr.responseType = "text"
        xhr.send(data)
        return xhr
    },

    initializeCustomizers: function (target, prefix, node) {
        var all = {}
        KanbaniWeb.customizers.forEach(function (Cust) {
            var cust = new Cust(target)
            cust.node = cust.findNode(node)
            if (cust.node) {
                cust.hook()
                cust.unserialize(prefix, KanbaniWeb.location.query())
                cust.hooks.addEventListener("apply", serialize)
                cust.hooks.addEventListener("cancel", serialize)
                all[cust.name] = cust
                function serialize() {
                    if (!KanbaniWeb.skipSerialize) {
                        var query = KanbaniWeb.location.query()
                        var ser = cust.serialize(prefix)
                        for (var k in ser) {
                            ser[k] == null ? (delete query[k]) : (query[k] = ser[k])
                        }
                        KanbaniWeb.location.push(query)
                    }
                }
            }
        })
        KanbaniWeb.location.hooks.addEventListener("unserialize", function () {
            for (var k in all) {
                all[k].unserialize(prefix, KanbaniWeb.location.query())
            }
        })
        return all
    },

    Board: function (data) {
        this.data = data
        this.node = document.body

        var cust = this.node.querySelector("[data-kwv-board-customizations]")
        // Board customization is handled both by backend and frontend and so
        // using no prefix here to match ?query parameters of the <form>.
        // List customizations are frontend-only and are not recognized by the
        // backend so they are using special prefixes.
        this.customizers = KanbaniWeb.initializeCustomizers({
            filter: function () {},
            sort: function () {},
            node: document.createElement("a"),  // dummy.
        }, "", cust)
        Object.keys(this.customizers).forEach(function (name) {
            var propagate = function () {
                Object.keys(this.lists).forEach(function (id) {
                    var list = this.lists[id]
                    if (list.customizers[name] && !list.customizers[name].isOverridden) {
                        cust.apply(list)
                    }
                }, this)
            }.bind(this)
            var cust = this.customizers[name]
            cust.hooks.addEventListener("apply", propagate)
            cust.hooks.addEventListener("cancel", propagate)
        }, this)

        this.lists = {}
        data.lists.forEach(function (list) {
            if ("cards" in list) {
                this.lists[list.id] = new KanbaniWeb.List(this, list, document.getElementById(list.id))
            }
        }, this)
    },

    List: function (board, data, node) {
        this.data = data
        this.node = node
        this.board = board
        var filters = {}
        var sorter

        this.filter = function (name, func) {
            func ? (filters[name] = func) : (delete filters[name])
            this._refresh()
        }
        this.sort = function (func) {
            sorter = func
            this._refresh()
        }
        this._refresh = function () {
            var count = 0
            for (var id in this.cards) {
                var visible = true
                for (var k in filters) {
                    if (!(visible = filters[k](this.cards[id]))) {
                        break
                    }
                }
                this.cards[id].toggle(visible)
                var tocNode = document.querySelector('[data-kwv-toc="' + this.cards[id].data.id + '"]')  //XXX make an event
                if (tocNode) { tocNode.classList.toggle("filtered", !visible) }
                if (visible) { count++ }
            }
            node.querySelector("[data-kwv-list-counter]").textContent = count
            node.querySelector("[data-kwv-list-empty]").classList.toggle("filtered", count > 0)
            Object.keys(this.cards)
                .sort(function (a, b) {
                    return sorter ? sorter(this.cards[a], this.cards[b])
                        : this.cards[a].position - this.cards[b].position
                }.bind(this))
                .forEach(function (id, index) {
                    this.cards[id].node.parentNode
                        .insertBefore(this.cards[id].node, this.cards[id].node.parentNode.children[index])
                }, this)
        }

        this._createFilterCheckbox = function (list, cust) {
            var checkbox = cust.node.parentNode.insertBefore(
                    document.querySelector("[data-kwv-override]").cloneNode(true),
                    cust.node)
            checkbox.removeAttribute("data-kwv-override")
            var param = data.id + idSeparator + "_" + cust.name
            unserialize()
            function unserialize() {
                checkbox.checked = !+KanbaniWeb.location.query()[param]
                update(!checkbox.checked)
            }
            function update(override) {
                cust.node.disabled = !override
                cust.isOverridden = !!override
                if (override) {
                    cust.apply()
                    cust.node.focus()
                } else {
                    cust.cancel()
                    board.customizers[cust.name].apply(list)
                }
            }
            function preview(e) {
                var override = (e.type == 'mousemove') == checkbox.checked
                override == cust.isOverridden || update(override)
            }
            checkbox.addEventListener("mousemove", preview)
            checkbox.addEventListener("mouseout", preview)
            checkbox.addEventListener("change", function () {
                var override = !checkbox.checked
                var query = KanbaniWeb.location.query()
                override ? (query[param] = 1) : (delete query[param])
                KanbaniWeb.location.push(query)
                update(override)
            })
            KanbaniWeb.location.hooks.addEventListener("unserialize", unserialize)
        }

        this.cards = {}
        data.cards.forEach(function (card, index) {
            if ("title" in card) {
                this.cards[card.id] = new KanbaniWeb.Card(this, card, document.getElementById(card.id))
            }
        }, this)

        var cust = node.querySelector("[data-kwv-list-customizations]")
        board.node.querySelectorAll('[data-kwv-customize="list"]').forEach(function (node) {
            cust.parentNode.insertBefore(node.cloneNode(true), cust)
                .removeAttribute("data-kwv-customize")
        })
        this.customizers = KanbaniWeb.initializeCustomizers(this, data.id + idSeparator, cust.parentNode)
        for (var name in this.customizers) {
            if (board.customizers[name]) {
                this._createFilterCheckbox(this, this.customizers[name])
            }
        }
    },

    Card: function (list, data, node) {
        this.data = data
        this.node = node
        this.list = list
        this.visible = !node.classList.contains("filtered")

        this.toggle = function (visible) {
            if (this.visible != visible) {
                this.visible = visible
                node.classList.toggle("filtered", !visible)
            }
        }
    },

    Location: function (current) {
        var query = {}
        this.hooks = document.createElement("a")

        this.query = function () {
            return Object.assign({}, query)
        }

        this.merge = function () {
            var args = [{}, query].concat(Array.prototype.slice.apply(arguments))
            return Object.assign.apply(this, args)
        }

        this.push = function (obj) {
            return this._pushState('pushState', obj)
        }

        this.replace = function (obj) {
            return this._pushState('replaceState', obj)
        }

        this._pushState = function (func, obj) {
            query = obj
            try {
                var base = location.href.replace(/[?#].*$/, "")
                var url = base + this.serialize() + location.hash
                if (url != location.href) {
                    console.log("pushState: old/new\n" + location.href + "\n" + url)
                    history[func]({}, "", url)
                }
                this.hooks.dispatchEvent(new Event("pushState"))
            } catch (e) {
                console.error(e)
            }
        }

        this.unserialize = function (search) {
            var prefixes = []
            query = {}
            search
                .replace(/[?&]p=([^#?&]+)/, function (f, p) {
                    prefixes = p.split("~")
                    return ""
                })
                .replace(/[?&]([^#?&=]+)=([^#?&]*)/g, function (f, k, v) {
                    // idSeparator used in regexp.
                    k = k.replace(/^([^~]{1,2})~/, function (f, p) {
                        return (prefixes[parseInt(p, 36)] || p) + idSeparator
                    })
                    query[decodeURIComponent(k)] = decodeURIComponent(v)
                })
            this.hooks.dispatchEvent(new Event("unserialize"))
        }

        this.serialize = function () {
            var keys = Object.keys(query)
            if (!keys.length) { return "" }
            var strKeys = keys.join("\n")
            var prefixes = []
            // This assumes there are no 1-2 character prefixes that can clash with
            // auto-generated ones, i.e. no query["0"] ~ query["zz"].
            keys.forEach(function (key) {
                // idSeparator used in regexp.
                key = (key.match(/^[^~]{3,}~/) || [""])[0]
                var re = new RegExp("^" + KanbaniWeb.escapeRegExp(key), "gm")
                var short = prefixes.length.toString(36) + idSeparator
                var str = strKeys.replace(re, short)
                var count = (strKeys.length - str.length) / (key.length - short.length)
                if (key && count > 1) {
                    strKeys = str
                    prefixes.push(key.substr(0, key.length - 1))
                }
            })
            var serialized = strKeys.split(/\n/g).map(function (key, index) {
                return (index ? "&" : "?") + encodeURIComponent(key) +
                       "=" + encodeURIComponent(query[keys[index]])
            }).join("")
            if (prefixes.length) {
                serialized += "&p=" + encodeURIComponent(prefixes.join("~"))
            }
            return serialized
        }

        this.unserialize(current || location.search)
    },

    // target - object with methods: filter(), sort(), such as a Board or LIst.
    Customizer: function (target, name, node) {
        this.target = target
        this.name = name
        this.node = node
        this.hooks = document.createElement("a")
        this.default = null
        var lastExplicitValue

        this.hook = function () {
            if (this.default == null) {
                this.default = this.node.value
            }
            this.node.addEventListener("change", this.apply.bind(this, null, null))
            // Mouse events don't work in WebKit: https://stackoverflow.com/questions/8902405
            if (this.preview) {
                var timer
                var cust = this
                var preview = function (e) {
                    clearTimeout(timer)
                    timer = setTimeout(function () {
                        KanbaniWeb.skipSerialize = true
                        try {
                            var value = cust.preview(e, e.type == "mouseout" ? lastExplicitValue : null)
                            cust.apply(null, value)
                        } finally {
                            KanbaniWeb.skipSerialize = false
                        }
                    }, 0)
                }
                this.node.addEventListener("mousemove", preview)
                this.node.addEventListener("mouseout", preview)
            }
        }

        this.preview = function (e, value) {
            if (value != null) {
                this.node.value = value
            }
            return e.target.value
        }

        this.apply = function (target, value) {
            if (value == null) {
                value = this.node.value.trim()
                if (!target) { lastExplicitValue = value }
            }
            if (value) {
                this._apply(target || this.target, value)
                target || this.hooks.dispatchEvent(new Event("apply"))
            } else {
                this.cancel(target)
            }
        }

        this._apply = function (target, value) {
            throw new Error("Method not implemented.")
        }

        this.cancel = function (target) {
            ;(target || this.target).filter(this.name, null)
            target || this.hooks.dispatchEvent(new Event("cancel"))
        }

        this.findNode = function (parent) {
            return parent.querySelector('[name="' + this.name + '"]')
        }

        this.serialize = function (prefix) {
            var o = {}
            o[prefix + this.name] = this.node.value == this.default ? null : this.node.value
            return o
        }

        this.unserialize = function (prefix, obj) {
            var name = prefix + this.name
            this.node.value = obj.hasOwnProperty(name) ? obj[name] : this.default
            this.apply()
        }
    },
}

KanbaniWeb.location = new KanbaniWeb.Location

addEventListener("popstate", function () {
    console.log("popstate:\n" + location.href)
    KanbaniWeb.location.unserialize(location.search)
})

addEventListener("DOMContentLoaded", function () {
    if (typeof kanbaniData != "undefined") {
        KanbaniWeb.board = new KanbaniWeb.Board(kanbaniData.profile.boards[kanbaniData.currentBoard])
    }
})

function updateQrCodes() {
    document.querySelectorAll("[data-kwv-qr]").forEach(function (node) {
        node[node.href ? "href" : "src"] = location.href.replace(/#.*$/, "") +
            (location.search ? "&" : "?") + "do=" + node.getAttribute("data-kwv-qr") +
            location.hash
    })
}
addEventListener("popstate", updateQrCodes)
addEventListener("hashchange", updateQrCodes)
KanbaniWeb.location.hooks.addEventListener("pushState", updateQrCodes)
