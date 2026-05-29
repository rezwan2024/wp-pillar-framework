<?php
/**
 * API Test page — Tools > API Test
 *
 * Variables injected by AppServiceProvider::renderApiTestPage():
 *   string $endpoint  Full REST URL, e.g. https://example.com/wp-json/example-plugin/v1/examples
 *   string $nonce     wp_create_nonce('wp_rest') value for the current user
 */
defined('ABSPATH') || exit;
?>
<div class="wrap">
    <h1><?php esc_html_e('WP Pillar — API Test', 'example-plugin'); ?></h1>

    <p>
        Calls <code><?php echo esc_url($endpoint); ?></code> using the current
        user's nonce and displays the raw JSON response.
    </p>

    <p>
        <button id="ep-fetch-btn" class="button button-primary">
            <?php esc_html_e('Fetch Examples', 'example-plugin'); ?>
        </button>
        <span id="ep-status" style="margin-left:10px;font-weight:600;"></span>
    </p>

    <pre id="ep-response" style="
        display:none;
        background:#1e1e1e;
        color:#d4d4d4;
        padding:16px 20px;
        margin-top:4px;
        border-radius:4px;
        overflow:auto;
        max-height:520px;
        font-size:13px;
        line-height:1.6;
        white-space:pre-wrap;
        word-break:break-all;
    "></pre>
</div>

<script>
(function () {
    var btn      = document.getElementById('ep-fetch-btn');
    var status   = document.getElementById('ep-status');
    var response = document.getElementById('ep-response');

    /* Values are JSON-encoded by PHP — safe to embed directly in JS. */
    var ENDPOINT = <?php echo wp_json_encode($endpoint); ?>;
    var NONCE    = <?php echo wp_json_encode($nonce); ?>;

    btn.addEventListener('click', function () {
        btn.disabled         = true;
        status.textContent   = 'Loading…';
        status.style.color   = '#666';
        response.style.display = 'none';

        fetch(ENDPOINT, {
            method:  'GET',
            headers: {
                'X-WP-Nonce':   NONCE,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            credentials: 'same-origin',
        })
        .then(function (res) {
            status.textContent = 'HTTP ' + res.status + ' ' + res.statusText;
            status.style.color = res.ok ? '#007a5e' : '#cc1818';
            return res.json();
        })
        .then(function (data) {
            response.textContent   = JSON.stringify(data, null, 2);
            response.style.display = 'block';
        })
        .catch(function (err) {
            status.textContent = 'Error: ' + err.message;
            status.style.color = '#cc1818';
        })
        .finally(function () {
            btn.disabled = false;
        });
    });
}());
</script>
