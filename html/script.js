var onLeaveMessage =
  "The installation is not yet completed. Are you sure that you want to leave?";

// var title = document.title;
var html = document.documentElement;
var page = html.getAttribute("id");
var body = document.getElementsByTagName("body")[0];
var wrapper = document.querySelector(".wrapper");
var screenEls = document.querySelectorAll(".screen");
var screens = {};
for (let i = 0; i < screenEls.length; i++) {
  let el = screenEls[i];
  screens[el.id.replace("screen-", "")] = {
    title: el.querySelector("h1").innerText
  };
}

/**
 * This function is case insensitive since Chrome (and maybe others) change the qs case on manual input.
 * @param {string} name The parameter name in the query string.
 * @return {boolean} True if the name is present in the query string.
 */
function locationHasParameter(name) {
  var queryString = window.location.search.substring(1);
  if (queryString) {
    var paramArray = queryString.split("&");
    for (let paramPair of paramArray) {
      var param = paramPair.split("=")[0];
      console.log(
        param,
        "^" + param + "$",
        new RegExp("^" + param + "$", "i").test(name)
      );
      if (new RegExp("^" + param + "$", "i").test(name)) {
        return true;
      }
    }
  }
  return false;
}

var installer = {
  uid: false,
  data: {},
  isUpgradeToPaid: locationHasParameter("UpgradeToPaid"),
  process: "install",
  defaultScreen: "ready",
  init: function() {
    if (this.isUpgradeToPaid) {
      this.process = "upgrade";
      this.defaultScreen = "upgrade";
    }
    var self = this;
    this.popScreen(this.defaultScreen);
    this.history.replace(this.defaultScreen);
    if (page != "error") {
      this.bindActions();
    }
    document.addEventListener(
      "click",
      function(event) {
        if (!event.target.matches(".alert-close")) return;
        event.preventDefault();
        installer.popAlert();
      },
      false
    );
    window.onbeforeunload = function(e) {
      if (!body.classList.contains("body--working")) {
        return;
      }
      e.returnValue = onLeaveMessage;
      return onLeaveMessage;
    };
    window.onpopstate = function(e) {
      var isBack = installer.uid > e.state.uid;
      var isForward = !isBack;
      installer.uid = e.state.uid;
      console.log("onpopstate", {
        direction: isBack ? "back" : "forward",
        uid: installer.uid,
        pushState: e.state
      });
      if (body.classList.contains("body--working")) {
        if (confirm(onLeaveMessage)) {
          installer.XHR.abort();
        } else {
          return;
        }
      }
      var state = e.state;
      var form = installer.getShownScreenEl("form");
      if (isForward && form) {
        if (form.checkValidity()) {
          console.log("Forward form re-submit action:", form.dataset.trigger);
          installer.actions[form.dataset.trigger](form.dataset.arg);
          return;
        } else {
          history.go(-1);
          var tmpSubmit = document.createElement("button");
          form.appendChild(tmpSubmit);
          tmpSubmit.click();
          form.removeChild(tmpSubmit);
          return;
        }
      }
      self.popScreen(state.view);
    };
    var forms = document.querySelectorAll("form");
    for (let i = 0; i < forms.length; i++) {
      forms[i].addEventListener(
        "submit",
        function(e) {
          console.log("Form submit event: " + forms[i].dataset.trigger);
          e.preventDefault();
          e.stopPropagation();
          installer.actions[forms[i].dataset.trigger](forms[i].dataset.arg);
        },
        false
      );
    }
  },
  getCurrentScreen: function() {
    return this.getShownScreenEl("").id.replace("screen-", "");
  },
  getShownScreenEl: function(query) {
    return document.querySelector(".screen--show " + query);
  },
  shakeEl: function(el) {
    el.classList.remove("shake");
    setTimeout(function() {
      el.classList.add("shake");
    }, 1);
    setTimeout(function() {
      el.classList.remove("shake");
    }, 500);
  },
  pushAlert: function(message) {
    console.error(message);
    var pushiInnerHTML =
      "<span>" + message + '</span><a class="alert-close"></a>';
    var el = this.getShownScreenEl(".alert");
    var html = el.innerHTML;
    if (pushiInnerHTML == html) {
      this.shakeEl(el);
    } else {
      el.innerHTML = pushiInnerHTML;
    }
  },
  popAlert: function() {
    var el = this.getShownScreenEl(".alert");
    if (el) {
      el.innerHTML = "";
    }
  },
  getFormData: function() {
    var form = installer.getShownScreenEl("form");
    if (!form) {
      return;
    }
    console.log("GET active form data", form);
    var screen = this.getCurrentScreen();
    var inputEls = form.getElementsByTagName("input");
    var data = {};
    for (let inputEl of inputEls) {
      var id = inputEl.id.replace(screen, "");
      var key = id.charAt(0).toLowerCase() + id.slice(1);
      data[key] = inputEl.value;
    }
    return data;
  },
  writeFormData: function(screen, data) {
    console.log("Data write:" + screen, installer.data[screen]);
    installer.data[screen] = this.getFormData();
  },
  bindActions: function() {
    var self = this;
    var triggers = document.querySelectorAll("[data-action]");
    for (let i = 0; i < triggers.length; i++) {
      var trigger = triggers[i];
      trigger.addEventListener("click", function(e) {
        var dataset = e.currentTarget.dataset;
        self.actions[dataset.action](dataset.arg);
      });
    }
  },
  history: {
    push: function(view) {
      this.writter("push", { view: view });
    },
    replace: function(view) {
      this.writter("replace", { view: view });
    },
    writter: function(fn, data) {
      data.uid = new Date().getTime();
      installer.uid = data.uid;
      switch (fn) {
        case "push":
          history.pushState(data, data.view);
          break;
        case "replace":
          history.replaceState(data, data.view);
          break;
      }
      console.log("history.writter:", fn, data);
    }
  },
  /**
   *
   * @param {string} action
   * @param {object} params
   * @param {object} callback {always: fn(response, json), success: fn(response,json), error: fn(response,json),}
   */
  fetch: function(action, params, callback) {
    var self = this;
    var data = new FormData();
    data.append("action", action);
    for (var key in params) {
      data.append(key, params[key]);
    }
    var disableEls = document.querySelectorAll("button, input");
    for (let disableEl of disableEls) {
      disableEl.disabled = true;
    }
    var box = this.getShownScreenEl(".flex-box");
    var loader = this.getShownScreenEl(".loader");
    if (!loader) {
      var loader = document.createElement("div");
      loader.classList.add("loader", "animate");
      box.insertBefore(loader, box.firstChild);
    }
    setTimeout(function() {
      loader.classList.add("loader--show");
    }, 1);
    return fetch(vars.installerFile, {
      method: "POST",
      body: data
    })
      .then(response => Promise.all([response, response.json()]))
      .catch(error => {
        self.pushAlert(error);
      })
      .then(([response, json]) => {
        loader.classList.remove("loader--show");
        for (let disableEl of disableEls) {
          disableEl.disabled = false;
        }
        if (callback && callback.hasOwnProperty("always")) {
          callback.always(response, json);
        }
        if (response.ok) {
          installer.popAlert();
          callback.success(response, json);
        } else {
          self.pushAlert(json.response.message);
          callback.error(response, json);
        }
      });
  },
  popScreen: function(screen) {
    console.log("popScreen:" + screen);
    document.title = screens[screen].title;
    var shownScreens = document.querySelectorAll(".screen--show");
    shownScreens.forEach(a => {
      a.classList.remove("screen--show");
    });
    document.querySelector("#screen-" + screen).classList.add("screen--show");
  },
  checkLicense: function(key, callback) {
    return this.fetch("licenseCheck", { license: key }, callback);
  },
  checkDatabase: function(params, callback) {
    return this.fetch("dabataseCheck", params, callback);
  },
  download: function(params, callback) {
    return this.fetch("download", params, callback);
  },
  extract: function(filePath, callback) {
    return this.fetch("extract", { filePath: filePath }, callback);
  },
  actions: {
    show: function(screen) {
      installer.popScreen(screen);
      if (history.state.view != screen) {
        installer.history.push(screen);
      }
    },
    setLicense: function(elId) {
      var licenseEl = document.getElementById(elId);
      var license = licenseEl.value;
      if (!license) {
        licenseEl.focus();
        installer.shakeEl(licenseEl);
        return;
      }
      installer.checkLicense(license, {
        success: function() {
          installer.data.license = license;
          installer.actions.setEdition("chevereto");
        },
        error: function() {
          installer.data.license = null;
        }
      });
    },
    setEdition: function(edition) {
      console.log("setEdition");
      body.classList.remove("sel--chevereto", "sel--chevereto-free");
      body.classList.add("sel--" + edition);
      installer.data.software = edition;
      this.show("cpanel");
    },
    setUpgrade: function() {
      // console.log("setUpgrade");
      // body.classList.remove("sel--chevereto-free");
      // body.classList.add("sel--chevereto");
      // installer.data.license = document.getElementById("upgradeKey").value;
      // installer.checkLicense(installer.data.license);
      // this.show("complete-upgrade");
    },
    cpanelProcess: function() {
      var els = {
        user: document.getElementById("cpanelUser"),
        password: document.getElementById("cpanelPassword")
      };
      for (let key in els) {
        let el = els[key];
        if (!el.value) {
          el.focus();
          installer.shakeEl(el);
          return;
        }
      }
      console.log("cpanelProcess");
      this.show("db");
    },
    setDb: function() {
      console.log("setDb");
      var params = installer.getFormData();
      installer.checkDatabase(params, {
        success: function(response, json) {
          installer.writeFormData("db", params);
          installer.actions.show("admin");
        },
        error: function(response, json) {
          console.error("error", response, json);
        }
      });
    },
    setAdmin: function() {
      console.log("setAdmin");
      installer.writeFormData("admin");
      this.show("emails");
    },
    setEmails: function() {
      console.log("setEmails");
      installer.writeFormData("email");
      this.show("ready");
    },
    setReady: function() {
      this.show("ready");
    },
    install: function() {
      installer.setBodyInstalling(true);
      installer.data.software = "chevereto-free";
      console.log("install");
      this.show("installing");

      installer.log(vars.serverStr);

      installer.log(
        "Trying to download latest `" + installer.data.software + "` release"
      );

      var callback = {
        always: function(response, json) {
          installer.log(json.response.message);
        },
        error: function() {
          installer.abortInstall();
        }
      };

      // Yo dawg! Again I put a promise on top of a promise so you can promise while you promise!
      installer.download(
        {
          software: installer.data.software,
          license: installer.data.license
        },
        {
          always: callback.always,
          error: callback.error,
          success: function(response, json) {
            installer.log("Trying to extract `" + json.data.fileBasename + "`");
            installer.extract(
              { filePath: json.data.filePath },
              {
                always: callback.always,
                error: callback.error,
                success: function(response, json) {
                  console.log("EXTRACTION OJ SIMPSON");
                }
              }
            );
          }
        }
      );

      // installer.request("download", downloadParams).then(
      //   function(response) {
      //     installer
      //       .request("extract", {
      //         fileBasename: response.data.download.fileBasename
      //       })
      //       .then(
      //         function(response) {
      //           var s = 3;
      //           var to = isUpgradeToPaid ? "install" : "setup";
      //           installer.log("Redirecting to " + to + " form in " + s + "s");
      //           setTimeout(function() {
      //             installer.log("Redirecting now!");
      //             installer.setBodyInstalling(false);
      //             var redirectUrl = rootUrl;
      //             if (isUpgradeToPaid) {
      //               redirectUrl += "install";
      //             }
      //             window.location.replace(redirectUrl);
      //           }, 1000 * s);
      //         },
      //       );
      //   },
      // );
    }
  },
  /**
   * @param {string} message
   */
  abortInstall: function(message) {
    this.log(message ? message : "Installation aborted");
    this.setBodyInstalling(false);
  },
  setBodyInstalling: function(bool) {
    body.classList[bool ? "add" : "remove"]("body--installing");
  },
  log: function(message) {
    var date = new Date();
    var t = {
      h: date.getHours(),
      m: date.getMinutes(),
      s: date.getSeconds()
    };
    for (var k in t) {
      if (t[k] < 10) {
        t[k] = "0" + t[k];
      }
    }
    var time = t.h + ":" + t.m + ":" + t.s;
    var el = document.querySelector(".install-log");
    var p = document.createElement("p");
    var t = document.createTextNode(time + " " + message);
    p.appendChild(t);
    el.appendChild(p);
    el.scrollTop = el.scrollHeight;
  }
};
if ("error" != document.querySelector("html").id) {
  installer.init();
}
