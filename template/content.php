<?php if ($pageId == 'error') { ?>
  <div id="screen-error" class="screen screen--error">
    <div class="flex-box error-box">
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
        <p>Confirm that the above details match to where you want to install Chevereto and that there's no other software installed.</p>
        <?php
          if (preg_match('/nginx/i', $runtime->serverSoftware)) { ?>
          <p class="highlight">✍ Take note on the <a href="<?php echo $runtime->rootUrl . $runtime->installerFilename . '?getNginxRules'; ?>" target="_blank">nginx server rules</a> that should be already applied to your <code>nginx.conf</code> server block. If those aren't provided this installer will fail to complete the process.</p>
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
        <p>A license key is required to install Chevereto. You can <a href="https://chevereto.com/pricing" target="_blank">get a license</a> if you don't have one yet.</p>
        <p class="highlight">💎 The paid edition has more features, gets more frequent updates, and keeps the developer eating.</p>
        <p class="p alert"></p>
        <div class="p input-label">
          <label for="installKey">License key</label>
          <input class="radius width-100p" type="text" name="installKey" id="installKey" placeholder="Paste your license key here" autofill="off" autocomplete="off">
          <div><small>You can find the license key at your <a href="https://chevereto.com/panel/license" target="_blank">client panel</a>.</small></div>
        </div>
        <div>
          <button class="action radius" data-action="setLicense" data-arg="installKey">Enter license key</button>
          <button class=" radius" data-action="setSoftware" data-arg="chevereto-free">Skip – Use Chevereto-Free</button>
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
          <button class="action radius" data-action="setUpgrade" data-arg="upgradeKey">Enter license key</button>
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
          <p class="highlight">⛔ You are not browsing using HTTPS. For extra security, change your cPanel password once the installation gets completed.</p>
        <?php } ?>
        <p>The cPanel credentials won't be stored either transmitted to anyone.</p>
        <p class="highlight">⏩ Skip this if you don't run cPanel or if you want to setup the database requirements manually.</p>
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
          <button class="action radius" data-action="cPanelProcess">Connect to cPanel</button>
          <button class="radius" data-action="show" data-arg="db">Skip</button>
        </div>
      </div>
    </div>
  </div>

  <div id="screen-db" class="screen animate animate--slow">
    <div class="flex-box col-width">
      <div>
        <h1>Database</h1>
        <p>Chevereto requires a SQL database, ideally MariaDB 10.</p>
        <?php if(isDocker()) { ?>
        <p class="highlight">✨ Database values are being provided using environment variables.</p>
        <?php } ?>
        <?php
            function echoDatabaseEnv(string $env, string $default): void {
                echo 'placeholder="' . $default . '" ';
                $getEnv = getenv($env);
                if($getEnv !== false) {
                    echo 'value="' . getenv($env) .'" readonly';
                }
            }
        ?>
        <form method="post" name="database" data-trigger="setDb" autocomplete="off">
          <p class="p alert"></p>
          <div class="p input-label">
            <label for="dbHost">Host</label>
            <input class="radius width-100p" type="text" name="dbHost" id="dbHost"
            <?php
                echoDatabaseEnv('CHEVERETO_DB_HOST', 'localhost');
            ?>
            required>
          </div>
          <div class="p input-label">
            <label for="dbPort">Port</label>
            <input class="radius width-100p" type="number" name="dbPort" id="dbPort"
            <?php
                echoDatabaseEnv('CHEVERETO_DB_PORT', '3306');
            ?>
            required>
          </div>
          <div class="p input-label">
            <label for="dbName">Name</label>
            <input class="radius width-100p" type="text" name="dbName" id="dbName"
            <?php
                echoDatabaseEnv('CHEVERETO_DB_NAME', 'database');
            ?>
            required>
          </div>
          <div class="p input-label">
            <label for="dbUser">User</label>
            <input class="radius width-100p" type="text" name="dbUser" id="dbUser"
            <?php
                echoDatabaseEnv('CHEVERETO_DB_USER', 'username');
            ?>
            required>
            <div><small>The database user must have ALL PRIVILEGES on the target database.</small></div>
          </div>
          <div class="p input-label">
            <label for="dbUserPassword">User password</label>
            <input class="radius width-100p" type="password" name="dbUserPassword" id="dbUserPassword"
            <?php
                echoDatabaseEnv('CHEVERETO_DB_PASS', 'password');
            ?>
            >
          </div>
          <div>
            <button class="action radius">
            <?php 
                echo isDocker() ? 'Check' : 'Set';
            ?> Database
            </button>
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
            <input class="radius width-100p" type="text" name="adminUsername" id="adminUsername" placeholder="admin" pattern="<?php echo $patterns['username_pattern']; ?>" autocomplete="off" required>
            <div><small>3 to 16 characters. Letters, numbers and underscore.</small></div>
          </div>
          <div class="p input-label">
            <label for="adminPassword">Password</label>
            <input class="radius width-100p" type="password" name="adminPassword" id="adminPassword" placeholder="password" pattern="<?php echo $patterns['user_password_pattern']; ?>" autocomplete="off" required>
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
        <p class="highlight chevereto--hide">By installing is understood that you accept the Chevereto-Free <a href="<?php echo APPLICATIONS['chevereto-free']['url'] . '/blob/master/LICENSE'; ?>" target="_blank">MIT license</a>.</p>
        <div>
          <button class="action radius" data-action="install">Install <span class="chevereto-free--hide">Chevereto</span><span class="chevereto--hide">Chevereto-Free</span></button>
        </div>
      </div>
    </div>
  </div>

  <div id="screen-ready-upgrade" class="screen animate animate--slow">
    <div class="flex-box col-width">
      <div>
        <h1>Ready to upgrade</h1>
        <p>The installer is ready to download and upgrade to the latest Chevereto release in <code><?php echo $runtime->absPath; ?></code></p>
        <p class="highlight">By upgrading is understood that you accept the <a href="https://chevereto.com/license" target="_blank">Chevereto EULA</a>.</p>
        <div>
          <button class="action radius" data-action="upgrade">Upgrade Chevereto</button>
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
        <div class="log log--install p"></div>
      </div>
    </div>
  </div>

  <div id="screen-upgrading" class="screen animate animate--slow">
    <div class="flex-box col-width">
      <div>
        <h1>Upgrading</h1>
        <p>The software is being upgraded. Don't close this window until the process gets completed.</p>
        <p class="p alert"></p>
        <div class="log log--upgrade p"></div>
      </div>
    </div>
  </div>

  <div id="screen-complete" class="screen animate animate--slow">
    <div class="flex-box col-width">
      <div>
        <h1>Installation completed</h1>
        <p>Chevereto has been installed. You can now login to your dashboard panel to configure your website to fit your needs.</p>
        <p class="alert">Double-check if the installer file was removed from <code><?php echo INSTALLER_FILEPATH; ?></code></p>
        <p>Take note on the installation details below.</p>
        <div class="install-details p highlight font-size-80p"></div>
        <p>💖 Hope you enjoy using Chevereto.</p>
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
        <h1>Upgrade prepared</h1>
        <p>The system files have been upgraded. You can now install the upgrade which will perform the database changes needed and complete the process.</p>
        <p class="alert">Double-check if the installer file was removed from <code><?php echo INSTALLER_FILEPATH; ?></code></p>
        <div>
          <a class="button action radius" href="<?php echo $runtime->rootUrl; ?>install">Install upgrade</a>
        </div>
      </div>
    </div>
  </div>

<?php } ?>