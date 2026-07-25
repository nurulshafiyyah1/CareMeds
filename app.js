/**
 * app.js
 * Client-side script for CAREMEDS Hospital Appointment Management System
 * Handles client-side UX: modals, multi-step wizards, auto-fills, edit actions, and print actions
 */

// ==========================================================================
// WIZARD FORM MULTI-STEP NAVIGATION (ADD APPOINTMENT)
// ==========================================================================

let activeFormTab = "tab-patient";

function switchFormTab(tabId) {
    activeFormTab = tabId;
    
    // Toggle active tab header buttons
    document.querySelectorAll(".form-tab-btn").forEach(btn => {
        if (btn.getAttribute("data-tab") === tabId) {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });

    // Toggle active tab content body
    document.querySelectorAll(".form-tab-content").forEach(content => {
        if (content.id === tabId) {
            content.classList.add("active");
        } else {
            content.classList.remove("active");
        }
    });

    // Manage Wizard footer buttons
    const prevBtn = document.getElementById("btn-form-prev");
    const nextBtn = document.getElementById("btn-form-next");
    const saveBtn = document.getElementById("btn-save-record");

    if (!prevBtn || !nextBtn || !saveBtn) return;

    if (tabId === "tab-patient") {
        prevBtn.classList.add("hidden");
        nextBtn.classList.remove("hidden");
        saveBtn.classList.add("hidden");
    } else if (tabId === "tab-vitals") {
        prevBtn.classList.remove("hidden");
        nextBtn.classList.remove("hidden");
        saveBtn.classList.add("hidden");
    } else if (tabId === "tab-treatment") {
        prevBtn.classList.remove("hidden");
        nextBtn.classList.add("hidden");
        saveBtn.classList.remove("hidden");
    }
}

// Validate fields of a single tab
function validateTabInputs(tabId) {
    let isValid = true;

    if (tabId === "tab-patient") {
        const nameInput = document.getElementById("form-patient-name");
        const idInput = document.getElementById("form-patient-id");
        const ageInput = document.getElementById("form-patient-age");
        const genderInput = document.getElementById("form-patient-gender");

        const requiredInputs = [nameInput, idInput, ageInput, genderInput];
        requiredInputs.forEach(input => {
            if (!input || !input.value.trim()) {
                if (input) input.parentElement.classList.add("invalid");
                isValid = false;
            } else {
                if (input) input.parentElement.classList.remove("invalid");
            }
        });
    } 
    else if (tabId === "tab-vitals") {
        const hospital = document.getElementById("form-hospital");
        const doctor = document.getElementById("form-doctor");
        const dateTime = document.getElementById("form-date-time");
        const type = document.getElementById("form-type");

        const requiredInputs = [hospital, doctor, dateTime, type];
        requiredInputs.forEach(input => {
            if (!input || !input.value.trim()) {
                if (input) input.parentElement.classList.add("invalid");
                isValid = false;
            } else {
                if (input) input.parentElement.classList.remove("invalid");
            }
        });
    } 
    else if (tabId === "tab-treatment") {
        const symptoms = document.getElementById("form-symptoms");
        const nurse = document.getElementById("form-nurse");
        const status = document.getElementById("form-status");

        const requiredInputs = [symptoms, nurse, status];
        requiredInputs.forEach(input => {
            if (!input || !input.value || !input.value.trim()) {
                if (input) input.parentElement.classList.add("invalid");
                isValid = false;
            } else {
                if (input) input.parentElement.classList.remove("invalid");
            }
        });
    }

    return isValid;
}

// Auto-fill patient properties upon name change (Add Modal)
const patientInput = document.getElementById("form-patient-name");
if (patientInput) {
    patientInput.addEventListener("input", (e) => {
        const val = e.target.value;
        if (typeof patientsData !== 'undefined') {
            const patient = patientsData.find(p => p.name.toLowerCase() === val.toLowerCase());
            
            if (patient) {
                document.getElementById("form-patient-id").value = patient.id;
                document.getElementById("form-patient-age").value = patient.age;
                document.getElementById("form-patient-gender").value = patient.gender;
                document.getElementById("form-patient-contact").value = patient.contact;
                document.getElementById("form-patient-allergies").value = patient.allergies;
                document.getElementById("form-patient-chronic").value = patient.chronic;
                
                patientInput.parentElement.classList.remove("invalid");
                document.getElementById("form-patient-id").parentElement.classList.remove("invalid");
                document.getElementById("form-patient-age").parentElement.classList.remove("invalid");
                document.getElementById("form-patient-gender").parentElement.classList.remove("invalid");
            }
        }
    });
}

// Auto-fill patient properties upon name change (Edit Modal)
const editPatientInput = document.getElementById("edit-patient-name");
if (editPatientInput) {
    editPatientInput.addEventListener("input", (e) => {
        const val = e.target.value;
        if (typeof patientsData !== 'undefined') {
            const patient = patientsData.find(p => p.name.toLowerCase() === val.toLowerCase());
            
            if (patient) {
                document.getElementById("edit-patient-id").value = patient.id;
                document.getElementById("edit-patient-age").value = patient.age;
                document.getElementById("edit-patient-gender").value = patient.gender;
                document.getElementById("edit-patient-contact").value = patient.contact;
                document.getElementById("edit-patient-allergies").value = patient.allergies;
                document.getElementById("edit-patient-chronic").value = patient.chronic;
            }
        }
    });
}

// Tab headers navigation click
document.querySelectorAll(".form-tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const targetTab = btn.getAttribute("data-tab");
        
        let canNavigate = true;
        if (activeFormTab === "tab-patient" && targetTab !== "tab-patient") {
            canNavigate = validateTabInputs("tab-patient");
        } else if (activeFormTab === "tab-vitals" && targetTab === "tab-treatment") {
            canNavigate = validateTabInputs("tab-patient") && validateTabInputs("tab-vitals");
        }

        if (canNavigate) {
            switchFormTab(targetTab);
        }
    });
});

