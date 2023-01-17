<?php
namespace src;

class Product {

  private $db;
  private $requestMethod;
  private $productId;

  public function __construct($db, $requestMethod, $productId)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;
    $this->productId = $productId;
  }

  public function processRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        if ($this->productId) {
          $response = $this->getProduct($this->productId);
        } else {
          $response = $this->getAllProducts();
        };
        break;
      case 'POST':
        $response = $this->createProduct();
        break;
      case 'PUT':
        $response = $this->updateProduct($this->productId);
        break;
      case 'DELETE':
        $response = $this->deleteProduct($this->productId);
        break;
      default:
        $response = $this->notFoundResponse();
        break;
    }
    header($response['status_code_header']);
    if ($response['body']) {
        echo $response['body'];
    }
  }

  private function getAllProducts()
  {
    $query = "
      SELECT
          id, name, description, quantity, created_at, updated_at
      FROM
          products;
    ";

    try {
      $statement = $this->db->query($query);
      $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }

    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function getProduct($id)
  {
    $result = $this->find($id);
    if (! $result) {
        return $this->notFoundResponse();
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function createProduct()
  {

    if (! $this->validateProduct($_POST)) {
      return $this->unprocessableEntityResponse();
    }

    $query = "
      INSERT INTO products
          (name, description, quantity)
      VALUES
          (:name, :description, :quantity);
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array(
        'name'  => htmlspecialchars($_POST['name']),
        'description' => htmlspecialchars($_POST['description']),
        'quantity' => $_POST['quantity'],
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }

    $response['status_code_header'] = 'HTTP/1.1 201 Created';
    $response['body'] = json_encode(array('message' => 'Product Created'));
    return $response;
  }

  private function updateProduct($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validateProduct($input)) {
      return $this->unprocessableEntityResponse();
    }

    $statement = "
      UPDATE products
      SET
        name = :name,
        description  = :description,
        quantity = :quantity,
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($statement);
      $statement->execute(array(
        'id' => (int) $id,
        'name' => $input['name'],
        'description'  => $input['description'],
        'quantity' => $input['quantity'],
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Post Updated!'));
    return $response;
  }

  private function deleteProduct(int $id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }

    $query = "
      DELETE FROM products
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Product deleted'));
    return $response;
  }

  public function find($id)
  {
    $query = "
      SELECT
          id, name, description, quantity, created_at, updated_at
      FROM
          products
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }

  private function validateProduct($input) : bool
  {
    if (! isset($input['name'])) {
      return false;
    }
    
    if (! isset($input['description'])) {
      return false;
    }

    if (! isset($input['quantity'])) {
        return false;
    }
    elseif(!is_numeric($input['quantity']))
    {
        return false;
    }
    return true;
  }

  private function unprocessableEntityResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
    $response['body'] = json_encode([
      'error' => 'Invalid input'
    ]);
    return $response;
  }

  private function notFoundResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
    $response['body'] = null;
    return $response;
  }
}