<?php
/*
Plugin Name: Wiry.io Cookie-less Chat, Popups and Analytics
Version: 1.0.0
Plugin URI: https://wiry.io/
Author: Wiry.io
Author URI: https://wiry.io/
Description: Add Wiry.io cookie-less chat, popups and analytics to your website.
*/

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WIRY_IO')) {

    class WIRY_IO
    {

        var $plugin_version = '1.0.0';

        function __construct()
        {
            define('WIRY_IO_VERSION', $this->plugin_version);
            $this->plugin_includes();
        }

        function plugin_includes()
        {
            if (is_admin()) {
                add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
            }
            add_action('admin_init', array($this, 'settings_api_init'));
            add_action('admin_menu', array($this, 'add_options_menu'));
            add_action('wp_footer', array($this, 'add_code'));
        }

        function plugin_url()
        {
            if ($this->plugin_url)
                return $this->plugin_url;
            return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
        }

        function plugin_action_links($links, $file)
        {
            if ($file == plugin_basename(dirname(__FILE__) . '/main.php')) {
                $links[] = '<a href="options-general.php?page=wiryio-settings">Settings</a>';
            }
            return $links;
        }
        function add_options_menu()
        {
            if (is_admin()) {
                add_options_page('Wiry.io','Wiry.io', 'manage_options', 'wiryio-settings', array($this, 'options_page'));
            }
        }
        function settings_api_init()
        {
            register_setting('wiryiosettings', 'wiryio_settings');

            add_settings_section(
                'wiryio_section',
                'Settings',
                array($this, 'wiryio_settings_section_callback'),
                'wiryiosettings'
            );

            add_settings_field(
                'account_id',
                'Account ID',
                array($this, 'account_id_render'),
                'wiryiosettings',
                'wiryio_section'
            );
        }
        function account_id_render()
        {
            $options = get_option('wiryio_settings');
?>
            <input type='text' name='wiryio_settings[account_id]' value='<?php echo $options['account_id']; ?>'>
            <p class="description"><?php printf('Enter your Account ID (17 characters).'); ?></p>
        <?php
        }

        function options_page()
        {
        ?>
            <div class="wrap">
                <h2>Wiry.io Cookie-less Chat, Popups and Analytics</h2>
                <div>
                    <p>Version: <?php echo $this->plugin_version; ?></p>
                </div>
                <div>
                    <p>For instructions, please visit <a href="https://docs.wiry.io/">our documentation</a>.</p>
                    <p>View the dashboard and manage your chats and popups in the <a href="https://eu.app.wiry.io/?ref=wordpress">Wiry.io App</a></p>
                </div>
                <form action='options.php' method='post'>
                    <?php
                    settings_fields('wiryiosettings');
                    do_settings_sections('wiryiosettings');
                    submit_button();
                    ?>
                </form>
            </div>
<?php
        }

        function is_logged_in()
        {
            $is_logged_in = false;
            if (is_user_logged_in()) { 
                if (current_user_can('editor') || current_user_can('administrator')) {
                    $is_logged_in = true;
                }
            }
            return $is_logged_in;
        }

        function add_code()
        {
            $options = get_option('wiryio_settings');
            $account_id = $options['account_id'];
            $extras = '';
            if ($this->is_logged_in()) {
                $extras .= 'WiryConfig(\'dev\', true); // do not track admin users'; 
            } 
            if (isset($account_id) && !empty($account_id)) {
                $output = <<<EOT
                <!-- Wiry.io Plugin v{$this->plugin_version} -->
                <script>
                    (function(scope, targetEl, tag, baseUrl, accountId) {
                        var fn, el, script;
                        fn = function WiryConfig(prop, val) {
                        scope[fn.name]._options[prop] = val;
                        };
                        scope[fn.name] = fn;
                        fn._options = { accountId: accountId, baseUrl: baseUrl, load: new Date() };
                        script = targetEl.createElement(tag);
                        script.async = 1;
                        script.src = baseUrl + '/static/script/bundle.js';
                        el = targetEl.getElementsByTagName(tag)[0];
                        (el ? el.parentNode.insertBefore(script, el) : targetEl.head.appendChild(script));
                    })(window, document, 'script', 'https://gateway.wiryio.com', '{$account_id}');
                    {$extras}
                    </script>
                <!-- / Wiry.io Plugin -->
EOT;

                echo $output;
            }
        }
    }

    $GLOBALS['wiryop'] = new WIRY_IO();
}
