<?php

/**
 * Base class to handle Google reCAPTCHA API functionality.
 * 
 * @since 4.5.3
 * @package AviaFramework
 */
if( ! class_exists( 'av_google_recaptcha' ) )
{
    class av_google_recaptcha {    
        const API_URL = 'https://www.google.com/recaptcha/api.js';
        const API_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';		
        const AJAX_VERIFY_NONCE = 'av_google_recaptcha_verify_nonce';
        const AJAX_SUBMISSION_NONCE = 'av_google_recaptcha_submission_nonce';

        /**
		 * Holds the instance of this class
		 * 
		 * @since 4.5.4
		 * @var av_google_recaptcha 
		 */
		static private $_instance = null;

        /**
         * The version of this class.
         *
         * @since    4.5.4
         * @access   private
         * @var      string    $version   
         */
        public $version;

        /**
         * The google reCAPTCHA keys.
         *
         * @since    4.5.4
         * @access   private
         * @var      array   
         */
        private $sitekeys;

        /**
         * Notification for unverified users.
         *
         * @since    4.5.4
         * @access   private
         * @var      array   
         */
        private $notice;
        
        /**
         * The google reCAPTCHA api url.
         *
         * @since    4.5.4
         * @access   private
         * @var      string   
         */
        private $api_url;

        /**
         * The google reCAPTCHA site verify url.
         *
         * @since    4.5.4
         * @access   private
         * @var      string   
         */
        private $verify_url;

        /**
         * The google reCAPTCHA api version.
         *
         * @since    4.5.4
         * @access   private
         * @var      string   
         */
        private $api_vn;

        /**
         * The google reCAPTCHA status.
         *
         * @since    4.5.4
         * @access   private
         * @var      string   
         */
        public $verified;

        /**
		 * Return the instance of this class
		 * 
		 * @since 4.5.4
		 * @return av_google_recaptcha
		 */
		static public function instance()
		{
			if( is_null( av_google_recaptcha::$_instance ) ) {
				av_google_recaptcha::$_instance = new av_google_recaptcha();
			}
			
			return av_google_recaptcha::$_instance;
		}	

        /**
         * Initialize the class and set its properties.
         *
         * @since    4.5.4
         * @param      string    $plugin_name       The name of the plugin.
         * @param      string    $version    The version of this plugin.
         */
        public function __construct() {
            $theme = wp_get_theme();
            $version = $theme->get( 'Version' );

            $this->version = $version;
            $this->api_url = av_google_recaptcha::API_URL;
            $this->verify_url = av_google_recaptcha::API_VERIFY_URL; 

            add_filter( 'avf_option_page_data_init', array( $this, 'register_admin_options' ), 10, 1 );
            add_filter( 'avf_ajax_form_class', array( $this, 'modify_form_class' ), 10, 3 );

            add_action( 'after_setup_theme', array( $this, 'get_api_version' ), 10 );
            add_action( 'after_setup_theme', array( $this, 'get_api_keys' ), 15 );
            
            add_filter( 'avf_sc_contact_form_elements', array( $this, 'create_decoy' ), 10, 2 );
            add_filter( 'avf_contact_form_submit_button_attr', array( $this, 'modify_button_attributes' ), 10, 3 );

            add_action( 'wp_enqueue_scripts', array( $this, 'register_recaptcha_scripts' ), 10 );
            add_action( 'wp_enqueue_scripts', array( $this, 'register_api_script' ), 10 );
            
            add_action( 'wp_ajax_avia_ajax_recaptcha_verify', array( $this, 'verify_token' ), 10 );
            add_action( 'wp_ajax_nopriv_avia_ajax_recaptcha_verify', array( $this, 'verify_token' ), 10 );
        }
        
        public function register_api_script()
        {
            if( ! $this->is_recaptcha_active() ) {
                return false;
            }

            $api_key = $this->get_public_key();
            $api_vn = $this->api_vn;
            $api_url = $this->api_url;

            $params = array(
                'onload' => 'aviaRecaptchaRender',
                'render' => $api_vn == 'v2' ? 'explicit' : $api_key,
            );

            $api_url = add_query_arg( $params, $api_url );

            wp_register_script( 'avia-recaptcha-api', $api_url, array( 'avia-default' ), '1.0.0', false);
            wp_enqueue_script( 'avia-recaptcha-api' );
        }

        public function register_recaptcha_scripts() {
            if( ! $this->is_recaptcha_active() ) {
                return false;
            }

            $api_vn = $this->api_vn;
            $notice = $this->get_notice();
            $template_url = get_template_directory_uri();

            wp_register_script(
                'avia-recaptcha',
                AVIA_JS_URL . 'avia_recaptcha.js',
                array( 'jquery' ),
                $this->version,
                false
            );

            wp_enqueue_script( 'avia-recaptcha' );

            wp_localize_script(
                'avia-recaptcha',
                'avia_recaptcha',
                array(
                    'verify_nonce' => wp_create_nonce( av_google_recaptcha::AJAX_VERIFY_NONCE ),
                    'submission_nonce' => wp_create_nonce( av_google_recaptcha::AJAX_SUBMISSION_NONCE ),
            ) );
        }

        public function get_api_version() {
            $api_vn = avia_get_option( 'avia_recaptcha_version' );
            $vn = 'v2';

            if( $api_vn == 'avia_recaptcha_v3' ) {
                $vn = 'v3';
            }
           
            if( $api_vn == '' ) {
                $vn = null;
            }

            $this->api_vn = $vn;
        }

        public function get_api_keys() {
            $secret_key = avia_get_option( 'avia_recaptcha_skey_' . $this->api_vn );
            $public_key = avia_get_option( 'avia_recaptcha_pkey_' . $this->api_vn );

            if( empty( $secret_key ) || empty( $public_key ) ) {
                return new WP_Error( 'enfold-recaptcha-sitekeys-missing', __( "The recaptcha keys key are missing.", "avia_framework" ) );
            }

            $this->sitekeys = array(
                'public' => $public_key,
                'secret' => $secret_key, 
            );

            if( $this->api_vn == 'v3' ) {
                $this->sitekeys['rekey'] = $public_key = avia_get_option( 'avia_recaptcha_pkey_v2' );
            }     
        }

        public function get_public_key() {
            if ( empty( $this->sitekeys ) || ! is_array( $this->sitekeys ) ) {
                return false;
            }
        
            return $this->sitekeys['public'];
        }

        public function get_secret_key() {
            if ( empty( $this->sitekeys ) || ! is_array( $this->sitekeys ) ) {
                return false;
            }

            return $this->sitekeys['secret'];
        }

        public function get_reverify_key() {
            if ( $this->api_vn == 'v2' ) {
                return false;
            }
        
            return $this->sitekeys['rekey'];
        }

        public function get_notice() {
            $notice = avia_get_option( 'avia_recaptcha_notice' );
            $this->notice = $notice;
            return $notice;
        }

        public function is_recaptcha_active() {
            $api_vn = $this->api_vn;

            if( $api_vn == '' ) {
                return false;
            }

            $secret_key = $this->get_secret_key();
            $public_key = $this->get_public_key();

            return $public_key && $secret_key;
        }

        public function modify_form_class( $class, $id, $params ) {
            if( ! $this->is_recaptcha_active() || $params['mailchimp'] ) {
                return $class;
            }

            $class .= ' av-form-recaptcha av-form-labels-style';

            
            if( $this->api_vn == 'v3' ) {
                $class .= ' av-form-input-visible';
            }

            return $class;
        }

        public function modify_button_attributes( $atts, $id, $params ) {
            if ( empty( $this->sitekeys ) || ! is_array( $this->sitekeys ) || $params['mailchimp'] ) {
                return $atts;
            }

            $api_vn = $this->api_vn;
            $public_key = $this->get_public_key();
            $notice = $this->get_notice();
            
            $atts .= ' disabled=disabled';
            if( $notice != '' ) $atts .= ' data-notice=' . urlencode( $notice );
            $atts .= ' data-sitekey=' . $public_key;
            $atts .= ' data-theme=light';
            $atts .= ' data-size=normal';
            $atts .= ' data-tabindex=' . $id;
            $atts .= ' data-callback=aviaRecaptchaSuccess';
            $atts .= ' data-expired-callback=aviaRecaptchaSuccess';
            $atts .= ' data-vn=' . $api_vn;
            
            return $atts;
        } 

        public function register_admin_options( $elements ) {
            $elements[] =	array(
                "name" => __("Google ReCAPTCHA",'avia_framework'),
                "desc" => __("Add a Google reCAPTCHA widget to the theme's contact form element.",'avia_framework'),
                "id" => "avia_recaptcha",
                "std" => "",
                "slug"	=> "google",
                "type" => "heading",
                "nodescription"=>true);

            $recaptcha_admin = 'https://www.google.com/recaptcha/admin';

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Select Google reCAPTCHA Version", 'avia_framework'),
                "desc" 	=> 
                __("Choose the type of reCAPTCHA and then fill in the following fields with your <a href='{$recaptcha_admin}'>new API key pair</a>.", 'avia_framework'),
                "id" 	=> "avia_recaptcha_version",
                "type" 	=> "select",
                "std" 	=> "",
                "subtype" => array( __('reCAPTCHA v2', 'avia_framework') => 'avia_recaptcha_v2',
                                    __('reCAPTCHA v3', 'avia_framework') => 'avia_recaptcha_v3',
                                    ));

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Site Key", 'avia_framework'),
                "desc" 	=> __('Enter the v2 API public or site key here.', 'avia_framework'),
                "id" 	=> "avia_recaptcha_pkey_v2",
                "type" 	=> "text",
                "required" => array('avia_recaptcha_version','avia_recaptcha_v2'),
                );

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Secret Key", 'avia_framework'),
                "desc" 	=> __("Enter the v2 API secret key here.", 'avia_framework'),
                "id" 	=> "avia_recaptcha_skey_v2",
                "required" => array('avia_recaptcha_version','avia_recaptcha_v2'),
                "type" 	=> "text"
                );

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Site Key", 'avia_framework'),
                "desc" 	=> __('Enter the v3 API public or site key here.', 'avia_framework'),
                "id" 	=> "avia_recaptcha_pkey_v3",
                "type" 	=> "text",
                "required" => array('avia_recaptcha_version','avia_recaptcha_v3'),
                );

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Secret Key", 'avia_framework'),
                "desc" 	=> __("Enter the v3 API secret key here.", 'avia_framework'),
                "id" 	=> "avia_recaptcha_skey_v3",
                "required" => array('avia_recaptcha_version','avia_recaptcha_v3'),
                "type" 	=> "text"
                );

            // todo: score thresholds https://developers.google.com/recaptcha/docs/v3
            // $elements[] =	array(
            //     "slug"	=> "google",
            //     "name" 	=> __("Score Threshold", 'avia_framework'),
            //     "desc" 	=> __("reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot). Based on the score, you can take variable action in the context of your site like displaying the reCAPTCHA v2 if the score is below the expected score.", 'avia_framework'),
            //     "id" 	=> "avia_recaptcha_threshold",
            //     "required" => array('avia_recaptcha_version','avia_recaptcha_v3'),
            //     "type" 	=> "select",
            //     "std" => "0.5",
            //     "subtype" => array(
            //         '0.1' =>'0.1',
            //         '0.2' =>'0.2',
            //         '0.3' =>'0.3',
            //         '0.4' =>'0.4',
            //         '0.5' =>'0.5',
            //         '0.6' =>'0.6',
            //         '0.7' =>'0.7',
            //         '0.8' =>'0.8',
            //         '0.9' =>'0.9',
            //         '1' =>'1',
            //         ));
            //     );

            $elements[] =	array(
                "slug"	=> "google",
                "name" 	=> __("Widget Notice", 'avia_framework'),
                "desc" 	=> __('Display a message or notification for unverified users.', 'avia_framework'),
                "id" 	=> "avia_recaptcha_notice",
                "type" 	=> "text",
                "std" => __('', 'avia_framework'),
                );

            return $elements;
        }	

        public function verify_token()
        {  
            $is_human = false;
            $g_recaptcha_alert = $this->get_recaptcha_response( 'g_recaptcha_alert' );
            $g_recaptcha_response = $this->get_recaptcha_response( 'g_recaptcha_response' );

            if( empty( $g_recaptcha_response ) || empty( $g_recaptcha_alert ) ) {
                $this->verified = false;
                return $is_human;
            }

            if( ! $this->is_recaptcha_active() ) {
                $this->verified = false;
                return $is_human;
            }

            $verify_url = $this->verify_url;
            $secret_key = $this->get_secret_key();

            $response = wp_safe_remote_post( $verify_url, array(
                'body' => array(
                    'secret' => $secret_key,
                    'response' => $g_recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR'],
                ),
            ) );
            
            if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
                $this->verified = false;
                return $is_human;
            }

            $response = wp_remote_retrieve_body( $response );
            $response = json_decode( $response, true );

            if( isset( $response['success'] ) && true == $response['success'] ) {
                $this->verified = true;  
       
                foreach( $g_recaptcha_alert as $key => $alert ) {
                    $name = 'avia_recaptcha_transient_' . $alert;
                    $token = get_transient( $name );
                    
                    if ( false === $token ) {
                        set_transient( $name, $alert, 120 * MINUTE_IN_SECONDS );
                    }
                }  
            } 

            wp_die( json_encode( $response ) );
        }

        public function is_human() {
            return $this->verified;
        }

        public function get_recaptcha_response( $response ) {
            if ( isset( $_POST[$response] ) ) {
                return $_POST[$response];
            }
        
            return false;
        }

        function create_decoy( $form_fields, $atts ) {
            if( ! $this->is_recaptcha_active() ) {
                return $form_fields;
            }
            
            $token = $this->generate_token( 'avia_recaptcha' );
            $decoy['test'] = array('label' => '', 'type' => 'hidden', 'value' => $token);
            $form_fields = $decoy + $form_fields;

            return $form_fields;
        }

        function generate_token( $form ) {
            $token = md5( uniqid( microtime(), true ) );  

            return $token;
        }
    }

	/**
	 * Returns the main instance of av_google_recaptcha to prevent the need to use globals.
	 * 
	 * @since 4.5.4
	 * @return av_google_recaptcha
	 */
	function Av_Google_Recaptcha() 
	{
		return av_google_recaptcha::instance();
	}
}

Av_Google_Recaptcha();

