<!DOCTYPE html>
<html>
<head>
    <title>New Contact Form Submission</title>
</head>
<body>
    <h1>Contact Details</h1>
    <p><strong>Name:</strong> {{ $contactData['name'] }}</p>
    <p><strong>Email:</strong> {{ $contactData['email'] }}</p>
    <p><strong>Phone:</strong> {{ $contactData['phone'] }}</p>
    <p><strong>Business Name:</strong> {{ $contactData['business_name'] ?? 'N/A' }}</p>
    <p><strong>Yearly Turnover:</strong> {{ $contactData['yearly_turnover'] }}</p>
    <p><strong>Interested Services:</strong>
        <ul>
            @foreach(json_decode($contactData['interested_service'], true) as $service)
                <li>{{ $service }}</li>
            @endforeach
        </ul>
    </p>
    <p><strong>Message:</strong> {{ $contactData['message'] }}</p>
</body>
</html>