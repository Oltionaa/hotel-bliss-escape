<!DOCTYPE html>
<html>
<head>
    <title>Regjistrohu</title>
</head>
<body>
    <h2>Forma e Regjistrimit</h2>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label>Emri:</label>
        <input type="text" name="name" required><br><br>

        <label>Email:</label>
        <input type="email" name="email" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <label>Konfirmo Password:</label>
        <input type="password" name="password_confirmation" required><br><br>

        <button type="submit">Regjistrohu</button>
    </form>

    @if($errors->any())
        <ul style="color:red;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
