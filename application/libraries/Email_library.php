<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email_library {

    public function __construct() {
        // Load PHPMailer via composer autoload
    }

    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $body, $isHTML = true) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return ['status' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            log_message('error', 'Email Error: ' . $mail->ErrorInfo);
            return ['status' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
        }
    }

    /**
     * Send password reset email
     */
    public function send_password_reset($email, $reset_link) {
        $subject = 'Password Reset Request - ' . SMTP_FROM_NAME;
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h2 style="color: #007bff;">Password Reset Request</h2>
                <p>You requested a password reset for your account. Click the button below to reset your password:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $reset_link . '" style="background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
                </div>
                <p>Or copy and paste this link in your browser:</p>
                <p style="word-break: break-all; color: #007bff;">' . $reset_link . '</p>
                <p><strong>Note:</strong> This link will expire in 1 hour.</p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">
                    This is an automated email from ' . SMTP_FROM_NAME . '. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ';

        return $this->send($email, $subject, $body, true);
    }

    /**
     * Send welcome email
     */
    public function send_welcome_email($email, $name) {
        $subject = 'Welcome to ' . SMTP_FROM_NAME;
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h2 style="color: #007bff;">Welcome to ' . SMTP_FROM_NAME . '!</h2>
                <p>Hi <strong>' . $name . '</strong>,</p>
                <p>Thank you for registering with us. Your account has been created successfully.</p>
                <p>You can now login and start using our services.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">
                    This is an automated email from ' . SMTP_FROM_NAME . '. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ';

        return $this->send($email, $subject, $body, true);
    }

    /**
     * Send verification email
     */
    public function send_verification_email($email, $name, $verification_link) {
        $subject = 'Email Verification - ' . SMTP_FROM_NAME;
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h2 style="color: #007bff;">Verify Your Email</h2>
                <p>Hi <strong>' . $name . '</strong>,</p>
                <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $verification_link . '" style="background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email</a>
                </div>
                <p>Or copy and paste this link in your browser:</p>
                <p style="word-break: break-all; color: #007bff;">' . $verification_link . '</p>
                <p>If you did not create an account, please ignore this email.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">
                    This is an automated email from ' . SMTP_FROM_NAME . '. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ';

        return $this->send($email, $subject, $body, true);
    }

    public function send_otp_email($email, $otp) {
    $subject = 'Your OTP Code - ' . SMTP_FROM_NAME;

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px;">
        <div style="max-width:600px; margin:auto; background:#ffffff; padding:30px; border-radius:10px; text-align:center;">
            
            <h2 style="color:#007bff;">Password Reset OTP</h2>
            
            <p style="font-size:16px; color:#333;">
                Use the OTP below to reset your password:
            </p>

            <div style="margin:30px 0;">
                <span style="font-size:32px; letter-spacing:5px; font-weight:bold; color:#000;">
                    ' . $otp . '
                </span>
            </div>

            <p style="color:#666;">
                This OTP is valid for <strong>5 minutes</strong>.
            </p>

            <p style="color:#999; font-size:12px; margin-top:20px;">
                If you didn’t request this, ignore this email.
            </p>

        </div>
    </body>
    </html>
    ';

    return $this->send($email, $subject, $body, true);
}
}
