<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Code - EMOH Real Estate</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #000000;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.15);
            border: 2px solid #ff8c00;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #ff8c00;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .title {
            font-size: 26px;
            color: #000000;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .content {
            font-size: 16px;
            color: #000000;
            margin-bottom: 30px;
        }
        .otp-container {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.3);
        }
        .otp-code {
            font-size: 42px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
            background-color: rgba(0, 0, 0, 0.1);
            padding: 15px 25px;
            border-radius: 10px;
            display: inline-block;
        }
        .otp-label {
            color: #ffffff;
            font-size: 16px;
            margin-top: 15px;
            font-weight: 500;
        }
        .warning {
            background-color: #fff3e0;
            border: 2px solid #ff8c00;
            color: #000000;
            padding: 18px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
            font-weight: 500;
        }
        .info-box {
            background-color: #f5f5f5;
            border: 2px solid #000000;
            color: #000000;
            padding: 18px;
            border-radius: 10px;
            margin: 25px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #ff8c00;
            font-size: 14px;
            color: #000000;
            text-align: center;
        }
        .steps {
            background-color: #fff8f0;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            border-left: 4px solid #ff8c00;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
            color: #000000;
            font-weight: 500;
        }
        .steps strong {
            color: #ff8c00;
            font-weight: 600;
        }
        .warning strong {
            color: #ff8c00;
        }
        .info-box strong {
            color: #ff8c00;
        }
        ul li {
            margin-bottom: 8px;
            color: #000000;
        }
        p {
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EMOH Real Estate</div>
            <h1 class="title">Email Verification Code</h1>
        </div>

        <div class="content">
            <p>Hello {{ $client->name }},</p>
            
            <p>Thank you for registering with EMOH Real Estate! To complete your email verification, please use the following 6-digit code:</p>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-label">Your Verification Code</div>
            </div>
            
            <div class="warning">
                <strong>Important:</strong> This verification code will expire in 10 minutes for security reasons.
            </div>
            
            <div class="steps">
                <strong>How to verify your email:</strong>
                <ol>
                    <li>Return to the EMOH Real Estate app or website</li>
                    <li>Enter the 6-digit code above in the verification field</li>
                    <li>Click "Verify Email" to complete the process</li>
                </ol>
            </div>
            
            <div class="info-box">
                <strong>Security Note:</strong> You have 5 attempts to enter the correct code. If you exceed this limit, you'll need to request a new verification code.
            </div>
            
            <p>Once your email is verified, you'll have full access to:</p>
            <ul>
                <li>Your personalized dashboard</li>
                <li>Property search and favorites</li>
                <li>Direct communication with our agents</li>
                <li>Property alerts and market updates</li>
            </ul>
            
            <p>If you didn't request this verification code, please ignore this email or contact our support team if you have concerns.</p>
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
