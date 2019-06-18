var Data = {};
var uid;
var onLeaveMessage =
  "The installation is not yet completed. Are you sure that you want to leave?";
var UpgradeToPaid = getParameterByName("UpgradeToPaid") == "";
Data.process = UpgradeToPaid ? "upgrade" : "install";

var title = document.title;
var html = document.documentElement;
var page = html.getAttribute("id");
var body = document.getElementsByTagName("body")[0];
var wrapper = document.querySelector(".wrapper");
var screenEls = document.querySelectorAll(".screen");
var screens = {};
for (let i = 0; i < screenEls.length; i++) {
  var el = screenEls[i];
  screens[el.id.replace("screen-", "")] = {
    title: el.querySelector("h1").innerText
  };
}

console.log(screens);

var installer = {
  init: function() {
    var self = this;
    var defaultScreen = UpgradeToPaid ? "upgrade" : "welcome";
    this.popScreen(defaultScreen);
    this.history.replace(defaultScreen);
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
      var isBack = uid > e.state.uid;
      var isForward = !isBack;
      uid = e.state.uid;
      console.log("onpopstate", {
        direction: isBack ? "back" : "forward",
        uid: uid,
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
      if (form && isForward) {
        if (!form.checkValidity()) {
          history.go(-1);
          var tmpSubmit = document.createElement("button");
          form.appendChild(tmpSubmit);
          tmpSubmit.click();
          form.removeChild(tmpSubmit);
          return;
        } else {
          console.log("Form re-submit action:", form.dataset.trigger);
          installer.actions[form.dataset.trigger](form.dataset.arg);
        }
      }
      if (isBack) {
        self.popScreen(state.view);
      }
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
    this.getShownScreenEl(".alert").innerHTML = "";
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
    console.log("Data write:" + screen, Data[screen]);
    Data[screen] = this.getFormData();
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
      uid = data.uid;
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
        if (response.ok) {
          installer.popAlert();
          callback.success(response, json);
        } else {
          self.pushAlert(json.response.message);
          callback.error(response, json);
        }
      });
  },
  checkLicense: function(key, callback) {
    return this.fetch("licenseCheck", { license: key }, callback);
  },
  checkDatabase: function(params, callback) {
    return this.fetch("dabataseCheck", params, callback);
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
  // Actions wired into HTML
  actions: {
    show: function(screen) {
      installer.popScreen(screen);
      if (history.state.view != screen) {
        installer.history.push(screen);
      }
    },
    setLicense: function(id) {
      var keyEl = document.getElementById(id);
      var key = keyEl.value;
      if (!key) {
        keyEl.focus();
        installer.shakeEl(keyEl);
        return;
      }
      installer.checkLicense(key, {
        success: function() {
          Data.key = key;
          installer.actions.setEdition("chevereto");
        },
        error: function() {
          Data.key = null;
        }
      });
    },
    setEdition: function(edition) {
      console.log("setEdition");
      body.classList.remove("sel--chevereto", "sel--chevereto-free");
      body.classList.add("sel--" + edition);
      Data.software = edition;
      this.show("cpanel");
    },
    setUpgrade: function() {
      // console.log("setUpgrade");
      // body.classList.remove("sel--chevereto-free");
      // body.classList.add("sel--chevereto");
      // Data.key = document.getElementById("upgradeKey").value;
      // installer.checkLicense(Data.key);
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
      console.log("install");
      this.show("installing");
      return;
      if (!arg) {
        return;
      }
      if (arg == "paid" && !installer.getKeyValue()) {
        key.focus();
        return;
      }
      var edition = installer.getActiveEdition();

      body.classList = "";
      body.classList.add("body--installing", "body--" + edition.id);

      installer.setWorking(true);

      if (typeof installer.XHR == typeof undefined) {
        installer.log(serverStr);
      }

      // Yo dawg! I put a promise on top of a promise so you can promise while you promise
      installer.log("Trying to download latest " + edition.label + " release");
      var downloadParams = { edition: arg };
      if (edition.id == "paid") {
        downloadParams.license = installer.getKeyValue();
      }
      installer.request("download", downloadParams).then(
        function(response) {
          if (response.status.code != 200) {
            installer.setWorking(false);
            document.title = "Download failed";
            installer.log(
              document.title +
                ": " +
                response.response.message +
                " - Installation aborted"
            );
            return;
          }
          installer.log(response.response.message);
          installer.log(
            "Trying to extract " + response.data.download.fileBasename
          );
          installer
            .request("extract", {
              fileBasename: response.data.download.fileBasename
            })
            .then(
              function(response) {
                if (response.status.code != 200) {
                  installer.setWorking(false);
                  document.title = "Extraction failed";
                  installer.log(
                    document.title +
                      ": " +
                      response.response.message +
                      " - Installation aborted"
                  );
                  return;
                }
                installer.log(response.response.message);
                var s = 3;
                var to = UpgradeToPaid ? "install" : "setup";
                installer.log("Redirecting to " + to + " form in " + s + "s");
                setTimeout(function() {
                  installer.log("Redirecting now!");
                  installer.setWorking(false);
                  var redirectUrl = rootUrl;
                  if (UpgradeToPaid) {
                    redirectUrl += "install";
                  }
                  window.location.replace(redirectUrl);
                }, 1000 * s);
              },
              function(error) {
                installer.setWorking(false);
                installer.log(error);
              }
            );
        },
        function(error) {
          installer.setWorking(false);
          installer.log(error);
        }
      );
    }
  },
  setWorking: function(bool) {
    body.classList[bool ? "add" : "remove"]("body--working");
  },
  getKeyValue: function() {
    return key.value.replace(/\s/g, "");
  },
  getOppositeEdition(edition) {
    return edition.toLowerCase() == "free" ? "paid" : "free";
  },
  getActiveEdition: function() {
    var ret;
    var edition;
    for (var k in editions) {
      ret = body.classList.contains("body--" + k);
      if (ret) {
        return {
          id: k,
          label: editions[k]
        };
      }
    }
    return;
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
    var el = document.querySelector(
      "." + installer.getActiveEdition().id + "--show .install-log"
    );
    var p = document.createElement("p");
    var t = document.createTextNode(time + " " + message);
    p.appendChild(t);
    el.appendChild(p);
    el.scrollTop = el.scrollHeight;
  },
  request: function(action, args) {
    return new Promise(function(resolve, reject) {
      var edition = installer.getActiveEdition();
      var postData = new FormData();
      args.action = action;
      args.edition = edition.id;
      for (var i in args) {
        postData.append(i, args[i]);
      }
      installer.XHR = new XMLHttpRequest();
      installer.XHR.responseType = "json";
      installer.XHR.open("POST", installerFile, true);
      installer.XHR.addEventListener("load", function(e) {
        resolve(e.currentTarget.response);
      });
      installer.XHR.addEventListener("abort", function(e) {
        reject("Process aborted by user");
      });
      installer.XHR.addEventListener("error", function(e) {
        reject("Transfer failed");
      });
      installer.XHR.send(postData);
    });
  }
};
if ("error" != document.querySelector("html").id) {
  installer.init();
}
