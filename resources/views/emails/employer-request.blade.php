<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Role Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { width: 90%; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { font-size: 24px; color: #333; }
        .content { margin-top: 20px; }
        .content p { margin-bottom: 10px; }
        .message-box { background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">New Employer Role Request</div>
        <div class="content">
            <p>You have received a new request from a user to gain employer privileges.</p>
            
            <p>
                <strong>User Name:</strong> {{ $user->name }}
            </p>
            <p>
                <strong>User Email:</strong> {{ $user->email }}
            </p>
            <p>
                <strong>User ID:</strong> {{ $user->id }}
            </p>

            <hr>

            <p><strong>Message from user:</strong></p>
            <div class="message-box">
                <p>
                    {{ $requestMessage }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>