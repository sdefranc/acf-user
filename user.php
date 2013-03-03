<?php

/*
 *	Advanced Custom Fields - User Relationship Field
 *	
 *	Documentation: 
 *
 *	Create a user field type based on the relationship field
 *
 */
 
 
class acf_field_user extends acf_Field
{

	/*--------------------------------------------------------------------------------------
	*
	*	Constructor
	*	- This function is called when the field class is initalized on each page.
	*	- Here you can add filters / actions and setup any other functionality for your field
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function __construct($parent)
	{
		// do not delete! ok!
    	parent::__construct($parent);
    	
    	// set name / title
    	$this->name = 'user'; // variable name 
		$this->title = __("User",'acf'); // field label displayed in edit screens

        // actions
        add_action('wp_ajax_acf_get_user_results', array(__CLASS__, 'acf_get_user_results'));
   	}

	

    /*--------------------------------------------------------------------------------------
    *
    *   acf_get_user_results
    *
    *   @author Sam De Francesco
    *   @description: Generates items for Left column relationship results
    *   @created: 3/03/2013
    * 
    *-------------------------------------------------------------------------------------*/

    function acf_get_user_results()
    {
        global $wpdb;

        // vars
        $options = array(
            'roles'        => '',
            'paged'        => 0,
            'orderby'      => 'login',
            'order'        => 'ASC',
            'number'       => '15',
            'count_total'  => false,
            's'            => '',
            'lang'         => false,
            'fields'       => 'all_with_meta',
            'field_name'   => '',
            'field_key'    => ''
        );

        $options = array_merge($options, $_POST);


        if( $options['paged'] ) {
            $options['offset']  = ($options['paged'] - 1) * $options['number'];
        }

        // WPML
        if( $options['lang'] )
        {
            global $sitepress;

            $sitepress->switch_lang( $options['lang'] );
        }

        // convert types
        $options['roles'] = explode(',', $options['roles']);

        // load all users types by default
        if( !$options['roles'] || !is_array($options['roles']) || $options['roles'][0] == "" )
        {
            $options['roles'] = array('');
        }


        // search
        if( $options['s'] )
        {
            $options['search'] = '*' . $options['s'] . '*';
        }

        unset( $options['s'] );


        // filters
        $options = apply_filters('acf_user_query', $options);
        $options = apply_filters('acf_user_query-' . $options['field_name'] , $options);
        $options = apply_filters('acf_user_query-' . $options['field_key'], $options);


        $results = false;
        $results = apply_filters('acf_user_results', $results, $options);
        $results = apply_filters('acf_user_results-' . $options['field_name'] , $results, $options);
        $results = apply_filters('acf_user_results-' . $options['field_key'], $results, $options);


        if( ! $results )
        {
            // load the users for each role
            $users = array();
            foreach ($options['roles'] as $role) {
                $options['role'] = $role;
                $users = array_merge($users, get_users( $options ));
            }

            if( $users )
            {
                foreach( $users  as $user )
                {
                    // right aligned info
                    $title = '<span class="user-item-info">';

                        // $title .= $user->display_name;
                        $title .= $user->user_login;

                        // WPML
                        if( $options['lang'] )
                        {
                            $title .= ' (' . $options['lang'] . ')';
                        } 

                    $title .= '</span>';

                    // find title. 
                    $title .= $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')';

                    $title = apply_filters('acf_user_result', $title);
                    $title = apply_filters('acf_user_result-' . $options['field_name'] , $title);
                    $title = apply_filters('acf_user_result-' . $options['field_key'], $title);


                    $results .= '<li><a href="/wp-admin/user-edit.php?user_id=' . $user->ID . '" data-user_id="' . $user->ID . '">' . $title .  '<span class="acf-button-add"></span></a></li>';
                }
            }
        }

        echo $results;
        die();

    }

