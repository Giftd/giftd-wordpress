<?php
/**
 * Created by PhpStorm.
 * User: Аркадий
 * Date: 23.06.2015
 * Time: 14:51
 */

class Giftd
{
    private static
        $initiated = false,
        $user_id = '',
        $userFirstName = '',
        $userLastName = '',
        $userEmail = '',
        $userPhone = '',
        $log = 0
    ;

    public static function init()
    {
        if ( ! self::$initiated )
        {
            self::init_hooks();
            self::$initiated = true;
            load_plugin_textdomain('giftd', false, 'giftd/languages');
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        // adds "Settings" link to the plugin action page
        add_filter( 'plugin_action_links', array('Giftd', 'plgn_action_links'), 10, 2 );

        //Calling a function add administrative menu.
        add_action( 'admin_menu', array('Giftd', 'plgn_add_pages') );

        if(!is_admin())
        {
            add_action('wp_head', array('Giftd', 'giftd_main') );
        }

        add_filter( 'woocommerce_coupon_code', array('Giftd', 'check_coupon'), 10, 1 );
        add_action( 'woocommerce_checkout_order_processed', array('Giftd', 'checkOrder'));

        register_uninstall_hook( __FILE__, array('Giftd', 'delete_options') );
    }

    // Function for delete options
    public static function delete_options()
    {
        self::unRegisterShop(self::get_params());
        delete_option('giftd_plgn_options');
    }

    //Function 'plgn_action_links' are using to create action links on admin page.
    public static function plgn_action_links($links, $file)
    {
        //Static so we don't call plugin_basename on every plugin row.
        static $this_plugin;

        if (!$this_plugin)
        {
            $this_plugin = plugin_basename(ABSPATH.'/wp-content/plugins/giftd/giftd.php');
        }

        if ($file == $this_plugin)
        {
            $settings_link = '<a href="admin.php?page=giftd">' . __('Settings', 'giftd') . '</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    public static function plgn_add_pages()
    {
        add_submenu_page(
            'plugins.php',
            __( 'Giftd', 'giftd' ),
            __( 'Giftd', 'giftd' ),
            'manage_options',
            "giftd",
            array('Giftd', 'plgn_settings_page')
        );
        //call register settings function
        add_action( 'admin_init', array('Giftd', 'plgn_settings') );
    }

    public static function plgn_options_default()
    {
        return array(
            'user_id' => '',
            'api_key' => '',
            'partner_code' => '',
            'partner_token_prefix' => '',
        );
    }

    public static function plgn_settings()
    {
        $plgn_options_default = self::plgn_options_default();

        if (!get_option('giftd_plgn_options'))
        {
            add_option('giftd_plgn_options', $plgn_options_default, '', 'yes');
        }

        $plgn_options = get_option('giftd_plgn_options');
        $plgn_options = array_merge($plgn_options_default, $plgn_options);

        update_option('giftd_plgn_options', $plgn_options);
    }

    //Function formed content of the plugin's admin page.
    public static function plgn_settings_page()
    {
        $giftd_plgn_options = self::get_params();
        $giftd_plgn_options_default = self::plgn_options_default();
        $message = "";
        $error = "";

        if (isset($_REQUEST['giftd_plgn_form_submit'])
            && check_admin_referer(plugin_basename(dirname(__DIR__)), 'giftd_plgn_nonce_name'))
        {
            $oldAPIKey = $giftd_plgn_options['api_key'];

            foreach($giftd_plgn_options_default as $k => $v)
            {
                $giftd_plgn_options[$k] = trim(self::request($k, $v));
            }

            if($oldAPIKey != $giftd_plgn_options['api_key']){
                self::registerShop($giftd_plgn_options);
            }

            update_option('giftd_plgn_options', $giftd_plgn_options);

            $message = __("Settings saved", 'giftd');
        }

        $options = array(
            'giftd_plgn_options' => $giftd_plgn_options,
            'message' => $message,
            'error' => $error,
        );

        echo self::loadTPL('adminform', $options);
    }


    private static function loadTPL($name, $options)
    {
        $tmpl = ABSPATH . 'wp-content/plugins/giftd/tmpl/' . $name . '.php';

        if(!is_file($tmpl))
            return __('Error Load Template', 'giftd');

        extract($options, EXTR_PREFIX_SAME, "giftd");

        ob_start();

        include $tmpl;

        return ob_get_clean();
    }

    private static function request($name, $default=null)
    {
        return (isset($_REQUEST[$name])) ? $_REQUEST[$name] : $default;
    }

    //На всех страницах
    public static function giftd_main()
    {
        $giftd_plgn_options = self::get_params();
        if(!empty($giftd_plgn_options['api_key']))
        {
            if(self::request('giftd-update-js', 0) == 1)
            {
                self::updateJavaScript();
            }

            ?>
            <script type="text/javascript">
                <?php require ABSPATH . 'wp-content/plugins/giftd/assets/js/giftd.js' ?>
            </script>
            <?php
        }
    }

    public static function check_coupon($code)
    {
        if(self::couponExisit($code)){
            return $code;
        }

        $card = self::getGiftCard($code);

        if(is_null($card))
        {
            return $code;
        }

        $expire = date('Y-m-d', $card->expires);
        self::createCoupon($code, $card->amount_available, (float)$card->min_amount_total, $expire, $card->cannot_be_used_on_discounted_items);

        return $code;
    }

    private static function couponExisit($code)
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1", $code )
        );

        return !empty($row->post_title);
    }

    private static function getCouponAmount($code)
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' LIMIT 1", $code )
        );

