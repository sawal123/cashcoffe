document.addEventListener("livewire:navigated", () => {
    console.log("Livewire navigated - inisialisasi ulang file upload");

    const fileInput = document.getElementById("upload-file");
    const previewWrapper = document.querySelector(".uploaded-img");
    const previewImg = document.getElementById("uploaded-img__preview");
    const removeBtn = document.querySelector(".uploaded-img__remove");

    if (!fileInput || !previewWrapper || !previewImg || !removeBtn) return;

    // bersihkan event lama supaya tidak dobel
    fileInput.onchange = null;
    removeBtn.onclick = null;

    // Saat file dipilih
    fileInput.addEventListener("change", function (e) {
        const file = e.target.files[0];
        console.log(file);
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                previewImg.src = event.target.result;
                previewWrapper.classList.remove("hidden"); // tampilkan preview
            };
            reader.readAsDataURL(file);
        }
    });

    // Saat tombol remove ditekan

    removeBtn.addEventListener("click", function () {
        previewImg.src = "/assets/images/user.png"; // gunakan path langsung
        fileInput.value = "";
        previewWrapper.classList.add("hidden");
    });
});

document.addEventListener("reset-upload", () => {
    const fileInput = document.getElementById("upload-file");
    const previewWrapper = document.querySelector(".uploaded-img");
    const previewImg = document.getElementById("uploaded-img__preview");

    if (fileInput && previewWrapper && previewImg) {
        fileInput.value = "";
        previewImg.src = "/storage/assets/images/user.png"; // atau pakai asset()
        previewWrapper.classList.add("hidden");
    }
});
