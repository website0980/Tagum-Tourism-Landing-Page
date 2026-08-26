document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('certApplicationForm');
    const renewalFields = document.getElementById('renewalFields');
    const applicationTypeInputs = document.querySelectorAll('input[name="application_type"]');
    const roomTable = document.getElementById('roomTypesTable');
    const amenitiesTable = document.getElementById('amenitiesTable');

    function toggleRenewalFields() {
        const selected = document.querySelector('input[name="application_type"]:checked');
        const isRenewal = selected && selected.value === 'renewal';
        if (renewalFields) {
            renewalFields.classList.toggle('is-visible', isRenewal);
        }
    }

    applicationTypeInputs.forEach((input) => {
        input.addEventListener('change', toggleRenewalFields);
    });
    toggleRenewalFields();

    document.getElementById('addRoomRow')?.addEventListener('click', () => {
        if (!roomTable) return;
        const tbody = roomTable.querySelector('tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="room_type_name[]" class="cert-input cert-table-input"></td>
            <td><input type="text" name="room_type_rate[]" class="cert-input cert-table-input"></td>
            <td><input type="number" name="room_type_number[]" min="0" class="cert-input cert-table-input"></td>
            <td><button type="button" class="cert-btn-secondary cert-remove-row" aria-label="Remove row">×</button></td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('addAmenityRow')?.addEventListener('click', () => {
        if (!amenitiesTable) return;
        const tbody = amenitiesTable.querySelector('tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="amenity_name[]" class="cert-input cert-table-input" placeholder="e.g., Restaurant"></td>
            <td><button type="button" class="cert-btn-secondary cert-remove-row" aria-label="Remove row">×</button></td>
        `;
        tbody.appendChild(row);
    });

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('cert-remove-row')) {
            e.target.closest('tr')?.remove();
        }
    });

    form?.addEventListener('submit', (e) => {
        const selected = document.querySelector('input[name="application_type"]:checked');
        if (!selected) {
            e.preventDefault();
            alert('Please select New Application or Renewal.');
        }
    });
});

function downloadForm() {
    window.print();
}

function downloadBlankForm() {
    const selectedTrack = document.querySelector('input[name="certification_track"]:checked');
    const trackValue = selectedTrack ? (selectedTrack.value === 'dot_accredited' ? 'dot' : 'local') : 'local';
    const printWindow = window.open('accommodation-form-compact.php?track=' + trackValue, '_blank');

    if (printWindow) {
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
            }, 500);
        };
    } else {
        alert('Please allow popups to download the form.');
    }
}
