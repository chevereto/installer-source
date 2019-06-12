<?php

function remoteInstall($args = [])
{
    @set_time_limit(120); // Up to 2 minutes
    $editionLabel = ucwords(str_replace('-', ' ', $args['install']['edition']));
    $domain_data = [];
    // Add some data
    $args['user']['host'] = parse_url($args['user']['website'], PHP_URL_HOST);
    $args['user']['path'] = ltrim(parse_url($args['user']['website'], PHP_URL_PATH), '/\\');
    $args['install']['url'] = $args['install']['website'].'/install';
    // $args['user']['host'] = 'woollypigs.com';// add_forwarder no reconoce sub-dominios

    $steps = [
        'cPanelAPI' => [
            [
                'module' => 'DomainInfo',
                'function' => 'single_domain_data',
                'params' => [
                    'domain' => $args['user']['host'],
                ],
            ],
            'addForwarderIncoming' => [
                'module' => 'Email',
                'function' => 'add_forwarder',
                'params' => [
                    'domain' => $args['user']['host'],
                    'email' => $args['install']['email']['email_incoming_email'],
                    'fwdopt' => 'fwd',
                    'fwdemail' => $args['user']['email'],
                ],
            ],
            'addForwarderFrom' => [
                'module' => 'Email',
                'function' => 'add_forwarder',
                'params' => [
                    'domain' => $args['user']['host'],
                    'email' => $args['install']['email']['email_from_email'],
                    'fwdopt' => 'blackhole',
                ],
            ],
            [
                'module' => 'Mysql',
                'function' => 'create_database',
                'params' => [
                    'name' => $args['db']['db_name'],
                ],
            ],
            [
                'module' => 'Mysql',
                'function' => 'create_user',
                'params' => [
                    'name' => $args['db']['db_user'],
                    'password' => $args['db']['db_pass'],
                ],
            ],
            [
                'module' => 'Mysql',
                'function' => 'set_privileges_on_database',
                'params' => [
                    'user' => $args['db']['db_user'],
                    'database' => $args['db']['db_name'],
                    'privileges' => 'ALL PRIVILEGES',
                ],
            ],
            'uploadWebInstaller' => [
                'module' => 'Fileman',
                'function' => 'upload_files',
                'params' => [
                    'file' => $args['install']['edition'] == 'chevereto' ? CHV_FILE_WEBINSTALLER : CHV_FILE_WEBINSTALLER_FREE,
                    'dir' => null,
                ],
            ],
        ],
        'webInstaller' => [
            'download' => [
                'action' => 'download',
                'license' => $args['install']['edition'] == 'chevereto' ? urlencode($args['user']['licenseKey']) : null,
            ],
            'extract' => [
                'action' => 'extract',
                'file' => null,
            ],
        ],
        'installClient' => [
            [
                'action' => 'POST database',
                'url' => $args['install']['url'],
                'POST' => $args['db'],
            ],
            'FormInstall' => [
                'action' => 'POST install',
                'url' => $args['install']['url'],
                'POST' => $args['install']['admin'] + $args['install']['email'] + [
                    'website_mode' => 'community',
                ],
            ],
        ],
    ];

    foreach ($steps as $group => &$step) {
        foreach ($step as $thread) {
            try {
                switch ($group) {
                    case 'cPanelAPI':
                        if ($thread['module'] == 'Email') {
                            $thread['params']['email'] .= $email_at_domain;
                        }
                        if ($thread['module'] == 'Fileman') {
                            $thread['params']['dir'] = $server_dir;
                            if (is_null($thread['params']['dir'])) {
                                throw new Exception('NULL target upload dir');
                            }
                        }
                        $API = cPanelAPI($args['cpanel'] + $thread);
                        if ($thread['module'] == 'DomainInfo') {
                            $domain_data = $API['data'];
                            // Fix a upload dir relative to user folder
                            $domain_data['dir'] = G\add_trailing_slashes(G\str_replace_first($domain_data['homedir'], null, $domain_data['documentroot']));
                            // Store server target dir
                            $server_dir = trim($domain_data['dir'].$args['user']['path'], '/');
                            // Fill email addresses on domain
                            // $email_at_domain = '@' . ($domain_data['type'] == 'parked_domain' ? $args['user']['host'] : getRootDomain($args['user']['host']));
                            $email_at_domain = '@'.(in_array($domain_data['type'], ['main_domain', 'parked_domain']) ? $args['user']['host'] : getRootDomain($args['user']['host']));
                            foreach (['incoming', 'from'] as $k) {
                                // Fill installClient FormInstall
                                $steps['installClient']['FormInstall']['POST']['email_'.$k.'_email'] .= $email_at_domain;
                                $args['install']['email']['email_'.$k.'_email'] .= $email_at_domain;
                            }
                        }
                    break;
                    case 'webInstaller':
                        if ($thread['action'] == 'extract') {
                            $thread['file'] = $API['download']['filename'];
                        }
                        $API_RAW = G\fetch_url($args['install']['website'].'/index.php?'.urldecode(http_build_query($thread)));
                        // G\debug($API_RAW);
                        $API = json_decode($API_RAW, true);
                        if (json_last_error() !== JSON_ERROR_NONE && strpos($API_RAW, 'chevereto.com') === false) {
                            $errorReference = [
                                JSON_ERROR_NONE => 'No error has occurred.',
                                JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.',
                                JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.',
                                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.',
                                JSON_ERROR_SYNTAX => 'Syntax error.',
                                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
                                JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded.',
                                JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded.',
                                JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given.',
                            ];
                            throw new Exception('json_decode: '.$errorReference[json_last_error()]);
                        }
                    break;
                    case 'installClient':
                        $API = installClient($thread);
                    break;
                }
            } catch (Exception $e) {
                $API = [
                    'status_code' => 400,
                    'error' => [
                        'message' => $e->getMessage(),
                    ],
                ];
            }
            if ($API['status_code'] == 400 || $API['status'] == '0') {
                $error_output = '<b>%group/%module%function</b> error > '.(is_array($API['errors']) ? implode(', ', $API['errors']) : $API['error']['message']);
                $error_output = strtr($error_output, [
                    '%group' => $group,
                    '%module' => $thread[$group == 'cPanelAPI' ? 'module' : 'action'],
                    '%function' => $group == 'cPanelAPI' ? ('/'.$thread['function']) : null,
                ]);
                throw new Exception($error_output, 400);
            }
        }
    }

    $messageTpl = '<p>We have just installed %editionLabel in your website. Here all the details:</p>

<table width="100%" border="0" cellspacing="0" cellpadding="5" style="margin-bottom: 20px;">
	<tr>
		<td colspan="2"><b>URLs</b></td>
	</tr>
	<tr>
		<td width="20%">Website</td>
		<td width="80%"><a href="%install_website">%install_website</a></td>
	</tr>
	<tr>
		<td>Dashboard</td>
		<td><a href="%install_website/dashboard">%install_website/dashboard</a></td>
	</tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="5" style="margin-bottom: 20px;">
	<tr>
		<td colspan="2"><b>Admin account</b></td>
	</tr>
	<tr>
		<td width="20%">Username</td>
		<td width="80%">%install_admin_username</td>
	</tr>
	<tr>
		<td>Email</td>
		<td>%install_admin_email</td>
	</tr>
	<tr>
		<td>Password</td>
		<td>%install_admin_password</td>
	</tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="5" style="margin-bottom: 20px;">
	<tr>
		<td colspan="2"><b>Email forwarding</b></td>
	</tr>
	<tr>
		<td width="20%">Incoming</td>
		<td width="80%">%install_email_email_incoming_email (forwards to %install_admin_email)</td>
	</tr>
	<tr>
		<td>No reply</td>
		<td>%install_email_email_from_email (:blackhole:)</td>
	</tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="5"">
	<tr>
		<td colspan="2"><b>Database details</b></td>
	</tr>
	<tr>
		<td width="20%">DB Name</td>
		<td width="80%">%db_db_name</td>
	</tr>
	<tr>
		<td>DB User</td>
		<td>%db_db_user</td>
	</tr>
	<tr>
		<td>DB User Pass</td>
		<td>%db_db_pass</td>
	</tr>
</table>

<p>Go to <a href="'.G\get_base_url().'">Chevereto.com</a> for news, downloads, support, community forums and more.</p>';

    $translate = [];
    foreach ($args as $group => $arg) {
        foreach ($arg as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $translate['%'.$group.'_'.$k.'_'.$kk] = $vv;
                }
            } else {
                $translate['%'.$group.'_'.$k] = $v;
            }
        }
    }

    $translate['%editionLabel'] = $editionLabel.($args['install']['edition'] == 'chevereto' ? (' v'.get_settings()['last_release']) : null);
    $message = strtr($messageTpl, $translate);

    // ALL DONE!, send the email
    mail_to_user($args['user']['email'], strtr('%editionLabel installation details', ['%editionLabel' => $editionLabel]), $message);
}
