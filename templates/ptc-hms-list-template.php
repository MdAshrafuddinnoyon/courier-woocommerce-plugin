<?php

global $wpdb;

$limit = 10;
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // Current page number

$offset = ( $page - 1 ) * $limit;

$wcOrdersPageType = 'shop_order';

$order_counts = wp_count_posts($wcOrdersPageType);
$total_orders = isset($order_counts->publish) ? $order_counts->publish : 0;

$last_page = ceil( $total_orders / $limit );

$columns = ptc_order_list_columns();

$search = esc_attr($_GET['search'] ?? '');

$ids = [];

if ($search) {
    $ids = $wpdb->get_col(/** @lang text */ "
            SELECT DISTINCT post_id
            FROM {$wpdb->prefix}postmeta 
            WHERE (meta_key = 'billing_first_name' AND meta_value LIKE '%$search%') OR
                    (meta_key = 'billing_last_name' AND meta_value LIKE '%$search%') OR
                    (meta_key = 'ptc_consignment_id' AND meta_value LIKE '%$search%')
        ");
}


$args = [
    'limit' => $limit,
    'offset' => $offset,
    'type' => $wcOrdersPageType,
];

if ((int)($search)) { // if search is a number, then its order id
    $ids[] = (int)$search;
}

if ($ids) {
    $args['post__in'] = $ids;
}

$orders = wc_get_orders($args);

$search = $_GET['search'] ?? '';
?>


<form id="posts-filter" method="get">

    <p class="search-box">
        <label class="screen-reader-text" for="post-search-input">Search orders:</label>
        <input type="search" id="post-search-input" name="search" value="<?php echo $search ?>">
        <input type="hidden" name="page" class="post_type_page" value="<?php echo PTC_PLUGIN_ORDERS_PAGE_TYPE ?>">
        <input type="submit" id="search-submit" class="button" value="Search orders">
    </p>

    <div class="tablenav top">

        <div class="alignleft actions">
            <label for="filter-by-date" class="screen-reader-text">Filter by date</label>
            <select name="m" id="filter-by-date">
                <option selected="selected" value="0">All dates</option>
                <option value="202402">February 2024</option>
                <option value="202311">November 2023</option>
                <option value="202310">October 2023</option>
                <option value="202309">September 2023</option>
            </select>
            <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
        </div>
        <div class="tablenav-pages one-page">
            <span class="displaying-num"><?php echo count($orders); ?> items</span>
            <span class="pagination-links"><span class="tablenav-pages-navspan button disabled"
                                                 aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
            <span class="paging-input"><label for="current-page-selector"
                                              class="screen-reader-text">Current Page</label><input class="current-page"
                                                                                                    id="current-page-selector"
                                                                                                    type="text"
                                                                                                    name="paged"
                                                                                                    value="1" size="1"
                                                                                                    aria-describedby="table-paging"><span
                        class="tablenav-paging-text"> of <span class="total-pages">1</span></span></span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span></span></div>
        <br class="clear">
    </div>
    <h2 class="screen-reader-text">Orders list</h2>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
        <tr>
            <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
                <label for="cb-select-all-1">
                    <span class="screen-reader-text">Select All</span>
                </label>
            </td>

            <?php foreach (ptc_order_list_columns() as $column): ?>
                <th scope="col" id="order_number"
                    class="manage-column column-order_number column-primary sortable desc">
                    <span> <?php echo $column ?> </span>
                </th>
            <?php endforeach; ?>
        </tr>
        </thead>

        <tbody id="the-list">
            <?php foreach ($orders as $order): ?>

            <?php
                $orderId = $order->get_id();
                $customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $total = $order->get_total();
                $consignmentId = $order->get_meta('ptc_consignment_id');
                $currencyCode = $order->get_currency();
                $currencySymbol = get_woocommerce_currency_symbol($currencyCode);
                $date = date("F jS, Y", strtotime($order->get_date_created()));
                $editLink = get_edit_post_link($orderId);
            ?>

            <tr id="post-33" class="author-self level-0 post-<?php echo $orderId ?> type-shop_order">
                <th scope="row" class="check-column">
                    <input id="cb-select-<?php echo $orderId ?>" type="checkbox" name="post[]" value="<?php echo $orderId ?>">
                </th>

                <?php foreach ($columns as $key => $column): ?>

                    <?php switch ($key):
                        case 'order_number': ?>
                               <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                    <a href="<?php echo $editLink; ?>" class="order-view">
                                        <strong>#<?php echo $orderId .'-'. $customerName;  ?></strong>
                                    </a>
                                </td>
                        <?php break; ?>

                        <?php case 'date': ?>
                            <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                        <span>
                                            <?php echo $date; ?>
                                        </span>
                            </td>
                        <?php break; ?>

                        <?php case 'status': ?>
                            <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                        <span>
                                           <?php echo $order->get_status(); ?>
                                        </span>
                            </td>
                        <?php break; ?>

                        <?php case 'total': ?>
                            <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                        <span>
                                            <?php echo $currencySymbol . $total; ?>
                                        </span>
                            </td>
                        <?php break; ?>

                        <?php case 'pathao': ?>

                            <?php if (!$consignmentId): ?>
                                <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                    <button class="ptc-open-modal-button" data-order-id="<?php echo $orderId ?>">Send with Pathao</button>
                                </td>
                            <?php else: ?>
                                <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                    <span>
                                        <?php echo $consignmentId; ?>
                                    </span>
                                </td>
                            <?php endif; ?>

                        <?php break; ?>

                        <?php case 'pathao_status': ?>
                            <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                        <span>
                                           <?php echo ucfirst(get_post_meta($orderId, 'ptc_status', true)); ?>
                                        </span>
                            </td>
                        <?php break; ?>


                        <?php case 'pathao_delivery_fee': ?>
                            <td class="order_number column-order_number has-row-actions column-primary" data-colname="Order">
                                        <span>
                                            <?php echo get_post_meta($orderId, 'ptc_delivery_fee', true); ?>
                                        </span>
                            </td>
                        <?php break; ?>

                        <?php default:

                            $columnValue = ptc_order_list_column_values_callback("", $column, $orderId);
                        ?>
                            <td>
                                <span><?php echo esc_html($columnValue); ?></span>
                            </td>

                    <?php endswitch; ?>

                <?php endforeach; ?>

                <?php endforeach; ?>
            </tr>
        </tbody>

    </table>


</form>