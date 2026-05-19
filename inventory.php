<?php
session_start();
include("db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

$stmt = $con->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: loginpage.php");
    exit();
}

$full_name = $user['full_name'];

$inventoryQuery = $con->query("SELECT medicine_name, availability FROM inventory ORDER BY medicine_name ASC");
$medicines = [];
if ($inventoryQuery) {
    while ($row = $inventoryQuery->fetch_assoc()) {
        $medicines[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medicine Inventory - Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="inventory.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

  <button class="menu-toggle" id="menuToggle" aria-label="Open menu">
    <i class="fas fa-bars"></i>
  </button>

  <div class="sidebar" id="sidebar">
    <img src="logoo.PNG" alt="Logo" class="sidebar-logo">
    <h3><?php echo htmlspecialchars($full_name); ?></h3>
    <nav class="menu">
      <a href="patient dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
      <a href="History.php"><i class="fas fa-history"></i><span>History</span></a>
      <a href="appointment.php"><i class="fas fa-calendar-alt"></i><span>Appointment</span></a>
      <a href="Account.php"><i class="fas fa-user-circle"></i><span>Account</span></a>
    </nav>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
  </div>

  <div class="main-container">
    <div class="overlay" id="overlay"></div>
    <main class="main-content">
      <header class="header">
        <div class="header-title">
          <h2>Medicine Inventory</h2>
          <p>Check the availability of medicines.</p>
        </div>
      </header>
      
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Search for a medicine...">
      </div>
      
      <div class="table-container">
        <div class="table-wrapper">
          <table id="medicineTable">
            <thead>
              <tr>
                <th>Medicine Name</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($medicines)): ?>
                <tr>
                  <td colspan="2" class="empty-state">The inventory is currently empty.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($medicines as $medicine): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                    <td>
                      <?php if ($medicine['availability'] === 'Yes'): ?>
                          <span class="status-badge available"><i class="fas fa-check-circle"></i> Available</span>
                      <?php else: ?>
                          <span class="status-badge not-available"><i class="fas fa-times-circle"></i> Not Available</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr class="no-results" style="display: none;">
                    <td colspan="2" class="empty-state">No medicines found matching your search.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const searchInput = document.getElementById('searchInput');

    function toggleMenu() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMenu);
    }
    if (overlay) {
        overlay.addEventListener('click', toggleMenu);
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toUpperCase();
            const table = document.getElementById('medicineTable');
            const tr = table.getElementsByTagName('tr');
            const noResultsRow = table.querySelector('.no-results');
            let visibleRowCount = 0;

            for (let i = 1; i < tr.length; i++) {
                if (tr[i].classList.contains('no-results')) continue;

                let td = tr[i].getElementsByTagName("td")[0]; 
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        visibleRowCount++;
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }

            if (noResultsRow) {
                if (visibleRowCount === 0) {
                    noResultsRow.style.display = "table-row";
                } else {
                    noResultsRow.style.display = "none";
                }
            }
        });
    }
});
</script>

</body>
</html>