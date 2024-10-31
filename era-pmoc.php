<?php
/*
Plugin Name: Payment Method Order Column
Plugin URI:  https://www.eraclito.it/metodo-di-pagamento-negli-ordini-di-woocommerce/
Description: Add a column to to oder list to filter orders by payment method
Version:     2.0.0
Author:      Alessio Rosi - Eraclito
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: payment-method-order-column
Domain Path: /languages


*/

// Carica il text domain per la traduzione
add_action('plugins_loaded', 'woocommerce_payment_icons_load_textdomain');
function woocommerce_payment_icons_load_textdomain() {
    load_plugin_textdomain('payment-method-order-column', false, dirname(plugin_basename(__FILE__)) . '/languages');
}


// Includi il supporto per il media uploader di WordPress
function enqueue_media_script() {
    wp_enqueue_media();
    wp_enqueue_script('payment-icons-media-script', plugin_dir_url(__FILE__) . 'js/admin-media.js', array('jquery'), null, true);
}


add_action('admin_enqueue_scripts', 'enqueue_media_script');


// Carica il file CSS personalizzato per la pagina di impostazioni
add_action('admin_enqueue_scripts', 'enqueue_custom_payment_icons_style');
function enqueue_custom_payment_icons_style() {
    wp_enqueue_style('payment-icons-style', plugin_dir_url(__FILE__) . 'css/style.css');
}



// Aggiungi una colonna per il metodo di pagamento nella pagina degli ordini
add_filter('manage_edit-shop_order_columns', 'add_payment_method_column');
function add_payment_method_column($columns) {
    $new_columns = array();

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ('order_status' === $key) {
            $new_columns['payment_method'] = __('Payment Method', 'payment-method-order-column');
        }
    }

    return $new_columns;
}

// Riempi la colonna con l'icona e il nome del metodo di pagamento
add_action('manage_shop_order_posts_custom_column', 'populate_payment_method_column');
function populate_payment_method_column($column) {
    global $post;

    if ('payment_method' === $column) {
        $order = wc_get_order($post->ID);
        $payment_method = $order->get_payment_method();
        $payment_method_title = $order->get_payment_method_title();
        $icon = get_payment_method_icon($payment_method);

        echo '<img src="' . esc_url($icon) . '" title="' . esc_attr($payment_method_title) . '" style="width:64px;height:64px;margin-right:5px;"/>';
    }
}

// Aggiungi il filtro per il metodo di pagamento nella pagina degli ordini
add_action('restrict_manage_posts', 'filter_by_payment_method');
function filter_by_payment_method() {
    global $typenow;
    if ($typenow === 'shop_order') {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        ?>
        <select name="payment_method_filter">
            <option value=""><?php _e('All Payment Methods', 'payment-method-order-column'); ?></option>
            <?php foreach ($available_gateways as $gateway) : ?>
                <option value="<?php echo esc_attr($gateway->id); ?>" <?php echo isset($_GET['payment_method_filter']) && $_GET['payment_method_filter'] === $gateway->id ? 'selected' : ''; ?>>
                    <?php echo esc_html($gateway->get_title()); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}

// Filtra gli ordini in base al metodo di pagamento selezionato
add_filter('request', 'filter_orders_by_payment_method');
function filter_orders_by_payment_method($vars) {
    if (isset($_GET['payment_method_filter']) && !empty($_GET['payment_method_filter'])) {
        $vars['meta_query'] = array(
            array(
                'key' => '_payment_method',
                'value' => sanitize_text_field($_GET['payment_method_filter']),
                'compare' => '=',
            ),
        );
    }
    return $vars;
}

// Funzione per ottenere l'icona del metodo di pagamento

function get_payment_method_icon($payment_method_id) {
    $icons = get_option('payment_method_icons', array());
    if (!empty($icons[$payment_method_id])) {
        return $icons[$payment_method_id]; // Restituisce l'URL dell'icona personalizzata
    }
    // Se non c'è un'icona, usa l'icona di default
    return plugin_dir_url(__FILE__) . 'images/default-icon.png';
}








// Aggiungi una pagina di impostazioni per caricare le icone dei metodi di pagamento   __('Payment Method Icons', 'payment-method-order-column'),
add_action('admin_menu', 'payment_icons_settings_page');
function payment_icons_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('Payment Method Icons', 'payment-method-order-column'),
        __('Payment Method Icons', 'payment-method-order-column'),
        'manage_options',
        'payment-icons-settings',
        'payment_icons_settings_callback'
    );
}


// Callback della pagina di impostazioni con struttura a div
function payment_icons_settings_callback() {
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $icons = get_option('payment_method_icons', array());

    // Verifica che il form sia stato inviato e che il nonce sia valido
    if ($_POST && isset($_POST['save_payment_icons']) && check_admin_referer('save_payment_icons_action', 'save_payment_icons_nonce')) {
        foreach ($available_gateways as $gateway) {
            if (!empty($_POST['payment_icon_' . $gateway->id])) {
                // Salva l'URL dell'icona per ogni metodo di pagamento
                $icons[$gateway->id] = esc_url_raw($_POST['payment_icon_' . $gateway->id]);
            } else {
                // Se non c'è un'icona, mantieni quella precedente o rimuovila
                unset($icons[$gateway->id]);
            }
        }
        // Aggiorna le icone nel database
        update_option('payment_method_icons', $icons);
        echo '<div class="updated"><p>' . __('Icons updated', 'payment-method-order-column') . '</p></div>';
    }

    // Gestione del reset delle icone
    if ($_POST && isset($_POST['reset_payment_icons']) && check_admin_referer('save_payment_icons_action', 'save_payment_icons_nonce')) {
        foreach ($available_gateways as $gateway) {
            if (isset($_POST['reset_icon_' . $gateway->id])) {
                // Rimuovi l'icona personalizzata, tornando all'icona di default
                unset($icons[$gateway->id]);
            }
        }
        update_option('payment_method_icons', $icons);
        echo '<div class="updated"><p>' . __('Icons reset', 'payment-method-order-column') . '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1><?php _e('Manage Payment Method Icons', 'payment-method-order-column'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('save_payment_icons_action', 'save_payment_icons_nonce'); ?>

            <div class="payment-icons-grid">
                <?php foreach ($available_gateways as $gateway) : ?>
                    <div class="payment-icons-row">
                        <div class="payment-icons-cell payment-method-name">
                            <?php echo esc_html($gateway->get_title()); ?>
                        </div>
                        <div class="payment-icons-cell payment-method-icon">
                            <img src="<?php echo esc_url(get_payment_method_icon($gateway->id)); ?>" style="width:64px;height:64px;margin-right:10px;"/>
                        </div>
                        <div class="payment-icons-cell payment-method-actions">
                            <input type="text" name="payment_icon_<?php echo esc_attr($gateway->id); ?>" value="<?php echo isset($icons[$gateway->id]) ? esc_url($icons[$gateway->id]) : ''; ?>" style="width:300px;"/>
                            <button type="button" class="upload_icon_button button"><?php _e('Choose Icon', 'payment-method-order-column'); ?></button>
                            <input type="checkbox" name="reset_icon_<?php echo esc_attr($gateway->id); ?>" value="1"> <?php _e('Reset icon', 'payment-method-order-column'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p>
                <input type="submit" name="save_payment_icons" class="button-primary" value="<?php _e('Save Icons', 'payment-method-order-column'); ?>"/>
                <input type="submit" name="reset_payment_icons" class="button-secondary" value="<?php _e('Reset Selected Icons', 'payment-method-order-column'); ?>"/>
            </p>
        </form>
    </div>
    <?php
}



?>
