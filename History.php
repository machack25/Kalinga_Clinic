<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// --- NEW: Handle Medical History Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'update_medical_history') {
        header('Content-Type: application/json');
        
        $details = $input['details'] ?? '';
        
        try {
            $stmt = $con->prepare("UPDATE users SET details = ? WHERE id = ?");
            $stmt->bind_param("si", $details, $patient_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Medical history updated successfully!']);
            } else {
                // This isn't an error, just means the data was the same
                echo json_encode(['status' => 'success', 'message' => 'Medical history saved.']);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
        }
        exit();
    }
}
// --- END: Handle Medical History Update ---


// --- Fetch User Name AND Medical History ---
$stmt = $con->prepare("SELECT full_name, details FROM users WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($full_name, $details);
$stmt->fetch();
$stmt->close();

// --- Fetch Consultation History ---
$history_sql = $con->prepare("
    SELECT 
        c.consultation_id, 
        c.consultation_date, 
        c.diagnosis,
        c.prescription,
        c.notes,
        d.doctor_name
    FROM consultation c
    JOIN doctor d ON c.doctor_id = d.doctor_id
    WHERE c.patient_id = ?
    ORDER BY c.consultation_date DESC, c.consultation_id DESC
");
$history_sql->bind_param("i", $patient_id);
$history_sql->execute();
$result = $history_sql->get_result();
$consultations = $result->fetch_all(MYSQLI_ASSOC);
$history_sql->close();

// --- Pass data to JavaScript ---
$consultations_json = json_encode($consultations);
$details_json = json_encode($details); // Pass details to JS
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History - Kalinga Medical Clinic</title>
    <link rel="stylesheet" href="History.css">
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
            <a href="History.php" class="active"><i class="fas fa-history"></i><span>History</span></a>
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
                    <h2>Patient History & Records</h2>
                    <p>Review your past consultations and manage your medical history.</p>
                </div>
            </header>

            <div id="notification" class="notification"></div>

            <div class="content-grid">
                <section class="history-section">
                    <h3><i class="fas fa-notes-medical"></i> Consultation Records</h3>
                    <p>Review details from your previous visits.</p>
                    <button class="view-history-btn" onclick="openHistoryModal()">
                        <i class="fas fa-folder-open"></i> View Consultation History
                    </button>
                </section>
                
                <section class="history-section">
                    <h3><i class="fas fa-file-medical"></i> Medical History</h3>
                    <p>Keep your medical history up to date.</p>
                    <button class="view-history-btn" onclick="openMedicalModal()">
                        <i class="fas fa-edit"></i> Update Medical History
                    </button>
                </section>
            </div>
        </main>
    </div>

    <div class="modal" id="historyFolderModal">
        <div class="modal-overlay" onclick="closeHistoryModal()"></div>
        <div class="modal-content folder-modal-content">
            
            <div id="historyListContainer">
                <div class="modal-header">
                    <h2><i class="fas fa-folder"></i> Consultation History</h2>
                    <span class="close" onclick="closeHistoryModal()">&times;</span>
                </div>
                <div class="modal-body" id="historyList">
                    </div>
            </div>

            <div id="historyDetailContainer" style="display:none;">
                <div class="modal-header detail-header">
                    <button class="back-btn" onclick="showHistoryList()">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                    <h2 id="detailTitle"><i class="fas fa-file-medical-alt"></i> Consultation Details</h2>
                    <span class="close" onclick="closeHistoryModal()">&times;</span>
                </div>
                <div class="modal-body detail-body">
                    <div class="detail-group">
                        <span><i class="fas fa-calendar-day"></i> Date:</span>
                        <p id="detailConsultationDate"></p>
                    </div>
                    <div class="detail-group">
                        <span><i class="fas fa-user-md"></i> Doctor:</span>
                        <p id="detailDoctorName"></p>
                    </div>
                    <hr>
                    <div class="detail-group">
                        <span><i class="fas fa-stethoscope"></i> Diagnosis:</span>
                        <p id="detailDiagnosis"></p>
                    </div>
                    <div class="detail-group">
                        <span><i class="fas fa-prescription"></i> Prescription:</span>
                        <p id="detailPrescription"></p>
                    </div>
                    <div class="detail-group">
                        <span><i class="fas fa-notes-medical"></i> Notes:</span>
                        <p id="detailNotes"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal" id="medicalModal">
        <div class="modal-overlay" onclick="closeMedicalModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-medical"></i> Update Medical History</h2>
                <span class="close" onclick="closeMedicalModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="medicalHistoryForm">
                    <div class="form-group">
                        <label for="details">Medical History (e.g., allergies, family history)</label>
                        <textarea id="details" name="details" rows="6" placeholder="Enter any known allergies, past surgeries, or family medical history (e.g., diabetes, hypertension)..."></textarea>
                    </div>
                    <button type="submit" class="form-btn">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

<script>
// Store consultation data from PHP
const consultations = <?php echo $consultations_json; ?>;
// NEW: Store medical history data
let medicalHistory = <?php echo $details_json; ?>;

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

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
    
    // --- Event Delegation for History List ---
    const historyListDiv = document.getElementById("historyList");
    if (historyListDiv) {
        historyListDiv.addEventListener('click', function(event) {
            const listItem = event.target.closest('.history-list-item');
            if (listItem) {
                event.preventDefault();
                const consultationId = listItem.getAttribute('data-id');
                if (consultationId) {
                    showHistoryDetail(parseInt(consultationId, 10));
                }
            }
        });
    }

    // --- NEW: Medical History Form Submission ---
    const medicalHistoryForm = document.getElementById("medicalHistoryForm");
    if(medicalHistoryForm) {
        medicalHistoryForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            const newDetails = document.getElementById("details").value;

            try {
                const response = await fetch('History.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_medical_history',
                        details: newDetails
                    })
                });
                const data = await response.json();
                
                showNotification(data.message, data.status);
                if (data.status === 'success') {
                    medicalHistory = newDetails; // Update the local variable
                    closeMedicalModal();
                }
            } catch (error) {
                showNotification('An unexpected error occurred.', 'error');
                console.error('Error:', error);
            }
        });
    }
});

