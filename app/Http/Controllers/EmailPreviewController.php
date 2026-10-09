<?php

namespace App\Http\Controllers;

use Modules\Core\Models\User;
use Modules\Store\Models\Product;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;

class EmailPreviewController extends Controller
{
    /**
     * Show email preview index
     */
    public function index()
    {
        $templates = [
            'verify-email' => 'Email Verification',
            'reset-password' => 'Password Reset',
            'welcome' => 'Welcome Email',
            'store_invitation' => 'Store Invitation',
            'account-upgrade' => 'Account Upgrade',
            'order-confirmation' => 'Order Confirmation',
            'payment-failed' => 'Payment Failed',
            'product-review-request' => 'Product Review Request',
            'account-suspended' => 'Account Suspension',
        ];

        return view('emails.preview.index', compact('templates'));
    }

    /**
     * Preview specific email template
     */
    public function preview(Request $request, string $template)
    {
        $data = $this->getSampleData($template);

        try {
            return view("emails.{$template}", $data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Template not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get sample data for email templates
     */
    private function getSampleData(string $template): array
    {
        $baseData = [
            'user' => (object) [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ],
            'customer' => (object) [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
            ],
            'store' => (object) [
                'name' => 'Amazing Store',
                'description' => 'Your one-stop shop for amazing products',
            ],
            'product' => (object) [
                'name' => 'Premium Wireless Headphones',
            ],
            'order' => (object) [
                'order_number' => 'ORD-2024-001',
                'total_amount' => 299.99,
                'payment_method' => 'Credit Card',
                'created_at' => now(),
            ],
            'inviter' => (object) [
                'name' => 'Store Owner',
            ],
            'invitedAt' => now(),
            'roleDisplay' => 'Store Manager',
            'permissionsByCategory' => [
                'Products' => [
                    'products.create',
                    'products.read',
                    'products.update',
                    'products.delete',
                ],
                'Orders' => [
                    'orders.read',
                    'orders.update',
                ],
                'Analytics' => [
                    'analytics.read',
                ],
            ],
            'orderItems' => [
                (object) [
                    'product_name' => 'Premium Wireless Headphones',
                    'quantity' => 1,
                    'price' => 299.99,
                ],
            ],
            'shippingAddress' => (object) [
                'name' => 'Jane Smith',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States',
            ],
        ];

        switch ($template) {
            case 'verify-email':
                return array_merge($baseData, [
                    'otp' => '123456',
                ]);

            case 'reset-password':
                return array_merge($baseData, [
                    'otp' => '789012',
                ]);

            case 'welcome':
                return array_merge($baseData, [
                    'dashboardUrl' => config('app.url') . '/dashboard',
                ]);

            case 'store_invitation':
                return array_merge($baseData, [
                    'inviteUrl' => config('app.url') . '/invitations/accept/abc123',
                ]);

            case 'order-confirmation':
                return array_merge($baseData, [
                    'trackingUrl' => config('app.url') . '/orders/track/ORD-2024-001',
                    'estimatedDelivery' => '3-5 business days',
                ]);

            case 'payment-failed':
                return array_merge($baseData, [
                    'failureReason' => 'Insufficient funds',
                    'retryPaymentUrl' => config('app.url') . '/orders/retry-payment/ORD-2024-001',
                ]);

            case 'product-review-request':
                return array_merge($baseData, [
                    'reviewUrl' => config('app.url') . '/reviews/create/123',
                ]);

            case 'account-suspended':
                return array_merge($baseData, [
                    'suspensionReason' => 'Terms of Service Violation',
                    'suspensionDate' => now(),
                    'appealUrl' => config('app.url') . '/appeal',
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Send test email
     */
    public function sendTest(Request $request, string $template)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $data = $this->getSampleData($template);

        try {
            Mail::send("emails.{$template}", $data, function ($message) use ($request, $template) {
                $message->to($request->email)
                    ->subject('Test Email: ' . ucwords(str_replace('-', ' ', $template)));
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }
}
