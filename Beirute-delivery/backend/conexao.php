<?php
$host= 'localhost';
$dbname= 'beirute';
$user= 'root';
$pass= '';

try {
    // usando PDO para acessar o banco - mais seguro e moderno
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);

    // configurar o PDO para lançar exceções em caso de erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

    // qdo o sistema estiver em produção ele exibirá a mensagem para o usuário
    die("Erro ao conectar com o Banco de dados: " . $e->getMessage());
}
?>