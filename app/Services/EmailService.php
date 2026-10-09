<?php

namespace App\Services;

use Modules\Core\Models\User;
use Modules\Job\Models\JobApplication;
use Modules\Store\Models\Product;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreOrder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailService
{
    /**
     * Send email verification
     */
    public function sendEmailVerification(User $user, string $otp): bool
    {
        try {
            Mail::send('emails.verify-email', [
                'user' => $user,
                'otp' => $otp,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Verify Your Email Address - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Email verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(User $user, string $otp): bool
    {
        try {
            Mail::send('emails.reset-password', [
                'user' => $user,
                'otp' => $otp,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Reset Your Password - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Password reset email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(User $user, string $dashboardUrl = null): bool
    {
        try {
            Mail::send('emails.welcome', [
                'user' => $user,
                'dashboardUrl' => $dashboardUrl ?? config('app.url') . '/dashboard',
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Welcome to ' . config('app.name') . '!');
            });

            return true;
        } catch (Exception $e) {
            Log::error('Welcome email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send store invitation email
     */
    public function sendStoreInvitation(
        string $email,
        Store $store,
        string $roleDisplay,
        array $permissionsByCategory,
        string $inviteUrl,
        User $inviter = null,
        \DateTime $invitedAt = null
    ): bool {
        try {
            Mail::send('emails.store_invitation', [
                'email' => $email,
                'store' => $store,
                'roleDisplay' => $roleDisplay,
                'permissionsByCategory' => $permissionsByCategory,
                'inviteUrl' => $inviteUrl,
                'inviter' => $inviter,
                'invitedAt' => $invitedAt ?? now(),
            ], function ($message) use ($email, $store) {
                $message->to($email)
                    ->subject('You\'re invited to join ' . $store->name . ' - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Store invitation email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation(
        User $customer,
        StoreOrder $storeOrder,
        array $orderItems = [],
        object $shippingAddress = null,
        string $trackingUrl = null,
        string $estimatedDelivery = null
    ): bool {
        try {
            Mail::send('emails.order-confirmation', [
                'customer' => $customer,
                'order' => $storeOrder,
                'orderItems' => $orderItems,
                'shippingAddress' => $shippingAddress,
                'trackingUrl' => $trackingUrl,
                'estimatedDelivery' => $estimatedDelivery,
            ], function ($message) use ($customer, $storeOrder) {
                $message->to($customer->email, $customer->name)
                    ->subject('Order Confirmation #' . $storeOrder->id . ' - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Order confirmation email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailedNotification(
        User $customer,
        StoreOrder $storeOrder,
        string $failureReason = null,
        string $retryPaymentUrl = null
    ): bool {
        try {
            Mail::send('emails.payment-failed', [
                'customer' => $customer,
                'order' => $storeOrder,
                'failureReason' => $failureReason,
                'retryPaymentUrl' => $retryPaymentUrl,
            ], function ($message) use ($customer, $storeOrder) {
                $message->to($customer->email, $customer->name)
                    ->subject('Payment Failed - Order #' . $storeOrder->id . ' - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Payment failed email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send product review request
     */
    public function sendProductReviewRequest(
        User $customer,
        StoreOrder $storeOrder,
        Product $product,
        Store $store,
        string $reviewUrl = null
    ): bool {
        try {
            Mail::send('emails.product-review-request', [
                'customer' => $customer,
                'order' => $storeOrder,
                'product' => $product,
                'store' => $store,
                'reviewUrl' => $reviewUrl,
            ], function ($message) use ($customer, $product) {
                $message->to($customer->email, $customer->name)
                    ->subject('How was your purchase of ' . $product->name . '? - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Product review request email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send account suspension notice
     */
    public function sendAccountSuspensionNotice(
        User $user,
        string $suspensionReason = null,
        \DateTime $suspensionDate = null,
        string $appealUrl = null
    ): bool {
        try {
            Mail::send('emails.account-suspended', [
                'user' => $user,
                'suspensionReason' => $suspensionReason,
                'suspensionDate' => $suspensionDate ?? now(),
                'appealUrl' => $appealUrl,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Account Suspension Notice - ' . config('app.name'));
            });

            return true;
        } catch (Exception $e) {
            Log::error('Account suspension email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send custom email with template
     */
    public function sendCustomEmail(
        string $email,
        string $name,
        string $template,
        array $data,
        string $subject
    ): bool {
        try {
            Mail::send($template, $data, function ($message) use ($email, $name, $subject) {
                $message->to($email, $name)
                    ->subject($subject);
            });

            return true;
        } catch (Exception $e) {
            Log::error('Custom email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulkEmails(
        array $recipients,
        string $template,
        array $data,
        string $subject
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            try {
                $success = $this->sendCustomEmail(
                    $recipient['email'],
                    $recipient['name'] ?? '',
                    $template,
                    array_merge($data, ['recipient' => $recipient]),
                    $subject
                );

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = 'Failed to send to ' . $recipient['email'];
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = 'Error sending to ' . $recipient['email'] . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Send job application success notification (validated).
     */
    public function sendJobApplicationSuccess(User $candidate, JobApplication $application): bool
    {
        try {
            $jobTitle = $application->job->title ?? __('Job');
            Mail::send('emails.job-application-success', [
                'user' => $candidate,
                'application' => $application,
                'jobTitle' => $jobTitle,
            ], function ($message) use ($candidate) {
                $message->to($candidate->email, $candidate->name)
                    ->subject(__('Application submitted successfully') . ' - ' . config('app.name'));
            });
            return true;
        } catch (Exception $e) {
            Log::error('Job application success email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send job application rejected notification (scan failed or invalid).
     */
    public function sendJobApplicationRejected(User $candidate, JobApplication $application, string $reason): bool
    {
        try {
            $jobTitle = $application->job->title ?? __('Job');
            Mail::send('emails.job-application-rejected', [
                'user' => $candidate,
                'application' => $application,
                'jobTitle' => $jobTitle,
                'reason' => $reason,
            ], function ($message) use ($candidate) {
                $message->to($candidate->email, $candidate->name)
                    ->subject(__('Application could not be accepted') . ' - ' . config('app.name'));
            });
            return true;
        } catch (Exception $e) {
            Log::error('Job application rejected email failed: ' . $e->getMessage());
            return false;
        }
    }
}
