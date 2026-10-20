<?php
/**
 * Example: WooCommerce order.completed → DocuHook invoice render.
 *
 * Install as a small custom plugin, or require from a mu-plugin.
 * Requires the DocuHook plugin (docuhook_render) and WooCommerce.
 *
 * Put invoice-template.html somewhere readable, e.g.:
 *   wp-content/uploads/invoice-template.html
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_order_status_completed', 'docuhook_example_invoice_on_complete', 20, 1);

/**
 * @param int $order_id
 */
function docuhook_example_invoice_on_complete($order_id): void
{
    if (!function_exists('docuhook_render') || !function_exists('wc_get_order')) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $items = [];
    foreach ($order->get_items() as $item) {
        $items[] = [
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'total' => $item->get_total(),
        ];
    }

    $data = [
        'order' => [
            'number' => $order->get_order_number(),
            'date' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : '',
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
        ],
        'billing' => [
            'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'email' => $order->get_billing_email(),
        ],
        'line_items' => $items,
    ];

    $template = WP_CONTENT_DIR . '/uploads/invoice-template.html';
    if (!is_readable($template)) {
        return;
    }

    $html = file_get_contents($template);
    if ($html === false) {
        return;
    }

    $result = docuhook_render($data, $html, [
        'mode' => 'async',
        'filename' => 'invoice-' . $order->get_order_number() . '.pdf',
    ]);

    if (!empty($result['job_id'])) {
        $order->update_meta_data('_docuhook_job_id', $result['job_id']);
        $order->save();
    }
}
