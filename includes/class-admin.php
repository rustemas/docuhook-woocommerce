<?php

if (!defined('ABSPATH')) {
    exit;
}

final class DocuHook_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_post_docuhook_test_render', [self::class, 'test_render']);
        add_action('admin_notices', [self::class, 'admin_notices']);
    }

    public static function menu(): void
    {
        add_options_page(
            'DocuHook',
            'DocuHook',
            'manage_options',
            'docuhook',
            [self::class, 'render_page']
        );
    }

    public static function register(): void
    {
        register_setting('docuhook', 'docuhook_settings', [
            'type' => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{api_url: string, api_key: string, mode: string}
     */
    public static function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $mode = isset($input['mode']) && $input['mode'] === 'async' ? 'async' : 'sync';
        return [
            'api_url' => isset($input['api_url']) ? esc_url_raw(wp_unslash((string) $input['api_url'])) : 'https://docuhook.com',
            'api_key' => isset($input['api_key']) ? sanitize_text_field(wp_unslash((string) $input['api_key'])) : '',
            'mode' => $mode,
        ];
    }

    public static function test_render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'docuhook'));
        }
        check_admin_referer('docuhook_test_render');

        $result = DocuHook_Client::render(
            [
                'site' => get_bloginfo('name'),
                'url' => home_url('/'),
                'time' => gmdate('c'),
            ],
            '<!DOCTYPE html><html><body style="font-family:system-ui;padding:24px">'
            . '<h1>DocuHook test</h1>'
            . '<p>{{site}}</p><p>{{url}}</p><p>{{time}}</p>'
            . '</body></html>',
            ['mode' => 'sync', 'filename' => 'docuhook-test.pdf']
        );

        set_transient(
            'docuhook_admin_notice_' . get_current_user_id(),
            [
                'ok' => !empty($result['ok']),
                'code' => (int) ($result['code'] ?? 0),
                'message' => isset($result['error']) ? (string) $result['error'] : 'ok',
            ],
            60
        );

        wp_safe_redirect(admin_url('options-general.php?page=docuhook'));
        exit;
    }

    public static function admin_notices(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page routing for admin notice scope.
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if ($page !== 'docuhook') {
            return;
        }

        $notice = get_transient('docuhook_admin_notice_' . get_current_user_id());
        if (!is_array($notice)) {
            return;
        }
        delete_transient('docuhook_admin_notice_' . get_current_user_id());

        $ok = !empty($notice['ok']);
        $code = isset($notice['code']) ? (int) $notice['code'] : 0;
        $message = isset($notice['message']) ? (string) $notice['message'] : '';
        $class = $ok ? 'notice-success' : 'notice-error';

        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>';
        if ($ok) {
            echo esc_html(
                sprintf(
                    /* translators: %d: HTTP status code */
                    __('Test render succeeded (HTTP %d).', 'docuhook'),
                    $code
                )
            );
        } else {
            echo esc_html(
                sprintf(
                    /* translators: 1: HTTP status code, 2: error message */
                    __('Test render failed (HTTP %1$d): %2$s', 'docuhook'),
                    $code,
                    $message
                )
            );
        }
        echo '</p></div>';
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = DocuHook_Client::settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DocuHook', 'docuhook'); ?></h1>
            <p>
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: docuhook.com URL */
                        __('Connect this site to <a href="%s" target="_blank" rel="noopener noreferrer">docuhook.com</a>. Create an account, copy an API key, then paste it below.', 'docuhook'),
                        esc_url('https://docuhook.com')
                    ),
                    [
                        'a' => [
                            'href' => true,
                            'target' => true,
                            'rel' => true,
                        ],
                    ]
                );
                ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('docuhook'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="docuhook_api_url"><?php echo esc_html__('API URL', 'docuhook'); ?></label></th>
                        <td>
                            <input name="docuhook_settings[api_url]" id="docuhook_api_url" type="url" class="regular-text"
                                   value="<?php echo esc_attr($settings['api_url']); ?>" placeholder="https://docuhook.com">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="docuhook_api_key"><?php echo esc_html__('API key', 'docuhook'); ?></label></th>
                        <td>
                            <input name="docuhook_settings[api_key]" id="docuhook_api_key" type="text" class="regular-text code"
                                   value="<?php echo esc_attr($settings['api_key']); ?>" placeholder="dh_...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="docuhook_mode"><?php echo esc_html__('Default mode', 'docuhook'); ?></label></th>
                        <td>
                            <select name="docuhook_settings[mode]" id="docuhook_mode">
                                <option value="sync" <?php selected($settings['mode'], 'sync'); ?>><?php echo esc_html__('Sync', 'docuhook'); ?></option>
                                <option value="async" <?php selected($settings['mode'], 'async'); ?>><?php echo esc_html__('Async (202 job)', 'docuhook'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save settings', 'docuhook')); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="docuhook_test_render">
                <?php wp_nonce_field('docuhook_test_render'); ?>
                <?php submit_button(__('Send test render', 'docuhook'), 'secondary'); ?>
            </form>

            <hr>
            <h2><?php echo esc_html__('Theme / plugin usage', 'docuhook'); ?></h2>
            <pre style="background:#f6f7f7;padding:12px;overflow:auto">$result = docuhook_render(
    [ 'order_id' => 123, 'customer' => 'Ada' ],
    '&lt;h1&gt;Order {{order_id}} for {{customer}}&lt;/h1&gt;',
    [ 'mode' => 'sync' ]
);

if ( ! empty( $result['ok'] ) ) {
    echo $result['html']; // merged HTML from DocuHook
}</pre>
        </div>
        <?php
    }
}
