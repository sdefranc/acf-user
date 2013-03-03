<?php
/*
Plugin Name: Advanced Custom Fields: User Relationship Field
Plugin URI: https://github.com/sdefranc/acf-user
Description: Adds the user relationship field
Version: 0.0.1
Author: Sam De Francesco
Author URI: http://www.qibla.com/
License: GPL
Copyright: Sam De Francesco
*/


class acf_user_plugin
{
	var $settings;
	
	
	/*
	*  Constructor
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 23/06/12
	*/
	
	function __construct()
	{
		// vars
		$settings = array(
			'version' => '0.0.1',
			'basename' => plugin_basename(__FILE__),
		);
		
		// actions
		// V4: add_action('acf/register_fields', array(__CLASS__, 'register_fields'));
		add_action('init', array(__CLASS__, 'register_fields'));
	}
	
	
	/*
	*  register_fields
	*
	*  @description: 
	*  @since: 3.6
	*  @created: 31/01/13
	*/
	
	function register_fields()
	{
        // V4: include('user.php');
        if(function_exists('register_field')) {
            register_field('acf_field_user', dirname(__File__) . '/user.php');
        }
	}
}

new acf_user_plugin();

