document.addEventListener("DOMContentLoaded", function () {
    const professorSelect = document.getElementById("professor-select"); // Professor selection input or dropdown
    const cameraSection = document.getElementById("camera-section"); // Camera section for capturing photo
    const takePhotoBtn = document.getElementById("take-photo"); // Button to take a photo
    let isProcessing = false; // Prevent multiple requests

    // Initialize Webcam with proper size and settings
    Webcam.set({
        width: 320,
        height: 240,
        image_format: 'jpeg',
        jpeg_quality: 90,
        constraints: {
            facingMode: 'user' // Use front-facing camera
        }
    });

    if (professorSelect && cameraSection) {
        professorSelect.addEventListener("change", function () {
            if (this.value) {
                cameraSection.style.display = "block";
                Webcam.attach("#camera");
            } else {
                cameraSection.style.display = "none";
                Webcam.reset();
            }
        });
    } else {
        console.error("Error: professorSelect or cameraSection not found.");
    }

    // Capture and Save Photo with GPS Data
    takePhotoBtn.addEventListener("click", function () {
        if (isProcessing) return; // Prevent multiple submissions
        isProcessing = true;
        takePhotoBtn.disabled = true; // Disable button to prevent spam clicks
        takePhotoBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing';

        const professorId = professorSelect.value;
        if (!professorId) {
            alert("Please select your name before taking a photo.");
            isProcessing = false;
            takePhotoBtn.disabled = false;
            takePhotoBtn.innerHTML = '<i class="fas fa-camera me-2"></i> Capture';
            return;
        }

        getGPSLocation((latitude, longitude, accuracy, deviceType) => {
            Webcam.snap(function (data_uri) {
                document.getElementById("results").innerHTML = `<img src="${data_uri}" width="100"/>`;
                
                // Determine status (On Leave logic is handled separately)
                const status = determineStatus(professorId, latitude, longitude);

                sendPhotoToBackend(professorId, data_uri, latitude, longitude, accuracy, deviceType, status);
            });
        });
    });

    // Determine the professor's status based on check-in time
    function determineStatus(professorId, latitude, longitude) {
        const scheduledTime = "2025-02-20 08:00:00"; // Get the scheduled time from the backend (can be dynamic)
        const actualCheckInTime = new Date().toISOString(); // Get current time as check-in time

        const scheduledTimestamp = new Date(scheduledTime).getTime();
        const checkInTimestamp = new Date(actualCheckInTime).getTime();

        const gracePeriod = 5 * 60 * 1000; // 5 minutes in milliseconds

        // Determine if the professor is present or absent based on check-in time
        if (checkInTimestamp <= (scheduledTimestamp + gracePeriod)) {
            return 'Present';
        } else {
            return 'Absent';
        }
    }

    // Send the captured photo to the backend with the status
    function sendPhotoToBackend(professorId, imageData, latitude, longitude, accuracy, deviceType, status) {
        fetch("../api/checkin.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `professor_id=${professorId}&image_data=${encodeURIComponent(imageData)}&latitude=${latitude}&longitude=${longitude}&accuracy=${accuracy}&device=${deviceType}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                // Show success modal instead of alert
                showSuccessModal('Time In Successful', `Time in recorded for ${data.professor_name} at ${data.check_in}`);
                
                // Refresh data after 3 seconds
                setTimeout(() => {
                    if (typeof loadAttendance === 'function') {
                        loadAttendance();
                    }
                    location.reload();
                }, 3000);
            } else {
                alert(data.message); // Alert if already checked in
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred during check-in. Please try again.");
        })
        .finally(() => {
            isProcessing = false;
            takePhotoBtn.disabled = false;
            takePhotoBtn.innerHTML = '<i class="fas fa-camera me-2"></i> Capture';
        });
    }

    // Helper function to get GPS location
    function getGPSLocation(callback) {
        if (!navigator.geolocation) {
            alert("❌ Geolocation is not supported on this device.");
            return;
        }

        // Show location processing status
        const locationProcess = document.getElementById("location-process");
        const locationText = document.getElementById("location-text");
        if (locationProcess && locationText) {
            document.getElementById("location-status").classList.add("hidden");
            locationProcess.classList.remove("hidden");
            locationText.textContent = "Getting your location...";
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                let latitude = position.coords.latitude;
                let longitude = position.coords.longitude;
                let accuracy = position.coords.accuracy;
                let deviceType = /iPhone|Android/i.test(navigator.userAgent) ? "Mobile" : "Desktop";

                console.log("📍 Latitude:", latitude);
                console.log("📍 Longitude:", longitude);
                console.log("🎯 Accuracy:", accuracy, "meters");

                // Update location status
                if (locationProcess && locationText) {
                    locationText.textContent = `Location acquired (Accuracy: ${accuracy}m)`;
                }

                if (accuracy > 1000) {
                    alert("⚠️ Your location accuracy is low (" + accuracy + "m). Try moving to an open area.");
                }

                callback(latitude, longitude, accuracy, deviceType);
            },
            function(error) {
                console.error("GPS Error:", error);
                if (locationProcess && locationText) {
                    locationText.textContent = `Error: ${error.message}`;
                }
                alert("❌ GPS Error: " + error.message);
                console.log("Retrying location in 5 seconds...");
                setTimeout(() => getGPSLocation(callback), 5000); // Retry after 5 seconds
            },
            {
                enableHighAccuracy: true,
                timeout: 60000,
                maximumAge: 0
            }
        );
    }

    // Function to show success modal
    function showSuccessModal(title, message) {
        // Check if modal elements exist
        const successModalEl = document.getElementById("successModal");
        if (!successModalEl) {
            console.warn("Success modal element not found");
            alert(`${title}: ${message}`);
            return;
        }
        
        const successMessageEl = document.getElementById("success-message");
        const successDetailsEl = document.getElementById("success-details");
        
        if (successMessageEl && successDetailsEl) {
            successMessageEl.textContent = title;
            successDetailsEl.textContent = message;
            
            const successModal = new bootstrap.Modal(successModalEl);
            successModal.show();
        } else {
            alert(`${title}: ${message}`);
        }
    }
});