<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("db.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila'); // Ensure timezone is set

if (!isset($_SESSION['admin_id'])) {
    header("Location: loginadmin.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$stmt = $con->prepare("SELECT full_name, role FROM admin WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    session_destroy();
    header("Location: loginadmin.php");
    exit();
}

$full_name = $admin['full_name'];
$role = $admin['role'];

// --- Data Fetching for Reports ---
$today = new DateTime();
$today_str = $today->format('Y-m-d');

// Weekly Data (Last 7 Days including today)
$weekly_data = [];
$weekly_labels = [];
$date_counts_weekly = [];
$week_start_date = (clone $today)->modify('-6 days');

$stmt_weekly = $con->prepare("
    SELECT consultation_date, COUNT(*) as count
    FROM consultation
    WHERE consultation_date BETWEEN ? AND ?
    GROUP BY consultation_date
    ORDER BY consultation_date ASC
");
$week_start_str = $week_start_date->format('Y-m-d');
$stmt_weekly->bind_param("ss", $week_start_str, $today_str);
$stmt_weekly->execute();
$result_weekly = $stmt_weekly->get_result();
while ($row = $result_weekly->fetch_assoc()) {
    $date_counts_weekly[$row['consultation_date']] = (int)$row['count']; // Ensure integer
}
$stmt_weekly->close();

// Populate weekly data array for the last 7 days
for ($i = 0; $i < 7; $i++) {
    $current_day = (clone $week_start_date)->modify("+$i days");
    $day_str = $current_day->format('Y-m-d');
    $weekly_labels[] = $current_day->format('M d');
    $weekly_data[] = $date_counts_weekly[$day_str] ?? 0;
}
$weekly_total = array_sum($weekly_data);


// Monthly Data (Last 12 Months including current month)
$monthly_data = [];
$monthly_labels = [];
$month_counts = [];
$current_month_date = new DateTime('first day of this month');

$stmt_monthly = $con->prepare("
    SELECT DATE_FORMAT(consultation_date, '%Y-%m') as month_year, COUNT(*) as count
    FROM consultation
    WHERE consultation_date >= DATE_SUB(?, INTERVAL 11 MONTH) AND consultation_date <= ?
    GROUP BY month_year
    ORDER BY month_year ASC
");
$month_start_date_str = (clone $current_month_date)->modify('-11 months')->format('Y-m-d');
$today_end_of_month_str = $today->format('Y-m-t');
$stmt_monthly->bind_param("ss", $month_start_date_str, $today_end_of_month_str);
$stmt_monthly->execute();
$result_monthly = $stmt_monthly->get_result();
while ($row = $result_monthly->fetch_assoc()) {
    $month_counts[$row['month_year']] = (int)$row['count']; // Ensure integer
}
$stmt_monthly->close();

// Populate monthly data array for the last 12 months
for ($i = 0; $i < 12; $i++) {
    $current_loop_month = (clone $current_month_date)->modify("-$i months");
    $month_year_str = $current_loop_month->format('Y-m');
    array_unshift($monthly_labels, $current_loop_month->format('M \'y'));
    array_unshift($monthly_data, $month_counts[$month_year_str] ?? 0);
}
$monthly_total_current_month = $month_counts[$today->format('Y-m')] ?? 0;
$monthly_total_12_months = array_sum($monthly_data); // NEW: Total for analysis

// --- Pass data to JavaScript ---
$weekly_report_data = [
    'labels' => $weekly_labels,
    'data' => $weekly_data,
    'total' => $weekly_total // Pass total
];
$monthly_report_data = [
    'labels' => $monthly_labels,
    'data' => $monthly_data,
    'total' => $monthly_total_12_months // Pass total
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Reports | Kalinga Medical Clinic</title>
  <link rel="stylesheet" href="superadweeklyreports.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
       <a href="superadmindashboard.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
       <a href="superadweeklyreports.php" class="nav-item active"><i class="fas fa-clipboard-list"></i><span>Reports</span></a>
       <a href="aichatlogs.php" class="nav-item"><i class="fas fa-robot"></i><span>AI Chat Records</span></a>
       <a href="superadinventory.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Inventory</span></a>
       <a href="superadappointment.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
       <a href="superadaccounts.php" class="nav-item"><i class="fas fa-users"></i><span>Accounts</span></a>
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
            <h1 class="page-title"><i class="fas fa-chart-line"></i> Clinic Reports</h1>
            <p class="page-subtitle">View consultation statistics.</p>
          </div>
        </div>
        <div class="header-actions">
            <button class="btn-primary" onclick="downloadReport()">
                <i class="fas fa-download"></i>
                <span>Download Report</span>
            </button>
        </div>
      </div>
    </header>

    <div class="content-section">
      <div class="dashboard-grid">
        <div class="dashboard-card report-tab active" id="weeklyTab" onclick="showReport('weekly')">
          <div class="card-icon"><i class="fas fa-calendar-week"></i></div>
          <div class="card-content">
            <h3>Weekly Consultations</h3>
            <h1 id="weeklyCountDisplay"><?php echo $weekly_total; ?></h1>
            <p>In the last 7 days</p>
          </div>
        </div>
        <div class="dashboard-card report-tab" id="monthlyTab" onclick="showReport('monthly')">
          <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
          <div class="card-content">
            <h3>Monthly Consultations</h3>
            <h1 id="monthlyCountDisplay"><?php echo $monthly_total_current_month; ?></h1>
            <p>For <?php echo date('F Y'); ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="content-section">
      <div class="chart-container" id="reportToDownload">
        <h2 id="chartTitle">Weekly Consultation Report (Last 7 Days)</h2>
        <canvas id="consultationChart"></canvas>
      </div>
    </div>

<script>
    // Data from PHP
    const weeklyReport = <?php echo json_encode($weekly_report_data); ?>;
    const monthlyReport = <?php echo json_encode($monthly_report_data); ?>;

    let consultationChart = null;
    let currentReportType = 'weekly';

    // --- NEW: Function to load image as base64 ---
    function loadImageAsBase64(url, callback) {
        const xhr = new XMLHttpRequest();
        xhr.onload = function() {
            const reader = new FileReader();
            reader.onloadend = function() {
                callback(reader.result);
            }
            reader.readAsDataURL(xhr.response);
        };
        xhr.open('GET', url);
        xhr.responseType = 'blob';
        xhr.send();
    }
    // --- END ---

    let logoBase64 = null; // Variable to hold the logo image data

    document.addEventListener("DOMContentLoaded", function() {
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

        const ctx = document.getElementById('consultationChart').getContext('2d');
        consultationChart = new Chart(ctx, {
            type: 'bar',
            data: { /* Initial data set in showReport */ },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0 // Ensure integer ticks
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 14 },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 6,
                        displayColors: false
                    }
                },
                 // Ensure white background is drawn before rendering chart elements for PDF
                 plugins: [{
                    id: 'customCanvasBackgroundColor',
                    beforeDraw: (chart, args, options) => {
                      const {ctx} = chart;
                      ctx.save();
                      ctx.globalCompositeOperation = 'destination-over';
                      ctx.fillStyle = options.color || '#ffffff'; // Use white or option color
                      ctx.fillRect(0, 0, chart.width, chart.height);
                      ctx.restore();
                    }
                  }],
                animation: false // Disable animation for faster PDF generation if needed
            }
        });

        // Load logo image data
        loadImageAsBase64('logoo.PNG', function(base64) {
            logoBase64 = base64;
        });

        showReport('weekly'); // Show initial report
    });


    function showReport(type) {
        const weeklyTab = document.getElementById('weeklyTab');
        const monthlyTab = document.getElementById('monthlyTab');
        const chartTitle = document.getElementById('chartTitle');

        if (!consultationChart) return;

        currentReportType = type; // Update the current report type

        if (type === 'weekly') {
            consultationChart.data.labels = weeklyReport.labels;
            consultationChart.data.datasets[0] = { // Define dataset properties
                label: 'Consultations',
                data: weeklyReport.data,
                backgroundColor: 'rgba(59, 130, 246, 0.7)', // Brighter blue
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1,
                borderRadius: 4
            };
            chartTitle.textContent = 'Weekly Consultation Report (Last 7 Days)';
            weeklyTab.classList.add('active');
            monthlyTab.classList.remove('active');
        } else if (type === 'monthly') {
            consultationChart.data.labels = monthlyReport.labels;
            consultationChart.data.datasets[0] = { // Define dataset properties
                label: 'Consultations',
                data: monthlyReport.data,
                backgroundColor: 'rgba(16, 185, 129, 0.7)', // Green color
                borderColor: 'rgba(5, 150, 105, 1)',
                borderWidth: 1,
                borderRadius: 4
            };
            chartTitle.textContent = 'Monthly Consultation Report (Last 12 Months)';
            weeklyTab.classList.remove('active');
            monthlyTab.classList.add('active');
        }
        consultationChart.update();
    }

    // --- UPDATED: Download PDF Function ---
    async function downloadReport() {
        const { jsPDF } = window.jspdf;
        const reportElement = document.getElementById('reportToDownload');
        const canvasElement = document.getElementById('consultationChart');
        const date = new Date();
        const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit'});
        const filename = `Kalinga-Clinic-${currentReportType}-report-${date.toISOString().slice(0, 10)}.pdf`;

        // Get current data for analysis
        const currentData = (currentReportType === 'weekly') ? weeklyReport : monthlyReport;
        const totalConsultations = currentData.total;
        const periodLength = currentData.labels.length;
        const averageConsultations = periodLength > 0 ? (totalConsultations / periodLength).toFixed(1) : 0;
        const peakValue = Math.max(...currentData.data);
        const peakIndex = currentData.data.indexOf(peakValue);
        const peakLabel = periodLength > 0 && peakIndex !== -1 ? currentData.labels[peakIndex] : 'N/A';
        const analysisPeriod = (currentReportType === 'weekly') ? 'day' : 'month';

        try {
            // 1. Capture the chart canvas
            const canvasImage = canvasElement.toDataURL('image/png', 1.0); // High quality PNG

            // 2. Initialize jsPDF (A4 Landscape: 297x210 mm -> approx 842x595 points)
            const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'pt',
                format: 'a4'
            });

            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const margin = 40;
            let currentY = margin;

            // 3. Add Logo (if loaded)
            if (logoBase64) {
                // You might need to adjust logo dimensions
                const logoWidth = 50;
                const logoHeight = 50;
                pdf.addImage(logoBase64, 'PNG', margin, currentY, logoWidth, logoHeight);
            }

            // 4. Add Clinic Name and Report Title
            pdf.setFontSize(18);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Kalinga Medical Clinic', pageWidth / 2, currentY + 15, { align: 'center' });

            pdf.setFontSize(14);
            pdf.setFont('helvetica', 'normal');
            pdf.text(chartTitle.textContent, pageWidth / 2, currentY + 35, { align: 'center' });
            currentY += 60; // Move down past logo/title area

            // 5. Add Generation Info
            pdf.setFontSize(9);
            pdf.setTextColor(100); // Gray color
            pdf.text(`Report Generated: ${formattedDate} at ${formattedTime}`, margin, currentY);
            currentY += 20;

            // 6. Add Analysis Section
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(0); // Black color
            pdf.text('Report Analysis:', margin, currentY);
            currentY += 18;

            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'normal');
            pdf.text(`• Total Consultations (${(currentReportType === 'weekly' ? 'Last 7 Days' : 'Last 12 Months')}): ${totalConsultations}`, margin + 10, currentY);
            currentY += 14;
            pdf.text(`• Average Consultations per ${analysisPeriod}: ${averageConsultations}`, margin + 10, currentY);
            currentY += 14;
            pdf.text(`• Peak Period (${analysisPeriod}): ${peakLabel} (${peakValue} consultations)`, margin + 10, currentY);
            currentY += 25; // More space before chart

            // 7. Add Chart Image
            const chartMaxHeight = pageHeight - currentY - margin; // Max height remaining
            const chartMaxWidth = pageWidth - (2 * margin);
            const imgProps = pdf.getImageProperties(canvasImage);
            const aspectRatio = imgProps.width / imgProps.height;

            let imgHeight = chartMaxHeight;
            let imgWidth = imgHeight * aspectRatio;

            if (imgWidth > chartMaxWidth) {
                imgWidth = chartMaxWidth;
                imgHeight = imgWidth / aspectRatio;
            }

            // Center the chart
            const chartX = margin + (chartMaxWidth - imgWidth) / 2;
            pdf.addImage(canvasImage, 'PNG', chartX, currentY, imgWidth, imgHeight);
            currentY += imgHeight + 20; // Move below chart

            // 8. Add Footer (Optional)
            pdf.setFontSize(8);
            pdf.setTextColor(150);
            const footerText = "Kalinga Medical Clinic Report - Confidential";
            pdf.text(footerText, pageWidth / 2, pageHeight - margin / 2, { align: 'center' });

            // 9. Save PDF
            pdf.save(filename);

        } catch (err) {
            console.error("Could not generate PDF", err);
            alert("Sorry, an error occurred while generating the PDF.");
        }
    }


</script>
</body>
</html>