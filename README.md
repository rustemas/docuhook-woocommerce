# DocuHook for WooCommerce

WordPress plugin + examples to turn **WooCommerce orders into PDFs** via [DocuHook](https://docuhook.com) — merge order JSON into an HTML invoice template, no Puppeteer box to maintain.

```
WooCommerce order.completed → JSON payload → HTML template → PDF
```

## Install

1. Download the [plugin zip](https://docuhook.com/download/docuhook.zip), or clone this repo into `wp-content/plugins/docuhook`
2. Activate **DocuHook** in WP Admin
3. **Settings → DocuHook** → API URL `https://docuhook.com` + your [API key](https://docuhook.com/account)
4. Click **Send test render**

Requires WordPress 5.8+, PHP 7.4+, and WooCommerce for the invoice example.

## Quick usage

```php
$result = docuhook_render(
    [
        'order' => [
            'number' => '1042',
            'total'  => '49.00',
        ],
        'billing' => [
            'name'  => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ],
    ],
    '<h1>Invoice {{order.number}}</h1><p>{{billing.name}} — {{order.total}}</p>',
    ['filename' => 'invoice-1042.pdf', 'mode' => 'sync']
);

if ( ! empty( $result['ok'] ) ) {
    // Merged HTML from DocuHook (PDF via API / your delivery pipeline)
    echo $result['html'];
}
```

Prefer `mode => 'async'` on storefront webhooks so WooCommerce is not held open during render.

## WooCommerce: invoice on order completed

Copy [`examples/order-completed-invoice.php`](examples/order-completed-invoice.php) into a small custom plugin (or mu-plugin), and put [`examples/invoice-template.html`](examples/invoice-template.html) somewhere readable (e.g. `wp-content/uploads/invoice-template.html`).

```php
add_action( 'woocommerce_order_status_completed', 'my_docuhook_invoice_on_complete', 20, 1 );
```

Flow:

1. Order status → completed  
2. Build JSON from `WC_Order`  
3. `docuhook_render( $data, $html, [ 'mode' => 'async' ] )`  
4. Store `job_id` on the order; poll `docuhook_job_result()` when you need the HTML/PDF  

Full walkthrough: [WooCommerce webhook → PDF invoice](https://docuhook.com/blog/woocommerce-webhook-to-pdf-invoice)

## Helpers

| Function | Purpose |
|---|---|
| `docuhook_render( $data, $html, $args )` | Merge + render (sync or async) |
| `docuhook_job_status( $job_id )` | Poll async job |
| `docuhook_job_result( $job_id )` | Fetch completed HTML |

## Pure webhook (no PHP hook)

Point a WooCommerce webhook at:

```http
POST https://docuhook.com/api/v1/hooks/render
Authorization: Bearer dh_...
Content-Type: application/json
```

```json
{
  "mode": "async",
  "data": { "order": { "number": "1042" } },
  "html": "<h1>Invoice {{order.number}}</h1>",
  "filename": "invoice.pdf"
}
```

Returns `202` with `job_id`, `status_url`, and `result_url`.

## Links

- Product / live sandbox: [docuhook.com](https://docuhook.com)  
- API docs: [docuhook.com/docs](https://docuhook.com/docs)  
- Other clients: [Node](https://github.com/rustemas/docuhook-node) · [PHP](https://github.com/rustemas/docuhook-php) · [Python](https://github.com/rustemas/docuhook-python) · [Go](https://github.com/rustemas/docuhook-go) · [React](https://github.com/rustemas/docuhook-react)

## License

GPL-2.0-or-later (WordPress plugin license).
