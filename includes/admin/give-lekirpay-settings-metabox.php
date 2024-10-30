<?php

class Give_lekirpay_Settings_Metabox {
  static private $instance;

  private function __construct() {

  }

  static function get_instance() {
    if (null === static::$instance) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  /**
   * Setup hooks.
   */
  public function setup_hooks() {
    if (is_admin()) {
      add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));
      add_filter('give_forms_lekirpay_metabox_fields', array($this, 'give_lekirpay_add_settings'));
      add_filter('give_metabox_form_data_settings', array($this, 'add_lekirpay_setting_tab'), 0, 1);
    }
  }

  public function add_lekirpay_setting_tab($settings) {
    if (give_is_gateway_active('lekirpay')) {
      $settings['lekirpay_options'] = apply_filters('give_forms_lekirpay_options', array(
        'id'        => 'lekirpay_options',
        'title'     => __('Lekirpay', 'give'),
        'icon-html' => '<span class="give-icon give-icon-purse"></span>',
        'fields'    => apply_filters('give_forms_lekirpay_metabox_fields', array()),
      ));
    }

    return $settings;
  }

  public function give_lekirpay_add_settings($settings) {

    // Bailout: Do not show offline gateways setting in to metabox if its disabled globally.
    if (in_array('lekirpay', (array) give_get_option('gateways'))) {
      return $settings;
    }

    $is_gateway_active = give_is_gateway_active('lekirpay');

    //this gateway isn't active
    if (!$is_gateway_active) {
      //return settings and bounce
      return $settings;
    }

    //Fields
    $check_settings = array(
		
		array(
                'name' => __('Lekirpay', 'give-lekirpay'),
                'desc' => __('Do you want to customize the donation instructions for this form?', 'give-lekirpay'),
                'id' => 'lekirpay_customize_lekirpay_donations',
                'type' => 'radio_inline',
                'default' => 'global',
                'options' => apply_filters('give_forms_content_options_select', array(
                    'global' => __('Global Option', 'give-lekirpay'),
                    'enabled' => __('Customize', 'give-lekirpay'),
                    'disabled' => __('Disable', 'give-lekirpay'),
                )
                ),
            ),
			   array(
            'name'        => __('Client ID', 'give-lekirpay'),
            'desc'        => __('Enter your Client ID, found in your lekirpay Account Settings.', 'give-lekirpay'),
            'id'          => 'lekirpay_client_id',
            'type'        => 'text',
            'row_classes' => 'give-lekirpay-key',
      ),
          array(
            'name'        => __('LekirKey', 'give-lekirpay'),
            'desc'        => __('Enter your LekirKey, found in your lekirpay Account Settings.', 'give-lekirpay'),
            'id'          => 'lekirpay_lekirKey',
            'type'        => 'text',
            'row_classes' => 'give-lekirpay-key',
          ),
			
		  array(
            'name'        => __('Lekir-signature Key', 'give-lekirpay'),
            'desc'        => __('Enter your Lekir-signature Key, found in your Lekirpay Account Settings.', 'give-lekirpay'),
            'id'          => 'lekirpay_sonix_signature_key',
            'type'        => 'text',
            'row_classes' => 'give-lekirpay-key',
          ),
			
		  array(
            'name'        => __('Group ID', 'give-lekirpay'),
            'desc'        => __('Enter your Group ID, found in your Lekirpay Account Settings.', 'give-lekirpay'),
            'id'          => 'lekirpay_group_id',
            'type'        => 'text',
            'row_classes' => 'give-lekirpay-key',
          ),
			
		array(
			'name' => __('Server End Point', 'give-lekirpay'),
			'desc' => __('Enable Live Server, disable will set to default sandbox server', 'give-lekirpay'),
			'id' => 'lekirpay_server_end_point',
			'type' => 'radio_inline',
			'default' => 'disabled',
			'options' => array(
				'disabled' => __('Disabled', 'give-lekirpay'),
				'enabled' => __('Enabled', 'give-lekirpay'),
			),
			),
    
    );

    return array_merge($settings, $check_settings);
  }

  public function enqueue_js($hook) {
    if ('post.php' === $hook || $hook === 'post-new.php') {
      wp_enqueue_script('give_lekirpay_each_form', GIVE_lekirpay_PLUGIN_URL . '/includes/js/meta-box.js');
    }
  }

}
Give_lekirpay_Settings_Metabox::get_instance()->setup_hooks();