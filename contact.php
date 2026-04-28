<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Contact Us';
$errors = [];

if (isPostRequest()) {
    $errors = validateRequiredFields([
        'full_name' => 'Full name',
        'email' => 'Email address',
        'subject' => 'Subject',
        'message' => 'Message',
    ]);

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $saved = saveContactMessage([
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'subject' => trim($_POST['subject']),
            'message' => trim($_POST['message']),
        ]);

        if ($saved) {
            setFlashMessage('success', 'Your message has been sent. We will get back to you soon.');
            redirect('contact.php');
        }

        $errors[] = 'Database connection is unavailable. Please import the SQL file and try again.';
    }
}

$flashMessage = getFlashMessage('success');

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero-card reveal">
            <span class="eyebrow">Contact Us</span>
            <h1>Contact page placeholder with final layout already in place</h1>
            <p>Replace the text, contact details, and map image later while keeping the same spacing, card shape, and overall composition.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-card reveal">
            <div class="contact-grid">
                <div class="form-card">
                    <h2>Get in touch!</h2>
                    <p class="lead">Contact section placeholder copy. Replace this with your final support and inquiry message.</p>

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

                    <div class="info-card spacer-bottom-md">
                        <p><strong>Facebook:</strong> Social Link Placeholder</p>
                        <p><strong>Address:</strong> Address Placeholder</p>
                        <p><strong>Email:</strong> email@example.com</p>
                        <p><strong>Phone:</strong> Phone Placeholder</p>
                    </div>

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
                            <div class="field-full">
                                <label for="subject">Subject</label>
                                <input id="subject" name="subject" type="text" required value="<?php echo escape($_POST['subject'] ?? ''); ?>">
                            </div>
                            <div class="field-full">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="stack-inline">
                            <button type="submit" class="button">Send Message</button>
                        </div>
                    </form>
                </div>

                <div class="contact-map">
                    <img src="assets/images/map-placeholder.svg" alt="Map location placeholder for ARC Kitchen">
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