        if(empty($row->ID)){
            return false;
        }

        $amount = get_post_meta( $row->ID, 'coupon_amount', true );
        return $amount;
    }

    private static function createCoupon($code, $amount, $minAmountTotal, $expire, $excludeSaleItems){


        $discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product

        $coupon = array(
            'post_title' => $code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type'		=> 'shop_coupon'
        );
        $excludeSaleItems = $excludeSaleItems ? 'yes' : 'no';

        $new_coupon_id = wp_insert_post( $coupon );

        $data = array(
            'discount_type'              => 'fixed_cart',
            'coupon_amount'              => $amount,
            'individual_use'             => 'no',
            'usage_limit'                => '1',
            'expiry_date'                => $expire,
            'free_shipping'              => 'no',
            'exclude_sale_items'         => $excludeSaleItems,
            'minimum_amount'             => $minAmountTotal,
//            'product_ids'                => array(),
//            'exclude_product_ids'        => array(),
//            'usage_limit_per_user'       => '',
//            'limit_usage_to_x_items'     => '',
//            'usage_count'                => '',
//            'product_categories'         => array(),
//            'exclude_product_categories' => array(),
//            'maximum_amount'             => '',
//            'customer_email'             => array()
        );

        // Add meta
        foreach($data as $k => $v){
            update_post_meta( $new_coupon_id, $k, $v );
        }
    }


    private static function registerShop($params)
    {
        self::updateUserInfo();
        $data = array(
            'email' => self::$userEmail,
            'phone' => self::$userPhone,
            'name' => self::$userFirstName . ' ' . self::$userLastName,
            'url' => network_home_url(),
            'title' => get_option('blogname'),
            'wordpress_version' => $GLOBALS['wp_version']
        );

        $result = null;

        $client = new Giftd_Client($params['user_id'], $params['api_key']);

        try{
            $result = $client->query("wordpress/install", $data);
        }
        catch(Exception $e){}

        return $result;
    }

    private static function unRegisterShop($params)
    {
        $client = new Giftd_Client($params['user_id'], $params['api_key']);
        $result = $client->query("wordpress/uninstall");
        return $result;
    }

    private static function updateUserInfo()
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_data = get_user_meta( $user_id );

        self::$user_id = $user_id;

        if(!empty($user_data['first_name'][0]))
            self::$userFirstName = $user_data['first_name'][0];
        if(!empty($user_data['last_name'][0]))
            self::$userLastName = $user_data['last_name'][0];
        if(!empty($current_user->data->user_email))
            self::$userEmail = $current_user->data->user_email;
        if(!empty($user_data['billing_phone'][0]))
            self::$userPhone = $user_data['billing_phone'][0];
    }

    private static function get_params()
    {
        static $params;
        if(empty($params))
        {
            $params = get_option('giftd_plgn_options');
        }
        return $params;
    }

    public static function checkOrder($order_id)
    {
        $params = self::get_params();
        $userId = $params['user_id'];
        $apiKey = $params['api_key'];
        $partner_token_prefix = $params['partner_token_prefix'];

        if (function_exists('wc_get_order'))
            $order = wc_get_order( $order_id );
        else
            $order = new WC_Order( $order_id );
        $coupons = $order->get_items('coupon');

        if(!is_array($coupons))
        {
            return;
        }

        $couponCode = '';
        foreach($coupons as $k => $coupon)
        {
            if(is_array($coupon) && !empty($coupon['name']) && strpos($coupon['name'], $partner_token_prefix) === 0)
            {
                $couponCode = $coupon['name'];
            }
        }

        if(empty($couponCode)){
            return;
        }

        $amount = self::getCouponAmount($couponCode);

        $total = $order->get_total();
        $shipping = (method_exists($order, 'get_total_shipping')) ? $order->get_total_shipping() : 0;
        $order_total = $total - $shipping;

        if(!self::useCoupon($couponCode, $amount, $order_total, $order_id)){
            wp_redirect( wc_get_page_permalink( 'cart' ) );
        }
    }

    private static function useCoupon($code, $amount, $amountTotal=null, $orderId = null, $comment = null)
    {
        $params = self::get_params();
        $userId = $params['user_id'];
        $apiKey = $params['api_key'];
        $partner_token_prefix = $params['partner_token_prefix'];

        if(empty($partner_token_prefix) || strpos($code, $partner_token_prefix) !== 0)
        {
            return true;
        }

        $client = new Giftd_Client($userId, $apiKey);

        try
        {
            $data = $client->charge($code, $amount, $amountTotal, $orderId, $comment);
        }
        catch(Exception $e)
        {
            self::cancelOrder($orderId);
            return false;
        }

        if(is_null($data) || !in_array($data->status, array('ready', 'received')))
        {
            self::cancelOrder($orderId);
            return false;
        }

        return true;
    }

    private static function cancelOrder($order_id)
    {
        if (function_exists('wc_get_order'))
            $order = wc_get_order( $order_id );
        else
            $order = new WC_Order( $order_id );
        $order->cancel_order('Coupon Error');
    }

    private static function getGiftCard($code)
    {
        $params = self::get_params();
        $userId = $params['user_id'];
        $apiKey = $params['api_key'];
        $partner_token_prefix = $params['partner_token_prefix'];

        if(empty($partner_token_prefix) || strpos($code, $partner_token_prefix) !== 0)
        {
            return null;
        }
        $client = new Giftd_Client($userId, $apiKey);
        $data = $client->checkByToken($code);

        if(is_null($data) || !in_array($data->status, array('ready', 'received')))
        {
            return null;
        }

        return $data;
    }

    private static function updateJavaScript()
    {
        try {
            $params = self::get_params();
            $userId = $params['user_id'];
            $apiKey = $params['api_key'];
            $client = new Giftd_Client($userId, $apiKey);
            $result = $client->query('partner/getJs');
            $code = isset($result['data']['js']) ? $result['data']['js'] : null;
            if ($code) {
                $file = ABSPATH . 'wp-content/plugins/giftd/assets/js/giftd.js';
                file_put_contents($file, $code);
            }
        } catch (Exception $e) {

        }
    }
}