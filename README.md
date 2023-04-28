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
3. `string $manifest_url` - This parameter sets the location of the `manifest.json` file that will be used to signal when a new update for your plugin is available. If omitted, this will default to `https://inverseparadox.com/ip-plugins/{plugin_slug}/manifest.json`. 

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