const editButton = document.getElementById("edit-profile-btn");
const cancelButton = document.getElementById("cancel-edit");

const profileInfo = document.getElementById("profile-info");
const editForm = document.getElementById("edit-profile-form");

if (editButton && cancelButton && profileInfo && editForm) {
    if (editForm.querySelector('[role="alert"]')) {
        profileInfo.style.display = "none";
        editForm.style.display = "block";
    }

    editButton.addEventListener("click", function () {
        profileInfo.style.display = "none";
        editForm.style.display = "block";
    });

    cancelButton.addEventListener("click", function () {
        editForm.style.display = "none";
        profileInfo.style.display = "block";
    });
}

const bioTextarea = document.getElementById("profile-bio");
const bioCharacterCount = document.getElementById("bio-character-count");

if (bioTextarea && bioCharacterCount) {
    const updateBioCharacterCount = function () {
        bioCharacterCount.textContent = `${bioTextarea.value.length}/${bioTextarea.maxLength}`;
    };

    bioTextarea.addEventListener("input", updateBioCharacterCount);
    updateBioCharacterCount();
}