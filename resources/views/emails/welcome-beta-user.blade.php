<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to Middle East Commerce Beta</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            width: 100%;
            display: block;
        }
        .email-header img {
            width: 100%;
            height: auto;
            display: block;
            max-width: 600px;
        }
        .email-content {
            padding: 40px 30px;
            color: #333333;
        }
        .greeting {
            font-size: 18px;
            color: #006650;
            margin-bottom: 20px;
            font-weight: 600;
        }
        h2 {
            color: #006650;
            font-size: 22px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        p {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: #4a5568;
            line-height: 1.6;
        }
        .credentials-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border: 2px solid #006650;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 2px 8px rgba(0, 102, 80, 0.1);
        }
        .credential-item {
            margin: 12px 0;
            padding: 12px;
            background-color: #ffffff;
            border-radius: 6px;
            border-left: 3px solid #006650;
        }
        .credential-label {
            font-weight: 600;
            color: #006650;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 16px;
            color: #2d3748;
            word-break: break-all;
            font-family: 'Courier New', monospace;
        }
        .login-button {
            display: inline-block;
            background-color: #006650;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .login-button:hover {
            background-color: #004d3d;
        }
        ul {
            margin: 16px 0;
            padding-left: 20px;
        }
        li {
            margin: 10px 0;
            color: #4a5568;
            font-size: 15px;
        }
        .info-section {
            background-color: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .signature {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
        .signature strong {
            color: #006650;
            display: block;
            margin-top: 10px;
        }
        @media only screen and (max-width: 600px) {
            .email-content {
                padding: 25px 20px;
            }
            h2 {
                font-size: 20px;
            }
            .credentials-box {
                padding: 20px;
            }
            .login-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div style="background-color: #f4f4f4; padding: 20px 0;">
        <div class="email-wrapper">
            <!-- Header Banner Image -->
            <div class="email-header">
                <img src="{{ asset('images/banner.jpg') }}" alt="Middle East E-Commerce Early Access" />
            </div>
            
            <!-- Email Content -->
            <div class="email-content">
                <div class="greeting">Hello {{ $user->name }},</div>
                
                <p>You're receiving this email because you were selected to join the private beta of Middle East – Commerce Module.</p>
                
                <p>At this stage, beta access is limited exclusively to our core commerce features. This phase is focused on validating the foundations of store operations before expanding to additional platform capabilities.</p>
                
                <h2>Your Beta Access Credentials</h2>
                
                <div class="credentials-box">
                    <div class="credential-item">
                        <div class="credential-label">Email</div>
                        <div class="credential-value">{{ $user->email }}</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">Temporary Password</div>
                        <div class="credential-value">{{ $password }}</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin: 25px 0;">
                    <a href="{{ config('app.beta_login_url', config('app.frontend_url', 'https://uat.middleeastcommerce.net') . '/beta/login') }}" class="login-button">Access Beta Platform</a>
                </div>
                
                <p style="text-align: center; color: #718096; font-size: 14px;">
                    <strong>Note:</strong> For security reasons, you will be asked to set a new password on your first login.
                </p>
                
                <div class="info-section">
                    <h2 style="margin-top: 0; color: #006650;">What This Beta Includes</h2>
                    <ul style="margin-bottom: 0;">
                        <li>Store setup and basic configuration</li>
                        <li>Product and item management</li>
                        <li>Pricing and inventory handling</li>
                        <li>Cart and checkout flow (beta environment)</li>
                        <li>Internal sales and order logic</li>
                    </ul>
                </div>
                
                <div class="info-section">
                    <h2 style="margin-top: 0; color: #006650;">What This Beta Does Not Include</h2>
                    <ul style="margin-bottom: 0;">
                        <li>Community or social features</li>
                        <li>Advertising or promotion tools</li>
                        <li>Wallets, payments, or external integrations</li>
                        <li>Free zones, services, or other platform sections</li>
                    </ul>
                </div>
                
                <h2>Feedback Matters</h2>
                <p>This beta exists to validate usability, clarity, and workflow of the commerce experience.<br>
                Feedback can be shared via email.</p>
                
                <div class="info-section">
                    <h2 style="margin-top: 0; color: #006650;">Important Notes</h2>
                    <ul style="margin-bottom: 0;">
                        <li>This access is confidential</li>
                        <li>Features may change</li>
                        <li>Account data preserved unless removal requested</li>
                    </ul>
                </div>
                
                <div class="signature">
                    <p style="margin: 0;">Sign as:</p>
                    <strong>The Middle East Team<br>
                    Commerce Beta Program</strong>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
