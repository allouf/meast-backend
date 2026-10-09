<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #006650 0%, #004d3d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #e74c3c;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .warning-box ul {
            color: #856404;
            margin-left: 20px;
        }

        .warning-box li {
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #006650;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            margin-top: 2px;
        }

        .checkbox-group label {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }

        .button-group {
            display: flex;
            gap: 15px;
        }

        .btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #006650;
            color: white;
        }

        .btn-secondary:hover {
            background: #004d3d;
            transform: translateY(-2px);
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e74c3c;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Delete Account</h1>
            <p>This action cannot be undone</p>
        </div>

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <div class="warning-box">
            <h3>⚠️ Warning: This action is permanent</h3>
            <ul>
                <li>Your account will be permanently deleted</li>
                <li>All your data will be removed from our systems</li>
                <li>You will lose access to all services</li>
                <li>This action cannot be reversed</li>
            </ul>
        </div>

        <form id="deleteAccountForm">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="form-group">
                <label for="identifier">Email or Phone Number:</label>
                <input type="text" id="identifier" name="identifier" placeholder="Enter your email or phone number"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Confirm your password:</label>
                <input type="password" id="password" name="password" required>
            </div>


            <div class="checkbox-group">
                <input type="checkbox" id="confirm" name="confirm" required>
                <label for="confirm">
                    I understand that this action is permanent and cannot be undone.
                    I want to delete my account and all associated data.
                </label>
            </div>

            <div class="button-group">
                <a href="{{ $frontendUrl ?? config('app.frontend_url', 'https://uat.middleeastcommerce.net') }}"
                    class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Delete Account</button>
            </div>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Deleting your account...</p>
        </div>
    </div>
    <!-- Add jQuery CDN in <head> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#deleteAccountForm').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);
                var loading = $('#loading');
                var errorMessage = $('#errorMessage');
                var successMessage = $('#successMessage');

                errorMessage.hide();
                successMessage.hide();
                loading.show();
                form.hide();

                $.ajax({
                    url: '/account/delete', // relative URL for HTTPS
                    method: 'POST',
                    data: form.serialize(), // includes _token hidden input
                    dataType: 'json',
                    success: function(data) {
                        successMessage.text(data.message ||
                            'Your account has been successfully deleted.');
                        successMessage.show();

                        var redirectUrl = data.redirect_url ||
                            '{{ $frontendUrl ?? config('app.frontend_url', 'https://uat.middleeastcommerce.net') }}';

                        setTimeout(function() {
                            window.location.href = redirectUrl;
                        }, 3000);
                    },
                    error: function(xhr) {
                        var msg = 'An error occurred while deleting your account.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        errorMessage.text(msg).show();

                        loading.hide();
                        form.show();
                    }
                });
            });
        });
    </script>

</body>

</html>
