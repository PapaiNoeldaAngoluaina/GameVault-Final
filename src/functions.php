<?php
require_once __DIR__ . '/config.php';

function getProducts()
{
    $sql = "
        SELECT
            products.id,
            products.name,
            products.price,
            products.image,
            categories.name AS category,
            products.description
        FROM products
        INNER JOIN categories ON categories.id = products.category_id
        ORDER BY products.id
    ";

    return getConnection()->query($sql)->fetchAll();
}

function getProduct($id)
{
    $sql = "
        SELECT
            products.id,
            products.name,
            products.price,
            products.image,
            categories.name AS category,
            products.description
        FROM products
        INNER JOIN categories ON categories.id = products.category_id
        WHERE products.id = :id
        LIMIT 1
    ";

    $statement = getConnection()->prepare($sql);
    $statement->execute(['id' => (int) $id]);

    return $statement->fetch() ?: null;
}

const PAYMENT_METHODS = [
    'pix' => 'PIX (Simulado)',
    'boleto' => 'Boleto (Simulado)',
    'cartao' => 'Cartão de Crédito (Simulado)'
];

const MAX_ITEM_QUANTITY = 99;

function generateActivationKey()
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $groups = [];

    for ($group = 0; $group < 3; $group++) {
        $part = '';

        for ($position = 0; $position < 5; $position++) {
            $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $groups[] = $part;
    }

    return implode('-', $groups);
}

function insertActivationKey(PDOStatement $statement, $orderItemId)
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        try {
            $statement->execute([
                'order_item_id' => $orderItemId,
                'activation_key' => generateActivationKey()
            ]);

            return;
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
        }
    }

    throw new RuntimeException('Não foi possível gerar uma chave única.');
}

function createOrder(array $cart, $payment, $sessionId)
{
    if (!isset(PAYMENT_METHODS[$payment])) {
        throw new InvalidArgumentException('Selecione uma forma de pagamento válida.');
    }

    if (!$cart) {
        throw new InvalidArgumentException('O carrinho está vazio.');
    }

    $items = [];

    foreach ($cart as $productId => $quantity) {
        $productId = filter_var($productId, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!$productId || $productId < 1 || !$quantity || $quantity < 1 || $quantity > MAX_ITEM_QUANTITY) {
            throw new InvalidArgumentException('O carrinho contém produtos ou quantidades inválidos.');
        }

        $items[$productId] = $quantity;
    }

    $connection = getConnection();
    $connection->beginTransaction();

    try {
        $placeholders = implode(',', array_fill(0, count($items), '?'));
        $statement = $connection->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $statement->execute(array_keys($items));
        $prices = array_column($statement->fetchAll(), 'price', 'id');

        if (count($prices) !== count($items)) {
            throw new InvalidArgumentException('Um ou mais jogos do carrinho não estão mais disponíveis.');
        }

        $totalCents = 0;

        foreach ($items as $productId => $quantity) {
            $totalCents += (int) round($prices[$productId] * 100) * $quantity;
        }

        $connection->prepare('INSERT INTO orders (session_id, payment_method, total) VALUES (:session_id, :payment_method, :total)')
            ->execute([
                'session_id' => $sessionId,
                'payment_method' => $payment,
                'total' => number_format($totalCents / 100, 2, '.', '')
            ]);
        $orderId = (int) $connection->lastInsertId();

        $itemStatement = $connection->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:order_id, :product_id, :quantity, :unit_price)');
        $keyStatement = $connection->prepare('INSERT INTO order_keys (order_item_id, activation_key) VALUES (:order_item_id, :activation_key)');

        foreach ($items as $productId => $quantity) {
            $itemStatement->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $prices[$productId]
            ]);
            $orderItemId = (int) $connection->lastInsertId();

            for ($unit = 0; $unit < $quantity; $unit++) {
                insertActivationKey($keyStatement, $orderItemId);
            }
        }

        $connection->commit();

        return $orderId;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    }
}

function getOrdersBySession($sessionId)
{
    $sql = "
        SELECT
            orders.id AS order_id,
            orders.payment_method,
            orders.total,
            orders.created_at,
            order_items.id AS item_id,
            order_items.quantity,
            order_items.unit_price,
            products.name AS product_name,
            order_keys.activation_key
        FROM orders
        INNER JOIN order_items ON order_items.order_id = orders.id
        INNER JOIN products ON products.id = order_items.product_id
        LEFT JOIN order_keys ON order_keys.order_item_id = order_items.id
        WHERE orders.session_id = :session_id
        ORDER BY orders.id DESC, order_items.id, order_keys.id
    ";

    $statement = getConnection()->prepare($sql);
    $statement->execute(['session_id' => $sessionId]);

    $orders = [];

    foreach ($statement->fetchAll() as $row) {
        $orderId = $row['order_id'];
        $itemId = $row['item_id'];

        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $orderId,
                'payment_method' => $row['payment_method'],
                'total' => $row['total'],
                'created_at' => $row['created_at'],
                'items' => []
            ];
        }

        if (!isset($orders[$orderId]['items'][$itemId])) {
            $orders[$orderId]['items'][$itemId] = [
                'name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'keys' => []
            ];
        }

        if ($row['activation_key'] !== null) {
            $orders[$orderId]['items'][$itemId]['keys'][] = $row['activation_key'];
        }
    }

    return $orders;
}
