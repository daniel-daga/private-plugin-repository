=== Plugin Name ===
Donate link: http://gruetzmacher.at
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides an API for updating private Wordpress plugins

== Description ==

Based on https://github.com/omarabid/Self-Hosted-WordPress-Plugin-repository

== Installation ==

Extract into plugin folder.
Place the plugin folders into the uploads/repository folder. In the plugin folder, provide the main plugin file (plugin-slug.php) and a zip with the complete plugin (plugin-slug.zip). So in total 2 files are needed:

uploads/repository/plugin-slug/plugin-slug.php
uploads/repository/plugin-slug/plugin-slug.zip

The script will compare the installed plugin version with the one provided in the plugin-slug.php

Use this to compile plugin folders to the needed format: https://github.com/daniel-daga/private-plugin-repository-wrapper

To Do:
- Username and license key are hardcoded for now, add options, licensing system?