# v1.2.0
## 02/21/2015

1. [](#new)
  * Added option `mode` to parse links passively (where no CSS classes are set) and actively
2. [](#improved)
  * Allow multiple classes to exclude in option `exclude.classes`
  * Improved process engine to ensure not to alter HTML tags or HTML entities in content
  * Refactored code
3. [](#bugfix)
  * Fixed self-closing tags in HTML5 and ensured to return contents compliant to HTML(5)
  * Fixed LightSlider issue

# v1.1.3
## 02/10/2015

3. [](#bugfix)
  * Fixed self-closing tags in HTML5 and ensured to return contents compliant to HTML(5)

# v1.1.2
## 02/10/2015

1. [](#new)
  * By default `External Links` now uses the class `external-links` for CSS styling; using `external` is still possible e.g. for manually markup external links
2. [](#improved)
  * Improved usage example in README.md
3. [](#bugfix)
  * Fixed issue with LightSlider plugin

# v1.1.1
## 02/06/2015

1. [](#new)
  * Added usage example in README.md
  * Add icons next to external links via CSS when using class `external` only
2. [](#improved)
  * Added support for HHVM **(requires Grav 0.9.17+)**
  * Added modular pages support
3. [](#bugfix)
  * Fixed regular expression in `isExternalUrl($url)` method

# v1.1.0
## 02/05/2015

1. [](#new)
  * IMPORTANT: Changed names of external link classes with images to `image`, `images` and `no-image`
2. [](#improved)
  * Improved readability of code
  * Updated plugin to use new `mergeConfig` method of Grav core
  * Improved and corrected calculations of image size
3. [](#bugfix)
  * Fixed some typo in the documentation
  * Fixed and removed additional <body> tag from page content

# v1.0.1
## 01/29/2015

1. [](#improved)
  * Fixed minor issues (broken README link, removed debugging functions)

# v1.0.0
## 01/29/2015

1. [](#new)
  * ChangeLog started...
