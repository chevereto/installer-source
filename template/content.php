<?php if ($pageId == 'error') { ?>
    <div id="screen-error" class="screen screen--error">
      <div class="flex-box error-box" >
        <div>
          <h1>Aw, Snap!</h1>
          <p>Your web server lacks some requirements that must be fixed to install Chevereto.</p>
          <p>Please check:</p>
          <ul>
<?php
                  foreach ($requirementsCheck->errors as $v) {
                      ?>
              <li><?php echo $v; ?></li>
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
          <h1>Chevereto Installer</h1>
          <p>This tool will guide you through the process of installing Chevereto. To proceed, check the information below.</p>
          <ul>
            <li>Server path <code><?php echo $runtime->absPath; ?></code></li>
            <li>Website url <code><?php echo $runtime->rootUrl; ?></code></li>
          </ul>
          <p>Confirm that the above details match to where you want to install Chevereto and that there's no other software installed there.</p>
<?php
if (preg_match('/nginx/i', $runtime->serverSoftware)) { ?>
          <p>Add the following <a href="<?php echo $runtime->rootUrl.$runtime->installerFilename.'?getNginxRules'; ?>" target="_blank">server rules</a> to your <a href="https://www.digitalocean.com/community/tutorials/understanding-the-nginx-configuration-file-structure-and-configuration-contexts" target="_blank">nginx.conf</a> server block. Restart the server to apply changes. Once done, come back here and continue the process.</p>
<?php } ?>
          <div>
            <button class="action radius" data-action="show" data-arg="license">Continue</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-license" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Enter license key</h1>
          <p>A license key is required to install our main edition. You can purchase a license from our <a href="https://chevereto.com/pricing" target="_blank">website</a> if you don't have one yet.</p>
          <p></p>
          <p>Skip this to install <a href="https://chevereto.com/free" target="_blank">Chevereto-Free</a>, which is our Open Source edition.</p> 
          <p class="highlight">The paid edition has more features, gets more frequent updates, and provides additional support assistance.</p>
          <p class="p alert"></p>
          <div class="p input-label">
            <label for="installKey">License key</label>
            <input class="radius width-100p" type="text" name="installKey" id="installKey" placeholder="Paste your license key here" autofill="off" autocomplete="off">
            <div><small>You can find the license key at your <a href="https://chevereto.com/panel/license" target="_blank">client panel</a>.</small></div>
          </div>
          <div>
            <button class="action radius" data-action="setLicense" data-arg="installKey">Enter license key</button>
            <button class=" radius" data-action="setEdition" data-arg="chevereto-free">Skip – Use Chevereto-Free</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-upgrade" class="screen animate animate--slow">
      <div class="header flex-item"><?php echo $svgLogo; ?></div>
      <div class="flex-box col-width">
        <div>
          <h1>Upgrade</h1>
          <p>A license key is required to upgrade to our main edition. You can purchase a license from our <a href="https://chevereto.com/pricing" target="_blank">website</a> if you don't have one yet.</p>
          <p>The system database schema will change, and the system files will get replaced. Don't forget to backup.</p>
          <p>Your system settings, previous uploads, and all user-generated content will remain there.</p>
          <p class="p alert"></p>
          <div class="p input-label">
            <label for="upgradeKey">License key</label>
            <input class="radius width-100p" type="text" name="upgradeKey" id="upgradeKey" placeholder="Paste your license key here">
            <div><small>You can find the license key at your <a href="https://chevereto.com/panel/license" target="_blank">client panel</a>.</small></div>
          </div>
          <div>
            <button class="action radius" data-action="setLicense" data-arg="upgradeKey">Enter license key</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-cpanel" class="screen animate animate--slow">
      <div class="header flex-item"><?php echo $svgCpanelLogo; ?></div>
      <div class="flex-box col-width">
        <div>
          <h1>cPanel access</h1>
          <p>This installer can connect to a cPanel backend using the <a href="https://documentation.cpanel.net/display/DD/Guide+to+UAPI" target="_blank">cPanel UAPI</a> to create the database, its user, and grant database privileges.</p>
<?php if ('https' == $runtime->httpProtocol) { ?>
          <p class="highlight">You are not browsing using HTTPS. For extra security, change your cPanel password once the installation gets completed.</p>
<?php } ?>
          <p>The cPanel credentials won't be stored either transmitted to anyone.</p>
          <p class="highlight">Skip this if you don't run cPanel or if you want to setup the database requirements manually.</p>
          <p class="p alert"></p>
          <div class="p input-label">
            <label for="cpanelUser">User</label>
            <input class="radius width-100p" type="text" name="cpanelUser" id="cpanelUser" placeholder="username" autocomplete="off">
          </div>
          <div class="p input-label">
          <label for="cpanelPassword">Password</label>
            <input class="radius width-100p" type="password" name="cpanelPassword" id="cpanelPassword" placeholder="password" autocomplete="off">
          </div>
          <div>
            <button class="action radius" data-action="cpanelProcess">Connect to cPanel</button>
            <button class="radius" data-action="show" data-arg="db">Skip</button>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-db" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Database</h1>
          <p>Chevereto requires a MySQL 5.6 (MySQL 8 recommended) database. It will also work with MariaDB 10.</p>
          <form method="post" name="database" data-trigger="setDb" autocomplete="off">
            <p class="p alert"></p>
            <div class="p input-label">
              <label for="dbHost">Host</label>
              <input class="radius width-100p" type="text" name="dbHost" id="dbHost" placeholder="localhost" value="localhost" required>
              <div><small>If you are using Docker, enter the MySQL/MariaDB container hostname or its IP.</small></div>
            </div>
            <div class="p input-label">
              <label for="dbPort">Port</label>
              <input class="radius width-100p" type="number" name="dbPort" id="dbPort" value="3306" placeholder="3306" required>
            </div>
            <div class="p input-label">
              <label for="dbName">Name</label>
              <input class="radius width-100p" type="text" name="dbName" id="dbName" placeholder="mydatabase" required>
            </div>
            <div class="p input-label">
              <label for="dbUser">User</label>
              <input class="radius width-100p" type="text" name="dbUser" id="dbUser" placeholder="username" required>
              <div><small>The database user must have ALL PRIVILEGES on the target database.</small></div>
            </div>
            <div class="p input-label">
              <label for="dbUserPassword">User password</label>
              <input class="radius width-100p" type="password" name="dbUserPassword" id="dbUserPassword" placeholder="password">
            </div>
            <div>
              <button class="action radius">Set database</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="screen-admin" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Administrator</h1>
          <p>Fill in your administrator user details. You can edit this account or add more administrators later.</p>
          <form method="post" name="admin" data-trigger="setAdmin" autocomplete="off">
            <p class="p alert"></p>
            <div class="p input-label">
              <label for="adminEmail">Email</label>
              <input class="radius width-100p" type="email" name="adminEmail" id="adminEmail" placeholder="username@domain.com" autocomplete="off" required>
              <div><small>Make sure that this email is working or you won't be able to recover the account if you lost the password.</small></div>
            </div>
            <div class="p input-label">
              <label for="adminUsername">Username</label>
              <input class="radius width-100p" type="text" name="adminUsername" id="adminUsername" placeholder="admin" pattern="^[\w]{3,16}$" autocomplete="off" minlength="3" maxlength="16" required>
              <div><small>3 to 16 characters. Letters, numbers and underscore.</small></div>
            </div>
            <div class="p input-label">
              <label for="adminPassword">Password</label>
              <input class="radius width-100p" type="password" name="adminPassword" id="adminPassword" placeholder="password" pattern="^.{6,128}$" minlength="6" maxlength="128" autocomplete="off" required>
              <div><small>6 to 128 characters.</small></div>
            </div>
            <div>
              <button class="action radius">Set administrator</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div id="screen-emails" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Email addresses</h1>
          <p>Fill in the email addresses that will be used by the system. You can edit this later.</p>
          <form method="post" name="emails" data-trigger="setEmails">
            <p class="p alert"></p>
            <div class="p input-label">
              <label for="no-reply">No-reply</label>
              <input class="radius width-100p" type="email" name="emailNoreply" id="emailNoreply" placeholder="no-reply@domain.com" required>
              <div><small>This address will be used as FROM email address when sending transactional emails (account functions, singup, alerts, etc.)</small></div>
            </div>
            <div class="p input-label">
              <label for="inbox">Inbox</label>
              <input class="radius width-100p" type="email" name="emailInbox" id="emailInbox" placeholder="inbox@domain.com" required>
              <div><small>This address will be used to get contact form messages.</small></div>
            </div>
            <div>
              <button class="action radius">Set emails</button>
            </div>
          </form>
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
          <p class="p alert"></p>
          <div class="install-log p"></div>
        </div>
      </div>
    </div>

    <div id="screen-complete" class="screen animate animate--slow">
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
</pre>
          </div>
           
          <p>Hope you enjoy using Chevereto as much we care in creating it. Help us by providing us feedback and recommend our software.</pp>
          <div>
            <a class="button action radius" href="<?php echo $runtime->rootUrl; ?>dashboard" target="_blank">Open dashboard</a>
            <a class="button radius" href="<?php echo $runtime->rootUrl; ?>" target="_blank">Open homepage</a>
          </div>
        </div>
      </div>
    </div>

    <div id="screen-complete-upgrade" class="screen animate animate--slow">
      <div class="flex-box col-width">
        <div>
          <h1>Upgrade completed</h1>
          <p>Chevereto has been upgraded. You can now login to your dashboard panel to configure your website to fit your needs.</p>
          <p>The installer has self-removed its file at <code><?php echo INSTALLER_FILEPATH; ?></code></p>     
          <p>Hope you enjoy the software and many thanks for support our work.</p>     
          <div>
            <button class="action radius" data-action="goTo" data-arg="dashboard">Open dashboard</button>
            <button class="radius" data-action="goTo" data-arg="homepage">Open homepage</button>
          </div>
        </div>
      </div>
    </div>

<?php } ?>