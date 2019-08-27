# Installer source

This repository contains the source code used by the single-file [Chevereto/Installer](https://github.com/Chevereto/Installer).

## Application development

Make your HTTP calls to `./app.php`. All your changes will be interpreted without the need of build anything.

* `./app.php` The actual application (front controller).
* `./make.php` The single-file maker.
* `./build` Contains the built installer file.
* `./html` Contains HTML related resources (images, js, css).
* `./src` Contains the classes.
* `./template` Contains the templates.

## Build

The whole application can be concatenaded in one single file (distribution). To do this, you have to execute the `make.php` file.

```bash
php make.php
```

If everything goes OK you should get a message indicating the location of the build file (relative path):

```bash
[OK] build/installer.php
```

If something goes wrong, check the actual console output and make sure that your PHP interpreter isn't suppressing errors.
