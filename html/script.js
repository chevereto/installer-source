var editions = {
  paid: "Chevereto"
};
var onLeaveMessage =
  "The installation is not yet completed. Are you sure that you want to leave?";
var UpgradeToPaid = getParameterByName("UpgradeToPaid") == "";
if (!UpgradeToPaid) {
  editions.free = "Chevereto Free";
}

var title = document.title;
var html = document.documentElement;
var page = html.getAttribute("id");
var body = document.getElementsByTagName("body")[0];
var wrapper = document.querySelector(".wrapper");
var key = document.getElementById("key");
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
    var state = {
      action: "show",
      arg: UpgradeToPaid ? "upgrade" : "welcome"
    };
    installer.actions[state.action](state.arg);
    history.replaceState(state, title);
    if (page != "error") {
      this.bindActions();
    }
    window.onbeforeunload = function(e) {
      if (!body.classList.contains("body--working")) {
        return;
      }
      e.returnValue = onLeaveMessage;
      return onLeaveMessage;
    };
    window.onpopstate = function(e) {
      if (body.classList.contains("body--working")) {
        if (confirm(onLeaveMessage)) {
          installer.XHR.abort();
        } else {
          return;
        }
      }
      var state = e.state;
      var form = document.querySelector(".screen--show form");
      if (form && !form.checkValidity()) {
        var tmpSubmit = document.createElement("button");
        form.appendChild(tmpSubmit);
        tmpSubmit.click();
        form.removeChild(tmpSubmit);
      } else {
        self.actions[state.action](state.arg);
      }
    };
    var forms = document.querySelectorAll("form");
    for (let i = 0; i < forms.length; i++) {
      forms[i].addEventListener(
        "submit",
        function(e) {
          console.log("form ql " + forms[i].dataset.trigger);
          e.preventDefault();
          e.stopPropagation();
          installer.actions[forms[i].dataset.trigger](forms[i].dataset.arg);
          installer.history(forms[i].dataset.trigger, forms[i].dataset.arg);
        },
        false
      );
    }
  },
  bindActions: function() {
    var self = this;
    var triggers = document.querySelectorAll("[data-action]");
    for (let i = 0; i < triggers.length; i++) {
      var trigger = triggers[i];
      trigger.addEventListener("click", function(e) {
        var dataset = e.currentTarget.dataset;
        self.actions[dataset.action](dataset.arg);
        installer.history(dataset.action, dataset.arg);
      });
    }
  },
  history: function(action, arg) {
    history.pushState({ action: action, arg: arg }, action);
  },
  checkLicense: function(key) {
    console.log("CHECK THE LICENSE! " + key);
  },
  actions: {
    show: function(screen, args) {
      document.title = screens[screen].title;
      var shownScreens = document.querySelectorAll(".screen--show");
      shownScreens.forEach(a => {
        a.classList.remove("screen--show");
      });
      document.querySelector("#screen-" + screen).classList.add("screen--show");
    },
    setEdition: function(edition) {
      body.classList.remove("sel--chevereto", "sel--chevereto-free");
      if ("chevereto" == edition) {
        installer.checkLicense("KEY DE PRUEBA");
      }
      body.classList.add("sel--" + edition);
      this.show("cpanel");
    },
    setUpgrade: function() {
      body.classList.remove("sel--chevereto-free");
      body.classList.add("sel--chevereto");
      installer.checkLicense("KEY DE PRUEBA");
      this.show("complete-upgrade");
    },
    cpanelProcess: function() {
      console.log("CHECK LOGIN AND DO CPANEL PROCESS!");
      this.show("database");
    },
    setDatabase: function() {
      console.log("CHECK AND SET DATABASE!");
      this.show("admin");
    },
    setAdmin: function() {
      console.log("SET ADMIN DETAILS!");
      this.show("emails");
    },
    setEmails: function() {
      console.log("SET EMAILS!");
      this.show("ready");
    },
    install: function(arg, push) {
      console.log("I N S T A L L!");
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

      installer.history(push, "installing", arg);
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
installer.init();
