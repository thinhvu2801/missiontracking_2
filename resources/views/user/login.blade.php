<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="backend/css/bootstrap.min.css" rel="stylesheet">
    <link href="backend/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="backend/css/animate.css" rel="stylesheet">
    <link href="backend/css/style.css" rel="stylesheet">
</head>
<body class="gray-bg">
    <div class="middle-box loginscreen animated fadeInDown">
        <div>
            <h2 class="text-center">Đăng nhập</h3>
            @include('partials.error')
            <form method="post" class="m-t" role="form" action={{ route('auth.login.post') }}>
                @csrf
                @error('login')
                    <div class="text-center">
                        <span class="text-danger">
                            *{{ $message }}
                        </span>
                    </div>
                @enderror   
                <div class="form-group">
                    <input 
                        type="text" 
                        name="username"
                        value="{{ old('username') }}"
                        class="form-control" 
                        placeholder="Tài khoản"   
                        required                      
                    >
                </div>
                <div class="form-group">
                    <input 
                        type="password"
                        name="password" 
                        class="form-control" 
                        placeholder="Mật khẩu"  
                        required                       
                    >                   
                </div>
                <button type="submit" class="btn btn-primary block full-width m-b">Login</button>
            </form>
        </div>
    </div>
    
    <!-- Mainly scripts -->
    <script src="backend/js/jquery-3.1.1.min.js"></script>
    <script src="backend/js/bootstrap.min.js"></script>
</body>

</html>
