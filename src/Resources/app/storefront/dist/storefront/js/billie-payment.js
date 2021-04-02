(window.webpackJsonp = window.webpackJsonp || []).push([["billie-payment"], {
  P7eA: function (t, e, n) {
    "use strict";
    (function (t) {
      n.d(e, "a", (function () {
        return d
      }));
      var o = n("FGIj"), i = n("k8s9");

      function r(t) {
        return (r = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
          return typeof t
        } : function (t) {
          return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t
        })(t)
      }

      function s(t, e) {
        for (var n = 0; n < e.length; n++) {
          var o = e[n];
          o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(t, o.key, o)
        }
      }

      function a(t) {
        return (a = Object.setPrototypeOf ? Object.getPrototypeOf : function (t) {
          return t.__proto__ || Object.getPrototypeOf(t)
        })(t)
      }

      function u(t) {
        if (void 0 === t) throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
        return t
      }

      function c(t, e) {
        return (c = Object.setPrototypeOf || function (t, e) {
          return t.__proto__ = e, t
        })(t, e)
      }

      function l(t, e, n) {
        return e in t ? Object.defineProperty(t, e, {
          value: n,
          enumerable: !0,
          configurable: !0,
          writable: !0
        }) : t[e] = n, t
      }

      var f = null, d = function (e) {
        function n() {
          var t, e, o, i;
          !function (t, e) {
            if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
          }(this, n);
          for (var s = arguments.length, c = new Array(s), f = 0; f < s; f++) c[f] = arguments[f];
          return o = this, e = !(i = (t = a(n)).call.apply(t, [this].concat(c))) || "object" !== r(i) && "function" != typeof i ? u(o) : i, l(u(e), "form", null), e
        }

        var o, d, m;
        return function (t, e) {
          if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function");
          t.prototype = Object.create(e && e.prototype, {
            constructor: {
              value: t,
              writable: !0,
              configurable: !0
            }
          }), e && c(t, e)
        }(n, e), o = n, (d = [{
          key: "init", value: function () {
            this._registerEvents(), this.form = this.el.form, this.form.addEventListener("submitForm", this._submitForm.bind(this))
          }
        }, {
          key: "_submitForm", value: function (e) {
            if (this._isAddressConfirmed()) return !0;
            e.preventDefault(), BillieCheckoutWidget.mount({
              billie_config_data: {
                session_id: this.options.checkoutSessionId,
                merchant_name: this.options.merchantName
              }, billie_order_data: this.options.checkoutData
            }).then((function (e) {
              t.ajax({
                type: "POST", url: me.opts.validateAddressUrl, data: e, dataType: "json", success: function (t) {
                  t.status ? (me._setAddressConfirmed(!0), me.$form.submit()) : t.redirect ? window.location.href = t.redirect : (me.showDefaultMessage(), me.unlockSubmitButton())
                }, fail: function () {
                  me._setAddressConfirmed(!1)
                }
              })
            })).catch((function (t) {
              "declined" !== t.state && (console.log("Error occurred", t), me.showDefaultMessage()), me.unlockSubmitButton()
            }))
          }
        }, {
          key: "_registerEvents", value: function () {
            this._runtimeSelect && this._runtimeSelect.addEventListener("change", this._onSelectRuntime.bind(this)), this._rateInput && this._rateInput.addEventListener("input", this._onInputRate.bind(this)), this._rateButton && this._rateButton.addEventListener("click", this._onSubmitRate.bind(this)), this._registerInstallmentPlanEvents()
          }
        }, {
          key: "_fetchInstallmentPlan", value: function (t, e) {
            var n = this, o = new i.a(window.accessKey, window.contextToken),
              r = "".concat(window.rpInstallmentCalculateUrl, "?type=").concat(t, "&value=").concat(e);
            this._activateLoader(), f && f.abort(), f = o.get(r, this._executeCallback.bind(this, (function (o) {
              n._setContent(o), n._typeHolder.value = t, n._valueHolder.value = e, n._registerInstallmentPlanEvents()
            })))
          }
        }, {
          key: "_executeCallback", value: function (t, e) {
            "function" == typeof t && t(e)
          }
        }, {
          key: "_insertWidget", value: function () {
            var t, e, n, o, i, r, s;
            window.billiePaymentData = this.options.checkoutData, t = window, e = document, n = "script", o = "bcw", i = this.options.src, t.BillieCheckoutWidget = o, t[o] = t[o] || function () {
              (t[o].q = t[o].q || []).push(arguments)
            }, t.billieSrc = i, r = e.createElement(n), s = e.getElementsByTagName(n)[0], r.id = o, r.src = i, r.charset = "utf-8", r.async = 1, s.parentNode.insertBefore(r, s), bcw("init")
          }
        }, {
          key: "_setAddressConfirmed", value: function (t) {
            this.form.dataset.billieConfirmed = parseInt(t)
          }
        }, {
          key: "_isAddressConfirmed", value: function () {
            return 1 === parseInt(this.form.dataset.billieConfirmed)
          }
        }]) && s(o.prototype, d), m && s(o, m), n
      }(o.a);
      l(d, "options", {src: null, checkoutSessionId: null, merchantName: null, checkoutData: null})
    }).call(this, n("UoTJ"))
  }, m9Ug: function (t, e, n) {
    "use strict";
    n.r(e);
    var o = n("P7eA");

    function i(t) {
      return (i = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (t) {
        return typeof t
      } : function (t) {
        return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : typeof t
      })(t)
    }

    var r = window.PluginManager;
    void 0 === i(r.getPluginList().BillieCheckout) && r.register("BillieCheckout", o.a, '[data-billie-checkout="true"]')
  }
}, [["m9Ug", "runtime", "vendor-node", "vendor-shared"]]]);
