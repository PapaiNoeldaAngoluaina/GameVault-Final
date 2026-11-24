<?php
require_once '../src/functions.php';
include '../templates/header.php';
$orders = getOrdersBySession(session_id());
?>
<h2>Histórico de Compras</h2>
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success mt-4">Compra concluída com sucesso! (Simulação)</div>
<?php endif; ?>
<?php if (!$orders): ?>
    <p>Nenhuma compra realizada. <a href="index.php">Voltar ao catálogo</a></p>
<?php else: foreach ($orders as $order): ?>
    <h4 class="mt-4">Pedido #<?= $order['id'] ?></h4>
    <p>
        Data: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
        | Pagamento: <?= htmlspecialchars(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?>
    </p>
    <table class="table table-bordered align-middle cart-table">
        <thead class="table-dark">
            <tr>
                <th>Jogo</th><th>Quantidade</th><th>Preço Unit.</th><th>Total</th><th>Chaves</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td>R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') ?></td>
                    <td>
                        <?php foreach ($item['keys'] as $key): ?>
                            <div><code><?= htmlspecialchars($key) ?></code></div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h5>Total do pedido: R$ <?= number_format($order['total'], 2, ',', '.') ?></h5>
<?php endforeach; endif; ?>
<?php include '../templates/footer.php'; ?>
