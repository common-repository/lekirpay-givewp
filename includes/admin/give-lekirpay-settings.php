<?php

/**
 * Class Give_lekirpay_Settings
 *
 * @since 3.1.2
 */
class Give_lekirpay_Settings
{

    /**
     * @access private
     * @var Give_lekirpay_Settings $instance
     */
    private static $instance;

    /**
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @access private
     *
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_lekirpay_Settings constructor.
     */
    private function __construct()
    {

    }

    /**
     * get class object.
     *
     * @return Give_lekirpay_Settings
     */
    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks()
    {

        $this->section_id = 'lekirpay';
        $this->section_label = __('lekirpay', 'give-lekirpay');

        if (is_admin()) {
            // Add settings.
            add_filter('give_get_settings_gateways', array($this, 'add_settings'), 99);
            add_filter('give_get_sections_gateways', array($this, 'add_sections'), 99);
        }
    }

    /**
     * Add setting section.
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_sections($sections)
    {
        $sections[$this->section_id] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings($settings)
    {
        $current_section = give_get_current_setting_section();

        if ($current_section != 'lekirpay') {
            return $settings;
        }

        $give_lekirpay_settings = array(
            array(
                'name' => __('Lekirpay Settings', 'give-lekirpay'),
                'id' => 'give_title_gateway_lekirpay',
                'type' => 'title',
            ),
         array(
            'name'        => __('Client ID', 'give-lekirpay'),
            'desc'        => __('Enter your Client ID, found in your Lekirpay Account Settings.', 'give-lekirpay'),
            'id'          => 'lekirpay_client_id',
            'type'        => 'text',
            'row_classes' => 'give-lekirpay-key',
      ),
          array(
            'name'        => __('LekirKey', 'give-lekirpay'),
            'desc'        => __('Enter your LekirKey, found in your Lekirpay Account Settings.', 'give-lekirpay'),
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
         
          
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_lekirpay',
            ),
        );

        return array_merge($settings, $give_lekirpay_settings);
    }
}

Give_lekirpay_Settings::get_instance()->setup_hooks();
