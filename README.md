# [Grav External Links Plugin][project]

> This plugin adds small icons to external and mailto links, informing users the link will take them to a new site or open their email client.

## About

`External Links` is a plugin for [GetGrav.org](http://getgrav.org) tused to differentiate between internal and external links. It also includes the ability of adding "rel=nofollow" to links as well and how external links shall be opened via "target" attribute. [Wikipedia](https://www.wikipedia.org/) is a well-known example.

Furthermore this plugin enables you to specify multiple domains, each of them on a new line to prevent them from being seen as external sites.

If you are interested in seeing this plugin in action, here is a screenshot:

![Screenshot External Links Plugin](assets/readme.png "External Links Preview")

## Installation and Updates

Installing or updating the `External Links` plugin can be done in one of two ways. Using the GPM (Grav Package Manager) installation method or manual install or update method by downloading [this plugin](https://github.com/sommerregen/grav-plugin-external-links) and extracting all plugin files to

    /your/site/grav/user/plugins/external_Links

For more informations, please check the [Installation and update guide](INSTALL.md).

## Usage

The `External Links` plugin comes with some sensible default configuration, that are pretty self explanatory:

### Config Defaults

```
# Global plugin configurations

enabled: true                 # Set to false to disable this plugin completely
built_in_css: true            # Use built-in CSS of the plugin
weight: 0                     # Set the weight (order of executing)

exclude:
  classes: exclude            # Exclude all links with this class
  domains:                    # A list of domains to be excluded e.g
  # - localhost/*             # (any RegExp can be used)
  # - img.domain.com/*

# Global and page specific configurations

process: true                 # Filter external links
no_follow: true               # Add rel="nofollow" to all external links
target: _blank                # Set target attribute of link
```

If you need to change any value, then the best process is to copy the [external_links.yaml](external_links.yaml) file into your `users/config/plugins/` folder (create it if it doesn't exist), and then modify there. This will override the default settings.

If you want to alter the settings for one or only few pages, you can do so by adding page specific configurations into your page headers, e.g.

```
external_links:
  process: false
```

to switch off `External Links` plugin just for this page.

### CSS Stylesheet Override

Something you might want to do is to override the look and feel of the external links, and with Grav it is super easy.

Copy the stylesheet [css/external_links.css](css/external_links.css) into the `css` folder of your custom theme, and add it to the list of CSS files.

```
/your/site/grav/user/themes/custom-theme/css/external_links.css
```

After that set the `built_in_css` option of the `External Links` plugin to `false`. That's it.

You can now edit, override and tweak it however you prefer. However, this plugin adds extra classes for styling to every link, you might wanna know:

- `external` -- Used to identify external links.
- `mailto` -- Used to identify mailto links.
- `no-img` -- Set if a link does not contain any image tags.
- `icon`-- Set if a link contains an image (with size <= 32px).
- `img` -- Set if a link contains an image (with size > 32px).
- `imgs` -- Set if a link contains more than one image.

## Contributing

You can contribute at any time! Before opening any issue, please search for existing issues and review the [guidelines for contributing](CONTRIBUTING.md).

After that please note:

* If you find a bug or would like to make a feature request or suggest an improvement, [please open a new issue][issues]. If you have any interesting ideas for additions to the syntax please do suggest them as well!
* Feature requests are more likely to get attention if you include a clearly described use case.
* If you wish to submit a pull request, please make again sure that your request match the [guidelines for contributing](CONTRIBUTING.md) and that you keep track of adding unit tests for any new or changed functionality.

### Support and donations

If you like my project, feel free to support me, since donations will keep this project alive. You can [![Flattr me](https://api.flattr.com/button/flattr-badge-large.png)][flattr] or send me some bitcoins to **1HQdy5aBzNKNvqspiLvcmzigCq7doGfLM4** whenever you want.

Thanks!

## License

Copyright (c) 2015 [Benjamin Regler][github]. See also the list of [contributors] who participated in this project.

[Licensed](LICENSE) for use under the terms of the [MIT license][mit-license].

[github]: https://github.com/sommerregen/ "GitHub account from Benjamin Regler"
[mit-license]: http://www.opensource.org/licenses/mit-license.php "MIT license"

[flattr]: https://flattr.com/submit/auto?user_id=Sommerregen&url=https://github.com/sommerregen/grav-plugin-external-links "Flatter my GitHub project"

[project]: https://github.com/sommerregen/grav-plugin-external-links
[issues]: https://github.com/sommerregen/grav-plugin-external-links/issues "GitHub Issues for Grav External Links Plugin"
[contributors]: https://github.com/sommerregen/grav-plugin-external-links/graphs/contributors "List of contributors of the project"
