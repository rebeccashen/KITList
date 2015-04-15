<?php
final class Access_Ajax_Helper
{
    public static function init()
    {
        /*
         * AJAX calls.
         */
        add_action('wp_ajax_wpcf_access_save_settings', array(__CLASS__, 'wpcf_access_save_settings'));
		add_action('wp_ajax_wpcf_access_save_settings_section', array(__CLASS__, 'wpcf_access_save_settings_section'));
        add_action('wp_ajax_wpcf_access_ajax_reset_to_default',  array(__CLASS__, 'wpcf_access_ajax_reset_to_default'));
        add_action('wp_ajax_wpcf_access_suggest_user', array(__CLASS__, 'wpcf_access_wpcf_access_suggest_user_ajax'));
        add_action('wp_ajax_wpcf_access_ajax_set_level', array(__CLASS__, 'wpcf_access_ajax_set_level'));
        add_action('wp_ajax_wpcf_access_add_role', array(__CLASS__, 'wpcf_access_add_role_ajax'));
        add_action('wp_ajax_wpcf_access_delete_role', array(__CLASS__, 'wpcf_access_delete_role_ajax'));
		add_action('wp_ajax_wpcf_access_show_error_list', array(__CLASS__, 'wpcf_access_show_error_list_ajax'));
		add_action('wp_ajax_wpcf_access_add_new_group_form', array(__CLASS__, 'wpcf_access_add_new_group_form_ajax'));
		add_action('wp_ajax_wpcf_process_new_access_group', array(__CLASS__, 'wpcf_process_new_access_group_ajax'));
		add_action('wp_ajax_wpcf_process_modify_access_group', array(__CLASS__, 'wpcf_process_modify_access_group_ajax'));
		add_action('wp_ajax_wpcf_remove_group', array(__CLASS__, 'wpcf_remove_group_ajax'));
		add_action('wp_ajax_wpcf_remove_group_process', array(__CLASS__, 'wpcf_remove_group_process_ajax'));
		add_action('wp_ajax_wpcf_search_posts_for_groups', array(__CLASS__, 'wpcf_search_posts_for_groups_ajax'));
		add_action('wp_ajax_wpcf_remove_postmeta_group', array(__CLASS__, 'wpcf_remove_postmeta_group_ajax'));
		add_action('wp_ajax_wpcf_select_access_group_for_post', array(__CLASS__, 'wpcf_select_access_group_for_post_ajax'));
		add_action('wp_ajax_wpcf_process_select_access_group_for_post', array(__CLASS__, 'wpcf_process_select_access_group_for_post_ajax'));
		add_action('wp_ajax_wpcf_access_change_role_caps', array(__CLASS__, 'wpcf_access_change_role_caps_ajax'));
		add_action('wp_ajax_wpcf_process_change_role_caps', array(__CLASS__, 'wpcf_process_change_role_caps_ajax'));
		add_action('wp_ajax_wpcf_access_show_role_caps', array(__CLASS__, 'wpcf_access_show_role_caps_ajax'));
		add_action('wp_ajax_wpcf_create_new_cap', array(__CLASS__, 'wpcf_create_new_cap'));
		add_action('wp_ajax_wpcf_delete_cap', array(__CLASS__, 'wpcf_delete_cap'));

    }

