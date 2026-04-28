<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Book Now';
$errors = [];
$packages = getPackages();

if (isPostRequest()) {
    $errors = validateRequiredFields([
        'full_name' => 'Full name',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'event_date' => 'Event date',
        'event_type' => 'Event type',
        'guest_count' => 'Guest count',
    ]);

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ((int) ($_POST['guest_count'] ?? 0) <= 0) {
        $errors[] = 'Guest count should be greater than zero.';
    }

    if (!$errors) {
        $saved = saveInquiry([
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
            'event_date' => trim($_POST['event_date']),
            'event_type' => trim($_POST['event_type']),
            'guest_count' => (int) $_POST['guest_count'],
            'package_interest' => trim($_POST['package_interest'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
        ]);

        if ($saved) {
            setFlashMessage('success', 'Your inquiry has been submitted. ARC Kitchen will contact you shortly.');
            redirect('booking.php');
        }

        $errors[] = 'Database connection is unavailable. Please import the SQL file and confirm your MySQL credentials.';
    }
}

$flashMessage = getFlashMessage('success');

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero-card reveal">
            <span class="eyebrow">Book Now</span>
            <h1>Booking page placeholder with complete inquiry structure</h1>
            <p>Keep this full booking flow in place, then replace the copy and package names with your finalized event booking content later.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-card reveal">
            <div class="split-layout">
                <div class="form-card">
                    <h2>Booking / Inquiry Form</h2>

                    <?php if ($flashMessage): ?>
                        <div class="flash success"><?php echo escape($flashMessage); ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="error-list">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo escape($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" data-validate>
                        <div class="form-grid">
                            <div class="field">
                                <label for="full_name">Full Name</label>
                                <input id="full_name" name="full_name" type="text" required value="<?php echo escape($_POST['full_name'] ?? ''); ?>">
                            </div>
                            <div class="field">
                                <label for="email">Email Address</label>
                                <input id="email" name="email" type="email" required value="<?php echo escape($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="field">
                                <label for="phone">Phone Number</label>
                                <input id="phone" name="phone" type="text" required value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="field">
                                <label for="event_date">Event Date</label>
                                <input id="event_date" name="event_date" type="date" required value="<?php echo escape($_POST['event_date'] ?? ''); ?>">
                            </div>
                            <div class="field">
                                <label for="event_type">Event Type</label>
                                <select id="event_type" name="event_type" required>
                                    <option value="">Select event type</option>
                                    <?php
                                    $eventTypes = ['Birthday', 'Wedding', 'Corporate', 'Baptism', 'Family Gathering', 'Other'];
                                    foreach ($eventTypes as $eventType):
                                        $selected = ($_POST['event_type'] ?? '') === $eventType ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo escape($eventType); ?>" <?php echo $selected; ?>><?php echo escape($eventType); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="guest_count">Guest Count</label>
                                <input id="guest_count" name="guest_count" type="number" min="1" required value="<?php echo escape($_POST['guest_count'] ?? ''); ?>">
                            </div>
                            <div class="field-full">
                                <label for="package_interest">Preferred Package</label>
                                <select id="package_interest" name="package_interest">
                                    <option value="">No preferred package yet</option>
                                    <?php foreach ($packages as $package): ?>
                                        <?php $selected = ($_POST['package_interest'] ?? '') === $package['name'] ? 'selected' : ''; ?>
                                        <option value="<?php echo escape($package['name']); ?>" <?php echo $selected; ?>>
                                            <?php echo escape($package['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-full">
                                <label for="message">Additional Notes</label>
                                <textarea id="message" name="message" placeholder="Tell us about your preferred dishes, location, or service style."><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="stack-inline">
                            <button type="submit" class="button">Submit Inquiry</button>
                        </div>
                    </form>
                </div>

                <div class="info-card">
                    <h2>What happens next?</h2>
                    <p class="lead">Process placeholder copy. Replace this with your final booking flow explanation later.</p>
                    <div class="feature-card">
                        <h3>Step 1</h3>
                        <p>Placeholder process step for inquiry review.</p>
                    </div>
                    <div class="feature-card spacer-top-md">
                        <h3>Step 2</h3>
                        <p>Placeholder process step for quote preparation.</p>
                    </div>
                    <div class="feature-card spacer-top-md">
                        <h3>Step 3</h3>
                        <p>Placeholder process step for confirmation and final details.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

