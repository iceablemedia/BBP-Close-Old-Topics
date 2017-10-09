<?php
/**
 * Plugin to close old bbPress topics automatically when they are older than a set period of time.
 *
 * @link              http://www.iceable.com/
 * @since             1.0.0
 * @package           Bbp_Close_Old_Topics
 *
 * Plugin Name:       BBP Close Old Topics
 * Plugin URI:        https://github.com/iceablemedia/BBP-Close-Old-Topics
 * Description:       bbPress extension to close old topics automatically when they are older than an admin-defined period of time, from one week to one year. Old topics can be "soft-closed" on the fly only, or actually closed in the database.
 * Version:           1.0.0
 * Author:            Mathieu Sarrasin - Iceable Media
 * Author URI:        http://www.iceable.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       iceable-bbp-close-old-topics
 * Domain Path:       /languages
 *
 * Copyright 2017 Mathieu Sarrasin - Iceable Media
 *
 * BBP Close old Topics is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * bbPress Close old Topics is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with bbPress Close old Topics. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) :
	die;
endif;

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Bbp_Close_Old_Topics
 * @author     Mathieu Sarrasin - Iceable Media
 */
class Iceable_Bbp_Close_Old_Topics {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The constructor for our main class.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'iceable-bbp-close-old-topics';
		$this->version = '1.0.0';

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Loads the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			$this->get_plugin_name(),
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Hooks to 'the_posts' to check for old topics on the fly in queries.
	 *
	 * @since    1.0.0
	 * @param    array  $posts    Array of post objects.
	 * @param    object $query    WP_Query object.
	 * @return   array  $posts    Filtered array of post objects.
	 */
	public function filter_the_posts( $posts, $query ) {

		// Bail if $posts array is empty.
		if ( empty( $posts ) ) :
			return $posts;
		endif;

		// Bail if this is not a query for topics.
		if ( bbp_get_topic_post_type() !== $posts[0]->post_type ) :
			return $posts;
		endif;

		// Check all topics in the query.
		foreach ( $posts as $key => $topic ) :

			// Is topic already closed?
			if ( bbp_get_closed_status_id() === $topic->post_status ) :
				continue;
			endif;

			// Get topic ID.
			$topic_id = bbp_get_topic_id( $topic->ID );

			// Should this topic be closed?
			$maybe_close_topic = $this->maybe_close_topic( $topic_id );

			if ( $maybe_close_topic ) :
				// Alter status in the current array of posts (soft closing).
				$posts[ $key ]->post_status = bbp_get_closed_status_id();
			endif;

		endforeach;

		return $posts;

	}

	/**
	 * Hooks to 'bbp_get_topic_status' to filter old posts that should be closed.
	 *
	 * @since    1.0.0
	 * @param    string $status      Status of the current topic.
	 * @param    int    $topic_id    The topic id.
	 * @return   string $status      Filtered status of the current topic.
	 */
	public function filter_topic_status( $status, $topic_id ) {

		// Is topic already closed?
		if ( bbp_get_closed_status_id() === $status ) :
			return $status;
		endif;

		// Validate topic id.
		$topic_id = bbp_get_topic_id( $topic_id );

		// Make sure this is a topic.
		if ( ! bbp_is_topic( $topic_id ) ) :
			return $status;
		endif;

		// Should this topic be closed?
		$maybe_close_topic = $this->maybe_close_topic( $topic_id );

		if ( $maybe_close_topic ) :
			// Changed current status to closed (soft closing).
			$status = bbp_get_closed_status_id();
		endif;

		return $status;

	}

	/**
	 * Compare topic freshness to time period setting and decide whether topic should be closed.
	 *
	 * @since    1.0.0
	 * @param    int $topic_id       ID of the topic.
	 * @return   bool                True if the topic should be closed
	 */
	public function maybe_close_topic( $topic_id ) {

		// Bail if topic closing is disabled.
		if ( ! $this->close_topics() ) :
			return false;
		endif;

		// Get timestamp of last activity of the topic.
		$last_active = strtotime( get_post_field( 'post_date', bbp_get_topic_last_active_id( $topic_id ) ) );

		// Compare last active timestamp with defined time period.
		if ( $last_active < strtotime( '-' . $this->old_topic_age_setting() . ' days' ) ) :

			// Maybe hard close topic.
			if ( $this->hard_close_topics() ) :
				bbp_close_topic( $topic_id );
			endif;

			return true;

		else :

			return false;

		endif;

	}

	/**
	 * Checks if auto closing is enabled.
	 *
	 * @since    1.0.0
	 * @param    bool $default    Default option value.
	 * @return   bool
	 */
	public function close_topics( $default = true ) {

		return (bool) get_option( '_iceable_bbp_close_old_topics', $default );

	}

	/**
	 * Checks if "hard close" setting is enabled.
	 *
	 * @since    1.0.0
	 * @param    bool $default    Default option value.
	 * @return   bool
	 */
	public function hard_close_topics( $default = false ) {

		return (bool) get_option( '_iceable_bbp_hard_close_old_topics', $default );

	}

	/**
	 * Check user defined time period before closing topics.
	 *
	 * @since    1.0.0
	 * @param    int $default    Default option value.
	 * @return   int             Current option: time period in number of days.
	 */
	public function old_topic_age_setting( $default = 365 ) {

		return (int) get_option( '_iceable_bbp_close_old_topics_age', $default );

	}

