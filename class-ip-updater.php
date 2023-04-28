<?php

namespace IP;

// Don't define the class twice
if ( class_exists( '\IP\Updater' ) ) return;

/**
 * Inverse Paradox Updater Class
 * 
 * This class provides native WordPress update functionality to internal plugins. 
 * Updated plugin information is read from a remote manifest in JSON format. 
 * The response from the remote server is cached in a transient for 24 hours. 
 * When an update is found, the user can proceed using the standard WordPress 
 * methods for plugin updates. To use this functionality in a custom plugin, 
 * copy the class-ip-updater.php file into your plugin and instantiate the 
 * class from your main plugin file.
 * 
 * Example usage:
 * $ip_updater = new IP\Updater( plugin_basename( __FILE__ ), '1.7.2', 'https://www.inverseparadox.com/test-manifest.json' );
 * 
 * @author Erik Teichmann <erik@inverseparadox.net>
 * @version 1.0.0
 * @see https://anchor.host/using-github-to-self-host-updates-for-wordpress-plugins/
 */
class Updater {

    public $plugin_basename;
    public $plugin_slug;
    public $version;
    public $cache_key;
    public $cache_allowed;
	public $manifest_url;

	/**
	 * Initialize the Updater
	 *
     * @param string $plugin_basename Basename of the plugin file
	 * @param string $version Current version of the plugin
	 * @param string $manifest_url URL to the manifest JSON
	 */
    public function __construct( $plugin_basename, $version, $manifest_url = '' ) {

        // Set up the configuration for the updater
        $this->plugin_basename = $plugin_basename;
        $this->plugin_slug     = dirname( $plugin_basename ); //dirname ( plugin_basename( __DIR__ ) );
        $this->version         = $version;
        $this->cache_key       = $this->plugin_slug . '_updater';
        $this->cache_allowed   = true;
		$this->manifest_url    = ! empty( $manifest_url ) ? $manifest_url : 'https://www.inverseparadox.com/wp-json/ip-plugin/v1/manifest/' . $this->plugin_slug;

        // Set IP_DEV_MODE to disable SSL checks and response caching
        if ( defined( 'IP_DEV_MODE' ) ) {
            add_filter('https_ssl_verify', '__return_false');
            add_filter('https_local_ssl_verify', '__return_false');
            add_filter('http_request_host_is_external', '__return_true');
            $this->cache_allowed = false;
        }

		// Show the plugin info on the plugins listing
        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        // Retrieve the update
		add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        // Clear the update transient
		add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );

    }

	/**
	 * Perform the remote request to check for updates
	 * Sets a transient with expiration of one day
	 *
	 * @return object|false Remote request response on success, false on failure
	 */
    public function request(){

		// First check for a cached response
        $remote = get_transient( $this->cache_key );

		// Check if we need to perform a new request
        if( false === $remote || ! $this->cache_allowed ) {

			// Run the remote request to get the manifest
            $remote = wp_remote_get( $this->manifest_url, [
                    'timeout' => 10,
                    'headers' => [
                        'Accept' => 'application/json'
                    ]
                ]
            );

			// Handle errors
            if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
                return false;
            }

			// Cache the request result
            set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );
        }

		// Decode the JSON and return as an array
        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

    }

	/**
	 * Get info to display on the plugins screen
	 *
	 * @param false|object|array $response Information from the manifest
	 * @param string $action Action being performed
	 * @param object $args
	 * @return void
	 */
    function info( $response, $action, $args ) {

		// Do nothing if we're not getting plugin information right now
        if ( 'plugin_information' !== $action ) {
            return $response;
        }

        // Do nothing if it is not our plugin
        if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
            return $response;
        }

        // Get updates
        $remote = $this->request();

		// If update failed, carry on
        if ( ! $remote ) {
            return $response;
        }

		// Set up new data to show on the info screen
        $response = new \stdClass();

        $response->name           = $remote->name;
        $response->slug           = $remote->slug;
        $response->version        = $remote->version;
        $response->tested         = $remote->tested;
        $response->requires       = $remote->requires;
        $response->author         = $remote->author;
        $response->author_profile = $remote->author_profile;
        $response->donate_link    = $remote->donate_link;
        $response->homepage       = $remote->homepage;
        $response->download_link  = $remote->download_url;
        $response->trunk          = $remote->download_url;
        $response->requires_php   = $remote->requires_php;
        $response->last_updated   = $remote->last_updated;

        $response->sections = [
            'description'  => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog'    => $remote->sections->changelog
        ];

        if ( ! empty( $remote->banners ) ) {
            $response->banners = [
                'low'  => $remote->banners->low,
                'high' => $remote->banners->high
            ];
        }

		return $response;

    }

	/**
	 * Check for plugin update if required
	 *
	 * @param object $transient The cached request
	 * @return object Data from manifest
	 */
    public function update( $transient ) {

		// If the cached data is valid, return that
        if ( empty($transient->checked ) ) {
            return $transient;
        }

		// Run a request
        $remote = $this->request();

		// If the local version is older, show the updated plugin info
        if ( $remote && version_compare( $this->version, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {

			$response              = new \stdClass();
            $response->slug        = $this->plugin_slug;
            $response->plugin      = $this->plugin_basename;
            $response->new_version = $remote->version;
            $response->tested      = $remote->tested;
            $response->package     = $remote->download_url;

            $transient->response[ $response->plugin ] = $response;

        }
		
		return $transient;

    }

	/**
	 * Clear the update information after the upgrade has been performed
	 *
	 * @param WP_Upgrader $upgrader Instance of WP_Upgrader
	 * @param array $options Array of bulk item update data
	 * @return void
	 */
    public function purge( $upgrader, $options ) {

        if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options[ 'type' ] ) {
            // Clean the cache when new plugin version is installed
            delete_transient( $this->cache_key );
        }

    }

}