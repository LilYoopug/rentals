<?php

/**
 * Trigger Functions
 * 
 * This file contains PHP functions that replace SQL triggers for rentals and returns.
 * These functions must be called explicitly in the application code when inserting,
 * updating, or deleting rentals and returns.
 */

require_once __DIR__ . '/functions.php';

/**
 * Check if a rental status reserves stock
 * 
 * @param string $status Rental status
 * @return bool True if status reserves stock
 */
function rental_status_reserves_stock($status)
{
    return in_array($status, ['menunggu', 'disetujui', 'mendatang', 'aktif'], true);
}

/**
 * Update product stock when rental is created
 * Called before inserting a new rental
 * 
 * @param int $product_id Product ID
 * @param string $status Rental status
 * @return bool True on success, false on failure
 */
function trigger_rental_before_insert($product_id, $status)
{
    if (!db_ready()) {
        return false;
    }

    // Only reserve stock if status requires it
    if (!rental_status_reserves_stock($status)) {
        return true;
    }

    // Decrease stock and update in_stock flag
    $affected = db_execute_count(
        'UPDATE products
         SET stock_available = stock_available - 1,
             in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
         WHERE id = ?
           AND status = "aktif"
           AND stock_available > 0',
        [$product_id]
    );

    // Check if exactly one row was updated (stock was available)
    if ($affected !== 1) {
        // Stock not available
        return false;
    }

    return true;
}

/**
 * Update product stock when rental is updated
 * Called before updating a rental
 * 
 * @param array $old_rental Old rental data (before update)
 * @param array $new_rental New rental data (after update)
 * @return bool True on success, false on failure
 */
function trigger_rental_before_update($old_rental, $new_rental)
{
    if (!db_ready()) {
        return false;
    }

    $old_product_id = (int) $old_rental['product_id'];
    $new_product_id = (int) $new_rental['product_id'];
    $old_status = (string) $old_rental['status'];
    $new_status = (string) $new_rental['status'];

    $old_reserves_stock = rental_status_reserves_stock($old_status);
    $new_reserves_stock = rental_status_reserves_stock($new_status);

    // Case 1: Product changed
    if ($old_product_id !== $new_product_id) {
        // Release stock from old product if it was reserved
        if ($old_reserves_stock) {
            db_execute(
                'UPDATE products
                 SET stock_available = LEAST(stock_total, stock_available + 1),
                     in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
                 WHERE id = ?',
                [$old_product_id]
            );
        }

        // Reserve stock from new product if needed
        if ($new_reserves_stock) {
            $affected = db_execute_count(
                'UPDATE products
                 SET stock_available = stock_available - 1,
                     in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
                 WHERE id = ?
                   AND status = "aktif"
                   AND stock_available > 0',
                [$new_product_id]
            );

            if ($affected !== 1) {
                // Stock not available, rollback old product stock change
                if ($old_reserves_stock) {
                    db_execute(
                        'UPDATE products
                         SET stock_available = stock_available - 1,
                             in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
                         WHERE id = ?',
                        [$old_product_id]
                    );
                }
                return false;
            }
        }
    } else {
        // Case 2: Same product, status changed
        if (!$old_reserves_stock && $new_reserves_stock) {
            // Status changed to reserve stock
            $affected = db_execute_count(
                'UPDATE products
                 SET stock_available = stock_available - 1,
                     in_stock = CASE WHEN stock_available - 1 > 0 THEN 1 ELSE 0 END
                 WHERE id = ?
                   AND status = "aktif"
                   AND stock_available > 0',
                [$new_product_id]
            );

            if ($affected !== 1) {
                return false;
            }
        } elseif ($old_reserves_stock && !$new_reserves_stock) {
            // Status changed to release stock
            db_execute(
                'UPDATE products
                 SET stock_available = LEAST(stock_total, stock_available + 1),
                     in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
                 WHERE id = ?',
                [$old_product_id]
            );
        }
    }

    return true;
}

/**
 * Update product stock when rental is deleted
 * Called before deleting a rental
 * 
 * @param array $rental Rental data to be deleted
 * @return bool True on success
 */
function trigger_rental_before_delete($rental)
{
    if (!db_ready()) {
        return false;
    }

    $product_id = (int) $rental['product_id'];
    $status = (string) $rental['status'];

    // Release stock if it was reserved
    if (rental_status_reserves_stock($status)) {
        db_execute(
            'UPDATE products
             SET stock_available = LEAST(stock_total, stock_available + 1),
                 in_stock = CASE WHEN LEAST(stock_total, stock_available + 1) > 0 THEN 1 ELSE 0 END
             WHERE id = ?',
            [$product_id]
        );
    }

    return true;
}