	/*--------------------------------------------------------------------------------------
	*
	*	create_options
	*	- called from core/field_meta_box.php to create extra options
	*
	*	@params
	*	- $key (int) - the $_POST obejct key required to save the options to the field
	*	- $field (array) - the field object
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_options($key, $field)
	{
        // vars
        $defaults = array(
            'roles'     =>  '',
            'multiple'  =>  ''
        );

        $field = array_merge($defaults, $field);


        // validate roles
        if( !is_array($field['roles']) )
        {
            $field['roles'] = array('');
        }

        $field['multiple'] = (int) $field['multiple'];

        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e("Filter by Role",'acf'); ?></label>
            </td>
            <td>
                <?php
                $choices = array(
                        '' => __("All",'acf')
                );

                $roles = get_editable_roles();
                foreach ( $roles as $role => $details ) {
                    // only translate the output not the value
                    $choices[$role] = translate_user_role( $details['name'] );
                }

                $this->parent->create_field(array(
                    'type'  =>  'select',
                    'name' => 'fields[' . $key . '][roles]',
                    'value' => $field['roles'],
                    'choices' => array($choices),
                    'optgroup' => true,
                    'multiple'  =>  1,
                ));
                ?>
            </td>
        </tr>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
            <td class="label">
                <label><?php _e( "Allow multiple users?", 'acf' ); ?></label>
            </td>
            <td>
                <?php
                $this->parent->create_field(  array(
                    'type' => 'radio',
                    'name' => 'fields[' . $key . '][multiple]',
                    'value' => $field['multiple'],
                    'choices' => array (
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'layout' => 'horizontal',
                ) );
                ?>
            </td>
        </tr>
        <?php
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	pre_save_field
	*	- this function is called when saving your acf object. Here you can manipulate the
	*	field object and it's options before it gets saved to the database.
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function pre_save_field($field)
	{
		// do stuff with field (mostly format options data)
		
		return parent::pre_save_field($field);
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	create_field
	*	- this function is called on edit screens to produce the html for this field
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function create_field($field)
	{
        // vars
        $defaults = array(
            'roles'     =>  '',
            'multiple'  =>  '',
            'max'       =>  99999,
        );

        $field = array_merge($defaults, $field);

        // validate types
        $field['multiple'] = (int) $field['multiple'];


        // row limit <= 0?
        if( $field['multiple'] == 0 ) {
            $field['max'] = 1;
        } 

        // load all roles by default
        if( !$field['roles'] || !is_array($field['roles']) || $field['roles'][0] == "" )
        {
            // $field['roles'] = $this->get_roles();
            $field['roles'] = array('');
        }

        ?>
<div class="acf_user" data-max="<?php echo $field['max']; ?>" data-s="" data-paged="1" data-roles="<?php echo implode(',', $field['roles']); ?>" <?php if( defined('ICL_LANGUAGE_CODE') ){ echo 'data-lang="' . ICL_LANGUAGE_CODE . '"';} ?>>

    <!-- Hidden Blank default value -->
    <input type="hidden" name="<?php echo $field['name']; ?>" value="" />

    <!-- Template for value -->
    <script type="text/html" class="tmpl-li">
    <li>
        <a href="#" data-user_id="{user_id}">{title}<span class="acf-button-remove"></span></a>
        <input type="hidden" name="<?php echo $field['name']; ?>[]" value="{user_id}" />
    </li>
    </script>
    <!-- / Template for value -->

    <!-- Left List -->
    <div class="user_left">
        <table class="widefat">
            <thead>
                <tr>
                    <th>
                        <label class="user_label" for="user_<?php echo $field['name']; ?>"><?php _e("Search",'acf'); ?>...</label>
                        <input class="user_search" type="text" id="user_<?php echo $field['name']; ?>" />
                        <div class="clear_user_search"></div>
                    </th>
                </tr>
            </thead>
        </table>
        <ul class="bl user_list">
            <li class="load-more">
                <div class="acf-loading"></div>
            </li>
        </ul>
    </div>
    <!-- /Left List -->

    <!-- Right List -->
    <div class="user_right">
        <ul class="bl user_list">
        <?php


        if( $field['value'] )
        {
            foreach( $field['value'] as $userid )
            {
                $user = get_userdata( $userid );

                // check that user exists (my have been trashed)
                if( !is_object($user) )
                {
                    continue;
                }


                // right aligned info
                $title = '<span class="user-item-info">';

                    $title .= $user->user_login;

                    // WPML
                    if( defined('ICL_LANGUAGE_CODE') )
                    {
                        $title .= ' (' . ICL_LANGUAGE_CODE . ')';
                    }

                $title .= '</span>';

                // add title. 
                $title .= $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')';

                echo '<li>
                    <a href="' . get_permalink($user->ID) . '" class="" data-user_id="' . $user->ID . '">' . $title . '<span class="acf-button-remove"></span></a>
                    <input type="hidden" name="' . $field['name'] . '[]" value="' . $user->ID . '" />
                </li>';


            }
        }

        ?>
        </ul>
    </div>
    <!-- / Right List -->

</div>
        <?php
		
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	admin_head
	*	- this function is called in the admin_head of the edit screen where your field
	*	is created. Use this function to create css and javascript to assist your 
	*	create_field() function.
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function admin_head()
	{

	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	admin_print_scripts / admin_print_styles
	*	- this function is called in the admin_print_scripts / admin_print_styles where 
	*	your field is created. Use this function to register css and javascript to assist 
	*	your create_field() function.
	*
	*	@author Sam De Francesco
	*	@since 3.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function admin_print_scripts()
	{
        wp_register_script('qcc-action-inputs', plugins_url( 'js/input-actions.js', __FILE__ ),  array('acf-input-actions'), false, false );
        wp_enqueue_script ('qcc-action-inputs' );
	}
	
	function admin_print_styles()
	{
        wp_register_style('qcc-input', plugins_url( 'css/input.css' , __FILE__ ), false, false, false );
        wp_enqueue_style ('qcc-input' );
	}

	
	/*--------------------------------------------------------------------------------------
	*
	*	update_value
	*	- this function is called when saving a post object that your field is assigned to.
	*	the function will pass through the 3 parameters for you to use.
	*
	*	@params
	*	- $post_id (int) - usefull if you need to save extra data or manipulate the current
	*	post object
	*	- $field (array) - usefull if you need to manipulate the $value based on a field option
	*	- $value (mixed) - the new value of your field.
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function update_value($post_id, $field, $value)
	{
		// do stuff with value
		
		// save value
		parent::update_value($post_id, $field, $value);
	}
	
	
	
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	get_value
	*	- called from the edit page to get the value of your field. This function is useful
	*	if your field needs to collect extra data for your create_field() function.
	*
	*	@params
	*	- $post_id (int) - the post ID which your value is attached to
	*	- $field (array) - the field object.
	*
	*	@author Sam De Francesco
	*	@since 2.2.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function get_value($post_id, $field)
	{
		// get value
		$value = parent::get_value($post_id, $field);
		
		// format value
		
		// return value
		return $value;		
	}
	
	
	/*--------------------------------------------------------------------------------------
	*
	*	get_value_for_api
	*	- called from your template file when using the API functions (get_field, etc). 
	*	This function is useful if your field needs to format the returned value
	*
	*	@params
	*	- $post_id (int) - the post ID which your value is attached to
	*	- $field (array) - the field object.
	*
	*	@author Sam De Francesco
	*	@since 3.0.0
	* 
	*-------------------------------------------------------------------------------------*/
	
	function get_value_for_api($post_id, $field)
	{
		// get value
		$value = $this->get_value($post_id, $field);
		
		// format value
		
		// return value
		return $value;

	}
	
}

