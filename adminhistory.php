<?php
session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginadmin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch Admin Info
$stmt = $con->prepare("SELECT full_name, role FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}
$full_name = $admin['full_name'];
$role      = $admin['role'];


// --- UPDATED: Fetch ALL Patients (users) ---
$patients_with_history = []; // Renamed variable, now holds all patients
$patient_query = "
    SELECT u.id, u.full_name /* REMOVED: DISTINCT */
    FROM users u
    /* REMOVED: JOIN consultation c ON u.id = c.patient_id */
    WHERE u.role = 'user'
    ORDER BY u.full_name ASC
";
$patient_result = $con->query($patient_query);
while ($row = $patient_result->fetch_assoc()) {
    $patients_with_history[] = $row; // Now holds all patients
}


// --- Fetch ALL Consultations, grouped by patient ---
$all_consultations = [];
$consultation_query = "
    SELECT
        c.consultation_id, c.appointment_id, c.consultation_date,
        c.diagnosis, c.prescription, c.notes,
        c.patient_id, c.doctor_id,
        d.doctor_name
    FROM consultation c
    JOIN doctor d ON c.doctor_id = d.doctor_id
    ORDER BY c.patient_id, c.consultation_date DESC, c.consultation_id DESC
";
$consultation_result = $con->query($consultation_query);
while ($row = $consultation_result->fetch_assoc()) {
    $patient_id_key = $row['patient_id'];
    if (!isset($all_consultations[$patient_id_key])) {
        $all_consultations[$patient_id_key] = [];
    }
    // Store consultation data keyed by consultation_id within each patient
    $all_consultations[$patient_id_key][$row['consultation_id']] = $row;
}

// --- Fetch All Doctors for Modal ---
$all_doctors_list = [];
$doc_result = $con->query("SELECT doctor_id, doctor_name FROM doctor ORDER BY doctor_name");
while ($doc_row = $doc_result->fetch_assoc()) {
    $all_doctors_list[] = $doc_row;
}


// Pass data to JavaScript
$patients_json = json_encode(array_values($patients_with_history)); // Ensure it's a JS array
$consultations_json = json_encode($all_consultations);
$doctors_json = json_encode($all_doctors_list);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Patient Records | Kalinga Medical Clinic</title>
    <link rel="stylesheet" href="adminhistory.css">
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
                <div class="logo-text"><h2>Kalinga Medical</h2><p>Clinic Management</p></div>
            </div>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                <span class="user-role"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="doctor dashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="adminhistory.php" class="nav-item active"><i class="fas fa-clipboard-list"></i><span>Patient Records</span></a>
            <a href="admininventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
            <a href="adminappoint.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <header class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <button id="menu-toggle" class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <div class="title-group">
                        <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Patient Consultation Records</h1>
                        <p class="page-subtitle">View and manage patient history</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="search-section">
             <div class="search-container">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search patients..." onkeyup="filterPatientList()">
                </div>
            </div>
        </div>

        <div class="content-section">

            <div class="records-container" id="patientListContainer">
                <div class="records-header">
                    <h2 class="records-title"><i class="fas fa-users"></i> Select a Patient</h2>
                    <p class="records-stats">Showing <span id="patientCount"><?php echo count($patients_with_history); ?></span> registered patients.</p>
                </div>
                <div class="patient-list" id="patientList">
                    </div>
            </div>

            <div class="records-container" id="consultationListContainer" style="display: none;">
                <div class="records-header">
                     <div> <button class="back-button" onclick="showPatientList()"><i class="fas fa-arrow-left"></i> Back to Patients</button>
                        <h2 class="records-title" style="display:inline-block; margin-left: 10px;"><i class="fas fa-notes-medical"></i> Consultations for <span id="selectedPatientName"></span></h2>
                    </div>
                     <div> <p class="records-stats" style="display:inline-block; margin-right: 15px;">Total records: <span id="consultationCount">0</span></p>
                        <button class="btn-primary" id="addConsultationBtn" onclick="openAddConsultationModal()"><i class="fas fa-plus"></i> Add Record</button>
                    </div>
                </div>
                <div class="records-grid" id="consultationList">
                    </div>
            </div>

        </div> </div> <div class="modal" id="editConsultationModal" style="display:none;">
        <div class="modal-overlay" onclick="closeEditConsultationModal()"></div>
        <div class="modal-content hospital-modal">
            <div class="modal-header">
                <div class="modal-icon"><i class="fas fa-notes-medical"></i></div>
                <h2 class="modal-title" id="editConsultationModalTitle">Consultation Details</h2>
                <button class="close-btn" onclick="closeEditConsultationModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="editConsultationForm" class="hospital-form">
                <input type="hidden" id="editConsultationId" name="consultation_id">
                <input type="hidden" id="editPatientId" name="patient_id">
                <input type="hidden" id="editAppointmentId" name="appointment_id"> <div class="modal-body">
                    <div class="form-section">
                        <h3 class="section-title">Consultation For: <span id="editPatientName" style="font-weight: normal;"></span></h3>
                    </div>
                     <div class="form-section">
                        <h3 class="section-title">Details</h3>
                        <div class="form-group">
                            <label for="editConsultationDate" class="form-label"><i class="fas fa-calendar-alt"></i> Date</label>
                            <input type="date" id="editConsultationDate" name="consultation_date" class="form-input" required>
                        </div>
                        <div class="form-group">
                             <label for="editDoctorId" class="form-label"><i class="fas fa-user-md"></i> Doctor</label>
                             <select id="editDoctorId" name="doctor_id" class="form-select" required>
                                 <option value="">Select Doctor...</option>
                                 <?php foreach ($all_doctors_list as $doctor): ?>
                                     <option value="<?php echo $doctor['doctor_id']; ?>"><?php echo htmlspecialchars($doctor['doctor_name']); ?></option>
                                 <?php endforeach; ?>
                             </select>
                         </div>
                    </div>
                    <div class="form-section">
                        <h3 class="section-title">Findings</h3>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-stethoscope"></i> Diagnosis</label>
                            <textarea id="editDiagnosis" name="diagnosis" class="form-textarea" placeholder="Enter diagnosis..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-prescription-bottle-alt"></i> Prescription</label>
                            <textarea id="editPrescription" name="prescription" class="form-textarea" placeholder="Enter prescription details..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-file-alt"></i> Notes</label>
                            <textarea id="editNotes" name="notes" class="form-textarea" placeholder="Enter additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditConsultationModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>


<script>
    const allPatients = <?php echo $patients_json; ?>;
    let allConsultations = <?php echo $consultations_json; ?>; // Use let to allow updates
    const allDoctors = <?php echo $doctors_json; ?>;
    let currentPatientId = null; // Track currently viewed patient
    let currentPatientName = null;

    document.addEventListener("DOMContentLoaded", function() {
        renderPatientList(allPatients); // Initial render

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

        // Event delegation for clicking on a patient item
        const patientListDiv = document.getElementById('patientList');
        if (patientListDiv) {
            patientListDiv.addEventListener('click', function(event) {
                const patientItem = event.target.closest('.patient-list-item');
                if (patientItem) {
                    currentPatientId = patientItem.getAttribute('data-patient-id');
                    currentPatientName = patientItem.getAttribute('data-patient-name');
                    if (currentPatientId && currentPatientName) {
                        showConsultations(currentPatientId, currentPatientName);
                    }
                }
            });
        }

        // Add submit listener for the edit/add form
        const editForm = document.getElementById('editConsultationForm');
        if(editForm) {
            editForm.addEventListener('submit', handleEditConsultationFormSubmit);
        }
    });

    function showPatientList() {
        document.getElementById('patientListContainer').style.display = 'block';
        document.getElementById('consultationListContainer').style.display = 'none';
        document.getElementById('searchInput').value = ''; // Clear search
        currentPatientId = null; // Clear current patient
        currentPatientName = null;
        renderPatientList(allPatients); // Re-render full list
    }

    function showConsultations(patientId, patientName) {
        document.getElementById('patientListContainer').style.display = 'none';
        document.getElementById('consultationListContainer').style.display = 'block';
        document.getElementById('selectedPatientName').textContent = patientName;

        // Use Object.values() to get consultations as an array for the specific patient
        const consultationsArray = allConsultations[patientId] ? Object.values(allConsultations[patientId]) : [];
        // Sort by date descending (most recent first)
        consultationsArray.sort((a, b) => new Date(b.consultation_date) - new Date(a.consultation_date));

        renderConsultationList(consultationsArray);
        document.getElementById('consultationCount').textContent = consultationsArray.length;
    }

    function renderPatientList(patients) {
        const listContainer = document.getElementById('patientList');
        listContainer.innerHTML = ''; // Clear previous list

        if (!patients || patients.length === 0) {
            listContainer.innerHTML = `<div class="empty-state folder-empty"><i class="fas fa-folder-open"></i><p>No patients found.</p></div>`; // Updated text
            document.getElementById('patientCount').textContent = 0;
            return;
        }

        document.getElementById('patientCount').textContent = patients.length;
        patients.forEach(patient => {
            const item = document.createElement('div');
            item.className = 'patient-list-item';
            item.setAttribute('data-patient-id', patient.id);
            item.setAttribute('data-patient-name', patient.full_name);
            item.innerHTML = `
                <div class="patient-icon"><i class="fas fa-folder"></i></div>
                <div class="patient-info">
                    <span class="patient-name">${patient.full_name}</span>
                    <span class="patient-id">ID: ${patient.id}</span>
                </div>
                <div class="patient-arrow"><i class="fas fa-chevron-right"></i></div>
            `;
            listContainer.appendChild(item);
        });
    }

    function renderConsultationList(consultations) {
        const gridContainer = document.getElementById('consultationList');
        gridContainer.innerHTML = ''; // Clear previous list

        if (!consultations || consultations.length === 0) {
            gridContainer.innerHTML = `<div class="empty-state record-empty"><i class="fas fa-file-excel"></i><p>No consultation records found for this patient.</p></div>`;
            return;
        }

        consultations.forEach(record => {
            const formattedDate = new Date(record.consultation_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const getSafeText = (text) => text ? text.replace(/\\r\\n|\r\n|\n/g, '\n') : 'N/A';

            const card = document.createElement('div');
            card.className = 'record-card';
            card.innerHTML = `
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-file-medical-alt"></i></div>
                    <div class="card-title">
                        <h3>Consultation Record</h3>
                        <span class="card-date">${formattedDate}</span>
                    </div>
                    <div class="card-actions">
                         <button class="action-btn edit-btn" onclick="openEditConsultationModal(${record.consultation_id})" title="Edit Record"><i class="fas fa-edit"></i></button>
                         </div>
                </div>
                <div class="card-content">
                    <div class="info-row">
                        <div class="info-item">
                            <i class="fas fa-user-md"></i>
                            <div class="info-text">
                                 <span class="info-label">Doctor</span>
                                 <span class="info-value">${record.doctor_name || 'N/A'}</span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-stethoscope"></i>
                            <div class="info-text">
                                <span class="info-label">Diagnosis</span>
                                <span class="info-value">${getSafeText(record.diagnosis)}</span>
                            </div>
                        </div>
                    </div>
                     <div class="info-row">
                         <div class="info-item full-width">
                            <i class="fas fa-prescription"></i>
                            <div class="info-text">
                                <span class="info-label">Prescription</span>
                                <span class="info-value notes-content">${getSafeText(record.prescription)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="notes-section">
                        <span class="notes-label"><i class="fas fa-notes-medical"></i> Notes</span>
                        <p class="notes-content">${getSafeText(record.notes)}</p>
                    </div>
                </div>
            `;
            gridContainer.appendChild(card);
        });
    }

    function filterPatientList() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const filteredPatients = allPatients.filter(patient =>
            patient.full_name.toLowerCase().includes(searchTerm)
        );
        renderPatientList(filteredPatients);
    }

    // --- Modal Functions ---
    const modal = document.getElementById('editConsultationModal');
    const form = document.getElementById('editConsultationForm');

    function openAddConsultationModal() {
        if (!currentPatientId || !currentPatientName) {
            alert("Please select a patient first.");
            return;
        }
        form.reset(); // Clear form
        document.getElementById('editConsultationModalTitle').textContent = 'Add New Consultation';
        document.getElementById('editConsultationId').value = ''; // No ID for new record
        document.getElementById('editPatientId').value = currentPatientId;
        document.getElementById('editPatientName').textContent = currentPatientName;
        document.getElementById('editAppointmentId').value = ''; // No appointment ID when adding from history
        document.getElementById('editConsultationDate').valueAsDate = new Date(); // Default to today
        document.getElementById('editDoctorId').value = ''; // Reset doctor dropdown
        modal.style.display = 'flex';
    }

    function openEditConsultationModal(consultationId) {
         if (!currentPatientId || !currentPatientName) return; // Should not happen if button is visible

        const record = allConsultations[currentPatientId]?.[consultationId];

        if (!record) {
            alert('Error: Could not find consultation details.');
            return;
        }

        form.reset();
        document.getElementById('editConsultationModalTitle').textContent = 'Edit Consultation Details';
        document.getElementById('editConsultationId').value = record.consultation_id;
        document.getElementById('editPatientId').value = record.patient_id;
        document.getElementById('editPatientName').textContent = currentPatientName; // Use name from context
        document.getElementById('editAppointmentId').value = record.appointment_id || ''; // May be null
        document.getElementById('editConsultationDate').value = record.consultation_date;
        document.getElementById('editDoctorId').value = record.doctor_id;
        document.getElementById('editDiagnosis').value = record.diagnosis || '';
        document.getElementById('editPrescription').value = record.prescription || '';
        document.getElementById('editNotes').value = record.notes || '';

        modal.style.display = 'flex';
    }

    function closeEditConsultationModal() {
        modal.style.display = 'none';
    }

    async function handleEditConsultationFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(form);
        const isEditMode = !!formData.get('consultation_id'); // Check if it's an edit or add
        const url = isEditMode ? 'update_consultation.php' : 'add_consultation.php'; // Use different endpoints

        // For add_consultation.php compatibility, ensure appointment_id isn't sent if empty
        if (!isEditMode && formData.get('appointment_id') === '') {
             formData.delete('appointment_id');
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams(formData) // Send as form data
            });
            const result = await response.json();

            if (result.success) {
                alert(isEditMode ? 'Consultation updated successfully!' : 'Consultation added successfully!');
                closeEditConsultationModal();
                // --- Refresh data ---
                await refreshConsultationsForCurrentPatient();

            } else {
                alert(`Error: ${result.message || 'An unknown error occurred.'}`);
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            alert('An error occurred. Please check the console and try again.');
        }
    }

    // --- Helper to refresh data after add/edit ---
    async function refreshConsultationsForCurrentPatient() {
        if (!currentPatientId) return;

        try {
             // Re-fetch all consultations (could be optimized to fetch only one patient)
             const response = await fetch('get_all_consultations.php'); // Need to create this endpoint
             if (!response.ok) throw new Error('Network response was not ok');
             allConsultations = await response.json(); // Update global data

             // Re-render the list for the current patient
             showConsultations(currentPatientId, currentPatientName);

        } catch (error) {
            console.error('Error refreshing consultation data:', error);
            alert('Could not refresh data automatically. Please reload the page.');
            // Fallback: Reload the whole page
            // location.reload();
        }
    }


</script>
</body>
</html>