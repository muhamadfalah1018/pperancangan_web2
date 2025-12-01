<!DOCTYPE html>
<html>
<head><title>Register User</title></head>
<body>

<h2>Form Pendaftaran User</h2>

<form action="proses_register.php" method="post">
    Username : <input type="text" name="username" required><br><br>
    Password : <input type="password" name="password" required><br><br>

    Gender : 
    <select name="gender" required>
        <option value="L">Laki-laki</option>
        <option value="P">Perempuan</option>
    </select>
    <br><br>

    <button type="submit">Daftar</button>
</form>

<p><a href="login.php">Sudah punya akun? Login</a></p>

</body>
</html>
