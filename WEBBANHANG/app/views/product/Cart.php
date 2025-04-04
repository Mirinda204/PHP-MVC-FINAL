<?php include 'app/views/shares/header.php'; ?>
<h1>Giỏ hàng</h1>
<?php if (!empty($cart)): ?>
<ul class="list-group" id="cart-items">
<?php 
$total = 0;
foreach ($cart as $id => $item): 
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
?>
<li class="list-group-item" data-id="<?php echo $id; ?>">
    <h2><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if ($item['image']): ?>
        <img src="/<?php echo $item['image']; ?>" alt="Product Image" style="max-width: 100px;">
    <?php endif; ?>
    <p>Giá: <?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?> VND</p>
    <div class="quantity-control">
        <p>Số lượng: 
            <button class="btn btn-sm btn-secondary decrease" data-id="<?php echo $id; ?>">-</button>
            <span class="quantity"><?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?></span>
            <button class="btn btn-sm btn-secondary increase" data-id="<?php echo $id; ?>">+</button>
        </p>
    </div>
</li>
<?php endforeach; ?>
</ul>
<div class="total mt-3">
    <h3>Tổng tiền: <span id="total-amount"><?php echo number_format($total, 0, ',', '.'); ?></span> VND</h3>
</div>
<?php else: ?>
<p>Giỏ hàng của bạn đang trống.</p>
<?php endif; ?>
<a href="/Product" class="btn btn-secondary mt-2">Tiếp tục mua sắm</a>
<a href="/Product/checkout" class="btn btn-secondary mt-2">Thanh Toán</a>

<?php include 'app/views/shares/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.getElementById('cart-items');
    
    function updateCart(id, quantity) {
        fetch('/Product/updateCart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.list-group-item[data-id="${id}"]`);
                if (data.quantity === 0 && item) {
                    item.remove(); // Xóa sản phẩm khỏi giao diện
                    if (!cartItems.children.length) {
                        cartItems.outerHTML = '<p>Giỏ hàng của bạn đang trống.</p>';
                    }
                } else {
                    const quantitySpan = item.querySelector('.quantity');
                    quantitySpan.textContent = data.quantity;
                }
                // Cập nhật tổng tiền
                const totalAmount = document.getElementById('total-amount');
                totalAmount.textContent = new Intl.NumberFormat('vi-VN').format(data.total);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    cartItems.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('decrease') || target.classList.contains('increase')) {
            const id = target.getAttribute('data-id');
            const quantitySpan = target.parentElement.querySelector('.quantity');
            let quantity = parseInt(quantitySpan.textContent);
            
            if (target.classList.contains('decrease')) {
                quantity--;
            } else {
                quantity++;
            }
            
            if (quantity >= 0) {
                updateCart(id, quantity);
            }
        }
    });

    // Reset giỏ hàng khi đăng nhập vào tài khoản khác
    fetch('/Product/resetCartOnLogin')
        .then(response => response.json())
        .then(data => {
            if (data.reset) {
                document.getElementById('cart-items').innerHTML = '<p>Giỏ hàng của bạn đang trống.</p>';
                document.getElementById('total-amount').textContent = '0';
            }
        });
});
</script>