// Wizard Next / Prev Buttons
const nextFormBtn = document.getElementById("btn-form-next");
if (nextFormBtn) {
    nextFormBtn.addEventListener("click", () => {
        if (activeFormTab === "tab-patient") {
            if (validateTabInputs("tab-patient")) {
                switchFormTab("tab-vitals");
            }
        } else if (activeFormTab === "tab-vitals") {
            if (validateTabInputs("tab-vitals")) {
                switchFormTab("tab-treatment");
            }
        }
    });
}

const prevFormBtn = document.getElementById("btn-form-prev");
if (prevFormBtn) {
    prevFormBtn.addEventListener("click", () => {
        if (activeFormTab === "tab-vitals") {
            switchFormTab("tab-patient");
        } else if (activeFormTab === "tab-treatment") {
            switchFormTab("tab-vitals");
        }
    });
}

// Save Appointment Form Submit Handler
const healthForm = document.getElementById("health-record-form");
if (healthForm) {
    healthForm.addEventListener("submit", (e) => {
        const isPatientValid = validateTabInputs("tab-patient");
        const isVitalsValid = validateTabInputs("tab-vitals");
        const isTreatmentValid = validateTabInputs("tab-treatment");

        if (!isPatientValid) {
            e.preventDefault();
            switchFormTab("tab-patient");
            return;
        }
        if (!isVitalsValid) {
            e.preventDefault();
            switchFormTab("tab-vitals");
            return;
        }
        if (!isTreatmentValid) {
            e.preventDefault();
            switchFormTab("tab-treatment");
            return;
        }
    });
}

// Add Modal toggles
const addModal = document.getElementById("add-record-modal");
const openAddBtnTable = document.getElementById("btn-add-record-table");
const closeAddBtn = document.getElementById("btn-close-add-modal");
const cancelAddBtn = document.getElementById("btn-cancel-record");

function openAddModalHandler() {
    if (healthForm) healthForm.reset();
    
    // Clear autofill inputs
    const inputsToClear = [
        "form-patient-name", "form-patient-id", "form-patient-age", 
        "form-patient-gender", "form-patient-contact", "form-patient-allergies", 
        "form-patient-chronic", "form-hospital", "form-doctor", 
        "form-date-time", "form-symptoms", "form-notes"
    ];
    inputsToClear.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = "";
    });

    // Clear validation highlights
    document.querySelectorAll(".form-group").forEach(el => el.classList.remove("invalid"));

    switchFormTab("tab-patient");
    if (addModal) addModal.classList.remove("hidden");
    lucide.createIcons();
}

function closeAddModalHandler() {
    if (addModal) addModal.classList.add("hidden");
}

if (openAddBtnTable) openAddBtnTable.addEventListener("click", openAddModalHandler);
if (closeAddBtn) closeAddBtn.addEventListener("click", closeAddModalHandler);
if (cancelAddBtn) cancelAddBtn.addEventListener("click", closeAddModalHandler);

// ==========================================================================
// EDIT MODAL ACTIONS
// ==========================================================================
const editModal = document.getElementById("edit-record-modal");
const closeEditBtn = document.getElementById("btn-close-edit-modal");
const cancelEditBtn = document.getElementById("btn-cancel-edit-record");

