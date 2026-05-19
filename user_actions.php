<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}
$admin_id_session = $_SESSION['admin_id'];
$stmt_auth = $con->prepare("SELECT role FROM admin WHERE admin_id = ?");
$stmt_auth->bind_param("i", $admin_id_session);
$stmt_auth->execute();
$auth_result = $stmt_auth->get_result()->fetch_assoc();
if (!$auth_result || $auth_result['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Authorization failed. Super admin rights required.']);
    exit;
}


$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {
    case 'add':
        $response = addUser();
        break;
    case 'edit':
        $response = editUser();
        break;
    case 'delete':
        $response = deleteUser();
        break;
}

echo json_encode($response);
$con->close();


function addUser() {
    global $con;
    $accountType = $_POST['accountType'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($role) || empty($full_name)) {
        return ['success' => false, 'message' => 'All fields except phone are required.'];
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    if ($accountType === 'admin') {
        $sql = "INSERT INTO admin (username, email, password, role, full_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $full_name);
    } else { // 'user'
        $sql = "INSERT INTO users (username, email, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $role, $full_name, $phone);
    }
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Account created successfully.'];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
    }
}

function editUser() {
    global $con;
    $id = $_POST['id'] ?? 0;
    $accountType = $_POST['accountType'] ?? '';
    if (!$id || !$accountType) {
        return ['success' => false, 'message' => 'Invalid ID or account type.'];
    }

    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    if ($accountType === 'admin') {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE admin SET username=?, email=?, full_name=?, role=?, password=? WHERE admin_id=?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("sssssi", $username, $email, $full_name, $role, $hashed_password, $id);
        } else {
            $sql = "UPDATE admin SET username=?, email=?, full_name=?, role=? WHERE admin_id=?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssssi", $username, $email, $full_name, $role, $id);
        }
    } else { 
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET username=?, email=?, full_name=?, phone=?, role=?, password=? WHERE id=?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssssssi", $username, $email, $full_name, $phone, $role, $hashed_password, $id);
        } else {
            $sql = "UPDATE users SET username=?, email=?, full_name=?, phone=?, role=? WHERE id=?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("sssssi", $username, $email, $full_name, $phone, $role, $id);
        }
    }

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Account updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
    }
}

function deleteUser() {
    global $con, $admin_id_session;
    $id = $_POST['id'] ?? 0;
    $accountType = $_POST['accountType'] ?? '';

    if (!$id || !$accountType) {
        return ['success' => false, 'message' => 'Invalid ID or account type.'];
    }
    
    if ($accountType === 'admin' && $id == $admin_id_session) {
        return ['success' => false, 'message' => 'You cannot delete your own account.'];
    }

    if ($accountType === 'admin') {
        $sql = "DELETE FROM admin WHERE admin_id = ?";
    } else { 
        $sql = "DELETE FROM users WHERE id = ?";
    }
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Account deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Account not found.'];
        }
    } else {
        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
    }
}
?>