/**
 * Calculate fine amount and set returned_at timestamp
 * Called before inserting a return
 * 
 * @param int $rental_id Rental ID
 * @param string $status Return status
 * @param string|null $returned_at Returned timestamp (optional)
 * @return array Array with 'fine_amount' and 'returned_at' keys
 */
function trigger_return_before_insert($rental_id, $status, $returned_at = null)
{
    if (!db_ready()) {
        return ['fine_amount' => 0, 'returned_at' => null];
    }

    $result = ['fine_amount' => 0, 'returned_at' => null];

    // Set returned_at if status is 'selesai' and not provided
    if ($status === 'selesai' && empty($returned_at)) {
        $result['returned_at'] = date('Y-m-d H:i:s');
    } else {
        $result['returned_at'] = $returned_at;
    }

    // Calculate fine amount if completed
    if ($status === 'selesai') {
        $rental = db_one(
            'SELECT end_date, daily_rate FROM rentals WHERE id = ? LIMIT 1',
            [$rental_id]
        );

        if ($rental) {
            $end_date = $rental['end_date'];
            $daily_rate = (float) $rental['daily_rate'];
            $returned_date = $result['returned_at'] ? date('Y-m-d', strtotime($result['returned_at'])) : date('Y-m-d');

            // Calculate days late
            $days_late = max(0, (strtotime($returned_date) - strtotime($end_date)) / 86400);
            $result['fine_amount'] = $days_late * $daily_rate;
        }
    }

    return $result;
}

/**
 * Update rental status when return is inserted
 * Called after inserting a return
 * 
 * @param int $rental_id Rental ID
 * @param string $return_status Return status
 * @param string|null $returned_at Returned timestamp
 * @return bool True on success
 */
function trigger_return_after_insert($rental_id, $return_status, $returned_at = null)
{
    if (!db_ready()) {
        return false;
    }

    // If return is completed, mark rental as completed
    if ($return_status === 'selesai') {
        $completed_at = $returned_at ?? date('Y-m-d H:i:s');
        
        db_execute(
            'UPDATE rentals
             SET status = "selesai",
                 completed_at = COALESCE(?, NOW())
             WHERE id = ?
               AND status <> "selesai"',
            [$completed_at, $rental_id]
        );
    }

    return true;
}

/**
 * Calculate fine amount and set returned_at timestamp
 * Called before updating a return
 * 
 * @param int $rental_id Rental ID
 * @param string $status Return status
 * @param string|null $old_returned_at Old returned timestamp
 * @param string|null $new_returned_at New returned timestamp (optional)
 * @return array Array with 'fine_amount' and 'returned_at' keys
 */
function trigger_return_before_update($rental_id, $status, $old_returned_at = null, $new_returned_at = null)
{
    if (!db_ready()) {
        return ['fine_amount' => 0, 'returned_at' => null];
    }

    $result = ['fine_amount' => 0, 'returned_at' => null];

    // Set returned_at if status is 'selesai' and not provided
    if ($status === 'selesai' && empty($new_returned_at)) {
        $result['returned_at'] = $old_returned_at ?? date('Y-m-d H:i:s');
    } else {
        $result['returned_at'] = $new_returned_at;
    }

    // Calculate fine amount if completed
    if ($status === 'selesai') {
        $rental = db_one(
            'SELECT end_date, daily_rate FROM rentals WHERE id = ? LIMIT 1',
            [$rental_id]
        );

        if ($rental) {
            $end_date = $rental['end_date'];
            $daily_rate = (float) $rental['daily_rate'];
            $returned_date = $result['returned_at'] ? date('Y-m-d', strtotime($result['returned_at'])) : date('Y-m-d');

            // Calculate days late
            $days_late = max(0, (strtotime($returned_date) - strtotime($end_date)) / 86400);
            $result['fine_amount'] = $days_late * $daily_rate;
        }
    }

    return $result;
}

/**
 * Update rental status when return is updated
 * Called after updating a return
 * 
 * @param int $rental_id Rental ID
 * @param string $old_status Old return status
 * @param string $new_status New return status
 * @param string|null $returned_at Returned timestamp
 * @return bool True on success
 */
function trigger_return_after_update($rental_id, $old_status, $new_status, $returned_at = null)
{
    if (!db_ready()) {
        return false;
    }

    // If return status changed to completed, mark rental as completed
    if ($new_status === 'selesai' && $old_status !== 'selesai') {
        $completed_at = $returned_at ?? date('Y-m-d H:i:s');
        
        db_execute(
            'UPDATE rentals
             SET status = "selesai",
                 completed_at = COALESCE(?, NOW())
             WHERE id = ?
               AND status <> "selesai"',
            [$completed_at, $rental_id]
        );
    }

    return true;
}