function closeEditModalHandler() {
    if (editModal) editModal.classList.add("hidden");
}

if (closeEditBtn) closeEditBtn.addEventListener("click", closeEditModalHandler);
if (cancelEditBtn) cancelEditBtn.addEventListener("click", closeEditModalHandler);

document.querySelectorAll(".btn-edit-appointment").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.preventDefault();
        
        // Fill out all fields using the data attributes
        document.getElementById("edit-appointment-id").value = btn.getAttribute("data-appointment-id");
        document.getElementById("edit-patient-name").value = btn.getAttribute("data-patient-name");
        document.getElementById("edit-patient-id").value = btn.getAttribute("data-patient-id");
        document.getElementById("edit-patient-age").value = btn.getAttribute("data-patient-age");
        document.getElementById("edit-patient-gender").value = btn.getAttribute("data-patient-gender");
        document.getElementById("edit-patient-contact").value = btn.getAttribute("data-patient-contact");
        document.getElementById("edit-patient-allergies").value = btn.getAttribute("data-patient-allergies");
        document.getElementById("edit-patient-chronic").value = btn.getAttribute("data-patient-chronic");
        
        document.getElementById("edit-hospital").value = btn.getAttribute("data-hospital");
        document.getElementById("edit-doctor").value = btn.getAttribute("data-doctor");
        document.getElementById("edit-date-time").value = btn.getAttribute("data-date-time");
        document.getElementById("edit-type").value = btn.getAttribute("data-type");
        document.getElementById("edit-status").value = btn.getAttribute("data-status");
        
        document.getElementById("edit-symptoms").value = btn.getAttribute("data-symptoms");
        document.getElementById("edit-nurse").value = btn.getAttribute("data-nurse-id");
        document.getElementById("edit-notes").value = btn.getAttribute("data-notes");

        // Clear validation highlights
        document.querySelectorAll("#edit-record-modal .form-group").forEach(el => el.classList.remove("invalid"));

        // Show the Edit Modal
        if (editModal) editModal.classList.remove("hidden");
        lucide.createIcons();
    });
});

// ==========================================================================
// VIEW MODAL TAB NAVIGATION
// ==========================================================================

function switchViewModalTab(tabId) {
    // Tab headers
    document.querySelectorAll(".view-tab-btn").forEach(btn => {
        if (btn.getAttribute("data-view-tab") === tabId) {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });

    // Tab contents
    document.querySelectorAll(".view-tab-content").forEach(content => {
        if (content.id === tabId) {
            content.classList.add("active");
        } else {
            content.classList.remove("active");
        }
    });
}

document.querySelectorAll(".view-tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const tabId = btn.getAttribute("data-view-tab");
        switchViewModalTab(tabId);
    });
});


// ==========================================================================
// THEME SWITCHER LOGIC
// ==========================================================================

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute("data-theme");
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("mv_theme", newTheme);
}

function initTheme() {
    const savedTheme = localStorage.getItem("mv_theme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
}

const themeBtn = document.getElementById("theme-toggle-btn");
if (themeBtn) {
    themeBtn.addEventListener("click", toggleTheme);
}


// ==========================================================================
// APP INITIALIZATION
// ==========================================================================

window.addEventListener("DOMContentLoaded", () => {
    initTheme();
    
    // Set formatted header date
    const displayDateElement = document.getElementById("current-date-display");
    if (displayDateElement) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateObj = new Date("2026-07-15");
        displayDateElement.textContent = dateObj.toLocaleDateString('en-US', options);
    }
    
    // Process URL notifications or parameters
    const urlParams = new URLSearchParams(window.location.search);
    let shouldCleanUrl = false;
    
    if (urlParams.get('error') === 'validation') {
        shouldCleanUrl = true;
        openAddModalHandler();
        validateTabInputs("tab-patient");
        validateTabInputs("tab-vitals");
        validateTabInputs("tab-treatment");
    } else if (urlParams.get('success') === '1') {
        shouldCleanUrl = true;
        alert("Success: Hospital appointment has been booked successfully.");
    } else if (urlParams.get('success_edit') === '1') {
        shouldCleanUrl = true;
        alert("Success: Hospital appointment has been rescheduled successfully.");
    } else if (urlParams.get('success_note') === '1') {
        shouldCleanUrl = true;
    }
    
    // Clear URL parameters to clean up address bar
    if (shouldCleanUrl) {
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        const hash = window.location.hash;
        window.history.replaceState({ path: cleanUrl }, '', cleanUrl + hash);
    }
    
    // Apply theme icon triggers
    lucide.createIcons();
});
