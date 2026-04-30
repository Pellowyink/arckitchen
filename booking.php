<?php
/**
 * Booking page - Redirects to inquiry.php which is now the main booking flow
 * The inquiry page has the cart system integrated with the booking form
 */

require_once __DIR__ . '/includes/functions.php';

// If there's a flash message, pass it along
$flashMessage = getFlashMessage('success');
if ($flashMessage) {
    setFlashMessage('success', $flashMessage);
}

redirect('inquiry.php');
exit;

