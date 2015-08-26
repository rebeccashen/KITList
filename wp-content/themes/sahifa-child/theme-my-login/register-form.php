<?php
/*
If you would like to edit this file, copy it to your current theme's directory and edit it there.
Theme My Login will always look in your theme's directory first, before using this default template.
*/
?>
<div class="login" id="theme-my-login<?php $template->the_instance(); ?>">
	<?php $template->the_action_template_message( 'register' ); ?>
	<?php $template->the_errors(); ?>
	<form name="registerform" id="registerform<?php $template->the_instance(); ?>" action="<?php $template->the_action_url( 'register' ); ?>" method="post">

        <p>
            <label for="user_login<?php $template->the_instance(); ?>"><?php _e( 'Username', 'theme-my-login' ); ?></label>
            <input type="text" name="user_login" id="user_login<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value( 'user_login' ); ?>" size="20" />
        </p>

        <p>
            <label for="first_name<?php $template->the_instance(); ?>"><?php _e( 'First name', 'theme-my-login' ) ?></label>
            <input type="text" name="first_name" id="first_name<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value( 'first_name' ); ?>" size="20" tabindex="20" />
        </p>
        <p>
            <label for="last_name<?php $template->the_instance(); ?>"><?php _e( 'Last name', 'theme-my-login' ) ?></label>
            <input type="text" name="last_name" id="last_name<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value( 'last_name' ); ?>" size="20" tabindex="20" />
        </p>

        <p>
            <label for="user_email<?php $template->the_instance(); ?>"><?php _e( 'E-mail', 'theme-my-login' ); ?></label>
            <input type="text" name="user_email" id="user_email<?php $template->the_instance(); ?>" class="input" value="<?php $template->the_posted_value( 'user_email' ); ?>" size="20" />
        </p>

        <p>
        <div class="user-kit-profile-role">
            <label for="wpcf-profile-roles" class="cred-label">Profile Roles</label>
                <div data-item_name="select-wpcf-profile-roles">
                    <select id="kit_user_meta_role"  class="wpt-form-select form-select select" data-wpt-type="select" name="wpcf-profile-roles">
                        <option value="wpcf-fields-select-option-54fa44f5d8089fb9d692da9b44b098d6-1" class="wpt-form-option form-option option" data-types-value="1" data-wpt-type="option" data-wpt-id="cred_form_744_1_cred_form_744_1-select-1-1434811159" data-wpt-name="wpcf-profile-roles">Job Seeker</option>
                        <option value="wpcf-fields-select-option-1f2337aecdcb5c0bb2e995b572481d9b-1" class="wpt-form-option form-option option" data-types-value="2" data-wpt-type="option" data-wpt-id="cred_form_744_1_cred_form_744_1-select-1-1434811159" data-wpt-name="wpcf-profile-roles">Consultant</option>
                        <option value="wpcf-fields-select-option-42576ceb6094814dd3c53066cc69d82d-1" class="wpt-form-option form-option option" data-types-value="3" data-wpt-type="option" data-wpt-id="cred_form_744_1_cred_form_744_1-select-1-1434811159" data-wpt-name="wpcf-profile-roles">HR Professional or In-House Recruiter</option>
                        <option value="wpcf-fields-select-option-010b888ea7acd21cd1e433ab9180f9d7-1" class="wpt-form-option form-option option" data-types-value="4" data-wpt-type="option" data-wpt-id="cred_form_744_1_cred_form_744_1-select-1-1434811159" data-wpt-name="wpcf-profile-roles">Employer/Hiring Manager</option>
                        <option value="wpcf-fields-select-option-bc91579825a2b59cbe3e5f6e43be4c2c-1" class="wpt-form-option form-option option" data-types-value="5" data-wpt-type="option" data-wpt-id="cred_form_744_1_cred_form_744_1-select-1-1434811159" data-wpt-name="wpcf-profile-roles">Other</option>
                    </select>
                </div>
        </div>


</p>


        <?php
       do_action( 'register_form' );
        do_action('user_register', 'update_kit_meta');
        ?>

		<p id="reg_passmail<?php $template->the_instance(); ?>"><?php echo apply_filters( 'tml_register_passmail_template_message', __( 'A password will be e-mailed to you.', 'theme-my-login' ) ); ?></p>



        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit<?php $template->the_instance(); ?>" value="<?php esc_attr_e( 'Register', 'theme-my-login' ); ?>" />
            <input type="hidden" name="redirect_to" value="<?php $template->the_redirect_url( 'register' ); ?>" />
            <input type="hidden" name="instance" value="<?php $template->the_instance(); ?>" />
            <input type="hidden" name="action" value="register" />
        </p>


	</form>
	<?php $template->the_action_links( array( 'register' => false ) ); ?>
</div>
