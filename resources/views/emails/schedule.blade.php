<!DOCTYPE html>
<html>
<head>
    <title>Meeting Scheduled</title>
</head>
<body>
    <h1>Meeting Details</h1>
    <p><strong>Date:</strong> {{ $scheduleData['date'] }}</p>
    <p><strong>Time:</strong> {{ $scheduleData['time'] }}</p>
    <p><strong>Time Zone:</strong> {{ $scheduleData['time_zone'] }}</p>
    <p><strong>Meeting Type:</strong> {{ $scheduleData['meet_type'] }}</p>
    <p><strong>First Name:</strong> {{ $scheduleData['first_name'] }}</p>
    <p><strong>Last Name:</strong> {{ $scheduleData['last_name'] }}</p>
    <p><strong>Email:</strong> {{ $scheduleData['email'] }}</p>
    <p><strong>Phone:</strong> {{ $scheduleData['phone'] }}</p>
    <p><strong>Discussion Topic:</strong> {{ $scheduleData['discussion'] }}</p>
</body>
</html>