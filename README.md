# Bridge To Jira
Contributors: ccosmin
Donate link: https://snowlinesoftware.com
Tags: comments, spam
Requires at least: 4.6
Tested up to: 5.2.2
Requires PHP: 5.2.4
Stable tag: trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin presents issues stored in a Jira instance.

## Description

Use this plugin in order to easily present in your posts Jira issues dynamically from a Jira instance.

Features:
* Maximum flexibility by presenting issues as returned by a Jira filter. Write the filter by hand or copy one from Jira
* Pick the fields that you wish to present in the post
* Issues are presented in a table. Add one or more filters in the normal post by placing them between markers [JIRA] and [/JIRA]
* The issue key is a link to the issue in the Jira instance

## Installation

In order to install the plugin you need to follow these steps:

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Bridget to Jira screen to configure the plugin

The plugin settings require the below informations:
* The Jira endpoint. This is needed in order to communicate with Jira. For instance if your Jira instance is cloud hosted then the endpoint is just the Jira address. Example: if your Jira site is: https://mycompany.atlassian.net than this is exactly what you should input in the endpoint field
* Username: this is the username of the issue whose issues will be presented
* Password: this is a basic authentication token needed in order to communicate with Jira. Instructions on how to generate this token can be found here: https://confluence.atlassian.com/cloud/api-tokens-938839638.html

Note that all the setting information is stored locally on your wordpress server and can be transmitted only to *your* Jira instance for authentication purposes.

## Use

### As shortcode.

The plugin publishes shortcode "bridge-to-jira-filter". Execution of this shortcode results in a
table of JIRA issues.

The shortcode has as parameters:
* attribute: "fields" -> contains a comma-separated list of JIRA field keys (like: "summary", "status") that will
be shown as columns in the resulting table;
* content: the actual JQL filter that is sent to JIRA for evaluation. If empty then all the JIRA issues are returned.

Example:

* shortcode below shows summary and status for all issues:
```
[bridge-to-jira-filter fields="summary,status"][/bridge-to-jira-filter]
```
* shortcode below shows summary and status but only for the issues which have a status Done:
```
[bridge-to-jira-filter fields="summary,status"]status = Done[/bridge-to-jira-filter]
```

## Frequently Asked Questions

### Where are my sensitive Jira authentication information stored

Uniquely on your Wordpress instance.

### What kind of Jira authentication methods can be used?

Only the basic token authentication, the one that Jira recommends.

### Is there any support available for this plugin?

You are welcome to change this plugin in any way you like as stated by the GPL2 license. If interested in paid support then contact the company that published it [Snow Line Software](https://snowlinesoftware.com)

## Changelog

### 2.0
Adding the "bridge-to-jira" shortcode.

### 1.0
Initial version.

## Upgrade Notice

### 1.0
Initial version.

## Screencast

Here's below a video demonstration of the plugin features [here](https://www.youtube.com/embed/P4o_qTo6FxQ)

