<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template Preview</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .template-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .template-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .template-card h3 {
            margin: 0 0 10px 0;
            color: #2d3748;
        }

        .template-card p {
            color: #4a5568;
            margin: 0 0 15px 0;
        }

        .template-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .test-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2d3748;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }

        .alert-success {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #22543d;
        }

        .alert-error {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #742a2a;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Template Preview</h1>
            <p>Preview and test all email templates</p>
        </div>

        <div class="content">
            <h2>Available Templates</h2>
            <p>Click on any template below to preview it, or send a test email to see how it looks in your inbox.</p>

            <div class="template-grid">
                @foreach ($templates as $key => $name)
                    <div class="template-card">
                        <h3>{{ $name }}</h3>
                        <p>Preview the {{ strtolower($name) }} email template with sample data.</p>
                        <div class="template-actions">
                            <a href="/email-preview/{{ $key }}" target="_blank"
                                class="btn btn-primary">Preview</a>
                            <button onclick="showTestForm('{{ $key }}')" class="btn btn-secondary">Send
                                Test</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="test-form-container" class="test-form" style="display: none;">
                <h3>Send Test Email</h3>
                <form id="test-form" onsubmit="sendTestEmail(event)">
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required placeholder="Enter email address">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Send Test Email</button>
                        <button type="button" onclick="hideTestForm()" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
                <div id="test-result"></div>
            </div>
        </div>
    </div>

    <script>
        let currentTemplate = '';

        function showTestForm(template) {
            currentTemplate = template;
            document.getElementById('test-form-container').style.display = 'block';
            document.getElementById('test-form').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function hideTestForm() {
            document.getElementById('test-form-container').style.display = 'none';
            document.getElementById('test-result').innerHTML = '';
            currentTemplate = '';
        }

        async function sendTestEmail(event) {
            event.preventDefault();

            const email = document.getElementById('email').value;
            const resultDiv = document.getElementById('test-result');

            resultDiv.innerHTML = '<div class="alert">Sending test email...</div>';

            try {
                const response = await fetch(`/email-preview/${currentTemplate}/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') || ''
                    },
                    body: JSON.stringify({
                        email: email
                    })
                });

                const result = await response.json();

                if (result.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-error">${result.message}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-error">Error: ${error.message}</div>`;
            }
        }
    </script>
</body>

</html>
