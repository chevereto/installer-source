var editions = {
  paid: "Chevereto"
};
var onLeaveMessage =
  "The installation is not yet completed. Are you sure that you want to leave?";
var UpgradeToPaid = getParameterByName("UpgradeToPaid") == "";
if (!UpgradeToPaid) {
  editions.free = "Chevereto Free";
}
var screens = {
  splash: {
    callee: "splash",
    title: "Chevereto Installer"
  },
  install: {
    callee: "choose",
    title: "Install %s"
  },
  installing: {
    callee: "install",
    title: "Installing %s"
  }
};

var title = document.title;
var html = document.documentElement;
var page = html.getAttribute("id");
var body = document.getElementsByTagName("body")[0];
var wrapper = document.querySelector(".wrapper");
var key = document.getElementById("key");

var installer = {
  init: function() {
    var self = this;
    var state = {
      screen: UpgradeToPaid ? "install" : "splash"
    };
    if (UpgradeToPaid) {
      state.arg = "paid";
    }
    history.replaceState(state, title);
    if (page != "error") {
      this.preload();
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
      var screen = state.screen;
      var arg = state.arg;
      var screen2Action = {
        splash: "splash",
        install: "choose",
        installing: "install"
      };
      var action = screens[screen].callee;
      self.actions[action](arg, false);
    };
  },
  bindActions: function() {
    var self = this;
    var triggers = document.querySelectorAll("[data-action]");
    for (var i = 0; i < triggers.length; i++) {
      var trigger = triggers[i];
      trigger.addEventListener("click", function(e) {
        var el = e.currentTarget;
        var action = el.dataset.action;
        var arg = el.dataset.arg;
        self.actions[action](arg);
      });
    }
  },
  history: function(push, screen, edition) {
    if (typeof push == typeof undefined) {
      var push = true;
    }
    document.title = screens[screen].title.replace(/%s/g, editions[edition]);
    if (push) {
      history.pushState({ screen: screen, arg: edition }, screen);
    }
  },
  actions: {
    splash: function(arg, push) {
      body.classList = "";
      body.classList.add("body--splash");
      installer.history(push, "splash");
    },
    choose: function(arg, push) {
      if (!arg) {
        return;
      }
      body.classList = "";
      body.classList.add("body--install", "body--" + arg);
      installer.history(push, "install", arg);
    },
    install: function(arg, push) {
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
  preload: function() {
    var self = this;
    // var preloader = document.createElement('div');
    // preloader.setAttribute('id', 'preloader');
    // preloader.setAttribute('class', 'animate');
    // var spinner = document.createElement('div');
    // spinner.setAttribute('class', 'spinner');
    // preloader.insertBefore(spinner, preloader.firstChild);
    // wrapper.insertBefore(preloader, wrapper.firstChild);
    body.classList.add("body--splash");
    // var loader = document.createElement("div");
    // loader.classList.add("loader", "animate");
    // var boxInstall = document.querySelector(
    //   ".container--installing .box--install"
    // );
    // boxInstall.insertBefore(loader, boxInstall.firstChild);
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
