<!DOCTYPE html>
<html>
<head>
    <title>Job Alert: {{ $job->job_title }} is Hiring Again!</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Good news!</h2>
    <p>A job you saved is now hiring again:</p>
    
    <div style="border: 1px solid #e0e0e0; padding: 15px; border-radius: 5px; background-color: #f9f9f9;">
        <h3 style="margin-top: 0;">{{ $job->job_title }}</h3>
        <p><strong>Company:</strong> {{ $job->company_name }}</p>
        <p><strong>Location:</strong> {{ $job->job_location }}</p>
        <p><a href="{{ url('/jobs/' . $job->id) }}" style="background-color: #4f46e5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">View Job</a></p>
    </div>

    <p>Good luck with your application!</p>
    <p>Best regards,<br>The JobBoard Team</p>
</body>
</html>