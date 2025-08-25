<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class WooCommerce
 *
 * Advanced helper for WooCommerce-related operations.
 *
 * Features:
 * ✅ Auto-detect WooCommerce availability once
 * ✅ Safely get product details
 * ✅ Manage order statuses
 * ✅ Retrieve user orders by status
 * ✅ Calculate order totals including discounts
 * ✅ Bulk update product prices by percentage
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\WooCommerce;
 *
 * $product = WooCommerce::getProduct(123);
 * WooCommerce::updateOrderStatus(456, 'completed');
 * $pendingOrders = WooCommerce::getUserOrdersByStatus(123, 'pending');
 * $total = WooCommerce::calculateOrderTotal(456);
 * WooCommerce::bulkUpdatePrices([123, 124], 10); // increase by 10%
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class WooCommerce
{
    /**
     * Whether the class has checked WooCommerce availability.
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Whether WooCommerce functions are available.
     *
     * @var bool
     */
    private static bool $isActive = false;

    /**
     * Initialize the class by checking WooCommerce availability.
     *
     * Called automatically on first method use.
     *
     * @return void
     */
    private static function initialize(): void
    {
        if (!self::$initialized) {
            // Check if WooCommerce core functions exist
            self::$isActive = class_exists('WooCommerce');

            // Mark initialization done
            self::$initialized = true;
        }
    }

    /**
     * Guard method to initialize and verify WooCommerce availability.
     *
     * @return bool True if WooCommerce is active, false otherwise.
     */
    private static function guard(): bool
    {
        self::initialize();
        return self::$isActive;
    }

    /**
     * Get WooCommerce product by ID safely.
     *
     * @param int $productId Product ID.
     * @return \WC_Product|null Product object or null if WooCommerce inactive or product not found.
     *
     * @example
     * ```php
     * $product = WooCommerce::getProduct(123);
     * ```
     */
    public static function getProduct(int $productId): ?\WC_Product
    {
        if (!self::guard()) {
            return null;
        }

        // Attempt to get the product object
        $product = wc_get_product($productId);

        // Return product or null
        return $product ?: null;
    }

    /**
     * Update the status of an order.
     *
     * @param int $orderId Order ID.
     * @param string $status New status slug (e.g., 'completed', 'processing').
     * @return bool True if status updated successfully, false otherwise or if WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateOrderStatus(456, 'completed');
     * ```
     */
    public static function updateOrderStatus(int $orderId, string $status): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Update status and save order
        $order->update_status($status);
        $order->save();

        return true;
    }

    /**
     * Retrieve all orders of a user filtered by a specific status.
     *
     * @param int $userId User ID.
     * @param string $status Order status (e.g., 'pending', 'completed').
     * @return \WC_Order[] Array of order objects or empty array if none found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getUserOrdersByStatus(123, 'pending');
     * ```
     */
    public static function getUserOrdersByStatus(int $userId, string $status): array
    {
        if (!self::guard()) {
            return [];
        }

        // Query orders for user with specified status
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'status' => $status,
            'limit' => -1,
        ]);

        return $orders;
    }

    /**
     * Calculate total amount for an order, including discounts and fees.
     *
     * @param int $orderId Order ID.
     * @return float Total amount or 0.0 if order not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $total = WooCommerce::calculateOrderTotal(456);
     * ```
     */
    public static function calculateOrderTotal(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return 0.0;
        }

        // Return total amount
        return (float) $order->get_total();
    }

    /**
     * Bulk update product prices by a percentage.
     *
     * @param int[] $productIds Array of product IDs.
     * @param float $percentage Percentage to change price (positive to increase, negative to decrease).
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::bulkUpdatePrices([123, 124], 10);  // Increase by 10%
     * WooCommerce::bulkUpdatePrices([123, 124], -5);  // Decrease by 5%
     * ```
     */
    public static function bulkUpdatePrices(array $productIds, float $percentage): void
    {
        if (!self::guard()) {
            return;
        }

        // Loop through products to update prices
        foreach ($productIds as $productId) {
            $product = wc_get_product($productId);

            // Skip invalid or unsupported product types
            if (!$product || !in_array($product->get_type(), ['simple', 'variable'], true)) {
                continue;
            }

            // Get current regular price as float
            $price = (float) $product->get_regular_price();

            // Calculate new price after percentage change
            $newPrice = $price + ($price * $percentage / 100);

            // Prevent negative pricing
            $newPrice = max($newPrice, 0);

            // Set and save new price
            $product->set_regular_price($newPrice);
            $product->save();
        }
    }

    /**
     * Get the total quantity of items in an order.
     *
     * @param int $orderId Order ID.
     * @return int Total quantity of all items or 0 if order not found/WooCommerce inactive.
     *
     * @example
     * ```php
     * $qty = WooCommerce::getOrderTotalQuantity(456);
     * ```
     */
    public static function getOrderTotalQuantity(int $orderId): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Retrieve the order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return 0;
        }

        $totalQty = 0;

        // Loop through each order item
        foreach ($order->get_items() as $item) {
            // Add item quantity to total
            $totalQty += $item->get_quantity();
        }

        return $totalQty;
    }

    /**
     * Check if a product is on sale.
     *
     * @param int $productId Product ID.
     * @return bool True if on sale, false if not or WooCommerce inactive.
     *
     * @example
     * ```php
     * $onSale = WooCommerce::isProductOnSale(123);
     * ```
     */
    public static function isProductOnSale(int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Use WooCommerce method to check sale status
        return $product->is_on_sale();
    }

    /**
     * Get product stock quantity.
     *
     * @param int $productId Product ID.
     * @return int|null Stock quantity or null if not managing stock or WooCommerce inactive.
     *
     * @example
     * ```php
     * $stock = WooCommerce::getProductStock(123);
     * ```
     */
    public static function getProductStock(int $productId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return null;
        }

        // Return current stock quantity
        return $product->get_stock_quantity();
    }

    /**
     * Reduce stock quantity for a product safely.
     *
     * @param int $productId Product ID.
     * @param int $quantity Quantity to reduce.
     * @return bool True if stock reduced successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::reduceProductStock(123, 2);
     * ```
     */
    public static function reduceProductStock(int $productId, int $quantity): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return false;
        }

        $currentStock = $product->get_stock_quantity();

        // Ensure not reducing below zero
        if ($quantity > $currentStock) {
            return false;
        }

        // Calculate new stock
        $newStock = $currentStock - $quantity;

        // Set new stock and save
        $product->set_stock_quantity($newStock);
        $product->save();

        return true;
    }

    /**
     * Get a list of all WooCommerce order statuses.
     *
     * @return string[] Array of order status slugs or empty array if WooCommerce inactive.
     *
     * @example
     * ```php
     * $statuses = WooCommerce::getOrderStatuses();
     * ```
     */
    public static function getOrderStatuses(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get all order statuses registered in WooCommerce
        $statuses = wc_get_order_statuses();

        // Return keys (status slugs)
        return array_keys($statuses);
    }

    /**
     * Add a product to the cart programmatically.
     *
     * @param int $productId Product ID to add.
     * @param int $quantity Quantity to add, default 1.
     * @return bool True if added successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::addProductToCart(123, 2);
     * ```
     */
    public static function addProductToCart(int $productId, int $quantity = 1): bool
    {
        if (!self::guard()) {
            return false;
        }

        // WC()->cart must be initialized (frontend or via session)
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        // Add product to cart and get cart item key or false
        $cartItemKey = WC()->cart->add_to_cart($productId, $quantity);

        return $cartItemKey !== false;
    }

    /**
     * Get the number of products in stock for a given product type.
     *
     * @param string $productType Product type slug (e.g., 'simple', 'variable').
     * @return int Total stock quantity for all products of that type, or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $simpleStock = WooCommerce::getTotalStockByProductType('simple');
     * ```
     */
    public static function getTotalStockByProductType(string $productType): int
    {
        if (!self::guard()) {
            return 0;
        }

        $args = [
            'limit' => -1,
            'type' => $productType,
            'status' => 'publish',
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'instock',
                ],
            ],
        ];

        // Get product IDs matching criteria
        $productIds = wc_get_products($args);

        $totalStock = 0;

        // Sum stock quantities
        foreach ($productIds as $productId) {
            $product = wc_get_product($productId);
            if ($product && $product->managing_stock()) {
                $stock = $product->get_stock_quantity();
                $totalStock += $stock ?? 0;
            }
        }

        return $totalStock;
    }

    /**
     * Get the total revenue generated by orders in a date range.
     *
     * @param string $startDate Start date in 'Y-m-d' format.
     * @param string $endDate End date in 'Y-m-d' format.
     * @return float Total revenue or 0.0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $revenue = WooCommerce::getRevenueByDateRange('2025-01-01', '2025-01-31');
     * ```
     */
    public static function getRevenueByDateRange(string $startDate, string $endDate): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Query orders in date range with completed status
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'completed',
            'date_created' => $startDate . '...' . $endDate,
        ]);

        $totalRevenue = 0.0;

        // Sum order totals
        foreach ($orders as $order) {
            $totalRevenue += (float) $order->get_total();
        }

        return $totalRevenue;
    }

    /**
     * Check if a user has purchased a specific product.
     *
     * @param int $userId User ID.
     * @param int $productId Product ID.
     * @return bool True if user purchased product, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $hasPurchased = WooCommerce::hasUserPurchasedProduct(123, 456);
     * ```
     */
    public static function hasUserPurchasedProduct(int $userId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Use WooCommerce helper function if available
        if (function_exists('wc_customer_bought_product')) {
            return wc_customer_bought_product('', $userId, $productId);
        }

        return false;
    }

    /**
     * Get all product categories as an associative array [term_id => name].
     *
     * @return array<int, string> Array of product category term IDs and names, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $categories = WooCommerce::getProductCategories();
     * ```
     */
    public static function getProductCategories(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get product categories terms
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $categories = [];

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[$term->term_id] = $term->name;
            }
        }

        return $categories;
    }

    /**
     * Get the total number of orders for a specific user.
     *
     * @param int $userId User ID.
     * @return int Total count of orders or 0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $orderCount = WooCommerce::getUserOrderCount(123);
     * ```
     */
    public static function getUserOrderCount(int $userId): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Query orders by customer ID
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'limit' => -1,
            'return' => 'ids',
        ]);

        // Return count of orders
        return count($orders);
    }

    /**
     * Get the average order value (AOV) for a user.
     *
     * @param int $userId User ID.
     * @return float Average order value or 0.0 if no orders or WooCommerce inactive.
     *
     * @example
     * ```php
     * $aov = WooCommerce::getUserAverageOrderValue(123);
     * ```
     */
    public static function getUserAverageOrderValue(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Retrieve all orders for user
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'limit' => -1,
        ]);

        $totalSpent = 0.0;
        $orderCount = count($orders);

        if ($orderCount === 0) {
            return 0.0;
        }

        // Sum total spent in all orders
        foreach ($orders as $order) {
            $totalSpent += (float) $order->get_total();
        }

        // Calculate average order value
        return $totalSpent / $orderCount;
    }

    /**
     * Get the default payment gateway ID for WooCommerce.
     *
     * @return string|null Gateway ID or null if WooCommerce inactive or no default set.
     *
     * @example
     * ```php
     * $defaultGateway = WooCommerce::getDefaultPaymentGateway();
     * ```
     */
    public static function getDefaultPaymentGateway(): ?string
    {
        if (!self::guard()) {
            return null;
        }

        // Retrieve payment gateways
        $gateways = WC()->payment_gateways->get_available_payment_gateways();

        // Get the default gateway ID from options
        $defaultGatewayId = get_option('woocommerce_default_gateway');

        // Check if default gateway is available and return ID
        return isset($gateways[$defaultGatewayId]) ? $defaultGatewayId : null;
    }

    /**
     * Get product IDs linked to a specific coupon.
     *
     * @param string $couponCode Coupon code.
     * @return int[] Array of product IDs the coupon applies to, empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $products = WooCommerce::getCouponProductIds('SUMMER2025');
     * ```
     */
    public static function getCouponProductIds(string $couponCode): array
    {
        if (!self::guard()) {
            return [];
        }

        // Load coupon object by code
        $coupon = new \WC_Coupon($couponCode);

        if (!$coupon->get_id()) {
            return [];
        }

        // Return product IDs the coupon applies to
        return $coupon->get_product_ids();
    }

    /**
     * Get shipping methods enabled for the store.
     *
     * @return array List of shipping method instances or empty array if WooCommerce inactive.
     *
     * @example
     * ```php
     * $methods = WooCommerce::getShippingMethods();
     * ```
     */
    public static function getShippingMethods(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Return all enabled shipping methods
        return WC()->shipping()->get_shipping_methods();
    }

    /**
     * Get all products purchased by a specific user.
     *
     * @param int $userId User ID.
     * @return \WC_Product[] Array of WC_Product objects, empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $products = WooCommerce::getProductsPurchasedByUser(123);
     * ```
     */
    public static function getProductsPurchasedByUser(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get all orders for the user
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'limit' => -1,
        ]);

        $products = [];

        // Loop through each order
        foreach ($orders as $order) {
            // Loop through items in order
            foreach ($order->get_items() as $item) {
                $productId = $item->get_product_id();
                // Avoid duplicates
                if (!isset($products[$productId])) {
                    $product = wc_get_product($productId);
                    if ($product) {
                        $products[$productId] = $product;
                    }
                }
            }
        }

        // Return array of WC_Product objects
        return array_values($products);
    }

    /**
     * Get stock status label for a product.
     *
     * @param int $productId Product ID.
     * @return string Stock status label ('In stock', 'Out of stock', 'On backorder'), empty if WooCommerce inactive or product not found.
     *
     * @example
     * ```php
     * $status = WooCommerce::getProductStockStatusLabel(123);
     * ```
     */
    public static function getProductStockStatusLabel(int $productId): string
    {
        if (!self::guard()) {
            return '';
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return '';
        }

        // Return stock status label using WooCommerce helper
        return wc_get_stock_html($product);
    }

    /**
     * Get all payment gateways enabled in WooCommerce.
     *
     * @return \WC_Payment_Gateway[] Array of payment gateway objects, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $gateways = WooCommerce::getPaymentGateways();
     * ```
     */
    public static function getPaymentGateways(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get all available payment gateways
        return WC()->payment_gateways()->get_available_payment_gateways();
    }

    /**
     * Get order IDs filtered by minimum order total.
     *
     * @param float $minTotal Minimum order total amount.
     * @return int[] Array of order IDs meeting the criteria, empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getOrdersByMinTotal(100.00);
     * ```
     */
    public static function getOrdersByMinTotal(float $minTotal): array
    {
        if (!self::guard()) {
            return [];
        }

        // Fetch orders with total >= $minTotal
        return wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => '_order_total',
                    'value' => $minTotal,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ],
            ],
        ]);
    }

    /**
     * Get the number of products a user has in their wishlist.
     * Note: Requires WooCommerce Wishlist plugin or compatible implementation.
     *
     * @param int $userId User ID.
     * @return int Number of wishlist products, 0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $wishlistCount = WooCommerce::getUserWishlistCount(123);
     * ```
     */
    public static function getUserWishlistCount(int $userId): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Assuming wishlist stored as user meta 'wishlist' with product IDs
        $wishlist = get_user_meta($userId, 'wishlist', true);

        if (is_array($wishlist)) {
            return count($wishlist);
        }

        return 0;
    }

    /**
     * Get a list of all customers who have purchased a specific product.
     *
     * @param int $productId Product ID.
     * @return int[] Array of user IDs who purchased the product, empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $customers = WooCommerce::getCustomersByProduct(456);
     * ```
     */
    public static function getCustomersByProduct(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        $userIds = [];

        // Query orders containing the product
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => 'completed',
            'return' => 'objects',
            'meta_query' => [
                [
                    'key' => '_product_id',
                    'value' => $productId,
                    'compare' => '=',
                ],
            ],
        ]);

        // Loop through orders to extract customer IDs
        foreach ($orders as $order) {
            $customerId = (int) $order->get_customer_id();
            if ($customerId > 0 && !in_array($customerId, $userIds, true)) {
                $userIds[] = $customerId;
            }
        }

        return $userIds;
    }

    /**
     * Get shipping zones with their respective shipping methods.
     *
     * @return array Array of shipping zones, each containing shipping methods, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $zones = WooCommerce::getShippingZonesWithMethods();
     * ```
     */
    public static function getShippingZonesWithMethods(): array
    {
        if (!self::guard()) {
            return [];
        }

        $zones = WC_Shipping_Zones::get_zones();

        // Append default zone manually
        $zones[] = WC_Shipping_Zones::get_zone_by('zone_id', 0);

        $result = [];

        // Loop through zones and get their shipping methods
        foreach ($zones as $zone) {
            $zoneId = $zone->get_id();
            $result[$zoneId] = [
                'zone_name' => $zone->get_zone_name(),
                'methods' => $zone->get_shipping_methods(),
            ];
        }

        return $result;
    }

    /**
     * Update stock quantity for a specific product.
     *
     * @param int $productId Product ID.
     * @param int $quantity New stock quantity.
     * @return bool True if stock updated successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateProductStock(123, 50);
     * ```
     */
    public static function updateProductStock(int $productId, int $quantity): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return false;
        }

        // Set new stock quantity and save product
        $product->set_stock_quantity($quantity);
        $product->save();

        return true;
    }

    /**
     * Get the tax rates applied to a specific product.
     *
     * @param int $productId Product ID.
     * @return array Array of WC_Tax_Rate objects or empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $taxRates = WooCommerce::getProductTaxRates(123);
     * ```
     */
    public static function getProductTaxRates(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return [];
        }

        // Get tax class for the product
        $taxClass = $product->get_tax_class();

        // Retrieve tax rates for the product's tax class
        return WC_Tax::get_rates($taxClass);
    }

    /**
     * Get the total sales amount for a specific product.
     *
     * @param int $productId Product ID.
     * @return float Total sales amount or 0.0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $totalSales = WooCommerce::getProductTotalSales(123);
     * ```
     */
    public static function getProductTotalSales(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Get the total sales count from product meta
        $totalSales = get_post_meta($productId, 'total_sales', true);

        // Return as float, default 0.0
        return is_numeric($totalSales) ? (float) $totalSales : 0.0;
    }

    /**
     * Create a new WooCommerce coupon programmatically.
     *
     * @param string $code Coupon code (unique).
     * @param array $args Array of coupon properties (discount type, amount, usage limits, etc).
     * @return int|null Coupon post ID on success, null on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * $couponId = WooCommerce::createCoupon('SUMMER25', ['discount_type' => 'percent', 'amount' => 25]);
     * ```
     */
    public static function createCoupon(string $code, array $args = []): ?int
    {
        if (!self::guard()) {
            return null;
        }

        // Check if coupon already exists by code
        $existingCoupon = new \WC_Coupon($code);
        if ($existingCoupon->get_id()) {
            return null; // Coupon code already exists
        }

        // Prepare default coupon post data
        $postData = [
            'post_title' => $code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1, // Admin user ID, change as needed
            'post_type' => 'shop_coupon',
        ];

        // Insert the coupon post
        $couponId = wp_insert_post($postData);

        if (!$couponId) {
            return null;
        }

        // Create WC_Coupon object
        $coupon = new \WC_Coupon($couponId);

        // Set coupon properties from args
        foreach ($args as $key => $value) {
            $setter = 'set_' . $key;
            if (method_exists($coupon, $setter)) {
                $coupon->{$setter}($value);
            } else {
                // Fallback: update post meta directly
                update_post_meta($couponId, $key, $value);
            }
        }

        // Save coupon to persist changes
        $coupon->save();

        return $couponId;
    }

    /**
     * Delete a WooCommerce coupon by coupon code.
     *
     * @param string $code Coupon code.
     * @return bool True if deleted, false if not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $deleted = WooCommerce::deleteCoupon('SUMMER25');
     * ```
     */
    public static function deleteCoupon(string $code): bool
    {
        if (!self::guard()) {
            return false;
        }

        $coupon = new \WC_Coupon($code);

        if (!$coupon->get_id()) {
            return false;
        }

        // Delete coupon post permanently
        return wp_delete_post($coupon->get_id(), true) !== false;
    }

    /**
     * Get a summary of stock quantities for all products.
     *
     * @return array Associative array ['total_stock' => int, 'total_products' => int, 'out_of_stock' => int], empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $stockSummary = WooCommerce::getStockSummary();
     * ```
     */
    public static function getStockSummary(): array
    {
        if (!self::guard()) {
            return [];
        }

        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $productIds = get_posts($args);

        $totalStock = 0;
        $outOfStockCount = 0;

        foreach ($productIds as $productId) {
            $product = wc_get_product($productId);
            if (!$product) {
                continue;
            }

            // Sum stock quantities where stock is managed
            if ($product->managing_stock()) {
                $stockQty = $product->get_stock_quantity() ?? 0;
                $totalStock += $stockQty;
                if ($stockQty <= 0) {
                    $outOfStockCount++;
                }
            }
        }

        return [
            'total_stock' => $totalStock,
            'total_products' => count($productIds),
            'out_of_stock' => $outOfStockCount,
        ];
    }

    /**
     * Check if an order contains a specific product.
     *
     * @param int $orderId Order ID.
     * @param int $productId Product ID.
     * @return bool True if product is in order, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $hasProduct = WooCommerce::orderHasProduct(987, 123);
     * ```
     */
    public static function orderHasProduct(int $orderId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Check each order item for matching product ID
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() === $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total revenue for a specific product.
     *
     * @param int $productId Product ID.
     * @return float Total revenue generated by the product, or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $revenue = WooCommerce::getProductRevenue(123);
     * ```
     */
    public static function getProductRevenue(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing'],
        ]);

        $totalRevenue = 0.0;

        // Loop through each order to calculate revenue for this product
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() === $productId) {
                    $totalRevenue += $item->get_total();
                }
            }
        }

        return $totalRevenue;
    }

    /**
     * Get average order value (AOV) across all orders.
     *
     * @return float Average order value or 0 if WooCommerce inactive or no orders.
     *
     * @example
     * ```php
     * $aov = WooCommerce::getAverageOrderValue();
     * ```
     */
    public static function getAverageOrderValue(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing'],
            'return' => 'objects',
        ]);

        $totalAmount = 0.0;
        $orderCount = count($orders);

        if ($orderCount === 0) {
            return 0.0;
        }

        // Sum totals of all orders
        foreach ($orders as $order) {
            $totalAmount += (float) $order->get_total();
        }

        // Calculate and return average order value
        return $totalAmount / $orderCount;
    }

    /**
     * Get all product categories with product counts.
     *
     * @return array Associative array of category slug => ['name' => string, 'count' => int], empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $categories = WooCommerce::getProductCategoriesWithCounts();
     * ```
     */
    public static function getProductCategoriesWithCounts(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get all product categories
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $result = [];

        // Loop through each category to build result
        foreach ($terms as $term) {
            $result[$term->slug] = [
                'name' => $term->name,
                'count' => (int) $term->count,
            ];
        }

        return $result;
    }

    /**
     * Get total refunded amount for an order.
     *
     * @param int $orderId Order ID.
     * @return float Total refunded amount or 0 if WooCommerce inactive or no refunds.
     *
     * @example
     * ```php
     * $refundAmount = WooCommerce::getOrderRefundAmount(987);
     * ```
     */
    public static function getOrderRefundAmount(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return 0.0;
        }

        // Sum all refunds amounts
        $refunds = $order->get_refunds();
        $totalRefunded = 0.0;

        foreach ($refunds as $refund) {
            $totalRefunded += (float) $refund->get_amount();
        }

        return $totalRefunded;
    }

    /**
     * Get shipping methods available for a specific shipping zone.
     *
     * @param int $zoneId Shipping zone ID.
     * @return \WC_Shipping_Method[] Array of shipping methods or empty array if WooCommerce inactive.
     *
     * @example
     * ```php
     * $methods = WooCommerce::getShippingMethodsForZone(1);
     * ```
     */
    public static function getShippingMethodsForZone(int $zoneId): array
    {
        if (!self::guard()) {
            return [];
        }

        $zone = \WC_Shipping_Zones::get_zone_by('zone_id', $zoneId);

        if (!$zone) {
            return [];
        }

        // Return shipping methods for the zone
        return $zone->get_shipping_methods();
    }

    /**
     * Get the default WooCommerce currency symbol.
     *
     * @return string Currency symbol or empty string if WooCommerce inactive.
     *
     * @example
     * ```php
     * $symbol = WooCommerce::getCurrencySymbol();
     * ```
     */
    public static function getCurrencySymbol(): string
    {
        if (!self::guard()) {
            return '';
        }

        // Return the currency symbol based on store settings
        return get_woocommerce_currency_symbol();
    }

    /**
     * Get product variations for a variable product.
     *
     * @param int $productId Product ID (must be variable product).
     * @return \WC_Product_Variation[] Array of variation products, empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $variations = WooCommerce::getProductVariations(123);
     * ```
     */
    public static function getProductVariations(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        $product = wc_get_product($productId);

        if (!$product || $product->get_type() !== 'variable') {
            return [];
        }

        // Return variations as array of WC_Product_Variation objects
        return $product->get_children() ? array_map(fn($id) => wc_get_product($id), $product->get_children()) : [];
    }

    /**
     * Get total number of orders by status.
     *
     * @param string|string[] $statuses Order status or array of statuses.
     * @return int Number of orders or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $completedCount = WooCommerce::getOrderCountByStatus('completed');
     * ```
     */
    public static function getOrderCountByStatus(string|array $statuses): int
    {
        if (!self::guard()) {
            return 0;
        }

        $statuses = (array) $statuses;

        // Query to count orders by status
        $query = new \WC_Order_Query([
            'limit' => 1,
            'status' => $statuses,
            'return' => 'ids',
        ]);

        $orders = $query->get_orders();

        return count($orders);
    }

    /**
     * Get the top selling products.
     *
     * @param int $limit Number of products to retrieve.
     * @return \WC_Product[] Array of WC_Product objects sorted by total sales, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $topProducts = WooCommerce::getTopSellingProducts(10);
     * ```
     */
    public static function getTopSellingProducts(int $limit = 10): array
    {
        if (!self::guard()) {
            return [];
        }

        // Query products ordered by total sales meta key, descending
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'fields' => 'ids',
        ];

        $productIds = get_posts($args);

        // Map IDs to WC_Product objects
        return array_map(fn($id) => wc_get_product($id), $productIds);
    }

    /**
     * Get orders placed within a date range.
     *
     * @param string $startDate Start date in 'Y-m-d' format.
     * @param string $endDate End date in 'Y-m-d' format.
     * @param string|string[] $statuses Order statuses to filter by.
     * @return \WC_Order[] Array of orders in date range, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getOrdersByDateRange('2025-01-01', '2025-01-31', 'completed');
     * ```
     */
    public static function getOrdersByDateRange(string $startDate, string $endDate, string|array $statuses = 'completed'): array
    {
        if (!self::guard()) {
            return [];
        }

        $statuses = (array) $statuses;

        // Query orders between start and end dates with specified statuses
        return wc_get_orders([
            'date_created' => $startDate . '...' . $endDate,
            'status' => $statuses,
            'limit' => -1,
        ]);
    }

    /**
     * Get a summary of customer spending.
     *
     * @param int $userId User ID.
     * @return float Total amount spent by the customer, 0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $totalSpent = WooCommerce::getCustomerTotalSpent(123);
     * ```
     */
    public static function getCustomerTotalSpent(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $customer = new \WC_Customer($userId);

        // Return total spent amount
        return (float) $customer->get_total_spent();
    }

    /**
     * Get coupon usage count by coupon code.
     *
     * @param string $code Coupon code.
     * @return int Number of times coupon used, 0 if not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $usageCount = WooCommerce::getCouponUsageCount('SUMMER25');
     * ```
     */
    public static function getCouponUsageCount(string $code): int
    {
        if (!self::guard()) {
            return 0;
        }

        $coupon = new \WC_Coupon($code);

        if (!$coupon->get_id()) {
            return 0;
        }

        // Return usage count
        return (int) $coupon->get_usage_count();
    }

    /**
     * Get all downloadable files for a given product.
     *
     * @param int $productId Product ID.
     * @return array Array of downloadable files (name => file URL), empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $downloads = WooCommerce::getProductDownloadableFiles(123);
     * ```
     */
    public static function getProductDownloadableFiles(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->is_downloadable()) {
            return [];
        }

        // Return downloadable files array
        return $product->get_downloads();
    }

    /**
     * Get a list of all tags used on products.
     *
     * @return array Associative array of tag slug => tag name, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $tags = WooCommerce::getProductTags();
     * ```
     */
    public static function getProductTags(): array
    {
        if (!self::guard()) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ]);

        $result = [];

        // Build slug => name map
        foreach ($terms as $term) {
            $result[$term->slug] = $term->name;
        }

        return $result;
    }

    /**
     * Get the most recent orders.
     *
     * @param int $limit Number of orders to retrieve.
     * @return \WC_Order[] Array of WC_Order objects ordered by creation date, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $recentOrders = WooCommerce::getRecentOrders(5);
     * ```
     */
    public static function getRecentOrders(int $limit = 5): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get most recent orders regardless of status
        return wc_get_orders([
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    /**
     * Get total number of products in the store.
     *
     * @return int Number of published products or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $productCount = WooCommerce::getTotalProductsCount();
     * ```
     */
    public static function getTotalProductsCount(): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Query count of published products
        return (int) wp_count_posts('product')->publish;
    }

    /**
     * Get the total stock quantity for a product (including variations).
     *
     * @param int $productId Product ID.
     * @return int Total stock quantity, 0 if WooCommerce inactive or product not found.
     *
     * @example
     * ```php
     * $stockQty = WooCommerce::getTotalStockQuantity(123);
     * ```
     */
    public static function getTotalStockQuantity(int $productId): int
    {
        if (!self::guard()) {
            return 0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0;
        }

        // If variable product, sum stock quantities of all variations
        if ($product->is_type('variable')) {
            $totalStock = 0;

            foreach ($product->get_children() as $variationId) {
                $variation = wc_get_product($variationId);
                if ($variation && $variation->managing_stock()) {
                    $totalStock += (int) $variation->get_stock_quantity();
                }
            }

            return $totalStock;
        }

        // For simple products, return stock quantity if managed
        if ($product->managing_stock()) {
            return (int) $product->get_stock_quantity();
        }

        return 0;
    }

    /**
     * Get the SKU for a product.
     *
     * @param int $productId Product ID.
     * @return string SKU string or empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $sku = WooCommerce::getProductSKU(123);
     * ```
     */
    public static function getProductSKU(int $productId): string
    {
        if (!self::guard()) {
            return '';
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return '';
        }

        // Return product SKU
        return (string) $product->get_sku();
    }

    /**
     * Get the tax classes available in WooCommerce.
     *
     * @return string[] Array of tax class names, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $taxClasses = WooCommerce::getTaxClasses();
     * ```
     */
    public static function getTaxClasses(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get tax classes from WooCommerce settings
        $classes = WC_Tax::get_tax_classes();

        // Add 'Standard' as first element as WooCommerce does
        array_unshift($classes, 'Standard');

        return $classes;
    }

    /**
     * Apply a coupon code to the current cart session.
     *
     * @param string $couponCode Coupon code to apply.
     * @return bool True if coupon applied successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $applied = WooCommerce::applyCoupon('SAVE20');
     * ```
     */
    public static function applyCoupon(string $couponCode): bool
    {
        if (!self::guard()) {
            return false;
        }

        if (!WC()->cart) {
            return false;
        }

        // Return success or failure
        return WC()->cart->apply_coupon($couponCode);
    }

    /**
     * Remove a coupon code from the current cart session.
     *
     * @param string $couponCode Coupon code to remove.
     * @return bool True if coupon removed successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $removed = WooCommerce::removeCoupon('SAVE20');
     * ```
     */
    public static function removeCoupon(string $couponCode): bool
    {
        if (!self::guard()) {
            return false;
        }

        if (!WC()->cart) {
            return false;
        }

        // Remove coupon code from cart
        WC()->cart->remove_coupon($couponCode);

        // Recalculate totals after coupon removal
        WC()->cart->calculate_totals();

        // Check if coupon still exists in cart
        return !in_array($couponCode, WC()->cart->get_applied_coupons(), true);
    }

    /**
     * Get the last order placed by a user.
     *
     * @param int $userId User ID.
     * @return \WC_Order|null Last order object or null if none found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $lastOrder = WooCommerce::getLastOrder(123);
     * ```
     */
    public static function getLastOrder(int $userId): ?\WC_Order
    {
        if (!self::guard()) {
            return null;
        }

        // Fetch most recent order for user
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        // Return the first order or null
        return $orders[0] ?? null;
    }

    /**
     * Get the total amount refunded for an order.
     *
     * @param int $orderId Order ID.
     * @return float Total refunded amount or 0 if WooCommerce inactive or no refunds.
     *
     * @example
     * ```php
     * $refundedAmount = WooCommerce::getOrderRefundedAmount(456);
     * ```
     */
    public static function getOrderRefundedAmount(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return 0.0;
        }

        // Get total refunded amount
        return (float) $order->get_total_refunded();
    }

    /**
     * Check if a product is virtual.
     *
     * @param int $productId Product ID.
     * @return bool True if product is virtual, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $isVirtual = WooCommerce::isProductVirtual(123);
     * ```
     */
    public static function isProductVirtual(int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Return virtual status of product
        return $product->is_virtual();
    }

    /**
     * Create a new order programmatically.
     *
     * @param int $userId Customer user ID.
     * @param array $items Associative array of product_id => quantity.
     * @param string $status Order status (default: 'pending').
     * @return \WC_Order|null Created order object or null on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * $order = WooCommerce::createOrder(123, [456 => 2, 789 => 1], 'processing');
     * ```
     */
    public static function createOrder(int $userId, array $items, string $status = 'pending'): ?\WC_Order
    {
        if (!self::guard()) {
            return null;
        }

        // Create new order object
        $order = wc_create_order(['customer_id' => $userId]);

        if (!$order) {
            return null;
        }

        // Add products and quantities to the order
        foreach ($items as $productId => $qty) {
            $product = wc_get_product($productId);

            if ($product) {
                $order->add_product($product, $qty);
            }
        }

        // Set order status
        $order->set_status($status);

        // Calculate totals and save
        $order->calculate_totals();
        $order->save();

        return $order;
    }

    /**
     * Get the product IDs in a given order.
     *
     * @param int $orderId Order ID.
     * @return int[] Array of product IDs in the order, empty if WooCommerce inactive or order not found.
     *
     * @example
     * ```php
     * $productIds = WooCommerce::getOrderProductIds(456);
     * ```
     */
    public static function getOrderProductIds(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        $productIds = [];

        // Loop through items in order
        foreach ($order->get_items() as $item) {
            $productIds[] = $item->get_product_id();
        }

        return $productIds;
    }

    /**
     * Get the number of products on sale.
     *
     * @return int Number of products on sale, 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $count = WooCommerce::getOnSaleProductsCount();
     * ```
     */
    public static function getOnSaleProductsCount(): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Get IDs of on-sale products
        $ids = wc_get_product_ids_on_sale();

        // Return count
        return count($ids);
    }

    /**
     * Get average rating for a product.
     *
     * @param int $productId Product ID.
     * @return float Average rating or 0 if no ratings or WooCommerce inactive.
     *
     * @example
     * ```php
     * $avgRating = WooCommerce::getProductAverageRating(123);
     * ```
     */
    public static function getProductAverageRating(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0.0;
        }

        // Return average rating value
        return (float) $product->get_average_rating();
    }

    /**
     * Get stock status of a product.
     *
     * @param int $productId Product ID.
     * @return string Stock status ('instock', 'outofstock', 'onbackorder') or empty string if WooCommerce inactive.
     *
     * @example
     * ```php
     * $status = WooCommerce::getProductStockStatus(123);
     * ```
     */
    public static function getProductStockStatus(int $productId): string
    {
        if (!self::guard()) {
            return '';
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return '';
        }

        // Return stock status string
        return $product->get_stock_status();
    }

    /**
     * Get payment method title by method ID.
     *
     * @param string $methodId Payment method ID.
     * @return string Title of the payment method or empty string if WooCommerce inactive or not found.
     *
     * @example
     * ```php
     * $title = WooCommerce::getPaymentMethodTitle('paypal');
     * ```
     */
    public static function getPaymentMethodTitle(string $methodId): string
    {
        if (!self::guard()) {
            return '';
        }

        $gateways = WC()->payment_gateways()->payment_gateways();

        // Return title if method exists
        return isset($gateways[$methodId]) ? $gateways[$methodId]->get_title() : '';
    }

    /**
     * Get all downloadable products for a user.
     *
     * @param int $userId User ID.
     * @return array Array of downloadable products keyed by product ID with download data.
     *
     * @example
     * ```php
     * $downloads = WooCommerce::getUserDownloads(123);
     * ```
     */
    public static function getUserDownloads(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        $customer = new \WC_Customer($userId);

        // Return downloads data
        return $customer->get_downloadable_products();
    }

    /**
     * Get all coupons that are currently active.
     *
     * @return \WC_Coupon[] Array of active coupon objects, empty if WooCommerce inactive.
     *
     * @example
     * ```php
     * $coupons = WooCommerce::getActiveCoupons();
     * ```
     */
    public static function getActiveCoupons(): array
    {
        if (!self::guard()) {
            return [];
        }

        $args = [
            'posts_per_page' => -1,
            'post_type'      => 'shop_coupon',
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'expiry_date',
                    'value'   => date('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
        ];

        $posts = get_posts($args);

        $coupons = [];

        // Instantiate WC_Coupon for each post
        foreach ($posts as $post) {
            $coupon = new \WC_Coupon($post->ID);
            $coupons[] = $coupon;
        }

        return $coupons;
    }

    /**
     * Get the billing country of a user from their last order.
     *
     * @param int $userId User ID.
     * @return string|null Billing country code or null if no orders or WooCommerce inactive.
     *
     * @example
     * ```php
     * $country = WooCommerce::getUserBillingCountry(123);
     * ```
     */
    public static function getUserBillingCountry(int $userId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $order = self::getLastOrder($userId);

        if (!$order) {
            return null;
        }

        // Return billing country from order
        return $order->get_billing_country();
    }

    /**
     * Get products featured flag for a list of product IDs.
     *
     * @param int[] $productIds Array of product IDs.
     * @return array Associative array productId => bool (true if featured).
     *
     * @example
     * ```php
     * $featured = WooCommerce::areProductsFeatured([123, 456]);
     * ```
     */
    public static function areProductsFeatured(array $productIds): array
    {
        if (!self::guard()) {
            return [];
        }

        $result = [];

        // Loop through product IDs to check featured status
        foreach ($productIds as $id) {
            $product = wc_get_product($id);
            $result[$id] = $product ? $product->is_featured() : false;
        }

        return $result;
    }

    /**
     * Get an array of product IDs that are low in stock.
     *
     * @param int $threshold Stock quantity threshold (default 5).
     * @return int[] Array of product IDs with stock below threshold.
     *
     * @example
     * ```php
     * $lowStock = WooCommerce::getLowStockProducts(10);
     * ```
     */
    public static function getLowStockProducts(int $threshold = 5): array
    {
        if (!self::guard()) {
            return [];
        }

        $args = [
            'limit'       => -1,
            'stock_status'=> 'instock',
            'meta_query'  => [
                [
                    'key'     => '_stock',
                    'value'   => $threshold,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        $products = wc_get_products($args);

        $ids = [];

        // Collect product IDs that meet criteria
        foreach ($products as $product) {
            if ($product->managing_stock()) {
                $ids[] = $product->get_id();
            }
        }

        return $ids;
    }

    /**
     * Get order total amount by order ID.
     *
     * @param int $orderId Order ID.
     * @return float Order total or 0 if WooCommerce inactive or order not found.
     *
     * @example
     * ```php
     * $total = WooCommerce::getOrderTotal(456);
     * ```
     */
    public static function getOrderTotal(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return 0.0;
        }

        // Return total amount for the order
        return (float) $order->get_total();
    }

    /**
     * Get all products in a specific category.
     *
     * @param string|int $category Category slug or ID.
     * @param int $limit Number of products to return. Default -1 for all.
     * @return \WC_Product[] Array of product objects.
     *
     * @example
     * ```php
     * $products = WooCommerce::getProductsByCategory('clothing', 10);
     * ```
     */
    public static function getProductsByCategory(string|int $category, int $limit = -1): array
    {
        if (!self::guard()) {
            return [];
        }

        // Prepare query arguments
        $args = [
            'limit' => $limit,
            'category' => is_int($category) ? '' : [$category],
            'category_operator' => 'IN',
        ];

        // If category is ID, convert to slug
        if (is_int($category)) {
            $term = get_term($category, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $args['category'] = [$term->slug];
            } else {
                $args['category'] = [];
            }
        }

        // Fetch products by category
        return wc_get_products($args);
    }

    /**
     * Get all orders of a user.
     *
     * @param int $userId User ID.
     * @param string[] $statuses Order statuses to filter. Defaults to all.
     * @return \WC_Order[] Array of order objects.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getUserOrders(123, ['wc-completed']);
     * ```
     */
    public static function getUserOrders(int $userId, array $statuses = []): array
    {
        if (!self::guard()) {
            return [];
        }

        // Set default statuses if none provided
        if (empty($statuses)) {
            $statuses = array_keys(wc_get_order_statuses());
        }

        // Query orders for user with given statuses
        return wc_get_orders([
            'customer_id' => $userId,
            'status'      => $statuses,
            'limit'       => -1,
        ]);
    }

    /**
     * Check if a product is downloadable.
     *
     * @param int $productId Product ID.
     * @return bool True if downloadable, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $isDownloadable = WooCommerce::isProductDownloadable(123);
     * ```
     */
    public static function isProductDownloadable(int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Return downloadable flag
        return $product->is_downloadable();
    }

    /**
     * Create a simple product programmatically.
     *
     * @param string $name Product name.
     * @param float $price Product regular price.
     * @param int $stock Stock quantity.
     * @return int|false Product ID on success, false on failure.
     *
     * @example
     * ```php
     * $productId = WooCommerce::createSimpleProduct('Sample', 29.99, 10);
     * ```
     */
    public static function createSimpleProduct(string $name, float $price, int $stock): int|false
    {
        if (!self::guard()) {
            return false;
        }

        // Prepare product data array
        $productData = [
            'post_title'   => $name,
            'post_status'  => 'publish',
            'post_type'    => 'product',
            'meta_input'   => [
                '_regular_price' => $price,
                '_price'         => $price,
                '_stock'         => $stock,
                '_stock_status'  => $stock > 0 ? 'instock' : 'outofstock',
                '_manage_stock'  => 'yes',
                '_visibility'    => 'visible',
            ],
        ];

        // Insert product post
        $productId = wp_insert_post($productData);

        if (is_wp_error($productId) || !$productId) {
            return false;
        }

        // Set product type to simple
        wp_set_object_terms($productId, 'simple', 'product_type');

        return $productId;
    }

    /**
     * Get the total number of products in the store.
     *
     * @return int Number of published products or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $count = WooCommerce::getProductCount();
     * ```
     */
    public static function getProductCount(): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Get count of published products
        $count = wp_count_posts('product');

        return $count->publish ?? 0;
    }

    /**
     * Check if an order contains a specific product.
     *
     * @param int $orderId Order ID.
     * @param int $productId Product ID to check.
     * @return bool True if product found in order, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $hasProduct = WooCommerce::orderContainsProduct(456, 123);
     * ```
     */
    public static function orderContainsProduct(int $orderId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Loop through order items to find product
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() === $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all downloadable orders for a user.
     *
     * @param int $userId User ID.
     * @return \WC_Order[] Array of orders containing downloadable products.
     *
     * @example
     * ```php
     * $downloadableOrders = WooCommerce::getUserDownloadableOrders(123);
     * ```
     */
    public static function getUserDownloadableOrders(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        $orders = self::getUserOrders($userId);

        $downloadableOrders = [];

        // Filter orders with downloadable items
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && $product->is_downloadable()) {
                    $downloadableOrders[] = $order;
                    break; // No need to check more items
                }
            }
        }

        return $downloadableOrders;
    }

    /**
     * Add a note to an order.
     *
     * @param int $orderId Order ID.
     * @param string $note Note content.
     * @param bool $isCustomerNote Whether the note is visible to customer. Default false.
     * @return bool True on success, false on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::addOrderNote(456, 'Shipped via UPS', false);
     * ```
     */
    public static function addOrderNote(int $orderId, string $note, bool $isCustomerNote = false): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Add order note, optionally visible to customer
        $order->add_order_note($note, $isCustomerNote);

        return true;
    }

    /**
     * Get all products that are out of stock.
     *
     * @return \WC_Product[] Array of out-of-stock product objects.
     *
     * @example
     * ```php
     * $outOfStockProducts = WooCommerce::getOutOfStockProducts();
     * ```
     */
    public static function getOutOfStockProducts(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Query for out of stock products
        return wc_get_products([
            'limit'        => -1,
            'stock_status' => 'outofstock',
        ]);
    }

    /**
     * Get all tax rates configured in WooCommerce.
     *
     * @return array Array of tax rate objects.
     *
     * @example
     * ```php
     * $taxRates = WooCommerce::getTaxRates();
     * ```
     */
    public static function getTaxRates(): array
    {
        if (!self::guard()) {
            return [];
        }

        // Access WooCommerce tax rates via global WC_Tax class
        return \WC_Tax::get_rates();
    }

    /**
     * Calculate shipping cost for a given shipping method and package.
     *
     * @param string $methodId Shipping method ID.
     * @param array $package Shipping package array.
     * @return float Shipping cost or 0 if WooCommerce inactive or method not found.
     *
     * @example
     * ```php
     * $cost = WooCommerce::calculateShippingCost('flat_rate', $package);
     * ```
     */
    public static function calculateShippingCost(string $methodId, array $package): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Get shipping zones
        $zones = \WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            foreach ($zone['shipping_methods'] as $method) {
                if ($method->id === $methodId && $method->is_enabled()) {
                    // Calculate cost using method's calculate_shipping function
                    $method->calculate_shipping($package);
                    return $method->cost ?? 0.0;
                }
            }
        }

        // Also check default zone
        $defaultZone = \WC_Shipping_Zones::get_zone_by('zone_id', 0);
        foreach ($defaultZone->get_shipping_methods() as $method) {
            if ($method->id === $methodId && $method->is_enabled()) {
                $method->calculate_shipping($package);
                return $method->cost ?? 0.0;
            }
        }

        return 0.0;
    }

    /**
     * Get all coupons applied to an order.
     *
     * @param int $orderId Order ID.
     * @return string[] Array of coupon codes applied to the order.
     *
     * @example
     * ```php
     * $coupons = WooCommerce::getOrderCoupons(456);
     * ```
     */
    public static function getOrderCoupons(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        // Retrieve coupon codes applied to the order
        return $order->get_coupon_codes();
    }

    /**
     * Get a product's price including or excluding tax.
     *
     * @param int $productId Product ID.
     * @param bool $includeTax Whether to include tax in price. Default true.
     * @return float Price amount or 0 if WooCommerce inactive or product missing.
     *
     * @example
     * ```php
     * $priceInclTax = WooCommerce::getProductPrice(123, true);
     * $priceExclTax = WooCommerce::getProductPrice(123, false);
     * ```
     */
    public static function getProductPrice(int $productId, bool $includeTax = true): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0.0;
        }

        // Return price including or excluding tax
        return (float) wc_get_price_including_tax($product, ['price' => $product->get_price()]) * ($includeTax ? 1 : 0)
            ?: wc_get_price_excluding_tax($product);
    }

    /**
     * Check if an order has been refunded fully.
     *
     * @param int $orderId Order ID.
     * @return bool True if order fully refunded, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $isRefunded = WooCommerce::isOrderFullyRefunded(456);
     * ```
     */
    public static function isOrderFullyRefunded(int $orderId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Compare total refunded amount to order total
        return (float) $order->get_total_refunded() >= (float) $order->get_total();
    }

    /**
     * Get customer ID from an order.
     *
     * @param int $orderId Order ID.
     * @return int|null Customer user ID or null if guest or WooCommerce inactive.
     *
     * @example
     * ```php
     * $customerId = WooCommerce::getOrderCustomerId(456);
     * ```
     */
    public static function getOrderCustomerId(int $orderId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Get user ID; return null if guest
        return $order->get_user_id() ?: null;
    }

    /**
     * Get shipping address from an order.
     *
     * @param int $orderId Order ID.
     * @return array Associative array with shipping address fields or empty array if WooCommerce inactive or order missing.
     *
     * @example
     * ```php
     * $shippingAddress = WooCommerce::getOrderShippingAddress(456);
     * ```
     */
    public static function getOrderShippingAddress(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        // Return associative array of shipping address fields
        return [
            'first_name' => $order->get_shipping_first_name(),
            'last_name'  => $order->get_shipping_last_name(),
            'company'    => $order->get_shipping_company(),
            'address_1'  => $order->get_shipping_address_1(),
            'address_2'  => $order->get_shipping_address_2(),
            'city'       => $order->get_shipping_city(),
            'state'      => $order->get_shipping_state(),
            'postcode'   => $order->get_shipping_postcode(),
            'country'    => $order->get_shipping_country(),
        ];
    }

    /**
     * Get order items with product details.
     *
     * @param int $orderId Order ID.
     * @return array Array of items with product ID, name, quantity, and total.
     *
     * @example
     * ```php
     * $items = WooCommerce::getOrderItems(456);
     * ```
     */
    public static function getOrderItems(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        $itemsData = [];

        // Loop through order items
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            $itemsData[] = [
                'product_id' => $product ? $product->get_id() : null,
                'name'       => $item->get_name(),
                'quantity'   => $item->get_quantity(),
                'total'      => $item->get_total(),
            ];
        }

        return $itemsData;
    }

    /**
     * Get total number of products sold.
     *
     * @param int $productId Product ID.
     * @return int Number of units sold or 0 if WooCommerce inactive or product missing.
     *
     * @example
     * ```php
     * $unitsSold = WooCommerce::getProductUnitsSold(123);
     * ```
     */
    public static function getProductUnitsSold(int $productId): int
    {
        if (!self::guard()) {
            return 0;
        }

        // Retrieve product sales count from post meta
        return (int) get_post_meta($productId, 'total_sales', true);
    }

    /**
     * Get product stock quantity.
     *
     * @param int $productId Product ID.
     * @return int|null Stock quantity or null if stock not managed or WooCommerce inactive.
     *
     * @example
     * ```php
     * $stockQty = WooCommerce::getProductStockQuantity(123);
     * ```
     */
    public static function getProductStockQuantity(int $productId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return null;
        }

        // Return current stock quantity
        return $product->get_stock_quantity();
    }

    /**
     * Increase stock for a product by quantity.
     *
     * @param int $productId Product ID.
     * @param int $quantity Quantity to increase.
     * @return bool True on success, false on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * $success = WooCommerce::increaseProductStock(123, 5);
     * ```
     */
    public static function increaseProductStock(int $productId, int $quantity): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return false;
        }

        $currentStock = $product->get_stock_quantity() ?? 0;

        // Increase stock quantity
        $product->set_stock_quantity($currentStock + $quantity);

        // Save product changes
        $product->save();

        return true;
    }

    /**
     * Check if a product is backorder allowed.
     *
     * @param int $productId Product ID.
     * @return bool True if backorders allowed, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $backorderAllowed = WooCommerce::isBackorderAllowed(123);
     * ```
     */
    public static function isBackorderAllowed(int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return false;
        }

        // Return backorder status (yes, no, notify)
        return in_array($product->get_backorders(), ['yes', 'notify'], true);
    }

    /**
     * Get product's regular price.
     *
     * @param int $productId Product ID.
     * @return float Regular price or 0 if WooCommerce inactive or product missing.
     *
     * @example
     * ```php
     * $regularPrice = WooCommerce::getProductRegularPrice(123);
     * ```
     */
    public static function getProductRegularPrice(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0.0;
        }

        // Return regular price as float
        return (float) $product->get_regular_price();
    }

    /**
     * Get product's sale price.
     *
     * @param int $productId Product ID.
     * @return float|null Sale price or null if not on sale or WooCommerce inactive.
     *
     * @example
     * ```php
     * $salePrice = WooCommerce::getProductSalePrice(123);
     * ```
     */
    public static function getProductSalePrice(int $productId): ?float
    {
        if (!self::guard()) {
            return null;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return null;
        }

        $salePrice = $product->get_sale_price();

        // Return sale price as float or null if not set
        return $salePrice !== '' ? (float) $salePrice : null;
    }

    /**
     * Get order payment method title.
     *
     * @param int $orderId Order ID.
     * @return string|null Payment method title or null if WooCommerce inactive or order missing.
     *
     * @example
     * ```php
     * $paymentMethod = WooCommerce::getOrderPaymentMethod(456);
     * ```
     */
    public static function getOrderPaymentMethod(int $orderId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Return payment method title string
        return $order->get_payment_method_title();
    }

    /**
     * Get billing email from an order.
     *
     * @param int $orderId Order ID.
     * @return string|null Billing email or null if WooCommerce inactive or order missing.
     *
     * @example
     * ```php
     * $email = WooCommerce::getOrderBillingEmail(456);
     * ```
     */
    public static function getOrderBillingEmail(int $orderId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Return billing email from order
        return $order->get_billing_email();
    }

    /**
     * Get shipping method titles used in an order.
     *
     * @param int $orderId Order ID.
     * @return string[] Array of shipping method names or empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $methods = WooCommerce::getOrderShippingMethods(456);
     * ```
     */
    public static function getOrderShippingMethods(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        $methods = [];

        // Loop through shipping items to get method titles
        foreach ($order->get_shipping_methods() as $shipping_item) {
            $methods[] = $shipping_item->get_name();
        }

        return $methods;
    }

    /**
     * Get all tax totals for an order.
     *
     * @param int $orderId Order ID.
     * @return array Associative array of tax code => amount or empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $taxes = WooCommerce::getOrderTaxTotals(456);
     * ```
     */
    public static function getOrderTaxTotals(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        // Return associative array of tax totals
        return $order->get_tax_totals();
    }

    /**
     * Get customer orders by user ID.
     *
     * @param int $userId User ID.
     * @param string[] $statuses Array of order statuses to filter. Default: ['completed', 'processing'].
     * @return \WC_Order[] Array of WC_Order objects or empty if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getCustomerOrders(123, ['completed']);
     * ```
     */
    public static function getCustomerOrders(int $userId, array $statuses = ['completed', 'processing']): array
    {
        if (!self::guard()) {
            return [];
        }

        // Query orders for user with given statuses
        return wc_get_orders([
            'customer' => $userId,
            'status'   => $statuses,
            'limit'    => -1,
        ]);
    }

    /**
     * Get order creation date as DateTime object.
     *
     * @param int $orderId Order ID.
     * @return \DateTime|null DateTime object or null if order missing or WooCommerce inactive.
     *
     * @example
     * ```php
     * $date = WooCommerce::getOrderDate(456);
     * ```
     */
    public static function getOrderDate(int $orderId): ?\DateTime
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Get order creation date string
        $dateStr = $order->get_date_created();

        // Return DateTime object or null
        return $dateStr ? $dateStr->date('Y-m-d H:i:s') : null;
    }

    /**
     * Get user last order date.
     *
     * @param int $userId User ID.
     * @return \DateTime|null Last order date or null if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $lastOrderDate = WooCommerce::getUserLastOrderDate(123);
     * ```
     */
    public static function getUserLastOrderDate(int $userId): ?\DateTime
    {
        if (!self::guard()) {
            return null;
        }

        // Get last order by user, sorted desc by date
        $orders = wc_get_orders([
            'customer' => $userId,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($orders)) {
            return null;
        }

        // Return creation date of last order
        return $orders[0]->get_date_created();
    }

    /**
     * Get order status label.
     *
     * @param int $orderId Order ID.
     * @return string|null Human-readable status label or null if WooCommerce inactive or order missing.
     *
     * @example
     * ```php
     * $status = WooCommerce::getOrderStatusLabel(456);
     * ```
     */
    public static function getOrderStatusLabel(int $orderId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Get order status slug, e.g., 'completed'
        $statusSlug = $order->get_status();

        // Get WooCommerce order statuses labels
        $statuses = wc_get_order_statuses();

        // Return label or null if missing
        return $statuses['wc-' . $statusSlug] ?? null;
    }

    /**
     * Refund a completed order programmatically.
     *
     * @param int $orderId Order ID.
     * @param float|null $amount Refund amount or null to refund full.
     * @param string|null $reason Refund reason.
     * @return bool True on success, false on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * $refundSuccess = WooCommerce::refundOrder(456, 50.00, 'Customer returned item');
     * ```
     */
    public static function refundOrder(int $orderId, ?float $amount = null, ?string $reason = null): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order || !$order->has_status('completed')) {
            return false;
        }

        // Prepare refund data
        $refundAmount = $amount ?? $order->get_total();

        // Create refund
        $refund = wc_create_refund([
            'amount'         => $refundAmount,
            'reason'         => $reason,
            'order_id'       => $orderId,
            'refund_payment' => true,
            'restock_items'  => true,
        ]);

        // Return true if refund is WP_Error false/null otherwise true
        return !is_wp_error($refund);
    }

    /**
     * Get order items as an array with product names and quantities.
     *
     * @param int $orderId Order ID.
     * @return array Array of ['product_name' => string, 'quantity' => int] or empty array on failure.
     *
     * @example
     * ```php
     * $items = WooCommerce::getOrderItemsArray(456);
     * ```
     */
    public static function getOrderItemsArray(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        $itemsArr = [];

        // Loop through order items
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $itemsArr[] = [
                    'product_name' => $product->get_name(),
                    'quantity' => $item->get_quantity(),
                ];
            }
        }

        return $itemsArr;
    }

    /**
     * Add a product to an order programmatically.
     *
     * @param int $orderId Order ID.
     * @param int $productId Product ID.
     * @param int $quantity Quantity to add.
     * @return bool True if added successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $added = WooCommerce::addProductToOrder(456, 123, 2);
     * ```
     */
    public static function addProductToOrder(int $orderId, int $productId, int $quantity = 1): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Add product item to order
        $item = new \WC_Order_Item_Product();
        $item->set_product($product);
        $item->set_quantity($quantity);

        // Add item to order
        $order->add_item($item);

        // Calculate totals and save
        $order->calculate_totals();
        $order->save();

        return true;
    }

    /**
     * Remove a product from an order programmatically.
     *
     * @param int $orderId Order ID.
     * @param int $productId Product ID.
     * @return bool True if removed successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $removed = WooCommerce::removeProductFromOrder(456, 123);
     * ```
     */
    public static function removeProductFromOrder(int $orderId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Loop through order items
        foreach ($order->get_items() as $item_id => $item) {
            if ($item->get_product_id() === $productId) {
                // Remove item from order
                $order->remove_item($item_id);
                $order->calculate_totals();
                $order->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Set product price.
     *
     * @param int $productId Product ID.
     * @param float $price New price.
     * @return bool True if price updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductPrice(123, 19.99);
     * ```
     */
    public static function setProductPrice(int $productId, float $price): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set regular price (you may want to extend for sale price)
        $product->set_regular_price($price);

        // Save product changes
        $product->save();

        return true;
    }

    /**
     * Check if order is paid.
     *
     * @param int $orderId Order ID.
     * @return bool True if order is paid, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $isPaid = WooCommerce::isOrderPaid(456);
     * ```
     */
    public static function isOrderPaid(int $orderId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Check if order status is paid or completed
        return $order->is_paid();
    }

    /**
     * Get user’s last order.
     *
     * @param int $userId User ID.
     * @return \WC_Order|null Last order object or null if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $lastOrder = WooCommerce::getUserLastOrder(123);
     * ```
     */
    public static function getUserLastOrder(int $userId): ?\WC_Order
    {
        if (!self::guard()) {
            return null;
        }

        // Get user orders sorted by date descending
        $orders = wc_get_orders([
            'customer_id' => $userId,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        // Return first order or null
        return $orders[0] ?? null;
    }

    /**
     * Get order status.
     *
     * @param int $orderId Order ID.
     * @return string|null Status slug or null if order not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $status = WooCommerce::getOrderStatus(456);
     * ```
     */
    public static function getOrderStatus(int $orderId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Return order status without 'wc-' prefix
        return $order->get_status();
    }

    /**
     * Delete a product by ID.
     *
     * @param int $productId Product ID.
     * @param bool $forceDelete Whether to force delete or move to trash.
     * @return bool True on success, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $deleted = WooCommerce::deleteProduct(123, true);
     * ```
     */
    public static function deleteProduct(int $productId, bool $forceDelete = false): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Use WP function to delete post/product
        return wp_delete_post($productId, $forceDelete) !== false;
    }

    /**
     * Get order items with quantities.
     *
     * @param int $orderId Order ID.
     * @return array Associative array of product_id => quantity or empty if no order or WooCommerce inactive.
     *
     * @example
     * ```php
     * $items = WooCommerce::getOrderItemsWithQuantity(456);
     * ```
     */
    public static function getOrderItemsWithQuantity(int $orderId): array
    {
        if (!self::guard()) {
            return [];
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return [];
        }

        $items = [];

        // Loop through order items
        foreach ($order->get_items() as $item) {
            $productId = $item->get_product_id();
            $qty = $item->get_quantity();
            $items[$productId] = $qty;
        }

        return $items;
    }

    /**
     * Set product regular price.
     *
     * @param int $productId Product ID.
     * @param float $price New regular price.
     * @return bool True if updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductRegularPrice(123, 99.99);
     * ```
     */
    public static function setProductRegularPrice(int $productId, float $price): bool
    {
        if (!self::guard() || $price < 0) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set and save new regular price
        $product->set_regular_price($price);
        $product->save();

        return true;
    }

    /**
     * Set product sale price.
     *
     * @param int $productId Product ID.
     * @param float|null $price New sale price or null to remove sale price.
     * @return bool True if updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductSalePrice(123, 79.99);
     * ```
     */
    public static function setProductSalePrice(int $productId, ?float $price): bool
    {
        if (!self::guard() || ($price !== null && $price < 0)) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set or remove sale price
        if ($price === null) {
            $product->set_sale_price('');
        } else {
            $product->set_sale_price($price);
        }

        $product->save();

        return true;
    }

    /**
     * Check if a coupon is valid and active.
     *
     * @param string $couponCode Coupon code.
     * @return bool True if coupon exists and valid, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $isValid = WooCommerce::isCouponValid('SUMMER2025');
     * ```
     */
    public static function isCouponValid(string $couponCode): bool
    {
        if (!self::guard()) {
            return false;
        }

        $coupon = new \WC_Coupon($couponCode);

        // Check coupon existence and validity
        return $coupon->get_code() === $couponCode && $coupon->is_valid();
    }

    /**
     * Get product download files URLs.
     *
     * @param int $productId Product ID.
     * @return array Array of download file URLs or empty array if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $files = WooCommerce::getProductDownloadFiles(123);
     * ```
     */
    public static function getProductDownloadFiles(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->is_downloadable()) {
            return [];
        }

        $files = $product->get_downloads();

        $urls = [];

        // Extract file URLs from download objects
        foreach ($files as $file) {
            $urls[] = $file->get_file();
        }

        return $urls;
    }

    /**
     * Set product stock quantity.
     *
     * @param int $productId Product ID.
     * @param int $quantity New stock quantity.
     * @return bool True if updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductStockQuantity(123, 50);
     * ```
     */
    public static function setProductStockQuantity(int $productId, int $quantity): bool
    {
        if (!self::guard() || $quantity < 0) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product || !$product->managing_stock()) {
            return false;
        }

        // Set stock quantity and save product
        $product->set_stock_quantity($quantity);
        $product->save();

        return true;
    }

    /**
     * Check if product is in stock.
     *
     * @param int $productId Product ID.
     * @return bool True if in stock, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $inStock = WooCommerce::isProductInStock(123);
     * ```
     */
    public static function isProductInStock(int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Returns stock status (true/false)
        return $product->is_in_stock();
    }

    /**
     * Set product SKU.
     *
     * @param int $productId Product ID.
     * @param string $sku SKU string.
     * @return bool True if updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductSKU(123, 'SKU12345');
     * ```
     */
    public static function setProductSKU(int $productId, string $sku): bool
    {
        if (!self::guard() || trim($sku) === '') {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set SKU and save product
        $product->set_sku($sku);
        $product->save();

        return true;
    }

    /**
     * Add product category.
     *
     * @param int $productId Product ID.
     * @param int $categoryId Category term ID.
     * @return bool True if category added, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $added = WooCommerce::addProductCategory(123, 15);
     * ```
     */
    public static function addProductCategory(int $productId, int $categoryId): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get existing categories
        $terms = wp_get_post_terms($productId, 'product_cat', ['fields' => 'ids']);

        if (is_wp_error($terms)) {
            return false;
        }

        // Add new category ID if not exists
        if (!in_array($categoryId, $terms, true)) {
            $terms[] = $categoryId;
        }

        // Assign categories back to product
        return wp_set_post_terms($productId, $terms, 'product_cat') !== false;
    }

    /**
     * Remove product category.
     *
     * @param int $productId Product ID.
     * @param int $categoryId Category term ID.
     * @return bool True if category removed, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $removed = WooCommerce::removeProductCategory(123, 15);
     * ```
     */
    public static function removeProductCategory(int $productId, int $categoryId): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get existing categories
        $terms = wp_get_post_terms($productId, 'product_cat', ['fields' => 'ids']);

        if (is_wp_error($terms)) {
            return false;
        }

        // Remove category ID if exists
        $terms = array_filter($terms, fn($termId) => $termId !== $categoryId);

        // Assign updated categories back
        return wp_set_post_terms($productId, $terms, 'product_cat') !== false;
    }

    /**
     * Get customer ID from order.
     *
     * @param int $orderId Order ID.
     * @return int|null Customer user ID or null if not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $customerId = WooCommerce::getCustomerIdFromOrder(987);
     * ```
     */
    public static function getCustomerIdFromOrder(int $orderId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Get user ID associated with the order, 0 if guest order
        $userId = $order->get_user_id();

        return $userId > 0 ? $userId : null;
    }

    /**
     * Add product to cart programmatically.
     *
     * @param int $productId Product ID.
     * @param int $quantity Quantity to add.
     * @param array $variation Attributes if variable product (default empty).
     * @return bool True if added successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $added = WooCommerce::addToCart(123, 2, ['attribute_pa_color' => 'blue']);
     * ```
     */
    public static function addToCart(int $productId, int $quantity = 1, array $variation = []): bool
    {
        if (!self::guard() || !function_exists('WC')) {
            return false;
        }

        // Get WooCommerce cart instance
        $cart = WC()->cart;

        if (!$cart) {
            return false;
        }

        // Add product to cart, variation array optional for variable products
        $added = $cart->add_to_cart($productId, $quantity, 0, $variation);

        return $added !== false;
    }

    /**
     * Remove product from cart programmatically.
     *
     * @param string $cartItemKey Cart item key (unique identifier).
     * @return bool True if removed, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $removed = WooCommerce::removeFromCart('abc123def456');
     * ```
     */
    public static function removeFromCart(string $cartItemKey): bool
    {
        if (!self::guard() || !function_exists('WC')) {
            return false;
        }

        $cart = WC()->cart;

        if (!$cart) {
            return false;
        }

        // Remove cart item by key and return success
        $cart->remove_cart_item($cartItemKey);

        return true;
    }

    /**
     * Get cart total amount.
     *
     * @return float|null Total amount in cart or null if WooCommerce inactive.
     *
     * @example
     * ```php
     * $total = WooCommerce::getCartTotal();
     * ```
     */
    public static function getCartTotal(): ?float
    {
        if (!self::guard() || !function_exists('WC')) {
            return null;
        }

        $cart = WC()->cart;

        if (!$cart) {
            return null;
        }

        // Return cart total amount (float)
        return (float) $cart->get_total('edit');
    }

    /**
     * Empty the current cart.
     *
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::emptyCart();
     * ```
     */
    public static function emptyCart(): void
    {
        if (!self::guard() || !function_exists('WC')) {
            return;
        }

        $cart = WC()->cart;

        if ($cart) {
            // Clear all cart contents
            $cart->empty_cart();
        }
    }

    /**
     * Set product stock quantity.
     *
     * @param int $productId Product ID.
     * @param int $quantity New stock quantity (>= 0).
     * @return bool True if updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * $updated = WooCommerce::setProductStock(123, 50);
     * ```
     */
    public static function setProductStock(int $productId, int $quantity): bool
    {
        if (!self::guard() || $quantity < 0) {
            return false;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set stock quantity and save product
        $product->set_stock_quantity($quantity);

        // If quantity > 0, mark in stock; else out of stock
        $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');

        $product->save();

        return true;
    }

    /**
     * Create a new product programmatically.
     *
     * @param array $data Associative array of product data: name, regular_price, description, sku, stock_quantity, etc.
     * @return int|null New product ID or null if WooCommerce inactive or creation failed.
     *
     * @example
     * ```php
     * $newProductId = WooCommerce::createProduct([
     *     'name' => 'My New Product',
     *     'regular_price' => '19.99',
     *     'description' => 'Product description here',
     *     'sku' => 'MYNEWPROD001',
     *     'stock_quantity' => 100,
     * ]);
     * ```
     */
    public static function createProduct(array $data): ?int
    {
        if (!self::guard()) {
            return null;
        }

        // Setup product post data
        $postData = [
            'post_title'   => $data['name'] ?? 'New Product',
            'post_content' => $data['description'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => 'product',
        ];

        // Insert product post
        $productId = wp_insert_post($postData);

        if (!$productId || is_wp_error($productId)) {
            return null;
        }

        // Get product object
        $product = wc_get_product($productId);

        if (!$product) {
            return null;
        }

        // Set product fields if provided
        if (isset($data['regular_price'])) {
            $product->set_regular_price($data['regular_price']);
        }

        if (isset($data['sale_price'])) {
            $product->set_sale_price($data['sale_price']);
        }

        if (isset($data['sku'])) {
            $product->set_sku($data['sku']);
        }

        if (isset($data['stock_quantity'])) {
            $product->set_stock_quantity($data['stock_quantity']);
            $product->set_stock_status($data['stock_quantity'] > 0 ? 'instock' : 'outofstock');
        }

        if (isset($data['description'])) {
            $product->set_description($data['description']);
        }

        // Save the product
        $product->save();

        return $productId;
    }

    /**
     * Update product price.
     *
     * @param int $productId Product ID.
     * @param float|string $price New regular price.
     * @return bool True on success, false on failure or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateProductPrice(123, 29.99);
     * ```
     */
    public static function updateProductPrice(int $productId, float|string $price): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get the product object
        $product = wc_get_product($productId);

        // Return false if product not found
        if (!$product) {
            return false;
        }

        // Set new regular price
        $product->set_regular_price($price);

        // Save product changes
        $product->save();

        return true;
    }

    /**
     * Add a product tag to a product.
     *
     * @param int $productId Product ID.
     * @param int|string $tag Tag ID or slug.
     * @return bool True if tag added, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::addProductTag(123, 'summer-sale');
     * ```
     */
    public static function addProductTag(int $productId, int|string $tag): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get product
        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Get current tag IDs
        $currentTags = $product->get_tag_ids();

        // Convert slug to ID if string
        if (is_string($tag)) {
            $term = get_term_by('slug', $tag, 'product_tag');
            if (!$term || is_wp_error($term)) {
                return false;
            }
            $tag = $term->term_id;
        }

        // Add tag ID if not already present
        if (!in_array($tag, $currentTags, true)) {
            $currentTags[] = $tag;
        }

        // Assign updated tags
        wp_set_object_terms($productId, $currentTags, 'product_tag');

        return true;
    }

    /**
     * Remove a product tag from a product.
     *
     * @param int $productId Product ID.
     * @param int|string $tag Tag ID or slug.
     * @return bool True if tag removed, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::removeProductTag(123, 'summer-sale');
     * ```
     */
    public static function removeProductTag(int $productId, int|string $tag): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get product
        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Get current tags
        $currentTags = $product->get_tag_ids();

        // Convert slug to ID if needed
        if (is_string($tag)) {
            $term = get_term_by('slug', $tag, 'product_tag');
            if (!$term || is_wp_error($term)) {
                return false;
            }
            $tag = $term->term_id;
        }

        // Remove tag if exists
        $updatedTags = array_filter($currentTags, fn($tagId) => $tagId !== $tag);

        // Assign updated tags
        wp_set_object_terms($productId, $updatedTags, 'product_tag');

        return true;
    }

    /**
     * Get order items count.
     *
     * @param int $orderId Order ID.
     * @return int|null Number of items or null if WooCommerce inactive or order not found.
     *
     * @example
     * ```php
     * $itemsCount = WooCommerce::getOrderItemCount(456);
     * ```
     */
    public static function getOrderItemCount(int $orderId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Return count of items in order
        return count($order->get_items());
    }

    /**
     * Get all orders by customer ID.
     *
     * @param int $customerId User ID of customer.
     * @return \WC_Order[] Array of WC_Order objects or empty array if WooCommerce inactive or none found.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getOrdersByCustomer(789);
     * ```
     */
    public static function getOrdersByCustomer(int $customerId): array
    {
        if (!self::guard()) {
            return [];
        }

        // Query orders for customer user ID
        $orders = wc_get_orders([
            'customer_id' => $customerId,
            'limit' => -1, // no limit
        ]);

        return $orders;
    }

    /**
     * Get customer billing address for an order.
     *
     * @param int $orderId Order ID.
     * @return array|null Associative array with billing address fields or null if WooCommerce inactive or order not found.
     *
     * @example
     * ```php
     * $billingAddress = WooCommerce::getOrderBillingAddress(456);
     * ```
     */
    public static function getOrderBillingAddress(int $orderId): ?array
    {
        if (!self::guard()) {
            return null;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        // Retrieve billing fields from order
        return [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'company'    => $order->get_billing_company(),
            'address_1'  => $order->get_billing_address_1(),
            'address_2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            'state'      => $order->get_billing_state(),
            'postcode'   => $order->get_billing_postcode(),
            'country'    => $order->get_billing_country(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
        ];
    }

    /**
     * Update customer billing phone number for an order.
     *
     * @param int $orderId Order ID.
     * @param string $phone New billing phone number.
     * @return bool True if updated successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateOrderBillingPhone(456, '+1234567890');
     * ```
     */
    public static function updateOrderBillingPhone(int $orderId, string $phone): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Set new billing phone
        $order->set_billing_phone($phone);

        // Save order changes
        $order->save();

        return true;
    }

    /**
     * Update product SKU.
     *
     * @param int $productId Product ID.
     * @param string $sku New SKU string.
     * @return bool True if updated successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateProductSKU(123, 'NEW-SKU-001');
     * ```
     */
    public static function updateProductSKU(int $productId, string $sku): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get product object
        $product = wc_get_product($productId);

        if (!$product) {
            return false;
        }

        // Set new SKU
        $product->set_sku($sku);

        // Save product changes
        $product->save();

        return true;
    }

    /**
     * Set order status.
     *
     * @param int $orderId Order ID.
     * @param string $status New order status (e.g., 'completed', 'processing').
     * @return bool True if status updated, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::setOrderStatus(456, 'completed');
     * ```
     */
    public static function setOrderStatus(int $orderId, string $status): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return false;
        }

        // Update order status safely (prepend 'wc-' if not present)
        $status = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
        $order->set_status(str_replace('wc-', '', $status)); // wc_set_status expects slug without wc-

        // Save order changes
        $order->save();

        return true;
    }

    /**
     * Get products in an order.
     *
     * @param int $orderId Order ID.
     * @return array|null Array of WC_Product objects or null if WooCommerce inactive or order not found.
     *
     * @example
     * ```php
     * $products = WooCommerce::getOrderProducts(456);
     * ```
     */
    public static function getOrderProducts(int $orderId): ?array
    {
        if (!self::guard()) {
            return null;
        }

        // Get order object
        $order = wc_get_order($orderId);

        if (!$order) {
            return null;
        }

        $products = [];

        // Loop through order items
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Get coupon by code.
     *
     * @param string $code Coupon code.
     * @return \WC_Coupon|null Coupon object or null if not found or WooCommerce inactive.
     *
     * @example
     * ```php
     * $coupon = WooCommerce::getCoupon('SUMMER21');
     * ```
     */
    public static function getCoupon(string $code): ?\WC_Coupon
    {
        if (!self::guard()) {
            return null;
        }

        // Load coupon by code
        $coupon = new \WC_Coupon($code);

        // Check if coupon exists
        if (!$coupon || !$coupon->get_id()) {
            return null;
        }

        return $coupon;
    }

    /**
     * Update product categories by slugs.
     *
     * @param int $productId Product ID.
     * @param string[] $categorySlugs Array of category slugs to assign.
     * @return bool True if updated successfully, false otherwise or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::updateProductCategories(123, ['clothing', 'sale']);
     * ```
     */
    public static function updateProductCategories(int $productId, array $categorySlugs): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Set product categories taxonomy terms by slug
        $result = wp_set_object_terms($productId, $categorySlugs, 'product_cat', false);

        // Return true if terms set successfully
        return !is_wp_error($result);
    }

    /**
     * Get customer billing email.
     *
     * @param int $userId User ID.
     * @return string|null Billing email or null if WooCommerce inactive or user not found.
     *
     * @example
     * ```php
     * $email = WooCommerce::getCustomerBillingEmail(123);
     * ```
     */
    public static function getCustomerBillingEmail(int $userId): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $user = get_userdata($userId);

        if (!$user) {
            return null;
        }

        // Use WooCommerce function to get billing email from user meta
        return get_user_meta($userId, 'billing_email', true) ?: null;
    }

    /**
     * Update customer billing phone.
     *
     * @param int $userId User ID.
     * @param string $phone New billing phone number.
     * @return bool True on success, false if WooCommerce inactive or user not found.
     *
     * @example
     * ```php
     * WooCommerce::updateCustomerBillingPhone(123, '+1234567890');
     * ```
     */
    public static function updateCustomerBillingPhone(int $userId, string $phone): bool
    {
        if (!self::guard()) {
            return false;
        }

        $user = get_userdata($userId);

        if (!$user) {
            return false;
        }

        // Update billing phone user meta
        return update_user_meta($userId, 'billing_phone', $phone);
    }

    /**
     * Assign categories to a product.
     *
     * @param int $productId Product ID.
     * @param int[] $categoryIds Array of category IDs.
     * @return bool True on success, false otherwise or if WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::setProductCategories(123, [10, 15]);
     * ```
     */
    public static function setProductCategories(int $productId, array $categoryIds): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Set product categories (terms) for the product post
        $result = wp_set_object_terms($productId, $categoryIds, 'product_cat');

        return !is_wp_error($result);
    }

    /**
     * Add a product to user's wishlist (stored as user meta).
     *
     * @param int $userId User ID.
     * @param int $productId Product ID.
     * @return bool True if added, false if already in wishlist or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::addToWishlist(12, 123);
     * ```
     */
    public static function addToWishlist(int $userId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get current wishlist, default empty array
        $wishlist = get_user_meta($userId, '_wc_wishlist', true) ?: [];

        // Prevent duplicates
        if (in_array($productId, $wishlist, true)) {
            return false;
        }

        // Add product ID and update meta
        $wishlist[] = $productId;
        update_user_meta($userId, '_wc_wishlist', $wishlist);

        return true;
    }

    /**
     * Remove a product from user's wishlist.
     *
     * @param int $userId User ID.
     * @param int $productId Product ID.
     * @return bool True if removed, false if not in wishlist or WooCommerce inactive.
     *
     * @example
     * ```php
     * WooCommerce::removeFromWishlist(12, 123);
     * ```
     */
    public static function removeFromWishlist(int $userId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        // Get current wishlist
        $wishlist = get_user_meta($userId, '_wc_wishlist', true) ?: [];

        // Check if product exists in wishlist
        if (!in_array($productId, $wishlist, true)) {
            return false;
        }

        // Remove product ID and update meta
        $wishlist = array_filter($wishlist, fn($id) => $id !== $productId);
        update_user_meta($userId, '_wc_wishlist', $wishlist);

        return true;
    }

    /**
     * Get user's wishlist.
     *
     * @param int $userId User ID.
     * @return int[] Array of product IDs or empty array if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $wishlist = WooCommerce::getWishlist(12);
     * ```
     */
    public static function getWishlist(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get wishlist or empty array
        return get_user_meta($userId, '_wc_wishlist', true) ?: [];
    }

    /**
     * Get the current user's cart items.
     *
     * @return array List of cart items or empty array if WooCommerce inactive or cart empty.
     *
     * @example
     * ```php
     * $items = WooCommerce::getCartItems();
     * ```
     */
    public static function getCartItems(): array
    {
        if (!self::guard() || !WC()->cart) {
            return [];
        }

        // Get cart contents as array
        return WC()->cart->get_cart();
    }

    /**
     * Clear the current user's cart.
     *
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::clearCart();
     * ```
     */
    public static function clearCart(): void
    {
        if (!self::guard() || !WC()->cart) {
            return;
        }

        // Empty cart contents
        WC()->cart->empty_cart();
    }

    /**
     * Get the current customer's default billing country.
     *
     * @return string|null Country code or null if WooCommerce inactive or no customer.
     *
     * @example
     * ```php
     * $country = WooCommerce::getCustomerBillingCountry();
     * ```
     */
    public static function getCustomerBillingCountry(): ?string
    {
        if (!self::guard()) {
            return null;
        }

        $customer = WC()->customer;

        if (!$customer) {
            return null;
        }

        // Return billing country code
        return $customer->get_billing_country();
    }

    /**
     * Clear all coupons from the current cart.
     *
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::clearCoupons();
     * ```
     */
    public static function clearCoupons(): void
    {
        if (!self::guard() || !WC()->cart) {
            return;
        }

        // Remove all applied coupons
        WC()->cart->remove_coupons();
    }

    /**
     * Get products on sale.
     *
     * @param int $limit Number of products to retrieve.
     * @return \WC_Product[] Array of product objects on sale.
     *
     * @example
     * ```php
     * $saleProducts = WooCommerce::getProductsOnSale(5);
     * ```
     */
    public static function getProductsOnSale(int $limit = 10): array
    {
        if (!self::guard()) {
            return [];
        }

        // Get IDs of products currently on sale
        $productIds = wc_get_product_ids_on_sale();

        if (empty($productIds)) {
            return [];
        }

        // Limit the number of products returned
        $productIds = array_slice($productIds, 0, $limit);

        // Map product IDs to WC_Product objects
        return array_map('wc_get_product', $productIds);
    }

    /**
     * Get total revenue generated from all orders.
     *
     * @return float Total revenue or 0.0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $revenue = WooCommerce::getTotalRevenue();
     * ```
     */
    public static function getTotalRevenue(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Sum order totals for completed or processing orders
        $total = $wpdb->get_var("
        SELECT SUM(meta_value + 0)
        FROM {$wpdb->prefix}postmeta pm
        JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_order_total'
        AND p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
    ");

        return (float) $total;
    }

    /**
     * Get customer lifetime value (CLV) for a specific user.
     *
     * @param int $userId User ID.
     * @return float Total amount spent by the customer or 0.0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $clv = WooCommerce::getCustomerLifetimeValue(123);
     * ```
     */
    public static function getCustomerLifetimeValue(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Sum total order amounts for completed or processing orders by user
        $total_spent = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta_value + 0)
        FROM {$wpdb->prefix}postmeta pm
        JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_order_total'
        AND p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
        AND p.post_author = %d
    ", $userId));

        return (float) $total_spent ?: 0.0;
    }

    /**
     * Calculate product sales velocity (units sold per day since publish).
     *
     * @param int $productId Product ID.
     * @return float Average units sold per day or 0.0 if no sales or WooCommerce inactive.
     *
     * @example
     * ```php
     * $velocity = WooCommerce::getProductSalesVelocity(123);
     * ```
     */
    public static function getProductSalesVelocity(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0.0;
        }

        // Get total sales (units sold)
        $total_sales = (int) $product->get_total_sales();

        // Get product publish date timestamp
        $publish_date = strtotime(get_post_field('post_date', $productId));

        if (!$publish_date) {
            return 0.0;
        }

        // Calculate days since product published
        $days_since = max(1, (time() - $publish_date) / DAY_IN_SECONDS);

        // Calculate average units sold per day
        return round($total_sales / $days_since, 2);
    }

    /**
     * Calculate the percentage of refunded amount for an order.
     *
     * @param int $orderId Order ID.
     * @return float Percentage of refund (0-100) or 0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $refundPercent = WooCommerce::getOrderRefundPercentage(456);
     * ```
     */
    public static function getOrderRefundPercentage(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            return 0.0;
        }

        // Total order amount
        $total = (float) $order->get_total();

        // Total refunded amount
        $refunded = (float) $order->get_total_refunded();

        if ($total <= 0) {
            return 0.0;
        }

        // Calculate refund percentage
        return round(($refunded / $total) * 100, 2);
    }

    /**
     * Calculate gross profit for a product based on cost and sales.
     *
     * Requires '_cost' meta key set for product cost price.
     *
     * @param int $productId Product ID.
     * @return float Gross profit (sales revenue - cost) or 0.0 if WooCommerce inactive or no cost.
     *
     * @example
     * ```php
     * $profit = WooCommerce::getProductGrossProfit(123);
     * ```
     */
    public static function getProductGrossProfit(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $product = wc_get_product($productId);

        if (!$product) {
            return 0.0;
        }

        // Get product cost price from meta (_cost is common in WooCommerce cost plugins)
        $cost = (float) get_post_meta($productId, '_cost', true);

        if ($cost <= 0) {
            return 0.0;
        }

        // Get total units sold
        $units_sold = (int) $product->get_total_sales();

        // Calculate gross profit: (price - cost) * units sold
        $profit_per_unit = max(0, (float) $product->get_price() - $cost);

        return round($profit_per_unit * $units_sold, 2);
    }

    /**
     * Calculate total tax collected for all completed orders.
     *
     * @return float Total tax amount or 0.0 if WooCommerce inactive or no tax collected.
     *
     * @example
     * ```php
     * $totalTax = WooCommerce::getTotalTaxCollected();
     * ```
     */
    public static function getTotalTaxCollected(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Sum tax totals from completed or processing orders
        $total_tax = $wpdb->get_var("
        SELECT SUM(meta_value + 0)
        FROM {$wpdb->prefix}postmeta pm
        JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_order_tax'
        AND p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
    ");

        // Return total tax or 0.0 if null
        return (float) $total_tax ?: 0.0;
    }

    /**
     * Get the most purchased product ID within a date range.
     *
     * @param string|null $startDate Start date (Y-m-d format), null for no limit.
     * @param string|null $endDate End date (Y-m-d format), null for no limit.
     * @return int|null Product ID of the best seller or null if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $topProductId = WooCommerce::getBestSellingProduct('2023-01-01', '2023-12-31');
     * ```
     */
    public static function getBestSellingProduct(?string $startDate = null, ?string $endDate = null): ?int
    {
        if (!self::guard()) {
            return null;
        }

        global $wpdb;

        // Prepare date conditions if provided
        $date_conditions = '';
        $params = [];

        if ($startDate) {
            $date_conditions .= " AND p.post_date >= %s";
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate) {
            $date_conditions .= " AND p.post_date <= %s";
            $params[] = $endDate . ' 23:59:59';
        }

        // SQL to get product ID with highest quantity sold
        $query = "
        SELECT order_item_meta.meta_value AS product_id, SUM(order_item_meta_qty.meta_value) AS total_qty
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta
          ON order_items.order_item_id = order_item_meta.order_item_id AND order_item_meta.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_qty
          ON order_items.order_item_id = order_item_meta_qty.order_item_id AND order_item_meta_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}posts AS p ON order_items.order_id = p.ID
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          {$date_conditions}
        GROUP BY product_id
        ORDER BY total_qty DESC
        LIMIT 1
    ";

        // Prepare and execute SQL query safely
        $result = $wpdb->get_row($wpdb->prepare($query, ...$params));

        // Return product ID or null if none
        return $result ? (int) $result->product_id : null;
    }

    /**
     * Calculate customer retention rate.
     *
     * Retention rate = percentage of customers with more than one completed order.
     *
     * @return float Retention rate percentage (0-100) or 0.0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $retention = WooCommerce::getCustomerRetentionRate();
     * ```
     */
    public static function getCustomerRetentionRate(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Count distinct customers with at least one completed order
        $total_customers = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->prefix}posts
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        if ($total_customers === 0) {
            return 0.0;
        }

        // Count customers with more than one completed order
        $repeat_customers = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM (
            SELECT post_author, COUNT(ID) AS order_count
            FROM {$wpdb->prefix}posts
            WHERE post_type = 'shop_order'
              AND post_status IN ('wc-completed', 'wc-processing')
            GROUP BY post_author
            HAVING order_count > 1
        ) AS sub
    ");

        // Calculate retention rate percentage
        return round(($repeat_customers / $total_customers) * 100, 2);
    }

    /**
     * Calculate average days between customer orders.
     *
     * @param int $userId Customer user ID.
     * @return float Average days between orders or 0.0 if less than 2 orders or WooCommerce inactive.
     *
     * @example
     * ```php
     * $avgDays = WooCommerce::getAverageDaysBetweenOrders(123);
     * ```
     */
    public static function getAverageDaysBetweenOrders(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        $orders = wc_get_orders([
            'customer_id' => $userId,
            'status'      => ['completed', 'processing'],
            'orderby'     => 'date',
            'order'       => 'ASC',
            'limit'       => -1,
            'return'      => 'objects',
        ]);

        // Need at least two orders to calculate interval
        if (count($orders) < 2) {
            return 0.0;
        }

        $total_days = 0;
        $previous_timestamp = null;
        $count_intervals = 0;

        // Loop through orders to calculate intervals
        foreach ($orders as $order) {
            $current_timestamp = strtotime($order->get_date_created());

            if ($previous_timestamp !== null) {
                // Calculate difference in days
                $diff = ($current_timestamp - $previous_timestamp) / DAY_IN_SECONDS;
                $total_days += $diff;
                $count_intervals++;
            }

            $previous_timestamp = $current_timestamp;
        }

        // Calculate average days between orders
        return round($total_days / $count_intervals, 2);
    }

    /**
     * Get the total number of unique customers who made purchases.
     *
     * @return int Number of unique customers or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $uniqueCustomers = WooCommerce::getUniqueCustomerCount();
     * ```
     */
    public static function getUniqueCustomerCount(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Count distinct authors of completed or processing orders
        $count = $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->prefix}posts
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        return (int) $count;
    }

    /**
     * Calculate total revenue for a given product ID.
     *
     * @param int $productId Product ID.
     * @return float Total revenue generated by the product or 0.0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $revenue = WooCommerce::getProductTotalRevenue(45);
     * ```
     */
    public static function getProductTotalRevenue(int $productId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Query to sum line totals for a specific product in completed orders
        $query = "
        SELECT SUM(meta_line_total.meta_value + 0) AS total_revenue
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_line_total
          ON order_items.order_item_id = meta_line_total.order_item_id AND meta_line_total.meta_key = '_line_total'
        JOIN {$wpdb->prefix}posts AS posts
          ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_product_id.meta_value = %d
    ";

        // Prepare and execute the query safely
        $total = $wpdb->get_var($wpdb->prepare($query, $productId));

        // Return total revenue or 0.0 if null
        return (float) $total ?: 0.0;
    }

    /**
     * Get all products purchased by a customer.
     *
     * @param int $userId Customer user ID.
     * @return int[] Array of purchased product IDs.
     *
     * @example
     * ```php
     * $products = WooCommerce::getProductsByCustomer(123);
     * ```
     */
    public static function getProductsByCustomer(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Query product IDs from customer's completed orders
        $query = "
        SELECT DISTINCT meta_product_id.meta_value AS product_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND posts.post_author = %d
    ";

        // Fetch product IDs as integers
        $results = $wpdb->get_col($wpdb->prepare($query, $userId));

        return array_map('intval', $results);
    }

    /**
     * Get monthly sales totals for a given year.
     *
     * @param int $year Year (e.g. 2023).
     * @return array Associative array with keys as month numbers (1-12) and values as total sales.
     *
     * @example
     * ```php
     * $sales = WooCommerce::getMonthlySalesTotals(2023);
     * ```
     */
    public static function getMonthlySalesTotals(int $year): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Initialize all months with 0 sales
        $monthly_sales = array_fill(1, 12, 0.0);

        // Query sales totals grouped by month for completed orders in given year
        $query = "
        SELECT MONTH(posts.post_date) AS month, SUM(meta_total.meta_value + 0) AS total
        FROM {$wpdb->prefix}posts AS posts
        JOIN {$wpdb->prefix}postmeta AS meta_total ON posts.ID = meta_total.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_total.meta_key = '_order_total'
          AND YEAR(posts.post_date) = %d
        GROUP BY month
    ";

        // Fetch results
        $results = $wpdb->get_results($wpdb->prepare($query, $year));

        // Fill monthly sales with query results
        foreach ($results as $row) {
            $monthly_sales[(int) $row->month] = (float) $row->total;
        }

        return $monthly_sales;
    }

    /**
     * Get total number of orders placed by a specific customer.
     *
     * @param int $userId Customer user ID.
     * @return int Total count of orders by the user or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $orderCount = WooCommerce::getCustomerOrderCount(123);
     * ```
     */
    public static function getCustomerOrderCount(int $userId): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Count orders by post_author with status completed or processing
        $query = "
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author = %d
    ";

        // Prepare and execute query safely
        $count = $wpdb->get_var($wpdb->prepare($query, $userId));

        return (int) $count;
    }

    /**
     * Get total number of customers (users who have placed at least one order).
     *
     * @return int Number of customers or 0 if WooCommerce inactive.
     *
     * @example
     * ```php
     * $totalCustomers = WooCommerce::getTotalCustomers();
     * ```
     */
    public static function getTotalCustomers(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query distinct post_author from completed or processing orders
        $query = "
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author > 0
    ";

        $count = $wpdb->get_var($query);

        return (int) $count;
    }

    /**
     * Get total quantity sold for a specific product.
     *
     * @param int $productId Product ID.
     * @return int Total quantity sold or 0 if WooCommerce inactive or no sales.
     *
     * @example
     * ```php
     * $quantitySold = WooCommerce::getProductTotalQuantitySold(45);
     * ```
     */
    public static function getProductTotalQuantitySold(int $productId): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query total quantity sold for the product in completed or processing orders
        $query = "
        SELECT SUM(meta_qty.meta_value + 0) AS total_qty
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
          ON order_items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_product_id.meta_value = %d
    ";

        // Execute prepared query safely
        $totalQty = $wpdb->get_var($wpdb->prepare($query, $productId));

        // Return quantity as integer, or 0 if null
        return (int) $totalQty ?: 0;
    }

    /**
     * Get all coupons used by a specific customer.
     *
     * @param int $userId Customer user ID.
     * @return string[] Array of coupon codes used by the customer.
     *
     * @example
     * ```php
     * $coupons = WooCommerce::getCouponsUsedByCustomer(123);
     * ```
     */
    public static function getCouponsUsedByCustomer(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Query coupon codes used in customer's completed or processing orders
        $query = "
        SELECT DISTINCT meta_coupon.meta_value AS coupon_code
        FROM {$wpdb->prefix}posts AS posts
        JOIN {$wpdb->prefix}postmeta AS meta_customer ON posts.ID = meta_customer.post_id
        JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_coupon ON order_items.order_item_id = meta_coupon.order_item_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_customer.meta_key = '_customer_user'
          AND meta_customer.meta_value = %d
          AND order_items.order_item_type = 'coupon'
          AND meta_coupon.meta_key = 'coupon_code'
    ";

        // Fetch coupon codes
        $results = $wpdb->get_col($wpdb->prepare($query, $userId));

        return $results ?: [];
    }

    /**
     * Get total sales amount for a specific coupon code.
     *
     * @param string $couponCode Coupon code.
     * @return float Total sales amount discounted by this coupon.
     *
     * @example
     * ```php
     * $couponSales = WooCommerce::getCouponTotalDiscount('SUMMER21');
     * ```
     */
    public static function getCouponTotalDiscount(string $couponCode): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Query sum of discounts applied for the coupon in completed or processing orders
        $query = "
        SELECT SUM(meta_discount.meta_value + 0) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_coupon_code
          ON order_items.order_item_id = meta_coupon_code.order_item_id AND meta_coupon_code.meta_key = 'coupon_code'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_discount
          ON order_items.order_item_id = meta_discount.order_item_id AND meta_discount.meta_key = 'discount_amount'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_coupon_code.meta_value = %s
    ";

        // Execute safely
        $totalDiscount = $wpdb->get_var($wpdb->prepare($query, $couponCode));

        return (float) $totalDiscount ?: 0.0;
    }

    /**
     * Check if a customer has purchased a specific product.
     *
     * @param int $userId Customer user ID.
     * @param int $productId Product ID.
     * @return bool True if customer purchased the product, false otherwise.
     *
     * @example
     * ```php
     * $hasPurchased = WooCommerce::hasCustomerPurchasedProduct(123, 45);
     * ```
     */
    public static function hasCustomerPurchasedProduct(int $userId, int $productId): bool
    {
        if (!self::guard()) {
            return false;
        }

        global $wpdb;

        // Query to check if user has an order containing the product
        $query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        JOIN {$wpdb->prefix}postmeta AS meta_customer ON posts.ID = meta_customer.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_product_id.meta_value = %d
          AND meta_customer.meta_key = '_customer_user'
          AND meta_customer.meta_value = %d
    ";

        // Execute query with prepared params
        $count = $wpdb->get_var($wpdb->prepare($query, $productId, $userId));

        return $count > 0;
    }

    /**
     * Get the average time (in seconds) from order placement to completion.
     *
     * @return float Average time in seconds or 0.0 if no orders or WooCommerce inactive.
     *
     * @example
     * ```php
     * $avgCompletionTime = WooCommerce::getAverageOrderCompletionTime();
     * ```
     */
    public static function getAverageOrderCompletionTime(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Calculate average difference between post_date (order placed) and post_modified (order completed) for completed orders
        $query = "
        SELECT AVG(TIMESTAMPDIFF(SECOND, post_date, post_modified)) AS avg_seconds
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_modified > post_date
    ";

        $avgSeconds = $wpdb->get_var($query);

        return (float) $avgSeconds ?: 0.0;
    }

    /**
     * Get all orders for a customer within a specific date range.
     *
     * @param int $userId Customer user ID.
     * @param string $startDate Start date in 'Y-m-d' format.
     * @param string $endDate End date in 'Y-m-d' format.
     * @return \WC_Order[] Array of WC_Order objects.
     *
     * @example
     * ```php
     * $orders = WooCommerce::getCustomerOrdersByDateRange(123, '2024-01-01', '2024-06-30');
     * ```
     */
    public static function getCustomerOrdersByDateRange(int $userId, string $startDate, string $endDate): array
    {
        if (!self::guard()) {
            return [];
        }

        // Prepare WP_User query arguments
        $args = [
            'customer_id' => $userId,
            'date_created' => $startDate . '...' . $endDate,
            'limit' => -1, // No limit, get all
            'return' => 'objects',
        ];

        // Use WooCommerce order query
        $orders = wc_get_orders($args);

        return $orders;
    }

    /**
     * Get all products purchased by a customer.
     *
     * @param int $userId Customer user ID.
     * @return \WC_Product[] Array of WC_Product objects purchased by the customer.
     *
     * @example
     * ```php
     * $purchasedProducts = WooCommerce::getProductsPurchasedByCustomer(123);
     * ```
     */
    public static function getProductsPurchasedByCustomer(int $userId): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Query product IDs purchased by the user in completed or processing orders
        $query = "
        SELECT DISTINCT meta_product_id.meta_value AS product_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        JOIN {$wpdb->prefix}postmeta AS meta_customer ON posts.ID = meta_customer.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_customer.meta_key = '_customer_user'
          AND meta_customer.meta_value = %d
    ";

        $productIds = $wpdb->get_col($wpdb->prepare($query, $userId));

        $products = [];

        // Convert IDs to WC_Product objects
        foreach ($productIds as $pid) {
            $product = wc_get_product($pid);
            if ($product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * Get total discounts applied on an order.
     *
     * @param int $orderId WooCommerce order ID.
     * @return float Total discount amount applied to the order.
     *
     * @example
     * ```php
     * $discountTotal = WooCommerce::getOrderDiscountTotal(789);
     * ```
     */
    public static function getOrderDiscountTotal(int $orderId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        // Get order object
        $order = wc_get_order($orderId);

        // Return 0 if order not found
        if (!$order) {
            return 0.0;
        }

        // Return total discount amount (includes coupons and other discounts)
        return (float) $order->get_total_discount();
    }

    /**
     * Get total number of products sold across all orders.
     *
     * @return int Total quantity of all products sold.
     *
     * @example
     * ```php
     * $totalSold = WooCommerce::getTotalProductsSold();
     * ```
     */
    public static function getTotalProductsSold(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query sum of all product quantities sold in completed and processing orders
        $query = "
        SELECT SUM(meta_qty.meta_value + 0) AS total_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
          ON order_items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
    ";

        // Get total quantity sold or 0 if none found
        $totalSold = $wpdb->get_var($query);

        return (int) $totalSold ?: 0;
    }

    /**
     * Get the number of distinct products purchased by a customer.
     *
     * @param int $userId Customer user ID.
     * @return int Number of unique products purchased.
     *
     * @example
     * ```php
     * $uniqueProducts = WooCommerce::getCustomerUniqueProductsCount(123);
     * ```
     */
    public static function getCustomerUniqueProductsCount(int $userId): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query distinct count of products bought by user in completed or processing orders
        $query = "
        SELECT COUNT(DISTINCT meta_product_id.meta_value) AS unique_products
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        JOIN {$wpdb->prefix}postmeta AS meta_customer ON posts.ID = meta_customer.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_customer.meta_key = '_customer_user'
          AND meta_customer.meta_value = %d
    ";

        // Get count or 0 if none found
        $count = $wpdb->get_var($wpdb->prepare($query, $userId));

        return (int) $count ?: 0;
    }

    /**
     * Get the total refunded quantity for a product.
     *
     * @param int $productId Product ID.
     * @return int Total refunded quantity of the product.
     *
     * @example
     * ```php
     * $refundedQty = WooCommerce::getProductTotalRefundedQuantity(45);
     * ```
     */
    public static function getProductTotalRefundedQuantity(int $productId): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query sum of refunded quantities for the product in completed or refunded orders
        $query = "
        SELECT SUM(meta_qty.meta_value + 0) AS refunded_qty
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
          ON order_items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-refunded', 'wc-completed')
          AND meta_product_id.meta_value = %d
          AND posts.ID IN (
            SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'refund'
          )
    ";

        $refundedQty = $wpdb->get_var($wpdb->prepare($query, $productId));

        return (int) $refundedQty ?: 0;
    }

    /**
     * Get total number of coupons used across all orders.
     *
     * @return int Total coupon usage count.
     *
     * @example
     * ```php
     * $couponUsage = WooCommerce::getTotalCouponUsage();
     * ```
     */
    public static function getTotalCouponUsage(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query count of all coupon usage in completed orders
        $query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND order_items.order_item_type = 'coupon'
    ";

        $count = $wpdb->get_var($query);

        return (int) $count ?: 0;
    }

    /**
     * Get total revenue per customer.
     *
     * @param int $userId Customer user ID.
     * @return float Total revenue generated by the customer.
     *
     * @example
     * ```php
     * $customerRevenue = WooCommerce::getCustomerTotalRevenue(123);
     * ```
     */
    public static function getCustomerTotalRevenue(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Query sum of order totals for a customer from completed orders
        $query = "
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS total_revenue
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = posts.ID AND pm.meta_key = '_customer_user' AND pm.meta_value = %d
          )
          AND meta_total.meta_key = '_order_total'
    ";

        $totalRevenue = $wpdb->get_var($wpdb->prepare($query, $userId));

        return (float) $totalRevenue ?: 0.0;
    }

    /**
     * Get orders count per product.
     *
     * @param int $productId Product ID.
     * @return int Number of orders containing the product.
     *
     * @example
     * ```php
     * $ordersCount = WooCommerce::getProductOrdersCount(45);
     * ```
     */
    public static function getProductOrdersCount(int $productId): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Query count distinct orders containing the product in completed or processing orders
        $query = "
        SELECT COUNT(DISTINCT order_items.order_id) AS orders_count
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
          ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_product_id.meta_value = %d
    ";

        $ordersCount = $wpdb->get_var($wpdb->prepare($query, $productId));

        return (int) $ordersCount ?: 0;
    }

    /**
     * Calculate the repeat purchase rate: the percentage of customers who placed more than one order.
     *
     * @return float Repeat purchase rate percentage (0‑100) or 0.0 if no customers or WooCommerce inactive.
     *
     * @example
     * ```php
     * $repeatRate = WooCommerce::getRepeatPurchaseRate();
     * ```
     */
    public static function getRepeatPurchaseRate(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Total customers with at least one completed or processing order
        $totalCustomers = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author > 0
    ");

        if ($totalCustomers === 0) {
            return 0.0;
        }

        // Customers with more than one such order
        $repeatCustomers = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM (
            SELECT post_author, COUNT(ID) AS orders
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status IN ('wc-completed', 'wc-processing')
              AND post_author > 0
            GROUP BY post_author
            HAVING orders > 1
        ) AS sub
    ");

        return round(($repeatCustomers / $totalCustomers) * 100, 2);
    }

    /**
     * Calculate average order frequency per customer (orders per month).
     *
     * @return float Average orders per month or 0.0 if not available.
     *
     * @example
     * ```php
     * $frequency = WooCommerce::getAverageOrderFrequency();
     * ```
     */
    public static function getAverageOrderFrequency(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Total number of orders
        $totalOrders = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        // Earliest and latest completed/processing order dates
        $minDate = $wpdb->get_var("
        SELECT MIN(post_date) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");
        $maxDate = $wpdb->get_var("
        SELECT MAX(post_date) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        if (!$minDate || !$maxDate) {
            return 0.0;
        }

        $months = max(1, (strtotime($maxDate) - strtotime($minDate)) / (30 * DAY_IN_SECONDS));

        return round($totalOrders / $months, 2);
    }

    /**
     * Calculate average order value for a specific customer.
     *
     * @param int $userId Customer user ID.
     * @return float Customer's average order value or 0.0 if no orders exist.
     *
     * @example
     * ```php
     * $avgAOV = WooCommerce::getCustomerAverageOrderValue(123);
     * ```
     */
    public static function getCustomerAverageOrderValue(int $userId): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        // Sum of all order totals and count for the customer
        $result = $wpdb->get_row($wpdb->prepare("
        SELECT
            SUM(CAST(meta_total.meta_value AS DECIMAL(10,2))) as total_spent,
            COUNT(*) as order_count
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} meta_total
          ON p.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND EXISTS (
              SELECT 1 FROM {$wpdb->postmeta} pm2
              WHERE pm2.post_id = p.ID AND pm2.meta_key = '_customer_user' AND pm2.meta_value = %d
          )
    ", $userId));

        if (!$result || !$result->order_count) {
            return 0.0;
        }

        return round((float) $result->total_spent / (int) $result->order_count, 2);
    }

    /**
     * Get monthly customer acquisition count for a given year.
     *
     * @param int $year Full year (e.g., 2024).
     * @return array Associative array [month_num => new_customers_count].
     *
     * @example
     * ```php
     * $acquisitions = WooCommerce::getMonthlyCustomerAcquisitions(2024);
     * ```
     */
    public static function getMonthlyCustomerAcquisitions(int $year): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Initialize results
        $monthly = array_fill(1, 12, 0);

        // Query number of distinct first-time customer orderers per month
        $query = $wpdb->prepare("
        SELECT MONTH(p.post_date) AS month, COUNT(DISTINCT p.post_author) AS new_customers
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND YEAR(p.post_date) = %d
        GROUP BY month
    ", $year);

        $results = $wpdb->get_results($query);

        foreach ($results as $row) {
            $monthly[(int)$row->month] = (int)$row->new_customers;
        }

        return $monthly;
    }

    /**
     * Get best-selling products within a date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to End date (Y-m-d).
     * @param int $limit Max number of products to return.
     * @return array List of product IDs ordered by quantity sold.
     *
     * @example
     * ```php
     * $topProducts = WooCommerce::getBestSellingProducts('2025-01-01', '2025-08-31', 10);
     * ```
     */
    public static function getBestSellingProducts(string $from, string $to, int $limit = 10): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $query = $wpdb->prepare("
        SELECT meta_product_id.meta_value AS product_id, SUM(CAST(meta_qty.meta_value AS UNSIGNED)) AS total_qty
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
            ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
            ON order_items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND posts.post_date BETWEEN %s AND %s
        GROUP BY product_id
        ORDER BY total_qty DESC
        LIMIT %d
    ", $from, $to, $limit);

        $results = $wpdb->get_results($query);

        $output = [];
        foreach ($results as $row) {
            $output[(int)$row->product_id] = (int)$row->total_qty;
        }

        return $output;
    }

    /**
     * Get customer’s most frequently purchased product.
     *
     * @param int $userId WooCommerce customer user ID.
     * @return int|null Product ID or null if no purchases found.
     *
     * @example
     * ```php
     * $topProduct = WooCommerce::getCustomerTopProduct(456);
     * ```
     */
    public static function getCustomerTopProduct(int $userId): ?int
    {
        if (!self::guard()) {
            return null;
        }

        global $wpdb;

        $query = $wpdb->prepare("
        SELECT meta_product_id.meta_value AS product_id, SUM(CAST(meta_qty.meta_value AS UNSIGNED)) AS total_qty
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product_id
            ON order_items.order_item_id = meta_product_id.order_item_id AND meta_product_id.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
            ON order_items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        JOIN {$wpdb->postmeta} AS meta_user ON posts.ID = meta_user.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
          AND meta_user.meta_key = '_customer_user'
          AND meta_user.meta_value = %d
        GROUP BY product_id
        ORDER BY total_qty DESC
        LIMIT 1
    ", $userId);

        $productId = $wpdb->get_var($query);

        return $productId ? (int) $productId : null;
    }

    /**
     * Get abandoned cart estimate (guessed from failed or pending orders).
     *
     * @return int Number of potentially abandoned carts.
     *
     * @example
     * ```php
     * $abandonedCarts = WooCommerce::getAbandonedCartEstimate();
     * ```
     */
    public static function getAbandonedCartEstimate(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        // Count orders that never completed or processed
        $count = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed', 'wc-cancelled')
    ");

        return (int) $count;
    }

    /**
     * Get average number of products per order.
     *
     * @return float Average product count per order or 0.0 if no orders.
     *
     * @example
     * ```php
     * $avgProducts = WooCommerce::getAverageProductsPerOrder();
     * ```
     */
    public static function getAverageProductsPerOrder(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        $totalQty = (int) $wpdb->get_var("
        SELECT SUM(CAST(meta_qty.meta_value AS UNSIGNED))
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
        JOIN {$wpdb->prefix}woocommerce_order_items AS items
            ON meta_qty.order_item_id = items.order_item_id
        JOIN {$wpdb->posts} AS posts ON items.order_id = posts.ID
        WHERE meta_qty.meta_key = '_qty'
          AND posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
    ");

        $orderCount = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        return $orderCount > 0 ? round($totalQty / $orderCount, 2) : 0.0;
    }

    /**
     * Get total number of refunded orders.
     *
     * @return int Refunded order count.
     *
     * @example
     * ```php
     * $refundCount = WooCommerce::getTotalRefundedOrders();
     * ```
     */
    public static function getTotalRefundedOrders(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        $count = $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-refunded'
    ");

        return (int) $count;
    }

    /**
     * Get top countries by order volume.
     *
     * @param int $limit Number of top countries to return.
     * @return array Associative array [country_code => order_count].
     *
     * @example
     * ```php
     * $topCountries = WooCommerce::getTopOrderCountries(5);
     * ```
     */
    public static function getTopOrderCountries(int $limit = 5): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT billing_country.meta_value AS country, COUNT(*) AS total
        FROM {$wpdb->prefix}postmeta AS billing_country
        JOIN {$wpdb->posts} AS posts ON billing_country.post_id = posts.ID
        WHERE billing_country.meta_key = '_billing_country'
          AND posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-completed', 'wc-processing')
        GROUP BY billing_country.meta_value
        ORDER BY total DESC
        LIMIT %d
    ", $limit));

        $countries = [];
        foreach ($results as $row) {
            $countries[$row->country] = (int) $row->total;
        }

        return $countries;
    }

    /**
     * Get number of new customers acquired in the current month.
     *
     * @return int Number of unique new customers this month.
     *
     * @example
     * ```php
     * $newCustomers = WooCommerce::getCurrentMonthNewCustomers();
     * ```
     */
    public static function getCurrentMonthNewCustomers(): int
    {
        if (!self::guard()) {
            return 0;
        }

        global $wpdb;

        $currentMonth = date('Y-m-01');

        $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_date >= %s
          AND post_author > 0
    ", $currentMonth));

        return (int) $count;
    }

    /**
     * Get products that are frequently bought together with a given product.
     *
     * @param int $productId Product ID to analyze.
     * @return array Array of related product IDs (frequently bought with this one).
     *
     * @example
     * ```php
     * $related = WooCommerce::getFrequentlyBoughtTogether(321);
     * ```
     */
    public static function getFrequentlyBoughtTogether(int $productId): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        // Get all orders containing the product
        $orderIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
          ON items.order_item_id = meta_product.order_item_id
        WHERE meta_product.meta_key = '_product_id'
          AND meta_product.meta_value = %d
    ", $productId));

        if (empty($orderIds)) {
            return [];
        }

        // Find other products in those same orders
        $placeholders = implode(',', array_fill(0, count($orderIds), '%d'));

        $query = "
        SELECT meta.meta_value AS related_product_id, COUNT(*) AS frequency
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta
          ON items.order_item_id = meta.order_item_id
        WHERE meta.meta_key = '_product_id'
          AND items.order_id IN ($placeholders)
          AND meta.meta_value != %d
        GROUP BY meta.meta_value
        ORDER BY frequency DESC
        LIMIT 10
    ";

        $values = array_merge($orderIds, [$productId]);
        $results = $wpdb->get_results($wpdb->prepare($query, ...$values));

        $related = [];
        foreach ($results as $row) {
            $related[(int) $row->related_product_id] = (int) $row->frequency;
        }

        return $related;
    }

    /**
     * Get coupon usage stats.
     *
     * @param string $couponCode Coupon code to analyze.
     * @return array Stats including total uses, discount total, and unique users.
     *
     * @example
     * ```php
     * $couponStats = WooCommerce::getCouponStats('SUMMER2025');
     * ```
     */
    public static function getCouponStats(string $couponCode): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $results = $wpdb->get_row($wpdb->prepare("
        SELECT
            COUNT(DISTINCT o.ID) AS order_count,
            SUM(CAST(pm.meta_value AS DECIMAL(10,2))) AS total_discount,
            COUNT(DISTINCT o.post_author) AS unique_users
        FROM {$wpdb->posts} AS o
        JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON oi.order_id = o.ID
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS pm ON oi.order_item_id = pm.order_item_id
        WHERE o.post_type = 'shop_order'
          AND o.post_status IN ('wc-completed', 'wc-processing')
          AND oi.order_item_type = 'coupon'
          AND oi.order_item_name = %s
          AND pm.meta_key = 'discount_amount'
    ", $couponCode));

        return [
            'orders'        => (int) ($results->order_count ?? 0),
            'total_discount'=> (float) ($results->total_discount ?? 0),
            'unique_users'  => (int) ($results->unique_users ?? 0),
        ];
    }

    /**
     * Get most refunded products.
     *
     * @param int $limit Max number of products to return.
     * @return array Array of product IDs with refund count.
     *
     * @example
     * ```php
     * $topRefunds = WooCommerce::getMostRefundedProducts(5);
     * ```
     */
    public static function getMostRefundedProducts(int $limit = 5): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_product.meta_value AS product_id, COUNT(*) AS refund_count
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
          ON items.order_item_id = meta_product.order_item_id
        JOIN {$wpdb->posts} AS posts ON items.order_id = posts.ID
        WHERE meta_product.meta_key = '_product_id'
          AND posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-refunded'
        GROUP BY product_id
        ORDER BY refund_count DESC
        LIMIT %d
    ", $limit));

        $products = [];
        foreach ($results as $row) {
            $products[(int) $row->product_id] = (int) $row->refund_count;
        }

        return $products;
    }

    /**
     * Get gross revenue within a specific date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to End date (Y-m-d).
     * @return float Total gross revenue from completed/processing orders.
     *
     * @example
     * ```php
     * $revenue = WooCommerce::getGrossRevenue('2025-01-01', '2025-08-31');
     * ```
     */
    public static function getGrossRevenue(string $from, string $to): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_total'
          AND p.post_date BETWEEN %s AND %s
    ", $from, $to));

        return (float) $total ?: 0.0;
    }

    /**
     * Get average shipping cost per order.
     *
     * @return float Average shipping cost or 0.0 if not applicable.
     *
     * @example
     * ```php
     * $avgShipping = WooCommerce::getAverageShippingCost();
     * ```
     */
    public static function getAverageShippingCost(): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        $totalShipping = (float) $wpdb->get_var("
        SELECT SUM(CAST(meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_order_shipping'
    ");

        $orderCount = (int) $wpdb->get_var("
        SELECT COUNT(ID)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
    ");

        return $orderCount > 0 ? round($totalShipping / $orderCount, 2) : 0.0;
    }

    /**
     * Get top coupon codes by number of uses.
     *
     * @param int $limit Maximum number of coupons to return.
     * @return array List of coupon codes with usage counts.
     *
     * @example
     * ```php
     * $topCoupons = WooCommerce::getTopCoupons(5);
     * ```
     */
    public static function getTopCoupons(int $limit = 5): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT order_item_name AS coupon_code, COUNT(*) AS usage_count
        FROM {$wpdb->prefix}woocommerce_order_items
        WHERE order_item_type = 'coupon'
        GROUP BY order_item_name
        ORDER BY usage_count DESC
        LIMIT %d
    ", $limit));

        $coupons = [];
        foreach ($results as $row) {
            $coupons[$row->coupon_code] = (int) $row->usage_count;
        }

        return $coupons;
    }

    /**
     * Calculate the total tax collected in a given time range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to End date (Y-m-d).
     * @return float Total tax collected.
     *
     * @example
     * ```php
     * $taxTotal = WooCommerce::getTaxCollected('2025-01-01', '2025-08-31');
     * ```
     */
    public static function getTaxCollected(string $from, string $to): float
    {
        if (!self::guard()) {
            return 0.0;
        }

        global $wpdb;

        $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_tax'
          AND p.post_date BETWEEN %s AND %s
    ", $from, $to));

        return (float) $total ?: 0.0;
    }

    /**
     * Get list of inactive customers (no orders in X months).
     *
     * @param int $months Number of months to check for inactivity.
     * @return array List of inactive user IDs.
     *
     * @example
     * ```php
     * $inactiveUsers = WooCommerce::getInactiveCustomers(6);
     * ```
     */
    public static function getInactiveCustomers(int $months = 6): array
    {
        if (!self::guard()) {
            return [];
        }

        global $wpdb;

        $cutoffDate = date('Y-m-d', strtotime("-{$months} months"));

        // Get users with last order before the cutoff
        $results = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} AS pm
        JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
        WHERE pm.meta_key = '_customer_user'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date < %s
          AND pm.meta_value > 0
          AND pm.meta_value NOT IN (
              SELECT DISTINCT pm2.meta_value
              FROM {$wpdb->postmeta} AS pm2
              JOIN {$wpdb->posts} AS p2 ON p2.ID = pm2.post_id
              WHERE pm2.meta_key = '_customer_user'
                AND p2.post_type = 'shop_order'
                AND p2.post_status IN ('wc-completed', 'wc-processing')
                AND p2.post_date >= %s
          )
    ", $cutoffDate, $cutoffDate));

        return array_map('intval', $results);
    }

    /**
     * Get average order value (AOV) for a specific date range.
     *
     * @param string $from Start date in 'Y-m-d' format.
     * @param string $to End date in 'Y-m-d' format.
     * @return float Average order value or 0.0 if none or WooCommerce inactive.
     *
     * @example
     * ```php
     * $aov = WooCommerce::getAverageOrderValueByDate('2025-01-01', '2025-08-31');
     * ```
     */
    public static function getAverageOrderValueByDate(string $from, string $to): float
    {
        if (!self::guard()) return 0.0;
        global $wpdb;

        $avg = $wpdb->get_var($wpdb->prepare("
        SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_total'
          AND p.post_date BETWEEN %s AND %s
    ", $from, $to));

        return (float) ($avg ?: 0.0);
    }

    /**
     * Calculate revenue per customer (average spend per customer).
     *
     * @return float Average revenue per unique customer, or 0.0 if none.
     *
     * @example
     * ```php
     * $revPerCust = WooCommerce::getAverageRevenuePerCustomer();
     * ```
     */
    public static function getAverageRevenuePerCustomer(): float
    {
        if (!self::guard()) return 0.0;
        global $wpdb;

        $totalRevenue = (float) $wpdb->get_var("
        SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_total'
    ");
        $uniqueCustomers = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author > 0
    ");

        return $uniqueCustomers > 0 ? round($totalRevenue / $uniqueCustomers, 2) : 0.0;
    }

    /**
     * Get the churn rate: percentage of customers who made their first purchase before a cutoff and haven't purchased since.
     *
     * @param int $months Months since last purchase to count as churned.
     * @return float Churn rate percentage (0–100) or 0.0 if not applicable.
     *
     * @example
     * ```php
     * $churn = WooCommerce::getCustomerChurnRate(6);
     * ```
     */
    public static function getCustomerChurnRate(int $months = 6): float
    {
        if (!self::guard()) return 0.0;
        global $wpdb;

        $cutoff = date('Y-m-d', strtotime("-{$months} months"));

        $total = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM (
            SELECT post_author, MIN(post_date) AS first_order, MAX(post_date) AS last_order
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing') AND post_author > 0
            GROUP BY post_author
        ) AS customer_orders
        WHERE first_order <= %s
    ", $cutoff);

        $churned = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM (
            SELECT post_author, MAX(post_date) AS last_order
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order' AND post_status IN ('wc-completed', 'wc-processing') AND post_author > 0
            GROUP BY post_author
        ) AS customer_last
        WHERE last_order <= %s
    ", $cutoff);

        return $total > 0 ? round(($churned / $total) * 100, 2) : 0.0;
    }

    /**
     * Get total number of refunded orders in a date range.
     *
     * @param string $from Start date 'Y-m-d'.
     * @param string $to End date 'Y-m-d'.
     * @return int Count of refunded orders in timeframe.
     *
     * @example
     * ```php
     * $refunds = WooCommerce::getRefundedOrdersCount('2025-01-01', '2025-08-31');
     * ```
     */
    public static function getRefundedOrdersCount(string $from, string $to): int
    {
        if (!self::guard()) return 0;
        global $wpdb;

        $count = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-refunded'
          AND post_date BETWEEN %s AND %s
    ", $from, $to));

        return $count;
    }

    /**
     * Get net margin: (revenue – cost) for a product.
     *
     * Requires '_cost' meta key storing cost price.
     *
     * @param int $productId Product ID.
     * @return float Net profit or 0.0 if cost not set or WooCommerce inactive.
     *
     * @example
     * ```php
     * $margin = WooCommerce::getProductNetMargin(123);
     * ```
     */
    public static function getProductNetMargin(int $productId): float
    {
        if (!self::guard()) return 0.0;

        $revenue = self::getProductTotalRevenue($productId);
        $costPerUnit = (float) get_post_meta($productId, '_cost', true);
        $unitsSold = (int) get_post_meta($productId, 'total_sales', true);

        if ($costPerUnit <= 0 || $unitsSold <= 0) return 0.0;

        $profitPerUnit = (float) wc_get_product($productId)->get_price() - $costPerUnit;
        return round($profitPerUnit * $unitsSold, 2);
    }

    /**
     * Get products with the highest refund ratio.
     *
     * Calculates ratio: (refunded units / sold units), sorted by highest ratio.
     *
     * @param int $limit Max number of products to return.
     * @return array Array of product IDs with refund ratio.
     *
     * @example
     * ```php
     * $refundRatios = WooCommerce::getTopRefundRatioProducts(5);
     * ```
     */
    public static function getTopRefundRatioProducts(int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Get refunded units per product
        $refunded = $wpdb->get_results("
        SELECT meta.meta_value AS product_id, COUNT(*) AS refunded_count
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        JOIN {$wpdb->posts} AS p ON items.order_id = p.ID
        WHERE meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status = 'wc-refunded'
        GROUP BY meta.meta_value
    ");

        $ratios = [];

        foreach ($refunded as $row) {
            $productId = (int) $row->product_id;
            $refundedCount = (int) $row->refunded_count;
            $sold = (int) get_post_meta($productId, 'total_sales', true);

            if ($sold > 0) {
                $ratios[$productId] = round($refundedCount / $sold, 2);
            }
        }

        // Sort by ratio DESC
        arsort($ratios);

        return array_slice($ratios, 0, $limit, true);
    }

    /**
     * Get top revenue-generating customers.
     *
     * @param int $limit Number of customers to return.
     * @return array Associative array of user ID => total spent.
     *
     * @example
     * ```php
     * $topCustomers = WooCommerce::getTopRevenueCustomers(10);
     * ```
     */
    public static function getTopRevenueCustomers(int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_author AS user_id, SUM(CAST(pm.meta_value AS DECIMAL(10,2))) AS total_spent
        FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_total'
          AND p.post_author > 0
        GROUP BY post_author
        ORDER BY total_spent DESC
        LIMIT %d
    ", $limit));

        $top = [];
        foreach ($results as $row) {
            $top[(int) $row->user_id] = (float) $row->total_spent;
        }

        return $top;
    }

    /**
     * Get customers with no orders at all (registered users with no purchases).
     *
     * @return int[] Array of user IDs with no orders.
     *
     * @example
     * ```php
     * $noOrderUsers = WooCommerce::getUsersWithNoOrders();
     * ```
     */
    public static function getUsersWithNoOrders(): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $userIds = $wpdb->get_col("
        SELECT ID
        FROM {$wpdb->users}
        WHERE ID NOT IN (
            SELECT DISTINCT post_author
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_author > 0
        )
    ");

        return array_map('intval', $userIds);
    }

    /**
     * Get percentage of returning customers.
     *
     * @return float Percentage value between 0 and 100.
     *
     * @example
     * ```php
     * $returningRate = WooCommerce::getReturningCustomerRate();
     * ```
     */
    public static function getReturningCustomerRate(): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $returning = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM (
            SELECT post_author
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status IN ('wc-completed', 'wc-processing')
              AND post_author > 0
            GROUP BY post_author
            HAVING COUNT(*) > 1
        ) AS multiple_orders
    ");

        $total = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author > 0
    ");

        return $total > 0 ? round(($returning / $total) * 100, 2) : 0.0;
    }

    /**
     * Get abandoned cart estimate (based on created but unpaid orders).
     *
     * Note: Only works if unpaid orders are retained in the database.
     *
     * @param int $days Lookback days to consider.
     * @return int Estimated count of abandoned carts.
     *
     * @example
     * ```php
     * $abandoned = WooCommerce::getAbandonedCartCount(7);
     * ```
     */
    public static function getAbandonedCartCount(int $days = 7): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-pending'
          AND post_date >= %s
    ", $since));

        return (int) $count;
    }

    /**
     * Estimate future revenue using simple linear projection based on last N days.
     *
     * @param int $days Number of past days to use for projection.
     * @param int $futureDays Number of future days to forecast.
     * @return float Estimated future revenue.
     *
     * @example
     * ```php
     * $forecast = WooCommerce::forecastRevenue(30, 7);
     * ```
     */
    public static function forecastRevenue(int $days = 30, int $futureDays = 7): float
    {
        if (!self::guard()) return 0.0;
        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND pm.meta_key = '_order_total'
          AND p.post_date >= %s
    ", $since));

        $dailyAvg = $total && $days > 0 ? ((float) $total) / $days : 0.0;

        return round($dailyAvg * $futureDays, 2);
    }

    /**
     * Get total number of upsell conversions (upsell products actually purchased).
     *
     * @return int Count of upsell product orders.
     *
     * @example
     * ```php
     * $upsellCount = WooCommerce::getUpsellConversions();
     * ```
     */
    public static function getUpsellConversions(): int
    {
        if (!self::guard()) return 0;

        $upsellIds = [];

        $products = wc_get_products(['limit' => -1]);

        foreach ($products as $product) {
            $upsells = $product->get_upsell_ids();
            $upsellIds = array_merge($upsellIds, $upsells);
        }

        $upsellIds = array_unique($upsellIds);

        if (empty($upsellIds)) return 0;

        global $wpdb;

        $placeholders = implode(',', array_fill(0, count($upsellIds), '%d'));

        $query = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_itemmeta
        WHERE meta_key = '_product_id'
          AND meta_value IN ($placeholders)
    ";

        $count = $wpdb->get_var($wpdb->prepare($query, ...$upsellIds));

        return (int) $count;
    }

    /**
     * Get price elasticity estimate based on price changes and sales volume.
     *
     * Assumes price and total_sales are tracked over time.
     *
     * @param int $productId WooCommerce product ID.
     * @return float Elasticity coefficient or 0.0 if not computable.
     *
     * @example
     * ```php
     * $elasticity = WooCommerce::getProductPriceElasticity(123);
     * ```
     */
    public static function getProductPriceElasticity(int $productId): float
    {
        if (!self::guard()) return 0.0;

        $product = wc_get_product($productId);

        if (!$product || !$product->get_regular_price()) return 0.0;

        $price = (float) $product->get_regular_price();
        $sales = (int) get_post_meta($productId, 'total_sales', true);

        $prevPrice = (float) get_post_meta($productId, '_price_prev', true);
        $prevSales = (int) get_post_meta($productId, '_sales_prev', true);

        if ($prevPrice == 0 || $prevSales == 0 || $price == $prevPrice) {
            return 0.0;
        }

        // Elasticity formula: %ΔQ / %ΔP
        $percentChangeSales = ($sales - $prevSales) / $prevSales;
        $percentChangePrice = ($price - $prevPrice) / $prevPrice;

        return round($percentChangeSales / $percentChangePrice, 3);
    }

    /**
     * Get the top N customers ranked by total revenue.
     *
     * @param int $limit Number of top customers to return.
     * @return array List of user data with total revenue.
     *
     * @example
     * ```php
     * $topCustomers = WooCommerce::getTopCustomersByRevenue(10);
     * ```
     */
    public static function getTopCustomersByRevenue(int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Query to sum order totals grouped by customer/user ID.
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            postmeta.meta_value AS user_id,
            SUM(meta2.meta_value) AS total_spent
        FROM {$wpdb->posts} AS posts
        INNER JOIN {$wpdb->postmeta} AS postmeta
            ON posts.ID = postmeta.post_id AND postmeta.meta_key = '_customer_user'
        INNER JOIN {$wpdb->postmeta} AS meta2
            ON posts.ID = meta2.post_id AND meta2.meta_key = '_order_total'
        WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed', 'wc-processing')
        GROUP BY user_id
        ORDER BY total_spent DESC
        LIMIT %d
    ", $limit));

        $customers = [];

        foreach ($results as $row) {
            $user = get_userdata((int) $row->user_id);
            if (!$user) continue;

            $customers[] = [
                'user_id'      => (int) $row->user_id,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'total_spent'  => round((float) $row->total_spent, 2),
            ];
        }

        return $customers;
    }

    /**
     * Identify potentially fraudulent orders using heuristic checks.
     *
     * @return array List of suspicious order IDs.
     *
     * @example
     * ```php
     * $fraudOrders = WooCommerce::detectFraudulentOrders();
     * ```
     */
    public static function detectFraudulentOrders(): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Get last 100 completed or processing orders
        $orders = wc_get_orders([
            'limit'        => 100,
            'status'       => ['completed', 'processing'],
            'orderby'      => 'date',
            'order'        => 'DESC',
            'return'       => 'ids',
        ]);

        $suspicious = [];

        foreach ($orders as $orderId) {
            $order = wc_get_order($orderId);

            // Heuristic 1: Order total exceeds a high threshold
            $highValue = $order->get_total() > 1000;

            // Heuristic 2: Billing and shipping countries do not match
            $mismatchedCountries = $order->get_billing_country() !== $order->get_shipping_country();

            // Heuristic 3: Order placed within 2 minutes of account creation
            $user = $order->get_user();
            $createdRecently = false;

            if ($user && $user->user_registered) {
                $registered = strtotime($user->user_registered);
                $createdRecently = (time() - $registered) <= 120;
            }

            // Flag order if at least 2 out of 3 heuristics are true
            $flags = [$highValue, $mismatchedCountries, $createdRecently];
            if (array_sum($flags) >= 2) {
                $suspicious[] = $orderId;
            }
        }

        return $suspicious;
    }

    /**
     * Estimate Customer Lifetime Value (CLV).
     *
     * @param int $userId WordPress user ID.
     * @return float CLV in currency units.
     *
     * @example
     * ```php
     * $clv = WooCommerce::estimateCustomerLifetimeValue(5);
     * ```
     */
    public static function estimateCustomerLifetimeValue(int $userId): float
    {
        if (!self::guard() || $userId <= 0) return 0.0;

        $orders = wc_get_orders([
            'customer_id' => $userId,
            'status'      => ['completed', 'processing'],
            'limit'       => -1,
        ]);

        if (empty($orders)) return 0.0;

        $totalRevenue = 0.0;
        $firstOrder = null;
        $lastOrder = null;

        foreach ($orders as $order) {
            $totalRevenue += $order->get_total();

            $dateCreated = $order->get_date_created();
            if (!$firstOrder || $dateCreated < $firstOrder) {
                $firstOrder = $dateCreated;
            }

            if (!$lastOrder || $dateCreated > $lastOrder) {
                $lastOrder = $dateCreated;
            }
        }

        // Time span in days between first and last purchase
        $daysActive = ($lastOrder->getTimestamp() - $firstOrder->getTimestamp()) / 86400;
        $daysActive = max($daysActive, 1); // Prevent division by zero

        $averageOrderValue = $totalRevenue / count($orders);
        $ordersPerDay = count($orders) / $daysActive;

        // CLV formula: AOV × purchase frequency × lifespan (e.g., 365 days)
        return round($averageOrderValue * $ordersPerDay * 365, 2);
    }

    /**
     * Calculate the store conversion rate from visits to completed orders.
     *
     * @param int $visits Total number of visits during the same time.
     * @param int $days Time window in days.
     * @return float Conversion rate as a percentage.
     *
     * @example
     * ```php
     * $conversion = WooCommerce::calculateConversionRate(10000, 30);
     * ```
     */
    public static function calculateConversionRate(int $visits, int $days = 30): float
    {
        if (!self::guard() || $visits <= 0) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Count completed orders in the given period
        $orderCount = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if (!$orderCount) return 0.0;

        // Conversion = (orders / visits) * 100
        return round(($orderCount / $visits) * 100, 2);
    }

    /**
     * Predict stock depletion date based on average daily sales.
     *
     * @param int $productId WooCommerce product ID.
     * @param int $days Number of past days to analyze.
     * @return string|null Estimated date (Y-m-d) or null if not enough data.
     *
     * @example
     * ```php
     * $depletion = WooCommerce::predictStockDepletionDate(101, 14);
     * ```
     */
    public static function predictStockDepletionDate(int $productId, int $days = 14): ?string
    {
        if (!self::guard()) return null;

        $product = wc_get_product($productId);
        if (!$product || !$product->managing_stock()) return null;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get number of units sold in the given period
        $sales = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta_qty.meta_value)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_pid
          ON items.order_item_id = meta_pid.order_item_id
         AND meta_pid.meta_key = '_product_id'
         AND meta_pid.meta_value = %d
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
          ON items.order_item_id = meta_qty.order_item_id
         AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders
          ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $productId, $since));

        $sales = (int) $sales;
        if ($sales === 0) return null;

        $avgDailySales = $sales / $days;
        if ($avgDailySales == 0) return null;

        $stockQty = (int) $product->get_stock_quantity();
        $daysLeft = ceil($stockQty / $avgDailySales);

        return date('Y-m-d', strtotime("+{$daysLeft} days"));
    }

    /**
     * Get total discount amount applied across all completed orders in a time frame.
     *
     * @param int $days Lookback period in days.
     * @return float Total discount amount.
     *
     * @example
     * ```php
     * $totalDiscount = WooCommerce::getTotalDiscountsApplied(30);
     * ```
     */
    public static function getTotalDiscountsApplied(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Sum the discount total meta values for all completed orders
        $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta.meta_value)
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
          AND meta.meta_key = '_order_discount'
    ", $since));

        return round((float) $total, 2);
    }

    /**
     * Calculate refund rate as a percentage of total orders.
     *
     * @param int $days Time period to evaluate.
     * @return float Refund rate (%).
     *
     * @example
     * ```php
     * $refundRate = WooCommerce::getRefundRate(60);
     * ```
     */
    public static function getRefundRate(int $days = 60): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Count refunded orders
        $refunded = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-refunded'
          AND post_date >= %s
    ", $since));

        // Count total orders (completed + refunded)
        $total = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-refunded')
          AND post_date >= %s
    ", $since));

        if ((int) $total === 0) return 0.0;

        return round(((int) $refunded / (int) $total) * 100, 2);
    }

    /**
     * Identify frequently bundled products (often bought together).
     *
     * @param int $minThreshold Minimum co-purchase count to be included.
     * @return array Array of product ID pairs and count.
     *
     * @example
     * ```php
     * $bundles = WooCommerce::getFrequentlyBundledProducts(5);
     * ```
     */
    public static function getFrequentlyBundledProducts(int $minThreshold = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Get all completed order IDs
        $orderIds = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
    ");

        $bundleMap = [];

        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);
            $items = $order->get_items();

            // Get product IDs in the order
            $productIds = array_map(function($item) {
                return $item->get_product_id();
            }, $items);

            // Loop through combinations of product pairs
            sort($productIds);
            $count = count($productIds);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $pair = "{$productIds[$i]}_{$productIds[$j]}";
                    $bundleMap[$pair] = ($bundleMap[$pair] ?? 0) + 1;
                }
            }
        }

        // Filter by threshold
        $result = [];
        foreach ($bundleMap as $pair => $count) {
            if ($count >= $minThreshold) {
                [$id1, $id2] = explode('_', $pair);
                $result[] = [
                    'product_1' => (int) $id1,
                    'product_2' => (int) $id2,
                    'count'     => $count,
                ];
            }
        }

        return $result;
    }

    /**
     * Forecast future revenue using linear regression on past 30 days.
     *
     * @return float Projected revenue for next 30 days.
     *
     * @example
     * ```php
     * $forecast = WooCommerce::forecastMonthlyRevenue();
     * ```
     */
    public static function forecastMonthlyRevenue(): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        // Gather revenue per day for last 30 days
        $daily = $wpdb->get_results("
        SELECT DATE(post_date) AS date, SUM(meta.meta_value) AS revenue
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND meta.meta_key = '_order_total'
          AND post_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(post_date)
        ORDER BY DATE(post_date)
    ");

        if (count($daily) < 5) return 0.0; // Not enough data to forecast

        $x = [];
        $y = [];
        $i = 0;

        // Prepare data points: x = day index, y = revenue
        foreach ($daily as $row) {
            $x[] = $i++;
            $y[] = (float) $row->revenue;
        }

        $n = count($x);

        // Calculate linear regression slope and intercept
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($a, $b) => $a * $b, $x, $y));
        $sumX2 = array_sum(array_map(fn($v) => $v * $v, $x));

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / max((($n * $sumX2) - ($sumX ** 2)), 1);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Project revenue for next 30 days
        $forecast = 0.0;
        for ($d = $n; $d < $n + 30; $d++) {
            $forecast += $slope * $d + $intercept;
        }

        return round($forecast, 2);
    }

    /**
     * Get reorder rate — how often customers place repeat purchases.
     *
     * @param int $days Number of past days to analyze.
     * @return float Reorder rate as a percentage.
     *
     * @example
     * ```php
     * $rate = WooCommerce::getReorderRate(90);
     * ```
     */
    public static function getReorderRate(int $days = 90): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get all completed orders in the timeframe
        $orders = wc_get_orders([
            'limit'     => -1,
            'status'    => ['completed'],
            'date_paid'=> ">={$since}",
            'return'    => 'ids',
        ]);

        if (empty($orders)) return 0.0;

        $firstTime = 0;
        $repeat = 0;
        $customerOrderCounts = [];

        // Track how many orders each customer has
        foreach ($orders as $orderId) {
            $order = wc_get_order($orderId);
            $customerId = $order->get_customer_id();

            if (!$customerId) continue;

            if (!isset($customerOrderCounts[$customerId])) {
                $customerOrderCounts[$customerId] = 0;
            }

            $customerOrderCounts[$customerId]++;
        }

        foreach ($customerOrderCounts as $count) {
            if ($count === 1) $firstTime++;
            else $repeat++;
        }

        $total = $firstTime + $repeat;
        if ($total === 0) return 0.0;

        return round(($repeat / $total) * 100, 2);
    }

    /**
     * Identify stale inventory — products with no sales in given days.
     *
     * @param int $days Number of days to look back.
     * @return array List of product IDs that haven't sold.
     *
     * @example
     * ```php
     * $stale = WooCommerce::getStaleInventory(60);
     * ```
     */
    public static function getStaleInventory(int $days = 60): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get IDs of products sold in the time period
        $soldProductIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_product.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
          ON items.order_item_id = meta_product.order_item_id
         AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->posts} AS posts
          ON items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
    ", $since));

        // Get all product IDs that are published and visible
        $allProductIds = wc_get_products([
            'status' => 'publish',
            'limit'  => -1,
            'return' => 'ids',
        ]);

        // Find which products were never sold
        $unsold = array_diff($allProductIds, $soldProductIds);

        return array_values($unsold);
    }

    /**
     * Detect pricing anomalies — products priced far above or below category median.
     *
     * @param float $deviationPercent Threshold deviation (e.g., 0.5 = 50%).
     * @return array List of flagged product IDs.
     *
     * @example
     * ```php
     * $anomalies = WooCommerce::getPricingAnomalies(0.4);
     * ```
     */
    public static function getPricingAnomalies(float $deviationPercent = 0.5): array
    {
        if (!self::guard()) return [];

        $flagged = [];
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);

        foreach ($categories as $cat) {
            $products = wc_get_products([
                'status'   => 'publish',
                'limit'    => -1,
                'category' => [$cat->slug],
            ]);

            if (count($products) < 3) continue; // Skip tiny categories

            // Gather prices
            $prices = array_map(fn($p) => (float) $p->get_price(), $products);
            $median = self::calculateMedian($prices);

            foreach ($products as $product) {
                $price = (float) $product->get_price();
                $delta = abs($price - $median) / $median;

                if ($delta > $deviationPercent) {
                    $flagged[] = $product->get_id();
                }
            }
        }

        return array_unique($flagged);
    }

    /**
     * Utility: Calculate median of numeric array.
     *
     * @param array $numbers Array of numeric values.
     * @return float Median value.
     */
    private static function calculateMedian(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);
        $mid = floor($count / 2);

        if ($count % 2) {
            return $numbers[$mid];
        }

        return ($numbers[$mid - 1] + $numbers[$mid]) / 2;
    }

    /**
     * Calculate average delivery delay in days between order completion and shipping date.
     *
     * @param int $days Number of past days to check.
     * @return float Average delay in days.
     *
     * @example
     * ```php
     * $avgDelay = WooCommerce::getAverageDeliveryDelay(90);
     * ```
     */
    public static function getAverageDeliveryDelay(int $days = 90): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Orders must have meta '_date_shipped' indicating shipping date
        $orders = $wpdb->get_results($wpdb->prepare("
        SELECT posts.ID, posts.post_modified, shipped.meta_value AS shipped_date
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS shipped ON posts.ID = shipped.post_id AND shipped.meta_key = '_date_shipped'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
          AND shipped.meta_value IS NOT NULL
    ", $since));

        if (empty($orders)) return 0.0;

        $totalDelay = 0;
        $count = 0;

        foreach ($orders as $order) {
            $completed = strtotime($order->post_modified);
            $shipped = strtotime($order->shipped_date);

            if ($shipped && $shipped >= $completed) {
                $delay = ($shipped - $completed) / 86400; // seconds to days
                $totalDelay += $delay;
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        return round($totalDelay / $count, 2);
    }

    /**
     * Calculate the ratio of digital/downloadable products sold vs physical products.
     *
     * @param int $days Number of past days to evaluate.
     * @return array Associative array with 'digital' and 'physical' keys and their sales counts.
     *
     * @example
     * ```php
     * $ratio = WooCommerce::getDigitalVsPhysicalSalesRatio(30);
     * ```
     */
    public static function getDigitalVsPhysicalSalesRatio(int $days = 30): array
    {
        if (!self::guard()) return ['digital' => 0, 'physical' => 0];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Query order items joined with products to detect downloadable status
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT p.ID, p.post_type, p.post_status, pm.downloadable, COUNT(*) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        JOIN {$wpdb->posts} AS p ON item_meta.meta_value = p.ID AND item_meta.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key = '_downloadable'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND item_meta.meta_key = '_product_id'
        GROUP BY p.ID, pm.downloadable
    ", $since));

        $digitalCount = 0;
        $physicalCount = 0;

        foreach ($results as $row) {
            $qty = (int) $row->qty_sold;
            $isDigital = filter_var($row->downloadable, FILTER_VALIDATE_BOOLEAN);

            if ($isDigital) {
                $digitalCount += $qty;
            } else {
                $physicalCount += $qty;
            }
        }

        return ['digital' => $digitalCount, 'physical' => $physicalCount];
    }

    /**
     * Get top performing categories by total sales revenue in a period.
     *
     * @param int $days Number of past days to consider.
     * @param int $limit Number of top categories to return.
     * @return array Associative array [category_name => total_sales]
     *
     * @example
     * ```php
     * $topCats = WooCommerce::getTopCategoriesBySales(30, 5);
     * ```
     */
    public static function getTopCategoriesBySales(int $days = 30, int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Query to sum sales totals grouped by product category slug
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT terms.name AS category_name, SUM(meta_total.meta_value) AS total_sales
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        JOIN {$wpdb->posts} AS products ON item_meta.meta_value = products.ID AND item_meta.meta_key = '_product_id'
        JOIN {$wpdb->term_relationships} AS rel ON products.ID = rel.object_id
        JOIN {$wpdb->term_taxonomy} AS tax ON rel.term_taxonomy_id = tax.term_taxonomy_id AND tax.taxonomy = 'product_cat'
        JOIN {$wpdb->terms} AS terms ON tax.term_id = terms.term_id
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY terms.term_id
        ORDER BY total_sales DESC
        LIMIT %d
    ", $since, $limit));

        $topCategories = [];

        foreach ($results as $row) {
            $topCategories[$row->category_name] = (float) $row->total_sales;
        }

        return $topCategories;
    }

    /**
     * Calculate product return rate: percentage of sold units that were returned.
     *
     * @param int $days Number of days to consider.
     * @return float Return rate percentage.
     *
     * @example
     * ```php
     * $returnRate = WooCommerce::getProductReturnRate(180);
     * ```
     */
    public static function getProductReturnRate(int $days = 180): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Total quantity sold per product in period (completed orders)
        $soldResults = $wpdb->get_results($wpdb->prepare("
        SELECT item_meta.meta_value AS product_id, SUM(meta_qty.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id AND item_meta.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY product_id
    ", $since));

        // Total quantity returned per product in period (using 'refunded' orders or returned meta)
        $returnedResults = $wpdb->get_results($wpdb->prepare("
        SELECT item_meta.meta_value AS product_id, SUM(meta_qty.meta_value) AS qty_returned
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta ON items.order_item_id = item_meta.order_item_id AND item_meta.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-refunded'
          AND orders.post_date >= %s
        GROUP BY product_id
    ", $since));

        // Map sold quantities
        $soldMap = [];
        foreach ($soldResults as $row) {
            $soldMap[$row->product_id] = (int) $row->qty_sold;
        }

        // Map returned quantities
        $returnedMap = [];
        foreach ($returnedResults as $row) {
            $returnedMap[$row->product_id] = (int) $row->qty_returned;
        }

        $totalSold = 0;
        $totalReturned = 0;

        // Sum totals to calculate overall return rate
        foreach ($soldMap as $productId => $qtySold) {
            $totalSold += $qtySold;
            $totalReturned += $returnedMap[$productId] ?? 0;
        }

        if ($totalSold === 0) return 0.0;

        return round(($totalReturned / $totalSold) * 100, 2);
    }

    /**
     * Calculate the percentage of repeat customers in a given period.
     *
     * @param int $days Number of past days to evaluate.
     * @return float Percentage of customers with more than one order.
     *
     * @example
     * ```php
     * $repeatRate = WooCommerce::getRepeatCustomerRate(365);
     * ```
     */
    public static function getRepeatCustomerRate(int $days = 365): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get count of orders per customer in period
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_customer.meta_value AS customer_id, COUNT(posts.ID) AS order_count
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_customer ON posts.ID = meta_customer.post_id AND meta_customer.meta_key = '_customer_user'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
          AND meta_customer.meta_value != 0
        GROUP BY customer_id
    ", $since));

        if (empty($results)) return 0.0;

        $totalCustomers = count($results);
        $repeatCustomers = 0;

        foreach ($results as $row) {
            if ($row->order_count > 1) {
                $repeatCustomers++;
            }
        }

        // Percentage of customers with multiple orders
        return round(($repeatCustomers / $totalCustomers) * 100, 2);
    }

    /**
     * Calculate the average cart abandonment rate over a period.
     *
     * Cart abandonment = (carts created - completed orders) / carts created * 100
     *
     * @param int $days Number of past days to evaluate.
     * @return float Percentage of abandoned carts.
     *
     * @example
     * ```php
     * $abandonRate = WooCommerce::getCartAbandonmentRate(30);
     * ```
     */
    public static function getCartAbandonmentRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count unique abandoned carts: pending orders or carts saved but not completed
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT posts.ID)
        FROM {$wpdb->posts} AS posts
        WHERE posts.post_type = 'shop_order'
          AND posts.post_date >= %s
          AND posts.post_status IN ('wc-pending', 'wc-cart')
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Count completed orders in period
        $completedOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        // Calculate abandonment rate
        $abandonRate = (($totalCarts - $completedOrders) / $totalCarts) * 100;

        return round(max(0, $abandonRate), 2);
    }

    /**
     * Calculate average discount percentage applied on orders in a period.
     *
     * @param int $days Number of past days to consider.
     * @return float Average discount percentage.
     *
     * @example
     * ```php
     * $avgDiscount = WooCommerce::getAverageOrderDiscount(60);
     * ```
     */
    public static function getAverageOrderDiscount(int $days = 60): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get total order amounts and discount totals for completed orders
        $orders = $wpdb->get_results($wpdb->prepare("
        SELECT 
            CAST(meta_total.meta_value AS DECIMAL(10,2)) AS order_total,
            CAST(meta_discount.meta_value AS DECIMAL(10,2)) AS discount_total
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        LEFT JOIN {$wpdb->postmeta} AS meta_discount ON posts.ID = meta_discount.post_id AND meta_discount.meta_key = '_cart_discount'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
    ", $since));

        if (empty($orders)) return 0.0;

        $totalDiscountPercent = 0.0;
        $count = 0;

        foreach ($orders as $order) {
            $total = (float) $order->order_total;
            $discount = (float) $order->discount_total;

            // Ignore orders with zero total or no discount
            if ($total > 0 && $discount > 0) {
                $discountPercent = ($discount / ($total + $discount)) * 100;
                $totalDiscountPercent += $discountPercent;
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        return round($totalDiscountPercent / $count, 2);
    }

    /**
     * Calculate the proportion of subscription products in total sales.
     *
     * @param int $days Number of past days to consider.
     * @return float Percentage of subscription product sales.
     *
     * @example
     * ```php
     * $subscriptionRatio = WooCommerce::getSubscriptionSalesRatio(90);
     * ```
     */
    public static function getSubscriptionSalesRatio(int $days = 90): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Total quantity sold of all products
        $totalSold = (int) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta_qty.meta_value)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        if ($totalSold === 0) return 0.0;

        // Quantity sold of subscription products (identified by product meta '_subscription_period')
        $subscriptionSold = (int) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta_qty.meta_value)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_sub ON meta_product.meta_value = meta_sub.post_id AND meta_sub.meta_key = '_subscription_period'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        return round(($subscriptionSold / $totalSold) * 100, 2);
    }

    /**
     * Get products with the highest return rate in a period.
     *
     * Return rate = (number returned / number sold) * 100.
     *
     * @param int $days Number of past days to consider.
     * @param int $limit Number of top products to return.
     * @return array Array of product IDs and their return rates.
     *
     * @example
     * ```php
     * $topReturns = WooCommerce::getTopReturnedProducts(90, 5);
     * ```
     */
    public static function getTopReturnedProducts(int $days = 90, int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Query total sold quantities
        $sold = $wpdb->get_results($wpdb->prepare("
        SELECT meta_product.meta_value AS product_id, SUM(CAST(meta_qty.meta_value AS UNSIGNED)) AS total_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY product_id
    ", $since));

        // Query returned quantities
        $returned = $wpdb->get_results($wpdb->prepare("
        SELECT meta_product.meta_value AS product_id, SUM(CAST(meta_qty.meta_value AS UNSIGNED)) AS total_returned
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON items.order_item_id = meta_product.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        JOIN {$wpdb->postmeta} AS postmeta ON orders.ID = postmeta.post_id AND postmeta.meta_key = '_refund_reason'  -- indicative of refund/return
        WHERE orders.post_type = 'shop_order_refund'
          AND orders.post_date >= %s
        GROUP BY product_id
    ", $since));

        // Map returned quantities by product_id
        $returnedMap = [];
        foreach ($returned as $ret) {
            $returnedMap[$ret->product_id] = (int)$ret->total_returned;
        }

        $returnRates = [];

        foreach ($sold as $sale) {
            $pid = $sale->product_id;
            $soldQty = (int)$sale->total_sold;
            $retQty = $returnedMap[$pid] ?? 0;

            if ($soldQty > 0) {
                $rate = ($retQty / $soldQty) * 100;
                $returnRates[$pid] = round($rate, 2);
            }
        }

        // Sort descending by return rate
        arsort($returnRates);

        return array_slice($returnRates, 0, $limit, true);
    }

    /**
     * Calculate average time to first purchase for new customers in days.
     *
     * @param int $days Number of past days to consider for new customers.
     * @return float Average days from registration to first order.
     *
     * @example
     * ```php
     * $avgTimeFirstPurchase = WooCommerce::getAverageTimeToFirstPurchase(180);
     * ```
     */
    public static function getAverageTimeToFirstPurchase(int $days = 180): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get new customers registered in the timeframe
        $newCustomers = $wpdb->get_results($wpdb->prepare("
        SELECT ID, user_registered
        FROM {$wpdb->users}
        WHERE user_registered >= %s
    ", $since));

        if (empty($newCustomers)) return 0.0;

        $totalDays = 0;
        $count = 0;

        foreach ($newCustomers as $user) {
            // Get first completed order date for user
            $firstOrderDate = $wpdb->get_var($wpdb->prepare("
            SELECT MIN(posts.post_date)
            FROM {$wpdb->posts} AS posts
            JOIN {$wpdb->postmeta} AS meta_user ON posts.ID = meta_user.post_id AND meta_user.meta_key = '_customer_user' AND meta_user.meta_value = %d
            WHERE posts.post_type = 'shop_order'
              AND posts.post_status = 'wc-completed'
        ", $user->ID));

            if ($firstOrderDate) {
                $registrationTime = strtotime($user->user_registered);
                $firstOrderTime = strtotime($firstOrderDate);

                if ($firstOrderTime >= $registrationTime) {
                    $daysDiff = ($firstOrderTime - $registrationTime) / DAY_IN_SECONDS;
                    $totalDays += $daysDiff;
                    $count++;
                }
            }
        }

        if ($count === 0) return 0.0;

        return round($totalDays / $count, 2);
    }

    /**
     * Get the percentage of orders paid with a specific payment gateway.
     *
     * @param string $gatewayId Payment gateway ID slug (e.g. 'paypal', 'stripe').
     * @param int $days Number of past days to check.
     * @return float Percentage of orders paid with the gateway.
     *
     * @example
     * ```php
     * $paypalShare = WooCommerce::getPaymentGatewayShare('paypal', 30);
     * ```
     */
    public static function getPaymentGatewayShare(string $gatewayId, int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Total completed orders in period
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return 0.0;

        // Orders paid with specified gateway (stored in _payment_method meta)
        $gatewayOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT postmeta.post_id)
        FROM {$wpdb->postmeta} AS postmeta
        JOIN {$wpdb->posts} AS posts ON postmeta.post_id = posts.ID
        WHERE postmeta.meta_key = '_payment_method'
          AND postmeta.meta_value = %s
          AND posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
    ", $gatewayId, $since));

        return round(($gatewayOrders / $totalOrders) * 100, 2);
    }

    /**
     * Get top N customers by total spending over a period.
     *
     * Returns an array of customer IDs and their total spendings.
     *
     * @param int $days Number of past days to consider.
     * @param int $limit Number of top customers to return.
     * @return array Associative array [customer_id => total_spent].
     *
     * @example
     * ```php
     * $topCustomers = WooCommerce::getTopCustomersBySpending(365, 10);
     * ```
     */
    public static function getTopCustomersBySpending(int $days = 365, int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get total spending grouped by customer_id ordered descending by total spent
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_customer.meta_value AS customer_id, SUM(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS total_spent
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_customer ON posts.ID = meta_customer.post_id AND meta_customer.meta_key = '_customer_user'
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
          AND meta_customer.meta_value != 0
        GROUP BY customer_id
        ORDER BY total_spent DESC
        LIMIT %d
    ", $since, $limit));

        $topCustomers = [];

        foreach ($results as $row) {
            $topCustomers[(int)$row->customer_id] = (float)$row->total_spent;
        }

        return $topCustomers;
    }

    /**
     * Calculate revenue per visitor (RPV) for a given period.
     *
     * RPV = Total revenue / Total unique visitors
     *
     * Requires WooCommerce and a visitor tracking system that stores visitor counts in options or stats table.
     *
     * @param int $days Number of past days to consider.
     * @return float Revenue per visitor in site currency.
     *
     * @example
     * ```php
     * $rpv = WooCommerce::getRevenuePerVisitor(30);
     * ```
     */
    public static function getRevenuePerVisitor(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Sum total revenue from completed orders
        $totalRevenue = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status = 'wc-completed'
          AND posts.post_date >= %s
    ", $since));

        // Get total unique visitors from a custom option or table
        // Replace 'unique_visitors_last_30_days' with actual option or tracking logic
        $visitorOptionKey = "unique_visitors_last_{$days}_days";
        $uniqueVisitors = (int) get_option($visitorOptionKey, 0);

        if ($uniqueVisitors === 0) return 0.0;

        return round($totalRevenue / $uniqueVisitors, 2);
    }

    /**
     * Get the distribution of order statuses in a period as percentages.
     *
     * @param int $days Number of past days to consider.
     * @return array Associative array [status_slug => percentage]
     *
     * @example
     * ```php
     * $statusDistribution = WooCommerce::getOrderStatusDistribution(30);
     * ```
     */
    public static function getOrderStatusDistribution(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get counts of orders grouped by post_status
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_status, COUNT(*) AS count
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_date >= %s
        GROUP BY post_status
    ", $since), OBJECT_K);

        if (empty($results)) return [];

        $totalOrders = 0;
        foreach ($results as $status => $row) {
            $totalOrders += (int) $row->count;
        }

        if ($totalOrders === 0) return [];

        $distribution = [];

        foreach ($results as $status => $row) {
            $distribution[$status] = round(((int)$row->count / $totalOrders) * 100, 2);
        }

        return $distribution;
    }

    /**
     * Get average cart value for all abandoned carts within a given period.
     *
     * Assumes abandoned carts are orders with status 'wc-pending' or 'wc-failed'
     * and optionally marked with '_abandoned_cart' meta = 'yes'.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average cart value in site currency.
     *
     * @example
     * ```php
     * $avgAbandonedCartValue = WooCommerce::getAverageAbandonedCartValue(30);
     * ```
     */
    public static function getAverageAbandonedCartValue(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select abandoned carts matching criteria
        $avgValue = (float) $wpdb->get_var($wpdb->prepare("
        SELECT AVG(CAST(meta_total.meta_value AS DECIMAL(10,2))) 
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        JOIN {$wpdb->postmeta} AS meta_abandoned ON posts.ID = meta_abandoned.post_id AND meta_abandoned.meta_key = '_abandoned_cart'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
          AND meta_abandoned.meta_value = 'yes'
    ", $since));

        return round($avgValue, 2);
    }

    /**
     * Get the total number of items currently in all active carts.
     *
     * Active carts are considered those in WooCommerce sessions or stored as 'pending' orders.
     *
     * @return int Total quantity of items in active carts.
     *
     * @example
     * ```php
     * $activeCartItems = WooCommerce::getTotalItemsInActiveCarts();
     * ```
     */
    public static function getTotalItemsInActiveCarts(): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        // Count quantity of all items in pending or failed orders (typical carts)
        $totalItems = (int) $wpdb->get_var("
        SELECT SUM(CAST(meta_qty.meta_value AS UNSIGNED))
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON items.order_item_id = meta_qty.order_item_id AND meta_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
    ");

        return $totalItems ?: 0;
    }

    /**
     * Estimate the cart conversion rate over a period.
     *
     * Conversion rate = (number of completed orders / number of carts created) * 100
     *
     * Carts created are approximated by counting orders with statuses 'pending' or 'failed'
     * plus completed orders, assuming each corresponds to a cart.
     *
     * @param int $days Number of past days to analyze.
     * @return float Cart conversion rate percentage.
     *
     * @example
     * ```php
     * $cartConversionRate = WooCommerce::getCartConversionRate(30);
     * ```
     */
    public static function getCartConversionRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Count completed orders
        $completedOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        // Count carts initiated (pending + failed orders)
        $initiatedCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        $totalCarts = $initiatedCarts + $completedOrders;

        if ($totalCarts === 0) return 0.0;

        return round(($completedOrders / $totalCarts) * 100, 2);
    }

    /**
     * Get average time (in minutes) carts remain abandoned before being recovered or lost.
     *
     * Measures the time difference between cart creation and either order completion or last update,
     * focusing on abandoned carts (pending or failed).
     *
     * @param int $days Number of past days to analyze.
     * @return float Average abandonment duration in minutes.
     *
     * @example
     * ```php
     * $avgAbandonTime = WooCommerce::getAverageCartAbandonmentTime(30);
     * ```
     */
    public static function getAverageCartAbandonmentTime(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select carts with creation and last update times
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_date AS created, post_modified AS modified
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        if (empty($results)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        foreach ($results as $row) {
            $created = strtotime($row->created);
            $modified = strtotime($row->modified);

            if ($modified > $created) {
                $totalSeconds += ($modified - $created);
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        // Return average in minutes
        return round(($totalSeconds / $count) / 60, 2);
    }

    /**
     * Get the percentage of carts containing more than one product.
     *
     * Helps understand if customers tend to buy multiple items or single products in carts.
     *
     * @param int $days Number of past days to consider.
     * @return float Percentage of multi-product carts.
     *
     * @example
     * ```php
     * $multiProductCartRate = WooCommerce::getMultiProductCartPercentage(30);
     * ```
     */
    public static function getMultiProductCartPercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Total carts (pending or failed)
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Count carts with more than 1 distinct product
        $multiProductCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT items.order_id)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY items.order_id
        HAVING COUNT(DISTINCT items.order_item_id) > 1
    ", $since));

        // Note: HAVING + COUNT + GROUP BY returns multiple rows, so we count distinct order_ids from those rows.

        return round(($multiProductCarts / $totalCarts) * 100, 2);
    }

    /**
     * Get total revenue lost due to abandoned carts within a period.
     *
     * Calculates the sum of order totals for carts that never converted (pending or failed and marked abandoned).
     *
     * @param int $days Number of past days to analyze.
     * @return float Total lost revenue amount.
     *
     * @example
     * ```php
     * $lostRevenue = WooCommerce::getLostRevenueFromAbandonedCarts(30);
     * ```
     */
    public static function getLostRevenueFromAbandonedCarts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum totals of abandoned carts (pending or failed, with '_abandoned_cart' meta = 'yes')
        $lostRevenue = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        JOIN {$wpdb->postmeta} AS meta_abandoned ON posts.ID = meta_abandoned.post_id AND meta_abandoned.meta_key = '_abandoned_cart'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
          AND meta_abandoned.meta_value = 'yes'
    ", $since));

        return round($lostRevenue, 2);
    }

    /**
     * Get the average number of unique products per cart in a given period.
     *
     * This helps measure how many different products customers add to their carts on average.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average unique products per cart.
     *
     * @example
     * ```php
     * $avgUniqueProducts = WooCommerce::getAverageUniqueProductsPerCart(30);
     * ```
     */
    public static function getAverageUniqueProductsPerCart(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Get total number of carts (pending or failed)
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Sum of unique product counts per cart
        $totalUniqueProducts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(product_count) FROM (
            SELECT COUNT(DISTINCT items.order_item_id) AS product_count
            FROM {$wpdb->prefix}woocommerce_order_items AS items
            JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
            WHERE orders.post_type = 'shop_order'
              AND orders.post_status IN ('wc-pending', 'wc-failed')
              AND orders.post_date >= %s
            GROUP BY items.order_id
        ) AS subquery
    ", $since));

        return round($totalUniqueProducts / $totalCarts, 2);
    }

    /**
     * Get the percentage of carts that contain at least one product from a specific category.
     *
     * Useful to understand category-specific cart engagement.
     *
     * @param int $categoryId WooCommerce product category ID.
     * @param int $days Number of past days to analyze.
     * @return float Percentage of carts with category product.
     *
     * @example
     * ```php
     * $categoryCartRate = WooCommerce::getCartPercentageWithCategoryProduct(15, 123);
     * ```
     */
    public static function getCartPercentageWithCategoryProduct(int $categoryId, int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Total carts (pending or failed)
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Count carts containing at least one product in the given category
        $cartsWithCategory = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT items.order_id)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id AND itemmeta.meta_key = '_product_id'
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        JOIN {$wpdb->term_relationships} AS term_rel ON term_rel.object_id = itemmeta.meta_value
        JOIN {$wpdb->term_taxonomy} AS term_tax ON term_tax.term_taxonomy_id = term_rel.term_taxonomy_id
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND term_tax.taxonomy = 'product_cat'
          AND term_tax.term_id = %d
    ", $since, $categoryId));

        return round(($cartsWithCategory / $totalCarts) * 100, 2);
    }

    /**
     * Calculate average quantity per product in all active carts.
     *
     * Helps to understand average units of each product added to carts.
     *
     * @param int $days Number of past days to consider.
     * @return float Average quantity per product in active carts.
     *
     * @example
     * ```php
     * $avgQty = WooCommerce::getAverageQuantityPerProductInCarts(30);
     * ```
     */
    public static function getAverageQuantityPerProductInCarts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d', strtotime("-{$days} days"));

        // Sum total quantity of all items in carts
        $totalQty = (int) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_qty.meta_value AS UNSIGNED))
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta_qty.order_item_id = items.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE meta_qty.meta_key = '_qty'
          AND orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        // Count distinct products in carts
        $distinctProducts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT meta_product.meta_value)
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta_product.order_item_id = items.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE meta_product.meta_key = '_product_id'
          AND orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($distinctProducts === 0) return 0.0;

        return round($totalQty / $distinctProducts, 2);
    }

    /**
     * Calculate the percentage of carts abandoned after adding at least one coupon.
     *
     * This helps analyze if customers abandon carts after trying to apply discounts.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of carts with coupons that were abandoned.
     *
     * @example
     * ```php
     * $couponAbandonRate = WooCommerce::getCouponAppliedCartAbandonmentRate(30);
     * ```
     */
    public static function getCouponAppliedCartAbandonmentRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total carts with coupons applied (pending or failed)
        $totalCartsWithCoupons = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS coupons ON coupons.order_id = orders.ID AND coupons.order_item_type = 'coupon'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        // Total completed orders with coupons
        $completedCartsWithCoupons = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS coupons ON coupons.order_id = orders.ID AND coupons.order_item_type = 'coupon'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        if ($totalCartsWithCoupons === 0) return 0.0;

        // Abandoned = carts with coupons - completed carts with coupons
        $abandonedCartsWithCoupons = max(0, $totalCartsWithCoupons - $completedCartsWithCoupons);

        return round(($abandonedCartsWithCoupons / $totalCartsWithCoupons) * 100, 2);
    }

    /**
     * Calculate the average cart subtotal (before discounts) for all active carts.
     *
     * This excludes completed orders, focusing on carts still in process or abandoned.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average cart subtotal amount.
     *
     * @example
     * ```php
     * $avgCartSubtotal = WooCommerce::getAverageCartSubtotal(30);
     * ```
     */
    public static function getAverageCartSubtotal(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum and average of '_cart_subtotal' meta for pending and failed orders
        $avgSubtotal = (float) $wpdb->get_var($wpdb->prepare("
        SELECT AVG(CAST(meta_subtotal.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_subtotal ON posts.ID = meta_subtotal.post_id AND meta_subtotal.meta_key = '_cart_subtotal'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        return round($avgSubtotal, 2);
    }

    /**
     * Get the most frequently abandoned product IDs within a given period.
     *
     * Identifies products that appear most often in abandoned carts.
     *
     * @param int $days Number of past days to analyze.
     * @param int $limit Number of top products to return.
     * @return array Array of product IDs sorted by abandonment frequency.
     *
     * @example
     * ```php
     * $topAbandonedProducts = WooCommerce::getTopAbandonedProducts(30, 10);
     * ```
     */
    public static function getTopAbandonedProducts(int $days = 30, int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query products appearing in abandoned carts (pending or failed orders)
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_product.meta_value AS product_id, COUNT(*) AS abandon_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY meta_product.meta_value
        ORDER BY abandon_count DESC
        LIMIT %d
    ", $since, $limit));

        // Extract product IDs into a simple array
        return array_map(fn($row) => (int)$row->product_id, $results);
    }

    /**
     * Calculate the average discount amount applied per abandoned cart.
     *
     * This measures how much discount value (coupon or manual) was used in carts that were abandoned,
     * helping to understand potential revenue lost due to discounts.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average discount amount per abandoned cart.
     *
     * @example
     * ```php
     * $avgDiscount = WooCommerce::getAverageDiscountPerAbandonedCart(30);
     * ```
     */
    public static function getAverageDiscountPerAbandonedCart(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get total discount amount and count of abandoned carts (pending or failed)
        $result = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(DISTINCT posts.ID) AS cart_count,
            SUM(CAST(meta_discount.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_discount ON posts.ID = meta_discount.post_id AND meta_discount.meta_key = '_cart_discount'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        if (!$result || (int)$result->cart_count === 0) return 0.0;

        return round(((float)$result->total_discount) / ((int)$result->cart_count), 2);
    }

    /**
     * Get the average number of coupons applied per abandoned cart.
     *
     * This helps analyze coupon usage frequency in carts that were abandoned.
     *
     * @param int $days Number of past days to consider.
     * @return float Average coupons applied per abandoned cart.
     *
     * @example
     * ```php
     * $avgCoupons = WooCommerce::getAverageCouponsPerAbandonedCart(30);
     * ```
     */
    public static function getAverageCouponsPerAbandonedCart(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total abandoned carts (pending or failed)
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Count total coupons applied in abandoned carts
        $totalCoupons = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_items AS coupons
        JOIN {$wpdb->posts} AS orders ON coupons.order_id = orders.ID
        WHERE coupons.order_item_type = 'coupon'
          AND orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        return round($totalCoupons / $totalCarts, 2);
    }

    /**
     * Get the percentage of abandoned carts recovered within a specified time frame.
     *
     * Recovery is defined as carts initially pending or failed but converted to completed orders within the timeframe.
     *
     * @param int $days Number of past days to analyze.
     * @param int $recoveryWindowHours Time window in hours to consider a cart as recovered.
     * @return float Percentage of abandoned carts successfully recovered.
     *
     * @example
     * ```php
     * $recoveryRate = WooCommerce::getAbandonedCartRecoveryRate(30, 48);
     * ```
     */
    public static function getAbandonedCartRecoveryRate(int $days = 30, int $recoveryWindowHours = 48): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get abandoned carts (pending or failed)
        $abandonedCarts = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_date
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        if (empty($abandonedCarts)) return 0.0;

        $recoveredCount = 0;

        // For each abandoned cart, check if a completed order exists from the same customer within recovery window
        foreach ($abandonedCarts as $cart) {
            // Retrieve customer ID or email meta from order meta for matching
            $customerId = $wpdb->get_var($wpdb->prepare("
            SELECT meta_value FROM {$wpdb->postmeta}
            WHERE post_id = %d AND meta_key = '_customer_user'
        ", $cart->ID));

            if (!$customerId) continue; // Skip if no customer ID

            $abandonTimestamp = strtotime($cart->post_date);
            $recoveryDeadline = date('Y-m-d H:i:s', $abandonTimestamp + ($recoveryWindowHours * 3600));

            // Check if completed order exists for same customer within recovery window after abandoned cart
            $completedCount = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status = 'wc-completed'
              AND post_date >= %s
              AND post_date <= %s
              AND ID != %d
              AND (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = ID AND meta_key = '_customer_user') = %d
        ", $cart->post_date, $recoveryDeadline, $cart->ID, $customerId));

            if ($completedCount > 0) {
                $recoveredCount++;
            }
        }

        return round(($recoveredCount / count($abandonedCarts)) * 100, 2);
    }

    /**
     * Get the total value of all abandoned carts within a given period.
     *
     * Calculates the sum of cart totals for all carts with statuses 'pending' or 'failed',
     * helping estimate potential lost revenue.
     *
     * @param int $days Number of past days to consider.
     * @return float Total abandoned cart value.
     *
     * @example
     * ```php
     * $totalAbandonedValue = WooCommerce::getTotalAbandonedCartValue(30);
     * ```
     */
    public static function getTotalAbandonedCartValue(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum the '_order_total' meta for all abandoned carts (pending or failed)
        $total = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        return round($total, 2);
    }

    /**
     * Get the average time (in minutes) customers spend with products in cart before abandoning.
     *
     * Measures the average duration between cart creation and last cart modification for abandoned carts.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average cart lifetime in minutes.
     *
     * @example
     * ```php
     * $avgCartLifetime = WooCommerce::getAverageAbandonedCartLifetime(30);
     * ```
     */
    public static function getAverageAbandonedCartLifetime(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get abandoned carts with their created and modified timestamps
        $carts = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_date, post_modified
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        if (empty($carts)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        // Calculate time difference between creation and last modification
        foreach ($carts as $cart) {
            $created = strtotime($cart->post_date);
            $modified = strtotime($cart->post_modified);

            if ($modified >= $created) {
                $totalSeconds += ($modified - $created);
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        // Return average in minutes
        return round(($totalSeconds / $count) / 60, 2);
    }

    /**
     * Get the distribution of cart statuses over a specified period.
     *
     * Returns counts of carts by their WooCommerce order status (pending, failed, cancelled, etc.)
     * to analyze cart lifecycle trends.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array of status => count.
     *
     * @example
     * ```php
     * $statusDist = WooCommerce::getCartStatusDistribution(30);
     * ```
     */
    public static function getCartStatusDistribution(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query counts of orders grouped by post_status
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_status, COUNT(*) AS count
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_date >= %s
          AND post_status != 'wc-completed' -- exclude completed as focus is carts
        GROUP BY post_status
    ", $since));

        $distribution = [];

        foreach ($results as $row) {
            // Normalize status key by removing 'wc-' prefix for readability
            $statusKey = preg_replace('/^wc-/', '', $row->post_status);
            $distribution[$statusKey] = (int) $row->count;
        }

        return $distribution;
    }

    /**
     * Calculate the average number of items per abandoned cart within a specified timeframe.
     *
     * This helps understand customer behavior by measuring cart size before abandonment.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average quantity of items in abandoned carts.
     *
     * @example
     * ```php
     * $avgItems = WooCommerce::getAverageItemsInAbandonedCarts(30);
     * ```
     */
    public static function getAverageItemsInAbandonedCarts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get total quantity of items and count of abandoned carts (pending or failed)
        $result = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(DISTINCT posts.ID) AS cart_count,
            SUM(CAST(meta_qty.meta_value AS UNSIGNED)) AS total_qty
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = posts.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_qty ON meta_qty.order_item_id = items.order_item_id AND meta_qty.meta_key = '_qty'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        if (!$result || (int)$result->cart_count === 0) return 0.0;

        return round(((float)$result->total_qty) / ((int)$result->cart_count), 2);
    }

    /**
     * Get the top N most commonly abandoned product categories within a given timeframe.
     *
     * Useful for identifying product categories that contribute most to cart abandonment.
     *
     * @param int $days Number of past days to analyze.
     * @param int $limit Number of categories to return.
     * @return array Associative array of category name => abandonment count.
     *
     * @example
     * ```php
     * $topCategories = WooCommerce::getTopAbandonedProductCategories(30, 5);
     * ```
     */
    public static function getTopAbandonedProductCategories(int $days = 30, int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Join orders, items, and term relationships to get category data for abandoned carts
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT terms.name AS category_name, COUNT(*) AS abandon_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->term_relationships} AS term_rel ON term_rel.object_id = meta_product.meta_value
        JOIN {$wpdb->term_taxonomy} AS term_tax ON term_tax.term_taxonomy_id = term_rel.term_taxonomy_id AND term_tax.taxonomy = 'product_cat'
        JOIN {$wpdb->terms} AS terms ON terms.term_id = term_tax.term_id
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY terms.term_id
        ORDER BY abandon_count DESC
        LIMIT %d
    ", $since, $limit));

        $categories = [];
        foreach ($results as $row) {
            $categories[$row->category_name] = (int)$row->abandon_count;
        }

        return $categories;
    }

    /**
     * Calculate the ratio of carts abandoned after adding a specific product.
     *
     * Measures how often carts containing a given product remain incomplete.
     *
     * @param int $productId WooCommerce product ID to analyze.
     * @param int $days Number of past days to analyze.
     * @return float Percentage of carts with the product that were abandoned.
     *
     * @example
     * ```php
     * $abandonRate = WooCommerce::getProductAbandonmentRate(123, 30);
     * ```
     */
    public static function getProductAbandonmentRate(int $productId, int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total carts containing the product (any status)
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id' AND meta_product.meta_value = %d
        WHERE orders.post_type = 'shop_order'
          AND orders.post_date >= %s
    ", $productId, $since));

        if ($totalCarts === 0) return 0.0;

        // Carts with product that were abandoned (pending or failed)
        $abandonedCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id' AND meta_product.meta_value = %d
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $productId, $since));

        return round(($abandonedCarts / $totalCarts) * 100, 2);
    }

    /**
     * Calculate the average cart value for recovered carts within a timeframe.
     *
     * Recovered carts are those initially abandoned (pending or failed) but later converted to completed orders.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average order total of recovered carts.
     *
     * @example
     * ```php
     * $avgRecoveredValue = WooCommerce::getAverageRecoveredCartValue(30);
     * ```
     */
    public static function getAverageRecoveredCartValue(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select recovered orders: completed orders from customers who had abandoned carts within timeframe
        $query = "
        SELECT AVG(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS avg_value
        FROM {$wpdb->posts} AS completed
        JOIN {$wpdb->postmeta} AS meta_total ON completed.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE completed.post_type = 'shop_order'
          AND completed.post_status = 'wc-completed'
          AND completed.post_date >= %s
          AND EXISTS (
              SELECT 1 FROM {$wpdb->posts} AS abandoned
              WHERE abandoned.post_type = 'shop_order'
                AND abandoned.post_status IN ('wc-pending', 'wc-failed')
                AND abandoned.post_date >= %s
                AND (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = abandoned.ID AND meta_key = '_customer_user') = 
                    (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = completed.ID AND meta_key = '_customer_user')
                AND abandoned.post_date < completed.post_date
          )
    ";

        $avgValue = $wpdb->get_var($wpdb->prepare($query, $since, $since));

        return $avgValue ? round((float) $avgValue, 2) : 0.0;
    }

    /**
     * Get the top N products most frequently removed from carts before abandonment.
     *
     * Tracks products that customers added but later removed from their carts in abandoned sessions.
     * Assumes a custom meta or tracking mechanism that logs removed products per order.
     *
     * @param int $days Number of past days to analyze.
     * @param int $limit Number of top products to return.
     * @return array Associative array of product ID => removal count.
     *
     * @example
     * ```php
     * $topRemoved = WooCommerce::getTopRemovedProductsFromAbandonedCarts(30, 5);
     * ```
     */
    public static function getTopRemovedProductsFromAbandonedCarts(int $days = 30, int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // This example assumes a custom postmeta '_removed_products' storing serialized array of product IDs
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->postmeta} AS pm
        JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
        WHERE pm.meta_key = '_removed_products'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-pending', 'wc-failed')
          AND p.post_date >= %s
    ", $since));

        $removalCounts = [];

        // Aggregate counts of removed products
        foreach ($results as $row) {
            $removedProducts = maybe_unserialize($row->meta_value);
            if (!is_array($removedProducts)) continue;

            foreach ($removedProducts as $prodId) {
                if (!isset($removalCounts[$prodId])) {
                    $removalCounts[$prodId] = 0;
                }
                $removalCounts[$prodId]++;
            }
        }

        // Sort by descending removal counts and return top N
        arsort($removalCounts);

        return array_slice($removalCounts, 0, $limit, true);
    }

    /**
     * Get the ratio of abandoned carts that contain at least one product on sale.
     *
     * This helps analyze if discounted products influence cart abandonment behavior.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of abandoned carts containing sale products.
     *
     * @example
     * ```php
     * $saleCartRatio = WooCommerce::getAbandonedCartsWithSaleProductsRatio(30);
     * ```
     */
    public static function getAbandonedCartsWithSaleProductsRatio(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total abandoned carts
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Carts that have at least one sale product
        $query = "
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_sale ON meta_sale.post_id = meta_product.meta_value AND meta_sale.meta_key = '_sale_price'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND meta_sale.meta_value != '' AND meta_sale.meta_value > 0
    ";

        $cartsWithSale = (int) $wpdb->get_var($wpdb->prepare($query, $since));

        return round(($cartsWithSale / $totalCarts) * 100, 2);
    }

    /**
     * Calculate the average discount amount given per abandoned cart within a timeframe.
     *
     * This helps understand how much potential discount value is lost due to cart abandonment.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average discount amount per abandoned cart.
     *
     * @example
     * ```php
     * $avgDiscount = WooCommerce::getAverageDiscountInAbandonedCarts(30);
     * ```
     */
    public static function getAverageDiscountInAbandonedCarts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum of discounts and count of abandoned carts
        $result = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(DISTINCT posts.ID) AS cart_count,
            SUM(CAST(meta_discount.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_discount ON posts.ID = meta_discount.post_id AND meta_discount.meta_key = '_cart_discount'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        if (!$result || (int)$result->cart_count === 0) return 0.0;

        return round(((float)$result->total_discount) / ((int)$result->cart_count), 2);
    }

    /**
     * Get the percentage of abandoned carts that used coupon codes.
     *
     * Useful to analyze coupon effectiveness and cart abandonment correlation.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of abandoned carts using coupons.
     *
     * @example
     * ```php
     * $couponUsageRate = WooCommerce::getAbandonedCartsCouponUsageRate(30);
     * ```
     */
    public static function getAbandonedCartsCouponUsageRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total abandoned carts
        $totalCarts = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        if ($totalCarts === 0) return 0.0;

        // Abandoned carts with at least one coupon used
        $cartsWithCoupons = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'coupon'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ", $since));

        return round(($cartsWithCoupons / $totalCarts) * 100, 2);
    }

    /**
     * Get the average number of unique customers who abandoned carts multiple times within the timeframe.
     *
     * Identifies repeat cart abandoners to target for remarketing.
     *
     * @param int $days Number of past days to analyze.
     * @return int Count of customers with multiple abandoned carts.
     *
     * @example
     * ```php
     * $repeatAbandoners = WooCommerce::getRepeatAbandoningCustomers(30);
     * ```
     */
    public static function getRepeatAbandoningCustomers(int $days = 30): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query customers who have 2 or more abandoned carts (pending or failed)
        $query = "
        SELECT COUNT(DISTINCT customer_id) FROM (
            SELECT meta_customer.meta_value AS customer_id, COUNT(*) AS abandon_count
            FROM {$wpdb->posts} AS orders
            JOIN {$wpdb->postmeta} AS meta_customer ON orders.ID = meta_customer.post_id AND meta_customer.meta_key = '_customer_user'
            WHERE orders.post_type = 'shop_order'
              AND orders.post_status IN ('wc-pending', 'wc-failed')
              AND orders.post_date >= %s
              AND meta_customer.meta_value != '0' AND meta_customer.meta_value IS NOT NULL
            GROUP BY meta_customer.meta_value
            HAVING abandon_count >= 2
        ) AS subquery
    ";

        return (int) $wpdb->get_var($wpdb->prepare($query, $since));
    }

    /**
     * Calculate the total potential revenue lost due to abandoned carts within a timeframe.
     *
     * This sums the total value of all abandoned carts to estimate revenue at risk.
     *
     * @param int $days Number of past days to analyze.
     * @return float Total potential revenue lost.
     *
     * @example
     * ```php
     * $revenueLost = WooCommerce::getTotalPotentialRevenueLostFromAbandonedCarts(30);
     * ```
     */
    public static function getTotalPotentialRevenueLostFromAbandonedCarts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum the '_order_total' meta values of abandoned carts (pending or failed orders)
        $totalLost = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->posts} AS posts
        JOIN {$wpdb->postmeta} AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ('wc-pending', 'wc-failed')
          AND posts.post_date >= %s
    ", $since));

        return $totalLost ? round((float)$totalLost, 2) : 0.0;
    }

    /**
     * Get the average time (in minutes) carts remain abandoned before being either recovered or permanently lost.
     *
     * Helps analyze customer decision time for cart recovery strategies.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average abandonment duration in minutes.
     *
     * @example
     * ```php
     * $avgAbandonTime = WooCommerce::getAverageAbandonmentDuration(30);
     * ```
     */
    public static function getAverageAbandonmentDuration(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query abandoned carts (pending or failed) and the earliest completed order from same user after abandonment
        $query = "
        SELECT AVG(TIMESTAMPDIFF(MINUTE, abandoned.post_date, COALESCE(completed.post_date, NOW()))) AS avg_duration
        FROM {$wpdb->posts} AS abandoned
        LEFT JOIN {$wpdb->posts} AS completed ON
            completed.post_type = 'shop_order'
            AND completed.post_status = 'wc-completed'
            AND completed.post_date > abandoned.post_date
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} AS meta_abandoned
                JOIN {$wpdb->postmeta} AS meta_completed ON meta_abandoned.meta_value = meta_completed.meta_value
                WHERE meta_abandoned.post_id = abandoned.ID AND meta_abandoned.meta_key = '_customer_user'
                  AND meta_completed.post_id = completed.ID AND meta_completed.meta_key = '_customer_user'
            )
        WHERE abandoned.post_type = 'shop_order'
          AND abandoned.post_status IN ('wc-pending', 'wc-failed')
          AND abandoned.post_date >= %s
    ";

        $avgDuration = $wpdb->get_var($wpdb->prepare($query, $since));

        return $avgDuration ? round((float)$avgDuration, 2) : 0.0;
    }

    /**
     * Get the count of abandoned carts that contain digital/downloadable products.
     *
     * Useful to understand abandonment rates for digital goods versus physical.
     *
     * @param int $days Number of past days to analyze.
     * @return int Number of abandoned carts containing digital products.
     *
     * @example
     * ```php
     * $digitalAbandoned = WooCommerce::getAbandonedCartsWithDigitalProducts(30);
     * ```
     */
    public static function getAbandonedCartsWithDigitalProducts(int $days = 30): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Join abandoned orders and line items with product downloadable meta
        $query = "
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_downloadable ON meta_downloadable.post_id = meta_product.meta_value AND meta_downloadable.meta_key = '_downloadable'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND meta_downloadable.meta_value = 'yes'
    ";

        $count = (int) $wpdb->get_var($wpdb->prepare($query, $since));

        return $count;
    }

    /**
     * Calculate the abandonment rate for carts containing subscription products within a timeframe.
     *
     * This helps analyze if subscription products have a higher or lower abandonment rate compared to normal products.
     *
     * @param int $days Number of past days to analyze.
     * @return float Abandonment rate percentage for subscription-product carts.
     *
     * @example
     * ```php
     * $subAbandonRate = WooCommerce::getSubscriptionProductCartAbandonmentRate(30);
     * ```
     */
    public static function getSubscriptionProductCartAbandonmentRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total carts with subscription products (both completed and abandoned)
        $totalQuery = "
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_sub ON meta_sub.post_id = meta_product.meta_value AND meta_sub.meta_key = '_subscription_period'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed', 'wc-completed')
          AND orders.post_date >= %s
          AND meta_sub.meta_value != ''
    ";

        // Abandoned carts with subscription products
        $abandonedQuery = "
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_sub ON meta_sub.post_id = meta_product.meta_value AND meta_sub.meta_key = '_subscription_period'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND meta_sub.meta_value != ''
    ";

        $totalCarts = (int) $wpdb->get_var($wpdb->prepare($totalQuery, $since));
        $abandonedCarts = (int) $wpdb->get_var($wpdb->prepare($abandonedQuery, $since));

        if ($totalCarts === 0) return 0.0;

        return round(($abandonedCarts / $totalCarts) * 100, 2);
    }

    /**
     * Get the total value of abandoned carts that include products with low stock status.
     *
     * This helps identify if low stock products contribute to cart abandonment.
     *
     * @param int $days Number of past days to analyze.
     * @return float Total potential revenue lost from carts with low stock products.
     *
     * @example
     * ```php
     * $lowStockAbandonValue = WooCommerce::getAbandonedCartValueWithLowStockProducts(30);
     * ```
     */
    public static function getAbandonedCartValueWithLowStockProducts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get low stock product IDs based on WooCommerce threshold
        $threshold = (int) get_option('woocommerce_notify_low_stock_amount', 2);
        $lowStockProductIds = [];

        $products = wc_get_products([
            'limit' => -1,
            'stock_status' => 'instock',
        ]);

        foreach ($products as $product) {
            if ($product->managing_stock() && $product->get_stock_quantity() <= $threshold) {
                $lowStockProductIds[] = $product->get_id();
            }
        }

        if (empty($lowStockProductIds)) return 0.0;

        // Prepare placeholders for SQL IN clause
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($lowStockProductIds), '%d'));

        // Query total order value of abandoned carts containing low stock products
        $query = "
        SELECT SUM(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS total_value
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        JOIN {$wpdb->postmeta} AS meta_total ON meta_total.post_id = orders.ID AND meta_total.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND meta_product.meta_value IN ($placeholders)
    ";

        $params = array_merge([$since], $lowStockProductIds);

        $totalValue = $wpdb->get_var($wpdb->prepare($query, ...$params));

        return $totalValue ? round((float)$totalValue, 2) : 0.0;
    }

    /**
     * Get the distribution of abandoned carts by device type (desktop, mobile, tablet).
     *
     * Requires WooCommerce plugin or custom meta tracking user device info at checkout.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with device types as keys and abandonment counts as values.
     *
     * @example
     * ```php
     * $deviceStats = WooCommerce::getAbandonedCartsByDeviceType(30);
     * ```
     */
    public static function getAbandonedCartsByDeviceType(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Assuming device type stored in postmeta with key '_device_type' (e.g., 'desktop', 'mobile', 'tablet')
        $query = "
        SELECT meta_device.meta_value AS device_type, COUNT(*) AS count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_device ON orders.ID = meta_device.post_id AND meta_device.meta_key = '_device_type'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY meta_device.meta_value
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $distribution = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];

        foreach ($results as $row) {
            $device = strtolower($row->device_type);
            if (isset($distribution[$device])) {
                $distribution[$device] = (int) $row->count;
            }
        }

        return $distribution;
    }

    /**
     * Identify the top 5 products most frequently found in abandoned carts within the given timeframe.
     *
     * Useful for targeting remarketing campaigns on products with high abandonment frequency.
     *
     * @param int $days Number of past days to analyze.
     * @return array Array of product IDs and their abandonment counts, ordered descending.
     *
     * @example
     * ```php
     * $topAbandonedProducts = WooCommerce::getTopAbandonedCartProducts(30);
     * ```
     */
    public static function getTopAbandonedCartProducts(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product IDs from abandoned carts and count occurrences
        $query = "
        SELECT meta_product.meta_value AS product_id, COUNT(*) AS abandon_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_product ON meta_product.order_item_id = items.order_item_id AND meta_product.meta_key = '_product_id'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY meta_product.meta_value
        ORDER BY abandon_count DESC
        LIMIT 5
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $topProducts = [];
        foreach ($results as $row) {
            $topProducts[(int)$row->product_id] = (int)$row->abandon_count;
        }

        return $topProducts;
    }

    /**
     * Calculate the cart abandonment recovery rate within a specified timeframe.
     *
     * This measures the percentage of abandoned carts that were later converted into completed orders.
     *
     * @param int $days Number of past days to analyze.
     * @return float Recovery rate as a percentage.
     *
     * @example
     * ```php
     * $recoveryRate = WooCommerce::getCartAbandonmentRecoveryRate(30);
     * ```
     */
    public static function getCartAbandonmentRecoveryRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count abandoned carts (pending/failed)
        $abandonedCount = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-pending', 'wc-failed')
          AND post_date >= %s
    ", $since));

        if ($abandonedCount === 0) return 0.0;

        // Count completed orders that originated from abandoned carts by matching customer ID and timestamps
        $completedCount = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT completed.ID)
        FROM {$wpdb->posts} AS abandoned
        JOIN {$wpdb->posts} AS completed ON
            completed.post_type = 'shop_order' 
            AND completed.post_status = 'wc-completed'
            AND completed.post_date > abandoned.post_date
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} AS meta_abandoned
                JOIN {$wpdb->postmeta} AS meta_completed ON meta_abandoned.meta_value = meta_completed.meta_value
                WHERE meta_abandoned.post_id = abandoned.ID
                  AND meta_abandoned.meta_key = '_customer_user'
                  AND meta_completed.post_id = completed.ID
                  AND meta_completed.meta_key = '_customer_user'
            )
        WHERE abandoned.post_type = 'shop_order'
          AND abandoned.post_status IN ('wc-pending', 'wc-failed')
          AND abandoned.post_date >= %s
    ", $since));

        return round(($completedCount / $abandonedCount) * 100, 2);
    }

    /**
     * Retrieve the top 5 coupon codes used in abandoned carts within a timeframe.
     *
     * Helps evaluate which coupons might not be incentivizing purchases effectively.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with coupon codes as keys and usage counts as values.
     *
     * @example
     * ```php
     * $topCoupons = WooCommerce::getTopCouponsInAbandonedCarts(30);
     * ```
     */
    public static function getTopCouponsInAbandonedCarts(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query coupon usage from abandoned carts
        $query = "
        SELECT meta_coupon.meta_value AS coupon_code, COUNT(*) AS usage_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_coupon ON orders.ID = meta_coupon.post_id AND meta_coupon.meta_key = '_used_coupons'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
        GROUP BY meta_coupon.meta_value
        ORDER BY usage_count DESC
        LIMIT 5
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $topCoupons = [];
        foreach ($results as $row) {
            $topCoupons[$row->coupon_code] = (int)$row->usage_count;
        }

        return $topCoupons;
    }

    /**
     * Get the average time customers spend on the checkout page before completing the order.
     *
     * Measures how long (in seconds) customers take from checkout page load to order placement.
     * Requires custom tracking of checkout start times stored as order meta '_checkout_start_time'.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average checkout duration in seconds.
     *
     * @example
     * ```php
     * $avgCheckoutTime = WooCommerce::getAverageCheckoutDuration(30);
     * ```
     */
    public static function getAverageCheckoutDuration(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query checkout start time and order date for completed orders with the meta key
        $query = "
        SELECT meta_start.meta_value AS checkout_start, orders.post_date AS order_date
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_start ON orders.ID = meta_start.post_id AND meta_start.meta_key = '_checkout_start_time'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND meta_start.meta_value IS NOT NULL
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        foreach ($results as $row) {
            $start = strtotime($row->checkout_start);
            $end = strtotime($row->order_date);

            if ($start !== false && $end !== false && $end >= $start) {
                $totalSeconds += ($end - $start);
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        return round($totalSeconds / $count, 2);
    }

    /**
     * Get the percentage of checkout sessions that failed due to payment gateway errors.
     *
     * Requires logging of failed payment attempts with meta key '_payment_gateway_error' attached to orders.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of failed payment attempts during checkout.
     *
     * @example
     * ```php
     * $failureRate = WooCommerce::getCheckoutPaymentFailureRate(30);
     * ```
     */
    public static function getCheckoutPaymentFailureRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count all checkout attempts (completed + failed)
        $totalQuery = "
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_date >= %s
          AND post_status IN ('wc-completed', 'wc-failed', 'wc-pending')
    ";

        // Count failed payment attempts indicated by presence of error meta
        $failedQuery = "
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_error ON orders.ID = meta_error.post_id AND meta_error.meta_key = '_payment_gateway_error'
        WHERE orders.post_date >= %s
          AND orders.post_status IN ('wc-failed', 'wc-pending')
          AND meta_error.meta_value != ''
    ";

        $totalCount = (int) $wpdb->get_var($wpdb->prepare($totalQuery, $since));
        $failedCount = (int) $wpdb->get_var($wpdb->prepare($failedQuery, $since));

        if ($totalCount === 0) return 0.0;

        return round(($failedCount / $totalCount) * 100, 2);
    }

    /**
     * Retrieve the most common billing countries for completed checkout orders within a timeframe.
     *
     * Useful for analyzing geographic distribution of successful checkouts.
     *
     * @param int $days Number of past days to analyze.
     * @param int $limit Number of top countries to return.
     * @return array Associative array of country codes and order counts.
     *
     * @example
     * ```php
     * $topBillingCountries = WooCommerce::getTopBillingCountries(30, 5);
     * ```
     */
    public static function getTopBillingCountries(int $days = 30, int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query billing country meta for completed orders and count occurrences
        $query = "
        SELECT meta_country.meta_value AS country_code, COUNT(*) AS order_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_country ON orders.ID = meta_country.post_id AND meta_country.meta_key = '_billing_country'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY meta_country.meta_value
        ORDER BY order_count DESC
        LIMIT %d
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since, $limit));

        $countries = [];
        foreach ($results as $row) {
            $countries[$row->country_code] = (int) $row->order_count;
        }

        return $countries;
    }

    /**
     * Calculate the average number of checkout attempts before successful order placement.
     *
     * Tracks how many times a customer attempts checkout (including failed/pending) before completing an order.
     * Requires '_customer_user' meta tracking user ID on orders.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average number of attempts per customer.
     *
     * @example
     * ```php
     * $avgAttempts = WooCommerce::getAverageCheckoutAttempts(30);
     * ```
     */
    public static function getAverageCheckoutAttempts(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get counts of all checkout attempts per customer user ID
        $attemptsQuery = "
        SELECT meta_user.meta_value AS user_id, COUNT(*) AS attempt_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_user ON orders.ID = meta_user.post_id AND meta_user.meta_key = '_customer_user'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_date >= %s
          AND orders.post_status IN ('wc-completed', 'wc-pending', 'wc-failed')
          AND meta_user.meta_value != '0'
        GROUP BY meta_user.meta_value
    ";

        // Get counts of successful orders per customer user ID
        $completedQuery = "
        SELECT meta_user.meta_value AS user_id, COUNT(*) AS completed_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_user ON orders.ID = meta_user.post_id AND meta_user.meta_key = '_customer_user'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_date >= %s
          AND orders.post_status = 'wc-completed'
          AND meta_user.meta_value != '0'
        GROUP BY meta_user.meta_value
    ";

        $attemptsResults = $wpdb->get_results($wpdb->prepare($attemptsQuery, $since));
        $completedResults = $wpdb->get_results($wpdb->prepare($completedQuery, $since));

        // Map user ID to completed order count
        $completedMap = [];
        foreach ($completedResults as $row) {
            $completedMap[$row->user_id] = (int)$row->completed_count;
        }

        $totalAttempts = 0;
        $totalCompleted = 0;

        foreach ($attemptsResults as $row) {
            $userId = $row->user_id;
            $attemptCount = (int)$row->attempt_count;
            $completedCount = $completedMap[$userId] ?? 0;

            // Only consider customers with at least one completed order
            if ($completedCount > 0) {
                $totalAttempts += $attemptCount;
                $totalCompleted += $completedCount;
            }
        }

        if ($totalCompleted === 0) return 0.0;

        return round($totalAttempts / $totalCompleted, 2);
    }

    /**
     * Get the frequency distribution of payment gateways used in completed checkouts.
     *
     * Useful for understanding customer payment preferences.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with payment gateway IDs as keys and usage counts as values.
     *
     * @example
     * ```php
     * $paymentGatewayUsage = WooCommerce::getPaymentGatewayUsageFrequency(30);
     * ```
     */
    public static function getPaymentGatewayUsageFrequency(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Payment method stored in postmeta '_payment_method'
        $query = "
        SELECT meta_method.meta_value AS payment_method, COUNT(*) AS usage_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_method ON orders.ID = meta_method.post_id AND meta_method.meta_key = '_payment_method'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY meta_method.meta_value
        ORDER BY usage_count DESC
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $usage = [];
        foreach ($results as $row) {
            $usage[$row->payment_method] = (int)$row->usage_count;
        }

        return $usage;
    }

    /**
     * Get the average number of items per checkout order within a specified timeframe.
     *
     * Helps understand typical cart size at checkout.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average items per completed order.
     *
     * @example
     * ```php
     * $avgItems = WooCommerce::getAverageItemsPerOrder(30);
     * ```
     */
    public static function getAverageItemsPerOrder(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total order items linked to completed orders within timeframe
        $query = "
        SELECT 
            COUNT(items.order_item_id) AS total_items,
            COUNT(DISTINCT orders.ID) AS total_orders
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON items.order_id = orders.ID AND items.order_item_type = 'line_item'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ";

        $result = $wpdb->get_row($wpdb->prepare($query, $since));

        if (!$result || $result->total_orders == 0) return 0.0;

        return round($result->total_items / $result->total_orders, 2);
    }

    /**
     * Calculate the percentage of guest checkouts versus registered user checkouts.
     *
     * Useful for understanding how many customers complete checkout without creating an account.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with 'guest' and 'registered' keys and their respective percentages.
     *
     * @example
     * ```php
     * $guestVsRegistered = WooCommerce::getGuestVsRegisteredCheckoutRatio(30);
     * ```
     */
    public static function getGuestVsRegisteredCheckoutRatio(int $days = 30): array
    {
        if (!self::guard()) return ['guest' => 0.0, 'registered' => 0.0];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total completed orders and how many belong to registered users (customer ID != 0)
        $query = "
        SELECT
            COUNT(*) AS total_orders,
            SUM(CASE WHEN meta_user.meta_value = '0' THEN 1 ELSE 0 END) AS guest_orders,
            SUM(CASE WHEN meta_user.meta_value != '0' THEN 1 ELSE 0 END) AS registered_orders
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_user ON orders.ID = meta_user.post_id AND meta_user.meta_key = '_customer_user'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ";

        $result = $wpdb->get_row($wpdb->prepare($query, $since));

        if (!$result || $result->total_orders == 0) {
            return ['guest' => 0.0, 'registered' => 0.0];
        }

        $guestPercent = round(($result->guest_orders / $result->total_orders) * 100, 2);
        $registeredPercent = round(($result->registered_orders / $result->total_orders) * 100, 2);

        return [
            'guest' => $guestPercent,
            'registered' => $registeredPercent,
        ];
    }

    /**
     * Calculate the average checkout cart value for abandoned (pending/failed) versus completed orders.
     *
     * Helps compare cart values and identify potential price points that lead to abandonment.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with 'abandoned' and 'completed' average cart values.
     *
     * @example
     * ```php
     * $avgValues = WooCommerce::getAverageCartValueAbandonedVsCompleted(30);
     * ```
     */
    public static function getAverageCartValueAbandonedVsCompleted(int $days = 30): array
    {
        if (!self::guard()) return ['abandoned' => 0.0, 'completed' => 0.0];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Average cart value for abandoned orders
        $abandonedQuery = "
        SELECT AVG(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS avg_abandoned
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
    ";

        // Average cart value for completed orders
        $completedQuery = "
        SELECT AVG(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS avg_completed
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ";

        $avgAbandoned = (float) $wpdb->get_var($wpdb->prepare($abandonedQuery, $since));
        $avgCompleted = (float) $wpdb->get_var($wpdb->prepare($completedQuery, $since));

        return [
            'abandoned' => round($avgAbandoned, 2),
            'completed' => round($avgCompleted, 2),
        ];
    }

    /**
     * Get the top 5 most common reasons for checkout failure from stored failure messages.
     *
     * Assumes failure reasons are logged as order meta '_checkout_failure_reason'.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array of failure reason messages and their counts.
     *
     * @example
     * ```php
     * $failureReasons = WooCommerce::getTopCheckoutFailureReasons(30);
     * ```
     */
    public static function getTopCheckoutFailureReasons(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query distinct failure reasons and count occurrences
        $query = "
        SELECT meta_reason.meta_value AS failure_reason, COUNT(*) AS count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_reason ON orders.ID = meta_reason.post_id AND meta_reason.meta_key = '_checkout_failure_reason'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-failed', 'wc-pending')
          AND orders.post_date >= %s
          AND meta_reason.meta_value != ''
        GROUP BY meta_reason.meta_value
        ORDER BY count DESC
        LIMIT 5
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $failureReasons = [];
        foreach ($results as $row) {
            $failureReasons[$row->failure_reason] = (int)$row->count;
        }

        return $failureReasons;
    }

    /**
     * Calculate the abandonment rate of checkout sessions over a period.
     *
     * Compares the number of initiated checkouts vs. completed orders.
     * Assumes '_checkout_start_time' meta is saved at checkout initiation.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of abandoned checkouts.
     *
     * @example
     * ```php
     * $abandonmentRate = WooCommerce::getCheckoutAbandonmentRate(30);
     * ```
     */
    public static function getCheckoutAbandonmentRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total checkout sessions initiated (orders with '_checkout_start_time' meta)
        $startedQuery = "
        SELECT COUNT(DISTINCT post_id) 
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_checkout_start_time'
          AND post_id IN (
              SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_date >= %s
          )
    ";

        // Count total completed orders in timeframe
        $completedQuery = "
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ";

        $startedCount = (int) $wpdb->get_var($wpdb->prepare($startedQuery, $since));
        $completedCount = (int) $wpdb->get_var($wpdb->prepare($completedQuery, $since));

        if ($startedCount === 0) return 0.0;

        // Calculate abandonment as proportion of started but not completed
        $abandonedCount = $startedCount - $completedCount;

        // Avoid negative abandonment due to data inconsistencies
        $abandonedCount = max(0, $abandonedCount);

        return round(($abandonedCount / $startedCount) * 100, 2);
    }

    /**
     * Retrieve average discount percentage applied during checkout on completed orders.
     *
     * Calculates how much on average customers save via coupons or manual discounts.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average discount percentage (0-100).
     *
     * @example
     * ```php
     * $avgDiscount = WooCommerce::getAverageCheckoutDiscountPercentage(30);
     * ```
     */
    public static function getAverageCheckoutDiscountPercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query subtotal and total paid from order meta for completed orders
        $query = "
        SELECT 
            AVG(
                CASE
                    WHEN CAST(meta_subtotal.meta_value AS DECIMAL(10,2)) > 0 THEN
                        (CAST(meta_subtotal.meta_value AS DECIMAL(10,2)) - CAST(meta_total.meta_value AS DECIMAL(10,2))) / CAST(meta_subtotal.meta_value AS DECIMAL(10,2)) * 100
                    ELSE 0
                END
            ) AS avg_discount_pct
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_subtotal ON orders.ID = meta_subtotal.post_id AND meta_subtotal.meta_key = '_order_subtotal'
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ";

        $avgDiscount = (float) $wpdb->get_var($wpdb->prepare($query, $since));

        return round($avgDiscount, 2);
    }

    /**
     * Get the average time taken for customers to abandon the checkout (from start to abandonment).
     *
     * Assumes '_checkout_start_time' is saved and abandoned checkouts have status 'wc-pending' or 'wc-failed'.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average abandonment time in minutes.
     *
     * @example
     * ```php
     * $avgAbandonTime = WooCommerce::getAverageCheckoutAbandonmentTime(30);
     * ```
     */
    public static function getAverageCheckoutAbandonmentTime(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select orders with abandonment status and checkout start time meta
        $query = "
        SELECT meta_start.meta_value AS start_time, orders.post_modified AS last_update
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_start ON orders.ID = meta_start.post_id AND meta_start.meta_key = '_checkout_start_time'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-pending', 'wc-failed')
          AND orders.post_date >= %s
          AND meta_start.meta_value IS NOT NULL
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        foreach ($results as $row) {
            $start = strtotime($row->start_time);
            $end = strtotime($row->last_update);

            if ($start !== false && $end !== false && $end >= $start) {
                $totalSeconds += ($end - $start);
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        // Return average abandonment time in minutes rounded to 2 decimals
        return round(($totalSeconds / $count) / 60, 2);
    }

    /**
     * Calculate the conversion rate from cart to completed checkout.
     *
     * Measures how many carts lead to a successful order completion within the timeframe.
     *
     * @param int $days Number of past days to analyze.
     * @return float Conversion rate percentage.
     *
     * @example
     * ```php
     * $conversionRate = WooCommerce::getCartToCheckoutConversionRate(30);
     * ```
     */
    public static function getCartToCheckoutConversionRate(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count distinct carts created (assuming carts saved as sessions or custom post type, here simplified)
        // Since WooCommerce does not store carts as posts by default, this method assumes you log carts as custom post type 'wc_cart'
        $cartCount = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'wc_cart'
          AND post_date >= %s
    ", $since));

        // Count completed orders in timeframe
        $completedCount = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($cartCount === 0) return 0.0;

        return round(($completedCount / $cartCount) * 100, 2);
    }

    /**
     * Get the distribution of payment methods used specifically in failed checkout attempts.
     *
     * Helps identify if payment gateways might be causing checkout failures.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with payment method IDs and their counts.
     *
     * @example
     * ```php
     * $failedPaymentMethods = WooCommerce::getFailedCheckoutPaymentMethods(30);
     * ```
     */
    public static function getFailedCheckoutPaymentMethods(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = "
        SELECT meta_method.meta_value AS payment_method, COUNT(*) AS failure_count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_method ON orders.ID = meta_method.post_id AND meta_method.meta_key = '_payment_method'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-failed', 'wc-pending')
          AND orders.post_date >= %s
        GROUP BY meta_method.meta_value
        ORDER BY failure_count DESC
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        $failures = [];
        foreach ($results as $row) {
            $failures[$row->payment_method] = (int)$row->failure_count;
        }

        return $failures;
    }

    /**
     * Get average customer metadata count per completed checkout order.
     *
     * Measures how many custom metadata fields (e.g. additional info) are saved per order at checkout.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average number of metadata entries per order.
     *
     * @example
     * ```php
     * $avgMetaCount = WooCommerce::getAverageOrderMetaCount(30);
     * ```
     */
    public static function getAverageOrderMetaCount(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total meta entries for completed orders in timeframe
        $queryMetaCount = "
        SELECT COUNT(*) AS total_meta
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.post_type = 'shop_order'
          AND p.post_status = 'wc-completed'
          AND p.post_date >= %s
    ";

        // Count total completed orders in timeframe
        $queryOrderCount = "
        SELECT COUNT(*) AS total_orders
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ";

        $totalMeta = (int) $wpdb->get_var($wpdb->prepare($queryMetaCount, $since));
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare($queryOrderCount, $since));

        if ($totalOrders === 0) return 0.0;

        return round($totalMeta / $totalOrders, 2);
    }

    /**
     * Calculate the average number of items in the cart at the time of checkout for completed orders.
     *
     * Helps understand typical cart size when customers successfully complete checkout.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average item count per completed order.
     *
     * @example
     * ```php
     * $avgItems = WooCommerce::getAverageCartItemsAtCheckout(30);
     * ```
     */
    public static function getAverageCartItemsAtCheckout(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query order IDs and their item counts by joining order items table
        $query = "
        SELECT oi.order_id, COUNT(oi.order_item_id) AS item_count
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        JOIN {$wpdb->posts} AS orders ON oi.order_id = orders.ID
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND oi.order_item_type = 'line_item'
        GROUP BY oi.order_id
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalItems = 0;
        $orderCount = count($results);

        foreach ($results as $row) {
            $totalItems += (int) $row->item_count;
        }

        return round($totalItems / $orderCount, 2);
    }

    /**
     * Retrieve the percentage of orders using express checkout methods (e.g., PayPal, Apple Pay).
     *
     * Useful to analyze customer preference for faster checkout options.
     *
     * @param int $days Number of past days to analyze.
     * @param array $expressMethods Array of payment method IDs considered as express (e.g., ['paypal', 'stripe_apple_pay']).
     * @return float Percentage of completed orders paid with express methods.
     *
     * @example
     * ```php
     * $expressCheckoutPercent = WooCommerce::getExpressCheckoutUsage(30, ['paypal', 'stripe_apple_pay']);
     * ```
     */
    public static function getExpressCheckoutUsage(int $days = 30, array $expressMethods = []): float
    {
        if (!self::guard() || empty($expressMethods)) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Prepare placeholders for IN clause dynamically
        $placeholders = implode(',', array_fill(0, count($expressMethods), '%s'));

        // Count completed orders with express payment methods
        $queryExpress = "
        SELECT COUNT(*) FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_payment_method'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND meta.meta_value IN ($placeholders)
    ";

        // Count all completed orders in timeframe
        $queryTotal = "
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ";

        $paramsExpress = array_merge([$since], $expressMethods);

        $expressCount = (int) $wpdb->get_var($wpdb->prepare($queryExpress, ...$paramsExpress));
        $totalCount = (int) $wpdb->get_var($wpdb->prepare($queryTotal, $since));

        if ($totalCount === 0) return 0.0;

        return round(($expressCount / $totalCount) * 100, 2);
    }

    /**
     * Calculate the average checkout processing time (from payment initiated to order completion).
     *
     * Assumes '_payment_initiated_time' meta is saved when payment starts.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average processing time in minutes.
     *
     * @example
     * ```php
     * $avgProcessingTime = WooCommerce::getAverageCheckoutProcessingTime(30);
     * ```
     */
    public static function getAverageCheckoutProcessingTime(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select completed orders with payment initiation time
        $query = "
        SELECT meta_payment.meta_value AS payment_time, orders.post_modified AS completed_time
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_payment ON orders.ID = meta_payment.post_id AND meta_payment.meta_key = '_payment_initiated_time'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND meta_payment.meta_value IS NOT NULL
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        foreach ($results as $row) {
            $paymentStart = strtotime($row->payment_time);
            $completed = strtotime($row->completed_time);

            if ($paymentStart !== false && $completed !== false && $completed >= $paymentStart) {
                $totalSeconds += ($completed - $paymentStart);
                $count++;
            }
        }

        if ($count === 0) return 0.0;

        return round(($totalSeconds / $count) / 60, 2); // Convert seconds to minutes
    }

    /**
     * Get percentage of orders where customers used guest checkout vs. registered accounts.
     *
     * Useful to analyze customer behavior and encourage account creation.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with 'guest' and 'registered' percentages.
     *
     * @example
     * ```php
     * $checkoutTypes = WooCommerce::getGuestVsRegisteredCheckout(30);
     * ```
     */
    public static function getGuestVsRegisteredCheckout(int $days = 30): array
    {
        if (!self::guard()) return ['guest' => 0.0, 'registered' => 0.0];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count total completed orders in timeframe
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return ['guest' => 0.0, 'registered' => 0.0];

        // Count completed orders by registered users (post_author != 0)
        $registeredOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_author != 0
          AND post_date >= %s
    ", $since));

        $guestOrders = $totalOrders - $registeredOrders;

        return [
            'guest'      => round(($guestOrders / $totalOrders) * 100, 2),
            'registered' => round(($registeredOrders / $totalOrders) * 100, 2),
        ];
    }

    /**
     * Calculate the average number of coupon codes applied per completed checkout.
     *
     * Helps understand discount usage trends.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average count of coupons used per completed order.
     *
     * @example
     * ```php
     * $avgCoupons = WooCommerce::getAverageCouponsUsedPerCheckout(30);
     * ```
     */
    public static function getAverageCouponsUsedPerCheckout(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query count of coupons used per order
        $query = "
        SELECT orders.ID, COUNT(coupon.order_item_id) AS coupon_count
        FROM {$wpdb->posts} AS orders
        LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS coupon ON orders.ID = coupon.order_id AND coupon.order_item_type = 'coupon'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY orders.ID
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalCoupons = 0;
        $orderCount = count($results);

        foreach ($results as $row) {
            $totalCoupons += (int) $row->coupon_count;
        }

        return round($totalCoupons / $orderCount, 2);
    }

    /**
     * Get percentage of completed checkouts with applied gift cards.
     *
     * Assumes gift cards are tracked as coupons with a meta key '_gift_card' set to 'yes'.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of completed orders using gift cards.
     *
     * @example
     * ```php
     * $giftCardUsage = WooCommerce::getGiftCardUsagePercentage(30);
     * ```
     */
    public static function getGiftCardUsagePercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Subquery: get order IDs with gift card coupons applied
        $giftCardOrdersQuery = "
        SELECT DISTINCT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON order_items.order_item_id = meta.order_item_id
        WHERE order_items.order_item_type = 'coupon'
          AND meta.meta_key = '_gift_card'
          AND meta.meta_value = 'yes'
    ";

        // Count total completed orders
        $totalOrdersQuery = "
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ";

        // Count gift card orders within timeframe
        $giftCardCountQuery = "
        SELECT COUNT(*) FROM ({$giftCardOrdersQuery}) AS gift_card_orders
        JOIN {$wpdb->posts} orders ON gift_card_orders.order_id = orders.ID
        WHERE orders.post_date >= %s
          AND orders.post_status = 'wc-completed'
    ";

        $totalOrders = (int) $wpdb->get_var($wpdb->prepare($totalOrdersQuery, $since));
        if ($totalOrders === 0) return 0.0;

        $giftCardOrders = (int) $wpdb->get_var($wpdb->prepare($giftCardCountQuery, $since));

        return round(($giftCardOrders / $totalOrders) * 100, 2);
    }

    /**
     * Retrieve the percentage of orders that used a specific shipping method at checkout.
     *
     * Useful to understand shipping preferences and optimize shipping options.
     *
     * @param string $shippingMethodId The shipping method ID to check (e.g., 'flat_rate:1').
     * @param int $days Number of past days to analyze.
     * @return float Percentage of completed orders using the specified shipping method.
     *
     * @example
     * ```php
     * $shippingUsage = WooCommerce::getShippingMethodUsage('flat_rate:1', 30);
     * ```
     */
    public static function getShippingMethodUsage(string $shippingMethodId, int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count completed orders with the specified shipping method
        $queryShipping = "
        SELECT COUNT(*) FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_shipping ON orders.ID = meta_shipping.post_id AND meta_shipping.meta_key = '_shipping_method'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND meta_shipping.meta_value = %s
    ";

        // Count total completed orders
        $queryTotal = "
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ";

        $shippingCount = (int) $wpdb->get_var($wpdb->prepare($queryShipping, $since, $shippingMethodId));
        $totalCount = (int) $wpdb->get_var($wpdb->prepare($queryTotal, $since));

        if ($totalCount === 0) return 0.0;

        return round(($shippingCount / $totalCount) * 100, 2);
    }

    /**
     * Get the percentage of completed orders with multiple shipping addresses used.
     *
     * Useful to analyze how often customers split shipments in a single order.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of orders with multiple shipping addresses.
     *
     * @example
     * ```php
     * $multiShipPercent = WooCommerce::getMultipleShippingAddressesUsage(30);
     * ```
     */
    public static function getMultipleShippingAddressesUsage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get all completed orders in timeframe
        $orders = $wpdb->get_results($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if (empty($orders)) return 0.0;

        $ordersWithMultipleAddresses = 0;

        foreach ($orders as $order) {
            $shippingAddresses = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT meta_value)
            FROM {$wpdb->postmeta}
            WHERE post_id = %d
              AND meta_key LIKE '_shipping_address_%'
        ", $order->ID));

            if ($shippingAddresses > 1) {
                $ordersWithMultipleAddresses++;
            }
        }

        return round(($ordersWithMultipleAddresses / count($orders)) * 100, 2);
    }

    /**
     * Calculate average number of payment retries before successful checkout completion.
     *
     * Assumes retry count is saved as order meta '_payment_retry_count'.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average retry attempts per completed order.
     *
     * @example
     * ```php
     * $avgRetries = WooCommerce::getAveragePaymentRetries(30);
     * ```
     */
    public static function getAveragePaymentRetries(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = "
        SELECT meta.meta_value
        FROM {$wpdb->posts} AS orders
        LEFT JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_payment_retry_count'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ";

        $results = $wpdb->get_col($wpdb->prepare($query, $since));

        if (empty($results)) return 0.0;

        $totalRetries = 0;
        $count = 0;

        foreach ($results as $retry) {
            $retryCount = (int) $retry;
            $totalRetries += $retryCount;
            $count++;
        }

        return $count > 0 ? round($totalRetries / $count, 2) : 0.0;
    }

    /**
     * Calculate the rate of abandoned checkouts that resumed and completed within a given timeframe.
     *
     * Requires tracking abandoned carts with meta '_abandoned_checkout_time' and matching completed orders by user/email.
     *
     * @param int $days Number of past days to analyze.
     * @param int $resumeWindow Minutes within which abandonment was resumed.
     * @return float Percentage of abandoned checkouts resumed and completed.
     *
     * @example
     * ```php
     * $resumedRate = WooCommerce::getAbandonedCheckoutResumeRate(30, 60);
     * ```
     */
    public static function getAbandonedCheckoutResumeRate(int $days = 30, int $resumeWindow = 60): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get abandoned checkouts in timeframe
        $abandonedCheckouts = $wpdb->get_results($wpdb->prepare("
        SELECT post_id, meta_value AS abandoned_time
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_abandoned_checkout_time'
          AND meta_value >= %s
    ", $sinceDate));

        if (empty($abandonedCheckouts)) return 0.0;

        $resumedCount = 0;

        foreach ($abandonedCheckouts as $abandoned) {
            $abandonedTime = strtotime($abandoned->abandoned_time);
            if (!$abandonedTime) continue;

            // Check if there's a completed order by same user/email after abandoned time within resume window
            $order = $wpdb->get_var($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status = 'wc-completed'
              AND post_date >= %s
              AND post_date <= %s
              AND post_author = (
                  SELECT post_author FROM {$wpdb->posts} WHERE ID = %d
              )
            LIMIT 1
        ",
                date('Y-m-d H:i:s', $abandonedTime),
                date('Y-m-d H:i:s', $abandonedTime + $resumeWindow * 60),
                $abandoned->post_id));

            if ($order) {
                $resumedCount++;
            }
        }

        return round(($resumedCount / count($abandonedCheckouts)) * 100, 2);
    }

    /**
     * Get the percentage of orders that were refunded partially or fully in the given timeframe.
     *
     * Useful for tracking refund trends and potential product or service issues.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of refunded orders.
     *
     * @example
     * ```php
     * $refundRate = WooCommerce::getRefundedOrdersPercentage(30);
     * ```
     */
    public static function getRefundedOrdersPercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders in timeframe
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return 0.0;

        // Count orders with refunds (woocommerce_order_items of type 'refund')
        $refundedOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT order_id) FROM {$wpdb->prefix}woocommerce_order_items AS oi
        JOIN {$wpdb->posts} AS orders ON oi.order_id = orders.ID
        WHERE oi.order_item_type = 'refund'
          AND orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        return round(($refundedOrders / $totalOrders) * 100, 2);
    }

    /**
     * Get the distribution of orders by payment method in the specified timeframe.
     *
     * Returns an associative array of payment method IDs and their respective order counts and percentages.
     *
     * @param int $days Number of past days to analyze.
     * @return array Example: ['paypal' => ['count' => 50, 'percent' => 40.0], 'stripe' => [...]]
     *
     * @example
     * ```php
     * $paymentDistribution = WooCommerce::getOrderDistributionByPaymentMethod(30);
     * ```
     */
    public static function getOrderDistributionByPaymentMethod(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders count
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return [];

        // Get payment method meta counts
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta.meta_value AS payment_method, COUNT(*) AS count
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_payment_method'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY payment_method
    ", $since));

        $distribution = [];

        foreach ($results as $row) {
            $distribution[$row->payment_method] = [
                'count' => (int) $row->count,
                'percent' => round(($row->count / $totalOrders) * 100, 2),
            ];
        }

        return $distribution;
    }

    /**
     * Get the median order total for completed orders in a given timeframe.
     *
     * Provides insight into the "middle" order value, which can be less skewed than the average.
     *
     * @param int $days Number of past days to analyze.
     * @return float Median order total.
     *
     * @example
     * ```php
     * $medianOrderTotal = WooCommerce::getMedianOrderTotal(30);
     * ```
     */
    public static function getMedianOrderTotal(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Retrieve all order totals sorted
        $orderTotals = $wpdb->get_col($wpdb->prepare("
        SELECT CAST(meta.meta_value AS DECIMAL(10,2)) AS order_total
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        ORDER BY order_total ASC
    ", $since));

        $count = count($orderTotals);
        if ($count === 0) return 0.0;

        $middle = (int) floor(($count - 1) / 2);

        if ($count % 2) {
            // Odd count, median is middle element
            return round((float) $orderTotals[$middle], 2);
        } else {
            // Even count, median is average of two middle elements
            return round(((float) $orderTotals[$middle] + (float) $orderTotals[$middle + 1]) / 2, 2);
        }
    }

    /**
     * Get the top N customers by total spend in completed orders within a timeframe.
     *
     * Returns an associative array keyed by user ID with total spend amounts.
     *
     * @param int $topN Number of top customers to retrieve.
     * @param int $days Number of past days to analyze.
     * @return array Example: [123 => 1500.00, 456 => 1200.50, ...]
     *
     * @example
     * ```php
     * $topCustomers = WooCommerce::getTopCustomersBySpend(5, 90);
     * ```
     */
    public static function getTopCustomersBySpend(int $topN = 5, int $days = 90): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Aggregate total spend by user_id for completed orders with post_author > 0 (registered users)
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT orders.post_author AS user_id, SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_spent
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND orders.post_author > 0
        GROUP BY orders.post_author
        ORDER BY total_spent DESC
        LIMIT %d
    ", $since, $topN));

        $topCustomers = [];

        foreach ($results as $row) {
            $topCustomers[(int) $row->user_id] = round((float) $row->total_spent, 2);
        }

        return $topCustomers;
    }

    /**
     * Get the average time (in hours) between order creation and payment completion for completed orders.
     *
     * Useful for analyzing payment processing delays.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average payment processing time in hours.
     *
     * @example
     * ```php
     * $avgPaymentTime = WooCommerce::getAveragePaymentProcessingTime(30);
     * ```
     */
    public static function getAveragePaymentProcessingTime(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Fetch order ID, post_date (created), and payment completed timestamp stored as meta '_paid_date'
        $orders = $wpdb->get_results($wpdb->prepare("
        SELECT orders.ID, orders.post_date, paid_meta.meta_value AS paid_date
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS paid_meta ON orders.ID = paid_meta.post_id AND paid_meta.meta_key = '_paid_date'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND paid_meta.meta_value IS NOT NULL
    ", $since));

        if (empty($orders)) return 0.0;

        $totalSeconds = 0;
        $count = 0;

        foreach ($orders as $order) {
            $created = strtotime($order->post_date);
            $paid = strtotime($order->paid_date);

            if ($paid !== false && $created !== false && $paid >= $created) {
                $totalSeconds += ($paid - $created);
                $count++;
            }
        }

        return $count > 0 ? round($totalSeconds / $count / 3600, 2) : 0.0; // Convert to hours
    }

    /**
     * Get the count and percentage of completed orders that used coupons within a timeframe.
     *
     * Helps track coupon usage trends.
     *
     * @param int $days Number of past days to analyze.
     * @return array ['count' => int, 'percent' => float]
     *
     * @example
     * ```php
     * $couponStats = WooCommerce::getCouponUsageStats(30);
     * ```
     */
    public static function getCouponUsageStats(int $days = 30): array
    {
        if (!self::guard()) return ['count' => 0, 'percent' => 0.0];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders count
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return ['count' => 0, 'percent' => 0.0];

        // Count orders with at least one coupon applied (woocommerce_order_items with type 'coupon')
        $couponOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT order_id) FROM {$wpdb->prefix}woocommerce_order_items AS oi
        JOIN {$wpdb->posts} AS orders ON oi.order_id = orders.ID
        WHERE oi.order_item_type = 'coupon'
          AND orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        return [
            'count' => $couponOrders,
            'percent' => round(($couponOrders / $totalOrders) * 100, 2)
        ];
    }

    /**
     * Retrieve the average number of refunds per refunded order in a given timeframe.
     *
     * Provides insight into refund frequency for orders that were refunded.
     *
     * @param int $days Number of past days to analyze.
     * @return float Average refunds per refunded order.
     *
     * @example
     * ```php
     * $avgRefundsPerOrder = WooCommerce::getAverageRefundsPerOrder(30);
     * ```
     */
    public static function getAverageRefundsPerOrder(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Count distinct refunded orders
        $refundedOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT order_id)
        FROM {$wpdb->prefix}woocommerce_order_items
        WHERE order_item_type = 'refund'
          AND order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status = 'wc-completed'
              AND post_date >= %s
          )
    ", $since));

        if ($refundedOrders === 0) return 0.0;

        // Count total refunds
        $totalRefunds = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_items
        WHERE order_item_type = 'refund'
          AND order_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status = 'wc-completed'
              AND post_date >= %s
          )
    ", $since));

        return round($totalRefunds / $refundedOrders, 2);
    }

    /**
     * Get the percentage of completed orders that were placed by guest users in a timeframe.
     *
     * Useful for understanding guest checkout adoption.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of guest orders.
     *
     * @example
     * ```php
     * $guestOrderPercent = WooCommerce::getGuestOrderPercentage(30);
     * ```
     */
    public static function getGuestOrderPercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders count
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'shop_order' 
          AND post_status = 'wc-completed' 
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return 0.0;

        // Count orders with post_author = 0 (guests)
        $guestOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_type = 'shop_order' 
          AND post_status = 'wc-completed' 
          AND post_date >= %s
          AND post_author = 0
    ", $since));

        return round(($guestOrders / $totalOrders) * 100, 2);
    }

    /**
     * Get the average number of orders placed per customer.
     *
     * Useful for understanding customer buying frequency.
     *
     * @return float Average orders per customer.
     *
     * @example
     * ```php
     * $avgOrders = WooCommerce::getAverageOrdersPerCustomer();
     * ```
     */
    public static function getAverageOrdersPerCustomer(): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        // Total number of completed or processing orders by registered users
        $totalOrders = (int) $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
          AND post_author > 0
    ");

        // Total unique customers who placed orders
        $totalCustomers = self::getTotalCustomers();

        if ($totalCustomers === 0) return 0.0;

        return round($totalOrders / $totalCustomers, 2);
    }

    /**
     * Get the customer IDs who have spent the most in completed orders within a timeframe.
     *
     * Returns an array of customer IDs ordered by total spending descending.
     *
     * @param int $limit Number of top customers to return.
     * @param int $days Number of past days to analyze.
     * @return array Array of customer user IDs.
     *
     * @example
     * ```php
     * $topCustomers = WooCommerce::getTopSpendingCustomers(10, 30);
     * ```
     */
    public static function getTopSpendingCustomers(int $limit = 10, int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum _order_total meta grouped by post_author (customer ID)
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT post_author AS customer_id, SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_spent
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND orders.post_author > 0
        GROUP BY post_author
        ORDER BY total_spent DESC
        LIMIT %d
    ", $since, $limit));

        $customerIds = [];

        foreach ($results as $row) {
            $customerIds[] = (int) $row->customer_id;
        }

        return $customerIds;
    }

    /**
     * Get the percentage of customers who placed their first order within the last X days.
     *
     * This helps measure new customer acquisition over a timeframe.
     *
     * @param int $days Number of past days to analyze.
     * @return float Percentage of new customers.
     *
     * @example
     * ```php
     * $newCustomerPercent = WooCommerce::getNewCustomerPercentage(30);
     * ```
     */
    public static function getNewCustomerPercentage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total customers with at least one completed order ever
        $totalCustomers = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT post_author)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_author > 0
    ");

        if ($totalCustomers === 0) return 0.0;

        // Customers whose first completed order was within the last X days
        $newCustomers = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM (
            SELECT post_author, MIN(post_date) AS first_order_date
            FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
              AND post_status = 'wc-completed'
              AND post_author > 0
            GROUP BY post_author
            HAVING first_order_date >= %s
        ) AS subquery
    ", $since));

        return round(($newCustomers / $totalCustomers) * 100, 2);
    }

    /**
     * Get average customer lifetime value (LTV).
     *
     * LTV is average total spending per customer across all completed orders.
     *
     * @return float Average lifetime value per customer.
     *
     * @example
     * ```php
     * $avgLTV = WooCommerce::getAverageCustomerLifetimeValue();
     * ```
     */
    public static function getAverageCustomerLifetimeValue(): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        // Total revenue from completed orders grouped by customer
        $totalSpentByCustomers = $wpdb->get_results("
        SELECT post_author AS customer_id, SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_spent
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_author > 0
        GROUP BY post_author
    ");

        if (empty($totalSpentByCustomers)) return 0.0;

        $sum = 0;
        $count = count($totalSpentByCustomers);

        foreach ($totalSpentByCustomers as $customer) {
            $sum += floatval($customer->total_spent);
        }

        return round($sum / $count, 2);
    }

    /**
     * Get the top customers by average order value (AOV) within a timeframe.
     *
     * Returns an array of customer IDs sorted by their average order value descending.
     *
     * @param int $limit Number of customers to return.
     * @param int $days Number of past days to analyze.
     * @return array Array of customer IDs.
     *
     * @example
     * ```php
     * $topCustomersByAOV = WooCommerce::getTopCustomersByAverageOrderValue(10, 30);
     * ```
     */
    public static function getTopCustomersByAverageOrderValue(int $limit = 10, int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Calculate average order value per customer in the given timeframe
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT
            post_author AS customer_id,
            AVG(CAST(meta.meta_value AS DECIMAL(10,2))) AS avg_order_value
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta ON orders.ID = meta.post_id AND meta.meta_key = '_order_total'
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND orders.post_author > 0
        GROUP BY post_author
        ORDER BY avg_order_value DESC
        LIMIT %d
    ", $since, $limit));

        $customerIds = [];

        foreach ($results as $row) {
            $customerIds[] = (int) $row->customer_id;
        }

        return $customerIds;
    }

    /**
     * Get customers with the longest gap between their first and last orders.
     *
     * Useful for identifying loyal or repeat customers with long-term engagement.
     *
     * @param int $limit Number of customers to return.
     * @return array Array of customer IDs.
     *
     * @example
     * ```php
     * $loyalCustomers = WooCommerce::getCustomersWithLongestOrderGap(10);
     * ```
     */
    public static function getCustomersWithLongestOrderGap(int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Select customers with their first and last order dates and calculate the difference
        $results = $wpdb->get_results("
        SELECT
            post_author AS customer_id,
            DATEDIFF(MAX(post_date), MIN(post_date)) AS order_gap_days
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
          AND post_author > 0
        GROUP BY post_author
        ORDER BY order_gap_days DESC
        LIMIT {$limit}
    ");

        $customerIds = [];

        foreach ($results as $row) {
            $customerIds[] = (int) $row->customer_id;
        }

        return $customerIds;
    }

    /**
     * Get the distribution of customers by number of orders placed.
     *
     * Returns an associative array where keys are order counts and values are number of customers with that many orders.
     *
     * @return array Distribution of customers by order count.
     *
     * @example
     * ```php
     * $distribution = WooCommerce::getCustomerOrderCountDistribution();
     * ```
     */
    public static function getCustomerOrderCountDistribution(): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Get count of orders grouped by customer
        $results = $wpdb->get_results("
        SELECT post_author AS customer_id, COUNT(*) AS order_count
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
          AND post_author > 0
        GROUP BY post_author
    ");

        $distribution = [];

        foreach ($results as $row) {
            $count = (int) $row->order_count;
            if (!isset($distribution[$count])) {
                $distribution[$count] = 0;
            }
            $distribution[$count]++;
        }

        ksort($distribution); // Sort by order count ascending

        return $distribution;
    }

    /**
     * Get the total amount refunded to a customer across all orders.
     *
     * This includes all refunded amounts recorded in order meta.
     *
     * @param int $customerId WooCommerce customer user ID.
     * @return float Total refunded amount.
     *
     * @example
     * ```php
     * $totalRefunded = WooCommerce::getTotalRefundedAmountForCustomer(123);
     * ```
     */
    public static function getTotalRefundedAmountForCustomer(int $customerId): float
    {
        if (!self::guard() || $customerId <= 0) return 0.0;

        global $wpdb;

        // Get order IDs for this customer
        $orderIds = $wpdb->get_col($wpdb->prepare("
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_author = %d
    ", $customerId));

        if (empty($orderIds)) return 0.0;

        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($orderIds), '%d'));

        // Sum refunded amounts from order meta (_refund_amount)
        $query = "
        SELECT SUM(CAST(meta_value AS DECIMAL(10,2))) 
        FROM {$wpdb->postmeta} 
        WHERE post_id IN ($placeholders)
          AND meta_key = '_refund_amount'
    ";

        $totalRefunded = $wpdb->get_var($wpdb->prepare($query, ...$orderIds));

        return round(floatval($totalRefunded), 2);
    }

    /**
     * Get customers who have not logged in within the last X days.
     *
     * Useful for identifying dormant customers for re-engagement campaigns.
     *
     * @param int $days Number of past days to check for inactivity.
     * @return array Array of customer user IDs who are inactive.
     *
     * @example
     * ```php
     * $inactiveCustomers = WooCommerce::getInactiveCustomersByLogin(90);
     * ```
     */
    public static function getInactiveCustomersByLogin(int $days = 90): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get users with WooCommerce customer role
        $users = get_users(['role' => 'customer', 'fields' => ['ID']]);

        if (empty($users)) return [];

        $inactiveCustomers = [];

        foreach ($users as $user) {
            // Get last login timestamp stored as user meta (if plugin exists)
            $lastLogin = get_user_meta($user->ID, 'last_login', true);

            if (empty($lastLogin) || strtotime($lastLogin) < strtotime($cutoffDate)) {
                $inactiveCustomers[] = $user->ID;
            }
        }

        return $inactiveCustomers;
    }

    /**
     * Calculate the average time (in minutes) a customer takes to complete checkout
     * after adding the first item to their cart.
     *
     * Requires tracking 'first_add_to_cart' timestamp per customer (custom implementation needed).
     *
     * @param int $customerId WooCommerce customer user ID.
     * @return float Average time in minutes or 0 if not computable.
     *
     * @example
     * ```php
     * $avgCheckoutTime = WooCommerce::getAverageCheckoutCompletionTime(123);
     * ```
     */
    public static function getAverageCheckoutCompletionTime(int $customerId): float
    {
        if (!self::guard() || $customerId <= 0) return 0.0;

        global $wpdb;

        // Retrieve orders for this customer with their created date and a hypothetical meta key for first cart add timestamp
        $orders = $wpdb->get_results($wpdb->prepare("
        SELECT orders.ID, orders.post_date AS order_date,
            (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = orders.ID AND meta_key = '_first_add_to_cart') AS first_add_to_cart
        FROM {$wpdb->posts} AS orders
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-completed', 'wc-processing')
          AND orders.post_author = %d
    ", $customerId));

        if (empty($orders)) return 0.0;

        $totalMinutes = 0;
        $count = 0;

        foreach ($orders as $order) {
            if (empty($order->first_add_to_cart)) continue;

            $firstAdd = strtotime($order->first_add_to_cart);
            $orderTime = strtotime($order->order_date);

            if ($orderTime > $firstAdd) {
                $totalMinutes += ($orderTime - $firstAdd) / 60;
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0.0;
    }

    /**
     * Get the number of customers who have used coupons at least once.
     *
     * Helps analyze coupon usage among customer base.
     *
     * @return int Count of customers who have used coupons.
     *
     * @example
     * ```php
     * $couponUsers = WooCommerce::getCustomersUsingCoupons();
     * ```
     */
    public static function getCustomersUsingCoupons(): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        // Get distinct post_authors from orders where coupon was used (coupon meta exists)
        $customerIds = $wpdb->get_col("
        SELECT DISTINCT orders.post_author
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
          AND items.order_item_type = 'coupon'
          AND orders.post_author > 0
    ");

        return count($customerIds);
    }

    /**
     * Get the total discount amount given via coupons in the last X days.
     *
     * Aggregates discount amounts from all completed orders using coupons.
     *
     * @param int $days Number of past days to consider.
     * @return float Total discount amount.
     *
     * @example
     * ```php
     * $totalDiscount = WooCommerce::getTotalCouponDiscountLastDays(30);
     * ```
     */
    public static function getTotalCouponDiscountLastDays(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Sum the order item meta '_discount_amount' for coupon items in orders within timeframe
        $query = $wpdb->prepare("
        SELECT SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND meta.meta_key = 'discount_amount'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since);

        $totalDiscount = $wpdb->get_var($query);

        return round(floatval($totalDiscount), 2);
    }

    /**
     * Get the top N coupons by usage count in completed orders.
     *
     * Useful for identifying most effective or popular coupons.
     *
     * @param int $limit Number of coupons to return.
     * @return array Associative array of coupon code => usage count.
     *
     * @example
     * ```php
     * $topCoupons = WooCommerce::getTopCouponsByUsage(5);
     * ```
     */
    public static function getTopCouponsByUsage(int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Get coupon usage count grouped by coupon code
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT items.order_item_name AS coupon_code, COUNT(*) AS usage_count
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND orders.post_status = 'wc-completed'
        GROUP BY items.order_item_name
        ORDER BY usage_count DESC
        LIMIT %d
    ", $limit));

        $coupons = [];

        foreach ($results as $row) {
            $coupons[$row->coupon_code] = (int) $row->usage_count;
        }

        return $coupons;
    }

    /**
     * Get the average discount percentage applied by coupons per order.
     *
     * Calculates the ratio of coupon discount amount to order total.
     *
     * @param int $days Number of past days to consider.
     * @return float Average discount percentage (0-100).
     *
     * @example
     * ```php
     * $avgDiscountPercent = WooCommerce::getAverageCouponDiscountPercent(30);
     * ```
     */
    public static function getAverageCouponDiscountPercent(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Fetch orders with coupons and their total and discount amounts
        $ordersWithCoupons = $wpdb->get_results($wpdb->prepare("
        SELECT
            orders.ID,
            CAST(meta_total.meta_value AS DECIMAL(10,2)) AS order_total,
            CAST(SUM(CAST(meta_discount.meta_value AS DECIMAL(10,2))) ) AS coupon_discount
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id AND items.order_item_type = 'coupon'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta_discount ON items.order_item_id = meta_discount.order_item_id AND meta_discount.meta_key = 'discount_amount'
        WHERE orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY orders.ID, order_total
    ", $since));

        if (empty($ordersWithCoupons)) return 0.0;

        $totalPercent = 0.0;
        $count = 0;

        foreach ($ordersWithCoupons as $order) {
            if ($order->order_total > 0) {
                $percent = ($order->coupon_discount / $order->order_total) * 100;
                $totalPercent += $percent;
                $count++;
            }
        }

        return $count > 0 ? round($totalPercent / $count, 2) : 0.0;
    }

    /**
     * Get the total number of unique customers who used coupons in the last X days.
     *
     * Helps measure coupon reach among customers.
     *
     * @param int $days Number of past days to consider.
     * @return int Count of unique customers using coupons.
     *
     * @example
     * ```php
     * $uniqueCouponUsers = WooCommerce::getUniqueCustomersUsingCouponsLastDays(30);
     * ```
     */
    public static function getUniqueCustomersUsingCouponsLastDays(int $days = 30): int
    {
        if (!self::guard()) return 0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Select distinct post_author for orders that used coupons in timeframe
        $query = $wpdb->prepare("
        SELECT COUNT(DISTINCT orders.post_author)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND items.order_item_type = 'coupon'
          AND orders.post_author > 0
    ", $since);

        return (int) $wpdb->get_var($query);
    }

    /**
     * Get the total discount amount given by a specific coupon code across all completed orders.
     *
     * Useful for evaluating individual coupon impact.
     *
     * @param string $couponCode Coupon code to check.
     * @return float Total discount amount.
     *
     * @example
     * ```php
     * $couponDiscount = WooCommerce::getTotalDiscountByCouponCode('SUMMER21');
     * ```
     */
    public static function getTotalDiscountByCouponCode(string $couponCode): float
    {
        if (!self::guard() || empty($couponCode)) return 0.0;

        global $wpdb;

        // Sum discount_amount meta for specific coupon code on completed orders
        $query = $wpdb->prepare("
        SELECT SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND items.order_item_name = %s
          AND meta.meta_key = 'discount_amount'
          AND orders.post_status = 'wc-completed'
    ", $couponCode);

        $totalDiscount = $wpdb->get_var($query);

        return round(floatval($totalDiscount), 2);
    }

    /**
     * Calculate the redemption rate of a coupon code relative to the total customers.
     *
     * Redemption rate = (Number of unique customers who used coupon) / (Total number of customers) * 100
     *
     * @param string $couponCode Coupon code.
     * @return float Redemption rate as a percentage.
     *
     * @example
     * ```php
     * $redemptionRate = WooCommerce::getCouponRedemptionRate('BLACKFRIDAY');
     * ```
     */
    public static function getCouponRedemptionRate(string $couponCode): float
    {
        if (!self::guard() || empty($couponCode)) return 0.0;

        global $wpdb;

        // Count unique customers who used this coupon
        $uniqueUsersQuery = $wpdb->prepare("
        SELECT COUNT(DISTINCT orders.post_author)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE items.order_item_type = 'coupon'
          AND items.order_item_name = %s
          AND orders.post_status = 'wc-completed'
          AND orders.post_author > 0
    ", $couponCode);

        $uniqueUsers = (int) $wpdb->get_var($uniqueUsersQuery);

        // Count total customers with 'customer' role
        $totalCustomers = count(get_users(['role' => 'customer', 'fields' => 'ID']));

        if ($totalCustomers === 0) return 0.0;

        // Calculate redemption rate as percentage
        return round(($uniqueUsers / $totalCustomers) * 100, 2);
    }

    /**
     * Get the average order value (AOV) for orders that used a specific coupon.
     *
     * Helps measure how a coupon affects average order spend.
     *
     * @param string $couponCode Coupon code to analyze.
     * @param int $days Number of past days to include.
     * @return float Average order total for orders using the coupon.
     *
     * @example
     * ```php
     * $aov = WooCommerce::getAverageOrderValueByCoupon('SAVE20', 60);
     * ```
     */
    public static function getAverageOrderValueByCoupon(string $couponCode, int $days = 60): float
    {
        if (!self::guard() || empty($couponCode)) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $results = $wpdb->get_row($wpdb->prepare("
        SELECT AVG(CAST(meta_total.meta_value AS DECIMAL(10,2))) AS avg_order_total
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->postmeta} AS meta_total ON orders.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE items.order_item_type = 'coupon'
          AND items.order_item_name = %s
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $couponCode, $since));

        return $results && $results->avg_order_total ? round(floatval($results->avg_order_total), 2) : 0.0;
    }

    /**
     * Get the most frequently combined coupons used together in completed orders.
     *
     * Useful for identifying coupon stacking trends.
     *
     * @param int $limit Number of coupon pairs to return.
     * @return array Array of ['coupon_1', 'coupon_2', 'count'] sorted by count descending.
     *
     * @example
     * ```php
     * $comboCoupons = WooCommerce::getMostCommonCouponPairs(5);
     * ```
     */
    public static function getMostCommonCouponPairs(int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Subquery to get order IDs and coupon codes per order
        $query = "
        SELECT c1.coupon_code AS coupon_1, c2.coupon_code AS coupon_2, COUNT(*) AS count
        FROM (
            SELECT order_id, order_item_name AS coupon_code
            FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_item_type = 'coupon'
        ) AS c1
        JOIN (
            SELECT order_id, order_item_name AS coupon_code
            FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_item_type = 'coupon'
        ) AS c2 ON c1.order_id = c2.order_id AND c1.coupon_code < c2.coupon_code
        GROUP BY coupon_1, coupon_2
        ORDER BY count DESC
        LIMIT %d
    ";

        $results = $wpdb->get_results($wpdb->prepare($query, $limit));

        $pairs = [];

        foreach ($results as $row) {
            $pairs[] = [
                'coupon_1' => $row->coupon_1,
                'coupon_2' => $row->coupon_2,
                'count'    => (int) $row->count,
            ];
        }

        return $pairs;
    }

    /**
     * Get total discount amount given by coupons segmented by coupon type.
     *
     * Coupon types can be 'percent', 'fixed_cart', 'fixed_product', etc.
     *
     * @param int $days Number of past days to analyze.
     * @return array Associative array with coupon type as key and total discount amount as value.
     *
     * @example
     * ```php
     * $discountsByType = WooCommerce::getDiscountAmountByCouponType(30);
     * ```
     */
    public static function getDiscountAmountByCouponType(int $days = 30): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get all coupons with their type
        $coupons = wc_get_coupons(['limit' => -1]);

        if (empty($coupons)) return [];

        // Prepare list of coupon codes and their types
        $couponTypeMap = [];
        foreach ($coupons as $coupon) {
            $couponTypeMap[$coupon->get_code()] = $coupon->get_discount_type();
        }

        // Get total discount per coupon code in timeframe
        $query = $wpdb->prepare("
        SELECT items.order_item_name AS coupon_code, SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND meta.meta_key = 'discount_amount'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
        GROUP BY items.order_item_name
    ", $since);

        $results = $wpdb->get_results($query);

        $totalsByType = [];

        foreach ($results as $row) {
            $type = $couponTypeMap[$row->coupon_code] ?? 'unknown';
            if (!isset($totalsByType[$type])) {
                $totalsByType[$type] = 0.0;
            }
            $totalsByType[$type] += floatval($row->total_discount);
        }

        // Round totals
        foreach ($totalsByType as &$amount) {
            $amount = round($amount, 2);
        }

        return $totalsByType;
    }

    /**
     * Get the expiration dates of all active coupons and their usage counts.
     *
     * Useful for monitoring upcoming coupon expirations and usage trends.
     *
     * @return array Array of coupon code => ['expiry_date' => string|null, 'usage_count' => int].
     *
     * @example
     * ```php
     * $couponsExpirations = WooCommerce::getCouponExpirationsAndUsage();
     * ```
     */
    public static function getCouponExpirationsAndUsage(): array
    {
        if (!self::guard()) return [];

        $coupons = wc_get_coupons(['limit' => -1, 'status' => 'publish']);
        $result = [];

        foreach ($coupons as $coupon) {
            // Get coupon code
            $code = $coupon->get_code();

            // Get expiration date if set, null otherwise
            $expiry = $coupon->get_date_expires();
            $expiryDate = $expiry ? $expiry->date('Y-m-d') : null;

            // Get usage count
            $usageCount = (int) $coupon->get_usage_count();

            $result[$code] = [
                'expiry_date' => $expiryDate,
                'usage_count' => $usageCount,
            ];
        }

        return $result;
    }

    /**
     * Calculate total discount amount given by coupons per customer.
     *
     * Useful for understanding which customers benefit most from coupon usage.
     *
     * @param int $customerId User ID of the customer.
     * @return float Total coupon discount amount used by the customer in completed orders.
     *
     * @example
     * ```php
     * $totalDiscount = WooCommerce::getTotalCouponDiscountByCustomer(123);
     * ```
     */
    public static function getTotalCouponDiscountByCustomer(int $customerId): float
    {
        if (!self::guard() || $customerId <= 0) return 0.0;

        global $wpdb;

        // Join orders and coupon order items to sum discount amounts for this customer
        $query = $wpdb->prepare("
        SELECT SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        WHERE orders.post_author = %d
          AND orders.post_status = 'wc-completed'
          AND items.order_item_type = 'coupon'
          AND meta.meta_key = 'discount_amount'
    ", $customerId);

        $totalDiscount = $wpdb->get_var($query);

        return round(floatval($totalDiscount), 2);
    }

    /**
     * Get the percentage of orders using any coupon in the last X days.
     *
     * Shows coupon usage penetration relative to total orders.
     *
     * @param int $days Number of days to analyze.
     * @return float Percentage (0-100) of completed orders that used at least one coupon.
     *
     * @example
     * ```php
     * $couponUsagePercent = WooCommerce::getCouponUsagePercentageLastDays(30);
     * ```
     */
    public static function getCouponUsagePercentageLastDays(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders count in timeframe
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return 0.0;

        // Completed orders that have at least one coupon used
        $ordersWithCoupon = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT orders.ID)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND items.order_item_type = 'coupon'
    ", $since));

        return round(($ordersWithCoupon / $totalOrders) * 100, 2);
    }

    /**
     * Get the list of coupon codes that have never been used.
     *
     * Useful for identifying inactive or obsolete coupons.
     *
     * @return array Array of unused coupon codes.
     *
     * @example
     * ```php
     * $unusedCoupons = WooCommerce::getUnusedCoupons();
     * ```
     */
    public static function getUnusedCoupons(): array
    {
        if (!self::guard()) return [];

        $coupons = wc_get_coupons(['limit' => -1]);
        $unused = [];

        foreach ($coupons as $coupon) {
            // Check if usage count is zero
            if ((int) $coupon->get_usage_count() === 0) {
                $unused[] = $coupon->get_code();
            }
        }

        return $unused;
    }

    /**
     * Get the top N coupons by total discount amount given in completed orders.
     *
     * Useful for identifying the most impactful coupons.
     *
     * @param int $limit Number of top coupons to return.
     * @return array Array of ['coupon_code' => string, 'total_discount' => float] sorted descending.
     *
     * @example
     * ```php
     * $topCoupons = WooCommerce::getTopCouponsByDiscount(5);
     * ```
     */
    public static function getTopCouponsByDiscount(int $limit = 5): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $query = $wpdb->prepare("
        SELECT items.order_item_name AS coupon_code, SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS meta ON items.order_item_id = meta.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND meta.meta_key = 'discount_amount'
          AND orders.post_status = 'wc-completed'
        GROUP BY items.order_item_name
        ORDER BY total_discount DESC
        LIMIT %d
    ", $limit);

        $results = $wpdb->get_results($query);

        $topCoupons = [];

        foreach ($results as $row) {
            $topCoupons[] = [
                'coupon_code'    => $row->coupon_code,
                'total_discount' => round(floatval($row->total_discount), 2),
            ];
        }

        return $topCoupons;
    }

    /**
     * Calculate the average number of coupons applied per order over the last X days.
     *
     * Helps measure how often customers stack coupons.
     *
     * @param int $days Number of days to analyze.
     * @return float Average coupons per order.
     *
     * @example
     * ```php
     * $avgCoupons = WooCommerce::getAverageCouponsPerOrder(30);
     * ```
     */
    public static function getAverageCouponsPerOrder(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total completed orders in timeframe
        $totalOrders = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status = 'wc-completed'
          AND post_date >= %s
    ", $since));

        if ($totalOrders === 0) return 0.0;

        // Total number of coupons used in these orders
        $totalCoupons = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->posts} AS orders
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON orders.ID = items.order_id
        WHERE orders.post_type = 'shop_order'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
          AND items.order_item_type = 'coupon'
    ", $since));

        return round($totalCoupons / $totalOrders, 2);
    }

    /**
     * Get the total number of times each coupon was applied in completed orders.
     *
     * Useful for analyzing coupon popularity.
     *
     * @param int $limit Number of coupons to retrieve.
     * @return array Array of ['coupon_code' => string, 'usage_count' => int] sorted descending.
     *
     * @example
     * ```php
     * $couponUsage = WooCommerce::getCouponUsageCounts(10);
     * ```
     */
    public static function getCouponUsageCounts(int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $query = $wpdb->prepare("
        SELECT items.order_item_name AS coupon_code, COUNT(*) AS usage_count
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND orders.post_status = 'wc-completed'
        GROUP BY items.order_item_name
        ORDER BY usage_count DESC
        LIMIT %d
    ", $limit);

        $results = $wpdb->get_results($query);

        $usageCounts = [];

        foreach ($results as $row) {
            $usageCounts[] = [
                'coupon_code' => $row->coupon_code,
                'usage_count' => (int) $row->usage_count,
            ];
        }

        return $usageCounts;
    }

    /**
     * Get the average discount amount per coupon usage over the last X days.
     *
     * Useful to see how generous coupons are on average.
     *
     * @param int $days Number of past days to consider.
     * @return float Average discount amount per coupon usage.
     *
     * @example
     * ```php
     * $avgDiscount = WooCommerce::getAverageDiscountPerCouponUsage(30);
     * ```
     */
    public static function getAverageDiscountPerCouponUsage(int $days = 30): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Total discount amount for coupons in timeframe
        $totalDiscount = (float) $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE meta.meta_key = 'discount_amount'
          AND items.order_item_type = 'coupon'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        // Total coupon usages in timeframe
        $totalUsages = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}woocommerce_order_items AS items
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE items.order_item_type = 'coupon'
          AND orders.post_status = 'wc-completed'
          AND orders.post_date >= %s
    ", $since));

        if ($totalUsages === 0) return 0.0;

        return round($totalDiscount / $totalUsages, 2);
    }

    /**
     * Retrieve coupons that give a discount higher than a specified amount.
     *
     * Useful to find high-value coupons.
     *
     * @param float $minDiscount Minimum discount amount.
     * @return array Array of coupon codes matching criteria.
     *
     * @example
     * ```php
     * $highValueCoupons = WooCommerce::getCouponsByMinimumDiscount(20.00);
     * ```
     */
    public static function getCouponsByMinimumDiscount(float $minDiscount): array
    {
        if (!self::guard()) return [];

        $coupons = wc_get_coupons(['limit' => -1]);
        $result = [];

        foreach ($coupons as $coupon) {
            // Get coupon amount and type
            $amount = (float) $coupon->get_amount();
            $type = $coupon->get_discount_type();

            // Only consider fixed amount or percentage coupons with amount >= minDiscount
            // For percentage coupons, interpret amount as a percentage, so skip or convert as needed.
            if (in_array($type, ['fixed_cart', 'fixed_product']) && $amount >= $minDiscount) {
                $result[] = $coupon->get_code();
            }
        }

        return $result;
    }

    /**
     * Get the total revenue discounted by coupons in completed orders within a date range.
     *
     * Useful for understanding the financial impact of coupons.
     *
     * @param string|null $startDate Start date in 'Y-m-d' format or null for no limit.
     * @param string|null $endDate End date in 'Y-m-d' format or null for no limit.
     * @return float Total discounted amount given via coupons.
     *
     * @example
     * ```php
     * $discountedRevenue = WooCommerce::getTotalCouponDiscountRevenue('2025-01-01', '2025-07-01');
     * ```
     */
    public static function getTotalCouponDiscountRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        if (!self::guard()) return 0.0;

        global $wpdb;

        $where = "orders.post_status = 'wc-completed'";

        if ($startDate) {
            $where .= $wpdb->prepare(" AND orders.post_date >= %s", $startDate);
        }
        if ($endDate) {
            $where .= $wpdb->prepare(" AND orders.post_date <= %s", $endDate);
        }

        $query = "
        SELECT SUM(CAST(meta.meta_value AS DECIMAL(10,2))) AS total_discount
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta
        JOIN {$wpdb->prefix}woocommerce_order_items AS items ON meta.order_item_id = items.order_item_id
        JOIN {$wpdb->posts} AS orders ON items.order_id = orders.ID
        WHERE meta.meta_key = 'discount_amount'
          AND items.order_item_type = 'coupon'
          AND {$where}
    ";

        $totalDiscount = $wpdb->get_var($query);

        return round(floatval($totalDiscount), 2);
    }

    /**
     * Get all coupons that are restricted to specific products and return those products mapping.
     *
     * Useful to quickly see product-specific coupon restrictions.
     *
     * @return array Array with coupon_code => array of product IDs it applies to.
     *
     * @example
     * ```php
     * $restrictedCoupons = WooCommerce::getCouponsWithProductRestrictions();
     * ```
     */
    public static function getCouponsWithProductRestrictions(): array
    {
        if (!self::guard()) return [];

        $coupons = wc_get_coupons(['limit' => -1]);
        $result = [];

        foreach ($coupons as $coupon) {
            // Get the list of product IDs the coupon is limited to (if any)
            $productIds = $coupon->get_product_ids();

            // Only return if there are restrictions
            if (!empty($productIds)) {
                $result[$coupon->get_code()] = $productIds;
            }
        }

        return $result;
    }

    /**
     * Get a list of all WooCommerce admin users with their user IDs and email addresses.
     *
     * Useful for sending admin notifications or audits.
     *
     * @return array Array of ['ID' => int, 'user_email' => string, 'display_name' => string].
     *
     * @example
     * ```php
     * $admins = WooCommerce::getAdminUsers();
     * ```
     */
    public static function getAdminUsers(): array
    {
        if (!self::guard()) return [];

        // Fetch users with 'manage_woocommerce' capability (WooCommerce admins)
        $args = [
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID', 'user_email', 'display_name'],
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => -1,
        ];

        $userQuery = new WP_User_Query($args);

        $admins = [];

        foreach ($userQuery->get_results() as $user) {
            $admins[] = [
                'ID'           => $user->ID,
                'user_email'   => $user->user_email,
                'display_name' => $user->display_name,
            ];
        }

        return $admins;
    }

    /**
     * Clear WooCommerce transients related to admin reports and statistics.
     *
     * Helps to refresh dashboard data and fix caching issues.
     *
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::clearAdminTransients();
     * ```
     */
    public static function clearAdminTransients(): void
    {
        if (!self::guard()) return;

        global $wpdb;

        // WooCommerce transients often start with 'wc_admin_reports_' prefix
        $transientPrefix = '_transient_wc_admin_reports_';
        $expiredTransientPrefix = '_transient_timeout_wc_admin_reports_';

        // Delete active transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transientPrefix . '%'
            )
        );

        // Delete expired transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $expiredTransientPrefix . '%'
            )
        );
    }

    /**
     * Get the number of WooCommerce system status warnings currently active.
     *
     * Useful for quick health checks in admin dashboards.
     *
     * @return int Count of active WooCommerce system warnings.
     *
     * @example
     * ```php
     * $warningCount = WooCommerce::getSystemStatusWarningCount();
     * ```
     */
    public static function getSystemStatusWarningCount(): int
    {
        if (!self::guard()) return 0;

        if (!class_exists('WC_Admin_System_Status')) {
            return 0; // WooCommerce class missing
        }

        // Get system status report data
        $system_status = new \WC_Admin_System_Status();

        // Fetch the full system status report
        $report = $system_status->get_system_report();

        $warnings = 0;

        // Iterate through all sections and check for warnings count
        foreach ($report as $section) {
            foreach ($section['checks'] as $check) {
                if (!empty($check['status']) && $check['status'] === 'warning') {
                    $warnings++;
                }
            }
        }

        return $warnings;
    }

    /**
     * Get all WooCommerce scheduled action hooks related to order processing.
     *
     * Useful for debugging or managing queued background tasks.
     *
     * @return array Array of scheduled actions with hook name and next scheduled timestamp.
     *
     * @example
     * ```php
     * $scheduledActions = WooCommerce::getScheduledOrderActions();
     * ```
     */
    public static function getScheduledOrderActions(): array
    {
        if (!self::guard()) return [];

        if (!class_exists('ActionScheduler')) {
            return [];
        }

        $store = ActionScheduler::store();

        // Fetch all scheduled actions related to WooCommerce order processing hooks
        $hooks = [
            'woocommerce_cancel_unpaid_orders',
            'woocommerce_scheduled_subscription_payment',
            'woocommerce_process_shop_order_meta',
            // Add more hooks as needed
        ];

        $scheduled = [];

        foreach ($hooks as $hook) {
            $actions = ActionScheduler::get_scheduled_actions([
                'per_page' => -1,
                'hook'     => $hook,
                'orderby'  => 'scheduled_date',
                'order'    => 'ASC',
                'status'   => ActionScheduler_Store::STATUS_PENDING,
            ]);

            foreach ($actions as $action) {
                $scheduled[] = [
                    'hook'           => $hook,
                    'next_scheduled' => $action->get_scheduled_date()->getTimestamp(),
                    'action_id'      => $action->get_id(),
                ];
            }
        }

        return $scheduled;
    }

    /**
     * Programmatically trigger WooCommerce system status email report to all admin users.
     *
     * Useful for sending health reports on-demand.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $sent = WooCommerce::sendSystemStatusEmailReport();
     * ```
     */
    public static function sendSystemStatusEmailReport(): bool
    {
        if (!self::guard()) return false;

        if (!class_exists('WC_Admin_System_Status')) {
            return false;
        }

        $system_status = new WC_Admin_System_Status();

        // Generate the system status report email content
        $emailContent = $system_status->get_email_report();

        // Get admin emails
        $admins = self::getAdminUsers();

        if (empty($admins)) {
            return false;
        }

        $emails = array_column($admins, 'user_email');

        // Prepare email headers and subject
        $subject = 'WooCommerce System Status Report';
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Send email to each admin
        $success = true;

        foreach ($emails as $email) {
            if (!wp_mail($email, $subject, $emailContent, $headers)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Retrieve WooCommerce admin dashboard widget statuses (active/inactive).
     *
     * Useful for monitoring which dashboard widgets are enabled.
     *
     * @return array Associative array of widget IDs => boolean (true = active).
     *
     * @example
     * ```php
     * $widgetsStatus = WooCommerce::getAdminDashboardWidgetsStatus();
     * ```
     */
    public static function getAdminDashboardWidgetsStatus(): array
    {
        if (!self::guard()) return [];

        // Fetch the current user's dashboard widget options
        $userId = get_current_user_id();
        $optionName = "meta-box-order_dashboard";

        // User meta stores the widget order and visibility
        $userWidgets = get_user_meta($userId, $optionName, true);

        if (empty($userWidgets) || !is_array($userWidgets)) {
            // Default state: assume all WooCommerce widgets active
            return [
                'woocommerce_dashboard_status'    => true,
                'woocommerce_dashboard_sales'     => true,
                'woocommerce_dashboard_recent_reviews' => true,
                'woocommerce_dashboard_recent_orders'  => true,
                'woocommerce_dashboard_top_sellers'    => true,
            ];
        }

        // Widgets set to 'postbox-hidden' are inactive
        $widgetsStatus = [];

        foreach ($userWidgets as $widgetId => $state) {
            $widgetsStatus[$widgetId] = ($state !== 'postbox-hidden');
        }

        return $widgetsStatus;
    }

    /**
     * Force regenerate WooCommerce product lookup tables used for reports.
     *
     * Useful when product data changes and reports are outdated.
     *
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * $success = WooCommerce::regenerateProductLookupTables();
     * ```
     */
    public static function regenerateProductLookupTables(): bool
    {
        if (!self::guard()) return false;

        if (!class_exists('WC_Product_Data_Store_CPT')) {
            return false;
        }

        $store = WC_Data_Store::load('product');

        try {
            // Trigger rebuilding of the lookup tables (like wc_product_meta_lookup)
            $store->update_product_lookup_tables();

            return true;
        } catch (Exception $e) {
            error_log('Error regenerating product lookup tables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of WooCommerce admin users who have not logged in within last X days.
     *
     * Useful for user management and security audits.
     *
     * @param int $days Number of days to check inactivity.
     * @return int Number of inactive admin/shop_manager users.
     *
     * @example
     * ```php
     * $inactiveCount = WooCommerce::countInactiveAdminUsers(90);
     * ```
     */
    public static function countInactiveAdminUsers(int $days = 90): int
    {
        if (!self::guard()) return 0;

        $args = [
            'role__in'   => ['administrator', 'shop_manager'],
            'fields'     => ['ID', 'user_login'],
            'number'     => -1,
            'meta_query' => [
                [
                    'key'     => 'last_login',
                    'value'   => strtotime("-{$days} days"),
                    'compare' => '<',
                    'type'    => 'NUMERIC',
                ],
            ],
        ];

        $userQuery = new WP_User_Query($args);

        return $userQuery->get_total();
    }

    /**
     * Get detailed list of WooCommerce admin users with their last login timestamps.
     *
     * Useful for tracking admin user activity and auditing.
     *
     * @return array Array of ['ID' => int, 'user_email' => string, 'display_name' => string, 'last_login' => int|null].
     *
     * @example
     * ```php
     * $admins = WooCommerce::getAdminUsersWithLastLogin();
     * ```
     */
    public static function getAdminUsersWithLastLogin(): array
    {
        if (!self::guard()) return [];

        $args = [
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID', 'user_email', 'display_name'],
            'number' => -1,
        ];

        $userQuery = new WP_User_Query($args);

        $admins = [];

        foreach ($userQuery->get_results() as $user) {
            // Get last login timestamp from user meta (null if never logged in)
            $lastLogin = get_user_meta($user->ID, 'last_login', true);
            $lastLoginTimestamp = $lastLogin ? (int) $lastLogin : null;

            $admins[] = [
                'ID'           => $user->ID,
                'user_email'   => $user->user_email,
                'display_name' => $user->display_name,
                'last_login'   => $lastLoginTimestamp,
            ];
        }

        return $admins;
    }

    /**
     * Disable WooCommerce admin email notifications temporarily.
     *
     * Useful when running bulk updates or imports to prevent notification spam.
     *
     * @param callable $callback Function containing the code block during which emails are disabled.
     * @return mixed Returns whatever the callback returns.
     *
     * @example
     * ```php
     * WooCommerce::disableAdminEmails(function() {
     *     // bulk update logic here
     * });
     * ```
     */
    public static function disableAdminEmails(callable $callback)
    {
        if (!self::guard()) return null;

        // Temporarily remove WooCommerce email notifications
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_completed_order', '__return_false');

        // Execute the callback block with emails disabled
        $result = $callback();

        // Remove filters to re-enable emails
        remove_filter('woocommerce_email_enabled_new_order', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_completed_order', '__return_false');

        return $result;
    }

    /**
     * Retrieve WooCommerce admin user roles and their capabilities related to WooCommerce.
     *
     * Useful for role and permission audits.
     *
     * @return array Associative array with role names as keys and WooCommerce capabilities array as values.
     *
     * @example
     * ```php
     * $rolesCapabilities = WooCommerce::getAdminRolesCapabilities();
     * ```
     */
    public static function getAdminRolesCapabilities(): array
    {
        if (!self::guard()) return [];

        global $wp_roles;

        $roles = $wp_roles->roles;

        $wcCaps = [];

        foreach ($roles as $roleName => $roleInfo) {
            $caps = $roleInfo['capabilities'];
            // Filter capabilities related to WooCommerce (starting with 'manage_woocommerce' or 'view_woocommerce')
            $wcCapabilities = array_filter($caps, function($cap, $key) {
                return strpos($key, 'woocommerce') !== false;
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($wcCapabilities)) {
                $wcCaps[$roleName] = $wcCapabilities;
            }
        }

        return $wcCaps;
    }

    /**
     * Get WooCommerce admin user activity logs from the last X days.
     *
     * Requires a logging plugin or custom logging mechanism that stores logs in a custom table or post type.
     * This example assumes logs stored as custom post type 'wc_admin_log'.
     *
     * @param int $days Number of days to look back.
     * @return array Array of logs with user ID, action, and timestamp.
     *
     * @example
     * ```php
     * $logs = WooCommerce::getAdminActivityLogs(14);
     * ```
     */
    public static function getAdminActivityLogs(int $days = 14): array
    {
        if (!self::guard()) return [];

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $args = [
            'post_type'      => 'wc_admin_log',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'date_query'     => [
                'after' => $since,
            ],
            'meta_query'     => [
                [
                    'key'     => 'user_role',
                    'value'   => ['administrator', 'shop_manager'],
                    'compare' => 'IN',
                ],
            ],
        ];

        $query = new WP_Query($args);

        $logs = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $logs[] = [
                    'user_id'    => (int) get_post_meta(get_the_ID(), 'user_id', true),
                    'action'     => get_the_title(),
                    'timestamp'  => get_the_date('Y-m-d H:i:s'),
                    'post_id'    => get_the_ID(),
                ];
            }
            wp_reset_postdata();
        }

        return $logs;
    }

    /**
     * Programmatically create a WooCommerce admin notice (transient based) that auto-expires.
     *
     * Useful for showing custom admin alerts or warnings.
     *
     * @param string $message Notice message to display.
     * @param string $type Notice type: 'success', 'warning', 'error', 'info'.
     * @param int $duration Duration in seconds before notice expires (default 1 hour).
     * @return void
     *
     * @example
     * ```php
     * WooCommerce::createAdminNotice('Custom warning message', 'warning', 3600);
     * ```
     */
    public static function createAdminNotice(string $message, string $type = 'info', int $duration = 3600): void
    {
        if (!self::guard()) return;

        // Prepare a unique transient key for this notice
        $transientKey = 'wc_admin_notice_' . md5($message . $type);

        // Store the notice data in a transient for the specified duration
        set_transient($transientKey, [
            'message' => $message,
            'type'    => $type,
        ], $duration);

        // Hook to admin_notices to display transient notices
        add_action('admin_notices', function() use ($transientKey) {
            $notice = get_transient($transientKey);
            if (!$notice) return;

            $class = 'notice notice-' . sanitize_html_class($notice['type']) . ' is-dismissible';

            echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';

            // Delete the transient after showing once
            delete_transient($transientKey);
        });
    }

    /**
     * Get list of WooCommerce admin users who have two-factor authentication enabled.
     *
     * Assumes a 2FA plugin stores user meta key 'two_factor_enabled' = true/false.
     *
     * @return array Array of user IDs with 2FA enabled.
     *
     * @example
     * ```php
     * $usersWith2FA = WooCommerce::getAdminUsersWithTwoFactor();
     * ```
     */
    public static function getAdminUsersWithTwoFactor(): array
    {
        if (!self::guard()) return [];

        $args = [
            'role__in'   => ['administrator', 'shop_manager'],
            'meta_key'   => 'two_factor_enabled',
            'meta_value' => true,
            'fields'     => 'ID',
            'number'     => -1,
        ];

        $userQuery = new WP_User_Query($args);

        return $userQuery->get_results();
    }

    /**
     * Get WooCommerce admin users who have not updated their passwords in the last X days.
     *
     * Helps enforce password security policies.
     *
     * @param int $days Number of days since last password update.
     * @return array Array of user IDs needing password update.
     *
     * @example
     * ```php
     * $users = WooCommerce::getAdminsNeedingPasswordUpdate(90);
     * ```
     */
    public static function getAdminsNeedingPasswordUpdate(int $days = 90): array
    {
        if (!self::guard()) return [];

        $threshold = strtotime("-{$days} days");

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $needsUpdate = [];

        foreach ($admins as $admin) {
            // Retrieve password last updated timestamp stored in user meta (custom implementation)
            $lastUpdate = (int) get_user_meta($admin->ID, 'password_last_updated', true);

            // If no record or last update before threshold, mark for update
            if (!$lastUpdate || $lastUpdate < $threshold) {
                $needsUpdate[] = $admin->ID;
            }
        }

        return $needsUpdate;
    }

    /**
     * Send a custom WooCommerce admin notification email.
     *
     * This can be used to alert admins about system issues or important updates.
     *
     * @param string $subject Email subject.
     * @param string $message Email body in HTML format.
     * @return bool True on success, false on failure.
     *
     * @example
     * ```php
     * WooCommerce::sendAdminNotificationEmail('Alert', '<p>Important system update available.</p>');
     * ```
     */
    public static function sendAdminNotificationEmail(string $subject, string $message): bool
    {
        if (!self::guard()) return false;

        // Get admin emails
        $admins = self::getAdminUsers();

        if (empty($admins)) {
            return false;
        }

        $emails = array_column($admins, 'user_email');

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $allSent = true;

        foreach ($emails as $email) {
            if (!wp_mail($email, $subject, $message, $headers)) {
                $allSent = false;
            }
        }

        return $allSent;
    }

    /**
     * Get WooCommerce admin user IDs with specific meta key and value.
     *
     * Useful for custom admin metadata audits or segmentation.
     *
     * @param string $metaKey Meta key to search for.
     * @param mixed $metaValue Meta value to match.
     * @return array Array of matching user IDs.
     *
     * @example
     * ```php
     * $users = WooCommerce::getAdminUsersByMeta('department', 'sales');
     * ```
     */
    public static function getAdminUsersByMeta(string $metaKey, $metaValue): array
    {
        if (!self::guard()) return [];

        $args = [
            'role__in'   => ['administrator', 'shop_manager'],
            'meta_key'   => $metaKey,
            'meta_value' => $metaValue,
            'fields'     => 'ID',
            'number'     => -1,
        ];

        $userQuery = new WP_User_Query($args);

        return $userQuery->get_results();
    }

    /**
     * Get WooCommerce admin users currently logged in within the last X minutes.
     *
     * Useful for monitoring active admin sessions.
     *
     * @param int $minutes Time window in minutes.
     * @return array Array of user IDs currently active.
     *
     * @example
     * ```php
     * $activeAdmins = WooCommerce::getActiveAdminUsers(15);
     * ```
     */
    public static function getActiveAdminUsers(int $minutes = 15): array
    {
        if (!self::guard()) return [];

        $threshold = time() - ($minutes * 60);

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $activeUsers = [];

        foreach ($admins as $admin) {
            $lastActivity = (int) get_user_meta($admin->ID, 'last_activity', true);
            if ($lastActivity && $lastActivity >= $threshold) {
                $activeUsers[] = $admin->ID;
            }
        }

        return $activeUsers;
    }

    /**
     * Set or update a WooCommerce admin user meta field safely.
     *
     * Ensures the meta update only occurs for valid admin users.
     *
     * @param int $userId User ID.
     * @param string $metaKey Meta key to update.
     * @param mixed $metaValue Value to set.
     * @return bool True on success, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::updateAdminUserMeta(5, 'dashboard_theme', 'dark');
     * ```
     */
    public static function updateAdminUserMeta(int $userId, string $metaKey, $metaValue): bool
    {
        if (!self::guard()) return false;

        $user = get_user_by('ID', $userId);
        if (!$user || !in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles)) {
            return false;
        }

        return update_user_meta($userId, $metaKey, $metaValue);
    }

    /**
     * Remove WooCommerce admin user meta key for all admins.
     *
     * Useful for bulk clearing deprecated or sensitive admin metadata.
     *
     * @param string $metaKey Meta key to remove.
     * @return int Number of users updated.
     *
     * @example
     * ```php
     * $count = WooCommerce::removeAdminUserMeta('deprecated_setting');
     * ```
     */
    public static function removeAdminUserMeta(string $metaKey): int
    {
        if (!self::guard()) return 0;

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $removedCount = 0;

        foreach ($admins as $admin) {
            if (delete_user_meta($admin->ID, $metaKey)) {
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * Get the count of WooCommerce admin users by their last login date range.
     *
     * Helps track admin login activity and detect inactive users.
     *
     * @param string $startDate Start date in 'Y-m-d' format.
     * @param string $endDate End date in 'Y-m-d' format.
     * @return int Count of admin users logged in between the dates.
     *
     * @example
     * ```php
     * $count = WooCommerce::countAdminLoginsBetween('2025-01-01', '2025-08-01');
     * ```
     */
    public static function countAdminLoginsBetween(string $startDate, string $endDate): int
    {
        if (!self::guard()) return 0;

        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $count = 0;

        foreach ($admins as $admin) {
            $lastLogin = (int) get_user_meta($admin->ID, 'last_login', true);
            if ($lastLogin >= $startTimestamp && $lastLogin <= $endTimestamp) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get a list of WooCommerce admin users who have not logged in for over X days.
     *
     * Useful for security audits and cleaning up unused admin accounts.
     *
     * @param int $days Number of days of inactivity.
     * @return array Array of user IDs inactive beyond given days.
     *
     * @example
     * ```php
     * $inactiveAdmins = WooCommerce::getInactiveAdminUsers(60);
     * ```
     */
    public static function getInactiveAdminUsers(int $days = 60): array
    {
        if (!self::guard()) return [];

        $threshold = time() - ($days * 86400);

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $inactiveUsers = [];

        foreach ($admins as $admin) {
            $lastLogin = (int) get_user_meta($admin->ID, 'last_login', true);
            if (!$lastLogin || $lastLogin < $threshold) {
                $inactiveUsers[] = $admin->ID;
            }
        }

        return $inactiveUsers;
    }

    /**
     * Reset WooCommerce admin users’ password expiration date meta to a new timestamp.
     *
     * Useful when implementing password expiration policies.
     *
     * @param int $timestamp Unix timestamp to set as new expiration date.
     * @return int Number of users updated.
     *
     * @example
     * ```php
     * $updatedCount = WooCommerce::resetAdminPasswordExpiry(time() + 2592000);
     * ```
     */
    public static function resetAdminPasswordExpiry(int $timestamp): int
    {
        if (!self::guard()) return 0;

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $updated = 0;

        foreach ($admins as $admin) {
            if (update_user_meta($admin->ID, 'password_expiry', $timestamp)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get WooCommerce admin users with a specific capability.
     *
     * Useful to identify admins who have permissions for sensitive actions.
     *
     * @param string $capability Capability string to check (e.g., 'manage_woocommerce').
     * @return array Array of user IDs who have the capability.
     *
     * @example
     * ```php
     * $capableAdmins = WooCommerce::getAdminUsersByCapability('manage_woocommerce');
     * ```
     */
    public static function getAdminUsersByCapability(string $capability): array
    {
        if (!self::guard()) return [];

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields'   => ['ID'],
            'number'   => -1,
        ]);

        $result = [];

        foreach ($admins as $admin) {
            $user = get_user_by('ID', $admin->ID);
            if ($user && $user->has_cap($capability)) {
                $result[] = $admin->ID;
            }
        }

        return $result;
    }

    /**
     * Lock WooCommerce admin users by setting a custom meta flag and optionally sending notification.
     *
     * Useful for temporarily disabling admin accounts without deleting them.
     *
     * @param int $userId User ID to lock.
     * @param bool $notify Whether to send notification email to user.
     * @return bool True if user locked, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::lockAdminUser(5, true);
     * ```
     */
    public static function lockAdminUser(int $userId, bool $notify = false): bool
    {
        if (!self::guard()) return false;

        $user = get_user_by('ID', $userId);
        if (!$user || !in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles)) {
            return false;
        }

        // Add meta flag to indicate lock status
        update_user_meta($userId, 'wc_admin_locked', true);

        if ($notify) {
            $subject = 'Your WooCommerce Admin Account Has Been Locked';
            $message = 'Your admin account has been temporarily locked. Please contact the site administrator for more info.';
            wp_mail($user->user_email, $subject, $message);
        }

        return true;
    }

    /**
     * Unlock WooCommerce admin user by removing the lock meta flag.
     *
     * @param int $userId User ID to unlock.
     * @return bool True if unlocked successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::unlockAdminUser(5);
     * ```
     */
    public static function unlockAdminUser(int $userId): bool
    {
        if (!self::guard()) return false;

        $user = get_user_by('ID', $userId);
        if (!$user || !in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles)) {
            return false;
        }

        return delete_user_meta($userId, 'wc_admin_locked');
    }

    /**
     * Get WooCommerce admin users who have enabled two-factor authentication (2FA).
     *
     * Useful for security audits to ensure admins use 2FA.
     *
     * @return array Array of admin user IDs with 2FA enabled.
     *
     * @example
     * ```php
     * $adminsWith2FA = WooCommerce::getAdminsWithTwoFactorAuth();
     * ```
     */
    public static function getAdminsWithTwoFactorAuth(): array
    {
        if (!self::guard()) return [];

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $adminsWith2FA = [];

        foreach ($admins as $admin) {
            // Assuming 2FA status stored as user meta 'two_factor_enabled' (boolean)
            $twoFactorEnabled = get_user_meta($admin->ID, 'two_factor_enabled', true);
            if ($twoFactorEnabled) {
                $adminsWith2FA[] = $admin->ID;
            }
        }

        return $adminsWith2FA;
    }

    /**
     * Send an admin dashboard summary report email.
     *
     * The report includes order count, sales totals, and low stock product count.
     *
     * @param string $toEmail Email recipient.
     * @return bool True if email sent successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::sendAdminDashboardReport('admin@example.com');
     * ```
     */
    public static function sendAdminDashboardReport(string $toEmail): bool
    {
        if (!self::guard()) return false;

        // Get total completed orders count
        $completedOrders = wc_orders_count('completed');

        // Get total sales amount (completed orders)
        $totalSales = wc_get_total_sales();

        // Get low stock products count using previously defined method
        $lowStockCount = count(self::getLowStockProducts());

        // Compose HTML email body
        $message = "
        <h2>WooCommerce Dashboard Summary</h2>
        <ul>
            <li>Total Completed Orders: {$completedOrders}</li>
            <li>Total Sales: $" . number_format($totalSales, 2) . "</li>
            <li>Low Stock Products: {$lowStockCount}</li>
        </ul>
        <p>Report generated on " . date('Y-m-d H:i:s') . "</p>
    ";

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        return wp_mail($toEmail, 'WooCommerce Dashboard Summary Report', $message, $headers);
    }

    /**
     * Bulk update WooCommerce admin users’ roles.
     *
     * Changes the role of multiple users in one go, useful for large admin restructures.
     *
     * @param array $userIds Array of user IDs to update.
     * @param string $newRole New role slug (e.g., 'shop_manager').
     * @return int Number of users successfully updated.
     *
     * @example
     * ```php
     * $updatedCount = WooCommerce::bulkUpdateAdminRoles([3, 5, 7], 'shop_manager');
     * ```
     */
    public static function bulkUpdateAdminRoles(array $userIds, string $newRole): int
    {
        if (!self::guard()) return 0;

        $validRoles = ['administrator', 'shop_manager'];

        if (!in_array($newRole, $validRoles)) {
            return 0; // Invalid role
        }

        $updated = 0;

        foreach ($userIds as $userId) {
            $user = get_user_by('ID', $userId);
            if ($user && array_intersect(['administrator', 'shop_manager'], $user->roles)) {
                // Remove old roles and add new role
                foreach ($validRoles as $role) {
                    if ($user->has_role($role)) {
                        $user->remove_role($role);
                    }
                }
                $user->add_role($newRole);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get WooCommerce admin users with pending password reset requests.
     *
     * This helps track admins who may need assistance or reminders to reset passwords.
     *
     * @return array Array of admin user IDs with pending password resets.
     *
     * @example
     * ```php
     * $pendingResets = WooCommerce::getAdminsWithPendingPasswordResets();
     * ```
     */
    public static function getAdminsWithPendingPasswordResets(): array
    {
        if (!self::guard()) return [];

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $pendingAdmins = [];

        foreach ($admins as $admin) {
            // Assume a meta key 'password_reset_pending' set to true if reset requested
            $pending = get_user_meta($admin->ID, 'password_reset_pending', true);
            if ($pending) {
                $pendingAdmins[] = $admin->ID;
            }
        }

        return $pendingAdmins;
    }

    /**
     * Log an admin action for WooCommerce auditing.
     *
     * Stores action details in a custom table or log file for security and tracking.
     *
     * @param int $userId Admin user ID performing the action.
     * @param string $action Description of the action performed.
     * @param string|null $context Optional additional context or metadata.
     * @return bool True if logged successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::logAdminAction(5, 'Updated product stock quantity', 'Product ID: 123');
     * ```
     */
    public static function logAdminAction(int $userId, string $action, ?string $context = null): bool
    {
        if (!self::guard()) return false;

        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_admin_action_logs';

        // Prepare data array for insertion
        $data = [
            'user_id'    => $userId,
            'action'     => $action,
            'context'    => $context,
            'timestamp'  => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $format = ['%d', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table_name, $data, $format);

        return $inserted !== false;
    }

    /**
     * Get WooCommerce admin action logs filtered by user and date range.
     *
     * Useful for auditing or investigating specific admin activity.
     *
     * @param int|null $userId Filter logs by user ID, or null for all users.
     * @param string|null $startDate Start date in 'Y-m-d' format or null for no start filter.
     * @param string|null $endDate End date in 'Y-m-d' format or null for no end filter.
     * @param int $limit Number of records to retrieve.
     * @return array Array of log entries (associative arrays).
     *
     * @example
     * ```php
     * $logs = WooCommerce::getAdminActionLogs(5, '2025-01-01', '2025-08-01', 50);
     * ```
     */
    public static function getAdminActionLogs(?int $userId = null, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_admin_action_logs';

        $where = [];
        $params = [];

        if ($userId !== null) {
            $where[] = 'user_id = %d';
            $params[] = $userId;
        }

        if ($startDate !== null) {
            $where[] = 'timestamp >= %s';
            $params[] = $startDate . ' 00:00:00';
        }

        if ($endDate !== null) {
            $where[] = 'timestamp <= %s';
            $params[] = $endDate . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} {$whereSql} ORDER BY timestamp DESC LIMIT %d",
            ...array_merge($params, [$limit])
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get count of WooCommerce admin users currently online (active in last X minutes).
     *
     * Helps monitor active admin sessions for security or support purposes.
     *
     * @param int $minutes Time window in minutes to consider a user "online".
     * @return int Number of admin users active within the timeframe.
     *
     * @example
     * ```php
     * $onlineAdmins = WooCommerce::countOnlineAdmins(15);
     * ```
     */
    public static function countOnlineAdmins(int $minutes = 15): int
    {
        if (!self::guard()) return 0;

        $threshold = time() - ($minutes * 60);

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $count = 0;

        foreach ($admins as $admin) {
            $lastActivity = (int) get_user_meta($admin->ID, 'last_activity', true);
            if ($lastActivity >= $threshold) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Force logout all WooCommerce admin users except current user.
     *
     * Useful for security when a breach is suspected or during maintenance.
     *
     * @return int Number of users logged out.
     *
     * @example
     * ```php
     * $loggedOutCount = WooCommerce::forceLogoutOtherAdmins();
     * ```
     */
    public static function forceLogoutOtherAdmins(): int
    {
        if (!self::guard()) return 0;

        $currentUserId = get_current_user_id();

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $loggedOutCount = 0;

        foreach ($admins as $admin) {
            if ($admin->ID === $currentUserId) {
                continue; // skip current user
            }
            // Remove all sessions for this user to force logout
            if (function_exists('wp_destroy_other_sessions')) {
                // wp_destroy_other_sessions() destroys other sessions except current logged in
                wp_destroy_other_sessions($admin->ID);
                $loggedOutCount++;
            } else {
                // Fallback: clear all session tokens manually
                delete_user_meta($admin->ID, 'session_tokens');
                $loggedOutCount++;
            }
        }

        return $loggedOutCount;
    }

    /**
     * Retrieve WooCommerce admin users with expired password expiration meta.
     *
     * Useful for enforcing password rotation policies.
     *
     * @return array Array of admin user IDs with expired passwords.
     *
     * @example
     * ```php
     * $expiredAdmins = WooCommerce::getAdminsWithExpiredPasswords();
     * ```
     */
    public static function getAdminsWithExpiredPasswords(): array
    {
        if (!self::guard()) return [];

        $now = time();

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $expiredUsers = [];

        foreach ($admins as $admin) {
            $expiry = (int) get_user_meta($admin->ID, 'password_expiry', true);
            if ($expiry && $expiry < $now) {
                $expiredUsers[] = $admin->ID;
            }
        }

        return $expiredUsers;
    }

    /**
     * Set password expiry date for a WooCommerce admin user.
     *
     * Useful for enforcing password rotation policies by setting expiry timestamps.
     *
     * @param int $userId Admin user ID.
     * @param int $days Number of days from now before password expires.
     * @return bool True if updated successfully, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::setAdminPasswordExpiry(5, 90); // Expires in 90 days
     * ```
     */
    public static function setAdminPasswordExpiry(int $userId, int $days): bool
    {
        if (!self::guard()) return false;

        $user = get_user_by('ID', $userId);
        if (!$user || !in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles)) {
            return false;
        }

        $expiryTimestamp = time() + ($days * 86400); // days to seconds
        return update_user_meta($userId, 'password_expiry', $expiryTimestamp);
    }

    /**
     * Disable WooCommerce admin users by removing all roles.
     *
     * Temporarily disables admin access without deleting accounts.
     *
     * @param int $userId Admin user ID to disable.
     * @return bool True if roles removed, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::disableAdminUser(7);
     * ```
     */
    public static function disableAdminUser(int $userId): bool
    {
        if (!self::guard()) return false;

        $user = get_user_by('ID', $userId);
        if (!$user || !array_intersect(['administrator', 'shop_manager'], $user->roles)) {
            return false;
        }

        // Remove all roles to disable access
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        return true;
    }

    /**
     * Enable WooCommerce admin user by assigning a specified role.
     *
     * Restores admin access by assigning a valid admin role.
     *
     * @param int $userId Admin user ID to enable.
     * @param string $role Role to assign ('administrator' or 'shop_manager').
     * @return bool True if role assigned, false otherwise.
     *
     * @example
     * ```php
     * WooCommerce::enableAdminUser(7, 'shop_manager');
     * ```
     */
    public static function enableAdminUser(int $userId, string $role = 'shop_manager'): bool
    {
        if (!self::guard()) return false;

        $validRoles = ['administrator', 'shop_manager'];
        if (!in_array($role, $validRoles)) {
            return false;
        }

        $user = get_user_by('ID', $userId);
        if (!$user) return false;

        // Add specified role
        $user->add_role($role);

        return true;
    }

    /**
     * Get a list of WooCommerce admin users with their last login date.
     *
     * Useful for monitoring admin activity and identifying inactive users.
     *
     * @param int $days Number of days to consider recent logins (default 30).
     * @return array Associative array [user_id => last_login_date].
     *
     * @example
     * ```php
     * $adminsLastLogin = WooCommerce::getAdminLastLoginDates(60);
     * ```
     */
    public static function getAdminLastLoginDates(int $days = 30): array
    {
        if (!self::guard()) return [];

        $threshold = strtotime("-{$days} days");
        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $result = [];

        foreach ($admins as $admin) {
            // Assume last login timestamp stored in user meta 'last_login'
            $lastLogin = (int) get_user_meta($admin->ID, 'last_login', true);
            if ($lastLogin >= $threshold) {
                $result[$admin->ID] = date('Y-m-d H:i:s', $lastLogin);
            }
        }

        return $result;
    }

    /**
     * Reset all WooCommerce admin users’ password expiry dates to a new value.
     *
     * Useful when enforcing new password policies across all admins.
     *
     * @param int $days Number of days from now before password expires.
     * @return int Number of users updated.
     *
     * @example
     * ```php
     * $updatedCount = WooCommerce::resetAllAdminPasswordExpiries(90);
     * ```
     */
    public static function resetAllAdminPasswordExpiries(int $days): int
    {
        if (!self::guard()) return 0;

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $expiryTimestamp = time() + ($days * 86400);
        $updated = 0;

        foreach ($admins as $admin) {
            if (update_user_meta($admin->ID, 'password_expiry', $expiryTimestamp)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get WooCommerce admin users who have two-factor authentication disabled.
     *
     * Helps identify users who may need to enable 2FA for better security.
     *
     * @return array Array of admin user IDs without 2FA enabled.
     *
     * @example
     * ```php
     * $adminsWithout2FA = WooCommerce::getAdminsWithoutTwoFactorAuth();
     * ```
     */
    public static function getAdminsWithoutTwoFactorAuth(): array
    {
        if (!self::guard()) return [];

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID'],
            'number' => -1,
        ]);

        $result = [];

        foreach ($admins as $admin) {
            $twoFactorEnabled = get_user_meta($admin->ID, 'two_factor_enabled', true);
            if (!$twoFactorEnabled) {
                $result[] = $admin->ID;
            }
        }

        return $result;
    }

    /**
     * Get WooCommerce admin users sorted by the number of orders they processed.
     *
     * Useful for identifying the most active admins in terms of order handling.
     *
     * @param int $limit Number of top admins to retrieve.
     * @return array Associative array [user_id => order_count], sorted descending.
     *
     * @example
     * ```php
     * $topAdmins = WooCommerce::getAdminsByOrderCount(10);
     * ```
     */
    public static function getAdminsByOrderCount(int $limit = 10): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        // Query posts table for completed orders and group by post_author (admin who created/processed order)
        $query = $wpdb->prepare("
        SELECT post_author, COUNT(ID) AS order_count
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
          AND post_status IN ('wc-completed', 'wc-processing')
          AND post_author != 0
        GROUP BY post_author
        ORDER BY order_count DESC
        LIMIT %d
    ", $limit);

        $results = $wpdb->get_results($query);

        $adminsOrderCounts = [];

        foreach ($results as $row) {
            // Confirm user is admin or shop_manager
            $user = get_user_by('ID', $row->post_author);
            if ($user && array_intersect(['administrator', 'shop_manager'], $user->roles)) {
                $adminsOrderCounts[$row->post_author] = (int) $row->order_count;
            }
        }

        return $adminsOrderCounts;
    }

    /**
     * Get all WooCommerce admin users who have not updated their profiles recently.
     *
     * Useful for reminding admins to keep their profile information up-to-date.
     *
     * @param int $days Number of days since last update to consider as outdated.
     * @return array Array of admin user IDs with outdated profiles.
     *
     * @example
     * ```php
     * $outdatedAdmins = WooCommerce::getAdminsWithOutdatedProfiles(60);
     * ```
     */
    public static function getAdminsWithOutdatedProfiles(int $days = 60): array
    {
        if (!self::guard()) return [];

        $threshold = strtotime("-{$days} days");

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['ID', 'user_registered', 'last_update'],
            'number' => -1,
        ]);

        $outdated = [];

        foreach ($admins as $admin) {
            $lastUpdate = (int) get_user_meta($admin->ID, 'profile_last_update', true);
            if (!$lastUpdate || $lastUpdate < $threshold) {
                $outdated[] = $admin->ID;
            }
        }

        return $outdated;
    }

    /**
     * Send an admin notification email to all WooCommerce admin users.
     *
     * Useful for broadcasting important admin messages or alerts.
     *
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return int Number of emails sent.
     *
     * @example
     * ```php
     * $sentCount = WooCommerce::notifyAllAdmins('Maintenance Notice', 'Site will be down at midnight.');
     * ```
     */
    public static function notifyAllAdmins(string $subject, string $message): int
    {
        if (!self::guard()) return 0;

        $admins = get_users([
            'role__in' => ['administrator', 'shop_manager'],
            'fields' => ['user_email'],
            'number' => -1,
        ]);

        $sent = 0;

        foreach ($admins as $admin) {
            if (wp_mail($admin->user_email, $subject, $message)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Get products with the highest average rating within a specified category.
     *
     * Useful for highlighting top-rated products in a given category.
     *
     * @param int $categoryId WooCommerce product category ID.
     * @param int $limit Number of products to return.
     * @return array Array of product IDs sorted by average rating descending.
     *
     * @example
     * ```php
     * $topRated = WooCommerce::getTopRatedProductsByCategory(15, 5);
     * ```
     */
    public static function getTopRatedProductsByCategory(int $categoryId, int $limit = 10): array
    {
        if (!self::guard()) return [];

        // Get products in category
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
            'category' => [$categoryId],
        ]);

        $productRatings = [];

        foreach ($products as $product) {
            // Get average rating, skip products without ratings
            $rating = (float) $product->get_average_rating();
            if ($rating > 0) {
                $productRatings[$product->get_id()] = $rating;
            }
        }

        // Sort by rating descending
        arsort($productRatings);

        // Limit results
        return array_slice(array_keys($productRatings), 0, $limit);
    }

    /**
     * Calculate total stock value for all WooCommerce products.
     *
     * Stock value = stock quantity * regular price, summed over all products.
     *
     * @return float Total stock value in store currency.
     *
     * @example
     * ```php
     * $totalStockValue = WooCommerce::getTotalStockValue();
     * ```
     */
    public static function getTotalStockValue(): float
    {
        if (!self::guard()) return 0.0;

        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
        ]);

        $totalValue = 0.0;

        foreach ($products as $product) {
            // Only consider products managing stock and in stock
            if ($product->managing_stock() && $product->is_in_stock()) {
                $quantity = (int) $product->get_stock_quantity();
                $price = (float) $product->get_regular_price();

                // Add stock value for this product
                $totalValue += $quantity * $price;
            }
        }

        return round($totalValue, 2);
    }

    /**
     * Get product IDs with the largest sales increase compared to previous period.
     *
     * Compares total_sales meta with previous sales saved in custom meta _sales_prev.
     *
     * @param int $limit Number of products to return.
     * @return array Product IDs sorted by sales increase descending.
     *
     * @example
     * ```php
     * $fastGrowingProducts = WooCommerce::getProductsWithLargestSalesIncrease(5);
     * ```
     */
    public static function getProductsWithLargestSalesIncrease(int $limit = 10): array
    {
        if (!self::guard()) return [];

        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
        ]);

        $salesIncrease = [];

        foreach ($products as $product) {
            $id = $product->get_id();
            $currentSales = (int) get_post_meta($id, 'total_sales', true);
            $prevSales = (int) get_post_meta($id, '_sales_prev', true);

            if ($prevSales > 0 && $currentSales > $prevSales) {
                $increase = $currentSales - $prevSales;
                $salesIncrease[$id] = $increase;
            }
        }

        arsort($salesIncrease);

        return array_slice(array_keys($salesIncrease), 0, $limit);
    }

    /**
     * Get products that have not sold at all in the last X days.
     *
     * Helps identify slow-moving or stagnant inventory.
     *
     * @param int $days Number of days to check sales.
     * @return array Array of product IDs with zero sales in given period.
     *
     * @example
     * ```php
     * $unsoldProducts = WooCommerce::getUnsoldProducts(90);
     * ```
     */
    public static function getUnsoldProducts(int $days = 90): array
    {
        if (!self::guard()) return [];

        global $wpdb;

        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query for product IDs sold after $since
        $soldProductIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
        INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
        WHERE meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
    ", $since));

        // Get all products
        $allProducts = wc_get_products(['limit' => -1, 'status' => 'publish']);
        $unsold = [];

        foreach ($allProducts as $product) {
            $id = $product->get_id();
            if (!in_array($id, $soldProductIds, true)) {
                $unsold[] = $id;
            }
        }

        return $unsold;
    }

    /**
     * Calculate average margin percentage for a product.
     *
     * Margin = ((Regular Price - Cost Price) / Regular Price) * 100
     *
     * @param int $productId WooCommerce product ID.
     * @return float Margin percentage, or 0 if not computable.
     *
     * @example
     * ```php
     * $margin = WooCommerce::getProductMarginPercentage(123);
     * ```
     */
    public static function getProductMarginPercentage(int $productId): float
    {
        if (!self::guard()) return 0.0;

        $product = wc_get_product($productId);
        if (!$product) return 0.0;

        $regularPrice = (float) $product->get_regular_price();
        $costPrice = (float) get_post_meta($productId, '_cost_price', true);

        if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) {
            return 0.0;
        }

        $margin = (($regularPrice - $costPrice) / $regularPrice) * 100;

        return round($margin, 2);
    }

    /**
     * Get products with price drops compared to previous recorded price.
     *
     * Compares current regular price to meta '_price_prev'.
     *
     * @param int $limit Number of products to return.
     * @return array Array of product IDs sorted by largest price drop descending.
     *
     * @example
     * ```php
     * $priceDropped = WooCommerce::getProductsWithPriceDrops(10);
     * ```
     */
    public static function getProductsWithPriceDrops(int $limit = 10): array
    {
        if (!self::guard()) return [];

        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
        $priceDrops = [];

        foreach ($products as $product) {
            $id = $product->get_id();
            $currentPrice = (float) $product->get_regular_price();
            $prevPrice = (float) get_post_meta($id, '_price_prev', true);

            if ($prevPrice > 0 && $currentPrice < $prevPrice) {
                $dropAmount = $prevPrice - $currentPrice;
                $priceDrops[$id] = $dropAmount;
            }
        }

        arsort($priceDrops);

        return array_slice(array_keys($priceDrops), 0, $limit);
    }

    /**
     * Get products without any images assigned.
     *
     * Helps identify products that may need images added for better presentation.
     *
     * @return array Array of product IDs with no images.
     *
     * @example
     * ```php
     * $productsWithoutImages = WooCommerce::getProductsWithoutImages();
     * ```
     */
    public static function getProductsWithoutImages(): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products without images
        $noImages = [];

        // Loop through each product
        foreach ($products as $product) {
            // Check if product has no featured image and no gallery images
            if (!$product->get_image_id() && empty($product->get_gallery_image_ids())) {
                // Add product ID to noImages array
                $noImages[] = $product->get_id();
            }
        }

        // Return array of product IDs without images
        return $noImages;
    }

    /**
     * Get products that have low margin.
     *
     * Helps identify products that may be underperforming financially.
     *
     * @param float $threshold Margin percentage threshold.
     * @return array Array of product IDs with margin below the threshold.
     *
     * @example
     * ```php
     * $lowMarginProducts = WooCommerce::getLowMarginProducts(20);
     * ```
     */
    public static function getLowMarginProducts(float $threshold = 20.0): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products with low margin
        $lowMargin = [];

        // Loop through each product
        foreach ($products as $product) {
            // Calculate product margin percentage
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($product->get_id(), '_cost_price', true);

            // Skip if prices are invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate margin
            $margin = (($regularPrice - $costPrice) / $regularPrice) * 100;

            // Check if margin is below threshold
            if ($margin < $threshold) {
                // Add product ID to lowMargin array
                $lowMargin[] = $product->get_id();
            }
        }

        // Return array of low margin product IDs
        return $lowMargin;
    }

    /**
     * Get products that have been recently added.
     *
     * Helps identify new products for promotion or review.
     *
     * @param int $days Number of days since product creation.
     * @return array Array of product IDs added in the last X days.
     *
     * @example
     * ```php
     * $recentProducts = WooCommerce::getRecentlyAddedProducts(30);
     * ```
     */
    public static function getRecentlyAddedProducts(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Calculate date from which to consider products as recent
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store recently added product IDs
        $recent = [];

        // Loop through each product
        foreach ($products as $product) {
            // Get product creation date
            $dateCreated = $product->get_date_created();

            // Check if product was created after $since
            if ($dateCreated && $dateCreated->date('Y-m-d H:i:s') >= $since) {
                // Add product ID to recent array
                $recent[] = $product->get_id();
            }
        }

        // Return array of recently added product IDs
        return $recent;
    }

    /**
     * Get products that have never been reviewed.
     *
     * Helps identify products that may need more engagement or promotion.
     *
     * @return array Array of product IDs with zero reviews.
     *
     * @example
     * ```php
     * $noReviewProducts = WooCommerce::getProductsWithoutReviews();
     * ```
     */
    public static function getProductsWithoutReviews(): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store product IDs without reviews
        $noReviews = [];

        // Loop through each product
        foreach ($products as $product) {
            // Get number of reviews
            $reviewCount = (int) $product->get_review_count();

            // Check if product has zero reviews
            if ($reviewCount === 0) {
                // Add product ID to noReviews array
                $noReviews[] = $product->get_id();
            }
        }

        // Return array of product IDs without reviews
        return $noReviews;
    }

    /**
     * Get products that are unsold and have low margin.
     *
     * Helps identify slow-moving, unprofitable products for clearance or discounting.
     *
     * @param int $days Number of days to check sales.
     * @param float $marginThreshold Margin percentage threshold.
     * @return array Array of product IDs meeting both criteria.
     *
     * @example
     * ```php
     * $unsoldLowMargin = WooCommerce::getUnsoldLowMarginProducts(90, 20);
     * ```
     */
    public static function getUnsoldLowMarginProducts(int $days = 90, float $marginThreshold = 20.0): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate the start date for sales check
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get product IDs sold in the last X days
        $soldProductIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value
        FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
        INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
        WHERE meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
    ", $since));

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products that are unsold and low margin
        $unsoldLowMargin = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Skip products that have been sold
            if (in_array($id, $soldProductIds, true)) continue;

            // Get product prices
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices are invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate margin
            $margin = (($regularPrice - $costPrice) / $regularPrice) * 100;

            // Check if margin is below threshold
            if ($margin < $marginThreshold) {
                // Add product ID to result array
                $unsoldLowMargin[] = $id;
            }
        }

        // Return array of product IDs
        return $unsoldLowMargin;
    }

    /**
     * Get products with price drop and low stock.
     *
     * Helps identify products that are on sale but may run out soon.
     *
     * @param int $stockThreshold Stock quantity threshold.
     * @param int $limit Maximum number of products to return.
     * @return array Array of product IDs sorted by largest price drop descending.
     *
     * @example
     * ```php
     * $priceDropLowStock = WooCommerce::getPriceDropLowStockProducts(5, 10);
     * ```
     */
    public static function getPriceDropLowStockProducts(int $stockThreshold = 5, int $limit = 10): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products with price drop and low stock
        $priceDropLowStock = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get current and previous prices
            $currentPrice = (float) $product->get_regular_price();
            $prevPrice = (float) get_post_meta($id, '_price_prev', true);

            // Skip if no price drop
            if ($prevPrice <= 0 || $currentPrice >= $prevPrice) continue;

            // Calculate price drop amount
            $dropAmount = $prevPrice - $currentPrice;

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if stock is above threshold
            if ($stock > $stockThreshold) continue;

            // Store product with price drop as key for sorting later
            $priceDropLowStock[$id] = $dropAmount;
        }

        // Sort products by largest price drop descending
        arsort($priceDropLowStock);

        // Return limited number of product IDs
        return array_slice(array_keys($priceDropLowStock), 0, $limit);
    }

    /**
     * Get products that are new, unsold, and have low reviews.
     *
     * Helps identify new products that may need promotion or attention.
     *
     * @param int $days Number of days since product creation.
     * @param int $reviewThreshold Maximum number of reviews to qualify.
     * @return array Array of product IDs meeting all criteria.
     *
     * @example
     * ```php
     * $newUnsoldLowReview = WooCommerce::getNewUnsoldLowReviewProducts(30, 2);
     * ```
     */
    public static function getNewUnsoldLowReviewProducts(int $days = 30, int $reviewThreshold = 2): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate date since which products are considered new
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get product IDs sold in the last X days
        $soldProductIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value
        FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
        INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
        WHERE meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
    ", $since));

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products matching all criteria
        $result = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Skip products that have been sold
            if (in_array($id, $soldProductIds, true)) continue;

            // Check if product is new
            $dateCreated = $product->get_date_created();
            if (!$dateCreated || $dateCreated->date('Y-m-d H:i:s') < $since) continue;

            // Get review count
            $reviewCount = (int) $product->get_review_count();

            // Skip if reviews exceed threshold
            if ($reviewCount > $reviewThreshold) continue;

            // Add product ID to result array
            $result[] = $id;
        }

        // Return array of product IDs
        return $result;
    }

    /**
     * Get products with predicted monthly profit.
     *
     * Calculates estimated monthly profit based on recent sales velocity and margin.
     *
     * @param int $days Number of days to check past sales.
     * @return array Array of product IDs with estimated monthly profit.
     *
     * @example
     * ```php
     * $predictedProfit = WooCommerce::getProductsPredictedMonthlyProfit(30);
     * ```
     */
    public static function getProductsPredictedMonthlyProfit(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales quantities in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A); // Execute query and return associative array

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store predicted monthly profit
        $predictedProfit = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get recent sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Skip products with no sales
            if ($qtySold <= 0) continue;

            // Get product prices
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate margin per unit
            $marginPerUnit = $regularPrice - $costPrice;

            // Estimate daily sales velocity
            $dailySales = $qtySold / max($days, 1);

            // Predict monthly profit (approx. 30 days)
            $monthlyProfit = $marginPerUnit * $dailySales * 30;

            // Store predicted profit
            $predictedProfit[$id] = round($monthlyProfit, 2);
        }

        // Sort products by predicted profit descending
        arsort($predictedProfit);

        // Return array of product IDs sorted by predicted profit
        return array_keys($predictedProfit);
    }

    /**
     * Get products with inventory velocity score.
     *
     * Calculates how quickly products are selling relative to stock levels.
     *
     * @param int $days Number of days to check past sales.
     * @return array Array of product IDs with velocity score (higher = faster selling).
     *
     * @example
     * ```php
     * $velocity = WooCommerce::getProductsInventoryVelocity(30);
     * ```
     */
    public static function getProductsInventoryVelocity(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales quantities in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store velocity scores
        $velocityScores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if no stock
            if ($stock <= 0) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Calculate velocity score = sold quantity / stock
            $velocity = $qtySold / $stock;

            // Store velocity score
            $velocityScores[$id] = round($velocity, 2);
        }

        // Sort products by velocity descending
        arsort($velocityScores);

        // Return array of product IDs sorted by velocity
        return array_keys($velocityScores);
    }

    /**
     * Get products with combined performance score.
     *
     * Score combines margin, sales velocity, stock, and review count for ranking.
     *
     * @param int $days Number of days to check past sales.
     * @return array Array of product IDs sorted by performance score descending.
     *
     * @example
     * ```php
     * $performance = WooCommerce::getProductsPerformanceScore(30);
     * ```
     */
    public static function getProductsPerformanceScore(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales quantities in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store performance scores
        $performanceScores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if no stock
            if ($stock <= 0) continue;

            // Get prices
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate margin percentage
            $margin = (($regularPrice - $costPrice) / $regularPrice) * 100;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Calculate sales velocity
            $velocity = $qtySold / max($stock, 1);

            // Get review count
            $reviews = (int) $product->get_review_count();

            // Calculate combined performance score with weighted factors
            $score = ($margin * 0.4) + ($velocity * 0.4 * 100) + ($reviews * 0.2);

            // Store rounded score
            $performanceScores[$id] = round($score, 2);
        }

        // Sort products by performance score descending
        arsort($performanceScores);

        // Return array of product IDs sorted by performance score
        return array_keys($performanceScores);
    }

    /**
     * Get products recommended for clearance.
     *
     * Combines unsold status, low margin, and high stock to identify products for discounting or removal.
     *
     * @param int $days Number of days to check unsold status.
     * @param float $marginThreshold Margin percentage threshold.
     * @param int $stockThreshold Stock quantity threshold.
     * @return array Array of product IDs recommended for clearance.
     *
     * @example
     * ```php
     * $clearanceProducts = WooCommerce::getProductsForClearance(90, 20, 50);
     * ```
     */
    public static function getProductsForClearance(int $days = 90, float $marginThreshold = 20.0, int $stockThreshold = 50): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for unsold check
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Get IDs of products sold in last X days
        $soldProductIds = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value
        FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
        INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
        WHERE meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
    ", $since));

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store clearance recommendations
        $clearance = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Skip products that have been sold recently
            if (in_array($id, $soldProductIds, true)) continue;

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if stock below threshold
            if ($stock < $stockThreshold) continue;

            // Get prices
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate margin percentage
            $margin = (($regularPrice - $costPrice) / $regularPrice) * 100;

            // Skip if margin above threshold
            if ($margin > $marginThreshold) continue;

            // Add product ID to clearance list
            $clearance[] = $id;
        }

        // Return array of product IDs recommended for clearance
        return $clearance;
    }

    /**
     * Get products recommended for promotion.
     *
     * Combines high margin, recent price drops, and low stock to identify products to push for sales.
     *
     * @param int $limit Maximum number of products to return.
     * @param float $marginThreshold Minimum margin percentage.
     * @return array Array of product IDs recommended for promotion, sorted by impact.
     *
     * @example
     * ```php
     * $promoProducts = WooCommerce::getProductsForPromotion(10, 30);
     * ```
     */
    public static function getProductsForPromotion(int $limit = 10, float $marginThreshold = 30.0): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store products with price drop and margin
        $promoCandidates = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get prices
            $currentPrice = (float) $product->get_regular_price();
            $prevPrice = (float) get_post_meta($id, '_price_prev', true);

            // Skip if no price drop
            if ($prevPrice <= 0 || $currentPrice >= $prevPrice) continue;

            // Calculate margin percentage
            $costPrice = (float) get_post_meta($id, '_cost_price', true);
            if ($costPrice <= 0 || $costPrice > $currentPrice) continue;

            $margin = (($currentPrice - $costPrice) / $currentPrice) * 100;

            // Skip if margin below threshold
            if ($margin < $marginThreshold) continue;

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if stock is zero
            if ($stock <= 0) continue;

            // Calculate potential impact = price drop * stock
            $impact = ($prevPrice - $currentPrice) * $stock;

            // Store product and impact
            $promoCandidates[$id] = $impact;
        }

        // Sort by highest impact
        arsort($promoCandidates);

        // Return limited product IDs
        return array_slice(array_keys($promoCandidates), 0, $limit);
    }

    /**
     * Get products recommended for reorder.
     *
     * Combines low stock, high velocity, and good margin to identify products to reorder urgently.
     *
     * @param int $stockThreshold Stock quantity threshold.
     * @param int $days Number of days to calculate sales velocity.
     * @param float $marginThreshold Minimum margin percentage.
     * @return array Array of product IDs recommended for reorder.
     *
     * @example
     * ```php
     * $reorderProducts = WooCommerce::getProductsForReorder(10, 30, 25);
     * ```
     */
    public static function getProductsForReorder(int $stockThreshold = 10, int $days = 30, float $marginThreshold = 25.0): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales quantities in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store reorder recommendations
        $reorder = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if stock above threshold
            if ($stock > $stockThreshold) continue;

            // Get prices
            $currentPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($currentPrice <= 0 || $costPrice <= 0 || $costPrice > $currentPrice) continue;

            // Calculate margin percentage
            $margin = (($currentPrice - $costPrice) / $currentPrice) * 100;

            // Skip if margin below threshold
            if ($margin < $marginThreshold) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Skip if no sales
            if ($qtySold <= 0) continue;

            // Calculate daily sales velocity
            $velocity = $qtySold / max($days, 1);

            // Skip if velocity is too low (optional threshold 0.1 per day)
            if ($velocity < 0.1) continue;

            // Add product to reorder list
            $reorder[] = $id;
        }

        // Return array of product IDs recommended for reorder
        return $reorder;
    }

    /**
     * Get products with highest profit-to-stock ratio.
     *
     * Helps identify products that yield high profit relative to their current stock.
     *
     * @return array Array of product IDs sorted by profit-to-stock ratio descending.
     *
     * @example
     * ```php
     * $profitStock = WooCommerce::getProductsProfitToStockRatio();
     * ```
     */
    public static function getProductsProfitToStockRatio(): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store profit-to-stock ratio
        $ratio = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock quantity
            $stock = (int) $product->get_stock_quantity();

            // Skip if no stock
            if ($stock <= 0) continue;

            // Get prices
            $regularPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($regularPrice <= 0 || $costPrice <= 0 || $costPrice > $regularPrice) continue;

            // Calculate total potential profit
            $profit = ($regularPrice - $costPrice) * $stock;

            // Calculate profit-to-stock ratio
            $ratio[$id] = $profit / $stock;
        }

        // Sort products by ratio descending
        arsort($ratio);

        // Return array of product IDs sorted by profit-to-stock ratio
        return array_keys($ratio);
    }

    /**
     * Get products with most consistent sales over a period.
     *
     * Uses standard deviation of daily sales to measure consistency.
     *
     * @param int $days Number of days to analyze.
     * @return array Array of product IDs sorted by most consistent sales.
     *
     * @example
     * ```php
     * $consistentProducts = WooCommerce::getProductsWithConsistentSales(30);
     * ```
     */
    public static function getProductsWithConsistentSales(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query daily sales per product
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, DATE(p.post_date) AS sale_date, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value, DATE(p.post_date)
    ", $since), ARRAY_A);

        // Organize sales by product ID
        $sales = [];
        foreach ($results as $row) {
            $id = (int) $row['product_id'];
            $sales[$id][] = (int) $row['qty_sold'];
        }

        // Array to store consistency scores
        $consistency = [];

        // Calculate standard deviation for each product
        foreach ($sales as $id => $quantities) {
            $mean = array_sum($quantities) / count($quantities);
            $variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $quantities)) / count($quantities);
            $stdDev = sqrt($variance);

            // Consistency score = inverse of standard deviation
            $consistency[$id] = $stdDev > 0 ? 1 / $stdDev : 0;
        }

        // Sort products by highest consistency
        arsort($consistency);

        // Return array of product IDs sorted by consistency
        return array_keys($consistency);
    }

    /**
     * Get products ranked by review-weighted popularity.
     *
     * Combines review count and average rating with sales to rank popularity.
     *
     * @param int $days Number of days to analyze recent sales.
     * @return array Array of product IDs sorted by popularity score descending.
     *
     * @example
     * ```php
     * $popularProducts = WooCommerce::getProductsByReviewWeightedPopularity(30);
     * ```
     */
    public static function getProductsByReviewWeightedPopularity(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store popularity scores
        $popularity = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get sales quantity
            $qtySold = $salesData[$id] ?? 0;

            // Get review count
            $reviewCount = (int) $product->get_review_count();

            // Get average rating
            $avgRating = (float) $product->get_average_rating();

            // Popularity score = sales * (1 + log(1 + reviews)) * average rating
            $score = $qtySold * (1 + log(1 + $reviewCount)) * $avgRating;

            // Store rounded score
            $popularity[$id] = round($score, 2);
        }

        // Sort products by popularity descending
        arsort($popularity);

        // Return array of product IDs sorted by popularity
        return array_keys($popularity);
    }

    /**
     * Get products ranked by risk of stockout.
     *
     * Combines current stock, average daily sales, and lead time to estimate risk.
     *
     * @param int $days Number of days to calculate sales velocity.
     * @param int $leadTime Expected restock lead time in days.
     * @return array Array of product IDs sorted by highest stockout risk.
     *
     * @example
     * ```php
     * $stockoutRisk = WooCommerce::getProductsStockoutRisk(30, 7);
     * ```
     */
    public static function getProductsStockoutRisk(int $days = 30, int $leadTime = 7): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales quantities in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store stockout risk scores
        $riskScores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get current stock
            $stock = (int) $product->get_stock_quantity();

            // Skip if no stock
            if ($stock <= 0) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Calculate daily sales rate
            $dailySales = $qtySold / max($days, 1);

            // Calculate stockout risk score = (leadTime * dailySales) / stock
            $risk = ($leadTime * $dailySales) / max($stock, 1);

            // Store risk score
            $riskScores[$id] = round($risk, 2);
        }

        // Sort products by highest risk descending
        arsort($riskScores);

        // Return array of product IDs sorted by stockout risk
        return array_keys($riskScores);
    }

    /**
     * Get products ranked by marketing priority.
     *
     * Combines margin, sales velocity, price drop, and review popularity to rank products for promotion.
     *
     * @param int $days Number of days to analyze sales.
     * @return array Array of product IDs sorted by marketing priority score descending.
     *
     * @example
     * ```php
     * $marketingPriority = WooCommerce::getProductsMarketingPriority(30);
     * ```
     */
    public static function getProductsMarketingPriority(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store marketing priority scores
        $priorityScores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get prices
            $currentPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);
            $prevPrice = (float) get_post_meta($id, '_price_prev', true);

            // Skip if prices invalid
            if ($currentPrice <= 0 || $costPrice <= 0 || $costPrice > $currentPrice) continue;

            // Calculate margin percentage
            $margin = (($currentPrice - $costPrice) / $currentPrice) * 100;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Get review count and average rating
            $reviewCount = (int) $product->get_review_count();
            $avgRating = (float) $product->get_average_rating();

            // Calculate price drop factor
            $priceDrop = max($prevPrice - $currentPrice, 0);

            // Marketing priority score = margin*0.3 + dailySales*0.3 + priceDrop*0.2 + reviewFactor*0.2
            $dailySales = $qtySold / max($days, 1);
            $reviewFactor = $reviewCount * $avgRating;
            $score = ($margin * 0.3) + ($dailySales * 0.3) + ($priceDrop * 0.2) + ($reviewFactor * 0.2);

            // Store rounded score
            $priorityScores[$id] = round($score, 2);
        }

        // Sort products by highest marketing priority
        arsort($priorityScores);

        // Return array of product IDs sorted by marketing priority
        return array_keys($priorityScores);
    }

    /**
     * Get products with combined profitability and popularity score.
     *
     * Combines predicted profit, sales velocity, and review popularity to identify top-performing products.
     *
     * @param int $days Number of days to calculate recent sales and velocity.
     * @return array Array of product IDs sorted by combined score descending.
     *
     * @example
     * ```php
     * $topPerformers = WooCommerce::getProductsTopProfitPopularity(30);
     * ```
     */
    public static function getProductsTopProfitPopularity(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date for sales data
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed', 'wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store combined scores
        $combinedScores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get prices
            $currentPrice = (float) $product->get_regular_price();
            $costPrice = (float) get_post_meta($id, '_cost_price', true);

            // Skip if prices invalid
            if ($currentPrice <= 0 || $costPrice <= 0 || $costPrice > $currentPrice) continue;

            // Calculate margin per unit
            $marginPerUnit = $currentPrice - $costPrice;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Estimate predicted profit
            $predictedProfit = $marginPerUnit * $qtySold;

            // Get review data
            $reviewCount = (int) $product->get_review_count();
            $avgRating = (float) $product->get_average_rating();

            // Calculate combined popularity factor
            $popularityFactor = $qtySold * (1 + log(1 + $reviewCount)) * $avgRating;

            // Combined score = predicted profit + popularity factor
            $score = $predictedProfit + $popularityFactor;

            // Store rounded score
            $combinedScores[$id] = round($score, 2);
        }

        // Sort products by highest combined score
        arsort($combinedScores);

        // Return array of product IDs sorted by combined profitability and popularity
        return array_keys($combinedScores);
    }

    /**
     * Get products with highest profit-to-risk ratio.
     *
     * Combines predicted profit with stockout risk to identify products yielding high profit with low risk.
     *
     * @param int $days Number of days to analyze sales.
     * @param int $leadTime Lead time in days for restocking.
     * @return array Array of product IDs sorted by profit-to-risk ratio descending.
     *
     * @example
     * ```php
     * $profitRisk = WooCommerce::getProductsProfitToRiskRatio(30, 7);
     * ```
     */
    public static function getProductsProfitToRiskRatio(int $days = 30, int $leadTime = 7): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent sales quantities
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array: product_id => qty_sold
        $salesData = [];
        foreach ($results as $row) {
            $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store profit-to-risk ratio
        $ratio = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock
            $stock = (int) $product->get_stock_quantity();
            if ($stock <= 0) continue;

            // Get prices
            $price = (float) $product->get_regular_price();
            $cost = (float) get_post_meta($id, '_cost_price', true);
            if ($price <= 0 || $cost <= 0 || $cost > $price) continue;

            // Calculate predicted profit
            $qtySold = $salesData[$id] ?? 0;
            $predictedProfit = ($price - $cost) * $qtySold;

            // Calculate daily sales rate
            $dailySales = $qtySold / max($days, 1);

            // Calculate stockout risk
            $risk = ($leadTime * $dailySales) / max($stock, 1);

            // Skip if risk is zero to avoid division by zero
            if ($risk <= 0) continue;

            // Calculate profit-to-risk ratio
            $ratio[$id] = round($predictedProfit / $risk, 2);
        }

        // Sort by highest ratio
        arsort($ratio);

        // Return array of product IDs sorted by profit-to-risk ratio
        return array_keys($ratio);
    }

    /**
     * Get products with trending demand.
     *
     * Compares recent sales to previous period to identify products with increasing demand.
     *
     * @param int $recentDays Number of recent days to analyze.
     * @param int $previousDays Number of days in previous period to compare.
     * @return array Array of product IDs sorted by demand growth descending.
     *
     * @example
     * ```php
     * $trendingProducts = WooCommerce::getProductsTrendingDemand(14, 14);
     * ```
     */
    public static function getProductsTrendingDemand(int $recentDays = 14, int $previousDays = 14): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start dates
        $recentSince = date('Y-m-d H:i:s', strtotime("-{$recentDays} days"));
        $previousSince = date('Y-m-d H:i:s', strtotime("-".($recentDays + $previousDays)." days"));

        // Query recent sales
        $recentResults = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $recentSince), ARRAY_A);

        // Convert recent sales to associative array
        $recentSales = [];
        foreach ($recentResults as $row) $recentSales[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Query previous period sales
        $previousResults = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
          AND p.post_date < %s
        GROUP BY meta_value
    ", $previousSince, $recentSince), ARRAY_A);

        // Convert previous sales to associative array
        $previousSales = [];
        foreach ($previousResults as $row) $previousSales[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Array to store growth rate
        $growth = [];

        // Loop through all products with recent sales
        foreach ($recentSales as $id => $qty) {
            $prevQty = $previousSales[$id] ?? 0;

            // Calculate growth = (recent - previous) / max(previous,1)
            $growth[$id] = round(($qty - $prevQty) / max($prevQty, 1), 2);
        }

        // Sort by highest growth descending
        arsort($growth);

        // Return array of product IDs sorted by trending demand
        return array_keys($growth);
    }

    /**
     * Get products with engagement-adjusted revenue score.
     *
     * Combines total revenue with review count and average rating for engagement weighting.
     *
     * @param int $days Number of days to calculate recent revenue.
     * @return array Array of product IDs sorted by engagement-adjusted revenue descending.
     *
     * @example
     * ```php
     * $engagementRevenue = WooCommerce::getProductsEngagementAdjustedRevenue(30);
     * ```
     */
    public static function getProductsEngagementAdjustedRevenue(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent revenue
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value * pm.meta_value) AS revenue
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        INNER JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = oi_meta.meta_value AND pm.meta_key = '_regular_price'
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array
        $revenueData = [];
        foreach ($results as $row) $revenueData[(int)$row['product_id']] = (float)$row['revenue'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store engagement-adjusted revenue
        $adjustedRevenue = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get base revenue
            $revenue = $revenueData[$id] ?? 0;
            if ($revenue <= 0) continue;

            // Get reviews
            $reviewCount = (int) $product->get_review_count();
            $avgRating = (float) $product->get_average_rating();

            // Engagement factor = 1 + log(1 + reviewCount) * avgRating
            $engagementFactor = 1 + log(1 + $reviewCount) * $avgRating;

            // Calculate engagement-adjusted revenue
            $adjustedRevenue[$id] = round($revenue * $engagementFactor, 2);
        }

        // Sort by highest adjusted revenue
        arsort($adjustedRevenue);

        // Return array of product IDs sorted by engagement-adjusted revenue
        return array_keys($adjustedRevenue);
    }

    /**
     * Get products ranked by margin-adjusted sales velocity.
     *
     * Combines sales velocity and margin percentage to identify high-earning fast movers.
     *
     * @param int $days Number of days to calculate sales velocity.
     * @return array Array of product IDs sorted by margin-adjusted velocity descending.
     *
     * @example
     * ```php
     * $marginVelocity = WooCommerce::getProductsMarginAdjustedVelocity(30);
     * ```
     */
    public static function getProductsMarginAdjustedVelocity(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb; // Access WordPress database

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent sales
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store margin-adjusted velocity
        $scores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;
            if ($qtySold <= 0) continue;

            // Get prices
            $price = (float) $product->get_regular_price();
            $cost = (float) get_post_meta($id, '_cost_price', true);
            if ($price <= 0 || $cost <= 0 || $cost > $price) continue;

            // Calculate margin percentage
            $margin = (($price - $cost) / $price) * 100;

            // Calculate daily sales velocity
            $velocity = $qtySold / max($days, 1);

            // Score = margin * daily velocity
            $scores[$id] = round($margin * $velocity, 2);
        }

        // Sort descending
        arsort($scores);

        // Return sorted product IDs
        return array_keys($scores);
    }

    /**
     * Get products with potential price elasticity.
     *
     * Detects products where sales are sensitive to price changes.
     *
     * @param int $days Number of days to analyze sales and price changes.
     * @return array Array of product IDs sorted by estimated price sensitivity descending.
     *
     * @example
     * ```php
     * $elasticProducts = WooCommerce::getProductsPriceElasticity(30);
     * ```
     */
    public static function getProductsPriceElasticity(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales with order date and price
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold, AVG(pm.meta_value) AS avg_price
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        INNER JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = oi_meta.meta_value AND pm.meta_key = '_regular_price'
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = ['qty' => (int)$row['qty_sold'], 'avgPrice' => (float)$row['avg_price']];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array for elasticity scores
        $elasticity = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();
            $data = $salesData[$id] ?? null;
            if (!$data) continue;

            // Current price
            $currentPrice = (float) $product->get_regular_price();
            if ($currentPrice <= 0) continue;

            // Estimated elasticity = relative sales change / relative price change
            $relativePriceChange = ($currentPrice - $data['avgPrice']) / max($data['avgPrice'], 0.01);
            $relativeSalesChange = ($data['qty'] - 1) / 1; // Minimal baseline adjustment to avoid zero
            $elasticity[$id] = round(abs($relativeSalesChange / max($relativePriceChange, 0.01)), 2);
        }

        // Sort descending
        arsort($elasticity);

        // Return sorted product IDs
        return array_keys($elasticity);
    }

    /**
     * Get products with review growth trend.
     *
     * Identifies products with rapidly increasing review activity.
     *
     * @param int $days Number of days to analyze new reviews.
     * @return array Array of product IDs sorted by review growth descending.
     *
     * @example
     * ```php
     * $reviewGrowth = WooCommerce::getProductsReviewGrowthTrend(30);
     * ```
     */
    public static function getProductsReviewGrowthTrend(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query new approved reviews
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT comment_post_ID AS product_id, COUNT(*) AS new_reviews
        FROM {$wpdb->comments} c
        WHERE comment_type = 'review'
          AND comment_approved = 1
          AND comment_date >= %s
        GROUP BY comment_post_ID
    ", $since), ARRAY_A);

        // Convert to associative array
        $reviewGrowth = [];
        foreach ($results as $row) $reviewGrowth[(int)$row['product_id']] = (int)$row['new_reviews'];

        // Sort descending
        arsort($reviewGrowth);

        // Return sorted product IDs
        return array_keys($reviewGrowth);
    }

    /**
     * Get products under inventory pressure.
     *
     * Combines low stock with high sales velocity to identify items at risk of running out.
     *
     * @param int $stockThreshold Minimum stock level to consider at risk.
     * @param int $days Number of days to calculate sales velocity.
     * @return array Array of product IDs sorted by inventory pressure descending.
     *
     * @example
     * ```php
     * $inventoryPressure = WooCommerce::getProductsInventoryPressure(10, 30);
     * ```
     */
    public static function getProductsInventoryPressure(int $stockThreshold = 10, int $days = 30): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query product sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store inventory pressure scores
        $pressure = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock
            $stock = (int) $product->get_stock_quantity();

            // Skip if stock above threshold
            if ($stock > $stockThreshold) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Calculate daily velocity
            $velocity = $qtySold / max($days, 1);

            // Inventory pressure = velocity / max(stock,1)
            $pressure[$id] = round($velocity / max($stock, 1), 2);
        }

        // Sort descending
        arsort($pressure);

        // Return array of product IDs sorted by inventory pressure
        return array_keys($pressure);
    }

    /**
     * Get high-potential upsell candidates.
     *
     * Combines products with high margin, moderate price, and strong sales velocity.
     *
     * @param int $days Number of days to analyze sales.
     * @param float $minMargin Minimum margin percentage.
     * @param float $maxPrice Maximum price threshold for upsell.
     * @return array Array of product IDs sorted by upsell potential descending.
     *
     * @example
     * ```php
     * $upsellCandidates = WooCommerce::getProductsUpsellCandidates(30, 30, 100);
     * ```
     */
    public static function getProductsUpsellCandidates(int $days = 30, float $minMargin = 30.0, float $maxPrice = 100.0): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales data
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store upsell scores
        $scores = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get prices
            $price = (float) $product->get_regular_price();
            $cost = (float) get_post_meta($id, '_cost_price', true);
            if ($price <= 0 || $cost <= 0 || $cost > $price || $price > $maxPrice) continue;

            // Calculate margin
            $margin = (($price - $cost) / $price) * 100;
            if ($margin < $minMargin) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;
            if ($qtySold <= 0) continue;

            // Calculate daily velocity
            $velocity = $qtySold / max($days, 1);

            // Upsell score = margin * velocity
            $scores[$id] = round($margin * $velocity, 2);
        }

        // Sort descending
        arsort($scores);

        // Return sorted product IDs
        return array_keys($scores);
    }

    /**
     * Get products ranked by profit stability.
     *
     * Measures stability by comparing average profit per sale to variance over time.
     *
     * @param int $days Number of days to analyze sales.
     * @return array Array of product IDs sorted by profit stability descending.
     *
     * @example
     * ```php
     * $profitStability = WooCommerce::getProductsProfitStability(30);
     * ```
     */
    public static function getProductsProfitStability(int $days = 30): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales data
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold, SUM(pm.meta_value * oi_meta.meta_value) AS revenue
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        INNER JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = oi_meta.meta_value AND pm.meta_key = '_regular_price'
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results
        $profitData = [];
        foreach ($results as $row) {
            $qty = (int)$row['qty_sold'];
            $revenue = (float)$row['revenue'];
            $profitData[(int)$row['product_id']] = ['avgProfit' => $revenue / max($qty,1), 'qty' => $qty];
        }

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store stability scores
        $stability = [];

        // Loop through each product
        foreach ($products as $product) {
            $id = $product->get_id();
            $data = $profitData[$id] ?? null;
            if (!$data) continue;

            // Get cost
            $cost = (float) get_post_meta($id, '_cost_price', true);
            if ($cost <= 0) continue;

            // Calculate profit variance (simplified approximation)
            $variance = $data['avgProfit'] / max($data['qty'],1); // Average per sale variance
            $stability[$id] = round($data['avgProfit'] / max($variance,0.01), 2);
        }

        // Sort descending
        arsort($stability);

        // Return sorted product IDs
        return array_keys($stability);
    }

    /**
     * Get products ranked by profitability index.
     *
     * Combines margin, stock, and recent sales to calculate an overall profitability index.
     *
     * @param int $days Number of days to analyze sales.
     * @return array Array of product IDs sorted by profitability index descending.
     *
     * @example
     * ```php
     * $profitabilityIndex = WooCommerce::getProductsProfitabilityIndex(30);
     * ```
     */
    public static function getProductsProfitabilityIndex(int $days = 30): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent sales quantities
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert results
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store profitability index
        $index = [];

        // Loop through products
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get prices and stock
            $price = (float)$product->get_regular_price();
            $cost = (float)get_post_meta($id, '_cost_price', true);
            $stock = (int)$product->get_stock_quantity();
            if ($price <= 0 || $cost <= 0 || $cost > $price || $stock <= 0) continue;

            // Calculate margin percentage
            $margin = (($price - $cost) / $price) * 100;

            // Get recent sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Profitability index = margin * qtySold * stock
            $index[$id] = round($margin * $qtySold * $stock, 2);
        }

        // Sort descending
        arsort($index);

        // Return sorted product IDs
        return array_keys($index);
    }

    /**
     * Get slow-moving products.
     *
     * Identifies products with low sales velocity relative to stock.
     *
     * @param int $days Number of days to analyze sales.
     * @param int $threshold Max average daily sales to consider slow.
     * @return array Array of product IDs sorted by slowest movers first.
     *
     * @example
     * ```php
     * $slowMovers = WooCommerce::getProductsSlowMovers(90, 1);
     * ```
     */
    public static function getProductsSlowMovers(int $days = 90, int $threshold = 1): array
    {
        // Check if WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent sales
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store slow movers
        $slow = [];

        // Loop through products
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get stock
            $stock = (int)$product->get_stock_quantity();

            // Skip if stock is zero
            if ($stock <= 0) continue;

            // Calculate daily sales
            $qtySold = $salesData[$id] ?? 0;
            $dailySales = $qtySold / max($days, 1);

            // If below threshold, consider slow
            if ($dailySales <= $threshold) {
                $slow[$id] = round($dailySales, 2);
            }
        }

        // Sort ascending (slowest first)
        asort($slow);

        // Return array of product IDs
        return array_keys($slow);
    }

    /**
     * Get products ranked by customer engagement score.
     *
     * Combines number of reviews, average rating, and recent sales for engagement-weighted ranking.
     *
     * @param int $days Number of days to analyze sales.
     * @return array Array of product IDs sorted by customer engagement descending.
     *
     * @example
     * ```php
     * $engagementScore = WooCommerce::getProductsCustomerEngagement(30);
     * ```
     */
    public static function getProductsCustomerEngagement(int $days = 30): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query recent sales
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS product_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_product_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['product_id']] = (int)$row['qty_sold'];

        // Get all published products
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

        // Array to store engagement scores
        $engagement = [];

        // Loop through products
        foreach ($products as $product) {
            $id = $product->get_id();

            // Get review count and average rating
            $reviewCount = (int)$product->get_review_count();
            $avgRating = (float)$product->get_average_rating();

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;

            // Engagement score = (qtySold + 1) * (1 + log(1 + reviewCount)) * avgRating
            $engagement[$id] = round(($qtySold + 1) * (1 + log(1 + $reviewCount)) * $avgRating, 2);
        }

        // Sort descending
        arsort($engagement);

        // Return array of product IDs sorted by engagement
        return array_keys($engagement);
    }

    /**
     * Get variant IDs with highest sales in last X days.
     *
     * Helps identify top-performing product variations.
     *
     * @param int $days Number of days to analyze sales.
     * @param int $limit Number of variants to return.
     * @return array Array of variant IDs sorted by quantity sold descending.
     *
     * @example
     * ```php
     * $topVariantSales = WooCommerce::getTopSellingVariants(30, 10);
     * ```
     */
    public static function getTopSellingVariants(int $days = 30, int $limit = 10): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query variant sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS variant_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_variation_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $variantSales = [];
        foreach ($results as $row) $variantSales[(int)$row['variant_id']] = (int)$row['qty_sold'];

        // Sort descending
        arsort($variantSales);

        // Return top N variant IDs
        return array_slice(array_keys($variantSales), 0, $limit);
    }

    /**
     * Get variants with highest margin percentage.
     *
     * Calculates margin for each variant using cost and regular price.
     *
     * @param int $limit Number of variants to return.
     * @return array Array of variant IDs sorted by margin descending.
     *
     * @example
     * ```php
     * $highMarginVariants = WooCommerce::getHighMarginVariants(10);
     * ```
     */
    public static function getHighMarginVariants(int $limit = 10): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        // Get all products including variations
        $products = wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => ['variation']]);

        $variantMargins = [];

        // Loop through each variant
        foreach ($products as $variant) {
            $id = $variant->get_id();

            // Get prices
            $price = (float) $variant->get_regular_price();
            $cost = (float) get_post_meta($id, '_cost_price', true);
            if ($price <= 0 || $cost <= 0 || $cost > $price) continue;

            // Calculate margin %
            $margin = (($price - $cost) / $price) * 100;

            $variantMargins[$id] = round($margin, 2);
        }

        // Sort descending
        arsort($variantMargins);

        // Return top N variant IDs
        return array_slice(array_keys($variantMargins), 0, $limit);
    }

    /**
     * Get variants under stock risk.
     *
     * Identifies variants with low stock relative to recent sales.
     *
     * @param int $days Number of days to analyze sales.
     * @param int $stockThreshold Maximum stock considered risky.
     * @return array Array of variant IDs sorted by highest risk descending.
     *
     * @example
     * ```php
     * $variantStockRisk = WooCommerce::getVariantStockRisk(30, 5);
     * ```
     */
    public static function getVariantStockRisk(int $days = 30, int $stockThreshold = 5): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query variant sales in last X days
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS variant_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_variation_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $variantSales = [];
        foreach ($results as $row) $variantSales[(int)$row['variant_id']] = (int)$row['qty_sold'];

        // Get all variations
        $variants = wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => ['variation']]);

        $stockRisk = [];

        // Loop through each variant
        foreach ($variants as $variant) {
            $id = $variant->get_id();
            $stock = (int) $variant->get_stock_quantity();
            if ($stock > $stockThreshold) continue;

            // Get sold quantity
            $qtySold = $variantSales[$id] ?? 0;

            // Risk score = daily sales / stock
            $dailySales = $qtySold / max($days, 1);
            $stockRisk[$id] = round($dailySales / max($stock,1), 2);
        }

        // Sort descending
        arsort($stockRisk);

        // Return variant IDs sorted by stock risk
        return array_keys($stockRisk);
    }

    /**
     * Get variants with recent price drops.
     *
     * Compares current price to previously recorded price and identifies largest drops.
     *
     * @param int $limit Number of variants to return.
     * @return array Array of variant IDs sorted by largest price drop descending.
     *
     * @example
     * ```php
     * $priceDropVariants = WooCommerce::getVariantsWithPriceDrops(10);
     * ```
     */
    public static function getVariantsWithPriceDrops(int $limit = 10): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        // Get all published variations
        $variants = wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => ['variation']]);

        $priceDrops = [];

        // Loop through each variant
        foreach ($variants as $variant) {
            $id = $variant->get_id();
            $currentPrice = (float)$variant->get_regular_price();
            $prevPrice = (float)get_post_meta($id, '_price_prev', true);

            // Check if previous price exists and is higher than current
            if ($prevPrice > 0 && $currentPrice < $prevPrice) {
                $priceDrops[$id] = $prevPrice - $currentPrice;
            }
        }

        // Sort descending by price drop amount
        arsort($priceDrops);

        // Return top N variant IDs
        return array_slice(array_keys($priceDrops), 0, $limit);
    }

    /**
     * Get variants with review growth trend.
     *
     * Identifies variants with rapidly increasing review activity.
     *
     * @param int $days Number of days to analyze new reviews.
     * @return array Array of variant IDs sorted by review growth descending.
     *
     * @example
     * ```php
     * $reviewGrowthVariants = WooCommerce::getVariantReviewGrowthTrend(30);
     * ```
     */
    public static function getVariantReviewGrowthTrend(int $days = 30): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query approved reviews for variants
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT comment_post_ID AS variant_id, COUNT(*) AS new_reviews
        FROM {$wpdb->comments} c
        WHERE comment_type = 'review'
          AND comment_approved = 1
          AND comment_post_ID IN (
              SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation'
          )
          AND comment_date >= %s
        GROUP BY comment_post_ID
    ", $since), ARRAY_A);

        // Convert results to associative array
        $reviewGrowth = [];
        foreach ($results as $row) $reviewGrowth[(int)$row['variant_id']] = (int)$row['new_reviews'];

        // Sort descending
        arsort($reviewGrowth);

        // Return variant IDs sorted by review growth
        return array_keys($reviewGrowth);
    }

    /**
     * Get variants with upsell potential.
     *
     * Combines margin, price range, and recent sales to identify high-potential upsell variants.
     *
     * @param int $days Number of days to analyze sales.
     * @param float $minMargin Minimum margin percentage for upsell.
     * @param float $maxPrice Maximum price for upsell.
     * @return array Array of variant IDs sorted by upsell potential descending.
     *
     * @example
     * ```php
     * $upsellVariants = WooCommerce::getVariantUpsellCandidates(30, 30, 100);
     * ```
     */
    public static function getVariantUpsellCandidates(int $days = 30, float $minMargin = 30.0, float $maxPrice = 100.0): array
    {
        // Check WooCommerce is ready
        if (!self::guard()) return [];

        global $wpdb;

        // Calculate start date
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query sales for variants
        $results = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value AS variant_id, SUM(oi_meta.meta_value) AS qty_sold
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oi_meta ON oi.order_item_id = oi_meta.order_item_id
        INNER JOIN {$wpdb->posts} AS p ON oi.order_id = p.ID
        WHERE oi_meta.meta_key = '_variation_id'
          AND p.post_type = 'shop_order'
          AND p.post_status IN ('wc-completed','wc-processing')
          AND p.post_date >= %s
        GROUP BY meta_value
    ", $since), ARRAY_A);

        // Convert to associative array
        $salesData = [];
        foreach ($results as $row) $salesData[(int)$row['variant_id']] = (int)$row['qty_sold'];

        // Get all published variations
        $variants = wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => ['variation']]);

        $upsellScores = [];

        // Loop through each variant
        foreach ($variants as $variant) {
            $id = $variant->get_id();

            // Get prices
            $price = (float)$variant->get_regular_price();
            $cost = (float)get_post_meta($id, '_cost_price', true);
            if ($price <= 0 || $cost <= 0 || $cost > $price || $price > $maxPrice) continue;

            // Calculate margin %
            $margin = (($price - $cost) / $price) * 100;
            if ($margin < $minMargin) continue;

            // Get sold quantity
            $qtySold = $salesData[$id] ?? 0;
            if ($qtySold <= 0) continue;

            // Upsell score = margin * daily velocity
            $upsellScores[$id] = round($margin * ($qtySold / max($days,1)), 2);
        }

        // Sort descending
        arsort($upsellScores);

        // Return variant IDs sorted by upsell potential
        return array_keys($upsellScores);
    }
}
