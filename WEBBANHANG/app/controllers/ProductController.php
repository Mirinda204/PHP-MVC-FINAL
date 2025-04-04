<?php
require_once 'app/config/database.php';
require_once 'app/models/ProductModel.php';
require_once 'app/models/CategoryModel.php';
require_once 'app/helpers/SessionHelper.php';

class ProductController {
    private $productModel;
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->productModel = new ProductModel($this->db);
    }

    private function isAdmin() {
        return SessionHelper::isAdmin();
    }

    public function index() {
        $products = $this->productModel->getProducts();
        include 'app/views/product/list.php';
    }

    public function show($id) {
        $product = $this->productModel->getProductById($id);
        if ($product) {
            include 'app/views/product/show.php';
        } else {
            echo "Không thấy sản phẩm.";
        }
    }

    public function add() {
        if (!$this->isAdmin()) {
            exit("Bạn không có quyền truy cập chức năng này!");
        }
        $categories = (new CategoryModel($this->db))->getCategories();
        include 'app/views/product/add.php';
    }

    public function save() {
        if (!$this->isAdmin()) {
            exit("Bạn không có quyền truy cập chức năng này!");
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? '';
            $category_id = $_POST['category_id'] ?? null;
            $image = isset($_FILES['image']) && $_FILES['image']['error'] == 0 ? $this->uploadImage($_FILES['image']) : "";
            
            $result = $this->productModel->addProduct($name, $description, $price, $category_id, $image);
            if (is_array($result)) {
                $errors = $result;
                $categories = (new CategoryModel($this->db))->getCategories();
                include 'app/views/product/add.php';
            } else {
                header('Location: /Product');
            }
        }
    }

    public function edit($id) {
        if (!$this->isAdmin()) {
            exit("Bạn không có quyền truy cập chức năng này!");
        }
        $product = $this->productModel->getProductById($id);
        $categories = (new CategoryModel($this->db))->getCategories();
        if ($product) {
            include 'app/views/product/edit.php';
        } else {
            echo "Không thấy sản phẩm.";
        }
    }

    public function update() {
        if (!$this->isAdmin()) {
            exit("Bạn không có quyền truy cập chức năng này!");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $category_id = $_POST['category_id'];
            $image = isset($_FILES['image']) && $_FILES['image']['error'] == 0 ? $this->uploadImage($_FILES['image']) : $_POST['existing_image'];
            
            if ($this->productModel->updateProduct($id, $name, $description, $price, $category_id, $image)) {
                header('Location: /Product');
            } else {
                echo "Đã xảy ra lỗi khi lưu sản phẩm.";
            }
        }
    }

    public function delete($id) {
        if (!$this->isAdmin()) {
            exit("Bạn không có quyền truy cập chức năng này!");
        }
        if ($this->productModel->deleteProduct($id)) {
            header('Location: /Product');
        } else {
            echo "Đã xảy ra lỗi khi xóa sản phẩm.";
        }
    }

    private function uploadImage($file) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $target_file = $target_dir . basename($file["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($file["tmp_name"]);
        
        if ($check === false || $file["size"] > 10 * 1024 * 1024 || !in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception("Lỗi: File không hợp lệ hoặc quá lớn.");
        }
        if (!move_uploaded_file($file["tmp_name"], $target_file)) {
            throw new Exception("Lỗi khi tải lên hình ảnh.");
        }
        return $target_file;
    }

    public function addToCart($id) {
        $product = $this->productModel->getProductById($id);
        if (!$product) exit("Không tìm thấy sản phẩm.");
        
        $_SESSION['cart'][$id] = isset($_SESSION['cart'][$id]) ? [
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $_SESSION['cart'][$id]['quantity'] + 1,
            'image' => $product->image
        ] : [
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'image' => $product->image
        ];
        
        header('Location: /Product/cart');
    }

    public function cart() {
        $cart = $_SESSION['cart'] ?? [];
        include 'app/views/product/cart.php';
    }

    public function updateCart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'];
            $quantity = $data['quantity'];
            
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] = ($quantity > 0) ? $quantity : 0;
                echo json_encode(['success' => true, 'total' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $_SESSION['cart']))]);
            }
        }
    }

    public function checkout() {
        include 'app/views/product/checkout.php';
    }
}
