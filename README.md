[![Build Status](https://travis-ci.org/o10n-x/wordpress-file-page-cache.svg?branch=master)](https://travis-ci.org/o10n-x/wordpress-file-page-cache) ![Version](https://img.shields.io/github/release/o10n-x/wordpress-file-page-cache.svg)

# WordPress File Page Cache

Advanced file based page cache with PHP Opcache boost option ([500x faster than Redis and Memcached](https://blog.graphiq.com/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad)). 


* [Documentation](https://github.com/o10n-x/wordpress-file-page-cache/tree/master/docs)
* [Description](https://github.com/o10n-x/wordpress-file-page-cache#description)
* [Version history (Changelog)](https://github.com/o10n-x/wordpress-file-page-cache/releases)

## Installation

![Github Updater](https://github.com/afragen/github-updater/raw/develop/assets/GitHub_Updater_logo_small.png)

This plugin can be installed and updated using [Github Updater](https://github.com/afragen/github-updater) ([installation instructions](https://github.com/afragen/github-updater/wiki/Installation))

## WordPress WPO Collection

This plugin is part of a Website Performance Optimization collection that include [CSS](https://github.com/o10n-x/wordpress-css-optimization), [HTML](https://github.com/o10n-x/wordpress-html-optimization), [Web Font](https://github.com/o10n-x/wordpress-font-optimization), [HTTP/2](https://github.com/o10n-x/wordpress-http2-optimization), [Progressive Web App (Service Worker)](https://github.com/o10n-x/wordpress-pwa-optimization) and [Security Header](https://github.com/o10n-x/wordpress-security-header-optimization) optimization. 

The WPO optimization plugins provide in all essential tools that enable to achieve perfect [Google Lighthouse Test](https://developers.google.com/web/tools/lighthouse/) scores and to validate a website as [Google PWA](https://developers.google.com/web/progressive-web-apps/), an important ranking factor for Google's [Speed Update](https://searchengineland.com/google-speed-update-page-speed-will-become-ranking-factor-mobile-search-289904) (July 2018).

![Google Lighthouse Perfect Performance Scores](https://github.com/o10n-x/wordpress-css-optimization/blob/master/docs/images/google-lighthouse-pwa-validation.jpg)

The WPO optimization plugins are designed to work together with single plugin performance. The plugins provide the latest optimization technologies and many unique innovations.

### JSON shema configuration

The WPO optimization plugins are based on JSON schema based configuration that enables full control of the optimization using JSON. This provides several great advantages for website performance optimization.

Read more about [JSON schemas](https://github.com/o10n-x/wordpress-o10n-core/tree/master/schemas).

## Google PageSpeed vs Google Lighthouse Scores

While a Google PageSpeed 100 score is still of value, websites with a high Google PageSpeed score may score very bad in Google's new [Lighthouse performance test](https://developers.google.com/web/tools/lighthouse/). 

The following scores are for the same site. It shows that a perfect Google PageSpeed score does not correlate to a high Google Lighthouse performance score.

![Perfect Google PageSpeed 100 Score](https://github.com/o10n-x/wordpress-css-optimization/blob/master/docs/images/google-pagespeed-100.png) ![Google Lighthouse Critical Performance Score](https://github.com/o10n-x/wordpress-css-optimization/blob/master/docs/images/lighthouse-performance-15.png)

### Google PageSpeed score is outdated

For the open web to have a chance of survival in a mobile era it needs to compete with and win from native mobile apps. Google is dependent on the open web for it's advertising revenue. Google therefor seeks a way to secure the open web and the main objective is to rapidly enhance the quality of the open web to meet the standards of native mobile apps.

For SEO it is therefor simple: websites will need to meet the standards set by the [Google Lighthouse Test](https://developers.google.com/web/tools/lighthouse/) (or Google's future new tests). A website with perfect scores will be preferred in search over low performance websites. The officially announced [Google Speed Update](https://searchengineland.com/google-speed-update-page-speed-will-become-ranking-factor-mobile-search-289904) (July 2018) shows that Google is going as far as it can to drive people to enhance the quality to ultra high levels, to meet the quality of, and hopefully beat native mobile apps.

A perfect Google Lighthouse Score includes validation of a website as a [Progressive Web App (PWA)](https://developers.google.com/web/progressive-web-apps/).

Google offers another new website performance test that is much tougher than the Google PageSpeed score. It is based on a AI neural network and it can be accessed on https://testmysite.thinkwithgoogle.com

## Description

This plugin is a file based page cache with PHP Opcache boost option ([500x faster than Redis and Memcached](https://blog.graphiq.com/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad)).

The plugin provides a cache policy editor that enables to selectively enable/disable or configure the page cache based on page URLs or conditional methods such as `is_page`.

![Cache Policy Editor](https://github.com/o10n-x/wordpress-file-page-cache/blob/master/docs/images/cache-policy-editor.png)
 
Due to the limit of the PHP Opcache total size, the boost option can be configured for individual pages.

Additional features can be requested on the [Github forum](https://github.com/o10n-x/wordpress-file-page-cache/issues).
