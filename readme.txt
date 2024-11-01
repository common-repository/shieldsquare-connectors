=== ShieldSquare - Real-time Bot Management Solution for Web Apps, Mobile & APIs ===
Contributors: ShieldSquare
Tags: bot detection, scrapping protection, crawler protection, security, bot mitigation
Requires at least: 4.0.0
Tested up to: 4.9.7
Requires PHP: 5.2.4
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ShieldSquare plugin provides users access to Dashboards to follow up on traffic and understand bot activity on their websites.


== Description ==
On integrating the plugin, the CMS Plugin will start making API calls to ShieldSquare solution and the response received is in the form of a response code. The response code directly indicates the action to be taken based on the incoming request. All API calls made to ShieldSquare, (in monitor mode) by default would be asynchronous in nature, based on the compatibility of the website platform.

== Installation ==
This section describes how to install the plugin and configure it:
1. Register with ShieldSquare and get SID [https://www.shieldsquare.com/signup/]
2. install the plugin through the WordPress 'Plugins' screen or upload the plugin zip file to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Update the default configurations in shieldsquare settings and save the configurations


== Requirements ==
1. WordPress version >= 4.0.0
2. PHP version >= 5.2.4 with "curl" extension.
3. cURL extension: http://php.net/manual/en/curl.installation.php


== Online Integration Guide ==
The online integration guide can be found at http://integration.shieldsquare.com.
For further assistance, please reach us at support@shieldsquare.com

== Additional Details ==
1. If log is enabled then log file gets generated in '/tmp/shieldsquare.log'.
2. Response Codes:
		0  -> Valid request
		2  -> Display captcha code
		3  -> Blocked
		4  -> Feed Fake data
		-1 -> Error page


== Changelog ==
= 1.0.0 =
Release date: Jan 6th, 2018
* Added VersionB features

= 1.1.0 =
Release date: 05-03-2018
* Request filter
* Skip URLs
* Configurable CAPTCHA-Block domain
