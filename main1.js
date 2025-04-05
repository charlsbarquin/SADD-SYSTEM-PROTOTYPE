document.addEventListener("DOMContentLoaded", function () {
    const professorSelect = document.getElementById("professor-select");
    const cameraSection = document.getElementById("camera-section");
    const takePhotoBtn = document.getElementById("take-photo");
    const attendanceTable = document.getElementById("attendance-table");

    let isProcessing = false; // Prevent multiple requests

    // Initialize Webcam
    Webcam.set({
        width: 320,
        height: 240,
        image_format: 'jpeg',
        jpeg_quality: 90
    });

    // Show Camera Only When a Professor is Selected
    professorSelect.addEventListener("change", function () {
        if (this.value) {
            cameraSection.style.display = "block";
            Webcam.attach("#camera");
        } else {
            cameraSection.style.display = "none";
            Webcam.reset();
        }
    });

    // Load attendance when the page loads
    loadAttendance();

    // Capture and Save Photo with GPS Data
    takePhotoBtn.addEventListener("click", function () {
        if (isProcessing) return; // Prevent multiple submissions
        isProcessing = true;
        takePhotoBtn.disabled = true; // Disable button to prevent spam clicks

        const professorId = professorSelect.value;
        if (!professorId) {
            alert("Please select your name before taking a photo.");
            isProcessing = false;
            takePhotoBtn.disabled = false;
            return;
        }

        getGPSLocation((latitude, longitude, accuracy, deviceType) => {
            Webcam.snap(function (data_uri) {
                document.getElementById("results").innerHTML = `<img src="${data_uri}" width="100"/>`;
                sendPhotoToBackend(professorId, data_uri, latitude, longitude, accuracy, deviceType);
            });
        });
    });

    function getGPSLocation(callback) {
        if (!navigator.geolocation) {
            alert("❌ Geolocation is not supported on this device.");
            return;
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

                if (accuracy > 1000) {
                    alert("⚠️ Your location accuracy is low (" + accuracy + "m). Try moving to an open area.");
                }

                callback(latitude, longitude, accuracy, deviceType);
            },
            function(error) {
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

    function sendPhotoToBackend(professorId, imageData, latitude, longitude, accuracy, deviceType) {
        fetch("../api/checkin.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `professor_id=${professorId}&image_data=${encodeURIComponent(imageData)}&latitude=${latitude}&longitude=${longitude}&accuracy=${accuracy}&device=${deviceType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                if (data.maps_link) {
                    document.getElementById("maps-link").innerHTML = `<a href="${data.maps_link}" target="_blank">📍 View Check-In Location</a>`;
                }
                loadAttendance(); // Refresh attendance table after check-in
            } else {
                alert(data.message); // Alert if already checked in
            }
        })
        .catch(error => console.error("Error:", error))
        .finally(() => {
            isProcessing = false;
            takePhotoBtn.disabled = false;
        });
    }

    function loadAttendance() {
        fetch("../api/get-attendance.php")
        .then(response => response.json())
        .then(data => {
            let table = document.getElementById("attendance-table");
            table.innerHTML = "";

            data.forEach(row => {
                table.innerHTML += `
                    <tr>
                        <td><img src="${row.face_scan_image}" width="50"></td>
                        <td>${row.name}</td>
                        <td>${row.check_in}</td>
                        <td>
                            ${row.check_out 
                                ? row.check_out 
                                : `<button class="btn btn-sm btn-danger timeout-btn" data-id="${row.professor_id}">Time Out</button>`}
                        </td>
                        <td>${row.recorded_at}</td>
                        <td><a href="https://www.google.com/maps?q=${row.latitude},${row.longitude}" target="_blank">📍 View Location</a></td>
                    </tr>
                `;
            });

            document.querySelectorAll(".timeout-btn").forEach(button => {
                button.addEventListener("click", function () {
                    const professorId = this.getAttribute("data-id");
                    checkOut(professorId);
                });
            });
        })
        .catch(error => console.error("Error:", error));
    }

    function checkOut(professorId) {
        fetch("../api/checkout.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `professor_id=${professorId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                loadAttendance();
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error("Error:", error));
    }
});
