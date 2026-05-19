<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kalinga Medical Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="login.css" />
    <style>
      .popup {
        font-family: "Manrope", sans-serif;
        font-size: 16px;
        line-height: 1.6;
        font-weight: 500;
      }
      .popup b {
        font-family: "Outfit", sans-serif;
        font-weight: 700;
        color: #007bff;
      }
      .popup-icon {
        width: 24px;
        height: 24px;
        margin-right: 8px;
        vertical-align: middle;
      }
    </style>
  </head>
  <body>
    <nav class="navbar">
      <div class="navbar-brand">
        <img src="logoo.PNG" alt="Clinic Logo" />
        <span class="brand">Kalinga Medical Clinic</span>
      </div>

      <button class="hamburger" id="hamburger-menu">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>

      <div class="nav-links" id="nav-links">
        <ul>
          <li class="nav-item">
            <a href="#">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l6.59-6.59L19 9l-8 8z"
                />
              </svg>
              SCHEDULE
            </a>
            <div class="popup">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"
                />
              </svg>
              <b>Clinic Hours:</b><br />
              Everyday: 9 AM - 6 PM<br />
              <br />
              OPENS EVERYDAY!
            </div>
          </li>
          <li class="nav-item">
            <a href="#">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                />
              </svg>
              SERVICES
            </a>
            <div class="popup">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"
                />
              </svg>
              <b>Our Services:</b><br />
              General Check-ups<br />
              Emergency Care<br />
              Family Medicine
            </div>
          </li>
          <li class="nav-item">
            <a href="#">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"
                />
              </svg>
              HOTLINES
            </a>
            <div class="popup">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"
                />
              </svg>
              <b>Contact Us:</b><br />
              Landline: (046)8502497<br />
              Mobile: +63 912 345 6789<br />
              Emergency: 911<br />
              <br />
              <b>Alternative Hotlines (if closed):</b><br />
              Cavite Provincial Hospital: (046) 416 1234<br />
              De La Salle Medical Center: (02) 524 4611<br />
              St. Luke's Medical Center: (02) 8723 0000<br />
              Makati Medical Center: (02) 8888 8999
            </div>
          </li>
          <li class="nav-item">
            <a href="#">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 9l-9 9z"
                />
              </svg>
              ABOUT US
            </a>
            <div class="popup">
              <svg class="popup-icon" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                />
              </svg>
              <b>About Kalinga Medical Clinic:</b><br />
              We are dedicated to providing compassionate and affordable
              healthcare for families in Cavite since 2005. Our team of
              experienced professionals ensures the highest quality care in a
              welcoming environment.
            </div>
          </li>
        </ul>
        <div class="buttons">
          <a href="loginpage.php" class="btn login-btn">
            <svg
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 20 20"
              style="margin-right: 8px"
            >
              <path
                fill-rule="evenodd"
                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                clip-rule="evenodd"
              />
            </svg>
            LOGIN
          </a>
          <a href="register.php" class="btn register-btn">
            <svg
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 20 20"
              style="margin-right: 8px"
            >
              <path
                d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0V8h1V7z"
              />
            </svg>
            REGISTER
          </a>
          <a href="gemini_chat.php" class="btn ai-assistant-btn" target="_blank">
            <svg
              width="16"
              height="16"
              fill="currentColor"
              viewBox="0 0 24 24"
              style="margin-right: 8px"
            >
              <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 9l-9 9z"
              />
            </svg>
            AI ASSISTANT
          </a>
        </div>
      </div>
    </nav>

    <section class="hero">
      <h1>Welcome to Kalinga Medical Clinic</h1>
      <p>YOUR HEALTH, OUR PRIORITY</p>
    </section>

    <section class="services">
      <h2>Our Services</h2>
      <div class="service-grid">
        <div class="service">
          <h3>General Check-ups</h3>
          <p>Comprehensive health examinations to keep you in top shape.</p>
        </div>
        <div class="service">
          <h3>Emergency Care</h3>
          <p>24/7 emergency, Contact nearby hostpitals </p>
        </div>
        <div class="service">
          <h3>Family Medicine</h3>
          <p>Care for the whole family, from pediatrics to geriatrics.</p>
        </div>
      </div>
    </section>

    <footer class="footer">
      <p>&copy; 2025 Kalinga Medical Clinic. All rights reserved.</p>
    </footer>

    <script>
  const hamburgerMenu = document.getElementById("hamburger-menu");
  const navLinks = document.getElementById("nav-links");
  const navItems = document.querySelectorAll(".nav-item");

  hamburgerMenu.addEventListener("click", (event) => {
    event.stopPropagation(); 
    
    navLinks.classList.toggle("active");
    hamburgerMenu.classList.toggle("active");
  });

  navItems.forEach((item) => {
    const popup = item.querySelector(".popup");
    if (popup) {
      item.addEventListener("click", (event) => {
        if (window.innerWidth <= 768) {
          event.preventDefault();

          const isAlreadyActive = popup.classList.contains("active");

          navItems.forEach(otherItem => {
            const otherPopup = otherItem.querySelector('.popup');
            if (otherPopup) {
              otherPopup.classList.remove('active');
            }
          });

          if (!isAlreadyActive) {
            popup.classList.add("active");
          }
        }
      });
    }
  });
</script>
  </body>
</html>