// --- NEW: Show Notification Function ---
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification show ${type}`;
    setTimeout(() => {
        notification.className = 'notification';
    }, 3000);
}

// --- Consultation History Modal Logic ---
const historyModal = document.getElementById("historyFolderModal");
const historyListContainer = document.getElementById("historyListContainer");
const historyDetailContainer = document.getElementById("historyDetailContainer");

function openHistoryModal() {
    renderHistoryList();
    showHistoryList(); // Ensure list is visible by default
    if (historyModal) historyModal.style.display = "flex";
}

function closeHistoryModal() {
    if (historyModal) historyModal.style.display = "none";
}

function renderHistoryList() {
    const historyListDiv = document.getElementById("historyList");
    if (!historyListDiv) return;

    historyListDiv.innerHTML = ''; // Clear previous list

    if (!consultations || consultations.length === 0) {
        historyListDiv.innerHTML = `<div class="empty-state modal-empty"><i class="fas fa-folder-open"></i><p>No past consultations found.</p></div>`;
        return;
    }

    consultations.forEach(c => {
        const item = document.createElement('a');
        item.href = "#";
        item.className = 'history-list-item';
        item.setAttribute('data-id', c.consultation_id);
        
        const formattedDate = new Date(c.consultation_date).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric'
        });
        
        item.innerHTML = `
            <div class="item-icon"><i class="fas fa-file-alt"></i></div>
            <div class="item-info">
                <span class="item-title">${formattedDate} - ${c.doctor_name}</span>
                <span class="item-subtitle">${c.diagnosis || 'General Consultation'}</span>
            </div>
            <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
        `;
        historyListDiv.appendChild(item);
    });
}

function showHistoryDetail(consultationId) {
    const consultation = consultations.find(c => parseInt(c.consultation_id, 10) === consultationId);

    if (!consultation) {
        alert("Error: Could not find consultation details.");
        return;
    }

    const formattedDate = new Date(consultation.consultation_date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    
    const getSafeText = (text) => {
        if (!text) return 'N/A';
        return text.replace(/\\r\\n/g, '\n').replace(/\r\n/g, '\n');
    };

    document.getElementById("detailConsultationDate").innerText = formattedDate;
    document.getElementById("detailDoctorName").innerText = consultation.doctor_name;
    document.getElementById("detailDiagnosis").innerText = getSafeText(consultation.diagnosis);
    document.getElementById("detailPrescription").innerText = getSafeText(consultation.prescription);
    document.getElementById("detailNotes").innerText = getSafeText(consultation.notes);

    if (historyListContainer && historyDetailContainer) {
        historyListContainer.style.display = 'none';
        historyDetailContainer.style.display = 'block';
    }
}

function showHistoryList() {
    if (historyListContainer && historyDetailContainer) {
        historyDetailContainer.style.display = 'none';
        historyListContainer.style.display = 'block';
    }
}

// --- NEW: Medical History Modal Logic ---
const medicalModal = document.getElementById("medicalModal");

function openMedicalModal() {
    // Populate the textarea with the current history
    document.getElementById("details").value = medicalHistory || '';
    if (medicalModal) medicalModal.style.display = "flex";
}

function closeMedicalModal() {
    if (medicalModal) medicalModal.style.display = "none";
}

// Close modals if clicking on overlay
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeHistoryModal();
        closeMedicalModal();
    }
}
</script>
</body>
</html>