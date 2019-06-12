<?php if ($pageId == 'error') { ?>
    <div id="screen-error" class="screen">
      <div class="flex-box error-box" >
        <div>
          <h1>Aw, Snap!</h1>
          <p>Your web server lacks some requirements that must be fixed to install Chevereto.</p>
          <p>Please check:</p>
          <ul>
<?php
                  foreach ($requirementsCheck->missing as $k => $v) {
                      ?>
              <li><?php echo $v['message']; ?></li>
<?php
                  } ?>
          </ul>
          <p>If you already fixed your web server then make sure to restart it to apply changes. If the problem persists, contact your server administrator.</p>
          <p>Check our <a href="https://chevereto.com/hosting" target="_blank">hosting</a> offer if you don't want to worry about this.</p>
          <p class="error-box-code">Server <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        </div>
      </div>
    </div>
<?php } else { ?>
    <div id="screen-welcome" class="screen screen--show animate animate--slow">
      <div class="header flex-item"><?php echo $svgLogo; ?></div>
      <div class="flex-box flex-item">
        <div>
          <h1>Chevereto Installer <a class="installer-version radius" href="<?php echo APP_URL; ?>" target="_blank">v<?php echo APP_VERSION; ?></a></h1>
          <p>This tool will guide you through the process of installing <a href="https://chevereto.com/" target="_blank">Chevereto</a>. To proceed, check the information below.</p>
          <ul>
            <li>Server path <code><?php echo $runtime->absPath; ?></code></li>
            <li>Website url <code><?php echo $runtime->rootUrl; ?></code></li>
          </ul>
          <p>Check that the above details match to where you want to install Chevereto and that there's no other software installed there.</p>
<?php if ($nginx) {
                      echo $nginx;
                  } ?>
          <div>
            <button class="action radius" data-action="show" data-arg="license">Continue</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-license" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>License key</h1>
          <p>The main release, Chevereto, is proprietary software and you require to <a href="https://chevereto.com/purchase" target="_blank">buy a license</a> to use it. The free edition (known as <a href="https://chevereto.com/free" target="_blank">Chevereto-Free</a>) is Free Open Source Software (FOSS).</p>
          <p class="highlight">The paid version has more features, gets more frequent updates, and provides an additional support layer if you need help. By purchasing, you support the ongoing development of this software.</p>
          <p>You can find the license key at your <a href="https://chevereto.com/panel/license" target="_blank">client panel</a>.</p>
          <div class="p input-label">
            <label for="licenseKey">License key</label>
            <input class="radius width-100p" type="text" name="licenseKey" id="licenseKey" placeholder="Paste your license key here">
          </div>
          <div>
            <button class="action radius" data-action="setEdition" data-arg="chevereto">Enter license key</button>
            <button class="radius" data-action="setEdition" data-arg="chevereto-free">Skip – Use Chevereto-Free</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-cpanel" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>cPanel access</h1>
          <p>This installer can connect to your <a href="https://documentation.cpanel.net/display/DD/Guide+to+UAPI" target="_blank">cPanel UAPI</a> to create the database, its user, and grant database privileges. Your cPanel user and password will be only used locally to connect to your cPanel backend.</p>
          <p>Nothing will be stored neither transmitted to anyone.</p>
<?php if ('https' == $runtime->httpProtocol) { ?>
          <p class="highlight">You are not browsing using HTTPS. For extra security, change your cPanel password once the installation gets completed.</p>
<?php } ?>
          <p class="highlight">Skip this if you don't run cPanel or if you want to setup the database requirements manually.</p>
          <div class="p input-label">
            <label for="cpanelUser">User</label>
            <input class="radius col-8" type="text" name="cpanelUser" placeholder="username">
          </div>
          <div class="p input-label">
          <label for="cpanelPassword">Password</label>
            <input class="radius col-8" type="password" name="cpanelPassword" placeholder="password">
          </div>
          <div>
            <button class="action radius" data-action="cpanelProcess">Connect to cPanel</button>
            <button class="radius" data-action="show" data-arg="database">Skip</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-database" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Database</h1>
          <p>Chevereto requires at least a MySQL 5.6 database. It will also work with MariaDB 10.</p>
          <p class="highlight">✨ The installer has already created this database setup for you.</p>
          <div class="p input-label">
            <label for="dbHost">Host</label>
            <input class="radius col-8" type="text" name="dbHost" id="dbHost" placeholder="localhost" required>
          </div>
          <div class="p input-label">
            <label for="dbPort">Port</label>
            <input class="radius col-8" type="number" name="dbPort" id="dbPort" value="3306" placeholder="3306" required>
          </div>
          <div class="p input-label">
            <label for="dbName">Name</label>
            <input class="radius col-8" type="text" name="dbName" id="dbName" placeholder="mydatabase" required>
          </div>
          <div class="p input-label">
            <label for="dbUser">User</label>
            <input class="radius col-8" type="text" name="dbUser" id="dbUser" placeholder="username" required>
          </div>
          <div class="p input-label">
            <label for="dbUserPassword">User password</label>
            <input class="radius col-8" type="text" name="dbUserPassword" id="dbUserPassword" placeholder="password" required>
          </div>
          <div>
            <button class="action radius" data-action="setDatabase">Set database</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-admin" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Administrator</h1>
          <p>Fill in your administrator user details. You can edit this account or add more administrators later.</p>
          <div class="p input-label">
            <label for="email">Email</label>
            <input class="radius col-8" type="email" name="email" id="email" placeholder="username@domain.com" required>
          </div>
          <div class="p input-label">
            <label for="username">Username</label>
            <input class="radius col-8" type="text" name="username" id="username" placeholder="admin" required>
          </div>
          <div class="p input-label">
            <label for="password">Password</label>
            <input class="radius col-8" type="password" name="password" id="password" placeholder="password" required>
          </div>
          <div>
            <button class="action radius" data-action="setAdmin">Set administrator</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-email" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Email addresses</h1>
          <div class="p input-label">
            <label for="no-reply">No-reply</label>
            <input class="radius col-8" type="email" name="no-reply" id="password" placeholder="no-reply@domain.com" required>
            <div><small>This address will be used as FROM email address when sending transactional emails (account functions, singup, alerts, etc.)</small></div>
          </div>
          <div class="p input-label">
            <label for="inbox">Inbox</label>
            <input class="radius col-8" type="email" name="inbox" id="password" placeholder="inbox@domain.com" required>
            <div><small>This address will be used to get contact form messages.</small></div>
          </div>
          <div>
            <button class="action radius" data-action="setEmails">Set emails</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-ready" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Ready to install</h1>
          <p>The installer is ready to download and install the latest <span class="chevereto-free--hide">Chevereto</span><span class="chevereto--hide">Chevereto-Free</span> release in <code><?php echo $runtime->absPath; ?></code></p>
          <p class="highlight chevereto-free--hide">By installing is understood that you accept the <a href="https://chevereto.com/license" target="_blank">Chevereto EULA</a>.</p>
          <p class="highlight chevereto--hide">By installing is understood that you accept the Chevereto-Free <a href="https://github.com/Chevereto/Chevereto-Free/blob/master/AGPLv3" target="_blank">AGPLv3 license</a>.</p>
          <div>
            <button class="action radius" data-action="install">Install <span class="chevereto-free--hide">Chevereto</span><span class="chevereto--hide">Chevereto-Free</span></button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-installing" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Installing</h1>
          <p>The software is being installed. Don't close this window until the process gets completed.</p>
          <div class="install-log p">
            <p>[T0] Installation process started</p>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-complete" class="screen screen--splash animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Installation completed</h1>
          <p>Chevereto has been installed. You can now login to your dashboard panel to configure your website to fit your needs.</p>
          <p>The installer has self-removed its file at <code><?php echo INSTALLER_FILEPATH; ?></code></p>
          <p>Take note on the installation details below.</p>
          <div class="install-details p highlight force-select font-size-80p">
            <pre>Chevereto installation
==========================

UTC 2019-11-06 22:18:37

URL: https://localhost:8888/
Edition: Chevereto-Free

#Administrator
Email: email@domain.com
Username: admin
Password: password

#Database
db_host: localhost
db_port: 3306
db_name: chevereto_c4f2h3
db_user: chevereto_87gnI
db_user_passwd: &8300f(**&39)
</pre></div>
           
          <p>Hope you enjoy using Chevereto as much we care in creating it. Help us by providing us feedback and recommend our software.</pp>
          <div>
            <button class="action radius" data-action="goTo" data-arg="dashboard">Open dashboard</button>
            <button class="radius" data-action="goTo" data-arg="homepage">Open homepage</button>
          </div>
        </div>
      </div>
    </div>
<?php } ?>