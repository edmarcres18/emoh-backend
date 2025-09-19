<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - EMOH Real Estate</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .verify-button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .verify-button:hover {
            background-color: #1d4ed8;
        }
        .alternative-link {
            font-size: 14px;
            color: #6b7280;
            margin-top: 20px;
            padding: 15px;
            background-color: #f3f4f6;
            border-radius: 6px;
            word-break: break-all;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EMOH Real Estate</div>
            <h1 class="title">Verify Your Email Address</h1>
        </div>

        <div class="content">
            <p>Hello {{ $client->name }},</p>
            
            <p>Thank you for registering with EMOH Real Estate! To complete your registration and start exploring our premium real estate services, please verify your email address by clicking the button below.</p>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-button">Verify Email Address</a>
            </div>
            
            <div class="warning">
                <strong>Important:</strong> This verification link will expire in 60 minutes for security reasons.
            </div>
            
            <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
            
            <div class="alternative-link">
                {{ $verificationUrl }}
            </div>
            
            <p>Once your email is verified, you'll be able to:</p>
            <ul>
                <li>Access your personalized dashboard</li>
                <li>Save your favorite properties</li>
                <li>Receive property alerts and updates</li>
                <li>Contact our real estate agents directly</li>
            </ul>
            
            <p>If you didn't create an account with EMOH Real Estate, please ignore this email.</p>
        </div>

        <div class="footer">
            <p>Best regards,<br>The EMOH Real Estate Team</p>
            <p style="margin-top: 20px;">
                This is an automated message. Please do not reply to this email.<br>
                If you need assistance, please contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
