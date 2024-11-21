<?php
// Include Composer's autoloader for PHPMailer (Run "composer require phpmailer/phpmailer")
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email Configurations
$recipient_email = "verification@royaltynexusenterprise.com";
$subject = "New Account Submission";

// Arkesel SMS Configurations
$AUTH_KEY = "RE5tTHpWdGNKSVNTc29LT2NiRkY"; // Replace with your AUTH_KEY
$senderId = "oboshie"; // Replace with your Sender ID
$sms_url = "https://sms.arkesel.com/api/v2/sms/send";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Form Data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];
    $countryCode = $_POST['countryCode'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $password = $_POST['password']; // Note: Hash this in real use for security

    // File Uploads
    $uploads_dir = 'uploads/';
    if (!file_exists($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    $ghanaCard = $_FILES['ghanaCard']['tmp_name'];
    $ghanaCardPath = $uploads_dir . basename($_FILES['ghanaCard']['name']);
    move_uploaded_file($ghanaCard, $ghanaCardPath);

    $attachedPictures = [];
    foreach ($_FILES['attachPictures']['tmp_name'] as $key => $tmp_name) {
        $filePath = $uploads_dir . basename($_FILES['attachPictures']['name'][$key]);
        move_uploaded_file($tmp_name, $filePath);
        $attachedPictures[] = $filePath;
    }

    $verificationPhoto = $_FILES['verificationPhoto']['tmp_name'];
    $verificationPhotoPath = $uploads_dir . basename($_FILES['verificationPhoto']['name']);
    move_uploaded_file($verificationPhoto, $verificationPhotoPath);

    // Prepare Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.royaltynexusenterprise.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'verification@royaltynexusenterprise.com'; // SMTP username
        $mail->Password = 'YLTZc{IOGtC}';   // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('your_email@example.com', 'Verification System');
        $mail->addAddress($recipient_email);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            <h3>New Account Submission</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Date of Birth:</strong> $dob</p>
            <p><strong>Mobile:</strong> $countryCode $mobile</p>
            <p><strong>Address:</strong> $address</p>
        ";

        // Attachments
        $mail->addAttachment($ghanaCardPath, 'GhanaCard.jpg');
        foreach ($attachedPictures as $index => $picturePath) {
            $mail->addAttachment($picturePath, "Picture_" . ($index + 1) . ".jpg");
        }
        $mail->addAttachment($verificationPhotoPath, 'VerificationPhoto.jpg');

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        exit;
    }

    // Send SMS via Arkesel
    $sms_message = "Thank you, $name! Your account details have been submitted. Verification is in progress.";
    $sms_data = [
        "sender" => $senderId,
        "message" => $sms_message,
        "recipients" => $countryCode . $mobile
    ];
    $sms_headers = [
        "Authorization: $AUTH_KEY",
        "Content-Type: application/json"
    ];

    $ch = curl_init($sms_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sms_headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $sms_response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "SMS Error: " . curl_error($ch);
        exit;
    }
    curl_close($ch);

    // Send Email Confirmation to User
    try {
        $mail->clearAddresses();
        $mail->addAddress($email);
        $mail->Subject = "Submission Received";
        $mail->Body = "
            <h3>Thank You, $name!</h3>
            <p>Your account details have been submitted successfully. Verification is in progress. We will notify you shortly.</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        echo "Confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    // Redirect or Confirm Submission
    header("Location: thank_you.html");
    exit;
} else {
    echo "Invalid Request Method.";
}
?>
