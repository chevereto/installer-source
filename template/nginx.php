<?php

header('Content-Type: text/plain');
printf('# Chevereto NGINX generated rules for ' . $runtime->rootUrl . '

# Context limits
client_max_body_size 20M;

# Disable access to sensitive files
location ~* ' . $runtime->relPath . '(app|content|lib)/.*\.(po|php|lock|sql)$ {
  deny all;
}

# Image not found replacement
location ~ \.(jpe?g|png|gif|webp)$ {
    log_not_found off;
    error_page 404 ' . $runtime->relPath . 'content/images/system/default/404.gif;
}

# CORS header (avoids font rendering issues)
location ~* ' . $runtime->relPath . '.*\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js)$ {
  add_header Access-Control-Allow-Origin "*";
}

# Pretty URLs
location ' . $runtime->relPath . ' {
  index index.php;
  try_files $uri $uri/ /index.php$is_args$query_string;
}

# END Chevereto NGINX rules
');
die();
