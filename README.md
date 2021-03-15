# Installer source

This repository contains the source code used by the single-file [Chevereto/Installer](https://github.com/Chevereto/Installer).

## Components

* `./app.php` The actual application (front controller).
* `./make.php` The single-file maker.
* `./build` Contains the build result.
* `./html` Contains HTML related resources (images, js, css).
* `./src` Contains the PHP sources.
* `./template` Contains the templates.

## Build

The whole application can be concatenated in one single file (distribution). To do this, you have to execute the `make.php` file.

```bash
php make.php
```

If everything goes OK you should get a message indicating the location of the build file (relative path):

```bash
[OK] build/installer.php
```

If something goes wrong, check the actual console output and make sure that your PHP interpreter isn't suppressing errors.

## Application development

Make your HTTP/CLI calls to `./app.php`. Learn more at the [Installer API](https://github.com/Chevereto/Installer#api).
