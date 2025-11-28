{{-- resources/views/auth/admin-login.blade.php --}}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login - TaskTable</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, Helvetica, sans-serif;
            padding: 0;
            margin: 0;
        }

        .login-wrapper {
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 380px;
            padding: 28px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.35s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .title {
            font-weight: 700;
            font-size: 22px;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: linear-gradient(90deg, #6366F1, #8b5cf6) !important;
            border: none !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
        }

        .input-group-text {
            background: #eef0fe;
            border: 1px solid #dbe1ff;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }

        .admin-tag {
            background: #eef2ff;
            padding: 3px 8px;
            color: #4f46e5;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 6px;
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="text-center mb-3">
                <h3 class="title">
                    TaskTable Admin <span class="admin-tag">ADMIN</span>
                </h3>
                <div class="subtitle">Secure access for administrators only</div>
            </div>

            @if ($msg)
                <div class="alert alert-danger">{{ $msg }}</div>
            @endif

            <form method="GET" action="{{ route('admin.login') }}">

                <label class="mb-1" style="font-weight:600;">Admin User ID</label>

                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                            fill="currentColor" class="bi bi-person-lock">
                            <path
                                d="M11 1a2 2 0 0 1 2 2v1h-1V3a1 1 0 0 0-2 0v1H9V3a2 2 0 0 1 2-2z" />
                            <path
                                d="M8 7a3 3 0 1 0-6 0 3 3 0 0 0 6 0z" />
                            <path
                                d="M11.5 6h1A1.5 1.5 0 0 1 14 7.5v4A1.5 1.5 0 0 1 12.5 13h-1A1.5 1.5 0 0 1 10 11.5v-4A1.5 1.5 0 0 1 11.5 6z" />
                        </svg>
                    </span>
                    <input type="text" name="userid" class="form-control" placeholder="Enter admin userid"
                        required>
                </div>

                <button class="btn btn-primary w-100 mt-2">Login</button>
            </form>

            {{-- <div class="footer-note mt-3">
                Not an admin?  
                <a href="/login">Login as Employee</a>
            </div> --}}

        </div>
    </div>

</body>

</html>
