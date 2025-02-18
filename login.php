<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Adicionado para responsividade -->
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">   
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <form action="autenticar.php" method="post">
            <div style="position: relative;">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            <div style="position: relative;">
                <i class="fas fa-lock"></i>
                <input type="password" id="senha" name="senha" placeholder="Senha" required>
            </div>
            <input type="submit" value="Entrar">
        </form>
    </div>
</body>
</html>