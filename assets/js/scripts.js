// Fonction pour afficher la pop-up après 3 secondes
function showSignupModalAfterDelay() {
    setTimeout(() => {
        const signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
        signupModal.show();
    }, 3000);
}

// Basculer entre les modes affichage et édition (utilisé dans edit_bmc.php)
function toggleBmcEditMode() {
    const displaySection = document.getElementById('bmc-display');
    const editSection = document.getElementById('bmc-edit');
    const editButton = document.getElementById('edit-bmc');
    const cancelButton = document.getElementById('cancel-edit');

    if (editButton && cancelButton) {
        editButton.addEventListener('click', () => {
            displaySection.style.display = 'none';
            editSection.style.display = 'block';
        });

        cancelButton.addEventListener('click', () => {
            displaySection.style.display = 'block';
            editSection.style.display = 'none';
        });
    }
}
// Ajouter un nouveau segment
document.querySelectorAll('.add-segment').forEach(button => {
    button.addEventListener('click', () => {
        const list = button.previousElementSibling;
        const index = list.children.length;
        const newInput = document.createElement('div');
        newInput.className = 'input-group mb-2';
        newInput.innerHTML = `
            <input type="text" class="form-control" name="customer_segments[${index}]" required>
            <button type="button" class="btn btn-danger btn-sm remove-segment"><i class="bi bi-trash"></i></button>
        `;
        list.appendChild(newInput);
    });
});

// Supprimer un segment
document.addEventListener('click', (e) => {
    if (e.target.closest('.remove-segment')) {
        e.target.closest('.input-group').remove();
    }
});