    /**
     * Saves Access settings.
     */
    public static function wpcf_access_save_settings()
    {
        if (
            isset($_POST['_wpnonce']) &&
            wp_verify_nonce($_POST['_wpnonce'], 'wpcf-access-edit')
        )
        {
            //taccess_log($_POST['types_access']);

            $model = TAccess_Loader::get('MODEL/Access');

            //$isTypesActive = Access_Helper::wpcf_access_is_wpcf_active();

            $access_bypass_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses the same name for singular name and plural name. Access can't control access to this object. Please use a different name for the singular and plural names.", 'wpcf_access')."</p></div>";
            $access_conflict_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default Wordpress capabilities. Access can not manage this entity, try changing entity's name and / or slug", 'wpcf_access')."</p></div>";
            $access_notices='';
            $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
            $_taxonomies=Access_Helper::wpcf_object_to_array( $model->getTaxonomies() );

            //taccess_log($_taxonomies);

            // start empty
            $settings_access_types_previous = $model->getAccessTypes();
            $settings_access_taxs_previous = $model->getAccessTaxonomies();
            $settings_access_types = array();
            $settings_access_taxs = array();

			// Post Types
			$custom_data = array();

            if (!empty($_POST['types_access_error_type']['types']))
            {
                foreach ($_POST['types_access_error_type']['types'] as $type => $data)
                {
                     $settings_access_types['_custom_read_errors'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types);
            }
            if (!empty($_POST['types_access_error_value']['types']))
            {
                foreach ($_POST['types_access_error_value']['types'] as $type => $data)
                {
                     $settings_access_types['_custom_read_errors_value'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types);
            }

			//Archives
			if (!empty($_POST['types_access_archive_error_type']['types']))
            {
                foreach ($_POST['types_access_archive_error_type']['types'] as $type => $data)
                {
                     $settings_access_types['_archive_custom_read_errors'][$type] = $data;
                }

                $model->updateAccessTypes($settings_access_types);
            }
            if (!empty($_POST['types_access_archive_error_value']['types']))
            {
                foreach ($_POST['types_access_archive_error_value']['types'] as $type => $data)
                {
                     $settings_access_types['_archive_custom_read_errors_value'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types);
            }

            // Post Types
            if (!empty($_POST['types_access']['types']))
            {
                $caps = Access_Helper::wpcf_access_types_caps_predefined();
                foreach ($_POST['types_access']['types'] as $type => $data)
                {
                    $mode = isset($data['mode']) ? $data['mode'] : 'not_managed';
                    // Use saved if any and not_managed
                    if ( isset($data['mode']) && $data['mode'] == 'not_managed'
                            && isset($settings_access_types_previous[$type])) {
                        $data = $settings_access_types_previous[$type];
                    }
                    $data['mode'] = $mode;
					if ( strpos($type, 'wpcf-custom-group-') === 0 && isset($_POST['groupvalue-'.$type]) ){
						 $data['title'] = $_POST['groupvalue-'.$type];
					}
                    $data['permissions'] = Access_Helper::wpcf_access_parse_permissions($data, $caps);
                    //taccess_log($data['permissions']);

                    if (
                        /*!Access_Helper::wpcf_is_object_valid('type', $_post_types[$type])*/
                        isset($_post_types[$type]['__accessIsNameValid']) && !$_post_types[$type]['__accessIsNameValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_bypass_template,__('Post Type','wpcf_access'),$_post_types[$type]['labels']['singular_name']);
                    }

                    if (
                        /*isset($_post_types[$type]['cap']) && Access_Helper::wpcf_check_cap_conflict(array_values($_post_types[$type]['cap']))*/
                        isset($_post_types[$type]['__accessIsCapValid']) && !$_post_types[$type]['__accessIsCapValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_conflict_template,__('Post Type','wpcf_access'),$_post_types[$type]['labels']['singular_name']);
                    }
                    $settings_access_types[$type] = $data;
                }
                //taccess_log($settings_access_types);
                // update settings
                $model->updateAccessTypes($settings_access_types);
                unset($settings_access_types_previous);
            }

            // Taxonomies
            $caps = Access_Helper::wpcf_access_tax_caps();
            // when a taxonomy is unchecked, no $_POST data exist, so loop over all existing taxonomies, instead of $_POST data
            foreach ($_taxonomies as $tax=>$_taxdata)
            {
                if (isset($_POST['types_access']['tax']) && isset($_POST['types_access']['tax'][$tax]))
                {
                    $data=$_POST['types_access']['tax'][$tax];
                    //foreach ($_POST['types_access']['tax'] as $tax => $data) {
                    if (!isset($data['not_managed']))
                        $data['mode'] = 'not_managed';

                    if (!isset($data['mode']))
                        $data['mode'] = 'permissions';

                    $data['mode'] = isset($data['mode']) ? $data['mode'] : 'not_managed';

                    $data['mode'] = Access_Helper::wpcf_access_get_taxonomy_mode($tax,  $data['mode']);

                    // Prevent overwriting
                    if ($data['mode'] == 'not_managed')
                    {
                        if (isset($settings_access_taxs_previous[$tax]) /*&& isset($settings_access_taxs_previous[$tax]['permissions'])*/)
                        {
                            //$data['permissions'] = $settings_access_taxs_previous[$tax]['permissions'];
                            $data = $settings_access_taxs_previous[$tax];
                            $data['mode'] = 'not_managed';
                        }
                    }
                    elseif ($data['mode'] == 'follow')
                    {
                        if (!isset($data['__permissions']))
                        {
                            // add this here since it is needed elsewhere
                            // and it is missing :P
                            $data['__permissions'] = Access_Helper::wpcf_get_taxs_caps_default(); /*array(
                                'manage_terms' => array(
                                        'role' => 'administrator'
                                ),
                                'edit_terms' => array(
                                        'role' => 'administrator'
                                ),
                                'delete_terms' => array(
                                        'role' => 'administrator'
                                ),
                                'assign_terms' => array(
                                        'role' => 'administrator'
                                )
                            );*/
                        }
                        //taccess_log($_taxdata);
                        $tax_post_type_array = array_values($_taxdata['object_type']);
						$tax_post_type = array();
						if ( count($tax_post_type_array) > 0 ){
                        	$tax_post_type = array_shift( $tax_post_type_array );
						}
                        $follow_caps = array();
                        // if parent post type managed by access, and tax is same as parent
                        // translate and hardcode the post type capabilities to associated tax capabilties
                        if (isset($settings_access_types[$tax_post_type]) && 'permissions'==$settings_access_types[$tax_post_type]['mode'])
                        {
                            $follow_caps = Access_Helper::wpcf_types_to_tax_caps($tax, $_taxdata, $settings_access_types[$tax_post_type]);
                        }
                        //taccess_log(array($tax, $follow_caps));
                        if (!empty($follow_caps))
                        {
                            $data['permissions'] = $follow_caps;
                        }
                        else
                        {
                            $data['mode']='not_managed';
                        }
                        //taccess_log(array($tax_post_type, $follow_caps, $settings_access_types[$tax_post_type]['permissions']));

                        /*if (isset($settings_access_taxs[$tax]) && isset($settings_access_taxs[$tax]['permissions']))
                            $data['permissions'] = $settings_access_taxs[$tax]['permissions'];*/
                    }
                    //taccess_log($data['permissions']);
                    $data['permissions'] = Access_Helper::wpcf_access_parse_permissions($data,  $caps);
                    //taccess_log(array($tax, $data));

                    if (
                        /*!Access_Helper::wpcf_is_object_valid('taxonomy', $_taxonomies[$tax])*/
                        isset($_taxonomies[$tax]['__accessIsNameValid']) && !$_taxonomies[$tax]['__accessIsNameValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_bypass_template,__('Taxonomy','wpcf_access'),$_taxonomies[$tax]['labels']['singular_name']);
                    }
                    if (
                        /*isset($_taxonomies[$tax]['cap']) && Access_Helper::wpcf_check_cap_conflict(array_values($_taxonomies[$tax]['cap']))*/
                        isset($_taxonomies[$tax]['__accessIsCapValid']) && !$_taxonomies[$tax]['__accessIsCapValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_conflict_template,__('Taxonomy','wpcf_access'),$_taxonomies[$tax]['labels']['singular_name']);
                    }

                    $settings_access_taxs[$tax] = $data;
                }
                else
                {
                    $data=array();
                    $data['mode'] = 'not_managed';

                    // Prevent overwriting
                    if ($data['mode'] == 'not_managed')
                    {
                        if (isset($settings_access_taxs_previous[$tax]) /*&& isset($settings_access_taxs_previous[$tax]['permissions'])*/)
                        {
                            //$data['permissions'] = $settings_access_taxs_previous[$tax]['permissions'];
                            $data = $settings_access_taxs_previous[$tax];
                            $data['mode'] = 'not_managed';
                        }
                    }
                    /*elseif ($data['mode'] == 'follow')
                    {
                        if (isset($settings_access_taxs[$tax]) && isset($settings_access_taxs[$tax]['permissions']))
                            $data['permissions'] = $settings_access[$tax]['permissions'];
                    }*/
                    $data['permissions'] = Access_Helper::wpcf_access_parse_permissions($data, $caps);

                    $settings_access_taxs[$tax] = $data;
                }
            }
            //taccess_log($settings_access_taxs);
            // update settings
            $model->updateAccessTaxonomies($settings_access_taxs);
            unset($settings_access_taxs_previous);

            // 3rd-Party
            if (!empty($_POST['types_access']))
            {
                // start empty
                //$settings_access_thirdparty_previous = $model->getAccessThirdParty();
                $third_party = array();
                foreach ($_POST['types_access'] as $area_id => $area_data)
                {
                    // Skip Types
                    if ($area_id == 'types' || $area_id == 'tax')
                    {
                        //unset($third_party[$area_id]);
                        continue;
                    }
                    $third_party[$area_id]=array();
                    foreach ($area_data as $group => $group_data)
                    {
                        // Set user IDs
                        $group_data['permissions'] = Access_Helper::wpcf_access_parse_permissions($group_data,  $caps, true);

                        $third_party[$area_id][$group] = $group_data;
                        $third_party[$area_id][$group]['mode'] = 'permissions';
                    }
                }
                //taccess_log($third_party);
                // update settings
                $model->updateAccessThirdParty($third_party);
            }

            // Roles
            if (!empty($_POST['roles']))
            {
                $access_roles = $model->getAccessRoles();
                foreach ($_POST['roles'] as $role => $level)
                {
                    $role_data = get_role($role);
                    if (!empty(/*$role*/$role_data))
                    {
                        $level = intval($level);
                        for ($index = 0; $index < 11; $index++)
                        {
                            if ($index <= $level)
                                $role_data->add_cap('level_' . $index, 1);
                            else
                                $role_data->remove_cap('level_' . $index);

                            if (isset($access_roles[$role]))
                            {
                                if (isset($access_roles[$role]['caps']))
                                {
                                    if ($index <= $level)
                                    {
                                        $access_roles[$role]['caps']['level_' . $index]=true;
                                    }
                                    else
                                    {
                                        unset($access_roles[$role]['caps']['level_' . $index]);
                                    }
                                }
                            }
                        }
                    }
                }
                //taccess_log(array($_POST['roles'], $access_roles));
                $model->updateAccessRoles($access_roles);
            }

            if (defined('DOING_AJAX'))
            {
                do_action('types_access_save_settings');
                echo "<div class='updated'><p>" . __('Access rules saved', 'wpcf_access') . "</p></div>";
                echo $access_notices;
                die();
            }
        }
    }


	/**
     * Saves Access settings by section
     */
    public static function wpcf_access_save_settings_section()
    {
        if (
            isset($_POST['_wpnonce']) &&
            wp_verify_nonce($_POST['_wpnonce'], 'wpcf-access-edit')
        )
        {
            //taccess_log($_POST['types_access']);

            $model = TAccess_Loader::get('MODEL/Access');

            //$isTypesActive = Access_Helper::wpcf_access_is_wpcf_active();

            $access_bypass_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses the same name for singular name and plural name. Access can't control access to this object. Please use a different name for the singular and plural names.", 'wpcf_access')."</p></div>";
            $access_conflict_template="<div class='error'><p>".__("<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default Wordpress capabilities. Access can not manage this entity, try changing entity's name and / or slug", 'wpcf_access')."</p></div>";
            $access_notices='';
            $_post_types=Access_Helper::wpcf_object_to_array( $model->getPostTypes() );
            $_taxonomies=Access_Helper::wpcf_object_to_array( $model->getTaxonomies() );

            //taccess_log($_taxonomies);

            // start empty
            $settings_access_types_previous = $model->getAccessTypes();
            $settings_access_taxs_previous = $model->getAccessTaxonomies();
			$settings_access_thirdparty_previous = $model->getAccessThirdParty();
            $settings_access_types = array();
            $settings_access_taxs = array();

			// Post Types
			$custom_data = array();
			$settings_access = $model->getAccessTypes();


            if (!empty($_POST['types_access_error_type']['types']))
            {
                foreach ($_POST['types_access_error_type']['types'] as $type => $data)
                {
                     $settings_access_types_previous['_custom_read_errors'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types_previous);
            }
            if (!empty($_POST['types_access_error_value']['types']))
            {
                foreach ($_POST['types_access_error_value']['types'] as $type => $data)
                {
                     $settings_access_types_previous['_custom_read_errors_value'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types_previous);
            }

			//Archives
			if (!empty($_POST['types_access_archive_error_type']['types']))
            {
                foreach ($_POST['types_access_archive_error_type']['types'] as $type => $data)
                {
                     $settings_access_types_previous['_archive_custom_read_errors'][$type] = $data;
                }

                $model->updateAccessTypes($settings_access_types_previous);
            }
            if (!empty($_POST['types_access_archive_error_value']['types']))
            {
                foreach ($_POST['types_access_archive_error_value']['types'] as $type => $data)
                {
                     $settings_access_types_previous['_archive_custom_read_errors_value'][$type] = $data;
                }
                $model->updateAccessTypes($settings_access_types_previous);
            }


            // Post Types
            if (!empty($_POST['types_access']['types']))
            {
                $caps = Access_Helper::wpcf_access_types_caps_predefined();

                foreach ($_POST['types_access']['types'] as $type => $data)
                {

                    $mode = isset($data['mode']) ? $data['mode'] : 'not_managed';
                    // Use saved if any and not_managed
                    if ( isset($data['mode']) && $data['mode'] == 'not_managed'
                            && isset($settings_access_types_previous[$type])) {
                        $data = $settings_access_types_previous[$type];
                    }

                    $data['mode'] = $mode;
					if ( strpos($type, 'wpcf-custom-group-') === 0 && isset($_POST['groupvalue-'.$type]) ){
						 $data['title'] = $_POST['groupvalue-'.$type];
					}

                    $data['permissions'] = Access_Helper::wpcf_access_parse_permissions($data, $caps);
                    //taccess_log($data['permissions']);

                    if (
                        /*!Access_Helper::wpcf_is_object_valid('type', $_post_types[$type])*/
                        isset($_post_types[$type]['__accessIsNameValid']) && !$_post_types[$type]['__accessIsNameValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_bypass_template,__('Post Type','wpcf_access'),$_post_types[$type]['labels']['singular_name']);
                    }

                    if (
                        /*isset($_post_types[$type]['cap']) && Access_Helper::wpcf_check_cap_conflict(array_values($_post_types[$type]['cap']))*/
                        isset($_post_types[$type]['__accessIsCapValid']) && !$_post_types[$type]['__accessIsCapValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_conflict_template,__('Post Type','wpcf_access'),$_post_types[$type]['labels']['singular_name']);
                    }
                    //$settings_access_types[$type] = $data;
					$settings_access_types_previous[$type] = $data;
                }
                // update settings
                $model->updateAccessTypes($settings_access_types_previous);
                //unset($settings_access_types_previous);
            }

            // Taxonomies
            $caps = Access_Helper::wpcf_access_tax_caps();
            // when a taxonomy is unchecked, no $_POST data exist, so loop over all existing taxonomies, instead of $_POST data
            foreach ($_taxonomies as $tax=>$_taxdata)
            {
                if (isset($_POST['types_access']['tax']) && isset($_POST['types_access']['tax'][$tax]))
                {
                    $data=$_POST['types_access']['tax'][$tax];

                    //foreach ($_POST['types_access']['tax'] as $tax => $data) {
                    if (!isset($data['not_managed']))
                        $data['mode'] = 'not_managed';

                    if (!isset($data['mode']))
                        $data['mode'] = 'permissions';

                    $data['mode'] = isset($data['mode']) ? $data['mode'] : 'not_managed';
					//Checkthis
                    //$data['mode'] = Access_Helper::wpcf_access_get_taxonomy_mode($tax,  $data['mode']);

                    // Prevent overwriting
                    if ($data['mode'] == 'not_managed')
                    {
                        if (isset($settings_access_taxs_previous[$tax]) /*&& isset($settings_access_taxs_previous[$tax]['permissions'])*/)
                        {
                            //$data['permissions'] = $settings_access_taxs_previous[$tax]['permissions'];
                            $data = $settings_access_taxs_previous[$tax];
                            $data['mode'] = 'not_managed';
                        }
                    }
                    elseif ($data['mode'] == 'follow')
                    {
                        if (!isset($data['__permissions']))
                        {
                            // add this here since it is needed elsewhere
                            // and it is missing :P
                            $data['__permissions'] = Access_Helper::wpcf_get_taxs_caps_default();
                        }
                        //taccess_log($_taxdata);
                        $tax_post_type = '';
                        if (isset($tax_post_type)){
                        	$tax_arr = array_values($_taxdata['object_type']);
							if ( is_array($tax_arr)){
                        		$tax_post_type = array_shift($tax_arr);
							}
						}
						//$tax_post_type = array_shift(array_values($_taxdata['object_type']));
                        $follow_caps = array();
                        // if parent post type managed by access, and tax is same as parent
                        // translate and hardcode the post type capabilities to associated tax capabilties
                        if (isset( $settings_access_types_previous[$tax_post_type]) && 'permissions'== $settings_access_types_previous[$tax_post_type]['mode'])
                        {
                            $follow_caps = Access_Helper::wpcf_types_to_tax_caps($tax, $_taxdata,  $settings_access_types_previous[$tax_post_type]);
                        }
                        //taccess_log(array($tax, $follow_caps));

                        if (!empty($follow_caps))
                        {
                            $data['permissions'] = $follow_caps;
                        }
                        else
                        {
                            $data['mode']='not_managed';
                        }
                        //taccess_log(array($tax_post_type, $follow_caps, $settings_access_types[$tax_post_type]['permissions']));

                        /*if (isset($settings_access_taxs[$tax]) && isset($settings_access_taxs[$tax]['permissions']))
                            $data['permissions'] = $settings_access_taxs[$tax]['permissions'];*/
                    }
                    //taccess_log($data['permissions']);
                    $data['permissions'] = Access_Helper::wpcf_access_parse_permissions($data,  $caps);
                    //taccess_log(array($tax, $data));

                    if (
                        /*!Access_Helper::wpcf_is_object_valid('taxonomy', $_taxonomies[$tax])*/
                        isset($_taxonomies[$tax]['__accessIsNameValid']) && !$_taxonomies[$tax]['__accessIsNameValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_bypass_template,__('Taxonomy','wpcf_access'),$_taxonomies[$tax]['labels']['singular_name']);
                    }
                    if (
                        /*isset($_taxonomies[$tax]['cap']) && Access_Helper::wpcf_check_cap_conflict(array_values($_taxonomies[$tax]['cap']))*/
                        isset($_taxonomies[$tax]['__accessIsCapValid']) && !$_taxonomies[$tax]['__accessIsCapValid']
                    )
                    {
                        $data['mode'] = 'not_managed';
                        $access_notices.=sprintf($access_conflict_template,__('Taxonomy','wpcf_access'),$_taxonomies[$tax]['labels']['singular_name']);
                    }

                    //$settings_access_taxs[$tax] = $data;
                    $settings_access_taxs_previous[$tax] = $data;
                }

            }
            //taccess_log($settings_access_taxs);
            // update settings
            $model->updateAccessTaxonomies($settings_access_taxs_previous);
            unset($settings_access_taxs_previous);

            // 3rd-Party
            if (!empty($_POST['types_access']))
            {
                // start empty
                //$settings_access_thirdparty_previous = $model->getAccessThirdParty();
                $third_party = $settings_access_thirdparty_previous;
				if ( !is_array($third_party) ){
					$third_party = array();
				}
                foreach ($_POST['types_access'] as $area_id => $area_data)
                {
                    // Skip Types
                    if ($area_id == 'types' || $area_id == 'tax')
                    {
                        //unset($third_party[$area_id]);
                        continue;
                    }
					if ( !isset($third_party[$area_id]) || empty($third_party[$area_id]) ){
                    	$third_party[$area_id]=array();
					}

                    foreach ($area_data as $group => $group_data)
                    {
                        // Set user IDs
                        $group_data['permissions'] = Access_Helper::wpcf_access_parse_permissions($group_data,  $caps, true);

                        $third_party[$area_id][$group] = $group_data;
                        $third_party[$area_id][$group]['mode'] = 'permissions';
                    }
                }
                //taccess_log($third_party);
                // update settings
                $model->updateAccessThirdParty($third_party);
            }

            // Roles
            if (!empty($_POST['roles']))
            {
                $access_roles = $model->getAccessRoles();
                foreach ($_POST['roles'] as $role => $level)
                {
                    $role_data = get_role($role);
                    if (!empty(/*$role*/$role_data))
                    {
                        $level = intval($level);
                        for ($index = 0; $index < 11; $index++)
                        {
                            if ($index <= $level)
                                $role_data->add_cap('level_' . $index, 1);
                            else
                                $role_data->remove_cap('level_' . $index);

                            if (isset($access_roles[$role]))
                            {
                                if (isset($access_roles[$role]['caps']))
                                {
                                    if ($index <= $level)
                                    {
                                        $access_roles[$role]['caps']['level_' . $index]=true;
                                    }
                                    else
                                    {
                                        unset($access_roles[$role]['caps']['level_' . $index]);
                                    }
                                }
                            }
                        }
                    }
                }
                //taccess_log(array($_POST['roles'], $access_roles));
                $model->updateAccessRoles($access_roles);
            }

            if (defined('DOING_AJAX'))
            {
                do_action('types_access_save_settings');
                echo "<div class='updated'><p>" . __('Access rules saved', 'wpcf_access') . "</p></div>";
                echo $access_notices;
                die();
            }
        }
    }

    /**
     * AJAX revert to default call.
     */
    public static function wpcf_access_ajax_reset_to_default()
    {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'],
                        'wpcf_access_ajax_reset_to_default')) {
            die('verification failed');
        }
        if ($_GET['type'] == 'type') {
            $caps = Access_Helper::wpcf_access_types_caps_predefined();
        } else if ($_GET['type'] == 'tax') {
            $caps = Access_Helper::wpcf_access_tax_caps();
        }
        if (!empty($caps) && isset($_GET['button_id'])) {
            $output = array();
            foreach ($caps as $cap => $cap_data) {
                $output[$cap] = $cap_data['role'];
            }
            echo json_encode(array(
                'output' => $output,
                'type' => $_GET['type'],
                'button_id' => $_GET['button_id'],
            ));
        }
        die();
    }

    /**
     * AJAX set levels default call.
     */
    public static function wpcf_access_ajax_set_level()
    {
        if (!isset($_POST['_wpnonce'])
                || !wp_verify_nonce($_POST['_wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }
        TAccess_Loader::load('CLASS/Admin_Edit');
        $model = TAccess_Loader::get('MODEL/Access');

		$default_caps = getDefaultCaps();
		$default_wordpress_caps = $default_caps[10];




        if (!empty($_POST['roles']))
        {
            $access_roles = $model->getAccessRoles();
            foreach ($_POST['roles'] as $role => $level)
            {

				$add_caps = array();
				$clone_from = 'subscriber';
				if ( $level == 1){
					$clone_from = 'contributor';
				}
				if ( $level >= 2 && $level < 7 ){
					$clone_from = 'author';
				}
				if ( $level >= 7 && $level < 10 ){
					$clone_from = 'editor';
				}
				if ( $level == 10 ){
					$clone_from = 'administrator';
				}
				$temp_role_data = get_role($clone_from);

				$role_data = get_role($role);
				foreach( $role_data->capabilities as $role_cap => $role_status){
					$role_data->remove_cap($role_cap);
				}

				foreach( $temp_role_data->capabilities as $role_cap => $role_status){
					$role_data->add_cap($role_cap);
				}
				$role_data->add_cap('wpcf_access_role');


                $role_data = get_role($role);

                if (!empty(/*$role*/$role_data))
                {
                    $level = intval($level);
                    for ($index = 0; $index < 11; $index++)
                    {
                        if ($index <= $level) {
                            $role_data->add_cap('level_' . $index, 1);
                        } else {
                            $role_data->remove_cap('level_' . $index);
                        }
                        if (isset($access_roles[$role]))
                        {
                            if (isset($access_roles[$role]['caps']))
                            {
                                if ($index <= $level)
                                {
                                    $access_roles[$role]['caps']['level_' . $index]=true;
                                }
                                else
                                {
                                    unset($access_roles[$role]['caps']['level_' . $index]);
                                }
                            }
                        }
                    }

                }
            }
            $model->updateAccessRoles($access_roles);
        }
        echo json_encode(array(
            'output' => Access_Admin_Edit::wpcf_access_admin_set_custom_roles_level_form(
                            Access_Helper::wpcf_get_editable_roles(),
                            true
                        ),
        ));
        die();
    }

    /**
     * Suggest user AJAX.
     */
    public static function wpcf_access_wpcf_access_suggest_user_ajax()
    {
        global $wpdb;
        $users = array();
        $q = esc_sql(trim($_POST['q']));
        $q = like_escape($q);
        $found = $wpdb->get_results("SELECT ID, display_name, user_login FROM $wpdb->users WHERE user_nicename LIKE '%%$q%%' OR user_login LIKE '%%$q%%' OR display_name LIKE '%%$q%%' OR user_email LIKE '%%$q%%' LIMIT 10");
        if (!empty($found)) {
            foreach ($found as $user) {
                $users[$user->ID] = $user->display_name . ' (' . $user->user_login . ')';
            }
        }
        echo json_encode($users);
        die();
    }

    /**
     * Adds new custom role.
     */
    public static function wpcf_access_add_role_ajax()
    {
        TAccess_Loader::load('CLASS/Admin_Edit');
        $model = TAccess_Loader::get('MODEL/Access');
        $access_roles = $model->getAccessRoles();
        $capabilities = array('level_0' => true, 'read' => true);
        $caps = Access_Helper::wpcf_access_types_caps();
        foreach ($caps as $cap => $data)
        {
            if ($data['predefined'] == 'read')
            {
                $capabilities[$cap] = true;
            }
        }
		$capabilities['wpcf_access_role'] = true;
        $role_slug = str_replace('-', '_', sanitize_title($_POST['role']));
        $success = add_role($role_slug, $_POST['role'], $capabilities);
        if (!is_null($success))
        {
            $access_roles[$role_slug]=array(
                'name'=> $_POST['role'],
                'caps'=> $capabilities
            );
            $model->updateAccessRoles($access_roles);
        }
        //taccess_log(array($_POST['role'], $access_roles));
        echo json_encode(array(
            'error' => is_null($success) ? 'true' : 'false',
            'output' => is_null($success) ? '
				<div class="error toolset-alert toolset-alert-error js-toolset-alert">'
					. __('Role already exists', 'wpcf_access') . '
				</div>' : Access_Admin_Edit::wpcf_access_admin_set_custom_roles_level_form(Access_Helper::wpcf_get_editable_roles()),
        ));
        die();
    }

    /**
     * Deletes custom role.
     */
    public static function wpcf_access_delete_role_ajax()
    {
        if (!isset($_POST['wpcf_access_delete_role_nonce'])
                || !wp_verify_nonce($_POST['wpcf_access_delete_role_nonce'],
                        'delete_role')) {
            die('verification failed');
        }

        if (in_array(strtolower(trim($_POST['wpcf_access_delete_role'])),
                        Access_Helper::wpcf_get_default_roles()))
        {
            $error = 'true';
            $output = '<div class="error toolset-alert toolset-alert-error js-toolset-alert">' . __('Role can not be deleted', 'wpcf_access') . '</div>';
        } else {
            TAccess_Loader::load('CLASS/Admin_Edit');
            $model = TAccess_Loader::get('MODEL/Access');
            $access_roles = $model->getAccessRoles();
            if ($_POST['wpcf_reassign'] != 'ignore')
            {
                $users = get_users('role=' . $_POST['wpcf_access_delete_role']);
                foreach ($users as $user)
                {
                    $user = new WP_User($user->ID);
                    $user->add_role($_POST['wpcf_reassign']);
					$user->remove_role($_POST['wpcf_access_delete_role']);
                }
            }
            remove_role($_POST['wpcf_access_delete_role']);
            if (isset($access_roles[$_POST['wpcf_access_delete_role']]))
            {
                unset($access_roles[$_POST['wpcf_access_delete_role']]);
            }
            //taccess_log(array($_POST['wpcf_access_delete_role'], $access_roles));
            $model->updateAccessRoles($access_roles);

            $error = 'false';
            $output = Access_Admin_Edit::wpcf_access_admin_set_custom_roles_level_form(Access_Helper::wpcf_get_editable_roles());
        }
        echo json_encode(array(
            'error' => $error,
            'output' => $output,
        ));
        die();
    }

	/*
	 * Load list of Errors page (404, Ct, PHP tempaltes)
	 */
	public static function wpcf_access_show_error_list_ajax()
    {
		if ( !isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages') ) {
            die('verification failed');
        }
		$post_type = $_POST['posttype'];
		$is_archive = $_POST['is_archive'];
		$out = '
			<form method="" id="wpcf-access-set_error_page">
				<input type="hidden" value="'. $_POST['access_type'].'" name="typename">
				<input type="hidden" value="'.$_POST['access_value'].'" name="valuename">';
		if ( $is_archive == 1){
		$out .= '<input type="hidden" value="'. $_POST['access_archivetype'].'" name="archivetypename">
				<input type="hidden" value="'.$_POST['access_archivevalue'].'" name="archivevaluename">';
		}

		$out .= '<h2>'. __('Single post error','wpcf_access') .'</h2>';
		$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == '' )?' checked="checked" ':'';
		if ( $_POST['forall'] != 1){
		$out .= '
				<p>
					<label>
						<input type="radio" value="default" name="error_type" class="js-wpcf-access-type"'.$checked.'> '. __('Default error','wpcf_access') .'
					</label>
				</p>';
		}
		$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_404' )?' checked="checked" ':'';
		if ( $_POST['forall'] == 1 && isset($_POST['cur_type']) && $_POST['cur_type'] == '' ){
			$checked = ' checked="checked" ';
		}

		$out .= '
				<p>
					<label>
						<input type="radio" value="error_404" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show 404 - page not found','wpcf_access') .'
					</label>
				</p>';
		if( class_exists('WP_Views') ){
			$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_ct' )?' checked="checked" ':'';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_ct" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show Content Template','wpcf_access') .'
					</label>
					<select name="wpcf-access-ct" class="hidden" class="js-wpcf-error-ct-value">
						<option value="">'.__('None','wpcf_access').'</option>';
			$wpv_args = array('post_type' => 'view-template','posts_per_page' => -1,'order' => 'ASC','orderby' => 'title','post_status' => 'publish');
			$content_tempaltes = get_posts( $wpv_args );
			foreach ( $content_tempaltes as $post ) :
				$selected = ( isset($_POST['cur_value']) && $_POST['cur_value'] == $post->ID )?' selected="selected" ':'';
				$out .= '
						<option value="'.$post->ID.'"'. $selected .'>'.$post->post_title.'</option>';
			endforeach;
			$out .= '
					</select>
				</p>';
		}
		$templates = wp_get_theme()->get_page_templates();
		if ( !empty($templates) ){
			$checked = ( isset($_POST['cur_type']) && $_POST['cur_type'] == 'error_php' )?' checked="checked" ':'';
			$out .= '
				<p>
					<label>
						<input type="radio" value="error_php" name="error_type"'.$checked.' class="js-wpcf-access-type"> '. __('Show Page template','wpcf_access') .'
					</label>
					<select name="wpcf-access-php" class="hidden" class="js-wpcf-error-php-value">
						<option value="">'.__('None','wpcf_access').'</option>';
							foreach ( $templates as $template_name => $template_filename ) {
							   $selected = ( isset($_POST['cur_value']) && $_POST['cur_value'] == $template_filename )?' selected="selected" ':'';
							   $out .= '<option value="'.$template_filename.'"'. $selected .'>'.$template_filename.'</option>';
							}
			$out .= '
					</select>
				</p>';
		}

		$show_php_tempaltes = true;

		if ( $is_archive == 1){
			$archive_out = '';
			//Hide php templates
			$show_php_tempaltes = true;
			$out .= '<h2>'. __('Archive error','wpcf_access') .'</h2>';



			if( class_exists('WP_Views') && function_exists('wpv_force_wordpress_archive') ){
				global $WPV_view_archive_loop, $WP_Views;

				$have_archives = wpv_has_wordpress_archive( 'post', $post_type);

				if ( $have_archives > 0 ){
					$show_php_tempaltes = false;

					$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'error_ct' )?' checked="checked" ':'';
					$has_items = wpv_check_views_exists('archive');
					if ( count($has_items) > 0 ){
							$archive_out .= '<p><label>
							<input type="radio" value="error_ct" name="archive_error_type" '.$checked.'class="js-wpcf-access-type-archive">
							'. __('Choose a different WordPress archive for people without read permission','wpcf_access') .'<br />';
							$custom_error = '';
									$custom_error = '';
									$view = get_post( $have_archives );
									if ( is_object($view) ){
										$has_items = array_diff($has_items, array($have_archives));
										$custom_error = sprintf(
										__( 'This custom post archive displays with the WordPress Archive "%s".', 'wpcf_access' ), $view->post_title );
									}
							$archive_out .= '</label>';
						$wpv_args = array( // array of WP_Query parameters
							'post_type' => 'view',
							'post__in' => $has_items,
							'posts_per_page' => -1,
							'order' => 'ASC',
							'orderby' => 'title',
							'post_status' => 'publish'
						);
						$wpv_query = new WP_Query( $wpv_args );
						$wpv_count_posts = $wpv_query->post_count;
						if ( $wpv_count_posts > 0 ) {
							$archive_out .= '<select name="wpcf-access-archive-ct" class="js-wpcf-error-ct-value">
							<option value="">'.__('None','wpcf_access').'</option>';
							while ($wpv_query->have_posts()) :
								$wpv_query->the_post();
								$post_id = get_the_id();
								//$options = $WPV_view_archive_loop->_view_edit_options($post_id, $options);

								$post = get_post($post_id);
								$selected = ( isset($_POST['cur_archivevalue']) && $_POST['cur_archivevalue'] == $post->ID )?' selected="selected" ':'';
								$archive_out .= '<option value="'.$post->ID.'" '.$selected.'>'.$post->post_title.'</option>';
							endwhile;
							$archive_out .= '</select>';
						}


						$archive_out .= '<p class="toolset-alert toolset-alert- js-wpcf-error-ct-value-info" style="display: none; opacity: 1;">
								'.$custom_error.'</p>';
						$archive_out .= '</p>';

					}
					else {
						$archive_out .= '<p>'. __('Sorry, no alternative WordPress Archives. First, create a new WordPress Archive, then return here to choose it.','wpcf_access') .'</p>';
					}
				}
			}



			if ( $show_php_tempaltes ){
				$theme_files = array();
				if ( isset($_POST['cur_archivevalue']) ){
					$_POST['cur_archivevalue'] = urldecode($_POST['cur_archivevalue']);
					$_POST['cur_archivevalue'] = str_replace("\\\\","\\",$_POST['cur_archivevalue']);
				}

				if ( is_child_theme() ){
					$child_theme_dir = get_stylesheet_directory();
					$theme_files = self::wpcf_access_scan_dir( $child_theme_dir, $theme_files);
				}
				$theme_dir = get_template_directory().'/';

				if ( file_exists( $theme_dir.'archive-'.$post_type.'.php') ){
					$curent_file = 'archive-'.$post_type.'.php';
				}elseif ( file_exists( $theme_dir.'archive.php') ){
					$current_file = 'archive.php';
				}else{
					$current_file = 'index.php';
				}
				$error_message  =	sprintf(
							__( 'This custom post archive displays with the PHP template "%s".', 'wpcf_access' ), $current_file );
				$theme_files = self::wpcf_access_scan_dir( $theme_dir, $theme_files, $current_file);
				$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'error_php' )?' checked="checked" ':'';

				$archive_out .= '<p><label>
					<input type="radio" value="error_php" name="archive_error_type"'.$checked.' class="js-wpcf-access-type-archive"> '
					. __('Choose a different PHP template for people without read permission','wpcf_access') .'
					</label>
					<p class="toolset-alert toolset-alert- js-wpcf-error-php-value-info" style="display: none; opacity: 1;">
					'. $error_message .'
					</p><select name="wpcf-access-archive-php" class="js-wpcf-error-php-value hidden">
					<option value="">'.__('None','wpcf_access').'</option>';
						for ( $i=0,$limit=count($theme_files);$i<$limit;$i++){
							$selected = ( isset($_POST['cur_archivevalue']) && $_POST['cur_archivevalue'] == $theme_files[$i] )?' selected="selected" ':'';
							$archive_out .= '<option value="'.$theme_files[$i].'" '.$selected.'>'.preg_replace("/.*(\/.*\/)/","$1",$theme_files[$i]).'</option>';
						}
					$archive_out .= '</select></p>';
			}

			//Default error, use for everyone if set.
			if ( $_POST['forall'] != 1){
				$checked = ( empty($checked) )?' checked="checked" ':'';
				$out .= '<p><label>
				<input type="radio" value="default" name="archive_error_type" class="js-wpcf-access-type-archive"'.$checked.'> '
				. __('Default error','wpcf_access') .'
				</label></p>';
			}

			//Show post not found message'
			//Set post type not queryable
			$checked = ( isset($_POST['cur_archivetype']) && $_POST['cur_archivetype'] == 'default_error' || empty($checked) )?' checked="checked" ':'';
			$out .= '<p><label>
			<input type="radio" value="default_error" name="archive_error_type" class="js-wpcf-access-type-archive"'.$checked.'> '
			. __('Display: "No posts found"','wpcf_access') .'
			</label></p>';

			$out .= $archive_out;

		}//End check if post type have archive



		$out .= '</form>';

		ob_start();
		?>
		<div class="wpv-dialog js-wpcf-add-access-error-page">
			<div class="wpv-dialog-header">
				<h2><?php _e('Set error','wpcf_access')?></h2>
			</div>
			<div class="wpv-dialog-content">
				<?php echo $out;?>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e('Cancel', 'wpcf_access')?></button>
				<button class="button button-primary js-set-error-page" data-error1="<?php _e('Show 404 - page not found','wpcf_access')?>"
				data-error2="<?php echo __('Show Content Template','wpcf_access').' - ';?>"
				 data-error3="<?php echo __('Show Page template','wpcf_access').' - ';?>"
				 data-inf01="<?php echo __('Template','wpcf_access');?>"
				 data-inf02="<?php echo __('PHP Template','wpcf_access');?>"
				 data-inf03="<?php echo __('PHP Archive','wpcf_access');?>"
				 data-inf04="<?php echo __('View Archive','wpcf_access');?>"
				 data-inf05="<?php echo __('Display: \'No posts found\'','wpcf_access');?>"
				 ><?php _e('Set errors', 'wpcf_access')?></button>
			</div>
    	</div>
		<?php
		$out = ob_get_contents();
   		ob_end_clean();
		echo $out;
		die();
	}


	/*
	 * Scan directory for php files.
	 */
	public static function wpcf_access_scan_dir( $dir, $files = array(), $exclude = ''){
		$file_list = scandir($dir);
		foreach($file_list as $file){
	        if($file != '.' && $file != '..' && preg_match("/\.php/",$file) && !preg_match("/^comments|^single|^image|^functions|^header|^footer|^page/",$file) && $file != $exclude ){

	            if( !is_dir($dir.$file) ){
	            	$files[] = 	$dir.$file;
				}
				else{
					$files = self::wpcf_access_scan_dir($dir.$file.'/', $files);
				}
	        }
	    }
		return $files;
	}

	/*
	 * Add new custom group form
	 */
	public static function wpcf_access_add_new_group_form_ajax()
    {
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$out = '<form method="" id="wpcf-access-set_error_page">';
		$act = 'Add';
		$title = '';
		if ( isset($_POST['modify']) ) {
			$act = 'Modify';
			$id = $_POST['modify'];
			$model = TAccess_Loader::get('MODEL/Access');
			$settings_access = $model->getAccessTypes();
            $current_role = $settings_access[$id];
			$title = $current_role['title'];
		}

		$out .= '
			<p>
				<label for="wpcf-access-new-group-title">'. __('Group title','wpcf_access') .'</label><br>
				<input type="text" id="wpcf-access-new-group-title" value="'.$title.'">
			</p>
			<div class="js-error-container"></div>
			<input type="hidden" value="add" id="wpcf-access-new-group-action">';

		$out .= '
			<p>
				<label for="wpcf-access-suggest-posts">'. __('Search posts','wpcf_access') .'</label><br>
				<input type="text" id="wpcf-access-suggest-posts">
				<input type="button" value="'. __('Search','wpcf_access') .'" class="button js-wpcf-search-posts">
			</p>';

		$out .= '
			<h4>' . __('Search result','wpcf_access') . '</h4>
			<p class="hidden js-use-search toolset-alert toolset-alert-info">'. __('Search for posts to add more','wpcf_access') .'</p>
			<div class="wpcf-suggested-posts js-wpcf-suggested-posts">
				<ul>';
					$post_types_array = array();
					$post_types = get_post_types( array('public'=> true), 'names' );
					foreach ( $post_types  as $post_type ) {
						$post_types_array[] = $post_type;
					}
					if ( $act == 'Add' ){

						$args = array('posts_per_page' => '10', 'post_status' => 'publish', 'post_type' => $post_types_array);
						$the_query = new WP_Query( $args );
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post();
								$out .= '<li>'. get_the_title() .' <a href="" class="js-wpcf-add-post-to-group" data-title="'.esc_js(get_the_title()).'" data-id="'.get_the_ID().'">+' . __('Add','wpcf_access') . '</a></li>';
							};
						}
					}
		$out .= '</ul>
			</div>';

		$out .= '
			<h4>' . __('Assigned posts','wpcf_access') . '</h4>
			<div class="wpcf-assigned-posts js-wpcf-assigned-posts">
				<p class="hidden js-no-posts-assigned toolset-alert toolset-alert-info">'. __('No posts assigned','wpcf_access') .'</p>
				<ul>';
					if ( $act == 'Modify' ) {
						$args = array(
							'posts_per_page' => -1,
							'post_status' => 'publish',
							'post_type' => $post_types_array,
							'meta_query' => array(array(
												   'key' => '_wpcf_access_group',
												   'value' => $id
											   )
										   )
						);
						$the_query = new WP_Query( $args );
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post();
								$out .= '<li class="js-assigned-access-post-'.get_the_ID().'">'.get_the_title().'
								 <a href="" class="js-wpcf-unassign-access-post" data-id="'.get_the_ID().'"> ' . __('Remove','wpcf_access') . '</a>'.
								'<input type="hidden" value="'.get_the_ID().'" name="assigned-posts[]"></li>';
							};
						}
					}
			$out .= '</ul>
			</div>
		</form>';
		ob_start();
		?>
			<div class="wpv-dialog js-wpcf-add-access-error-page">
			<div class="wpv-dialog-header">
				<h2><?php _e('Custom Access Group','wpcf_access')?></h2>
			</div>
			<div class="wpv-dialog-content">
				<?php echo $out;?>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e('Cancel','wpcf_access')?></button>
				<?php if ( $act == 'Modify'){?>
					<button class="button button-primary js-wpcf-process-modify-access-group"
							data-error="<?php echo esc_js(__('Group title aready exists', 'wpcf_access'))?>"
							data-id="<?php echo $id?>"><?php _e('Modify Group', 'wpcf_access')?>
					</button>
				<?php } else { ?>
				<button class="button button-primary js-wpcf-process-new-access-group"
						data-error="<?php echo esc_js(__('Group title aready exists','wpcf_access'))?>"><?php _e('Add Group','wpcf_access')?>
				</button>
				<?php }?>
			</div>
			</div>
		<?php
		$out = ob_get_contents();
   		ob_end_clean();
		echo $out;
		die();
	}

	/*
	 * Proccss new access group
	 */
	public static function wpcf_process_new_access_group_ajax() {
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$nice = sanitize_title('wpcf-custom-group-'.$_POST['title']);
		$posts = array();
		if ( isset($_POST['posts']) ) {
			$posts = $_POST['posts'];
		}

		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		$groups[$nice] = array(
			'title' => $_POST['title'],
			'mode' => 'permissions',
			'__permissions' => array( 'read'=>array('role'=>'guest') ),
			'permissions' => array( 'read'=>array('role'=>'guest') ),
			);

		$process = true;
		if ( !empty($settings_access) ){
			foreach ($settings_access as $permission_slug => $data){
				if ( $permission_slug == $nice ){
					$process = false;
				}
			}
		}

		if ( !$process ){
			echo 'error';
			die();
		}

		for ($i=0, $limit=count($posts);$i<$limit;$i++){
			update_post_meta($posts[$i],'_wpcf_access_group', $nice);
		}
		TAccess_Loader::load('CLASS/Admin_Edit');
		$roles = Access_Helper::wpcf_get_editable_roles();
		$settings_access = array_merge( $settings_access, $groups);
		$model->updateAccessTypes( $settings_access );
			$enabled = true;
			$group['id'] = $nice;
			$group['name']= $_POST['title'];

            $output = '<a name="' . $group['id'] . '"></a>';
            $output .= '<div class="wpcf-access-type-item is-enabled" id="js-box-' . $nice . '">';
				$output .= '<h4>' . $group['name'] . '</h4>';
				$output .= '<div class="wpcf-access-mode">';
				$caps = array();
				$saved_data = array();
				$saved_data['read'] = array('role' => 'guest');
				$group_data = $groups[$nice];
				$def = array(
					'read' => array(
						'title' => __('Read','wpcf_access'),
						'role' => 'guest',
						'predefined' => 'read' ,
						'cap_id' => 'group')
					);

					$output .= Access_Admin_Edit::wpcf_access_permissions_table(
							$roles, $saved_data,
							$def, 'types', $group['id'],
							$enabled, 'permissions', $group_data);

					$output .= '<p class="wpcf-access-buttons-wrap">';
						$output .= '<span class="ajax-loading spinner"></span>';
						$output .= '<input data-group="' . $nice . '"  type="button" value="' . __('Modify Group', 'wpcf_access') . '"  class="js-wpcf-modify-group button-secondary" /> ';
						$output .= '<input data-group="' . $nice . '"  type="button" value="' . __('Remove Group', 'wpcf_access') . '"  class="button-secondary js-wpcf-remove-group" /> ';
						$output .= Access_Admin_Edit::wpcf_access_submit_button($enabled, true, $group['name']);
					$output .= '</p>';
					$output .= '<input type="hidden" name="groupvalue-' . $nice . '" value="' . $_POST['title'] . '">';
            $output .= '</div>	<!-- .wpcf-access-mode -->';
//        $output .= '<p class="wpcf-access-top-anchor"><a href="#wpcf-access-top-anchor">'. __('Back to Top', 'wpcf_access') .'</a></p>';
		$output .= '</div>	<!-- .wpcf-access-type-item -->';

		echo $output;
		die();
	}

	/*
	 * Process modify group
	 */
	public static function wpcf_process_modify_access_group_ajax()
    {
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$nice = $_POST['id'];
		$posts = array();
		if ( isset($_POST['posts']) ){
			$posts = $_POST['posts'];
		}

		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();
    	$process = true;
		if ( isset($settings_access[$nice]) ){
			foreach ($settings_access as $permission_slug => $data){
				if ( isset($data['title']) && $data['title'] == $_POST['title'] && $permission_slug != $nice ){
					$process = false;
				}
			}
		}else{
			$process = false;
		}

		$settings_access[$nice]['title'] = $_POST['title'];
		TAccess_Loader::load('CLASS/Admin_Edit');
		$roles = Access_Helper::wpcf_get_editable_roles();
		$model->updateAccessTypes( $settings_access );



		if ( !$process ){
			echo 'error';
			die();
		}

		for ($i=0,$posts_limit=count($posts);$i<$posts_limit;$i++){
			update_post_meta($posts[$i],'_wpcf_access_group', $nice);
		}
		die();
	}

	/*
	 * Remove group
	 */
	public static function wpcf_remove_group_ajax()
    {
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$out = '<form method="">
		<p>'. __('Are you sure want to delete this group?','wpcf_access') .'"</p>
		</form>';
		ob_start();
		?>
		<div class="wpv-dialog js-wpcf-add-access-error-page">

			<div class="wpv-dialog-header">
				<h2><?php _e('Custom Access Group', 'wpcf_access')?></h2>
			</div>

			<div class="wpv-dialog-content">
				<?php echo $out;?>
			</div>

			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e('Cancel','wpcf_access')?></button>
				<button class="button button-primary js-wpcf-delete-group-process" data-group="<?php echo $_POST['group_id']?>"><?php _e('Remove Group','wpcf_access')?></button>
			</div>

    	</div>
		<?php
		$out = ob_get_contents();
			ob_end_clean();
		echo $out;
		die();
	}

	/*
	 * Remove group process
	 */
	public static function wpcf_remove_group_process_ajax()
    {
    	if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], 'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		if ( isset($settings_access[$_POST['group_id']]) ) {
			unset($settings_access[$_POST['group_id']]);
		}
		$model->updateAccessTypes( $settings_access );

		die();
	}

	/*
	 * Search post for group
	 */
	public static function wpcf_search_posts_for_groups_ajax()
    {

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
    	$out = '';
		$post_types_array = array();
		$post_types = get_post_types( array('public'=> true), 'names' );
		foreach ( $post_types  as $post_type ) {
			$post_types_array[] = $post_type;
		}
		$args = array(
			'posts_per_page' => '10',
			'post_status' => 'publish',
			'post_type' => $post_types_array,
			's' => $_POST['title']);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
			 	$out .= '
					<li>'. get_the_title() .' <a href="" class="js-wpcf-add-post-to-group"
						data-title="'.esc_js(get_the_title()).'"
						data-id="'.get_the_ID().'">+ '.__('Add','wpcf_access').'</a>
					</li>';
			};
		}

		print $out;
		die();
	}

	/*
	 * Remove post from group
	 */
	public static function wpcf_remove_postmeta_group_ajax(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		delete_post_meta($_POST['id'], '_wpcf_access_group');
	}

	/*
	 * Set group for post
	 */
	public static function wpcf_select_access_group_for_post_ajax(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$group = get_post_meta($_POST['id'], '_wpcf_access_group', true);
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		$out = '<form method="#" id="wpcf-access-set_error_page">';
		$checked = ( isset($group) && !empty($group) )?' checked="checked" ':'';
		$out .= '
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-existing-group" value="existing_group" '.$checked.'>
					<label for="wpcf-access-group-method-existing-group">'. __('Select existing group','wpcf_access').'</label>
					<select name="wpcf-access-existing-groups" class="hidden">
						<option value="">- '.__('None','wpcf_access').' -</option>';

    	$process = true;
		foreach ($settings_access as $permission_slug => $data){
			if ( strpos( $permission_slug, 'wpcf-custom-group-') === 0 ){
				$checked = ( $permission_slug == $group )?' selected="selected" ':'';
				$out .= '
						<option value="'.$permission_slug.'"'.$checked.'>'.$data['title'].'</option>';
			}
		}
		$out .= '
					</select>
				</p>
		';

		$out .= '
				<p>
					<input type="radio" name="wpcf-access-group-method" id="wpcf-access-group-method-new-group" value="new_group">
					<label for="wpcf-access-group-method-new-group">'. __('Create new group','wpcf_access').'</label>
					<input type="text" name="wpcf-access-new-group" class="hidden">
					<div class="js-error-container"></div>
				</p>';

		$out .= '</form>';
		ob_start();
		?>
		<div class="wpv-dialog js-wpcf-add-access-error-page">

			<div class="wpv-dialog-header">
				<h2><?php _e('Assign group to Post','wpcf_access')?></h2>
			</div>

			<div class="wpv-dialog-content">
				<?php echo $out;?>
			</div>

			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e('Cancel', 'wpcf_access')?></button>
				<button class="button button-primary js-wpcf-process-access-assign-post-to-group"
						data-error="<?php echo esc_js(__('Group already exists','wpcf_access'))?>"
						data-id="<?php echo $_POST['id']?>"><?php _e('Assign group', 'wpcf_access')?>
				</button>
			</div>

    	</div>
		<?php
		$out = ob_get_contents();
			ob_end_clean();
		print $out;
		die();
	}

	/*
	 *
	 */
	public static function wpcf_process_select_access_group_for_post_ajax() {
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$model = TAccess_Loader::get('MODEL/Access');
		$settings_access = $model->getAccessTypes();

		if ( $_POST['methodtype'] == 'existing_group' ){

			update_post_meta( $_POST['id'], '_wpcf_access_group', $_POST['group']);
			if ( $_POST['group'] != ''){
			$message = sprintf(
					__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf_access' ), $settings_access[$_POST['group']]['title'] ).'</p>
						<p><a href="admin.php?page=types_access#'.$_POST['group'].'">'.
					sprintf(__( 'Edit %s group privileges', 'wpcf_access' ), $settings_access[$_POST['group']]['title']).'</a></p>';
			}else{
				$message =  __( 'No group selected.', 'wpcf_access' );
			}
		}else{
			$nice = sanitize_title('wpcf-custom-group-'.$_POST['new_group']);
			$groups[$nice] = array(
				'title' => $_POST['new_group'],
				'mode' => 'permissions',
				'__permissions' => array( 'read' => array('role' => 'guest')),
				'permissions' => array( 'read' => array('role' => 'guest')),
			);

			$process = true;
			foreach ($settings_access as $permission_slug => $data){
				if ( $permission_slug == $nice ){
					$process = false;
				}
			}

			if ( !$process ){
				echo 'error';
				die();
			}
			update_post_meta( $_POST['id'], '_wpcf_access_group', $nice);
			TAccess_Loader::load('CLASS/Admin_Edit');
			$roles = Access_Helper::wpcf_get_editable_roles();
			$settings_access = array_merge( $settings_access, $groups);
			$model->updateAccessTypes( $settings_access );
			$message = sprintf(
					__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf_access' ), $_POST['new_group'] ).'</p>
						<p><a href="admin.php?page=types_access#'.$nice.'">'.sprintf(__( 'Edit %s group privileges', 'wpcf_access' ), $_POST['new_group']).'</a></p>';
		}

		print $message;
		die();
	}

	/*
	 * Show popup for custom roles: caps
	 */
	public static function wpcf_access_change_role_caps_ajax(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }
		$role = $_POST['role'];
		$out = '<form method="#" id="wpcf-access-set_error_page">';
		$wordpress_caps = getDefaultWordpressCaps();
		$model = TAccess_Loader::get('MODEL/Access');

		$default_caps = getDefaultCaps();
		$default_wordpress_caps = getDefaultWordpressCaps();
		$access_roles = $model->getAccessRoles();
        $role_data = get_role($role);
		$role_caps = $role_data->capabilities;
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		//$toolset_caps_list = get_toolset_caps();



		$toolset_caps_limit = array();
		$out .= '<h3>'.__('General capabilities','wpcf_access').'</h3>';
		foreach ($default_wordpress_caps as $cap => $cap_info){
			$checked = ( isset($role_caps[$cap]) && $role_caps[$cap] == 1 )?' checked="checked" ':'';
			$out .= '<p><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" '.$checked.'>
			'.$cap;
			if ( !empty($cap_info[0]) ){
				$out .= '<br><small>'.$cap_info[0];
				if ( !empty($cap_info[1]) ){
					$out .= ' ('.$cap_info[1].')';
				}
				$out .= '</small>';
			}
			$out .= '</label></p>';
		}

		//Check if WPMl is installed
		if( defined('ICL_SITEPRESS_VERSION') and ICL_SITEPRESS_VERSION >= 3.1){
			$out .= '<h3>'.__('WPML capabilities','wpcf_access').'</h3>';
			foreach ($wpml_caps_list as $cap => $cap_info){
				$checked = ( isset($role_caps[$cap]) && $role_caps[$cap] == 1 )?' checked="checked" ':'';
				$out .= '<p><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" '.$checked.'>
				'.$cap.'<br><small>'. $cap_info .'</small></label></p>';
			}
		}
		/*
		//TODO, add check for all plugins if activated
		//Add cups when plugin activated
		$out .= "<h3><span class='toolsetIcon'></span>Toolset capabilities</h3>";
			foreach ($toolset_caps_list as $cap => $cap_info){
				$checked = ( isset($role_caps[$cap]) && $role_caps[$cap] == 1 )?' checked="checked" ':'';
				$out .= '<p><span class="toolsetIcon_'.$cap.'"></span><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" '.$checked.'>
				'.$cap.'<br><small>'. $cap_info .'</small></label></p>';
			}
		*/
		//Show woocommerce caps if woocommerce installed
		$is_woocommerce = wpcf_check_if_woocommerce();
		if ( $is_woocommerce ){

			$out .= '<h3>'.__('Woocommerce capabilities','wpcf_access').'</h3>';
			foreach ($wocommerce_caps as $cap => $cap_info){
				$checked = ( isset($role_caps[$cap]) && $role_caps[$cap] == 1 )?' checked="checked" ':'';
				$out .= '<p><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" '.$checked.'>
				'.$cap.'<br><small>'. $cap_info .'</small></label></p>';
			}
		}

		$out .= '<h3>'.__('Custom capabilities','wpcf_access').'</h3>';
		$custom_caps = get_option('wpcf_access_custom_caps');
		$out .= '<div class="js-wpcf-list-custom-caps">';
		if ( is_array($custom_caps) && count($custom_caps) > 0 ){
			foreach ($custom_caps as $cap => $cap_info){
				$checked = ( isset($role_caps[$cap]) && $role_caps[$cap] == 1 )?' checked="checked" ':'';
				$out .= '<p id="wpcf-custom-cap-'.$cap.'">'.
				'<label for="cap_'.$cap.'">'.
				'<input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" '.$checked.'>
				'.$cap.'<br><small>'. $cap_info .'</small></label>'.
				'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_'.$cap.'">'.
				'<a href="" data-object="wpcf-custom-cap-'.$cap.'" data-remove="0" data-cap="'.$cap.'">Delete</a><span class="ajax-loading spinner"></span>'.
				'</span>'.
				'</p>';
			}
		}
		$hidden = count($custom_caps) > 0 ?' hidden':'';
		$out .= '<p class="js-wpcf-no-custom-caps '. $hidden .'">'.__('No custom capabilities','wpcf_access').'</p>';
		$out .= '</div>';
		ob_start();
		?>
		<div class="wpcf-create-new-cap-div js-wpcf-create-new-cap-div">
			<p>
				<button class="button js-wpcf-access-add-custom-cap"><?php _e('New custom capability','wpcf_access')?></button>
			</p>
			<div class="js-wpcf-create-new-cap-form hidden">
				<p>
					<label for="js-wpcf-new-cap-slug"><?php _e('Capability name','wpcf_access')?>:</label>
					<input type="text" name="new_cap_name" id="js-wpcf-new-cap-slug">
				</p>
				<p>
					<label for="js-wpcf-new-cap-description"><?php _e('Capability description','wpcf_access')?>:</label>
					<input type="text" name="new_cap_description" id="js-wpcf-new-cap-description">
				</p>
				<p class="wpcf-access-buttons-wrap wpcf-access-buttons-wrap-left">
					<button class="button js-wpcf-new-cap-cancel"><?php _e('Cancel','wpcf_access')?></button>
					<button class="button button-primary js-wpcf-new-cap-add" disabled="disabled" data-error="<?php _e('Only lowercase letters, numbers and _ allowed in capability name','wpcf_access')?>"><?php _e('Add','wpcf_access')?></button>
					<span class="ajax-loading spinner js-new-cap-spinner"></span>
				</p>
			</div>
		</div>
		<input type="hidden" value="<?php echo $role?>" class="js-wpcf-current-edit-role">
		<?php
		$out .= ob_get_contents();
		ob_end_clean();
		$out .= '</form>';

		ob_start();
		?>
		<div class="wpv-dialog js-wpcf-add-access-error-page">

        <div class="wpv-dialog-header">
            <h2><?php _e('Change permissions','wpcf_access')?></h2>
        </div>

        <div class="wpv-dialog-content">
            <?php echo $out;?>
        </div>

        <div class="wpv-dialog-footer">
            <button class="button js-dialog-close"><?php _e('Cancel','wpcf_access')?></button>
            <button class="button button-primary js-wpcf-access-role-caps-process" data-role="<?php echo $role?>"><?php _e('Change permissions','wpcf_access')?></button>
        </div>

    	</div>
		<?php
		$out = ob_get_contents();
   		ob_end_clean();
		print $out;
		die();
	}

	/*
	 * Proccess custom role caps
	 */
	public static function wpcf_process_change_role_caps_ajax(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$role = $_POST['role'];
		$caps = '';
		if ( isset($_POST['caps']) ){
			$caps = $_POST['caps'];
		}

		TAccess_Loader::load('CLASS/Admin_Edit');
        $model = TAccess_Loader::get('MODEL/Access');

		$default_caps = getDefaultCaps();
		$default_wordpress_caps = $default_caps[10];
		$access_roles = $model->getAccessRoles();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$custom_caps = get_option('wpcf_access_custom_caps');
		//$toolset_caps_list = get_toolset_caps();

		$role_data = get_role($role);
		for ($i=0, $caps_limit = count($default_wordpress_caps);$i<$caps_limit;$i++)
        {
            if ( isset( $access_roles[$role]['caps'][$default_wordpress_caps[$i]] ) ){
            	unset( $access_roles[$role]['caps'][$default_wordpress_caps[$i]] );
				$role_data->remove_cap($default_wordpress_caps[$i]);
			}
		}
		foreach ($wocommerce_caps as $cap => $cap_info){
			if ( isset( $access_roles[$role]['caps'][$cap] ) ){
				unset( $access_roles[$role]['caps'][$cap] );
				$role_data->remove_cap($cap);
			}
		}
		foreach ($wpml_caps_list as $cap => $cap_info){
			if ( isset( $access_roles[$role]['caps'][$cap] ) ){
				unset( $access_roles[$role]['caps'][$cap] );
				$role_data->remove_cap($cap);
			}
		}
		if ( is_array($custom_caps) ){
			foreach ($custom_caps as $cap => $cap_info){
				if ( isset( $access_roles[$role]['caps'][$cap] ) ){
					unset( $access_roles[$role]['caps'][$cap] );
					$role_data->remove_cap($cap);
				}
			}
		}
		/*
		foreach ($toolset_caps_list as $cap => $cap_info){
			if ( isset( $access_roles[$role]['caps'][$cap] ) ){
				unset( $access_roles[$role]['caps'][$cap] );
				$role_data->remove_cap($cap);
			}
		}
		*/

		for ($i=0, $caps_limit=count($caps);$i<$caps_limit;$i++){
			$cap = str_replace('cap_','',$caps[$i]);
			$access_roles[$role]['caps'][$cap] = true;
			$role_data->add_cap($cap);
		}
        $model->updateAccessRoles($access_roles);

		die();
	}

	/*
	 * Show popup for custom roles: caps (read only)
	 */
	public static function wpcf_access_show_role_caps_ajax(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$role = $_POST['role'];

		$out = '<form method="#">';
		$role_info = get_role($role);
		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$custom_caps = get_option('wpcf_access_custom_caps');

		foreach ($role_info->capabilities as $cap => $cap_info){
			if ( !preg_match("/level_[0-9]+/",$cap) ){
			$out .= '<p><label for="cap_'.$cap.'"><input type="checkbox" checked="checked" value="" disabled id="cap_'.$cap.'" >
			'.$cap;
			if ( isset($default_wordpress_caps[$cap]) ){
				$out .= '<br><small>'.$default_wordpress_caps[$cap][0];
				if ( !empty($default_wordpress_caps[$cap][1]) ){
					$out .= ' ('.$default_wordpress_caps[$cap][1].')';
				}
				if ( !empty($wocommerce_caps[$cap][1]) ){
					$out .= ' ('.$wocommerce_caps[$cap][1].')';
				}
				if ( !empty($wpml_caps_list[$cap][1]) ){
					$out .= ' ('.$wpml_caps_list[$cap][1].')';
				}

				$out .= '</small>';
			}
			if ( isset($custom_caps[$cap]) ){
				$out .= '<br><small>'.$custom_caps[$cap].'</small>';
			}
			$out .= '</label></p>';
			}
		}

		$out .= '</form>';

		ob_start();
		?>

		<div class="wpv-dialog js-wpcf-add-access-error-page">
			<div class="wpv-dialog-header">
				<h2><?php _e('Role permissions','wpcf_access')?></h2>
			</div>
			<div class="wpv-dialog-content">
				<?php echo $out;?>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e('Close','wpcf_access')?></button>
			</div>
    	</div>

		<?php
		$out = ob_get_contents();
   		ob_end_clean();
		echo $out;
		die();
	}

	/*
	 * Create new custom capability
	 */
	public static function wpcf_create_new_cap(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$custom_caps = get_option('wpcf_access_custom_caps');

		if ( !is_array($custom_caps) ){
			$custom_caps = array();
		}
		$output = '';
		$model = TAccess_Loader::get('MODEL/Access');
		$default_caps = getDefaultCaps();
		$default_wordpress_caps = getDefaultWordpressCaps();
		$wocommerce_caps = get_woocommerce_caps();
		$wpml_caps_list = get_wpml_caps();
		$cap = $_POST['cap_name'];
		$description = $_POST['cap_description'];

		if ( isset($custom_caps[$cap]) || isset($default_wordpress_caps[$cap]) || isset($wocommerce_caps[$cap]) || isset($wpml_caps_list[$cap]) ){
			$output = array('error', __('This capability already exists in your site','wpcf_access'));
		}
		else{
			$custom_caps[$cap] = $description;
			update_option( 'wpcf_access_custom_caps', $custom_caps);
			$input = '<p id="wpcf-custom-cap-'.$cap.'"><label for="cap_'.$cap.'"><input type="checkbox" name="current_role_caps[]" value="cap_'.$cap.'" id="cap_'.$cap.'" checked="checked">
				'.$cap.'<br><small>'. $description .'</small></label>'.
				'<span class="js-wpcf-remove-custom-cap js-wpcf-remove-custom-cap_'.$cap.'">'.
				'<a href="" data-object="wpcf-custom-cap-'.$cap.'" data-remove="0" data-cap="'.$cap.'">Delete</a><span class="ajax-loading spinner"></span>'.
				'</span>'.
				'</p>';
			$output = array(1, $input);
		}


		echo json_encode($output);
		die();
	}

	/*
	 * Create new custom capability
	 */
	public static function wpcf_delete_cap(){
		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
                        'wpcf-access-error-pages')) {
            die('verification failed');
        }

		$custom_caps = get_option('wpcf_access_custom_caps');

		if ( !is_array($custom_caps) ){
			$custom_caps = array();
		}
		$output = '';
		$edit_role = $_POST['edit_role'];
		$model = TAccess_Loader::get('MODEL/Access');
		$access_roles = $model->getAccessRoles();
		$cap = $_POST['cap_name'];
		$remove = $_POST['remove'];
		$roles = '';
		if ( $remove == 0 ){
			foreach ($access_roles as $role => $role_info){
				if ( isset($role_info['caps'][$cap])  && $role != $edit_role ){
					$roles[] = $role;
				}
			}
			if ( is_array($roles) ){
				$roles = implode(", ", $roles);
				$output = '<div class="js-wpcf-removediv js-removediv_'.$cap.'">'
						. '<p>' . __( 'The following role(s) have this capability:', 'wpcf_access' ) . '</p>' . $roles;
				$output .= '<p><button class="js-wpcf-remove-cap-cancel button" data-cap="'.$cap.'">'.__( 'Cancel', 'wpcf_access' ).'</button> '
						. '<button class="js-wpcf-remove-cap-anyway button-primary button" data-remove="1" data-object="'.$_POST['remove_div'].'" data-cap="'.$cap.'">' . __( 'Delete anyway', 'wpcf_access' ) . '</button> '
						. '<span class="ajax-loading spinner"></span>'
						. '</p></div>';
			}
			else{
				foreach ($access_roles as $role => $role_info){
					if ( isset($role_info['caps'][$cap]) ){
						unset($access_roles[$role]['caps'][$cap]);
					}
				}
				$model->updateAccessRoles($access_roles);
				unset($custom_caps[$cap]);
				update_option( 'wpcf_access_custom_caps', $custom_caps);
				$output = 1;
			}
		}
		else{
			foreach ($access_roles as $role => $role_info){
				if ( isset($role_info['caps'][$cap]) ){
					unset($access_roles[$role]['caps'][$cap]);
				}
			}
			$model->updateAccessRoles($access_roles);
			unset($custom_caps[$cap]);
			update_option( 'wpcf_access_custom_caps', $custom_caps);
			$output = 1;
		}
		echo $output;
		die();
	}


}

// init on load
Access_Ajax_Helper::init();