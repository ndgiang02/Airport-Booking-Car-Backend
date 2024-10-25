<!-- resources/views/home.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Chào mừng đến với Trang Chủ!</h1>
        <p class="lead text-center">Máy chủ của ứng dụng Đặt xe đưa đón sân bay.</p>
        <div class="text-center mt-4">
            <a href="{{ url('/admin') }}" class="btn btn-primary">Đi đến Admin</a>
        </div>
    </div>
</body>
</html>