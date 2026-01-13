<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        body { font-family: sans-serif; padding: 40px; }
        .error { color: #dc2626; background: #fee; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="error">
        <h1>Error {{ $status }}</h1>
        <p>{{ $error }}</p>
     
    </div>
</body>
</html>