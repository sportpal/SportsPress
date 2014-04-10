<?php
/**
 * Setup menus in WP admin.
 *
 * @author 		ThemeBoy
 * @category 	Admin
 * @package 	SportsPress/Admin
 * @version     0.7
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SP_Admin_Menus' ) ) :

/**
 * SP_Admin_Menus Class
 */
class SP_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_filter( 'menu_order', array( $this, 'menu_order' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
	}

	/**
	 * Add menu item
	 */
	public function admin_menu() {
		global $menu;

	    if ( current_user_can( 'manage_sportspress' ) )
	    	$menu[] = array( '', 'read', 'separator-sportspress', '', 'wp-menu-separator sportspress' );

		$main_page = add_menu_page( __( 'SportsPress Settings', 'sportspress' ), __( 'SportsPress', 'sportspress' ), 'manage_sportspress', 'sportspress', array( $this, 'settings_page' ), null, '51.5' );
	}

	/**
	 * Highlights the correct top level admin menu item for post type add screens.
	 *
	 * @access public
	 * @return void
	 */
	public function menu_highlight() {
		global $typenow, $submenu;
		if ( in_array( $typenow, array( 'sp_result', 'sp_outcome', 'sp_column', 'sp_performance', 'sp_metric' ) ) )
			$this->highlight_admin_menu( 'sportspress', 'edit.php?post_type=' . $typenow );
		elseif ( $typenow == 'sp_calendar' )
			$this->highlight_admin_menu( 'edit.php?post_type=sp_event', 'edit.php?post_type=sp_calendar' );
		elseif ( $typenow == 'sp_table' )
			$this->highlight_admin_menu( 'edit.php?post_type=sp_team', 'edit.php?post_type=sp_table' );
		elseif ( $typenow == 'sp_list' )
			$this->highlight_admin_menu( 'edit.php?post_type=sp_player', 'edit.php?post_type=sp_list' );
		elseif ( $typenow == 'sp_directory' )
			$this->highlight_admin_menu( 'edit.php?post_type=sp_staff', 'edit.php?post_type=sp_directory' );

		if ( isset( $submenu['sportspress'] ) && isset( $submenu['sportspress'][0] ) && isset( $submenu['sportspress'][0][0] ) ) {
			$submenu['sportspress'][0][0] = __( 'Settings', 'sportspress' );
		}
	}

	/**
	 * Reorder the SP menu items in admin.
	 *
	 * @param mixed $menu_order
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array
		$sportspress_menu_order = array();

		// Get the index of our custom separator
		$sportspress_separator = array_search( 'separator-sportspress', $menu_order );

		// Get index of menu items
		$sportspress_event = array_search( 'edit.php?post_type=sp_event', $menu_order );
		$sportspress_team = array_search( 'edit.php?post_type=sp_team', $menu_order );
		$sportspress_player = array_search( 'edit.php?post_type=sp_player', $menu_order );
		$sportspress_staff = array_search( 'edit.php?post_type=sp_staff', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :

			if ( ( ( 'sportspress' ) == $item ) ) :
				$sportspress_menu_order[] = 'separator-sportspress';
				$sportspress_menu_order[] = $item;
				$sportspress_menu_order[] = 'edit.php?post_type=sp_event';
				$sportspress_menu_order[] = 'edit.php?post_type=sp_team';
				$sportspress_menu_order[] = 'edit.php?post_type=sp_player';
				$sportspress_menu_order[] = 'edit.php?post_type=sp_staff';
				unset( $menu_order[$sportspress_separator] );
				unset( $menu_order[$sportspress_event] );
				unset( $menu_order[$sportspress_team] );
				unset( $menu_order[$sportspress_player] );
				unset( $menu_order[$sportspress_staff] );
			elseif ( !in_array( $item, array( 'separator-sportspress' ) ) ) :
				$sportspress_menu_order[] = $item;
			endif;

		endforeach;

		// Return order
		return $sportspress_menu_order;
	}

	/**
	 * custom_menu_order
	 * @return bool
	 */
	public function custom_menu_order() {
		if ( ! current_user_can( 'manage_sportspress' ) )
			return false;
		return true;
	}

	/**
	 * Clean the SP menu items in admin.
	 */
	public function menu_clean() {
		global $menu, $submenu;

		// Find where our separator is in the menu
		foreach( $menu as $key => $data ):
			if ( is_array( $data ) && array_key_exists( 2, $data ) && $data[2] == 'edit.php?post_type=sp_separator' )
				$separator_position = $key;
		endforeach;

		// Swap our separator post type with a menu separator
		if ( isset( $separator_position ) ):
			$menu[ $separator_position ] = array( '', 'read', 'separator-sportspress', '', 'wp-menu-separator sportspress' );
		endif;

	    // Remove "Venues" and "Positions" links from Media submenu
		if ( isset( $submenu['upload.php'] ) ):
			$submenu['upload.php'] = array_filter( $submenu['upload.php'], array( $this, 'remove_venues' ) );
			$submenu['upload.php'] = array_filter( $submenu['upload.php'], array( $this, 'remove_positions' ) );
		endif;

	    // Remove "Leagues" and "Seasons" links from Events submenu
		if ( isset( $submenu['edit.php?post_type=sp_event'] ) ):
			$submenu['edit.php?post_type=sp_event'] = array_filter( $submenu['edit.php?post_type=sp_event'], array( $this, 'remove_leagues' ) );
			$submenu['edit.php?post_type=sp_event'] = array_filter( $submenu['edit.php?post_type=sp_event'], array( $this, 'remove_seasons' ) );
		endif;

	    // Remove "Leagues" and "Seasons" links from Players submenu
		if ( isset( $submenu['edit.php?post_type=sp_player'] ) ):
			$submenu['edit.php?post_type=sp_player'] = array_filter( $submenu['edit.php?post_type=sp_player'], array( $this, 'remove_leagues' ) );
			$submenu['edit.php?post_type=sp_player'] = array_filter( $submenu['edit.php?post_type=sp_player'], array( $this, 'remove_seasons' ) );
		endif;

	    // Remove "Leagues" and "Seasons" links from Staff submenu
		if ( isset( $submenu['edit.php?post_type=sp_staff'] ) ):
			$submenu['edit.php?post_type=sp_staff'] = array_filter( $submenu['edit.php?post_type=sp_staff'], array( $this, 'remove_leagues' ) );
			$submenu['edit.php?post_type=sp_staff'] = array_filter( $submenu['edit.php?post_type=sp_staff'], array( $this, 'remove_seasons' ) );
		endif;
	}

	/**
	 * Init the settings page
	 */
	public function settings_page() {
		include_once( 'class-sp-admin-settings.php' );
		SP_Admin_Settings::output();
	}

	public function remove_add_new( $arr = array() ) {
		return $arr[0] != __( 'Add New', 'sportspress' );
	}

	public function remove_leagues( $arr = array() ) {
		return $arr[0] != __( 'Leagues', 'sportspress' );
	}

	public function remove_positions( $arr = array() ) {
		return $arr[0] != __( 'Positions', 'sportspress' );
	}

	public function remove_seasons( $arr = array() ) {
		return $arr[0] != __( 'Seasons', 'sportspress' );
	}

	public function remove_venues( $arr = array() ) {
		return $arr[0] != __( 'Venues', 'sportspress' );
	}

	public static function highlight_admin_menu( $p = 'sportspress', $s = 'sportspress' ) {
		global $parent_file, $submenu_file;
		$parent_file = $p;
		$submenu_file = $s;
	}
}

endif;

return new SP_Admin_Menus();