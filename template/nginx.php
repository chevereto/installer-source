<?php

header('Content-Type: text/plain');
printf('# Chevereto nginx generated rules for '.$runtime->rootUrl.'
## Disable access to sensitive files
location ~* '.$runtime->relPath.'(app|content|lib)/.*\.(po|php|lock|sql)$ {
  deny all;
}
## CORS headers
location ~* '.$runtime->relPath.'.*\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js) {
  add_header Access-Control-Allow-Origin "*";
}
## Upload path for image content only and set 404 replacement
location ^~ '.$runtime->relPath.'images/ {
  location ~* (jpe?g|png|gif) {
      log_not_found off;
      error_page 404 '.$runtime->relPath.'content/images/system/default/404.gif;
  }
  return 403;
}
## Pretty URLs
location '.$runtime->relPath.' {
  index index.php;
  try_files $uri $uri/ '.$runtime->relPath.'index.php?$query_string;
}
# END Chevereto nginx rules
');
die();
