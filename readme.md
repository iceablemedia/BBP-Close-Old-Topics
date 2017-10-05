# BBP Close Old Topics
**Contributors:** iceable  
**Tags:** bbpress, bbp, close, topics, forums  
**Requires at least:** 4.0  
**Tested up to:** 4.8.2  
**Stable tag:** 1.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  
**Donate link:** http://www.iceable.com/buy-me-a-beer/

Extension for bbPress to close old topics automatically when they are older than an admin-defined period of time.

## Description

BBP Close Old Topics is a bbPress extension to close old topics automatically when they are older than an admin-defined period of time, from one week to one year.

The period of time you set is compared to the topic freshness (usually based on the date of the last reply) to determine if a topic should be closed.

Old topics can be "soft-closed" on the fly only, or actually closed in the database.

Settings are in Settings > Forums, under "Forum Features".

If you choose to only soft-close old topics on the fly, they will only appear closed as long as the plugin is active, but you will find them still open if you disable it.

If you check the "Hard close" option, every topic that gets closed on the fly will also be effectively closed in the database. In this case they will remain closed even if you disable this plugin.

__This plugin only works with bbPress 2.2 or later.__ It will not have any effect if bbPress is not installed and activated, and will  not work correctly with any version older than 2.2. This plugin was tested with bbPress up to 2.5.14.

## Installation

### From GitHub

1. Clone or Download this repo.
2. Unzip it and upload the 'bbp-close-old-topics' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, FileManager, etc...)
3. Activate BBP Close old Topics from your Plugins page in wp-admin.

### From your WordPress dashboard

(coming soon)

### From WordPress.org

(coming soon)

## Configuration

1. Visit 'Settings > Forums' and set the settings in the "Forum Features" section.
2. Check the "Close Old Topics" box to enable this feature, and set how old posts should be before closing them (1 year by default).
3. Optionally check the "Hard Close Old Topics" if you want topics to be actually closed in the database.

## Frequently Asked Questions

### It doesn't do anything, there isn't even any settings!

__This plugin only works with bbPress 2.2 or later.__ It will not have any effect if bbPress is not installed and activated, and will  not work correctly with any version older than 2.2. This plugin was tested with bbPress up to 2.5.14.

### It's cool, and I think it could be even better

Suggestions are certainly welcome!  
Pull requests are also more than welcome on [GitHub](https://github.com/iceable/bbp-close-old-topics)

## Screenshots

1. Settings in the bbPress Admin settings area.

![Settings in the bbPress Admin settings area.](screenshot-1.png)

## Credits

Some parts of the code of this plugin were inspired by the work of Brandon Allen: https://github.com/thebrandonallen/bbp-auto-close-topics

## Changelog

#### [1.0.0] - 2017-10-05
* Initial release.
