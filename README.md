# Installer source

> 🔔 [Subscribe](https://newsletter.chevereto.com/subscription?f=PmL892XuTdfErVq763PCycJQrvZ8PYc9JbsVUttqiPV1zXt6DDtf7lhepEStqE8LhGs8922ZYmGT7CYjMH5uSx23pL6Q) to don't miss any update regarding Chevereto.

![Chevereto](LOGO.svg)

[![Discord](https://img.shields.io/discord/759137550312407050?style=flat-square)](https://chv.to/discord)

This is the repository for the source code used by the well-known [chevereto/installer](https://github.com/chevereto/installer).

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

## Application development

Make your HTTP/CLI calls to `./app.php`. Learn more at the [Installer API](https://github.com/chevereto/installer#apis).
