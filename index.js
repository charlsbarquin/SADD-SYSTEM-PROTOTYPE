document.addEventListener("DOMContentLoaded", function () {
  // Initialize the system
  initializeSystem();

  // Core functions
  function initializeSystem() {
    updateClock();
    setInterval(updateClock, 1000);
    loadAttendanceOverview();
    loadRecentHistory();
    loadAttendance();
    setupEventListeners();
  }

  function updateClock() {
    const clockElement = document.getElementById("clock");
    if (!clockElement) return;

    const date = new Date();
    let hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, "0");
    const seconds = date.getSeconds().toString().padStart(2, "0");
    const ampm = hours >= 12 ? "PM" : "AM";

    hours = hours % 12 || 12; // Convert to 12-hour format
    clockElement.innerText = `${hours}:${minutes}:${seconds} ${ampm}`;
  }

  async function loadAttendanceOverview() {
    try {
      const response = await fetch("../api/get-attendance.php");
      if (!response.ok) throw new Error("Network response not ok");
      const data = await response.json();

      document.getElementById("total-professors").innerText =
        data.total_professors;
      document.getElementById("total-attendance").innerText =
        data.total_attendance;
      document.getElementById("pending-checkouts").innerText =
        data.pending_checkouts;
    } catch (error) {
      console.error("Error loading attendance overview:", error);
    }
  }

  async function loadRecentHistory() {
    try {
      const response = await fetch("../api/get-recent-history.php");
      if (!response.ok) throw new Error("Network response not ok");
      const data = await response.json();

      const historyList = document.getElementById("recent-history-list");
      if (!historyList) return;

      // Initial display
      historyList.innerHTML = data
        .slice(0, 5)
        .map(
          (item) =>
            `<li class="list-group-item">${item.name} - ${item.status} at ${item.check_in}</li>`
        )
        .join("");

      // View More/Less functionality
      const viewMoreButton = document.getElementById("view-more-btn");
      if (viewMoreButton) {
        viewMoreButton.addEventListener("click", function () {
          const isExpanded = historyList.children.length > 5;
          historyList.innerHTML = data
            .slice(0, isExpanded ? 5 : data.length)
            .map(
              (item) =>
                `<li class="list-group-item">${item.name} - ${item.status} at ${item.check_in}</li>`
            )
            .join("");
          viewMoreButton.innerText = isExpanded ? "View More" : "View Less";
        });
      }
    } catch (error) {
      console.error("Error loading recent history:", error);
      const historyList = document.getElementById("recent-history-list");
      if (historyList) {
        historyList.innerHTML =
          "<li class='list-group-item'>Failed to load history</li>";
      }
    }
  }

  async function loadAttendance() {
    try {
      const response = await fetch("../api/get-attendance.php");
      if (!response.ok) throw new Error("Network response not ok");
      const data = await response.json();

      const table = document.getElementById("attendance-table");
      if (!table) return;

      table.innerHTML = data
        .map(
          (row) => `
          <tr data-id="${row.professor_id}">
            <td><img src="${
              row.face_scan_image
            }" width="50" alt="Professor photo"></td>
            <td>${row.name}</td>
            <td>${row.check_in}</td>
            <td>${
              row.check_out ||
              `<button class="btn btn-sm btn-danger timeout-btn" data-id="${row.professor_id}">
                Time Out
              </button>`
            }
            </td>
            <td>${row.recorded_at}</td>
            <td>
              <a href="https://www.google.com/maps?q=${row.latitude},${
            row.longitude
          }" 
                 target="_blank" rel="noopener noreferrer">
                📍 View Location
              </a>
            </td>
            <td>${row.status}</td>
          </tr>
        `
        )
        .join("");

      // Add event listeners to timeout buttons
      document.querySelectorAll(".timeout-btn").forEach((button) => {
        button.addEventListener("click", function () {
          checkOutProfessor(this.getAttribute("data-id"));
        });
      });
    } catch (error) {
      console.error("Error loading attendance:", error);
    }
  }

  function setupEventListeners() {
    // Time Out button
    document
      .getElementById("time-out-btn")
      ?.addEventListener("click", openTimeOutModal);

    // Time In button
    document
      .getElementById("time-in-btn")
      ?.addEventListener("click", fetchProfessorsForCheckIn);

    // Search functionality
    document
      .getElementById("search-professor-in")
      ?.addEventListener("input", filterProfessorList);
    document
      .getElementById("search-professor-out")
      ?.addEventListener("input", filterProfessorList);
  }

  async function openTimeOutModal() {
    const modalElement = document.getElementById("timeOutModal");
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    const professorListOut = document.getElementById("professor-list-out");
    if (!professorListOut) return;

    try {
      professorListOut.innerHTML =
        '<li class="list-group-item text-center py-3"><div class="spinner-border" role="status"></div></li>';

      const response = await fetch("../api/get-checkedin-professors.php");
      if (!response.ok) throw new Error("Network response not ok");
      const data = await response.json();

      professorListOut.innerHTML =
        data.length > 0
          ? data
              .map(
                (prof) => `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                ${prof.name}
                <button class="btn btn-sm btn-outline-danger timeout-btn" 
                        data-id="${prof.id}">
                  Time Out
                </button>
              </li>
            `
              )
              .join("")
          : '<li class="list-group-item">No professors available for check-out</li>';

      // Add event listeners to modal timeout buttons
      professorListOut.querySelectorAll(".timeout-btn").forEach((button) => {
        button.addEventListener("click", function () {
          checkOutProfessor(this.getAttribute("data-id"));
          modal.hide();
        });
      });
    } catch (error) {
      console.error("Error opening timeout modal:", error);
      professorListOut.innerHTML =
        '<li class="list-group-item text-danger">Error loading data</li>';
    }
  }

  async function checkOutProfessor(professorId) {
    const buttons = document.querySelectorAll(
      `.timeout-btn[data-id="${professorId}"]`
    );

    // Show loading state
    buttons.forEach((btn) => {
      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status"></span> Processing';
    });

    try {
      const response = await fetch("../api/checkout.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `professor_id=${professorId}`,
      });

      // First check if the response is OK
      if (!response.ok) {
        // Try to parse error response as JSON, fallback to text if it fails
        let errorData;
        try {
          errorData = await response.json();
          throw new Error(errorData.message || "Checkout failed");
        } catch (e) {
          const errorText = await response.text();
          throw new Error(errorText || "Checkout failed with unknown error");
        }
      }

      // If response is OK, parse as JSON
      const data = await response.json();

      if (data.status !== "success") {
        throw new Error(data.message || "Checkout failed");
      }

      // Update UI
      const row = document.querySelector(`tr[data-id="${professorId}"]`);
      if (row) {
        row.cells[3].innerHTML = data.check_out;
        row.cells[6].innerHTML = data.status;
      }

      // Show success modal
      showSuccessModal(
        "Time Out Successful",
        `Professor has been successfully checked out at ${new Date().toLocaleTimeString()}.`
      );

      // Close the time out modal if open
      const timeOutModal = bootstrap.Modal.getInstance(
        document.getElementById("timeOutModal")
      );
      if (timeOutModal) {
        timeOutModal.hide();
      }

      // Refresh data after a short delay
      setTimeout(() => {
        loadAttendanceOverview();
        loadRecentHistory();
        loadAttendance(); // Refresh the attendance table
      }, 1000);
    } catch (error) {
      console.error("Error during checkout:", error);

      // Show error in modal
      document.getElementById("error-title").textContent = "Checkout Failed";
      document.getElementById("error-message").textContent = error.message;
      const errorModal = new bootstrap.Modal(
        document.getElementById("errorModal")
      );
      errorModal.show();
    } finally {
      // Reset buttons
      buttons.forEach((btn) => {
        btn.disabled = false;
        btn.innerHTML = "Time Out";
      });
    }
  }

  // Add this function if not already present
  function showSuccessModal(title, message) {
    document.getElementById("success-message").textContent = title;
    document.getElementById("success-details").textContent = message;
    const successModal = new bootstrap.Modal(
      document.getElementById("successModal")
    );
    successModal.show();
  }

  async function fetchProfessorsForCheckIn() {
    const professorList = document.getElementById("professor-list-in");
    if (!professorList) return;

    try {
      professorList.innerHTML =
        '<li class="list-group-item text-center py-3"><div class="spinner-border" role="status"></div></li>';

      const response = await fetch("../api/get-professors.php");
      if (!response.ok) throw new Error("Network response not ok");
      const data = await response.json();

      professorList.innerHTML =
        data.length > 0
          ? data
              .map(
                (prof) => `
              <li class="list-group-item professor-item" data-id="${prof.id}">
                ${prof.name}
              </li>
            `
              )
              .join("")
          : '<li class="list-group-item">No professors available</li>';

      // Add click handlers
      professorList.querySelectorAll(".professor-item").forEach((item) => {
        item.addEventListener("click", function () {
          selectProfessorForCheckIn(
            this.getAttribute("data-id"),
            this.textContent.trim()
          );
        });
      });
    } catch (error) {
      console.error("Error fetching professors:", error);
      professorList.innerHTML =
        '<li class="list-group-item text-danger">Error loading professors</li>';
    }
  }

  function selectProfessorForCheckIn(professorId, professorName) {
    const cameraSection = document.getElementById("camera-section");
    const locationStatus = document.getElementById("location-status");

    if (!cameraSection || !locationStatus) return;

    // Show check-in UI
    document.getElementById("search-professor-in").value = professorName;
    cameraSection.classList.remove("hidden");
    locationStatus.classList.remove("hidden");

    // Initialize webcam
    Webcam.set({
      width: 320,
      height: 240,
      image_format: "jpeg",
      jpeg_quality: 90,
    });
    Webcam.attach("#camera");

    // Handle photo capture
    document
      .getElementById("take-photo")
      .addEventListener("click", function () {
        if (!navigator.geolocation) {
          showErrorModal("Error", "Geolocation not supported by your browser");
          return;
        }

        navigator.geolocation.getCurrentPosition(
          async function (position) {
            try {
              const { latitude, longitude } = position.coords;
              const dataUri = await new Promise((resolve) =>
                Webcam.snap(resolve)
              );

              const response = await fetch("../api/store-photo.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `professor_id=${professorId}&image_data=${encodeURIComponent(
                  dataUri
                )}&latitude=${latitude}&longitude=${longitude}`,
              });

              if (!response.ok) throw new Error("Network response not ok");
              const data = await response.json();

              // Show success modal
              showSuccessModal(
                "Time In Successful",
                `${professorName} has been successfully checked in at ${new Date().toLocaleTimeString()}.`
              );

              // Close the time in modal
              const timeInModal = bootstrap.Modal.getInstance(
                document.getElementById("timeInModal")
              );
              if (timeInModal) {
                timeInModal.hide();
              }

              // Refresh data
              setTimeout(() => {
                loadRecentHistory();
                loadAttendanceOverview();
                loadAttendance();
              }, 1000);
            } catch (error) {
              console.error("Error during check-in:", error);
              showErrorModal("Check-in Failed", error.message);
            }
          },
          function (error) {
            console.error("Geolocation error:", error);
            showErrorModal("Location Error", "Could not get your location");
          }
        );
      });
  }

  function showErrorModal(title, message) {
    document.getElementById("error-title").textContent = title;
    document.getElementById("error-message").textContent = message;
    const errorModal = new bootstrap.Modal(
      document.getElementById("errorModal")
    );
    errorModal.show();
  }

  function filterProfessorList(event) {
    const searchQuery = event.target.value.toLowerCase();
    const listId =
      event.target.id === "search-professor-in"
        ? "professor-list-in"
        : "professor-list-out";

    const items = document.querySelectorAll(`#${listId} .list-group-item`);
    items.forEach((item) => {
      item.style.display = item.textContent.toLowerCase().includes(searchQuery)
        ? ""
        : "none";
    });
  }
});
