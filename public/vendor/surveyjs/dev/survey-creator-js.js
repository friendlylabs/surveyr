/*! For license information please see survey-creator-js.min.js.LICENSE.txt */ 
! function (e, t) {
    "object" == typeof exports && "object" == typeof module ? module.exports = t(require("survey-core"), require("survey-creator-core"), require("survey-js-ui")) : "function" == typeof define && define.amd ? define("survey-creator-js", ["survey-core", "survey-creator-core", "survey-js-ui"], t) : "object" == typeof exports ? exports["survey-creator-js"] = t(require("survey-core"), require("survey-creator-core"), require("survey-js-ui")) : e.SurveyCreator = t(e.Survey, e.SurveyCreatorCore, e.SurveyUI)
}(this, ((e, t, n) => (() => {
  "use strict";
  var o = {
      156: t => {
        t.exports = e
      },
      982: e => {
        e.exports = t
      },
      675: e => {
        e.exports = n
      }
    },
    r = {};

  function i(e) {
    var t = r[e];
    if (void 0 !== t) return t.exports;
    var n = r[e] = {
      exports: {}
    };
    return o[e](n, n.exports, i), n.exports
  }
  i.d = (e, t) => {
    for (var n in t) i.o(t, n) && !i.o(e, n) && Object.defineProperty(e, n, {
      enumerable: !0,
      get: t[n]
    })
  }, i.o = (e, t) => Object.prototype.hasOwnProperty.call(e, t), i.r = e => {
    "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, {
      value: "Module"
    }), Object.defineProperty(e, "__esModule", {
      value: !0
    })
  };
  var a = {};
  i.r(a), i.d(a, {
    ActionButton: () => I,
    AdaptiveToolbox: () => Te,
    CellQuestionAdornerComponent: () => K,
    CellQuestionDropdownAdornerComponent: () => z,
    CreatorSurveyPageComponent: () => G,
    IPropertyGridEditor: () => l.IPropertyGridEditor,
    ISurveyCreatorOptions: () => l.ISurveyCreatorOptions,
    ImageItemValueAdornerComponent: () => ue,
    ItemValueAdornerComponent: () => se,
    LogoImageComponent: () => ee,
    MatrixCellAdornerComponent: () => me,
    PanelAdornerComponent: () => Z,
    PropertyGridComponent: () => Rt,
    PropertyGridEditorCollection: () => l.PropertyGridEditorCollection,
    PropertyGridPlaceholderComponent: () => St,
    QuestionAdornerComponent: () => N,
    QuestionBanner: () => M,
    QuestionDropdownAdornerComponent: () => D,
    QuestionEditorContentComponent: () => ae,
    QuestionErrorComponent: () => et,
    QuestionImageAdornerComponent: () => k,
    QuestionRatingAdornerComponent: () => U,
    QuestionWidgetAdornerComponent: () => L,
    QuestionWrapperFooter: () => T,
    QuestionWrapperHeader: () => x,
    ReactDragEvent: () => C,
    ReactMouseEvent: () => O,
    RowWrapper: () => b,
    SearchComponent: () => xe,
    SideBarDefaultHeader: () => Ae,
    SidebarComponent: () => He,
    StylesManager: () => l.StylesManager,
    SurveyCreator: () => v,
    SurveyCreatorComponent: () => y,
    SurveyCreatorToolboxCategory: () => we,
    SurveyCreatorToolboxItem: () => ge,
    SurveyCreatorToolboxItemGroup: () => _e,
    SurveyCreatorToolboxTool: () => ve,
    SurveyElementEmbeddedSurvey: () => re,
    SurveyLocStringEditor: () => Ze,
    SurveyLogic: () => l.SurveyLogic,
    SurveyLogicOpertor: () => nt,
    SurveyLogicUI: () => l.SurveyLogicUI,
    SurveyNavigation: () => Ie,
    SurveyQuestionBooleanSwitch: () => Xt,
    SurveyQuestionColor: () => Vt,
    SurveyQuestionEditorDefinition: () => l.SurveyQuestionEditorDefinition,
    SurveyQuestionFileEditor: () => Ht,
    SurveyQuestionLinkValue: () => ne,
    SurveyQuestionSpinEditor: () => At,
    SurveyQuestionTextWithReset: () => Wt,
    SurveyResults: () => fe,
    SurveyResultsByRow: () => he,
    SurveySimulator: () => _t,
    SwitcherComponent: () => Ft,
    TabButtonComponent: () => Me,
    TabDesignerComponent: () => ut,
    TabJsonEditorAceComponent: () => ht,
    TabJsonEditorErrorsComponent: () => mt,
    TabJsonEditorTextareaComponent: () => dt,
    TabLogicAddButtonComponent: () => vt,
    TabLogicComponent: () => gt,
    TabPreviewSurveyComponent: () => Ot,
    TabPreviewTestSurveyAgainComponent: () => wt,
    TabThemeSurveyComponent: () => Pt,
    TabTranslationComponent: () => jt,
    TabbedMenuComponent: () => m,
    TabbedMenuItemComponent: () => f,
    Toolbox: () => Ce,
    ToolboxList: () => Ne,
    ToolboxToolViewModel: () => l.ToolboxToolViewModel,
    TranslateFromAction: () => Xe,
    TranslationLineSkeleton: () => Ge,
    Version: () => Gt,
    editorLocalization: () => l.editorLocalization,
    localization: () => l.localization,
    renderSurveyCreator: () => $t,
    settings: () => l.settings,
    svgBundle: () => l.svgBundle
  });
  var c, s = i(675),
    l = i(982),
    u = i(156),
    p = (c = function (e, t) {
      return c = Object.setPrototypeOf || {
        __proto__: []
      }
      instanceof Array && function (e, t) {
        e.__proto__ = t
      } || function (e, t) {
        for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
      }, c(e, t)
    }, function (e, t) {
      if ("function" != typeof t && null !== t) throw new TypeError("Class extends value " + String(t) + " is not a constructor or null");

      function n() {
        this.constructor = e
      }
      c(e, t), e.prototype = null === t ? Object.create(t) : (n.prototype = t.prototype, new n)
    }),
    m = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootRef = s.createRef(), n
      }
      return p(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this.model.renderedActions.map((function (e) {
          return s.createElement(d, {
            item: e,
            key: e.id
          })
        }));
        return s.createElement("div", {
          ref: this.rootRef,
          className: "svc-tabbed-menu"
        }, e)
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this);
        var t = this.rootRef.current;
        t && (this.manager = new u.ResponsivityManager(t, this.model, ".svc-tabbed-menu-item-container:not(.sv-dots)>.sv-action__content"))
      }, t.prototype.componentWillUnmount = function () {
        this.manager && this.manager.dispose(), e.prototype.componentWillUnmount.call(this)
      }, t
    }(s.SurveyElementBase),
    d = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return p(t, e), Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.renderElement = function () {
        var e = "svc-tabbed-menu-item-container";
        this.item.css && (e += " " + this.item.css), e += this.item.isVisible ? "" : " sv-action--hidden";
        var t = s.ReactElementFactory.Instance.createElement(this.item.component || "svc-tabbed-menu-item", {
          item: this.item
        });
        return s.createElement("span", {
          key: this.item.id,
          className: e
        }, s.createElement("div", {
          className: "sv-action__content"
        }, t))
      }, t
    }(s.SurveyElementBase),
    f = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return p(t, e), Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.render = function () {
        var e = this.item;
        return (0, s.attachKey2click)(s.createElement("div", {
          className: e.getRootCss(),
          onClick: function () {
            return e.action(e)
          }
        }, e.hasTitle ? s.createElement("span", {
          className: e.getTitleCss()
        }, e.title) : null, e.hasIcon ? s.createElement(s.SvgIcon, {
          iconName: e.iconName,
          className: e.getIconCss(),
          size: "auto",
          title: e.tooltip || e.title
        }) : null))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tabbed-menu-item", (function (e) {
    return s.createElement(f, e)
  }));
  var h = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    y = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootNode = s.createRef(), n
      }
      return h(t, e), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.creator
      }, Object.defineProperty(t.prototype, "style", {
        get: function () {
          return this.props.style
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n), this.creator !== t.creator && (t.creator && t.creator.unsubscribeRootElement(), this.creator && this.rootNode.current && this.creator.setRootElement(this.rootNode.current))
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.creator.setRootElement(this.rootNode.current)
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.creator.unsubscribeRootElement()
      }, t.prototype.renderElement = function () {
        var e = this.props.creator;
        if (e.isCreatorDisposed) return null;
        var t = "svc-full-container svc-creator__area svc-flex-column" + (this.props.creator.haveCommercialLicense ? "" : " svc-creator__area--with-banner"),
          n = "svc-creator__content-wrapper svc-flex-row" + (this.props.creator.isMobileView ? " svc-creator__content-wrapper--footer-toolbar" : ""),
          o = "svc-flex-row svc-full-container svc-creator__side-bar--" + this.creator.sidebarLocation,
          r = {};
        (0, l.assign)(r, this.style, this.props.creator.themeVariables);
        var i = null;
        if (!this.props.creator.haveCommercialLicense) {
        //if(false) {
          var a = {
            __html: this.props.creator.licenseText
          };
          i = s.createElement("div", {
            className: "svc-creator__banner"
          }, s.createElement("span", {
            className: "svc-creator__non-commercial-text",
            dangerouslySetInnerHTML: a
          }))
        }
        return s.createElement("div", {
          className: this.creator.getRootCss(),
          ref: this.rootNode,
          style: r
        }, s.createElement(s.SvgBundleComponent, null), s.createElement(s.PopupModal, null), s.createElement("div", {
          className: t
        }, s.createElement("div", {
          className: o
        }, s.createElement("div", {
          className: "svc-flex-column svc-flex-row__element svc-flex-row__element--growing"
        }, s.createElement("div", {
          className: "svc-top-bar"
        }, e.showTabs ? s.createElement("div", {
          className: "svc-tabbed-menu-wrapper"
        }, s.createElement(m, {
          model: e.tabbedMenu
        })) : null, e.showToolbar ? s.createElement("div", {
          className: "svc-toolbar-wrapper"
        }, s.createElement(s.SurveyActionBar, {
          model: e.toolbar
        })) : null), s.createElement("div", {
          className: n
        }, s.createElement("div", {
          className: "svc-creator__content-holder svc-flex-column"
        }, this.renderActiveTab())), s.createElement("div", {
          className: "svc-footer-bar"
        }, e.isMobileView ? s.createElement("div", {
          className: "svc-toolbar-wrapper"
        }, s.createElement(s.SurveyActionBar, {
          model: e.footerToolbar
        })) : null)), this.renderSidebar()), i, s.createElement(s.NotifierComponent, {
          notifier: e.notifier
        })))
      }, t.prototype.renderActiveTab = function () {
        for (var e = this.props.creator, t = 0; t < e.tabs.length; t++)
          if (e.tabs[t].id === e.activeTab) return this.renderCreatorTab(e.tabs[t]);
        return null
      }, t.prototype.renderCreatorTab = function (e) {
        if (!1 === e.visible) return null;
        var t = this.props.creator,
          n = e.renderTab ? e.renderTab() : s.ReactElementFactory.Instance.createElement(e.componentContent, {
            creator: t,
            survey: t.survey,
            data: e.data.model
          }),
          o = "svc-creator-tab" + ("right" == t.toolboxLocation ? " svc-creator__toolbox--right" : "");
        return s.createElement("div", {
          key: e.id,
          id: "scrollableDiv-" + e.id,
          className: o
        }, n)
      }, t.prototype.renderSidebar = function () {
        return this.creator.sidebar ? s.ReactElementFactory.Instance.createElement("svc-side-bar", {
          model: this.creator.sidebar
        }) : null
      }, t
    }(s.SurveyElementBase),
    v = function (e) {
      function t(t, n) {
        return void 0 === t && (t = {}), e.call(this, t, n) || this
      }
      return h(t, e), t.prototype.render = function (e) {
        var t = e;
        "string" == typeof e && (t = document.getElementById(e)), s.unmountComponentAtNode(t), s.render(s.createElement(s.StrictMode, null, s.createElement(y, {
          creator: this
        })), t)
      }, t.prototype.createQuestionElement = function (e) {
        return s.ReactQuestionFactory.Instance.createQuestion(e.isDefaultRendering() ? e.getTemplate() : e.getComponentName(), {
          question: e,
          isDisplayMode: e.isReadOnly,
          creator: this
        })
      }, t.prototype.renderError = function (e, t, n) {
        return s.createElement("div", {
          key: e
        }, s.createElement("span", {
          className: n.error.icon,
          "aria-hidden": "true"
        }), s.createElement("span", {
          className: n.error.item
        }, s.createElement(s.SurveyLocStringViewer, {
          locStr: t.locText
        })))
      }, t.prototype.questionTitleLocation = function () {
        return this.survey.questionTitleLocation
      }, t.prototype.questionErrorLocation = function () {
        return this.survey.questionErrorLocation
      }, t
    }(l.SurveyCreatorModel);
  s.ReactElementFactory.Instance.registerElement("survey-widget", (function (e) {
    return s.createElement(s.Survey, e)
  }));
  var g = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    E = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.createModel(t), n
      }
      return g(t, e), t.prototype.shouldComponentUpdate = function (t, n) {
        var o = e.prototype.shouldComponentUpdate.call(this, t, n);
        return o && this.needUpdateModel(t) && this.createModel(t), o
      }, t.prototype.createModel = function (e) {}, t.prototype.needUpdateModel = function (e) {
        var t = this.getUpdatedModelProps();
        if (!Array.isArray(t)) return !0;
        for (var n = 0; n < t.length; n++) {
          var o = t[n];
          if (this.props[o] !== e[o]) return !0
        }
        return !1
      }, t.prototype.getUpdatedModelProps = function () {}, t
    }(s.SurveyElementBase),
    _ = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    b = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return _(t, e), t.prototype.createModel = function (e) {
        this.model = new l.RowViewModel(e.componentData.creator, e.row, null)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["row", "componentData"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        return s.createElement("div", {
          key: "svc-row-" + this.props.row.id,
          className: this.model.cssClasses
        }, s.createElement("div", {
          className: "svc-row__drop-indicator svc-row__drop-indicator--top"
        }), s.createElement("div", {
          className: "svc-row__drop-indicator svc-row__drop-indicator--bottom"
        }), this.props.element)
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-row", (function (e) {
    return s.createElement(b, e)
  }));
  var w = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    O = function () {
      function e(e) {
        this.event = e
      }
      return e.prototype.stopPropagation = function () {
        this.event.stopPropagation()
      }, e.prototype.preventDefault = function () {
        this.event.preventDefault()
      }, Object.defineProperty(e.prototype, "cancelBubble", {
        get: function () {
          return !1
        },
        set: function (e) {},
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "target", {
        get: function () {
          return this.event.target
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "currentTarget", {
        get: function () {
          return this.event.currentTarget
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "clientX", {
        get: function () {
          return this.event.clientX
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "clientY", {
        get: function () {
          return this.event.clientY
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "offsetX", {
        get: function () {
          return this.event.nativeEvent.offsetX
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(e.prototype, "offsetY", {
        get: function () {
          return this.event.nativeEvent.offsetY
        },
        enumerable: !1,
        configurable: !0
      }), e
    }(),
    C = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.event = t, n
      }
      return w(t, e), Object.defineProperty(t.prototype, "dataTransfer", {
        get: function () {
          return this.event.dataTransfer
        },
        enumerable: !1,
        configurable: !0
      }), t
    }(O),
    S = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    N = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootRef = s.createRef(), n
      }
      return S(t, e), t.prototype.createModel = function (e) {
        this.model ? this.model.attachToUI(e.question, this.rootRef.current) : this.modelValue = this.createQuestionViewModel(e)
      }, t.prototype.createQuestionViewModel = function (e) {
        return new l.QuestionAdornerViewModel(e.componentData, e.question, null)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "componentData"]
      }, Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.modelValue
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this,
          t = this.model.element.isInteractiveDesignElement,
          n = this.renderQuestionTitle(),
          o = this.renderContent(t);
        return s.createElement("div", {
          ref: this.rootRef,
          "data-sv-drop-target-survey-element": this.model.element.name || null,
          className: this.model.rootCss(),
          onDoubleClick: function (n) {
            t && e.model.dblclick(n.nativeEvent), n.stopPropagation()
          },
          onMouseLeave: function (n) {
            return t && e.model.hover(n.nativeEvent, n.currentTarget)
          },
          onMouseOver: function (n) {
            return t && e.model.hover(n.nativeEvent, n.currentTarget)
          }
        }, n, o)
      }, t.prototype.disableTabStop = function () {
        return !0
      }, t.prototype.renderContent = function (e) {
        var t = this,
          n = this.renderElementContent();
        return (0, s.attachKey2click)(s.createElement("div", {
          className: this.model.css(),
          onClick: function (e) {
            return t.model.select(t.model, new O(e))
          }
        }, s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--left"
        }), s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--right"
        }), s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--top"
        }), s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--bottom"
        }), e ? this.renderHeader() : null, n, this.renderFooter()), void 0, {
          disableTabStop: this.disableTabStop()
        })
      }, t.prototype.renderHeader = function () {
        return s.ReactElementFactory.Instance.createElement("svc-question-header", {
          model: this.model
        })
      }, t.prototype.renderFooter = function () {
        return this.model.element.isInteractiveDesignElement ? s.ReactElementFactory.Instance.createElement("svc-question-footer", {
          className: "svc-question__content-actions",
          model: this.model
        }) : null
      }, t.prototype.renderCarryForwardBanner = function () {
        return this.model.isBannerShowing ? s.ReactElementFactory.Instance.createElement("svc-question-banner", this.model.createBannerParams()) : null
      }, t.prototype.renderQuestionTitle = function () {
        var e = this;
        if (!this.model.showHiddenTitle) return null;
        var t = this.model.element;
        return s.createElement("div", {
          className: this.model.cssCollapsedHiddenHeader
        }, s.createElement("div", {
          ref: function (t) {
            return t && (e.model.renderedCollapsed ? t.removeAttribute("inert") : t.setAttribute("inert", ""))
          },
          className: this.model.cssCollapsedHiddenTitle
        }, t.hasTitle ? s.SurveyElementBase.renderLocString(t.locTitle, null, "q_title") : s.createElement("span", {
          className: "svc-fake-title"
        }, t.name)))
      }, t.prototype.renderElementContent = function () {
        return s.createElement(s.Fragment, null, this.props.element, this.renderElementPlaceholder(), this.renderCarryForwardBanner())
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.model.attachToUI(this.props.question, this.rootRef.current)
      }, t.prototype.renderElementPlaceholder = function () {
        return this.model.isEmptyElement ? s.createElement("div", {
          className: "svc-panel__placeholder_frame-wrapper"
        }, s.createElement("div", {
          className: "svc-panel__placeholder_frame"
        }, s.createElement("div", {
          className: "svc-panel__placeholder"
        }, this.model.placeholderText))) : null
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.model.detachFromUI()
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-question", (function (e) {
    return s.createElement(N, e)
  }));
  var P = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    x = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return P(t, e), t.prototype.render = function () {
        var e = this;
        return this.props.model.allowDragging ? s.createElement("div", {
          className: "svc-question__drag-area",
          onPointerDown: function (t) {
            return e.props.model.onPointerDown(t)
          }
        }, s.createElement(s.SvgIcon, {
          className: "svc-question__drag-element",
          size: 24,
          iconName: "icon-drag-area-indicator_24x16"
        }), s.createElement("div", {
          className: "svc-question__top-actions"
        }, s.createElement(s.SurveyActionBar, {
          model: this.props.model.topActionContainer,
          handleClick: !1
        }))) : null
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-question-header", (function (e) {
    return s.createElement(x, e)
  }));
  var j = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    T = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return j(t, e), t.prototype.render = function () {
        var e = this;
        return s.createElement("div", {
          className: this.props.className,
          onFocus: function (t) {
            return e.props.model.select(e.props.model, new O(t))
          }
        }, s.createElement(s.SurveyActionBar, {
          model: this.props.model.actionContainer,
          handleClick: !1
        }))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-question-footer", (function (e) {
    return s.createElement(T, e)
  }));
  var q = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    I = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return q(t, e), t.prototype.renderElement = function () {
        var e = this,
          t = this.props.classes + " svc-action-button";
        return this.props.selected && (t += " svc-action-button--selected"), this.props.disabled ? (t += " svc-action-button--disabled", s.createElement("span", {
          className: t
        }, this.props.text)) : s.createElement(s.Fragment, null, (0, s.attachKey2click)(s.createElement("span", {
          role: "button",
          className: t,
          onClick: function (t) {
            e.props.allowBubble || t.stopPropagation(), e.props.click()
          },
          title: this.props.title
        }, this.props.text)))
      }, t
    }(s.SurveyElementBase),
    R = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    M = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return R(t, e), t.prototype.render = function () {
        var e = this;
        return s.createElement("div", {
          className: "svc-carry-forward-panel-wrapper"
        }, s.createElement("div", {
          className: "svc-carry-forward-panel"
        }, s.createElement("span", null, this.props.text, " "), s.createElement("span", {
          className: "svc-carry-forward-panel__link"
        }, s.createElement(I, {
          click: function () {
            return e.props.onClick()
          },
          text: this.props.actionText
        }))))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-question-banner", (function (e) {
    return s.createElement(M, e)
  }));
  var F = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    D = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return F(t, e), t.prototype.createQuestionViewModel = function (e) {
        return new l.QuestionDropdownAdornerViewModel(e.componentData, e.question, null)
      }, Object.defineProperty(t.prototype, "dropdownModel", {
        get: function () {
          return this.model
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.dropdownModel.question
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderElementPlaceholder = function () {
        var e = this,
          t = this.question.textStyle;
        return s.createElement("div", {
          className: "svc-question__dropdown-choices--wrapper"
        }, s.createElement("div", null, s.createElement("div", {
          className: "svc-question__dropdown-choices"
        }, (this.dropdownModel.getRenderedItems() || []).map((function (n, o) {
          return s.createElement("div", {
            className: e.dropdownModel.getChoiceCss(),
            key: "editable_choice_".concat(o)
          }, s.ReactSurveyElementsWrapper.wrapItemValue(e.question.survey, s.ReactElementFactory.Instance.createElement(e.dropdownModel.itemComponent, {
            key: n.value,
            question: e.question,
            cssClasses: e.question.cssClasses,
            isDisplayMode: !0,
            item: n,
            textStyle: t,
            index: o,
            isChecked: e.question.value === n.value
          }), e.question, n))
        }))), this.dropdownModel.needToCollapse ? s.createElement(I, {
          click: this.dropdownModel.switchCollapse,
          text: this.dropdownModel.getButtonText(),
          allowBubble: !0
        }) : null))
      }, t
    }(N);
  s.ReactElementFactory.Instance.registerElement("svc-dropdown-question", (function (e) {
    return s.createElement(D, e)
  }));
  var B = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    k = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return B(t, e), t.prototype.createQuestionViewModel = function (e) {
        return new l.QuestionImageAdornerViewModel(e.componentData, e.question, null)
      }, Object.defineProperty(t.prototype, "imageModel", {
        get: function () {
          return this.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderHeader = function () {
        return s.createElement(s.Fragment, null, s.createElement("input", {
          type: "file",
          "aria-hidden": "true",
          tabIndex: -1,
          accept: this.imageModel.acceptedTypes,
          className: "svc-choose-file-input",
          style: {
            position: "absolute",
            opacity: 0,
            width: "1px",
            height: "1px",
            overflow: "hidden"
          }
        }), e.prototype.renderHeader.call(this))
      }, t.prototype.renderLoadingPlaceholder = function () {
        return s.createElement("div", {
          className: "svc-image-question__loading-placeholder"
        }, s.createElement("div", {
          className: "svc-image-question__loading"
        }, s.createElement(s.LoadingIndicatorComponent, null)))
      }, t.prototype.renderChooseButton = function () {
        var e = this;
        return s.createElement("div", {
          className: "svc-image-question-controls"
        }, this.model.allowEdit ? (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button",
          onClick: function () {
            return e.imageModel.chooseFile(e.imageModel)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-file"
        }))) : null)
      }, t.prototype.renderElementPlaceholder = function () {
        return this.imageModel.isUploading ? this.renderLoadingPlaceholder() : this.renderChooseButton()
      }, t.prototype.getStateElements = function () {
        return [this.model, this.imageModel.filePresentationModel]
      }, t.prototype.renderElementContent = function () {
        if (this.imageModel.isEmptyImageLink) {
          var e = s.ReactQuestionFactory.Instance.createQuestion("file", {
            creator: this.imageModel.question.survey,
            isDisplayMode: !1,
            question: this.imageModel.filePresentationModel
          });
          return s.createElement(s.Fragment, null, e)
        }
        return s.createElement(s.Fragment, null, this.props.element, this.renderElementPlaceholder())
      }, t
    }(N);
  s.ReactElementFactory.Instance.registerElement("svc-image-question", (function (e) {
    return s.createElement(k, e)
  }));
  var A = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    U = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return A(t, e), t.prototype.createModel = function (e) {
        this.modelValue = this.createQuestionViewModel(e)
      }, t.prototype.createQuestionViewModel = function (e) {
        return new l.QuestionRatingAdornerViewModel(e.componentData, e.question, null)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "componentData"]
      }, Object.defineProperty(t.prototype, "ratingModel", {
        get: function () {
          return this.model
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.modelValue
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this.ratingModel;
        return s.createElement(s.Fragment, null, s.createElement("div", {
          className: "svc-rating-question-content"
        }, s.createElement("div", {
          className: e.controlsClassNames
        }, e.allowRemove ? (0, s.attachKey2click)(s.createElement("span", {
          className: e.removeClassNames,
          "aria-label": e.removeTooltip,
          onClick: function () {
            return e.removeItem(e)
          }
        }, s.createElement(s.SvgIcon, {
          size: 16,
          iconName: "icon-remove_16x16",
          title: e.removeTooltip
        }))) : null, e.allowAdd ? (0, s.attachKey2click)(s.createElement("span", {
          className: e.addClassNames,
          "aria-label": e.addTooltip,
          onClick: function () {
            return e.addItem(e)
          }
        }, s.createElement(s.SvgIcon, {
          size: 16,
          iconName: "icon-add_16x16",
          title: e.addTooltip
        }))) : null), this.props.element))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-rating-question", (function (e) {
    return s.createElement(U, e)
  })), s.ReactElementFactory.Instance.registerElement("svc-rating-question-content", (function (e) {
    return s.createElement(U, e)
  }));
  var Q = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    L = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Q(t, e), t.prototype.createQuestionViewModel = function (e) {
        return new l.QuestionAdornerViewModel(e.componentData, e.question, null)
      }, Object.defineProperty(t.prototype, "widgetModel", {
        get: function () {
          return this.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderElementContent = function () {
        return s.createElement("div", {
          className: "svc-widget__content"
        }, this.props.element)
      }, t
    }(N);
  s.ReactElementFactory.Instance.registerElement("svc-widget-question", (function (e) {
    return s.createElement(L, e)
  }));
  var V = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    K = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return V(t, e), t.prototype.createModel = function (e) {
        this.model = new l.QuestionAdornerViewModel(e.componentData, e.question, null)
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "componentData"]
      }, t.prototype.render = function () {
        return s.createElement(s.Fragment, null, s.createElement("div", {
          "data-sv-drop-target-survey-element": this.model.element.name,
          className: "svc-question__adorner"
        }, s.createElement("div", {
          className: " svc-question__content--in-popup svc-question__content"
        }, this.props.element)))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-cell-question", (function (e) {
    return s.createElement(K, e)
  }));
  var H = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    z = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return H(t, e), t.prototype.createModel = function (e) {
        this.model = new l.QuestionAdornerViewModel(e.componentData, e.question, null)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "componentData"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        var e = this.props.question,
          t = this.props.question.textStyle;
        return s.createElement(s.Fragment, null, s.createElement("div", {
          "data-sv-drop-target-survey-element": this.model.element.name,
          className: "svc-question__adorner"
        }, s.createElement("div", {
          className: " svc-question__content--in-popup svc-question__content"
        }, this.props.element, s.createElement("div", {
          className: "svc-question__dropdown-choices"
        }, e.visibleChoices.map((function (n, o) {
          return s.createElement("div", {
            className: "svc-question__dropdown-choice",
            key: "editable_choice_".concat(o)
          }, s.ReactSurveyElementsWrapper.wrapItemValue(e.survey, s.ReactElementFactory.Instance.createElement("survey-radiogroup-item", {
            question: e,
            cssClasses: e.cssClasses,
            isDisplayMode: !0,
            item: n,
            textStyle: t,
            index: o,
            isChecked: e.value === n.value
          }), e, n))
        }))))))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-cell-dropdown-question", (function (e) {
    return s.createElement(z, e)
  }));
  var W = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    G = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootRef = s.createRef(), n
      }
      return W(t, e), t.prototype.createModel = function (e) {
        this.model && this.model.attachToUI(e.page, this.rootRef.current), this.model = new l.PageAdorner(e.creator, e.page)
      }, t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["creator", "page"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.model.attachToUI(this.props.page, this.rootRef.current)
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.model.detachFromUI()
      }, t.prototype.canRender = function () {
        return !!this.model && this.model.isPageLive && !!this.model.page && !!this.model.page.survey
      }, t.prototype.renderElement = function () {
        var e = this;
        return this.props.page ? (0, s.attachKey2click)(s.createElement("div", {
          ref: this.rootRef,
          id: this.props.page.id,
          "data-sv-drop-target-survey-page": this.model.dropTargetName,
          className: "svc-page__content " + this.model.css,
          onClick: function (t) {
            return e.model.select(e.model, new O(t))
          },
          onDoubleClick: function (t) {
            return e.model.dblclick(t.nativeEvent)
          },
          onMouseLeave: function (t) {
            return e.model.hover(t.nativeEvent, t.currentTarget)
          },
          onMouseOver: function (t) {
            return e.model.hover(t.nativeEvent, t.currentTarget)
          }
        }, s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--top"
        }), s.createElement("div", {
          className: "svc-question__drop-indicator svc-question__drop-indicator--bottom"
        }), this.renderHeader(), this.renderContent(), this.renderPlaceholder(), this.renderFooter())) : null
      }, t.prototype.renderPlaceholder = function () {
        return this.model.showPlaceholder ? s.createElement("div", {
          className: "svc-page__placeholder_frame"
        }, s.createElement("div", {
          className: "svc-panel__placeholder_frame"
        }, s.createElement("div", {
          className: "svc-panel__placeholder"
        }, this.model.placeholderText))) : null
      }, t.prototype.renderContent = function () {
        return s.createElement(s.SurveyPage, {
          page: this.props.page,
          survey: this.props.survey,
          creator: this.props.creator,
          css: this.model.css
        })
      }, t.prototype.renderHeader = function () {
        var e = this,
          t = s.createElement("div", {
            className: "svc-page__content-actions"
          }, s.createElement(s.SurveyActionBar, {
            model: this.model.actionContainer
          }));
        return this.model.isGhost || !this.model.allowDragging ? t : s.createElement("div", {
          className: "svc-question__drag-area",
          onPointerDown: function (t) {
            return e.model.onPointerDown(t)
          }
        }, s.createElement(s.SvgIcon, {
          className: "svc-question__drag-element",
          size: 24,
          iconName: "icon-drag-area-indicator_24x16"
        }), t)
      }, t.prototype.renderFooter = function () {
        return s.createElement(s.SurveyActionBar, {
          model: this.model.footerActionsBar
        })
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-page", (function (e) {
    return s.createElement(G, e)
  }));
  var J = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    X = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return J(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.item.data
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderTypeSelector = function () {
        var e = this.model.questionTypeSelectorModel;
        return (0, s.attachKey2click)(s.createElement("button", {
          type: "button",
          onClick: function (t) {
            t.stopPropagation(), e.action()
          },
          className: "svc-element__question-type-selector",
          title: this.model.addNewQuestionText
        }, s.createElement("span", {
          className: "svc-element__question-type-selector-icon"
        }, s.createElement(s.SvgIcon, {
          iconName: e.iconName,
          size: 24,
          title: this.model.addNewQuestionText
        })), void 0 === this.props.renderPopup || this.props.renderPopup ? s.createElement(s.Popup, {
          model: e.popupModel
        }) : null))
      }, t.prototype.renderElement = function () {
        var e = this,
          t = this.props.buttonClass || "svc-btn";
        return s.createElement(s.Fragment, null, (0, s.attachKey2click)(s.createElement("div", {
          className: "svc-element__add-new-question " + t,
          onClick: function (t) {
            t.stopPropagation(), e.model.addNewQuestion(e.model, new O(t))
          },
          onMouseOver: function (t) {
            return e.model.hoverStopper && e.model.hoverStopper(t.nativeEvent, t.currentTarget)
          }
        }, s.createElement(s.SvgIcon, {
          className: "svc-panel__add-new-question-icon",
          iconName: "icon-add_24x24",
          size: 24
        }), s.createElement("span", {
          className: "svc-add-new-item-button__text"
        }, this.model.addNewQuestionText), !1 !== this.props.renderPopup ? this.renderTypeSelector() : null)), !1 === this.props.renderPopup ? this.renderTypeSelector() : null)
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-add-new-question-btn", (function (e) {
    return s.createElement(X, e)
  }));
  var Y = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Z = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Y(t, e), t.prototype.renderElementPlaceholder = function () {
        var e = this;
        return this.model.isEmptyElement ? s.createElement("div", {
          className: "svc-panel__placeholder_frame-wrapper"
        }, s.createElement("div", {
          className: "svc-panel__placeholder_frame"
        }, s.createElement("div", {
          className: "svc-panel__placeholder"
        }, this.model.placeholderText), this.model.showAddQuestionButton ? (0, s.attachKey2click)(s.createElement("div", {
          className: "svc-panel__add-new-question svc-action-button",
          onClick: function (t) {
            t.stopPropagation(), e.model.addNewQuestion()
          }
        }, s.createElement(s.SvgIcon, {
          className: "svc-panel__add-new-question-icon",
          iconName: "icon-add_24x24",
          size: 24
        }), s.createElement("span", {
          className: "svc-add-new-item-button__text"
        }, this.model.addNewQuestionText))) : null)) : null
      }, t.prototype.disableTabStop = function () {
        return !0
      }, t.prototype.renderFooter = function () {
        return s.createElement(s.Fragment, null, !this.model.isEmptyElement && this.model.element.isPanel && this.model.showAddQuestionButton ? s.createElement("div", {
          className: "svc-panel__add-new-question-container"
        }, s.createElement("div", {
          className: "svc-panel__question-type-selector-popup"
        }, s.createElement(s.Popup, {
          model: this.model.questionTypeSelectorModel.popupModel
        })), s.createElement("div", {
          className: "svc-panel__add-new-question-wrapper"
        }, s.createElement(X, {
          item: {
            data: this.model
          },
          buttonClass: "svc-action-button",
          renderPopup: !1
        }))) : null, e.prototype.renderFooter.call(this))
      }, t
    }(N);
  s.ReactElementFactory.Instance.registerElement("svc-panel", (function (e) {
    return s.createElement(Z, e)
  }));
  var $ = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ee = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootRef = s.createRef(), n
      }
      return $(t, e), t.prototype.createModel = function (e) {
        var t = null;
        this.model && (t = this.model.root), this.model = new l.LogoImageViewModel(e.data, t)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["data"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.model.root = this.rootRef.current
      }, t.prototype.renderChooseButton = function () {
        var e = this;
        return (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button",
          onClick: function () {
            return e.model.chooseFile(e.model)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-file"
        })))
      }, t.prototype.renderClearButton = function () {
        var e = this;
        return (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button svc-context-button--danger",
          onClick: function () {
            return e.model.remove(e.model)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-clear"
        })))
      }, t.prototype.renderButtons = function () {
        return s.createElement("div", {
          className: "svc-context-container svc-logo-image-controls"
        }, this.renderChooseButton(), this.renderClearButton())
      }, t.prototype.renderImage = function () {
        return s.createElement("div", {
          className: this.model.containerCss
        }, this.renderButtons(), s.createElement(s.LogoImage, {
          data: this.props.data.survey
        }))
      }, t.prototype.renderPlaceHolder = function () {
        var e = this;
        return this.model.allowEdit && !this.model.isUploading ? (0, s.attachKey2click)(s.createElement("div", {
          className: "svc-logo-image-placeholder",
          onClick: function () {
            return e.model.chooseFile(e.model)
          }
        }, s.createElement("svg", null, s.createElement("use", {
          xlinkHref: "#icon-logo"
        })))) : null
      }, t.prototype.renderInput = function () {
        return s.createElement("input", {
          "aria-hidden": "true",
          type: "file",
          tabIndex: -1,
          accept: this.model.acceptedTypes,
          className: "svc-choose-file-input"
        })
      }, t.prototype.renderLoadingIndicator = function () {
        return s.createElement("div", {
          className: "svc-logo-image__loading"
        }, s.createElement(s.LoadingIndicatorComponent, null))
      }, t.prototype.render = function () {
        var e;
        return e = this.model.survey.locLogo.renderedHtml && !this.model.isUploading ? this.renderImage() : this.model.isUploading ? this.renderLoadingIndicator() : this.renderPlaceHolder(), s.createElement("div", {
          ref: this.rootRef,
          className: "svc-logo-image"
        }, this.renderInput(), e)
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-logo-image", (function (e) {
    return s.createElement(ee, e)
  }));
  var te = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ne = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return te(t, e), Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.questionBase
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderClear = function () {
        var e = this,
          t = this.questionBase.showClear;
        return !this.questionBase.isReadOnly && t ? s.createElement(I, {
          classes: this.question.linkClearButtonCssClasses,
          click: function () {
            return e.question.doClearClick()
          },
          selected: this.question.isSelected,
          text: l.editorLocalization.getString("pe.clear")
        }) : null
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement(s.Fragment, null, s.createElement(I, {
          classes: this.question.linkSetButtonCssClasses,
          click: function () {
            return e.question.doLinkClick()
          },
          selected: this.question.isSelected,
          disabled: !this.question.isClickable,
          text: this.question.linkValueText,
          title: this.question.tooltip
        }), this.renderClear())
      }, t
    }(s.SurveyQuestionElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("linkvalue", (function (e) {
    return s.createElement(ne, e)
  }));
  var oe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    re = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return oe(t, e), Object.defineProperty(t.prototype, "embeddedSurvey", {
        get: function () {
          return this.props.element || this.props.question
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.render = function () {
        if (!this.embeddedSurvey) return null;
        var e = this.embeddedSurvey.embeddedSurvey;
        return e && e.currentPage ? s.createElement(s.SurveyPage, {
          survey: e,
          page: e.currentPage,
          css: e.css,
          creator: this.creator
        }) : null
      }, t
    }(s.SurveyQuestionElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("embeddedsurvey", (function (e) {
    return s.createElement(re, e)
  }));
  var ie = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ae = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return ie(t, e), Object.defineProperty(t.prototype, "survey", {
        get: function () {
          return this.props.survey
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.createQuestionElement = function (e) {
        return s.ReactQuestionFactory.Instance.createQuestion(!e.isDefaultRendering || e.isDefaultRendering() ? e.getTemplate() : e.getComponentName(), {
          question: e,
          isDisplayMode: e.isInputReadOnly,
          creator: this
        })
      }, t.prototype.questionTitleLocation = function () {
        return this.survey.questionTitleLocation
      }, t.prototype.questionErrorLocation = function () {
        return this.survey.questionErrorLocation
      }, t.prototype.renderError = function (e, t, n) {
        return null
      }, t.prototype.render = function () {
        var e = this.survey.getAllQuestions()[0];
        return s.createElement(s.Fragment, null, s.createElement(s.SurveyQuestion, {
          creator: this,
          element: e
        }))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-question-editor-content", (function (e) {
    return s.createElement(ae, e)
  }));
  var ce = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    se = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.onBlur = function (e) {
          n.model.onFocusOut(e.nativeEvent)
        }, n.rootRef = s.createRef(), n
      }
      return ce(t, e), t.prototype.createModel = function (e) {
        this.model = new l.ItemValueWrapperViewModel(e.componentData.creator, e.question, e.item)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "item"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n), this.props.item.setRootElement(this.rootRef.current), t.item !== this.props.item && t.item && t.item.setRootElement(void 0)
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.props.item.setRootElement(this.rootRef.current)
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.props.item.setRootElement(void 0)
      }, t.prototype.render = function () {
        var e = this;
        this.model.item = this.props.item;
        var t = this.model.allowAdd ? (0, s.attachKey2click)(s.createElement("span", {
            className: "svc-item-value-controls__button svc-item-value-controls__add",
            "aria-label": this.model.tooltip,
            onClick: function () {
              e.model.add(e.model), e.model.isNew = !1
            }
          }, s.createElement(s.SvgIcon, {
            size: 16,
            iconName: "icon-add_16x16",
            title: this.model.tooltip
          }))) : s.createElement(s.Fragment, null, " ", this.model.isDraggable ? s.createElement("span", {
            className: "svc-item-value-controls__button svc-item-value-controls__drag"
          }, s.createElement(s.SvgIcon, {
            className: "svc-item-value-controls__drag-icon",
            size: 24,
            iconName: "icon-drag-area-indicator",
            title: this.model.dragTooltip
          })) : null, this.model.allowRemove ? (0, s.attachKey2click)(s.createElement("span", {
            className: "svc-item-value-controls__button svc-item-value-controls__remove",
            "aria-label": this.model.tooltip,
            onClick: function () {
              return e.model.remove(e.model)
            }
          }, s.createElement(s.SvgIcon, {
            size: 16,
            iconName: "icon-remove_16x16",
            title: this.model.tooltip
          }))) : null),
          n = this.props.element.key + (this.model.allowAdd ? "_new" : "");
        return s.createElement("div", {
          ref: this.rootRef,
          className: "svc-item-value-wrapper" + (this.model.allowAdd ? " svc-item-value--new" : "") + (this.model.isDragging ? " svc-item-value--dragging" : "") + (this.model.isDragDropGhost ? " svc-item-value--ghost" : "") + (this.model.isDragDropMoveDown ? " svc-item-value--movedown" : "") + (this.model.isDragDropMoveUp ? " svc-item-value--moveup" : ""),
          key: n,
          "data-sv-drop-target-item-value": this.model.isDraggable ? this.model.item.value : void 0,
          onPointerDown: function (t) {
            return e.model.onPointerDown(t)
          }
        }, s.createElement("div", {
          className: "svc-item-value__ghost"
        }), s.createElement("div", {
          className: "svc-item-value-controls",
          onBlur: this.onBlur
        }, t), s.createElement("div", {
          className: "svc-item-value__item",
          onClick: function (t) {
            return e.model.select(e.model, t.nativeEvent)
          }
        }, this.props.element))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-item-value", (function (e) {
    return s.createElement(se, e)
  }));
  var le = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ue = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.preventDragHandler = function (e) {
          e.preventDefault()
        }, n.rootRef = s.createRef(), n
      }
      return le(t, e), t.prototype.createModel = function (e) {
        this.model = new l.ImageItemValueWrapperViewModel(e.componentData.creator, e.question, e.item, null, null)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["question", "item"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.props.question
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.model.itemsRoot = this.rootRef.current
      }, t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n), this.model.itemsRoot = this.rootRef.current
      }, t.prototype.renderLoadingIndicator = function () {
        return s.createElement("div", {
          className: "svc-image-item-value__loading"
        }, s.createElement(s.LoadingIndicatorComponent, null))
      }, t.prototype.render = function () {
        var e = this;
        this.model.item = this.props.item;
        var t = !this.props.question.isItemInList(this.props.item);
        this.model.isNew = t;
        var n, o = this.model.getIsNewItemSingle() ? null : {
          width: this.question.renderedImageWidth,
          height: this.question.renderedImageHeight
        };
        return n = t || this.model.isUploading ? s.createElement(s.Fragment, null, s.createElement("div", {
          className: "svc-image-item-value__item",
          onDrop: this.model.onDrop,
          onDragOver: this.model.onDragOver,
          onDragLeave: this.model.onDragLeave
        }, s.createElement("div", {
          className: "sd-imagepicker__item sd-imagepicker__item--inline"
        }, s.createElement("label", {
          className: "sd-imagepicker__label"
        }, s.createElement("div", {
          style: o,
          className: "sd-imagepicker__image"
        }, this.model.isUploading ? this.renderLoadingIndicator() : null)))), s.createElement("div", {
          className: "svc-image-item-value-controls"
        }, this.model.allowAdd && !this.model.isUploading ? (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button svc-image-item-value-controls__add",
          onClick: function () {
            return e.model.chooseNewFile(e.model)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-add-lg",
          title: this.model.addFileTitle
        }))) : null)) : s.createElement(s.Fragment, null, s.createElement("div", {
          className: "svc-image-item-value__item"
        }, this.props.element), this.model.isDraggable && this.model.canRenderControls ? s.createElement("span", {
          className: "svc-context-button svc-image-item-value-controls__drag-area-indicator",
          onPointerDown: function (t) {
            return e.model.onPointerDown(t)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-drag-area-indicator"
        })) : null, this.model.canRenderControls ? s.createElement("div", {
          className: "svc-context-container svc-image-item-value-controls"
        }, this.model.allowRemove && !this.model.isUploading ? (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button",
          onClick: function () {
            return e.model.chooseFile(e.model)
          }
        }, s.createElement(s.SvgIcon, {
          role: "button",
          size: 24,
          iconName: "icon-file",
          title: this.model.selectFileTitle
        }))) : null, this.model.allowRemove && !this.model.isUploading ? (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-context-button svc-context-button--danger",
          onClick: function () {
            return e.model.remove(e.model)
          }
        }, s.createElement(s.SvgIcon, {
          role: "button",
          size: 24,
          iconName: "icon-delete",
          title: this.model.removeFileTitle
        }))) : null) : null), s.createElement("div", {
          ref: this.rootRef,
          className: this.model.getRootCss(),
          key: this.props.element.key,
          "data-sv-drop-target-item-value": this.model.isDraggable ? this.model.item.value : void 0,
          onPointerDown: function (t) {
            return e.model.onPointerDown(t)
          },
          onDragStart: this.preventDragHandler
        }, s.createElement("div", {
          className: "svc-image-item-value-wrapper__ghost",
          style: o
        }), s.createElement("div", {
          className: "svc-image-item-value-wrapper__content"
        }, s.createElement("input", {
          type: "file",
          "aria-hidden": "true",
          tabIndex: -1,
          accept: this.model.acceptedTypes,
          className: "svc-choose-file-input",
          style: {
            position: "absolute",
            opacity: 0,
            width: "1px",
            height: "1px",
            overflow: "hidden"
          }
        }), n))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-image-item-value", (function (e) {
    return s.createElement(ue, e)
  }));
  var pe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    me = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return pe(t, e), t.prototype.createModel = function (e) {
        var t, n = e.componentData,
          o = !1;
        this.model && (o = this.model.isSelected), this.model = new l.MatrixCellWrapperViewModel(n.creator, n.element, n.question, n.row, n.column || (null === (t = n.element.cell) || void 0 === t ? void 0 : t.column)), this.model.isSelected = o
      }, t.prototype.getUpdatedModelProps = function () {
        return ["componentData"]
      }, t.prototype.componentDidUpdate = function (t, n) {
        var o, r;
        e.prototype.componentDidUpdate.call(this, t, n);
        var i = this.props.componentData;
        this.model.templateData = i.element, this.model.row = i.row, this.model.column = i.column || (null === (r = null === (o = i.element) || void 0 === o ? void 0 : o.cell) || void 0 === r ? void 0 : r.column), this.model.question = i.question
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        var e = this,
          t = null;
        return this.model.isSupportCellEditor && (t = s.createElement("div", {
          className: "svc-matrix-cell__question-controls"
        }, (0, s.attachKey2click)(s.createElement("span", {
          className: "svc-matrix-cell__question-controls-button",
          onClick: function (t) {
            return e.model.editQuestion(e.model, t)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: "icon-edit"
        }))))), s.createElement("div", {
          className: "svc-matrix-cell",
          tabIndex: -1,
          key: this.props.element.key,
          onClick: function (t) {
            return !e.props.question && e.model.selectContext(e.model, t)
          },
          onMouseOut: function (t) {
            return e.model.hover(t.nativeEvent, t.currentTarget)
          },
          onMouseOver: function (t) {
            return e.model.hover(t.nativeEvent, t.currentTarget)
          }
        }, s.createElement("div", {
          className: "svc-matrix-cell--selected" + (this.model.isSelected ? " svc-visible" : "")
        }), this.props.element, t)
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-matrix-cell", (function (e) {
    return s.createElement(me, e)
  }));
  var de = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    fe = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return de(t, e), t.prototype.createModel = function (e) {
        this.props.survey && (this.model = new l.SurveyResultsModel(e.survey))
      }, t.prototype.getUpdatedModelProps = function () {
        return ["survey"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        var e = this;
        return this.model ? s.createElement("div", {
          className: "svd-test-results"
        }, s.createElement("div", {
          className: "svd-test-results__header"
        }, s.createElement("div", {
          className: "svd-test-results__header-text"
        }, this.model.surveyResultsText), s.createElement("div", {
          className: "svd-test-results__header-types"
        }, s.createElement(I, {
          click: function () {
            return e.model.selectTableClick()
          },
          text: this.model.surveyResultsTableText,
          selected: this.model.isTableSelected,
          disabled: !1
        }), s.createElement(I, {
          click: function () {
            return e.model.selectJsonClick()
          },
          text: this.model.surveyResultsJsonText,
          selected: this.model.isJsonSelected,
          disabled: !1
        }))), this.renderResultAsText(), this.renderResultAsTable()) : null
      }, t.prototype.renderResultAsText = function () {
        return "text" !== this.model.resultViewType ? null : s.createElement("div", {
          className: "svd-test-results__text svd-light-bg-color"
        }, s.createElement("div", null, this.model.resultText))
      }, t.prototype.renderResultAsTable = function () {
        return "table" !== this.model.resultViewType ? null : s.createElement("div", {
          className: "svd-test-results__table svd-light-bg-color"
        }, s.createElement("table", null, s.createElement("thead", null, s.createElement("tr", {
          className: "svd-light-background-color"
        }, s.createElement("th", {
          key: 1,
          className: "svd-dark-border-color"
        }, this.model.resultsTitle), s.createElement("th", {
          key: 2,
          className: "svd-dark-border-color"
        }, this.model.resultsDisplayValue))), s.createElement("tbody", null, t.renderRows(this.model.resultData))))
      }, t.renderRows = function (e) {
        for (var t = [], n = 0; n < e.length; n++) t.push(s.createElement(he, {
          key: n + 1,
          row: e[n]
        }));
        return t
      }, t
    }(E),
    he = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return de(t, e), Object.defineProperty(t.prototype, "row", {
        get: function () {
          return this.props.row
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.row
      }, t.prototype.render = function () {
        var e = this;
        return s.createElement(s.Fragment, null, (0, s.attachKey2click)(s.createElement("tr", {
          onClick: function () {
            return e.row.toggle()
          }
        }, s.createElement("td", {
          key: 1,
          style: {
            paddingLeft: this.row.textMargin
          },
          className: "svd-dark-border-color"
        }, this.row.isNode ? s.createElement("span", {
          style: {
            left: this.row.markerMargin
          },
          className: "svd-test-results__marker " + (this.row.collapsed ? "" : "svd-test-results__marker--expanded")
        }, s.createElement(s.SvgIcon, {
          iconName: "icon-expand_16x16",
          size: 16
        })) : null, this.row.question ? s.createElement(s.SurveyLocStringViewer, {
          locStr: this.row.question.locTitle
        }) : s.createElement("span", null, this.row.title)), s.createElement("td", {
          key: 2,
          className: this.row.isNode ? "svd-test-results__node-value" : "svd-dark-border-color"
        }, this.row.getString(this.row.displayValue)))), this.row.isNode && !this.row.collapsed ? fe.renderRows(this.row.data) : null)
      }, t
    }(E),
    ye = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ve = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return ye(t, e), t.prototype.createModel = function (e) {
        this.model = new l.ToolboxToolViewModel(e.item, e.creator, e.parentModel)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["creator", "item"]
      }, Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "isCompact", {
        get: function () {
          return this.props.isCompact
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.render = function () {
        var e = this,
          t = this.item,
          n = s.ReactElementFactory.Instance.createElement(this.model.itemComponent, {
            item: t,
            creator: this.creator,
            parentModel: this.creator.toolbox,
            model: this.model,
            isCompact: this.isCompact
          });
        return s.createElement("div", {
          className: t.css,
          key: t.id
        }, t.needSeparator && !this.creator.toolbox.showCategoryTitles ? s.createElement("div", {
          className: "svc-toolbox__category-separator"
        }) : null, s.createElement("div", {
          className: "svc-toolbox__tool-content sv-action__content",
          onPointerDown: function (t) {
            t.persist(), e.model.onPointerDown(t)
          }
        }, n))
      }, t
    }(E),
    ge = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return ye(t, e), t.prototype.getUpdatedModelProps = function () {
        return ["creator", "item"]
      }, Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        var e = this,
          t = this.props.isCompact ? s.createElement("span", {
            className: "svc-toolbox__item-banner",
            onClick: function (t) {
              t.persist(), e.model.click(t)
            }
          }, s.createElement(s.SvgIcon, {
            size: 24,
            iconName: this.item.iconName,
            className: "svc-toolbox__item-icon",
            title: this.item.tooltip
          }), s.createElement("span", null, this.item.title)) : null,
          n = (0, s.attachKey2click)(s.createElement("div", {
            className: this.item.renderedCss,
            tabIndex: 0,
            role: "button",
            "aria-label": this.item.tooltip,
            onClick: function (t) {
              t.persist(), e.model.click(t)
            }
          }, s.createElement("span", {
            className: "svc-toolbox__item-container"
          }, this.item.iconName ? s.createElement(s.SvgIcon, {
            size: 24,
            iconName: this.item.iconName
          }) : null), this.props.isCompact ? null : s.createElement("span", {
            className: "svc-toolbox__item-title"
          }, this.item.title)));
        return s.createElement(s.Fragment, null, n, t)
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-toolbox-item", (function (e) {
    return (0, s.createElement)(ge, e)
  }));
  var Ee = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    _e = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Ee(t, e), t.prototype.getUpdatedModelProps = function () {
        return ["creator", "item"]
      }, Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "isCompact", {
        get: function () {
          return this.props.isCompact
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "parentModel", {
        get: function () {
          return this.props.parentModel
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.render = function () {
        var e = this;
        return s.createElement(s.Fragment, null, s.createElement(ge, {
          item: this.item,
          creator: this.creator,
          model: this.model,
          parentModel: this.parentModel,
          isCompact: this.isCompact
        }), s.createElement("div", {
          className: "svc-toolbox__item-submenu-button",
          onMouseOver: function (t) {
            return e.model.onMouseOver(e.item, t)
          },
          onMouseLeave: function (t) {
            return e.model.onMouseLeave(e.item, t)
          }
        }, s.createElement(s.SvgIcon, {
          size: 24,
          iconName: this.item.subitemsButtonIcon
        }), s.createElement(s.Popup, {
          model: this.item.popupModel,
          getArea: this.item.getArea
        })))
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement("svc-toolbox-item-group", (function (e) {
    return s.createElement(_e, e)
  }));
  var be = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    we = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return be(t, e), Object.defineProperty(t.prototype, "category", {
        get: function () {
          return this.props.category
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "toolbox", {
        get: function () {
          return this.props.toolbox
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "class", {
        get: function () {
          return "svc-toolbox__category" + (this.category.collapsed ? " svc-toolbox__category--collapsed" : "") + (this.category.empty ? " svc-toolbox__category--empty" : "")
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.category
      }, t.prototype.render = function () {
        var e = this.renderCategoryHeader(),
          t = this.renderCategoryContent();
        return s.createElement("div", {
          className: this.class,
          key: this.category.name
        }, s.createElement("div", {
          className: "svc-toolbox__category-header-wrapper"
        }, e), t)
      }, t.prototype.renderCategoryHeader = function () {
        var e = this,
          t = "svc-toolbox__category-header";
        return this.toolbox.canCollapseCategories && (t += " svc-toolbox__category-header--collapsed"), (0, s.attachKey2click)(s.createElement("div", {
          className: t,
          onClick: function (t) {
            return e.category.toggleState()
          }
        }, s.createElement("span", {
          className: "svc-toolbox__category-title"
        }, this.category.title), this.renderButton()))
      }, t.prototype.renderButton = function () {
        if (!this.toolbox.canCollapseCategories) return null;
        var e = this.category.collapsed ? "arrow-down" : "arrow-up",
          t = "svc-toolbox__category-header__button svc-string-editor__button--" + (this.category.collapsed ? "expand" : "collapse");
        return s.createElement("div", {
          className: "svc-toolbox__category-header__controls"
        }, s.createElement(s.SvgIcon, {
          className: t,
          iconName: "icon-" + e,
          size: 24
        }))
      }, t.prototype.renderCategoryContent = function () {
        return this.renderItems(this.category.items)
      }, t.prototype.renderItems = function (e, t) {
        var n = this;
        return void 0 === t && (t = !1), e.map((function (e, o) {
          return s.createElement(ve, {
            item: e,
            creator: n.toolbox.creator,
            parentModel: n.toolbox,
            isCompact: t,
            key: "item" + o
          })
        }))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-toolbox-category", (function (e) {
    return s.createElement(we, e)
  }));
  var Oe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ce = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Oe(t, e), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "toolbox", {
        get: function () {
          return this.creator.toolbox
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.toolbox
      }, t.prototype.render = function () {
        return this.toolbox.hasActions ? s.createElement("div", {
          className: "svc-toolbox"
        }, s.createElement("div", {
          className: "svc-toolbox__container"
        }, 1 != this.toolbox.categories.length && this.toolbox.showCategoryTitles ? this.renderCategories() : s.createElement("div", {
          className: "svc-toolbox__category"
        }, this.renderItems(this.toolbox.visibleActions)))) : null
      }, t.prototype.renderItems = function (e, t) {
        var n = this;
        void 0 === t && (t = !1);
        var o = [];
        return e.forEach((function (e, r) {
          var i = s.createElement(ve, {
            item: e,
            creator: n.creator,
            parentModel: n.toolbox,
            isCompact: t,
            key: "item" + r
          });
          o.push(i)
        })), o
      }, t.prototype.renderCategories = function () {
        var e = this;
        return this.toolbox.categories.map((function (t, n) {
          return s.createElement(we, {
            category: t,
            toolbox: e.toolbox,
            key: "category" + n
          })
        }))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-toolbox", (function (e) {
    return s.createElement(Ce, e)
  }));
  var Se = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ne = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Se(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.creator
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.render = function () {
        if (!this.model || !this.model.renderElements) return null;
        var e = this.renderItems();
        return s.createElement("div", {
          className: this.model.cssClasses.root
        }, e)
      }, t.prototype.renderItems = function () {
        var e = this;
        return this.model.renderedActions.map((function (t, n) {
          return s.createElement(ve, {
            item: t,
            creator: e.creator,
            parentModel: e.model,
            isCompact: !1,
            key: "item" + n
          })
        }))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-toolbox-list", (function (e) {
    return s.createElement(Ne, e)
  }));
  var Pe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    xe = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.state = {
          filterString: n.model.filterString || ""
        }, n
      }
      return Pe(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this;
        return this.model.isVisible ? s.createElement("div", {
          className: "spg-search-editor_container"
        }, s.createElement("div", {
          className: "spg-search-editor_search-icon"
        }, s.createElement(s.SvgIcon, {
          iconName: "icon-search",
          size: "auto"
        })), s.createElement("input", {
          type: "text",
          className: "spg-search-editor_input",
          "aria-label": this.model.filterStringPlaceholder,
          placeholder: this.model.filterStringPlaceholder,
          value: this.state.filterString,
          onChange: function (t) {
            var n = u.settings.environment.root;
            t.target === n.activeElement && (e.model.filterString = t.target.value)
          }
        }), s.createElement("div", {
          className: "spg-search-editor_toolbar"
        }, s.createElement("div", {
          className: "spg-search-editor_toolbar-counter"
        }, this.model.matchCounterText), s.createElement(s.SurveyActionBar, {
          model: this.model.searchActionBar
        }))) : null
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-search", (function (e) {
    return s.createElement(xe, e)
  }));
  var je = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Te = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.rootRef = s.createRef(), n
      }
      return je(t, e), t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this);
        var t = this.rootRef.current;
        this.toolbox.setRootElement(t), t && (this.manager = new u.VerticalResponsivityManager(this.toolbox.containerElement, this.toolbox, this.toolbox.itemSelector, null, void 0, (function (e) {
          return setTimeout(e)
        })))
      }, t.prototype.componentWillUnmount = function () {
        this.manager && this.manager.dispose(), this.toolbox.unsubscribeRootElement(), e.prototype.componentWillUnmount.call(this)
      }, t.prototype.renderSearch = function () {
        var e = this.toolbox.isCompactRendered ? s.createElement(s.Fragment, null, s.createElement(ve, {
          item: this.toolbox.searchItem,
          creator: this.creator,
          parentModel: this.toolbox,
          isCompact: this.toolbox.isCompactRendered,
          key: "searchitem"
        })) : null;
        return s.createElement("div", {
          className: "svc-toolbox__search-container"
        }, e, s.createElement(xe, {
          model: this.toolbox.searchManager
        }), s.createElement("div", {
          className: "svc-toolbox__category-separator svc-toolbox__category-separator--search"
        }))
      }, t.prototype.render = function () {
        var e = this;
        if (!this.toolbox.hasActions) return null;
        var t = this.toolbox.showSearch ? this.renderSearch() : null,
          n = this.toolbox.showPlaceholder ? s.createElement("div", {
            className: "svc-toolbox__placeholder"
          }, this.toolbox.toolboxNoResultsFound) : null;
        return s.createElement("div", {
          ref: this.rootRef,
          className: this.toolbox.classNames
        }, s.createElement("div", {
          onBlur: function (t) {
            return e.toolbox.focusOut(t)
          },
          className: "svc-toolbox__panel"
        }, t, s.createElement("div", {
          onBlur: function (t) {
            return e.toolbox.focusOut(t)
          },
          className: "svc-toolbox__scroll-wrapper"
        }, s.createElement("div", {
          className: "svc-toolbox__scroller sv-drag-target-skipped",
          onScroll: function (t) {
            return e.toolbox.onScroll(e.toolbox, t)
          }
        }, n, s.createElement("div", {
          className: "svc-toolbox__container"
        }, this.toolbox.showInSingleCategory ? s.createElement("div", {
          className: "svc-toolbox__category"
        }, this.renderItems(this.toolbox.renderedActions, this.toolbox.isCompactRendered)) : this.renderCategories())), s.createElement("div", {
          className: "svc-toolbox__scrollbar",
          onScroll: function (t) {
            return e.toolbox.onScrollbarScroll(t.nativeEvent)
          }
        }, s.createElement("div", {
          className: "svc-toolbox__scrollbar-sizer"
        })))))
      }, t
    }(Ce);
  s.ReactElementFactory.Instance.registerElement("svc-adaptive-toolbox", (function (e) {
    return s.createElement(Te, e)
  }));
  var qe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ie = function (e) {
      function t() {
        var t = null !== e && e.apply(this, arguments) || this;
        return t.onPropChangedHandler = function (e, n) {
          if (!t.isRendering) {
            var o = ["showProgressBar", "progressBarType", "currentPageValue"];
            if (!(o.indexOf(n.name) < 0)) {
              for (var r = {}, i = 0; i < o.length; i++) {
                var a = o[i];
                r[a] = t.survey[a]
              }
              t.setState(r)
            }
          }
        }, t
      }
      return qe(t, e), t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.setHandler()
      }, t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n), this.setHandler()
      }, t.prototype.setHandler = function () {
        this.survey && !this.survey.onPropertyChanged.hasFunc(this.onPropChangedHandler) && this.survey.onPropertyChanged.add(this.onPropChangedHandler)
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.survey && this.survey.onPropertyChanged.remove(this.onPropChangedHandler)
      }, Object.defineProperty(t.prototype, "survey", {
        get: function () {
          return this.props.survey
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "location", {
        get: function () {
          return this.props.location
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "isTop", {
        get: function () {
          return "top" == this.location
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.canRender = function () {
        return this.isTop ? this.survey.isShowProgressBarOnTop : this.survey.isShowProgressBarOnBottom
      }, t.prototype.renderElement = function () {
        return s.ReactElementFactory.Instance.createElement(this.survey.getProgressTypeComponent(), {
          survey: this.survey,
          css: this.survey.css,
          isTop: this.isTop
        })
      }, t
    }(s.SurveyElementBase),
    Re = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Me = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Re(t, e), t.prototype.getStateElement = function () {
        return this.props.model
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement("div", {
          className: "svc-menu-action"
        }, s.createElement("div", {
          className: this.props.model.buttonClassName,
          title: this.props.model.tooltip,
          onClick: function () {
            e.props.model.action()
          }
        }, s.createElement("div", {
          className: "svc-menu-action__icon"
        }, s.createElement("div", {
          className: "svc-menu-action__icon-container"
        }, s.createElement(s.SvgIcon, {
          iconName: this.props.model.iconName,
          size: 24
        })))))
      }, t
    }(s.SurveyElementBase),
    Fe = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    De = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Fe(t, e), t.prototype.getStateElement = function () {
        return this.props.model
      }, t.prototype.canRender = function () {
        return !!this.props.model && e.prototype.canRender.call(this)
      }, t.prototype.renderElement = function () {
        return s.createElement("div", {
          className: this.props.model.sideBarClassName
        }, s.createElement("div", {
          className: "svc-sidebar-tabs__top-container"
        }, s.createElement("div", {
          className: "svc-sidebar-tabs__collapse-button"
        }, s.createElement(Me, {
          model: this.props.model.expandCollapseAction
        })), s.createElement("div", {
          className: "svc-sidebar-tabs__separator"
        }, s.createElement("div", null)), s.createElement("div", {
          className: "svc-sidebar-tabs__items"
        }, s.createElement(Be, {
          model: this.props.model.topToolbar
        }))), s.createElement("div", {
          className: "svc-sidebar-tabs__bottom-container"
        }, s.createElement("div", {
          className: "svc-sidebar-tabs__items"
        }, s.createElement(Be, {
          model: this.props.model.bottomToolbar
        }))))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-control", (function (e) {
    return s.createElement(De, e)
  }));
  var Be = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Fe(t, e), t.prototype.getStateElement = function () {
        return this.props.model
      }, t.prototype.renderElement = function () {
        return s.createElement(s.Fragment, null, this.props.model.actions.map((function (e, t) {
          return s.createElement(Me, {
            model: e,
            key: "item" + t
          })
        })))
      }, t
    }(s.SurveyElementBase),
    ke = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ae = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return ke(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this.model.title ? s.createElement("div", {
          className: "svc-side-bar__container-title"
        }, this.model.title) : null;
        return s.createElement("div", {
          className: "svc-side-bar__container-header"
        }, s.createElement("div", {
          className: "svc-side-bar__container-actions"
        }, s.createElement(s.SurveyActionBar, {
          model: this.model.toolbar
        })), e)
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-side-bar-default-header", (function (e) {
    return s.createElement(Ae, e)
  }));
  var Ue = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Qe = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Ue(t, e), Object.defineProperty(t.prototype, "objectSelectionAction", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.objectSelectionAction
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement("div", {
          className: "svc-sidebar__header svc-sidebar__header--tabbed"
        }, s.createElement("div", {
          className: "svc-sidebar__header-container svc-sidebar__header-container--with-subtitle"
        }, s.createElement("div", {
          className: "svc-sidebar__header-content",
          onClick: function () {
            return e.objectSelectionAction.action()
          }
        }, s.createElement("div", {
          className: this.objectSelectionAction.buttonClassName
        }, s.createElement("div", {
          className: "svc-sidebar__header-caption"
        }, s.createElement("span", {
          className: "svc-sidebar__header-title"
        }, this.objectSelectionAction.title), s.createElement("span", {
          className: "svc-sidebar__header-subtitle"
        }, this.objectSelectionAction.tooltip))), s.createElement(s.Popup, {
          model: this.objectSelectionAction.popupModel,
          getTarget: u.getActionDropdownButtonTarget
        }))))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-side-bar-property-grid-header", (function (e) {
    return s.createElement(Qe, e)
  }));
  var Le = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ve = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Le(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        return s.createElement("div", {
          className: "svc-side-bar__container-header svc-sidebar__header-container"
        }, this.model.subTitle ? s.createElement("div", {
          className: "svc-sidebar__header-caption"
        }, s.createElement("span", {
          className: "svc-sidebar__header-title"
        }, this.model.title), s.createElement("span", {
          className: "svc-sidebar__header-subtitle"
        }, this.model.subTitle)) : s.createElement("div", {
          className: "svc-side-bar__container-title"
        }, this.model.title))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-side-bar-header", (function (e) {
    return s.createElement(Ve, e)
  }));
  var Ke = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    He = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.containerRef = s.createRef(), n
      }
      return Ke(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.model.initResizeManager(this.containerRef.current)
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.model.resetResizeManager()
      }, t.prototype.canRender = function () {
        return !!this.model && e.prototype.canRender.call(this)
      }, t.prototype.renderElement = function () {
        var e = this,
          t = {
            display: this.model.renderRoot ? "" : "none"
          },
          n = {
            display: this.model.renderContainer ? "" : "none"
          },
          o = this.model.pages.map((function (e) {
            return s.createElement(ze, {
              page: e,
              key: e.id
            })
          })),
          r = s.ReactElementFactory.Instance.createElement(this.model.header.component, {
            model: this.model.header.componentModel
          }),
          i = null;
        return this.model.sideAreaComponentName && (i = s.ReactElementFactory.Instance.createElement(this.model.sideAreaComponentName, {
          model: this.model.sideAreaComponentData
        })), s.createElement("div", {
          className: this.model.rootCss,
          style: t
        }, s.createElement("div", {
          className: "svc-side-bar__shadow",
          onClick: function () {
            return e.model.collapseSidebar()
          },
          style: n
        }), s.createElement("div", {
          className: "svc-flex-row svc-side-bar__wrapper"
        }, s.createElement("div", {
          className: "svc-side-bar__container-wrapper",
          style: n
        }, s.createElement("div", {
          ref: this.containerRef,
          className: "svc-side-bar__container"
        }, r, s.createElement("div", {
          className: "svc-side-bar__container-content"
        }, o))), i))
      }, t
    }(s.SurveyElementBase),
    ze = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Ke(t, e), Object.defineProperty(t.prototype, "page", {
        get: function () {
          return this.props.page
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.page
      }, t.prototype.renderElement = function () {
        return this.page.visible ? s.ReactElementFactory.Instance.createElement(this.page.componentName, {
          model: this.page.componentData
        }) : null
      }, t
    }(s.SurveyElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("svc-side-bar-page", (function (e) {
    return s.createElement(ze, e)
  })), s.ReactElementFactory.Instance.registerElement("svc-side-bar", (function (e) {
    return s.createElement(He, e)
  }));
  var We = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ge = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return We(t, e), t.prototype.render = function () {
        return s.createElement("div", {
          className: "sd-translation-line-skeleton"
        })
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("sd-translation-line-skeleton", (function (e) {
    return s.createElement(Ge, e)
  }));
  var Je = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Xe = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Je(t, e), Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.renderElement = function () {
        var e = this.item;
        return s.createElement("div", {
          className: e.data.containerCss
        }, s.createElement("span", {
          className: e.data.additionalTitleCss
        }, e.data.additionalTitle), s.ReactElementFactory.Instance.createElement("sv-action-bar-item-dropdown", {
          item: this.item
        }))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-translate-from-action", (function (e) {
    return s.createElement(Xe, e)
  }));
  var Ye = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ze = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.onChangedHandler = function (e, t) {
          n.setState({
            changed: n.state && n.state.changed ? n.state.changed + 1 : 1
          })
        }, n.onBlur = function (e) {
          return n.svStringEditorRef.current && (n.svStringEditorRef.current.spellcheck = !1), n.locString.__isEditing = !1, n.justFocused = !1, n.baseModel.onBlur(e.nativeEvent), n.baseModel.errorText
        }, n.onCompositionStart = function (e) {
          n.baseModel.onCompositionStart(e.nativeEvent)
        }, n.onCompositionEnd = function (e) {
          n.baseModel.onCompositionEnd(e.nativeEvent)
        }, n.onInput = function (e) {
          n.baseModel.onInput(e.nativeEvent)
        }, n.onPaste = function (e) {
          n.baseModel.onPaste(e.nativeEvent)
        }, n.justFocused = !1, n.onFocus = function (e) {
          n.baseModel.onFocus(e.nativeEvent), n.justFocused = !0
        }, n.onKeyDown = function (e) {
          return n.baseModel.onKeyDown(e.nativeEvent)
        }, n.onKeyUp = function (e) {
          return n.baseModel.onKeyUp(e.nativeEvent)
        }, n.onMouseUp = function (e) {
          return n.baseModel.onMouseUp(e.nativeEvent)
        }, n.done = function (e) {
          n.baseModel.done(e), n.locString.__isEditing = !1
        }, n.edit = function (e) {
          n.svStringEditorRef.current.focus(), n.locString.__isEditing = !0, n.baseModel.onClick(e)
        }, n.state = {
          changed: 0
        }, n.svStringEditorRef = s.createRef(), n
      }
      return Ye(t, e), t.prototype.createModel = function (e) {
        this.baseModel && this.baseModel.dispose(), this.baseModel = new l.StringEditorViewModelBase(this.locString, this.creator)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["creator", "locString"]
      }, Object.defineProperty(t.prototype, "locString", {
        get: function () {
          return this.props.locStr.locStr
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.props.locStr.creator
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "style", {
        get: function () {
          return this.props.style
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.baseModel
      }, Object.defineProperty(t.prototype, "errorText", {
        get: function () {
          return this.baseModel.errorText
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidMount = function () {
        var t = this;
        e.prototype.componentDidMount.call(this), this.locString && (this.baseModel.setLocString(this.locString), this.baseModel.getEditorElement = function () {
          return t.svStringEditorRef.current
        }, this.baseModel.blurEditor = function () {
          t.svStringEditorRef.current.blur(), t.svStringEditorRef.current.spellcheck = !1
        }, this.baseModel.afterRender(), this.locString.onStringChanged.add(this.onChangedHandler), this.locString.__isEditing && this.svStringEditorRef.current.focus())
      }, t.prototype.componentDidUpdate = function (t, n) {
        e.prototype.componentDidUpdate.call(this, t, n), this.baseModel.setLocString(this.locString), this.baseModel.afterRender()
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.baseModel.detachFromUI(), this.locString && this.locString.onStringChanged.remove(this.onChangedHandler)
      }, Object.defineProperty(t.prototype, "placeholder", {
        get: function () {
          return this.baseModel.placeholder
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "contentEditable", {
        get: function () {
          return this.baseModel.contentEditable
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "className", {
        get: function () {
          return this.baseModel.className(this.locString.renderedHtml)
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.render = function () {
        if (!this.locString) return null;
        var e = null;
        if (this.locString.hasHtml) {
          var t = {
            __html: this.locString.renderedHtml
          };
          e = s.createElement("span", {
            role: "textbox",
            ref: this.svStringEditorRef,
            className: "sv-string-editor sv-string-editor--html",
            contentEditable: this.contentEditable,
            spellCheck: !1,
            "aria-placeholder": this.placeholder,
            "aria-label": this.placeholder || "content editable",
            suppressContentEditableWarning: !0,
            dangerouslySetInnerHTML: t,
            onBlur: this.onBlur,
            onFocus: this.onFocus,
            onKeyDown: this.onKeyDown,
            onMouseUp: this.onMouseUp,
            onClick: this.edit
          })
        } else e = s.createElement("span", {
          role: "textbox",
          ref: this.svStringEditorRef,
          className: "sv-string-editor",
          contentEditable: this.contentEditable,
          spellCheck: !1,
          "aria-placeholder": this.placeholder,
          "aria-label": this.placeholder || "content editable",
          suppressContentEditableWarning: !0,
          key: this.locString.renderedHtml,
          onBlur: this.onBlur,
          onInput: this.onInput,
          onPaste: this.onPaste,
          onCompositionStart: this.onCompositionStart,
          onCompositionEnd: this.onCompositionEnd,
          onFocus: this.onFocus,
          onKeyDown: this.onKeyDown,
          onKeyUp: this.onKeyUp,
          onMouseUp: this.onMouseUp,
          onClick: this.edit
        }, this.locString.renderedHtml);
        var n = this.baseModel.showCharacterCounter ? s.createElement(s.CharacterCounterComponent, {
          counter: this.baseModel.characterCounter,
          remainingCharacterCounter: this.baseModel.getCharacterCounterClass
        }) : null;
        return s.createElement("span", {
          className: this.className
        }, s.createElement("span", {
          className: "svc-string-editor__content"
        }, s.createElement("div", {
          className: "svc-string-editor__border svc-string-editor__border--hover",
          onClick: this.edit
        }), s.createElement("div", {
          className: "svc-string-editor__border svc-string-editor__border--focus",
          onClick: this.edit
        }), s.createElement("span", {
          className: "svc-string-editor__input"
        }, e, s.createElement("div", {
          className: "svc-string-editor__controls",
          onClick: this.edit
        }), n)), this.errorText ? s.createElement("span", {
          className: "svc-string-editor__error"
        }, this.errorText) : "")
      }, t
    }(E);
  s.ReactElementFactory.Instance.registerElement(l.editableStringRendererName, (function (e) {
    return s.createElement(Ze, e)
  }));
  var $e = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    et = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return $e(t, e), t.prototype.render = function () {
        return s.createElement("div", null, s.createElement(s.SvgIcon, {
          "aria-hidden": "true",
          iconName: "icon-alert_24x24",
          size: "24",
          className: this.props.cssClasses.error.icon
        }), s.createElement("span", {
          className: this.props.cssClasses.error.item || void 0
        }, s.createElement(s.SurveyLocStringViewer, {
          locStr: this.props.error.locText
        })))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-question-error", (function (e) {
    return s.createElement(et, e)
  }));
  var tt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    nt = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return tt(t, e), t.prototype.renderInput = function () {
        var e = this.question;
        (0, l.initLogicOperator)(e);
        var t = e.selectedItemLocText ? this.renderLocString(e.selectedItemLocText) : "";
        return s.createElement("div", {
          id: this.question.inputId,
          className: e.getControlClass(),
          tabIndex: this.question.isInputReadOnly ? void 0 : 0,
          disabled: this.question.isInputReadOnly,
          required: this.question.isRequired,
          onChange: this.updateValueOnEvent,
          onInput: this.updateValueOnEvent,
          onKeyUp: this.keyhandler,
          role: this.question.ariaRole,
          "aria-required": this.question.ariaRequired,
          "aria-label": this.question.ariaLabel,
          "aria-invalid": this.question.ariaInvalid,
          "aria-describedby": this.question.ariaDescribedBy
        }, s.createElement("div", {
          className: this.question.cssClasses.controlValue
        }, t, s.createElement("div", null, e.readOnlyText)), this.createClearButton())
      }, t
    }(s.SurveyQuestionDropdown);
  s.ReactQuestionFactory.Instance.registerQuestion("sv-logic-operator", (function (e) {
    return s.createElement(nt, e)
  })), u.RendererFactory.Instance.registerRenderer("dropdown", "logicoperator", "sv-logic-operator");
  var ot = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    rt = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.containerRef = s.createRef(), n
      }
      return ot(t, e), t.prototype.createModel = function (e) {
        this.model && this.model.dispose(), this.model = new l.PageNavigatorViewModel(e.pagesController, e.pageEditMode)
      }, t.prototype.getUpdatedModelProps = function () {
        return ["pagesController", "pageEditMode"]
      }, t.prototype.getStateElement = function () {
        return this.model
      }, Object.defineProperty(t.prototype, "scrollableContainer", {
        get: function () {
          var e = this.containerRef.current;
          return e ? e.parentElement.parentElement.parentElement : e
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidMount = function () {
        if (e.prototype.componentDidMount.call(this), "bypage" !== this.props.pageEditMode) {
          var t = this.containerRef.current;
          if (t) {
            var n = this;
            t.parentElement.parentElement.parentElement.onscroll = function (e) {
              return n.model.onContainerScroll(e.currentTarget)
            }, n.model.setItemsContainer(t.parentElement), n.model.setScrollableContainer(t.parentElement.parentElement.parentElement)
          }
        }
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this);
        var t = this.containerRef.current;
        t && (t.parentElement.parentElement.parentElement.onscroll = void 0), this.model.stopItemsContainerHeightObserver(), this.model.setScrollableContainer(void 0)
      }, t.prototype.renderElement = function () {
        var e = this,
          t = "svc-page-navigator__selector";
        return this.model.isPopupOpened && (t += " svc-page-navigator__selector--opened"), s.createElement("div", {
          className: "svc-page-navigator",
          ref: this.containerRef,
          style: {
            display: this.model.visible ? "flex" : "none"
          }
        }, (0, s.attachKey2click)(s.createElement("div", {
          className: t,
          onClick: function () {
            return e.model.togglePageSelector()
          },
          title: this.model.pageSelectorCaption
        }, s.createElement(s.SvgIcon, {
          className: "svc-page-navigator__navigator-icon",
          iconName: this.model.icon,
          size: 24,
          title: this.model.pageSelectorCaption
        }), s.createElement(s.Popup, {
          model: this.model.popupModel
        }))), s.createElement("div", null, this.model.visibleItems.map((function (e) {
          return s.createElement(it, {
            key: e.id,
            item: e
          })
        }))))
      }, t
    }(E),
    it = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return ot(t, e), t.prototype.getStateElement = function () {
        return this.props.item
      }, t.prototype.renderElement = function () {
        var e = this.props.item,
          t = "svc-page-navigator-item-content";
        return (0, u.unwrap)(e.active) && (t += " svc-page-navigator-item--selected"), (0, u.unwrap)(e.disabled) && (t += " svc-page-navigator-item--disabled"), s.createElement("div", {
          className: "svc-page-navigator-item"
        }, (0, s.attachKey2click)(s.createElement("div", {
          className: t,
          onClick: function (t) {
            e.action(e), t.stopPropagation()
          }
        }, s.createElement("div", {
          className: "svc-page-navigator-item__dot",
          title: e.title
        }), s.createElement("div", {
          className: "svc-page-navigator-item__banner"
        }, s.createElement("span", {
          className: "svc-page-navigator-item__text"
        }, e.title), s.createElement("span", {
          className: "svc-page-navigator-item__dot"
        })))))
      }, t
    }(E),
    at = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ct = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return at(t, e), t.prototype.render = function () {
        return s.createElement("div", {
          className: "svc-surface-placeholder"
        }, s.createElement("div", {
          className: "svc-surface-placeholder__image svc-surface-placeholder__image--" + this.props.name
        }), s.createElement("div", {
          className: "svc-surface-placeholder__text"
        }, s.createElement("div", {
          className: "svc-surface-placeholder__title"
        }, this.props.placeholderTitleText), s.createElement("div", {
          className: "svc-surface-placeholder__description"
        }, this.props.placeholderDescriptionText)))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-surface-placeholder", (function (e) {
    return s.createElement(ct, e)
  }));
  var st = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    lt = function () {
      return lt = Object.assign || function (e) {
        for (var t, n = 1, o = arguments.length; n < o; n++)
          for (var r in t = arguments[n]) Object.prototype.hasOwnProperty.call(t, r) && (e[r] = t[r]);
        return e
      }, lt.apply(this, arguments)
    },
    ut = function (e) {
      function t() {
        var t = null !== e && e.apply(this, arguments) || this;
        return t.denyUpdate = function () {
          t.denyComponentUpdate()
        }, t.allowUpdate = function () {
          t.allowComponentUpdate()
        }, t.addDragDropEvents = function () {
          t.creator.onDragStart.add(t.denyUpdate), t.creator.onDragEnd.add(t.allowUpdate)
        }, t.clearDragDropEvents = function () {
          t.creator.onDragStart.remove(t.denyUpdate), t.creator.onDragEnd.remove(t.allowUpdate)
        }, t.renderedPagesCache = {}, t
      }
      return st(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data
        },
        enumerable: !1,
        configurable: !0
      }), Object.defineProperty(t.prototype, "creator", {
        get: function () {
          return this.model.creator
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidMount = function () {
        e.prototype.componentDidMount.call(this), this.addDragDropEvents()
      }, t.prototype.componentWillUnmount = function () {
        e.prototype.componentWillUnmount.call(this), this.clearDragDropEvents(), e.prototype.componentWillUnmount.call(this)
      }, t.prototype.getStateElements = function () {
        return [this.model, this.model.survey, this.model.pagesController]
      }, t.prototype.getRenderedPages = function () {
        var e = this;
        "pages" !== this.changedStatePropName && (this.renderedPagesCache = {});
        var t = [];
        if ("bypage" !== this.creator.pageEditMode) this.creator.survey.pages.forEach((function (n, o) {
          var r = e.renderedPagesCache[n.id];
          r || (r = e.createRenderedPage(n, o), e.renderedPagesCache[n.id] = r), t.push(r)
        })), this.model.showNewPage && t.push(this.renderNewPage("svc-page", this.model.newPage.id + "-ghost-new-page"));
        else {
          var n = this.model.pagesController.page2Display;
          if (n) {
            var o = this.renderedPagesCache[n.id];
            o || (o = this.createRenderedPage(n, 0, this.model.newPage === n), this.renderedPagesCache[n.id] = o), t.push(o)
          }
        }
        return t
      }, t.prototype.createRenderedPage = function (e, t, n) {
        return s.createElement("div", {
          className: "svc-page",
          "data-sv-drop-target-page": e.name,
          "data-sv-drop-target-survey-element": n ? "newGhostPage" : e.name,
          key: e.id + "-" + t
        }, this.renderPage(e))
      }, t.prototype.renderNewPage = function (e, t) {
        return void 0 === t && (t = ""), s.createElement(s.Fragment, {
          key: t
        }, s.createElement("div", {
          className: e,
          "data-sv-drop-target-survey-element": "newGhostPage"
        }, this.model.newPage ? this.renderPage(this.model.newPage) : null))
      }, t.prototype.renderPage = function (e) {
        return s.ReactElementFactory.Instance.createElement("svc-page", {
          survey: this.creator.survey,
          page: e,
          creator: this.creator
        })
      }, t.prototype.renderElement = function () {
        var e = this,
          t = "svc-tab-designer " + this.model.getRootCss();
        return s.createElement(s.Fragment, null, s.createElement("div", {
          className: "svc-flex-column"
        }, this.model.isToolboxVisible ? s.ReactElementFactory.Instance.createElement("svc-adaptive-toolbox", {
          model: this.creator
        }) : null), s.createElement("div", {
          className: t,
          onClick: function () {
            return e.model.clickDesigner()
          }
        }, s.createElement("div", {
          className: "svc-tab-designer_content"
        }, this.model.showPlaceholder ? this.renderPlaceHolder() : this.renderTabContent())))
      }, t.prototype.renderHeader = function (e) {
        if (!e) return null;
        var t = this.creator.survey;
        return s.createElement(s.Fragment, null, s.createElement("div", {
          className: "svc-designer-header"
        }, s.createElement(s.SurveyHeader, {
          survey: t
        })))
      }, t.prototype.renderPlaceHolder = function () {
        var e = this.renderHeader(this.creator.allowEditSurveyTitle && this.creator.showHeaderInEmptySurvey);
        return s.createElement(s.Fragment, null, e, s.createElement("div", {
          className: "svc-designer__placeholder-container",
          "data-sv-drop-target-survey-element": "newGhostPage"
        }, this.renderPlaceHolderContent(), this.renderNewPage("svc-designer-placeholder-page")))
      }, t.prototype.renderPlaceHolderContent = function () {
        return s.createElement(ct, {
          name: "designer",
          placeholderTitleText: this.model.placeholderTitleText,
          placeholderDescriptionText: this.model.placeholderDescriptionText
        })
      }, t.prototype.renderTabContent = function () {
        var e = this.creator.survey,
          t = this.renderHeader(this.creator.allowEditSurveyTitle),
          n = lt({}, this.creator.designTabSurveyThemeVariables);
        return e.width && (n.maxWidth = e.renderedWidth), s.createElement(s.Fragment, null, s.createElement("div", {
          className: this.model.designerCss,
          style: n
        }, t, this.getRenderedPages()), this.creator.showPageNavigator ? s.createElement("div", {
          className: "svc-tab-designer__page-navigator"
        }, s.createElement(rt, {
          pagesController: this.model.pagesController,
          pageEditMode: this.model.creator.pageEditMode
        })) : null, this.model.hasToolbar ? s.createElement("div", {
          className: "svc-tab-designer__toolbar"
        }, s.createElement(s.SurveyActionBar, {
          model: this.model.actionContainer,
          handleClick: !1
        })) : null)
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-designer", (function (e) {
    return s.createElement(ut, e)
  }));
  var pt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    mt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return pt(t, e), t.prototype.getStateElement = function () {
        return this.model
      }, Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderElement = function () {
        return s.createElement("div", {
          className: "svc-json-editor-tab__errros_list",
          style: {
            display: this.model.hasErrors ? "block" : "none"
          }
        }, s.createElement(s.List, {
          model: this.model.errorList
        }))
      }, t
    }(s.SurveyElementBase),
    dt = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return pt(t, e), t.prototype.getStateElement = function () {
        return this.model
      }, Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderElement = function () {
        var e = this,
          t = s.createElement(mt, {
            data: this.model
          });
        return s.createElement("div", {
          className: "svc-creator-tab__content"
        }, s.createElement("div", {
          className: "svc-json-editor-tab__content"
        }, s.createElement("textarea", {
          ref: function (t) {
            return n = t, void(e.model.textElement = n);
            var n
          },
          className: "svc-json-editor-tab__content-area",
          value: this.model.text,
          onChange: function (t) {
            return e.model.text = t.target.value
          },
          onKeyDown: function (t) {
            return e.model.checkKey(t, t)
          },
          disabled: this.model.readOnly,
          "aria-label": this.model.ariaLabel
        }), t))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-json-editor-textarea", (function (e) {
    return s.createElement(dt, e)
  }));
  var ft = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    ht = function (e) {
      function t(t) {
        var n = e.call(this, t) || this;
        return n.aceEditorrRef = s.createRef(), n
      }
      return ft(t, e), t.prototype.getStateElement = function () {
        return this.model
      }, Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.componentDidMount = function () {
        this.model.init(ace.edit(this.aceEditorrRef.current))
      }, t.prototype.renderElement = function () {
        var e = s.createElement(mt, {
          data: this.model
        });
        return s.createElement("div", {
          className: "svc-creator-tab__content"
        }, s.createElement("div", {
          className: "svc-json-editor-tab__content"
        }, s.createElement("div", {
          className: "svc-json-editor-tab__ace-editor",
          ref: this.aceEditorrRef
        }), e))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-json-editor-ace", (function (e) {
    return s.createElement(ht, e)
  }));
  var yt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    vt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return yt(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.button
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this,
          t = "svc-logic-tab__content-action svc-btn" + (void 0 === this.model.enabled || this.model.enabled ? "" : " svc-logic-tab__content-action--disabled");
        return (0, s.attachKey2click)(s.createElement("div", {
          role: "button",
          onClick: function (t) {
            t.stopPropagation(), e.model.action()
          },
          className: t,
          title: this.model.title
        }, s.createElement("span", {
          className: "svc-add-new-item-button__text"
        }, this.model.title)))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-logic-add-button", (function (e) {
    return s.createElement(vt, e)
  }));
  var gt = function (e) {
    function t() {
      return null !== e && e.apply(this, arguments) || this
    }
    return yt(t, e), Object.defineProperty(t.prototype, "model", {
      get: function () {
        return this.props.data
      },
      enumerable: !1,
      configurable: !0
    }), t.prototype.getStateElement = function () {
      return this.model
    }, t.prototype.renderElement = function () {
      this.model;
      var e = this.renderViewContent();
      return s.createElement("div", {
        className: "svc-creator-tab__content svc-logic-tab"
      }, e)
    }, t.prototype.renderViewContent = function () {
      var e = "svc-plugin-tab__content svc-logic-tab svc-logic-tab__content " + (this.model.hasItems ? "" : "svc-logic-tab--empty"),
        t = this.model.readOnly ? void 0 : s.createElement(vt, {
          button: this.model.addNewButton
        });
      return s.createElement(s.Fragment, null, s.createElement("div", {
        className: e
      }, s.createElement(s.Survey, {
        model: this.model.itemsSurvey
      }), this.model.hasItems ? null : s.createElement("div", {
        className: "svc-logic-tab__content-empty"
      }, s.createElement(ct, {
        name: "logic",
        placeholderTitleText: this.model.placeholderTitleText,
        placeholderDescriptionText: this.model.placeholderDescriptionText
      })), t))
    }, t
  }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-logic", (function (e) {
    return s.createElement(gt, e)
  }));
  var Et = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    _t = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Et(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this,
          t = this.model.getRootCss();
        return this.model.survey ? this.model.hasFrame ? s.createElement("div", {
          className: t,
          onKeyDown: function (t) {
            return e.model.tryToZoom(t, t)
          },
          onMouseEnter: "desktop" === this.model.device ? null : this.model.activateZoom,
          onMouseLeave: "desktop" === this.model.device ? null : this.model.deactivateZoom
        }, s.createElement("div", {
          className: "svd-simulator-wrapper",
          id: "svd-simulator-wrapper",
          style: {
            width: this.model.simulatorFrame.frameWidth + "px",
            height: this.model.simulatorFrame.frameHeight + "px"
          }
        }, s.createElement("div", {
          className: "svd-simulator",
          style: {
            width: this.model.simulatorFrame.deviceWidth + "px",
            height: this.model.simulatorFrame.deviceHeight + "px",
            transform: "scale(" + this.model.simulatorFrame.scale + ") translate(-50%, -50%)"
          }
        }, s.createElement("div", {
          className: "svd-simulator-content"
        }, s.createElement(s.Survey, {
          model: this.model.survey
        }))))) : s.createElement("div", {
          className: t
        }, s.createElement("div", {
          className: "svd-simulator-content"
        }, s.createElement(s.Survey, {
          model: this.model.survey
        }))) : s.createElement("div", {
          className: t
        })
      }, t
    }(s.SurveyElementBase),
    bt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    wt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return bt(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model.testAgainAction
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        var e = this;
        return (0, s.attachKey2click)(s.createElement("div", {
          role: "button",
          onClick: function (t) {
            t.stopPropagation(), e.model.action()
          },
          className: "svc-preview__test-again svc-btn",
          title: this.model.title
        }, s.createElement("span", {
          className: "svc-preview-test-again__text"
        }, this.model.title)))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-complete-page", (function (e) {
    return s.createElement(wt, e)
  }));
  var Ot = function (e) {
    function t(t) {
      return e.call(this, t) || this
    }
    return bt(t, e), Object.defineProperty(t.prototype, "model", {
      get: function () {
        return this.props.data
      },
      enumerable: !1,
      configurable: !0
    }), t.prototype.getStateElement = function () {
      return this.model
    }, t.prototype.renderPlaceholder = function () {
      return s.createElement("div", {
        className: "svc-test-tab--empty"
      }, s.createElement(ct, {
        name: "preview",
        placeholderTitleText: this.model.placeholderTitleText,
        placeholderDescriptionText: this.model.placeholderDescriptionText
      }))
    }, t.prototype.renderSimulator = function () {
      return s.createElement("div", {
        className: "svc-plugin-tab__content"
      }, s.createElement(_t, {
        model: this.model.simulator
      }), this.model.showResults ? s.createElement(fe, {
        survey: this.model.simulator.survey
      }) : null)
    }, t.prototype.renderElement = function () {
      var e = "svc-creator-tab__content svc-test-tab__content" + (this.model.isPageToolbarVisible ? " svc-creator-tab__content--with-toolbar" : "");
      return s.createElement("div", {
        className: e
      }, this.model.simulator.survey.isEmpty ? this.renderPlaceholder() : this.renderSimulator(), this.getBottomToolbar())
    }, t.prototype.getBottomToolbar = function () {
      return this.model.isPageToolbarVisible ? s.createElement("div", {
        className: "svc-test-tab__content-actions"
      }, s.createElement(s.SurveyActionBar, {
        model: this.model.pages
      })) : null
    }, t
  }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-test", (function (e) {
    return s.createElement(Ot, e)
  }));
  var Ct = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    St = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Ct(t, e), t.prototype.render = function () {
        return s.createElement("div", {
          className: "svc-property-grid-placeholder"
        }, s.createElement("div", {
          className: "svc-property-grid-placeholder__header"
        }, s.createElement("span", {
          className: "svc-property-grid-placeholder__title"
        }, l.editorLocalization.getString("ed.propertyGridPlaceholderTitle")), s.createElement("span", {
          className: "svc-property-grid-placeholder__description"
        }, l.editorLocalization.getString("ed.propertyGridPlaceholderDescription"))), s.createElement("div", {
          className: "svc-property-grid-placeholder__content"
        }, s.createElement("div", {
          className: "svc-property-grid-placeholder__gap"
        }), s.createElement("div", {
          className: "svc-property-grid-placeholder__image"
        })))
      }, t
    }(s.Component);
  s.ReactElementFactory.Instance.registerElement("svc-property-grid-placeholder", (function (e) {
    return s.createElement(St, e)
  }));
  var Nt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Pt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Nt(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderPlaceholder = function () {
        return s.createElement("div", {
          className: "svc-test-tab--empty"
        }, s.createElement(ct, {
          name: "theme",
          placeholderTitleText: this.model.placeholderTitleText,
          placeholderDescriptionText: this.model.placeholderDescriptionText
        }))
      }, t.prototype.renderSimulator = function () {
        return s.createElement("div", {
          className: "svc-plugin-tab__content"
        }, s.createElement(_t, {
          model: this.model.simulator
        }), this.model.showResults ? s.createElement(fe, {
          survey: this.model.simulator.survey
        }) : null)
      }, t.prototype.renderElement = function () {
        var e = "svc-creator-tab__content svc-test-tab__content" + (this.model.isPageToolbarVisible ? " svc-creator-tab__content--with-toolbar" : "");
        return s.createElement("div", {
          className: e
        }, this.model.simulator.survey.isEmpty ? this.renderPlaceholder() : this.renderSimulator(), this.getBottomToolbar())
      }, t.prototype.getBottomToolbar = function () {
        return this.model.isPageToolbarVisible ? s.createElement("div", {
          className: "svc-test-tab__content-actions"
        }, s.createElement(s.SurveyActionBar, {
          model: this.model.pages
        })) : null
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-theme", (function (e) {
    return s.createElement(Pt, e)
  }));
  var xt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    jt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return xt(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.data || this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        return this.model ? s.createElement("div", {
          className: "svc-creator-tab__content svc-translation-tab" + (this.model.isEmpty ? " svc-translation-tab--empty" : "")
        }, this.renderElementContent()) : null
      }, t.prototype.renderElementContent = function () {
        return this.model.isEmpty ? s.createElement(ct, {
          name: "translation",
          placeholderTitleText: this.model.placeholderTitleText,
          placeholderDescriptionText: this.model.placeholderDescriptionText
        }) : s.createElement("div", {
          className: "st-content"
        }, s.createElement("div", {
          className: "svc-flex-column st-strings-wrapper"
        }, s.createElement("div", {
          className: "svc-flex-row st-strings-header"
        }, s.createElement(s.Survey, {
          model: this.model.stringsHeaderSurvey
        })), s.createElement("div", {
          className: "svc-flex-row svc-plugin-tab__content st-strings"
        }, s.createElement(s.Survey, {
          model: this.model.stringsSurvey
        }))))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-tab-translation", (function (e) {
    return s.createElement(jt, e)
  }));
  var Tt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    qt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Tt(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.renderElement = function () {
        return this.model.isVisible ? s.createElement(s.List, {
          model: this.model.list
        }) : null
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-object-selector", (function (e) {
    return s.createElement(qt, e)
  }));
  var It = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Rt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return It(t, e), Object.defineProperty(t.prototype, "model", {
        get: function () {
          return this.props.model
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.model
      }, t.prototype.canRender = function () {
        return !!this.model && e.prototype.canRender.call(this)
      }, t.prototype.renderElement = function () {
        return s.createElement("div", {
          className: this.model.rootCss
        }, s.createElement(xe, {
          model: this.model.searchManager
        }), s.createElement(s.Survey, {
          model: this.model.survey
        }))
      }, t
    }(s.SurveyElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("buttongroup", (function (e) {
    return s.createElement(s.SurveyQuestionButtonGroup, e)
  })), s.ReactElementFactory.Instance.registerElement("svc-property-grid", (function (e) {
    return s.createElement(Rt, e)
  }));
  var Mt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ft = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Mt(t, e), Object.defineProperty(t.prototype, "item", {
        get: function () {
          return this.props.item
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getStateElement = function () {
        return this.item
      }, t.prototype.renderElement = function () {
        var e = this,
          t = this.item.tooltip || this.item.title,
          n = this.item.hasTitle ? s.createElement("span", {
            className: "svc-switcher__title"
          }, this.item.title) : null;
        return (0, s.attachKey2click)(s.createElement("button", {
          className: this.item.getActionBarItemCss(),
          type: "button",
          disabled: this.item.disabled,
          onClick: function (t) {
            return e.item.action(e.item, e.item.getIsTrusted(t))
          },
          title: t,
          "aria-checked": this.item.ariaChecked,
          "aria-expanded": this.item.ariaExpanded,
          role: this.item.ariaRole
        }, s.createElement("div", {
          className: this.item.getSwitcherIconCss()
        }, s.createElement("div", {
          className: "svc-switcher__icon-thumb"
        })), n), this.item, {
          processEsc: !1
        })
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("svc-switcher", (function (e) {
    return s.createElement(Ft, e)
  }));
  var Dt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Bt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Dt(t, e), t.prototype.render = function () {
        var e = this.props.item;
        return s.createElement(s.Fragment, null, s.createElement(s.SvgIcon, {
          iconName: e.iconName,
          iconSize: e.iconSize,
          className: "svc-json-error__icon"
        }), s.createElement("div", {
          className: "svc-json-error__container"
        }, s.createElement("div", {
          className: "svc-json-error__title"
        }, s.createElement("span", {
          key: 2
        }, this.renderLocString(e.locTitle, void 0, "locString"))), this.renderFixButton()))
      }, t.prototype.renderFixButton = function () {
        if (!this.props.item.data.showFixButton) return null;
        var e = this.props.item;
        return (0, s.attachKey2click)(s.createElement("button", {
          type: "button",
          onClick: function (t) {
            t.stopPropagation(), e.data.fixError()
          },
          title: e.data.fixButtonTitle,
          className: "svc-json-error__fix-button"
        }, s.createElement(s.SvgIcon, {
          iconName: e.data.fixButtonIcon,
          size: "auto"
        })))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("json-error-item", (function (e) {
    return s.createElement(Bt, e)
  }));
  var kt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    At = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return kt(t, e), Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.questionBase
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderInput = function () {
        var e = this;
        return s.createElement(s.Fragment, null, s.createElement("input", {
          id: this.question.inputId,
          disabled: this.isDisplayMode,
          className: this.question.cssClasses.control,
          ref: function (t) {
            return e.setControl(t)
          },
          placeholder: this.question.renderedPlaceholder,
          autoComplete: "off",
          onBlur: function (t) {
            return e.question.onBlur(t.nativeEvent)
          },
          onFocus: function (t) {
            return e.question.onFocus(t.nativeEvent)
          },
          onChange: this.question.onChange,
          onBeforeInput: function (t) {
            return e.question.onBeforeInput(t.nativeEvent)
          },
          onKeyUp: function (t) {
            return e.question.onKeyUp(t.nativeEvent)
          },
          onKeyDown: function (t) {
            return e.question.onInputKeyDown(t.nativeEvent)
          },
          "aria-required": this.question.ariaRequired,
          "aria-label": this.question.ariaLabel,
          "aria-invalid": this.question.ariaInvalid,
          "aria-describedby": this.question.ariaDescribedBy
        }))
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement("div", {
          className: this.question.cssClasses.root,
          onKeyDown: function (t) {
            return e.question.onKeyDown(t.nativeEvent)
          }
        }, this.renderInput(), this.renderButtons())
      }, t.prototype.getValueCore = function () {
        return this.question.renderedValue
      }, t.prototype.renderButtons = function () {
        var e = this;
        return s.createElement("span", {
          className: this.question.cssClasses.buttonsContainer
        }, s.createElement("button", {
          tabIndex: -1,
          className: this.question.cssClasses.arrowButton,
          disabled: this.isDisplayMode,
          onMouseDown: this.question.onDownButtonMouseDown,
          onMouseUp: this.question.onButtonMouseUp,
          onMouseLeave: this.question.onButtonMouseLeave,
          onBlur: function (t) {
            return e.question.onBlur(t.nativeEvent)
          },
          onFocus: function (t) {
            return e.question.onFocus(t.nativeEvent)
          }
        }, s.createElement(s.SvgIcon, {
          iconName: this.question.cssClasses.decreaseButtonIcon,
          size: "auto"
        })), s.createElement("button", {
          tabIndex: -1,
          className: this.question.cssClasses.arrowButton,
          disabled: this.isDisplayMode,
          onMouseDown: this.question.onUpButtonMouseDown,
          onMouseUp: this.question.onButtonMouseUp,
          onMouseLeave: this.question.onButtonMouseLeave,
          onBlur: function (t) {
            return e.question.onBlur(t.nativeEvent)
          },
          onFocus: function (t) {
            return e.question.onFocus(t.nativeEvent)
          }
        }, s.createElement(s.SvgIcon, {
          iconName: this.question.cssClasses.increaseButtonIcon,
          size: "auto"
        })))
      }, t
    }(s.SurveyQuestionText);
  s.ReactQuestionFactory.Instance.registerQuestion("spinedit", (function (e) {
    return s.createElement(At, e)
  }));
  var Ut = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Qt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Ut(t, e), t.prototype.render = function () {
        var e = this.props.item;
        return s.createElement(s.Fragment, null, s.createElement("span", {
          className: "spg-color-editor__color-swatch",
          style: {
            backgroundColor: e.value
          }
        }), s.createElement("span", {
          key: 2
        }, this.renderLocString(e.locTitle, void 0, "locString")))
      }, t
    }(s.SurveyElementBase);
  s.ReactElementFactory.Instance.registerElement("color-item", (function (e) {
    return s.createElement(Qt, e)
  }));
  var Lt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Vt = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Lt(t, e), Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.questionBase
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderInput = function () {
        var e = this;
        return s.createElement(s.Fragment, null, s.createElement("input", {
          id: this.question.inputId,
          disabled: this.isDisplayMode,
          className: this.question.cssClasses.control,
          ref: function (t) {
            return e.setControl(t)
          },
          placeholder: this.question.renderedPlaceholder,
          autoComplete: "off",
          onKeyUp: function (t) {
            return e.question.onKeyUp(t.nativeEvent)
          },
          onBlur: function (t) {
            return e.question.onBlur(t.nativeEvent)
          },
          onChange: this.question.onChange,
          onBeforeInput: function (t) {
            return e.question.onBeforeInput(t.nativeEvent)
          },
          "aria-required": this.question.ariaRequired,
          "aria-label": this.question.ariaLabel,
          "aria-invalid": this.question.ariaInvalid,
          "aria-describedby": this.question.ariaDescribedBy
        }))
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement("div", {
          className: this.question.cssClasses.root,
          onKeyDown: function (t) {
            return e.question.onKeyDown(t.nativeEvent)
          }
        }, this.renderColorSwatch(), this.renderInput(), this.question.showDropdownAction ? this.renderDropdownAction() : null)
      }, t.prototype.getValueCore = function () {
        return this.question.renderedValue
      }, t.prototype.renderColorSwatch = function () {
        var e = this;
        return s.createElement("label", {
          className: this.question.getSwatchCss(),
          style: this.question.getSwatchStyle()
        }, s.createElement(s.SvgIcon, {
          iconName: this.question.cssClasses.swatchIcon,
          size: "auto"
        }), s.createElement("input", {
          type: "color",
          disabled: this.isDisplayMode,
          value: this.question.renderedColorValue,
          className: this.question.cssClasses.colorInput,
          onChange: function (t) {
            return e.question.onColorInputChange(t.nativeEvent)
          },
          tabIndex: -1
        }))
      }, t.prototype.renderDropdownAction = function () {
        return s.createElement(s.Fragment, null, s.ReactElementFactory.Instance.createElement("sv-action-bar-item", {
          item: this.question.dropdownAction
        }), this.renderPopup())
      }, t.prototype.renderPopup = function () {
        return s.createElement(s.Popup, {
          model: this.question.dropdownAction.popupModel
        })
      }, t
    }(s.SurveyQuestionText);
  s.ReactQuestionFactory.Instance.registerQuestion("color", (function (e) {
    return s.createElement(Vt, e)
  }));
  var Kt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Ht = function (e) {
      function t(t) {
        return e.call(this, t) || this
      }
      return Kt(t, e), Object.defineProperty(t.prototype, "questionFile", {
        get: function () {
          return this.questionBase
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.getValueCore = function () {
        return this.question.renderedValue
      }, t.prototype.renderInput = function () {
        var e = this;
        return s.createElement(s.Fragment, null, s.createElement("input", {
          disabled: this.isDisplayMode,
          className: this.questionFile.cssClasses.control,
          placeholder: this.questionFile.renderedPlaceholder,
          ref: function (t) {
            return e.setControl(t)
          },
          autoComplete: "off",
          type: "text",
          onBlur: function (t) {
            return e.questionFile.onInputBlur(t.nativeEvent)
          },
          onChange: function (t) {
            return e.questionFile.onInputChange(t.nativeEvent)
          }
        }))
      }, t.prototype.renderFileInput = function () {
        var e = this;
        return s.createElement("input", {
          type: "file",
          disabled: this.isDisplayMode,
          className: this.questionFile.cssClasses.fileInput,
          id: this.questionFile.inputId,
          "aria-required": this.questionFile.ariaRequired,
          "aria-label": this.questionFile.ariaLabel,
          "aria-invalid": this.questionFile.ariaInvalid,
          "aria-describedby": this.questionFile.ariaDescribedBy,
          multiple: !1,
          title: this.questionFile.inputTitle,
          accept: this.questionFile.acceptedTypes,
          tabIndex: -1,
          onChange: function (t) {
            return e.questionFile.onFileInputChange(t.nativeEvent)
          }
        })
      }, t.prototype.renderButtons = function () {
        return s.createElement("div", {
          className: this.questionFile.cssClasses.buttonsContainer
        }, this.renderClearButton(), this.renderChooseButton())
      }, t.prototype.renderClearButton = function () {
        return (0, s.attachKey2click)(s.createElement("button", {
          type: "button",
          className: this.questionFile.cssClasses.clearButton,
          disabled: this.questionFile.getIsClearButtonDisabled(),
          onClick: this.questionFile.doClean
        }, s.createElement(s.SvgIcon, {
          iconName: this.questionFile.cssClasses.clearButtonIcon,
          size: "auto",
          title: this.questionFile.clearButtonCaption
        })))
      }, t.prototype.renderChooseButton = function () {
        var e = this;
        return (0, s.attachKey2click)(s.createElement("label", {
          role: "button",
          onClick: function (t) {
            return e.questionFile.chooseFiles(t.nativeEvent)
          },
          className: this.questionFile.getChooseButtonCss(),
          htmlFor: this.questionFile.inputId,
          "aria-label": this.questionFile.chooseButtonCaption
        }, s.createElement(s.SvgIcon, {
          iconName: this.questionFile.cssClasses.chooseButtonIcon,
          size: "auto",
          title: this.questionFile.chooseButtonCaption
        })))
      }, t.prototype.renderElement = function () {
        var e = this;
        return s.createElement("div", {
          className: this.questionFile.cssClasses.root,
          ref: function (t) {
            return e.setContent(t)
          },
          onDragEnter: this.questionFile.onDragEnter,
          onDragOver: this.questionFile.onDragOver,
          onDrop: this.questionFile.onDrop,
          onDragLeave: this.questionFile.onDragLeave,
          onKeyDown: function (t) {
            return e.question.onKeyDown(t.nativeEvent)
          }
        }, this.renderInput(), this.renderFileInput(), this.renderButtons())
      }, t
    }(s.SurveyQuestionText);
  s.ReactQuestionFactory.Instance.registerQuestion("fileedit", (function (e) {
    return s.createElement(Ht, e)
  }));
  var zt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Wt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return zt(t, e), Object.defineProperty(t.prototype, "question", {
        get: function () {
          return this.questionBase
        },
        enumerable: !1,
        configurable: !0
      }), t.prototype.renderElement = function () {
        var e = this.renderInput(),
          t = this.renderResetButton();
        return s.createElement("div", {
          className: this.question.getRootClass()
        }, e, t)
      }, t.prototype.renderInput = function () {
        return s.ReactQuestionFactory.Instance.createQuestion(this.question.wrappedQuestionTemplate, {
          question: this.question,
          isDisplayMode: this.question.isInputReadOnly,
          creator: this
        })
      }, t.prototype.renderResetButton = function () {
        var e = this;
        return s.createElement("button", {
          className: this.question.cssClasses.resetButton,
          disabled: this.question.resetValueAdorner.isDisabled,
          title: this.question.resetValueAdorner.caption,
          onClick: function () {
            return e.question.resetValueAdorner.resetValue()
          }
        }, s.createElement(s.SvgIcon, {
          iconName: this.question.cssClasses.resetButtonIcon,
          size: "auto"
        }))
      }, t
    }(s.SurveyQuestionElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("textwithreset", (function (e) {
    return s.createElement(Wt, e)
  })), s.ReactQuestionFactory.Instance.registerQuestion("commentwithreset", (function (e) {
    return s.createElement(Wt, e)
  }));
  var Gt, Jt = function () {
      var e = function (t, n) {
        return e = Object.setPrototypeOf || {
          __proto__: []
        }
        instanceof Array && function (e, t) {
          e.__proto__ = t
        } || function (e, t) {
          for (var n in t) Object.prototype.hasOwnProperty.call(t, n) && (e[n] = t[n])
        }, e(t, n)
      };
      return function (t, n) {
        if ("function" != typeof n && null !== n) throw new TypeError("Class extends value " + String(n) + " is not a constructor or null");

        function o() {
          this.constructor = t
        }
        e(t, n), t.prototype = null === n ? Object.create(n) : (o.prototype = n.prototype, new o)
      }
    }(),
    Xt = function (e) {
      function t() {
        return null !== e && e.apply(this, arguments) || this
      }
      return Jt(t, e), t.prototype.renderElement = function () {
        var e = this,
          t = (0, s.attachKey2click)(s.createElement("div", {
            className: "spg-boolean-switch__button" + (this.questionBase.value ? " spg-boolean-switch__button--checked" : ""),
            tabIndex: 0,
            role: "checkbox",
            "aria-required": this.questionBase.ariaRequired,
            "aria-label": this.questionBase.ariaLabel,
            "aria-invalid": this.questionBase.ariaInvalid,
            "aria-errormessage": this.questionBase.ariaErrormessage
          }, s.createElement("div", {
            className: "spg-boolean-switch__thumb"
          }, s.createElement("div", {
            className: "spg-boolean-switch__thumb-circle spg-boolean-switch__thumb--left"
          })), s.createElement("div", {
            className: "spg-boolean-switch__thumb"
          }, s.createElement("div", {
            className: "spg-boolean-switch__thumb-circle spg-boolean-switch__thumb--right"
          }))), this.questionBase, {
            processEsc: !1
          });
        return s.createElement("div", {
          className: "spg-boolean-switch",
          onClick: function () {
            return e.questionBase.value = !e.questionBase.value
          }
        }, t, s.createElement("div", {
          className: "spg-boolean-switch__caption"
        }, s.createElement("div", {
          className: "spg-boolean-switch__title"
        }, s.SurveyElementBase.renderLocString(this.questionBase.locTitle))))
      }, t
    }(s.SurveyQuestionElementBase);
  s.ReactQuestionFactory.Instance.registerQuestion("sv-boolean-switch", (function (e) {
    return s.createElement(Xt, e)
  })), u.RendererFactory.Instance.registerRenderer("boolean", "switch", "sv-boolean-switch"), Gt = "".concat("1.12.8"), (0, u.checkLibraryVersion)("".concat("1.12.8"), "survey-creator-react");
  var Yt = function () {
      return Yt = Object.assign || function (e) {
        for (var t, n = 1, o = arguments.length; n < o; n++)
          for (var r in t = arguments[n]) Object.prototype.hasOwnProperty.call(t, r) && (e[r] = t[r]);
        return e
      }, Yt.apply(this, arguments)
    },
    Zt = window.jQuery || window.$;

  function $t(e, t, n) {
    void 0 === n && (n = {});
    var o = s.preact.createElement(y, Yt({
      creator: e
    }, n));
    s.preact.render(o, t)
  }
  return Zt && Zt.fn.extend({
    SurveyCreator: function (e) {
      return this.each((function () {
        $t(e.model, this, e)
      }))
    }
  }), a
})()));