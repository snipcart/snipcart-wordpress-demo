<?php
function call_snipcart_api($url, $method = "GET", $post_data = null) {
    $url = 'https://app.snipcart.com/api' . $url;

    $query = curl_init();


    $headers = array();
    $headers[] = 'Content-type: application/json';
    if ($post_data)
        $headers[] = 'Content-Length: ' . strlen($post_data);
    $headers[] = 'Accept: application/json';

    $secret = file_get_contents(get_stylesheet_directory() . "/secret.txt");
    $secret = str_replace("\n", "", $secret);
    $secret = str_replace("\r", "", $secret);
    $headers[] = 'Authorization: Basic '.base64_encode($secret . ":");
    $options = array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    );

    if ($post_data) {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        $options[CURLOPT_POSTFIELDS] = $post_data;
    }

    curl_setopt_array($query, $options);
    $resp = curl_exec($query);
    curl_close($query);

    return json_decode($resp);
}

add_action( 'wp_enqueue_scripts', 'snipcart_enqueue_styles' );
function snipcart_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'snipcart-style', 'https://cdn.snipcart.com/themes/2.0/base/snipcart.min.css');
}

add_action('wp_ajax_nopriv_snipcart_endpoint', 'snipcart_endpoint');
//Allow authenticated call on the endpoint. Only needed for debugging purposes.
add_action('wp_ajax_snipcart_endpoint', 'snipcart_endpoint');
function snipcart_endpoint() {
    $token = $_SERVER["HTTP_X_SNIPCART_REQUESTTOKEN"];
    $resp = call_snipcart_api('/requestvalidation/' . $token);
    if (strpos($resp->resource, 'wp-admin/admin-ajax.php?action=snipcart_endpoint') === false) {
        echo "Caller is not snipcart";
        wp_die();
    }

    $json = file_get_contents('php://input');
    $body = json_decode($json, true);

    if (is_null($body) or !isset($body['eventName'])) {
        header('HTTP/1.1 400 Bad Request');
        wp_die();
    }

    switch ($body['eventName']) {
    case 'order.completed':
        foreach($body['content']['items'] as $item) {
            handle_item($item);
        }
        break;
    }

    wp_die();
}

function handle_item($item) {
    global $wpdb;

    $id = $wpdb->get_var( $wpdb->prepare( 
        "
		SELECT post_id
		FROM $wpdb->postmeta
		WHERE meta_value = %s AND meta_key = 'id'
	", $item['id']
    ) );

    $qte = get_post_meta($id, 'inventory')[0];
    update_post_meta($id, 'inventory', $qte - $item['quantity']);
}

add_action( 'admin_enqueue_scripts', 'snipcart_enqueue_admin_script' );
function snipcart_enqueue_admin_script( $hook ) {
    wp_register_style('snipcart_admin_style',
                        get_stylesheet_directory_uri() . '/css/admin.css', false, '1.0.0');
    wp_enqueue_style('snipcart_admin_style');
}

add_action( 'admin_menu', 'register_custom_menu_page' );
function register_custom_menu_page() {
    add_menu_page('snipcart', 'Snipcart', 'manage_options', 'snipcart', 'snipcart_dashboard', '', 6);
}

function snipcart_dashboard() {
    $resp = call_snipcart_api('/orders');
    $statuses = array("Processed", "Disputed", "Shipped", "Delivered", "Pending", "Cancelled");

    echo "<table class='snip-table'>";

    echo "<tr>
            <th>Invoice number</th>
            <th>Payment method</th>
            <th>Email</th>
            <th>Total</th>
            <th>Date</th>
            <th>Order status</th>
            <th>Update status</th>
            <th>Items</th>
          </tr>";

    foreach ($resp->items as $order) {
        echo "<tr>";
        echo "<td>";
        echo "<a target='_blank' href='https://app.snipcart.com/dashboard/orders/$order->token'>";
        echo $order->invoiceNumber. "</a></td>";
        echo "<td>" . $order->paymentMethod. "</td>";
        echo "<td>" . $order->email . "</td>";
        echo "<td>" . $order->finalGrandTotal. "$</td>";
        $date = new DateTime($order->creationDate);
        $outputDate = date_format($date, 'Y-m-d H:i');
        echo "<td>" . $outputDate. "</td>";
        echo "<td>" . $order->status. "</td>";
        echo "<td><select class='order-status-select' data-token='$order->token'>";

        foreach ($statuses as $status) {
            echo "<option value='$status' ";
            if ($status == $order->status) echo "selected='selected'";
            echo ">$status</option>";
        }

        echo "</select>";

        echo "<td>";
        foreach ($order->items as $item) {
            echo $item->name . "<br/>";
        }

        echo "</tr>";
    }

    echo "</table>";

    echo "<script src='". get_stylesheet_directory_uri() . '/js/admin.js' . "' />";
}

add_action('wp_ajax_snipcart_update_status', 'snipcart_update_status');
function snipcart_update_status() {
    if (!isset($_POST['token']) || !isset($_POST['value'])) {
        header('HTTP/1.1 400 Bad Request');
        echo "Bad request";
        wp_die();
    }

    $url = "/orders/" . $_POST['token'];
    $result = call_snipcart_api($url, "PUT", json_encode(array(
        "status" => $_POST['value']
    )));

    if ($result->status !== $_POST['value']) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Error while communicating with Snipcart";
    }

    wp_die();
}