	/**
	 * Adds our setting fields to bbPress settings.
	 *
	 * @since    1.0.0
	 * @param    array $settings    The settings fields for bbPress settings.
	 * @return   array $settings
	 */
	public function settings_fields( $settings = array() ) {

		// Add the close topics option and callback to the bbPress settings array.
		$settings['bbp_settings_features']['_iceable_bbp_close_old_topics'] = array(
			'title'             => __( 'Close Old Topics', 'iceable-bbp-close-old-topics' ),
			'callback'          => array( $this, 'setting_callback_close_old_topics' ),
			'sanitize_callback' => 'intval',
			'args'              => array(),
		);

		// Add the age option and callback to the bbPress settings array.
		$settings['bbp_settings_features']['_iceable_bbp_close_old_topics_age'] = array(
			'sanitize_callback' => 'intval',
			'args'              => array(),
		);

		// Add the hard close topics option and callback to the bbPress settings array.
		$settings['bbp_settings_features']['_iceable_bbp_hard_close_old_topics'] = array(
			'title'             => __( 'Hard Close Old Topics', 'iceable-bbp-close-old-topics' ),
			'callback'          => array( $this, 'setting_callback_hard_close_old_topics' ),
			'sanitize_callback' => 'intval',
			'args'              => array(),
		);

		return $settings;

	}

	/**
	 * Output HTML for our option fields in bbPress admin settings.
	 *
	 * @since  1.0.0
	 */
	public function setting_callback_close_old_topics() {

		// Define age options.
		$age_options = array(
			'7'   => __( '1 week',   'iceable-bbp-close-old-topics' ),
			'14'  => __( '2 weeks',  'iceable-bbp-close-old-topics' ),
			'30'  => __( '1 month',  'iceable-bbp-close-old-topics' ),
			'60'  => __( '2 months', 'iceable-bbp-close-old-topics' ),
			'90'  => __( '3 months', 'iceable-bbp-close-old-topics' ),
			'180' => __( '6 months', 'iceable-bbp-close-old-topics' ),
			'365' => __( '1 year',   'iceable-bbp-close-old-topics' ),
		);

		// Get current age setting.
		$current_age_setting = $this->old_topic_age_setting();

		?>
		<label for="_iceable_bbp_close_old_topics">
			<input name="_iceable_bbp_close_old_topics" id="_iceable_bbp_close_old_topics" type="checkbox" value="1"
			<?php
			checked( $this->close_topics() );
			bbp_maybe_admin_setting_disabled( '_iceable_bbp_close_old_topics' );
			?>
			/>
			<?php esc_html_e( 'Close topics on-the-fly when they are older than', 'iceable-bbp-close-old-topics' ); ?>
		</label>

		<label for="_iceable_bbp_close_old_topics_age">
			<select name="_iceable_bbp_close_old_topics_age" id="_iceable_bbp_close_old_topics_age" <?php bbp_maybe_admin_setting_disabled( '_iceable_bbp_close_old_topics_age' ); ?>>
			<?php foreach ( $age_options as $value => $display ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_age_setting ); ?>><?php echo esc_html( $display ); ?></option>
			<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Output HTML for our "hard close" option field in bbPress admin settings.
	 *
	 * @since  1.0.0
	 */
	public function setting_callback_hard_close_old_topics() {

		?>
		<label for="_iceable_bbp_hard_close_old_topics">
			<input name="_iceable_bbp_hard_close_old_topics" id="_iceable_bbp_hard_close_old_topics" type="checkbox" value="1"
			<?php
			checked( $this->hard_close_topics() );
			bbp_maybe_admin_setting_disabled( '_iceable_bbp_hard_close_old_topics' );
			?>
			/>
			<?php esc_html_e( 'Actually close old topics in the database (instead of merely closing them on the fly)', 'iceable-bbp-close-old-topics' ); ?>
		</label>
		<?php

	}

	/**
	 * Adds a "settings" link under the plugin's name on the Plugins page.
	 *
	 * @since     1.0.0
	 * @param     array $links    Array of links.
	 * @return    array $links    Modified array of links.
	 */
	public function add_plugin_actions_link( $links ) {

		$links = array_merge( $links, array(
			'<a href="' . esc_url( admin_url( '/options-general.php?page=bbpress' ) ) . '">' . __( 'Settings', 'iceable-bbp-close-old-topics' ) . '</a>',
		) );

		return $links;

	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since     1.0.0
	 */
	public function run() {

		// Loads the plugin text domain for translation.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Filters queries to check for old topics on the fly.
		add_filter( 'the_posts', array( $this, 'filter_the_posts' ), 10, 2 );

		// Filters bbp topic status.
		add_filter( 'bbp_get_topic_status', array( $this, 'filter_topic_status' ), 10, 2 );

		// Adds setting fields to bbPress admin settings.
		add_filter( 'bbp_admin_get_settings_fields', array( $this, 'settings_fields' ) );

		// Adds link to the settings page from the Plugins page.
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_actions_link' ) );

	}

}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function iceable_run_bbp_close_old_topics() {

	if ( class_exists( 'bbPress' ) ) :
		$plugin = new Iceable_Bbp_Close_Old_Topics();
		$plugin->run();
	endif;

}
// Hooks to 'bbp_includes' so this plugin is only kicked off along with bbPress.
add_action( 'bbp_includes', 'iceable_run_bbp_close_old_topics' );
