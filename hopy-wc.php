<?php
/*
* Plugin Name: Hopy
* Description: Venda mais, pagando menos. A plataforma completa de vendas para o seu e-Commerce.
* Version: 1.0.0
* Author: Hopy
* Author URI: https://www.hopy.io
*
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

class Hopy_WC 
{
	public function __construct() 
	{
		if (!class_exists('WooCommerce')) {
			return;
		}
		
		add_action('woocommerce_before_cart', [$this, 'add_cart_script']);
		add_action('woocommerce_before_checkout_form', [$this, 'add_checkout_script']);
        add_filter( 'woocommerce_add_to_cart_validation', [$this, 'woo_custom_add_to_cart_before']);
		add_action('rest_api_init', [$this, 'api']);
	}

	/**
	 * api
	 * Custom API handler.
	 */
	public function api()
	{
		register_rest_route('hopy-checkout/v1', '/orders', [
			'methods' => 'GET',
			'callback' => function ($data) {
				$orders = wc_get_orders([
					'transaction_id' => sanitize_text_field($_GET['transaction_id']),
				]);
				
				return [
					'exists' => count($orders) > 0,
				];
			}
		]);
	}
	
	/**
	* add_checkout_script
	* Put the Hopy Snippet on WC template.
	*
	* @access        public
	* @return        void
	*/
	public function add_checkout_script() 
	{
		$this->script(true);
	}
	
	public function add_cart_script()
	{
		$this->script();
	}
	
	public function script($isCheckout = false)
	{
		?>
		
		<script type='text/javascript'>

            var hopyLoader = `
                <style>
                    #hopy-loader {
                        position: fixed;
                        height: 100vh;
                        width: 100%;
                        display: none;
                        background: #fff;
                        z-index: 99999999;
                        top: 0;
                        left: 0;
                    }

                    div.hopy-loader-inside {
                        position: fixed;
                        top: 0;
                        left: 0;
                        height: 100%;
                        width: 100%;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                    }

                    div.loader-wrapper {
                        position: relative;
                        height: 110px;
                        width: 110px;
                    }

                    div.loader-around {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        border: 5px solid #eee;
                        border-top: 5px solid #00d994;
                        border-radius: 50%;
                        box-sizing: border-box;
                        animation: rotate 2s linear infinite;
                    }

                    .loader-icon-wrapper {
                        position: relative;
                        height: 100%;
                        width: 100%;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }

                    .loader-icon-wrapper svg {
                        position: relative;
                        width: 40px;
                        height: 40px;
                    }

                    .loader-info-text {
                        position: relative;
                        padding-top: 30px;
                        color: rgb(54, 62, 68);
                        font-size: 16px;
                    }

                    @keyframes rotate {
                        0% {
                            transform: rotate(0deg);
                        }

                        100% {
                            transform: rotate(360deg);
                        }
                    }
                </style>

                <div id="hopy-loader">
                    <div class="hopy-loader-inside">
                        <div class="loader-wrapper">
                            <div class="loader-around"></div>
                            <div class="loader-icon-wrapper">
                                <svg fill="#eee" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path
                                        d="M18 10v-4c0-3.313-2.687-6-6-6s-6 2.687-6 6v4h-3v14h18v-14h-3zm-5 7.723v2.277h-2v-2.277c-.595-.347-1-.984-1-1.723 0-1.104.896-2 2-2s2 .896 2 2c0 .738-.404 1.376-1 1.723zm-5-7.723v-4c0-2.206 1.794-4 4-4 2.205 0 4 1.794 4 4v4h-8z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <p class="loader-info-text">Conectando em ambiente seguro...</p>
                    </div>
                </div>
            `;

            var div = document.createElement("div");

            div.innerHTML = hopyLoader;

            document.body.appendChild(div);

            document.getElementById('hopy-loader').style.display = "block";

			window.Hopy = {
				page: <?php echo $isCheckout ? '"checkout"' : '"cart"'; ?>,
				merchant_url: "<?php echo $_SERVER['HTTP_HOST']; ?>",
				cart: <?php echo $this->format_cart(); ?>
			};
			
			(function() {
				var ch = document.createElement('script'); ch.type = 'text/javascript'; ch.async = true;
				ch.src = 'https://hopy.io/assets/scripts/woocommerce_redirect.js';
				var x = document.getElementsByTagName('script')[0]; x.parentNode.insertBefore(ch, x);
			})();
		</script>
		<?php
	}
	
	/**
	* format_cart
	* 
	* Format cart payload.
	*
	* @access        public
	* @return        string
	*/
	public function format_cart() 
	{
		$cartData = WC()->cart->get_cart();
		$cart = [];
		
		foreach ($cartData as $key => $item) {
			$cart['items'][] = [
                'product_id' => $item['product_id'],
				'variant_id' => $item['variation_id'],
                'quantity' => $item['quantity'],
                'fullItem' => $item
			];
		}
		
		return json_encode($cart);
	}

    /**
	* woo_custom_add_to_cart_before
	* 
	* Add to cart before.
	*
	* @access        public
	* @return        string
	*/
    public function woo_custom_add_to_cart_before( $cart_item_data )
    {
 
        WC()->cart->empty_cart();
     
        // Do nothing with the data and return
        return true;
    }
	
}

/**
* Load Hopy
*/
function hopy_plugins_loaded() {
	new Hopy_WC();
}

add_action('plugins_loaded', 'hopy_plugins_loaded');