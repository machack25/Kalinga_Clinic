<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginadmin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$stmt = $con->prepare("SELECT * FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

$full_name = $admin['full_name'];
$role      = $admin['role'];

$medicines = [];
$res = $con->query("SELECT * FROM inventory ORDER BY medicine_name ASC");
while ($row = $res->fetch_assoc()) {
  $medicines[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Inventory - Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="superadinventory.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  
  <div id="mobile-overlay" class="mobile-overlay"></div>

  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <img src="logoo.PNG" alt="Kalinga Medical Clinic" class="sidebar-logo">
        <div class="logo-text">
          <h2>Kalinga Medical</h2>
          <p>Clinic Management</p>
        </div>
      </div>
    </div>

    <div class="user-profile">
      <div class="user-avatar">
        <i class="fas fa-user-shield"></i>
      </div>
      <div class="user-info">
        <h3><?php echo htmlspecialchars($full_name); ?></h3>
        <span class="user-role"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a href="superadmindashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
      <a href="superadweeklyreports.php" class="nav-item"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
      <a href="aichatlogs.php" class="nav-item"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
      <a href="superadinventory.php" class="nav-item active"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
      <a href="superadappointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
      <a href="superadaccounts.php" class="nav-item"><i class="fas fa-users"></i><span>Accounts</span></a>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <div class="main-content">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <button id="menu-toggle" class="menu-toggle">
            <i class="fas fa-bars"></i>
          </button>
          <div class="title-group">
            <h1 class="page-title">
              <i class="fas fa-pills"></i>
              Medicine Inventory
            </h1>
            <p class="page-subtitle">Track and manage all medicine stock levels</p>
          </div>
        </div>
        <div class="header-actions">
          <button class="btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i>
            <span>Add Medicine</span>
          </button>
        </div>
      </div>
    </header>

    <div class="search-section">
      <div class="search-container">
        <div class="search-input-group">
          <i class="fas fa-search"></i>
          <input type="text" id="searchBar" placeholder="Search medicine..." onkeyup="searchMedicine()">
        </div>
        <div class="filter-options">
          <select class="filter-select" onchange="filterByStatus(this.value)">
            <option value="">All Statuses</option>
            <option value="Available">Available</option>
            <option value="No Stock">No Stock</option>
          </select>
        </div>
      </div>
    </div>

    <div class="content-section">
      <div class="records-container">
        <div class="records-header">
          <h2 class="records-title">
            <i class="fas fa-capsules"></i>
            Current Inventory
          </h2>
          <div class="records-stats">
            <span class="stat-item">
              <i class="fas fa-pills"></i>
              <span id="totalMedicines">0</span> Total Medicines
            </span>
          </div>
        </div>
        <div class="inventory-grid" id="inventoryTableBody">
          </div>
      </div>
    </div>
  </div>

  <div class="modal" id="medicineModal" style="display:none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content hospital-modal">
      <div class="modal-header">
        <div class="modal-icon"><i class="fas fa-pills"></i></div>
        <h2 id="modalTitle" class="modal-title">Add New Medicine</h2>
        <button class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
      </div>
      <form id="medicineForm" class="hospital-form">
        <div class="modal-body">
            <div class="form-section">
              <h3 class="section-title">Medicine Information</h3>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-capsules"></i> Medicine Name</label>
                <input type="text" id="medicineName" class="form-input" required>
              </div>
            </div>
            <div class="form-section">
              <h3 class="section-title">Stock Status</h3>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-warehouse"></i> Availability</label>
                <div class="radio-group">
                  <label class="radio-option">
                    <input type="radio" name="availability" value="Available" required>
                    <span class="radio-custom"></span>
                    <div class="radio-content">
                      <i class="fas fa-check-circle"></i>
                      <span>Available</span>
                    </div>
                  </label>
                  <label class="radio-option">
                    <input type="radio" name="availability" value="No Stock">
                    <span class="radio-custom"></span>
                    <div class="radio-content">
                      <i class="fas fa-times-circle"></i>
                      <span>No Stock</span>
                    </div>
                  </label>
                </div>
              </div>
            </div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Medicine</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let medicines = <?php echo json_encode($medicines); ?>;
    let currentlyEditingId = null;

    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });

        document.getElementById('medicineForm').addEventListener('submit', saveMedicine);
        renderTable();
    });

    function renderTable(filteredList = medicines) {
        const container = document.getElementById('inventoryTableBody');
        container.innerHTML = '';
        if (filteredList.length === 0) {
            container.innerHTML = `<div class="empty-state"><h3>No Medicines Found</h3><p>Your inventory is empty or no items match your search.</p></div>`;
            document.getElementById('totalMedicines').textContent = 0;
            return;
        }
        filteredList.forEach(medicine => {
            const statusClass = medicine.availability === 'Yes' ? 'available' : 'no-stock';
            const statusIcon = medicine.availability === 'Yes' ? 'fa-check-circle' : 'fa-times-circle';
            const statusText = medicine.availability === 'Yes' ? 'Available' : 'No Stock';
            const card = document.createElement('div');
            card.className = 'medicine-card';
            card.innerHTML = `
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-pills"></i></div>
                    <div class="card-title">
                        <h3>${medicine.medicine_name}</h3>
                        <span class="status-badge ${statusClass}"><i class="fas ${statusIcon}"></i> ${statusText}</span>
                    </div>
                    <div class="card-actions">
                        <button class="action-btn edit-btn" onclick='openEditModal(${JSON.stringify(medicine)})'><i class="fas fa-edit"></i></button>
                        <button class="action-btn delete-btn" onclick='deleteMedicine(${medicine.id})'><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            container.appendChild(card);
        });
        document.getElementById('totalMedicines').textContent = filteredList.length;
    }

    function searchMedicine() {
        const searchValue = document.getElementById('searchBar').value.toLowerCase();
        renderTable(medicines.filter(med => med.medicine_name.toLowerCase().includes(searchValue)));
    }

    function filterByStatus(status) {
        if (!status) {
            renderTable(medicines);
            return;
        }
        const availabilityStatus = status === 'Available' ? 'Yes' : 'No';
        renderTable(medicines.filter(med => med.availability === availabilityStatus));
    }

    function openAddModal() {
        currentlyEditingId = null;
        document.getElementById('medicineForm').reset();
        document.getElementById('modalTitle').textContent = "Add New Medicine";
        document.getElementById('medicineModal').style.display = "flex";
    }

    function openEditModal(medicine) {
        currentlyEditingId = medicine.id;
        document.getElementById('modalTitle').textContent = "Edit Medicine";
        document.getElementById('medicineName').value = medicine.medicine_name;
        const availabilityValue = medicine.availability === 'Yes' ? 'Available' : 'No Stock';
        document.querySelector(`input[name="availability"][value="${availabilityValue}"]`).checked = true;
        document.getElementById('medicineModal').style.display = "flex";
    }

    function closeModal() {
        document.getElementById("medicineModal").style.display = "none";
    }

    function saveMedicine(e) {
        e.preventDefault();
        const name = document.getElementById('medicineName').value;
        const availability = document.querySelector('input[name="availability"]:checked').value === "Available" ? "Yes" : "No";
        const url = currentlyEditingId ? "update_medicine.php" : "add_medicine.php";
        const body = currentlyEditingId
            ? `id=${currentlyEditingId}&name=${encodeURIComponent(name)}&availability=${availability}`
            : `name=${encodeURIComponent(name)}&availability=${availability}`;

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Medicine saved successfully!");
                location.reload();
            } else {
                alert("Error: " + data.error);
            }
        });
    }

    function deleteMedicine(id) {
        if (confirm("Are you sure you want to delete this medicine?")) {
            fetch("delete_medicine.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Medicine deleted successfully!");
                    location.reload();
                } else {
                    alert("Error: " + data.error);
                }
            });
        }
    }
  </script>

</body>
</html>