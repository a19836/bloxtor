php.js is an open source project that brings high-level PHP
functions to low-level JavaScript platforms such as webbrowsers,
  AIR, V8 and rhino.

If you want to perform high-level operations on these platforms,
you probably need to write JS that combines it's lower-level
functions and build it up until you have something useful like:
md5(), strip_tags(), strtotime(), number_format(), wordwrap().

That's what we are doing for you.

More info at:

- [http://phpjs.org/](http://phpjs.org/)
- [http://github.com/kvz/phpjs/wiki/](http://github.com/kvz/phpjs/wiki/)
- [https://github.com/locutusjs/locutus/blob/master/LICENSE](https://github.com/locutusjs/locutus/blob/master/LICENSE)

# Building the site

## Octopress

For prerequisites please check [here](http://kvz.io/blog/2012/09/25/blog-with-octopress/)

## build, generate, commit, push, deploy
```shell
make site MSG="Updated site"
```

## preview locally
```shell
make site-preview
```

## reset site (should not be necessary)
```shell
make site-clean
```
