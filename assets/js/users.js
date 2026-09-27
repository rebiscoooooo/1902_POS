// assets/js/users.js

function openUserModal(user = null) {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('userModalTitle');
    const hint = document.getElementById('passwordHint');
    const passInput = document.getElementById('userPassword');
    
    form.reset();
    
    if (user) {
        title.textContent = 'Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userUsername').value = user.username;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userStatus').value = user.status;
        document.getElementById('userCommissionRate').value = user.commission_rate || 0;
        
        passInput.required = false;
        hint.style.display = 'inline';
    } else {
        title.textContent = 'Add New User';
        document.getElementById('userId').value = '';
        document.getElementById('userCommissionRate').value = '0.00';
        passInput.required = true;
        hint.style.display = 'none';
    }
    
    modal.classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

async function saveUser(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveUser');
    const originalText = btn.innerHTML;
    btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i> Saving...`;
    lucide.createIcons();
    btn.disabled = true;

    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    formData.append('action', 'save');

    try {
        const res = await fetch('/1902_pos/ajax/users.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire({
                title: 'Success',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to save user.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function deleteUser(id) {
    Swal.fire({
        title: 'Delete User?',
        text: "Are you sure you want to delete this user? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            try {
                const res = await fetch('/1902_pos/ajax/users.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'User has been deleted.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to delete user.', 'error');
            }
        }
    });
}

function withdrawBalance(id, maxAmount) {
    Swal.fire({
        title: 'Withdraw Balance',
        input: 'number',
        inputLabel: `Amount to withdraw (Max: ₱${maxAmount.toFixed(2)})`,
        inputValue: maxAmount,
        showCancelButton: true,
        inputValidator: (value) => {
            if (!value || value <= 0) return 'Please enter a valid amount';
            if (value > maxAmount) return 'Amount exceeds balance';
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'withdraw');
            formData.append('id', id);
            formData.append('amount', result.value);
            
            try {
                const res = await fetch('/1902_pos/ajax/users.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Network error occurred.', 'error');
            }
        }
    });
}
