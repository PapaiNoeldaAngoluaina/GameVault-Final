<?php
require_once '../src/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$cart = $_SESSION['cart'] ?? [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cart) {
    try {
        createOrder($cart, $_POST['payment'] ?? '', session_id());
        $_SESSION['cart'] = [];
        header('Location: ' . BASE_URL . 'history.php?success=1');
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        $error = 'Não foi possível concluir a compra. Tente novamente.';
    }
}
include '../templates/header.php';
if (!$cart) {
    echo '<p>Carrinho vazio. <a href="index.php">Voltar ao catálogo</a></p>';
    include '../templates/footer.php';
    exit;
}
$total = 0;
foreach ($cart as $pid => $qty) {
    $product = getProduct($pid);
    if ($product) {
        $total += $product['price'] * $qty;
    }
}
?>
<h2>Finalizar Compra</h2>
<p>Esta é uma simulação. Todos os valores são fictícios.</p>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form action="checkout.php" method="post">
    <div class="mb-3"><label for="payment" class="form-label">Método de Pagamento</label>
        <select id="payment" name="payment" class="form-select">
            <option value="pix">PIX (Simulado)</option>
            <option value="boleto">Boleto (Simulado)</option>
            <option value="cartao">Cartão de Crédito (Simulado)</option>
        </select></div>
    <h4>Total: R$ <?= number_format($total, 2, ',', '.') ?></h4>
    <button type="submit" class="btn btn-success mt-3">Concluir Compra</button>
</form>
<?php include '../templates/footer.php'; ?>
