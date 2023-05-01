# Inverse Paradox Updater

The IP Updater Class is used to add native WordPress update functionality to custom-built or internal plugins.

## How to use

There are two components to the plugin updater -- the `IP\Updater` class you will include in your plugin, and the `manifest.json` file that will live on an external server and be updated when there is a new version of the plugin available.

### Include the code and run it in your plugin

1. Download `class-ip-updater.php` and put it in your plugin directory somewhere. 
2. In your main plugin file, include the class: `include 'inc/class-ip-updater.php';`
3. Instantiate the class from your main plugin file: 
```php
$ip_updater = new IP\Updater( plugin_basename( __FILE__ ), '1.7.2', 'https://www.inverseparadox.com/test-manifest.json' );
```

The `IP\Updater` class constructor has three parameters:

1. `string $plugin_basename` - This is the directory and filename of your plugin. This must be set to the main plugin file for WordPress to properly update your plugin. The easiest way to do this is to include the instantiator in the main plugin file, and use `plugin_basename( __FILE__ )` to get the basename. This can also be set manually with a string if necessary, eg `your-plugin-folder/main-file.php`. The directory component of the basename is used to set the cache key used for storing the plugin update transient.
2. `string $version` - This is the _current_ version of your plugin. This is used when comparing versions against the remote. Ideally this should be set using a constant or class property in your plugin, so when the plugin is updated we minimize the number of places the version number has to be bumped.
3. `string $manifest_url` **Optional** - This parameter sets the location of the `manifest.json` file that will be used to signal when a new update for your plugin is available. If omitted, this will default to `https://www.inverseparadox.com/wp-json/ip-plugin/v1/manifest/{plugin-slug}`. 

### Create a `manifest.json`

The manifest contains details about your plugin that will be displayed on the WordPress plugin updates page. Most importantly, this contains the latest version number of your plugin and the URL of the latest version in a .ZIP file. A sample manifest is included below:

```json
{
	"name" : "IP Sample Plugin",
	"slug" : "ip-sample-plugin",
	"author" : "<a href='https://www.inverseparadox.com'>Inverse Paradox</a>",
	"author_profile" : "https://profiles.wordpress.org/inverseparadox/",
	"version" : "1.0.0",
	"download_url" : "https://www.inverseparadox.com/wp-content/uploads/2023/04/ip-sample-plugin-1.0.0.zip",
	"requires" : "5.6",
	"tested" : "6.1",
	"requires_php" : "5.6",
	"added" : "2023-01-01 00:00:00",
	"last_updated" : "2023-04-28 14:10:00",
	"homepage" : "https://www.inverseparadox.com/ip-plugins/ip-sample-plugin",
	"sections" : {
		"description" : "Mauris dictumst nec ad quam tortor vulputate nullam pretium semper.",
		"installation" : "Nostra turpis tristique class sollicitudin imperdiet sociis venenatis dictumst et.",
		"changelog" : "<h4>v1.0.0 released on April 28, 2023</h4><ul><li>Feature: Class cras congue risus vehicula ipsum integer.</li><li>Fix: Laoreet egestas lectus viverra nullam ullamcorper.</li><li>Fix: Aliquet consectetur feugiat tellus natoque maecenas fames blandit tempus consequat.</li></ul>"
	},
	"banners" : {
		"low" : "https://www.inverseparadox.com/wp-content/uploads/2023/04/ip-sample-plugin-banner-772x250.webp",
		"high" : "https://www.inverseparadox.com/wp-content/uploads/2023/04/ip-sample-plugin-banner-1544x500.webp"
	}
}
```

### Create a IP Plugin post on inverseparadox.com

Instead of manually creating `manifest.json` files for every release, I've created a custom post type and some supporting code on the Inverse Paradox website. An `ip-plugin` post contains a description, images, tested-to versions, added/updated dates and metadata about the plugin. A repeater field on this post contains information on each version of the plugin, including version number, release date, and a changelog, as well as an uploader for the ZIP file that will be distributed to sites when updating.

1. Visit the dashboard and navigate to _IP Plugins > Add New_ and create a new plugin post.
2. Enter the plugin title and a description. The Block Editor content can contain any information, images, videos, etc. This will only be displayed on the IP website, not in the plugin updater. Consider this a landing page for information about the plugin.
3. Under the _IP Plugin_ metabox, fill in the information under **Plugin Details**. 
	1. **Plugin Slug** should match the folder name of the plugin being updated. 
	2. **Author** and **Author Profile** are filled by default to Inverse Paradox
	3. **Required WordPress Version** should be the earliest supported version of WordPress for the plugin. If a site requesting updates does not meet this minimum version, the update will not be downloaded.
	4. **Tested WordPress Version** will display a warning if the site requesting updates is newer than this version.
	5. **PHP Version Required** will prevent updates from being downloaded if the requesting site's PHP version is older.
	6. **Description** contains a brief plugin description that is displayed to the user in the WordPress Updater. 
	7. **Installation Instructions** are displayed on the WordPress updater. 
4. Under the _Images_ tab, upload images for the low- and high-res banners. These are displayed in the plugin updater when the user clicks the _More Info_ link.
5. Finally, the _Versions_ tab contains an ACF repeater with information on each release of the plugin.
	1. Click **Add Version** to add a new version.
	2. Enter the **Version** number for the plugin. This will be used for [version comparison](https://www.php.net/manual/en/function.version-compare.php) so make sure it follows the [Semantic Versioning](https://semver.org/) standart (eg "1.0.0"). 
	3. Under **Plugin ZIP** upload a ZIP compressed file for this version of the plugin. This ZIP file should extract to a single folder matching your plugin slug. Preferably, the ZIP file should be named as the plugin slug followed by the version number, eg `ip-sample-plugin-1.0.0.zip`
	4. Add entries under the **Changelog** for each change item. See [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) for some best practices on writing changelogs.

After publishing the post, the frontend page for the plugin will be displayed with the latest version available for download and the changelog, and a new `manifest.json` will be automatically created at the `ip-plugin` route of the WordPress REST API:

```
https://www.inverseparadox.com/wp-json/ip-plugin/v1/manifest/ip-sample-plugin
```

By default, the `IP\Updater` class will look for updates at the URL matching your plugin's slug. When an updated version is available, the WordPress Plugin Updater will show it as available, allowing the user to automatically update. In this process, the ZIP file for the current version will be downloaded and installed on the local server.